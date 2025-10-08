<?php
/**
 * 🧪 Test Invoice Generation with PDF Attachment
 * Test complete invoice flow: Generate PDF → Save → Attach to Email → Send
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Include required files
require_once __DIR__ . '/generate_invoice.php';

try {
    // Get test email from request or use default
    $input = json_decode(file_get_contents('php://input'), true);
    $testEmail = $input['email'] ?? 'attralsolar@gmail.com';
    
    // Create test order data
    $testOrderId = 'TEST_' . date('YmdHis');
    $testOrderData = [
        'orderId' => $testOrderId,
        'razorpay_order_id' => $testOrderId,
        'customer' => [
            'firstName' => 'Test',
            'lastName' => 'Customer',
            'email' => $testEmail,
            'phone' => '+91 9876543210'
        ],
        'shipping' => [
            'address' => '123 Test Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'country' => 'India'
        ],
        'product' => [
            'title' => 'ATTRAL GaN 65W Fast Charger',
            'price' => 2499,
            'quantity' => 1,
            'image' => 'https://attral.in/images/products/gan-charger.jpg'
        ],
        'pricing' => [
            'subtotal' => 2499,
            'shipping' => 0,
            'discount' => 0,
            'total' => 2499,
            'currency' => 'INR'
        ],
        'payment' => [
            'method' => 'Razorpay',
            'transaction_id' => 'txn_' . $testOrderId,
            'status' => 'completed'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Starting invoice generation and email test...',
        'test_data' => [
            'order_id' => $testOrderId,
            'test_email' => $testEmail,
            'order_total' => '₹2,499.00'
        ]
    ]);
    
    // Test the complete invoice flow
    $invoiceGenerator = new InvoiceGenerator();
    $result = $invoiceGenerator->generateAndSendInvoice($testOrderId);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Invoice generated and email sent successfully!',
            'details' => [
                'order_id' => $testOrderId,
                'test_email' => $testEmail,
                'pdf_generated' => true,
                'email_sent' => true,
                'attachment_included' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invoice test failed: ' . ($result['error'] ?? 'Unknown error'),
            'details' => [
                'order_id' => $testOrderId,
                'test_email' => $testEmail,
                'error_message' => $result['error'] ?? 'Unknown error'
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("INVOICE TEST ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Test exception: ' . $e->getMessage()
    ]);
}
?>
