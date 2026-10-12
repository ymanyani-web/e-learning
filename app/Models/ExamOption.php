<?php

namespace App\Models;

class ExamOption
{
    private ?int $id;
    private int $questionId;
    private string $optionText;
    private bool $isCorrect;

    public function __construct(
        ?int $id = null,
        int $questionId = 0,
        string $optionText = '',
        bool $isCorrect = false
    ) {
        $this->id = $id;
        $this->questionId = $questionId;
        $this->optionText = $optionText;
        $this->isCorrect = $isCorrect;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getQuestionId(): int { return $this->questionId; }
    public function setQuestionId(int $questionId): void { $this->questionId = $questionId; }

    public function getOptionText(): string { return $this->optionText; }
    public function setOptionText(string $optionText): void { $this->optionText = $optionText; }

    public function isCorrect(): bool { return $this->isCorrect; }
    public function setIsCorrect(bool $isCorrect): void { $this->isCorrect = $isCorrect; }
}
