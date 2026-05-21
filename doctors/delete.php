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
$id = (int)($input['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) error_response('Valid doctor id is required', 422);

$pdo = db();
$pdo->beginTransaction();

try {
    $gallery = $pdo->prepare('SELECT image FROM doctor_images WHERE doctor_id = :id');
    $gallery->execute(['id' => $id]);
    $images = $gallery->fetchAll(PDO::FETCH_COLUMN);

    $doctor = $pdo->prepare('SELECT profile_image FROM doctors WHERE id = :id LIMIT 1');
    $doctor->execute(['id' => $id]);
    $profile = $doctor->fetchColumn();

    $deleteGallery = $pdo->prepare('DELETE FROM doctor_images WHERE doctor_id = :id');
    $deleteGallery->execute(['id' => $id]);

    $deleteDoctor = $pdo->prepare('DELETE FROM doctors WHERE id = :id');
    $deleteDoctor->execute(['id' => $id]);

    $pdo->commit();

    success_response('Doctor deleted successfully', [
        'deleted_images' => array_values(array_filter(array_merge([$profile], $images))),
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_response('Failed to delete doctor', 500, ['error' => $e->getMessage()]);
}
