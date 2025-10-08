<?php
/**
 * 📧 Trigger Order Emails API
 * This endpoint is called from the order success page to send emails and generate invoices
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load configuration
$cfg = @include __DIR__ . '/config.php';
$RAZORPAY_KEY_SECRET = ($cfg['RAZORPAY_KEY_SECRET'] ?? null) ?: getenv('RAZORPAY_KEY_SECRET') ?: '';

// Database configuration
$dbFile = __DIR__ . '/orders.db';
$pdo = new PDO("sqlite:$dbFile");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Include required files
require_once __DIR__ . '/order_manager.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['order_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID is required']);
        exit;
    }
    
    $orderId = $input['order_id'];
    $customerEmail = $input['customer_email'] ?? '';
    
    try {
        // Get order details from database
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE razorpay_order_id = ? OR order_number = ?");
        $stmt->execute([$orderId, $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }
        
        // Parse order data
        $orderData = [
            'customer' => json_decode($order['customer_data'], true),
            'product' => json_decode($order['product_data'], true),
            'pricing' => json_decode($order['pricing_data'], true),
            'shipping' => json_decode($order['shipping_data'], true),
            'payment' => json_decode($order['payment_data'], true)
        ];
        
        $orderNumber = $order['order_number'];
        $results = [
            'order_number' => $orderNumber,
            'emails_sent' => false,
            'invoice_generated' => false,
            'errors' => []
        ];
        
        // Generate PDF invoice
        try {
            $invoicePath = generateInvoicePDF($orderNumber, $orderData);
            if ($invoicePath) {
                $results['invoice_generated'] = true;
                error_log("SUCCESS PAGE: Invoice generated for order $orderNumber");
            }
        } catch (Exception $pdfError) {
            $results['errors'][] = 'PDF generation failed: ' . $pdfError->getMessage();
            error_log("SUCCESS PAGE PDF ERROR: " . $pdfError->getMessage());
        }
        
        // 📧 Email sending removed - handled by order-success.html page
        error_log("TRIGGER EMAILS: Email sending removed. Handled by order-success page.");
        $results['emails_sent'] = false;
        
        // Update order status to indicate emails have been sent
        try {
            $stmt = $pdo->prepare("UPDATE orders SET notes = ? WHERE id = ?");
            $notes = json_encode(['emails_sent_at' => date('Y-m-d H:i:s'), 'emails_triggered_from' => 'success_page']);
            $stmt->execute([$notes, $order['id']]);
        } catch (Exception $updateError) {
            error_log("SUCCESS PAGE: Failed to update order notes: " . $updateError->getMessage());
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Email processing completed',
            'results' => $results
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to process emails: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
