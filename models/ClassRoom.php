<?php
require_once dirname(__DIR__) . '/config/database.php';

class ClassRoom
{
    public static function getAll(): array
    {
        return getDB()->query(
            'SELECT c.*, t.name AS homeroom_name
             FROM classes c
             LEFT JOIN teachers t ON c.homeroom_teacher_id = t.id
             ORDER BY c.grade_level, c.name'
        )->fetchAll();
    }

    public static function getById(int $id): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM classes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Untuk dropdown: returns array of ['id'=>…, 'label'=>'VII-A'] */
    public static function getForSelect(): array
    {
        return getDB()->query(
            "SELECT id, name AS label FROM classes ORDER BY grade_level, name"
        )->fetchAll();
    }

    public static function totalCount(): int
    {
        return (int) getDB()->query('SELECT COUNT(*) FROM classes')->fetchColumn();
    }
}
