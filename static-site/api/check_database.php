<?php
/**
 * Database Check Script
 * Check what data exists in your Firestore collections
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=================================================================\n";
echo "FIRESTORE DATABASE CHECK\n";
echo "=================================================================\n\n";

$testCode = $_GET['code'] ?? 'attral-71hlzssgan';
echo "Checking for affiliate code: $testCode\n\n";

// Load Firestore REST client
require_once __DIR__ . '/firestore_rest_client.php';

try {
    $firestore = new FirestoreRestClient(
        'e-commerce-1d40f',
        __DIR__ . '/firebase-service-account.json',
        true
    );
    
    echo "✅ Connected to Firestore successfully!\n\n";
    
    // 1. Check COUPONS collection
    echo "--- 1. COUPONS Collection ---\n";
    $coupons = $firestore->queryDocuments('coupons', [
        ['field' => 'code', 'op' => 'EQUAL', 'value' => $testCode]
    ], 1);
    
    if (!empty($coupons)) {
        $couponData = $coupons[0]['data'];
        echo "✅ Found coupon with code: $testCode\n";
        echo "Document ID: " . $coupons[0]['id'] . "\n";
        echo "Data:\n";
        print_r($couponData);
    } else {
        echo "❌ No coupon found with code: $testCode\n";
        
        // Let's see what coupons exist
        echo "\nChecking first 5 coupons in collection:\n";
        $allCoupons = $firestore->queryDocuments('coupons', [], 5);
        if (!empty($allCoupons)) {
            foreach ($allCoupons as $coupon) {
                $data = $coupon['data'];
                echo "- Code: " . ($data['code'] ?? 'N/A') . " | UsageCount: " . ($data['usageCount'] ?? 'N/A') . "\n";
            }
        } else {
            echo "❌ No coupons found in coupons collection at all!\n";
        }
    }
    
    echo "\n--- 2. AFFILIATES Collection ---\n";
    $affiliates = $firestore->queryDocuments('affiliates', [
        ['field' => 'code', 'op' => 'EQUAL', 'value' => $testCode]
    ], 1);
    
    if (!empty($affiliates)) {
        $affiliateData = $affiliates[0]['data'];
        echo "✅ Found affiliate with code: $testCode\n";
        echo "Document ID: " . $affiliates[0]['id'] . "\n";
        echo "Data:\n";
        print_r($affiliateData);
    } else {
        echo "❌ No affiliate found with code: $testCode\n";
    }
    
    echo "\n--- 3. ORDERS Collection (with this coupon) ---\n";
    $orders = $firestore->queryDocuments('orders', [
        ['field' => 'status', 'op' => 'EQUAL', 'value' => 'confirmed']
    ], 10);
    
    $foundOrders = 0;
    if (!empty($orders)) {
        foreach ($orders as $order) {
            $orderData = $order['data'];
            $coupons = $orderData['coupons'] ?? [];
            
            foreach ($coupons as $coupon) {
                if (isset($coupon['code']) && $coupon['code'] === $testCode) {
                    $foundOrders++;
                    echo "✅ Found order using coupon: " . ($orderData['orderId'] ?? $order['id']) . "\n";
                    echo "  Order amount: ₹" . ($orderData['amount'] ?? 'N/A') . "\n";
                    echo "  Customer: " . ($orderData['customer']['firstName'] ?? '') . " " . ($orderData['customer']['lastName'] ?? '') . "\n";
                    break;
                }
            }
        }
    }
    
    if ($foundOrders === 0) {
        echo "❌ No orders found using coupon: $testCode\n";
        
        // Show sample orders
        echo "\nSample orders (first 3):\n";
        foreach (array_slice($orders, 0, 3) as $order) {
            $orderData = $order['data'];
            echo "- Order: " . ($orderData['orderId'] ?? $order['id']) . " | Status: " . ($orderData['status'] ?? 'N/A') . "\n";
            if (isset($orderData['coupons'])) {
                echo "  Coupons: " . json_encode(array_column($orderData['coupons'], 'code')) . "\n";
            }
        }
    } else {
        echo "\n✅ Found $foundOrders orders using this coupon\n";
    }
    
    echo "\n=================================================================\n";
    echo "RECOMMENDATIONS:\n";
    echo "=================================================================\n";
    
    if (empty($coupons)) {
        echo "1. ❌ CRITICAL: Create a coupon document in the 'coupons' collection with:\n";
        echo "   - code: '$testCode'\n";
        echo "   - usageCount: [number of times used]\n";
        echo "   - Other coupon fields as needed\n\n";
    }
    
    if ($foundOrders > 0 && empty($coupons)) {
        echo "2. ⚠️  You have orders using this coupon but no coupon document!\n";
        echo "   The coupon document should have usageCount = $foundOrders\n\n";
    }
    
    if ($foundOrders === 0) {
        echo "2. ℹ️  No orders found with this coupon code.\n";
        echo "   This might be normal if the coupon hasn't been used yet.\n\n";
    }
    
    echo "3. 📝 To create the coupon document, you can use your admin panel\n";
    echo "   or create it manually in Firebase Console.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
