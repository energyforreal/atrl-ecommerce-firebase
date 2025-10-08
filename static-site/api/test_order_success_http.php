<?php
/**
 * 🧪 Test Order Success via HTTP
 * Test the email API via HTTP request like order-success.html does
 */

// Enable OpenSSL extension
if (!extension_loaded('openssl')) {
    dl('C:\Program Files\php-8.4.12\ext\php_openssl.dll');
}

echo "🧪 Testing Order Success Email via HTTP\n";
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

echo "\n2. Testing HTTP request to send_order_email.php...\n";

// Prepare the HTTP request data
$requestData = [
    'orderId' => $orderId,
    'orderData' => $orderData
];

$jsonData = json_encode($requestData);

echo "Request Data:\n";
echo $jsonData . "\n\n";

// Make HTTP request to the API endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/send_order_email.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "3. HTTP Response...\n";
echo "HTTP Code: $httpCode\n";
if ($curlError) {
    echo "CURL Error: $curlError\n";
}
echo "Response: $response\n\n";

if ($response) {
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        echo "✅ HTTP API test successful!\n";
        echo "   Email sent to: " . $orderData['customer']['email'] . "\n";
        echo "   Order ID: $orderId\n";
    } else {
        echo "❌ HTTP API test failed: " . ($result['error'] ?? 'Invalid response') . "\n";
    }
} else {
    echo "❌ No response received from API\n";
}

echo "\n4. Testing direct email service as fallback...\n";
try {
    require_once __DIR__ . '/brevo_email_service.php';
    $emailService = new BrevoEmailService();
    
    // Prepare email data
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

echo "\n🏁 Order Success HTTP Test Completed.\n";
echo "\n📧 Check your email inbox (attralsolar@gmail.com) for test emails.\n";
?>
