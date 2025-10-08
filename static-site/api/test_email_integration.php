<?php
/**
 * 🧪 Email Integration Test
 * Test all email-related APIs
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$tests = [];

// Test 1: Check if required files exist
$tests['files_exist'] = [
    'send_order_email.php' => file_exists(__DIR__ . '/send_order_email.php'),
    'generate_invoice.php' => file_exists(__DIR__ . '/generate_invoice.php'),
    'brevo_email_service.php' => file_exists(__DIR__ . '/brevo_email_service.php'),
    'config.php' => file_exists(__DIR__ . '/config.php'),
    'firebase-service-account.json' => file_exists(__DIR__ . '/firebase-service-account.json'),
    'orders.db' => file_exists(__DIR__ . '/orders.db')
];

// Test 2: Check PHP extensions
$tests['php_extensions'] = [
    'pdo_sqlite' => extension_loaded('pdo_sqlite'),
    'json' => extension_loaded('json'),
    'curl' => extension_loaded('curl'),
    'openssl' => extension_loaded('openssl')
];

// Test 3: Check classes
$tests['classes_available'] = [
    'BrevoEmailService' => class_exists('BrevoEmailService'),
    'OrderEmailSender' => class_exists('OrderEmailSender'),
    'InvoiceGenerator' => class_exists('InvoiceGenerator'),
    'Firebase\Factory' => class_exists('\Kreait\Firebase\Factory')
];

// Test 4: Test input parsing
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$tests['input_parsing'] = [
    'raw_input' => $rawInput,
    'parsed_input' => $input,
    'json_decode_success' => $input !== null,
    'has_orderId' => isset($input['orderId'])
];

// Test 5: Test database connection
$dbConnected = false;
$dbError = null;
if (extension_loaded('pdo_sqlite') && file_exists(__DIR__ . '/orders.db')) {
    try {
        $pdo = new PDO("sqlite:" . __DIR__ . '/orders.db');
        $dbConnected = true;
        
        // Test if orders table exists
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='orders'");
        $tableExists = $stmt->fetch() !== false;
        $tests['database'] = [
            'connected' => true,
            'orders_table_exists' => $tableExists
        ];
    } catch (Exception $e) {
        $dbError = $e->getMessage();
        $tests['database'] = [
            'connected' => false,
            'error' => $dbError
        ];
    }
} else {
    $tests['database'] = [
        'connected' => false,
        'error' => 'SQLite extension not available or database file not found'
    ];
}

// Test 6: Check configuration
try {
    $config = include __DIR__ . '/config.php';
    $tests['configuration'] = [
        'config_loaded' => true,
        'has_razorpay_key' => !empty($config['RAZORPAY_KEY_SECRET'] ?? ''),
        'has_brevo_key' => !empty($config['BREVO_API_KEY'] ?? ''),
        'local_mode' => $config['LOCAL_MODE'] ?? false
    ];
} catch (Exception $e) {
    $tests['configuration'] = [
        'config_loaded' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode([
    'success' => true,
    'message' => 'Email integration test completed',
    'tests' => $tests,
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
], JSON_PRETTY_PRINT);
?>
