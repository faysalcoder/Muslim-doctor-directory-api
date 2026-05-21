<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Test 1: Can PHP run?
$result = ['php' => 'ok', 'php_version' => PHP_VERSION];

// Test 2: Can we connect to MySQL?
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=nomp;charset=utf8mb4',
        'admin',   // change if yours is different
        'admin123',       // change if yours is different
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $result['database'] = 'connected';

    // Test 3: Does the doctors table exist?
    $count = $pdo->query('SELECT COUNT(*) FROM doctors')->fetchColumn();
    $result['doctors_table'] = 'ok';
    $result['doctor_count']  = (int)$count;

} catch (PDOException $e) {
    $result['database'] = 'FAILED';
    $result['db_error']  = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);