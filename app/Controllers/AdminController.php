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
            'newStudents' => $this->query('SELECT id, first_name, last_name, email, created_at FROM users WHERE role = "STUDENT" AND status = 1 ORDER BY created_at DESC LIMIT 5'),
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
        $conditions = ['u.role = "STUDENT"', 'u.status = 1'];
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
            'enrollments' => $this->query('SELECT e.id, e.academic_year, CONCAT(u.first_name, " ", u.last_name) AS student_name, cl.name AS class_name FROM enrollments e JOIN users u ON u.id = e.student_id JOIN school_classes cl ON cl.id = e.class_id WHERE e.status = "ACTIVE" AND u.status = 1 ORDER BY e.id DESC LIMIT 6'),
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
        $student = $this->query('SELECT id, first_name, last_name, email, status, created_at FROM users WHERE id = ? AND role = "STUDENT"', [$id])[0] ?? null;
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
            'calendarWeek' => $this->weeklyCalendar('EXISTS (SELECT 1 FROM enrollments n WHERE n.class_id = c.class_id AND n.student_id = ? AND n.status = "ACTIVE")', [$id], true),
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
            'calendarWeek' => $this->weeklyCalendar('c.teacher_id = ?', [$id]),
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
            'students' => $this->query('SELECT u.id, u.first_name, u.last_name, u.email FROM enrollments e JOIN users u ON u.id = e.student_id WHERE e.class_id = ? AND e.status = "ACTIVE" AND u.status = 1 ORDER BY u.last_name, u.first_name', [$id]),
            'courses' => $this->query('SELECT c.id, c.title, c.status, c.start_date, c.end_date, s.name AS subject_name, CONCAT(u.first_name, " ", u.last_name) AS teacher_name FROM courses c JOIN subjects s ON s.id = c.subject_id JOIN users u ON u.id = c.teacher_id WHERE c.class_id = ? ORDER BY c.title', [$id]),
            'courseSlots' => $this->groupBy($this->query('SELECT css.* FROM course_schedule_slots css JOIN courses c ON c.id = css.course_id WHERE c.class_id = ? ORDER BY css.weekday, css.start_time', [$id]), 'course_id'),
            'exams' => $this->query('SELECT e.* FROM exams e JOIN courses c ON c.id = e.course_id WHERE c.class_id = ? ORDER BY e.start_at', [$id]),
            'calendarWeek' => $this->weeklyClassCalendar($id),
        ]);
    }

    public function disableStudent(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $studentId = (int) Request::input('student_id');
        $statement = $this->pdo->prepare('UPDATE users SET status = 0 WHERE id = ? AND role = "STUDENT" AND status = 1');
        $statement->execute([$studentId]);
        if ($statement->rowCount() !== 1) $this->fail('/admin/students', 'Only an active student account can be disabled.');
        $this->success('/admin/students', 'Student account disabled. Historical enrolments, attendance, and grades were retained.');
    }

    public function updateCourseSchedule(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $course = $this->query('SELECT id, class_id FROM courses WHERE id = ?', [(int) Request::input('course_id')])[0] ?? null;
        if ($course === null) {
            Response::abort(404, 'Course not found.');
        }
        $startDate = $this->date('start_date');
        $endDate = $this->date('end_date');
        $slots = $this->weeklySlots();
        if ($startDate === null || $endDate === null || $endDate < $startDate || $slots === []) {
            $this->fail('/admin/classes?id=' . $course['class_id'], 'Choose a valid course period and at least one weekly slot.');
        }
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('UPDATE courses SET start_date = ?, end_date = ?, status = ? WHERE id = ?')->execute([$startDate, $endDate, in_array(Request::input('status'), ['DRAFT', 'PUBLISHED'], true) ? Request::input('status') : 'DRAFT', $course['id']]);
            $this->pdo->prepare('DELETE FROM course_schedule_slots WHERE course_id = ?')->execute([$course['id']]);
            $statement = $this->pdo->prepare('INSERT INTO course_schedule_slots (course_id, weekday, start_time, end_time) VALUES (?, ?, ?, ?)');
            foreach ($slots as $slot) {
                $statement->execute([$course['id'], $slot['weekday'], $slot['start_time'], $slot['end_time']]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        $this->success('/admin/classes?id=' . $course['class_id'], 'Course timetable updated.');
    }

    public function cancelCourse(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $course = $this->query('SELECT id, class_id FROM courses WHERE id = ?', [(int) Request::input('course_id')])[0] ?? null;
        if ($course === null) {
            Response::abort(404, 'Course not found.');
        }
        $this->pdo->prepare('UPDATE courses SET status = "ARCHIVED" WHERE id = ?')->execute([$course['id']]);
        $this->success('/admin/classes?id=' . $course['class_id'], 'Course cancelled.');
    }

    public function updateExamSchedule(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $exam = $this->query('SELECT e.id, c.class_id FROM exams e JOIN courses c ON c.id = e.course_id WHERE e.id = ?', [(int) Request::input('exam_id')])[0] ?? null;
        if ($exam === null) {
            Response::abort(404, 'Exam not found.');
        }
        $startAt = $this->dateTime('start_at');
        $endAt = $this->dateTime('end_at');
        if ($startAt === null || $endAt === null || $endAt <= $startAt) {
            $this->fail('/admin/classes?id=' . $exam['class_id'], 'Choose a valid exam start and end time.');
        }
        $status = in_array(Request::input('status'), ['DRAFT', 'PUBLISHED', 'CLOSED'], true) ? Request::input('status') : 'DRAFT';
        $this->pdo->prepare('UPDATE exams SET start_at = ?, end_at = ?, status = ? WHERE id = ?')->execute([$startAt, $endAt, $status, $exam['id']]);
        $this->success('/admin/classes?id=' . $exam['class_id'], $status === 'CLOSED' ? 'Exam cancelled.' : 'Exam schedule updated.');
    }

    public function moveCalendarEvent(): void
    {
        Auth::requireRole('ADMIN'); Csrf::validate();
        $classId = (int) Request::input('class_id'); $eventType = Request::input('event_type'); $eventId = (int) Request::input('event_id');
        $occurrenceDate = $this->date('occurrence_date'); $eventDate = $this->date('event_date'); $startTime = $this->quarterHour('start_time');
        $weekStart = (new \DateTimeImmutable('monday this week'))->setTime(0, 0); $weekEnd = $weekStart->modify('+7 days');
        if (!in_array($eventType, ['COURSE', 'EXAM'], true) || $eventId < 1 || $occurrenceDate === null || $eventDate === null || $startTime === null || $occurrenceDate < $weekStart->format('Y-m-d') || $occurrenceDate >= $weekEnd->format('Y-m-d') || $eventDate < $weekStart->format('Y-m-d') || $eventDate >= $weekEnd->format('Y-m-d')) $this->fail('/admin/classes?id=' . $classId, 'Move the event within this week at a 15-minute time.');
        if ($eventType === 'COURSE') {
            $slot = $this->query('SELECT css.id, css.start_time, css.end_time FROM course_schedule_slots css JOIN courses c ON c.id = css.course_id WHERE css.id = ? AND c.class_id = ?', [$eventId, $classId])[0] ?? null;
            if ($slot === null) Response::abort(404, 'Course occurrence not found.');
            $current = $this->query('SELECT start_time, end_time FROM course_schedule_overrides WHERE course_schedule_slot_id = ? AND occurrence_date = ?', [$eventId, $occurrenceDate])[0] ?? $slot;
            $duration = strtotime('1970-01-01 ' . $current['end_time']) - strtotime('1970-01-01 ' . $current['start_time']); $endTime = date('H:i:s', strtotime('1970-01-01 ' . $startTime) + $duration);
            if ($startTime < '08:00:00' || $endTime > '18:00:00') $this->fail('/admin/classes?id=' . $classId, 'The moved course must remain between 08:00 and 18:00.');
            $this->pdo->prepare('INSERT INTO course_schedule_overrides (course_schedule_slot_id, occurrence_date, override_date, start_time, end_time) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE override_date = VALUES(override_date), start_time = VALUES(start_time), end_time = VALUES(end_time)')->execute([$eventId, $occurrenceDate, $eventDate, $startTime, $endTime]);
        } else {
            $exam = $this->query('SELECT e.id, e.start_at, e.end_at FROM exams e JOIN courses c ON c.id = e.course_id WHERE e.id = ? AND c.class_id = ?', [$eventId, $classId])[0] ?? null;
            if ($exam === null) Response::abort(404, 'Exam occurrence not found.');
            $duration = strtotime($exam['end_at']) - strtotime($exam['start_at']); $startAt = $eventDate . ' ' . $startTime; $endAt = date('Y-m-d H:i:s', strtotime($startAt) + $duration);
            if ($startTime < '08:00:00' || substr($endAt, 0, 10) !== $eventDate || substr($endAt, 11) > '18:00:00') $this->fail('/admin/classes?id=' . $classId, 'The moved exam must remain between 08:00 and 18:00.');
            $this->pdo->prepare('UPDATE exams SET start_at = ?, end_at = ? WHERE id = ?')->execute([$startAt, $endAt, $eventId]);
        }
        $this->success('/admin/classes?id=' . $classId, 'Calendar event moved for this week.');
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
            'calendarWeek' => $this->weeklyCalendar('c.subject_id = ?', [$id]),
        ]);
    }

    private function weeklyClassCalendar(int $classId): array
    {
        return $this->weeklyCalendar('c.class_id = ?', [$classId]);
    }

    private function weeklyCalendar(string $scope, array $parameters, bool $publishedOnly = false): array
    {
        $weekStart = (new \DateTimeImmutable('monday this week'))->setTime(0, 0);
        $weekEnd = $weekStart->modify('+7 days');
        $courseStatus = $publishedOnly ? 'c.status = "PUBLISHED"' : 'c.status <> "ARCHIVED"';
        $examStatus = $publishedOnly ? 'e.status = "PUBLISHED"' : 'e.status <> "CLOSED"';
        $events = [];
        $slots = $this->query('SELECT css.id AS schedule_slot_id, css.weekday, css.start_time, css.end_time, c.id AS course_id, c.class_id, c.title AS course_title, c.start_date, c.end_date FROM course_schedule_slots css JOIN courses c ON c.id = css.course_id WHERE ' . $scope . ' AND ' . $courseStatus, $parameters);
        $overrides = $this->query('SELECT cso.course_schedule_slot_id, cso.occurrence_date, cso.override_date, cso.start_time, cso.end_time FROM course_schedule_overrides cso JOIN course_schedule_slots css ON css.id = cso.course_schedule_slot_id JOIN courses c ON c.id = css.course_id WHERE ' . $scope . ' AND ' . $courseStatus . ' AND cso.occurrence_date >= ? AND cso.occurrence_date < ?', array_merge($parameters, [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')]));
        $changed = []; foreach ($overrides as $o) $changed[$o['course_schedule_slot_id'] . ':' . $o['occurrence_date']] = $o;
        for ($i = 0; $i < 7; $i++) { $day = $weekStart->modify("+{$i} days"); $date = $day->format('Y-m-d'); foreach ($slots as $slot) { $key = $slot['schedule_slot_id'] . ':' . $date; if (strtoupper($day->format('l')) === $slot['weekday'] && $date >= $slot['start_date'] && $date <= $slot['end_date'] && !isset($changed[$key])) $events[] = ['event_type'=>'COURSE','event_date'=>$date,'occurrence_date'=>$date,'start_time'=>$slot['start_time'],'end_time'=>$slot['end_time'],'course_title'=>$slot['course_title'],'course_id'=>$slot['course_id'],'class_id'=>$slot['class_id'],'schedule_slot_id'=>$slot['schedule_slot_id']]; } }
        foreach ($overrides as $o) foreach ($slots as $slot) if ((int)$slot['schedule_slot_id'] === (int)$o['course_schedule_slot_id']) { $events[] = ['event_type'=>'COURSE','event_date'=>$o['override_date'],'occurrence_date'=>$o['occurrence_date'],'start_time'=>$o['start_time'],'end_time'=>$o['end_time'],'course_title'=>$slot['course_title'],'course_id'=>$slot['course_id'],'class_id'=>$slot['class_id'],'schedule_slot_id'=>$slot['schedule_slot_id']]; break; }
        $exams = $this->query('SELECT e.id AS exam_id, c.id AS course_id, c.class_id, DATE(e.start_at) AS event_date, DATE(e.start_at) AS occurrence_date, TIME(e.start_at) AS start_time, TIME(e.end_at) AS end_time, e.title AS course_title FROM exams e JOIN courses c ON c.id = e.course_id WHERE ' . $scope . ' AND ' . $examStatus . ' AND e.start_at >= ? AND e.start_at < ?', array_merge($parameters, [$weekStart->format('Y-m-d H:i:s'), $weekEnd->format('Y-m-d H:i:s')]));
        foreach ($exams as $exam) { $exam['event_type'] = 'EXAM'; $events[] = $exam; }
        usort($events, fn(array $a, array $b): int => strcmp($a['event_date'] . $a['start_time'], $b['event_date'] . $b['start_time']));
        return $events;
    }

    private function date(string $key): ?string
    {
        $value = trim((string) Request::input($key, ''));
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function dateTime(string $key): ?string
    {
        $value = trim((string) Request::input($key, ''));
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
        return $dateTime !== false && $dateTime->format('Y-m-d\TH:i') === $value ? $dateTime->format('Y-m-d H:i:s') : null;
    }

    private function quarterHour(string $key): ?string
    {
        $value = trim((string) Request::input($key, ''));
        if (!preg_match('/^(?:0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $value) || ((int) substr($value, 3, 2)) % 15 !== 0) return null;
        return $value . ':00';
    }

    private function weeklySlots(): array
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
                return [];
            }
            $slots[] = ['weekday' => $day, 'start_time' => $start . ':00', 'end_time' => $end . ':00'];
        }
        return $slots;
    }

    private function groupBy(array $rows, string $key): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row[$key]][] = $row;
        }
        return $grouped;
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
