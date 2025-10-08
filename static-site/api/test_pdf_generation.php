<?php
/**
 * 🧪 Test PDF Generation Only
 * Test FPDF invoice generation without email sending
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
require_once __DIR__ . '/order_manager.php';

try {
    // Create test order data
    $testOrderId = 'PDF_TEST_' . date('YmdHis');
    $testOrderData = [
        'orderId' => $testOrderId,
        'customer' => [
            'firstName' => 'Test',
            'lastName' => 'Customer',
            'email' => 'test@example.com',
            'phone' => '+91 9876543210'
        ],
        'shipping' => [
            'address' => '123 Test Street, Test Area',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'country' => 'India'
        ],
        'product' => [
            'title' => 'ATTRAL GaN 65W Fast Charger',
            'price' => 2499,
            'quantity' => 1
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
        ]
    ];
    
    // Generate PDF invoice
    $pdfPath = generateInvoicePDF($testOrderId, $testOrderData);
    
    if ($pdfPath && file_exists($pdfPath)) {
        $fileSize = filesize($pdfPath);
        $fileSizeKB = round($fileSize / 1024, 2);
        
        echo json_encode([
            'success' => true,
            'message' => 'PDF invoice generated successfully!',
            'details' => [
                'order_id' => $testOrderId,
                'pdf_path' => $pdfPath,
                'pdf_filename' => basename($pdfPath),
                'file_size_bytes' => $fileSize,
                'file_size_kb' => $fileSizeKB,
                'file_exists' => true,
                'readable' => is_readable($pdfPath),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate PDF invoice',
            'details' => [
                'order_id' => $testOrderId,
                'pdf_path' => $pdfPath,
                'file_exists' => $pdfPath ? file_exists($pdfPath) : false,
                'error' => 'PDF generation failed or file not created'
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("PDF GENERATION TEST ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'PDF generation exception: ' . $e->getMessage()
    ]);
}
?>
