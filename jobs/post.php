<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') error_response('Method not allowed', 405);
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) error_response('Valid job id is required', 422);
$pdo = db();
$stmt = $pdo->prepare('SELECT j.*, d.name AS author_name, d.specialty AS author_specialty, d.graduation_degree, d.graduation_year, d.experience AS doctor_experience FROM job_posts j LEFT JOIN doctors d ON d.id = j.doctor_id WHERE j.id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();
if (!$row) error_response('Job post not found', 404);
$auth = decode_auth_token();
if (!($auth && in_array((string)$auth['role'], ['admin','super_admin'], true)) && $row['status'] !== 'open' && (int)$row['doctor_id'] !== (int)($auth['id'] ?? 0)) error_response('Job post not found', 404);
json_response(['success' => true, 'data' => $row]);
