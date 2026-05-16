<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Teacher.php';

requireRole('admin');

$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$teachers = Teacher::getAll($search, $perPage, $offset);
$total    = Teacher::count($search);
$pages    = (int) ceil($total / $perPage);

// Excel export — fetch all matching rows (no pagination)
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $allTeachers = Teacher::getAll($search, PHP_INT_MAX, 0);
    $filename    = 'Data_Guru.xls';

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<table border="1">';
    echo '<tr><th colspan="6">Data Guru</th></tr>';
    echo '<tr><th>No</th><th>NIP</th><th>Nama Guru</th><th>Mata Pelajaran</th><th>Telepon</th><th>Email</th></tr>';
    foreach ($allTeachers as $i => $t) {
        echo '<tr>';
        echo '<td>' . ($i + 1) . '</td>';
        echo '<td>="' . htmlspecialchars($t['nip'] ?? '') . '"</td>';
        echo '<td>' . htmlspecialchars($t['name']) . '</td>';
        echo '<td>' . htmlspecialchars($t['subjects'] ?? '—') . '</td>';
        echo '<td>="' . htmlspecialchars($t['phone'] ?? '') . '"</td>';
        echo '<td>' . htmlspecialchars($t['email'] ?? '—') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

$pageTitle  = 'Data Guru';
$breadcrumb = [['label' => 'Admin'], ['label' => 'Guru']];
require_once APP_ROOT . '/includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Daftar Guru</h1>
            <p class="ns-page-subtitle"><?= $total ?> guru terdaftar<?php if ($pages > 1): ?> · Halaman <?= $page ?>/<?= $pages ?><?php endif; ?></p>
        </div>
        <div class="flex items-center gap-2 no-print">
            <?php $exportQs = http_build_query(['search' => $search, 'export' => 'excel']); ?>
            <a href="?<?= $exportQs ?>" class="ns-btn ns-btn-sm" style="background-color:#059669;color:white;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </a>
            <button onclick="exportTeachersPDF()" class="ns-btn ns-btn-sm" style="background-color:#E11D48;color:white;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                PDF
            </button>
            <button onclick="window.print()" class="ns-btn ns-btn-outline ns-btn-sm bg-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak
            </button>
            <a href="<?= BASE_URL ?>/pages/admin/teachers/create.php" class="ns-btn ns-btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Guru
            </a>
        </div>
    </div>

    <div id="teachers-panel" class="ns-glass-panel overflow-hidden mb-6">

        <!-- Toolbar -->
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print">
            <form method="GET" class="flex items-center gap-3 flex-wrap w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Cari nama / NIP…" 
                           class="ns-form-input w-full pl-10 text-sm">
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" class="ns-btn ns-btn-primary flex-1 sm:flex-none justify-center">Cari</button>
                    <?php if ($search): ?>
                    <a href="?" class="ns-btn ns-btn-outline bg-white flex-1 sm:flex-none justify-center">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
            <?php if ($search): ?>
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
                        <th>NIP</th>
                        <th>Nama Guru</th>
                        <th>Mata Pelajaran</th>
                        <th>Telepon</th>
                        <th>Email</th>
                        <th style="width:100px;" class="no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teachers)): ?>
                    <tr>
                        <td colspan="7" class="p-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-500">
                                    <?= $search ? 'Tidak ada guru yang sesuai pencarian.' : 'Belum ada data guru.' ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($teachers as $i => $t): ?>
                    <tr>
                        <td class="font-num" style="color:#C4C2BB;font-size:12px;"><?= $offset + $i + 1 ?></td>
                        <td>
                            <span class="font-num" style="font-size:12px;color:#6B7280;"><?= htmlspecialchars($t['nip'] ?? '—') ?></span>
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                <?php
                                $hp = UPLOAD_PATH . ($t['photo'] ?? '');
                                $ok = ($t['photo'] ?? 'default.png') !== 'default.png' && file_exists($hp);
                                ?>
                                <?php if ($ok): ?>
                                <img src="<?= UPLOAD_URL . htmlspecialchars($t['photo']) ?>"
                                     class="ns-avatar" style="width:32px;height:32px;" alt="">
                                <?php else: ?>
                                <div class="ns-avatar" style="width:32px;height:32px;font-size:11px;background:#047857;">
                                    <?= strtoupper(mb_substr($t['name'], 0, 2)) ?>
                                </div>
                                <?php endif; ?>
                                <span style="font-weight:500;"><?= htmlspecialchars($t['name']) ?></span>
                            </div>
                        </td>
                        <td style="font-size:12.5px;color:#6B7280;"><?= htmlspecialchars($t['subjects'] ?? '—') ?></td>
                        <td class="font-num" style="font-size:12px;color:#6B7280;"><?= htmlspecialchars($t['phone'] ?? '—') ?></td>
                        <td style="font-size:12px;color:#6B7280;"><?= htmlspecialchars($t['email'] ?? '—') ?></td>
                        <td class="no-print">
                            <div class="flex items-center gap-1.5">
                                <a href="<?= BASE_URL ?>/pages/admin/teachers/edit.php?id=<?= $t['id'] ?>"
                                   class="ns-btn ns-btn-warning ns-btn-xs">Edit</a>
                                <form method="POST" action="<?= BASE_URL ?>/pages/admin/teachers/delete.php"
                                      onsubmit="return confirm('Hapus guru <?= addslashes(htmlspecialchars($t['name'])) ?>?')">
                                    <input type="hidden" name="id"   value="<?= $t['id'] ?>">
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
            <span class="text-xs font-medium text-gray-500">Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> dari <?= $total ?> guru</span>
            <div class="flex items-center gap-1.5">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
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
function exportTeachersPDF() {
    const element = document.getElementById('teachers-panel');
    const opt = {
        margin:      0.5,
        filename:    'Data_Guru.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF:       { unit: 'in', format: 'a4', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
