<?php

header('Content-Type: application/json');

$status = 'ok';
$checks = array();

try {
    require_once __DIR__ . '/classes/PDO.class.php';
    $dbh = PDO_DB::factory();
    $stmt = $dbh->query('SELECT 1');
    $stmt->fetch();
    $checks['database'] = 'ok';
} catch (Exception $e) {
    $checks['database'] = 'error';
    $status = 'error';
}

$checks['php'] = 'ok';

http_response_code($status === 'ok' ? 200 : 503);

echo json_encode(array(
    'status' => $status,
    'checks' => $checks,
    'timestamp' => date('c'),
));
