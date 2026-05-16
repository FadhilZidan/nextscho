<?php
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/User.php';

if (isLoggedIn()) {
    $map = [
        'admin' => BASE_URL . '/pages/admin/dashboard.php',
        'guru'  => BASE_URL . '/pages/teacher/dashboard.php',
        'siswa' => BASE_URL . '/pages/student/dashboard.php',
    ];
    header('Location: ' . ($map[$_SESSION['role']] ?? '/'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $user = User::findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];

            $map = [
                'admin' => BASE_URL . '/pages/admin/dashboard.php',
                'guru'  => BASE_URL . '/pages/teacher/dashboard.php',
                'siswa' => BASE_URL . '/pages/student/dashboard.php',
            ];
            header('Location: ' . ($map[$user['role']] ?? BASE_URL . '/auth/login.php'));
            exit;
        }
        $error = 'Email atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — <?= SCHOOL_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,600;1,9..144,300;1,9..144,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', system-ui, sans-serif; }

        /* Role CSS variable */
        :root { --rp: #3730A3; }

        /* Geo drift animations */
        @keyframes geo-drift  { 0%{transform:translateY(0)} 100%{transform:translateY(-30px)} }
        @keyframes geo-drift2 { 0%{transform:translateY(0)} 100%{transform:translateY(30px)}  }
        .geo1 { animation: geo-drift  22s ease-in-out infinite alternate; }
        .geo2 { animation: geo-drift2 28s ease-in-out infinite alternate-reverse; }

        /* Shake on error */
        @keyframes ns-shake {
            10%,90%     { transform: translateX(-1px); }
            20%,80%     { transform: translateX(2px);  }
            30%,50%,70% { transform: translateX(-3px); }
            40%,60%     { transform: translateX(3px);  }
        }
        .ns-shake { animation: ns-shake 0.4s ease-in-out; }

        /* Bottom-border input */
        .ns-input-line {
            border: 0; border-bottom: 2px solid #E8E6DF;
            background: transparent; outline: none;
            padding: 10px 0; font-size: 14px; width: 100%;
            font-family: 'Inter', sans-serif; color: #0A0E1A;
            transition: border-color 0.2s ease;
        }
        .ns-input-line::placeholder { color: #9CA3AF; }
        .ns-input-line:focus { border-color: var(--rp); }

        /* Role selector */
        .role-chip {
            flex: 1; padding: 8px 12px; border-radius: 10px;
            font-size: 12px; font-weight: 600; border: none; cursor: pointer;
            transition: background 0.15s, color 0.15s, box-shadow 0.15s;
            background: transparent; color: #9CA3AF;
        }
        .role-chip.active {
            background: #fff; color: #0A0E1A;
            box-shadow: 0 1px 6px rgba(0,0,0,.12);
        }
    </style>
</head>
<body style="display:flex;min-height:100vh;background:#FAFAF7;">

<!-- ══ LEFT PANEL — Ink 60% ══════════════════════════════════════ -->
<div style="width:60%;background:#0A0E1A;position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden;"
     class="hidden lg:flex">

    <!-- Animated geometry -->
    <svg style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;"
         viewBox="0 0 700 900" preserveAspectRatio="xMidYMid slice">
        <g class="geo1" stroke="rgba(255,255,255,.05)" fill="none" stroke-width="1">
            <line x1="0"   y1="260" x2="700" y2="480"/>
            <line x1="130" y1="0"   x2="290" y2="900"/>
            <circle cx="360" cy="460" r="230"/>
            <polygon points="350,80 680,660 20,660"/>
        </g>
        <g class="geo2" stroke="rgba(255,255,255,.03)" fill="none" stroke-width="1">
            <line x1="610" y1="0"   x2="80"  y2="900"/>
            <line x1="0"   y1="580" x2="700" y2="260"/>
            <circle cx="350" cy="460" r="340"/>
            <rect x="180" y="180" width="340" height="540" rx="4"/>
        </g>
    </svg>

    <!-- Content -->
    <div style="position:relative;z-index:10;max-width:400px;padding:0 64px;color:#FAFAF7;user-select:none;">
        <!-- Wordmark -->
        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:56px;flex-wrap:wrap;">
            <img src="<?= BASE_URL ?>/assets/images/logo.png"
                 alt="Logo <?= htmlspecialchars(SCHOOL_SHORT, ENT_QUOTES) ?>"
                 style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;">
            <div style="min-width:0;">
                <span style="font-size:18px;font-weight:600;letter-spacing:-0.03em;display:block;color:#FAFAF7;">
                    <?= htmlspecialchars(SCHOOL_SHORT) ?>
                </span>
                <span style="margin-top:4px;font-size:11px;display:block;line-height:1.5;color:rgba(250,250,247,.52);letter-spacing:.02em;">
                    <?= htmlspecialchars(SCHOOL_NAME) ?>
                </span>
            </div>
        </div>

        <!-- Fraunces italic quote -->
        <p style="margin:0 0 14px;font-size:11px;font-weight:600;letter-spacing:0.12em;color:rgba(250,250,247,.40);text-transform:uppercase;">
            NextScho — portal akademik
        </p>
        <blockquote style="font-family:'Fraunces',Georgia,serif;font-style:italic;font-weight:300;
                           font-size:28px;line-height:1.35;margin:0 0 24px;letter-spacing:-.3px;">
            "Mendidik adalah seni memberi cahaya kepada pikiran yang haus."
        </blockquote>
        <p style="font-size:13px;color:rgba(250,250,247,.45);margin:0 0 48px;line-height:1.6;">
            Sistem informasi sekolah yang modern — nilai, absensi, dan pengumuman dalam satu tempat.
        </p>

        <!-- Feature tags -->
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach (['Absensi', 'Nilai', 'Pengumuman', 'Laporan'] as $feat): ?>
            <span style="display:inline-flex;align-items:center;gap:6px;
                         background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
                         border-radius:999px;padding:4px 12px;font-size:11px;font-weight:500;">
                <svg width="10" height="10" viewBox="0 0 20 20" fill="rgba(16,185,129,.9)">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <?= $feat ?>
            </span>
            <?php endforeach; ?>
        </div>

        <p style="margin-top:56px;padding-top:24px;border-top:1px solid rgba(255,255,255,.08);
                  font-size:11px;color:rgba(250,250,247,.3);">
            &copy; <?= date('Y') ?> NextScho &middot; <?= htmlspecialchars(SCHOOL_NAME) ?>
        </p>
    </div>
</div>

<!-- ══ RIGHT PANEL — Paper 40% ═══════════════════════════════════ -->
<div style="flex:1;display:flex;align-items:center;justify-content:center;background:#FAFAF7;min-height:100vh;" class="ns-login-panel">
    <div style="width:100%;max-width:340px;">

        <!-- Mobile wordmark -->
        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:32px;flex-wrap:wrap;" class="lg:hidden">
            <img src="<?= BASE_URL ?>/assets/images/logo.png"
                 alt="Logo <?= htmlspecialchars(SCHOOL_SHORT, ENT_QUOTES) ?>"
                 style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
            <div style="min-width:0;">
                <span style="font-size:17px;font-weight:700;display:block;color:#0A0E1A;line-height:1.25;letter-spacing:-0.03em;">
                    <?= htmlspecialchars(SCHOOL_SHORT) ?>
                </span>
                <span style="margin-top:2px;display:block;font-size:11px;color:#9CA3AF;line-height:1.35;">
                    <?= htmlspecialchars(SCHOOL_NAME) ?>
                </span>
            </div>
        </div>

        <!-- Heading -->
        <div style="margin-bottom:28px;">
            <h1 style="font-family:'Fraunces',Georgia,serif;font-weight:300;font-size:26px;
                       color:#0A0E1A;margin:0 0 6px;letter-spacing:-.4px;">
                Selamat datang kembali.
            </h1>
            <p style="font-size:13px;color:#9CA3AF;margin:0;">Masuk ke akun Anda untuk melanjutkan.</p>
        </div>

        <!-- Role selector -->
        <div style="margin-bottom:24px;">
            <p style="font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;
                      color:#9CA3AF;margin:0 0 8px;">Masuk sebagai</p>
            <div style="display:flex;gap:4px;background:#F1F0EB;border-radius:14px;padding:4px;" id="role-tabs">
                <?php
                $roles = [
                    'admin' => ['Admin',   '#3730A3'],
                    'guru'  => ['Guru',    '#047857'],
                    'siswa' => ['Siswa',   '#DC2626'],
                ];
                foreach ($roles as $key => [$label, $color]):
                ?>
                <button type="button"
                        class="role-chip <?= $key === 'admin' ? 'active' : '' ?>"
                        data-role="<?= $key ?>"
                        data-color="<?= $color ?>"
                        onclick="pickRole('<?= $key ?>')">
                    <?= $label ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Error -->
        <?php if ($error): ?>
        <div id="err-box" style="display:flex;align-items:center;gap:8px;padding:10px 14px;margin-bottom:20px;
                                  border-radius:10px;border:1px solid #FECACA;background:#FEF2F2;
                                  font-size:13px;color:#991B1B;">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="#EF4444" style="flex-shrink:0;">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" id="login-form" novalidate>

            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:11px;font-weight:600;letter-spacing:.05em;
                              text-transform:uppercase;color:#9CA3AF;margin-bottom:2px;">Email</label>
                <input type="email" name="email" class="ns-input-line"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="nama@sekolah.ac.id"
                       required autocomplete="email">
            </div>

            <div style="margin-bottom:28px;position:relative;">
                <label style="display:block;font-size:11px;font-weight:600;letter-spacing:.05em;
                              text-transform:uppercase;color:#9CA3AF;margin-bottom:2px;">Password</label>
                <input type="password" name="password" id="pwd-field" class="ns-input-line"
                       style="padding-right:32px;"
                       placeholder="••••••••"
                       required autocomplete="current-password">
                <button type="button" id="pwd-toggle"
                        style="position:absolute;right:0;bottom:10px;background:none;border:none;
                               cursor:pointer;color:#9CA3AF;padding:0;line-height:1;"
                        onclick="togglePwd()">
                    <svg id="eye-show" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg id="eye-hide" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>

            <button id="submit-btn" type="submit"
                    style="width:100%;padding:13px;border-radius:12px;border:none;cursor:pointer;
                           background:var(--rp);color:#fff;font-size:14px;font-weight:600;
                           font-family:'Inter',sans-serif;transition:opacity .15s,transform .1s;
                           display:flex;align-items:center;justify-content:center;gap:8px;"
                    onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'"
                    onmousedown="this.style.transform='scale(.99)'" onmouseup="this.style.transform='scale(1)'">
                <span id="btn-label">Masuk</span>
                <svg id="btn-spin" style="display:none;animation:spin .7s linear infinite;" width="16" height="16"
                     fill="none" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,.3)" stroke-width="4"/>
                    <path fill="white" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </button>

        </form>

        <p style="margin-top:32px;text-align:center;font-size:11px;color:#9CA3AF;">
            &copy; <?= date('Y') ?> NextScho &middot; <?= htmlspecialchars(SCHOOL_NAME) ?>
        </p>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
var roleCfg = {
    admin: '#3730A3',
    guru:  '#047857',
    siswa: '#DC2626',
};
var currentRole = 'admin';

function pickRole(role) {
    currentRole = role;
    var color = roleCfg[role];
    document.documentElement.style.setProperty('--rp', color);

    document.querySelectorAll('.role-chip').forEach(function (btn) {
        var active = btn.dataset.role === role;
        btn.classList.toggle('active', active);
    });
}

function togglePwd() {
    var f    = document.getElementById('pwd-field');
    var show = document.getElementById('eye-show');
    var hide = document.getElementById('eye-hide');
    var isPass = f.type === 'password';
    f.type = isPass ? 'text' : 'password';
    show.style.display = isPass ? 'none'  : '';
    hide.style.display = isPass ? ''      : 'none';
}

document.getElementById('login-form').addEventListener('submit', function (e) {
    var email = this.querySelector('[name="email"]').value.trim();
    var pwd   = this.querySelector('[name="password"]').value;
    if (!email || !pwd) {
        e.preventDefault();
        var form = this;
        form.classList.add('ns-shake');
        form.addEventListener('animationend', function () { form.classList.remove('ns-shake'); }, { once: true });
        return;
    }
    var btn    = document.getElementById('submit-btn');
    var label  = document.getElementById('btn-label');
    var spin   = document.getElementById('btn-spin');
    btn.disabled      = true;
    label.textContent = 'Memproses…';
    spin.style.display = '';
});

/* Init */
pickRole('admin');
<?php if ($error): ?>
(function () {
    var form = document.getElementById('login-form');
    form.classList.add('ns-shake');
    form.addEventListener('animationend', function () { form.classList.remove('ns-shake'); }, { once: true });
})();
<?php endif; ?>
</script>
</body>
</html>
