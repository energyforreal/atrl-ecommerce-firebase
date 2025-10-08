<?php
/**
 * Minimal PDF Generation API
 * Use PHP's built-in functions to create a simple PDF
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
    $orderData = $input['orderData'] ?? [];
    
    // Extract order data for invoice content
    $customer = $orderData['customer'] ?? [];
    $product = $orderData['product'] ?? [];
    $pricing = $orderData['pricing'] ?? [];
    $shipping = $orderData['shipping'] ?? [];
    
    // Prepare invoice content with real order data
    $customerName = trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? ''));
    if (empty($customerName)) {
        $customerName = 'Customer';
    }
    
    $productTitle = $product['title'] ?? 'Product';
    $productPrice = $pricing['total'] ?? 0;
    $currency = $pricing['currency'] ?? 'INR';
    $quantity = $product['quantity'] ?? 1;
    
    $shippingAddress = '';
    if (!empty($shipping)) {
        $shippingAddress = ($shipping['address'] ?? '') . '<br>' .
                          ($shipping['city'] ?? '') . ', ' . ($shipping['state'] ?? '') . ' ' . ($shipping['pincode'] ?? '') . '<br>' .
                          ($shipping['country'] ?? '');
    }
    if (empty($shippingAddress)) {
        $shippingAddress = 'Address not provided';
    }
    
    // Create invoices directory if it doesn't exist
    $invoicesDir = __DIR__ . '/../invoices';
    if (!is_dir($invoicesDir)) {
        mkdir($invoicesDir, 0755, true);
    }
    
    // Create a professional tax invoice HTML based on the template
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Tax Invoice - ' . htmlspecialchars($orderId) . '</title>
        <style>
            @page { margin: 0.5in; }
            * { box-sizing: border-box; }
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                margin: 0; 
                padding: 0; 
                background: #fff; 
                color: #333; 
                line-height: 1.4;
            }
            .invoice-container { 
                max-width: 800px; 
                margin: 0 auto; 
                background: #fff; 
                box-shadow: 0 0 20px rgba(0,0,0,0.1); 
                overflow: hidden;
            }
            .header { 
                background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%); 
                color: white; 
                padding: 40px 30px; 
                display: flex; 
                justify-content: space-between; 
                align-items: center;
                position: relative;
                overflow: hidden;
            }
            .header::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.05);
                background-image: radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
                background-size: 20px 20px;
            }
                    .company-info { 
                        display: flex; 
                        align-items: center; 
                        position: relative;
                        z-index: 1;
                    }
            .company-details h1 { 
                margin: 0; 
                font-size: 32px; 
                font-weight: 900; 
                color: white;
                text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                letter-spacing: 1px;
            }
            .company-details p { 
                margin: 8px 0 0 0; 
                font-size: 16px; 
                opacity: 0.9;
                font-weight: 300;
                letter-spacing: 0.5px;
            }
            .invoice-title { 
                font-size: 56px; 
                font-weight: 900; 
                color: #ff6b35; 
                margin: 0;
                text-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
                letter-spacing: 2px;
                position: relative;
                z-index: 1;
            }
            .invoice-details-bar { 
                display: flex; 
                background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); 
                color: white; 
                padding: 20px 30px;
                box-shadow: 0 4px 12px rgba(255, 107, 53, 0.2);
                position: relative;
            }
            .invoice-details-bar::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.1);
                background-image: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.2) 1px, transparent 1px);
                background-size: 15px 15px;
            }
            .invoice-details-bar > div { 
                flex: 1; 
                display: flex; 
                align-items: center; 
                gap: 12px;
                position: relative;
                z-index: 1;
            }
            .invoice-details-bar .right { 
                background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%); 
                margin: -20px -30px -20px 30px; 
                padding: 20px 30px;
                box-shadow: 0 4px 12px rgba(52, 73, 94, 0.3);
            }
            .detail-label { 
                font-weight: 600; 
                font-size: 15px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .detail-value { 
                font-weight: 800; 
                font-size: 18px;
                text-shadow: 0 1px 2px rgba(0,0,0,0.2);
            }
            .content-area { 
                padding: 40px; 
                background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
                position: relative;
            }
            .content-area::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 1px;
                background: linear-gradient(90deg, transparent 0%, #ff6b35 50%, transparent 100%);
            }
            .invoice-to { 
                margin-bottom: 30px;
            }
            .invoice-to h3 { 
                margin: 0 0 10px 0; 
                font-size: 18px; 
                font-weight: 600; 
                color: #2c3e50;
            }
            .customer-name { 
                font-size: 18px; 
                font-weight: 700; 
                color: #2c3e50; 
                margin-bottom: 8px;
            }
            .customer-address { 
                color: #555; 
                line-height: 1.5;
            }
            .items-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 40px;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                background: white;
            }
            .items-table th { 
                background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); 
                color: white; 
                padding: 18px 15px; 
                text-align: left; 
                font-weight: 700; 
                font-size: 14px; 
                text-transform: uppercase; 
                letter-spacing: 1px;
                text-shadow: 0 1px 2px rgba(0,0,0,0.2);
                position: relative;
            }
            .items-table th::after {
                content: "";
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 2px;
                background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
            }
            .items-table td { 
                padding: 18px 15px; 
                border-bottom: 1px solid #e9ecef; 
                vertical-align: top;
                transition: background-color 0.2s ease;
            }
            .items-table tr:nth-child(even) { 
                background: #f8f9fa; 
            }
            .items-table tr:hover { 
                background: #e3f2fd; 
            }
            .product-name { 
                font-weight: 600; 
                color: #2c3e50;
            }
            .product-description { 
                font-size: 12px; 
                color: #7f8c8d; 
                margin-top: 5px;
            }
            .quantity, .price, .total { 
                text-align: center; 
                font-weight: 500;
            }
            .summary-section { 
                display: flex; 
                justify-content: space-between; 
                align-items: flex-start; 
                margin-top: 30px;
            }
            .thank-you { 
                flex: 1;
            }
            .thank-you h4 { 
                margin: 0 0 15px 0; 
                font-size: 16px; 
                font-weight: 600; 
                color: #2c3e50;
            }
            .totals { 
                flex: 1; 
                max-width: 300px; 
                margin-left: 30px;
            }
            .total-row { 
                display: flex; 
                justify-content: space-between; 
                margin-bottom: 8px; 
                padding: 5px 0;
            }
            .total-label { 
                font-weight: 500; 
                color: #555;
            }
            .total-value { 
                font-weight: 600; 
                color: #2c3e50;
            }
            .grand-total { 
                background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); 
                color: white; 
                padding: 20px; 
                border-radius: 12px; 
                margin-top: 20px;
                box-shadow: 0 6px 20px rgba(44, 62, 80, 0.3);
                position: relative;
                overflow: hidden;
            }
            .grand-total::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.05);
                background-image: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
                background-size: 12px 12px;
            }
            .grand-total .total-label { 
                font-size: 18px; 
                font-weight: 700; 
                color: white;
                position: relative;
                z-index: 1;
            }
            .grand-total .total-value { 
                font-size: 22px; 
                font-weight: 900; 
                color: white;
                text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                position: relative;
                z-index: 1;
            }
            .footer { 
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
                padding: 35px 40px; 
                border-top: 3px solid #ff6b35;
                position: relative;
            }
            .footer::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 1px;
                background: linear-gradient(90deg, transparent 0%, #ff6b35 50%, transparent 100%);
            }
            .footer-content { 
                display: grid; 
                grid-template-columns: 1fr 1fr; 
                gap: 30px; 
                margin-bottom: 20px;
            }
            .footer-section h4 { 
                margin: 0 0 10px 0; 
                font-size: 14px; 
                font-weight: 600; 
                color: #2c3e50; 
                text-transform: uppercase; 
                letter-spacing: 0.5px;
            }
            .footer-section p { 
                margin: 3px 0; 
                font-size: 12px; 
                color: #555;
            }
            .footer-bottom { 
                border-top: 1px solid #e9ecef; 
                padding-top: 15px; 
                text-align: center; 
                font-size: 12px; 
                color: #7f8c8d;
            }
            .signature-section { 
                margin-top: 30px; 
                text-align: right;
            }
            .signature-line { 
                border-bottom: 1px solid #333; 
                width: 200px; 
                margin: 20px 0 5px auto;
            }
            .signature-label { 
                font-size: 12px; 
                color: #555;
            }
            @media print {
                .invoice-container { box-shadow: none; }
                body { background: white; }
            }
        </style>
    </head>
    <body>
        <div class="invoice-container">
            <div class="header">
                <div class="company-info">
                    <div class="company-details">
                        <h1>ATTRAL</h1>
                        <p>Smart Power. Smarter Living.</p>
                    </div>
                </div>
                <div class="invoice-title">INVOICE</div>
            </div>
            
            <div class="invoice-details-bar">
                <div class="left">
                    <span class="detail-label">Invoice#</span>
                    <span class="detail-value">' . htmlspecialchars($orderId) . '</span>
                </div>
                <div class="right">
                    <span class="detail-label">Date</span>
                    <span class="detail-value">' . date('d / m / Y') . '</span>
                </div>
            </div>
            
            <div class="content-area">
                <div class="invoice-to">
                    <h3>Invoice to:</h3>
                    <div class="customer-name">' . htmlspecialchars($customerName) . '</div>
                    <div class="customer-address">
                        ' . $shippingAddress . '
                    </div>
                </div>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">SL.</th>
                            <th style="width: 52%;">Item Description</th>
                            <th style="width: 15%;">Price</th>
                            <th style="width: 10%;">Qty.</th>
                            <th style="width: 15%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center;">1</td>
                            <td>
                                <div class="product-name">' . htmlspecialchars($productTitle) . '</div>
                                <div class="product-description">100W USB-C PD Charger with GaN Technology</div>
                            </td>
                            <td class="price">' . htmlspecialchars($currency . ' ' . number_format($productPrice, 2)) . '</td>
                            <td class="quantity">' . htmlspecialchars($quantity) . '</td>
                            <td class="total">' . htmlspecialchars($currency . ' ' . number_format($productPrice, 2)) . '</td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="summary-section">
                    <div class="thank-you">
                        <h4>Thank you for your business</h4>
                        <div style="margin-bottom: 20px;">
                            <strong>Payment Info:</strong><br>
                            <div style="margin-top: 8px; font-size: 12px;">
                                <div><strong>Account #:</strong> 614305010513</div>
                                <div><strong>A/C Name:</strong> M Lokesh</div>
                                <div><strong>IFSC:</strong> ICIC0006143</div>
                            </div>
                        </div>
                        <div>
                            <strong>Terms & Conditions:</strong><br>
                            <div style="margin-top: 8px; font-size: 12px; color: #555;">
                                This is a computer-generated invoice and does not require a signature. 
                                All terms and conditions apply as per company policy.
                            </div>
                        </div>
                    </div>
                    <div class="totals">
                        <div class="total-row">
                            <span class="total-label">Sub Total:</span>
                            <span class="total-value">' . htmlspecialchars($currency . ' ' . number_format($productPrice, 2)) . '</span>
                        </div>
                        <div class="total-row">
                            <span class="total-label">Tax:</span>
                            <span class="total-value">(inclusive of tax)</span>
                        </div>
                        <div class="grand-total">
                            <div class="total-row">
                                <span class="total-label">Total:</span>
                                <span class="total-value">' . htmlspecialchars($currency . ' ' . number_format($productPrice, 2)) . '</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="signature-section">
                    <div class="signature-line"></div>
                    <div class="signature-label">Authorised Sign</div>
                </div>
            </div>
            
            <div class="footer">
                <div class="footer-content">
                    <div class="footer-section">
                        <h4>Company Details</h4>
                        <p><strong>GST No:</strong> 33ATUPL4776H1Z3</p>
                        <p><strong>Address:</strong> No: 7, VOC Nagar EXTN, Phase 2 Sathuvachari</p>
                        <p>Vellore - 632007, Tamil Nadu, India</p>
                    </div>
                    <div class="footer-section">
                        <h4>Contact Information</h4>
                        <p><strong>Phone:</strong> +91 8903479870</p>
                        <p><strong>Email:</strong> info@attral.in</p>
                        <p><strong>Website:</strong> www.attral.in</p>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>© 2025 ATTRAL Electronics. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    // Save HTML file
    $filename = 'invoice_' . $orderId . '_' . date('YmdHis') . '.html';
    $filepath = $invoicesDir . '/' . $filename;
    
    file_put_contents($filepath, $html);
    
    if (file_exists($filepath)) {
        $fileSize = filesize($filepath);
        $fileContent = file_get_contents($filepath);
        $base64Content = base64_encode($fileContent);
        
        echo json_encode([
            'success' => true,
            'message' => 'HTML invoice generated successfully',
            'filename' => $filename,
            'pdfContent' => $base64Content,
            'fileSize' => $fileSize,
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => 'This is an HTML invoice that can be converted to PDF by the browser',
            'orderData' => [
                'customerName' => $customerName,
                'productTitle' => $productTitle,
                'productPrice' => $productPrice,
                'currency' => $currency,
                'quantity' => $quantity
            ]
        ]);
    } else {
        throw new Exception('HTML file was not created');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
