<?php
/**
 * 🎯 Generate Invoice and Send Email
 * Simple API to generate PDF invoice and send via email
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
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Include required files
require_once __DIR__ . '/brevo_email_service.php';
require_once __DIR__ . '/order_manager.php';

class InvoiceGenerator {
    
    public function generateAndSendInvoice($orderId) {
        try {
            // Get order data from Firestore
            $orderData = $this->getOrderFromFirestore($orderId);
            
            if (!$orderData) {
                error_log("INVOICE: Order $orderId not found, creating minimal order data");
                // Create minimal order data to prevent invoice failure
                $orderData = $this->createMinimalOrderData($orderId);
            }
            
            // Generate PDF invoice
            $invoicePath = $this->generateInvoicePDF($orderId, $orderData);
            
            if (!$invoicePath) {
                return [
                    'success' => false,
                    'error' => 'Failed to generate PDF invoice'
                ];
            }
            
            // Send invoice email
            $emailResult = $this->sendInvoiceEmail($orderId, $invoicePath, $orderData);
            
            if ($emailResult['success']) {
                return [
                    'success' => true,
                    'message' => 'Invoice generated and sent successfully',
                    'invoicePath' => $invoicePath
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Invoice generated but email failed: ' . ($emailResult['error'] ?? 'Unknown error')
                ];
            }
            
        } catch (Exception $e) {
            error_log("INVOICE GENERATOR ERROR: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage()
            ];
        }
    }
    
    private function getOrderFromFirestore($orderId) {
        try {
            // Check if Firebase SDK is available
            if (!class_exists('\Kreait\Firebase\Factory')) {
                error_log("INVOICE: Firebase SDK not available, using fallback");
                return null;
            }
            
            // Initialize Firebase
            $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
            if (!file_exists($serviceAccountPath)) {
                error_log("INVOICE: Firebase service account file not found");
                return null;
            }
            
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            $firebase = (new \Kreait\Firebase\Factory())
                ->withServiceAccount($serviceAccount)
                ->create();
            
            $firestore = $firebase->firestore();
            
            // First try to get by razorpayOrderId (since orderId from URL is Razorpay order ID)
            $ordersRef = $firestore->collection('orders');
            $query = $ordersRef->where('razorpayOrderId', '=', $orderId);
            $documents = $query->documents();
            
            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    return $this->normalizeOrderData($doc->data());
                }
            }
            
            // If not found by razorpayOrderId, try by custom orderId
            $query = $ordersRef->where('orderId', '=', $orderId);
            $documents = $query->documents();
            
            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    return $this->normalizeOrderData($doc->data());
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("INVOICE: Firestore error: " . $e->getMessage());
            return null;
        }
    }
    
    private function normalizeOrderData($firestoreData) {
        // Normalize Firestore data to expected format
        return [
            'customer' => [
                'firstName' => $firestoreData['customer']['firstName'] ?? 'Customer',
                'lastName' => $firestoreData['customer']['lastName'] ?? '',
                'email' => $firestoreData['customer']['email'] ?? '',
                'phone' => $firestoreData['customer']['phone'] ?? ''
            ],
            'shipping' => [
                'address' => $firestoreData['shipping']['address'] ?? '',
                'city' => $firestoreData['shipping']['city'] ?? '',
                'state' => $firestoreData['shipping']['state'] ?? '',
                'pincode' => $firestoreData['shipping']['pincode'] ?? '',
                'country' => $firestoreData['shipping']['country'] ?? 'India'
            ],
            'product' => [
                'title' => $firestoreData['product']['title'] ?? 'ATTRAL 100W GaN Charger',
                'price' => $firestoreData['product']['price'] ?? 0,
                'items' => $firestoreData['product']['items'] ?? []
            ],
            'pricing' => [
                'subtotal' => $firestoreData['pricing']['subtotal'] ?? ($firestoreData['amount'] ?? 0),
                'shipping' => $firestoreData['pricing']['shipping'] ?? 0,
                'discount' => $firestoreData['pricing']['discount'] ?? 0,
                'total' => $firestoreData['pricing']['total'] ?? ($firestoreData['amount'] ?? 0),
                'currency' => $firestoreData['pricing']['currency'] ?? ($firestoreData['currency'] ?? 'INR')
            ]
        ];
    }
    
    private function generateInvoicePDF($orderId, $orderData) {
        // Use existing generateInvoicePDF function from order_manager.php
        return generateInvoicePDF($orderId, $orderData);
    }
    
    private function sendInvoiceEmail($orderId, $invoicePath, $orderData) {
        try {
            $customerEmail = $orderData['customer']['email'] ?? '';
            $customerName = trim(($orderData['customer']['firstName'] ?? '') . ' ' . ($orderData['customer']['lastName'] ?? ''));
            
            if (empty($customerEmail)) {
                return [
                    'success' => false,
                    'error' => 'Customer email not found'
                ];
            }
            
            if (empty($customerName)) {
                $customerName = 'Valued Customer';
            }
            
            // Check if PDF file exists
            if (!file_exists($invoicePath)) {
                return [
                    'success' => false,
                    'error' => 'Invoice PDF file not found at: ' . $invoicePath
                ];
            }
            
            // Prepare order data for email template
            $emailOrderData = [
                'customerName' => $customerName,
                'total' => $orderData['pricing']['total'] ?? 0,
                'orderDate' => date('F j, Y')
            ];
            
            // Read PDF content for attachment
            $pdfContent = file_get_contents($invoicePath);
            $pdfBase64 = base64_encode($pdfContent);
            $pdfFilename = basename($invoicePath);
            
            // Prepare email with PDF attachment
            $subject = "📄 Your ATTRAL Invoice - Order #$orderId";
            $htmlContent = $this->generateInvoiceEmailTemplate($emailOrderData, $orderId);
            
            // Send invoice email with PDF attachment via PHPMailer (Primary)
            $brevoService = new BrevoEmailService();
            $result = $brevoService->sendTransactionalEmail(
                $customerEmail,
                $subject,
                $htmlContent,
                [
                    'toName' => $customerName,
                    'fromEmail' => 'info@attral.in',
                    'fromName' => 'ATTRAL Electronics',
                    'attachments' => [
                        [
                            'content' => $pdfBase64,
                            'name' => $pdfFilename,
                            'type' => 'application/pdf'
                        ]
                    ]
                ]
            );
            
            if ($result['success']) {
                error_log("INVOICE EMAIL: Successfully sent invoice for order $orderId to $customerEmail");
            } else {
                error_log("INVOICE EMAIL ERROR: Failed to send invoice for order $orderId: " . ($result['error'] ?? 'Unknown error'));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("INVOICE EMAIL EXCEPTION: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate HTML email template for invoice
     */
    private function generateInvoiceEmailTemplate($orderData, $orderId) {
        $customerName = $orderData['customerName'] ?? 'Valued Customer';
        $total = number_format($orderData['total'] ?? 0, 2);
        $orderDate = $orderData['orderDate'] ?? date('F j, Y');
        
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center;">
                <h1 style="margin: 0; font-size: 28px;">📄 Your ATTRAL Invoice</h1>
                <p style="margin: 10px 0 0 0; opacity: 0.9;">Order #' . htmlspecialchars($orderId) . '</p>
            </div>
            
            <div style="padding: 30px;">
                <div style="background-color: #d1fae5; color: #065f46; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">✅ Invoice Generated Successfully!</h2>
                    <p style="margin-bottom: 0;">Dear ' . htmlspecialchars($customerName) . ', your invoice is ready and attached to this email.</p>
                </div>
                
                <div style="background-color: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0ea5e9;">
                    <h3 style="margin-top: 0; color: #0c4a6e;">Order Details</h3>
                    <ul style="color: #1e40af; margin-bottom: 0;">
                        <li><strong>Order Number:</strong> ' . htmlspecialchars($orderId) . '</li>
                        <li><strong>Order Date:</strong> ' . htmlspecialchars($orderDate) . '</li>
                        <li><strong>Total Amount:</strong> ₹' . $total . '</li>
                        <li><strong>Invoice:</strong> Attached as PDF</li>
                    </ul>
                </div>
                
                <div style="background-color: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #92400e;">📎 What\'s Attached</h3>
                    <ul style="color: #92400e; margin-bottom: 0;">
                        <li><strong>Invoice PDF:</strong> Professional tax invoice</li>
                        <li><strong>Order Summary:</strong> Complete order details</li>
                        <li><strong>Payment Receipt:</strong> Payment confirmation</li>
                        <li><strong>Tax Details:</strong> GST and tax breakdown</li>
                    </ul>
                </div>
                
                <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #374151;">📋 Important Notes</h3>
                    <ul style="color: #6b7280; margin-bottom: 0;">
                        <li>Please save this invoice for your records</li>
                        <li>This invoice is valid for tax purposes</li>
                        <li>Contact us if you have any questions</li>
                        <li>Thank you for choosing ATTRAL!</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="https://attral.in" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Visit ATTRAL Website</a>
                </div>
                
                <div style="text-align: center; color: #6b7280; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                    <p style="margin: 0;"><strong>ATTRAL Electronics</strong><br>
                    Premium GaN Chargers for Modern Life<br>
                    📧 info@attral.in | 📱 +91 8903479870</p>
                </div>
            </div>
        </div>';
    }
    
    /**
     * Create minimal order data when order is not found
     */
    private function createMinimalOrderData($orderId) {
        return [
            'orderId' => $orderId,
            'razorpayOrderId' => $orderId,
            'customer' => [
                'firstName' => 'Customer',
                'lastName' => '',
                'email' => 'customer@example.com'
            ],
            'product' => [
                'title' => 'ATTRAL Product',
                'price' => 0
            ],
            'pricing' => [
                'subtotal' => 0,
                'shipping' => 0,
                'discount' => 0,
                'total' => 0
            ],
            'shipping' => [
                'address' => 'Address not available',
                'city' => '',
                'state' => '',
                'pincode' => '',
                'country' => 'India'
            ],
            'status' => 'confirmed'
        ];
    }
}

// Handle the request
try {
    $rawInput = file_get_contents('php://input');
    error_log("INVOICE: Raw input received: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        error_log("INVOICE: No input received or JSON decode failed");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request'
        ]);
        exit;
    }
    
    if (!isset($input['orderId'])) {
        error_log("INVOICE: Order ID not provided. Input: " . json_encode($input));
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request'
        ]);
        exit;
    }
    
    $orderId = $input['orderId'];
    $generator = new InvoiceGenerator();
    $result = $generator->generateAndSendInvoice($orderId);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("INVOICE GENERATOR ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>
