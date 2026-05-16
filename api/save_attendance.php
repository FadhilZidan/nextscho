<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Attendance.php';
require_once APP_ROOT . '/models/Teacher.php';

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

$classId = (int) ($data['class_id'] ?? 0);
$date    = $data['date']            ?? '';
$records = $data['records']         ?? [];

if (!$classId || !$date || !is_array($records)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid date format']);
    exit;
}

$user      = currentUser();
$teacherId = 0;
if ($role === 'guru') {
    $teacher   = Teacher::getByUserId((int) $user['id']);
    $teacherId = $teacher['id'] ?? 0;
}

$clean = [];
foreach ($records as $rec) {
    $sid    = (int) ($rec['student_id'] ?? 0);
    $status = in_array($rec['status'] ?? '', ['hadir','izin','sakit','alpha']) ? $rec['status'] : 'hadir';
    $notes  = trim($rec['notes'] ?? '');
    if ($sid) {
        $clean[] = ['student_id' => $sid, 'status' => $status, 'notes' => $notes];
    }
}

Attendance::saveBatch($classId, $date, $teacherId, $clean);

echo json_encode(['success' => true, 'saved' => count($clean)]);
