<?php
/**
 * 🧪 Test Script: Affiliate Payout Tracking
 * 
 * Tests affiliate coupon tracking with commission-based payout increments.
 * Verifies that payoutUsage increments by actual commission amount, not just 1.
 * 
 * Usage:
 *   php test_affiliate_payout.php <COUPON_CODE> [ORDER_AMOUNT]
 * 
 * Or via browser:
 *   http://localhost/api/test_affiliate_payout.php?code=JOHN-REF&amount=999
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/coupon_tracking_service.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
}

echo "🧪 AFFILIATE PAYOUT TEST\n";
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
    
    // Get parameters
    if (php_sapi_name() === 'cli') {
        $couponCode = $argv[1] ?? null;
        $orderAmount = isset($argv[2]) ? floatval($argv[2]) : 999.00;
    } else {
        $couponCode = $_GET['code'] ?? null;
        $orderAmount = isset($_GET['amount']) ? floatval($_GET['amount']) : 999.00;
    }
    
    if (!$couponCode) {
        echo "❌ Please provide a coupon code\n";
        echo "\n💡 Usage: php test_affiliate_payout.php <COUPON_CODE> [ORDER_AMOUNT]\n";
        echo "   Example: php test_affiliate_payout.php JOHN-REF 1299.00\n";
        exit(1);
    }
    
    // Calculate commission (10%)
    $commissionRate = 0.10;
    $commissionAmount = $orderAmount * $commissionRate;
    
    echo "📋 Test Parameters:\n";
    echo "   Coupon Code: $couponCode\n";
    echo "   Order Amount: ₹" . number_format($orderAmount, 2) . "\n";
    echo "   Commission Rate: " . ($commissionRate * 100) . "%\n";
    echo "   Commission Amount: ₹" . number_format($commissionAmount, 2) . "\n\n";
    
    // Get initial coupon state
    echo "TEST 1: Getting initial coupon state\n";
    echo "------------------------------------\n";
    
    $normalizedCode = normalizeCouponCode($couponCode);
    $couponsRef = $firestore->collection('coupons');
    $query = $couponsRef->where('code', '=', $normalizedCode)->limit(1);
    $documents = $query->documents();
    
    $initialData = null;
    $docId = null;
    
    foreach ($documents as $doc) {
        if ($doc->exists()) {
            $initialData = $doc->data();
            $docId = $doc->id();
            break;
        }
    }
    
    if (!$initialData) {
        echo "❌ Coupon not found: $normalizedCode\n";
        echo "\n💡 Make sure the coupon exists in Firestore.\n";
        exit(1);
    }
    
    $initialUsageCount = $initialData['usageCount'] ?? 0;
    $initialPayoutUsage = $initialData['payoutUsage'] ?? 0;
    
    echo "✅ Found coupon: $normalizedCode\n";
    echo "   Initial usageCount: $initialUsageCount\n";
    echo "   Initial payoutUsage: ₹" . number_format($initialPayoutUsage, 2) . "\n\n";
    
    // Create test order
    $orderId = 'test_affiliate_' . time() . '_' . substr(md5($couponCode), 0, 6);
    $paymentId = 'pay_' . uniqid();
    
    echo "TEST 2: Creating test order\n";
    echo "------------------------------------\n";
    echo "   Order ID: $orderId\n";
    echo "   Payment ID: $paymentId\n";
    
    $orderRef = $firestore->collection('orders')->document($orderId);
    $orderRef->set([
        'orderId' => $orderId,
        'razorpayPaymentId' => $paymentId,
        'status' => 'test',
        'amount' => $orderAmount,
        'customer' => ['email' => 'test@example.com'],
        'createdAt' => new \Google\Cloud\Core\Timestamp(new DateTime())
    ]);
    
    echo "✅ Test order created\n\n";
    
    // Test 3: Apply affiliate coupon
    echo "TEST 3: Applying affiliate coupon\n";
    echo "------------------------------------\n";
    
    $result = applyCouponForOrder(
        $firestore,
        $couponCode,
        $orderId,
        [
            'amount' => $orderAmount,
            'customerEmail' => 'test@example.com',
            'affiliateCode' => $normalizedCode
        ],
        true, // isAffiliate
        $commissionAmount,
        $paymentId
    );
    
    if (!$result['success']) {
        echo "❌ Application failed: " . ($result['error'] ?? 'Unknown') . "\n";
        exit(1);
    }
    
    echo "✅ Coupon applied: " . $result['message'] . "\n";
    echo "   Is affiliate: " . ($result['coupon']['isAffiliate'] ? 'yes' : 'no') . "\n";
    echo "   Payout amount: ₹" . number_format($result['coupon']['payoutAmount'], 2) . "\n\n";
    
    // Test 4: Verify increments
    echo "TEST 4: Verifying increments\n";
    echo "------------------------------------\n";
    
    $docRef = $firestore->collection('coupons')->document($docId);
    $updatedSnap = $docRef->snapshot();
    $updatedData = $updatedSnap->data();
    
    $finalUsageCount = $updatedData['usageCount'] ?? 0;
    $finalPayoutUsage = $updatedData['payoutUsage'] ?? 0;
    
    $usageIncrement = $finalUsageCount - $initialUsageCount;
    $payoutIncrement = $finalPayoutUsage - $initialPayoutUsage;
    
    echo "After application:\n";
    echo "   usageCount: $initialUsageCount → $finalUsageCount (+$usageIncrement)\n";
    echo "   payoutUsage: ₹" . number_format($initialPayoutUsage, 2) . " → ₹" . number_format($finalPayoutUsage, 2);
    echo " (+" . number_format($payoutIncrement, 2) . ")\n\n";
    
    // Test 5: Verify affiliate usage log
    echo "TEST 5: Checking affiliate usage log\n";
    echo "------------------------------------\n";
    
    $guardKey = sha1($paymentId . '|' . $normalizedCode);
    $logRef = $orderRef->collection('affiliate_usage')->document($guardKey);
    $logSnap = $logRef->snapshot();
    
    if ($logSnap->exists()) {
        $logData = $logSnap->data();
        echo "✅ Affiliate usage logged\n";
        echo "   Coupon: " . ($logData['couponCode'] ?? 'N/A') . "\n";
        echo "   Affiliate: " . ($logData['affiliateCode'] ?? 'N/A') . "\n";
        echo "   Amount: ₹" . number_format($logData['amount'] ?? 0, 2) . "\n";
        echo "   Commission: ₹" . number_format($logData['commission'] ?? 0, 2) . "\n\n";
        $logExists = true;
    } else {
        echo "⚠️ Affiliate usage log not found\n\n";
        $logExists = false;
    }
    
    // Summary
    echo "📊 TEST SUMMARY\n";
    echo "===============\n";
    echo "Coupon Code: $normalizedCode\n";
    echo "Order Amount: ₹" . number_format($orderAmount, 2) . "\n";
    echo "Commission (10%): ₹" . number_format($commissionAmount, 2) . "\n\n";
    
    echo "Increments:\n";
    echo "  usageCount: +$usageIncrement (expected: +1)\n";
    echo "  payoutUsage: +₹" . number_format($payoutIncrement, 2);
    echo " (expected: +₹" . number_format($commissionAmount, 2) . ")\n\n";
    
    // Verify correctness
    $usageCorrect = ($usageIncrement === 1);
    $payoutCorrect = (abs($payoutIncrement - $commissionAmount) < 0.01); // Allow tiny floating point difference
    
    if ($usageCorrect && $payoutCorrect && $logExists) {
        echo "✅ AFFILIATE PAYOUT TEST PASSED!\n";
        echo "   ✓ usageCount incremented by 1\n";
        echo "   ✓ payoutUsage incremented by commission amount\n";
        echo "   ✓ Affiliate usage logged correctly\n";
        $testPassed = true;
    } else {
        echo "❌ AFFILIATE PAYOUT TEST FAILED!\n";
        if (!$usageCorrect) echo "   ✗ usageCount increment incorrect\n";
        if (!$payoutCorrect) echo "   ✗ payoutUsage increment incorrect\n";
        if (!$logExists) echo "   ✗ Affiliate usage log missing\n";
        $testPassed = false;
    }
    
    // Cleanup option
    echo "\n💡 Cleanup: To remove test order, run:\n";
    echo "   firebase firestore:delete orders/$orderId --recursive\n";
    
    // Output JSON for web requests
    if (php_sapi_name() !== 'cli') {
        echo "\n" . json_encode([
            'success' => $testPassed,
            'coupon' => $normalizedCode,
            'orderId' => $orderId,
            'orderAmount' => $orderAmount,
            'commission' => $commissionAmount,
            'increments' => [
                'usageCount' => [
                    'before' => $initialUsageCount,
                    'after' => $finalUsageCount,
                    'increment' => $usageIncrement,
                    'expected' => 1,
                    'correct' => $usageCorrect
                ],
                'payoutUsage' => [
                    'before' => $initialPayoutUsage,
                    'after' => $finalPayoutUsage,
                    'increment' => $payoutIncrement,
                    'expected' => $commissionAmount,
                    'correct' => $payoutCorrect
                ]
            ],
            'affiliateLogExists' => $logExists
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>

