<?php

namespace App\Repositories;

use App\Models\SchoolClass;

class SchoolClassRepository extends BaseRepository
{
    public function findById(int $id): ?SchoolClass
    {
        $stmt = $this->pdo->prepare('SELECT * FROM school_classes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->map($row) : null;
    }

    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT * FROM school_classes ORDER BY academic_year DESC, name')->fetchAll();
        return array_map(fn(array $row) => $this->map($row), $rows);
    }

    public function create(SchoolClass $class): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO school_classes (name, description, academic_year, status, created_at, updated_at)
             VALUES (:name, :description, :academic_year, :status, NOW(), NOW())'
        );
        $stmt->execute([
            'name' => $class->getName(),
            'description' => $class->getDescription(),
            'academic_year' => $class->getAcademicYear(),
            'status' => $class->getStatus() ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(SchoolClass $class): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE school_classes
             SET name = :name, description = :description, academic_year = :academic_year,
                 status = :status, updated_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $class->getId(),
            'name' => $class->getName(),
            'description' => $class->getDescription(),
            'academic_year' => $class->getAcademicYear(),
            'status' => $class->getStatus() ? 1 : 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM school_classes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): SchoolClass
    {
        return new SchoolClass(
            (int) $row['id'],
            $row['name'],
            $row['description'] ?? null,
            $row['academic_year'],
            (bool) $row['status'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
