<?php

namespace App\Repositories;

use App\Models\Assignment;

class AssignmentRepository extends BaseRepository
{
    public function findById(int $id): ?Assignment
    {
        $stmt = $this->pdo->prepare('SELECT * FROM assignments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM assignments WHERE course_id = :course_id ORDER BY due_at');
        $stmt->execute(['course_id' => $courseId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(Assignment $assignment): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO assignments (course_id, title, description, max_score, due_at, created_at, updated_at)
             VALUES (:course_id, :title, :description, :max_score, :due_at, NOW(), NOW())'
        );
        $stmt->execute([
            'course_id' => $assignment->getCourseId(),
            'title' => $assignment->getTitle(),
            'description' => $assignment->getDescription(),
            'max_score' => $assignment->getMaxScore(),
            'due_at' => $assignment->getDueAt(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(Assignment $assignment): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE assignments SET
                course_id = :course_id,
                title = :title,
                description = :description,
                max_score = :max_score,
                due_at = :due_at,
                updated_at = NOW()
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $assignment->getId(),
            'course_id' => $assignment->getCourseId(),
            'title' => $assignment->getTitle(),
            'description' => $assignment->getDescription(),
            'max_score' => $assignment->getMaxScore(),
            'due_at' => $assignment->getDueAt(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM assignments WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): Assignment
    {
        return new Assignment(
            (int) $row['id'],
            (int) $row['course_id'],
            $row['title'],
            $row['description'] ?? null,
            (float) $row['max_score'],
            $row['due_at'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
