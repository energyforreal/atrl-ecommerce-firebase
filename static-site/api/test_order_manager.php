<?php
/**
 * 🔧 Test Order Manager - Debug Tool
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Test Firebase SDK availability
$firebaseAvailable = class_exists('\Kreait\Firebase\Factory');
$serviceAccountExists = file_exists(__DIR__ . '/firebase-service-account.json');

// Test SQLite availability
$sqliteAvailable = extension_loaded('pdo_sqlite');
$dbExists = file_exists(__DIR__ . '/orders.db');

// Test database connection
$dbConnected = false;
if ($sqliteAvailable && $dbExists) {
    try {
        $pdo = new PDO("sqlite:" . __DIR__ . '/orders.db');
        $dbConnected = true;
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}

// Test input parsing
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

echo json_encode([
    'success' => true,
    'debug_info' => [
        'firebase_sdk_available' => $firebaseAvailable,
        'service_account_exists' => $serviceAccountExists,
        'sqlite_available' => $sqliteAvailable,
        'db_file_exists' => $dbExists,
        'db_connected' => $dbConnected,
        'db_error' => $dbError ?? null,
        'raw_input' => $rawInput,
        'parsed_input' => $input,
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? '',
        'php_version' => PHP_VERSION,
        'extensions' => get_loaded_extensions()
    ]
]);
?>
