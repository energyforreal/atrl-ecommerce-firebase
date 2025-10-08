<?php
// Monitor webhook activity and Firestore orders
header('Content-Type: application/json');

try {
    // Check if Firebase Admin SDK is available
    if (!class_exists('Google\Cloud\Firestore\FirestoreClient')) {
        throw new Exception('Firebase Admin SDK not available');
    }
    
    $serviceAccountPath = __DIR__ . '/api/firebase-service-account.json';
    if (!file_exists($serviceAccountPath)) {
        throw new Exception('Firebase service account file not found');
    }
    
    $firestore = new Google\Cloud\Firestore\FirestoreClient([
        'projectId' => 'e-commerce-1d40f',
        'keyFilePath' => $serviceAccountPath
    ]);
    
    // Get recent orders from Firestore
    $ordersSnapshot = $firestore->collection('orders')
        ->orderBy('createdAt', 'desc')
        ->limit(10)
        ->get();
    
    $orders = [];
    foreach ($ordersSnapshot as $doc) {
        $data = $doc->data();
        $orders[] = [
            'id' => $doc->id(),
            'orderId' => $data['orderId'] ?? 'N/A',
            'paymentId' => $data['razorpayPaymentId'] ?? 'N/A',
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'INR',
            'status' => $data['status'] ?? 'unknown',
            'source' => $data['source'] ?? 'unknown',
            'createdAt' => $data['createdAt'] ? $data['createdAt']->format('Y-m-d H:i:s') : 'N/A'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'orders_count' => count($orders),
        'recent_orders' => $orders,
        'message' => 'Webhook monitoring active'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Firebase connection failed'
    ], JSON_PRETTY_PRINT);
}
?>
