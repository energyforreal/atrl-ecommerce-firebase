<?php
/**
 * 🧪 Test Exact Order Data from Logs
 * Test email functionality with the exact order data from order-success.html logs
 */

// Enable OpenSSL extension
if (!extension_loaded('openssl')) {
    dl('C:\Program Files\php-8.4.12\ext\php_openssl.dll');
}

echo "🧪 Testing Exact Order Data from Logs\n";
echo "===================================\n\n";

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

echo "\n2. Testing BrevoEmailService directly...\n";
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
    
    echo "Sending order confirmation email...\n";
    $result = $emailService->sendOrderConfirmation($emailData);
    
    if ($result['success']) {
        echo "✅ BrevoEmailService test successful!\n";
        echo "   Email sent to: " . $orderData['customer']['email'] . "\n";
        echo "   Order ID: $orderId\n";
        echo "   Payment ID: " . $orderData['razorpay_payment_id'] . "\n";
        echo "   Total: " . $orderData['pricing']['currency'] . " " . $orderData['pricing']['total'] . "\n";
    } else {
        echo "❌ BrevoEmailService test failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ BrevoEmailService test exception: " . $e->getMessage() . "\n";
}

echo "\n3. Testing OrderEmailSender class...\n";
try {
    require_once __DIR__ . '/send_order_email.php';
    
    $sender = new OrderEmailSender();
    echo "Sending order confirmation email via OrderEmailSender...\n";
    $result = $sender->sendOrderConfirmationEmailWithData($orderId, $orderData);
    
    if ($result['success']) {
        echo "✅ OrderEmailSender test successful!\n";
        echo "   Email sent to: " . $orderData['customer']['email'] . "\n";
        echo "   Order ID: $orderId\n";
    } else {
        echo "❌ OrderEmailSender test failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ OrderEmailSender test exception: " . $e->getMessage() . "\n";
}

echo "\n4. Testing direct transactional email...\n";
try {
    $emailService = new BrevoEmailService();
    
    $subject = "Order Confirmation - $orderId ✅";
    $htmlContent = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2>Order Confirmation</h2>
        <p>Dear " . $orderData['customer']['firstName'] . ",</p>
        <p>Thank you for your order! Your order has been confirmed.</p>
        <p><strong>Order ID:</strong> $orderId</p>
        <p><strong>Payment ID:</strong> " . $orderData['razorpay_payment_id'] . "</p>
        <p><strong>Total:</strong> " . $orderData['pricing']['currency'] . " " . $orderData['pricing']['total'] . "</p>
        <p><strong>Product:</strong> " . $orderData['product']['title'] . "</p>
        <p>Best regards,<br>ATTRAL Team</p>
    </div>";
    
    $result = $emailService->sendTransactionalEmail(
        $orderData['customer']['email'],
        $subject,
        $htmlContent,
        ['toName' => $orderData['customer']['firstName'] . ' ' . $orderData['customer']['lastName']]
    );
    
    if ($result['success']) {
        echo "✅ Direct transactional email test successful!\n";
        echo "   Email sent to: " . $orderData['customer']['email'] . "\n";
        echo "   Subject: $subject\n";
    } else {
        echo "❌ Direct transactional email test failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Direct transactional email test exception: " . $e->getMessage() . "\n";
}

echo "\n🏁 Exact Order Data Test Completed.\n";
echo "\n📧 Check your email inbox (attralsolar@gmail.com) for test emails.\n";
echo "📊 Order ID: $orderId\n";
echo "💰 Total: " . $orderData['pricing']['currency'] . " " . $orderData['pricing']['total'] . "\n";
echo "📦 Product: " . $orderData['product']['title'] . "\n";
echo "🏠 Shipping: " . $orderData['shipping']['address'] . ", " . $orderData['shipping']['city'] . "\n";
?>
