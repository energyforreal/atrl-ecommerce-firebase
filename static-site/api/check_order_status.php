<?php
/**
 * 📊 Check Order Status API
 * 
 * For order-success.html to poll order status after payment
 * Returns order details after webhook has processed the payment
 * 
 * Compatible with Hostinger shared hosting
 * 
 * @version 1.0.0
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/firestore_rest_client.php';

try {
    $orderId = $_GET['orderId'] ?? $_GET['order_id'] ?? '';
    $paymentId = $_GET['paymentId'] ?? $_GET['payment_id'] ?? '';
    
    if (!$orderId && !$paymentId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Order ID or Payment ID required'
        ]);
        exit;
    }
    
    error_log("ORDER STATUS CHECK: Checking for orderId={$orderId}, paymentId={$paymentId}");
    
    // Initialize Firestore REST client
    $client = new FirestoreRestClient(
        'e-commerce-1d40f',
        __DIR__ . '/firebase-service-account.json',
        true
    );
    
    $order = null;
    
    // Try to find order by Razorpay Order ID
    if ($orderId) {
        $orders = $client->queryDocuments('orders', [
            ['field' => 'razorpayOrderId', 'op' => 'EQUAL', 'value' => $orderId]
        ], 1);
        
        if (!empty($orders)) {
            $order = $orders[0];
        }
    }
    
    // Try to find by payment ID if not found
    if (!$order && $paymentId) {
        $orders = $client->queryDocuments('orders', [
            ['field' => 'razorpayPaymentId', 'op' => 'EQUAL', 'value' => $paymentId]
        ], 1);
        
        if (!empty($orders)) {
            $order = $orders[0];
        }
    }
    
    // Try to find by orderId field (business order number like ATRL-0001)
    if (!$order && $orderId) {
        $orders = $client->queryDocuments('orders', [
            ['field' => 'orderId', 'op' => 'EQUAL', 'value' => $orderId]
        ], 1);
        
        if (!empty($orders)) {
            $order = $orders[0];
        }
    }
    
    if (!$order) {
        error_log("ORDER STATUS CHECK: Order not found");
        echo json_encode([
            'success' => true,
            'exists' => false,
            'message' => 'Order not yet processed (webhook may still be processing)',
            'orderId' => $orderId,
            'paymentId' => $paymentId
        ]);
        exit;
    }
    
    $orderData = $order['data'];
    
    error_log("ORDER STATUS CHECK: ✅ Found order {$orderData['orderId']} - Status: {$orderData['status']}");
    
    // Return order data (sanitized for client)
    echo json_encode([
        'success' => true,
        'exists' => true,
        'order' => [
            'id' => $order['id'],
            'orderId' => $orderData['orderId'],
            'orderNumber' => $orderData['orderId'],
            'status' => $orderData['status'],
            'amount' => $orderData['amount'],
            'currency' => $orderData['currency'],
            'customer' => [
                'firstName' => $orderData['customer']['firstName'],
                'lastName' => $orderData['customer']['lastName'],
                'email' => $orderData['customer']['email']
            ],
            'product' => $orderData['product'],
            'shipping' => $orderData['shipping'],
            'createdAt' => $orderData['createdAt'] ?? null,
            'estimatedDelivery' => '2-5 business days'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("ORDER STATUS CHECK ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to check order status',
        'details' => $e->getMessage()
    ]);
}
?>

