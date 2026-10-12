<?php

namespace App\Models;

class Grade
{
    private ?int $id;
    private int $studentId;
    private int $courseId;
    private string $type;
    private ?int $referenceId;
    private float $score;
    private float $maxScore;
    private ?string $createdAt;

    public function __construct(
        ?int $id = null,
        int $studentId = 0,
        int $courseId = 0,
        string $type = '',
        ?int $referenceId = null,
        float $score = 0.0,
        float $maxScore = 20.0,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->studentId = $studentId;
        $this->courseId = $courseId;
        $this->type = $type;
        $this->referenceId = $referenceId;
        $this->score = $score;
        $this->maxScore = $maxScore;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getStudentId(): int { return $this->studentId; }
    public function setStudentId(int $studentId): void { $this->studentId = $studentId; }

    public function getCourseId(): int { return $this->courseId; }
    public function setCourseId(int $courseId): void { $this->courseId = $courseId; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): void { $this->type = $type; }

    public function getReferenceId(): ?int { return $this->referenceId; }
    public function setReferenceId(?int $referenceId): void { $this->referenceId = $referenceId; }

    public function getScore(): float { return $this->score; }
    public function setScore(float $score): void { $this->score = $score; }

    public function getMaxScore(): float { return $this->maxScore; }
    public function setMaxScore(float $maxScore): void { $this->maxScore = $maxScore; }

    public function getPercentage(): float
    {
        return $this->maxScore > 0 ? ($this->score / $this->maxScore) * 100 : 0.0;
    }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }
}
