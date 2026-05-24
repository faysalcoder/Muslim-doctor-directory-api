<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
require_admin();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $posts = $pdo->query('SELECT p.*, d.name AS author_name FROM forum_posts p LEFT JOIN doctors d ON d.id = p.doctor_id ORDER BY p.id DESC LIMIT 200')->fetchAll();
    $comments = $pdo->query('SELECT c.*, d.name AS author_name, p.title AS post_title FROM forum_comments c LEFT JOIN doctors d ON d.id = c.doctor_id LEFT JOIN forum_posts p ON p.id = c.post_id ORDER BY c.id DESC LIMIT 200')->fetchAll();
    json_response(['success' => true, 'data' => ['posts' => $posts, 'comments' => $comments]]);
}

if ($method === 'POST') {
    $data = read_json_body();
    $type = str_value($data['entity'] ?? '');
    $id = (int)($data['id'] ?? 0);
    $status = str_value($data['status'] ?? 'hidden');
    if ($id <= 0 || $type === '') error_response('Entity and id are required', 422);

    if ($type === 'post') {
        $stmt = $pdo->prepare('UPDATE forum_posts SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    } elseif ($type === 'comment') {
        $stmt = $pdo->prepare('UPDATE forum_comments SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    } else {
        error_response('Invalid entity', 422);
    }
    json_response(['success' => true, 'message' => 'Updated successfully']);
}

error_response('Method not allowed', 405);
