<?php

namespace App\Models;

class Assignment
{
    private ?int $id;
    private int $courseId;
    private string $title;
    private ?string $description;
    private float $maxScore;
    private ?string $dueAt;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        int $courseId = 0,
        string $title = '',
        ?string $description = null,
        float $maxScore = 20.0,
        ?string $dueAt = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->courseId = $courseId;
        $this->title = $title;
        $this->description = $description;
        $this->maxScore = $maxScore;
        $this->dueAt = $dueAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getCourseId(): int { return $this->courseId; }
    public function setCourseId(int $courseId): void { $this->courseId = $courseId; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): void { $this->description = $description; }

    public function getMaxScore(): float { return $this->maxScore; }
    public function setMaxScore(float $maxScore): void { $this->maxScore = $maxScore; }

    public function getDueAt(): ?string { return $this->dueAt; }
    public function setDueAt(?string $dueAt): void { $this->dueAt = $dueAt; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): void { $this->updatedAt = $updatedAt; }
}
