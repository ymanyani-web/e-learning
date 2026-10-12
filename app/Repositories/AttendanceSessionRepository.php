<?php

namespace App\Repositories;

use App\Models\AttendanceSession;

class AttendanceSessionRepository extends BaseRepository
{
    public function findById(int $id): ?AttendanceSession
    {
        $stmt = $this->pdo->prepare('SELECT * FROM attendance_sessions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM attendance_sessions
             WHERE course_id = :course_id
             ORDER BY session_date DESC, start_time DESC'
        );
        $stmt->execute(['course_id' => $courseId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(AttendanceSession $session): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO attendance_sessions
             (course_id, session_date, start_time, end_time, created_by, created_at)
             VALUES (:course_id, :session_date, :start_time, :end_time, :created_by, NOW())'
        );
        $stmt->execute([
            'course_id' => $session->getCourseId(),
            'session_date' => $session->getSessionDate(),
            'start_time' => $session->getStartTime(),
            'end_time' => $session->getEndTime(),
            'created_by' => $session->getCreatedBy(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(AttendanceSession $session): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE attendance_sessions SET
                course_id = :course_id,
                session_date = :session_date,
                start_time = :start_time,
                end_time = :end_time,
                created_by = :created_by
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $session->getId(),
            'course_id' => $session->getCourseId(),
            'session_date' => $session->getSessionDate(),
            'start_time' => $session->getStartTime(),
            'end_time' => $session->getEndTime(),
            'created_by' => $session->getCreatedBy(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM attendance_sessions WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): AttendanceSession
    {
        return new AttendanceSession(
            (int) $row['id'],
            (int) $row['course_id'],
            $row['session_date'],
            $row['start_time'],
            $row['end_time'],
            (int) $row['created_by'],
            $row['created_at'] ?? null
        );
    }
}
