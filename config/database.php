<?php
declare(strict_types=1);

// ── Edit these to match your XAMPP/MySQL setup ────────────────────────────
// XAMPP default:  DB_USER = 'root',  DB_PASS = ''  (empty string)
// If you created a custom MySQL user, change these to match.
define('DB_HOST', 'localhost');
define('DB_NAME', 'nomp');       // must match setup.sql
define('DB_USER', 'root');       // XAMPP default is 'root'
define('DB_PASS', '');           // XAMPP default is empty string ''
// ─────────────────────────────────────────────────────────────────────────

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        // Send a clean JSON error — never let PDO print HTML to the response
        while (ob_get_level()) ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Check DB_USER/DB_PASS in config/database.php.',
            'error'   => $e->getMessage(),
        ]);
        exit;
    }

    return $pdo;
}

$conn = db();
