<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);

$data     = read_json_body();
$email    = str_value($data['email'] ?? '');
$newPw    = (string)($data['new_password'] ?? '');

if ($email === '')         error_response('Email is required', 422);
if ($newPw === '')         error_response('New password is required', 422);
if (strlen($newPw) < 6)   error_response('Password must be at least 6 characters', 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error_response('Valid email is required', 422);

$pdo  = db();

// Find doctor by email
$stmt = $pdo->prepare('SELECT id, name FROM doctors WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$doctor = $stmt->fetch();

if (!$doctor) {
    error_response('No account found for this email address. The doctor may not have a login account yet.', 404);
}

// Hash and update
$hash   = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
$update = $pdo->prepare(
    'UPDATE doctors SET password_hash = :hash, account_type = "member" WHERE id = :id'
);
$update->execute([':hash' => $hash, ':id' => (int)$doctor['id']]);

json_response([
    'success' => true,
    'message' => 'Password updated successfully for ' . $doctor['name'],
]);
