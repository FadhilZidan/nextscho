<?php
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/auth/middleware.php';
require_once APP_ROOT . '/models/Announcement.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$limit = min((int) ($_GET['limit'] ?? 5), 20);
$items = Announcement::getLatest($limit);

$out = array_map(function ($a) {
    return [
        'id'      => $a['id'],
        'title'   => $a['title'],
        'excerpt' => mb_substr($a['content'], 0, 100),
        'date'    => $a['created_at'],
    ];
}, $items);

echo json_encode($out);
