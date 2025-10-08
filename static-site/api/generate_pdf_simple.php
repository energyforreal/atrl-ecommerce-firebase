<?php
/**
 * Simple PDF Generation API
 * Standalone PDF generation without dependencies
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    if (!isset($input['orderId'])) {
        throw new Exception('orderId is required');
    }
    
    $orderId = $input['orderId'];
    
    // Load FPDF
    require_once __DIR__ . '/lib/fpdf/fpdf.php';
    
    // Create invoices directory if it doesn't exist
    $invoicesDir = __DIR__ . '/../invoices';
    if (!is_dir($invoicesDir)) {
        mkdir($invoicesDir, 0755, true);
    }
    
    // Create PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('Arial', 'B', 16);
    
    // Title
    $pdf->Cell(0, 10, 'ATTRAL Electronics - Invoice', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Order details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Order Details', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Cell(40, 6, 'Order ID:', 0, 0);
    $pdf->Cell(0, 6, $orderId, 0, 1);
    
    $pdf->Cell(40, 6, 'Date:', 0, 0);
    $pdf->Cell(0, 6, date('Y-m-d H:i:s'), 0, 1);
    
    $pdf->Cell(40, 6, 'Status:', 0, 0);
    $pdf->Cell(0, 6, 'Confirmed', 0, 1);
    
    $pdf->Ln(10);
    
    // Customer details (from test data)
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Customer Details', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Cell(40, 6, 'Name:', 0, 0);
    $pdf->Cell(0, 6, 'Test User', 0, 1);
    
    $pdf->Cell(40, 6, 'Email:', 0, 0);
    $pdf->Cell(0, 6, 'attralsolar@gmail.com', 0, 1);
    
    $pdf->Cell(40, 6, 'Phone:', 0, 0);
    $pdf->Cell(0, 6, '+91 9876543210', 0, 1);
    
    $pdf->Ln(10);
    
    // Shipping details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Shipping Address', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Cell(0, 6, '123 Test Street', 0, 1);
    $pdf->Cell(0, 6, 'Vellore, Tamil Nadu 632009', 0, 1);
    $pdf->Cell(0, 6, 'India', 0, 1);
    
    $pdf->Ln(10);
    
    // Product details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Product Details', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Cell(80, 6, 'Product', 1, 0, 'C');
    $pdf->Cell(30, 6, 'Quantity', 1, 0, 'C');
    $pdf->Cell(30, 6, 'Price', 1, 0, 'C');
    $pdf->Cell(30, 6, 'Total', 1, 1, 'C');
    
    $pdf->Cell(80, 6, 'ATTRAL GaN Charger', 1, 0);
    $pdf->Cell(30, 6, '1', 1, 0, 'C');
    $pdf->Cell(30, 6, '₹10', 1, 0, 'C');
    $pdf->Cell(30, 6, '₹10', 1, 1, 'C');
    
    $pdf->Ln(10);
    
    // Total
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(140, 6, 'Total Amount:', 0, 0, 'R');
    $pdf->Cell(30, 6, '₹10', 1, 1, 'C');
    
    // Footer
    $pdf->Ln(20);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 6, 'Thank you for your business!', 0, 1, 'C');
    $pdf->Cell(0, 6, 'ATTRAL Electronics - info@attral.in', 0, 1, 'C');
    
    // Save PDF
    $filename = 'invoice_' . $orderId . '_' . date('YmdHis') . '.pdf';
    $filepath = $invoicesDir . '/' . $filename;
    
    $pdf->Output('F', $filepath);
    
    if (file_exists($filepath)) {
        // Read PDF content and encode as base64
        $pdfContent = file_get_contents($filepath);
        $base64Content = base64_encode($pdfContent);
        
        echo json_encode([
            'success' => true,
            'message' => 'PDF generated successfully',
            'filename' => $filename,
            'pdfContent' => $base64Content,
            'fileSize' => strlen($pdfContent),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception('PDF file was not created');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
