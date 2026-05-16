<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Subject.php';
require_once APP_ROOT . '/models/Teacher.php';
require_once APP_ROOT . '/models/ClassRoom.php';

requireRole('admin');

$errors   = [];
$teachers = Teacher::getForSelect();
$classes  = ClassRoom::getForSelect();
$f = ['name' => '', 'code' => '', 'teacher_id' => '', 'class_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) die('Invalid CSRF token.');
    $f = [
        'name'       => trim($_POST['name']       ?? ''),
        'code'       => strtoupper(trim($_POST['code'] ?? '')),
        'teacher_id' => (int) ($_POST['teacher_id'] ?? 0),
        'class_id'   => (int) ($_POST['class_id']   ?? 0),
    ];
    if ($f['name'] === '') $errors['name'] = 'Nama mata pelajaran wajib diisi.';
    if ($f['code'] === '') $errors['code'] = 'Kode mata pelajaran wajib diisi.';

    if (empty($errors)) {
        Subject::create($f);
        flash('success', "Mata pelajaran {$f['name']} berhasil ditambahkan.");
        header('Location: ' . BASE_URL . '/pages/admin/subjects/index.php');
        exit;
    }
}

$pageTitle  = 'Tambah Mapel';
$breadcrumb = [
    ['label' => 'Mata Pelajaran', 'url' => BASE_URL . '/pages/admin/subjects/index.php'],
    ['label' => 'Tambah Mapel'],
];
require_once APP_ROOT . '/includes/header.php';
?>

<div class="max-w-lg" data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header" style="margin-bottom:20px;">
        <div>
            <h1 class="ns-page-title">Tambah Mapel</h1>
            <p class="ns-page-subtitle">Isi informasi mata pelajaran baru.</p>
        </div>
    </div>

    <form method="POST" novalidate>
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

    <div class="ns-form-card" style="margin-bottom:20px;">
        <div class="ns-form-section">
            <p class="ns-form-section-title">Informasi Mata Pelajaran</p>
            <p class="ns-form-section-sub">Data identitas mapel</p>
        </div>
        <div class="ns-form-body">

            <div class="ns-form-row" style="margin-bottom:16px;">
                <div class="ns-form-group">
                    <label class="ns-form-label">Nama Mapel<span class="ns-required">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>"
                           placeholder="cth. Matematika"
                           class="ns-form-input <?= isset($errors['name']) ? 'ns-error' : '' ?>">
                    <?php if (isset($errors['name'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">Kode<span class="ns-required">*</span></label>
                    <input type="text" name="code" value="<?= htmlspecialchars($f['code']) ?>"
                           placeholder="cth. MTK"
                           class="ns-form-input <?= isset($errors['code']) ? 'ns-error' : '' ?>">
                    <p class="ns-form-hint">Kode akan diubah ke huruf kapital otomatis</p>
                    <?php if (isset($errors['code'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['code']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ns-form-row">
                <div class="ns-form-group">
                    <label class="ns-form-label">Kelas</label>
                    <select name="class_id" class="ns-form-input">
                        <option value="">— Semua Kelas —</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $f['class_id'] == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">Guru Pengampu</label>
                    <select name="teacher_id" class="ns-form-input">
                        <option value="">— Pilih Guru —</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $f['teacher_id'] == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="<?= BASE_URL ?>/pages/admin/subjects/index.php" class="ns-btn ns-btn-secondary">Batal</a>
        <button type="submit" class="ns-btn ns-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Mapel
        </button>
    </div>

    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
