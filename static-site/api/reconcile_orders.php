<?php
/**
 * Order Reconciliation Script
 * 
 * This script helps reconcile missing orders between:
 * - Razorpay payments
 * - Server database (SQLite)
 * - Firestore
 * 
 * Run this script to identify and fix missing orders.
 */

require_once __DIR__ . '/config.php';

// Load configuration
$cfg = @include __DIR__.'/config.php';
$RAZORPAY_KEY_ID = $cfg['RAZORPAY_KEY_ID'] ?? '';
$RAZORPAY_KEY_SECRET = $cfg['RAZORPAY_KEY_SECRET'] ?? '';

// Initialize database
$dbFile = __DIR__ . '/orders.db';
$pdo = new PDO("sqlite:$dbFile");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "🔍 ATTRAL Order Reconciliation Script\n";
echo "=====================================\n\n";

// 1. Check server database orders
echo "1. Server Database Orders:\n";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders");
$stmt->execute();
$serverCount = $stmt->fetch()['count'];
echo "   Total orders in server DB: $serverCount\n";

if ($serverCount > 0) {
    $stmt = $pdo->prepare("SELECT order_number, razorpay_payment_id, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Recent orders:\n";
    foreach ($recentOrders as $order) {
        echo "   - {$order['order_number']} ({$order['razorpay_payment_id']}) - {$order['status']} - {$order['created_at']}\n";
    }
}
echo "\n";

// 2. Check Firestore fallback file
echo "2. Firestore Fallback File:\n";
$fallbackFile = __DIR__ . '/firestore_fallback.json';
if (file_exists($fallbackFile)) {
    $fallbackData = json_decode(file_get_contents($fallbackFile), true);
    $fallbackCount = count($fallbackData);
    echo "   Orders in fallback file: $fallbackCount\n";
    
    if ($fallbackCount > 0) {
        echo "   Recent fallback orders:\n";
        $recent = array_slice($fallbackData, -3);
        foreach ($recent as $order) {
            echo "   - {$order['orderId']} ({$order['razorpayPaymentId']}) - {$order['status']}\n";
        }
    }
} else {
    echo "   No fallback file found\n";
}
echo "\n";

// 3. Check for specific payment ID
$paymentId = $argv[1] ?? null;
if ($paymentId) {
    echo "3. Checking Payment ID: $paymentId\n";
    
    // Check server database
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE razorpay_payment_id = ?");
    $stmt->execute([$paymentId]);
    $serverOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($serverOrder) {
        echo "   ✅ Found in server database: {$serverOrder['order_number']}\n";
    } else {
        echo "   ❌ Not found in server database\n";
    }
    
    // Check fallback file
    if (file_exists($fallbackFile)) {
        $fallbackData = json_decode(file_get_contents($fallbackFile), true);
        $found = false;
        foreach ($fallbackData as $order) {
            if ($order['razorpayPaymentId'] === $paymentId) {
                echo "   📝 Found in fallback file: {$order['orderId']}\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "   ❌ Not found in fallback file\n";
        }
    }
    
    // Try to fetch from Razorpay
    if ($RAZORPAY_KEY_ID && $RAZORPAY_KEY_SECRET) {
        echo "   🔍 Fetching from Razorpay...\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/payments/$paymentId");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$RAZORPAY_KEY_ID:$RAZORPAY_KEY_SECRET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $payment = json_decode($response, true);
            $status = $payment['status'] ?? 'unknown';
            $amountInRupees = isset($payment['amount']) ? ($payment['amount'] / 100) : 0;
            $orderIdFromRzp = $payment['order_id'] ?? 'N/A';
            echo "   ✅ Found in Razorpay: $status - ₹$amountInRupees\n";
            echo "   Order ID: $orderIdFromRzp\n";
        } else {
            echo "   ❌ Not found in Razorpay (HTTP $httpCode)\n";
        }
    }
}
echo "\n";

// 4. Reconciliation suggestions
echo "4. Reconciliation Actions:\n";
echo "   To fix missing orders:\n";
echo "   1. Upload the updated order_manager.php to your server\n";
echo "   2. Install Firebase Admin SDK: composer install\n";
echo "   3. Download and place firebase-service-account.json\n";
echo "   4. Update Firestore security rules\n";
echo "   5. Test with a new payment\n";
echo "   6. Check server logs for Firestore write status\n";
echo "\n";

// 5. Manual order creation for specific payment
if ($paymentId && isset($serverOrder)) {
    echo "5. Manual Order Creation:\n";
    echo "   To manually create order for payment $paymentId:\n";
    echo "   Run: php create_manual_order.php $paymentId\n";
    echo "\n";
}

echo "✅ Reconciliation complete!\n";
?>
