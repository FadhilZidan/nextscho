<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Student.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$classId = (int) ($_GET['class_id'] ?? 0);
if (!$classId) {
    echo json_encode([]);
    exit;
}

$db   = getDB();
$stmt = $db->prepare('SELECT id, nis, name FROM students WHERE class_id = ? ORDER BY name');
$stmt->execute([$classId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
