<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/ClassRoom.php';

requireRole('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/pages/admin/classes/index.php'); exit; }
if (!verifyCsrf($_POST['csrf'] ?? '')) { flash('error', 'Token tidak valid.'); header('Location: ' . BASE_URL . '/pages/admin/classes/index.php'); exit; }

$id    = (int) ($_POST['id'] ?? 0);
$kelas = ClassRoom::getById($id);
if (!$kelas) { flash('error', 'Kelas tidak ditemukan.'); header('Location: ' . BASE_URL . '/pages/admin/classes/index.php'); exit; }

try {
    getDB()->prepare('DELETE FROM classes WHERE id = ?')->execute([$id]);
    flash('success', "Kelas {$kelas['name']} berhasil dihapus.");
} catch (Exception $e) {
    flash('error', 'Gagal menghapus kelas (mungkin masih ada siswa terdaftar): ' . $e->getMessage());
}
header('Location: ' . BASE_URL . '/pages/admin/classes/index.php');
exit;
