<?php
/**
 * NextScho — One-time setup & seeder
 * Jalankan sekali di browser: http://localhost/nextscho/setup.php
 * Hapus atau rename file ini setelah selesai.
 */

$envFile = __DIR__ . '/.env';
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v);
    }
}

$host   = $env['DB_HOST'] ?? 'localhost';
$dbName = $env['DB_NAME'] ?? 'school_sis';
$user   = $env['DB_USER'] ?? 'root';
$pass   = $env['DB_PASS'] ?? '';

$log = [];

try {
    // 1. Connect without selecting DB first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // 2. Run SQL schema
    $sql = file_get_contents(__DIR__ . '/database/school_sis.sql');
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '') $pdo->exec($stmt);
    }
    $log[] = '✅ Tabel berhasil dibuat.';

    // 3. Switch to DB
    $pdo->exec("USE `$dbName`");

    // 4. Seed users
    $users = [
        ['Admin Sekolah',   'admin@school.sch.id',   'admin123',  'admin'],
        ['Budi Santoso',    'budi@school.sch.id',    'guru123',   'guru'],
        ['Sari Dewi',       'sari@school.sch.id',    'guru123',   'guru'],
        ['Ahmad Fauzi',     'ahmad@school.sch.id',   'siswa123',  'siswa'],
        ['Rizki Pratama',   'rizki@school.sch.id',   'siswa123',  'siswa'],
        ['Dewi Rahayu',     'dewi@school.sch.id',    'siswa123',  'siswa'],
        ['Muhammad Ilham',  'ilham@school.sch.id',   'siswa123',  'siswa'],
        ['Siti Nurhaliza',  'siti@school.sch.id',    'siswa123',  'siswa'],
        ['Farhan Wijaya',   'farhan@school.sch.id',  'siswa123',  'siswa'],
        ['Lestari Putri',   'lestari@school.sch.id', 'siswa123',  'siswa'],
        ['Hendra Gunawan',  'hendra@school.sch.id',  'siswa123',  'siswa'],
    ];

    $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $stmtIns   = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
    $userIds   = [];

    foreach ($users as $u) {
        $stmtCheck->execute([$u[1]]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stmtIns->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3]]);
            $userIds[$u[1]] = (int) $pdo->lastInsertId();
        } else {
            $row = $pdo->query("SELECT id FROM users WHERE email = '{$u[1]}'")->fetch();
            $userIds[$u[1]] = (int) $row['id'];
        }
    }
    $log[] = '✅ Data user berhasil di-seed.';

    // 5. Seed teachers
    $teachers = [
        [$userIds['budi@school.sch.id'],  '199001012015011001', 'Budi Santoso',   'Matematika', '08111111111'],
        [$userIds['sari@school.sch.id'],  '199203152016012002', 'Sari Dewi',      'Bahasa Indonesia', '08222222222'],
    ];
    $stmtT = $pdo->prepare('INSERT IGNORE INTO teachers (user_id, nip, name, subjects, phone) VALUES (?, ?, ?, ?, ?)');
    $teacherIdMap = [];
    foreach ($teachers as $t) {
        $stmtT->execute($t);
        if ($pdo->lastInsertId()) {
            $teacherIdMap[$t[0]] = (int) $pdo->lastInsertId();
        } else {
            $row = $pdo->query("SELECT id FROM teachers WHERE user_id = {$t[0]}")->fetch();
            $teacherIdMap[$t[0]] = (int) ($row['id'] ?? 0);
        }
    }
    $log[] = '✅ Data guru berhasil di-seed.';

    // 6. Seed classes
    $classes = [
        ['VII-A',  'VII',  '2025/2026', $teacherIdMap[$userIds['budi@school.sch.id']] ?? null],
        ['VII-B',  'VII',  '2025/2026', $teacherIdMap[$userIds['sari@school.sch.id']] ?? null],
        ['VIII-A', 'VIII', '2025/2026', null],
        ['VIII-B', 'VIII', '2025/2026', null],
        ['IX-A',   'IX',   '2025/2026', null],
    ];
    $stmtCls = $pdo->prepare('INSERT IGNORE INTO classes (name, grade_level, academic_year, homeroom_teacher_id) VALUES (?, ?, ?, ?)');
    foreach ($classes as $c) $stmtCls->execute($c);
    $log[] = '✅ Data kelas berhasil di-seed.';

    // Ambil class IDs
    $clsRows = $pdo->query("SELECT id, name FROM classes ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
    $classIds = array_flip($clsRows); // ['VII-A' => 1, ...]

    // 7. Seed students
    $students = [
        [$userIds['ahmad@school.sch.id'],   '2025001', 'Ahmad Fauzi',    $classIds['VII-A']  ?? 1, 'L', '2012-03-15', 'Jl. Merdeka No. 1',    '081234567890'],
        [$userIds['rizki@school.sch.id'],   '2025002', 'Rizki Pratama',  $classIds['VII-A']  ?? 1, 'L', '2012-07-22', 'Jl. Pahlawan No. 5',   '081234567891'],
        [$userIds['dewi@school.sch.id'],    '2025003', 'Dewi Rahayu',    $classIds['VII-B']  ?? 2, 'P', '2012-01-10', 'Jl. Kenanga No. 12',   '081234567892'],
        [$userIds['ilham@school.sch.id'],   '2025004', 'Muhammad Ilham', $classIds['VII-B']  ?? 2, 'L', '2012-09-05', 'Jl. Mawar No. 3',      '081234567893'],
        [$userIds['siti@school.sch.id'],    '2025005', 'Siti Nurhaliza', $classIds['VIII-A'] ?? 3, 'P', '2011-11-30', 'Jl. Melati No. 7',     '081234567894'],
        [$userIds['farhan@school.sch.id'],  '2025006', 'Farhan Wijaya',  $classIds['VIII-A'] ?? 3, 'L', '2011-04-18', 'Jl. Anggrek No. 9',    '081234567895'],
        [$userIds['lestari@school.sch.id'], '2025007', 'Lestari Putri',  $classIds['VIII-B'] ?? 4, 'P', '2011-06-25', 'Jl. Dahlia No. 4',     '081234567896'],
        [$userIds['hendra@school.sch.id'],  '2025008', 'Hendra Gunawan', $classIds['IX-A']   ?? 5, 'L', '2010-12-12', 'Jl. Flamboyan No. 2',  '081234567897'],
    ];
    $stmtS = $pdo->prepare(
        'INSERT IGNORE INTO students (user_id, nis, name, class_id, gender, birth_date, address, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($students as $s) $stmtS->execute($s);
    $log[] = '✅ Data siswa berhasil di-seed (' . count($students) . ' siswa).';

    // 8. Seed subjects
    $budTeacherId  = $teacherIdMap[$userIds['budi@school.sch.id']] ?? 0;
    $sariTeacherId = $teacherIdMap[$userIds['sari@school.sch.id']] ?? 0;
    $clsVIIA  = $classIds['VII-A']  ?? 1;
    $clsVIIB  = $classIds['VII-B']  ?? 2;
    $clsVIIIA = $classIds['VIII-A'] ?? 3;
    $clsVIIIB = $classIds['VIII-B'] ?? 4;
    $clsIXA   = $classIds['IX-A']   ?? 5;

    $subjects = [
        // VII-A — Budi Santoso
        ['Matematika',        'MTK',  $clsVIIA,  $budTeacherId],
        ['Bahasa Indonesia',  'BIND', $clsVIIA,  $budTeacherId],
        ['IPA',               'IPA',  $clsVIIA,  $budTeacherId],
        // VII-B — Sari Dewi
        ['Matematika',        'MTK',  $clsVIIB,  $sariTeacherId],
        ['Bahasa Indonesia',  'BIND', $clsVIIB,  $sariTeacherId],
        ['IPS',               'IPS',  $clsVIIB,  $sariTeacherId],
        // VIII-A — Budi Santoso
        ['Matematika',        'MTK',  $clsVIIIA, $budTeacherId],
        ['Bahasa Inggris',    'BING', $clsVIIIA, $budTeacherId],
        // VIII-B — Sari Dewi
        ['Matematika',        'MTK',  $clsVIIIB, $sariTeacherId],
        ['Bahasa Indonesia',  'BIND', $clsVIIIB, $sariTeacherId],
        // IX-A — Budi Santoso
        ['Matematika',        'MTK',  $clsIXA,   $budTeacherId],
        ['IPA',               'IPA',  $clsIXA,   $budTeacherId],
    ];

    $stmtSub = $pdo->prepare(
        'INSERT IGNORE INTO subjects (name, code, class_id, teacher_id) VALUES (?, ?, ?, ?)'
    );
    foreach ($subjects as $sub) $stmtSub->execute($sub);
    $log[] = '✅ Data mata pelajaran berhasil di-seed (' . count($subjects) . ' mapel).';

    // 9. Create uploads dir
    $uploadDir = __DIR__ . '/assets/images/uploads';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $log[] = '✅ Direktori uploads siap.';

} catch (PDOException $e) {
    $log[] = '❌ Error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>NextScho Setup</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
<div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422A12.08 12.08 0 0118.82 17M12 14l-6.16-3.422A12.08 12.08 0 005.18 17"/>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-800">NextScho Setup</h1>
            <p class="text-sm text-gray-500">Inisialisasi database & data awal</p>
        </div>
    </div>

    <ul class="space-y-2 mb-6">
        <?php foreach ($log as $item): ?>
        <li class="text-sm py-2 px-3 rounded-lg <?= str_starts_with($item, '❌') ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
            <?= htmlspecialchars($item) ?>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="border-t pt-5 space-y-3">
        <p class="text-sm font-semibold text-gray-700">Akun Login:</p>
        <div class="text-xs bg-gray-50 rounded-lg p-3 space-y-1 font-mono">
            <p><span class="text-indigo-600 font-semibold">Admin</span> — admin@school.sch.id / admin123</p>
            <p><span class="text-emerald-600 font-semibold">Guru</span>  — budi@school.sch.id / guru123</p>
            <p><span class="text-amber-600 font-semibold">Siswa</span>  — ahmad@school.sch.id / siswa123</p>
        </div>
        <div class="flex gap-3 pt-2">
            <a href="/nextscho/auth/login.php"
               class="flex-1 text-center py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                Buka Halaman Login
            </a>
            <div class="flex-1 py-2.5 bg-red-50 text-red-700 text-sm font-medium rounded-lg text-center">
                ⚠ Hapus setup.php setelah ini!
            </div>
        </div>
    </div>
</div>
</body>
</html>
