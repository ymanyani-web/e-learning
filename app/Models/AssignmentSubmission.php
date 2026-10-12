<?php

namespace App\Models;

class AssignmentSubmission
{
    private ?int $id;
    private int $assignmentId;
    private int $studentId;
    private ?string $filePath;
    private ?string $comment;
    private ?string $submittedAt;
    private ?float $score;
    private ?string $teacherFeedback;
    private string $status;

    public function __construct(
        ?int $id = null,
        int $assignmentId = 0,
        int $studentId = 0,
        ?string $filePath = null,
        ?string $comment = null,
        ?string $submittedAt = null,
        ?float $score = null,
        ?string $teacherFeedback = null,
        string $status = 'SUBMITTED'
    ) {
        $this->id = $id;
        $this->assignmentId = $assignmentId;
        $this->studentId = $studentId;
        $this->filePath = $filePath;
        $this->comment = $comment;
        $this->submittedAt = $submittedAt;
        $this->score = $score;
        $this->teacherFeedback = $teacherFeedback;
        $this->status = $status;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getAssignmentId(): int { return $this->assignmentId; }
    public function setAssignmentId(int $assignmentId): void { $this->assignmentId = $assignmentId; }

    public function getStudentId(): int { return $this->studentId; }
    public function setStudentId(int $studentId): void { $this->studentId = $studentId; }

    public function getFilePath(): ?string { return $this->filePath; }
    public function setFilePath(?string $filePath): void { $this->filePath = $filePath; }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): void { $this->comment = $comment; }

    public function getSubmittedAt(): ?string { return $this->submittedAt; }
    public function setSubmittedAt(?string $submittedAt): void { $this->submittedAt = $submittedAt; }

    public function getScore(): ?float { return $this->score; }
    public function setScore(?float $score): void { $this->score = $score; }

    public function getTeacherFeedback(): ?string { return $this->teacherFeedback; }
    public function setTeacherFeedback(?string $teacherFeedback): void { $this->teacherFeedback = $teacherFeedback; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
}
