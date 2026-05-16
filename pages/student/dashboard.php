<?php
define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Student.php';
require_once APP_ROOT . '/models/Grade.php';
require_once APP_ROOT . '/models/Attendance.php';
require_once APP_ROOT . '/models/Announcement.php';

requireRole('siswa');

$user    = currentUser();
$student = Student::getByUserId((int) $user['id']);
if (!$student) {
    flash('error', 'Data siswa tidak ditemukan.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$avgGrade      = Grade::getAverageByStudent($student['id'], SEMESTER, ACADEMIC_YEAR);
$summary       = Attendance::getSummary($student['id'], (int) date('Y'));
$announcements = Announcement::getLatest(4);

$total    = $summary['hadir'] + $summary['izin'] + $summary['sakit'] + $summary['alpha'];
$hadirPct = $total > 0 ? round(($summary['hadir'] / $total) * 100) : 0;

/* SVG arc ring: r=54, circumference=2π×54≈339.3 */
$arcR    = 54;
$arcCirc = round(2 * M_PI * $arcR, 1);   /* 339.3 */
$arcOffset = $avgGrade !== null
    ? round($arcCirc * (1 - min($avgGrade, 100) / 100), 1)
    : $arcCirc;

$pageTitle  = 'Dashboard';
$breadcrumb = [['label' => 'Dashboard']];
require_once APP_ROOT . '/includes/header.php';

$firstName = explode(' ', $user['name'])[0];
?>

<!-- Greeting Banner -->
<div class="ns-glass-panel p-8 mb-8 relative overflow-hidden" data-animate="fade-up" style="border: none; background: linear-gradient(135deg, var(--ns-role-student), #BE123C);">
    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="font-serif text-3xl md:text-4xl font-normal text-white tracking-tight mb-2">
                Halo, <span class="font-bold"><?= htmlspecialchars($firstName) ?></span> 👋
            </h1>
            <p class="text-white/90 text-sm font-medium mb-1">
                <?= htmlspecialchars($student['class_name'] ?? 'Siswa') ?> • NIS <?= htmlspecialchars($student['nis'] ?? '—') ?>
            </p>
            <p class="text-white/70 text-xs">
                Semester <?= SEMESTER ?> · <?= ACADEMIC_YEAR ?>
            </p>
        </div>
        <div class="hidden md:block">
            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 text-white shadow-lg">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
            </div>
        </div>
    </div>
    <!-- Decorative elements -->
    <div class="absolute -right-10 -top-10 w-64 h-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
    <div class="absolute left-1/4 -bottom-20 w-40 h-40 rounded-full bg-black/10 blur-2xl pointer-events-none"></div>
</div>

<!-- Stats row: GPA + attendance -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- GPA arc ring -->
    <div class="ns-glass-panel ns-card-hover p-6 flex flex-col items-center justify-center relative overflow-hidden bg-white" data-animate="scale-in">
        <p class="text-xs font-bold tracking-widest uppercase mb-4 text-gray-500">
            Rata-rata Nilai
        </p>
        <div class="relative w-32 h-32">
            <svg width="128" height="128" viewBox="0 0 128 128" class="-rotate-90">
                <!-- Track -->
                <circle cx="64" cy="64" r="<?= $arcR ?>"
                        fill="none" stroke="#F3F4F6" stroke-width="8"/>
                <!-- Fill -->
                <circle cx="64" cy="64" r="<?= $arcR ?>"
                        fill="none"
                        stroke="var(--ns-role-student)"
                        stroke-width="8"
                        stroke-linecap="round"
                        stroke-dasharray="<?= $arcCirc ?>"
                        stroke-dashoffset="<?= $arcCirc ?>"
                        id="arc-fill"
                        style="transition:stroke-dashoffset 1.4s cubic-bezier(0.16,1,0.3,1);"
                        data-offset="<?= $arcOffset ?>"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="font-serif leading-none text-gray-900" style="font-size:2rem;letter-spacing:-1px;">
                    <?= $avgGrade !== null ? number_format($avgGrade, 1) : '—' ?>
                </span>
                <span class="text-[10px] text-gray-400 mt-1 font-medium">/ 100</span>
            </div>
        </div>
    </div>

    <!-- Hadir -->
    <div class="ns-glass-panel ns-card-hover p-6 flex flex-col justify-between" data-animate="scale-in" data-delay="1">
        <div>
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold tracking-widest uppercase text-gray-500">Hadir</p>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <p class="font-serif leading-none mb-1 text-gray-900" style="font-size:2.5rem;letter-spacing:-1px;">
                <span data-count="<?= $summary['hadir'] ?>">0</span>
            </p>
            <p class="text-sm text-gray-500 font-medium mb-4">hari (<?= date('Y') ?>)</p>
        </div>
        <div>
            <div class="ns-score-bar">
                <div class="ns-score-fill bg-emerald-500" data-progress="<?= $hadirPct ?>%" style="width:0;"></div>
            </div>
            <p class="text-xs mt-2 font-mono text-emerald-600 font-medium"><?= $hadirPct ?>%</p>
        </div>
    </div>

    <!-- Izin + Sakit -->
    <div class="ns-glass-panel ns-card-hover p-6 flex flex-col justify-between" data-animate="scale-in" data-delay="2">
        <div>
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold tracking-widest uppercase text-gray-500">Izin/Sakit</p>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="font-serif leading-none mb-1 text-gray-900" style="font-size:2.5rem;letter-spacing:-1px;">
                <span data-count="<?= $summary['izin'] + $summary['sakit'] ?>">0</span>
            </p>
            <p class="text-sm text-gray-500 font-medium mb-4">hari (<?= date('Y') ?>)</p>
        </div>
        <?php $izinPct = $total > 0 ? round((($summary['izin'] + $summary['sakit']) / $total) * 100) : 0; ?>
        <div>
            <div class="ns-score-bar">
                <div class="ns-score-fill bg-amber-500" data-progress="<?= $izinPct ?>%" style="width:0;"></div>
            </div>
            <p class="text-xs mt-2 font-mono text-amber-600 font-medium"><?= $izinPct ?>%</p>
        </div>
    </div>

    <!-- Alpha -->
    <div class="ns-glass-panel ns-card-hover p-6 flex flex-col justify-between" data-animate="scale-in" data-delay="3">
        <div>
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold tracking-widest uppercase text-gray-500">Alpha</p>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            </div>
            <p class="font-serif leading-none mb-1 text-gray-900" style="font-size:2.5rem;letter-spacing:-1px;">
                <span data-count="<?= $summary['alpha'] ?>">0</span>
            </p>
            <p class="text-sm text-gray-500 font-medium mb-4">hari (<?= date('Y') ?>)</p>
        </div>
        <?php $alphaPct = $total > 0 ? round(($summary['alpha'] / $total) * 100) : 0; ?>
        <div>
            <div class="ns-score-bar">
                <div class="ns-score-fill bg-rose-500" data-progress="<?= $alphaPct ?>%" style="width:0;"></div>
            </div>
            <p class="text-xs mt-2 font-mono text-rose-600 font-medium"><?= $alphaPct ?>%</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    <!-- Announcements -->
    <div class="ns-glass-panel lg:col-span-2 overflow-hidden" data-animate="fade-up">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900">Pengumuman Terbaru</h2>
            <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </div>
        </div>
        <?php if (empty($announcements)): ?>
        <div class="px-6 py-12 text-center text-sm text-gray-500">
            Belum ada pengumuman.
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($announcements as $a): ?>
            <div class="px-6 py-5 hover:bg-gray-50/50 transition-colors border-l-4 border-transparent hover:border-blue-500">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-sm font-bold text-gray-900">
                        <?= htmlspecialchars($a['title']) ?>
                    </p>
                    <span class="text-xs text-gray-400 font-medium">
                        <?= date('d M Y', strtotime($a['created_at'])) ?>
                    </span>
                </div>
                <p class="text-sm text-gray-500 leading-relaxed line-clamp-2">
                    <?= htmlspecialchars($a['content']) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick access -->
    <div class="ns-glass-panel overflow-hidden" data-animate="fade-up" data-delay="1">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900">Akses Cepat</h2>
        </div>
        <div class="p-5 space-y-3">
            <?php
            $menu = [
                [BASE_URL.'/pages/student/grades.php',     'Nilai Saya',       'text-rose-600', 'bg-rose-50', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                [BASE_URL.'/pages/student/attendance.php', 'Rekap Kehadiran',  'text-emerald-600', 'bg-emerald-50', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                [BASE_URL.'/pages/student/profile.php',    'Profil Saya',      'text-blue-600', 'bg-blue-50', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ];
            foreach ($menu as [$url, $label, $fg, $bg, $path]):
            ?>
            <a href="<?= $url ?>"
               class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all bg-white group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg <?= $bg ?> <?= $fg ?> flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $path ?>"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-gray-900"><?= $label ?></span>
                </div>
                <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
/* Animate GPA arc on load */
(function () {
    var arc = document.getElementById('arc-fill');
    if (!arc) return;
    var offset = parseFloat(arc.dataset.offset);
    requestAnimationFrame(function () {
        requestAnimationFrame(function () { arc.style.strokeDashoffset = offset; });
    });
})();
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
