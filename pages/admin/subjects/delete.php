<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Subject.php';

requireRole('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/pages/admin/subjects/index.php'); exit; }
if (!verifyCsrf($_POST['csrf'] ?? '')) { flash('error', 'Token tidak valid.'); header('Location: ' . BASE_URL . '/pages/admin/subjects/index.php'); exit; }

$id      = (int) ($_POST['id'] ?? 0);
$subject = Subject::getById($id);
if (!$subject) { flash('error', 'Mata pelajaran tidak ditemukan.'); header('Location: ' . BASE_URL . '/pages/admin/subjects/index.php'); exit; }

try {
    Subject::delete($id);
    flash('success', "Mata pelajaran {$subject['name']} berhasil dihapus.");
} catch (Exception $e) {
    flash('error', 'Gagal menghapus: ' . $e->getMessage());
}
header('Location: ' . BASE_URL . '/pages/admin/subjects/index.php');
exit;
