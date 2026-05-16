<?php
require_once dirname(__DIR__) . '/config/session.php';

function requireRole(string ...$roles): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    if (!empty($roles) && !in_array($_SESSION['role'], $roles, true)) {
        $map = [
            'admin' => BASE_URL . '/pages/admin/dashboard.php',
            'guru'  => BASE_URL . '/pages/teacher/dashboard.php',
            'siswa' => BASE_URL . '/pages/student/dashboard.php',
        ];
        header('Location: ' . ($map[$_SESSION['role']] ?? BASE_URL . '/auth/login.php'));
        exit;
    }
}
