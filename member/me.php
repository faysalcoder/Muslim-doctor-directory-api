<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    error_response('Method not allowed', 405);
}

try {
    $payload = require_member();
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT id, name, title, academic_title, academic_affiliation,
                medical_school_affiliation, specialty, subspecialty,
                graduation_degree, graduation_year,
                location, practice, address, hospital_affiliations,
                phone, email, gender, languages,
                bio, education, experience, awards,
                accepting_patients, profile_image,
                status, account_type, profile_visibility,
                created_at, updated_at
         FROM doctors WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $payload['id']]);
    $user = $stmt->fetch();

    if (!$user) {
        error_response('Profile not found', 404);
    }

    $statsStmt = $pdo->prepare('SELECT
        (SELECT COUNT(*) FROM forum_posts       WHERE doctor_id = :id1) AS forum_posts,
        (SELECT COUNT(*) FROM forum_comments    WHERE doctor_id = :id2) AS forum_comments,
        (SELECT COUNT(*) FROM job_posts         WHERE doctor_id = :id3) AS job_posts,
        (SELECT COUNT(*) FROM doctor_availability WHERE doctor_id = :id4) AS availability
    ');
    $statsStmt->execute([
        'id1' => $payload['id'],
        'id2' => $payload['id'],
        'id3' => $payload['id'],
        'id4' => $payload['id'],
    ]);
    $stats = $statsStmt->fetch() ?: [];

    unset($user['password_hash']);
    $user['doctor_id'] = $user['id'];

    // Null-safe the new column in case the migration hasn't run yet
    $user['academic_affiliation'] = $user['academic_affiliation'] ?? '';

    json_response([
        'success' => true,
        'data' => [
            'profile' => $user,
            'stats' => [
                'forum_posts'     => (int)($stats['forum_posts']     ?? 0),
                'forum_comments'  => (int)($stats['forum_comments']  ?? 0),
                'job_posts'       => (int)($stats['job_posts']       ?? 0),
                'availability'    => (int)($stats['availability']    ?? 0),
            ],
        ],
    ]);
} catch (Throwable $e) {
    error_response('Failed to load member profile', 500, [
        'error' => $e->getMessage(),
    ]);
}
