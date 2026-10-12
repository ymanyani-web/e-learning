<?php

namespace App\Models;

class Exam
{
    private ?int $id;
    private int $courseId;
    private string $title;
    private ?string $description;
    private int $durationMinutes;
    private float $totalPoints;
    private int $maxAttempts;
    private ?string $startAt;
    private ?string $endAt;
    private string $status;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        int $courseId = 0,
        string $title = '',
        ?string $description = null,
        int $durationMinutes = 60,
        float $totalPoints = 20.0,
        int $maxAttempts = 1,
        ?string $startAt = null,
        ?string $endAt = null,
        string $status = 'DRAFT',
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->courseId = $courseId;
        $this->title = $title;
        $this->description = $description;
        $this->durationMinutes = $durationMinutes;
        $this->totalPoints = $totalPoints;
        $this->maxAttempts = $maxAttempts;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->status = $status;
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

    public function getDurationMinutes(): int { return $this->durationMinutes; }
    public function setDurationMinutes(int $durationMinutes): void { $this->durationMinutes = $durationMinutes; }

    public function getTotalPoints(): float { return $this->totalPoints; }
    public function setTotalPoints(float $totalPoints): void { $this->totalPoints = $totalPoints; }

    public function getMaxAttempts(): int { return $this->maxAttempts; }
    public function setMaxAttempts(int $maxAttempts): void { $this->maxAttempts = $maxAttempts; }

    public function getStartAt(): ?string { return $this->startAt; }
    public function setStartAt(?string $startAt): void { $this->startAt = $startAt; }

    public function getEndAt(): ?string { return $this->endAt; }
    public function setEndAt(?string $endAt): void { $this->endAt = $endAt; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): void { $this->updatedAt = $updatedAt; }
}
