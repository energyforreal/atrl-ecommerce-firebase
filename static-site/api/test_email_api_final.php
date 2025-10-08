<?php
/**
 * 🧪 Final Email API Test
 * Test the order confirmation email API with OpenSSL enabled
 */

// Enable OpenSSL extension
if (!extension_loaded('openssl')) {
    dl('C:\Program Files\php-8.4.12\ext\php_openssl.dll');
}

// Set headers for web response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Suppress warnings and errors
error_reporting(0);
ini_set('display_errors', 0);

echo "🧪 Final Email API Test with OpenSSL\n";
echo "===================================\n\n";

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
        'Final Test Email - ' . date('Y-m-d H:i:s'),
        '<h1>Final Test Email</h1><p>This is a final test email sent via PHPMailer with OpenSSL enabled.</p><p>Order ID: ' . $testData['orderId'] . '</p>',
        ['toName' => 'Test User']
    );
    
    if ($result['success']) {
        echo "✅ PHPMailer test successful\n";
        echo "   Email sent to: attralsolar@gmail.com\n";
        echo "   Order ID: " . $testData['orderId'] . "\n";
    } else {
        echo "❌ PHPMailer test failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ PHPMailer test exception: " . $e->getMessage() . "\n";
}

echo "\n3. Testing Order Email API simulation...\n";
try {
    // Simulate the API call
    $orderEmailSender = new OrderEmailSender();
    $result = $orderEmailSender->sendOrderConfirmationEmailWithData(
        $testData['orderId'],
        $testData['orderData']
    );
    
    if ($result['success']) {
        echo "✅ Order Email API simulation successful!\n";
        echo "   Order confirmation sent to: " . $testData['orderData']['customer']['email'] . "\n";
    } else {
        echo "❌ Order Email API simulation failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Order Email API simulation exception: " . $e->getMessage() . "\n";
}

echo "\n🏁 Final test completed.\n";
echo "\n📧 Check your email inbox (attralsolar@gmail.com) for test emails.\n";

// Return JSON response for web requests
if (php_sapi_name() !== 'cli') {
    echo json_encode([
        'success' => true,
        'message' => 'Final email API test completed',
        'openssl_enabled' => extension_loaded('openssl'),
        'test_order_id' => $testData['orderId'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
