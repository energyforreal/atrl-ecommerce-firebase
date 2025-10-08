<?php
/**
 * Real Email Sender API
 * Actually sends emails using PHPMailer with Brevo SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
    
    if (!isset($input['orderId'])) {
        throw new Exception('orderId is required');
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
    
    $mail = new PHPMailer(true);
    
    // Server settings - Brevo SMTP
    $mail->isSMTP();
    $mail->Host = $cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com';
    $mail->SMTPAuth = true;
    $mail->Username = $cfg['SMTP_USERNAME'] ?? '8c9aee002@smtp-brevo.com';
    $mail->Password = $cfg['SMTP_PASSWORD'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = intval($cfg['SMTP_PORT'] ?? 587);
    
    // Recipients
    $mail->setFrom($cfg['MAIL_FROM'] ?? 'info@attral.in', $cfg['MAIL_FROM_NAME'] ?? 'ATTRAL Electronics');
    
    // Get customer email from order data
    $customerEmail = 'attralsolar@gmail.com'; // fallback
    $customerName = 'Customer'; // fallback
    
    if (isset($input['orderData']) && isset($input['orderData']['customer'])) {
        $customerEmail = $input['orderData']['customer']['email'] ?? $customerEmail;
        $customerName = trim(($input['orderData']['customer']['firstName'] ?? '') . ' ' . ($input['orderData']['customer']['lastName'] ?? ''));
        if (empty($customerName)) {
            $customerName = 'Customer';
        }
    }
    
    $mail->addAddress($customerEmail, $customerName);
    
    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    $mail->Subject = '✅ Order Confirmation - ' . $input['orderId'] . ' - ' . date('H:i:s');
    
    // Extract order data for email content
    $orderData = $input['orderData'] ?? [];
    $customer = $orderData['customer'] ?? [];
    $product = $orderData['product'] ?? [];
    $pricing = $orderData['pricing'] ?? [];
    $shipping = $orderData['shipping'] ?? [];
    
    // Prepare email content with real order data
    $customerName = trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? ''));
    $productTitle = $product['title'] ?? 'Product';
    $productPrice = $pricing['total'] ?? 0;
    $currency = $pricing['currency'] ?? 'INR';
    $shippingAddress = '';
    
    if (!empty($shipping)) {
        $shippingAddress = ($shipping['address'] ?? '') . '<br>' .
                          ($shipping['city'] ?? '') . ', ' . ($shipping['state'] ?? '') . ' ' . ($shipping['pincode'] ?? '') . '<br>' .
                          ($shipping['country'] ?? '');
    }
    
    $mail->Body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
        <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h1 style="color: #2e7d32; text-align: center; margin-bottom: 30px;">✅ Order Confirmed!</h1>
            
            <div style="background-color: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="color: #2e7d32; margin-top: 0;">Order Information:</h2>
                <p><strong>Order ID:</strong> ' . htmlspecialchars($input['orderId']) . '</p>
                <p><strong>Customer:</strong> ' . htmlspecialchars($customerName) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($customerEmail) . '</p>
                <p><strong>Product:</strong> ' . htmlspecialchars($productTitle) . '</p>
                <p><strong>Total:</strong> ' . htmlspecialchars($currency . ' ' . number_format($productPrice, 2)) . '</p>
                <p><strong>Status:</strong> Confirmed</p>
                <p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>
            </div>
            
            <div style="background-color: #fff3e0; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #f57c00; margin-top: 0;">Shipping Address:</h3>
                <p>' . ($shippingAddress ?: 'Address not provided') . '</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <p style="color: #666; font-size: 14px;">Thank you for your order! We will process it shortly.</p>
                <p style="color: #666; font-size: 12px;">ATTRAL Electronics | info@attral.in</p>
            </div>
        </div>
    </body>
    </html>';
    
    $mail->AltBody = 'Order Confirmation - Order ID: ' . $input['orderId'] . ' - Customer: ' . $customerName . ' - Product: ' . $productTitle . ' - Total: ' . $currency . ' ' . number_format($productPrice, 2) . ' - Status: Confirmed - Timestamp: ' . date('Y-m-d H:i:s');
    
    // Add PDF attachment if provided
    if (isset($input['pdfAttachment']) && isset($input['pdfAttachment']['content'])) {
        $pdfContent = base64_decode($input['pdfAttachment']['content']);
        $filename = $input['pdfAttachment']['filename'] ?? 'invoice.pdf';
        $mail->addStringAttachment($pdfContent, $filename, 'base64', 'application/pdf');
    }
    
    $mail->send();
    
    echo json_encode([
        'success' => true,
        'message' => 'Email sent successfully',
        'orderId' => $input['orderId'],
        'timestamp' => date('Y-m-d H:i:s'),
        'hasAttachment' => isset($input['pdfAttachment']),
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
?>
