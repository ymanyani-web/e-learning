<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use PDO;

class AdminController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function overview(): void
    {
        Auth::requireRole('ADMIN');
        $courseStatus = $this->query('SELECT status, COUNT(*) AS total FROM courses GROUP BY status ORDER BY status');
        $maximum = max(array_map(fn(array $status): int => (int) $status['total'], $courseStatus) ?: [1]);

        $this->render('overview', [
            'stats' => [
                'students' => $this->count('users WHERE role = "STUDENT" AND status = 1'),
                'teachers' => $this->count('users WHERE role = "TEACHER" AND status = 1'),
                'classes' => $this->count('school_classes WHERE status = 1'),
                'courses' => $this->count('courses'),
            ],
            'courseStatus' => $courseStatus,
            'courseStatusMaximum' => $maximum,
            'newStudents' => $this->query('SELECT id, first_name, last_name, email, created_at FROM users WHERE role = "STUDENT" ORDER BY created_at DESC LIMIT 5'),
            'upcomingExams' => $this->query('SELECT e.title, e.start_at, c.title AS course_title, cl.name AS class_name FROM exams e JOIN courses c ON c.id = e.course_id JOIN school_classes cl ON cl.id = c.class_id WHERE e.start_at IS NOT NULL AND e.start_at >= NOW() ORDER BY e.start_at LIMIT 5'),
            'unreadNotifications' => $this->query('SELECT title, message, type, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5', [Auth::id()]),
        ]);
    }

    public function students(): void
    {
        Auth::requireRole('ADMIN');
        $id = (int) Request::input('id');
        if ($id > 0) {
            $this->studentDetail($id);
            return;
        }

        $search = trim((string) Request::input('q', ''));
        $classId = (int) Request::input('class_id');
        $conditions = ['u.role = "STUDENT"'];
        $parameters = [];
        if ($search !== '') {
            $conditions[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
            $parameters = array_merge($parameters, array_fill(0, 3, '%' . $search . '%'));
        }
        if ($classId > 0) {
            $conditions[] = 'EXISTS (SELECT 1 FROM enrollments ef WHERE ef.student_id = u.id AND ef.class_id = ? AND ef.status = "ACTIVE")';
            $parameters[] = $classId;
        }

        $this->render('students', [
            'filters' => ['q' => $search, 'class_id' => $classId],
            'classes' => $this->query('SELECT id, name, academic_year FROM school_classes WHERE status = 1 ORDER BY academic_year DESC, name'),
            'students' => $this->query('SELECT u.id, u.first_name, u.last_name, u.email, u.created_at, GROUP_CONCAT(DISTINCT cl.name ORDER BY cl.name SEPARATOR ", ") AS classes FROM users u LEFT JOIN enrollments e ON e.student_id = u.id AND e.status = "ACTIVE" LEFT JOIN school_classes cl ON cl.id = e.class_id WHERE ' . implode(' AND ', $conditions) . ' GROUP BY u.id ORDER BY u.created_at DESC LIMIT 10', $parameters),
        ]);
    }

    public function teachers(): void
    {
        Auth::requireRole('ADMIN');
        $id = (int) Request::input('id');
        if ($id > 0) {
            $this->teacherDetail($id);
            return;
        }

        $search = trim((string) Request::input('q', ''));
        $classId = (int) Request::input('class_id');
        $conditions = ['u.role = "TEACHER"'];
        $parameters = [];
        if ($search !== '') {
            $conditions[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
            $parameters = array_merge($parameters, array_fill(0, 3, '%' . $search . '%'));
        }
        if ($classId > 0) {
            $conditions[] = 'EXISTS (SELECT 1 FROM courses cf WHERE cf.teacher_id = u.id AND cf.class_id = ?)';
            $parameters[] = $classId;
        }

        $this->render('teachers', [
            'filters' => ['q' => $search, 'class_id' => $classId],
            'classes' => $this->query('SELECT id, name, academic_year FROM school_classes WHERE status = 1 ORDER BY academic_year DESC, name'),
            'teachers' => $this->query('SELECT u.id, u.first_name, u.last_name, u.email, u.created_at, COUNT(DISTINCT c.id) AS course_count, GROUP_CONCAT(DISTINCT cl.name ORDER BY cl.name SEPARATOR ", ") AS classes FROM users u LEFT JOIN courses c ON c.teacher_id = u.id LEFT JOIN school_classes cl ON cl.id = c.class_id WHERE ' . implode(' AND ', $conditions) . ' GROUP BY u.id ORDER BY u.created_at DESC LIMIT 10', $parameters),
        ]);
    }

    public function classes(): void
    {
        Auth::requireRole('ADMIN');
        $id = (int) Request::input('id');
        if ($id > 0) {
            $this->classDetail($id);
            return;
        }

        $search = trim((string) Request::input('q', ''));
        $year = trim((string) Request::input('year', ''));
        $conditions = ['1 = 1'];
        $parameters = [];
        if ($search !== '') {
            $conditions[] = 'name LIKE ?';
            $parameters[] = '%' . $search . '%';
        }
        if ($year !== '') {
            $conditions[] = 'academic_year = ?';
            $parameters[] = $year;
        }
        $this->render('classes', [
            'filters' => ['q' => $search, 'year' => $year],
            'years' => $this->query('SELECT DISTINCT academic_year FROM school_classes ORDER BY academic_year DESC'),
            'classes' => $this->query('SELECT cl.*, (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id AND e.status = "ACTIVE") AS student_count, (SELECT COUNT(*) FROM courses c WHERE c.class_id = cl.id) AS course_count FROM school_classes cl WHERE ' . implode(' AND ', $conditions) . ' ORDER BY cl.created_at DESC LIMIT 10', $parameters),
            'classOptions' => $this->query('SELECT id, name, academic_year FROM school_classes WHERE status = 1 ORDER BY academic_year DESC, name'),
            'students' => $this->query('SELECT id, first_name, last_name FROM users WHERE role = "STUDENT" AND status = 1 ORDER BY last_name, first_name'),
            'teachers' => $this->query('SELECT id, first_name, last_name FROM users WHERE role = "TEACHER" AND status = 1 ORDER BY last_name, first_name'),
            'subjects' => $this->query('SELECT id, name FROM subjects ORDER BY name'),
            'enrollments' => $this->query('SELECT e.id, e.academic_year, CONCAT(u.first_name, " ", u.last_name) AS student_name, cl.name AS class_name FROM enrollments e JOIN users u ON u.id = e.student_id JOIN school_classes cl ON cl.id = e.class_id WHERE e.status = "ACTIVE" ORDER BY e.id DESC LIMIT 6'),
        ]);
    }

    public function subjects(): void
    {
        Auth::requireRole('ADMIN');
        $id = (int) Request::input('id');
        if ($id > 0) {
            $this->subjectDetail($id);
            return;
        }

        $search = trim((string) Request::input('q', ''));
        $conditions = ['1 = 1'];
        $parameters = [];
        if ($search !== '') {
            $conditions[] = '(s.name LIKE ? OR s.code LIKE ?)';
            $parameters = ['%' . $search . '%', '%' . $search . '%'];
        }
        $this->render('subjects', [
            'filters' => ['q' => $search],
            'subjects' => $this->query('SELECT s.*, COUNT(DISTINCT c.id) AS course_count, COUNT(DISTINCT c.teacher_id) AS teacher_count FROM subjects s LEFT JOIN courses c ON c.subject_id = s.id WHERE ' . implode(' AND ', $conditions) . ' GROUP BY s.id ORDER BY s.created_at DESC LIMIT 10', $parameters),
        ]);
    }

    private function studentDetail(int $id): void
    {
        $student = $this->query('SELECT id, first_name, last_name, email, created_at FROM users WHERE id = ? AND role = "STUDENT"', [$id])[0] ?? null;
        if ($student === null) {
            Response::abort(404, 'Student not found.');
        }
        $this->render('student-detail', [
            'student' => $student,
            'courses' => $this->query('SELECT DISTINCT c.id, c.title, s.name AS subject_name, cl.name AS class_name, CONCAT(t.first_name, " ", t.last_name) AS teacher_name FROM courses c JOIN subjects s ON s.id = c.subject_id JOIN school_classes cl ON cl.id = c.class_id JOIN users t ON t.id = c.teacher_id JOIN enrollments e ON e.class_id = c.class_id WHERE e.student_id = ? AND e.status = "ACTIVE" ORDER BY c.title', [$id]),
            'exams' => $this->query('SELECT e.title, e.status, e.start_at, e.end_at, c.title AS course_title FROM exams e JOIN courses c ON c.id = e.course_id JOIN enrollments n ON n.class_id = c.class_id WHERE n.student_id = ? AND n.status = "ACTIVE" ORDER BY e.start_at DESC, e.id DESC', [$id]),
            'attendance' => $this->query('SELECT ar.status, ar.comment, ats.session_date, c.title AS course_title FROM attendance_records ar JOIN attendance_sessions ats ON ats.id = ar.attendance_session_id JOIN courses c ON c.id = ats.course_id WHERE ar.student_id = ? ORDER BY ats.session_date DESC LIMIT 10', [$id]),
            'grades' => $this->query('SELECT g.type, g.score, g.max_score, g.created_at, c.title AS course_title FROM grades g JOIN courses c ON c.id = g.course_id WHERE g.student_id = ? ORDER BY g.created_at DESC LIMIT 10', [$id]),
            'feedback' => $this->query('SELECT a.title AS assignment_title, s.teacher_feedback, s.score, s.submitted_at FROM assignment_submissions s JOIN assignments a ON a.id = s.assignment_id WHERE s.student_id = ? AND s.teacher_feedback IS NOT NULL AND s.teacher_feedback <> "" ORDER BY s.submitted_at DESC LIMIT 10', [$id]),
            'documents' => $this->query('SELECT r.title, r.type, r.file_path, r.url, c.title AS course_title FROM course_resources r JOIN course_chapters ch ON ch.id = r.chapter_id JOIN courses c ON c.id = ch.course_id JOIN enrollments n ON n.class_id = c.class_id WHERE n.student_id = ? AND n.status = "ACTIVE" ORDER BY r.created_at DESC LIMIT 10', [$id]),
            'calendar' => $this->studentCalendar($id),
        ]);
    }

    private function teacherDetail(int $id): void
    {
        $teacher = $this->query('SELECT id, first_name, last_name, email, created_at FROM users WHERE id = ? AND role = "TEACHER"', [$id])[0] ?? null;
        if ($teacher === null) {
            Response::abort(404, 'Professor not found.');
        }
        $this->render('teacher-detail', [
            'teacher' => $teacher,
            'courses' => $this->query('SELECT c.id, c.title, c.status, s.name AS subject_name, cl.name AS class_name FROM courses c JOIN subjects s ON s.id = c.subject_id JOIN school_classes cl ON cl.id = c.class_id WHERE c.teacher_id = ? ORDER BY cl.name, s.name', [$id]),
            'documents' => $this->query('SELECT r.title, r.type, r.file_path, r.url, c.title AS course_title FROM course_resources r JOIN course_chapters ch ON ch.id = r.chapter_id JOIN courses c ON c.id = ch.course_id WHERE c.teacher_id = ? ORDER BY r.created_at DESC LIMIT 10', [$id]),
            'calendar' => $this->teacherCalendar($id),
        ]);
    }

    private function classDetail(int $id): void
    {
        $class = $this->query('SELECT * FROM school_classes WHERE id = ?', [$id])[0] ?? null;
        if ($class === null) {
            Response::abort(404, 'Class not found.');
        }
        $this->render('class-detail', [
            'class' => $class,
            'students' => $this->query('SELECT u.id, u.first_name, u.last_name, u.email FROM enrollments e JOIN users u ON u.id = e.student_id WHERE e.class_id = ? AND e.status = "ACTIVE" ORDER BY u.last_name, u.first_name', [$id]),
            'courses' => $this->query('SELECT c.id, c.title, c.status, s.name AS subject_name, CONCAT(u.first_name, " ", u.last_name) AS teacher_name FROM courses c JOIN subjects s ON s.id = c.subject_id JOIN users u ON u.id = c.teacher_id WHERE c.class_id = ? ORDER BY c.title', [$id]),
            'calendar' => $this->classCalendar($id),
        ]);
    }

    private function subjectDetail(int $id): void
    {
        $subject = $this->query('SELECT * FROM subjects WHERE id = ?', [$id])[0] ?? null;
        if ($subject === null) {
            Response::abort(404, 'Subject not found.');
        }
        $this->render('subject-detail', [
            'subject' => $subject,
            'courses' => $this->query('SELECT c.id, c.title, c.status, cl.name AS class_name, CONCAT(u.first_name, " ", u.last_name) AS teacher_name FROM courses c JOIN school_classes cl ON cl.id = c.class_id JOIN users u ON u.id = c.teacher_id WHERE c.subject_id = ? ORDER BY c.title', [$id]),
            'documents' => $this->query('SELECT r.title, r.type, r.file_path, r.url, c.title AS course_title FROM course_resources r JOIN course_chapters ch ON ch.id = r.chapter_id JOIN courses c ON c.id = ch.course_id WHERE c.subject_id = ? ORDER BY r.created_at DESC LIMIT 10', [$id]),
            'calendar' => $this->subjectCalendar($id),
        ]);
    }

    private function studentCalendar(int $id): array { return $this->calendar('JOIN enrollments n ON n.class_id = c.class_id WHERE n.student_id = ? AND n.status = "ACTIVE"', [$id]); }
    private function teacherCalendar(int $id): array { return $this->calendar('WHERE c.teacher_id = ?', [$id]); }
    private function classCalendar(int $id): array { return $this->calendar('WHERE c.class_id = ?', [$id]); }
    private function subjectCalendar(int $id): array { return $this->calendar('WHERE c.subject_id = ?', [$id]); }

    private function calendar(string $filter, array $parameters): array
    {
        $attendance = $this->query('SELECT ats.session_date AS event_date, ats.start_time, ats.end_time, c.title AS course_title, "CLASS" AS event_type FROM attendance_sessions ats JOIN courses c ON c.id = ats.course_id ' . $filter . ' ORDER BY ats.session_date DESC LIMIT 10', $parameters);
        $exams = $this->query('SELECT DATE(e.start_at) AS event_date, TIME(e.start_at) AS start_time, TIME(e.end_at) AS end_time, c.title AS course_title, "EXAM" AS event_type FROM exams e JOIN courses c ON c.id = e.course_id ' . $filter . ' AND e.start_at IS NOT NULL ORDER BY e.start_at DESC LIMIT 10', $parameters);
        $events = array_merge($attendance, $exams);
        usort($events, fn(array $left, array $right): int => strcmp((string) $right['event_date'], (string) $left['event_date']));
        return array_slice($events, 0, 12);
    }

    private function render(string $section, array $data = []): void
    {
        View::render('portal/admin-area', [
            'user' => Auth::user(),
            'section' => $section,
            'message' => Session::consumeFlash('success'),
            'error' => Session::consumeFlash('error'),
        ] + $data);
    }

    private function count(string $from): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM {$from}")->fetchColumn();
    }

    private function query(string $sql, array $parameters = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }
}
