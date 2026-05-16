<?php
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    $map = [
        'admin' => BASE_URL . '/pages/admin/dashboard.php',
        'guru'  => BASE_URL . '/pages/teacher/dashboard.php',
        'siswa' => BASE_URL . '/pages/student/dashboard.php',
    ];
    header('Location: ' . ($map[$_SESSION['role']] ?? BASE_URL . '/auth/login.php'));
} else {
    header('Location: ' . BASE_URL . '/auth/login.php');
}
exit;
