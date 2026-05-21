<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
$admin = require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);

$input   = json_decode(file_get_contents('php://input'), true) ?: [];
$current = (string)($input['current_password'] ?? '');
$next    = (string)($input['new_password']     ?? '');
$confirm = (string)($input['confirm_password'] ?? '');

if ($current === '' || $next === '' || $confirm === '') error_response('All password fields are required', 422);
if (strlen($next) < 6) error_response('New password must be at least 6 characters', 422);
if ($next !== $confirm) error_response('New passwords do not match', 422);

$pdo  = db();
$stmt = $pdo->prepare('SELECT id, password FROM admins WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $admin['id']]);
$row = $stmt->fetch();

if (!$row || !password_verify($current, $row['password'])) {
    error_response('Current password is incorrect', 401);
}

$hash   = password_hash($next, PASSWORD_BCRYPT, ['cost' => 12]);
$update = $pdo->prepare('UPDATE admins SET password = :password WHERE id = :id');
$update->execute([':password' => $hash, ':id' => $admin['id']]);

json_response(['success' => true, 'message' => 'Password updated successfully']);
