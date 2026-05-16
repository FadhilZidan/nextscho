<?php
require_once dirname(__DIR__) . '/config/database.php';

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = getDB()->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function emailExists(string $email, int $excludeId = 0): bool
    {
        $stmt = getDB()->prepare('SELECT 1 FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $excludeId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(array $data): int
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'],
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $fields = ['name = ?', 'email = ?'];
        $params = [$data['name'], $data['email']];

        if (!empty($data['password'])) {
            $fields[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $params[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        getDB()->prepare($sql)->execute($params);
    }

    public static function deleteById(int $id): void
    {
        getDB()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
}
