<?php
/**
 * Database Structure Reader
 * This script reads your Firestore database to understand the actual structure
 * Run locally: php read_database_structure.php
 * Or visit: http://localhost/api/read_database_structure.php?code=attral-71hlzssgan
 */

header('Content-Type: text/plain; charset=utf-8');

// Load composer autoload
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    die("❌ ERROR: Composer autoload not found. Run 'composer install' first.\n");
}
require_once $composerAutoload;

use Kreait\Firebase\Factory;

try {
    $code = $_GET['code'] ?? 'attral-71hlzssgan';
    
    echo "=================================================================\n";
    echo "FIRESTORE DATABASE STRUCTURE READER\n";
    echo "=================================================================\n\n";
    
    // Initialize Firestore
    $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
    if (!file_exists($serviceAccountPath)) {
        die("❌ ERROR: Firebase service account file not found at: $serviceAccountPath\n");
    }
    
    echo "Initializing Firebase connection...\n";
    $factory = (new Factory())->withServiceAccount($serviceAccountPath);
    $firestore = $factory->createFirestore()->database();
    
    echo "✅ Connected to Firestore successfully!\n\n";
    
    // ============================================
    // 1. CHECK AFFILIATES COLLECTION
    // ============================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "1. AFFILIATES COLLECTION\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Try to find affiliate by code
    echo "Searching for affiliate with code: $code\n\n";
    
    $affiliatesQuery = $firestore->collection('affiliates')
        ->where('code', '=', $code)
        ->limit(1)
        ->documents();
    
    $affiliateFound = false;
    foreach ($affiliatesQuery as $doc) {
        if ($doc->exists()) {
            $affiliateFound = true;
            $data = $doc->data();
            
            echo "✅ FOUND AFFILIATE PROFILE:\n";
            echo "   Document ID: " . $doc->id() . "\n";
            echo "   Fields present:\n";
            
            foreach ($data as $key => $value) {
                if (is_object($value)) {
                    echo "   - $key: [Timestamp/Object]\n";
                } elseif (is_array($value)) {
                    echo "   - $key: [Array with " . count($value) . " items]\n";
                } else {
                    echo "   - $key: $value\n";
                }
            }
            
            echo "\n   Full data:\n";
            echo "   " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    
    if (!$affiliateFound) {
        echo "❌ NO AFFILIATE FOUND with code='$code'\n\n";
        
        // Try to find by UID instead
        echo "Trying to find by UID: pmgdmZlqp0ZomN2wz1eTsQqeBGg1\n\n";
        
        $uidDoc = $firestore->collection('affiliates')->document('pmgdmZlqp0ZomN2wz1eTsQqeBGg1')->snapshot();
        
        if ($uidDoc->exists()) {
            echo "✅ FOUND AFFILIATE BY UID:\n";
            $data = $uidDoc->data();
            echo "   " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "❌ NO AFFILIATE FOUND by UID either\n\n";
        }
    }
    
    echo "\n";
    
    // List all affiliates to see structure
    echo "Listing ALL affiliates (max 5):\n\n";
    
    $allAffiliates = $firestore->collection('affiliates')
        ->limit(5)
        ->documents();
    
    $count = 0;
    foreach ($allAffiliates as $doc) {
        if ($doc->exists()) {
            $count++;
            $data = $doc->data();
            
            echo "Affiliate #$count:\n";
            echo "   Document ID: " . $doc->id() . "\n";
            echo "   code: " . ($data['code'] ?? 'NOT SET') . "\n";
            echo "   email: " . ($data['email'] ?? 'NOT SET') . "\n";
            echo "   uid: " . ($data['uid'] ?? 'NOT SET') . "\n";
            echo "   status: " . ($data['status'] ?? 'NOT SET') . "\n";
            
            // Show all fields
            echo "   All fields: " . implode(', ', array_keys($data)) . "\n\n";
        }
    }
    
    if ($count === 0) {
        echo "⚠️  NO AFFILIATES FOUND in database!\n\n";
    }
    
    // ============================================
    // 2. CHECK COUPONS COLLECTION
    // ============================================
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "2. COUPONS COLLECTION\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "Searching for coupon with code: $code\n\n";
    
    $couponsQuery = $firestore->collection('coupons')
        ->where('code', '=', $code)
        ->limit(1)
        ->documents();
    
    $couponFound = false;
    foreach ($couponsQuery as $doc) {
        if ($doc->exists()) {
            $couponFound = true;
            $data = $doc->data();
            
            echo "✅ FOUND COUPON:\n";
            echo "   Document ID: " . $doc->id() . "\n";
            echo "   code: " . ($data['code'] ?? 'NOT SET') . "\n";
            echo "   usageCount: " . ($data['usageCount'] ?? 0) . "\n";
            echo "   payoutUsage: ₹" . ($data['payoutUsage'] ?? 0) . "\n";
            echo "   type: " . ($data['type'] ?? 'NOT SET') . "\n";
            echo "   active: " . (($data['active'] ?? false) ? 'true' : 'false') . "\n";
            
            echo "\n   Full data:\n";
            echo "   " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    
    if (!$couponFound) {
        echo "❌ NO COUPON FOUND with code='$code'\n\n";
        
        // List some coupons to see structure
        echo "Listing some coupons (max 3):\n\n";
        
        $someCoupons = $firestore->collection('coupons')
            ->limit(3)
            ->documents();
        
        $couponCount = 0;
        foreach ($someCoupons as $doc) {
            if ($doc->exists()) {
                $couponCount++;
                $data = $doc->data();
                
                echo "Coupon #$couponCount:\n";
                echo "   code: " . ($data['code'] ?? 'NOT SET') . "\n";
                echo "   usageCount: " . ($data['usageCount'] ?? 0) . "\n";
                echo "   payoutUsage: " . ($data['payoutUsage'] ?? 0) . "\n";
                echo "   All fields: " . implode(', ', array_keys($data)) . "\n\n";
            }
        }
    }
    
    // ============================================
    // 3. CHECK ORDERS COLLECTION
    // ============================================
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "3. ORDERS COLLECTION\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "Searching for orders with coupon code: $code\n\n";
    
    // Get recent confirmed orders
    $ordersQuery = $firestore->collection('orders')
        ->where('status', '=', 'confirmed')
        ->orderBy('createdAt', 'DESC')
        ->limit(10)
        ->documents();
    
    $matchedOrders = 0;
    $totalOrders = 0;
    
    foreach ($ordersQuery as $doc) {
        if ($doc->exists()) {
            $totalOrders++;
            $data = $doc->data();
            
            // Check if this order has the affiliate coupon
            $coupons = $data['coupons'] ?? [];
            $hasAffiliateCoupon = false;
            
            foreach ($coupons as $coupon) {
                if (isset($coupon['code']) && $coupon['code'] === $code) {
                    $hasAffiliateCoupon = true;
                    break;
                }
            }
            
            if ($hasAffiliateCoupon) {
                $matchedOrders++;
                
                if ($matchedOrders === 1) {
                    echo "✅ FOUND ORDER WITH AFFILIATE COUPON:\n";
                    echo "   Order ID: " . $doc->id() . "\n";
                    echo "   orderId: " . ($data['orderId'] ?? 'NOT SET') . "\n";
                    echo "   amount: ₹" . ($data['amount'] ?? 0) . "\n";
                    echo "   status: " . ($data['status'] ?? 'NOT SET') . "\n";
                    
                    // Show customer structure
                    if (isset($data['customer'])) {
                        echo "   customer structure:\n";
                        echo "      " . json_encode($data['customer'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                    }
                    
                    // Show coupons structure
                    echo "   coupons structure:\n";
                    echo "      " . json_encode($coupons, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
                }
            }
        }
    }
    
    echo "Summary:\n";
    echo "   Total confirmed orders checked: $totalOrders\n";
    echo "   Orders with coupon '$code': $matchedOrders\n\n";
    
    if ($matchedOrders === 0 && $totalOrders > 0) {
        echo "Showing structure of first order for reference:\n";
        
        $firstOrder = $firestore->collection('orders')
            ->where('status', '=', 'confirmed')
            ->limit(1)
            ->documents();
        
        foreach ($firstOrder as $doc) {
            if ($doc->exists()) {
                $data = $doc->data();
                echo "   Fields in order: " . implode(', ', array_keys($data)) . "\n";
                
                if (isset($data['coupons'])) {
                    echo "   Coupons: " . json_encode($data['coupons'], JSON_PRETTY_PRINT) . "\n";
                }
                
                if (isset($data['customer'])) {
                    echo "   Customer fields: " . implode(', ', array_keys($data['customer'])) . "\n";
                }
            }
        }
    }
    
    // ============================================
    // SUMMARY
    // ============================================
    echo "\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "SUMMARY\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "For affiliate code: $code\n";
    echo "   Affiliate Profile: " . ($affiliateFound ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";
    echo "   Coupon Record: " . ($couponFound ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";
    echo "   Orders with coupon: $matchedOrders\n\n";
    
    if (!$affiliateFound) {
        echo "⚠️  ISSUE: Affiliate profile does not exist in database!\n";
        echo "   This is why the API returns 400 errors.\n";
        echo "   Solution: Create affiliate profile or upload the fixed code.\n\n";
    }
    
    echo "=================================================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

