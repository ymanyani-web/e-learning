<?php

namespace App\Models;

class ExamAnswer
{
    private ?int $id;
    private int $attemptId;
    private int $questionId;
    private ?int $optionId;
    private ?string $textAnswer;
    private ?float $pointsAwarded;

    public function __construct(
        ?int $id = null,
        int $attemptId = 0,
        int $questionId = 0,
        ?int $optionId = null,
        ?string $textAnswer = null,
        ?float $pointsAwarded = null
    ) {
        $this->id = $id;
        $this->attemptId = $attemptId;
        $this->questionId = $questionId;
        $this->optionId = $optionId;
        $this->textAnswer = $textAnswer;
        $this->pointsAwarded = $pointsAwarded;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getAttemptId(): int { return $this->attemptId; }
    public function setAttemptId(int $attemptId): void { $this->attemptId = $attemptId; }

    public function getQuestionId(): int { return $this->questionId; }
    public function setQuestionId(int $questionId): void { $this->questionId = $questionId; }

    public function getOptionId(): ?int { return $this->optionId; }
    public function setOptionId(?int $optionId): void { $this->optionId = $optionId; }

    public function getTextAnswer(): ?string { return $this->textAnswer; }
    public function setTextAnswer(?string $textAnswer): void { $this->textAnswer = $textAnswer; }

    public function getPointsAwarded(): ?float { return $this->pointsAwarded; }
    public function setPointsAwarded(?float $pointsAwarded): void { $this->pointsAwarded = $pointsAwarded; }
}
