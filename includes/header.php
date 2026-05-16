<?php
$pageTitle  = $pageTitle  ?? 'Dashboard';
$breadcrumb = $breadcrumb ?? [];
$user       = currentUser();
$flash      = getFlash();
$roleColors = ['admin' => '#3730A3', 'guru' => '#047857', 'siswa' => '#DC2626'];
$avatarBg   = $roleColors[$user['role']] ?? '#3730A3';
$nameParts  = array_slice(explode(' ', $user['name']), 0, 2);
$initials   = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), $nameParts));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= SCHOOL_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans:  ['Inter', 'system-ui', 'sans-serif'],
                        serif: ['Fraunces', 'Georgia', 'serif'],
                        mono:  ['JetBrains Mono', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        ink:   '#111827',
                        paper: '#FFFFFF',
                        cloud: '#F3F4F6',
                        mist:  '#E5E7EB',
                        line:  '#F3F4F6',
                    },
                    boxShadow: {
                        lift: '0 10px 25px -3px rgba(0, 0, 0, 0.05)',
                        bar: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,600;1,9..144,300;1,9..144,400&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
</head>
<body class="antialiased bg-paper text-ink font-sans min-h-screen">

<div class="relative flex min-h-screen">

    <!-- Mobile drawer backdrop -->
    <div id="ns-sidebar-overlay"
         class="fixed inset-0 z-30 lg:hidden opacity-0 pointer-events-none bg-ink/20 backdrop-blur-[2px] transition-opacity duration-300 ease-out-expo"
         aria-hidden="true"></div>

    <!-- Sidebar (role-specific) -->
    <?php
    if ($user['role'] === 'admin')    require_once APP_ROOT . '/includes/sidebar_admin.php';
    elseif ($user['role'] === 'guru') require_once APP_ROOT . '/includes/sidebar_teacher.php';
    else                              require_once APP_ROOT . '/includes/sidebar_student.php';
    ?>

    <!-- Right column -->
    <div class="flex min-h-screen min-w-0 flex-1 flex-col">

        <!-- Top navbar -->
        <header class="sticky top-0 z-20 flex h-16 flex-shrink-0 items-center justify-between gap-4 ns-header-glass px-4 shadow-sm sm:px-6 lg:px-8">

            <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                <button type="button"
                        id="ns-sidebar-toggle"
                        class="flex size-10 flex-shrink-0 items-center justify-center rounded-xl border border-mist bg-cloud/70 text-zinc-500 transition-all hover:border-mist hover:bg-cloud hover:text-ink active:scale-[0.97] lg:hidden"
                        aria-label="Buka menu"
                        aria-expanded="false"
                        aria-controls="app-sidebar">
                    <svg id="ns-sidebar-toggle-icon" class="size-[18px] transition-transform duration-300 ease-out-expo" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <div class="hidden h-6 w-px flex-shrink-0 bg-mist sm:block lg:hidden"></div>

                <!-- Breadcrumbs -->
                <nav class="flex min-w-0 items-center gap-1 overflow-x-auto text-sm [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Breadcrumb">
                    <?php foreach ($breadcrumb as $i => $crumb): ?>
                        <?php if ($i > 0): ?>
                            <span class="mx-0.5 flex-shrink-0 text-zinc-300" aria-hidden="true">/</span>
                        <?php endif; ?>
                        <?php if (!empty($crumb['url'])): ?>
                            <a href="<?= htmlspecialchars($crumb['url']) ?>"
                               class="truncate font-medium text-zinc-400 transition-colors duration-200 hover:text-ink">
                                <?= htmlspecialchars($crumb['label']) ?>
                            </a>
                        <?php else: ?>
                            <span class="truncate font-semibold tracking-tight text-ink">
                                <?= htmlspecialchars($crumb['label']) ?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>

                <!-- School badge (compact, desktop+) -->
                <div class="ml-auto hidden min-w-0 items-center lg:flex">
                    <span class="truncate rounded-full border border-mist bg-cloud/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                        <?= htmlspecialchars(SCHOOL_SHORT) ?>
                    </span>
                </div>
            </div>

            <!-- Right cluster -->
            <div class="flex flex-shrink-0 items-center gap-1.5 sm:gap-2">

                <!-- Notifications bell -->
                <button type="button"
                        class="ns-notif-btn rounded-xl transition hover:shadow-sm hover:brightness-[1.03] active:scale-[0.96]"
                        data-tooltip="Notifikasi"
                        aria-label="Notifikasi">
                    <svg class="w-4 h-4 transition-transform hover:-rotate-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="ns-notif-dot"></span>
                </button>

                <!-- User identity + dropdown -->
                <div class="relative pl-1" id="user-menu-wrap">
                    <button type="button"
                            id="user-menu-btn"
                            class="flex items-center gap-2 rounded-xl px-2 py-1.5 text-left transition-colors duration-200 hover:bg-cloud [aria-expanded=true]:bg-cloud"
                            style="border:none;background:transparent;cursor:pointer;"
                            onclick="nsToggleMenu()"
                            aria-expanded="false"
                            aria-haspopup="true">
                        <div class="hidden text-right sm:block">
                            <p class="text-xs font-semibold leading-tight text-ink">
                                <?= htmlspecialchars($user['name']) ?>
                            </p>
                            <p class="text-xs capitalize text-zinc-400">
                                <?= $user['role'] === 'guru' ? 'Guru' : ($user['role'] === 'siswa' ? 'Siswa' : 'Admin') ?>
                            </p>
                        </div>
                        <div class="relative flex-shrink-0 ring-2 ring-white">
                            <div class="ns-avatar w-8 h-8 text-[11px]" style="background:<?= $avatarBg ?>;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <span class="ns-status-dot absolute -bottom-0.5 -right-0.5 shadow-sm"></span>
                        </div>
                        <svg class="user-menu-chevron w-3.5 h-3.5 flex-shrink-0 text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown -->
                    <div id="user-dropdown" class="ns-dropdown" style="display:none;" role="menu">
                        <?php
                        $profileUrl = BASE_URL . '/pages/' . ($user['role'] === 'admin' ? 'admin' : ($user['role'] === 'guru' ? 'teacher' : 'student')) . '/profile.php';
                        ?>
                        <a href="<?= $profileUrl ?>" class="ns-dropdown-item">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Profil Saya
                        </a>
                        <div class="ns-dropdown-sep"></div>
                        <a href="<?= BASE_URL ?>/auth/logout.php" class="ns-dropdown-item danger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Keluar
                        </a>
                    </div>
                </div>
            </div>

            <script>
            function nsToggleMenu() {
                var d = document.getElementById('user-dropdown');
                var btn = document.getElementById('user-menu-btn');
                if (!d || !btn) return;
                var open = d.style.display !== 'none';
                d.style.display = open ? 'none' : 'block';
                btn.setAttribute('aria-expanded', open ? 'false' : 'true');
                var ch = btn.querySelector('.user-menu-chevron');
                if (ch) ch.style.transform = open ? '' : 'rotate(180deg)';
            }
            document.addEventListener('click', function(e) {
                var wrap = document.getElementById('user-menu-wrap');
                if (wrap && !wrap.contains(e.target)) {
                    var d = document.getElementById('user-dropdown');
                    if (d) { d.style.display = 'none'; }
                    var btn = document.getElementById('user-menu-btn');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                        var ch = btn.querySelector('.user-menu-chevron');
                        if (ch) ch.style.transform = '';
                    }
                }
            });
            </script>
        </header>

        <!-- Flash message -->
        <?php if ($flash): ?>
        <div id="flash-msg" class="mx-4 sm:mx-6 mt-4 px-4 py-3 rounded-xl text-sm font-medium ns-toast"
             style="<?= $flash['type'] === 'success'
                 ? 'background:#F0FDF4;border-color:#6EE7B7;color:#065F46;'
                 : 'background:#FEF2F2;border-color:#FECACA;color:#991B1B;' ?>">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
        <?php endif; ?>

        <!-- Page content -->
        <main class="flex-1 p-4 sm:p-6">
