<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/AcademicCalendar.php';

requireRole('admin');

$currentYear = (int) ($_GET['year'] ?? date('Y'));
$events      = AcademicCalendar::getByYear($currentYear);
$grouped     = AcademicCalendar::groupByMonth($events);
$total       = AcademicCalendar::count();
$upcoming    = AcademicCalendar::countUpcoming();

$bulanPanjang = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
    7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
];

$pageTitle  = 'Kalender Akademik';
$breadcrumb = [['label' => 'Admin'], ['label' => 'Kalender Akademik']];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Kalender Akademik</h1>
            <p class="ns-page-subtitle"><?= $total ?> acara terdaftar · <?= $upcoming ?> akan datang</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Year selector -->
            <form method="GET" id="year-form">
                <select name="year" onchange="document.getElementById('year-form').submit()"
                        class="ns-form-input" style="width:auto;padding:8px 14px;font-size:0.875rem;">
                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                    <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
            <a href="<?= BASE_URL ?>/pages/admin/academic_calendar/create.php" class="ns-btn ns-btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Acara
            </a>
        </div>
    </div>

    <!-- Type legend -->
    <div class="flex flex-wrap items-center gap-2 mb-6">
        <span class="text-xs text-gray-400 font-medium mr-1">Tipe:</span>
        <span class="ns-chip ns-chip-red">Libur</span>
        <span class="ns-chip ns-chip-yellow">Ujian</span>
        <span class="ns-chip ns-chip-blue">Kegiatan</span>
        <span class="ns-chip ns-chip-violet">Semester</span>
        <span class="ns-chip ns-chip-ink">Lainnya</span>
    </div>

    <!-- Events grouped by month -->
    <div class="ns-table-wrap">

        <?php if (empty($events)): ?>
        <div class="ns-empty-state" style="padding:64px 24px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:10px;">
            <svg class="w-10 h-10" fill="none" stroke="#E8E6DF" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p style="font-size:13px;color:#9CA3AF;">Belum ada acara untuk tahun <?= $currentYear ?>.</p>
            <a href="<?= BASE_URL ?>/pages/admin/academic_calendar/create.php" class="ns-btn ns-btn-primary ns-btn-sm" style="margin-top:4px;">Tambah Acara Pertama</a>
        </div>
        <?php else: ?>

        <?php foreach ($grouped as $monthKey => $monthEvents):
            [$year, $month] = explode('-', $monthKey);
            $monthLabel = $bulanPanjang[(int)$month] . ' ' . $year;
        ?>
        <!-- Month header -->
        <div style="padding:10px 24px;background:rgba(0,0,0,0.02);border-bottom:1px solid #F1F0EB;">
            <p style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:#9CA3AF;">
                <?= $monthLabel ?>
            </p>
        </div>

        <?php foreach ($monthEvents as $ev):
            $status     = AcademicCalendar::eventStatus($ev['start_date'], $ev['end_date']);
            $borderColor = AcademicCalendar::typeBorderColor($ev['type']);
            $chip       = AcademicCalendar::typeChip($ev['type']);
            $label      = AcademicCalendar::typeLabel($ev['type']);
            $range      = AcademicCalendar::dateRange($ev['start_date'], $ev['end_date']);
        ?>
        <div style="padding:16px 24px;border-bottom:1px solid #F1F0EB;display:flex;align-items:flex-start;gap:16px;border-left:3px solid <?= $borderColor ?>;transition:background 0.15s;">
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:5px;">
                    <span class="ns-chip <?= $chip ?>"><?= $label ?></span>
                    <span style="font-size:11.5px;color:#9CA3AF;font-family:'JetBrains Mono',monospace;"><?= $range ?></span>
                    <?php if ($status === 'ongoing'): ?>
                    <span class="ns-chip ns-chip-green" style="font-size:10px;">Berlangsung</span>
                    <?php elseif ($status === 'upcoming' && (new DateTime($ev['start_date']))->diff(new DateTime())->days <= 7): ?>
                    <span class="ns-chip ns-chip-orange" style="font-size:10px;">Segera</span>
                    <?php endif; ?>
                </div>
                <p style="font-size:14px;font-weight:600;color:#0A0E1A;margin-bottom:3px;line-height:1.4;">
                    <?= htmlspecialchars($ev['title']) ?>
                </p>
                <?php if ($ev['description']): ?>
                <p style="font-size:12.5px;color:#9CA3AF;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    <?= htmlspecialchars($ev['description']) ?>
                </p>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;align-items:center;">
                <a href="<?= BASE_URL ?>/pages/admin/academic_calendar/edit.php?id=<?= $ev['id'] ?>"
                   class="ns-btn ns-btn-outline ns-btn-xs">Edit</a>
                <form method="POST" action="<?= BASE_URL ?>/pages/admin/academic_calendar/delete.php"
                      onsubmit="return confirm('Hapus acara ini?')">
                    <input type="hidden" name="id"   value="<?= $ev['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                    <button type="submit" class="ns-btn ns-btn-danger ns-btn-xs">Hapus</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>

</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
