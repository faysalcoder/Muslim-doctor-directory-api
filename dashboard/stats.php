<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    error_response('Method not allowed', 405);
}

try {
    $pdo = db();
    $totalDoctors = (int)$pdo->query('SELECT COUNT(*) FROM doctors')->fetchColumn();
    $verifiedDoctors = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'verified'")->fetchColumn();
    $pendingDoctors = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'pending'")->fetchColumn();
    $inactiveDoctors = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'inactive'")->fetchColumn();
    $totalForumPosts = (int)$pdo->query('SELECT COUNT(*) FROM forum_posts')->fetchColumn();
    $totalForumComments = (int)$pdo->query('SELECT COUNT(*) FROM forum_comments')->fetchColumn();
    $totalJobs = (int)$pdo->query('SELECT COUNT(*) FROM job_posts')->fetchColumn();
    $totalAvailability = (int)$pdo->query('SELECT COUNT(*) FROM doctor_availability')->fetchColumn();

    $latestDoctors = $pdo->query('SELECT id, name, specialty, status, profile_image, created_at FROM doctors ORDER BY id DESC LIMIT 5')->fetchAll();
    foreach ($latestDoctors as &$doc) {
        $doc['doctor_id'] = $doc['id'];
    }
    unset($doc);

    $latestForum = $pdo->query('SELECT id, title, type, status, created_at FROM forum_posts ORDER BY id DESC LIMIT 5')->fetchAll();
    $latestJobs = $pdo->query('SELECT id, post_name, job_location, status, created_at FROM job_posts ORDER BY id DESC LIMIT 5')->fetchAll();

    json_response([
        'success' => true,
        'stats' => [
            'totalDoctors' => $totalDoctors,
            'verified' => $verifiedDoctors,
            'pending' => $pendingDoctors,
            'inactive' => $inactiveDoctors,
            'forumPosts' => $totalForumPosts,
            'forumComments' => $totalForumComments,
            'jobs' => $totalJobs,
            'availability' => $totalAvailability,
        ],
        'latestDoctors' => $latestDoctors,
        'latestForum' => $latestForum,
        'latestJobs' => $latestJobs,
    ]);
} catch (Throwable $e) {
    error_response('Failed to load dashboard stats', 500, [
        'error' => $e->getMessage(),
    ]);
}
