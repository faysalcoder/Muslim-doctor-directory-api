<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') error_response('Method not allowed', 405);

$pdo = db();

$totalDoctors    = (int)$pdo->query('SELECT COUNT(*) FROM doctors')->fetchColumn();
$verifiedDoctors = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'verified'")->fetchColumn();
$pendingDoctors  = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'pending'")->fetchColumn();
$inactiveDoctors = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'inactive'")->fetchColumn();

$latestDoctors = $pdo->query('
    SELECT id, doctor_id, name, specialty, status, profile_image, created_at
    FROM doctors ORDER BY id DESC LIMIT 5
')->fetchAll();

$recentGallery = $pdo->query('
    SELECT di.id, di.image, di.created_at, d.name AS doctor_name, d.id AS doctor_ref_id
    FROM doctor_images di
    INNER JOIN doctors d ON d.id = di.doctor_id
    ORDER BY di.id DESC LIMIT 8
')->fetchAll();

// 'stats' key — matches what the React frontend getDashboardStats() expects
json_response([
    'success' => true,
    'stats' => [
        'totalDoctors' => $totalDoctors,
        'verified'     => $verifiedDoctors,
        'pending'      => $pendingDoctors,
        'inactive'     => $inactiveDoctors,
    ],
    // Extra data the admin dashboard can use
    'latestDoctors' => $latestDoctors,
    'recentGallery' => $recentGallery,
]);
