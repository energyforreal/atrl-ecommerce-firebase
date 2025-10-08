<?php
/**
 * 🧪 Test Order Email API with OpenSSL
 * Test the order confirmation email API with OpenSSL enabled
 */

// Enable OpenSSL extension
if (!extension_loaded('openssl')) {
    dl('C:\Program Files\php-8.4.12\ext\php_openssl.dll');
}

// Suppress warnings and errors
error_reporting(0);
ini_set('display_errors', 0);

echo "🧪 Testing Order Email API with OpenSSL\n";
echo "=====================================\n\n";

// Test data
$testData = [
    'orderId' => 'order_test_' . time(),
    'orderData' => [
        'customer' => [
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'attralsolar@gmail.com'
        ],
        'pricing' => [
            'subtotal' => 10,
            'shipping' => 0,
            'discount' => 0,
            'total' => 10,
            'currency' => 'INR'
        ],
        'razorpay_order_id' => 'order_test_' . time(),
        'razorpay_payment_id' => 'pay_test_' . time(),
        'status' => 'confirmed',
        'product' => [
            'title' => 'ATTRAL GaN Charger',
            'price' => 10
        ],
        'shipping' => [
            'address' => 'Test Address',
            'city' => 'Vellore',
            'state' => 'Tamil Nadu',
            'pincode' => '632009',
            'country' => 'India'
        ]
    ]
];

// Mock HTTP request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Mock php://input
$GLOBALS['test_input'] = json_encode($testData);
if (!function_exists('test_file_get_contents')) {
    function test_file_get_contents($filename) {
        if ($filename === 'php://input') {
            return $GLOBALS['test_input'];
        }
        return \file_get_contents($filename);
    }
}

echo "1. Testing OpenSSL extension...\n";
if (extension_loaded('openssl')) {
    echo "✅ OpenSSL extension loaded successfully\n";
} else {
    echo "❌ OpenSSL extension not loaded\n";
    exit(1);
}

echo "\n2. Testing PHPMailer directly...\n";
try {
    require_once __DIR__ . '/brevo_email_service.php';
    $emailService = new BrevoEmailService();
    
    $result = $emailService->sendTransactionalEmail(
        'attralsolar@gmail.com',
        'Test Email - ' . date('Y-m-d H:i:s'),
        '<h1>Test Email</h1><p>This is a test email sent via PHPMailer with OpenSSL.</p>',
        ['toName' => 'Test User']
    );
    
    if ($result['success']) {
        echo "✅ PHPMailer test successful\n";
    } else {
        echo "❌ PHPMailer test failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ PHPMailer test exception: " . $e->getMessage() . "\n";
}

echo "\n3. Testing Order Email API...\n";
try {
    // Capture output from send_order_email.php
    ob_start();
    include __DIR__ . '/send_order_email.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "API Response: " . $output . "\n";
    
    $response = json_decode($output, true);
    if ($response && $response['success']) {
        echo "✅ Order Email API test successful!\n";
    } else {
        echo "❌ Order Email API test failed: " . ($response['error'] ?? 'Invalid response') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Order Email API test exception: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test completed.\n";
?>
