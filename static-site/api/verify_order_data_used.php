<?php
/**
 * 🔍 Verify Order Data Used in Test Email
 * Check what order data was used in the test email
 */

// Enable OpenSSL extension
if (!extension_loaded('openssl')) {
    dl('C:\Program Files\php-8.4.12\ext\php_openssl.dll');
}

echo "🔍 Verifying Order Data Used in Test Email\n";
echo "========================================\n\n";

echo "1. Order Data from Your Logs:\n";
echo "==============================\n";
$originalOrderData = [
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

echo "Original Order ID: " . $originalOrderData['razorpay_order_id'] . "\n";
echo "Original Payment ID: " . $originalOrderData['razorpay_payment_id'] . "\n";
echo "Original Customer: " . $originalOrderData['customer']['firstName'] . " " . $originalOrderData['customer']['lastName'] . "\n";
echo "Original Email: " . $originalOrderData['customer']['email'] . "\n";
echo "Original Total: " . $originalOrderData['pricing']['currency'] . " " . $originalOrderData['pricing']['total'] . "\n";
echo "Original Product: " . $originalOrderData['product']['title'] . "\n";
echo "Original Shipping: " . $originalOrderData['shipping']['address'] . ", " . $originalOrderData['shipping']['city'] . "\n";

echo "\n2. Test Email Data Used:\n";
echo "========================\n";
$testOrderData = [
    'email' => 'attralsolar@gmail.com',
    'firstName' => 'Test',
    'lastName' => 'User',
    'orderId' => 'TEST_' . date('YmdHis'),
    'paymentId' => 'PAY_' . date('YmdHis'),
    'total' => 10,
    'currency' => 'INR',
    'product' => [
        'title' => 'ATTRAL GaN Charger',
        'price' => 10
    ],
    'shipping' => [
        'address' => '123 Test Street',
        'city' => 'Vellore',
        'state' => 'Tamil Nadu',
        'pincode' => '632009',
        'country' => 'India'
    ]
];

echo "Test Order ID: " . $testOrderData['orderId'] . "\n";
echo "Test Payment ID: " . $testOrderData['paymentId'] . "\n";
echo "Test Customer: " . $testOrderData['firstName'] . " " . $testOrderData['lastName'] . "\n";
echo "Test Email: " . $testOrderData['email'] . "\n";
echo "Test Total: " . $testOrderData['currency'] . " " . $testOrderData['total'] . "\n";
echo "Test Product: " . $testOrderData['product']['title'] . "\n";
echo "Test Shipping: " . $testOrderData['shipping']['address'] . ", " . $testOrderData['shipping']['city'] . "\n";

echo "\n3. Comparison:\n";
echo "==============\n";
echo "✅ Customer Name: " . ($originalOrderData['customer']['firstName'] === $testOrderData['firstName'] && $originalOrderData['customer']['lastName'] === $testOrderData['lastName'] ? 'MATCH' : 'DIFFERENT') . "\n";
echo "✅ Customer Email: " . ($originalOrderData['customer']['email'] === $testOrderData['email'] ? 'MATCH' : 'DIFFERENT') . "\n";
echo "✅ Product: " . ($originalOrderData['product']['title'] === $testOrderData['product']['title'] ? 'MATCH' : 'DIFFERENT') . "\n";
echo "✅ Price: " . ($originalOrderData['product']['price'] == $testOrderData['product']['price'] ? 'MATCH' : 'DIFFERENT') . "\n";
echo "✅ Total: " . ($originalOrderData['pricing']['total'] == $testOrderData['total'] ? 'MATCH' : 'DIFFERENT') . "\n";
echo "✅ Currency: " . ($originalOrderData['pricing']['currency'] === $testOrderData['currency'] ? 'MATCH' : 'DIFFERENT') . "\n";
echo "✅ Shipping Address: " . ($originalOrderData['shipping']['address'] === $testOrderData['shipping']['address'] ? 'MATCH' : 'DIFFERENT') . "\n";
echo "✅ Shipping City: " . ($originalOrderData['shipping']['city'] === $testOrderData['shipping']['city'] ? 'MATCH' : 'DIFFERENT') . "\n";
echo "✅ Shipping State: " . ($originalOrderData['shipping']['state'] === $testOrderData['shipping']['state'] ? 'MATCH' : 'DIFFERENT') . "\n";
echo "✅ Shipping Pincode: " . ($originalOrderData['shipping']['pincode'] === $testOrderData['shipping']['pincode'] ? 'MATCH' : 'DIFFERENT') . "\n";
echo "✅ Shipping Country: " . ($originalOrderData['shipping']['country'] === $testOrderData['shipping']['country'] ? 'MATCH' : 'DIFFERENT') . "\n";

echo "\n4. Sending Email with EXACT Original Order Data:\n";
echo "================================================\n";

try {
    require_once __DIR__ . '/brevo_email_service.php';
    $emailService = new BrevoEmailService();
    
    // Use EXACT data from your logs
    $exactOrderData = [
        'email' => $originalOrderData['customer']['email'],
        'firstName' => $originalOrderData['customer']['firstName'],
        'lastName' => $originalOrderData['customer']['lastName'],
        'orderId' => $originalOrderData['razorpay_order_id'],
        'paymentId' => $originalOrderData['razorpay_payment_id'],
        'total' => $originalOrderData['pricing']['total'],
        'currency' => $originalOrderData['pricing']['currency'],
        'product' => $originalOrderData['product'],
        'shipping' => $originalOrderData['shipping']
    ];
    
    echo "Sending email with EXACT order data from your logs...\n";
    echo "Order ID: " . $exactOrderData['orderId'] . "\n";
    echo "Payment ID: " . $exactOrderData['paymentId'] . "\n";
    echo "Customer: " . $exactOrderData['firstName'] . " " . $exactOrderData['lastName'] . "\n";
    echo "Email: " . $exactOrderData['email'] . "\n";
    echo "Total: " . $exactOrderData['currency'] . " " . $exactOrderData['total'] . "\n";
    echo "Product: " . $exactOrderData['product']['title'] . "\n";
    echo "Shipping: " . $exactOrderData['shipping']['address'] . ", " . $exactOrderData['shipping']['city'] . "\n";
    
    $result = $emailService->sendOrderConfirmation($exactOrderData);
    
    if ($result['success']) {
        echo "\n✅ Email sent successfully with EXACT order data from your logs!\n";
        echo "📧 Check your inbox for: Order Confirmation - " . $exactOrderData['orderId'] . "\n";
    } else {
        echo "\n❌ Email failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n🏁 Order Data Verification Completed.\n";
echo "\n📧 You should now receive an email with the EXACT order data from your logs:\n";
echo "   - Order ID: order_RPIFJ6HE8WPZz8\n";
echo "   - Payment ID: pay_RPIFYEOM0e0yiD\n";
echo "   - Customer: Test User\n";
echo "   - Email: attralsolar@gmail.com\n";
echo "   - Total: INR 10\n";
echo "   - Product: ATTRAL GaN Charger\n";
echo "   - Shipping: 123 Test Street, Vellore, Tamil Nadu 632009, India\n";
?>
