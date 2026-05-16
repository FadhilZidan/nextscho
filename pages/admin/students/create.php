<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/config/helpers.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Student.php';
require_once APP_ROOT . '/models/ClassRoom.php';
require_once APP_ROOT . '/models/User.php';

requireRole('admin');

$errors  = [];
$classes = ClassRoom::getForSelect();

$f = [
    'nis'        => '',
    'name'       => '',
    'class_id'   => '',
    'gender'     => '',
    'birth_date' => '',
    'address'    => '',
    'phone'      => '',
    'email'      => '',
    'password'   => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $f = [
        'nis'        => trim($_POST['nis']        ?? ''),
        'name'       => trim($_POST['name']       ?? ''),
        'class_id'   => (int) ($_POST['class_id'] ?? 0),
        'gender'     => $_POST['gender']           ?? '',
        'birth_date' => $_POST['birth_date']       ?? '',
        'address'    => trim($_POST['address']    ?? ''),
        'phone'      => trim($_POST['phone']      ?? ''),
        'email'      => trim($_POST['email']      ?? ''),
        'password'   => $_POST['password']         ?? '',
    ];

    if ($f['nis'] === '') {
        $errors['nis'] = 'NIS wajib diisi.';
    } elseif (!preg_match('/^\d{5,20}$/', $f['nis'])) {
        $errors['nis'] = 'NIS harus angka (5–20 digit).';
    } elseif (Student::nisExists($f['nis'])) {
        $errors['nis'] = 'NIS sudah digunakan.';
    }

    if ($f['name'] === '') $errors['name'] = 'Nama lengkap wajib diisi.';
    if (!in_array($f['gender'], ['L', 'P'], true)) $errors['gender'] = 'Pilih jenis kelamin.';

    if ($f['email'] !== '') {
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        } elseif (User::emailExists($f['email'])) {
            $errors['email'] = 'Email sudah digunakan.';
        }
    }

    if ($f['email'] !== '' && $f['password'] === '') {
        $errors['password'] = 'Password wajib diisi jika email disertakan.';
    } elseif ($f['password'] !== '' && strlen($f['password']) < 6) {
        $errors['password'] = 'Password minimal 6 karakter.';
    }

    $photoFilename = 'default.png';
    if (isset($_FILES['photo'])) {
        $res = handlePhotoUpload($_FILES['photo']);
        if (!$res['success']) {
            $errors['photo'] = $res['error'];
        } else {
            $photoFilename = $res['filename'];
        }
    }

    if (empty($errors)) {
        $db = getDB();
        $db->beginTransaction();
        try {
            $userId = null;
            if ($f['email'] !== '') {
                $userId = User::create([
                    'name'     => $f['name'],
                    'email'    => $f['email'],
                    'password' => $f['password'],
                    'role'     => 'siswa',
                ]);
            }

            Student::create([
                'user_id'    => $userId,
                'nis'        => $f['nis'],
                'name'       => $f['name'],
                'class_id'   => $f['class_id'] ?: null,
                'gender'     => $f['gender'],
                'birth_date' => $f['birth_date'] ?: null,
                'address'    => $f['address'],
                'phone'      => $f['phone'],
                'photo'      => $photoFilename,
            ]);

            $db->commit();
            flash('success', "Siswa {$f['name']} berhasil ditambahkan.");
            header('Location: ' . BASE_URL . '/pages/admin/students/index.php');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            if ($photoFilename !== 'default.png' && file_exists(UPLOAD_PATH . $photoFilename)) {
                @unlink(UPLOAD_PATH . $photoFilename);
            }
            $errors['_general'] = 'Kesalahan server: ' . $e->getMessage();
        }
    }
}

$pageTitle  = 'Tambah Siswa';
$breadcrumb = [
    ['label' => 'Siswa', 'url' => BASE_URL . '/pages/admin/students/index.php'],
    ['label' => 'Tambah Siswa'],
];
require_once APP_ROOT . '/includes/header.php';
?>

<div class="max-w-2xl" data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header" style="margin-bottom:20px;">
        <div>
            <h1 class="ns-page-title">Tambah Siswa</h1>
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

    <!-- Data siswa -->
    <div class="ns-glass-panel overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
            <p class="text-base font-bold text-gray-900">Data Siswa</p>
            <p class="text-xs text-gray-500 mt-1">Informasi identitas siswa</p>
        </div>
        <div class="p-6 space-y-5">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">NIS <span class="text-rose-500">*</span></label>
                    <input type="text" name="nis" value="<?= htmlspecialchars($f['nis']) ?>"
                           placeholder="cth. 2025001"
                           class="ns-form-input w-full <?= isset($errors['nis']) ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500' : '' ?>">
                    <?php if (isset($errors['nis'])): ?>
                    <p class="mt-1.5 text-sm text-rose-600 font-medium"><?= htmlspecialchars($errors['nis']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>"
                           placeholder="Nama lengkap siswa"
                           class="ns-form-input w-full <?= isset($errors['name']) ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500' : '' ?>">
                    <?php if (isset($errors['name'])): ?>
                    <p class="mt-1.5 text-sm text-rose-600 font-medium"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Kelas</label>
                    <select name="class_id" class="ns-form-input w-full">
                        <option value="">— Pilih Kelas —</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $f['class_id'] == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Jenis Kelamin <span class="text-rose-500">*</span></label>
                    <div class="flex gap-3">
                        <label id="lbl-L" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all <?= $f['gender'] === 'L' ? 'border-[var(--ns-role-primary)] text-[var(--ns-role-primary)] bg-indigo-50/50' : 'border-gray-200 text-gray-600 hover:border-gray-300' ?>">
                            <input type="radio" name="gender" value="L" <?= $f['gender'] === 'L' ? 'checked' : '' ?>
                                   class="w-4 h-4 text-[var(--ns-role-primary)] focus:ring-[var(--ns-role-primary)]" onchange="nsGender('L')">
                            <span class="text-sm font-bold">Laki-laki</span>
                        </label>
                        <label id="lbl-P" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all <?= $f['gender'] === 'P' ? 'border-[var(--ns-role-primary)] text-[var(--ns-role-primary)] bg-indigo-50/50' : 'border-gray-200 text-gray-600 hover:border-gray-300' ?>">
                            <input type="radio" name="gender" value="P" <?= $f['gender'] === 'P' ? 'checked' : '' ?>
                                   class="w-4 h-4 text-[var(--ns-role-primary)] focus:ring-[var(--ns-role-primary)]" onchange="nsGender('P')">
                            <span class="text-sm font-bold">Perempuan</span>
                        </label>
                    </div>
                    <?php if (isset($errors['gender'])): ?>
                    <p class="mt-1.5 text-sm text-rose-600 font-medium"><?= htmlspecialchars($errors['gender']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Tanggal Lahir</label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($f['birth_date']) ?>"
                           class="ns-form-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">No. Telepon</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($f['phone']) ?>"
                           placeholder="08xxxxxxxxxx" class="ns-form-input w-full">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Alamat</label>
                <textarea name="address" rows="2" placeholder="Jl. …" class="ns-form-input w-full resize-y"><?= htmlspecialchars($f['address']) ?></textarea>
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
            <p class="text-xs text-gray-500 mt-1">Kosongkan jika siswa tidak memerlukan akses portal</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($f['email']) ?>"
                           placeholder="siswa@sekolah.sch.id"
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
        <a href="<?= BASE_URL ?>/pages/admin/students/index.php" class="ns-btn ns-btn-outline bg-white text-gray-700 border-gray-200 hover:bg-gray-50">Batal</a>
        <button type="submit" class="ns-btn ns-btn-primary shadow-sm shadow-[var(--ns-role-primary)]/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Siswa
        </button>
    </div>

    </form>
</div>

<script>
var rp = getComputedStyle(document.documentElement).getPropertyValue('--ns-role-primary').trim() || '#3730A3';
function nsGender(v) {
    ['L','P'].forEach(function(g) {
        var lbl = document.getElementById('lbl-' + g);
        if (!lbl) return;
        lbl.style.borderColor = g === v ? rp : '#E8E6DF';
        lbl.style.color = g === v ? rp : '#6B7280';
    });
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
