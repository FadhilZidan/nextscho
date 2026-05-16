<?php
define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Student.php';
require_once APP_ROOT . '/models/ClassRoom.php';

requireRole('admin');

$totalSiswa = Student::totalCount();
$totalKelas = ClassRoom::totalCount();
try { $totalGuru     = (int) getDB()->query('SELECT COUNT(*) FROM teachers')->fetchColumn(); } catch (Exception $e) { $totalGuru = 0; }
try { $totalSubjects = (int) getDB()->query('SELECT COUNT(*) FROM subjects')->fetchColumn(); } catch (Exception $e) { $totalSubjects = 0; }

$pageTitle  = 'Dashboard';
$breadcrumb = [['label' => 'Dashboard']];
require_once APP_ROOT . '/includes/header.php';

$firstName = explode(' ', $user['name'])[0];
?>

<!-- Greeting Banner -->
<div class="ns-glass-panel p-8 mb-8 relative overflow-hidden" data-animate="fade-up" style="border: none; background: linear-gradient(135deg, var(--ns-role-admin), #1D4ED8);">
    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="font-serif text-3xl md:text-4xl font-normal text-white tracking-tight mb-2">
                Selamat datang, <span class="font-bold"><?= htmlspecialchars($firstName) ?></span> 👋
            </h1>
            <p class="text-white/90 text-sm font-medium mb-1">
                Administrator Sistem
            </p>
            <p class="text-white/70 text-xs">
                <?= date('d M Y') ?> · Ringkasan data sekolah hari ini
            </p>
        </div>
        <div class="hidden md:block">
            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 text-white shadow-lg">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
        </div>
    </div>
    <!-- Decorative elements -->
    <div class="absolute -right-10 -top-10 w-64 h-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
    <div class="absolute left-1/4 -bottom-20 w-40 h-40 rounded-full bg-black/10 blur-2xl pointer-events-none"></div>
</div>

<!-- ── Bento stat grid ── -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">

    <!-- Hero: Total Siswa -->
    <div class="ns-glass-panel ns-card-hover p-6 col-span-1 lg:col-span-2 relative overflow-hidden bg-white" data-animate="scale-in">
        <div class="relative z-10 flex flex-col justify-between h-full">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold tracking-widest uppercase text-gray-500">Total Siswa</p>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
            <div class="flex items-end justify-between gap-4 mt-auto">
                <div>
                    <p class="font-serif leading-none mb-2 text-gray-900" style="font-size:3.5rem;letter-spacing:-1px;">
                        <span data-count="<?= $totalSiswa ?>">0</span>
                    </p>
                    <p class="text-sm text-gray-500 font-medium">siswa terdaftar di sistem</p>
                </div>
                <!-- Inline mini bar chart -->
                <svg width="80" height="40" viewBox="0 0 80 40" class="opacity-80">
                    <?php
                    $bars   = [22, 30, 28, 35, 32, 40, 38];
                    $barW   = 8;
                    $gap    = 4;
                    $maxVal = max($bars);
                    foreach ($bars as $i => $v):
                        $h = round(($v / $maxVal) * 36);
                        $x = $i * ($barW + $gap);
                        $y = 40 - $h;
                    ?>
                    <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $barW ?>" height="<?= $h ?>"
                          rx="3" fill="#E5E7EB"/>
                    <?php endforeach; ?>
                    <rect x="<?= (count($bars)-1)*($barW+$gap) ?>" y="<?= 40 - round((end($bars)/$maxVal)*36) ?>"
                          width="<?= $barW ?>" height="<?= round((end($bars)/$maxVal)*36) ?>"
                          rx="3" fill="var(--ns-role-admin)"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Guru KPI -->
    <div class="ns-glass-panel ns-card-hover p-6 flex flex-col justify-between" data-animate="scale-in" data-delay="1">
        <div>
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold tracking-widest uppercase text-gray-500">Guru</p>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
            </div>
            <p class="font-serif leading-none mb-1 text-gray-900" style="font-size:2.5rem;letter-spacing:-1px;">
                <span data-count="<?= $totalGuru ?>">0</span>
            </p>
            <p class="text-sm text-gray-500 font-medium">pengajar aktif</p>
        </div>
        <div class="ns-score-bar mt-4">
            <div class="ns-score-fill bg-emerald-500" data-progress="<?= $totalGuru > 0 ? min(100, round($totalGuru/50*100)) : 0 ?>%" style="width:0;"></div>
        </div>
    </div>

    <!-- Kelas KPI -->
    <div class="ns-glass-panel ns-card-hover p-6 flex flex-col justify-between" data-animate="scale-in" data-delay="2">
        <div>
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold tracking-widest uppercase text-gray-500">Kelas</p>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <p class="font-serif leading-none mb-1 text-gray-900" style="font-size:2.5rem;letter-spacing:-1px;">
                <span data-count="<?= $totalKelas ?>">0</span>
            </p>
            <p class="text-sm text-gray-500 font-medium">ruang belajar</p>
        </div>
        <div class="ns-score-bar mt-4">
            <div class="ns-score-fill bg-amber-500" data-progress="<?= $totalKelas > 0 ? min(100, round($totalKelas/20*100)) : 0 ?>%" style="width:0;"></div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- Mata Pelajaran KPI -->
    <div class="ns-glass-panel ns-card-hover p-6 flex flex-col justify-between col-span-1" data-animate="scale-in" data-delay="3">
        <div>
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold tracking-widest uppercase text-gray-500">Mata Pelajaran</p>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
            </div>
            <p class="font-serif leading-none mb-1 text-gray-900" style="font-size:2.5rem;letter-spacing:-1px;">
                <span data-count="<?= $totalSubjects ?>">0</span>
            </p>
            <p class="text-sm text-gray-500 font-medium">kurikulum terdaftar</p>
        </div>
        <div class="ns-score-bar mt-4">
            <div class="ns-score-fill bg-blue-600" data-progress="<?= $totalSubjects > 0 ? min(100, round($totalSubjects/30*100)) : 0 ?>%" style="width:0;"></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="ns-glass-panel p-6 col-span-1 lg:col-span-2" data-animate="fade-up" data-delay="2">
        <p class="text-xs font-bold tracking-widest uppercase text-gray-500 mb-5">Akses Cepat</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php
            $actions = [
                [BASE_URL.'/pages/admin/students/index.php',  'Data Siswa',    'text-blue-600', 'bg-blue-50/80',
                 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                [BASE_URL.'/pages/admin/students/create.php', 'Tambah Siswa',  'text-emerald-600', 'bg-emerald-50/80',
                 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                [BASE_URL.'/pages/admin/teachers/index.php',  'Data Guru',     'text-amber-600', 'bg-amber-50/80',
                 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                [BASE_URL.'/pages/admin/classes/index.php',   'Data Kelas',    'text-indigo-600', 'bg-indigo-50/80',
                 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ];
            foreach ($actions as [$url, $label, $fg, $bg, $path]):
            ?>
            <a href="<?= $url ?>" class="ns-action-card <?= $bg ?>">
                <div class="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center flex-shrink-0 <?= $fg ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= $path ?>"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-gray-900"><?= $label ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Reports -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6" data-animate="fade-up" data-delay="3">
    <a href="<?= BASE_URL ?>/pages/admin/reports/grades_report.php"
       class="ns-glass-panel p-6 ns-card-hover flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div>
            <p class="text-base font-semibold text-gray-900">Laporan Nilai</p>
            <p class="text-sm text-gray-500 mt-1">Rekap nilai akademik per kelas &amp; semester dengan grafik analitik.</p>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/pages/admin/reports/attendance_report.php"
       class="ns-glass-panel p-6 ns-card-hover flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </div>
        <div>
            <p class="text-base font-semibold text-gray-900">Laporan Absensi</p>
            <p class="text-sm text-gray-500 mt-1">Rekap kehadiran bulanan seluruh siswa untuk monitoring kedisiplinan.</p>
        </div>
    </a>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
