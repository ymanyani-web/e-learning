<?php

namespace App\Models;

class ExamQuestion
{
    private ?int $id;
    private int $examId;
    private string $question;
    private string $type;
    private float $points;
    private int $position;

    public function __construct(
        ?int $id = null,
        int $examId = 0,
        string $question = '',
        string $type = 'MULTIPLE_CHOICE',
        float $points = 1.0,
        int $position = 0
    ) {
        $this->id = $id;
        $this->examId = $examId;
        $this->question = $question;
        $this->type = $type;
        $this->points = $points;
        $this->position = $position;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getExamId(): int { return $this->examId; }
    public function setExamId(int $examId): void { $this->examId = $examId; }

    public function getQuestion(): string { return $this->question; }
    public function setQuestion(string $question): void { $this->question = $question; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): void { $this->type = $type; }

    public function getPoints(): float { return $this->points; }
    public function setPoints(float $points): void { $this->points = $points; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): void { $this->position = $position; }
}
