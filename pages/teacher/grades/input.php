<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Teacher.php';
require_once APP_ROOT . '/models/Subject.php';
require_once APP_ROOT . '/models/Grade.php';

requireRole('guru');

$user    = currentUser();
$teacher = Teacher::getByUserId((int) $user['id']);

$subjectId = (int) ($_GET['subject_id'] ?? 0);
$subject   = Subject::getById($subjectId);

if (!$subject || !$teacher || $subject['teacher_id'] !== $teacher['id']) {
    flash('error', 'Mata pelajaran tidak ditemukan atau Anda tidak memiliki akses.');
    header('Location: ' . BASE_URL . '/pages/teacher/grades/index.php');
    exit;
}

$semester = (int) ($_GET['semester'] ?? SEMESTER);
$year     = $_GET['year'] ?? ACADEMIC_YEAR;
$classId  = (int) ($subject['class_id'] ?? 0);
$students = $classId ? Grade::getByClassSubject($classId, $subjectId, $semester, $year) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) die('Invalid CSRF token.');
    $grades = $_POST['grades'] ?? [];
    foreach ($grades as $studentId => $scores) {
        Grade::save((int) $studentId, $subjectId, $scores, $semester, $year);
    }
    flash('success', 'Nilai berhasil disimpan.');
    header('Location: ' . BASE_URL . '/pages/teacher/grades/input.php?subject_id=' . $subjectId . '&semester=' . $semester . '&year=' . urlencode($year));
    exit;
}

$pageTitle  = 'Input Nilai — ' . $subject['name'];
$breadcrumb = [
    ['label' => 'Input Nilai', 'url' => BASE_URL . '/pages/teacher/grades/index.php'],
    ['label' => $subject['name']],
];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

    <!-- Filter bar -->
    <div class="ns-glass-panel p-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Mapel:</span>
            <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-100"><?= htmlspecialchars($subject['name']) ?></span>
            <?php if ($subject['class_name']): ?>
            <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-blue-50 text-blue-700 border border-blue-100"><?= htmlspecialchars($subject['class_name']) ?></span>
            <?php endif; ?>
        </div>
        <form method="GET" class="flex items-center gap-2 w-full sm:w-auto flex-wrap">
            <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
            <select name="semester" class="ns-form-input w-full sm:w-[170px] text-sm py-1.5">
                <option value="1" <?= $semester==1?'selected':'' ?>>Semester 1 (Ganjil)</option>
                <option value="2" <?= $semester==2?'selected':'' ?>>Semester 2 (Genap)</option>
            </select>
            <input type="text" name="year" value="<?= htmlspecialchars($year) ?>" placeholder="2025/2026"
                   class="ns-form-input w-full sm:w-[110px] text-sm py-1.5">
            <button type="submit" class="ns-btn ns-btn-secondary ns-btn-sm w-full sm:w-auto justify-center">Terapkan</button>
        </form>
    </div>

    <?php if (empty($students)): ?>
    <div class="ns-glass-panel p-12 text-center flex flex-col items-center gap-3">
        <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-gray-500">
            <?= $classId ? 'Belum ada siswa di kelas ini.' : 'Mata pelajaran ini belum memiliki kelas yang terdaftar.' ?>
        </p>
    </div>
    <?php else: ?>

    <form method="POST" id="gradeForm">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        <div class="ns-glass-panel overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <p class="text-base font-bold text-gray-900">Input Nilai Siswa</p>
                    <p class="text-xs text-gray-500 mt-1">Semester <?= $semester ?> · TA <?= htmlspecialchars($year) ?> · <?= count($students) ?> siswa</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded bg-white border border-gray-200 text-gray-600 shadow-sm">H × 30%</span>
                    <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded bg-white border border-gray-200 text-gray-600 shadow-sm">UTS × 30%</span>
                    <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded bg-white border border-gray-200 text-gray-600 shadow-sm">UAS × 40%</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="ns-table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th class="text-center w-28">Nilai Harian</th>
                            <th class="text-center w-28">UTS</th>
                            <th class="text-center w-28">UAS</th>
                            <th class="text-center w-28">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($students as $i => $s): ?>
                        <tr id="row-<?= $s['student_id'] ?>" class="hover:bg-gray-50/50 transition-colors">
                            <td class="font-mono text-xs text-gray-400 text-center"><?= $i + 1 ?></td>
                            <td class="font-mono text-xs text-gray-500"><?= htmlspecialchars($s['nis']) ?></td>
                            <td class="font-medium text-gray-900"><?= htmlspecialchars($s['student_name']) ?></td>
                            <td class="p-2">
                                <input type="number" name="grades[<?= $s['student_id'] ?>][daily]"
                                       value="<?= $s['score_daily'] !== null ? $s['score_daily'] : '' ?>"
                                       min="0" max="100" step="0.1" placeholder="—"
                                       onchange="calcAvg(<?= $s['student_id'] ?>)"
                                       class="ns-form-input w-full text-center font-mono text-sm py-1.5 px-2 bg-transparent border-transparent hover:border-gray-200 focus:bg-white transition-all">
                            </td>
                            <td class="p-2">
                                <input type="number" name="grades[<?= $s['student_id'] ?>][mid]"
                                       value="<?= $s['score_mid'] !== null ? $s['score_mid'] : '' ?>"
                                       min="0" max="100" step="0.1" placeholder="—"
                                       onchange="calcAvg(<?= $s['student_id'] ?>)"
                                       class="ns-form-input w-full text-center font-mono text-sm py-1.5 px-2 bg-transparent border-transparent hover:border-gray-200 focus:bg-white transition-all">
                            </td>
                            <td class="p-2">
                                <input type="number" name="grades[<?= $s['student_id'] ?>][final]"
                                       value="<?= $s['score_final'] !== null ? $s['score_final'] : '' ?>"
                                       min="0" max="100" step="0.1" placeholder="—"
                                       onchange="calcAvg(<?= $s['student_id'] ?>)"
                                       class="ns-form-input w-full text-center font-mono text-sm py-1.5 px-2 bg-transparent border-transparent hover:border-gray-200 focus:bg-white transition-all">
                            </td>
                            <td class="font-mono text-center text-sm" id="avg-<?= $s['student_id'] ?>">
                                <?php
                                $avgVal = $s['score_average'] !== null ? number_format((float)$s['score_average'], 1) : '—';
                                $avgClr = ($s['score_average'] !== null && (float)$s['score_average'] < 70) ? 'text-rose-600' : 'text-emerald-600';
                                ?>
                                <span class="font-bold <?= $avgClr ?>"><?= $avgVal ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-xs text-gray-500 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Nilai dihitung otomatis: H×30% + UTS×30% + UAS×40%
                </div>
                <button type="submit" class="ns-btn ns-btn-primary w-full sm:w-auto justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Semua Nilai
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>

</div>

<script src="<?= BASE_URL ?>/assets/js/grade_input.js"></script>
<?php require_once APP_ROOT . '/includes/footer.php'; ?>
