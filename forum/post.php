<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') error_response('Method not allowed', 405);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) error_response('Valid post id is required', 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT p.*, d.name AS author_name, d.specialty AS author_specialty, d.profile_image AS author_image FROM forum_posts p LEFT JOIN doctors d ON d.id = p.doctor_id WHERE p.id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$post = $stmt->fetch();
if (!$post) error_response('Post not found', 404);

$auth = decode_auth_token();
if (!($auth && in_array((string)$auth['role'], ['admin','super_admin'], true)) && $post['status'] !== 'published' && (int)$post['doctor_id'] !== (int)($auth['id'] ?? 0)) {
    error_response('Post not found', 404);
}

$post['image_url'] = $post['image'] ? '/uploads/forum/' . $post['image'] : null;
unset($post['password_hash']);
json_response(['success' => true, 'data' => $post]);
