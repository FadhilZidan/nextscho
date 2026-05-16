<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/ClassRoom.php';
require_once APP_ROOT . '/models/Teacher.php';

requireRole('admin');

$errors   = [];
$teachers = Teacher::getForSelect();
$f = ['name' => '', 'grade_level' => '', 'academic_year' => ACADEMIC_YEAR, 'homeroom_teacher_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) die('Invalid CSRF token.');
    $f = [
        'name'                => trim($_POST['name']          ?? ''),
        'grade_level'         => trim($_POST['grade_level']   ?? ''),
        'academic_year'       => trim($_POST['academic_year'] ?? ACADEMIC_YEAR),
        'homeroom_teacher_id' => (int) ($_POST['homeroom_teacher_id'] ?? 0),
    ];
    if ($f['name'] === '')        $errors['name']        = 'Nama kelas wajib diisi.';
    if ($f['grade_level'] === '') $errors['grade_level'] = 'Tingkat wajib dipilih.';

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO classes (name, grade_level, academic_year, homeroom_teacher_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$f['name'], $f['grade_level'], $f['academic_year'], $f['homeroom_teacher_id'] ?: null]);
        flash('success', "Kelas {$f['name']} berhasil ditambahkan.");
        header('Location: ' . BASE_URL . '/pages/admin/classes/index.php');
        exit;
    }
}

$pageTitle  = 'Tambah Kelas';
$breadcrumb = [
    ['label' => 'Kelas', 'url' => BASE_URL . '/pages/admin/classes/index.php'],
    ['label' => 'Tambah Kelas'],
];
require_once APP_ROOT . '/includes/header.php';
?>

<div class="max-w-lg" data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header" style="margin-bottom:20px;">
        <div>
            <h1 class="ns-page-title">Tambah Kelas</h1>
            <p class="ns-page-subtitle">Isi informasi kelas baru.</p>
        </div>
    </div>

    <form method="POST" novalidate>
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

    <div class="ns-form-card" style="margin-bottom:20px;">
        <div class="ns-form-section">
            <p class="ns-form-section-title">Informasi Kelas</p>
            <p class="ns-form-section-sub">Data identitas kelas</p>
        </div>
        <div class="ns-form-body">

            <div class="ns-form-row" style="margin-bottom:16px;">
                <div class="ns-form-group">
                    <label class="ns-form-label">Nama Kelas<span class="ns-required">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>"
                           placeholder="cth. VII-A"
                           class="ns-form-input <?= isset($errors['name']) ? 'ns-error' : '' ?>">
                    <?php if (isset($errors['name'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">Tingkat<span class="ns-required">*</span></label>
                    <select name="grade_level" class="ns-form-input <?= isset($errors['grade_level']) ? 'ns-error' : '' ?>">
                        <option value="">— Pilih Tingkat —</option>
                        <?php foreach (['VII', 'VIII', 'IX'] as $g): ?>
                        <option value="<?= $g ?>" <?= $f['grade_level'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['grade_level'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['grade_level']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ns-form-row">
                <div class="ns-form-group">
                    <label class="ns-form-label">Tahun Ajaran</label>
                    <input type="text" name="academic_year" value="<?= htmlspecialchars($f['academic_year']) ?>"
                           placeholder="2025/2026" class="ns-form-input">
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">Wali Kelas</label>
                    <select name="homeroom_teacher_id" class="ns-form-input">
                        <option value="">— Tidak Ada —</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $f['homeroom_teacher_id'] == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="<?= BASE_URL ?>/pages/admin/classes/index.php" class="ns-btn ns-btn-secondary">Batal</a>
        <button type="submit" class="ns-btn ns-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Kelas
        </button>
    </div>

    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
