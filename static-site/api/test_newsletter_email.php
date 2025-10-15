<?php
/**
 * Test Newsletter Email Functionality
 * This script tests if the newsletter email system is working
 */

// Load the email service
require_once 'brevo_email_service.php';

echo "🧪 Testing Newsletter Email System...\n\n";

// Test data
$testEmail = 'test@example.com'; // Change this to your email for testing
$testFirstName = 'Test User';

echo "📧 Test Email: $testEmail\n";
echo "👤 Test Name: $testFirstName\n\n";

try {
    // Create email service instance
    $service = new BrevoEmailService();
    
    echo "✅ Email service initialized\n";
    
    // Test the free shipping coupon email
    $result = $service->sendFreeShipCouponEmail($testEmail, $testFirstName);
    
    echo "📤 Email sending result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($result['success']) {
        echo "🎉 SUCCESS! Email sent successfully!\n";
        echo "Check your inbox for the free shipping coupon email.\n";
    } else {
        echo "❌ FAILED! Email could not be sent.\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n🔍 Check the error logs for detailed information.\n";
echo "Log file location: " . ini_get('error_log') . "\n";
?>
