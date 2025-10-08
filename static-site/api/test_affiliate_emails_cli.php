<?php
/**
 * 🧪 Command Line Affiliate Email Testing
 * Run this script from terminal to test affiliate email functionality
 * Usage: php test_affiliate_emails_cli.php
 */

require_once __DIR__ . '/order_manager.php';
require_once __DIR__ . '/affiliate_email_sender.php';

echo "🧪 Affiliate Email Testing - Command Line\n";
echo "==========================================\n\n";

// Test affiliate code from your Firebase console
$testAffiliateCode = 'lokesh-9en4b82ktp';

echo "🔍 Testing Affiliate Lookup...\n";
echo "Affiliate Code: {$testAffiliateCode}\n\n";

// Step 1: Test affiliate lookup
echo "1️⃣ Looking up affiliate in Firestore...\n";
$affiliateInfo = getAffiliateByCode($testAffiliateCode);

if ($affiliateInfo) {
    echo "✅ Affiliate found!\n";
    echo "   ID: {$affiliateInfo['id']}\n";
    echo "   Email: {$affiliateInfo['email']}\n";
    echo "   Name: {$affiliateInfo['name']}\n";
    echo "   Code: {$affiliateInfo['code']}\n";
    echo "   Status: {$affiliateInfo['status']}\n\n";
} else {
    echo "❌ Affiliate not found!\n";
    echo "   Please check:\n";
    echo "   - Firebase service account file exists\n";
    echo "   - Firestore database is accessible\n";
    echo "   - Affiliate code exists in database\n\n";
    exit(1);
}

// Step 2: Test welcome email
echo "2️⃣ Testing Welcome Email...\n";
try {
    $result = sendAffiliateWelcomeEmail(null, [
        'email' => $affiliateInfo['email'],
        'name' => $affiliateInfo['name'],
        'affiliateCode' => $affiliateInfo['code']
    ]);
    
    if ($result['success']) {
        echo "✅ Welcome email sent successfully!\n";
        echo "   Recipient: {$affiliateInfo['email']}\n";
        echo "   Name: {$affiliateInfo['name']}\n";
    } else {
        echo "❌ Welcome email failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Welcome email exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Step 3: Test commission email
echo "3️⃣ Testing Commission Email...\n";
try {
    $commissionAmount = 150.00;
    $result = sendAffiliateCommissionEmail(null, [
        'email' => $affiliateInfo['email'],
        'name' => $affiliateInfo['name'],
        'commission' => $commissionAmount,
        'orderId' => 'ATRL-TEST-001'
    ]);
    
    if ($result['success']) {
        echo "✅ Commission email sent successfully!\n";
        echo "   Recipient: {$affiliateInfo['email']}\n";
        echo "   Commission: ₹{$commissionAmount}\n";
        echo "   Order ID: ATRL-TEST-001\n";
    } else {
        echo "❌ Commission email failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Commission email exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Step 4: Test payout email
echo "4️⃣ Testing Payout Email...\n";
try {
    $payoutAmount = 500.00;
    $result = sendAffiliatePayoutEmail(null, [
        'email' => $affiliateInfo['email'],
        'name' => $affiliateInfo['name'],
        'payoutAmount' => $payoutAmount,
        'payoutPeriod' => 'January 2024'
    ]);
    
    if ($result['success']) {
        echo "✅ Payout email sent successfully!\n";
        echo "   Recipient: {$affiliateInfo['email']}\n";
        echo "   Payout: ₹{$payoutAmount}\n";
        echo "   Period: January 2024\n";
    } else {
        echo "❌ Payout email failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Payout email exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Step 5: Test milestone email
echo "5️⃣ Testing Milestone Email...\n";
try {
    $result = sendAffiliateMilestoneEmail(null, [
        'email' => $affiliateInfo['email'],
        'name' => $affiliateInfo['name'],
        'milestone' => 'First 10 Sales',
        'achievement' => 'You\'ve reached your first 10 successful referrals!'
    ]);
    
    if ($result['success']) {
        echo "✅ Milestone email sent successfully!\n";
        echo "   Recipient: {$affiliateInfo['email']}\n";
        echo "   Milestone: First 10 Sales\n";
    } else {
        echo "❌ Milestone email failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Milestone email exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Step 6: Test complete order flow
echo "6️⃣ Testing Complete Order Flow...\n";
$testOrderData = [
    'orderId' => 'ATRL-TEST-002',
    'customer' => [
        'firstName' => 'Test',
        'lastName' => 'Customer',
        'email' => 'testcustomer@example.com'
    ],
    'product' => [
        'title' => 'Test Product',
        'price' => 1500.00
    ],
    'pricing' => [
        'total' => 1500.00,
        'currency' => 'INR'
    ],
    'payment' => [
        'url_params' => [
            'ref' => $testAffiliateCode
        ]
    ]
];

try {
    // Extract affiliate code
    $extractedCode = extractAffiliateCode($testOrderData);
    echo "   Extracted affiliate code: {$extractedCode}\n";
    
    if ($extractedCode === $testAffiliateCode) {
        echo "   ✅ Affiliate code extraction working\n";
        
        // Calculate commission
        $orderTotal = $testOrderData['pricing']['total'];
        $commissionAmount = $orderTotal * 0.10;
        echo "   Order total: ₹{$orderTotal}\n";
        echo "   Commission (10%): ₹{$commissionAmount}\n";
        
        // Send commission email
        $result = sendAffiliateCommissionEmail(null, [
            'email' => $affiliateInfo['email'],
            'name' => $affiliateInfo['name'],
            'commission' => $commissionAmount,
            'orderId' => $testOrderData['orderId']
        ]);
        
        if ($result['success']) {
            echo "   ✅ Complete flow working - commission email sent!\n";
        } else {
            echo "   ❌ Complete flow failed - email sending error\n";
        }
    } else {
        echo "   ❌ Affiliate code extraction failed\n";
    }
} catch (Exception $e) {
    echo "   ❌ Complete flow exception: " . $e->getMessage() . "\n";
}

echo "\n📊 TEST SUMMARY:\n";
echo "================\n";
echo "✅ Firestore Integration: Working\n";
echo "✅ Affiliate Lookup: Working\n";
echo "✅ Email Personalization: Using Firestore data\n";
echo "✅ Welcome Email: Tested\n";
echo "✅ Commission Email: Tested\n";
echo "✅ Payout Email: Tested\n";
echo "✅ Milestone Email: Tested\n";
echo "✅ Complete Order Flow: Tested\n";

echo "\n🎉 All tests completed!\n";
echo "Check the email inbox for {$affiliateInfo['email']} to verify email delivery.\n";
?>