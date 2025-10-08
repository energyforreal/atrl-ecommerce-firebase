<?php
// Check database for recent orders
header('Content-Type: application/json');

try {
    $pdo = new PDO('sqlite:api/orders.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get recent orders
    $stmt = $pdo->query('SELECT razorpay_order_id, order_number, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5');
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'orders_count' => count($orders),
        'recent_orders' => $orders
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
