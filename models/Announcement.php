<?php
require_once dirname(__DIR__) . '/config/database.php';

class Announcement
{
    public static function getAll(int $limit = 20, int $offset = 0): array
    {
        $stmt = getDB()->prepare(
            'SELECT a.*, u.name AS creator_name
             FROM announcements a LEFT JOIN users u ON a.created_by = u.id
             ORDER BY a.created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public static function count(): int
    {
        return (int) getDB()->query('SELECT COUNT(*) FROM announcements')->fetchColumn();
    }

    public static function getById(int $id): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM announcements WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d): int
    {
        $db   = getDB();
        $stmt = $db->prepare('INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)');
        $stmt->execute([$d['title'], $d['content'], $d['created_by']]);
        return (int) $db->lastInsertId();
    }

    public static function delete(int $id): void
    {
        getDB()->prepare('DELETE FROM announcements WHERE id = ?')->execute([$id]);
    }

    public static function getLatest(int $n = 5): array
    {
        $stmt = getDB()->prepare(
            'SELECT a.*, u.name AS creator_name FROM announcements a
             LEFT JOIN users u ON a.created_by = u.id
             ORDER BY a.created_at DESC LIMIT ?'
        );
        $stmt->execute([$n]);
        return $stmt->fetchAll();
    }
}
