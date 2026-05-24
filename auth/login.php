<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../vendor/autoload.php';

send_cors_headers();

use Firebase\JWT\JWT;

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);

$data = read_json_body();
$email = str_value($data['email'] ?? '');
$password = (string)($data['password'] ?? '');

if ($email === '' || $password === '') error_response('Email and password are required', 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT id, name, email, password, role, status, avatar FROM admins WHERE email = :email AND status = :status LIMIT 1');
$stmt->execute(['email' => $email, 'status' => 'active']);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
    error_response('Invalid email or password', 401);
}

$now = time();
$payload = [
    'iss' => 'nomp-api',
    'aud' => 'nomp-admin',
    'iat' => $now,
    'exp' => $now + JWT_TTL_SECONDS,
    'sub' => (int)$admin['id'],
    'email' => $admin['email'],
    'name' => $admin['name'],
    'role' => $admin['role'] ?? 'admin',
];
$token = JWT::encode($payload, JWT_SECRET, JWT_ALG);

json_response([
    'success' => true,
    'message' => 'Login successful',
    'token' => $token,
    'user' => [
        'id' => (int)$admin['id'],
        'name' => $admin['name'],
        'email' => $admin['email'],
        'role' => $admin['role'] ?? 'admin',
        'avatar' => $admin['avatar'] ?? null,
        'status' => $admin['status'] ?? 'active',
    ],
]);
