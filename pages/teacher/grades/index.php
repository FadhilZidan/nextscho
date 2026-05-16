<?php
define('APP_ROOT', dirname(__DIR__, 3));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Teacher.php';
require_once APP_ROOT . '/models/Subject.php';

requireRole('guru');

$user     = currentUser();
$teacher  = Teacher::getByUserId((int) $user['id']);
$subjects = $teacher ? Subject::getByTeacher($teacher['id']) : [];

$pageTitle  = 'Input Nilai';
$breadcrumb = [['label' => 'Portal Guru'], ['label' => 'Input Nilai']];
require_once APP_ROOT . '/includes/header.php';
?>

<div data-animate="fade-up">

    <div class="ns-page-header">
        <div>
            <h1 class="ns-page-title">Input Nilai</h1>
            <p class="ns-page-subtitle"><?= count($subjects) ?> mata pelajaran yang diampu</p>
        </div>
    </div>

    <div class="ns-glass-panel overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <div>
                <h2 class="text-base font-bold text-gray-900">Mata Pelajaran Anda</h2>
                <p class="text-xs text-gray-500 mt-1">Pilih mata pelajaran untuk menginput nilai siswa</p>
            </div>
            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
        </div>

        <?php if (empty($subjects)): ?>
        <div class="p-12 text-center flex flex-col items-center gap-3">
            <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-500">Belum ada mata pelajaran yang ditugaskan untuk Anda.</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($subjects as $s): ?>
            <div class="flex items-center justify-between gap-4 px-6 py-5 hover:bg-gray-50/50 transition-colors">
                <div>
                    <p class="text-sm font-bold text-gray-900 mb-1"><?= htmlspecialchars($s['name']) ?></p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-medium text-gray-400"><?= htmlspecialchars($s['code']) ?></span>
                        <?php if ($s['class_name']): ?>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-md bg-blue-50 text-blue-600">
                            <?= htmlspecialchars($s['class_name']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/pages/teacher/grades/input.php?subject_id=<?= $s['id'] ?>"
                   class="ns-btn ns-btn-primary flex-shrink-0">
                    Input Nilai
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
