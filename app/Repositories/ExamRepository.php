<?php

namespace App\Repositories;

use App\Models\Exam;

class ExamRepository extends BaseRepository
{
    public function findById(int $id): ?Exam
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exams WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exams WHERE course_id = :course_id ORDER BY start_at DESC');
        $stmt->execute(['course_id' => $courseId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(Exam $exam): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO exams
             (course_id, title, description, duration_minutes, total_points, max_attempts, start_at, end_at, status, created_at, updated_at)
             VALUES (:course_id, :title, :description, :duration_minutes, :total_points, :max_attempts, :start_at, :end_at, :status, NOW(), NOW())'
        );
        $stmt->execute([
            'course_id' => $exam->getCourseId(),
            'title' => $exam->getTitle(),
            'description' => $exam->getDescription(),
            'duration_minutes' => $exam->getDurationMinutes(),
            'total_points' => $exam->getTotalPoints(),
            'max_attempts' => $exam->getMaxAttempts(),
            'start_at' => $exam->getStartAt(),
            'end_at' => $exam->getEndAt(),
            'status' => $exam->getStatus(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(Exam $exam): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE exams SET
                course_id = :course_id,
                title = :title,
                description = :description,
                duration_minutes = :duration_minutes,
                total_points = :total_points,
                max_attempts = :max_attempts,
                start_at = :start_at,
                end_at = :end_at,
                status = :status,
                updated_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $exam->getId(),
            'course_id' => $exam->getCourseId(),
            'title' => $exam->getTitle(),
            'description' => $exam->getDescription(),
            'duration_minutes' => $exam->getDurationMinutes(),
            'total_points' => $exam->getTotalPoints(),
            'max_attempts' => $exam->getMaxAttempts(),
            'start_at' => $exam->getStartAt(),
            'end_at' => $exam->getEndAt(),
            'status' => $exam->getStatus(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM exams WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): Exam
    {
        return new Exam(
            (int) $row['id'],
            (int) $row['course_id'],
            $row['title'],
            $row['description'] ?? null,
            (int) $row['duration_minutes'],
            (float) $row['total_points'],
            (int) $row['max_attempts'],
            $row['start_at'] ?? null,
            $row['end_at'] ?? null,
            $row['status'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
