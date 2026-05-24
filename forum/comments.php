<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $postId = (int)($_GET['post_id'] ?? 0);
    $mine = bool_value($_GET['mine'] ?? 0);
    if ($postId <= 0 && !$mine) error_response('Post id is required', 422);
    $auth = decode_auth_token();

    $where = [];
    $params = [];
    if ($postId > 0) { $where[] = 'c.post_id = :post_id'; $params['post_id'] = $postId; }
    if ($mine && $auth && $auth['id']) { $where[] = 'c.doctor_id = :doctor_id'; $params['doctor_id'] = $auth['id']; }
    if (!($auth && in_array((string)$auth['role'], ['admin','super_admin'], true))) { $where[] = "c.status <> 'deleted'"; }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT c.*, d.name AS author_name, d.profile_image AS author_image FROM forum_comments c LEFT JOIN doctors d ON d.id = c.doctor_id {$whereSql} ORDER BY c.id ASC";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
    $stmt->execute();
    json_response(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $auth = require_member();
    $data = read_json_body();
    $postId = (int)($data['post_id'] ?? 0);
    $comment = str_value($data['comment'] ?? $data['content'] ?? '');
    $parentId = int_value($data['parent_id'] ?? null, null);
    if ($postId <= 0 || $comment === '') error_response('Post id and comment are required', 422);

    $check = $pdo->prepare('SELECT id FROM forum_posts WHERE id = :id LIMIT 1');
    $check->execute(['id' => $postId]);
    if (!$check->fetch()) error_response('Post not found', 404);

    $stmt = $pdo->prepare('INSERT INTO forum_comments (post_id, doctor_id, parent_id, comment, status) VALUES (:post_id, :doctor_id, :parent_id, :comment, :status)');
    $stmt->execute([
        'post_id' => $postId,
        'doctor_id' => $auth['id'],
        'parent_id' => $parentId,
        'comment' => $comment,
        'status' => 'published',
    ]);

    json_response(['success' => true, 'message' => 'Comment posted', 'id' => (int)$pdo->lastInsertId()]);
}

error_response('Method not allowed', 405);
