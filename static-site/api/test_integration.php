<?php
/**
 * Integration Test for Affiliate Dashboard
 * Tests the complete flow from frontend to backend
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=================================================================\n";
echo "AFFILIATE DASHBOARD INTEGRATION TEST\n";
echo "=================================================================\n\n";

$testCode = 'attral-71hlzssgan';
$baseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

echo "Testing integration with code: $testCode\n";
echo "Base URL: $baseUrl\n\n";

// Test 1: Check if REST client exists
echo "--- Test 1: Firestore REST Client ---\n";
$restClientPath = __DIR__ . '/firestore_rest_client.php';
if (file_exists($restClientPath)) {
    echo "✅ Firestore REST client found\n";
} else {
    echo "❌ Firestore REST client NOT found at: $restClientPath\n";
    exit(1);
}

// Test 2: Check if service account exists
echo "\n--- Test 2: Firebase Service Account ---\n";
$serviceAccountPath = __DIR__ . '/firebase-service-account.json';
if (file_exists($serviceAccountPath)) {
    echo "✅ Firebase service account found\n";
} else {
    echo "❌ Firebase service account NOT found at: $serviceAccountPath\n";
    exit(1);
}

// Test 3: Test API endpoints
echo "\n--- Test 3: API Endpoints ---\n";

$endpoints = [
    'getAffiliateStats' => "?action=getAffiliateStats&code=$testCode",
    'getAffiliateOrders' => "?action=getAffiliateOrders&code=$testCode&pageSize=5",
    'getAffiliateByCode' => "?action=getAffiliateByCode&code=$testCode"
];

foreach ($endpoints as $name => $params) {
    echo "Testing $name...\n";
    
    $url = $baseUrl . "/affiliate_functions.php" . $params;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL Error: $error\n";
    } elseif ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            echo "✅ $name: HTTP $httpCode, Success: " . ($data['success'] ? 'true' : 'false') . "\n";
            if (!$data['success'] && isset($data['error'])) {
                echo "   Error: " . $data['error'] . "\n";
            }
        } else {
            echo "⚠️ $name: HTTP $httpCode, Invalid JSON response\n";
        }
    } else {
        echo "❌ $name: HTTP $httpCode\n";
        if ($response) {
            echo "   Response: " . substr($response, 0, 200) . "\n";
        }
    }
    echo "\n";
}

// Test 4: Test POST endpoints
echo "--- Test 4: POST Endpoints ---\n";

$postTests = [
    'createAffiliateProfile' => [
        'uid' => 'test-user-123',
        'email' => 'test@example.com',
        'name' => 'Test User',
        'phone' => '1234567890'
    ]
];

foreach ($postTests as $name => $data) {
    echo "Testing POST $name...\n";
    
    $url = $baseUrl . "/affiliate_functions.php?action=$name";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($data))
    ]);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL Error: $error\n";
    } elseif ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['success'])) {
            echo "✅ POST $name: HTTP $httpCode, Success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
            if (!$responseData['success'] && isset($responseData['error'])) {
                echo "   Error: " . $responseData['error'] . "\n";
            }
        } else {
            echo "⚠️ POST $name: HTTP $httpCode, Invalid JSON response\n";
        }
    } else {
        echo "❌ POST $name: HTTP $httpCode\n";
        if ($response) {
            echo "   Response: " . substr($response, 0, 200) . "\n";
        }
    }
    echo "\n";
}

// Test 5: Check frontend integration
echo "--- Test 5: Frontend Integration Check ---\n";

$dashboardUrl = $baseUrl . "/affiliate-dashboard.html";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $dashboardUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Dashboard page accessible (HTTP $httpCode)\n";
    
    // Check if it contains the API call code
    if (strpos($response, 'callFunction') !== false) {
        echo "✅ Frontend contains API integration code\n";
    } else {
        echo "❌ Frontend missing API integration code\n";
    }
    
    if (strpos($response, 'getAffiliateStats') !== false) {
        echo "✅ Frontend contains getAffiliateStats calls\n";
    } else {
        echo "❌ Frontend missing getAffiliateStats calls\n";
    }
    
    if (strpos($response, 'getAffiliateOrders') !== false) {
        echo "✅ Frontend contains getAffiliateOrders calls\n";
    } else {
        echo "❌ Frontend missing getAffiliateOrders calls\n";
    }
} else {
    echo "❌ Dashboard page not accessible (HTTP $httpCode)\n";
}

echo "\n=================================================================\n";
echo "INTEGRATION TEST COMPLETE\n";
echo "=================================================================\n";

// Additional recommendations
echo "\n--- RECOMMENDATIONS ---\n";
echo "1. Check browser console for JavaScript errors\n";
echo "2. Verify Firebase authentication is working\n";
echo "3. Test with a real affiliate code from your database\n";
echo "4. Check server error logs for PHP errors\n";
echo "5. Ensure CORS headers are working properly\n";
?>
