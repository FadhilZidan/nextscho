<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Grade.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = currentUser()['role'] ?? '';
if ($role !== 'admin' && $role !== 'guru') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$subjectId = (int) ($data['subject_id'] ?? 0);
$semester  = (int) ($data['semester']   ?? SEMESTER);
$year      = $data['year']              ?? ACADEMIC_YEAR;
$grades    = $data['grades']            ?? [];

if (!$subjectId || !is_array($grades)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$saved = 0;
foreach ($grades as $studentId => $scores) {
    Grade::save((int) $studentId, $subjectId, $scores, $semester, $year);
    $saved++;
}

echo json_encode(['success' => true, 'saved' => $saved]);
