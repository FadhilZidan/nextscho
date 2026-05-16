<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Student.php';
require_once APP_ROOT . '/models/ClassRoom.php';

requireRole('admin');

$search  = trim($_GET['search']   ?? '');
$classId = (int) ($_GET['class_id'] ?? 0);
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$students = Student::getAll($search, $classId, $perPage, $offset);
$total    = Student::count($search, $classId);
$classes  = ClassRoom::getForSelect();
$pages    = (int) ceil($total / $perPage);

// Excel export — fetch all matching rows (no pagination)
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $allStudents = Student::getAll($search, $classId, PHP_INT_MAX, 0);
    $className   = '';
    foreach ($classes as $c) { if ($c['id'] == $classId) $className = $c['label']; }
    $suffix   = $className ? '_' . str_replace(' ', '_', $className) : '';
    $filename = "Data_Siswa{$suffix}.xls";

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<table border="1">';
    echo '<tr><th colspan="6">Data Siswa' . ($className ? ' — ' . htmlspecialchars($className) : '') . '</th></tr>';
    echo '<tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Kelas</th><th>L/P</th><th>Telepon</th></tr>';
    foreach ($allStudents as $i => $s) {
        echo '<tr>';
        echo '<td>' . ($i + 1) . '</td>';
        echo '<td>="' . htmlspecialchars($s['nis']) . '"</td>';
        echo '<td>' . htmlspecialchars($s['name']) . '</td>';
        echo '<td>' . htmlspecialchars($s['class_name'] ?? '—') . '</td>';
        echo '<td>' . ($s['gender'] === 'L' ? 'Laki-laki' : 'Perempuan') . '</td>';
        echo '<td>="' . htmlspecialchars($s['phone'] ?? '') . '"</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

$pageTitle  = 'Data Siswa';
$breadcrumb = [['label' => 'Admin'], ['label' => 'Siswa']];
require_once APP_ROOT . '/includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Daftar Siswa</h1>
            <p class="ns-page-subtitle"><?= $total ?> siswa terdaftar<?php if ($pages > 1): ?> · Halaman <?= $page ?>/<?= $pages ?><?php endif; ?></p>
        </div>
        <div class="flex items-center gap-2 no-print">
            <?php
            $exportQs = http_build_query(['search' => $search, 'class_id' => $classId, 'export' => 'excel']);
            ?>
            <a href="?<?= $exportQs ?>" class="ns-btn ns-btn-sm" style="background-color:#059669;color:white;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </a>
            <button onclick="exportStudentsPDF()" class="ns-btn ns-btn-sm" style="background-color:#E11D48;color:white;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                PDF
            </button>
            <button onclick="window.print()" class="ns-btn ns-btn-outline ns-btn-sm bg-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak
            </button>
            <a href="<?= BASE_URL ?>/pages/admin/students/create.php" class="ns-btn ns-btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Siswa
            </a>
        </div>
    </div>

    <!-- Table card -->
    <div id="students-panel" class="ns-glass-panel overflow-hidden mb-6">

        <!-- Toolbar: search + filter -->
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print">
            <form method="GET" class="flex items-center gap-3 flex-wrap w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Cari nama / NIS…" 
                           class="ns-form-input w-full pl-10 text-sm">
                </div>
                <select name="class_id" class="ns-form-input w-full sm:w-40 text-sm">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" class="ns-btn ns-btn-primary flex-1 sm:flex-none justify-center">Cari</button>
                    <?php if ($search || $classId): ?>
                    <a href="?" class="ns-btn ns-btn-outline bg-white flex-1 sm:flex-none justify-center">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
            <?php if ($search || $classId): ?>
            <p class="text-xs font-medium text-gray-500 whitespace-nowrap">
                Menampilkan <?= $total ?> hasil
            </p>
            <?php endif; ?>
        </div>

        <!-- Table -->
        <div style="overflow-x:auto;">
            <table class="ns-table">
                <thead>
                    <tr>
                        <th style="width:48px;">#</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>L/P</th>
                        <th>Telepon</th>
                        <th style="width:100px;" class="no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="7" class="p-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-500">
                                    <?= $search || $classId ? 'Tidak ada siswa yang sesuai filter.' : 'Belum ada data siswa.' ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($students as $i => $s): ?>
                    <tr>
                        <td class="font-num" style="color:#C4C2BB;font-size:12px;"><?= $offset + $i + 1 ?></td>
                        <td>
                            <span class="font-num" style="font-size:12px;color:#6B7280;"><?= htmlspecialchars($s['nis']) ?></span>
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                <?php
                                $photoPath = UPLOAD_PATH . ($s['photo'] ?? '');
                                $hasPhoto  = ($s['photo'] ?? 'default.png') !== 'default.png' && file_exists($photoPath);
                                ?>
                                <?php if ($hasPhoto): ?>
                                <img src="<?= UPLOAD_URL . htmlspecialchars($s['photo']) ?>"
                                     class="ns-avatar" style="width:32px;height:32px;font-size:11px;" alt="">
                                <?php else: ?>
                                <div class="ns-avatar" style="width:32px;height:32px;font-size:11px;">
                                    <?= strtoupper(mb_substr($s['name'], 0, 2)) ?>
                                </div>
                                <?php endif; ?>
                                <span style="font-weight:500;"><?= htmlspecialchars($s['name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <?php if ($s['class_name']): ?>
                            <span class="ns-chip ns-chip-blue"><?= htmlspecialchars($s['class_name']) ?></span>
                            <?php else: ?>
                            <span style="color:#C4C2BB;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="ns-chip <?= $s['gender'] === 'L' ? 'ns-chip-blue' : 'ns-chip-red' ?>">
                                <?= $s['gender'] === 'L' ? 'L' : 'P' ?>
                            </span>
                        </td>
                        <td class="font-num" style="font-size:12px;color:#6B7280;"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                        <td class="no-print">
                            <div class="flex items-center gap-1.5">
                                <a href="<?= BASE_URL ?>/pages/admin/students/edit.php?id=<?= $s['id'] ?>"
                                   class="ns-btn ns-btn-warning ns-btn-xs">Edit</a>
                                <form method="POST" action="<?= BASE_URL ?>/pages/admin/students/delete.php"
                                      onsubmit="return confirm('Hapus siswa <?= addslashes(htmlspecialchars($s['name'])) ?>?\nTindakan ini tidak dapat dibatalkan.')">
                                    <input type="hidden" name="id"   value="<?= $s['id'] ?>">
                                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                    <button type="submit" class="ns-btn ns-btn-danger ns-btn-xs">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row items-center justify-between gap-4 no-print">
            <span class="text-xs font-medium text-gray-500">Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> dari <?= $total ?> siswa</span>
            <div class="flex items-center gap-1.5">
                <?php
                $qs = http_build_query(['search' => $search, 'class_id' => $classId]);
                for ($i = 1; $i <= $pages; $i++):
                ?>
                <a href="?page=<?= $i ?>&<?= $qs ?>"
                   class="flex items-center justify-center w-8 h-8 rounded-lg text-sm font-medium transition-all <?= $i === $page ? 'bg-[var(--ns-role-primary)] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
function exportStudentsPDF() {
    const element = document.getElementById('students-panel');
    const opt = {
        margin:      0.5,
        filename:    'Data_Siswa.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF:       { unit: 'in', format: 'a4', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
