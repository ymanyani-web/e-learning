<?php

namespace App\Repositories;

use App\Models\ExamQuestion;

class ExamQuestionRepository extends BaseRepository
{
    public function findById(int $id): ?ExamQuestion
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_questions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByExam(int $examId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_questions WHERE exam_id = :exam_id ORDER BY position');
        $stmt->execute(['exam_id' => $examId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(ExamQuestion $question): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO exam_questions (exam_id, question, type, points, position)
             VALUES (:exam_id, :question, :type, :points, :position)'
        );
        $stmt->execute([
            'exam_id' => $question->getExamId(),
            'question' => $question->getQuestion(),
            'type' => $question->getType(),
            'points' => $question->getPoints(),
            'position' => $question->getPosition(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(ExamQuestion $question): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE exam_questions SET
                exam_id = :exam_id,
                question = :question,
                type = :type,
                points = :points,
                position = :position
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $question->getId(),
            'exam_id' => $question->getExamId(),
            'question' => $question->getQuestion(),
            'type' => $question->getType(),
            'points' => $question->getPoints(),
            'position' => $question->getPosition(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM exam_questions WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): ExamQuestion
    {
        return new ExamQuestion(
            (int) $row['id'],
            (int) $row['exam_id'],
            $row['question'],
            $row['type'],
            (float) $row['points'],
            (int) $row['position']
        );
    }
}
