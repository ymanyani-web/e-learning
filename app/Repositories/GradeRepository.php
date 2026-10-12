<?php

namespace App\Repositories;

use App\Models\Grade;

class GradeRepository extends BaseRepository
{
    public function findById(int $id): ?Grade
    {
        $stmt = $this->pdo->prepare('SELECT * FROM grades WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByStudent(int $studentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM grades WHERE student_id = :student_id ORDER BY created_at DESC');
        $stmt->execute(['student_id' => $studentId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function findByCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM grades WHERE course_id = :course_id ORDER BY created_at DESC');
        $stmt->execute(['course_id' => $courseId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(Grade $grade): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO grades (student_id, course_id, type, reference_id, score, max_score, created_at)
             VALUES (:student_id, :course_id, :type, :reference_id, :score, :max_score, NOW())'
        );
        $stmt->execute([
            'student_id' => $grade->getStudentId(),
            'course_id' => $grade->getCourseId(),
            'type' => $grade->getType(),
            'reference_id' => $grade->getReferenceId(),
            'score' => $grade->getScore(),
            'max_score' => $grade->getMaxScore(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(Grade $grade): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE grades SET
                student_id = :student_id,
                course_id = :course_id,
                type = :type,
                reference_id = :reference_id,
                score = :score,
                max_score = :max_score
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $grade->getId(),
            'student_id' => $grade->getStudentId(),
            'course_id' => $grade->getCourseId(),
            'type' => $grade->getType(),
            'reference_id' => $grade->getReferenceId(),
            'score' => $grade->getScore(),
            'max_score' => $grade->getMaxScore(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM grades WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): Grade
    {
        return new Grade(
            (int) $row['id'],
            (int) $row['student_id'],
            (int) $row['course_id'],
            $row['type'],
            isset($row['reference_id']) ? (int) $row['reference_id'] : null,
            (float) $row['score'],
            (float) $row['max_score'],
            $row['created_at'] ?? null
        );
    }
}
