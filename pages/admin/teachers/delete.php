<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Teacher.php';

requireRole('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/pages/admin/teachers/index.php'); exit; }
if (!verifyCsrf($_POST['csrf'] ?? '')) { flash('error', 'Token tidak valid.'); header('Location: ' . BASE_URL . '/pages/admin/teachers/index.php'); exit; }

$id      = (int) ($_POST['id'] ?? 0);
$teacher = Teacher::getById($id);
if (!$teacher) { flash('error', 'Data guru tidak ditemukan.'); header('Location: ' . BASE_URL . '/pages/admin/teachers/index.php'); exit; }

try {
    $photo = $teacher['photo'] ?? 'default.png';
    if ($photo !== 'default.png' && file_exists(UPLOAD_PATH . $photo)) @unlink(UPLOAD_PATH . $photo);
    Teacher::delete($id);
    flash('success', "Guru {$teacher['name']} berhasil dihapus.");
} catch (Exception $e) {
    flash('error', 'Gagal menghapus: ' . $e->getMessage());
}
header('Location: ' . BASE_URL . '/pages/admin/teachers/index.php');
exit;
