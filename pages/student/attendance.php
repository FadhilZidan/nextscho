<?php
define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Student.php';
require_once APP_ROOT . '/models/Attendance.php';

requireRole('siswa');

$user    = currentUser();
$student = Student::getByUserId((int) $user['id']);
if (!$student) { flash('error', 'Data siswa tidak ditemukan.'); header('Location: ' . BASE_URL . '/auth/login.php'); exit; }

$month   = (int) ($_GET['month'] ?? date('n'));
$year    = (int) ($_GET['year']  ?? date('Y'));
$records = Attendance::getByStudent($student['id'], $month, $year);
$summary = Attendance::getSummary($student['id'], $year);

$monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$dayNames   = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];

$total    = $summary['hadir'] + $summary['izin'] + $summary['sakit'] + $summary['alpha'];
$hadirPct = $total > 0 ? round(($summary['hadir'] / $total) * 100) : 0;

$pageTitle  = 'Rekap Kehadiran';
$breadcrumb = [['label' => 'Portal Siswa'], ['label' => 'Kehadiran']];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Rekap Kehadiran</h1>
            <p class="ns-page-subtitle">Kehadiran tahun <?= $year ?> · <?= $hadirPct ?>% hadir</p>
        </div>
    </div>

    <!-- Summary stat cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <?php
        $stats = [
            ['label' => 'Hadir',  'value' => $summary['hadir'],  'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50'],
            ['label' => 'Izin',   'value' => $summary['izin'],   'color' => 'text-amber-600', 'bg' => 'bg-amber-50'],
            ['label' => 'Sakit',  'value' => $summary['sakit'],  'color' => 'text-blue-600', 'bg' => 'bg-blue-50'],
            ['label' => 'Alpha',  'value' => $summary['alpha'],  'color' => 'text-rose-600', 'bg' => 'bg-rose-50'],
        ];
        foreach ($stats as $s): ?>
        <div class="ns-glass-panel p-5 ns-card-hover flex flex-col justify-between" data-animate="scale-in">
            <p class="text-xs font-bold tracking-widest uppercase mb-2 <?= $s['color'] ?>"><?= $s['label'] ?></p>
            <p class="font-serif text-3xl font-normal leading-none mb-1 <?= $s['color'] ?>"><?= $s['value'] ?></p>
            <p class="text-xs font-medium opacity-70 <?= $s['color'] ?>">hari (<?= $year ?>)</p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Attendance rate bar -->
    <div class="ns-glass-panel p-6 mb-6" data-animate="fade-up" data-delay="1">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-bold text-gray-900">Tingkat Kehadiran <?= $year ?></span>
            <span class="ns-chip <?= $hadirPct >= 80 ? 'ns-chip-green' : ($hadirPct >= 60 ? 'ns-chip-amber' : 'ns-chip-red') ?>">
                <?= $hadirPct ?>%
            </span>
        </div>
        <div class="ns-score-bar">
            <div class="ns-score-fill <?= $hadirPct >= 80 ? 'bg-emerald-500' : ($hadirPct >= 60 ? 'bg-amber-500' : 'bg-rose-500') ?>"
                 style="width:<?= $hadirPct ?>%;"></div>
        </div>
    </div>

    <!-- Filter bar -->
    <div class="ns-glass-panel p-4 mb-6 flex flex-wrap items-center gap-3" data-animate="fade-up" data-delay="2">
        <form method="GET" class="flex flex-wrap items-center gap-2 w-full">
            <select name="month" class="ns-form-input w-[160px] text-sm">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $month==$m?'selected':'' ?>><?= $monthNames[$m] ?></option>
                <?php endfor; ?>
            </select>
            <input type="number" name="year" value="<?= $year ?>" min="2020" max="2040"
                   class="ns-form-input w-[100px] text-sm">
            <button type="submit" class="ns-btn ns-btn-primary">
                Tampilkan
            </button>
        </form>
    </div>

    <!-- Records table -->
    <div class="ns-glass-panel overflow-hidden" data-animate="fade-up" data-delay="3">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h2 class="text-base font-bold text-gray-900">Kehadiran <?= $monthNames[$month] ?> <?= $year ?></h2>
                <p class="text-xs text-gray-500 mt-1"><?= count($records) ?> hari tercatat</p>
            </div>
            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="ns-table">
                <thead>
                    <tr>
                        <th style="width:48px;">#</th>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-3">
                                <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-500">Belum ada data kehadiran bulan ini.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($records as $i => $r):
                        $dayId = $dayNames[date('l', strtotime($r['date']))] ?? date('l', strtotime($r['date']));
                        $chipMap = [
                            'hadir' => 'ns-chip-green',
                            'izin'  => 'ns-chip-amber',
                            'sakit' => 'ns-chip-blue',
                            'alpha' => 'ns-chip-red',
                        ];
                        $chip = $chipMap[$r['status']] ?? 'ns-chip-gray';
                    ?>
                    <tr>
                        <td class="font-num" style="color:#C4C2BB;font-size:12px;"><?= $i + 1 ?></td>
                        <td class="font-num" style="font-weight:500;"><?= date('d M Y', strtotime($r['date'])) ?></td>
                        <td style="color:#6B7280;"><?= $dayId ?></td>
                        <td>
                            <span class="ns-chip <?= $chip ?>" style="text-transform:capitalize;">
                                <?= htmlspecialchars($r['status']) ?>
                            </span>
                        </td>
                        <td style="font-size:12px;color:#9CA3AF;font-style:italic;"><?= htmlspecialchars($r['notes'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
