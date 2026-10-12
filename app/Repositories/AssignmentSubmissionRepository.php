<?php

namespace App\Repositories;

use App\Models\AssignmentSubmission;

class AssignmentSubmissionRepository extends BaseRepository
{
    public function findById(int $id): ?AssignmentSubmission
    {
        $stmt = $this->pdo->prepare('SELECT * FROM assignment_submissions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByAssignment(int $assignmentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM assignment_submissions WHERE assignment_id = :assignment_id ORDER BY submitted_at DESC');
        $stmt->execute(['assignment_id' => $assignmentId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function findByStudent(int $studentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM assignment_submissions WHERE student_id = :student_id ORDER BY submitted_at DESC');
        $stmt->execute(['student_id' => $studentId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(AssignmentSubmission $submission): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO assignment_submissions
             (assignment_id, student_id, file_path, comment, submitted_at, score, teacher_feedback, status)
             VALUES (:assignment_id, :student_id, :file_path, :comment, :submitted_at, :score, :teacher_feedback, :status)'
        );
        $stmt->execute([
            'assignment_id' => $submission->getAssignmentId(),
            'student_id' => $submission->getStudentId(),
            'file_path' => $submission->getFilePath(),
            'comment' => $submission->getComment(),
            'submitted_at' => $submission->getSubmittedAt(),
            'score' => $submission->getScore(),
            'teacher_feedback' => $submission->getTeacherFeedback(),
            'status' => $submission->getStatus(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(AssignmentSubmission $submission): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE assignment_submissions SET
                assignment_id = :assignment_id,
                student_id = :student_id,
                file_path = :file_path,
                comment = :comment,
                submitted_at = :submitted_at,
                score = :score,
                teacher_feedback = :teacher_feedback,
                status = :status
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $submission->getId(),
            'assignment_id' => $submission->getAssignmentId(),
            'student_id' => $submission->getStudentId(),
            'file_path' => $submission->getFilePath(),
            'comment' => $submission->getComment(),
            'submitted_at' => $submission->getSubmittedAt(),
            'score' => $submission->getScore(),
            'teacher_feedback' => $submission->getTeacherFeedback(),
            'status' => $submission->getStatus(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM assignment_submissions WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): AssignmentSubmission
    {
        return new AssignmentSubmission(
            (int) $row['id'],
            (int) $row['assignment_id'],
            (int) $row['student_id'],
            $row['file_path'] ?? null,
            $row['comment'] ?? null,
            $row['submitted_at'] ?? null,
            isset($row['score']) ? (float) $row['score'] : null,
            $row['teacher_feedback'] ?? null,
            $row['status']
        );
    }
}
