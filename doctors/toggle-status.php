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
$id = (int)($input['id'] ?? 0);
$status = trim((string)($input['status'] ?? ''));

$allowed = ['verified', 'pending', 'inactive'];
if ($id <= 0 || !in_array($status, $allowed, true)) error_response('Invalid doctor id or status', 422);

$pdo = db();
$stmt = $pdo->prepare('UPDATE doctors SET status = :status WHERE id = :id');
$stmt->execute(['status' => $status, 'id' => $id]);

success_response('Doctor status updated');
