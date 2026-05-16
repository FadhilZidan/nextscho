<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Attendance.php';
require_once APP_ROOT . '/models/ClassRoom.php';

requireRole('admin');

$classId = (int) ($_GET['class_id'] ?? 0);
$month   = (int) ($_GET['month']    ?? date('n'));
$year    = (int) ($_GET['year']     ?? date('Y'));
$classes = ClassRoom::getForSelect();
$rows    = $classId ? Attendance::getReportByClass($classId, $month, $year) : [];

$monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// Export Excel
if ($classId && !empty($rows) && isset($_GET['export']) && $_GET['export'] === 'excel') {
    $className = '';
    foreach ($classes as $c) { if ($c['id'] == $classId) $className = $c['label']; }
    $filename = "Laporan_Absensi_" . str_replace(' ', '_', $className) . "_{$monthNames[$month]}_{$year}.xls";
    
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo '<table border="1">';
    echo '<tr><th colspan="9">Laporan Absensi - ' . htmlspecialchars($className) . ' (' . $monthNames[$month] . ' ' . $year . ')</th></tr>';
    echo '<tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>Total</th><th>% Hadir</th></tr>';
    
    foreach ($rows as $i => $r) {
        $pct = $r['total_days'] > 0 ? round($r['hadir'] / $r['total_days'] * 100) : 0;
        echo '<tr>';
        echo '<td>' . ($i + 1) . '</td>';
        echo '<td>="' . htmlspecialchars($r['nis']) . '"</td>'; // Prevent Excel from stripping leading zeros
        echo '<td>' . htmlspecialchars($r['student_name']) . '</td>';
        echo '<td>' . $r['hadir'] . '</td>';
        echo '<td>' . $r['izin'] . '</td>';
        echo '<td>' . $r['sakit'] . '</td>';
        echo '<td>' . $r['alpha'] . '</td>';
        echo '<td>' . $r['total_days'] . '</td>';
        echo '<td>' . $pct . '%</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

$pageTitle  = 'Laporan Absensi';
$breadcrumb = [['label' => 'Laporan'], ['label' => 'Absensi']];
require_once APP_ROOT . '/includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div data-animate="fade-up">

    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Laporan Absensi</h1>
            <p class="ns-page-subtitle">Rekap kehadiran siswa per kelas per bulan</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="ns-glass-panel p-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-4 w-full">
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Kelas</label>
                <select name="class_id" class="ns-form-input w-full text-sm py-1.5">
                    <option value="">— Pilih Kelas —</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Bulan</label>
                <select name="month" class="ns-form-input w-full text-sm py-1.5">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[120px]">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Tahun</label>
                <input type="number" name="year" value="<?= $year ?>" min="2020" max="2040"
                       class="ns-form-input w-full text-sm py-1.5">
            </div>
            <button type="submit" class="ns-btn ns-btn-primary ns-btn-sm h-[34px] self-end">Tampilkan</button>
        </form>
    </div>

    <?php if ($classId && !empty($rows)):
        $className = '';
        foreach ($classes as $c) { if ($c['id'] == $classId) $className = $c['label']; }
    ?>
    <div id="report-panel" class="ns-glass-panel overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-base font-bold text-gray-900">
                    Laporan Absensi · <?= htmlspecialchars($className) ?>
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    <?= $monthNames[$month] ?> <?= $year ?> · <?= count($rows) ?> siswa
                </p>
            </div>

            <div class="flex items-center gap-2 no-print">
                <a href="?class_id=<?= $classId ?>&month=<?= $month ?>&year=<?= $year ?>&export=excel" class="ns-btn ns-btn-sm" style="background-color: #059669; color: white;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </a>
                <button onclick="exportReportPDF()" class="ns-btn ns-btn-sm" style="background-color: #E11D48; color: white;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </button>
                <button onclick="window.print()" class="ns-btn ns-btn-outline ns-btn-sm bg-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak
                </button>
            </div>
        </div>
        <div class="overflow-x-auto" id="report-content">
            <table class="ns-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">#</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th class="text-center text-emerald-600">Hadir</th>
                        <th class="text-center text-amber-600">Izin</th>
                        <th class="text-center text-blue-600">Sakit</th>
                        <th class="text-center text-rose-600">Alpha</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">% Hadir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($rows as $i => $r):
                        $pct = $r['total_days'] > 0 ? round($r['hadir'] / $r['total_days'] * 100) : 0;
                        $chip = $pct >= 80 ? 'ns-chip-green' : ($pct >= 60 ? 'ns-chip-amber' : 'ns-chip-red');
                    ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="font-mono text-xs text-gray-400 text-center"><?= $i + 1 ?></td>
                        <td class="font-mono text-xs text-gray-500"><?= htmlspecialchars($r['nis']) ?></td>
                        <td class="font-medium text-gray-900"><?= htmlspecialchars($r['student_name']) ?></td>
                        <td class="font-mono text-xs text-center font-bold text-emerald-600"><?= $r['hadir'] ?></td>
                        <td class="font-mono text-xs text-center text-amber-600"><?= $r['izin'] ?></td>
                        <td class="font-mono text-xs text-center text-blue-600"><?= $r['sakit'] ?></td>
                        <td class="font-mono text-xs text-center font-bold text-rose-600"><?= $r['alpha'] ?></td>
                        <td class="font-mono text-xs text-center text-gray-500"><?= $r['total_days'] ?></td>
                        <td class="text-center">
                            <span class="ns-chip <?= $chip ?>"><?= $pct ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="ns-glass-panel p-16 text-center flex flex-col items-center gap-3">
        <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-gray-500">
            <?= $classId ? 'Belum ada data absensi untuk kelas ini pada bulan ini.' : 'Pilih kelas dan bulan untuk menampilkan laporan absensi.' ?>
        </p>
    </div>
    <?php endif; ?>

</div>

<script>
function exportReportPDF() {
    const element = document.getElementById('report-panel');
    const filename = 'Laporan_Absensi_<?= str_replace(' ', '_', addslashes($className)) ?>_<?= $monthNames[$month] ?>_<?= $year ?>.pdf';
    const opt = {
        margin:       0.5,
        filename:     filename,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
