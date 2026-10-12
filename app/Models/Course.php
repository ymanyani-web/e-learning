<?php

namespace App\Models;

class Course
{
    private ?int $id;
    private int $subjectId;
    private int $teacherId;
    private int $classId;
    private string $title;
    private ?string $description;
    private string $status;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        int $subjectId = 0,
        int $teacherId = 0,
        int $classId = 0,
        string $title = '',
        ?string $description = null,
        string $status = 'DRAFT',
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->subjectId = $subjectId;
        $this->teacherId = $teacherId;
        $this->classId = $classId;
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getSubjectId(): int { return $this->subjectId; }
    public function setSubjectId(int $subjectId): void { $this->subjectId = $subjectId; }

    public function getTeacherId(): int { return $this->teacherId; }
    public function setTeacherId(int $teacherId): void { $this->teacherId = $teacherId; }

    public function getClassId(): int { return $this->classId; }
    public function setClassId(int $classId): void { $this->classId = $classId; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): void { $this->description = $description; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): void { $this->updatedAt = $updatedAt; }
}
