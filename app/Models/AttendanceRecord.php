<?php

namespace App\Models;

class AttendanceRecord
{
    private ?int $id;
    private int $attendanceSessionId;
    private int $studentId;
    private string $status;
    private ?string $comment;
    private ?string $createdAt;

    public function __construct(
        ?int $id = null,
        int $attendanceSessionId = 0,
        int $studentId = 0,
        string $status = 'PRESENT',
        ?string $comment = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->attendanceSessionId = $attendanceSessionId;
        $this->studentId = $studentId;
        $this->status = $status;
        $this->comment = $comment;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getAttendanceSessionId(): int { return $this->attendanceSessionId; }
    public function setAttendanceSessionId(int $attendanceSessionId): void { $this->attendanceSessionId = $attendanceSessionId; }

    public function getStudentId(): int { return $this->studentId; }
    public function setStudentId(int $studentId): void { $this->studentId = $studentId; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): void { $this->comment = $comment; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }
}
