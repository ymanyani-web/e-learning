<?php

namespace App\Models;

class Enrollment
{
    private ?int $id;
    private int $studentId;
    private int $classId;
    private string $academicYear;
    private ?string $enrolledAt;
    private string $status;

    public function __construct(
        ?int $id = null,
        int $studentId = 0,
        int $classId = 0,
        string $academicYear = '',
        ?string $enrolledAt = null,
        string $status = 'ACTIVE'
    ) {
        $this->id = $id;
        $this->studentId = $studentId;
        $this->classId = $classId;
        $this->academicYear = $academicYear;
        $this->enrolledAt = $enrolledAt;
        $this->status = $status;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getStudentId(): int { return $this->studentId; }
    public function setStudentId(int $studentId): void { $this->studentId = $studentId; }

    public function getClassId(): int { return $this->classId; }
    public function setClassId(int $classId): void { $this->classId = $classId; }

    public function getAcademicYear(): string { return $this->academicYear; }
    public function setAcademicYear(string $academicYear): void { $this->academicYear = $academicYear; }

    public function getEnrolledAt(): ?string { return $this->enrolledAt; }
    public function setEnrolledAt(?string $enrolledAt): void { $this->enrolledAt = $enrolledAt; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
}
