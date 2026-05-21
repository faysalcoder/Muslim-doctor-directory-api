<?php
declare(strict_types=1);

// ── Prevent PHP warnings/notices from corrupting JSON output ──────────────
// Any HTML error output (e.g. "<br /><b>Warning</b>...") breaks JSON parsing
// in the frontend. We capture all output and discard it before sending JSON.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);  // still log to server log, just don't print to output

// Start output buffer so any accidental echo/warning before json_response()
// gets discarded rather than prepended to the JSON body.
if (!ob_get_level()) {
    ob_start();
}

function json_response(array $payload, int $statusCode = 200): void
{
    // Discard any buffered output (PHP warnings, BOM, whitespace, etc.)
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function success_response(string $message, array $data = [], int $statusCode = 200): void
{
    json_response(['success' => true, 'message' => $message, 'data' => $data], $statusCode);
}

function error_response(string $message, int $statusCode = 400, array $extra = []): void
{
    json_response(array_merge(['success' => false, 'message' => $message], $extra), $statusCode);
}
