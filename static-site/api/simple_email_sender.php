<?php
/**
 * Simple email sender that doesn't require Firebase SDK
 * Sends order confirmation emails directly via Brevo
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

// Include required files
require_once __DIR__ . '/brevo_email_service.php';

class SimpleEmailSender {
    
    private $brevoService;
    
    public function __construct() {
        $this->brevoService = new BrevoEmailService();
    }
    
    /**
     * Send order confirmation email with minimal data
     */
    public function sendOrderConfirmationEmail($orderId, $customerEmail = null) {
        try {
            // Use provided email or fallback to admin email
            $email = $customerEmail ?: 'info@attral.in';
            
            // Create minimal order data
            $orderData = [
                'orderId' => $orderId,
                'customer' => [
                    'firstName' => 'Customer',
                    'lastName' => '',
                    'email' => $email
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
            
            // Prepare email data
            $emailData = $this->prepareEmailData($orderData);
            
            // Send email via Brevo
            $result = $this->brevoService->sendOrderConfirmation($emailData);
            
            if ($result['success']) {
                error_log("SIMPLE EMAIL: Successfully sent confirmation for order $orderId to $email");
            } else {
                error_log("SIMPLE EMAIL ERROR: Failed to send confirmation for order $orderId: " . ($result['error'] ?? 'Unknown error'));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("SIMPLE EMAIL EXCEPTION: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Prepare email data from order data
     */
    private function prepareEmailData($orderData) {
        $customer = $orderData['customer'];
        $product = $orderData['product'];
        $pricing = $orderData['pricing'];
        $shipping = $orderData['shipping'];
        
        return [
            'email' => $customer['email'],
            'firstName' => $customer['firstName'] ?? 'Customer',
            'lastName' => $customer['lastName'] ?? '',
            'orderId' => $orderData['orderId'],
            'status' => 'Confirmed',
            'items' => [
                [
                    'title' => $product['title'] ?? 'ATTRAL Product',
                    'price' => $product['price'] ?? 0,
                    'quantity' => 1
                ]
            ],
            'shippingAddress' => [
                'address' => $shipping['address'] ?? 'Address not available',
                'city' => $shipping['city'] ?? '',
                'state' => $shipping['state'] ?? '',
                'pincode' => $shipping['pincode'] ?? '',
                'country' => $shipping['country'] ?? 'India'
            ],
            'subtotal' => $pricing['subtotal'] ?? $pricing['total'] ?? 0,
            'shipping' => $pricing['shipping'] ?? 0,
            'discount' => $pricing['discount'] ?? 0
        ];
    }
}

// Handle the request
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_log("SIMPLE EMAIL: No input received");
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code(400);
        }
        echo json_encode([
            'success' => false, 
            'error' => 'No input received'
        ]);
        exit;
    }
    
    if (!isset($input['orderId'])) {
        error_log("SIMPLE EMAIL: Order ID not provided. Input: " . json_encode($input));
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code(400);
        }
        echo json_encode([
            'success' => false, 
            'error' => 'Order ID is required'
        ]);
        exit;
    }
    
    $orderId = $input['orderId'];
    $customerEmail = $input['customerEmail'] ?? null;
    
    $sender = new SimpleEmailSender();
    $result = $sender->sendOrderConfirmationEmail($orderId, $customerEmail);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("SIMPLE EMAIL SENDER ERROR: " . $e->getMessage());
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>
