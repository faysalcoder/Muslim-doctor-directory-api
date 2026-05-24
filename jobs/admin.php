<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
require_admin();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $jobs = $pdo->query('SELECT j.*, d.name AS author_name FROM job_posts j LEFT JOIN doctors d ON d.id = j.doctor_id ORDER BY j.id DESC LIMIT 200')->fetchAll();
    $availability = $pdo->query('SELECT a.*, d.name AS doctor_name, d.email, d.phone, d.specialty AS doctor_specialty FROM doctor_availability a LEFT JOIN doctors d ON d.id = a.doctor_id ORDER BY a.id DESC LIMIT 200')->fetchAll();
    json_response(['success' => true, 'data' => ['jobs' => $jobs, 'availability' => $availability]]);
}

if ($method === 'POST') {
    $data = read_json_body();
    $entity = str_value($data['entity'] ?? '');
    $id = (int)($data['id'] ?? 0);
    $status = str_value($data['status'] ?? 'hidden');
    if ($entity === '' || $id <= 0) error_response('Entity and id are required', 422);
    if ($entity === 'job') {
        $stmt = $pdo->prepare('UPDATE job_posts SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    } elseif ($entity === 'availability') {
        $stmt = $pdo->prepare('UPDATE doctor_availability SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    } else {
        error_response('Invalid entity', 422);
    }
    json_response(['success' => true, 'message' => 'Updated successfully']);
}

error_response('Method not allowed', 405);
