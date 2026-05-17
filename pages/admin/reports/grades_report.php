<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Grade.php';
require_once APP_ROOT . '/models/ClassRoom.php';

requireRole('admin');

$classId  = (int) ($_GET['class_id'] ?? 0);
$semester = (int) ($_GET['semester']  ?? SEMESTER);
$year     = $_GET['year'] ?? ACADEMIC_YEAR;
$classes  = ClassRoom::getForSelect();
$rows     = $classId ? Grade::getReportByClass($classId, $semester, $year) : [];

$matrix   = [];
$subjects = [];
foreach ($rows as $r) {
    $sn = $r['student_name'];
    $mn = $r['subject_name'] ?? '—';
    $subjects[$mn] = true;
    $matrix[$sn]['nis'] = $r['nis'];
    $matrix[$sn]['subjects'][$mn] = $r['score_average'] !== null ? (float) $r['score_average'] : null;
}
$subjects = array_keys($subjects);

// Export Excel
if ($classId && !empty($matrix) && isset($_GET['export']) && $_GET['export'] === 'excel') {
    $className = '';
    foreach ($classes as $c) { if ($c['id'] == $classId) $className = $c['label']; }
    $filename = "Laporan_Nilai_" . str_replace(' ', '_', $className) . "_Sem{$semester}.xls";
    
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo '<table border="1">';
    echo '<tr><th colspan="' . (4 + count($subjects)) . '">Laporan Nilai - ' . htmlspecialchars($className) . ' (Semester ' . $semester . ' TA ' . htmlspecialchars($year) . ')</th></tr>';
    echo '<tr><th>No</th><th>NIS</th><th>Nama Siswa</th>';
    foreach ($subjects as $sub) echo '<th>' . htmlspecialchars($sub) . '</th>';
    echo '<th>Rata-rata</th></tr>';
    
    $i = 0;
    foreach ($matrix as $studentName => $data) {
        $i++;
        $scores = array_filter(array_values($data['subjects']), fn($v) => $v !== null);
        $avg    = count($scores) ? round(array_sum($scores) / count($scores), 1) : null;
        
        echo '<tr>';
        echo '<td>' . $i . '</td>';
        echo '<td>="' . htmlspecialchars($data['nis']) . '"</td>'; // Use ="..." to prevent Excel from removing leading zeros
        echo '<td>' . htmlspecialchars($studentName) . '</td>';
        foreach ($subjects as $sub) {
            $val = $data['subjects'][$sub] ?? null;
            echo '<td>' . ($val !== null ? number_format($val, 1) : '-') . '</td>';
        }
        echo '<td>' . ($avg !== null ? number_format($avg, 1) : '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

$pageTitle  = 'Laporan Nilai';
$breadcrumb = [['label' => 'Laporan'], ['label' => 'Nilai']];
require_once APP_ROOT . '/includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
@page { size: A4 landscape; margin: 1.5cm; }
@media print {
    .ns-table th, .ns-table td { border: 1px solid #d1d5db !important; }
    .ns-table thead th { background: #f3f4f6 !important; color: #111827 !important; }
}
</style>

<div data-animate="fade-up">

    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Laporan Nilai</h1>
            <p class="ns-page-subtitle">Rekap nilai siswa per kelas per semester</p>
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
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Semester</label>
                <select name="semester" class="ns-form-input w-full text-sm py-1.5">
                    <option value="1" <?= $semester == 1 ? 'selected' : '' ?>>Semester 1 (Ganjil)</option>
                    <option value="2" <?= $semester == 2 ? 'selected' : '' ?>>Semester 2 (Genap)</option>
                </select>
            </div>
            <div class="flex-1 min-w-[120px]">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Tahun Ajaran</label>
                <input type="text" name="year" value="<?= htmlspecialchars($year) ?>" placeholder="2025/2026"
                       class="ns-form-input w-full text-sm py-1.5">
            </div>
            <button type="submit" class="ns-btn ns-btn-primary ns-btn-sm h-[34px] self-end">Tampilkan</button>
        </form>
    </div>

    <?php if ($classId && !empty($matrix)):
        $className = '';
        foreach ($classes as $c) { if ($c['id'] == $classId) $className = $c['label']; }
    ?>
    <div id="report-panel" class="ns-glass-panel overflow-hidden mb-6">

        <!-- ══ KOP SURAT (hanya cetak/PDF) ══════════════════════════════ -->
        <div id="print-letterhead" class="print-only" style="padding:20px 28px 0;">
            <!-- Logo + Identitas Sekolah -->
            <div style="display:flex;align-items:center;gap:18px;padding-bottom:10px;border-bottom:3px double #1e3a5f;margin-bottom:14px;">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo <?= SCHOOL_NAME ?>"
                     style="width:72px;height:72px;object-fit:contain;flex-shrink:0;">
                <div style="flex:1;text-align:center;line-height:1.45;">
                    <div style="font-size:9px;font-weight:500;color:#6b7280;text-transform:uppercase;letter-spacing:.07em;margin-bottom:2px;">
                        <?= SCHOOL_PROV ?>
                    </div>
                    <div style="font-size:18px;font-weight:800;color:#1e3a5f;text-transform:uppercase;letter-spacing:.07em;margin:2px 0;">
                        <?= SCHOOL_NAME ?>
                    </div>
                    <div style="font-size:9px;color:#6b7280;">
                        <?= SCHOOL_ADDRESS ?>
                    </div>
                </div>
                <div style="width:72px;flex-shrink:0;"></div>
            </div>
            <!-- Judul Laporan -->
            <div style="text-align:center;margin-bottom:14px;">
                <div style="font-size:13.5px;font-weight:700;text-transform:uppercase;text-decoration:underline;letter-spacing:.05em;color:#111827;">
                    Laporan Nilai Siswa
                </div>
                <div style="font-size:10px;color:#374151;margin-top:4px;">
                    Kelas: <b><?= htmlspecialchars($className) ?></b> &nbsp;|&nbsp;
                    Semester: <b><?= $semester ?> (<?= $semester == 1 ? 'Ganjil' : 'Genap' ?>)</b> &nbsp;|&nbsp;
                    Tahun Ajaran: <b><?= htmlspecialchars($year) ?></b> &nbsp;|&nbsp;
                    Jumlah Siswa: <b><?= count($matrix) ?> orang</b>
                </div>
            </div>
        </div>
        <!-- ════════════════════════════════════════════════════════════ -->

        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print">
            <div>
                <p class="text-base font-bold text-gray-900">
                    Laporan Nilai · <?= htmlspecialchars($className) ?>
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    Semester <?= $semester ?> · TA <?= htmlspecialchars($year) ?> · <?= count($matrix) ?> siswa
                </p>
            </div>

            <div class="flex items-center gap-2 no-print">
                <a href="?class_id=<?= $classId ?>&semester=<?= $semester ?>&year=<?= urlencode($year) ?>&export=excel" class="ns-btn ns-btn-sm" style="background-color: #059669; color: white;">
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
                        <?php foreach ($subjects as $sub): ?>
                        <th class="text-center w-20 text-[10px] leading-tight"><?= htmlspecialchars($sub) ?></th>
                        <?php endforeach; ?>
                        <th class="text-center text-[10px] w-20 bg-gray-50/50">RATA<br>RATA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $i = 0; foreach ($matrix as $studentName => $data): $i++;
                        $scores = array_filter(array_values($data['subjects']), fn($v) => $v !== null);
                        $avg    = count($scores) ? round(array_sum($scores) / count($scores), 1) : null;
                    ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="font-mono text-xs text-gray-400 text-center"><?= $i ?></td>
                        <td class="font-mono text-xs text-gray-500"><?= htmlspecialchars($data['nis']) ?></td>
                        <td class="font-medium text-gray-900"><?= htmlspecialchars($studentName) ?></td>
                        <?php foreach ($subjects as $sub):
                            $val = $data['subjects'][$sub] ?? null;
                        ?>
                        <td class="font-mono text-xs text-center <?= ($val !== null && $val < 70) ? 'text-rose-600 font-bold' : 'text-gray-500' ?>">
                            <?= $val !== null ? number_format($val, 1) : '—' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="font-mono text-xs text-center font-bold bg-gray-50/50 <?= $avg !== null ? ($avg >= 70 ? 'text-[var(--ns-role-primary)]' : 'text-rose-600') : 'text-gray-400' ?>">
                            <?= $avg !== null ? number_format($avg, 1) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ══ TANDA TANGAN (hanya cetak/PDF) ════════════════════════════ -->
        <div id="print-signature" class="print-only">
            <div style="display:flex;justify-content:space-between;padding:24px 64px 16px;border-top:1px solid #e5e7eb;margin-top:4px;">
                <div style="text-align:center;">
                    <div style="font-size:10px;margin-bottom:56px;line-height:1.7;">
                        Mengetahui,<br><b>Kepala Sekolah</b>
                    </div>
                    <div style="border-top:1px solid #111827;padding-top:5px;min-width:200px;">
                        <div style="font-size:10px;font-weight:600;height:15px;">&nbsp;</div>
                        <div style="font-size:9px;color:#6b7280;">NIP. ________________________________</div>
                    </div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:10px;margin-bottom:56px;line-height:1.7;">
                        Palembang, <?= date('d F Y') ?><br><b>Wali Kelas</b>
                    </div>
                    <div style="border-top:1px solid #111827;padding-top:5px;min-width:200px;">
                        <div style="font-size:10px;font-weight:600;height:15px;">&nbsp;</div>
                        <div style="font-size:9px;color:#6b7280;">NIP. ________________________________</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ════════════════════════════════════════════════════════════ -->

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
            <?= $classId ? 'Belum ada data nilai untuk kelas ini pada semester ini.' : 'Pilih kelas untuk menampilkan laporan nilai.' ?>
        </p>
    </div>
    <?php endif; ?>

</div>

<script>
function exportReportPDF() {
    const element    = document.getElementById('report-panel');
    const letterhead = document.getElementById('print-letterhead');
    const signature  = document.getElementById('print-signature');
    const filename   = 'Laporan_Nilai_<?= str_replace(' ', '_', addslashes($className)) ?>_Sem<?= $semester ?>.pdf';

    // Tampilkan kop surat & tanda tangan saat PDF di-generate
    letterhead.style.display = 'block';
    signature.style.display  = 'block';

    const opt = {
        margin:      [0.4, 0.4, 0.4, 0.4],
        filename:    filename,
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, logging: false },
        jsPDF:       { unit: 'in', format: 'a4', orientation: 'landscape' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        // Sembunyikan kembali setelah selesai
        letterhead.style.display = '';
        signature.style.display  = '';
    });
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
