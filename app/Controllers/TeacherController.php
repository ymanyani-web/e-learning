<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use PDO;

class TeacherController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function dashboard(): void
    {
        Auth::requireRole('TEACHER');
        $status = $this->query('SELECT status, COUNT(*) AS total FROM courses WHERE teacher_id = ? GROUP BY status', [Auth::id()]);
        $maximum = max(array_map(fn(array $item): int => (int) $item['total'], $status) ?: [1]);
        $this->render('dashboard', [
            'stats' => [
                'courses' => $this->count('courses WHERE teacher_id = ?', [Auth::id()]),
                'classes' => $this->count('SELECT DISTINCT class_id FROM courses WHERE teacher_id = ?', [Auth::id()]),
                'students' => $this->count('SELECT DISTINCT e.student_id FROM enrollments e JOIN courses c ON c.class_id = e.class_id WHERE c.teacher_id = ? AND e.status = "ACTIVE"', [Auth::id()]),
                'slides' => $this->count('SELECT ch.id FROM course_chapters ch JOIN courses c ON c.id = ch.course_id WHERE c.teacher_id = ?', [Auth::id()]),
            ],
            'status' => $status,
            'maximum' => $maximum,
            'calendar' => $this->calendar(),
            'courses' => $this->query('SELECT c.id, c.title, c.status, s.name AS subject_name, cl.name AS class_name, COUNT(DISTINCT ch.id) AS slide_count FROM courses c JOIN subjects s ON s.id = c.subject_id JOIN school_classes cl ON cl.id = c.class_id LEFT JOIN course_chapters ch ON ch.course_id = c.id WHERE c.teacher_id = ? GROUP BY c.id ORDER BY c.updated_at DESC LIMIT 5', [Auth::id()]),
            'notifications' => $this->query('SELECT title, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5', [Auth::id()]),
        ]);
    }

    public function classes(): void
    {
        Auth::requireRole('TEACHER');
        $classes = $this->query('SELECT cl.id, cl.name, cl.academic_year, COUNT(DISTINCT e.student_id) AS student_count, COUNT(DISTINCT c.id) AS course_count FROM school_classes cl JOIN courses c ON c.class_id = cl.id LEFT JOIN enrollments e ON e.class_id = cl.id AND e.status = "ACTIVE" WHERE c.teacher_id = ? GROUP BY cl.id ORDER BY cl.academic_year DESC, cl.name', [Auth::id()]);
        $students = $this->query('SELECT DISTINCT c.class_id, u.id, u.first_name, u.last_name, u.email FROM courses c JOIN enrollments e ON e.class_id = c.class_id AND e.status = "ACTIVE" JOIN users u ON u.id = e.student_id WHERE c.teacher_id = ? ORDER BY u.last_name, u.first_name', [Auth::id()]);
        $courses = $this->query('SELECT c.id, c.class_id, c.title, s.name AS subject_name FROM courses c JOIN subjects s ON s.id = c.subject_id WHERE c.teacher_id = ? ORDER BY s.name, c.title', [Auth::id()]);
        $sessions = $this->query('SELECT ats.*, c.class_id, c.title AS course_title FROM attendance_sessions ats JOIN courses c ON c.id = ats.course_id WHERE c.teacher_id = ? ORDER BY ats.session_date DESC, ats.start_time DESC', [Auth::id()]);
        $records = $this->query('SELECT ar.*, CONCAT(u.first_name, " ", u.last_name) AS student_name FROM attendance_records ar JOIN attendance_sessions ats ON ats.id = ar.attendance_session_id JOIN courses c ON c.id = ats.course_id JOIN users u ON u.id = ar.student_id WHERE c.teacher_id = ? ORDER BY u.last_name, u.first_name', [Auth::id()]);
        $this->render('classes', [
            'classes' => $classes,
            'classStudents' => $this->groupBy($students, 'class_id'),
            'classCourses' => $this->groupBy($courses, 'class_id'),
            'classSessions' => $this->groupBy($sessions, 'class_id'),
            'sessionRecords' => $this->groupBy($records, 'attendance_session_id'),
            'calendar' => $this->calendar(),
        ]);
    }

    public function courses(): void
    {
        Auth::requireRole('TEACHER');
        $this->render('courses', [
            'courses' => $this->query('SELECT c.*, s.name AS subject_name, cl.name AS class_name, COUNT(DISTINCT ch.id) AS slide_count, COUNT(DISTINCT e.id) AS exam_count FROM courses c JOIN subjects s ON s.id = c.subject_id JOIN school_classes cl ON cl.id = c.class_id LEFT JOIN course_chapters ch ON ch.course_id = c.id LEFT JOIN exams e ON e.course_id = c.id WHERE c.teacher_id = ? GROUP BY c.id ORDER BY c.updated_at DESC', [Auth::id()]),
        ]);
    }

    public function grading(): void
    {
        Auth::requireRole('TEACHER');
        $examId = (int) Request::input('id');
        $exam = $this->query('SELECT e.*, c.title AS course_title FROM exams e JOIN courses c ON c.id = e.course_id WHERE e.id = ? AND c.teacher_id = ?', [$examId, Auth::id()])[0] ?? null;
        if ($exam === null) {
            Response::abort(404, 'Exam not found.');
        }

        public function manualMarks(): void
        {
            Auth::requireRole('TEACHER');
            $exam = $this->manualExam((int) Request::input('id'));
            $students = $this->query('SELECT u.id, u.first_name, u.last_name, u.email, g.id AS grade_id, g.score FROM enrollments n JOIN users u ON u.id = n.student_id LEFT JOIN grades g ON g.student_id = u.id AND g.course_id = ? AND g.type = "EXAM" AND g.reference_id = ? WHERE n.class_id = ? AND n.status = "ACTIVE" ORDER BY u.last_name, u.first_name', [$exam['course_id'], $exam['id'], $exam['class_id']]);
            View::render('portal/exam-marks', [
                'user' => Auth::user(),
                'exam' => $exam,
                'students' => $students,
                'message' => Session::consumeFlash('success'),
                'error' => Session::consumeFlash('error'),
            ]);
        }

        public function saveManualMark(): void
        {
            Auth::requireRole('TEACHER'); Csrf::validate();
            $exam = $this->manualExam((int) Request::input('exam_id'));
            $studentId = (int) Request::input('student_id');
            $student = $this->query('SELECT student_id FROM enrollments WHERE class_id = ? AND student_id = ? AND status = "ACTIVE"', [$exam['class_id'], $studentId])[0] ?? null;
            if ($student === null) {
                Response::abort(404, 'Student not found in this exam class.');
            }
            $score = (float) Request::input('score', -1);
            if ($score < 0 || $score > (float) $exam['total_points']) {
                $this->fail('/teacher/exam/marks?id=' . $exam['id'], 'Enter a mark between 0 and the exam total.');
            }
            $grade = $this->query('SELECT id FROM grades WHERE student_id = ? AND course_id = ? AND type = "EXAM" AND reference_id = ? ORDER BY id DESC LIMIT 1', [$studentId, $exam['course_id'], $exam['id']])[0] ?? null;
            if ($grade === null) {
                $this->pdo->prepare('INSERT INTO grades (student_id, course_id, type, reference_id, score, max_score) VALUES (?, ?, "EXAM", ?, ?, ?)')->execute([$studentId, $exam['course_id'], $exam['id'], $score, $exam['total_points']]);
            } else {
                $this->pdo->prepare('UPDATE grades SET score = ?, max_score = ? WHERE id = ?')->execute([$score, $exam['total_points'], $grade['id']]);
            }
            $this->success('/teacher/exam/marks?id=' . $exam['id'], 'Manual exam mark saved.');
        }

        public function createAttendanceSession(): void
        {
            Auth::requireRole('TEACHER'); Csrf::validate();
            $course = $this->query('SELECT id, class_id FROM courses WHERE id = ? AND class_id = ? AND teacher_id = ?', [(int) Request::input('course_id'), (int) Request::input('class_id'), Auth::id()])[0] ?? null;
            if ($course === null) {
                Response::abort(404, 'Course not found for this class.');
            }
            $date = trim((string) Request::input('session_date'));
            $start = trim((string) Request::input('start_time'));
            $end = trim((string) Request::input('end_time'));
            if (\DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') !== $date || !preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end) || $end <= $start) {
                $this->fail('/teacher/classes', 'Choose a valid date and start/end time.');
            }
            $students = $this->query('SELECT student_id FROM enrollments WHERE class_id = ? AND status = "ACTIVE"', [$course['class_id']]);
            $present = array_flip(array_map('intval', (array) ($_POST['present_students'] ?? [])));
            $this->pdo->beginTransaction();
            try {
                $this->pdo->prepare('INSERT INTO attendance_sessions (course_id, session_date, start_time, end_time, created_by) VALUES (?, ?, ?, ?, ?)')->execute([$course['id'], $date, $start . ':00', $end . ':00', Auth::id()]);
                $sessionId = (int) $this->pdo->lastInsertId();
                $statement = $this->pdo->prepare('INSERT INTO attendance_records (attendance_session_id, student_id, status) VALUES (?, ?, ?)');
                foreach ($students as $student) {
                    $statement->execute([$sessionId, $student['student_id'], isset($present[(int) $student['student_id']]) ? 'PRESENT' : 'ABSENT']);
                }
                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                throw $e;
            }
            $this->success('/teacher/classes', 'Attendance session saved.');
        }

        public function updateAttendanceRecord(): void
        {
            Auth::requireRole('TEACHER'); Csrf::validate();
            $record = $this->ownedAttendanceRecord((int) Request::input('record_id'));
            $status = (string) Request::input('status');
            if (!in_array($status, ['PRESENT', 'ABSENT', 'LATE', 'EXCUSED'], true)) {
                $this->fail('/teacher/classes', 'Choose a valid attendance status.');
            }
            $this->pdo->prepare('UPDATE attendance_records SET status = ?, comment = ? WHERE id = ?')->execute([$status, trim((string) Request::input('comment')) ?: null, $record['id']]);
            $this->success('/teacher/classes', 'Attendance record updated.');
        }

        public function removeAttendanceRecord(): void
        {
            Auth::requireRole('TEACHER'); Csrf::validate();
            $record = $this->ownedAttendanceRecord((int) Request::input('record_id'));
            $this->pdo->prepare('DELETE FROM attendance_records WHERE id = ?')->execute([$record['id']]);
            $this->success('/teacher/classes', 'Attendance record removed.');
        }
        $this->render('grading', [
            'exam' => $exam,
            'answers' => $this->query('SELECT a.id, a.text_answer, a.points_awarded, q.question, q.points AS max_points, CONCAT(u.first_name, " ", u.last_name) AS student_name, at.id AS attempt_id FROM exam_answers a JOIN exam_questions q ON q.id = a.question_id JOIN exam_attempts at ON at.id = a.attempt_id JOIN users u ON u.id = at.student_id WHERE q.exam_id = ? AND q.type = "TEXT" ORDER BY at.submitted_at DESC', [$examId]),
        ]);
    }

    private function calendar(): array
    {
        $classes = $this->query('SELECT ats.session_date AS event_date, ats.start_time, c.title AS course_title, "CLASS" AS event_type FROM attendance_sessions ats JOIN courses c ON c.id = ats.course_id WHERE c.teacher_id = ? ORDER BY ats.session_date DESC LIMIT 8', [Auth::id()]);
        $exams = $this->query('SELECT DATE(e.start_at) AS event_date, TIME(e.start_at) AS start_time, c.title AS course_title, "EXAM" AS event_type FROM exams e JOIN courses c ON c.id = e.course_id WHERE c.teacher_id = ? AND e.start_at IS NOT NULL ORDER BY e.start_at DESC LIMIT 8', [Auth::id()]);
        $events = array_merge($classes, $exams);
        usort($events, fn(array $left, array $right): int => strcmp((string) $right['event_date'], (string) $left['event_date']));
        return array_slice($events, 0, 10);
    }

    private function manualExam(int $id): array
    {
        $exam = $this->query('SELECT e.*, c.title AS course_title, c.class_id FROM exams e JOIN courses c ON c.id = e.course_id WHERE e.id = ? AND e.delivery_mode = "MANUAL" AND c.teacher_id = ?', [$id, Auth::id()])[0] ?? null;
        if ($exam === null) {
            Response::abort(404, 'Manual exam not found.');
        }
        return $exam;
    }

    private function ownedAttendanceRecord(int $id): array
    {
        $record = $this->query('SELECT ar.id FROM attendance_records ar JOIN attendance_sessions ats ON ats.id = ar.attendance_session_id JOIN courses c ON c.id = ats.course_id WHERE ar.id = ? AND c.teacher_id = ?', [$id, Auth::id()])[0] ?? null;
        if ($record === null) {
            Response::abort(404, 'Attendance record not found.');
        }
        return $record;
    }

    private function groupBy(array $rows, string $key): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row[$key]][] = $row;
        }
        return $grouped;
    }

    private function render(string $section, array $data): void
    {
        View::render('portal/teacher-area', [
            'user' => Auth::user(),
            'section' => $section,
            'message' => Session::consumeFlash('success'),
            'error' => Session::consumeFlash('error'),
        ] + $data);
    }

    private function query(string $sql, array $parameters = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    private function count(string $from, array $parameters): int
    {
        $sql = str_starts_with($from, 'SELECT') ? "SELECT COUNT(*) AS total FROM ({$from}) AS result" : "SELECT COUNT(*) AS total FROM {$from}";
        return (int) $this->query($sql, $parameters)[0]['total'];
    }

    private function success(string $path, string $message): never
    {
        Session::flash('success', $message);
        Response::redirect($path);
    }

    private function fail(string $path, string $message): never
    {
        Session::flash('error', $message);
        Response::redirect($path);
    }
}
