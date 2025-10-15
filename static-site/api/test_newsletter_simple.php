<?php
/**
 * Simple Newsletter Test
 * Tests the newsletter API endpoint directly
 */

echo "🧪 Testing Newsletter API Endpoint...\n\n";

// Test data
$testData = [
    'FIRSTNAME' => 'Test User',
    'EMAIL' => 'test@example.com'  // Change this to your email for testing
];

echo "📝 Test Data:\n";
echo "Name: " . $testData['FIRSTNAME'] . "\n";
echo "Email: " . $testData['EMAIL'] . "\n\n";

// Test the newsletter API
echo "📡 Calling newsletter API...\n";

// Create a POST request to the newsletter API
$postData = json_encode($testData);

if (function_exists('curl_init')) {
    // Use cURL if available
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://localhost:8000/api/brevo_newsletter.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "📡 Response Code: $httpCode\n";
    echo "📄 Response: $response\n\n";
    
} else {
    // Use file_get_contents as fallback
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData,
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents('http://localhost:8000/api/brevo_newsletter.php', false, $context);
    
    if ($response !== false) {
        echo "📡 Response: Success\n";
        echo "📄 Response: $response\n\n";
    } else {
        echo "❌ Failed to connect to API\n\n";
    }
}

// Parse response
$result = json_decode($response, true);

if ($result) {
    echo "🎯 API Result:\n";
    echo "Success: " . ($result['success'] ? '✅ Yes' : '❌ No') . "\n";
    echo "Message: " . ($result['message'] ?? 'No message') . "\n";
    
    if (isset($result['freeShippingCode'])) {
        echo "Coupon Code: " . $result['freeShippingCode'] . "\n";
    }
    
    if (isset($result['error'])) {
        echo "Error: " . $result['error'] . "\n";
    }
} else {
    echo "❌ Could not parse API response\n";
}

echo "\n💡 Make sure your PHP server is running on localhost:8000\n";
echo "💡 To test with real email, edit the EMAIL value in this script\n";
?>
