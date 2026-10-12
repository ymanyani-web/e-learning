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
        $this->render('classes', [
            'classes' => $this->query('SELECT cl.id, cl.name, cl.academic_year, COUNT(DISTINCT e.student_id) AS student_count, COUNT(DISTINCT c.id) AS course_count FROM school_classes cl JOIN courses c ON c.class_id = cl.id LEFT JOIN enrollments e ON e.class_id = cl.id AND e.status = "ACTIVE" WHERE c.teacher_id = ? GROUP BY cl.id ORDER BY cl.academic_year DESC, cl.name', [Auth::id()]),
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
}
