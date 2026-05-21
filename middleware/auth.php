<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function get_bearer_token(): ?string
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach (['Authorization', 'authorization'] as $key) {
        if (!empty($headers[$key]) && preg_match('/Bearer\s+(.*)$/i', $headers[$key], $matches)) {
            return trim($matches[1]);
        }
    }
    return null;
}

function require_admin(): array
{
    $token = get_bearer_token();
    if (!$token) error_response('Unauthorized', 401);

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALG));
        // Payload is encoded flat (sub, email, name, role) — not nested under 'data'
        return [
            'id'    => $decoded->sub   ?? null,
            'email' => $decoded->email ?? null,
            'name'  => $decoded->name  ?? null,
            'role'  => $decoded->role  ?? null,
        ];
    } catch (Throwable $e) {
        error_response('Invalid or expired token', 401);
    }
}
