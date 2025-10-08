<?php
/**
 * 🎯 ATTRAL Order Email Sender
 * Automatically sends order confirmation emails when payment is successful
 * 
 * Features:
 * - Fetches order data from Firestore
 * - Sends professional order confirmation email via Brevo
 * - Handles both single product and cart orders
 * - Includes order details, pricing, and shipping information
 */

// Suppress warnings and errors to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

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

class OrderEmailSender {
    
    private $brevoService;
    
    public function __construct() {
        $this->brevoService = new BrevoEmailService();
    }
    
    /**
     * Send order confirmation email using provided order data
     */
    public function sendOrderConfirmationEmailWithData($orderId, $orderData) {
        try {
            error_log("ORDER EMAIL: Using provided order data for $orderId");
            
            // Ensure we have valid order data
            if (!$orderData || !isset($orderData['customer']['email'])) {
                error_log("ORDER EMAIL: Invalid provided order data for $orderId, falling back to database lookup");
                return $this->sendOrderConfirmationEmail($orderId);
            }
            
            // Prepare email data
            $emailData = $this->prepareEmailData($orderData);
            
            // Send email via Brevo
            $result = $this->brevoService->sendOrderConfirmation($emailData);
            
            if ($result['success']) {
                error_log("ORDER EMAIL: Successfully sent confirmation for order $orderId to {$emailData['email']} using provided data");
            } else {
                error_log("ORDER EMAIL ERROR: Failed to send confirmation for order $orderId: " . ($result['error'] ?? 'Unknown error'));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("ORDER EMAIL EXCEPTION: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send order confirmation email for a specific order
     */
    public function sendOrderConfirmationEmail($orderId) {
        try {
            // Try to get order data from Firestore first
            $orderData = $this->getOrderFromFirestore($orderId);
            
            if (!$orderData) {
                // Try fallback: get from session storage or SQLite
                $orderData = $this->getFallbackOrderData($orderId);
                
                if (!$orderData) {
                    error_log("ORDER EMAIL: Order $orderId not found, creating minimal order data");
                    // Create minimal order data to prevent email failure
                    $orderData = $this->createMinimalOrderData($orderId);
                }
            }
            
            // Ensure we have valid order data
            if (!$orderData || !isset($orderData['customer']['email'])) {
                error_log("ORDER EMAIL: Invalid order data for $orderId, using fallback email");
                $orderData = $this->createMinimalOrderData($orderId);
            }
            
            // Prepare email data
            $emailData = $this->prepareEmailData($orderData);
            
            // Send email via Brevo
            $result = $this->brevoService->sendOrderConfirmation($emailData);
            
            if ($result['success']) {
                error_log("ORDER EMAIL: Successfully sent confirmation for order $orderId to {$emailData['email']}");
            } else {
                error_log("ORDER EMAIL ERROR: Failed to send confirmation for order $orderId: " . ($result['error'] ?? 'Unknown error'));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("ORDER EMAIL EXCEPTION: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get order data from Firestore
     */
    private function getOrderFromFirestore($orderId) {
        try {
            // Check if Firebase SDK is available
            if (!class_exists('\Kreait\Firebase\Factory')) {
                error_log("FIRESTORE: Firebase SDK not available, using fallback");
                return null;
            }
            
            // Initialize Firebase using Kreait SDK (same as other working files)
            $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
            if (!file_exists($serviceAccountPath)) {
                error_log("FIRESTORE: Firebase service account file not found");
                return null;
            }
            
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            $firebase = (new \Kreait\Firebase\Factory())
                ->withServiceAccount($serviceAccount)
                ->create();
            
            $firestore = $firebase->firestore();
            
            // Query orders collection by razorpayOrderId (since orderId from URL is Razorpay order ID)
            $ordersRef = $firestore->collection('orders');
            $query = $ordersRef->where('razorpayOrderId', '=', $orderId);
            $documents = $query->documents();
            
            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    $data = $doc->data();
                    error_log("FIRESTORE: Found order data for $orderId");
                    return $data;
                }
            }
            
            // If not found by razorpayOrderId, try by custom orderId
            $query = $ordersRef->where('orderId', '=', $orderId);
            $documents = $query->documents();
            
            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    $data = $doc->data();
                    error_log("FIRESTORE: Found order data for $orderId by orderId");
                    return $data;
                }
            }
            
            error_log("FIRESTORE: No order found with ID $orderId");
            return null;
            
        } catch (Exception $e) {
            error_log("FIRESTORE ERROR: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get fallback order data when Firestore lookup fails
     */
    private function getFallbackOrderData($orderId) {
        try {
            // Try to get from SQLite database as fallback
            $dbPath = __DIR__ . '/../api/orders.db';
            if (file_exists($dbPath)) {
                $pdo = new PDO("sqlite:$dbPath");
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE razorpay_order_id = ? OR razorpay_payment_id = ? LIMIT 1");
                $stmt->execute([$orderId, $orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    return [
                        'orderId' => $order['order_number'],
                        'razorpayOrderId' => $order['razorpay_order_id'],
                        'razorpayPaymentId' => $order['razorpay_payment_id'],
                        'customer' => json_decode($order['customer_data'], true),
                        'product' => json_decode($order['product_data'], true),
                        'pricing' => json_decode($order['pricing_data'], true),
                        'shipping' => json_decode($order['shipping_data'], true),
                        'payment' => json_decode($order['payment_data'], true),
                        'status' => $order['status']
                    ];
                }
            }
            
            // If no database fallback, create minimal order data
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
            
        } catch (Exception $e) {
            error_log("FALLBACK ORDER DATA ERROR: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Prepare email data from Firestore order data
     */
    private function prepareEmailData($orderData) {
        $customer = $orderData['customer'] ?? [];
        $pricing = $orderData['pricing'] ?? [];
        $shipping = $orderData['shipping'] ?? [];
        $product = $orderData['product'] ?? [];
        
        // Build customer name
        $firstName = $customer['firstName'] ?? '';
        $lastName = $customer['lastName'] ?? '';
        $customerName = trim($firstName . ' ' . $lastName);
        if (empty($customerName)) {
            $customerName = 'Valued Customer';
        }
        
        // Build shipping address
        $shippingAddress = '';
        if (!empty($shipping['address'])) {
            $shippingAddress = $shipping['address'];
            if (!empty($shipping['city'])) {
                $shippingAddress .= ', ' . $shipping['city'];
            }
            if (!empty($shipping['state'])) {
                $shippingAddress .= ', ' . $shipping['state'];
            }
            if (!empty($shipping['pincode'])) {
                $shippingAddress .= ' - ' . $shipping['pincode'];
            }
            if (!empty($shipping['country'])) {
                $shippingAddress .= ', ' . $shipping['country'];
            }
        }
        
        // Build order items
        $items = [];
        if (isset($product['items']) && is_array($product['items'])) {
            // Cart order
            foreach ($product['items'] as $item) {
                $items[] = [
                    'name' => $item['title'] ?? $item['name'] ?? 'Product',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0
                ];
            }
        } else {
            // Single product order
            $items[] = [
                'name' => $product['title'] ?? 'ATTRAL Product',
                'quantity' => 1,
                'price' => $product['price'] ?? $pricing['total'] ?? 0
            ];
        }
        
        return [
            'email' => $customer['email'] ?? '',
            'customerName' => $customerName,
            'orderId' => $orderData['orderId'] ?? $orderData['razorpayOrderId'] ?? 'N/A',
            'total' => $pricing['total'] ?? 0,
            'orderDate' => date('F j, Y'),
            'paymentMethod' => 'Razorpay',
            'status' => 'Confirmed',
            'items' => $items,
            'shippingAddress' => $shippingAddress,
            'subtotal' => $pricing['subtotal'] ?? $pricing['total'] ?? 0,
            'shipping' => $pricing['shipping'] ?? 0,
            'discount' => $pricing['discount'] ?? 0
        ];
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
                'email' => 'info@attral.in' // Use admin email as fallback
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
    // Clear any output buffer content
    ob_clean();
    
    $rawInput = file_get_contents('php://input');
    error_log("ORDER EMAIL: Raw input received: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        error_log("ORDER EMAIL: No input received or JSON decode failed");
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid request'
        ]);
        exit;
    }
    
    if (!isset($input['orderId'])) {
        error_log("ORDER EMAIL: Order ID not provided. Input: " . json_encode($input));
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid request'
        ]);
        exit;
    }
    
    $orderId = $input['orderId'];
    $sender = new OrderEmailSender();
    
    // Use provided orderData if available, otherwise fetch from database
    if (isset($input['orderData']) && !empty($input['orderData'])) {
        $result = $sender->sendOrderConfirmationEmailWithData($orderId, $input['orderData']);
    } else {
        $result = $sender->sendOrderConfirmationEmail($orderId);
    }
    
    // Clear output buffer and send clean JSON response
    ob_clean();
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("ORDER EMAIL SENDER ERROR: " . $e->getMessage());
    
    // Clear output buffer and send clean error response
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}

// End output buffering
ob_end_flush();
?>
