<?php

namespace App\Repositories;

use App\Models\ExamAttempt;

class ExamAttemptRepository extends BaseRepository
{
    public function findById(int $id): ?ExamAttempt
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_attempts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByExamAndStudent(int $examId, int $studentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM exam_attempts
             WHERE exam_id = :exam_id AND student_id = :student_id
             ORDER BY attempt_number'
        );
        $stmt->execute([
            'exam_id' => $examId,
            'student_id' => $studentId,
        ]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(ExamAttempt $attempt): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO exam_attempts
             (exam_id, student_id, attempt_number, started_at, submitted_at, score, status)
             VALUES (:exam_id, :student_id, :attempt_number, :started_at, :submitted_at, :score, :status)'
        );
        $stmt->execute([
            'exam_id' => $attempt->getExamId(),
            'student_id' => $attempt->getStudentId(),
            'attempt_number' => $attempt->getAttemptNumber(),
            'started_at' => $attempt->getStartedAt(),
            'submitted_at' => $attempt->getSubmittedAt(),
            'score' => $attempt->getScore(),
            'status' => $attempt->getStatus(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(ExamAttempt $attempt): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE exam_attempts SET
                exam_id = :exam_id,
                student_id = :student_id,
                attempt_number = :attempt_number,
                started_at = :started_at,
                submitted_at = :submitted_at,
                score = :score,
                status = :status
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $attempt->getId(),
            'exam_id' => $attempt->getExamId(),
            'student_id' => $attempt->getStudentId(),
            'attempt_number' => $attempt->getAttemptNumber(),
            'started_at' => $attempt->getStartedAt(),
            'submitted_at' => $attempt->getSubmittedAt(),
            'score' => $attempt->getScore(),
            'status' => $attempt->getStatus(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM exam_attempts WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): ExamAttempt
    {
        return new ExamAttempt(
            (int) $row['id'],
            (int) $row['exam_id'],
            (int) $row['student_id'],
            (int) $row['attempt_number'],
            $row['started_at'] ?? null,
            $row['submitted_at'] ?? null,
            isset($row['score']) ? (float) $row['score'] : null,
            $row['status']
        );
    }
}
