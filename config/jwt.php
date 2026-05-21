<?php
declare(strict_types=1);

// JWT_SECRET: set this as an environment variable on your server.
// For local dev, the fallback value is used. NEVER deploy with the fallback.
$_jwtSecret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: 'NMP_DEV_SECRET_CHANGE_IN_PRODUCTION_64CHARS!!';

define('JWT_SECRET',      $_jwtSecret);
define('JWT_ALG',         'HS256');
define('JWT_TTL_SECONDS', 60 * 60 * 24 * 7); // 7 days
