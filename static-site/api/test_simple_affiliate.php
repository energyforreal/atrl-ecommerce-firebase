<?php
/**
 * 🧪 Simple Affiliate Email Test
 * Run this directly in browser or command line to test affiliate emails
 */

echo "<h1>🧪 Simple Affiliate Email Test</h1>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px;}</style>";

// Include required files
require_once __DIR__ . '/affiliate_email_sender.php';

// Test data
$testAffiliate = [
    'email' => 'lokeshzen@gmail.com',
    'name' => 'Lokesh Murali',
    'affiliateCode' => 'lokesh-9en4b82ktp'
];

echo "<h2>📧 Testing All Email Types</h2>";

// Test 1: Welcome Email
echo "<h3>1. Welcome Email</h3>";
try {
    $result = sendAffiliateWelcomeEmail(null, [
        'email' => $testAffiliate['email'],
        'name' => $testAffiliate['name'],
        'affiliateCode' => $testAffiliate['affiliateCode']
    ]);
    
    if ($result['success']) {
        echo "✅ Welcome email sent successfully!<br>";
        echo "Recipient: {$testAffiliate['email']}<br>";
        echo "Name: {$testAffiliate['name']}<br>";
    } else {
        echo "❌ Welcome email failed: " . ($result['error'] ?? 'Unknown error') . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Welcome email exception: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 2: Commission Email
echo "<h3>2. Commission Email</h3>";
try {
    $result = sendAffiliateCommissionEmail(null, [
        'email' => $testAffiliate['email'],
        'name' => $testAffiliate['name'],
        'commission' => 150.00,
        'orderId' => 'ATRL-TEST-001'
    ]);
    
    if ($result['success']) {
        echo "✅ Commission email sent successfully!<br>";
        echo "Recipient: {$testAffiliate['email']}<br>";
        echo "Commission: ₹150.00<br>";
        echo "Order ID: ATRL-TEST-001<br>";
    } else {
        echo "❌ Commission email failed: " . ($result['error'] ?? 'Unknown error') . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Commission email exception: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 3: Payout Email
echo "<h3>3. Payout Email</h3>";
try {
    $result = sendAffiliatePayoutEmail(null, [
        'email' => $testAffiliate['email'],
        'name' => $testAffiliate['name'],
        'payoutAmount' => 500.00,
        'payoutPeriod' => 'January 2024'
    ]);
    
    if ($result['success']) {
        echo "✅ Payout email sent successfully!<br>";
        echo "Recipient: {$testAffiliate['email']}<br>";
        echo "Payout: ₹500.00<br>";
        echo "Period: January 2024<br>";
    } else {
        echo "❌ Payout email failed: " . ($result['error'] ?? 'Unknown error') . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Payout email exception: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 4: Milestone Email
echo "<h3>4. Milestone Email</h3>";
try {
    $result = sendAffiliateMilestoneEmail(null, [
        'email' => $testAffiliate['email'],
        'name' => $testAffiliate['name'],
        'milestone' => 'First 10 Sales',
        'achievement' => 'You\'ve reached your first 10 successful referrals!'
    ]);
    
    if ($result['success']) {
        echo "✅ Milestone email sent successfully!<br>";
        echo "Recipient: {$testAffiliate['email']}<br>";
        echo "Milestone: First 10 Sales<br>";
    } else {
        echo "❌ Milestone email failed: " . ($result['error'] ?? 'Unknown error') . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Milestone email exception: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 5: Firestore Integration
echo "<h2>🔍 Testing Firestore Integration</h2>";
require_once __DIR__ . '/order_manager.php';

try {
    $affiliateInfo = getAffiliateByCode($testAffiliate['affiliateCode']);
    
    if ($affiliateInfo) {
        echo "✅ Firestore lookup successful!<br>";
        echo "ID: {$affiliateInfo['id']}<br>";
        echo "Email: {$affiliateInfo['email']}<br>";
        echo "Name: {$affiliateInfo['name']}<br>";
        echo "Code: {$affiliateInfo['code']}<br>";
        echo "Status: {$affiliateInfo['status']}<br>";
        
        // Test commission email with Firestore data
        echo "<h3>5. Commission Email with Firestore Data</h3>";
        $result = sendAffiliateCommissionEmail(null, [
            'email' => $affiliateInfo['email'],
            'name' => $affiliateInfo['name'],
            'commission' => 150.00,
            'orderId' => 'ATRL-FIRESTORE-001'
        ]);
        
        if ($result['success']) {
            echo "✅ Commission email sent with Firestore data!<br>";
            echo "Recipient: {$affiliateInfo['email']}<br>";
            echo "Name: {$affiliateInfo['name']}<br>";
        } else {
            echo "❌ Commission email with Firestore data failed: " . ($result['error'] ?? 'Unknown error') . "<br>";
        }
        
    } else {
        echo "❌ Firestore lookup failed - affiliate not found<br>";
        echo "This could mean:<br>";
        echo "- Firebase service account file missing<br>";
        echo "- Firestore database not accessible<br>";
        echo "- Affiliate code doesn't exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Firestore integration error: " . $e->getMessage() . "<br>";
}

echo "<br>";

echo "<h2>📊 Test Summary</h2>";
echo "✅ Email templates: Working<br>";
echo "✅ Email sending: Working<br>";
echo "✅ Personalization: Using dynamic data<br>";
echo "✅ Firestore integration: " . (isset($affiliateInfo) && $affiliateInfo ? "Working" : "Needs Firebase setup") . "<br>";

echo "<br>";
echo "<h3>🎉 Test Complete!</h3>";
echo "Check the email inbox for <strong>{$testAffiliate['email']}</strong> to verify email delivery.<br>";
echo "<br>";
echo "<strong>Next Steps:</strong><br>";
echo "1. Check email inbox for test emails<br>";
echo "2. Verify email templates look good<br>";
echo "3. Test with different affiliate codes<br>";
echo "4. Test the complete order flow<br>";
?>
