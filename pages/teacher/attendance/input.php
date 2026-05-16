<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Teacher.php';
require_once APP_ROOT . '/models/ClassRoom.php';
require_once APP_ROOT . '/models/Subject.php';
require_once APP_ROOT . '/models/Attendance.php';

requireRole('guru');

$classId = (int) ($_GET['class_id'] ?? 0);
$date    = $_GET['date'] ?? date('Y-m-d');

$kelas = ClassRoom::getById($classId);

$user     = currentUser();
$teacher  = Teacher::getByUserId((int) $user['id']);

$subjects  = $teacher ? Subject::getByTeacher($teacher['id']) : [];
$classIds  = array_unique(array_column(array_filter($subjects, fn($s) => $s['class_id']), 'class_id'));

if (!$kelas || !$teacher || (!in_array($classId, $classIds) && $kelas['homeroom_teacher_id'] !== $teacher['id'])) {
    flash('error', 'Kelas tidak ditemukan atau Anda tidak memiliki akses.');
    header('Location: ' . BASE_URL . '/pages/teacher/attendance/index.php');
    exit;
}

$students = Attendance::getByClassDate($classId, $date);
$alreadyRecorded = Attendance::dateRecorded($classId, $date);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) die('Invalid CSRF token.');
    $records = [];
    foreach ($_POST['status'] ?? [] as $sid => $status) {
        $records[] = [
            'student_id' => (int) $sid,
            'status'     => in_array($status, ['hadir','izin','sakit','alpha']) ? $status : 'hadir',
            'notes'      => trim($_POST['notes'][$sid] ?? ''),
        ];
    }
    Attendance::saveBatch($classId, $date, $teacher['id'] ?? 0, $records);
    flash('success', 'Absensi ' . date('d M Y', strtotime($date)) . ' berhasil disimpan.');
    header('Location: ' . BASE_URL . '/pages/teacher/attendance/index.php');
    exit;
}

$pageTitle  = 'Input Absensi — ' . $kelas['name'];
$breadcrumb = [
    ['label' => 'Absensi', 'url' => BASE_URL . '/pages/teacher/attendance/index.php'],
    ['label' => $kelas['name'] . ' · ' . date('d M Y', strtotime($date))],
];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

<?php if ($alreadyRecorded): ?>
<div class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm font-medium text-amber-800 flex items-start gap-3 shadow-sm">
    <svg class="w-5 h-5 flex-shrink-0 text-amber-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
    </svg>
    <div>
        <p class="font-bold text-amber-900 mb-0.5">Perhatian</p>
        <p>Absensi untuk tanggal ini sudah pernah diinput sebelumnya. Menyimpan kembali akan <strong>menimpa</strong> data yang sudah ada.</p>
    </div>
</div>
<?php endif; ?>

<form method="POST" id="attendanceForm">
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

    <div class="ns-glass-panel overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-base font-bold text-gray-900">
                    <?= htmlspecialchars($kelas['name']) ?> · <?= date('d M Y', strtotime($date)) ?>
                </p>
                <p class="text-xs text-gray-500 mt-1"><?= count($students) ?> siswa</p>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="button" onclick="setAll('hadir')" class="ns-btn ns-btn-sm flex-1 sm:flex-none justify-center bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100">
                    Semua Hadir
                </button>
                <button type="button" onclick="setAll('alpha')" class="ns-btn ns-btn-sm flex-1 sm:flex-none justify-center bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100">
                    Semua Alpha
                </button>
            </div>
        </div>

        <?php if (empty($students)): ?>
        <div class="p-12 text-center flex flex-col items-center gap-3">
            <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-500">Tidak ada siswa di kelas ini.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="ns-table" id="attendanceTable">
                <thead>
                    <tr>
                        <th class="w-12 text-center">#</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th class="text-center text-emerald-600">Hadir</th>
                        <th class="text-center text-amber-600">Izin</th>
                        <th class="text-center text-blue-600">Sakit</th>
                        <th class="text-center text-rose-600">Alpha</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($students as $i => $s):
                        $cur = $s['status'] ?? 'hadir';
                    ?>
                    <tr data-student="<?= $s['student_id'] ?>" class="hover:bg-gray-50/50 transition-colors">
                        <td class="font-mono text-xs text-gray-400 text-center"><?= $i + 1 ?></td>
                        <td class="font-mono text-xs text-gray-500"><?= htmlspecialchars($s['nis']) ?></td>
                        <td class="font-medium text-gray-900"><?= htmlspecialchars($s['student_name']) ?></td>
                        <?php foreach (['hadir','izin','sakit','alpha'] as $st): ?>
                        <td class="text-center">
                            <input type="radio" name="status[<?= $s['student_id'] ?>]" value="<?= $st ?>"
                                   <?= $cur === $st ? 'checked' : '' ?>
                                   class="w-4 h-4 text-[var(--ns-role-primary)] focus:ring-[var(--ns-role-primary)] border-gray-300 cursor-pointer">
                        </td>
                        <?php endforeach; ?>
                        <td class="p-2">
                            <input type="text" name="notes[<?= $s['student_id'] ?>]"
                                   value="<?= htmlspecialchars($s['notes'] ?? '') ?>"
                                   placeholder="Keterangan…"
                                   class="ns-form-input w-full text-xs py-1.5 px-2.5 bg-transparent border-transparent hover:border-gray-200 focus:bg-white transition-all">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex justify-end">
            <button type="submit" class="ns-btn ns-btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan Absensi
            </button>
        </div>
        <?php endif; ?>
    </div>
</form>

</div>

<script src="<?= BASE_URL ?>/assets/js/attendance.js"></script>
<?php require_once APP_ROOT . '/includes/footer.php'; ?>
