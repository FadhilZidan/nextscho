<?php
define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/config/helpers.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Teacher.php';
require_once APP_ROOT . '/models/User.php';

requireRole('guru');

$user    = currentUser();
$teacher = Teacher::getByUserId((int) $user['id']);
if (!$teacher) { flash('error', 'Data guru tidak ditemukan.'); header('Location: ' . BASE_URL . '/auth/login.php'); exit; }

$errors = [];
$f = [
    'name'     => $teacher['name'],
    'phone'    => $teacher['phone']    ?? '',
    'subjects' => $teacher['subjects'] ?? '',
    'email'    => $teacher['email']    ?? '',
    'password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) die('Invalid CSRF token.');
    $f = [
        'name'     => trim($_POST['name']     ?? ''),
        'phone'    => trim($_POST['phone']    ?? ''),
        'subjects' => trim($_POST['subjects'] ?? ''),
        'email'    => trim($_POST['email']    ?? ''),
        'password' => $_POST['password']       ?? '',
    ];

    if ($f['name'] === '') $errors['name'] = 'Nama wajib diisi.';
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
            Teacher::update($teacher['id'], [
                'nip'      => $teacher['nip'],
                'name'     => $f['name'],
                'subjects' => $f['subjects'],
                'phone'    => $f['phone'],
                'photo'    => $photo,
            ]);

            if ($teacher['user_id'] && $f['email'] !== '') {
                User::update((int) $teacher['user_id'], ['name' => $f['name'], 'email' => $f['email'], 'password' => $f['password']]);
            }

            $db->commit();
            $_SESSION['user_name'] = $f['name'];
            flash('success', 'Profil berhasil diperbarui.');
            header('Location: ' . BASE_URL . '/pages/teacher/profile.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors['_general'] = 'Kesalahan: ' . $e->getMessage();
        }
    }
}

$pageTitle  = 'Profil Saya';
$breadcrumb = [['label' => 'Portal Guru'], ['label' => 'Profil']];
require_once APP_ROOT . '/includes/header.php';

$hp       = UPLOAD_PATH . ($teacher['photo'] ?? '');
$hasPhoto = ($teacher['photo'] ?? 'default.png') !== 'default.png' && file_exists($hp);
$initials = strtoupper(mb_substr($teacher['name'], 0, 2));
?>

<div class="max-w-lg" data-animate="fade-up">

<?php if (!empty($errors['_general'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;border:1px solid #FECACA;background:#FEF2F2;font-size:13px;color:#991B1B;">
    <?= htmlspecialchars($errors['_general']) ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" novalidate>
<input type="hidden" name="csrf" value="<?= csrfToken() ?>">

<!-- Profile hero -->
<div class="ns-glass-panel p-6 mb-6 relative overflow-hidden" style="background: linear-gradient(135deg, var(--ns-role-teacher), #047857); border: none;">
    <div class="flex flex-col sm:flex-row items-center gap-6 relative z-10">
        <?php if ($hasPhoto): ?>
        <img src="<?= UPLOAD_URL . htmlspecialchars($teacher['photo']) ?>"
             class="w-20 h-20 rounded-2xl object-cover border-2 border-white/40 shadow-lg flex-shrink-0" alt="">
        <?php else: ?>
        <div class="w-20 h-20 rounded-2xl bg-white/20 text-white flex items-center justify-center text-3xl font-bold shadow-lg flex-shrink-0 border-2 border-white/40 backdrop-blur-sm">
            <?= $initials ?>
        </div>
        <?php endif; ?>
        <div class="text-center sm:text-left text-white">
            <p class="font-serif text-3xl font-normal leading-tight mb-1"><?= htmlspecialchars($teacher['name']) ?></p>
            <?php if (!empty($teacher['nip'])): ?>
            <p class="text-sm text-white/90 font-medium mb-0.5">NIP <?= htmlspecialchars($teacher['nip']) ?></p>
            <?php endif; ?>
            <?php if (!empty($teacher['subjects'])): ?>
            <p class="text-xs text-white/70"><?= htmlspecialchars($teacher['subjects']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="absolute -right-10 -bottom-10 w-40 h-40 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
    <div class="absolute left-10 -top-10 w-32 h-32 rounded-full bg-black/10 blur-2xl pointer-events-none"></div>
</div>

<!-- Foto profil -->
<div class="ns-form-card" style="margin-bottom:20px;">
    <div class="ns-form-section">
        <p class="ns-form-section-title">Foto Profil</p>
        <p class="ns-form-section-sub">Ubah foto profil yang akan ditampilkan di sistem</p>
    </div>
    <div class="ns-form-body">
        <div class="ns-form-group">
            <label class="ns-form-label">Upload Foto Baru</label>
            <?php if ($hasPhoto): ?>
            <div class="flex items-center gap-4 mb-4">
                <img src="<?= UPLOAD_URL . htmlspecialchars($teacher['photo']) ?>"
                     class="w-16 h-16 rounded-xl object-cover border border-gray-200 shadow-sm" alt="">
                <span class="text-sm font-medium text-gray-500">Foto saat ini</span>
            </div>
            <?php endif; ?>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-colors cursor-pointer">
            <p class="ns-form-hint">JPG/PNG/GIF/WebP, maks. 2 MB</p>
            <?php if (isset($errors['photo'])): ?>
            <p class="ns-form-error"><?= htmlspecialchars($errors['photo']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit form -->
<div class="ns-form-card" style="margin-bottom:20px;">
    <div class="ns-form-section">
        <p class="ns-form-section-title">Edit Profil</p>
        <p class="ns-form-section-sub">Perbarui data diri dan akun login Anda</p>
    </div>
    <div class="ns-form-body">

        <div class="ns-form-row" style="margin-bottom:16px;">
            <div class="ns-form-group">
                <label class="ns-form-label">Nama Lengkap <span class="ns-required">*</span></label>
                <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>"
                       class="ns-form-input w-full <?= isset($errors['name']) ? 'ns-error' : '' ?>">
                <?php if (isset($errors['name'])): ?>
                <p class="ns-form-error"><?= htmlspecialchars($errors['name']) ?></p>
                <?php endif; ?>
            </div>
            <div class="ns-form-group">
                <label class="ns-form-label">No. Telepon</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($f['phone']) ?>"
                       placeholder="08xxxxxxxxxx" class="ns-form-input w-full">
            </div>
        </div>

        <div class="ns-form-group" style="margin-bottom:16px;">
            <label class="ns-form-label">Mata Pelajaran</label>
            <input type="text" name="subjects" value="<?= htmlspecialchars($f['subjects']) ?>"
                   placeholder="Matematika, Fisika, …" class="ns-form-input w-full">
            <p class="ns-form-hint">Pisahkan dengan koma jika lebih dari satu</p>
        </div>

        <div class="ns-form-row" style="border-top: 1px solid #F3F4F6; padding-top: 16px;">
            <div class="ns-form-group">
                <label class="ns-form-label">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($f['email']) ?>"
                       class="ns-form-input w-full <?= isset($errors['email']) ? 'ns-error' : '' ?>">
                <?php if (isset($errors['email'])): ?>
                <p class="ns-form-error"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>
            <div class="ns-form-group">
                <label class="ns-form-label">Password Baru</label>
                <input type="password" name="password" placeholder="Kosongkan jika tidak diubah"
                       class="ns-form-input w-full <?= isset($errors['password']) ? 'ns-error' : '' ?>">
                <?php if (isset($errors['password'])): ?>
                <p class="ns-form-error"><?= htmlspecialchars($errors['password']) ?></p>
                <?php endif; ?>
                <p class="ns-form-hint">Minimal 6 karakter.</p>
            </div>
        </div>

    </div>
</div>

<div class="flex items-center justify-end gap-3 mb-8">
    <a href="<?= BASE_URL ?>/pages/teacher/dashboard.php" class="ns-btn ns-btn-secondary">Batal</a>
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
