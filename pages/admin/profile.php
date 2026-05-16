<?php
define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/config/helpers.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/User.php';

requireRole('admin');

$user = currentUser();

$errors = [];
$f = [
    'name'     => $user['name'],
    'email'    => $user['email'] ?? '',
    'password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) die('Invalid CSRF token.');
    $f = [
        'name'     => trim($_POST['name']     ?? ''),
        'email'    => trim($_POST['email']    ?? ''),
        'password' => $_POST['password']       ?? '',
    ];

    if ($f['name'] === '') $errors['name'] = 'Nama wajib diisi.';
    if ($f['email'] === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    } elseif (User::emailExists($f['email'], (int) $user['id'])) {
        $errors['email'] = 'Email sudah digunakan.';
    }
    if ($f['password'] !== '' && strlen($f['password']) < 6) $errors['password'] = 'Password minimal 6 karakter.';

    if (empty($errors)) {
        try {
            User::update((int) $user['id'], [
                'name'     => $f['name'],
                'email'    => $f['email'],
                'password' => $f['password'],
            ]);
            $_SESSION['user_name']  = $f['name'];
            $_SESSION['user_email'] = $f['email'];
            flash('success', 'Profil berhasil diperbarui.');
            header('Location: ' . BASE_URL . '/pages/admin/profile.php');
            exit;
        } catch (Exception $e) {
            $errors['_general'] = 'Kesalahan: ' . $e->getMessage();
        }
    }
}

$pageTitle  = 'Profil Saya';
$breadcrumb = [['label' => 'Admin'], ['label' => 'Profil']];
require_once APP_ROOT . '/includes/header.php';

$initials = strtoupper(mb_substr($user['name'], 0, 2));
?>

<div class="max-w-lg" data-animate="fade-up">

<?php if (!empty($errors['_general'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;border:1px solid #FECACA;background:#FEF2F2;font-size:13px;color:#991B1B;">
    <?= htmlspecialchars($errors['_general']) ?>
</div>
<?php endif; ?>

<form method="POST" novalidate>
<input type="hidden" name="csrf" value="<?= csrfToken() ?>">

<!-- Profile hero -->
<div class="ns-glass-panel p-6 mb-6 relative overflow-hidden" style="background: linear-gradient(135deg, var(--ns-role-admin), #1D4ED8); border: none;">
    <div class="flex flex-col sm:flex-row items-center gap-6 relative z-10">
        <div class="w-20 h-20 rounded-2xl bg-white/20 text-white flex items-center justify-center text-3xl font-bold shadow-lg flex-shrink-0 border-2 border-white/40 backdrop-blur-sm">
            <?= $initials ?>
        </div>
        <div class="text-center sm:text-left text-white">
            <p class="font-serif text-3xl font-normal leading-tight mb-1"><?= htmlspecialchars($user['name']) ?></p>
            <p class="text-sm text-white/90 font-medium mb-2"><?= htmlspecialchars($user['email'] ?? '') ?></p>
            <span class="inline-block px-3 py-1 text-[11px] font-bold tracking-wider rounded-full bg-white/20 border border-white/30 text-white uppercase shadow-sm">
                Administrator
            </span>
        </div>
    </div>
    <div class="absolute -right-10 -bottom-10 w-40 h-40 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
    <div class="absolute left-10 -top-10 w-32 h-32 rounded-full bg-black/10 blur-2xl pointer-events-none"></div>
</div>

<div class="ns-form-card" style="margin-bottom:20px;">
    <div class="ns-form-section">
        <p class="ns-form-section-title">Edit Profil</p>
        <p class="ns-form-section-sub">Perbarui nama, email, atau password akun</p>
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
                <label class="ns-form-label">Email <span class="ns-required">*</span></label>
                <input type="email" name="email" value="<?= htmlspecialchars($f['email']) ?>"
                       class="ns-form-input w-full <?= isset($errors['email']) ? 'ns-error' : '' ?>">
                <?php if (isset($errors['email'])): ?>
                <p class="ns-form-error"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="ns-form-group" style="margin-bottom:16px; border-top: 1px solid #F3F4F6; padding-top: 16px;">
            <label class="ns-form-label">Password Baru</label>
            <input type="password" name="password" placeholder="Kosongkan jika tidak diubah"
                   class="ns-form-input w-full <?= isset($errors['password']) ? 'ns-error' : '' ?>">
            <?php if (isset($errors['password'])): ?>
            <p class="ns-form-error"><?= htmlspecialchars($errors['password']) ?></p>
            <?php endif; ?>
            <p class="ns-form-hint">Minimal 6 karakter.</p>
        </div>

        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm font-medium text-amber-800">
                    Akun ini memiliki akses penuh ke sistem. Gunakan password yang kuat dan jangan bagikan kredensial kepada siapapun.
                </p>
            </div>
        </div>

    </div>
</div>

<div class="flex items-center justify-end gap-3 mb-8">
    <a href="<?= BASE_URL ?>/pages/admin/dashboard.php" class="ns-btn ns-btn-secondary">Batal</a>
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
