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
    foreach ($headers as $key => $value) {
        if (strcasecmp((string)$key, 'Authorization') === 0 && preg_match('/Bearer\s+(.*)$/i', (string)$value, $matches)) {
            return trim($matches[1]);
        }
    }
    return null;
}

function decode_auth_token(): ?array
{
    $token = get_bearer_token();
    if (!$token) return null;

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALG));
        return [
            'id'    => isset($decoded->sub) ? (int)$decoded->sub : null,
            'email' => $decoded->email ?? null,
            'name'  => $decoded->name ?? null,
            'role'  => $decoded->role ?? null,
        ];
    } catch (Throwable $e) {
        return null;
    }
}

function require_role(array|string $roles): array
{
    $roles = is_array($roles) ? $roles : [$roles];
    $payload = decode_auth_token();
    if (!$payload || !$payload['id']) {
        error_response('Unauthorized', 401);
    }
    if (!in_array((string)($payload['role'] ?? ''), $roles, true)) {
        error_response('Forbidden', 403);
    }
    return $payload;
}

function require_admin(): array
{
    return require_role(['admin', 'super_admin']);
}

function require_member(): array
{
    return require_role(['member', 'admin', 'super_admin']);
}
