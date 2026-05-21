<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$imageId = (int)($input['image_id'] ?? 0);
if ($imageId <= 0) error_response('image_id is required', 422);

$pdo = db();
$stmt = $pdo->prepare('DELETE FROM doctor_images WHERE id = :id');
$stmt->execute(['id' => $imageId]);

success_response('Image deleted successfully');
