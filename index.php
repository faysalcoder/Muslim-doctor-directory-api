<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers/cors.php';
require_once __DIR__ . '/helpers/response.php';

send_cors_headers();

json_response([
    'success' => true,
    'message' => 'Network of Muslim Physicians API is running',
    'data' => [
        'name' => 'Network of Muslim Physicians API',
        'version' => '2.0.0',
        'endpoints' => [
            'POST /auth/login.php',
            'POST /auth/register.php',
            'POST /auth/member-login.php',
            'GET /auth/me.php',
            'GET /member/me.php',
            'POST /member/update.php',
            'POST /member/change-password.php',
            'GET /dashboard/stats.php',
            'GET /doctors/all.php',
            'GET /doctors/show.php?id=1',
            'POST /doctors/create.php',
            'POST /doctors/update.php',
            'POST /doctors/delete.php',
            'POST /doctors/upload-gallery.php',
            'POST /doctors/delete-image.php',
            'POST /doctors/toggle-status.php',
            'GET /forum/posts.php',
            'POST /forum/posts.php',
            'GET /forum/post.php?id=1',
            'GET /forum/comments.php?post_id=1',
            'POST /forum/comments.php',
            'GET /forum/admin.php',
            'POST /forum/admin.php',
            'GET /jobs/posts.php',
            'POST /jobs/posts.php',
            'GET /jobs/post.php?id=1',
            'GET /jobs/availability.php',
            'POST /jobs/availability.php',
            'GET /jobs/admin.php',
            'POST /jobs/admin.php'
        ]
    ]
]);
