<?php

namespace App\Repositories;

use App\Models\Notification;

class NotificationRepository extends BaseRepository
{
    public function findById(int $id): ?Notification
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByUser(int $userId, bool $onlyUnread = false): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = :user_id';

        if ($onlyUnread) {
            $sql .= ' AND is_read = 0';
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return array_map(fn(array $row) => $this->map($row), $stmt->fetchAll());
    }

    public function create(Notification $notification): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications
             (user_id, title, message, type, is_read, created_at, read_at)
             VALUES (:user_id, :title, :message, :type, :is_read, NOW(), :read_at)'
        );
        $stmt->execute([
            'user_id' => $notification->getUserId(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'type' => $notification->getType(),
            'is_read' => $notification->isRead() ? 1 : 0,
            'read_at' => $notification->getReadAt(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function markAsRead(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = :id'
        );
        return $stmt->execute(['id' => $id]);
    }

    public function update(Notification $notification): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notifications SET
                user_id = :user_id,
                title = :title,
                message = :message,
                type = :type,
                is_read = :is_read,
                read_at = :read_at
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $notification->getId(),
            'user_id' => $notification->getUserId(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'type' => $notification->getType(),
            'is_read' => $notification->isRead() ? 1 : 0,
            'read_at' => $notification->getReadAt(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM notifications WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function map(array $row): Notification
    {
        return new Notification(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['title'],
            $row['message'],
            $row['type'] ?? null,
            (bool) $row['is_read'],
            $row['created_at'] ?? null,
            $row['read_at'] ?? null
        );
    }
}
