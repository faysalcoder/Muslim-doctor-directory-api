<?php
declare(strict_types=1);

/**
 * GET /doctors/filters.php
 * Public — returns distinct specialties and locations from verified doctors.
 * Used by the frontend search dropdowns so they always reflect real DB data.
 */

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    error_response('Method not allowed', 405);
}

$pdo = db();

$specialties = $pdo
    ->query("SELECT DISTINCT specialty FROM doctors WHERE specialty != '' AND status = 'verified' ORDER BY specialty ASC")
    ->fetchAll(PDO::FETCH_COLUMN);

$locations = $pdo
    ->query("SELECT DISTINCT location FROM doctors WHERE location != '' AND status = 'verified' ORDER BY location ASC")
    ->fetchAll(PDO::FETCH_COLUMN);

json_response([
    'success'     => true,
    'specialties' => array_values(array_filter($specialties)),
    'locations'   => array_values(array_filter($locations)),
]);
