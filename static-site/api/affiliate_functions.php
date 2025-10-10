<?php
/**
 * Affiliate Functions API - PHP Replacement for Firebase Cloud Functions
 * Provides all affiliate management functionality for Hostinger deployment
 */

// Headers for CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Include Firestore service with error handling
if (!file_exists(__DIR__ . '/firestore_admin_service.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Firestore admin service not found. Please ensure firestore_admin_service.php exists in the api directory.'
    ]);
    exit;
}
require_once __DIR__ . '/firestore_admin_service.php';

// Route handling
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Extract function name from URL path
// Expected format: /api/affiliate_functions.php/functionName or ?action=functionName
$action = $_GET['action'] ?? '';
if (empty($action) && preg_match('/affiliate_functions\.php\/(\w+)/', $requestUri, $matches)) {
    $action = $matches[1];
}

// Initialize Firestore connection
try {
    $firestore = initFirestoreAdmin();
} catch (Exception $e) {
    error_log("Affiliate Functions: Firestore init failed - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Route to appropriate function
switch ($action) {
    case 'createAffiliateProfile':
        createAffiliateProfile($firestore);
        break;
    case 'getAffiliateOrders':
        getAffiliateOrders($firestore);
        break;
    case 'getAffiliateStats':
        getAffiliateStats($firestore);
        break;
    case 'getPaymentDetails':
        getPaymentDetails($firestore);
        break;
    case 'updatePaymentDetails':
        updatePaymentDetails($firestore);
        break;
    case 'getPayoutSettings':
        getPayoutSettings($firestore);
        break;
    case 'updatePayoutSettings':
        updatePayoutSettings($firestore);
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Function not found', 'action' => $action]);
        break;
}

/**
 * Create Affiliate Profile
 */
function createAffiliateProfile($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $uid = $input['uid'] ?? '';
        $email = $input['email'] ?? '';
        $name = $input['name'] ?? '';
        $phone = $input['phone'] ?? '';
        
        if (empty($uid) || empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'uid and email are required']);
            return;
        }
        
        // Generate affiliate code
        $affiliateCode = generateAffiliateCode();
        
        // Create affiliate data
        $affiliateData = [
            'uid' => $uid,
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'affiliateCode' => $affiliateCode,
            'status' => 'pending',
            'commissionRate' => 0.10, // 10% default
            'totalEarnings' => 0,
            'totalOrders' => 0,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ];
        
        // Add to Firestore
        $docRef = $firestore->collection('affiliates')->add($affiliateData);
        
        echo json_encode([
            'success' => true,
            'affiliateId' => $docRef->id(),
            'affiliateCode' => $affiliateCode
        ]);
        
    } catch (Exception $e) {
        error_log("Create Affiliate Profile Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Get Affiliate Orders
 */
function getAffiliateOrders($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        // Support both GET and POST for flexibility
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $code = $_GET['code'] ?? '';
            $status = $_GET['status'] ?? '';
            $pageSize = (int)($_GET['pageSize'] ?? 100);
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
            $code = $input['code'] ?? '';
            $status = $input['status'] ?? '';
            $pageSize = (int)($input['pageSize'] ?? 100);
        }
        
        if (empty($code)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Affiliate code is required']);
            return;
        }
        
        // 🔍 DEBUG: Log query parameters
        error_log("AFFILIATE ORDERS: Querying orders for code=$code, status=$status, pageSize=$pageSize");
        
        // Query ALL confirmed orders (cannot filter by coupons array directly in Firestore)
        // Must manually filter through coupons array in each order
        $query = $firestore->collection('orders')
            ->where('status', '=', 'confirmed')
            ->orderBy('createdAt', 'DESC')
            ->limit($pageSize);
        
        $documents = $query->documents();
        $orders = [];
        $totalAmount = 0;
        $processedCount = 0;
        $matchedOrderIds = [];
        
        foreach ($documents as $doc) {
            if ($doc->exists()) {
                $processedCount++;
                $orderData = $doc->data();
                $coupons = $orderData['coupons'] ?? [];
                
                // 🔍 DEBUG: Log first few orders for debugging
                if ($processedCount <= 5) {
                    error_log("AFFILIATE ORDERS: Order " . ($orderData['orderId'] ?? $doc->id()) . " has " . count($coupons) . " coupons: " . json_encode(array_column($coupons, 'code')));
                }
                
                // Manually check if this order used the affiliate's coupon
                $hasAffiliateCoupon = false;
                foreach ($coupons as $coupon) {
                    if (isset($coupon['code']) && $coupon['code'] === $code) {
                        $hasAffiliateCoupon = true;
                        $matchedOrderIds[] = $orderData['orderId'] ?? $doc->id();
                        break;
                    }
                }
                
                if ($hasAffiliateCoupon) {
                    $orderData['id'] = $doc->id();
                    $orders[] = $orderData;
                    $totalAmount += ($orderData['amount'] ?? 0);
                }
            }
        }
        
        // 🔍 DEBUG: Log final results
        error_log("AFFILIATE ORDERS: Query complete - Processed $processedCount total orders, found " . count($orders) . " matching orders");
        error_log("AFFILIATE ORDERS: Matched order IDs: " . implode(', ', $matchedOrderIds));
        error_log("AFFILIATE ORDERS: Total amount: ₹$totalAmount");
        
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'total' => count($orders),          // Frontend expects 'total'
            'totalAmount' => $totalAmount,      // Frontend expects 'totalAmount'
            'nextPageToken' => null
        ]);
        
    } catch (Exception $e) {
        error_log("Get Affiliate Orders Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Get Affiliate Stats
 */
function getAffiliateStats($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        // Support both GET and POST
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $code = $_GET['code'] ?? '';
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
            $code = $input['code'] ?? '';
        }
        
        if (empty($code)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Affiliate code is required']);
            return;
        }
        
        // 🔍 DEBUG: Log stats query start
        error_log("AFFILIATE STATS: Querying stats for code=$code");
        
        // Get affiliate profile (using 'code' field, not 'affiliateCode')
        $affiliatesQuery = $firestore->collection('affiliates')
            ->where('code', '=', $code)
            ->limit(1)
            ->documents();
        
        $affiliate = null;
        foreach ($affiliatesQuery as $doc) {
            if ($doc->exists()) {
                $affiliate = $doc->data();
                error_log("AFFILIATE STATS: ✅ Found affiliate profile for code=$code");
                break;
            }
        }
        
        if (!$affiliate) {
            error_log("AFFILIATE STATS: ❌ Affiliate not found for code=$code");
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Affiliate not found']);
            return;
        }
        
        // Get order stats - Query all confirmed orders and filter by coupon code
        error_log("AFFILIATE STATS: Querying all confirmed orders to filter by coupons array...");
        
        $ordersQuery = $firestore->collection('orders')
            ->where('status', '=', 'confirmed')
            ->documents();
        
        $totalReferrals = 0;
        $totalEarnings = 0;
        $monthlyEarnings = 0;
        $thisMonth = date('Y-m');
        $processedOrders = 0;
        $matchedOrders = [];
        
        foreach ($ordersQuery as $doc) {
            if ($doc->exists()) {
                $processedOrders++;
                $order = $doc->data();
                $coupons = $order['coupons'] ?? [];
                
                // Check if this order used the affiliate's coupon
                $hasAffiliateCoupon = false;
                foreach ($coupons as $coupon) {
                    if (isset($coupon['code']) && $coupon['code'] === $code) {
                        $hasAffiliateCoupon = true;
                        $matchedOrders[] = $order['orderId'] ?? $doc->id();
                        break;
                    }
                }
                
                if ($hasAffiliateCoupon) {
                    $totalReferrals++;
                    $totalEarnings += 300; // Fixed ₹300 commission per order
                    
                    // Calculate monthly earnings
                    $createdAt = $order['createdAt'] ?? null;
                    if ($createdAt && method_exists($createdAt, 'get')) {
                        $orderMonth = $createdAt->get()->format('Y-m');
                        if ($orderMonth === $thisMonth) {
                            $monthlyEarnings += 300;
                        }
                    }
                }
            }
        }
        
        // 🔍 DEBUG: Log results
        error_log("AFFILIATE STATS: Query complete - Processed $processedOrders total orders, matched $totalReferrals orders");
        error_log("AFFILIATE STATS: Matched order IDs: " . implode(', ', $matchedOrders));
        error_log("AFFILIATE STATS: Results - Earnings=₹$totalEarnings, Referrals=$totalReferrals, Monthly=₹$monthlyEarnings");
        
        // Calculate conversion rate (simplified - would need clicks tracking for actual rate)
        $conversionRate = $totalReferrals > 0 ? min(100, ($totalReferrals * 10)) : 0;
        
        echo json_encode([
            'success' => true,
            'totalEarnings' => $totalEarnings,
            'totalReferrals' => $totalReferrals,
            'monthlyEarnings' => $monthlyEarnings,
            'conversionRate' => $conversionRate,
            'affiliateCode' => $code,
            'status' => $affiliate['status'] ?? 'active'
        ]);
        
    } catch (Exception $e) {
        error_log("Get Affiliate Stats Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Get Payment Details
 */
function getPaymentDetails($firestore) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = $input['orderId'] ?? $_GET['orderId'] ?? '';
        
        if (empty($orderId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'orderId is required']);
            return;
        }
        
        $orderDoc = $firestore->collection('orders')->document($orderId)->snapshot();
        
        if (!$orderDoc->exists()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            return;
        }
        
        $order = $orderDoc->data();
        
        echo json_encode([
            'success' => true,
            'payment' => [
                'orderId' => $orderId,
                'razorpayOrderId' => $order['razorpayOrderId'] ?? '',
                'razorpayPaymentId' => $order['razorpayPaymentId'] ?? '',
                'amount' => $order['amount'] ?? $order['pricing']['total'] ?? 0,
                'currency' => $order['currency'] ?? 'INR',
                'status' => $order['status'] ?? 'pending',
                'paymentMethod' => $order['payment']['method'] ?? 'unknown',
                'createdAt' => $order['createdAt'] ?? null
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Get Payment Details Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Update Payment Details
 */
function updatePaymentDetails($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = $input['orderId'] ?? '';
        $paymentId = $input['paymentId'] ?? '';
        $status = $input['status'] ?? '';
        
        if (empty($orderId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'orderId is required']);
            return;
        }
        
        $updateData = [
            'updatedAt' => new \DateTime()
        ];
        
        if (!empty($paymentId)) {
            $updateData['razorpayPaymentId'] = $paymentId;
        }
        if (!empty($status)) {
            $updateData['paymentStatus'] = $status;
        }
        
        $firestore->collection('orders')->document($orderId)->update($updateData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment details updated'
        ]);
        
    } catch (Exception $e) {
        error_log("Update Payment Details Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Get Payout Settings
 */
function getPayoutSettings($firestore) {
    try {
        $affiliateId = $_GET['affiliateId'] ?? '';
        
        if (empty($affiliateId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'affiliateId is required']);
            return;
        }
        
        $affiliateDoc = $firestore->collection('affiliates')->document($affiliateId)->snapshot();
        
        if (!$affiliateDoc->exists()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Affiliate not found']);
            return;
        }
        
        $affiliate = $affiliateDoc->data();
        
        $defaultSettings = [
            'method' => 'bank_transfer',
            'minimumPayout' => 1000,
            'currency' => 'INR'
        ];
        
        echo json_encode([
            'success' => true,
            'payoutSettings' => $affiliate['payoutSettings'] ?? $defaultSettings
        ]);
        
    } catch (Exception $e) {
        error_log("Get Payout Settings Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Update Payout Settings
 */
function updatePayoutSettings($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $affiliateId = $input['affiliateId'] ?? '';
        $payoutSettings = $input['payoutSettings'] ?? null;
        
        if (empty($affiliateId) || !$payoutSettings) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'affiliateId and payoutSettings are required']);
            return;
        }
        
        $firestore->collection('affiliates')->document($affiliateId)->update([
            'payoutSettings' => $payoutSettings,
            'updatedAt' => new \DateTime()
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Payout settings updated'
        ]);
        
    } catch (Exception $e) {
        error_log("Update Payout Settings Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Helper: Generate Affiliate Code
 */
function generateAffiliateCode() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = 'AFF-';
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * Helper: Initialize Firestore Admin
 */
function initFirestoreAdmin() {
    $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
    
    if (!file_exists($serviceAccountPath)) {
        throw new Exception('Firebase service account file not found');
    }
    
    if (!class_exists('\Kreait\Firebase\Factory')) {
        throw new Exception('Firebase SDK not installed. Run: composer require kreait/firebase-php');
    }
    
    $factory = new \Kreait\Firebase\Factory();
    $factory = $factory->withServiceAccount($serviceAccountPath);
    
    // Correct: createFirestore() returns the Firestore database directly
    return $factory->createFirestore()->database();
}
?>

