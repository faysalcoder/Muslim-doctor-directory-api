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
    $q = trim((string)($_GET['q'] ?? ''));
    $mine = bool_value($_GET['mine'] ?? 0);
    $auth = decode_auth_token();
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(j.post_name LIKE :q OR j.job_location LIKE :q OR j.hospital_name LIKE :q OR j.description LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    if ($mine && $auth && $auth['id']) {
        $where[] = 'j.doctor_id = :mine_id';
        $params['mine_id'] = $auth['id'];
    } elseif (!($auth && in_array((string)$auth['role'], ['admin','super_admin'], true))) {
        $where[] = "j.status = 'open'";
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("SELECT j.*, d.name AS author_name, d.specialty AS author_specialty, d.graduation_degree, d.graduation_year, d.experience AS doctor_experience FROM job_posts j LEFT JOIN doctors d ON d.id = j.doctor_id {$whereSql} ORDER BY j.id DESC LIMIT 200");
    foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
    $stmt->execute();
    json_response(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $auth = require_member();
    $data = read_json_body();
    $postName = str_value($data['post_name'] ?? '');
    $jobLocation = str_value($data['job_location'] ?? '');
    $hospitalName = str_value($data['hospital_name'] ?? '');
    $vacancy = int_value($data['vacancy_available'] ?? null, null);
    $description = str_value($data['description'] ?? '');
    $status = str_value($data['status'] ?? 'open');
    if ($postName === '' || $jobLocation === '' || $hospitalName === '' || !$vacancy || $description === '') error_response('All fields are required', 422);

    $stmt = $pdo->prepare('INSERT INTO job_posts (doctor_id, post_name, job_location, hospital_name, vacancy_available, description, status) VALUES (:doctor_id, :post_name, :job_location, :hospital_name, :vacancy_available, :description, :status)');
    $stmt->execute([
        'doctor_id' => $auth['id'],
        'post_name' => $postName,
        'job_location' => $jobLocation,
        'hospital_name' => $hospitalName,
        'vacancy_available' => $vacancy,
        'description' => $description,
        'status' => $status,
    ]);
    json_response(['success' => true, 'message' => 'Job posted successfully', 'id' => (int)$pdo->lastInsertId()]);
}

error_response('Method not allowed', 405);
