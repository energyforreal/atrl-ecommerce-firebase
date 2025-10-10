<?php
/**
 * ✅ PRIMARY ORDER MANAGEMENT SYSTEM - Firestore REST API
 * 
 * This is the OFFICIAL and PRIMARY order management system for ATTRAL e-commerce.
 * All orders MUST be written to Firestore using this REST API implementation.
 * 
 * Features:
 * - ✅ Hostinger shared hosting compatible (no gRPC, no custom extensions)
 * - ✅ Pure PHP with cURL (no Composer dependencies required)
 * - ✅ Idempotent order creation (prevents duplicates)
 * - ✅ Atomic coupon tracking (₹300 fixed commission for affiliates)
 * - ✅ Comprehensive error logging
 * - ✅ JWT-based authentication
 * 
 * Called by:
 * - order-success.html (client-side after payment)
 * - webhook.php (server-side backup)
 * 
 * Database: Firestore collection 'orders' in project e-commerce-1d40f
 * 
 * @version 1.0.0
 * @status PRIMARY SYSTEM - PRODUCTION READY
 * @see firestore_rest_client.php for REST API client
 * @see coupon_tracking_service_rest.php for coupon tracking
 */

// Suppress warnings and errors to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

error_log("✅ PRIMARY ORDER SYSTEM: firestore_order_manager_rest.php (REST API) is active");

// Start output buffering to catch any unexpected output
ob_start();

// Only set headers if running in web context and no output has been sent
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Razorpay-Signature');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

// Load Firestore REST client
require_once __DIR__ . '/firestore_rest_client.php';

// Load enhanced coupon tracking service (will be refactored separately)
require_once __DIR__ . '/coupon_tracking_service_rest.php';

// Load configuration
$cfg = @include __DIR__.'/config.php';
$RAZORPAY_KEY_SECRET = ($cfg['RAZORPAY_KEY_SECRET'] ?? null) ?: getenv('RAZORPAY_KEY_SECRET') ?: '';

class FirestoreOrderManager {
    
    private $firestore; // Now FirestoreRestClient instead of SDK client
    private $razorpayKeySecret;
    
    public function __construct() {
        $this->razorpayKeySecret = $GLOBALS['RAZORPAY_KEY_SECRET'];
        $this->initializeFirestore();
    }
    
    private function initializeFirestore() {
        try {
            error_log("🔧 [DEBUG] FIRESTORE_MGR: Initializing Firestore REST client...");
            
            $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
            error_log("🔧 [DEBUG] FIRESTORE_MGR: Checking for service account at: $serviceAccountPath");
            
            if (!file_exists($serviceAccountPath)) {
                throw new Exception('Firebase service account file not found');
            }
            error_log("✅ [DEBUG] FIRESTORE_MGR: Service account file found");
            
            // Initialize REST client (no SDK required!)
            $this->firestore = new FirestoreRestClient(
                'e-commerce-1d40f',
                $serviceAccountPath,
                true // Enable token caching
            );
            
            error_log("✅ [DEBUG] FIRESTORE_MGR: *** FIRESTORE REST CLIENT INITIALIZED SUCCESSFULLY ***");
            
        } catch (Exception $e) {
            error_log("❌ [DEBUG] FIRESTORE_MGR: INITIALIZATION FAILED: " . $e->getMessage());
            error_log("❌ [DEBUG] FIRESTORE_MGR: Stack trace: " . $e->getTraceAsString());
            throw new Exception('Firestore initialization failed: ' . $e->getMessage());
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        error_log("🔧 [DEBUG] FIRESTORE_MGR: handleRequest() called");
        error_log("🔧 [DEBUG] FIRESTORE_MGR: Method: $method, URI: $requestUri");
        
        // Extract path after the script name
        $path = '/';
        if (strpos($requestUri, $scriptName) === 0) {
            $path = substr($requestUri, strlen($scriptName));
            if ($path === '') $path = '/';
        }
        
        // Handle query parameters for GET requests
        if ($method === 'GET' && strpos($path, '?') !== false) {
            $path = substr($path, 0, strpos($path, '?'));
        }
        
        error_log("🔧 [DEBUG] FIRESTORE_MGR: Extracted path: $path");
        
        try {
            switch ($path) {
                case '/create':
                    error_log("🔧 [DEBUG] FIRESTORE_MGR: Routing to createOrder()");
                    return $this->createOrder();
                    
                case '/status':
                    error_log("🔧 [DEBUG] FIRESTORE_MGR: Routing to getOrderStatus()");
                    return $this->getOrderStatus();
                    
                case '/update':
                    error_log("🔧 [DEBUG] FIRESTORE_MGR: Routing to updateOrderStatus()");
                    return $this->updateOrderStatus();
                    
                default:
                    error_log("❌ [DEBUG] FIRESTORE_MGR: Endpoint NOT FOUND: $path");
                    if (php_sapi_name() !== 'cli' && !headers_sent()) {
                        http_response_code(404);
                    }
                    return ['success' => false, 'error' => 'Endpoint not found'];
            }
        } catch (Exception $e) {
            error_log("❌ [DEBUG] FIRESTORE_MGR: EXCEPTION in handleRequest: " . $e->getMessage());
            error_log("❌ [DEBUG] FIRESTORE_MGR: Stack trace: " . $e->getTraceAsString());
            if (php_sapi_name() !== 'cli' && !headers_sent()) {
                http_response_code(500);
            }
            return ['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()];
        }
    }
    
    private function createOrder() {
        try {
            error_log("🔧 [DEBUG] FIRESTORE_MGR: createOrder() started");
            
            $rawInput = file_get_contents('php://input');
            error_log("🔧 [DEBUG] FIRESTORE_MGR: Raw input length: " . strlen($rawInput) . " bytes");
            
            $input = json_decode($rawInput, true);
            
            if (!$input) {
                error_log("❌ [DEBUG] FIRESTORE_MGR: Invalid JSON input");
                error_log("❌ [DEBUG] FIRESTORE_MGR: Raw input was: " . substr($rawInput, 0, 500));
                throw new Exception('Invalid JSON input');
            }
            
            error_log("🔧 [DEBUG] FIRESTORE_MGR: Parsed input successfully");
            error_log("🔧 [DEBUG] FIRESTORE_MGR: Input keys: " . implode(', ', array_keys($input)));
            error_log("🔧 [DEBUG] FIRESTORE_MGR: user_id in input: " . ($input['user_id'] ?? 'NULL'));
            
            // Validate required fields
            $required = ['order_id', 'payment_id', 'customer', 'product', 'pricing', 'shipping', 'payment'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    error_log("❌ [DEBUG] FIRESTORE_MGR: Missing required field: $field");
                    throw new Exception("Missing required field: $field");
                }
            }
            error_log("✅ [DEBUG] FIRESTORE_MGR: All required fields present");
            
            // Generate unique order number
            $orderNumber = $this->generateOrderNumber();
            error_log("🔧 [DEBUG] FIRESTORE_MGR: Generated order number: $orderNumber");
            
            // Check if order already exists (idempotent)
            $existingOrder = $this->getOrderByPaymentId($input['payment_id']);
            if ($existingOrder) {
                // Idempotent success: return the existing order instead of erroring
                error_log("✅ [DEBUG] FIRESTORE_MGR: Idempotent hit for payment {$input['payment_id']}, returning existing order");
                if (!headers_sent()) { header('X-Idempotent', 'true'); }
                return [
                    'success' => true,
                    'message' => 'Order already exists (idempotent)',
                    'order' => $existingOrder
                ];
            }
            
            // Resolve amount safely (prefer pricing.total; fallback to payment/order paise)
            $resolvedAmount = 0;
            if (isset($input['pricing']['total'])) {
                $resolvedAmount = floatval($input['pricing']['total']);
            } elseif (isset($input['payment']['amount'])) {
                // If Razorpay amount is in paise, convert to rupees when value looks like paise
                $amt = floatval($input['payment']['amount']);
                $resolvedAmount = ($amt > 1000) ? ($amt / 100.0) : $amt;
            } elseif (isset($input['amount'])) {
                $amt = floatval($input['amount']);
                $resolvedAmount = ($amt > 1000) ? ($amt / 100.0) : $amt;
            }

            // Create order document in Firestore using REST API
            $orderData = [
                'orderId' => $orderNumber,
                'razorpayOrderId' => $input['order_id'],
                'razorpayPaymentId' => $input['payment_id'],
                'uid' => $input['user_id'] ?? null, // Add uid field for user association
                'status' => 'confirmed',
                'amount' => $resolvedAmount,
                'currency' => $input['pricing']['currency'] ?? 'INR', // Add currency field
                'customer' => $input['customer'],
                'product' => $input['product'],
                'pricing' => $input['pricing'],
                'shipping' => $input['shipping'],
                'payment' => $input['payment'],
                'coupons' => isset($input['coupons']) && is_array($input['coupons']) ? $input['coupons'] : [],
                'createdAt' => firestoreTimestamp(), // REST API timestamp
                'updatedAt' => firestoreTimestamp(),
                'notes' => $input['notes'] ?? ''
            ];
            
            error_log("🔧 [DEBUG] FIRESTORE_MGR: Order data to save:");
            error_log("🔧 [DEBUG] FIRESTORE_MGR: - Order Number: $orderNumber");
            error_log("🔧 [DEBUG] FIRESTORE_MGR: - Razorpay Order ID: " . $input['order_id']);
            error_log("🔧 [DEBUG] FIRESTORE_MGR: - Razorpay Payment ID: " . $input['payment_id']);
            error_log("🔧 [DEBUG] FIRESTORE_MGR: - UID: " . ($orderData['uid'] ?? 'NULL'));
            error_log("🔧 [DEBUG] FIRESTORE_MGR: - Amount: $resolvedAmount");
            
            // Add affiliate tracking if present
            if (isset($input['affiliate_code'])) {
                $orderData['affiliate'] = [
                    'code' => $input['affiliate_code'],
                    'trackedAt' => firestoreTimestamp()
                ];
                error_log("🔧 [DEBUG] FIRESTORE_MGR: Affiliate code: " . $input['affiliate_code']);
            }
            
            // Save to Firestore using REST API (auto-generated ID)
            error_log("🔧 [DEBUG] FIRESTORE_MGR: Saving to Firestore collection 'orders'...");
            $result = $this->firestore->writeDocument('orders', $orderData);
            $orderId = $result['id'];
            
            error_log("✅ [DEBUG] FIRESTORE_MGR: *** ORDER SAVED TO FIRESTORE SUCCESSFULLY ***");
            error_log("✅ [DEBUG] FIRESTORE_MGR: Firestore Document ID: $orderId");
            error_log("✅ [DEBUG] FIRESTORE_MGR: Order Number: $orderNumber");
            
            // Add status history
            $this->addStatusHistory($orderId, 'confirmed', 'Order created and payment verified');
            
            // Send customer confirmation email
            $this->sendCustomerConfirmationEmail($orderData, $orderId, $orderNumber);
            
            // Process affiliate commission
            $this->processAffiliateCommission($orderData, $orderId);
            
            // Update inventory (if needed)
            $this->updateInventory($input['product']);

            // 🔢 Increment coupon usage counters using REST API
            $couponResults = [];
            if (!empty($input['coupons']) && is_array($input['coupons'])) {
                error_log("FIRESTORE ORDER: Processing " . count($input['coupons']) . " coupons for order $orderId");
                
                $orderMeta = [
                    'amount' => $resolvedAmount,
                    'customerEmail' => $input['customer']['email'] ?? null,
                ];
                
                $batchResult = batchApplyCouponsForOrderRest(
                    $this->firestore,
                    $input['coupons'],
                    $orderId,
                    $orderMeta,
                    $input['payment_id']
                );
                
                // Format results for logging
                foreach ($batchResult['results'] as $result) {
                    if ($result['success']) {
                        $status = ($result['idempotent'] ?? false) ? '↩️' : '✅';
                        $couponResults[] = "$status {$result['coupon']['code']} - {$result['message']}";
                    } else {
                        $couponResults[] = "❌ {$result['code']} - {$result['error']}";
                    }
                }
                
                error_log("FIRESTORE ORDER: Batch coupon processing - " . $batchResult['message']);
            } else {
                error_log("FIRESTORE ORDER: No coupons to process for order $orderId");
            }
            
            error_log("FIRESTORE ORDER: Order created successfully - ID: $orderId, Order Number: $orderNumber");
            error_log("FIRESTORE ORDER: Coupon results - " . (count($couponResults) > 0 ? implode(', ', $couponResults) : 'No coupons applied'));
            
            return [
                'success' => true,
                'orderId' => $orderId,
                'orderNumber' => $orderNumber,
                'message' => 'Order created successfully',
                'couponResults' => $couponResults
            ];
            
        } catch (Exception $e) {
            error_log("FIRESTORE ORDER ERROR: " . $e->getMessage());
            // If this is a coupon-related error, don't fail the entire order
            if (strpos($e->getMessage(), 'coupon') !== false || strpos($e->getMessage(), 'usage') !== false) {
                error_log("FIRESTORE ORDER: Coupon error occurred but order was created successfully");
                return [
                    'success' => true,
                    'message' => 'Order created successfully (coupon processing had issues)',
                    'orderId' => $orderId ?? 'unknown',
                    'orderNumber' => $orderNumber ?? 'unknown'
                ];
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getOrderStatus() {
        try {
            $orderId = $_GET['order_id'] ?? $_GET['order_number'] ?? '';
            
            if (!$orderId) {
                throw new Exception('Order ID is required');
            }
            
            $order = $this->getOrderById($orderId);
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            return [
                'success' => true,
                'order' => $order
            ];
            
        } catch (Exception $e) {
            error_log("FIRESTORE ORDER STATUS ERROR: " . $e->getMessage());
            http_response_code(404);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function updateOrderStatus() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['orderId'])) {
                throw new Exception('Order ID is required');
            }
            
            $orderId = $input['orderId'];
            $status = $input['status'] ?? null;
            $message = $input['message'] ?? '';
            $coupons = isset($input['coupons']) && is_array($input['coupons']) ? $input['coupons'] : null;
            
            // Find the order first (by document ID or orderId field)
            $order = $this->getOrderById($orderId);
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            $orderDocId = $order['_docId'];
            
            // Prepare updates
            $updates = [
                ['path' => 'updatedAt', 'value' => firestoreTimestamp()]
            ];
            
            if ($status) {
                $updates[] = ['path' => 'status', 'value' => $status];
            }
            
            // ✅ Support fulfillmentStatus updates (separate from order status)
            if (isset($input['fulfillmentStatus'])) {
                $updates[] = ['path' => 'fulfillmentStatus', 'value' => $input['fulfillmentStatus']];
                error_log("FIRESTORE_MGR_REST: Updating fulfillmentStatus to: " . $input['fulfillmentStatus']);
            }
            
            // ✅ Support tracking information updates
            if (isset($input['trackingId']) || isset($input['courierName'])) {
                $trackingData = [];
                if (isset($input['trackingId'])) {
                    $trackingData['trackingId'] = $input['trackingId'];
                }
                if (isset($input['courierName'])) {
                    $trackingData['courierName'] = $input['courierName'];
                }
                if (isset($input['trackingUrl'])) {
                    $trackingData['trackingUrl'] = $input['trackingUrl'];
                }
                
                // Store as nested object: shipping.tracking
                foreach ($trackingData as $key => $value) {
                    $updates[] = ['path' => "shipping.tracking.{$key}", 'value' => $value];
                }
                
                error_log("FIRESTORE_MGR_REST: Updating tracking info: " . json_encode($trackingData));
            }
            
            // ✅ Support deliveredAt timestamp
            if (isset($input['deliveredAt'])) {
                $updates[] = ['path' => 'deliveredAt', 'value' => firestoreTimestamp($input['deliveredAt'])];
            }
            
            if ($coupons) {
                $updates[] = ['path' => 'coupons', 'value' => $coupons];
            }
            
            // Optional exact amounts from client
            if (isset($input['amount_rupees_exact'])) {
                $updates[] = ['path' => 'amount', 'value' => floatval($input['amount_rupees_exact'])];
            }
            
            if (isset($input['amount_paise_exact']) && !isset($input['amount_rupees_exact'])) {
                $updates[] = ['path' => 'amount', 'value' => round(floatval($input['amount_paise_exact'])/100.0, 2)];
            }
            
            // Update document using REST API
            $this->firestore->updateDocument('orders', $orderDocId, $updates);
            
            // Add status history
            if ($status) {
                $this->addStatusHistory($orderDocId, $status, $message);
            }
            
            // If coupons supplied, increment usage
            $couponResults = [];
            if ($coupons && is_array($coupons)) {
                try {
                    $orderMeta = [
                        'amount' => $order['amount'] ?? 0,
                        'customerEmail' => $order['customer']['email'] ?? null,
                    ];
                    
                    $paymentId = $order['razorpayPaymentId'] ?? ($order['payment']['transaction_id'] ?? null);
                    
                    $batchResult = batchApplyCouponsForOrderRest(
                        $this->firestore,
                        $coupons,
                        $orderDocId,
                        $orderMeta,
                        $paymentId
                    );
                    
                    // Format results
                    foreach ($batchResult['results'] as $result) {
                        if ($result['success']) {
                            $status = ($result['idempotent'] ?? false) ? '↩️' : '✅';
                            $couponResults[] = "$status {$result['coupon']['code']} (update)";
                        } else {
                            $couponResults[] = "❌ {$result['code']} - {$result['error']}";
                        }
                    }
                } catch (Exception $t) {
                    error_log('FIRESTORE UPDATE: coupon increment failed - ' . $t->getMessage());
                }
            }
            
            error_log("FIRESTORE ORDER: Status updated - Order: $orderId, Status: $status");
            
            return [
                'success' => true,
                'message' => 'Order updated successfully',
                'couponResults' => $couponResults ?? []
            ];
            
        } catch (Exception $e) {
            error_log("FIRESTORE ORDER UPDATE ERROR: " . $e->getMessage());
            http_response_code(400);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function generateOrderNumber() {
        try {
            // Query latest order using REST API
            $orders = $this->firestore->queryDocuments(
                'orders',
                [],
                1,
                'createdAt',
                'DESCENDING'
            );
            
            $lastNumber = 0;
            
            if (!empty($orders)) {
                $latestOrder = $orders[0]['data'];
                if (isset($latestOrder['orderId']) && preg_match('/ATRL-(\d+)/', $latestOrder['orderId'], $matches)) {
                    $lastNumber = intval($matches[1]);
                }
            }
            
            $nextNumber = $lastNumber + 1;
            return 'ATRL-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
        } catch (Exception $e) {
            error_log("FIRESTORE ORDER NUMBER ERROR: " . $e->getMessage());
            // Fallback to timestamp-based number
            return 'ATRL-' . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
        }
    }
    
    private function getOrderByPaymentId($paymentId) {
        try {
            // Query by razorpayPaymentId field
            $orders = $this->firestore->queryDocuments(
                'orders',
                [
                    ['field' => 'razorpayPaymentId', 'op' => 'EQUAL', 'value' => $paymentId]
                ],
                1
            );
            
            if (!empty($orders)) {
                $order = $orders[0]['data'];
                $order['_docId'] = $orders[0]['id'];
                return $order;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("FIRESTORE GET ORDER BY PAYMENT ERROR: " . $e->getMessage());
            return null;
        }
    }
    
    private function getOrderById($orderId) {
        try {
            // First try to get by document ID
            $result = $this->firestore->getDocument('orders', $orderId);
            
            if ($result) {
                $order = $result['data'];
                $order['_docId'] = $result['id'];
                return $order;
            }
            
            // If not found by document ID, try by orderId field
            $orders = $this->firestore->queryDocuments(
                'orders',
                [
                    ['field' => 'orderId', 'op' => 'EQUAL', 'value' => $orderId]
                ],
                1
            );
            
            if (!empty($orders)) {
                $order = $orders[0]['data'];
                $order['_docId'] = $orders[0]['id'];
                return $order;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("FIRESTORE GET ORDER BY ID ERROR: " . $e->getMessage());
            return null;
        }
    }
    
    private function addStatusHistory($orderId, $status, $message = '') {
        try {
            $statusData = [
                'orderId' => $orderId,
                'status' => $status,
                'message' => $message,
                'createdAt' => firestoreTimestamp()
            ];
            
            // Write to order_status_history collection
            $this->firestore->writeDocument('order_status_history', $statusData);
            
        } catch (Exception $e) {
            error_log("FIRESTORE STATUS HISTORY ERROR: " . $e->getMessage());
        }
    }
    
    private function processAffiliateCommission($orderData, $orderId) {
        try {
            // Check for affiliate code
            $affiliateCode = $this->extractAffiliateCode($orderData);
            
            if (!$affiliateCode) {
                error_log("FIRESTORE AFFILIATE: No affiliate code found in order {$orderData['orderId']}");
                return;
            }
            
            // Look up affiliate information
            $affiliateInfo = $this->getAffiliateByCode($affiliateCode);
            
            if (!$affiliateInfo) {
                error_log("FIRESTORE AFFILIATE: Affiliate not found for code: $affiliateCode");
                return;
            }
            
            // Calculate commission (10% of order total)
            $orderTotal = $orderData['pricing']['total'] ?? 0;
            $commissionAmount = $orderTotal * 0.10;
            
            if ($commissionAmount <= 0) {
                error_log("FIRESTORE AFFILIATE: No commission to process for order {$orderData['orderId']}");
                return;
            }
            
            // Create commission record in Firestore
            $this->createCommissionRecord($affiliateInfo, $orderData, $commissionAmount, $orderId);
            
            // Send commission email
            $this->sendCommissionEmail($affiliateInfo, $commissionAmount, $orderData['orderId']);
            
            error_log("FIRESTORE AFFILIATE: Commission processed - ₹$commissionAmount for affiliate {$affiliateInfo['email']} on order {$orderData['orderId']}");
            
        } catch (Exception $e) {
            error_log("FIRESTORE AFFILIATE COMMISSION ERROR: " . $e->getMessage());
        }
    }
    
    private function extractAffiliateCode($orderData) {
        // Check multiple possible locations for affiliate code
        $affiliateCode = null;
        
        // Check affiliate object
        if (isset($orderData['affiliate']['code'])) {
            $affiliateCode = $orderData['affiliate']['code'];
        }
        
        // Check URL parameters in payment data
        if (!$affiliateCode && isset($orderData['payment']['url_params']['ref'])) {
            $affiliateCode = $orderData['payment']['url_params']['ref'];
        }
        
        // Check customer data for affiliate reference
        if (!$affiliateCode && isset($orderData['customer']['affiliate_code'])) {
            $affiliateCode = $orderData['customer']['affiliate_code'];
        }
        
        return $affiliateCode;
    }
    
    private function getAffiliateByCode($affiliateCode) {
        try {
            // Query affiliates collection
            $affiliates = $this->firestore->queryDocuments(
                'affiliates',
                [
                    ['field' => 'code', 'op' => 'EQUAL', 'value' => $affiliateCode]
                ],
                1
            );
            
            if (!empty($affiliates)) {
                $data = $affiliates[0]['data'];
                return [
                    'id' => $affiliates[0]['id'],
                    'email' => $data['email'] ?? '',
                    'name' => $data['displayName'] ?? $data['name'] ?? 'Affiliate',
                    'code' => $affiliateCode,
                    'status' => $data['status'] ?? 'active'
                ];
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("FIRESTORE AFFILIATE LOOKUP ERROR: " . $e->getMessage());
            return null;
        }
    }
    
    private function createCommissionRecord($affiliateInfo, $orderData, $commissionAmount, $orderId) {
        try {
            $commissionData = [
                'affiliateId' => $affiliateInfo['id'],
                'affiliateEmail' => $affiliateInfo['email'],
                'affiliateName' => $affiliateInfo['name'],
                'orderId' => $orderData['orderId'],
                'orderNumber' => $orderData['orderId'],
                'commissionAmount' => $commissionAmount,
                'commissionRate' => 10.0,
                'status' => 'pending',
                'createdAt' => firestoreTimestamp(),
                'paidAt' => null
            ];
            
            $this->firestore->writeDocument('affiliate_commissions', $commissionData);
            
            error_log("FIRESTORE COMMISSION: Commission record created - Amount: ₹$commissionAmount");
            
        } catch (Exception $e) {
            error_log("FIRESTORE COMMISSION RECORD ERROR: " . $e->getMessage());
        }
    }
    
    private function sendCommissionEmail($affiliateInfo, $commissionAmount, $orderNumber) {
        try {
            require_once __DIR__ . '/affiliate_email_sender.php';
            
            $result = sendAffiliateCommissionEmail(null, [
                'email' => $affiliateInfo['email'],
                'name' => $affiliateInfo['name'],
                'commission' => $commissionAmount,
                'orderId' => $orderNumber
            ]);
            
            if ($result['success']) {
                error_log("FIRESTORE AFFILIATE EMAIL: Enhanced commission notification sent to {$affiliateInfo['email']}");
            } else {
                error_log("FIRESTORE AFFILIATE EMAIL ERROR: Failed to send commission notification: " . ($result['error'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            error_log("FIRESTORE AFFILIATE EMAIL EXCEPTION: " . $e->getMessage());
        }
    }
    
    private function sendCustomerConfirmationEmail($orderData, $orderId, $orderNumber) {
        try {
            require_once __DIR__ . '/brevo_email_service.php';
            
            $customerName = trim($orderData['customer']['firstName'] . ' ' . $orderData['customer']['lastName']);
            $customerEmail = $orderData['customer']['email'];
            
            error_log("CUSTOMER EMAIL: Preparing confirmation for {$customerEmail}");
            
            // Build order items list
            $itemsList = '';
            if (isset($orderData['product']['items']) && is_array($orderData['product']['items'])) {
                foreach ($orderData['product']['items'] as $item) {
                    $itemsList .= "<li>{$item['title']} × {$item['quantity']} - ₹" . number_format($item['price'] * $item['quantity'], 2) . "</li>";
                }
            } else {
                $itemsList = "<li>{$orderData['product']['title']} - ₹" . number_format($orderData['amount'], 2) . "</li>";
            }
            
            // Build shipping address
            $shippingAddress = $orderData['shipping']['address'] . '<br>' .
                             $orderData['shipping']['city'] . ', ' . $orderData['shipping']['state'] . '<br>' .
                             $orderData['shipping']['pincode'] . '<br>' .
                             $orderData['shipping']['country'];
            
            // Create email content
            $htmlContent = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; }
                    .order-details { background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; }
                    .order-number { font-size: 24px; font-weight: bold; color: #667eea; }
                    .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 14px; color: #6b7280; border-radius: 0 0 8px 8px; }
                    .button { display: inline-block; background: #ff6b35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🎉 Order Confirmed!</h1>
                        <p style='margin: 0;'>Thank you for your order, {$customerName}</p>
                    </div>
                    
                    <div class='content'>
                        <p>Your order has been successfully placed and confirmed. We're excited to get your products to you!</p>
                        
                        <div class='order-details'>
                            <p><strong>Order Number:</strong> <span class='order-number'>{$orderNumber}</span></p>
                            <p><strong>Order Total:</strong> ₹" . number_format($orderData['amount'], 2) . "</p>
                            <p><strong>Status:</strong> Confirmed ✅</p>
                        </div>
                        
                        <h3>📦 Order Items:</h3>
                        <ul>{$itemsList}</ul>
                        
                        <h3>🚚 Shipping Address:</h3>
                        <p>{$shippingAddress}</p>
                        
                        <p><strong>Estimated Delivery:</strong> 2-5 business days</p>
                        
                        <div style='text-align: center;'>
                            <a href='https://attral.in/my-orders.html' class='button'>Track Your Order</a>
                        </div>
                        
                        <p style='margin-top: 30px;'>If you have any questions about your order, please contact us at <a href='mailto:info@attral.in'>info@attral.in</a> or call +91 8903479870.</p>
                    </div>
                    
                    <div class='footer'>
                        <p><strong>ATTRAL Electronics</strong></p>
                        <p>Phase 2 Sathuvachari, Vellore - 632009, Tamil Nadu, India</p>
                        <p>© " . date('Y') . " ATTRAL. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Send using Brevo service
            $brevoService = new BrevoEmailService();
            $result = $brevoService->sendTransactionalEmail(
                $customerEmail,
                "Order Confirmation - {$orderNumber}",
                $htmlContent,
                ['toName' => $customerName]
            );
            
            if ($result['success']) {
                error_log("CUSTOMER EMAIL: ✅ Confirmation sent to {$customerEmail} for order {$orderNumber}");
            } else {
                error_log("CUSTOMER EMAIL: ❌ Failed to send - " . ($result['error'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            error_log("CUSTOMER EMAIL EXCEPTION: " . $e->getMessage());
            // Don't fail order if email fails
        }
    }
    
    private function updateInventory($productData) {
        // Inventory management can be implemented here if needed
        // For now, we'll just log the product data
        error_log("FIRESTORE INVENTORY: Product data processed - " . json_encode($productData));
    }
}

// Handle the request
$responseSent = false;
$result = null;

// Add comprehensive debugging
error_log("FIRESTORE API: Request started - " . date('c'));
error_log("FIRESTORE API: Request method - " . $_SERVER['REQUEST_METHOD']);
error_log("FIRESTORE API: Request URI - " . $_SERVER['REQUEST_URI']);

try {
    $manager = new FirestoreOrderManager();
    $result = $manager->handleRequest();
} catch (Exception $e) {
    error_log("FIRESTORE ORDER MANAGER ERROR: " . $e->getMessage());
    
    $result = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Output the final result (only once)
if ($result) {
    // Add unique identifier to track which API is responding
    $result['api_source'] = 'firestore_order_manager_rest';
    $result['timestamp'] = date('c');
    $result['request_id'] = uniqid('firestore_rest_', true);

    // Ensure clean single JSON output
    if (function_exists('ob_get_level') && ob_get_level() > 0) {
        @ob_clean();
    }
    if (!headers_sent()) {
        http_response_code(200);
        header('X-API-Source: firestore_order_manager_rest');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    $payload = json_encode($result);
    error_log("FIRESTORE API: About to output JSON response - " . $payload);
    echo $payload;
    error_log("FIRESTORE API: JSON response output complete");
    exit; // hard-stop to prevent any further output
} else {
    error_log("FIRESTORE API: No result to output");
}
?>

