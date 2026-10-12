<?php

namespace App\Repositories;

use App\Models\ExamAnswer;

class ExamAnswerRepository extends BaseRepository
{
    public function findById(int $id): ?ExamAnswer
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_answers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByAttempt(int $attemptId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_answers WHERE attempt_id = :attempt_id ORDER BY question_id');
        $stmt->execute(['attempt_id' => $attemptId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(ExamAnswer $answer): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO exam_answers (attempt_id, question_id, option_id, text_answer, points_awarded)
             VALUES (:attempt_id, :question_id, :option_id, :text_answer, :points_awarded)'
        );
        $stmt->execute([
            'attempt_id' => $answer->getAttemptId(),
            'question_id' => $answer->getQuestionId(),
            'option_id' => $answer->getOptionId(),
            'text_answer' => $answer->getTextAnswer(),
            'points_awarded' => $answer->getPointsAwarded(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(ExamAnswer $answer): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE exam_answers SET
                attempt_id = :attempt_id,
                question_id = :question_id,
                option_id = :option_id,
                text_answer = :text_answer,
                points_awarded = :points_awarded
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $answer->getId(),
            'attempt_id' => $answer->getAttemptId(),
            'question_id' => $answer->getQuestionId(),
            'option_id' => $answer->getOptionId(),
            'text_answer' => $answer->getTextAnswer(),
            'points_awarded' => $answer->getPointsAwarded(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM exam_answers WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): ExamAnswer
    {
        return new ExamAnswer(
            (int) $row['id'],
            (int) $row['attempt_id'],
            (int) $row['question_id'],
            isset($row['option_id']) ? (int) $row['option_id'] : null,
            $row['text_answer'] ?? null,
            isset($row['points_awarded']) ? (float) $row['points_awarded'] : null
        );
    }
}
