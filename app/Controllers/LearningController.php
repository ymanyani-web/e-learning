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
use App\Services\AiService;
use PDO;
use RuntimeException;

class LearningController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function admin(): void
    {
        Auth::requireRole('ADMIN');
        View::render('portal/admin', [
            'user' => Auth::user(),
            'stats' => [
                'students' => $this->count('users WHERE role = "STUDENT" AND status = 1'),
                'teachers' => $this->count('users WHERE role = "TEACHER" AND status = 1'),
                'classes' => $this->count('school_classes WHERE status = 1'),
                'courses' => $this->count('courses'),
            ],
            'classes' => $this->pdo->query('SELECT * FROM school_classes ORDER BY academic_year DESC, name')->fetchAll(),
            'subjects' => $this->pdo->query('SELECT * FROM subjects ORDER BY name')->fetchAll(),
            'teachers' => $this->usersByRole('TEACHER'),
            'students' => $this->usersByRole('STUDENT'),
            'courses' => $this->pdo->query('SELECT c.*, s.name AS subject_name, cl.name AS class_name, CONCAT(u.first_name, " ", u.last_name) AS teacher_name FROM courses c JOIN subjects s ON s.id = c.subject_id JOIN school_classes cl ON cl.id = c.class_id JOIN users u ON u.id = c.teacher_id ORDER BY c.id DESC')->fetchAll(),
            'enrollments' => $this->query('SELECT e.id, e.academic_year, e.status, CONCAT(u.first_name, " ", u.last_name) AS student_name, cl.name AS class_name FROM enrollments e JOIN users u ON u.id = e.student_id JOIN school_classes cl ON cl.id = e.class_id WHERE e.status = "ACTIVE" ORDER BY e.id DESC LIMIT 8'),
            'studentSummaries' => $this->query('SELECT u.id, CONCAT(u.first_name, " ", u.last_name) AS student_name, u.email, (SELECT GROUP_CONCAT(DISTINCT cl.name ORDER BY cl.name SEPARATOR ", ") FROM enrollments e JOIN school_classes cl ON cl.id = e.class_id WHERE e.student_id = u.id AND e.status = "ACTIVE") AS classes, (SELECT ROUND(AVG(g.score / NULLIF(g.max_score, 0) * 100), 1) FROM grades g WHERE g.student_id = u.id) AS grade_average, (SELECT COUNT(*) FROM attendance_records ar WHERE ar.student_id = u.id) AS attendance_entries FROM users u WHERE u.role = "STUDENT" AND u.status = 1 ORDER BY u.last_name, u.first_name LIMIT 8'),
            'message' => Session::consumeFlash('success'),
            'error' => Session::consumeFlash('error'),
        ]);
    }

    public function createClass(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $name = $this->required('name');
        $year = $this->required('academic_year');
        $this->pdo->prepare('INSERT INTO school_classes (name, description, academic_year) VALUES (?, ?, ?)')->execute([$name, $this->text('description'), $year]);
        $this->success('/admin', 'Class created.');
    }

    public function createSubject(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $name = $this->required('name');
        $code = $this->text('code');
        $this->pdo->prepare('INSERT INTO subjects (name, code, description, coefficient) VALUES (?, ?, ?, ?)')->execute([$name, $code ?: null, $this->text('description'), max(0, (float) Request::input('coefficient', 1))]);
        $this->success('/admin', 'Subject created.');
    }

    public function createCourse(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $subject = (int) Request::input('subject_id');
        $teacher = (int) Request::input('teacher_id');
        $class = (int) Request::input('class_id');
        if (!$this->exists('subjects', $subject) || !$this->userHasRole($teacher, 'TEACHER') || !$this->exists('school_classes', $class)) {
            $this->error('/admin', 'Choose a valid subject, professor, and class.');
        }
        $title = $this->query('SELECT name FROM subjects WHERE id = ?', [$subject])[0]['name'];
        $status = in_array(Request::input('status'), ['DRAFT', 'PUBLISHED'], true) ? Request::input('status') : 'DRAFT';
        $this->pdo->prepare('INSERT INTO courses (subject_id, teacher_id, class_id, title, description, status) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$subject, $teacher, $class, $title, $this->text('description'), $status]);
        $this->success('/admin', 'Course created and assigned.');
    }

    public function enrollStudent(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $student = (int) Request::input('student_id');
        $class = (int) Request::input('class_id');
        if (!$this->userHasRole($student, 'STUDENT') || !$this->exists('school_classes', $class)) {
            $this->error('/admin', 'Choose a valid student and class.');
        }
        $year = $this->required('academic_year');
        $this->pdo->prepare('INSERT INTO enrollments (student_id, class_id, academic_year) VALUES (?, ?, ?)')->execute([$student, $class, $year]);
        $this->success('/admin', 'Student enrolled.');
    }

    public function removeEnrollment(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $enrollmentId = (int) Request::input('enrollment_id');
        $statement = $this->pdo->prepare('DELETE FROM enrollments WHERE id = ?');
        $statement->execute([$enrollmentId]);
        if ($statement->rowCount() !== 1) {
            $this->error('/admin', 'Enrollment was not found.');
        }
        $this->success('/admin', 'Student removed from the class.');
    }

    public function studentRecords(): void
    {
        Auth::requireRole('ADMIN');
        $studentId = (int) Request::input('id');
        $student = $this->query('SELECT id, first_name, last_name, email FROM users WHERE id = ? AND role = "STUDENT"', [$studentId])[0] ?? null;
        if ($student === null) {
            Response::abort(404, 'Student not found.');
        }

        View::render('portal/student-record', [
            'user' => Auth::user(),
            'student' => $student,
            'enrollments' => $this->query('SELECT cl.name, cl.academic_year, e.status, e.enrolled_at FROM enrollments e JOIN school_classes cl ON cl.id = e.class_id WHERE e.student_id = ? ORDER BY e.enrolled_at DESC', [$studentId]),
            'grades' => $this->query('SELECT g.type, g.score, g.max_score, g.created_at, c.title AS course_title FROM grades g JOIN courses c ON c.id = g.course_id WHERE g.student_id = ? ORDER BY g.created_at DESC', [$studentId]),
            'attendance' => $this->query('SELECT ar.status, ar.comment, ats.session_date, c.title AS course_title FROM attendance_records ar JOIN attendance_sessions ats ON ats.id = ar.attendance_session_id JOIN courses c ON c.id = ats.course_id WHERE ar.student_id = ? ORDER BY ats.session_date DESC', [$studentId]),
        ]);
    }

    public function teacher(): void
    {
        Auth::requireRole('TEACHER');
        $courses = $this->query('SELECT c.*, s.name AS subject_name, cl.name AS class_name FROM courses c JOIN subjects s ON s.id = c.subject_id JOIN school_classes cl ON cl.id = c.class_id WHERE c.teacher_id = ? ORDER BY c.id DESC', [Auth::id()]);
        View::render('portal/teacher', ['user' => Auth::user(), 'courses' => $courses, 'message' => Session::consumeFlash('success'), 'error' => Session::consumeFlash('error')]);
    }

    public function courseEditor(): void
    {
        Auth::requireRole('TEACHER');
        $course = $this->teacherCourse((int) Request::input('id'));
        $chapters = $this->query('SELECT * FROM course_chapters WHERE course_id = ? ORDER BY position, id', [$course['id']]);
        $exams = $this->query('SELECT * FROM exams WHERE course_id = ? ORDER BY id DESC', [$course['id']]);
        View::render('portal/course-editor', compact('course', 'chapters', 'exams') + ['message' => Session::consumeFlash('success'), 'error' => Session::consumeFlash('error')]);
    }

    public function saveChapter(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $course = $this->teacherCourse((int) Request::input('course_id'));
        $content = $this->text('content');
        if (Request::input('generate_ai') === '1') {
            try {
                $content = (new AiService())->generateLesson($course['subject_name'], $this->required('title'), $content);
            } catch (RuntimeException $e) {
                $this->error('/teacher/course?id=' . $course['id'], $e->getMessage());
            }
        }
        if ($content === '') $this->error('/teacher/course?id=' . $course['id'], 'Lesson content is required.');
        $position = (int) $this->pdo->query('SELECT COALESCE(MAX(position), 0) + 1 FROM course_chapters WHERE course_id = ' . (int) $course['id'])->fetchColumn();
        $status = Request::input('status') === 'PUBLISHED' ? 'PUBLISHED' : 'DRAFT';
        $this->pdo->prepare('INSERT INTO course_chapters (course_id, title, content, position, status) VALUES (?, ?, ?, ?, ?)')->execute([$course['id'], $this->required('title'), $content, $position, $status]);
        $this->success('/teacher/course?id=' . $course['id'], 'Chapter saved.');
    }

    public function createExam(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $course = $this->teacherCourse((int) Request::input('course_id'));
        $status = Request::input('status') === 'PUBLISHED' ? 'PUBLISHED' : 'DRAFT';
        $this->pdo->prepare('INSERT INTO exams (course_id, title, description, duration_minutes, total_points, max_attempts, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$course['id'], $this->required('title'), $this->text('description'), max(1, (int) Request::input('duration_minutes', 30)), max(1, (float) Request::input('total_points', 20)), max(1, (int) Request::input('max_attempts', 1)), $status]);
        $this->success('/teacher/course?id=' . $course['id'], 'Exam created. Add questions below.');
    }

    public function saveQuestion(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $exam = $this->teacherExam((int) Request::input('exam_id'));
        $question = $this->required('question');
        $options = array_values(array_filter(array_map('trim', (array) ($_POST['options'] ?? [])), fn($value) => $value !== ''));
        $correct = (int) Request::input('correct_option', -1);
        if (count($options) < 2 || !isset($options[$correct])) $this->error('/teacher/course?id=' . $exam['course_id'], 'Provide at least two options and select the correct one.');
        $position = (int) $this->pdo->query('SELECT COALESCE(MAX(position), 0) + 1 FROM exam_questions WHERE exam_id = ' . (int) $exam['id'])->fetchColumn();
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO exam_questions (exam_id, question, type, points, position) VALUES (?, ?, "MULTIPLE_CHOICE", ?, ?)')->execute([$exam['id'], $question, max(0.5, (float) Request::input('points', 1)), $position]);
            $questionId = (int) $this->pdo->lastInsertId();
            $insertOption = $this->pdo->prepare('INSERT INTO exam_options (question_id, option_text, is_correct) VALUES (?, ?, ?)');
            foreach ($options as $index => $option) $insertOption->execute([$questionId, $option, $index === $correct ? 1 : 0]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        $this->success('/teacher/course?id=' . $exam['course_id'], 'Question added.');
    }

    public function generateQuestions(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $exam = $this->teacherExam((int) Request::input('exam_id'));
        try {
            $questions = (new AiService())->generateQuestions($this->required('topic'), (int) Request::input('count', 3));
            $position = (int) $this->pdo->query('SELECT COALESCE(MAX(position), 0) FROM exam_questions WHERE exam_id = ' . (int) $exam['id'])->fetchColumn();
            $this->pdo->beginTransaction();
            $insertQuestion = $this->pdo->prepare('INSERT INTO exam_questions (exam_id, question, type, points, position) VALUES (?, ?, "MULTIPLE_CHOICE", 1, ?)');
            $insertOption = $this->pdo->prepare('INSERT INTO exam_options (question_id, option_text, is_correct) VALUES (?, ?, ?)');
            foreach ($questions as $generated) {
                $insertQuestion->execute([$exam['id'], trim($generated['question']), ++$position]);
                $questionId = (int) $this->pdo->lastInsertId();
                foreach ($generated['options'] as $index => $option) {
                    $insertOption->execute([$questionId, trim($option), $index === $generated['correct'] ? 1 : 0]);
                }
            }
            $this->pdo->commit();
        } catch (RuntimeException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->error('/teacher/course?id=' . $exam['course_id'], $e->getMessage());
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
        $this->success('/teacher/course?id=' . $exam['course_id'], 'AI questions added. Review them before students take the exam.');
    }

    public function student(): void
    {
        Auth::requireRole('STUDENT');
        $courses = $this->query('SELECT c.*, s.name AS subject_name, CONCAT(u.first_name, " ", u.last_name) AS teacher_name FROM courses c JOIN enrollments e ON e.class_id = c.class_id AND e.student_id = ? AND e.status = "ACTIVE" JOIN subjects s ON s.id = c.subject_id JOIN users u ON u.id = c.teacher_id WHERE c.status = "PUBLISHED" ORDER BY c.id DESC', [Auth::id()]);
        View::render('portal/student', ['user' => Auth::user(), 'courses' => $courses, 'message' => Session::consumeFlash('success'), 'error' => Session::consumeFlash('error')]);
    }

    public function courseStudy(): void
    {
        Auth::requireRole('STUDENT');
        $course = $this->studentCourse((int) Request::input('id'));
        $chapters = $this->query('SELECT * FROM course_chapters WHERE course_id = ? AND status = "PUBLISHED" ORDER BY position', [$course['id']]);
        $exams = $this->query('SELECT * FROM exams WHERE course_id = ? AND status = "PUBLISHED" ORDER BY id DESC', [$course['id']]);
        View::render('portal/course-study', compact('course', 'chapters', 'exams') + ['answer' => Session::consumeFlash('ai_answer'), 'message' => Session::consumeFlash('success'), 'error' => Session::consumeFlash('error')]);
    }

    public function askAi(): void
    {
        Auth::requireRole('STUDENT'); Csrf::validate();
        $course = $this->studentCourse((int) Request::input('course_id'));
        $chapter = $this->query('SELECT content FROM course_chapters WHERE id = ? AND course_id = ? AND status = "PUBLISHED"', [(int) Request::input('chapter_id'), $course['id']])[0] ?? null;
        if ($chapter === null) $this->error('/student/course?id=' . $course['id'], 'Choose a published chapter.');
        try {
            Session::flash('ai_answer', (new AiService())->simplify($chapter['content'] ?? '', $this->required('question')));
            $this->success('/student/course?id=' . $course['id'], 'Here is a simpler explanation.');
        } catch (RuntimeException $e) {
            $this->error('/student/course?id=' . $course['id'], $e->getMessage());
        }
    }

    public function takeExam(): void
    {
        Auth::requireRole('STUDENT');
        $exam = $this->studentExam((int) Request::input('id'));
        $attempts = (int) $this->query('SELECT COUNT(*) AS count FROM exam_attempts WHERE exam_id = ? AND student_id = ?', [$exam['id'], Auth::id()])[0]['count'];
        if ($attempts >= (int) $exam['max_attempts']) $this->error('/student/course?id=' . $exam['course_id'], 'You have used all attempts for this exam.');
        $questions = $this->query('SELECT q.*, o.id AS option_id, o.option_text FROM exam_questions q LEFT JOIN exam_options o ON o.question_id = q.id WHERE q.exam_id = ? ORDER BY q.position, o.id', [$exam['id']]);
        View::render('portal/exam', compact('exam', 'questions') + ['attempt' => $attempts + 1]);
    }

    public function submitExam(): void
    {
        Auth::requireRole('STUDENT'); Csrf::validate();
        $exam = $this->studentExam((int) Request::input('exam_id'));
        $questions = $this->query('SELECT q.id, q.points, o.id AS correct_option FROM exam_questions q LEFT JOIN exam_options o ON o.question_id = q.id AND o.is_correct = 1 WHERE q.exam_id = ?', [$exam['id']]);
        if ($questions === []) $this->error('/student/course?id=' . $exam['course_id'], 'This exam has no questions yet.');
        $attemptNumber = (int) $this->query('SELECT COUNT(*) AS count FROM exam_attempts WHERE exam_id = ? AND student_id = ?', [$exam['id'], Auth::id()])[0]['count'] + 1;
        if ($attemptNumber > (int) $exam['max_attempts']) $this->error('/student/course?id=' . $exam['course_id'], 'You have used all attempts for this exam.');
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO exam_attempts (exam_id, student_id, attempt_number, started_at, submitted_at, score, status) VALUES (?, ?, ?, NOW(), NOW(), 0, "GRADED")')->execute([$exam['id'], Auth::id(), $attemptNumber]);
            $attemptId = (int) $this->pdo->lastInsertId();
            $score = 0.0; $answer = $this->pdo->prepare('INSERT INTO exam_answers (attempt_id, question_id, option_id, points_awarded) VALUES (?, ?, ?, ?)');
            foreach ($questions as $question) {
                $choice = (int) ($_POST['answer'][$question['id']] ?? 0);
                $points = $choice > 0 && $choice === (int) $question['correct_option'] ? (float) $question['points'] : 0;
                $score += $points; $answer->execute([$attemptId, $question['id'], $choice ?: null, $points]);
            }
            $this->pdo->prepare('UPDATE exam_attempts SET score = ? WHERE id = ?')->execute([$score, $attemptId]);
            $this->pdo->prepare('INSERT INTO grades (student_id, course_id, type, reference_id, score, max_score) VALUES (?, ?, "EXAM", ?, ?, ?)')->execute([Auth::id(), $exam['course_id'], $exam['id'], $score, $exam['total_points']]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack(); throw $e;
        }
        $this->success('/student/course?id=' . $exam['course_id'], "Exam submitted. Score: {$score} / {$exam['total_points']}.");
    }

    private function usersByRole(string $role): array { return $this->query('SELECT id, first_name, last_name, email FROM users WHERE role = ? AND status = 1 ORDER BY last_name, first_name', [$role]); }
    private function count(string $from): int { return (int) $this->pdo->query("SELECT COUNT(*) FROM {$from}")->fetchColumn(); }
    private function query(string $sql, array $parameters = []): array { $statement = $this->pdo->prepare($sql); $statement->execute($parameters); return $statement->fetchAll(); }
    private function exists(string $table, int $id): bool { return $id > 0 && (bool) $this->query("SELECT id FROM {$table} WHERE id = ?", [$id]); }
    private function userHasRole(int $id, string $role): bool { return (bool) $this->query('SELECT id FROM users WHERE id = ? AND role = ? AND status = 1', [$id, $role]); }
    private function required(string $key): string { $value = $this->text($key); if ($value === '') $this->error(Request::path(), ucfirst(str_replace('_', ' ', $key)) . ' is required.'); return $value; }
    private function text(string $key): string { return trim((string) Request::input($key, '')); }
    private function teacherCourse(int $id): array { $course = $this->query('SELECT c.*, s.name AS subject_name FROM courses c JOIN subjects s ON s.id = c.subject_id WHERE c.id = ? AND c.teacher_id = ?', [$id, Auth::id()])[0] ?? null; if ($course === null) Response::abort(404, 'Course not found.'); return $course; }
    private function teacherExam(int $id): array { $exam = $this->query('SELECT e.* FROM exams e JOIN courses c ON c.id = e.course_id WHERE e.id = ? AND c.teacher_id = ?', [$id, Auth::id()])[0] ?? null; if ($exam === null) Response::abort(404, 'Exam not found.'); return $exam; }
    private function studentCourse(int $id): array { $course = $this->query('SELECT c.*, s.name AS subject_name FROM courses c JOIN subjects s ON s.id = c.subject_id JOIN enrollments e ON e.class_id = c.class_id AND e.student_id = ? AND e.status = "ACTIVE" WHERE c.id = ? AND c.status = "PUBLISHED"', [Auth::id(), $id])[0] ?? null; if ($course === null) Response::abort(404, 'Course not found.'); return $course; }
    private function studentExam(int $id): array { $exam = $this->query('SELECT e.* FROM exams e JOIN courses c ON c.id = e.course_id JOIN enrollments n ON n.class_id = c.class_id AND n.student_id = ? AND n.status = "ACTIVE" WHERE e.id = ? AND e.status = "PUBLISHED" AND c.status = "PUBLISHED" AND (e.start_at IS NULL OR e.start_at <= NOW()) AND (e.end_at IS NULL OR e.end_at >= NOW())', [Auth::id(), $id])[0] ?? null; if ($exam === null) Response::abort(404, 'Exam not found or is unavailable.'); return $exam; }
    private function success(string $path, string $message): never { Session::flash('success', $message); Response::redirect($path); }
    private function error(string $path, string $message): never { Session::flash('error', $message); Response::redirect($path); }
}
