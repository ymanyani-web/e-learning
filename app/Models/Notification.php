<?php

namespace App\Models;

class Notification
{
    private ?int $id;
    private int $userId;
    private string $title;
    private string $message;
    private ?string $type;
    private bool $isRead;
    private ?string $createdAt;
    private ?string $readAt;

    public function __construct(
        ?int $id = null,
        int $userId = 0,
        string $title = '',
        string $message = '',
        ?string $type = null,
        bool $isRead = false,
        ?string $createdAt = null,
        ?string $readAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->isRead = $isRead;
        $this->createdAt = $createdAt;
        $this->readAt = $readAt;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): void { $this->userId = $userId; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): void { $this->message = $message; }

    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): void { $this->type = $type; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): void { $this->isRead = $isRead; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    public function getReadAt(): ?string { return $this->readAt; }
    public function setReadAt(?string $readAt): void { $this->readAt = $readAt; }
}
