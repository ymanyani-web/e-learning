<?php

namespace App\Repositories;

use App\Models\AttendanceRecord;

class AttendanceRecordRepository extends BaseRepository
{
    public function findById(int $id): ?AttendanceRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM attendance_records WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findBySession(int $sessionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM attendance_records
             WHERE attendance_session_id = :session_id
             ORDER BY student_id'
        );
        $stmt->execute(['session_id' => $sessionId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function findByStudent(int $studentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM attendance_records
             WHERE student_id = :student_id
             ORDER BY id DESC'
        );
        $stmt->execute(['student_id' => $studentId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(AttendanceRecord $record): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO attendance_records
             (attendance_session_id, student_id, status, comment, created_at)
             VALUES (:attendance_session_id, :student_id, :status, :comment, NOW())'
        );
        $stmt->execute([
            'attendance_session_id' => $record->getAttendanceSessionId(),
            'student_id' => $record->getStudentId(),
            'status' => $record->getStatus(),
            'comment' => $record->getComment(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(AttendanceRecord $record): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE attendance_records SET
                attendance_session_id = :attendance_session_id,
                student_id = :student_id,
                status = :status,
                comment = :comment
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $record->getId(),
            'attendance_session_id' => $record->getAttendanceSessionId(),
            'student_id' => $record->getStudentId(),
            'status' => $record->getStatus(),
            'comment' => $record->getComment(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM attendance_records WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): AttendanceRecord
    {
        return new AttendanceRecord(
            (int) $row['id'],
            (int) $row['attendance_session_id'],
            (int) $row['student_id'],
            $row['status'],
            $row['comment'] ?? null,
            $row['created_at'] ?? null
        );
    }
}
