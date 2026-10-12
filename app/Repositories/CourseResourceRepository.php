<?php

namespace App\Repositories;

use App\Models\CourseResource;

class CourseResourceRepository extends BaseRepository
{
    public function findById(int $id): ?CourseResource
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_resources WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByChapter(int $chapterId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_resources WHERE chapter_id = :chapter_id ORDER BY id');
        $stmt->execute(['chapter_id' => $chapterId]);
        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(CourseResource $resource): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_resources (chapter_id, title, type, file_path, url, created_at)
             VALUES (:chapter_id, :title, :type, :file_path, :url, NOW())'
        );
        $stmt->execute([
            'chapter_id' => $resource->getChapterId(),
            'title' => $resource->getTitle(),
            'type' => $resource->getType(),
            'file_path' => $resource->getFilePath(),
            'url' => $resource->getUrl(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(CourseResource $resource): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE course_resources SET
                chapter_id = :chapter_id,
                title = :title,
                type = :type,
                file_path = :file_path,
                url = :url
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $resource->getId(),
            'chapter_id' => $resource->getChapterId(),
            'title' => $resource->getTitle(),
            'type' => $resource->getType(),
            'file_path' => $resource->getFilePath(),
            'url' => $resource->getUrl(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_resources WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): CourseResource
    {
        return new CourseResource(
            (int) $row['id'],
            (int) $row['chapter_id'],
            $row['title'],
            $row['type'],
            $row['file_path'] ?? null,
            $row['url'] ?? null,
            $row['created_at'] ?? null
        );
    }
}
