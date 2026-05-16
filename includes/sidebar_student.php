<?php
$cp = $_SERVER['SCRIPT_NAME'];
function navActiveS(string $path): string {
    global $cp;
    return str_contains($cp, $path) ? 'ns-nav-active' : 'ns-nav-item';
}
global $user;
$nameParts = array_slice(explode(' ', $user['name']), 0, 2);
$initials  = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), $nameParts));
?>
<style>
:root { --ns-role-primary: #E11D48; --ns-role-accent: #F43F5E; }
</style>
<aside id="app-sidebar"
       class="ns-sidebar-shell group/sidebar flex min-h-screen w-[260px] flex-shrink-0 flex-col overflow-x-hidden fixed inset-y-0 left-0 z-40 lg:relative lg:inset-auto lg:z-auto lg:sticky lg:top-0 lg:min-h-0 lg:h-screen lg:max-h-screen lg:w-64">

    <!-- Brand -->
    <div class="relative flex h-16 items-center gap-3 border-b border-mist px-6">
        <img src="<?= BASE_URL ?>/assets/images/logo.png"
             alt="Logo <?= htmlspecialchars(SCHOOL_SHORT, ENT_QUOTES) ?>"
             class="size-10 flex-shrink-0 rounded-full object-cover shadow-sm">
        <div class="min-w-0 flex-1 leading-tight">
            <p class="truncate font-serif text-[16px] font-semibold tracking-tight text-ink" title="<?= htmlspecialchars(SCHOOL_NAME, ENT_QUOTES) ?>">
                <?= htmlspecialchars(SCHOOL_SHORT) ?>
            </p>
            <p class="text-[11px] font-medium uppercase tracking-[0.12em] text-zinc-400">Siswa&nbsp;· <?= htmlspecialchars(ACADEMIC_YEAR) ?></p>
        </div>
        <button type="button" id="ns-sidebar-close"
                onclick="if(window.nsSidebarClose)window.nsSidebarClose()"
                class="lg:hidden flex size-8 flex-shrink-0 items-center justify-center rounded-lg text-zinc-400 hover:bg-mist hover:text-ink transition-colors"
                aria-label="Tutup menu">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-4 py-6 overflow-y-auto space-y-1">

        <p class="px-2 mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase">Menu</p>

        <a href="<?= BASE_URL ?>/pages/student/dashboard.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActiveS('/student/dashboard') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <a href="<?= BASE_URL ?>/pages/student/grades.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActiveS('/student/grades') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Nilai Saya
        </a>

        <a href="<?= BASE_URL ?>/pages/student/attendance.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActiveS('/student/attendance') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Kehadiran
        </a>

        <a href="<?= BASE_URL ?>/pages/student/academic_calendar.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActiveS('/student/academic_calendar') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Kalender Akademik
        </a>

        <a href="<?= BASE_URL ?>/pages/student/profile.php"
           class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition-colors <?= navActiveS('/student/profile') ?>">
            <svg class="ns-nav-link-icon size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Profil
        </a>

    </nav>

    <!-- User card -->
    <a href="<?= BASE_URL ?>/pages/student/profile.php"
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
            <p class="truncate text-[11px] text-zinc-400">Siswa</p>
        </div>
        <svg class="size-3.5 shrink-0 stroke-zinc-400 transition-colors group-hover/sidefoot:stroke-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

</aside>
