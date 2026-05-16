<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/AcademicCalendar.php';

requireRole('admin');

$errors = [];
$f = ['title' => '', 'description' => '', 'start_date' => '', 'end_date' => '', 'type' => 'kegiatan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) die('Invalid CSRF token.');
    $f = [
        'title'       => trim($_POST['title']       ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'start_date'  => trim($_POST['start_date']  ?? ''),
        'end_date'    => trim($_POST['end_date']    ?? ''),
        'type'        => trim($_POST['type']        ?? 'kegiatan'),
    ];
    $validTypes = ['libur', 'ujian', 'kegiatan', 'semester', 'lainnya'];
    if ($f['title'] === '')      $errors['title']      = 'Judul wajib diisi.';
    if ($f['start_date'] === '') $errors['start_date'] = 'Tanggal mulai wajib diisi.';
    if (!in_array($f['type'], $validTypes, true)) $f['type'] = 'kegiatan';
    if ($f['end_date'] !== '' && $f['start_date'] !== '' && $f['end_date'] < $f['start_date']) {
        $errors['end_date'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    }

    if (empty($errors)) {
        AcademicCalendar::create(array_merge($f, ['created_by' => currentUser()['id']]));
        flash('success', 'Acara berhasil ditambahkan.');
        header('Location: ' . BASE_URL . '/pages/admin/academic_calendar/index.php');
        exit;
    }
}

$pageTitle  = 'Tambah Acara';
$breadcrumb = [
    ['label' => 'Kalender Akademik', 'url' => BASE_URL . '/pages/admin/academic_calendar/index.php'],
    ['label' => 'Tambah Acara'],
];
require_once APP_ROOT . '/includes/header.php';

$types = [
    'kegiatan' => ['label' => 'Kegiatan', 'chip' => 'ns-chip-blue'],
    'libur'    => ['label' => 'Libur',    'chip' => 'ns-chip-red'],
    'ujian'    => ['label' => 'Ujian',    'chip' => 'ns-chip-yellow'],
    'semester' => ['label' => 'Semester', 'chip' => 'ns-chip-violet'],
    'lainnya'  => ['label' => 'Lainnya',  'chip' => 'ns-chip-ink'],
];
?>

<div class="max-w-2xl" data-animate="fade-up">

    <div class="ns-page-header" style="margin-bottom:20px;">
        <div>
            <h1 class="ns-page-title">Tambah Acara</h1>
            <p class="ns-page-subtitle">Tambahkan acara ke kalender akademik sekolah.</p>
        </div>
    </div>

    <form method="POST" novalidate>
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

    <div class="ns-form-card" style="margin-bottom:20px;">
        <div class="ns-form-section">
            <p class="ns-form-section-title">Detail Acara</p>
            <p class="ns-form-section-sub">Isi informasi acara yang akan masuk ke kalender</p>
        </div>
        <div class="ns-form-body">

            <!-- Title -->
            <div class="ns-form-group" style="margin-bottom:16px;">
                <label class="ns-form-label">Nama Acara<span class="ns-required">*</span></label>
                <input type="text" name="title" value="<?= htmlspecialchars($f['title']) ?>"
                       placeholder="cth. Libur Hari Raya Idul Fitri"
                       class="ns-form-input <?= isset($errors['title']) ? 'ns-error' : '' ?>">
                <?php if (isset($errors['title'])): ?>
                <p class="ns-form-error"><?= $errors['title'] ?></p>
                <?php endif; ?>
            </div>

            <!-- Type -->
            <div class="ns-form-group" style="margin-bottom:16px;">
                <label class="ns-form-label">Tipe<span class="ns-required">*</span></label>
                <select name="type" class="ns-form-input">
                    <?php foreach ($types as $val => $meta): ?>
                    <option value="<?= $val ?>" <?= $f['type'] === $val ? 'selected' : '' ?>>
                        <?= $meta['label'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dates -->
            <div class="ns-form-row" style="margin-bottom:16px;">
                <div class="ns-form-group">
                    <label class="ns-form-label">Tanggal Mulai<span class="ns-required">*</span></label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($f['start_date']) ?>"
                           class="ns-form-input <?= isset($errors['start_date']) ? 'ns-error' : '' ?>">
                    <?php if (isset($errors['start_date'])): ?>
                    <p class="ns-form-error"><?= $errors['start_date'] ?></p>
                    <?php endif; ?>
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">Tanggal Selesai <span style="color:#9CA3AF;font-weight:400;">(opsional)</span></label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($f['end_date']) ?>"
                           class="ns-form-input <?= isset($errors['end_date']) ? 'ns-error' : '' ?>">
                    <?php if (isset($errors['end_date'])): ?>
                    <p class="ns-form-error"><?= $errors['end_date'] ?></p>
                    <?php endif; ?>
                    <p class="ns-form-hint">Biarkan kosong jika hanya satu hari.</p>
                </div>
            </div>

            <!-- Description -->
            <div class="ns-form-group">
                <label class="ns-form-label">Keterangan <span style="color:#9CA3AF;font-weight:400;">(opsional)</span></label>
                <textarea name="description" rows="4"
                          placeholder="Tambahkan keterangan atau catatan…"
                          class="ns-form-input"
                          style="resize:vertical;"><?= htmlspecialchars($f['description']) ?></textarea>
            </div>

        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="<?= BASE_URL ?>/pages/admin/academic_calendar/index.php" class="ns-btn ns-btn-outline">Batalkan</a>
        <button type="submit" class="ns-btn ns-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Acara
        </button>
    </div>

    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
