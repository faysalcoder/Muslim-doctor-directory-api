<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);

$doctorId = (int)($_POST['doctor_id'] ?? 0);
if ($doctorId <= 0) error_response('doctor_id is required', 422);

$pdo  = db();
$stmt = $pdo->prepare('SELECT id FROM doctors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $doctorId]);
if (!$stmt->fetchColumn()) error_response('Doctor not found', 404);

// Accept 'images[]' (frontend field name) OR 'gallery_images' (original API field name)
$filesKey = null;
if (isset($_FILES['images'])) {
    $filesKey = 'images';
} elseif (isset($_FILES['gallery_images'])) {
    $filesKey = 'gallery_images';
}

if (!$filesKey) error_response('No images uploaded', 422);

$files  = upload_multiple_images($_FILES[$filesKey], __DIR__ . '/../uploads/doctors/gallery');
$insert = $pdo->prepare('INSERT INTO doctor_images (doctor_id, image) VALUES (:doctor_id, :image)');
foreach ($files as $file) {
    $insert->execute(['doctor_id' => $doctorId, 'image' => $file]);
}

json_response(['success' => true, 'message' => 'Gallery images uploaded', 'count' => count($files), 'images' => $files]);
