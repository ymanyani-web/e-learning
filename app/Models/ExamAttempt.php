<?php

namespace App\Models;

class ExamAttempt
{
    private ?int $id;
    private int $examId;
    private int $studentId;
    private int $attemptNumber;
    private ?string $startedAt;
    private ?string $submittedAt;
    private ?float $score;
    private string $status;

    public function __construct(
        ?int $id = null,
        int $examId = 0,
        int $studentId = 0,
        int $attemptNumber = 1,
        ?string $startedAt = null,
        ?string $submittedAt = null,
        ?float $score = null,
        string $status = 'IN_PROGRESS'
    ) {
        $this->id = $id;
        $this->examId = $examId;
        $this->studentId = $studentId;
        $this->attemptNumber = $attemptNumber;
        $this->startedAt = $startedAt;
        $this->submittedAt = $submittedAt;
        $this->score = $score;
        $this->status = $status;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getExamId(): int { return $this->examId; }
    public function setExamId(int $examId): void { $this->examId = $examId; }

    public function getStudentId(): int { return $this->studentId; }
    public function setStudentId(int $studentId): void { $this->studentId = $studentId; }

    public function getAttemptNumber(): int { return $this->attemptNumber; }
    public function setAttemptNumber(int $attemptNumber): void { $this->attemptNumber = $attemptNumber; }

    public function getStartedAt(): ?string { return $this->startedAt; }
    public function setStartedAt(?string $startedAt): void { $this->startedAt = $startedAt; }

    public function getSubmittedAt(): ?string { return $this->submittedAt; }
    public function setSubmittedAt(?string $submittedAt): void { $this->submittedAt = $submittedAt; }

    public function getScore(): ?float { return $this->score; }
    public function setScore(?float $score): void { $this->score = $score; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
}
