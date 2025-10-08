<?php
/**
 * 🧪 Simulate Order Success Environment Test
 * Reproduce the exact environment from order-success.html logs
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

echo "🧪 Simulating Order Success Environment\n";
echo "=====================================\n\n";

// Exact order data from the logs
$orderData = [
    'customer' => [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'attralsolar@gmail.com',
        'phone' => '+91 9876543210'
    ],
    'shipping' => [
        'address' => '123 Test Street',
        'city' => 'Vellore',
        'state' => 'Tamil Nadu',
        'pincode' => '632009',
        'country' => 'India'
    ],
    'product' => [
        'title' => 'ATTRAL GaN Charger',
        'price' => 10,
        'quantity' => 1
    ],
    'pricing' => [
        'subtotal' => 10,
        'shipping' => 0,
        'discount' => 0,
        'total' => 10,
        'currency' => 'INR'
    ],
    'coupons' => [],
    'orderType' => 'cart',
    'timestamp' => '2025-10-04T06:15:23.944Z',
    'razorpay_order_id' => 'order_RPIFJ6HE8WPZz8',
    'razorpay_payment_id' => 'pay_RPIFYEOM0e0yiD',
    'razorpay_signature' => '61f9c803cec64bd4dc367d318be414dd5467000546ac40cb065c3ea30a9e52d9',
    'status' => 'confirmed'
];

$orderId = 'order_RPIFJ6HE8WPZz8';

echo "1. Environment Setup...\n";
echo "✅ OpenSSL extension: " . (extension_loaded('openssl') ? 'Loaded' : 'Not loaded') . "\n";
echo "✅ Order ID: $orderId\n";
echo "✅ Customer Email: " . $orderData['customer']['email'] . "\n";
echo "✅ Order Total: " . $orderData['pricing']['currency'] . " " . $orderData['pricing']['total'] . "\n";

echo "\n2. Simulating order-success.html email API call...\n";

// Simulate the exact API call from order-success.html
$apiPayload = [
    'orderId' => $orderId,
    'orderData' => $orderData
];

echo "API Payload:\n";
echo json_encode($apiPayload, JSON_PRETTY_PRINT) . "\n\n";

// Test the email API endpoint
try {
    // Mock the HTTP request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    // Mock php://input
    $GLOBALS['test_input'] = json_encode($apiPayload);
    if (!function_exists('test_file_get_contents')) {
        function test_file_get_contents($filename) {
            if ($filename === 'php://input') {
                return $GLOBALS['test_input'];
            }
            return \file_get_contents($filename);
        }
    }
    
    // Override file_get_contents for testing
    if (!function_exists('original_file_get_contents')) {
        function original_file_get_contents($filename) {
            return \file_get_contents($filename);
        }
    }
    
    echo "3. Testing send_order_email.php API...\n";
    
    // Capture output from send_order_email.php
    ob_start();
    
    // Include the API endpoint
    include __DIR__ . '/send_order_email.php';
    
    $apiOutput = ob_get_contents();
    ob_end_clean();
    
    echo "API Response: " . $apiOutput . "\n\n";
    
    $response = json_decode($apiOutput, true);
    if ($response && $response['success']) {
        echo "✅ Order Email API test successful!\n";
        echo "   Email sent to: " . $orderData['customer']['email'] . "\n";
        echo "   Order ID: $orderId\n";
    } else {
        echo "❌ Order Email API test failed: " . ($response['error'] ?? 'Invalid response') . "\n";
        echo "   Raw response: " . $apiOutput . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Order Email API test exception: " . $e->getMessage() . "\n";
}

echo "\n4. Testing direct email service...\n";
try {
    require_once __DIR__ . '/brevo_email_service.php';
    $emailService = new BrevoEmailService();
    
    // Prepare email data similar to OrderEmailSender
    $emailData = [
        'email' => $orderData['customer']['email'],
        'firstName' => $orderData['customer']['firstName'],
        'lastName' => $orderData['customer']['lastName'],
        'orderId' => $orderId,
        'paymentId' => $orderData['razorpay_payment_id'],
        'total' => $orderData['pricing']['total'],
        'currency' => $orderData['pricing']['currency'],
        'product' => $orderData['product'],
        'shipping' => $orderData['shipping']
    ];
    
    $result = $emailService->sendOrderConfirmation($emailData);
    
    if ($result['success']) {
        echo "✅ Direct email service test successful!\n";
        echo "   Email sent to: " . $orderData['customer']['email'] . "\n";
    } else {
        echo "❌ Direct email service test failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Direct email service test exception: " . $e->getMessage() . "\n";
}

echo "\n5. Testing OrderEmailSender class directly...\n";
try {
    require_once __DIR__ . '/send_order_email.php';
    
    $sender = new OrderEmailSender();
    $result = $sender->sendOrderConfirmationEmailWithData($orderId, $orderData);
    
    if ($result['success']) {
        echo "✅ OrderEmailSender test successful!\n";
        echo "   Email sent to: " . $orderData['customer']['email'] . "\n";
    } else {
        echo "❌ OrderEmailSender test failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ OrderEmailSender test exception: " . $e->getMessage() . "\n";
}

echo "\n🏁 Order Success Environment Simulation Completed.\n";
echo "\n📧 Check your email inbox (attralsolar@gmail.com) for test emails.\n";
echo "📊 Order ID: $orderId\n";
echo "💰 Total: " . $orderData['pricing']['currency'] . " " . $orderData['pricing']['total'] . "\n";

// Return JSON response for web requests
if (php_sapi_name() !== 'cli') {
    echo json_encode([
        'success' => true,
        'message' => 'Order success environment simulation completed',
        'order_id' => $orderId,
        'customer_email' => $orderData['customer']['email'],
        'openssl_enabled' => extension_loaded('openssl'),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
