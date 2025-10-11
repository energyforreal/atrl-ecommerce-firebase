<?php
/**
 * Affiliate Functions API - REST API Version for Hostinger Shared Hosting
 * Uses Firestore REST API instead of Firebase SDK
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

// Include Firestore REST client
if (!file_exists(__DIR__ . '/firestore_rest_client.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Firestore REST client not found.'
    ]);
    exit;
}
require_once __DIR__ . '/firestore_rest_client.php';

// Route handling
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Extract function name from URL path
$action = $_GET['action'] ?? '';
if (empty($action) && preg_match('/affiliate_functions\.php\/(\w+)/', $requestUri, $matches)) {
    $action = $matches[1];
}

// Initialize Firestore REST client
try {
    $firestore = new FirestoreRestClient(
        'e-commerce-1d40f',
        __DIR__ . '/firebase-service-account.json',
        true // Enable token caching
    );
} catch (Exception $e) {
    error_log("Affiliate Functions: Firestore init failed - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Debug logging
error_log("AFFILIATE API: Action requested: $action");
error_log("AFFILIATE API: Request method: " . $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("AFFILIATE API: POST data: " . json_encode($input));
}

// Route to appropriate function
switch ($action) {
    case 'createAffiliateProfile':
        error_log("AFFILIATE API: Creating affiliate profile");
        createAffiliateProfile($firestore);
        break;
    case 'getAffiliateOrders':
        error_log("AFFILIATE API: Getting affiliate orders");
        getAffiliateOrders($firestore);
        break;
    case 'getAffiliateStats':
        error_log("AFFILIATE API: Getting affiliate stats");
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
    case 'getAffiliateByCode':
        getAffiliateByCode($firestore);
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
            'code' => $affiliateCode,
            'status' => 'pending',
            'commissionRate' => 0.10,
            'totalEarnings' => 0,
            'totalOrders' => 0,
            'createdAt' => ['_seconds' => time(), '_nanoseconds' => 0],
            'updatedAt' => ['_seconds' => time(), '_nanoseconds' => 0]
        ];
        
        // Add to Firestore using UID as document ID
        $firestore->setDocument("affiliates/$uid", $affiliateData);
        
        echo json_encode([
            'success' => true,
            'affiliateId' => $uid,
            'code' => $affiliateCode
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
        // Get parameters
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
            error_log("AFFILIATE ORDERS: ❌ No affiliate code provided");
            echo json_encode([
                'success' => true,
                'orders' => [],
                'total' => 0,
                'totalAmount' => 0,
                'nextPageToken' => null
            ]);
            return;
        }
        
        error_log("AFFILIATE ORDERS: Querying orders for code=$code (case-insensitive matching enabled)");
        
        // Query confirmed orders
        $documents = $firestore->queryDocuments('orders', [
            ['field' => 'status', 'op' => 'EQUAL', 'value' => 'confirmed']
        ], $pageSize, 'createdAt', 'DESCENDING');
        
        $orders = [];
        $totalAmount = 0;
        $processedCount = 0;
        $matchedOrderIds = [];
        
        foreach ($documents as $doc) {
            $processedCount++;
            $orderData = $doc['data'];
            $coupons = $orderData['coupons'] ?? [];
            
            // Debug: Log all coupons in this order
            if (!empty($coupons)) {
                error_log("AFFILIATE ORDERS: Order " . ($orderData['orderId'] ?? $doc['id']) . " has " . count($coupons) . " coupons: " . json_encode($coupons));
            }
            
            // Check if order uses affiliate coupon (case-insensitive)
            $hasAffiliateCoupon = false;
            $searchCode = strtolower($code);
            
            foreach ($coupons as $coupon) {
                // Check both affiliateCode and code fields for matching (case-insensitive)
                $couponAffiliateCode = isset($coupon['affiliateCode']) ? strtolower($coupon['affiliateCode']) : '';
                $couponCode = isset($coupon['code']) ? strtolower($coupon['code']) : '';
                
                error_log("AFFILIATE ORDERS: Comparing '$searchCode' with affiliateCode='$couponAffiliateCode', code='$couponCode'");
                
                if ($couponAffiliateCode === $searchCode || $couponCode === $searchCode) {
                    $hasAffiliateCoupon = true;
                    $matchedOrderIds[] = $orderData['orderId'] ?? $doc['id'];
                    error_log("AFFILIATE ORDERS: ✅ MATCH FOUND in order " . ($orderData['orderId'] ?? $doc['id']) . " (case-insensitive match): " . json_encode($coupon));
                    break;
                }
            }
            
            if ($hasAffiliateCoupon) {
                // Extract customer info from the correct structure
                $customer = $orderData['customer'] ?? [];
                $orderData['id'] = $doc['id'];
                $orderData['customerName'] = trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? ''));
                $orderData['customerEmail'] = $customer['email'] ?? '';
                $orderData['customerPhone'] = $customer['phone'] ?? '';
                
                // Debug log the extracted customer data
                error_log("AFFILIATE ORDERS: Customer data for order " . ($orderData['orderId'] ?? $doc['id']) . ": " . json_encode([
                    'customerName' => $orderData['customerName'],
                    'customerEmail' => $orderData['customerEmail'],
                    'customerPhone' => $orderData['customerPhone']
                ]));
                
                $orders[] = $orderData;
                $totalAmount += ($orderData['amount'] ?? 0);
            }
        }
        
        error_log("AFFILIATE ORDERS: Found " . count($orders) . " matching orders from $processedCount total");
        
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'total' => count($orders),
            'totalAmount' => $totalAmount,
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
        // Get code parameter
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
        
        error_log("AFFILIATE STATS: Querying stats for code=$code");
        
        // Query coupons collection directly for usage data
        // Search by affiliateCode field (not code field)
        $coupons = $firestore->queryDocuments('coupons', [
            ['field' => 'affiliateCode', 'op' => 'EQUAL', 'value' => $code]
        ], 1);
        
        $couponUsageCount = 0;
        $couponPayoutUsage = 0;
        
        if (!empty($coupons)) {
            $couponData = $coupons[0]['data'];
            $couponUsageCount = $couponData['usageCount'] ?? 0;
            $couponPayoutUsage = $couponUsageCount * 300; // Calculate from usageCount
            error_log("AFFILIATE STATS: Coupon usage - count=$couponUsageCount, calculated payout=₹$couponPayoutUsage");
        } else {
            error_log("AFFILIATE STATS: ❌ Coupon not found for code=$code");
            // Return empty stats if coupon not found
            echo json_encode([
                'success' => true,
                'totalEarnings' => 0,
                'totalReferrals' => 0,
                'monthlyEarnings' => 0,
                'conversionRate' => 0,
                'couponUsageCount' => 0,
                'couponPayoutUsage' => 0,
                'affiliateCode' => $code,
                'status' => 'not_found'
            ]);
            return;
        }
        
        error_log("AFFILIATE STATS: ✅ Found coupon data");
        
        // Calculate stats from coupon usage data
        $totalReferrals = $couponUsageCount;
        $totalEarnings = $couponUsageCount * 300; // ₹300 per usage
        
        // Query orders collection to calculate monthly earnings
        $orders = $firestore->queryDocuments('orders', [
            ['field' => 'status', 'op' => 'EQUAL', 'value' => 'confirmed']
        ], 1000);
        
        $monthlyEarnings = 0;
        $thisMonth = date('Y-m');
        
        foreach ($orders as $doc) {
            $orderData = $doc['data'];
            $coupons = $orderData['coupons'] ?? [];
            
            // Check if order uses affiliate coupon and was created this month (case-insensitive)
            $searchCode = strtolower($code);
            foreach ($coupons as $coupon) {
                // Check both affiliateCode and code fields for matching (case-insensitive)
                $couponAffiliateCode = isset($coupon['affiliateCode']) ? strtolower($coupon['affiliateCode']) : '';
                $couponCode = isset($coupon['code']) ? strtolower($coupon['code']) : '';
                
                if ($couponAffiliateCode === $searchCode || $couponCode === $searchCode) {
                    // Check if this month
                    $createdAt = $orderData['createdAt'] ?? null;
                    if ($createdAt && isset($createdAt['_seconds'])) {
                        $orderMonth = date('Y-m', $createdAt['_seconds']);
                        if ($orderMonth === $thisMonth) {
                            $monthlyEarnings += 300;
                        }
                    }
                    break;
                }
            }
        }
        
        $conversionRate = $totalReferrals > 0 ? min(100, ($totalReferrals * 10)) : 0;
        
        error_log("AFFILIATE STATS: Results - Earnings=₹$totalEarnings, Referrals=$totalReferrals");
        
        echo json_encode([
            'success' => true,
            'totalEarnings' => $totalEarnings,
            'totalReferrals' => $totalReferrals,
            'monthlyEarnings' => $monthlyEarnings,
            'conversionRate' => $conversionRate,
            'couponUsageCount' => $couponUsageCount,
            'couponPayoutUsage' => $couponPayoutUsage,
            'affiliateCode' => $code,
            'status' => 'active'
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
        $uid = $input['uid'] ?? $_GET['uid'] ?? '';
        
        if (empty($uid)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'uid is required']);
            return;
        }
        
        $doc = $firestore->getDocument("affiliates/$uid");
        
        if (!$doc) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Affiliate not found']);
            return;
        }
        
        $paymentDetails = $doc['data']['paymentDetails'] ?? [];
        
        echo json_encode([
            'success' => true,
            'bankAccountName' => $paymentDetails['bankAccountName'] ?? '',
            'bankAccountNumber' => $paymentDetails['bankAccountNumber'] ?? '',
            'ifsc' => $paymentDetails['ifsc'] ?? '',
            'upiId' => $paymentDetails['upiId'] ?? '',
            'upiMobile' => $paymentDetails['upiMobile'] ?? ''
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
        $uid = $input['uid'] ?? '';
        
        if (empty($uid)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'uid is required']);
            return;
        }
        
        $paymentDetails = [
            'bankAccountName' => $input['bankAccountName'] ?? '',
            'bankAccountNumber' => $input['bankAccountNumber'] ?? '',
            'ifsc' => $input['ifsc'] ?? '',
            'upiId' => $input['upiId'] ?? '',
            'upiMobile' => $input['upiMobile'] ?? ''
        ];
        
        $firestore->updateDocument("affiliates/$uid", [
            'paymentDetails' => $paymentDetails,
            'updatedAt' => ['_seconds' => time(), '_nanoseconds' => 0]
        ]);
        
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
        
        $doc = $firestore->getDocument("affiliates/$affiliateId");
        
        if (!$doc) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Affiliate not found']);
            return;
        }
        
        $defaultSettings = [
            'method' => 'bank_transfer',
            'minimumPayout' => 1000,
            'currency' => 'INR'
        ];
        
        echo json_encode([
            'success' => true,
            'payoutSettings' => $doc['data']['payoutSettings'] ?? $defaultSettings
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
        
        $firestore->updateDocument("affiliates/$affiliateId", [
            'payoutSettings' => $payoutSettings,
            'updatedAt' => ['_seconds' => time(), '_nanoseconds' => 0]
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
 * Get Affiliate by Code
 */
function getAffiliateByCode($firestore) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? $_GET['code'] ?? '';
        
        if (empty($code)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Code is required']);
            return;
        }
        
        error_log("AFFILIATE LOOKUP: Looking for affiliate with code=$code");
        
        $affiliates = $firestore->queryDocuments('affiliates', [
            ['field' => 'code', 'op' => 'EQUAL', 'value' => $code]
        ], 1);
        
        if (empty($affiliates)) {
            error_log("AFFILIATE LOOKUP: ❌ Affiliate not found for code=$code");
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Affiliate not found']);
            return;
        }
        
        $affiliate = $affiliates[0]['data'];
        $affiliate['id'] = $affiliates[0]['id'];
        
        error_log("AFFILIATE LOOKUP: ✅ Found affiliate profile for code=$code");
        
        echo json_encode([
            'success' => true,
            'affiliate' => $affiliate
        ]);
        
    } catch (Exception $e) {
        error_log("Get Affiliate by Code Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Helper: Generate Affiliate Code
 */
function generateAffiliateCode() {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $code = 'attral-';
    for ($i = 0; $i < 10; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}
?>
