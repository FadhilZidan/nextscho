<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Student.php';

requireRole('admin');

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/students/index.php');
    exit;
}

if (!verifyCsrf($_POST['csrf'] ?? '')) {
    flash('error', 'Token tidak valid. Silakan coba lagi.');
    header('Location: ' . BASE_URL . '/pages/admin/students/index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'ID siswa tidak valid.');
    header('Location: ' . BASE_URL . '/pages/admin/students/index.php');
    exit;
}

$student = Student::getById($id);
if (!$student) {
    flash('error', 'Data siswa tidak ditemukan.');
    header('Location: ' . BASE_URL . '/pages/admin/students/index.php');
    exit;
}

try {
    // Hapus foto dari server
    $photo = $student['photo'] ?? 'default.png';
    if ($photo !== 'default.png' && file_exists(UPLOAD_PATH . $photo)) {
        @unlink(UPLOAD_PATH . $photo);
    }

    Student::delete($id);

    flash('success', "Siswa {$student['name']} berhasil dihapus.");
} catch (Exception $e) {
    flash('error', 'Gagal menghapus data: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/pages/admin/students/index.php');
exit;
