<?php

namespace App\Models;

class CourseResource
{
    private ?int $id;
    private int $chapterId;
    private string $title;
    private string $type;
    private ?string $filePath;
    private ?string $url;
    private ?string $createdAt;

    public function __construct(
        ?int $id = null,
        int $chapterId = 0,
        string $title = '',
        string $type = 'OTHER',
        ?string $filePath = null,
        ?string $url = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->chapterId = $chapterId;
        $this->title = $title;
        $this->type = $type;
        $this->filePath = $filePath;
        $this->url = $url;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getChapterId(): int { return $this->chapterId; }
    public function setChapterId(int $chapterId): void { $this->chapterId = $chapterId; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): void { $this->type = $type; }

    public function getFilePath(): ?string { return $this->filePath; }
    public function setFilePath(?string $filePath): void { $this->filePath = $filePath; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): void { $this->url = $url; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }
}
