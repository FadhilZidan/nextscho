<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Announcement.php';

requireRole('admin');

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;
$list    = Announcement::getAll($perPage, $offset);
$total   = Announcement::count();
$pages   = (int) ceil($total / $perPage);

$pageTitle  = 'Pengumuman';
$breadcrumb = [['label' => 'Admin'], ['label' => 'Pengumuman']];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Pengumuman</h1>
            <p class="ns-page-subtitle"><?= $total ?> pengumuman<?php if ($pages > 1): ?> · Halaman <?= $page ?>/<?= $pages ?><?php endif; ?></p>
        </div>
        <a href="<?= BASE_URL ?>/pages/admin/announcements/create.php" class="ns-btn ns-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Buat Pengumuman
        </a>
    </div>

    <div class="ns-table-wrap">

        <?php if (empty($list)): ?>
        <div class="ns-empty-state" style="padding:64px 24px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:10px;">
            <svg class="w-10 h-10" fill="none" stroke="#E8E6DF" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            <p style="font-size:13px;color:#9CA3AF;">Belum ada pengumuman. Buat yang pertama!</p>
        </div>
        <?php else: ?>
        <div>
            <?php foreach ($list as $a): ?>
            <div class="ns-announce-item" style="padding:18px 24px;border-bottom:1px solid #F1F0EB;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
                <div style="flex:1;min-width:0;">
                    <p style="font-size:14px;font-weight:600;color:#0A0E1A;line-height:1.4;margin-bottom:4px;">
                        <?= htmlspecialchars($a['title']) ?>
                    </p>
                    <p style="font-size:12.5px;color:#9CA3AF;line-height:1.5;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        <?= htmlspecialchars(substr($a['content'], 0, 200)) ?><?= strlen($a['content']) > 200 ? '…' : '' ?>
                    </p>
                    <div class="flex items-center gap-3">
                        <span style="font-size:11.5px;color:#C4C2BB;">
                            <?= date('d M Y · H:i', strtotime($a['created_at'])) ?>
                        </span>
                        <span class="ns-chip ns-chip-violet" style="font-size:10.5px;">Dipublikasi</span>
                    </div>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/pages/admin/announcements/delete.php"
                      onsubmit="return confirm('Hapus pengumuman ini?')"
                      style="flex-shrink:0;">
                    <input type="hidden" name="id"   value="<?= $a['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                    <button type="submit" class="ns-btn ns-btn-danger ns-btn-xs">Hapus</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($pages > 1): ?>
        <div class="ns-table-footer">
            <span>Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> dari <?= $total ?></span>
            <div class="flex items-center gap-1">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?page=<?= $i ?>"
                   class="ns-pagination-btn <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
