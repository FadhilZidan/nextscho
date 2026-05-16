<?php
require_once dirname(__DIR__) . '/config/database.php';

class Subject
{
    public static function getAll(int $classId = 0, int $teacherId = 0): array
    {
        $sql    = 'SELECT s.*, t.name AS teacher_name, c.name AS class_name
                   FROM subjects s
                   LEFT JOIN teachers t ON s.teacher_id = t.id
                   LEFT JOIN classes  c ON s.class_id   = c.id
                   WHERE 1=1';
        $params = [];
        if ($classId > 0)   { $sql .= ' AND s.class_id   = ?'; $params[] = $classId; }
        if ($teacherId > 0) { $sql .= ' AND s.teacher_id = ?'; $params[] = $teacherId; }
        $sql .= ' ORDER BY c.name, s.name';
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT s.*, t.name AS teacher_name, c.name AS class_name
             FROM subjects s
             LEFT JOIN teachers t ON s.teacher_id = t.id
             LEFT JOIN classes  c ON s.class_id   = c.id
             WHERE s.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getByTeacher(int $teacherId): array
    {
        $stmt = getDB()->prepare(
            'SELECT s.*, c.name AS class_name
             FROM subjects s LEFT JOIN classes c ON s.class_id = c.id
             WHERE s.teacher_id = ? ORDER BY c.name, s.name'
        );
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public static function getForSelect(int $classId = 0): array
    {
        if ($classId > 0) {
            $stmt = getDB()->prepare('SELECT id, name FROM subjects WHERE class_id = ? ORDER BY name');
            $stmt->execute([$classId]);
            return $stmt->fetchAll();
        }
        return getDB()->query('SELECT id, name FROM subjects ORDER BY name')->fetchAll();
    }

    public static function create(array $d): int
    {
        $db   = getDB();
        $stmt = $db->prepare('INSERT INTO subjects (name, code, teacher_id, class_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$d['name'], $d['code'], $d['teacher_id'] ?: null, $d['class_id'] ?: null]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        getDB()->prepare('UPDATE subjects SET name=?, code=?, teacher_id=?, class_id=? WHERE id=?')
            ->execute([$d['name'], $d['code'], $d['teacher_id'] ?: null, $d['class_id'] ?: null, $id]);
    }

    public static function delete(int $id): void
    {
        getDB()->prepare('DELETE FROM subjects WHERE id = ?')->execute([$id]);
    }

    public static function totalCount(): int
    {
        return (int) getDB()->query('SELECT COUNT(*) FROM subjects')->fetchColumn();
    }
}
