<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$siteName = trim((string)($input['site_name']   ?? ''));
$email    = trim((string)($input['admin_email'] ?? ''));

if ($siteName === '') error_response('Site name is required', 422);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) error_response('Invalid email address', 422);

$pdo  = db();
$stmt = $pdo->prepare('
    INSERT INTO site_settings (setting_key, setting_value)
    VALUES (:k, :v)
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
');

$stmt->execute([':k' => 'site_name',   ':v' => $siteName]);
$stmt->execute([':k' => 'admin_email', ':v' => $email]);

json_response(['success' => true, 'message' => 'Settings saved successfully']);
