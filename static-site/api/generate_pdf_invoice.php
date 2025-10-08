<?php
/**
 * Generate PDF Invoice for Order Receipt
 * Creates a professional tax invoice PDF using FPDF
 */

// Suppress warnings and errors to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// Only set headers if running in web context and no output has been sent
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Include FPDF library
require_once __DIR__ . '/lib/fpdf/fpdf.php';

class PDFInvoiceGenerator {
    
    private $pdf;
    
    public function __construct() {
        $this->pdf = new FPDF('P', 'mm', 'A4');
        $this->pdf->AddPage();
        $this->pdf->SetAutoPageBreak(true, 20);
    }
    
    /**
     * Generate PDF invoice for an order
     */
    public function generateInvoice($orderData) {
        try {
            // Set up the invoice
            $this->addHeader();
            $this->addCompanyDetails();
            $this->addInvoiceDetails($orderData);
            $this->addCustomerDetails($orderData);
            $this->addProductDetails($orderData);
            $this->addTotals($orderData);
            $this->addFooter();
            
            // Generate the PDF content - use file approach since Output('S') doesn't work
            $tempFile = tempnam(sys_get_temp_dir(), 'invoice_') . '.pdf';
            $this->pdf->Output('F', $tempFile);
            
            // Read the PDF content
            $pdfContent = file_get_contents($tempFile);
            
            // Clean up temp file
            unlink($tempFile);
            
            if (!$pdfContent) {
                throw new Exception('Failed to generate PDF content');
            }
            
            return [
                'success' => true,
                'pdfContent' => base64_encode($pdfContent),
                'filename' => $this->generateFilename($orderData)
            ];
            
        } catch (Exception $e) {
            error_log("PDF INVOICE ERROR: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function addHeader() {
        // Background color for header
        $this->pdf->SetFillColor(102, 126, 234); // ATTRAL purple
        $this->pdf->Rect(10, 10, 190, 35, 'F');
        
        // Company logo area (placeholder)
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Rect(20, 15, 25, 25, 'F');
        $this->pdf->SetTextColor(102, 126, 234);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->SetXY(22, 22);
        $this->pdf->Cell(21, 6, 'ATTRAL', 0, 0, 'C');
        
        // Main title
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 28);
        $this->pdf->SetXY(55, 18);
        $this->pdf->Cell(0, 12, 'ATTRAL ELECTRONICS', 0, 0, 'L');
        
        // Subtitle
        $this->pdf->SetFont('Arial', 'I', 14);
        $this->pdf->SetXY(55, 30);
        $this->pdf->Cell(0, 8, 'Smart Power. Smarter Living.', 0, 0, 'L');
        
        // Invoice type badge
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->SetTextColor(102, 126, 234);
        $this->pdf->SetFont('Arial', 'B', 16);
        $this->pdf->SetXY(150, 20);
        $this->pdf->Cell(40, 12, 'TAX INVOICE', 1, 0, 'C', true);
        
        $this->pdf->Ln(50);
    }
    
    private function addCompanyDetails() {
        // Company details section with background
        $this->pdf->SetFillColor(248, 249, 250); // Light gray background
        $this->pdf->Rect(10, 55, 90, 35, 'F');
        
        // Section title
        $this->pdf->SetTextColor(102, 126, 234);
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetXY(15, 58);
        $this->pdf->Cell(80, 6, 'COMPANY INFORMATION', 0, 0, 'L');
        
        // Company details
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->SetXY(15, 65);
        $this->pdf->Cell(80, 4, 'ATTRAL Electronics', 0, 0, 'L');
        $this->pdf->SetXY(15, 69);
        $this->pdf->Cell(80, 4, 'Phase 2 Sathuvachari', 0, 0, 'L');
        $this->pdf->SetXY(15, 73);
        $this->pdf->Cell(80, 4, 'Vellore - 632009', 0, 0, 'L');
        $this->pdf->SetXY(15, 77);
        $this->pdf->Cell(80, 4, 'Tamil Nadu, India', 0, 0, 'L');
        
        // Contact info with icons (text representation)
        $this->pdf->SetTextColor(102, 126, 234);
        $this->pdf->SetXY(15, 81);
        $this->pdf->Cell(80, 4, '📧 info@attral.in', 0, 0, 'L');
        $this->pdf->SetXY(15, 85);
        $this->pdf->Cell(80, 4, '📱 +91 8903479870', 0, 0, 'L');
        
        $this->pdf->Ln(10);
    }
    
    private function addInvoiceDetails($orderData) {
        $orderNumber = $orderData['order_number'] ?? $orderData['razorpay_order_id'] ?? $orderData['orderId'] ?? 'N/A';
        $paymentId = $orderData['razorpay_payment_id'] ?? 'N/A';
        $orderDate = date('d-m-Y H:i:s');
        
        // Invoice details section with background
        $this->pdf->SetFillColor(248, 249, 250); // Light gray background
        $this->pdf->Rect(110, 55, 90, 35, 'F');
        
        // Section title
        $this->pdf->SetTextColor(102, 126, 234);
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetXY(115, 58);
        $this->pdf->Cell(80, 6, 'INVOICE DETAILS', 0, 0, 'L');
        
        // Invoice details with better formatting
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetXY(115, 65);
        $this->pdf->Cell(25, 4, 'Invoice No:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(55, 4, $orderNumber, 0, 1, 'L');
        
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetXY(115, 69);
        $this->pdf->Cell(25, 4, 'Payment ID:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(55, 4, substr($paymentId, 0, 20) . '...', 0, 1, 'L');
        
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetXY(115, 73);
        $this->pdf->Cell(25, 4, 'Date:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(55, 4, $orderDate, 0, 1, 'L');
        
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetXY(115, 77);
        $this->pdf->Cell(25, 4, 'Status:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $status = ucfirst($orderData['status'] ?? 'Confirmed');
        $this->pdf->Cell(55, 4, $status, 0, 1, 'L');
        
        // Status badge
        $this->pdf->SetFillColor(16, 185, 129); // Green for confirmed
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 7);
        $this->pdf->SetXY(115, 81);
        $this->pdf->Cell(20, 5, strtoupper($status), 0, 0, 'C', true);
        
        $this->pdf->Ln(20);
    }
    
    private function addCustomerDetails($orderData) {
        // Customer details section with background
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->SetDrawColor(102, 126, 234);
        $this->pdf->Rect(10, 105, 190, 50, 'FD');
        
        // Section title with background
        $this->pdf->SetFillColor(102, 126, 234);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->SetXY(15, 108);
        $this->pdf->Cell(180, 8, 'BILL TO & SHIP TO', 0, 0, 'L', true);
        
        // Get customer data
        $customer = $orderData['customer'] ?? $orderData['customer_data'] ?? [];
        $shipping = $orderData['shipping'] ?? $orderData['shipping_data'] ?? [];
        
        $customerName = $customer['firstName'] ?? $customer['name'] ?? 'Customer';
        $customerLastName = $customer['lastName'] ?? '';
        $customerEmail = $customer['email'] ?? 'N/A';
        $customerPhone = $customer['phone'] ?? 'N/A';
        
        // Customer details
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetXY(15, 120);
        $this->pdf->Cell(0, 5, $customerName . ' ' . $customerLastName, 0, 0, 'L');
        
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->SetXY(15, 125);
        $this->pdf->Cell(0, 4, '📧 ' . $customerEmail, 0, 0, 'L');
        
        $this->pdf->SetXY(15, 129);
        $this->pdf->Cell(0, 4, '📱 ' . $customerPhone, 0, 0, 'L');
        
        // Shipping address
        if ($shipping) {
            $this->pdf->SetXY(15, 135);
            $this->pdf->SetFont('Arial', 'B', 9);
            $this->pdf->Cell(0, 4, '📍 Shipping Address:', 0, 0, 'L');
            
            $this->pdf->SetFont('Arial', '', 8);
            $addressLines = [];
            if (isset($shipping['address'])) $addressLines[] = $shipping['address'];
            if (isset($shipping['city']) && isset($shipping['state'])) {
                $addressLines[] = $shipping['city'] . ', ' . $shipping['state'];
            }
            if (isset($shipping['pincode'])) $addressLines[] = $shipping['pincode'];
            if (isset($shipping['country'])) $addressLines[] = $shipping['country'];
            
            $yPos = 139;
            foreach ($addressLines as $line) {
                $this->pdf->SetXY(15, $yPos);
                $this->pdf->Cell(0, 4, $line, 0, 0, 'L');
                $yPos += 4;
            }
        }
        
        $this->pdf->Ln(20);
    }
    
    private function addProductDetails($orderData) {
        // Product details table with modern styling
        $this->pdf->SetDrawColor(102, 126, 234);
        $this->pdf->SetLineWidth(0.5);
        
        // Table header with gradient-like effect
        $this->pdf->SetFillColor(102, 126, 234);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 10);
        
        $this->pdf->SetXY(10, 170);
        $this->pdf->Cell(100, 10, 'PRODUCT DESCRIPTION', 1, 0, 'C', true);
        $this->pdf->Cell(25, 10, 'QTY', 1, 0, 'C', true);
        $this->pdf->Cell(30, 10, 'RATE', 1, 0, 'C', true);
        $this->pdf->Cell(35, 10, 'AMOUNT', 1, 1, 'C', true);
        
        // Product details with alternating row colors
        $this->pdf->SetFillColor(248, 249, 250);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);
        
        // Get product data
        $product = $orderData['product'] ?? $orderData['product_data'] ?? [];
        $productTitle = $product['title'] ?? $product['name'] ?? 'ATTRAL Product';
        $productPrice = $product['price'] ?? 0;
        $quantity = 1;
        
        $this->pdf->SetXY(10, 180);
        $this->pdf->Cell(100, 10, $productTitle, 1, 0, 'L', true);
        $this->pdf->Cell(25, 10, $quantity, 1, 0, 'C', true);
        $this->pdf->Cell(30, 10, '₹' . number_format($productPrice), 1, 0, 'R', true);
        $this->pdf->Cell(35, 10, '₹' . number_format($productPrice * $quantity), 1, 1, 'R', true);
        
        // Add some spacing
        $this->pdf->Ln(10);
    }
    
    private function addTotals($orderData) {
        // Get pricing data
        $pricing = $orderData['pricing'] ?? $orderData['pricing_data'] ?? [];
        $subtotal = $pricing['subtotal'] ?? $pricing['total'] ?? 0;
        $shipping = $pricing['shipping'] ?? 0;
        $discount = $pricing['discount'] ?? 0;
        $total = $pricing['total'] ?? $subtotal;
        
        // Calculate totals if not provided
        if (!$pricing) {
            $product = $orderData['product'] ?? $orderData['product_data'] ?? [];
            $subtotal = $product['price'] ?? 0;
            $total = $subtotal + $shipping - $discount;
        }
        
        // Totals section with modern styling
        $this->pdf->SetDrawColor(102, 126, 234);
        $this->pdf->SetLineWidth(0.5);
        
        // Totals box
        $this->pdf->SetFillColor(248, 249, 250);
        $this->pdf->Rect(110, 200, 90, 60, 'FD');
        
        // Section title
        $this->pdf->SetFillColor(102, 126, 234);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetXY(115, 203);
        $this->pdf->Cell(80, 8, 'ORDER SUMMARY', 0, 0, 'C', true);
        
        // Subtotal
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->SetXY(115, 215);
        $this->pdf->Cell(50, 5, 'Subtotal:', 0, 0, 'L');
        $this->pdf->Cell(30, 5, '₹' . number_format($subtotal), 0, 0, 'R');
        
        // Shipping
        if ($shipping > 0) {
            $this->pdf->SetXY(115, 220);
            $this->pdf->Cell(50, 5, 'Shipping:', 0, 0, 'L');
            $this->pdf->Cell(30, 5, '₹' . number_format($shipping), 0, 0, 'R');
        }
        
        // Discount
        if ($discount > 0) {
            $this->pdf->SetXY(115, 225);
            $this->pdf->Cell(50, 5, 'Discount:', 0, 0, 'L');
            $this->pdf->SetTextColor(220, 38, 38); // Red for discount
            $this->pdf->Cell(30, 5, '-₹' . number_format($discount), 0, 0, 'R');
            $this->pdf->SetTextColor(0, 0, 0);
        }
        
        // Separator line
        $this->pdf->SetDrawColor(102, 126, 234);
        $this->pdf->Line(115, 235, 195, 235);
        
        // Total with highlight
        $this->pdf->SetFillColor(102, 126, 234);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->SetXY(115, 240);
        $this->pdf->Cell(80, 10, 'TOTAL: ₹' . number_format($total), 0, 0, 'C', true);
        
        // Currency note
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->SetXY(115, 250);
        $this->pdf->Cell(80, 4, 'All amounts in Indian Rupees (INR)', 0, 0, 'C');
        
        $this->pdf->Ln(15);
    }
    
    private function addFooter() {
        // Footer with modern styling
        $this->pdf->SetFillColor(248, 249, 250);
        $this->pdf->Rect(10, 270, 190, 25, 'F');
        
        // Terms and conditions section
        $this->pdf->SetTextColor(102, 126, 234);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->SetXY(15, 273);
        $this->pdf->Cell(0, 4, 'Terms & Conditions:', 0, 0, 'L');
        
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->SetXY(15, 277);
        $this->pdf->Cell(0, 3, '• This is a computer generated invoice and does not require signature.', 0, 0, 'L');
        $this->pdf->SetXY(15, 280);
        $this->pdf->Cell(0, 3, '• Goods once sold will not be taken back.', 0, 0, 'L');
        $this->pdf->SetXY(15, 283);
        $this->pdf->Cell(0, 3, '• Subject to Vellore jurisdiction only.', 0, 0, 'L');
        
        // Thank you message with background
        $this->pdf->SetFillColor(102, 126, 234);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetXY(10, 285);
        $this->pdf->Cell(190, 8, 'Thank you for choosing ATTRAL Electronics!', 0, 0, 'C', true);
        
        // Company info
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->SetXY(10, 293);
        $this->pdf->Cell(190, 3, 'ATTRAL Electronics | info@attral.in | +91 8903479870 | www.attral.in', 0, 0, 'C');
    }
    
    private function generateFilename($orderData) {
        $orderNumber = $orderData['order_number'] ?? $orderData['razorpay_order_id'] ?? $orderData['orderId'] ?? 'invoice';
        return 'ATTRAL_Invoice_' . $orderNumber . '.pdf';
    }
}

// Handle the request
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_log("PDF INVOICE: No input received");
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code(400);
        }
        echo json_encode([
            'success' => false, 
            'error' => 'No input received'
        ]);
        exit;
    }
    
    if (!isset($input['orderData'])) {
        error_log("PDF INVOICE: Order data not provided");
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code(400);
        }
        echo json_encode([
            'success' => false, 
            'error' => 'Order data is required'
        ]);
        exit;
    }
    
    $orderData = $input['orderData'];
    $generator = new PDFInvoiceGenerator();
    $result = $generator->generateInvoice($orderData);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("PDF INVOICE GENERATOR ERROR: " . $e->getMessage());
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>
