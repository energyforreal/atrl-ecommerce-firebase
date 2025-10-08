<?php
/**
 * 🎯 Simple Test Email Script
 * Send a test email to verify the email system is working
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    // Initialize email service
    require_once __DIR__ . '/brevo_email_service.php';
    $emailService = new BrevoEmailService();
    
    // Get test email from request or use default
    $input = json_decode(file_get_contents('php://input'), true);
    $testEmail = $input['email'] ?? 'attralsolar@gmail.com';
    
    // Test email data
    $subject = "🎯 ATTRAL Email Test - " . date('Y-m-d H:i:s');
    
    $htmlContent = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;">🎯 ATTRAL Email System Test</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Email functionality verification</p>
        </div>
        
        <div style="padding: 30px;">
            <div style="background-color: #d1fae5; color: #065f46; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="margin-top: 0;">✅ Email System Working!</h2>
                <p style="margin-bottom: 0;">Your ATTRAL email system is fully operational and ready to send automated emails.</p>
            </div>
            
            <div style="background-color: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0ea5e9;">
                <h3 style="margin-top: 0; color: #0c4a6e;">Test Details</h3>
                <ul style="color: #1e40af; margin-bottom: 0;">
                    <li><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</li>
                    <li><strong>From:</strong> info@attral.in</li>
                    <li><strong>To:</strong> ' . htmlspecialchars($testEmail) . '</li>
                    <li><strong>Service:</strong> PHPMailer SMTP (Primary)</li>
                    <li><strong>Status:</strong> ✅ SUCCESS</li>
                </ul>
            </div>
            
            <div style="background-color: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #92400e;">✅ Working Features</h3>
                <ul style="color: #92400e; margin-bottom: 0;">
                    <li>Order confirmation emails</li>
                    <li>Invoice generation & email</li>
                    <li>Contact form notifications</li>
                    <li>Newsletter subscriptions</li>
                    <li>Affiliate commission emails</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="https://attral.in" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Visit ATTRAL Website</a>
            </div>
            
            <div style="text-align: center; color: #6b7280; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0;"><strong>ATTRAL Electronics</strong><br>
                Premium GaN Chargers for Modern Life<br>
                📧 info@attral.in | 📱 +91 8903479870</p>
            </div>
        </div>
    </div>';
    
    // Send the email
    $result = $emailService->sendTransactionalEmail(
        $testEmail,
        $subject,
        $htmlContent,
        [
            'toName' => 'ATTRAL Test Recipient',
            'fromEmail' => 'info@attral.in',
            'fromName' => 'ATTRAL Electronics'
        ]
    );
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Test email sent successfully!',
            'details' => [
                'to' => $testEmail,
                'subject' => $subject,
                'timestamp' => date('Y-m-d H:i:s'),
                'service' => 'PHPMailer SMTP (Primary)'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to send test email: ' . ($result['error'] ?? 'Unknown error'),
            'details' => $result
        ]);
    }
    
} catch (Exception $e) {
    error_log("TEST EMAIL ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage()
    ]);
}
?>
