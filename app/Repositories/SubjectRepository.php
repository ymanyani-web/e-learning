<?php

namespace App\Repositories;

use App\Models\Subject;

class SubjectRepository extends BaseRepository
{
    public function findById(int $id): ?Subject
    {
        $stmt = $this->pdo->prepare('SELECT * FROM subjects WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT * FROM subjects ORDER BY name')->fetchAll();
        return array_map(fn(array $row) => $this->map($row), $rows);
    }

    public function create(Subject $subject): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO subjects (name, code, description, coefficient, created_at, updated_at)
             VALUES (:name, :code, :description, :coefficient, NOW(), NOW())'
        );
        $stmt->execute([
            'name' => $subject->getName(),
            'code' => $subject->getCode(),
            'description' => $subject->getDescription(),
            'coefficient' => $subject->getCoefficient(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(Subject $subject): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE subjects
             SET name = :name, code = :code, description = :description,
                 coefficient = :coefficient, updated_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $subject->getId(),
            'name' => $subject->getName(),
            'code' => $subject->getCode(),
            'description' => $subject->getDescription(),
            'coefficient' => $subject->getCoefficient(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM subjects WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): Subject
    {
        return new Subject(
            (int) $row['id'],
            $row['name'],
            $row['code'] ?? null,
            $row['description'] ?? null,
            (float) $row['coefficient'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
