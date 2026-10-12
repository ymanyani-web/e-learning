<?php

namespace App\Models;

class AttendanceSession
{
    private ?int $id;
    private int $courseId;
    private string $sessionDate;
    private string $startTime;
    private string $endTime;
    private int $createdBy;
    private ?string $createdAt;

    public function __construct(
        ?int $id = null,
        int $courseId = 0,
        string $sessionDate = '',
        string $startTime = '',
        string $endTime = '',
        int $createdBy = 0,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->courseId = $courseId;
        $this->sessionDate = $sessionDate;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getCourseId(): int { return $this->courseId; }
    public function setCourseId(int $courseId): void { $this->courseId = $courseId; }

    public function getSessionDate(): string { return $this->sessionDate; }
    public function setSessionDate(string $sessionDate): void { $this->sessionDate = $sessionDate; }

    public function getStartTime(): string { return $this->startTime; }
    public function setStartTime(string $startTime): void { $this->startTime = $startTime; }

    public function getEndTime(): string { return $this->endTime; }
    public function setEndTime(string $endTime): void { $this->endTime = $endTime; }

    public function getCreatedBy(): int { return $this->createdBy; }
    public function setCreatedBy(int $createdBy): void { $this->createdBy = $createdBy; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }
}
