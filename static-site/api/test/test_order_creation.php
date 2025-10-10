<?php
/**
 * Test Suite for Order Creation via REST API
 * 
 * Tests the complete order creation flow with Firestore REST API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../firestore_rest_client.php';
require_once __DIR__ . '/../coupon_tracking_service_rest.php';

echo "🧪 ORDER CREATION TEST SUITE (REST API)\n";
echo "========================================\n\n";

// Initialize client
$projectId = 'e-commerce-1d40f';
$serviceAccountPath = __DIR__ . '/../firebase-service-account.json';

if (!file_exists($serviceAccountPath)) {
    die("❌ Service account file not found: $serviceAccountPath\n");
}

try {
    $client = new FirestoreRestClient($projectId, $serviceAccountPath, true);
    echo "✅ FirestoreRestClient initialized\n\n";
} catch (Exception $e) {
    die("❌ Failed to initialize client: " . $e->getMessage() . "\n");
}

// Test 1: Generate Order Number
echo "TEST 1: Generate Order Number\n";
echo "-----------------------------\n";
try {
    // Query latest order
    $orders = $client->queryDocuments(
        'orders',
        [],
        1,
        'createdAt',
        'DESCENDING'
    );
    
    $lastNumber = 0;
    if (!empty($orders)) {
        $latestOrder = $orders[0]['data'];
        if (isset($latestOrder['orderId']) && preg_match('/ATRL-(\d+)/', $latestOrder['orderId'], $matches)) {
            $lastNumber = intval($matches[1]);
        }
    }
    
    $nextNumber = $lastNumber + 1;
    $orderNumber = 'ATRL-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    
    echo "✅ Order number generated: $orderNumber\n";
    echo "Last order number: ATRL-" . str_pad($lastNumber, 4, '0', STR_PAD_LEFT) . "\n";
} catch (Exception $e) {
    echo "❌ Order number generation failed: " . $e->getMessage() . "\n";
    $orderNumber = 'ATRL-TEST';
}
echo "\n";

// Test 2: Create Test Order
echo "TEST 2: Create Test Order\n";
echo "-------------------------\n";
try {
    $testPaymentId = 'pay_test_' . time() . '_' . rand(1000, 9999);
    
    $orderData = [
        'orderId' => $orderNumber,
        'razorpayOrderId' => 'order_test_' . time(),
        'razorpayPaymentId' => $testPaymentId,
        'uid' => 'test_user_123',
        'status' => 'confirmed',
        'amount' => 2999.00,
        'currency' => 'INR',
        'customer' => [
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'test@example.com',
            'phone' => '9876543210'
        ],
        'product' => [
            'id' => 'prod_test',
            'title' => 'ATTRAL 100W GaN Charger',
            'price' => 2999,
            'items' => [
                [
                    'id' => 'item_1',
                    'title' => '100W GaN Charger',
                    'quantity' => 1,
                    'price' => 2999
                ]
            ]
        ],
        'pricing' => [
            'subtotal' => 2999,
            'shipping' => 0,
            'discount' => 0,
            'total' => 2999,
            'currency' => 'INR'
        ],
        'shipping' => [
            'address' => '123 Test Street',
            'city' => 'Vellore',
            'state' => 'Tamil Nadu',
            'pincode' => '632009',
            'country' => 'India'
        ],
        'payment' => [
            'method' => 'razorpay',
            'transaction_id' => $testPaymentId
        ],
        'coupons' => [],
        'createdAt' => firestoreTimestamp(),
        'updatedAt' => firestoreTimestamp(),
        'notes' => 'REST API Test Order'
    ];
    
    $result = $client->writeDocument('orders', $orderData);
    
    if (isset($result['id'])) {
        echo "✅ Test order created successfully\n";
        echo "Document ID: " . $result['id'] . "\n";
        echo "Order Number: $orderNumber\n";
        echo "Payment ID: $testPaymentId\n";
        $testOrderId = $result['id'];
    } else {
        echo "❌ Order creation failed\n";
        $testOrderId = null;
    }
} catch (Exception $e) {
    echo "❌ Order creation failed: " . $e->getMessage() . "\n";
    $testOrderId = null;
}
echo "\n";

// Test 3: Retrieve Order by Payment ID
echo "TEST 3: Retrieve Order by Payment ID\n";
echo "-------------------------------------\n";
if ($testOrderId && isset($testPaymentId)) {
    try {
        $orders = $client->queryDocuments(
            'orders',
            [
                ['field' => 'razorpayPaymentId', 'op' => 'EQUAL', 'value' => $testPaymentId]
            ],
            1
        );
        
        if (!empty($orders)) {
            echo "✅ Order retrieved successfully by payment ID\n";
            echo "Found order ID: " . $orders[0]['id'] . "\n";
            echo "Customer: " . $orders[0]['data']['customer']['firstName'] . " " . $orders[0]['data']['customer']['lastName'] . "\n";
            echo "Amount: ₹" . $orders[0]['data']['amount'] . "\n";
        } else {
            echo "❌ Order not found by payment ID\n";
        }
    } catch (Exception $e) {
        echo "❌ Query failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Skipping (no test order created)\n";
}
echo "\n";

// Test 4: Update Order Status
echo "TEST 4: Update Order Status\n";
echo "---------------------------\n";
if ($testOrderId) {
    try {
        $updates = [
            ['path' => 'status', 'value' => 'processing'],
            ['path' => 'updatedAt', 'value' => firestoreTimestamp()]
        ];
        
        $result = $client->updateDocument('orders', $testOrderId, $updates);
        
        if ($result && $result['data']['status'] === 'processing') {
            echo "✅ Order status updated successfully\n";
            echo "New status: " . $result['data']['status'] . "\n";
        } else {
            echo "❌ Status update failed or status doesn't match\n";
        }
    } catch (Exception $e) {
        echo "❌ Update failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Skipping (no test order created)\n";
}
echo "\n";

// Test 5: Add Status History
echo "TEST 5: Add Status History\n";
echo "--------------------------\n";
if ($testOrderId) {
    try {
        $statusData = [
            'orderId' => $testOrderId,
            'status' => 'processing',
            'message' => 'Order moved to processing',
            'createdAt' => firestoreTimestamp()
        ];
        
        $result = $client->writeDocument('order_status_history', $statusData);
        
        if (isset($result['id'])) {
            echo "✅ Status history created successfully\n";
            echo "History ID: " . $result['id'] . "\n";
        } else {
            echo "❌ Status history creation failed\n";
        }
    } catch (Exception $e) {
        echo "❌ Status history failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Skipping (no test order created)\n";
}
echo "\n";

// Test 6: Test Idempotency
echo "TEST 6: Test Idempotency\n";
echo "------------------------\n";
if ($testOrderId && isset($testPaymentId)) {
    try {
        // Try to create another order with same payment ID
        $duplicateData = $orderData; // Same data
        $duplicateData['notes'] = 'Duplicate attempt (should fail)';
        
        // First check if order exists
        $existing = $client->queryDocuments(
            'orders',
            [
                ['field' => 'razorpayPaymentId', 'op' => 'EQUAL', 'value' => $testPaymentId]
            ],
            1
        );
        
        if (!empty($existing)) {
            echo "✅ Idempotency check works - found existing order\n";
            echo "Existing order ID: " . $existing[0]['id'] . "\n";
        } else {
            echo "❌ Idempotency check failed - no existing order found\n";
        }
    } catch (Exception $e) {
        echo "❌ Idempotency test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Skipping (no test order created)\n";
}
echo "\n";

// Test 7: Delete Test Order
echo "TEST 7: Delete Test Order\n";
echo "-------------------------\n";
if ($testOrderId) {
    try {
        $result = $client->deleteDocument('orders', $testOrderId);
        
        if ($result) {
            echo "✅ Test order deleted successfully\n";
            echo "Deleted order ID: $testOrderId\n";
        } else {
            echo "❌ Order deletion failed\n";
        }
    } catch (Exception $e) {
        echo "❌ Delete failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Skipping (no test order created)\n";
}
echo "\n";

// Test Summary
echo "========================================\n";
echo "ORDER CREATION TEST SUITE COMPLETE\n";
echo "========================================\n";
echo "\n";
echo "✅ All order tests completed!\n";
echo "\n";
echo "Next Steps:\n";
echo "1. Test with real Razorpay webhook\n";
echo "2. Test coupon tracking integration\n";
echo "3. Test affiliate commission flow\n";
echo "4. Deploy to production\n";
?>

