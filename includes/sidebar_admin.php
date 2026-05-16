<?php
$cp = $_SERVER['SCRIPT_NAME'];
function navActive(string $path): string {
    global $cp;
    return str_contains($cp, $path) ? 'ns-nav-active' : 'ns-nav-item';
}
global $user;
$nameParts = array_slice(explode(' ', $user['name']), 0, 2);
$initials  = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), $nameParts));
?>
<style>
:root { --ns-role-primary: #2563EB; --ns-role-accent: #3B82F6; }
</style>
<aside id="app-sidebar"
       class="ns-sidebar-shell group/sidebar flex min-h-screen w-[260px] flex-shrink-0 flex-col overflow-x-hidden fixed inset-y-0 left-0 z-40 lg:relative lg:inset-auto lg:z-auto lg:sticky lg:top-0 lg:min-h-0 lg:h-screen lg:max-h-screen lg:w-64">

    <!-- Brand -->
    <div class="relative flex h-16 items-center gap-3 border-b border-mist px-6">
        <img src="<?= BASE_URL ?>/assets/images/logo.png"
             alt="Logo <?= htmlspecialchars(SCHOOL_SHORT, ENT_QUOTES) ?>"
             class="size-10 flex-shrink-0 rounded-full object-cover shadow-sm">
        <div class="min-w-0 leading-tight">
            <p class="truncate font-serif text-[16px] font-semibold tracking-tight text-ink" title="<?= htmlspecialchars(SCHOOL_NAME, ENT_QUOTES) ?>">
                <?= htmlspecialchars(SCHOOL_SHORT) ?>
            </p>
            <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-zinc-400">Admin&nbsp;· <?= htmlspecialchars(ACADEMIC_YEAR) ?></p>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-4 py-6 overflow-y-auto space-y-1">

        <p class="px-2 mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase">Menu</p>

        <a href="<?= BASE_URL ?>/pages/admin/dashboard.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActive('/admin/dashboard') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <a href="<?= BASE_URL ?>/pages/admin/students/index.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActive('/students') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Siswa
        </a>

        <a href="<?= BASE_URL ?>/pages/admin/teachers/index.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActive('/teachers') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Guru
        </a>

        <a href="<?= BASE_URL ?>/pages/admin/classes/index.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActive('/classes') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Kelas
        </a>

        <a href="<?= BASE_URL ?>/pages/admin/subjects/index.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActive('/subjects') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            Mata Pelajaran
        </a>

        <p class="px-2 mt-6 mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase">Konten</p>

        <a href="<?= BASE_URL ?>/pages/admin/academic_calendar/index.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActive('/academic_calendar') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Kalender Akademik
        </a>

        <a href="<?= BASE_URL ?>/pages/admin/announcements/index.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActive('/announcements') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            Pengumuman
        </a>

        <p class="px-2 mt-6 mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase">Laporan</p>

        <a href="<?= BASE_URL ?>/pages/admin/reports/grades_report.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActive('/reports/grades') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Laporan Nilai
        </a>

        <a href="<?= BASE_URL ?>/pages/admin/reports/attendance_report.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActive('/reports/attendance') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Laporan Absensi
        </a>

    </nav>

    <!-- User card -->
    <a href="<?= BASE_URL ?>/pages/admin/profile.php"
       class="group/sidefoot mt-auto flex items-center gap-3 border-t border-mist px-5 py-4 hover:bg-gray-50/50 transition-colors hover:no-underline"
       aria-label="Buka profil">
        <div class="relative flex-shrink-0">
            <div class="ns-avatar h-10 w-10 text-[12px]" style="background:var(--ns-role-primary);">
                <?= htmlspecialchars($initials) ?>
            </div>
            <span class="ns-status-dot absolute -bottom-0.5 -right-0.5 ring-2 ring-white"></span>
        </div>
        <div class="min-w-0 flex-1 leading-tight">
            <p class="truncate text-xs font-semibold text-ink"><?= htmlspecialchars($user['name']) ?></p>
            <p class="truncate text-[11px] text-zinc-400">Administrator</p>
        </div>
        <svg class="size-3.5 shrink-0 stroke-zinc-400 transition-colors group-hover/sidefoot:stroke-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

</aside>
