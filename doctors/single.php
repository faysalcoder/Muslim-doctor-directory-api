<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') error_response('Method not allowed', 405);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) error_response('Valid doctor id is required', 422);

$pdo  = db();
$stmt = $pdo->prepare('SELECT * FROM doctors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$doctor = $stmt->fetch();

if (!$doctor) error_response('Doctor not found', 404);

$imgStmt = $pdo->prepare('SELECT id, image, created_at FROM doctor_images WHERE doctor_id = :id ORDER BY id DESC');
$imgStmt->execute(['id' => $id]);
$doctor['gallery'] = $imgStmt->fetchAll();

// Response shape: { success, data: { ...doctor fields flat } }
// Matches React getDoctorById() → expects res.data to be the doctor object directly
json_response([
    'success' => true,
    'data'    => $doctor,
]);
