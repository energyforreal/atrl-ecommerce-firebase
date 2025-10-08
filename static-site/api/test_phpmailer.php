<?php
/**
 * 🧪 PHPMailer Test Script
 * Test PHPMailer SMTP functionality directly
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load PHPMailer
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load config
$cfg = include __DIR__ . '/config.php';

try {
    // Get test email from request or use default
    $input = json_decode(file_get_contents('php://input'), true);
    $testEmail = $input['email'] ?? 'attralsolar@gmail.com';
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    // Server settings - Brevo SMTP
    $mail->isSMTP();
    $mail->Host = $cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com';
    $mail->SMTPAuth = true;
    $mail->Username = $cfg['SMTP_USERNAME'] ?? '8c9aee002@smtp-brevo.com';
    $mail->Password = $cfg['SMTP_PASSWORD'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS for Brevo
    $mail->Port = intval($cfg['SMTP_PORT'] ?? 587);
    
    // Enable verbose debug output (for testing)
    $mail->SMTPDebug = 0; // Set to 2 for verbose debug output
    
    // Recipients
    $mail->setFrom($cfg['MAIL_FROM'] ?? 'info@attral.in', $cfg['MAIL_FROM_NAME'] ?? 'ATTRAL Electronics');
    $mail->addAddress($testEmail, 'Test Recipient');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = '🧪 PHPMailer Direct Test - ' . date('Y-m-d H:i:s');
    
    $mail->Body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;">🧪 PHPMailer Direct Test</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Direct SMTP connection test</p>
        </div>
        
        <div style="padding: 30px;">
            <div style="background-color: #d1fae5; color: #065f46; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="margin-top: 0;">✅ PHPMailer Working!</h2>
                <p style="margin-bottom: 0;">This email was sent directly via PHPMailer using Brevo SMTP.</p>
            </div>
            
            <div style="background-color: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0ea5e9;">
                <h3 style="margin-top: 0; color: #0c4a6e;">Test Details</h3>
                <ul style="color: #1e40af; margin-bottom: 0;">
                    <li><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</li>
                    <li><strong>From:</strong> ' . ($cfg['MAIL_FROM'] ?? 'info@attral.in') . '</li>
                    <li><strong>To:</strong> ' . htmlspecialchars($testEmail) . '</li>
                    <li><strong>SMTP Host:</strong> ' . ($cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com') . '</li>
                    <li><strong>SMTP Port:</strong> ' . ($cfg['SMTP_PORT'] ?? 587) . '</li>
                    <li><strong>SMTP Security:</strong> SSL/TLS</li>
                    <li><strong>Status:</strong> ✅ SUCCESS</li>
                </ul>
            </div>
            
            <div style="background-color: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #92400e;">✅ PHPMailer Features Verified</h3>
                <ul style="color: #92400e; margin-bottom: 0;">
                    <li>SMTP connection established</li>
                    <li>Authentication successful</li>
                    <li>HTML email rendering</li>
                    <li>Email delivery</li>
                    <li>Error handling</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="https://attral.in" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Visit ATTRAL Website</a>
            </div>
            
            <div style="text-align: center; color: #6b7280; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0;"><strong>ATTRAL Electronics</strong><br>
                Premium GaN Chargers for Modern Life<br>
                📧 info@attral.in | 📱 +91 8903479870</p>
            </div>
        </div>
    </div>';
    
    $mail->AltBody = 'PHPMailer Direct Test - This is a test email sent directly via PHPMailer using Brevo SMTP.';
    
    // Send the email
    $mail->send();
    
    echo json_encode([
        'success' => true,
        'message' => 'PHPMailer test email sent successfully!',
        'details' => [
            'to' => $testEmail,
            'from' => $cfg['MAIL_FROM'] ?? 'info@attral.in',
            'smtp_host' => $cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com',
            'smtp_port' => $cfg['SMTP_PORT'] ?? 587,
            'smtp_username' => $cfg['SMTP_USERNAME'] ?? 'info@attral.in',
            'timestamp' => date('Y-m-d H:i:s'),
            'transport' => 'phpmailer_direct_smtp'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("PHPMailer Test Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'PHPMailer failed: ' . $e->getMessage(),
        'details' => [
            'smtp_host' => $cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com',
            'smtp_port' => $cfg['SMTP_PORT'] ?? 587,
            'smtp_username' => $cfg['SMTP_USERNAME'] ?? 'info@attral.in',
            'error_message' => $e->getMessage()
        ]
    ]);
}
?>
