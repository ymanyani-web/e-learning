<?php

namespace App\Repositories;

use App\Models\Enrollment;

class EnrollmentRepository extends BaseRepository
{
    public function findById(int $id): ?Enrollment
    {
        $stmt = $this->pdo->prepare('SELECT * FROM enrollments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->map($row) : null;
    }

    public function findByStudent(int $studentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM enrollments WHERE student_id = :student_id ORDER BY id DESC');
        $stmt->execute(['student_id' => $studentId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function findByClass(int $classId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM enrollments WHERE class_id = :class_id ORDER BY id DESC');
        $stmt->execute(['class_id' => $classId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(Enrollment $enrollment): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO enrollments (student_id, class_id, academic_year, enrolled_at, status)
             VALUES (:student_id, :class_id, :academic_year, :enrolled_at, :status)'
        );

        $stmt->execute([
            'student_id' => $enrollment->getStudentId(),
            'class_id' => $enrollment->getClassId(),
            'academic_year' => $enrollment->getAcademicYear(),
            'enrolled_at' => $enrollment->getEnrolledAt(),
            'status' => $enrollment->getStatus(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(Enrollment $enrollment): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE enrollments
             SET student_id = :student_id,
                 class_id = :class_id,
                 academic_year = :academic_year,
                 enrolled_at = :enrolled_at,
                 status = :status
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $enrollment->getId(),
            'student_id' => $enrollment->getStudentId(),
            'class_id' => $enrollment->getClassId(),
            'academic_year' => $enrollment->getAcademicYear(),
            'enrolled_at' => $enrollment->getEnrolledAt(),
            'status' => $enrollment->getStatus(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM enrollments WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): Enrollment
    {
        return new Enrollment(
            (int) $row['id'],
            (int) $row['student_id'],
            (int) $row['class_id'],
            $row['academic_year'],
            $row['enrolled_at'] ?? null,
            $row['status']
        );
    }
}
