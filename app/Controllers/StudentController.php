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

class StudentController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function dashboard(): void
    {
        Auth::requireRole('STUDENT');
        $courses = $this->studentCourses();
        $this->render('dashboard', [
            'classes' => $this->query('SELECT cl.name, cl.academic_year FROM enrollments e JOIN school_classes cl ON cl.id = e.class_id WHERE e.student_id = ? AND e.status = "ACTIVE" ORDER BY cl.academic_year DESC, cl.name', [Auth::id()]),
            'ongoingCourses' => array_values(array_filter($courses, fn(array $course): bool => $course['progress_percent'] < 100)),
            'completedCourses' => array_values(array_filter($courses, fn(array $course): bool => $course['chapter_total'] > 0 && $course['progress_percent'] >= 100)),
            'scheduledExams' => $this->query('SELECT e.title, e.delivery_mode, e.start_at, e.end_at, c.title AS course_title FROM exams e JOIN courses c ON c.id = e.course_id JOIN enrollments n ON n.class_id = c.class_id WHERE n.student_id = ? AND n.status = "ACTIVE" AND c.status = "PUBLISHED" AND e.status = "PUBLISHED" AND e.start_at IS NOT NULL AND e.end_at >= NOW() ORDER BY e.start_at LIMIT 8', [Auth::id()]),
        ]);
    }

    public function courses(): void
    {
        Auth::requireRole('STUDENT');
        $this->render('courses', ['courses' => $this->studentCourses()]);
    }

    public function calendar(): void
    {
        Auth::requireRole('STUDENT');
        $this->render('calendar', ['calendarWeek' => $this->weeklyCalendar()]);
    }

    public function exams(): void
    {
        Auth::requireRole('STUDENT');
        $this->render('exams', [
            'exams' => $this->query('SELECT e.*, c.title AS course_title, s.name AS subject_name, COUNT(a.id) AS attempts_used, MAX(CASE WHEN a.status = "GRADED" THEN a.id END) AS latest_graded_attempt, CASE WHEN e.delivery_mode = "ONLINE" AND e.start_at <= NOW() AND e.end_at >= NOW() THEN 1 ELSE 0 END AS is_available FROM exams e JOIN courses c ON c.id = e.course_id JOIN subjects s ON s.id = c.subject_id JOIN enrollments n ON n.class_id = c.class_id LEFT JOIN exam_attempts a ON a.exam_id = e.id AND a.student_id = ? WHERE n.student_id = ? AND n.status = "ACTIVE" AND c.status = "PUBLISHED" AND e.status = "PUBLISHED" GROUP BY e.id ORDER BY e.start_at IS NULL, e.start_at, e.id DESC', [Auth::id(), Auth::id()]),
        ]);
    }

    public function saveProgress(): void
    {
        Auth::requireRole('STUDENT');
        Csrf::validate();
        $chapterId = (int) Request::input('chapter_id');
        $completed = Request::input('completed') === '1';
        $chapter = $this->query('SELECT ch.id FROM course_chapters ch JOIN courses c ON c.id = ch.course_id JOIN enrollments e ON e.class_id = c.class_id WHERE ch.id = ? AND ch.status = "PUBLISHED" AND c.status = "PUBLISHED" AND e.student_id = ? AND e.status = "ACTIVE"', [$chapterId, Auth::id()])[0] ?? null;
        if ($chapter === null) {
            Response::abort(404, 'Slide not found.');
        }
        $this->pdo->prepare('INSERT INTO course_chapter_progress (student_id, chapter_id, completed_at) VALUES (?, ?, CASE WHEN ? THEN NOW() ELSE NULL END) ON DUPLICATE KEY UPDATE completed_at = CASE WHEN VALUES(completed_at) IS NOT NULL THEN COALESCE(completed_at, NOW()) ELSE completed_at END, last_viewed_at = NOW()')->execute([Auth::id(), $chapterId, $completed ? 1 : 0]);
        $this->json(['ok' => true]);
    }

    public function askAiLive(): void
    {
        Auth::requireRole('STUDENT');
        Csrf::validate();
        $chapterId = (int) Request::input('chapter_id');
        $question = trim((string) Request::input('question', ''));
        $chapter = $this->query('SELECT ch.content FROM course_chapters ch JOIN courses c ON c.id = ch.course_id JOIN enrollments e ON e.class_id = c.class_id WHERE ch.id = ? AND ch.status = "PUBLISHED" AND c.status = "PUBLISHED" AND e.student_id = ? AND e.status = "ACTIVE"', [$chapterId, Auth::id()])[0] ?? null;
        if ($chapter === null) {
            Response::abort(404, 'Slide not found.');
        }
        if ($question === '') {
            Response::abort(422, 'Tell the assistant what you did not understand.');
        }
        try {
            $this->json(['answer' => (new AiService())->simplify($chapter['content'], $question)]);
        } catch (RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 422);
        }
    }

    public function reportTabLeave(): void
    {
        Auth::requireRole('STUDENT');
        Csrf::validate();
        $course = $this->studentCourse((int) Request::input('course_id'));
        $recent = $this->query('SELECT id FROM notifications WHERE user_id = ? AND type = "STUDENT_LEFT_TAB" AND message LIKE ? AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1', [$course['teacher_id'], '%' . Auth::id() . '%']);
        if ($recent === []) {
            $this->pdo->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, "STUDENT_LEFT_TAB")')
                ->execute([$course['teacher_id'], 'Student left a learning tab', Auth::user()['first_name'] . ' ' . Auth::user()['last_name'] . ' (student ID ' . Auth::id() . ') left the course tab for ' . $course['title'] . '.']);
        }
        $this->json(['ok' => true]);
    }

    private function studentCourses(): array
    {
        $courses = $this->query('SELECT c.id, c.title, c.description, s.name AS subject_name, CONCAT(u.first_name, " ", u.last_name) AS teacher_name, COUNT(DISTINCT ch.id) AS chapter_total, COUNT(DISTINCT CASE WHEN cp.completed_at IS NOT NULL THEN cp.chapter_id END) AS chapter_completed, (SELECT ch2.id FROM course_chapters ch2 LEFT JOIN course_chapter_progress cp2 ON cp2.chapter_id = ch2.id AND cp2.student_id = ? WHERE ch2.course_id = c.id AND ch2.status = "PUBLISHED" AND cp2.completed_at IS NULL ORDER BY ch2.position LIMIT 1) AS resume_chapter_id FROM courses c JOIN enrollments e ON e.class_id = c.class_id AND e.student_id = ? AND e.status = "ACTIVE" JOIN subjects s ON s.id = c.subject_id JOIN users u ON u.id = c.teacher_id LEFT JOIN course_chapters ch ON ch.course_id = c.id AND ch.status = "PUBLISHED" LEFT JOIN course_chapter_progress cp ON cp.chapter_id = ch.id AND cp.student_id = ? WHERE c.status = "PUBLISHED" GROUP BY c.id ORDER BY c.updated_at DESC', [Auth::id(), Auth::id(), Auth::id()]);
        foreach ($courses as &$course) {
            $course['progress_percent'] = $course['chapter_total'] > 0 ? (int) round(((int) $course['chapter_completed'] / (int) $course['chapter_total']) * 100) : 0;
        }
        unset($course);
        return $courses;
    }

    private function studentCourse(int $courseId): array
    {
        $course = $this->query('SELECT c.*, u.id AS teacher_id FROM courses c JOIN users u ON u.id = c.teacher_id JOIN enrollments e ON e.class_id = c.class_id WHERE c.id = ? AND c.status = "PUBLISHED" AND e.student_id = ? AND e.status = "ACTIVE"', [$courseId, Auth::id()])[0] ?? null;
        if ($course === null) {
            Response::abort(404, 'Course not found.');
        }
        return $course;
    }

    private function weeklyCalendar(): array
    {
        $weekStart = (new \DateTimeImmutable('monday this week'))->setTime(0, 0);
        $weekEnd = $weekStart->modify('+7 days');
        $scope = 'EXISTS (SELECT 1 FROM enrollments n WHERE n.class_id = c.class_id AND n.student_id = ? AND n.status = "ACTIVE")';
        $events = [];
        $slots = $this->query('SELECT css.id AS schedule_slot_id, css.weekday, css.start_time, css.end_time, c.id AS course_id, c.title AS course_title, c.start_date, c.end_date FROM course_schedule_slots css JOIN courses c ON c.id = css.course_id WHERE ' . $scope . ' AND c.status = "PUBLISHED"', [Auth::id()]);
        $overrides = $this->query('SELECT cso.course_schedule_slot_id, cso.occurrence_date, cso.override_date, cso.start_time, cso.end_time FROM course_schedule_overrides cso JOIN course_schedule_slots css ON css.id = cso.course_schedule_slot_id JOIN courses c ON c.id = css.course_id WHERE ' . $scope . ' AND c.status = "PUBLISHED" AND cso.occurrence_date >= ? AND cso.occurrence_date < ?', [Auth::id(), $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')]);
        $changed = [];
        foreach ($overrides as $override) {
            $changed[$override['course_schedule_slot_id'] . ':' . $override['occurrence_date']] = $override;
        }
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->modify("+{$i} days");
            $date = $day->format('Y-m-d');
            foreach ($slots as $slot) {
                $key = $slot['schedule_slot_id'] . ':' . $date;
                if (strtoupper($day->format('l')) === $slot['weekday'] && $date >= $slot['start_date'] && $date <= $slot['end_date'] && !isset($changed[$key])) {
                    $events[] = ['event_type' => 'COURSE', 'event_date' => $date, 'start_time' => $slot['start_time'], 'end_time' => $slot['end_time'], 'course_title' => $slot['course_title'], 'course_id' => $slot['course_id']];
                }
            }
        }
        foreach ($overrides as $override) {
            foreach ($slots as $slot) {
                if ((int) $slot['schedule_slot_id'] === (int) $override['course_schedule_slot_id']) {
                    $events[] = ['event_type' => 'COURSE', 'event_date' => $override['override_date'], 'start_time' => $override['start_time'], 'end_time' => $override['end_time'], 'course_title' => $slot['course_title'], 'course_id' => $slot['course_id']];
                    break;
                }
            }
        }
        $exams = $this->query('SELECT e.id AS exam_id, c.id AS course_id, DATE(e.start_at) AS event_date, TIME(e.start_at) AS start_time, TIME(e.end_at) AS end_time, e.title AS course_title FROM exams e JOIN courses c ON c.id = e.course_id WHERE ' . $scope . ' AND c.status = "PUBLISHED" AND e.status = "PUBLISHED" AND e.start_at >= ? AND e.start_at < ?', [Auth::id(), $weekStart->format('Y-m-d H:i:s'), $weekEnd->format('Y-m-d H:i:s')]);
        foreach ($exams as $exam) {
            $exam['event_type'] = 'EXAM';
            $events[] = $exam;
        }
        usort($events, fn(array $left, array $right): int => strcmp($left['event_date'] . $left['start_time'], $right['event_date'] . $right['start_time']));
        return $events;
    }

    private function render(string $section, array $data): void
    {
        View::render('portal/student-area', [
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

    private function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }
}
