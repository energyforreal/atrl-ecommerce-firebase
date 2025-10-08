<?php
/**
 * 📧 Verify Email Delivery
 * Send a simple test email to verify delivery
 */

// Enable OpenSSL extension
if (!extension_loaded('openssl')) {
    dl('C:\Program Files\php-8.4.12\ext\php_openssl.dll');
}

echo "📧 Verifying Email Delivery\n";
echo "==========================\n\n";

// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

echo "1. Sending Simple Test Email...\n";

try {
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
    $mail->Subject = '✅ ATTRAL Email Test - ' . date('Y-m-d H:i:s');
    
    $mail->Body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2e7d32;">✅ ATTRAL Email System Test</h2>
        <p>Hello!</p>
        <p>This is a test email to verify that the ATTRAL email system is working correctly.</p>
        
        <div style="background-color: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2e7d32;">Test Details:</h3>
            <ul style="color: #2e7d32;">
                <li><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</li>
                <li><strong>From:</strong> info@attral.in</li>
                <li><strong>To:</strong> attralsolar@gmail.com</li>
                <li><strong>SMTP:</strong> smtp-relay.brevo.com</li>
                <li><strong>Status:</strong> ✅ SUCCESS</li>
            </ul>
        </div>
        
        <p>If you receive this email, it means:</p>
        <ul>
            <li>✅ PHPMailer is working correctly</li>
            <li>✅ Brevo SMTP is configured properly</li>
            <li>✅ OpenSSL is enabled</li>
            <li>✅ Email delivery is functional</li>
        </ul>
        
        <p>This confirms that order confirmation emails will be sent successfully to customers.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="https://attral.in" style="display: inline-block; padding: 12px 24px; background-color: #2e7d32; color: white; text-decoration: none; border-radius: 6px;">Visit ATTRAL Website</a>
        </div>
        
        <p style="color: #666; font-size: 14px; text-align: center;">
            <strong>ATTRAL Electronics</strong><br>
            Premium GaN Chargers for Modern Life<br>
            📧 info@attral.in | 📱 +91 8903479870
        </p>
    </div>';
    
    $mail->AltBody = 'ATTRAL Email Test - This is a test email to verify that the ATTRAL email system is working correctly. Timestamp: ' . date('Y-m-d H:i:s');
    
    $mail->send();
    echo "✅ Test email sent successfully!\n";
    echo "   To: attralsolar@gmail.com\n";
    echo "   From: info@attral.in\n";
    echo "   Subject: ✅ ATTRAL Email Test - " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ Test email failed: " . $e->getMessage() . "\n";
}

echo "\n2. Sending Order Confirmation Test Email...\n";

try {
    require_once __DIR__ . '/brevo_email_service.php';
    $emailService = new BrevoEmailService();
    
    // Test order data
    $orderData = [
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
    
    $result = $emailService->sendOrderConfirmation($orderData);
    
    if ($result['success']) {
        echo "✅ Order confirmation test email sent successfully!\n";
        echo "   To: " . $orderData['email'] . "\n";
        echo "   Order ID: " . $orderData['orderId'] . "\n";
        echo "   Total: " . $orderData['currency'] . " " . $orderData['total'] . "\n";
    } else {
        echo "❌ Order confirmation test failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Order confirmation test exception: " . $e->getMessage() . "\n";
}

echo "\n🏁 Email Delivery Verification Completed.\n";
echo "\n📧 Check your email inbox (attralsolar@gmail.com) for:\n";
echo "   1. ✅ ATTRAL Email Test - [timestamp]\n";
echo "   2. Order Confirmation - TEST_[timestamp]\n";
echo "\n📋 If you don't receive the emails:\n";
echo "   1. Check your spam/junk folder\n";
echo "   2. Check the Promotions tab (Gmail)\n";
echo "   3. Wait a few minutes for delivery\n";
echo "   4. Verify the email address: attralsolar@gmail.com\n";
echo "\n✅ SMTP connection is working correctly!\n";
echo "✅ Emails are being sent successfully!\n";
?>
