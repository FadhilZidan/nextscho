<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/ClassRoom.php';

requireRole('admin');

$classes    = ClassRoom::getAll();
$pageTitle  = 'Data Kelas';
$breadcrumb = [['label' => 'Admin'], ['label' => 'Kelas']];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Daftar Kelas</h1>
            <p class="ns-page-subtitle"><?= count($classes) ?> kelas terdaftar</p>
        </div>
        <a href="<?= BASE_URL ?>/pages/admin/classes/create.php" class="ns-btn ns-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Kelas
        </a>
    </div>

    <div class="ns-table-wrap">
        <div style="overflow-x:auto;">
            <table class="ns-table">
                <thead>
                    <tr>
                        <th style="width:48px;">#</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Tahun Ajaran</th>
                        <th>Wali Kelas</th>
                        <th style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="6" style="padding:64px 20px;text-align:center;">
                            <div class="ns-empty-state" style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                                <svg class="w-10 h-10" fill="none" stroke="#E8E6DF" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <p style="font-size:13px;color:#9CA3AF;">Belum ada data kelas.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($classes as $i => $c): ?>
                    <tr>
                        <td class="font-num" style="color:#C4C2BB;font-size:12px;"><?= $i + 1 ?></td>
                        <td>
                            <span class="ns-chip ns-chip-violet" style="font-size:13px;padding:3px 10px;">
                                <?= htmlspecialchars($c['name']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="ns-chip ns-chip-ink"><?= htmlspecialchars($c['grade_level']) ?></span>
                        </td>
                        <td style="font-size:13px;color:#6B7280;"><?= htmlspecialchars($c['academic_year']) ?></td>
                        <td style="font-size:13px;color:#6B7280;"><?= htmlspecialchars($c['homeroom_name'] ?? '—') ?></td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <a href="<?= BASE_URL ?>/pages/admin/classes/edit.php?id=<?= $c['id'] ?>"
                                   class="ns-btn ns-btn-warning ns-btn-xs">Edit</a>
                                <form method="POST" action="<?= BASE_URL ?>/pages/admin/classes/delete.php"
                                      onsubmit="return confirm('Hapus kelas <?= addslashes(htmlspecialchars($c['name'])) ?>?')">
                                    <input type="hidden" name="id"   value="<?= $c['id'] ?>">
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
