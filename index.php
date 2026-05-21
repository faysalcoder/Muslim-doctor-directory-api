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
        'version' => '1.0.0',
        'endpoints' => [
            'POST /auth/login.php',
            'POST /auth/logout.php',
            'GET /auth/me.php',
            'GET /dashboard/stats.php',
            'GET /doctors/index.php',
            'GET /doctors/show.php?id=1',
            'POST /doctors/create.php',
            'POST /doctors/update.php',
            'POST /doctors/delete.php',
            'POST /doctors/upload-gallery.php',
            'POST /doctors/delete-image.php',
            'POST /doctors/toggle-status.php'
        ]
    ]
]);
