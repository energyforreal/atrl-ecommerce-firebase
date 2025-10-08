<?php
/**
 * Debug Send Email API
 * Debug version of send_order_email.php
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

echo json_encode([
    'success' => true,
    'debug' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'raw_input' => file_get_contents('php://input'),
        'input_length' => strlen(file_get_contents('php://input')),
        'json_decode_result' => json_decode(file_get_contents('php://input'), true),
        'timestamp' => date('Y-m-d H:i:s')
    ]
]);
?>
