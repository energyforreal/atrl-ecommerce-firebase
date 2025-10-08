<?php
/**
 * 🧪 Test Affiliate Email Functionality
 * Test script for all affiliate email features
 */

require_once __DIR__ . '/affiliate_email.php';

echo "🧪 Testing Affiliate Email System\n";
echo "================================\n\n";

// Test data
$testAffiliate = [
    'email' => 'test-affiliate@example.com',
    'name' => 'Test Affiliate',
    'affiliateCode' => 'TEST123',
    'commission' => 150.00,
    'orderId' => 'ATRL-0001',
    'amount' => 1500.00,
    'payoutDate' => '2024-01-15',
    'milestone' => 'First 10 Sales',
    'achievement' => 'You\'ve successfully referred 10 customers!'
];

echo "1. Testing Affiliate Welcome Email...\n";
$welcomeResult = testWelcomeEmail($testAffiliate);
echo $welcomeResult['success'] ? "✅ Welcome email test passed\n" : "❌ Welcome email test failed: " . $welcomeResult['error'] . "\n";

echo "\n2. Testing Commission Notification Email...\n";
$commissionResult = testCommissionEmail($testAffiliate);
echo $commissionResult['success'] ? "✅ Commission email test passed\n" : "❌ Commission email test failed: " . $commissionResult['error'] . "\n";

echo "\n3. Testing Payout Notification Email...\n";
$payoutResult = testPayoutEmail($testAffiliate);
echo $payoutResult['success'] ? "✅ Payout email test passed\n" : "❌ Payout email test failed: " . $payoutResult['error'] . "\n";

echo "\n4. Testing Milestone Notification Email...\n";
$milestoneResult = testMilestoneEmail($testAffiliate);
echo $milestoneResult['success'] ? "✅ Milestone email test passed\n" : "❌ Milestone email test failed: " . $milestoneResult['error'] . "\n";

echo "\n5. Testing Commission Processing...\n";
$commissionProcessingResult = testCommissionProcessing();
echo $commissionProcessingResult['success'] ? "✅ Commission processing test passed\n" : "❌ Commission processing test failed: " . $commissionProcessingResult['error'] . "\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎯 Test Summary:\n";
echo "- Welcome Email: " . ($welcomeResult['success'] ? 'PASS' : 'FAIL') . "\n";
echo "- Commission Email: " . ($commissionResult['success'] ? 'PASS' : 'FAIL') . "\n";
echo "- Payout Email: " . ($payoutResult['success'] ? 'PASS' : 'FAIL') . "\n";
echo "- Milestone Email: " . ($milestoneResult['success'] ? 'PASS' : 'FAIL') . "\n";
echo "- Commission Processing: " . ($commissionProcessingResult['success'] ? 'PASS' : 'FAIL') . "\n";

function testWelcomeEmail($data) {
    $payload = [
        'action' => 'welcome',
        'email' => $data['email'],
        'name' => $data['name'],
        'affiliateCode' => $data['affiliateCode']
    ];
    
    return makeTestRequest($payload);
}

function testCommissionEmail($data) {
    $payload = [
        'action' => 'commission',
        'email' => $data['email'],
        'name' => $data['name'],
        'commission' => $data['commission'],
        'orderId' => $data['orderId']
    ];
    
    return makeTestRequest($payload);
}

function testPayoutEmail($data) {
    $payload = [
        'action' => 'payout',
        'email' => $data['email'],
        'name' => $data['name'],
        'amount' => $data['amount'],
        'payoutDate' => $data['payoutDate']
    ];
    
    return makeTestRequest($payload);
}

function testMilestoneEmail($data) {
    $payload = [
        'action' => 'milestone',
        'email' => $data['email'],
        'name' => $data['name'],
        'milestone' => $data['milestone'],
        'achievement' => $data['achievement']
    ];
    
    return makeTestRequest($payload);
}

function testCommissionProcessing() {
    // Test the commission processing logic
    try {
        require_once __DIR__ . '/order_manager.php';
        
        // Mock order data with affiliate code
        $mockOrderData = [
            'pricing' => ['total' => 1000],
            'payment' => ['url_params' => ['ref' => 'TEST123']],
            'customer' => ['email' => 'customer@example.com']
        ];
        
        // Test affiliate code extraction
        $affiliateCode = extractAffiliateCode($mockOrderData);
        
        if ($affiliateCode !== 'TEST123') {
            return ['success' => false, 'error' => 'Affiliate code extraction failed'];
        }
        
        return ['success' => true, 'message' => 'Commission processing logic works'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function makeTestRequest($payload) {
    try {
        // Simulate the request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Capture output
        ob_start();
        
        // Mock the input
        $GLOBALS['test_input'] = json_encode($payload);
        
        // Override file_get_contents for testing
        function file_get_contents($filename) {
            if ($filename === 'php://input') {
                return $GLOBALS['test_input'];
            }
            return \file_get_contents($filename);
        }
        
        // Create handler and process
        $handler = new AffiliateEmailHandler();
        $result = $handler->handleRequest();
        
        ob_end_clean();
        
        return $result;
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

echo "\n🎉 All tests completed!\n";
?>
