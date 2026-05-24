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
$stmt = $pdo->prepare('SELECT id, name, email, phone, password_hash, status, account_type FROM doctors WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    error_response('Invalid email or password', 401);
}

$now = time();
$payload = [
    'iss' => 'nomp-api',
    'aud' => 'nomp-member',
    'iat' => $now,
    'exp' => $now + JWT_TTL_SECONDS,
    'sub' => (int)$user['id'],
    'email' => $user['email'],
    'name' => $user['name'],
    'role' => 'member',
];
$token = JWT::encode($payload, JWT_SECRET, JWT_ALG);

json_response([
    'success' => true,
    'message' => 'Login successful',
    'token' => $token,
    'user' => [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? null,
        'role' => 'member',
        'status' => $user['status'] ?? 'pending',
        'account_type' => $user['account_type'] ?? 'member',
    ],
]);
