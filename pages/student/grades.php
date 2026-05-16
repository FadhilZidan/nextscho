<?php
define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Student.php';
require_once APP_ROOT . '/models/Grade.php';

requireRole('siswa');

$user    = currentUser();
$student = Student::getByUserId((int) $user['id']);
if (!$student) { flash('error', 'Data siswa tidak ditemukan.'); header('Location: ' . BASE_URL . '/auth/login.php'); exit; }

$semester = (int) ($_GET['semester'] ?? SEMESTER);
$year     = $_GET['year'] ?? ACADEMIC_YEAR;
$grades   = Grade::getByStudent($student['id'], $semester, $year);

$avg  = null;
$vals = array_filter(array_column($grades, 'score_average'), fn($v) => $v !== null);
if (count($vals)) $avg = round(array_sum($vals) / count($vals), 2);

$pageTitle  = 'Nilai Saya';
$breadcrumb = [['label' => 'Portal Siswa'], ['label' => 'Nilai Saya']];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Nilai Saya</h1>
            <p class="ns-page-subtitle">Semester <?= $semester ?> · TA <?= htmlspecialchars($year) ?><?php if ($avg !== null): ?> · Rata-rata <strong><?= number_format($avg, 1) ?></strong><?php endif; ?></p>
        </div>
    </div>

    <!-- Filter -->
    <div class="ns-glass-panel p-4 mb-6 flex flex-wrap items-center gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-2 w-full">
            <select name="semester" class="ns-form-input w-[200px] text-sm">
                <option value="1" <?= $semester==1?'selected':'' ?>>Semester 1 (Ganjil)</option>
                <option value="2" <?= $semester==2?'selected':'' ?>>Semester 2 (Genap)</option>
            </select>
            <input type="text" name="year" value="<?= htmlspecialchars($year) ?>" placeholder="2025/2026"
                   class="ns-form-input w-[120px] text-sm">
            <button type="submit" class="ns-btn ns-btn-primary">Tampilkan</button>
        </form>
    </div>

    <?php if (empty($grades)): ?>
    <div class="ns-glass-panel p-12 text-center flex flex-col items-center justify-center gap-3">
        <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-gray-500">Belum ada nilai untuk semester ini.</p>
    </div>
    <?php else: ?>
    <div class="ns-glass-panel overflow-hidden" data-animate="fade-up" data-delay="1">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white/50">
            <div>
                <h2 class="text-base font-bold text-gray-900">Rincian Nilai</h2>
                <p class="text-xs text-gray-500 mt-1"><?= count($grades) ?> mata pelajaran</p>
            </div>
            <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="ns-table">
                <thead>
                    <tr>
                        <th style="width:48px;">#</th>
                        <th>Kode</th>
                        <th>Mata Pelajaran</th>
                        <th style="text-align:center;">Harian</th>
                        <th style="text-align:center;">UTS</th>
                        <th style="text-align:center;">UAS</th>
                        <th style="text-align:center;">Rata-rata</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $i => $g):
                        $score = $g['score_average'];
                        $pass  = $score === null || (float)$score >= 70;
                    ?>
                    <tr>
                        <td class="font-num" style="color:#C4C2BB;font-size:12px;"><?= $i + 1 ?></td>
                        <td>
                            <span class="font-num ns-chip ns-chip-ink" style="font-size:11.5px;">
                                <?= htmlspecialchars($g['subject_code'] ?? '—') ?>
                            </span>
                        </td>
                        <td style="font-weight:500;"><?= htmlspecialchars($g['subject_name']) ?></td>
                        <td class="font-num" style="text-align:center;color:#6B7280;">
                            <?= $g['score_daily'] !== null ? number_format((float)$g['score_daily'], 1) : '<span style="color:#C4C2BB;">—</span>' ?>
                        </td>
                        <td class="font-num" style="text-align:center;color:#6B7280;">
                            <?= $g['score_mid'] !== null ? number_format((float)$g['score_mid'], 1) : '<span style="color:#C4C2BB;">—</span>' ?>
                        </td>
                        <td class="font-num" style="text-align:center;color:#6B7280;">
                            <?= $g['score_final'] !== null ? number_format((float)$g['score_final'], 1) : '<span style="color:#C4C2BB;">—</span>' ?>
                        </td>
                        <td class="font-num" style="text-align:center;font-weight:700;color:<?= $score !== null ? ((float)$score >= 70 ? 'var(--ns-role-primary)' : '#DC2626') : '#C4C2BB' ?>;">
                            <?= $score !== null ? number_format((float)$score, 1) : '—' ?>
                        </td>
                        <td style="text-align:center;">
                            <?php if ($score !== null): ?>
                            <span class="ns-chip <?= $pass ? 'ns-chip-green' : 'ns-chip-red' ?>">
                                <?= $pass ? 'Lulus' : 'Remedial' ?>
                            </span>
                            <?php else: ?>
                            <span style="color:#C4C2BB;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
