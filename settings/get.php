<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') error_response('Method not allowed', 405);

$pdo  = db();
$rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();

$settings = [];
foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

json_response(['success' => true, 'settings' => $settings]);
