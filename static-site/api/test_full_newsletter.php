<?php
/**
 * Test Full Newsletter Functionality
 * Tests both Brevo API contact saving and email sending
 */

echo "🧪 Testing Full Newsletter System...\n\n";

// Load config
$cfg = include __DIR__ . '/config.php';

echo "📋 Configuration Check:\n";
echo "✅ SMTP Host: " . ($cfg['SMTP_HOST'] ?? 'NOT SET') . "\n";
echo "✅ SMTP Username: " . ($cfg['SMTP_USERNAME'] ?? 'NOT SET') . "\n";
echo "✅ SMTP Password: " . (empty($cfg['SMTP_PASSWORD']) ? 'NOT SET' : 'SET') . "\n";
echo "✅ Brevo API Key: " . (empty($cfg['BREVO_API_KEY']) ? 'NOT SET' : 'SET') . "\n";
echo "✅ Mail From: " . ($cfg['MAIL_FROM'] ?? 'NOT SET') . "\n";
echo "✅ Mail From Name: " . ($cfg['MAIL_FROM_NAME'] ?? 'NOT SET') . "\n\n";

// Test Brevo API connection
echo "🌐 Testing Brevo API Connection...\n";
$apiKey = $cfg['BREVO_API_KEY'] ?? '';
$apiUrl = 'https://api.brevo.com/v3/contacts';

$testData = [
    'email' => 'test@example.com',
    'attributes' => ['FIRSTNAME' => 'Test User'],
    'listIds' => [3],
    'updateEnabled' => true
];

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($testData),
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "📡 Brevo API Response: HTTP $httpCode\n";
    if ($httpCode === 201 || $httpCode === 204) {
        echo "✅ Brevo API: Working correctly\n";
    } else {
        echo "❌ Brevo API: Error - $response\n";
    }
} else {
    echo "⚠️ cURL not available - using file_get_contents\n";
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'accept: application/json',
                'api-key: ' . $apiKey,
                'content-type: application/json'
            ],
            'content' => json_encode($testData)
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    echo "📡 Brevo API Response: " . ($response !== false ? "Success" : "Failed") . "\n";
}

echo "\n📧 Testing Email Service...\n";

try {
    require_once __DIR__ . '/brevo_email_service.php';
    $emailService = new BrevoEmailService();
    
    echo "✅ Email service initialized\n";
    
    // Test email (change this to your email for actual testing)
    $testEmail = 'test@example.com';
    $testName = 'Test User';
    
    echo "📤 Attempting to send test email to: $testEmail\n";
    $result = $emailService->sendFreeShipCouponEmail($testEmail, $testName);
    
    if ($result['success']) {
        echo "✅ Email service: Working correctly\n";
        echo "📧 Test email sent successfully!\n";
    } else {
        echo "❌ Email service: Failed - " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Email service: Exception - " . $e->getMessage() . "\n";
}

echo "\n🎯 Summary:\n";
echo "1. Brevo API: " . (isset($httpCode) && ($httpCode === 201 || $httpCode === 204) ? "✅ Working" : "❌ Issues") . "\n";
echo "2. Email Service: " . (isset($result) && $result['success'] ? "✅ Working" : "❌ Issues") . "\n";
echo "3. PHPMailer: " . (class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? "✅ Available" : "❌ Missing") . "\n";

echo "\n💡 To test with real data, edit this script and change the test email address.\n";
?>
