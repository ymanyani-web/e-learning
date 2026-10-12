<?php

namespace App\Repositories;

use App\Models\ExamOption;

class ExamOptionRepository extends BaseRepository
{
    public function findById(int $id): ?ExamOption
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_options WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByQuestion(int $questionId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_options WHERE question_id = :question_id ORDER BY id');
        $stmt->execute(['question_id' => $questionId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(ExamOption $option): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO exam_options (question_id, option_text, is_correct)
             VALUES (:question_id, :option_text, :is_correct)'
        );
        $stmt->execute([
            'question_id' => $option->getQuestionId(),
            'option_text' => $option->getOptionText(),
            'is_correct' => $option->isCorrect() ? 1 : 0,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(ExamOption $option): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE exam_options SET
                question_id = :question_id,
                option_text = :option_text,
                is_correct = :is_correct
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $option->getId(),
            'question_id' => $option->getQuestionId(),
            'option_text' => $option->getOptionText(),
            'is_correct' => $option->isCorrect() ? 1 : 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM exam_options WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): ExamOption
    {
        return new ExamOption(
            (int) $row['id'],
            (int) $row['question_id'],
            $row['option_text'],
            (bool) $row['is_correct']
        );
    }
}
