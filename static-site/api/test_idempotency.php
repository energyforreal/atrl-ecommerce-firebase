<?php
/**
 * 🧪 Test Script: Idempotency Guard Testing
 * 
 * Tests that duplicate order applications don't double-increment coupon usage.
 * Simulates the scenario where the same payment/order is processed multiple times.
 * 
 * Usage:
 *   php test_idempotency.php <COUPON_CODE> <ORDER_ID> <PAYMENT_ID>
 * 
 * Or via browser:
 *   http://localhost/api/test_idempotency.php?code=SAVE20&order=test_order_123&payment=pay_abc123
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/coupon_tracking_service.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
}

echo "🧪 IDEMPOTENCY TEST\n";
echo "====================\n\n";

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
    
    // Get parameters
    if (php_sapi_name() === 'cli') {
        $couponCode = $argv[1] ?? null;
        $orderId = $argv[2] ?? 'test_order_' . time();
        $paymentId = $argv[3] ?? 'pay_' . uniqid();
    } else {
        $couponCode = $_GET['code'] ?? null;
        $orderId = $_GET['order'] ?? 'test_order_' . time();
        $paymentId = $_GET['payment'] ?? 'pay_' . uniqid();
    }
    
    if (!$couponCode) {
        echo "❌ Please provide a coupon code\n";
        echo "\n💡 Usage: php test_idempotency.php <COUPON_CODE> [ORDER_ID] [PAYMENT_ID]\n";
        exit(1);
    }
    
    echo "📋 Test Parameters:\n";
    echo "   Coupon Code: $couponCode\n";
    echo "   Order ID: $orderId\n";
    echo "   Payment ID: $paymentId\n\n";
    
    // Create a test order document if it doesn't exist
    echo "TEST 0: Ensuring test order exists\n";
    echo "------------------------------------\n";
    
    $orderRef = $firestore->collection('orders')->document($orderId);
    $orderSnap = $orderRef->snapshot();
    
    if (!$orderSnap->exists()) {
        echo "Creating test order document...\n";
        $orderRef->set([
            'orderId' => $orderId,
            'razorpayPaymentId' => $paymentId,
            'status' => 'test',
            'amount' => 999.00,
            'customer' => ['email' => 'test@example.com'],
            'createdAt' => new \Google\Cloud\Core\Timestamp(new DateTime())
        ]);
        echo "✅ Test order created\n\n";
    } else {
        echo "✅ Test order already exists\n\n";
    }
    
    // Get initial coupon state
    echo "TEST 1: Getting initial coupon state\n";
    echo "------------------------------------\n";
    
    $normalizedCode = normalizeCouponCode($couponCode);
    $couponsRef = $firestore->collection('coupons');
    $query = $couponsRef->where('code', '=', $normalizedCode)->limit(1);
    $documents = $query->documents();
    
    $initialUsageCount = null;
    $docId = null;
    
    foreach ($documents as $doc) {
        if ($doc->exists()) {
            $data = $doc->data();
            $docId = $doc->id();
            $initialUsageCount = $data['usageCount'] ?? 0;
            echo "✅ Found coupon: $normalizedCode\n";
            echo "   Initial usageCount: $initialUsageCount\n\n";
            break;
        }
    }
    
    if ($initialUsageCount === null) {
        echo "❌ Coupon not found: $normalizedCode\n";
        exit(1);
    }
    
    // Test 2: First application
    echo "TEST 2: First coupon application\n";
    echo "------------------------------------\n";
    
    $result1 = applyCouponForOrder(
        $firestore,
        $couponCode,
        $orderId,
        ['amount' => 999.00, 'customerEmail' => 'test@example.com'],
        false,
        0,
        $paymentId
    );
    
    if (!$result1['success']) {
        echo "❌ First application failed: " . ($result1['error'] ?? 'Unknown') . "\n";
        exit(1);
    }
    
    $isIdempotent1 = $result1['idempotent'] ?? false;
    echo ($isIdempotent1 ? "↩️" : "✅") . " First application: " . $result1['message'] . "\n";
    echo "   Idempotent: " . ($isIdempotent1 ? 'yes' : 'no') . "\n\n";
    
    // Check usage after first application
    $docRef = $firestore->collection('coupons')->document($docId);
    $snap1 = $docRef->snapshot();
    $usageAfterFirst = $snap1->data()['usageCount'] ?? 0;
    echo "   Usage count after first: $usageAfterFirst\n";
    echo "   Expected increment: " . ($isIdempotent1 ? "0" : "1") . "\n";
    echo "   Actual increment: " . ($usageAfterFirst - $initialUsageCount) . "\n\n";
    
    // Test 3: Second application (duplicate - should be idempotent)
    echo "TEST 3: Second application (duplicate)\n";
    echo "------------------------------------\n";
    
    $result2 = applyCouponForOrder(
        $firestore,
        $couponCode,
        $orderId,
        ['amount' => 999.00, 'customerEmail' => 'test@example.com'],
        false,
        0,
        $paymentId
    );
    
    if (!$result2['success']) {
        echo "❌ Second application failed: " . ($result2['error'] ?? 'Unknown') . "\n";
        exit(1);
    }
    
    $isIdempotent2 = $result2['idempotent'] ?? false;
    echo ($isIdempotent2 ? "↩️" : "⚠️") . " Second application: " . $result2['message'] . "\n";
    echo "   Idempotent: " . ($isIdempotent2 ? 'yes (CORRECT!)' : 'no (ERROR!)') . "\n\n";
    
    // Check usage after second application
    $snap2 = $docRef->snapshot();
    $usageAfterSecond = $snap2->data()['usageCount'] ?? 0;
    echo "   Usage count after second: $usageAfterSecond\n";
    echo "   Should be same as first: " . ($usageAfterFirst === $usageAfterSecond ? "YES ✅" : "NO ❌") . "\n\n";
    
    // Test 4: Third application (verify still idempotent)
    echo "TEST 4: Third application (verify)\n";
    echo "------------------------------------\n";
    
    $result3 = applyCouponForOrder(
        $firestore,
        $couponCode,
        $orderId,
        ['amount' => 999.00, 'customerEmail' => 'test@example.com'],
        false,
        0,
        $paymentId
    );
    
    $isIdempotent3 = $result3['idempotent'] ?? false;
    echo ($isIdempotent3 ? "↩️" : "⚠️") . " Third application: " . $result3['message'] . "\n";
    echo "   Idempotent: " . ($isIdempotent3 ? 'yes (CORRECT!)' : 'no (ERROR!)') . "\n\n";
    
    $snap3 = $docRef->snapshot();
    $usageAfterThird = $snap3->data()['usageCount'] ?? 0;
    
    // Summary
    echo "📊 TEST SUMMARY\n";
    echo "===============\n";
    echo "Coupon Code: $normalizedCode\n";
    echo "Order ID: $orderId\n";
    echo "Payment ID: $paymentId\n\n";
    
    echo "Usage Count Progression:\n";
    echo "  Initial:       $initialUsageCount\n";
    echo "  After 1st app: $usageAfterFirst (+" . ($usageAfterFirst - $initialUsageCount) . ")\n";
    echo "  After 2nd app: $usageAfterSecond (+" . ($usageAfterSecond - $initialUsageCount) . ")\n";
    echo "  After 3rd app: $usageAfterThird (+" . ($usageAfterThird - $initialUsageCount) . ")\n\n";
    
    $totalIncrement = $usageAfterThird - $initialUsageCount;
    $expectedIncrement = $isIdempotent1 ? 0 : 1;
    
    if ($totalIncrement === $expectedIncrement && $isIdempotent2 && $isIdempotent3) {
        echo "✅ IDEMPOTENCY TEST PASSED!\n";
        echo "   Coupon was only incremented once despite 3 applications.\n";
        $testPassed = true;
    } else {
        echo "❌ IDEMPOTENCY TEST FAILED!\n";
        echo "   Expected total increment: $expectedIncrement\n";
        echo "   Actual total increment: $totalIncrement\n";
        $testPassed = false;
    }
    
    // Output JSON for web requests
    if (php_sapi_name() !== 'cli') {
        echo "\n" . json_encode([
            'success' => $testPassed,
            'coupon' => $normalizedCode,
            'orderId' => $orderId,
            'paymentId' => $paymentId,
            'usageCount' => [
                'initial' => $initialUsageCount,
                'afterFirst' => $usageAfterFirst,
                'afterSecond' => $usageAfterSecond,
                'afterThird' => $usageAfterThird
            ],
            'idempotent' => [
                'first' => $isIdempotent1,
                'second' => $isIdempotent2,
                'third' => $isIdempotent3
            ],
            'totalIncrement' => $totalIncrement,
            'expectedIncrement' => $expectedIncrement
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>

