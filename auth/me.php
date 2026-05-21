<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') error_response('Method not allowed', 405);

$payload = require_admin();
$pdo = db();

$stmt = $pdo->prepare('SELECT id, name, email, role, status, avatar, created_at FROM admins WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $payload['id']]);
$admin = $stmt->fetch();

if (!$admin) error_response('Admin not found', 404);

success_response('Admin profile loaded', ['admin' => $admin]);
