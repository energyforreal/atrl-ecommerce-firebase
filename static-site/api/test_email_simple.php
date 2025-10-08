<?php
/**
 * Simple Email Test API
 * Minimal version for testing email functionality
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
    $mail->addAddress('attralsolar@gmail.com', 'Test Recipient');
    
    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    $mail->Subject = 'Test Email from Simple API - ' . date('H:i:s');
    
    $mail->Body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2e7d32;">✅ Test Email Success!</h2>
        <p>This is a test email from the simple API.</p>
        <div style="background-color: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h3>Order Details:</h3>
            <p><strong>Order ID:</strong> ' . htmlspecialchars($input['orderId']) . '</p>
            <p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>
        </div>
        <p>If you receive this email, the simple API is working correctly!</p>
    </div>';
    
    $mail->AltBody = 'Test Email Success - Order ID: ' . $input['orderId'] . ' - Timestamp: ' . date('Y-m-d H:i:s');
    
    $mail->send();
    
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent successfully',
        'orderId' => $input['orderId'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
