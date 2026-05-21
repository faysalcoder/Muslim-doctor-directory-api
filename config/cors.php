<?php
// config/cors.php — kept for any file that requires it directly.
// The canonical implementation is in helpers/cors.php → send_cors_headers().
require_once __DIR__ . '/../helpers/cors.php';
send_cors_headers();
