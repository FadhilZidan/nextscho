<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/config/helpers.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Teacher.php';
require_once APP_ROOT . '/models/User.php';

requireRole('admin');

$errors = [];
$f = ['nip' => '', 'name' => '', 'subjects' => '', 'phone' => '', 'email' => '', 'password' => ''];

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
    if ($f['nip'] !== '' && Teacher::nipExists($f['nip'])) $errors['nip'] = 'NIP sudah digunakan.';
    if ($f['email'] !== '') {
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format email tidak valid.';
        elseif (User::emailExists($f['email'])) $errors['email'] = 'Email sudah digunakan.';
    }
    if ($f['email'] !== '' && $f['password'] === '') $errors['password'] = 'Password wajib diisi jika email disertakan.';
    elseif ($f['password'] !== '' && strlen($f['password']) < 6) $errors['password'] = 'Password minimal 6 karakter.';

    $photo = 'default.png';
    if (isset($_FILES['photo'])) {
        $res = handlePhotoUpload($_FILES['photo'], 'default.png', 'teacher_');
        if (!$res['success']) $errors['photo'] = $res['error'];
        else $photo = $res['filename'];
    }

    if (empty($errors)) {
        $db = getDB();
        $db->beginTransaction();
        try {
            $userId = null;
            if ($f['email'] !== '') {
                $userId = User::create(['name' => $f['name'], 'email' => $f['email'], 'password' => $f['password'], 'role' => 'guru']);
            }
            Teacher::create(['user_id' => $userId, 'nip' => $f['nip'], 'name' => $f['name'], 'subjects' => $f['subjects'], 'phone' => $f['phone'], 'photo' => $photo]);
            $db->commit();
            flash('success', "Guru {$f['name']} berhasil ditambahkan.");
            header('Location: ' . BASE_URL . '/pages/admin/teachers/index.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            if ($photo !== 'default.png' && file_exists(UPLOAD_PATH . $photo)) @unlink(UPLOAD_PATH . $photo);
            $errors['_general'] = 'Kesalahan server: ' . $e->getMessage();
        }
    }
}

$pageTitle  = 'Tambah Guru';
$breadcrumb = [
    ['label' => 'Guru', 'url' => BASE_URL . '/pages/admin/teachers/index.php'],
    ['label' => 'Tambah Guru'],
];
require_once APP_ROOT . '/includes/header.php';
?>

<div class="max-w-2xl" data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header" style="margin-bottom:20px;">
        <div>
            <h1 class="ns-page-title">Tambah Guru</h1>
            <p class="ns-page-subtitle">Isi semua field yang wajib diisi.</p>
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
    <div class="ns-glass-panel overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
            <p class="text-base font-bold text-gray-900">Data Guru</p>
            <p class="text-xs text-gray-500 mt-1">Informasi identitas guru</p>
        </div>
        <div class="p-6 space-y-5">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">NIP</label>
                    <input type="text" name="nip" value="<?= htmlspecialchars($f['nip']) ?>"
                           placeholder="19900101200001001"
                           class="ns-form-input w-full <?= isset($errors['nip']) ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500' : '' ?>">
                    <?php if (isset($errors['nip'])): ?>
                    <p class="mt-1.5 text-sm text-rose-600 font-medium"><?= htmlspecialchars($errors['nip']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>"
                           placeholder="Nama lengkap guru"
                           class="ns-form-input w-full <?= isset($errors['name']) ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500' : '' ?>">
                    <?php if (isset($errors['name'])): ?>
                    <p class="mt-1.5 text-sm text-rose-600 font-medium"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Mata Pelajaran</label>
                    <input type="text" name="subjects" value="<?= htmlspecialchars($f['subjects']) ?>"
                           placeholder="cth. Matematika, IPA"
                           class="ns-form-input w-full">
                    <p class="mt-1.5 text-xs text-gray-500">Pisahkan dengan koma jika lebih dari satu</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">No. Telepon</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($f['phone']) ?>"
                           placeholder="08xxxxxxxxxx" class="ns-form-input w-full">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Foto Profil</label>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-colors cursor-pointer">
                <p class="mt-1.5 text-xs text-gray-500">JPG/PNG/GIF/WebP, maks. 2 MB</p>
                <?php if (isset($errors['photo'])): ?>
                <p class="mt-1.5 text-sm text-rose-600 font-medium"><?= htmlspecialchars($errors['photo']) ?></p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Akun login -->
    <div class="ns-glass-panel overflow-hidden mb-8">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
            <p class="text-base font-bold text-gray-900">Akun Login <span class="font-normal text-xs text-gray-500">(opsional)</span></p>
            <p class="text-xs text-gray-500 mt-1">Kosongkan jika guru tidak memerlukan akses portal</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($f['email']) ?>"
                           placeholder="guru@sekolah.sch.id"
                           class="ns-form-input w-full <?= isset($errors['email']) ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500' : '' ?>">
                    <?php if (isset($errors['email'])): ?>
                    <p class="mt-1.5 text-sm text-rose-600 font-medium"><?= htmlspecialchars($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Password</label>
                    <input type="password" name="password" placeholder="Min. 6 karakter"
                           class="ns-form-input w-full <?= isset($errors['password']) ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500' : '' ?>">
                    <?php if (isset($errors['password'])): ?>
                    <p class="mt-1.5 text-sm text-rose-600 font-medium"><?= htmlspecialchars($errors['password']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer actions -->
    <div class="flex items-center justify-end gap-3 pb-8">
        <a href="<?= BASE_URL ?>/pages/admin/teachers/index.php" class="ns-btn ns-btn-outline bg-white text-gray-700 border-gray-200 hover:bg-gray-50">Batal</a>
        <button type="submit" class="ns-btn ns-btn-primary shadow-sm shadow-[var(--ns-role-primary)]/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Guru
        </button>
    </div>

    </form>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
