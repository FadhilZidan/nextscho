<?php
require_once dirname(__DIR__) . '/config/database.php';

class Student
{
    public static function getAll(string $search, int $classId, int $limit, int $offset): array
    {
        $sql    = 'SELECT s.*, c.name AS class_name
                   FROM students s
                   LEFT JOIN classes c ON s.class_id = c.id
                   WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql     .= ' AND (s.name LIKE ? OR s.nis LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($classId > 0) {
            $sql     .= ' AND s.class_id = ?';
            $params[] = $classId;
        }

        $sql     .= ' ORDER BY s.name LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function count(string $search, int $classId): int
    {
        $sql    = 'SELECT COUNT(*) FROM students s WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql     .= ' AND (s.name LIKE ? OR s.nis LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($classId > 0) {
            $sql     .= ' AND s.class_id = ?';
            $params[] = $classId;
        }

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function getById(int $id): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT s.*, c.name AS class_name, u.email
             FROM students s
             LEFT JOIN classes c ON s.class_id = c.id
             LEFT JOIN users u   ON s.user_id  = u.id
             WHERE s.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d): int
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO students (user_id, nis, name, class_id, gender, birth_date, address, phone, photo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['user_id']    ?? null,
            $d['nis'],
            $d['name'],
            $d['class_id']   ?: null,
            $d['gender'],
            $d['birth_date'] ?: null,
            $d['address']    ?? null,
            $d['phone']      ?? null,
            $d['photo']      ?? 'default.png',
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        getDB()->prepare(
            'UPDATE students
             SET nis=?, name=?, class_id=?, gender=?, birth_date=?, address=?, phone=?, photo=?
             WHERE id=?'
        )->execute([
            $d['nis'],
            $d['name'],
            $d['class_id']   ?: null,
            $d['gender'],
            $d['birth_date'] ?: null,
            $d['address']    ?? null,
            $d['phone']      ?? null,
            $d['photo']      ?? 'default.png',
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        getDB()->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
    }

    public static function nisExists(string $nis, int $excludeId = 0): bool
    {
        $stmt = getDB()->prepare('SELECT 1 FROM students WHERE nis = ? AND id != ?');
        $stmt->execute([$nis, $excludeId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function totalCount(): int
    {
        return (int) getDB()->query('SELECT COUNT(*) FROM students')->fetchColumn();
    }

    public static function getByUserId(int $userId): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT s.*, c.name AS class_name
             FROM students s
             LEFT JOIN classes c ON s.class_id = c.id
             WHERE s.user_id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }
}
