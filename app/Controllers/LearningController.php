<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ContentSanitizer;
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
        $startDate = $this->date('start_date');
        $endDate = $this->date('end_date');
        if ($startDate === null || $endDate === null || $endDate < $startDate) {
            $this->error('/admin/classes', 'Choose a valid course start and end date.');
        }
        $slots = $this->scheduleSlots();
        if ($slots === []) {
            $this->error('/admin/classes', 'Choose at least one weekly course time slot.');
        }
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO courses (subject_id, teacher_id, class_id, title, description, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$subject, $teacher, $class, $title, $this->text('description'), $startDate, $endDate, $status]);
            $courseId = (int) $this->pdo->lastInsertId();
            $statement = $this->pdo->prepare('INSERT INTO course_schedule_slots (course_id, weekday, start_time, end_time) VALUES (?, ?, ?, ?)');
            foreach ($slots as $slot) {
                $statement->execute([$courseId, $slot['weekday'], $slot['start_time'], $slot['end_time']]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        $this->success('/admin/classes', 'Course created and assigned.');
    }

    public function enrollStudent(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $student = (int) Request::input('student_id');
        $class = (int) Request::input('class_id');
        $classRecord = $this->query('SELECT academic_year FROM school_classes WHERE id = ? AND status = 1', [$class])[0] ?? null;
        if (!$this->userHasRole($student, 'STUDENT') || $classRecord === null) {
            $this->error('/admin/classes', 'Choose an active student and class.');
        }
        // Retain the legacy enrollment column as a snapshot of the selected class year.
        $this->pdo->prepare('INSERT INTO enrollments (student_id, class_id, academic_year) VALUES (?, ?, ?)')->execute([$student, $class, $classRecord['academic_year']]);
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
            'calendar' => $this->studentRecordCalendar($studentId),
        ]);
    }

    private function studentRecordCalendar(int $studentId): array
    {
        $attendance = $this->query('SELECT ats.session_date AS event_date, ats.start_time, ats.end_time, c.title AS course_title, "ATTENDANCE" AS event_type FROM attendance_records ar JOIN attendance_sessions ats ON ats.id = ar.attendance_session_id JOIN courses c ON c.id = ats.course_id WHERE ar.student_id = ?', [$studentId]);
        $exams = $this->query('SELECT DATE(e.start_at) AS event_date, TIME(e.start_at) AS start_time, TIME(e.end_at) AS end_time, e.title AS course_title, "EXAM" AS event_type FROM exams e JOIN courses c ON c.id = e.course_id JOIN enrollments n ON n.class_id = c.class_id WHERE n.student_id = ? AND e.start_at IS NOT NULL GROUP BY e.id', [$studentId]);
        $events = array_merge($attendance, $exams);
        usort($events, fn(array $left, array $right): int => strcmp((string) $right['event_date'], (string) $left['event_date']));
        return array_slice($events, 0, 20);
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
        $questionsByExam = [];
        foreach ($this->query('SELECT q.*, o.id AS option_id, o.option_text, o.is_correct FROM exam_questions q JOIN exams e ON e.id = q.exam_id LEFT JOIN exam_options o ON o.question_id = q.id WHERE e.course_id = ? ORDER BY q.exam_id, q.position, q.id, o.id', [$course['id']]) as $row) {
            $questionId = (int) $row['id'];
            if (!isset($questionsByExam[$row['exam_id']][$questionId])) {
                $questionsByExam[$row['exam_id']][$questionId] = [
                    'id' => $questionId,
                    'question' => $row['question'],
                    'type' => $row['type'],
                    'allow_multiple' => $row['allow_multiple'],
                    'points' => $row['points'],
                    'position' => $row['position'],
                    'options' => [],
                ];
            }
            if ($row['option_id'] !== null) {
                $questionsByExam[$row['exam_id']][$questionId]['options'][] = [
                    'id' => (int) $row['option_id'],
                    'option_text' => $row['option_text'],
                    'is_correct' => (bool) $row['is_correct'],
                ];
            }
        }
        $resources = $this->query('SELECT r.*, ch.title AS chapter_title FROM course_resources r JOIN course_chapters ch ON ch.id = r.chapter_id WHERE ch.course_id = ? ORDER BY r.created_at DESC', [$course['id']]);
        $pendingTextAnswers = (int) $this->query('SELECT COUNT(*) AS count FROM exam_answers a JOIN exam_questions q ON q.id = a.question_id JOIN exams e ON e.id = q.exam_id WHERE e.course_id = ? AND q.type = "TEXT" AND a.points_awarded IS NULL', [$course['id']])[0]['count'];
        $pendingTextExamId = $this->query('SELECT e.id FROM exam_answers a JOIN exam_questions q ON q.id = a.question_id JOIN exams e ON e.id = q.exam_id WHERE e.course_id = ? AND q.type = "TEXT" AND a.points_awarded IS NULL ORDER BY a.id DESC LIMIT 1', [$course['id']])[0]['id'] ?? null;
        View::render('portal/course-editor', compact('course', 'chapters', 'exams', 'questionsByExam', 'resources', 'pendingTextAnswers', 'pendingTextExamId') + ['message' => Session::consumeFlash('success'), 'error' => Session::consumeFlash('error')]);
    }

    public function saveChapter(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $course = $this->teacherCourse((int) Request::input('course_id'));
        $content = trim((string) Request::input('content', ''));
        if (Request::input('generate_ai') === '1') {
            try {
                $content = (new AiService())->generateLesson($course['subject_name'], $this->required('title'), $content);
            } catch (RuntimeException $e) {
                $this->error('/teacher/course?id=' . $course['id'], $e->getMessage());
            }
        }
        $content = ContentSanitizer::sanitize($content);
        if ($content === '') $this->error('/teacher/course?id=' . $course['id'], 'Slide content is required.');
        $position = (int) $this->pdo->query('SELECT COALESCE(MAX(position), 0) + 1 FROM course_chapters WHERE course_id = ' . (int) $course['id'])->fetchColumn();
        $status = Request::input('status') === 'PUBLISHED' ? 'PUBLISHED' : 'DRAFT';
        $this->pdo->prepare('INSERT INTO course_chapters (course_id, title, content, position, status) VALUES (?, ?, ?, ?, ?)')->execute([$course['id'], $this->required('title'), $content, $position, $status]);
        $chapterId = (int) $this->pdo->lastInsertId();
        $media = $this->uploadSlideMedia();
        if ($media !== null) {
            $this->pdo->prepare('INSERT INTO course_resources (chapter_id, title, type, file_path) VALUES (?, ?, ?, ?)')
                ->execute([$chapterId, $media['name'], $media['type'], $media['path']]);
        }
        $this->success('/teacher/course?id=' . $course['id'], 'Chapter saved.');
    }

    public function generateCourseSlides(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $course = $this->teacherCourse((int) Request::input('course_id'));
        try {
            $slides = (new AiService())->generateCourseSlides($course['subject_name'], $this->required('brief'), (int) Request::input('count', 5));
        } catch (RuntimeException $e) {
            $this->error('/teacher/course?id=' . $course['id'], $e->getMessage());
        }

        $position = (int) $this->pdo->query('SELECT COALESCE(MAX(position), 0) FROM course_chapters WHERE course_id = ' . (int) $course['id'])->fetchColumn();
        $statement = $this->pdo->prepare('INSERT INTO course_chapters (course_id, title, content, position, status) VALUES (?, ?, ?, ?, "DRAFT")');
        foreach ($slides as $slide) {
            $statement->execute([$course['id'], $slide['title'], ContentSanitizer::sanitize($slide['content']), ++$position]);
        }
        $this->success('/teacher/course?id=' . $course['id'], 'AI created draft slides. Review and publish each slide.');
    }

    public function updateChapter(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $chapter = $this->query('SELECT ch.*, c.subject_id, s.name AS subject_name FROM course_chapters ch JOIN courses c ON c.id = ch.course_id JOIN subjects s ON s.id = c.subject_id WHERE ch.id = ? AND c.teacher_id = ?', [(int) Request::input('chapter_id'), Auth::id()])[0] ?? null;
        if ($chapter === null) {
            Response::abort(404, 'Slide not found.');
        }

        $title = $this->required('title');
        $content = trim((string) Request::input('content', ''));
        if (Request::input('expand_ai') === '1') {
            try {
                $content = (new AiService())->expandSlide($chapter['subject_name'], $title, $content, $this->text('ai_brief'));
            } catch (RuntimeException $e) {
                $this->error('/teacher/course?id=' . $chapter['course_id'], $e->getMessage());
            }
        }
        $content = ContentSanitizer::sanitize($content);
        if ($content === '') {
            $this->error('/teacher/course?id=' . $chapter['course_id'], 'Slide content is required.');
        }

        $status = Request::input('status') === 'PUBLISHED' ? 'PUBLISHED' : 'DRAFT';
        $this->pdo->prepare('UPDATE course_chapters SET title = ?, content = ?, status = ? WHERE id = ?')->execute([$title, $content, $status, $chapter['id']]);
        $this->success('/teacher/course?id=' . $chapter['course_id'], 'Slide updated.');
    }

    public function uploadInlineImage(): never
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $this->teacherCourse((int) Request::input('course_id'));
        $image = $this->uploadSlideMedia('image', true);
        if ($image === null) {
            Response::abort(422, 'Choose an image to insert.');
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['url' => Request::basePath() . '/' . $image['path']], JSON_THROW_ON_ERROR);
        exit;
    }

    public function createExam(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $course = $this->teacherCourse((int) Request::input('course_id'));
        $status = Request::input('status') === 'PUBLISHED' ? 'PUBLISHED' : 'DRAFT';
        $deliveryMode = Request::input('delivery_mode') === 'MANUAL' ? 'MANUAL' : 'ONLINE';
        $totalPoints = max(1, (float) Request::input('total_points', 20));
        $passingScore = max(0, min($totalPoints, (float) Request::input('passing_score', 0)));
        $startAt = $this->dateTime('start_at');
        $endAt = $this->dateTime('end_at');
        if ($startAt === null || $endAt === null || $endAt <= $startAt) {
            $this->error('/teacher/course?id=' . $course['id'], 'Choose a valid exam date, start time, and end time.');
        }
        $this->pdo->prepare('INSERT INTO exams (course_id, title, description, duration_minutes, total_points, passing_score, max_attempts, delivery_mode, start_at, end_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$course['id'], $this->required('title'), $this->text('description'), max(1, (int) Request::input('duration_minutes', 30)), $totalPoints, $passingScore, max(1, (int) Request::input('max_attempts', 1)), $deliveryMode, $startAt, $endAt, $status]);
        $this->success('/teacher/course?id=' . $course['id'], 'Exam created. Add questions below.');
    }

    public function saveQuestion(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $exam = $this->teacherExam((int) Request::input('exam_id'));
        ['question' => $question, 'type' => $questionType, 'options' => $options, 'correct' => $correct] = $this->questionInput($exam['course_id']);
        $position = (int) $this->pdo->query('SELECT COALESCE(MAX(position), 0) + 1 FROM exam_questions WHERE exam_id = ' . (int) $exam['id'])->fetchColumn();
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO exam_questions (exam_id, question, type, allow_multiple, points, position) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$exam['id'], $question, $questionType === 'TEXT' ? 'TEXT' : 'MULTIPLE_CHOICE', $questionType === 'CHECKBOX' ? 1 : 0, max(0.5, (float) Request::input('points', 1)), $position]);
            $questionId = (int) $this->pdo->lastInsertId();
            $insertOption = $this->pdo->prepare('INSERT INTO exam_options (question_id, option_text, is_correct) VALUES (?, ?, ?)');
            foreach ($options as $index => $option) $insertOption->execute([$questionId, $option, in_array($index, $correct, true) ? 1 : 0]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        $this->success('/teacher/course?id=' . $exam['course_id'], 'Question added.');
    }

    public function updateQuestion(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $questionRecord = $this->teacherQuestion((int) Request::input('question_id'));
        if ((int) $this->query('SELECT COUNT(*) AS count FROM exam_answers WHERE question_id = ?', [$questionRecord['id']])[0]['count'] > 0) {
            $this->error('/teacher/course?id=' . $questionRecord['course_id'], 'Questions with submitted answers cannot be changed.');
        }
        ['question' => $question, 'type' => $questionType, 'options' => $options, 'correct' => $correct] = $this->questionInput($questionRecord['course_id']);
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('UPDATE exam_questions SET question = ?, type = ?, allow_multiple = ?, points = ? WHERE id = ?')
                ->execute([$question, $questionType === 'TEXT' ? 'TEXT' : 'MULTIPLE_CHOICE', $questionType === 'CHECKBOX' ? 1 : 0, max(0.5, (float) Request::input('points', 1)), $questionRecord['id']]);
            $this->pdo->prepare('DELETE FROM exam_options WHERE question_id = ?')->execute([$questionRecord['id']]);
            if ($questionType !== 'TEXT') {
                $insertOption = $this->pdo->prepare('INSERT INTO exam_options (question_id, option_text, is_correct) VALUES (?, ?, ?)');
                foreach ($options as $index => $option) {
                    $insertOption->execute([$questionRecord['id'], $option, in_array($index, $correct, true) ? 1 : 0]);
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        $this->success('/teacher/course?id=' . $questionRecord['course_id'], 'Question updated.');
    }

    public function deleteQuestion(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $question = $this->teacherQuestion((int) Request::input('question_id'));
        if ((int) $this->query('SELECT COUNT(*) AS count FROM exam_answers WHERE question_id = ?', [$question['id']])[0]['count'] > 0) {
            $this->error('/teacher/course?id=' . $question['course_id'], 'Questions with submitted answers cannot be deleted.');
        }
        $this->pdo->prepare('DELETE FROM exam_questions WHERE id = ?')->execute([$question['id']]);
        $this->success('/teacher/course?id=' . $question['course_id'], 'Question deleted.');
    }

    public function gradeTextAnswer(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $answerId = (int) Request::input('answer_id');
        $answer = $this->query('SELECT a.id, a.attempt_id, q.points, q.exam_id, e.course_id, at.status AS attempt_status FROM exam_answers a JOIN exam_questions q ON q.id = a.question_id JOIN exams e ON e.id = q.exam_id JOIN courses c ON c.id = e.course_id JOIN exam_attempts at ON at.id = a.attempt_id WHERE a.id = ? AND q.type = "TEXT" AND c.teacher_id = ?', [$answerId, Auth::id()])[0] ?? null;
        if ($answer === null) {
            Response::abort(404, 'Text answer not found.');
        }
        $points = (float) Request::input('points_awarded', -1);
        if ($points < 0 || $points > (float) $answer['points']) {
            $this->error('/teacher/exam/grading?id=' . $answer['exam_id'], 'Enter a valid mark for this answer.');
        }
        $this->pdo->prepare('UPDATE exam_answers SET points_awarded = ? WHERE id = ?')->execute([$points, $answerId]);
        $remaining = (int) $this->query('SELECT COUNT(*) AS count FROM exam_answers a JOIN exam_questions q ON q.id = a.question_id WHERE a.attempt_id = ? AND q.type = "TEXT" AND a.points_awarded IS NULL', [$answer['attempt_id']])[0]['count'];
        if ($remaining === 0 && $answer['attempt_status'] !== 'GRADED') {
            $score = (float) $this->query('SELECT COALESCE(SUM(points_awarded), 0) AS score FROM exam_answers WHERE attempt_id = ?', [$answer['attempt_id']])[0]['score'];
            $this->pdo->prepare('UPDATE exam_attempts SET score = ?, status = "GRADED" WHERE id = ?')->execute([$score, $answer['attempt_id']]);
            $this->pdo->prepare('INSERT INTO grades (student_id, course_id, type, reference_id, score, max_score) SELECT student_id, ?, "EXAM", ?, ?, total_points FROM exam_attempts JOIN exams ON exams.id = ? WHERE exam_attempts.id = ?')->execute([$answer['course_id'], $answer['exam_id'], $score, $answer['exam_id'], $answer['attempt_id']]);
        }
        $this->success('/teacher/exam/grading?id=' . $answer['exam_id'], 'Text answer marked.');
    }

    public function generateQuestions(): void
    {
        Auth::requireRole('TEACHER'); Csrf::validate();
        $exam = $this->teacherExam((int) Request::input('exam_id'));
        try {
            $difficulty = (string) Request::input('difficulty', 'intermediate');
            if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) {
                $this->error('/teacher/course?id=' . $exam['course_id'], 'Choose a valid difficulty.');
            }
            $courseContent = implode("\n\n", array_column($this->query('SELECT content FROM course_chapters WHERE course_id = ? ORDER BY position', [$exam['course_id']]), 'content'));
            $questions = (new AiService())->generateQuestions($this->required('topic'), (int) Request::input('count', 3), $courseContent, $difficulty);
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
        $exams = $this->query('SELECT e.*, CASE WHEN e.delivery_mode = "ONLINE" AND e.start_at <= NOW() AND e.end_at >= NOW() THEN 1 ELSE 0 END AS is_available FROM exams e WHERE e.course_id = ? AND e.status = "PUBLISHED" ORDER BY e.start_at, e.id DESC', [$course['id']]);
        $resources = $this->query('SELECT r.*, ch.title AS chapter_title FROM course_resources r JOIN course_chapters ch ON ch.id = r.chapter_id WHERE ch.course_id = ? AND ch.status = "PUBLISHED" ORDER BY r.created_at', [$course['id']]);
        $completedChapters = array_column($this->query('SELECT chapter_id FROM course_chapter_progress WHERE student_id = ? AND completed_at IS NOT NULL', [Auth::id()]), 'chapter_id');
        View::render('portal/course-study', compact('course', 'chapters', 'exams', 'resources', 'completedChapters') + ['answer' => Session::consumeFlash('ai_answer'), 'message' => Session::consumeFlash('success'), 'error' => Session::consumeFlash('error')]);
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
        $questions = $this->query('SELECT id, type, allow_multiple, points FROM exam_questions WHERE exam_id = ? ORDER BY position', [$exam['id']]);
        if ($questions === []) $this->error('/student/course?id=' . $exam['course_id'], 'This exam has no questions yet.');
        $attemptNumber = (int) $this->query('SELECT COUNT(*) AS count FROM exam_attempts WHERE exam_id = ? AND student_id = ?', [$exam['id'], Auth::id()])[0]['count'] + 1;
        if ($attemptNumber > (int) $exam['max_attempts']) $this->error('/student/course?id=' . $exam['course_id'], 'You have used all attempts for this exam.');
        $hasTextAnswers = (bool) array_filter($questions, fn(array $question): bool => $question['type'] === 'TEXT');
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO exam_attempts (exam_id, student_id, attempt_number, started_at, submitted_at, score, status) VALUES (?, ?, ?, NOW(), NOW(), ?, ?)')->execute([$exam['id'], Auth::id(), $attemptNumber, $hasTextAnswers ? null : 0, $hasTextAnswers ? 'SUBMITTED' : 'GRADED']);
            $attemptId = (int) $this->pdo->lastInsertId();
            $score = 0.0;
            $answer = $this->pdo->prepare('INSERT INTO exam_answers (attempt_id, question_id, option_id, selected_option_ids, text_answer, points_awarded) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($questions as $question) {
                if ($question['type'] === 'TEXT') {
                    $textAnswer = trim((string) ($_POST['answer_text'][$question['id']] ?? ''));
                    $answer->execute([$attemptId, $question['id'], null, null, $textAnswer, null]);
                    continue;
                }
                $selected = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['answer'][$question['id']] ?? [])), fn(int $id): bool => $id > 0)));
                $correct = array_map('intval', array_column($this->query('SELECT id FROM exam_options WHERE question_id = ? AND is_correct = 1', [$question['id']]), 'id'));
                sort($selected);
                sort($correct);
                $points = $selected === $correct ? (float) $question['points'] : 0.0;
                $score += $points;
                $answer->execute([$attemptId, $question['id'], $selected[0] ?? null, json_encode($selected, JSON_THROW_ON_ERROR), null, $points]);
            }
            if (!$hasTextAnswers) {
                $this->pdo->prepare('UPDATE exam_attempts SET score = ? WHERE id = ?')->execute([$score, $attemptId]);
                $this->pdo->prepare('INSERT INTO grades (student_id, course_id, type, reference_id, score, max_score) VALUES (?, ?, "EXAM", ?, ?, ?)')->execute([Auth::id(), $exam['course_id'], $exam['id'], $score, $exam['total_points']]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack(); throw $e;
        }
        if ($hasTextAnswers) {
            $this->success('/student/course?id=' . $exam['course_id'], 'Exam submitted. Your professor will mark the text answers before the final result is available.');
        }
        $result = $score >= (float) $exam['passing_score'] ? 'Passed' : 'Not yet passed';
        $this->success('/student/exam/result?id=' . $attemptId, "Exam submitted. Score: {$score} / {$exam['total_points']}. {$result}.");
    }

    public function examResult(): void
    {
        Auth::requireRole('STUDENT');
        $attempt = $this->query('SELECT a.*, e.title AS exam_title, e.description AS exam_description, e.total_points, e.passing_score, c.id AS course_id, c.title AS course_title FROM exam_attempts a JOIN exams e ON e.id = a.exam_id JOIN courses c ON c.id = e.course_id JOIN enrollments n ON n.class_id = c.class_id AND n.student_id = ? AND n.status = "ACTIVE" WHERE a.id = ? AND a.student_id = ?', [Auth::id(), (int) Request::input('id'), Auth::id()])[0] ?? null;
        if ($attempt === null) {
            Response::abort(404, 'Exam result not found.');
        }
        if ($attempt['status'] !== 'GRADED') {
            $this->error('/student/course?id=' . $attempt['course_id'], 'This result will be available after your professor grades the exam.');
        }

        $answers = $this->query('SELECT a.text_answer, a.selected_option_ids, a.points_awarded, q.id AS question_id, q.question, q.type, q.points FROM exam_answers a JOIN exam_questions q ON q.id = a.question_id WHERE a.attempt_id = ? ORDER BY q.position', [$attempt['id']]);
        foreach ($answers as &$answer) {
            $answer['selected_option_ids'] = json_decode((string) ($answer['selected_option_ids'] ?? '[]'), true) ?: [];
            $answer['options'] = $this->query('SELECT id, option_text, is_correct FROM exam_options WHERE question_id = ? ORDER BY id', [$answer['question_id']]);
        }
        unset($answer);

        View::render('portal/exam-result', compact('attempt', 'answers') + [
            'user' => Auth::user(),
            'message' => Session::consumeFlash('success'),
            'error' => Session::consumeFlash('error'),
        ]);
    }

    private function dateTime(string $key): ?string
    {
        $value = trim((string) Request::input($key, ''));
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
        return $dateTime !== false && $dateTime->format('Y-m-d\TH:i') === $value ? $dateTime->format('Y-m-d H:i:s') : null;
    }
    private function date(string $key): ?string
    {
        $value = trim((string) Request::input($key, ''));
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }
    private function scheduleSlots(): array
    {
        $allowed = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];
        $selected = array_unique(array_filter((array) ($_POST['schedule_days'] ?? []), fn($day): bool => is_string($day) && in_array($day, $allowed, true)));
        $starts = (array) ($_POST['schedule_start'] ?? []);
        $ends = (array) ($_POST['schedule_end'] ?? []);
        $slots = [];
        foreach ($selected as $day) {
            $start = (string) ($starts[$day] ?? '');
            $end = (string) ($ends[$day] ?? '');
            if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end) || $end <= $start) {
                $this->error('/admin/classes', 'Choose valid start and end times for every selected weekday.');
            }
            $slots[] = ['weekday' => $day, 'start_time' => $start . ':00', 'end_time' => $end . ':00'];
        }
        return $slots;
    }
    private function usersByRole(string $role): array { return $this->query('SELECT id, first_name, last_name, email FROM users WHERE role = ? AND status = 1 ORDER BY last_name, first_name', [$role]); }
    private function questionInput(int $courseId): array
    {
        $question = $this->required('question');
        $type = (string) Request::input('question_type', 'RADIO');
        if (!in_array($type, ['RADIO', 'CHECKBOX', 'TEXT'], true)) {
            $this->error('/teacher/course?id=' . $courseId, 'Choose a valid answer type.');
        }
        if ($type === 'TEXT') {
            return compact('question', 'type') + ['options' => [], 'correct' => []];
        }

        $options = [];
        foreach ((array) ($_POST['options'] ?? []) as $index => $option) {
            $option = trim((string) $option);
            if ($option !== '') $options[$index] = $option;
        }
        $correct = [];
        foreach ((array) ($_POST['correct_options'] ?? []) as $index) {
            if (is_scalar($index) && ctype_digit((string) $index) && isset($options[(int) $index])) {
                $correct[] = (int) $index;
            }
        }
        $correct = array_values(array_unique($correct));
        if (count($options) < 2 || $correct === [] || ($type === 'RADIO' && count($correct) !== 1)) {
            $this->error('/teacher/course?id=' . $courseId, 'Add at least two options and select the valid correct answer or answers.');
        }
        return compact('question', 'type', 'options', 'correct');
    }
    private function uploadSlideMedia(string $field = 'media', bool $imagesOnly = false): ?array
    {
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
            $this->error(Request::path(), 'The media upload failed. Try again.');
        }
        if ((int) $file['size'] > 25 * 1024 * 1024) {
            $this->error(Request::path(), 'Images and videos must be 25 MB or smaller.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        $allowed = [
            'image/jpeg' => ['extension' => 'jpg', 'type' => 'IMAGE'],
            'image/png' => ['extension' => 'png', 'type' => 'IMAGE'],
            'image/webp' => ['extension' => 'webp', 'type' => 'IMAGE'],
            'image/gif' => ['extension' => 'gif', 'type' => 'IMAGE'],
            'video/mp4' => ['extension' => 'mp4', 'type' => 'VIDEO'],
            'video/webm' => ['extension' => 'webm', 'type' => 'VIDEO'],
        ];
        if ($imagesOnly) {
            $allowed = array_filter($allowed, fn(array $media): bool => $media['type'] === 'IMAGE');
        }
        if (!is_string($mime) || !isset($allowed[$mime])) {
            $this->error(Request::path(), 'Upload a JPEG, PNG, WebP, GIF, MP4, or WebM file.');
        }
        $directory = dirname(__DIR__, 2) . '/public/uploads/course-media';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('The course media directory could not be created.');
        }
        $fileName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime]['extension'];
        if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $fileName)) {
            throw new RuntimeException('The uploaded media could not be saved.');
        }
        return ['name' => basename((string) $file['name']), 'path' => 'uploads/course-media/' . $fileName, 'type' => $allowed[$mime]['type']];
    }
    private function count(string $from): int { return (int) $this->pdo->query("SELECT COUNT(*) FROM {$from}")->fetchColumn(); }
    private function query(string $sql, array $parameters = []): array { $statement = $this->pdo->prepare($sql); $statement->execute($parameters); return $statement->fetchAll(); }
    private function exists(string $table, int $id): bool { return $id > 0 && (bool) $this->query("SELECT id FROM {$table} WHERE id = ?", [$id]); }
    private function userHasRole(int $id, string $role): bool { return (bool) $this->query('SELECT id FROM users WHERE id = ? AND role = ? AND status = 1', [$id, $role]); }
    private function required(string $key): string { $value = $this->text($key); if ($value === '') $this->error(Request::path(), ucfirst(str_replace('_', ' ', $key)) . ' is required.'); return $value; }
    private function text(string $key): string { return trim((string) Request::input($key, '')); }
    private function teacherCourse(int $id): array { $course = $this->query('SELECT c.*, s.name AS subject_name FROM courses c JOIN subjects s ON s.id = c.subject_id WHERE c.id = ? AND c.teacher_id = ?', [$id, Auth::id()])[0] ?? null; if ($course === null) Response::abort(404, 'Course not found.'); return $course; }
    private function teacherExam(int $id): array { $exam = $this->query('SELECT e.* FROM exams e JOIN courses c ON c.id = e.course_id WHERE e.id = ? AND c.teacher_id = ?', [$id, Auth::id()])[0] ?? null; if ($exam === null) Response::abort(404, 'Exam not found.'); return $exam; }
    private function teacherQuestion(int $id): array { $question = $this->query('SELECT q.*, e.course_id FROM exam_questions q JOIN exams e ON e.id = q.exam_id JOIN courses c ON c.id = e.course_id WHERE q.id = ? AND c.teacher_id = ?', [$id, Auth::id()])[0] ?? null; if ($question === null) Response::abort(404, 'Question not found.'); return $question; }
    private function studentCourse(int $id): array { $course = $this->query('SELECT c.*, s.name AS subject_name FROM courses c JOIN subjects s ON s.id = c.subject_id JOIN enrollments e ON e.class_id = c.class_id AND e.student_id = ? AND e.status = "ACTIVE" WHERE c.id = ? AND c.status = "PUBLISHED"', [Auth::id(), $id])[0] ?? null; if ($course === null) Response::abort(404, 'Course not found.'); return $course; }
    private function studentExam(int $id): array { $exam = $this->query('SELECT e.* FROM exams e JOIN courses c ON c.id = e.course_id JOIN enrollments n ON n.class_id = c.class_id AND n.student_id = ? AND n.status = "ACTIVE" WHERE e.id = ? AND e.delivery_mode = "ONLINE" AND e.status = "PUBLISHED" AND c.status = "PUBLISHED" AND e.start_at <= NOW() AND e.end_at >= NOW()', [Auth::id(), $id])[0] ?? null; if ($exam === null) Response::abort(404, 'Exam is not available at this time.'); return $exam; }
    private function success(string $path, string $message): never { Session::flash('success', $message); Response::redirect($path); }
    private function error(string $path, string $message): never { Session::flash('error', $message); Response::redirect($path); }
}
