<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/config/helpers.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Teacher.php';
require_once APP_ROOT . '/models/User.php';

requireRole('admin');

$id = (int) ($_GET['id'] ?? 0);
$teacher = Teacher::getById($id);
if (!$teacher) {
    flash('error', 'Guru tidak ditemukan.');
    header('Location: ' . BASE_URL . '/pages/admin/teachers/index.php');
    exit;
}

$errors = [];
$f = [
    'nip'      => $teacher['nip']      ?? '',
    'name'     => $teacher['name'],
    'subjects' => $teacher['subjects'] ?? '',
    'phone'    => $teacher['phone']    ?? '',
    'email'    => $teacher['email']    ?? '',
    'password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) die('Invalid CSRF token.');
    $f = [
        'nip'      => trim($_POST['nip']      ?? ''),
        'name'     => trim($_POST['name']     ?? ''),
        'subjects' => trim($_POST['subjects'] ?? ''),
        'phone'    => trim($_POST['phone']    ?? ''),
        'email'    => trim($_POST['email']    ?? ''),
        'password' => $_POST['password']       ?? '',
    ];
    if ($f['name'] === '') $errors['name'] = 'Nama lengkap wajib diisi.';
    if ($f['nip'] !== '' && Teacher::nipExists($f['nip'], $id)) $errors['nip'] = 'NIP sudah digunakan.';
    if ($f['email'] !== '') {
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format email tidak valid.';
        elseif (User::emailExists($f['email'], (int) ($teacher['user_id'] ?? 0))) $errors['email'] = 'Email sudah digunakan.';
    }
    if ($f['password'] !== '' && strlen($f['password']) < 6) $errors['password'] = 'Password minimal 6 karakter.';

    $photo = $teacher['photo'] ?? 'default.png';
    if (isset($_FILES['photo'])) {
        $res = handlePhotoUpload($_FILES['photo'], $photo, 'teacher_');
        if (!$res['success']) $errors['photo'] = $res['error'];
        else $photo = $res['filename'];
    }

    if (empty($errors)) {
        $db = getDB();
        $db->beginTransaction();
        try {
            if ($f['email'] !== '') {
                if ($teacher['user_id']) {
                    User::update((int) $teacher['user_id'], ['name' => $f['name'], 'email' => $f['email'], 'password' => $f['password']]);
                } else {
                    $uid = User::create(['name' => $f['name'], 'email' => $f['email'], 'password' => $f['password'] ?: 'guru123', 'role' => 'guru']);
                    $db->prepare('UPDATE teachers SET user_id=? WHERE id=?')->execute([$uid, $id]);
                }
            }
            Teacher::update($id, array_merge($f, ['photo' => $photo]));
            $db->commit();
            flash('success', "Guru {$f['name']} berhasil diperbarui.");
            header('Location: ' . BASE_URL . '/pages/admin/teachers/index.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors['_general'] = 'Kesalahan server: ' . $e->getMessage();
        }
    }
}

$pageTitle  = 'Edit Guru';
$breadcrumb = [
    ['label' => 'Guru', 'url' => BASE_URL . '/pages/admin/teachers/index.php'],
    ['label' => 'Edit: ' . $teacher['name']],
];
require_once APP_ROOT . '/includes/header.php';

$hasPhoto = ($teacher['photo'] ?? 'default.png') !== 'default.png' && file_exists(UPLOAD_PATH . $teacher['photo']);
?>

<div class="max-w-2xl" data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:16px;">
            <?php if ($hasPhoto): ?>
            <img src="<?= UPLOAD_URL . htmlspecialchars($teacher['photo']) ?>"
                 style="width:52px;height:52px;border-radius:14px;object-fit:cover;border:2px solid var(--ns-role-primary);flex-shrink:0;" alt="">
            <?php else: ?>
            <div class="ns-avatar" style="width:52px;height:52px;font-size:15px;background:#047857;flex-shrink:0;">
                <?= strtoupper(mb_substr($teacher['name'], 0, 2)) ?>
            </div>
            <?php endif; ?>
            <div>
                <h1 class="ns-page-title">Edit Guru</h1>
                <p class="ns-page-subtitle"><?= htmlspecialchars($teacher['name']) ?><?= $teacher['nip'] ? ' · NIP ' . htmlspecialchars($teacher['nip']) : '' ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($errors['_general'])): ?>
    <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;border:1px solid #FECACA;background:#FEF2F2;font-size:13px;color:#991B1B;">
        <?= htmlspecialchars($errors['_general']) ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

    <!-- Data guru -->
    <div class="ns-form-card" style="margin-bottom:16px;">
        <div class="ns-form-section">
            <p class="ns-form-section-title">Data Guru</p>
            <p class="ns-form-section-sub">Informasi identitas guru</p>
        </div>
        <div class="ns-form-body">

            <div class="ns-form-row" style="margin-bottom:16px;">
                <div class="ns-form-group">
                    <label class="ns-form-label">NIP</label>
                    <input type="text" name="nip" value="<?= htmlspecialchars($f['nip']) ?>"
                           placeholder="19900101200001001"
                           class="ns-form-input <?= isset($errors['nip']) ? 'ns-error' : '' ?>">
                    <?php if (isset($errors['nip'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['nip']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">Nama Lengkap<span class="ns-required">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>"
                           placeholder="Nama lengkap guru"
                           class="ns-form-input <?= isset($errors['name']) ? 'ns-error' : '' ?>">
                    <?php if (isset($errors['name'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ns-form-row" style="margin-bottom:16px;">
                <div class="ns-form-group">
                    <label class="ns-form-label">Mata Pelajaran</label>
                    <input type="text" name="subjects" value="<?= htmlspecialchars($f['subjects']) ?>"
                           placeholder="cth. Matematika, IPA"
                           class="ns-form-input">
                    <p class="ns-form-hint">Pisahkan dengan koma jika lebih dari satu</p>
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">No. Telepon</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($f['phone']) ?>"
                           placeholder="08xxxxxxxxxx" class="ns-form-input">
                </div>
            </div>

            <div class="ns-form-group">
                <label class="ns-form-label">Ganti Foto Profil</label>
                <?php if ($hasPhoto): ?>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <img src="<?= UPLOAD_URL . htmlspecialchars($teacher['photo']) ?>"
                         style="width:48px;height:48px;border-radius:10px;object-fit:cover;border:1px solid #E8E6DF;" alt="">
                    <span style="font-size:12px;color:#9CA3AF;">Foto saat ini</span>
                </div>
                <?php endif; ?>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                       style="font-size:13px;color:#6B7280;">
                <p class="ns-form-hint">Kosongkan jika tidak ingin mengganti foto. JPG/PNG/GIF/WebP, maks. 2 MB</p>
                <?php if (isset($errors['photo'])): ?>
                <p class="ns-form-error"><?= htmlspecialchars($errors['photo']) ?></p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Akun login -->
    <div class="ns-form-card" style="margin-bottom:20px;">
        <div class="ns-form-section">
            <p class="ns-form-section-title">Akun Login <span style="font-weight:400;font-size:11px;color:#9CA3AF;">(opsional)</span></p>
            <p class="ns-form-section-sub">Kosongkan password jika tidak ingin mengubah</p>
        </div>
        <div class="ns-form-body">
            <div class="ns-form-row">
                <div class="ns-form-group">
                    <label class="ns-form-label">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($f['email']) ?>"
                           placeholder="guru@sekolah.sch.id"
                           class="ns-form-input <?= isset($errors['email']) ? 'ns-error' : '' ?>">
                    <?php if (isset($errors['email'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">Password Baru</label>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak diubah"
                           class="ns-form-input <?= isset($errors['password']) ? 'ns-error' : '' ?>">
                    <?php if (isset($errors['password'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['password']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer actions -->
    <div class="flex items-center justify-end gap-3">
        <a href="<?= BASE_URL ?>/pages/admin/teachers/index.php" class="ns-btn ns-btn-secondary">Batal</a>
        <button type="submit" class="ns-btn ns-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Perubahan
        </button>
    </div>

    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
