<?php

namespace App\Models;

class User
{
    private ?int $id;
    private string $firstName;
    private string $lastName;
    private string $email;
    private string $password;
    private string $role;
    private bool $status;
    private ?string $createdAt;
    private ?string $updatedAt;
    private ?string $phone;
    private ?string $personalEmail;
    private ?string $birthDate;
    private string $preferredLanguage;

    public function __construct(
        ?int $id = null,
        string $firstName = '',
        string $lastName = '',
        string $email = '',
        string $password = '',
        string $role = 'STUDENT',
        bool $status = true,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $phone = null,
        ?string $personalEmail = null,
        ?string $birthDate = null,
        string $preferredLanguage = 'en'
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->phone = $phone;
        $this->personalEmail = $personalEmail;
        $this->birthDate = $birthDate;
        $this->preferredLanguage = $preferredLanguage;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): void { $this->firstName = $firstName; }

    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): void { $this->lastName = $lastName; }

    public function getFullName(): string { return trim($this->firstName . ' ' . $this->lastName); }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): void { $this->password = $password; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): void { $this->role = $role; }

    public function isActive(): bool { return $this->status; }
    public function getStatus(): bool { return $this->status; }
    public function setStatus(bool $status): void { $this->status = $status; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): void { $this->updatedAt = $updatedAt; }

    public function getPhone(): ?string { return $this->phone; }
    public function getPersonalEmail(): ?string { return $this->personalEmail; }
    public function getBirthDate(): ?string { return $this->birthDate; }
    public function getPreferredLanguage(): string { return $this->preferredLanguage; }
}
