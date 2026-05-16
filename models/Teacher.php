<?php
require_once dirname(__DIR__) . '/config/database.php';

class Teacher
{
    public static function getAll(string $search = '', int $limit = 100, int $offset = 0): array
    {
        $sql    = 'SELECT t.*, u.email FROM teachers t LEFT JOIN users u ON t.user_id = u.id WHERE 1=1';
        $params = [];
        if ($search !== '') {
            $sql     .= ' AND (t.name LIKE ? OR t.nip LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $sql     .= ' ORDER BY t.name LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function count(string $search = ''): int
    {
        $sql    = 'SELECT COUNT(*) FROM teachers WHERE 1=1';
        $params = [];
        if ($search !== '') {
            $sql     .= ' AND (name LIKE ? OR nip LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function getById(int $id): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT t.*, u.email FROM teachers t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getByUserId(int $userId): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM teachers WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public static function getForSelect(): array
    {
        return getDB()->query('SELECT id, name FROM teachers ORDER BY name')->fetchAll();
    }

    public static function create(array $d): int
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO teachers (user_id, nip, name, subjects, phone, photo) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['user_id']  ?? null,
            $d['nip']      ?: null,
            $d['name'],
            $d['subjects'] ?? null,
            $d['phone']    ?? null,
            $d['photo']    ?? 'default.png',
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        getDB()->prepare(
            'UPDATE teachers SET nip=?, name=?, subjects=?, phone=?, photo=? WHERE id=?'
        )->execute([
            $d['nip']      ?: null,
            $d['name'],
            $d['subjects'] ?? null,
            $d['phone']    ?? null,
            $d['photo']    ?? 'default.png',
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        getDB()->prepare('DELETE FROM teachers WHERE id = ?')->execute([$id]);
    }

    public static function nipExists(string $nip, int $excludeId = 0): bool
    {
        $stmt = getDB()->prepare('SELECT 1 FROM teachers WHERE nip = ? AND id != ? AND nip != ""');
        $stmt->execute([$nip, $excludeId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function totalCount(): int
    {
        return (int) getDB()->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
    }
}
