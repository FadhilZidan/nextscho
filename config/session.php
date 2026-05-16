<?php
require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// CSRF token — dibuat sekali per session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): array
{
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? 'Guest',
        'role' => $_SESSION['role']      ?? '',
    ];
}

function csrfToken(): string
{
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = compact('type', 'msg');
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}
