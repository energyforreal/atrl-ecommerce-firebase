<?php
/**
 * Test script to verify the coupon field fix
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=================================================================\n";
echo "TESTING COUPON FIELD FIX\n";
echo "=================================================================\n\n";

$testCode = 'attral-71hlzssgan';
echo "Testing with affiliate code: $testCode\n\n";

// Load Firestore REST client
require_once __DIR__ . '/firestore_rest_client.php';

try {
    $firestore = new FirestoreRestClient(
        'e-commerce-1d40f',
        __DIR__ . '/firebase-service-account.json',
        true
    );
    
    echo "✅ Connected to Firestore successfully!\n\n";
    
    // Test 1: Query by 'code' field (old way - should fail)
    echo "--- Test 1: Query by 'code' field (old way) ---\n";
    $couponsByCode = $firestore->queryDocuments('coupons', [
        ['field' => 'code', 'op' => 'EQUAL', 'value' => $testCode]
    ], 1);
    
    if (!empty($couponsByCode)) {
        echo "✅ Found coupon by 'code' field\n";
        $data = $couponsByCode[0]['data'];
        echo "Code: " . ($data['code'] ?? 'N/A') . "\n";
        echo "AffiliateCode: " . ($data['affiliateCode'] ?? 'N/A') . "\n";
        echo "UsageCount: " . ($data['usageCount'] ?? 'N/A') . "\n";
    } else {
        echo "❌ No coupon found by 'code' field (expected)\n";
    }
    
    // Test 2: Query by 'affiliateCode' field (new way - should work)
    echo "\n--- Test 2: Query by 'affiliateCode' field (new way) ---\n";
    $couponsByAffiliateCode = $firestore->queryDocuments('coupons', [
        ['field' => 'affiliateCode', 'op' => 'EQUAL', 'value' => $testCode]
    ], 1);
    
    if (!empty($couponsByAffiliateCode)) {
        echo "✅ Found coupon by 'affiliateCode' field\n";
        $data = $couponsByAffiliateCode[0]['data'];
        echo "Code: " . ($data['code'] ?? 'N/A') . "\n";
        echo "AffiliateCode: " . ($data['affiliateCode'] ?? 'N/A') . "\n";
        echo "UsageCount: " . ($data['usageCount'] ?? 'N/A') . "\n";
        echo "PayoutUsage: " . ($data['payoutUsage'] ?? 'N/A') . "\n";
        
        // Calculate expected stats
        $usageCount = $data['usageCount'] ?? 0;
        $calculatedEarnings = $usageCount * 300;
        $calculatedPayout = $usageCount * 300;
        
        echo "\nExpected Stats Calculation:\n";
        echo "- Total Referrals: $usageCount\n";
        echo "- Total Earnings: ₹$calculatedEarnings\n";
        echo "- Coupon Payout Usage: ₹$calculatedPayout\n";
        
    } else {
        echo "❌ No coupon found by 'affiliateCode' field\n";
    }
    
    echo "\n--- Test 3: Check what coupon codes exist ---\n";
    $allCoupons = $firestore->queryDocuments('coupons', [], 5);
    if (!empty($allCoupons)) {
        echo "First 5 coupons in collection:\n";
        foreach ($allCoupons as $coupon) {
            $data = $coupon['data'];
            echo "- Code: " . ($data['code'] ?? 'N/A') . " | AffiliateCode: " . ($data['affiliateCode'] ?? 'N/A') . " | UsageCount: " . ($data['usageCount'] ?? 'N/A') . "\n";
        }
    }
    
    echo "\n=================================================================\n";
    echo "CONCLUSION:\n";
    echo "=================================================================\n";
    
    if (!empty($couponsByAffiliateCode)) {
        echo "✅ FIX CONFIRMED: Querying by 'affiliateCode' field works!\n";
        echo "✅ The API should now find the coupon and calculate stats correctly.\n";
        echo "✅ Expected stats for $testCode:\n";
        $data = $couponsByAffiliateCode[0]['data'];
        $usageCount = $data['usageCount'] ?? 0;
        echo "   - Total Referrals: $usageCount\n";
        echo "   - Total Earnings: ₹" . ($usageCount * 300) . "\n";
        echo "   - Monthly Earnings: Will be calculated from orders\n";
    } else {
        echo "❌ ISSUE: Still no coupon found. Check the affiliateCode field value.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
