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
    $mine = bool_value($_GET['mine'] ?? 0);
    $q = trim((string)($_GET['q'] ?? ''));
    $auth = decode_auth_token();
    $where = [];
    $params = [];
    if ($mine && $auth && $auth['id']) { $where[] = 'a.doctor_id = :mine_id'; $params['mine_id'] = $auth['id']; }
    else { $where[] = "a.status = 'open'"; }
    if ($q !== '') { $where[] = '(d.name LIKE :q OR a.degrees LIKE :q OR a.specialty LIKE :q OR a.subspecialty LIKE :q OR a.location LIKE :q)'; $params['q'] = '%' . $q . '%'; }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("SELECT a.*, d.name, d.specialty AS doctor_specialty, d.subspecialty AS doctor_subspecialty, d.graduation_degree, d.graduation_year, d.experience AS doctor_experience, d.profile_image FROM doctor_availability a LEFT JOIN doctors d ON d.id = a.doctor_id {$whereSql} ORDER BY a.id DESC LIMIT 200");
    foreach ($params as $k => $v) $stmt->bindValue(':' . $k, $v);
    $stmt->execute();
    json_response(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $auth = require_member();
    $data = read_json_body();
    $degrees = str_value($data['degrees'] ?? '');
    $yearExperience = str_value($data['year_of_experience'] ?? '');
    $specialty = str_value($data['specialty'] ?? '');
    $subspecialty = str_value($data['subspecialty'] ?? '');
    $location = str_value($data['location'] ?? '');
    $summary = str_value($data['summary'] ?? '');
    $status = str_value($data['status'] ?? 'open');
    if ($degrees === '' && $specialty === '' && $location === '') error_response('At least one availability detail is required', 422);

    $stmt = $pdo->prepare('INSERT INTO doctor_availability (doctor_id, degrees, year_of_experience, specialty, subspecialty, location, summary, status) VALUES (:doctor_id, :degrees, :year_of_experience, :specialty, :subspecialty, :location, :summary, :status) ON DUPLICATE KEY UPDATE degrees=VALUES(degrees), year_of_experience=VALUES(year_of_experience), specialty=VALUES(specialty), subspecialty=VALUES(subspecialty), location=VALUES(location), summary=VALUES(summary), status=VALUES(status), updated_at=NOW()');
    $stmt->execute([
        'doctor_id' => $auth['id'],
        'degrees' => $degrees,
        'year_of_experience' => $yearExperience,
        'specialty' => $specialty,
        'subspecialty' => $subspecialty,
        'location' => $location,
        'summary' => $summary,
        'status' => $status,
    ]);
    json_response(['success' => true, 'message' => 'Availability saved successfully']);
}

error_response('Method not allowed', 405);
