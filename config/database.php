<?php
require_once __DIR__ . '/constants.php';

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $env = [];
    $envFile = APP_ROOT . '/.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v);
        }
    }

    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $env['DB_HOST'] ?? 'localhost',
            $env['DB_NAME'] ?? 'school_sis'
        ),
        $env['DB_USER'] ?? 'root',
        $env['DB_PASS'] ?? '',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    return $pdo;
}
