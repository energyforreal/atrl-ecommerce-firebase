<?php
/**
 * Test Live API Endpoint
 * This script tests the newsletter API endpoint directly
 */

echo "🧪 Testing Live Newsletter API...\n\n";

// Test data
$testData = [
    'FIRSTNAME' => 'Test User',
    'EMAIL' => 'test@example.com'
];

echo "📝 Test Data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Test the API endpoint
$apiUrl = 'https://attral.in/api/brevo_newsletter.php';
$postData = json_encode($testData);

echo "📡 Testing API: $apiUrl\n";
echo "📤 Sending data: $postData\n\n";

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: Test-Script/1.0'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    echo "📡 Response Code: $httpCode\n";
    if ($curlError) {
        echo "❌ cURL Error: $curlError\n";
    }
    echo "📄 Response: $response\n\n";
    
    curl_close($ch);
    
} else {
    echo "⚠️ cURL not available - using file_get_contents\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'User-Agent: Test-Script/1.0'
            ],
            'content' => $postData,
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        echo "📡 Response: Success\n";
        echo "📄 Response: $response\n\n";
    } else {
        echo "❌ Failed to connect to API\n";
        if (isset($http_response_header)) {
            echo "📋 Response Headers:\n";
            foreach ($http_response_header as $header) {
                echo "  $header\n";
            }
        }
    }
}

// Parse and display result
if ($response) {
    $result = json_decode($response, true);
    
    echo "🎯 Parsed Result:\n";
    if ($result) {
        echo "Success: " . ($result['success'] ? '✅ Yes' : '❌ No') . "\n";
        echo "Message: " . ($result['message'] ?? 'No message') . "\n";
        echo "Error: " . ($result['error'] ?? 'No error') . "\n";
        
        if (isset($result['freeShippingCode'])) {
            echo "Coupon Code: " . $result['freeShippingCode'] . "\n";
        }
    } else {
        echo "❌ Could not parse JSON response\n";
        echo "Raw response: $response\n";
    }
}

echo "\n💡 This test helps identify if the API endpoint is working correctly.\n";
echo "💡 Check your server error logs for detailed debugging information.\n";
?>
