<?php
/**
 * 🚀 Quick Test Email Script
 * Simple script to send test email to attralsolar@gmail.com
 */

// Set content type for browser display
header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "<title>ATTRAL Email Test</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f8fafc; }\n";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }\n";
echo ".success { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 15px 0; }\n";
echo ".error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin: 15px 0; }\n";
echo ".info { background: #dbeafe; color: #1e40af; padding: 15px; border-radius: 8px; margin: 15px 0; }\n";
echo "pre { background: #f3f4f6; padding: 15px; border-radius: 8px; overflow-x: auto; }\n";
echo "</style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container'>\n";
echo "<h1>🎯 ATTRAL Email System Test</h1>\n";
echo "<p>Sending test email to <strong>attralsolar@gmail.com</strong> from <strong>info@attral.in</strong>...</p>\n";

try {
    // Initialize email service
    require_once 'brevo_email_service.php';
    $emailService = new BrevoEmailService();
    
    // Test email data
    $testEmail = 'attralsolar@gmail.com';
    $subject = "🎯 ATTRAL Email System Test - " . date('Y-m-d H:i:s');
    
    $htmlContent = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <div style="background: linear-gradient(135deg, #ff6b35, #f7931e); color: white; padding: 30px; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;">🎯 ATTRAL Email System Test</h1>
        </div>
        
        <div style="padding: 30px;">
            <h2 style="color: #1f2937;">✅ Email System Working Perfectly!</h2>
            
            <div style="background-color: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0ea5e9; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #0c4a6e;">Test Details</h3>
                <ul style="color: #1e40af;">
                    <li><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</li>
                    <li><strong>From:</strong> info@attral.in</li>
                    <li><strong>To:</strong> attralsolar@gmail.com</li>
                    <li><strong>Service:</strong> Brevo Email API</li>
                    <li><strong>Status:</strong> ✅ SUCCESS</li>
                </ul>
            </div>
            
            <p style="color: #4b5563; line-height: 1.6;">
                This test confirms that your ATTRAL email system is fully operational! 
                All automated emails (order confirmations, invoices, contact forms) will now work correctly.
            </p>
            
            <div style="background-color: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #92400e;">What This Means:</h3>
                <ul style="color: #92400e;">
                    <li>✅ Order confirmation emails will be sent</li>
                    <li>✅ Invoice emails will be sent</li>
                    <li>✅ Contact form emails will be sent</li>
                    <li>✅ Newsletter signups will work</li>
                    <li>✅ Affiliate emails will be sent</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="https://attral.in" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Visit ATTRAL Website</a>
            </div>
            
            <p style="text-align: center; color: #6b7280; margin-top: 30px;">
                <strong>ATTRAL Electronics</strong><br>
                Premium GaN Chargers for Modern Life
            </p>
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
        echo "<div class='success'>\n";
        echo "<h3>✅ SUCCESS!</h3>\n";
        echo "<p><strong>Test email sent successfully!</strong></p>\n";
        echo "<p>Email sent to: <strong>attralsolar@gmail.com</strong></p>\n";
        echo "<p>From: <strong>info@attral.in</strong></p>\n";
        echo "<p>Subject: <strong>{$subject}</strong></p>\n";
        echo "</div>\n";
        
        echo "<div class='info'>\n";
        echo "<h3>📊 Response Details:</h3>\n";
        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>\n";
        echo "</div>\n";
        
    } else {
        echo "<div class='error'>\n";
        echo "<h3>❌ FAILED!</h3>\n";
        echo "<p><strong>Failed to send test email!</strong></p>\n";
        echo "</div>\n";
        
        echo "<div class='error'>\n";
        echo "<h3>🚨 Error Details:</h3>\n";
        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>\n";
    echo "<h3>💥 EXCEPTION!</h3>\n";
    echo "<p><strong>An error occurred:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
    
    echo "<div class='error'>\n";
    echo "<h3>🔍 Debug Information:</h3>\n";
    echo "<pre>\n";
    echo "Exception: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "File: " . htmlspecialchars($e->getFile()) . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "</pre>\n";
    echo "</div>\n";
}

echo "<div class='info'>\n";
echo "<h3>🔧 System Status:</h3>\n";
echo "<ul>\n";
echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>\n";
echo "<li><strong>cURL Available:</strong> " . (extension_loaded('curl') ? '✅ Yes' : '❌ No') . "</li>\n";
echo "<li><strong>Current Time:</strong> " . date('Y-m-d H:i:s T') . "</li>\n";
echo "<li><strong>Brevo API:</strong> " . (defined('BREVO_API_KEY') && BREVO_API_KEY ? '✅ Configured' : '❌ Missing') . "</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<p><strong>Note:</strong> Check your email inbox (attralsolar@gmail.com) for the test email.</p>\n";
echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
?>
