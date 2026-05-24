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

$data     = read_json_body();
$name     = str_value($data['name']     ?? '');
$email    = str_value($data['email']    ?? '');
$phone    = str_value($data['phone']    ?? '');
$password = (string)($data['password'] ?? '');

if ($name === '' || $email === '' || $password === '') error_response('Name, email and password are required', 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL))        error_response('Valid email is required', 422);
if (strlen($password) < 6)                             error_response('Password must be at least 6 characters', 422);

$pdo = db();

// Block if already an admin
$check = $pdo->prepare('SELECT id FROM admins WHERE email = :email LIMIT 1');
$check->execute(['email' => $email]);
if ($check->fetch()) error_response('This email is already used by an admin account', 409);

// Check if a doctor listing already exists for this email (e.g. added by admin or via public-apply)
$check = $pdo->prepare('SELECT id, name, password_hash, status FROM doctors WHERE email = :email LIMIT 1');
$check->execute(['email' => $email]);
$existing = $check->fetch();

$hash = password_hash($password, PASSWORD_DEFAULT);

if ($existing) {
    // Doctor listing already exists — if it has no password yet, activate it as a member account.
    // If it already has a password the user should log in, not re-register.
    if (!empty($existing['password_hash'])) {
        error_response('An account with this email already exists. Please log in instead.', 409);
    }

    // Set password and upgrade to member account
    $update = $pdo->prepare(
        'UPDATE doctors SET password_hash = :hash, account_type = "member", name = :name ' .
        (($phone !== '') ? ', phone = :phone ' : '') .
        'WHERE id = :id'
    );
    $params = [':hash' => $hash, ':name' => $name, ':id' => (int)$existing['id']];
    if ($phone !== '') $params[':phone'] = $phone;
    $update->execute($params);

    $id         = (int)$existing['id'];
    $status     = $existing['status'];
    $doctorCode = null; // existing record, no new code
} else {
    // Brand-new user — insert a fresh doctor record
    $doctorCode = 'MNP-' . strtoupper(bin2hex(random_bytes(3)));

    $stmt = $pdo->prepare('
        INSERT INTO doctors (
            doctor_id, name, specialty, phone, email, password_hash,
            account_type, profile_visibility, status, accepting_patients, bio
        ) VALUES (
            :doctor_id, :name, :specialty, :phone, :email, :password_hash,
            :account_type, :profile_visibility, :status, 0, :bio
        )
    ');
    $stmt->execute([
        'doctor_id'          => $doctorCode,
        'name'               => $name,
        'specialty'          => null,
        'phone'              => $phone !== '' ? $phone : null,
        'email'              => $email,
        'password_hash'      => $hash,
        'account_type'       => 'member',
        'profile_visibility' => 'public',
        'status'             => 'pending',
        'bio'                => '',
    ]);
    $id     = (int)$pdo->lastInsertId();
    $status = 'pending';
}

// Fetch final doctor_id for the response
$row = $pdo->prepare('SELECT doctor_id FROM doctors WHERE id = :id LIMIT 1');
$row->execute([':id' => $id]);
$finalCode = ($row->fetchColumn()) ?: $doctorCode;

$now = time();
$payload = [
    'iss'   => 'nomp-api',
    'aud'   => 'nomp-member',
    'iat'   => $now,
    'exp'   => $now + JWT_TTL_SECONDS,
    'sub'   => $id,
    'email' => $email,
    'name'  => $name,
    'role'  => 'member',
];
$token = JWT::encode($payload, JWT_SECRET, JWT_ALG);

json_response([
    'success'   => true,
    'message'   => 'Account created successfully',
    'token'     => $token,
    'user'      => [
        'id'        => $id,
        'name'      => $name,
        'email'     => $email,
        'phone'     => $phone,
        'role'      => 'member',
        'status'    => $status,
        'doctor_id' => $finalCode,
    ],
]);
