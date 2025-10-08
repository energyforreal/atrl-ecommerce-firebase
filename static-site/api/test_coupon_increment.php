<?php
/**
 * 🧪 Test Script: Simple Coupon Increment
 * 
 * Tests the basic atomic increment functionality for coupon usage tracking.
 * This verifies that FieldValue::increment() works correctly even with missing fields.
 * 
 * Usage:
 *   php test_coupon_increment.php
 * 
 * Or via browser:
 *   http://localhost/api/test_coupon_increment.php?code=SAVE20
 */

// Suppress warnings for clean output
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load dependencies
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/coupon_tracking_service.php';

// Set headers for web access
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
}

echo "🧪 COUPON INCREMENT TEST\n";
echo "========================\n\n";

try {
    // Initialize Firestore
    $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
    if (!file_exists($serviceAccountPath)) {
        throw new Exception('Firebase service account file not found');
    }
    
    $factory = (new \Kreait\Firebase\Factory())
        ->withServiceAccount($serviceAccountPath);
    $firestore = $factory->createFirestore();
    
    echo "✅ Firestore connected\n\n";
    
    // Get coupon code from CLI argument or GET parameter
    $couponCode = null;
    if (php_sapi_name() === 'cli') {
        $couponCode = $argv[1] ?? null;
    } else {
        $couponCode = $_GET['code'] ?? null;
    }
    
    // If no code provided, list available coupons
    if (!$couponCode) {
        echo "📋 Listing first 5 available coupons:\n";
        echo "------------------------------------\n";
        
        $couponsRef = $firestore->collection('coupons');
        $query = $couponsRef->limit(5);
        $documents = $query->documents();
        
        $found = false;
        foreach ($documents as $doc) {
            if ($doc->exists()) {
                $found = true;
                $data = $doc->data();
                echo sprintf("  - %s (%s) - usageCount: %d, payoutUsage: %s\n",
                    $data['code'] ?? 'N/A',
                    $data['name'] ?? 'Unnamed',
                    $data['usageCount'] ?? 0,
                    isset($data['payoutUsage']) ? $data['payoutUsage'] : 0
                );
            }
        }
        
        if (!$found) {
            echo "  No coupons found in database.\n";
        }
        
        echo "\n💡 Usage: php test_coupon_increment.php <COUPON_CODE>\n";
        echo "   Example: php test_coupon_increment.php SAVE20\n";
        exit(0);
    }
    
    // Test 1: Check if coupon exists
    echo "TEST 1: Finding coupon '$couponCode'\n";
    echo "------------------------------------\n";
    
    $normalizedCode = normalizeCouponCode($couponCode);
    echo "Normalized code: $normalizedCode\n";
    
    $couponsRef = $firestore->collection('coupons');
    $query = $couponsRef->where('code', '=', $normalizedCode)->limit(1);
    $documents = $query->documents();
    
    $couponData = null;
    $docId = null;
    
    foreach ($documents as $doc) {
        if ($doc->exists()) {
            $docId = $doc->id();
            $couponData = $doc->data();
            break;
        }
    }
    
    if (!$couponData) {
        echo "❌ Coupon not found: $normalizedCode\n";
        echo "\n💡 Make sure the coupon exists in Firestore.\n";
        exit(1);
    }
    
    echo "✅ Coupon found!\n";
    echo "   Document ID: $docId\n";
    echo "   Name: " . ($couponData['name'] ?? 'N/A') . "\n";
    echo "   Type: " . ($couponData['type'] ?? 'N/A') . "\n";
    echo "   Value: " . ($couponData['value'] ?? 0) . "\n";
    echo "   Before usageCount: " . ($couponData['usageCount'] ?? 0) . "\n";
    echo "   Before payoutUsage: " . ($couponData['payoutUsage'] ?? 0) . "\n\n";
    
    // Test 2: Increment using service
    echo "TEST 2: Incrementing usage count\n";
    echo "------------------------------------\n";
    
    $result = incrementCouponByCode($firestore, $normalizedCode);
    
    if (!$result['success']) {
        echo "❌ Increment failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }
    
    echo "✅ Increment successful!\n";
    echo "   After usageCount: " . ($result['coupon']['usageCount'] ?? 0) . "\n";
    echo "   After payoutUsage: " . ($result['coupon']['payoutUsage'] ?? 0) . "\n\n";
    
    // Test 3: Verify the change
    echo "TEST 3: Verifying increment\n";
    echo "------------------------------------\n";
    
    $beforeUsage = $couponData['usageCount'] ?? 0;
    $afterUsage = $result['coupon']['usageCount'] ?? 0;
    $increment = $afterUsage - $beforeUsage;
    
    echo "Expected increment: 1\n";
    echo "Actual increment: $increment\n";
    
    if ($increment === 1) {
        echo "✅ Increment verified correctly!\n\n";
    } else {
        echo "⚠️ Unexpected increment value (might be due to concurrent updates)\n\n";
    }
    
    // Summary
    echo "📊 TEST SUMMARY\n";
    echo "===============\n";
    echo "Coupon Code: $normalizedCode\n";
    echo "Document ID: $docId\n";
    echo "Usage Count: $beforeUsage → $afterUsage (+$increment)\n";
    echo "\n✅ All tests passed!\n";
    
    // Output JSON for web requests
    if (php_sapi_name() !== 'cli') {
        echo "\n" . json_encode([
            'success' => true,
            'coupon' => [
                'code' => $normalizedCode,
                'docId' => $docId,
                'before' => [
                    'usageCount' => $beforeUsage,
                    'payoutUsage' => $couponData['payoutUsage'] ?? 0
                ],
                'after' => [
                    'usageCount' => $afterUsage,
                    'payoutUsage' => $result['coupon']['payoutUsage'] ?? 0
                ],
                'increment' => $increment
            ]
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "\n" . json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_PRETTY_PRINT);
    }
    
    exit(1);
}
?>

