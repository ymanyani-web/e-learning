<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();
        return array_map(fn(array $row) => $this->map($row), $rows);
    }

    public function findByRole(string $role): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE role = :role ORDER BY last_name, first_name');
        $stmt->execute(['role' => $role]);

        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(User $user): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, password, role, status, created_at, updated_at)
             VALUES (:first_name, :last_name, :email, :password, :role, :status, NOW(), NOW())'
        );

        $stmt->execute([
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'role' => $user->getRole(),
            'status' => $user->getStatus() ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(User $user): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET first_name = :first_name,
                 last_name = :last_name,
                 email = :email,
                 password = :password,
                 role = :role,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'role' => $user->getRole(),
            'status' => $user->getStatus() ? 1 : 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): User
    {
        return new User(
            (int) $row['id'],
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['password'],
            $row['role'],
            (bool) $row['status'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            $row['phone'] ?? null,
            $row['personal_email'] ?? null,
            $row['birth_date'] ?? null,
            $row['preferred_language'] ?? 'en'
        );
    }
}
