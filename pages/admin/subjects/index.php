<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Subject.php';
require_once APP_ROOT . '/models/ClassRoom.php';

requireRole('admin');

$classId  = (int) ($_GET['class_id'] ?? 0);
$subjects = Subject::getAll($classId);
$classes  = ClassRoom::getForSelect();

$pageTitle  = 'Mata Pelajaran';
$breadcrumb = [['label' => 'Admin'], ['label' => 'Mata Pelajaran']];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Mata Pelajaran</h1>
            <p class="ns-page-subtitle"><?= count($subjects) ?> mapel terdaftar</p>
        </div>
        <a href="<?= BASE_URL ?>/pages/admin/subjects/create.php" class="ns-btn ns-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Mapel
        </a>
    </div>

    <div class="ns-table-wrap">

        <!-- Toolbar: class filter -->
        <div class="ns-table-toolbar">
            <form method="GET" class="flex items-center gap-2">
                <select name="class_id" onchange="this.form.submit()"
                        class="ns-form-input" style="width:180px;padding:8px 36px 8px 12px;font-size:13px;">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($classId): ?>
                <a href="?" class="ns-btn ns-btn-ghost ns-btn-sm">Semua</a>
                <?php endif; ?>
            </form>
            <?php if ($classId):
                $sel = array_values(array_filter($classes, fn($c) => $c['id'] == $classId))[0] ?? null;
                if ($sel): ?>
            <div class="flex items-center gap-2" style="font-size:12px;color:#9CA3AF;">
                Filter kelas:
                <span class="ns-chip ns-chip-blue"><?= htmlspecialchars($sel['label']) ?></span>
            </div>
            <?php endif; endif; ?>
        </div>

        <!-- Table -->
        <div style="overflow-x:auto;">
            <table class="ns-table">
                <thead>
                    <tr>
                        <th style="width:48px;">#</th>
                        <th>Kode</th>
                        <th>Nama Mapel</th>
                        <th>Kelas</th>
                        <th>Guru Pengampu</th>
                        <th style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="6" style="padding:64px 20px;text-align:center;">
                            <div class="ns-empty-state" style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                                <svg class="w-10 h-10" fill="none" stroke="#E8E6DF" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <p style="font-size:13px;color:#9CA3AF;">Belum ada mata pelajaran.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($subjects as $i => $s): ?>
                    <tr>
                        <td class="font-num" style="color:#C4C2BB;font-size:12px;"><?= $i + 1 ?></td>
                        <td>
                            <span class="font-num ns-chip ns-chip-ink" style="font-size:11.5px;">
                                <?= htmlspecialchars($s['code']) ?>
                            </span>
                        </td>
                        <td style="font-weight:500;"><?= htmlspecialchars($s['name']) ?></td>
                        <td>
                            <?php if ($s['class_name']): ?>
                            <span class="ns-chip ns-chip-blue"><?= htmlspecialchars($s['class_name']) ?></span>
                            <?php else: ?>
                            <span style="color:#C4C2BB;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:13px;color:#6B7280;"><?= htmlspecialchars($s['teacher_name'] ?? '—') ?></td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <a href="<?= BASE_URL ?>/pages/admin/subjects/edit.php?id=<?= $s['id'] ?>"
                                   class="ns-btn ns-btn-warning ns-btn-xs">Edit</a>
                                <form method="POST" action="<?= BASE_URL ?>/pages/admin/subjects/delete.php"
                                      onsubmit="return confirm('Hapus mapel <?= addslashes(htmlspecialchars($s['name'])) ?>?')">
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
    </div>

</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
