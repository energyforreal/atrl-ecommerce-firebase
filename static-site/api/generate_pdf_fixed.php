<?php
/**
 * Fixed PDF Generation API
 * Proper PDF generation without output buffering issues
 */

// Disable output buffering completely
while (ob_get_level()) {
    ob_end_clean();
}

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
    
    // Create PDF with proper error handling
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetAutoPageBreak(true, 20);
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
    
    // Customer details
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
    
    // Product details table
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Product Details', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    // Table header
    $pdf->Cell(80, 8, 'Product', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Quantity', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Price', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Total', 1, 1, 'C');
    
    // Table row
    $pdf->Cell(80, 8, 'ATTRAL GaN Charger', 1, 0);
    $pdf->Cell(30, 8, '1', 1, 0, 'C');
    $pdf->Cell(30, 8, '₹10', 1, 0, 'C');
    $pdf->Cell(30, 8, '₹10', 1, 1, 'C');
    
    $pdf->Ln(10);
    
    // Total
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(140, 8, 'Total Amount:', 0, 0, 'R');
    $pdf->Cell(30, 8, '₹10', 1, 1, 'C');
    
    // Footer
    $pdf->Ln(20);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 6, 'Thank you for your business!', 0, 1, 'C');
    $pdf->Cell(0, 6, 'ATTRAL Electronics - info@attral.in', 0, 1, 'C');
    
    // Save PDF to file
    $filename = 'invoice_' . $orderId . '_' . date('YmdHis') . '.pdf';
    $filepath = $invoicesDir . '/' . $filename;
    
    // Output to file
    $pdf->Output('F', $filepath);
    
    // Verify file was created and has content
    if (!file_exists($filepath)) {
        throw new Exception('PDF file was not created');
    }
    
    $fileSize = filesize($filepath);
    if ($fileSize < 1000) {
        throw new Exception("PDF file is too small ($fileSize bytes) - likely corrupted");
    }
    
    // Read PDF content and encode as base64
    $pdfContent = file_get_contents($filepath);
    if (!$pdfContent || strlen($pdfContent) < 1000) {
        throw new Exception('PDF content is empty or too small');
    }
    
    $base64Content = base64_encode($pdfContent);
    
    // Verify PDF header
    $header = substr($pdfContent, 0, 8);
    if ($header !== '%PDF-1.3' && $header !== '%PDF-1.4') {
        throw new Exception('Invalid PDF header: ' . bin2hex($header));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'PDF generated successfully',
        'filename' => $filename,
        'pdfContent' => $base64Content,
        'fileSize' => $fileSize,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
