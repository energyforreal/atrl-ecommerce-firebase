<?php
/**
 * Fulfillment Status Email Sender API
 * Sends emails when fulfillment status changes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (!isset($input['orderId'])) {
        throw new Exception('orderId is required');
    }
    
    if (!isset($input['fulfillmentStatus'])) {
        throw new Exception('fulfillmentStatus is required');
    }
    
    if (!isset($input['customerEmail'])) {
        throw new Exception('customerEmail is required');
    }
    
    // Load PHPMailer
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($composerAutoload)) {
        require_once $composerAutoload;
    }
    $vendoredSrc = __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') && file_exists($vendoredSrc)) {
        require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';
        require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';
    }
    
    // Load config
    $cfg = include __DIR__ . '/config.php';
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    // Server settings - Brevo SMTP (without TLS for testing)
    $mail->isSMTP();
    $mail->Host = $cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com';
    $mail->SMTPAuth = true;
    $mail->Username = $cfg['SMTP_USERNAME'] ?? '8c9aee002@smtp-brevo.com';
    $mail->Password = $cfg['SMTP_PASSWORD'] ?? '';
    // $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // Disabled due to missing OpenSSL
    $mail->Port = intval($cfg['SMTP_PORT'] ?? 587);
    
    // Recipients
    $mail->setFrom($cfg['MAIL_FROM'] ?? 'info@attral.in', $cfg['MAIL_FROM_NAME'] ?? 'ATTRAL Electronics');
    
    // Get customer details
    $customerEmail = $input['customerEmail'];
    $customerName = $input['customerName'] ?? 'Customer';
    $orderId = $input['orderId'];
    $fulfillmentStatus = $input['fulfillmentStatus'];
    
    $mail->addAddress($customerEmail, $customerName);
    
    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    // Generate professional email content using the template function
    $emailContent = generateFulfillmentEmailContent($orderId, $fulfillmentStatus, $customerName, $input);
    
    $mail->Subject = $emailContent['subject'];
    $mail->Body = $emailContent['body'];
    $mail->AltBody = $emailContent['altBody'];
    
    // Send the email
    try {
        $mail->send();
    } catch (Exception $e) {
        throw new Exception('Failed to send email: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Fulfillment status email sent successfully',
        'orderId' => $orderId,
        'fulfillmentStatus' => $fulfillmentStatus,
        'timestamp' => date('Y-m-d H:i:s'),
        'recipient' => $customerEmail,
        'customerName' => $customerName
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Generate email content based on fulfillment status
 */
function generateFulfillmentEmailContent($orderId, $status, $customerName, $input) {
    $statusInfo = getStatusInfo($status);
    $timestamp = date('Y-m-d H:i:s');
    
    $subject = $statusInfo['subject'] . ' - Order #' . $orderId;
    
    // Extract additional order details if available
    $productTitle = $input['productTitle'] ?? 'Your Product';
    $trackingNumber = $input['trackingNumber'] ?? '';
    $estimatedDelivery = $input['estimatedDelivery'] ?? '';
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Status Update</title>
    </head>
    <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
        <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h1 style="color: ' . $statusInfo['color'] . '; text-align: center; margin-bottom: 30px;">' . $statusInfo['icon'] . ' ' . $statusInfo['title'] . '</h1>
            
            <div style="background-color: ' . $statusInfo['bgColor'] . '; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="color: ' . $statusInfo['color'] . '; margin-top: 0;">Order Information:</h2>
                <p><strong>Order ID:</strong> ' . htmlspecialchars($orderId) . '</p>
                <p><strong>Customer:</strong> ' . htmlspecialchars($customerName) . '</p>
                <p><strong>Product:</strong> ' . htmlspecialchars($productTitle) . '</p>
                <p><strong>Status:</strong> ' . htmlspecialchars($statusInfo['displayName']) . '</p>
                <p><strong>Updated:</strong> ' . $timestamp . '</p>
                ' . ($input['razorpayPaymentId'] ? '<p><strong>Payment ID:</strong> ' . htmlspecialchars($input['razorpayPaymentId']) . '</p>' : '') . '
                ' . ($input['razorpayOrderId'] ? '<p><strong>Transaction ID:</strong> ' . htmlspecialchars($input['razorpayOrderId']) . '</p>' : '') . '
                ' . ($trackingNumber ? '<p><strong>Tracking Number:</strong> ' . htmlspecialchars($trackingNumber) . '</p>' : '') . '
                ' . ($estimatedDelivery ? '<p><strong>Estimated Delivery:</strong> ' . htmlspecialchars($estimatedDelivery) . '</p>' : '') . '
            </div>
            
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #495057; margin-top: 0;">What happens next?</h3>
                <p>' . $statusInfo['description'] . '</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <p style="color: #666; font-size: 14px;">Thank you for choosing ATTRAL Electronics!</p>
                <p style="color: #666; font-size: 12px;">ATTRAL Electronics | info@attral.in | +91 8903479870</p>
            </div>
        </div>
    </body>
    </html>';
    
    $altBody = $statusInfo['title'] . ' - Order #' . $orderId . ' - Customer: ' . $customerName . ' - Product: ' . $productTitle . ' - Status: ' . $statusInfo['displayName'] . ' - Updated: ' . $timestamp . ($input['razorpayPaymentId'] ? ' - Payment ID: ' . $input['razorpayPaymentId'] : '') . ($input['razorpayOrderId'] ? ' - Transaction ID: ' . $input['razorpayOrderId'] : '') . ($trackingNumber ? ' - Tracking: ' . $trackingNumber : '') . ($estimatedDelivery ? ' - Estimated Delivery: ' . $estimatedDelivery : '');
    
    return [
        'subject' => $subject,
        'body' => $body,
        'altBody' => $altBody
    ];
}

/**
 * Get status information for different fulfillment statuses
 */
function getStatusInfo($status) {
    $statusMap = [
        'yet-to-dispatch' => [
            'icon' => '📦',
            'title' => 'Order Processing',
            'displayName' => 'Yet to Dispatch',
            'color' => '#ff9800',
            'bgColor' => '#fff3e0',
            'subject' => '📦 Your Order is Being Processed',
            'description' => 'We have received your order and our team is preparing it for shipment. You will receive another update once your order is ready for dispatch.'
        ],
        'ready-to-dispatch' => [
            'icon' => '🚚',
            'title' => 'Ready for Dispatch',
            'displayName' => 'Ready for Dispatch',
            'color' => '#2196f3',
            'bgColor' => '#e3f2fd',
            'subject' => '🚚 Your Order is Ready for Dispatch',
            'description' => 'Great news! Your order has been packed and is ready to be dispatched. It will be handed over to our delivery partner shortly.'
        ],
        'shipped' => [
            'icon' => '🚚',
            'title' => 'Order Shipped',
            'displayName' => 'Shipped',
            'color' => '#2196f3',
            'bgColor' => '#e3f2fd',
            'subject' => '🚚 Your Order has been Shipped',
            'description' => 'Your order has been shipped and is on its way to you. You can track your package using the tracking information provided.'
        ],
        'out-for-delivery' => [
            'icon' => '🚛',
            'title' => 'Out for Delivery',
            'displayName' => 'Out for Delivery',
            'color' => '#4caf50',
            'bgColor' => '#e8f5e8',
            'subject' => '🚛 Your Order is Out for Delivery',
            'description' => 'Exciting! Your order is now out for delivery and should reach you soon. Please keep your phone handy for delivery updates.'
        ],
        'delivered' => [
            'icon' => '✅',
            'title' => 'Order Delivered',
            'displayName' => 'Delivered',
            'color' => '#2e7d32',
            'bgColor' => '#e8f5e8',
            'subject' => '✅ Your Order has been Delivered',
            'description' => 'Your order has been successfully delivered! We hope you enjoy your purchase. Thank you for choosing ATTRAL Electronics.'
        ]
    ];
    
    return $statusMap[$status] ?? [
        'icon' => '📋',
        'title' => 'Status Update',
        'displayName' => $status,
        'color' => '#666',
        'bgColor' => '#f5f5f5',
        'subject' => '📋 Order Status Update',
        'description' => 'Your order status has been updated.'
    ];
}
?>
