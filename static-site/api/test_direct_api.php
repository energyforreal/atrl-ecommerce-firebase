<?php
/**
 * 🧪 Direct test of affiliate_email_sender.php API
 */

echo "🧪 Testing affiliate_email_sender.php directly...\n\n";

// Simulate a POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Test data
$testData = [
    'action' => 'welcome',
    'email' => 'test@example.com',
    'name' => 'Test User',
    'affiliateCode' => 'test-123'
];

// Create a temporary file to simulate php://input
$tempFile = tempnam(sys_get_temp_dir(), 'test_input');
file_put_contents($tempFile, json_encode($testData));

// Override file_get_contents to read from our temp file
function test_file_get_contents($filename) {
    global $tempFile;
    if ($filename === 'php://input') {
        return file_get_contents($tempFile);
    }
    return file_get_contents($filename);
}

// Start output buffering
ob_start();

try {
    // Include the API
    include 'affiliate_email_sender.php';
    
    $output = ob_get_clean();
    echo "📤 API Response:\n";
    echo $output . "\n\n";
    
    // Parse response
    $response = json_decode($output, true);
    
    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo "✅ SUCCESS: API working correctly!\n";
            echo "📧 Action: " . $response['action'] . "\n";
            echo "👤 Recipient: " . $response['recipient'] . "\n";
            echo "⏰ Timestamp: " . $response['timestamp'] . "\n";
        } else {
            echo "❌ ERROR: " . $response['error'] . "\n";
        }
    } else {
        echo "❌ ERROR: Invalid JSON response\n";
        echo "Raw output: " . $output . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

// Clean up
unlink($tempFile);

echo "\n🎯 Test completed!\n";
?>
