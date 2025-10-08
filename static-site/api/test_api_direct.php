<?php
/**
 * 🧪 Direct API Test
 * Test the API directly without web interface
 */

echo "<h1>🧪 Direct API Test</h1>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px;}</style>";

// Test 1: Check if API file exists
echo "<h2>1. API File Check</h2>";
$apiFile = __DIR__ . '/test_affiliate_api.php';
if (file_exists($apiFile)) {
    echo "✅ API file exists: test_affiliate_api.php<br>";
} else {
    echo "❌ API file missing: test_affiliate_api.php<br>";
    exit;
}

// Test 2: Check if functions file exists
echo "<h2>2. Functions File Check</h2>";
$functionsFile = __DIR__ . '/affiliate_email_sender_functions.php';
if (file_exists($functionsFile)) {
    echo "✅ Functions file exists: affiliate_email_sender_functions.php<br>";
} else {
    echo "❌ Functions file missing: affiliate_email_sender_functions.php<br>";
}

// Test 3: Check if order_manager exists
echo "<h2>3. Order Manager Check</h2>";
$orderManagerFile = __DIR__ . '/order_manager.php';
if (file_exists($orderManagerFile)) {
    echo "✅ Order manager exists: order_manager.php<br>";
} else {
    echo "❌ Order manager missing: order_manager.php<br>";
}

// Test 4: Check PHPMailer
echo "<h2>4. PHPMailer Check</h2>";
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
$vendoredSrc = __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';

if (file_exists($composerAutoload)) {
    echo "✅ PHPMailer found via Composer<br>";
} elseif (file_exists($vendoredSrc)) {
    echo "✅ PHPMailer found in vendor directory<br>";
} else {
    echo "❌ PHPMailer not found<br>";
}

// Test 5: Test API directly
echo "<h2>5. Direct API Test</h2>";
echo "<p>Testing API with test action...</p>";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Create test input
$testInput = json_encode(['action' => 'test']);

// Capture output
ob_start();
file_put_contents('php://input', $testInput);

try {
    include $apiFile;
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "<strong>API Output:</strong><br>";
    echo "<pre style='background:#f0f0f0;padding:10px;border-radius:5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // Try to parse as JSON
    $json = json_decode($output, true);
    if ($json !== null) {
        echo "✅ API returned valid JSON<br>";
        echo "<strong>Parsed JSON:</strong><br>";
        echo "<pre style='background:#e8f5e8;padding:10px;border-radius:5px;'>";
        echo htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT));
        echo "</pre>";
    } else {
        echo "❌ API returned invalid JSON<br>";
        echo "<strong>JSON Error:</strong> " . json_last_error_msg() . "<br>";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ API Error: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Recommendations</h2>";
echo "<ul>";
echo "<li>Check browser console (F12) for detailed debugging information</li>";
echo "<li>Use the 'Test API Connection' button in the web interface</li>";
echo "<li>Ensure PHP is running and can execute the API file</li>";
echo "<li>Check server error logs for PHP errors</li>";
echo "</ul>";

echo "<br><strong>🎯 Next Steps:</strong><br>";
echo "1. Open the web test page: <a href='../simple-affiliate-test.html'>simple-affiliate-test.html</a><br>";
echo "2. Open browser console (F12)<br>";
echo "3. Click 'Test API Connection' button<br>";
echo "4. Check console for debug information<br>";
?>
