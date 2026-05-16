<?php
define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/AcademicCalendar.php';

requireRole('guru');

$currentYear = (int) ($_GET['year'] ?? date('Y'));
$events      = AcademicCalendar::getByYear($currentYear);
$grouped     = AcademicCalendar::groupByMonth($events);
$upcoming    = AcademicCalendar::getUpcoming(5);

$bulanPanjang = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
    7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
];

$pageTitle  = 'Kalender Akademik';
$breadcrumb = [['label' => 'Guru'], ['label' => 'Kalender Akademik']];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

    <!-- Page header -->
    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Kalender Akademik</h1>
            <p class="ns-page-subtitle"><?= count($events) ?> acara · Tahun <?= $currentYear ?></p>
        </div>
        <form method="GET" id="year-form">
            <select name="year" onchange="document.getElementById('year-form').submit()"
                    class="ns-form-input" style="width:auto;padding:8px 14px;font-size:0.875rem;">
                <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main timeline -->
        <div class="lg:col-span-2">
            <div class="ns-table-wrap">

                <?php if (empty($events)): ?>
                <div style="padding:64px 24px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:10px;">
                    <svg class="w-10 h-10" fill="none" stroke="#E8E6DF" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p style="font-size:13px;color:#9CA3AF;">Belum ada acara untuk tahun <?= $currentYear ?>.</p>
                </div>
                <?php else: ?>

                <?php foreach ($grouped as $monthKey => $monthEvents):
                    [$year, $month] = explode('-', $monthKey);
                    $monthLabel = $bulanPanjang[(int)$month] . ' ' . $year;
                ?>
                <div style="padding:10px 24px;background:rgba(5,150,105,0.04);border-bottom:1px solid #F1F0EB;">
                    <p style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:#059669;">
                        <?= $monthLabel ?>
                    </p>
                </div>

                <?php foreach ($monthEvents as $ev):
                    $status      = AcademicCalendar::eventStatus($ev['start_date'], $ev['end_date']);
                    $borderColor = AcademicCalendar::typeBorderColor($ev['type']);
                    $chip        = AcademicCalendar::typeChip($ev['type']);
                    $label       = AcademicCalendar::typeLabel($ev['type']);
                    $range       = AcademicCalendar::dateRange($ev['start_date'], $ev['end_date']);
                ?>
                <div style="padding:16px 24px;border-bottom:1px solid #F1F0EB;border-left:3px solid <?= $borderColor ?>;">
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
                    <p style="font-size:12.5px;color:#9CA3AF;line-height:1.5;">
                        <?= htmlspecialchars($ev['description']) ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endforeach; ?>

                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming sidebar -->
        <div>
            <div class="ns-glass-panel overflow-hidden" style="position:sticky;top:80px;">
                <div style="padding:16px 20px;border-bottom:1px solid rgba(0,0,0,0.06);display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:10px;background:#ECFDF5;display:flex;align-items:center;justify-content:center;">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p style="font-size:14px;font-weight:700;color:#0A0E1A;">Acara Mendatang</p>
                </div>
                <?php if (empty($upcoming)): ?>
                <div style="padding:32px 20px;text-align:center;">
                    <p style="font-size:13px;color:#9CA3AF;">Tidak ada acara mendatang.</p>
                </div>
                <?php else: ?>
                <div style="padding:8px;">
                    <?php foreach ($upcoming as $ev):
                        $chip  = AcademicCalendar::typeChip($ev['type']);
                        $label = AcademicCalendar::typeLabel($ev['type']);
                        $range = AcademicCalendar::dateRange($ev['start_date'], $ev['end_date']);
                        $daysLeft = (new DateTime($ev['start_date']))->diff(new DateTime())->days;
                    ?>
                    <div style="padding:12px;border-radius:10px;margin-bottom:4px;transition:background 0.15s;"
                         onmouseover="this.style.background='rgba(0,0,0,0.03)'"
                         onmouseout="this.style.background='transparent'">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <span class="ns-chip <?= $chip ?>" style="font-size:10px;"><?= $label ?></span>
                            <?php if ($daysLeft === 0): ?>
                            <span style="font-size:10px;color:#059669;font-weight:600;">Hari ini</span>
                            <?php elseif ($daysLeft <= 7): ?>
                            <span style="font-size:10px;color:#D97706;font-weight:600;"><?= $daysLeft ?> hari lagi</span>
                            <?php endif; ?>
                        </div>
                        <p style="font-size:13px;font-weight:600;color:#0A0E1A;margin-bottom:2px;line-height:1.3;">
                            <?= htmlspecialchars($ev['title']) ?>
                        </p>
                        <p style="font-size:11px;color:#9CA3AF;font-family:'JetBrains Mono',monospace;">
                            <?= $range ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Legend -->
            <div class="ns-glass-panel" style="margin-top:16px;padding:16px 20px;">
                <p style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#9CA3AF;margin-bottom:10px;">Keterangan</p>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php
                    $legends = [
                        ['libur',    'Libur Nasional / Sekolah'],
                        ['ujian',    'Ujian / Ulangan'],
                        ['kegiatan', 'Kegiatan Sekolah'],
                        ['semester', 'Awal / Akhir Semester'],
                        ['lainnya',  'Lainnya'],
                    ];
                    foreach ($legends as [$type, $desc]):
                        $chip = AcademicCalendar::typeChip($type);
                        $lbl  = AcademicCalendar::typeLabel($type);
                    ?>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="ns-chip <?= $chip ?>" style="font-size:10px;"><?= $lbl ?></span>
                        <span style="font-size:12px;color:#6B7280;"><?= $desc ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
