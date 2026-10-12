<?php

namespace App\Repositories;

use App\Models\Course;

class CourseRepository extends BaseRepository
{
    public function findById(int $id): ?Course
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT * FROM courses ORDER BY id DESC')->fetchAll();
        return array_map(fn(array $row) => $this->map($row), $rows);
    }

    public function findByTeacher(int $teacherId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE teacher_id = :teacher_id ORDER BY id DESC');
        $stmt->execute(['teacher_id' => $teacherId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function findByClass(int $classId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE class_id = :class_id ORDER BY id DESC');
        $stmt->execute(['class_id' => $classId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(Course $course): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO courses (subject_id, teacher_id, class_id, title, description, status, created_at, updated_at)
             VALUES (:subject_id, :teacher_id, :class_id, :title, :description, :status, NOW(), NOW())'
        );
        $stmt->execute([
            'subject_id' => $course->getSubjectId(),
            'teacher_id' => $course->getTeacherId(),
            'class_id' => $course->getClassId(),
            'title' => $course->getTitle(),
            'description' => $course->getDescription(),
            'status' => $course->getStatus(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(Course $course): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE courses SET
                subject_id = :subject_id,
                teacher_id = :teacher_id,
                class_id = :class_id,
                title = :title,
                description = :description,
                status = :status,
                updated_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $course->getId(),
            'subject_id' => $course->getSubjectId(),
            'teacher_id' => $course->getTeacherId(),
            'class_id' => $course->getClassId(),
            'title' => $course->getTitle(),
            'description' => $course->getDescription(),
            'status' => $course->getStatus(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM courses WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): Course
    {
        return new Course(
            (int) $row['id'],
            (int) $row['subject_id'],
            (int) $row['teacher_id'],
            (int) $row['class_id'],
            $row['title'],
            $row['description'] ?? null,
            $row['status'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
