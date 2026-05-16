<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Teacher.php';
require_once APP_ROOT . '/models/Subject.php';
require_once APP_ROOT . '/models/ClassRoom.php';

requireRole('guru');

$user    = currentUser();
$teacher = Teacher::getByUserId((int) $user['id']);

$subjects  = $teacher ? Subject::getByTeacher($teacher['id']) : [];
$classIds  = array_unique(array_column(array_filter($subjects, fn($s) => $s['class_id']), 'class_id'));
$myClasses = [];
foreach ($classIds as $cid) {
    $kelas = ClassRoom::getById($cid);
    if ($kelas) $myClasses[] = $kelas;
}

$preClassId = (int) ($_GET['class_id'] ?? 0);
$preDate    = $_GET['date'] ?? date('Y-m-d');

$pageTitle  = 'Input Absensi';
$breadcrumb = [['label' => 'Portal Guru'], ['label' => 'Absensi']];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

    <div class="ns-page-header" style="margin-bottom:24px;">
        <div>
            <h1 class="ns-page-title">Input Absensi</h1>
            <p class="ns-page-subtitle"><?= count($myClasses) ?> kelas yang diampu untuk absensi</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Quick Action: Kelas List -->
        <div class="ns-glass-panel overflow-hidden lg:col-span-2">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Kelas Anda</h2>
                    <p class="text-xs text-gray-500 mt-1">Pilih kelas untuk input absensi hari ini (<?= date('d M Y') ?>)</p>
                </div>
                <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>

            <?php if (empty($myClasses)): ?>
            <div class="p-12 text-center flex flex-col items-center gap-3">
                <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">Belum ada kelas yang terdaftar untuk Anda.</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($myClasses as $k): ?>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-6 py-5 hover:bg-gray-50/50 transition-colors">
                    <div>
                        <p class="text-sm font-bold text-gray-900 mb-1">Kelas <?= htmlspecialchars($k['name']) ?></p>
                        <p class="text-xs text-gray-500">Wali Kelas: <?= htmlspecialchars($k['homeroom_teacher'] ?? '—') ?></p>
                    </div>
                    <a href="<?= BASE_URL ?>/pages/teacher/attendance/input.php?class_id=<?= $k['id'] ?>&date=<?= date('Y-m-d') ?>"
                       class="ns-btn ns-btn-primary bg-emerald-600 hover:bg-emerald-700 flex-shrink-0 text-xs sm:text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Absen Hari Ini
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Manual Date Form -->
        <div class="ns-glass-panel overflow-hidden h-fit">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <p class="text-base font-bold text-gray-900">Input Tanggal Lain</p>
                <p class="text-xs text-gray-500 mt-1">Cari absensi di hari sebelumnya</p>
            </div>
            <div class="p-6">
                <form method="GET" action="<?= BASE_URL ?>/pages/teacher/attendance/input.php">
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Kelas <span class="text-rose-500">*</span></label>
                        <?php if (empty($myClasses)): ?>
                        <p class="text-sm text-gray-500 italic">Tidak ada kelas.</p>
                        <?php else: ?>
                        <select name="class_id" required class="ns-form-input w-full">
                            <option value="">— Pilih Kelas —</option>
                            <?php foreach ($myClasses as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $preClassId == $k['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Tanggal <span class="text-rose-500">*</span></label>
                        <input type="date" name="date" value="<?= htmlspecialchars($preDate) ?>"
                               max="<?= date('Y-m-d') ?>" required class="ns-form-input w-full">
                    </div>
                    <button type="submit" <?= empty($myClasses) ? 'disabled' : '' ?>
                            class="ns-btn ns-btn-primary w-full justify-center <?= empty($myClasses) ? 'opacity-50 cursor-not-allowed' : '' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Cari Absensi
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
