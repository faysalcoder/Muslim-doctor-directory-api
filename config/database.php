<?php
declare(strict_types=1);

// ── Edit these to match your XAMPP/MySQL setup ────────────────────────────
// You can also override these with environment variables:
// DB_HOST, DB_NAME, DB_USER, DB_PASS
require_once __DIR__ . '/../helpers/schema.php';

function env_or_default(string $key, string $default): string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

define('DB_HOST', env_or_default('DB_HOST', 'localhost'));
define('DB_NAME', env_or_default('DB_NAME', 'nomp'));
define('DB_USER', env_or_default('DB_USER', 'root'));
define('DB_PASS', env_or_default('DB_PASS', ''));
// ─────────────────────────────────────────────────────────────────────────

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $baseDsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
        $server = new PDO(
            $baseDsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        $dbNameSafe = str_replace('`', '``', DB_NAME);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbNameSafe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $pdo = new PDO(
            $baseDsn . ';dbname=' . DB_NAME,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        ensure_schema($pdo);
        return $pdo;
    } catch (Throwable $e) {
        while (ob_get_level()) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Content-Type: application/json; charset=utf-8');
        }

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database or schema initialization failed.',
            'error'   => $e->getMessage(),
            'hint'    => 'Check DB_HOST, DB_NAME, DB_USER, DB_PASS and confirm MySQL is running.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
