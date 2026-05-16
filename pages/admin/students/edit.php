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

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Invalid student ID.');
    header('Location: ' . BASE_URL . '/pages/admin/students/index.php');
    exit;
}

$student = Student::getById($id);
if (!$student) {
    flash('error', 'Student not found.');
    header('Location: ' . BASE_URL . '/pages/admin/students/index.php');
    exit;
}

$errors  = [];
$classes = ClassRoom::getForSelect();

$f = [
    'nis'        => $student['nis'],
    'name'       => $student['name'],
    'class_id'   => $student['class_id'] ?? '',
    'gender'     => $student['gender'],
    'birth_date' => $student['birth_date'] ?? '',
    'address'    => $student['address']    ?? '',
    'phone'      => $student['phone']      ?? '',
    'email'      => $student['email']      ?? '',
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
    } elseif (Student::nisExists($f['nis'], $id)) {
        $errors['nis'] = 'NIS sudah digunakan.';
    }

    if ($f['name'] === '') $errors['name'] = 'Nama lengkap wajib diisi.';
    if (!in_array($f['gender'], ['L', 'P'], true)) $errors['gender'] = 'Pilih jenis kelamin.';

    if ($f['email'] !== '') {
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        } elseif (User::emailExists($f['email'], (int) ($student['user_id'] ?? 0))) {
            $errors['email'] = 'Email sudah digunakan.';
        }
    }

    if ($f['password'] !== '' && strlen($f['password']) < 6) {
        $errors['password'] = 'Password minimal 6 karakter.';
    }

    $photoFilename = $student['photo'] ?? 'default.png';
    if (isset($_FILES['photo'])) {
        $res = handlePhotoUpload($_FILES['photo'], $photoFilename);
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
            if ($f['email'] !== '') {
                if ($student['user_id']) {
                    User::update((int) $student['user_id'], [
                        'name'     => $f['name'],
                        'email'    => $f['email'],
                        'password' => $f['password'],
                    ]);
                } else {
                    $newUserId = User::create([
                        'name'     => $f['name'],
                        'email'    => $f['email'],
                        'password' => $f['password'] ?: 'siswa123',
                        'role'     => 'siswa',
                    ]);
                    $db->prepare('UPDATE students SET user_id = ? WHERE id = ?')
                       ->execute([$newUserId, $id]);
                }
            }

            Student::update($id, [
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
            flash('success', "Siswa {$f['name']} berhasil diperbarui.");
            header('Location: ' . BASE_URL . '/pages/admin/students/index.php');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors['_general'] = 'Kesalahan server: ' . $e->getMessage();
        }
    }
}

$pageTitle  = 'Edit Siswa';
$breadcrumb = [
    ['label' => 'Siswa', 'url' => BASE_URL . '/pages/admin/students/index.php'],
    ['label' => 'Edit: ' . $student['name']],
];
require_once APP_ROOT . '/includes/header.php';

$photoPath = UPLOAD_PATH . ($student['photo'] ?? '');
$hasPhoto  = ($student['photo'] ?? 'default.png') !== 'default.png' && file_exists($photoPath);
?>

<div class="max-w-2xl" data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:16px;">
            <?php if ($hasPhoto): ?>
            <img src="<?= UPLOAD_URL . htmlspecialchars($student['photo']) ?>"
                 style="width:52px;height:52px;border-radius:14px;object-fit:cover;border:2px solid var(--ns-role-primary);flex-shrink:0;" alt="">
            <?php else: ?>
            <div class="ns-avatar" style="width:52px;height:52px;font-size:15px;flex-shrink:0;">
                <?= strtoupper(mb_substr($student['name'], 0, 2)) ?>
            </div>
            <?php endif; ?>
            <div>
                <h1 class="ns-page-title">Edit Siswa</h1>
                <p class="ns-page-subtitle">NIS <?= htmlspecialchars($student['nis']) ?> · <?= htmlspecialchars($student['name']) ?></p>
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

    <!-- Data siswa -->
    <div class="ns-form-card" style="margin-bottom:16px;">
        <div class="ns-form-section">
            <p class="ns-form-section-title">Data Siswa</p>
            <p class="ns-form-section-sub">Informasi identitas siswa</p>
        </div>
        <div class="ns-form-body">

            <div class="ns-form-row" style="margin-bottom:16px;">
                <div class="ns-form-group">
                    <label class="ns-form-label">NIS<span class="ns-required">*</span></label>
                    <input type="text" name="nis" value="<?= htmlspecialchars($f['nis']) ?>"
                           placeholder="cth. 2025001"
                           class="ns-form-input <?= isset($errors['nis']) ? 'ns-error' : '' ?>">
                    <?php if (isset($errors['nis'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['nis']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">Nama Lengkap<span class="ns-required">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>"
                           placeholder="Nama lengkap siswa"
                           class="ns-form-input <?= isset($errors['name']) ? 'ns-error' : '' ?>">
                    <?php if (isset($errors['name'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ns-form-row" style="margin-bottom:16px;">
                <div class="ns-form-group">
                    <label class="ns-form-label">Kelas</label>
                    <select name="class_id" class="ns-form-input">
                        <option value="">— Pilih Kelas —</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $f['class_id'] == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">Jenis Kelamin<span class="ns-required">*</span></label>
                    <div class="flex gap-3 mt-1">
                        <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px 14px;border:1.5px solid <?= $f['gender'] === 'L' ? 'var(--ns-role-primary)' : '#E8E6DF' ?>;border-radius:10px;cursor:pointer;transition:border-color .15s;font-size:13.5px;font-weight:500;color:<?= $f['gender'] === 'L' ? 'var(--ns-role-primary)' : '#6B7280' ?>;" id="lbl-L">
                            <input type="radio" name="gender" value="L" <?= $f['gender'] === 'L' ? 'checked' : '' ?>
                                   style="accent-color:var(--ns-role-primary);" onchange="nsGender('L')">
                            Laki-laki
                        </label>
                        <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px 14px;border:1.5px solid <?= $f['gender'] === 'P' ? 'var(--ns-role-primary)' : '#E8E6DF' ?>;border-radius:10px;cursor:pointer;transition:border-color .15s;font-size:13.5px;font-weight:500;color:<?= $f['gender'] === 'P' ? 'var(--ns-role-primary)' : '#6B7280' ?>;" id="lbl-P">
                            <input type="radio" name="gender" value="P" <?= $f['gender'] === 'P' ? 'checked' : '' ?>
                                   style="accent-color:var(--ns-role-primary);" onchange="nsGender('P')">
                            Perempuan
                        </label>
                    </div>
                    <?php if (isset($errors['gender'])): ?>
                    <p class="ns-form-error"><?= htmlspecialchars($errors['gender']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ns-form-row" style="margin-bottom:16px;">
                <div class="ns-form-group">
                    <label class="ns-form-label">Tanggal Lahir</label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($f['birth_date']) ?>"
                           class="ns-form-input">
                </div>
                <div class="ns-form-group">
                    <label class="ns-form-label">No. Telepon</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($f['phone']) ?>"
                           placeholder="08xxxxxxxxxx" class="ns-form-input">
                </div>
            </div>

            <div class="ns-form-group" style="margin-bottom:16px;">
                <label class="ns-form-label">Alamat</label>
                <textarea name="address" rows="2" placeholder="Jl. …" class="ns-form-input"><?= htmlspecialchars($f['address']) ?></textarea>
            </div>

            <div class="ns-form-group">
                <label class="ns-form-label">Ganti Foto Profil</label>
                <?php if ($hasPhoto): ?>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <img src="<?= UPLOAD_URL . htmlspecialchars($student['photo']) ?>"
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
                           placeholder="siswa@sekolah.sch.id"
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
        <a href="<?= BASE_URL ?>/pages/admin/students/index.php" class="ns-btn ns-btn-secondary">Batal</a>
        <button type="submit" class="ns-btn ns-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Perubahan
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
