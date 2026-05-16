<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/AcademicCalendar.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/academic_calendar/index.php');
    exit;
}
if (!verifyCsrf($_POST['csrf'] ?? '')) {
    flash('error', 'Token tidak valid.');
    header('Location: ' . BASE_URL . '/pages/admin/academic_calendar/index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
try {
    AcademicCalendar::delete($id);
    flash('success', 'Acara berhasil dihapus.');
} catch (Exception $e) {
    flash('error', 'Gagal menghapus: ' . $e->getMessage());
}
header('Location: ' . BASE_URL . '/pages/admin/academic_calendar/index.php');
exit;
