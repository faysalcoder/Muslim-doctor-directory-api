<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') error_response('Method not allowed', 405);

$pdo = db();

$search    = trim((string)($_GET['search']    ?? ''));
$specialty = trim((string)($_GET['specialty'] ?? ''));
$location  = trim((string)($_GET['location']  ?? ''));
$status    = trim((string)($_GET['status']    ?? ''));
$page      = max(1, (int)($_GET['page']  ?? 1));
$limit     = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$offset    = ($page - 1) * $limit;

$where  = [];
$params = [];

if ($search !== '') {
    $where[]          = '(name LIKE :search OR doctor_id LIKE :search OR email LIKE :search OR phone LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($specialty !== '') {
    $where[]             = 'specialty = :specialty';
    $params['specialty'] = $specialty;
}
if ($location !== '') {
    $where[]            = 'location LIKE :location';
    $params['location'] = '%' . $location . '%';
}
if ($status !== '') {
    $where[]          = 'status = :status';
    $params['status'] = $status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM doctors {$whereSql}");
foreach ($params as $k => $v) $countStmt->bindValue(':' . $k, $v);
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();

// No JSON_ARRAYAGG — fetch doctors first, then images separately
$sql  = "SELECT * FROM doctors {$whereSql} ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$doctors = $stmt->fetchAll();

if (!empty($doctors)) {
    $ids          = array_column($doctors, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $imgStmt      = $pdo->prepare("SELECT id, doctor_id, image FROM doctor_images WHERE doctor_id IN ({$placeholders}) ORDER BY id ASC");
    $imgStmt->execute($ids);
    $imageMap = [];
    foreach ($imgStmt->fetchAll() as $img) {
        $imageMap[$img['doctor_id']][] = ['id' => $img['id'], 'image' => $img['image']];
    }
    foreach ($doctors as &$doctor) {
        $doctor['gallery'] = $imageMap[$doctor['id']] ?? [];
        unset($doctor['password_hash']);
    }
    unset($doctor);
} else {
    foreach ($doctors as &$doctor) { $doctor['gallery'] = []; unset($doctor['password_hash']); }
    unset($doctor);
}

success_response('Doctors loaded', [
    'data' => $doctors,
    'pagination' => [
        'total' => $total,
        'page'  => $page,
        'limit' => $limit,
        'pages' => (int)ceil($total / $limit),
    ],
]);
