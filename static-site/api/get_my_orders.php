<?php
/**
 * 📋 Get User Orders API
 * 
 * Returns order history for authenticated users
 * Used by my-orders.html page
 * 
 * Compatible with Hostinger shared hosting
 * 
 * @version 1.0.0
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/firestore_rest_client.php';

try {
    // Get user ID from request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $uid = $input['uid'] ?? '';
        $limit = $input['limit'] ?? 50;
    } else {
        $uid = $_GET['uid'] ?? '';
        $limit = $_GET['limit'] ?? 50;
    }
    
    if (!$uid) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'User ID required'
        ]);
        exit;
    }
    
    error_log("GET MY ORDERS: Fetching orders for user {$uid}");
    
    // Initialize Firestore REST client
    $client = new FirestoreRestClient(
        'e-commerce-1d40f',
        __DIR__ . '/firebase-service-account.json',
        true
    );
    
    // Query user's orders
    $orders = $client->queryDocuments('orders', [
        ['field' => 'uid', 'op' => 'EQUAL', 'value' => $uid]
    ], intval($limit), 'createdAt', 'DESCENDING');
    
    error_log("GET MY ORDERS: Found " . count($orders) . " orders for user {$uid}");
    
    // Format orders for client (sanitize sensitive data)
    $formattedOrders = [];
    
    foreach ($orders as $order) {
        $data = $order['data'];
        
        $formattedOrders[] = [
            'id' => $order['id'],
            'orderId' => $data['orderId'] ?? 'N/A',
            'orderNumber' => $data['orderId'] ?? 'N/A',
            'status' => $data['status'] ?? 'pending',
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'INR',
            'createdAt' => $data['createdAt'] ?? null,
            'updatedAt' => $data['updatedAt'] ?? null,
            'product' => [
                'title' => $data['product']['title'] ?? 'Product',
                'image' => $data['product']['image'] ?? '',
                'items' => $data['product']['items'] ?? []
            ],
            'shipping' => $data['shipping'] ?? [],
            'tracking' => [
                'status' => $data['status'] ?? 'confirmed',
                'estimatedDelivery' => '2-5 business days',
                'trackingNumber' => $data['trackingNumber'] ?? null
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $formattedOrders,
        'count' => count($formattedOrders),
        'uid' => $uid
    ]);
    
} catch (Exception $e) {
    error_log("GET MY ORDERS ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch orders',
        'details' => $e->getMessage()
    ]);
}
?>

