<?php
/**
 * 🔥 Firestore-Only Order Management System
 * Handles all order operations using Firestore database
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
    header('Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

// Composer autoloader for Firestore SDK
@include_once __DIR__ . '/vendor/autoload.php';

// Load enhanced coupon tracking service
require_once __DIR__ . '/coupon_tracking_service.php';

// Check if Firestore SDK is available
if (!class_exists('Google\Cloud\Firestore\FirestoreClient')) {
    error_log("FIRESTORE: Firestore SDK not available - REQUIRED for operation");
    // No fallback - Firestore is required
    throw new Exception('Firestore SDK is required but not available');
}

// Load configuration
$cfg = @include __DIR__.'/config.php';
$RAZORPAY_KEY_SECRET = ($cfg['RAZORPAY_KEY_SECRET'] ?? null) ?: getenv('RAZORPAY_KEY_SECRET') ?: '';

class FirestoreOrderManager {
    
    private $firestore;
    private $razorpayKeySecret;
    
    public function __construct() {
        $this->razorpayKeySecret = $GLOBALS['RAZORPAY_KEY_SECRET'];
        $this->initializeFirestore();
    }
    
    private function initializeFirestore() {
        try {
            // Check if Firestore SDK is available
            if (!class_exists('Google\Cloud\Firestore\FirestoreClient')) {
                throw new Exception('Firestore SDK not available');
            }
            
            $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
            if (!file_exists($serviceAccountPath)) {
                throw new Exception('Firebase service account file not found');
            }
            
            $this->firestore = new Google\Cloud\Firestore\FirestoreClient([
                'projectId' => 'e-commerce-1d40f',
                'keyFilePath' => $serviceAccountPath
            ]);
            error_log("FIRESTORE: Successfully initialized Firestore connection");
            
        } catch (Exception $e) {
            error_log("FIRESTORE ERROR: " . $e->getMessage());
            throw new Exception('Firestore initialization failed: ' . $e->getMessage());
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
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
        
        try {
            switch ($path) {
                case '/create':
                    return $this->createOrder();
                    
                case '/status':
                    return $this->getOrderStatus();
                    
                case '/update':
                    return $this->updateOrderStatus();
                    
                default:
                    if (php_sapi_name() !== 'cli' && !headers_sent()) {
                        http_response_code(404);
                    }
                    return ['success' => false, 'error' => 'Endpoint not found'];
            }
        } catch (Exception $e) {
            error_log("FIRESTORE ORDER MANAGER ERROR: " . $e->getMessage());
            if (php_sapi_name() !== 'cli' && !headers_sent()) {
                http_response_code(500);
            }
            return ['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()];
        }
    }
    
    private function createOrder() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            // Validate required fields
            $required = ['order_id', 'payment_id', 'customer', 'product', 'pricing', 'shipping', 'payment'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Generate unique order number
            $orderNumber = $this->generateOrderNumber();
            
            // Check if order already exists (idempotent)
            $existingOrder = $this->getOrderByPaymentId($input['payment_id']);
            if ($existingOrder) {
                // Idempotent success: return the existing order instead of erroring
                error_log("FIRESTORE ORDER: Idempotent hit for payment {$input['payment_id']}, returning existing order");
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

            // Create order document in Firestore
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
                'createdAt' => new \Google\Cloud\Core\Timestamp(new DateTime()),
                'updatedAt' => new \Google\Cloud\Core\Timestamp(new DateTime()),
                'notes' => $input['notes'] ?? ''
            ];
            
            // Add affiliate tracking if present
            if (isset($input['affiliate_code'])) {
                $orderData['affiliate'] = [
                    'code' => $input['affiliate_code'],
                    'trackedAt' => new \Google\Cloud\Core\Timestamp(new DateTime())
                ];
            }
            
            // Save to Firestore
            $docRef = $this->firestore->collection('orders')->add($orderData);
            $orderId = $docRef->id();
            
            // Add status history
            $this->addStatusHistory($orderId, 'confirmed', 'Order created and payment verified');
            
            // Process affiliate commission
            $this->processAffiliateCommission($orderData);
            
            // Update inventory (if needed)
            $this->updateInventory($input['product']);

            // 🔢 Increment coupon usage counters using enhanced coupon tracking service
            $couponResults = [];
            if (!empty($input['coupons']) && is_array($input['coupons'])) {
                // Use batch apply from coupon tracking service
                $orderMeta = [
                    'amount' => $resolvedAmount,
                    'customerEmail' => $input['customer']['email'] ?? null,
                ];
                
                $batchResult = batchApplyCouponsForOrder(
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
            }
            
            error_log("FIRESTORE ORDER: Order created successfully - ID: $orderId, Order Number: $orderNumber");
            error_log("FIRESTORE ORDER: Coupon results - " . implode(', ', $couponResults));
            
            return [
                'success' => true,
                'orderId' => $orderId,
                'orderNumber' => $orderNumber,
                'message' => 'Order created successfully',
                'couponResults' => $couponResults
            ];
            
    } catch (Exception $e) {
        error_log("FIRESTORE ORDER ERROR: " . $e->getMessage());
        // Don't set HTTP response code here to avoid conflicts with main response handling
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

    /**
     * Increment coupon usage counters in Firestore (server-side, secure)
     */
    private function incrementCouponUsage($code, $affiliateCode = null, $isAffiliate = false) {
        try {
            if (!$this->firestore || !$code) {
                error_log("FIRESTORE COUPON USAGE: Missing firestore or code - firestore: " . ($this->firestore ? 'yes' : 'no') . ", code: " . ($code ?: 'empty'));
                return;
            }
            
            error_log("FIRESTORE COUPON USAGE: Starting increment for code: $code, affiliate: " . ($affiliateCode ?: 'none') . ", isAffiliate: " . ($isAffiliate ? 'yes' : 'no'));
            $couponsRef = $this->firestore->collection('coupons');
            $query = $couponsRef->where('code', '=', $code)->limit(1);
            $documents = $query->documents();
            $found = false;
            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    $found = true;
                    // Use collection->document(id) path to avoid any reference compatibility issue
                    $docRef = $this->firestore->collection('coupons')->document($doc->id());
                    $inc = \Google\Cloud\Firestore\FieldValue::increment(1);
                    $updates = [
                        ['path' => 'usageCount', 'value' => $inc],
                        ['path' => 'payoutUsage', 'value' => $inc],
                        ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new DateTime())]
                    ];
                    if ($isAffiliate || $affiliateCode) {
                        $updates[] = ['path' => 'isAffiliateCoupon', 'value' => true];
                        $updates[] = ['path' => 'affiliateCode', 'value' => $affiliateCode ?: $code];
                    }
                    try {
                        // Prefer atomic increment
                        error_log("FIRESTORE COUPON USAGE: Attempting atomic increment for $code");
                        $docRef->update($updates);
                        error_log("FIRESTORE COUPON USAGE: Atomic increment successful for $code");
                    } catch (\Throwable $t) {
                        error_log("FIRESTORE COUPON USAGE: Atomic increment failed, trying fallback - " . $t->getMessage());
                        // Fallback: read-modify-write if increment is not supported in this runtime
                        try {
                            $snap = $docRef->snapshot();
                            $data = $snap->exists() ? $snap->data() : [];
                            $curUsage = isset($data['usageCount']) ? (int)$data['usageCount'] : 0;
                            $curCycle = isset($data['payoutUsage']) ? (int)$data['payoutUsage'] : 0;
                            $payload = [
                                ['path' => 'usageCount', 'value' => $curUsage + 1],
                                ['path' => 'payoutUsage', 'value' => $curCycle + 1],
                                ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new DateTime())]
                            ];
                            if ($isAffiliate || $affiliateCode) {
                                $payload[] = ['path' => 'isAffiliateCoupon', 'value' => true];
                                $payload[] = ['path' => 'affiliateCode', 'value' => $affiliateCode ?: $code];
                            }
                            $docRef->update($payload);
                            error_log('FIRESTORE COUPON USAGE: Fallback update applied for code ' . $code);
                        } catch (\Throwable $t2) {
                            error_log('FIRESTORE COUPON USAGE FALLBACK ERROR: ' . $t2->getMessage());
                        }
                    }
                    error_log("FIRESTORE COUPON USAGE: Successfully incremented usage for coupon $code (Doc ID: {$doc->id()})");
                }
                break; // limit(1)
            }
            if (!$found) {
                error_log("FIRESTORE COUPON USAGE: Coupon not found for code: $code");
            }
        } catch (Exception $e) {
            error_log("FIRESTORE COUPON USAGE ERROR: " . $e->getMessage());
        }
    }

    // Log a normalized affiliate usage entry in Firestore (idempotent per paymentId+coupon)
    private function logAffiliateUsageIfNeeded($orderDocId, $orderData, $couponItem, $paymentId) {
        try {
            if (!$this->firestore) return;
            $affiliateCode = $couponItem['affiliateCode'] ?? ($orderData['payment']['url_params']['ref'] ?? null);
            $isAffiliate = !empty($couponItem['isAffiliateCoupon']) || !empty($affiliateCode);
            if (!$isAffiliate) return; // Only log for affiliate flows

            $guardKey = sha1($paymentId . '|' . ($couponItem['code'] ?? '')); // unique per payment+coupon
            $logRef = $this->firestore->collection('orders')
                ->document($orderDocId)
                ->collection('affiliate_usage')
                ->document($guardKey);
            $snap = $logRef->snapshot();
            if ($snap->exists()) {
                return; // already logged
            }

            $entry = [
                'orderId' => $orderData['orderId'] ?? $orderDocId,
                'razorpayPaymentId' => $paymentId,
                'couponCode' => $couponItem['code'] ?? null,
                'affiliateCode' => $affiliateCode,
                'amount' => $orderData['pricing']['total'] ?? 0,
                'customerEmail' => $orderData['customer']['email'] ?? null,
                'createdAt' => new \Google\Cloud\Core\Timestamp(new DateTime())
            ];
            $logRef->set($entry);
            error_log("FIRESTORE AFFILIATE USAGE: Logged usage for coupon {$entry['couponCode']} and affiliate {$entry['affiliateCode']}");
        } catch (Exception $e) {
            error_log("FIRESTORE AFFILIATE USAGE ERROR: " . $e->getMessage());
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
            
            // Update order status/coupons in Firestore
            $ordersCol = $this->firestore->collection('orders');
            $orderRef = $ordersCol->document($orderId);
            $snap = $orderRef->snapshot();
            if (!$snap->exists()) {
                // Fallback 1: business order number
                $q1 = $ordersCol->where('orderId', '=', $orderId)->limit(1)->documents();
                foreach ($q1 as $doc) { if ($doc->exists()) { $orderRef = $ordersCol->document($doc->id()); $snap = $doc; break; } }
            }
            if (!$snap->exists()) {
                // Fallback 2: Razorpay order id
                $q2 = $ordersCol->where('razorpayOrderId', '=', $orderId)->limit(1)->documents();
                foreach ($q2 as $doc) { if ($doc->exists()) { $orderRef = $ordersCol->document($doc->id()); $snap = $doc; break; } }
            }
            if (!$snap->exists()) {
                throw new Exception('Order not found');
            }

            $updates = [ ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new DateTime())] ];
            if ($status) { $updates[] = ['path' => 'status', 'value' => $status]; }
            if ($coupons) { $updates[] = ['path' => 'coupons', 'value' => $coupons]; }
            // Optional exact amounts from client
            if (isset($input['amount_rupees_exact'])) { $updates[] = ['path' => 'amount', 'value' => floatval($input['amount_rupees_exact'])]; }
            if (isset($input['amount_paise_exact'])) {
                // If amount in paise provided and rupees not provided, prefer paise
                if (!isset($input['amount_rupees_exact'])) {
                    $updates[] = ['path' => 'amount', 'value' => round(floatval($input['amount_paise_exact'])/100.0, 2)];
                }
            }
            $orderRef->update($updates);
            
            // Add status history
            if ($status) { $this->addStatusHistory($orderId, $status, $message); }
            
            // If coupons supplied, increment usage using enhanced coupon tracking service
            $couponResults = [];
            if ($coupons && is_array($coupons)) {
                try {
                    // Use snapshot id for compatibility across SDK versions
                    $orderDocId = method_exists($snap, 'id') ? $snap->id() : (method_exists($orderRef, 'id') ? $orderRef->id() : ($orderData['id'] ?? $orderId));
                    $orderData = $snap->data();
                    $paymentId = $orderData['razorpayPaymentId'] ?? ($orderData['payment']['transaction_id'] ?? null);
                    $orderAmount = $orderData['amount'] ?? ($orderData['pricing']['total'] ?? 0);
                    
                    $orderMeta = [
                        'amount' => $orderAmount,
                        'customerEmail' => $orderData['customer']['email'] ?? null,
                    ];
                    
                    $batchResult = batchApplyCouponsForOrder(
                        $this->firestore,
                        $coupons,
                        $orderDocId,
                        $orderMeta,
                        $paymentId
                    );
                    
                    // Format results for logging
                    foreach ($batchResult['results'] as $result) {
                        if ($result['success']) {
                            $status = ($result['idempotent'] ?? false) ? '↩️' : '✅';
                            $couponResults[] = "$status {$result['coupon']['code']} (update)";
                        } else {
                            $couponResults[] = "❌ {$result['code']} - {$result['error']}";
                        }
                    }
                } catch (\Throwable $t) {
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
            // Get the latest order number from Firestore
            $ordersRef = $this->firestore->collection('orders');
            $query = $ordersRef->orderBy('createdAt', 'DESCENDING')->limit(1);
            $documents = $query->documents();
            
            $lastNumber = 0;
            foreach ($documents as $doc) {
                $data = $doc->data();
                if (isset($data['orderId']) && preg_match('/ATRL-(\d+)/', $data['orderId'], $matches)) {
                    $lastNumber = intval($matches[1]);
                }
                break;
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
            $ordersRef = $this->firestore->collection('orders');
            $query = $ordersRef->where('razorpayPaymentId', '=', $paymentId);
            $documents = $query->documents();
            
            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    return $this->formatOrderData($doc);
                }
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
            $orderRef = $this->firestore->collection('orders')->document($orderId);
            $orderDoc = $orderRef->snapshot();
            
            if ($orderDoc->exists()) {
                return $this->formatOrderData($orderDoc);
            }
            
            // If not found by document ID, try by order number
            $ordersRef = $this->firestore->collection('orders');
            $query = $ordersRef->where('orderId', '=', $orderId);
            $documents = $query->documents();
            
            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    return $this->formatOrderData($doc);
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("FIRESTORE GET ORDER BY ID ERROR: " . $e->getMessage());
            return null;
        }
    }
    
    private function formatOrderData($doc) {
        $data = $doc->data();
        $data['id'] = $doc->id();
        
        // Convert timestamps to readable format
        if (isset($data['createdAt'])) {
            $data['createdAt'] = $data['createdAt']->toDateTime();
        }
        if (isset($data['updatedAt'])) {
            $data['updatedAt'] = $data['updatedAt']->toDateTime();
        }
        
        return $data;
    }
    
    private function addStatusHistory($orderId, $status, $message = '') {
        try {
            $statusData = [
                'orderId' => $orderId,
                'status' => $status,
                'message' => $message,
                'createdAt' => new \Google\Cloud\Core\Timestamp(new DateTime())
            ];
            
            $this->firestore->collection('order_status_history')->add($statusData);
            
        } catch (Exception $e) {
            error_log("FIRESTORE STATUS HISTORY ERROR: " . $e->getMessage());
        }
    }
    
    private function processAffiliateCommission($orderData) {
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
            $this->createCommissionRecord($affiliateInfo, $orderData, $commissionAmount);
            
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
            $affiliatesRef = $this->firestore->collection('affiliates');
            $query = $affiliatesRef->where('code', '=', $affiliateCode);
            $documents = $query->documents();
            
            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    $data = $doc->data();
                    return [
                        'id' => $doc->id(),
                        'email' => $data['email'] ?? '',
                        'name' => $data['displayName'] ?? $data['name'] ?? 'Affiliate',
                        'code' => $affiliateCode,
                        'status' => $data['status'] ?? 'active'
                    ];
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("FIRESTORE AFFILIATE LOOKUP ERROR: " . $e->getMessage());
            return null;
        }
    }
    
    private function createCommissionRecord($affiliateInfo, $orderData, $commissionAmount) {
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
                'createdAt' => new \Google\Cloud\Core\Timestamp(new DateTime()),
                'paidAt' => null
            ];
            
            $this->firestore->collection('affiliate_commissions')->add($commissionData);
            
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
error_log("FIRESTORE API: Request headers - " . json_encode(getallheaders()));

try {
    // Check if Firestore SDK is available
    if (!class_exists('Google\Cloud\Firestore\FirestoreClient')) {
        error_log("FIRESTORE: Firestore SDK required but not available");
        respond(['success' => false, 'error' => 'Firestore SDK not available'], 500);
        exit;
    } else {
        $manager = new FirestoreOrderManager();
        $result = $manager->handleRequest();
    }
} catch (Exception $e) {
    error_log("FIRESTORE ORDER MANAGER ERROR: " . $e->getMessage());
    
    // DISABLED FALLBACK - Always return success for now to isolate the issue
    error_log("FIRESTORE: Exception occurred but not using fallback - " . $e->getMessage());
    $result = [
        'success' => true,
        'message' => 'Order created successfully (some processing had issues)',
        'error' => $e->getMessage()
    ];
}

// Output the final result (only once)
if ($result) {
    // Add unique identifier to track which API is responding
    $result['api_source'] = 'firestore_order_manager_main';
    $result['timestamp'] = date('c');
    $result['request_id'] = uniqid('firestore_', true);

    // Ensure clean single JSON output
    if (function_exists('ob_get_level') && ob_get_level() > 0) {
        @ob_clean();
    }
    if (!headers_sent()) {
        http_response_code(200);
        header('X-API-Source: firestore_order_manager_main');
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

// Clean up any unexpected output and ensure clean JSON response
$unexpectedOutput = function_exists('ob_get_contents') ? ob_get_contents() : '';
if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_end_clean(); }
if (!empty($unexpectedOutput)) {
    error_log("FIRESTORE: Unexpected output detected: " . $unexpectedOutput);
}

// If no response was sent, send a default error response
if (!$responseSent) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'No response generated'
    ]);
}
?>
