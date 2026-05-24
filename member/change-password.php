<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);

$payload = require_member();
$data = read_json_body();
$currentPassword = (string)($data['current_password'] ?? '');
$newPassword = (string)($data['new_password'] ?? '');

if ($currentPassword === '' || $newPassword === '') error_response('Current and new password are required', 422);
if (strlen($newPassword) < 6) error_response('New password must be at least 6 characters', 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT id, password_hash FROM doctors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $payload['id']]);
$user = $stmt->fetch();
if (!$user || empty($user['password_hash']) || !password_verify($currentPassword, $user['password_hash'])) {
    error_response('Current password is incorrect', 401);
}

$upd = $pdo->prepare('UPDATE doctors SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
$upd->execute([
    'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
    'id' => $payload['id'],
]);

json_response(['success' => true, 'message' => 'Password updated successfully']);
