<?php
declare(strict_types=1);

function send_cors_headers(): void
{
    // Hard-coded development origins
    $allowed = [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
    ];

    // Extra origins from env: ALLOWED_ORIGINS=https://yoursite.com,https://www.yoursite.com
    $envOrigins = getenv('ALLOWED_ORIGINS') ?: getenv('ALLOWED_ORIGIN');
    if ($envOrigins) {
        foreach (array_map('trim', explode(',', $envOrigins)) as $o) {
            if ($o !== '') $allowed[] = $o;
        }
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // In development (no origin header = same-origin / curl / Postman), allow freely
    if ($origin === '') {
        header('Access-Control-Allow-Origin: *');
    } elseif (in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    } else {
        // Unknown origin — still allow but reflect origin so preflight passes.
        // Remove this else-branch and replace with error_response() if you want strict blocking.
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
    header('Content-Type: application/json; charset=utf-8');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
