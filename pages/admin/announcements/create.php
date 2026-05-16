<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Announcement.php';

requireRole('admin');

$errors = [];
$f = ['title' => '', 'content' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) die('Invalid CSRF token.');
    $f = [
        'title'   => trim($_POST['title']   ?? ''),
        'content' => trim($_POST['content'] ?? ''),
    ];
    if ($f['title'] === '')   $errors['title']   = 'Judul wajib diisi.';
    if ($f['content'] === '') $errors['content'] = 'Konten wajib diisi.';

    if (empty($errors)) {
        Announcement::create(['title' => $f['title'], 'content' => $f['content'], 'created_by' => currentUser()['id']]);
        flash('success', 'Pengumuman berhasil dipublikasikan.');
        header('Location: ' . BASE_URL . '/pages/admin/announcements/index.php');
        exit;
    }
}

$pageTitle  = 'Buat Pengumuman';
$breadcrumb = [
    ['label' => 'Pengumuman', 'url' => BASE_URL . '/pages/admin/announcements/index.php'],
    ['label' => 'Buat Pengumuman'],
];
require_once APP_ROOT . '/includes/header.php';
?>

<div class="max-w-2xl" data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header" style="margin-bottom:20px;">
        <div>
            <h1 class="ns-page-title">Buat Pengumuman</h1>
            <p class="ns-page-subtitle">Pengumuman akan terlihat oleh semua pengguna.</p>
        </div>
    </div>

    <form method="POST" novalidate>
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

    <div class="ns-form-card" style="margin-bottom:20px;">
        <div class="ns-form-section">
            <p class="ns-form-section-title">Isi Pengumuman</p>
            <p class="ns-form-section-sub">Judul dan konten akan tampil di portal semua pengguna</p>
        </div>
        <div class="ns-form-body">

            <div class="ns-form-group" style="margin-bottom:16px;">
                <label class="ns-form-label">Judul<span class="ns-required">*</span></label>
                <input type="text" name="title" value="<?= htmlspecialchars($f['title']) ?>"
                       placeholder="Judul pengumuman"
                       class="ns-form-input <?= isset($errors['title']) ? 'ns-error' : '' ?>">
                <?php if (isset($errors['title'])): ?>
                <p class="ns-form-error"><?= htmlspecialchars($errors['title']) ?></p>
                <?php endif; ?>
            </div>

            <div class="ns-form-group">
                <label class="ns-form-label">Konten<span class="ns-required">*</span></label>
                <textarea name="content" rows="10"
                          placeholder="Tulis isi pengumuman di sini…"
                          class="ns-form-input <?= isset($errors['content']) ? 'ns-error' : '' ?>"
                          style="resize:vertical;"><?= htmlspecialchars($f['content']) ?></textarea>
                <?php if (isset($errors['content'])): ?>
                <p class="ns-form-error"><?= htmlspecialchars($errors['content']) ?></p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="<?= BASE_URL ?>/pages/admin/announcements/index.php" class="ns-btn ns-btn-secondary">Batalkan</a>
        <button type="submit" class="ns-btn ns-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            Publikasikan
        </button>
    </div>

    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
