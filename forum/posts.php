<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $query = trim((string)($_GET['q'] ?? ''));
    $type = trim((string)($_GET['type'] ?? ''));
    $mine = bool_value($_GET['mine'] ?? 0);
    $status = trim((string)($_GET['status'] ?? 'published'));
    $auth = decode_auth_token();

    $where = [];
    $params = [];
    if ($query !== '') {
        $where[] = '(p.title LIKE :q OR p.content LIKE :q OR d.name LIKE :q)';
        $params['q'] = '%' . $query . '%';
    }
    if ($type !== '') {
        $where[] = 'p.type = :type';
        $params['type'] = $type;
    }
    if ($mine && $auth && $auth['id']) {
        $where[] = 'p.doctor_id = :mine_id';
        $params['mine_id'] = $auth['id'];
    } elseif (!($auth && in_array((string)$auth['role'], ['admin','super_admin'], true))) {
        $where[] = 'p.status = :status';
        $params['status'] = $status ?: 'published';
    } elseif ($status !== '') {
        $where[] = 'p.status = :status';
        $params['status'] = $status;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT p.*, d.name AS author_name, d.specialty AS author_specialty, d.profile_image AS author_image FROM forum_posts p LEFT JOIN doctors d ON d.id = p.doctor_id {$whereSql} ORDER BY p.id DESC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
    $stmt->execute();
    $posts = $stmt->fetchAll();

    $ids = array_column($posts, 'id');
    $counts = [];
    if ($ids) {
        $place = implode(',', array_fill(0, count($ids), '?'));
        $c = $pdo->prepare("SELECT post_id, COUNT(*) AS total FROM forum_comments WHERE post_id IN ($place) AND status <> 'deleted' GROUP BY post_id");
        $c->execute($ids);
        foreach ($c->fetchAll() as $row) $counts[(int)$row['post_id']] = (int)$row['total'];
    }

    foreach ($posts as &$post) {
        $post['comment_count'] = $counts[(int)$post['id']] ?? 0;
        $post['image_url'] = $post['image'] ? '/uploads/forum/' . $post['image'] : null;
        unset($post['password_hash']);
    }
    unset($post);

    json_response(['success' => true, 'data' => $posts]);
}

if ($method === 'POST') {
    $auth = require_member();
    $data = !empty($_POST) ? $_POST : read_json_body();

    $title = str_value($data['title'] ?? '');
    $type = str_value($data['type'] ?? 'question');
    $content = str_value($data['content'] ?? '');
    $tags = str_value($data['tags'] ?? '');
    $status = str_value($data['status'] ?? 'published');

    if ($title === '' || $content === '') error_response('Title and content are required', 422);

    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $image = upload_single_image($_FILES['image'], __DIR__ . '/../uploads/forum');
    }

    $stmt = $pdo->prepare('INSERT INTO forum_posts (doctor_id, type, title, content, image, tags, status) VALUES (:doctor_id, :type, :title, :content, :image, :tags, :status)');
    $stmt->execute([
        'doctor_id' => $auth['id'],
        'type' => $type,
        'title' => $title,
        'content' => $content,
        'image' => $image,
        'tags' => $tags,
        'status' => $status,
    ]);

    json_response(['success' => true, 'message' => 'Post created successfully', 'id' => (int)$pdo->lastInsertId()]);
}

error_response('Method not allowed', 405);
