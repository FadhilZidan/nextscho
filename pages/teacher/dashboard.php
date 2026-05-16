<?php
define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Teacher.php';
require_once APP_ROOT . '/models/Subject.php';
require_once APP_ROOT . '/models/Announcement.php';

requireRole('guru');

$user          = currentUser();
$teacher       = Teacher::getByUserId((int) $user['id']);
$subjects      = $teacher ? Subject::getByTeacher($teacher['id']) : [];
$announcements = Announcement::getLatest(5);

$classCount = count(array_unique(array_filter(array_column($subjects, 'class_id'))));

$pageTitle  = 'Dashboard';
$breadcrumb = [['label' => 'Dashboard']];
require_once APP_ROOT . '/includes/header.php';

$firstName = explode(' ', $user['name'])[0];
?>

<!-- Greeting Banner -->
<div class="ns-glass-panel p-8 mb-8 relative overflow-hidden" data-animate="fade-up" style="border: none; background: linear-gradient(135deg, var(--ns-role-teacher), #047857);">
    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="font-serif text-3xl md:text-4xl font-normal text-white tracking-tight mb-2">
                Halo, <span class="font-bold"><?= htmlspecialchars($firstName) ?></span> 👋
            </h1>
            <p class="text-white/90 text-sm font-medium mb-1">
                NIP <?= htmlspecialchars($teacher['nip'] ?? '—') ?>
            </p>
            <p class="text-white/70 text-xs">
                Semester <?= SEMESTER ?> · <?= ACADEMIC_YEAR ?>
            </p>
        </div>
        <div class="hidden md:block">
            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 text-white shadow-lg">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
        </div>
    </div>
    <!-- Decorative elements -->
    <div class="absolute -right-10 -top-10 w-64 h-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
    <div class="absolute left-1/4 -bottom-20 w-40 h-40 rounded-full bg-black/10 blur-2xl pointer-events-none"></div>
</div>

<!-- Stat cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="ns-glass-panel ns-card-hover p-6 relative overflow-hidden" data-animate="scale-in">
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold tracking-widest uppercase text-gray-500">Mata Pelajaran</p>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
            </div>
            <p class="font-serif leading-none mb-1 text-gray-900" style="font-size:2.5rem;letter-spacing:-1px;">
                <span data-count="<?= count($subjects) ?>">0</span>
            </p>
            <p class="text-sm text-gray-500 font-medium">diampu</p>
        </div>
    </div>
    <div class="ns-glass-panel ns-card-hover p-6 relative overflow-hidden" data-animate="scale-in" data-delay="1">
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold tracking-widest uppercase text-gray-500">Kelas Diampu</p>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <p class="font-serif leading-none mb-1 text-gray-900" style="font-size:2.5rem;letter-spacing:-1px;">
                <span data-count="<?= $classCount ?>">0</span>
            </p>
            <p class="text-sm text-gray-500 font-medium">kelas</p>
        </div>
    </div>
    <div class="ns-glass-panel ns-card-hover p-6 relative overflow-hidden" data-animate="scale-in" data-delay="2">
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold tracking-widest uppercase text-gray-500">Pengumuman</p>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                </div>
            </div>
            <p class="font-serif leading-none mb-1 text-gray-900" style="font-size:2.5rem;letter-spacing:-1px;">
                <span data-count="<?= count($announcements) ?>">0</span>
            </p>
            <p class="text-sm text-gray-500 font-medium">terbaru</p>
        </div>
    </div>
</div>

<!-- Quick access chips -->
<div class="flex flex-wrap gap-3 mb-8" data-animate="fade-up" data-delay="2">
    <a href="<?= BASE_URL ?>/pages/teacher/grades/index.php"
       class="ns-btn ns-btn-primary bg-emerald-600 hover:bg-emerald-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Input Nilai
    </a>
    <a href="<?= BASE_URL ?>/pages/teacher/attendance/index.php"
       class="ns-btn ns-btn-primary bg-emerald-600 hover:bg-emerald-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        Input Absensi
    </a>
    <a href="<?= BASE_URL ?>/pages/teacher/profile.php"
       class="ns-btn ns-btn-outline bg-white border-gray-200 text-gray-700 hover:bg-gray-50">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        Profil Saya
    </a>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    <!-- Subjects & classes -->
    <div class="ns-glass-panel lg:col-span-2 overflow-hidden" data-animate="fade-up">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900">Kelas &amp; Mata Pelajaran</h2>
            <span class="px-3 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100">
                <?= count($subjects) ?> mapel
            </span>
        </div>

        <?php if (empty($subjects)): ?>
        <div class="px-6 py-12 text-center text-sm text-gray-500">
            Belum ada mata pelajaran yang ditugaskan. Hubungi admin.
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($subjects as $s): ?>
            <div class="flex items-center justify-between gap-4 px-6 py-5 hover:bg-gray-50/50 transition-colors">
                <div>
                    <p class="text-sm font-bold text-gray-900 mb-1">
                        <?= htmlspecialchars($s['name']) ?>
                    </p>
                    <div class="flex items-center gap-2">
                        <?php if ($s['class_name']): ?>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-md bg-emerald-50 text-emerald-600">
                            <?= htmlspecialchars($s['class_name']) ?>
                        </span>
                        <?php endif; ?>
                        <span class="text-xs font-mono text-gray-400 font-medium">
                            <?= htmlspecialchars($s['code']) ?>
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="<?= BASE_URL ?>/pages/teacher/grades/input.php?subject_id=<?= $s['id'] ?>"
                       class="px-4 py-2 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors">
                        Nilai
                    </a>
                    <?php if ($s['class_id']): ?>
                    <a href="<?= BASE_URL ?>/pages/teacher/attendance/index.php?class_id=<?= $s['class_id'] ?>"
                       class="px-4 py-2 text-xs font-bold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                        Absensi
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Announcements -->
    <div class="ns-glass-panel overflow-hidden" data-animate="fade-up" data-delay="1">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900">Pengumuman</h2>
        </div>
        <?php if (empty($announcements)): ?>
        <div class="px-6 py-12 text-center text-sm text-gray-500">
            Belum ada pengumuman.
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($announcements as $a): ?>
            <div class="px-6 py-5 hover:bg-gray-50/50 transition-colors">
                <p class="text-sm font-bold text-gray-900 mb-1">
                    <?= htmlspecialchars($a['title']) ?>
                </p>
                <p class="text-sm text-gray-500 line-clamp-2 leading-relaxed mb-2">
                    <?= htmlspecialchars($a['content']) ?>
                </p>
                <p class="text-xs font-medium text-gray-400">
                    <?= date('d M Y', strtotime($a['created_at'])) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
