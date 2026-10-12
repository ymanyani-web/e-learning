<?php

namespace App\Repositories;

use App\Models\CourseChapter;

class CourseChapterRepository extends BaseRepository
{
    public function findById(int $id): ?CourseChapter
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_chapters WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_chapters WHERE course_id = :course_id ORDER BY position');
        $stmt->execute(['course_id' => $courseId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(CourseChapter $chapter): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_chapters (course_id, title, content, position, status, created_at, updated_at)
             VALUES (:course_id, :title, :content, :position, :status, NOW(), NOW())'
        );
        $stmt->execute([
            'course_id' => $chapter->getCourseId(),
            'title' => $chapter->getTitle(),
            'content' => $chapter->getContent(),
            'position' => $chapter->getPosition(),
            'status' => $chapter->getStatus(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(CourseChapter $chapter): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE course_chapters SET
                course_id = :course_id,
                title = :title,
                content = :content,
                position = :position,
                status = :status,
                updated_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $chapter->getId(),
            'course_id' => $chapter->getCourseId(),
            'title' => $chapter->getTitle(),
            'content' => $chapter->getContent(),
            'position' => $chapter->getPosition(),
            'status' => $chapter->getStatus(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_chapters WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): CourseChapter
    {
        return new CourseChapter(
            (int) $row['id'],
            (int) $row['course_id'],
            $row['title'],
            $row['content'] ?? null,
            (int) $row['position'],
            $row['status'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
