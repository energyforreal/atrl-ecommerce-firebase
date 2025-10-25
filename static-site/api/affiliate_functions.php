<?php
/**
 * Affiliate Functions API - REST API Version for Hostinger Shared Hosting
 * Uses Firestore REST API instead of Firebase SDK
 */

// Start output buffering to prevent any output before JSON
ob_start();

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
    ob_clean();
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
    ob_clean();
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
    case 'testPaymentDetails':
        testPaymentDetails($firestore);
        break;
    default:
        http_response_code(404);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Function not found', 'action' => $action]);
        break;
}

/**
 * Create Affiliate Profile
 */
function createAffiliateProfile($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        ob_clean();
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
            error_log("AFFILIATE API: Missing required fields - uid: '$uid', email: '$email'");
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'uid and email are required']);
            return;
        }
        
        // Generate affiliate code using user's name and ID
        $affiliateCode = generateAffiliateCode($name, $uid);
        
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
        
        // Test Firestore connection first
        try {
            $testToken = $firestore->getAccessToken();
        } catch (Exception $e) {
            error_log("AFFILIATE API: Firestore connection failed: " . $e->getMessage());
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Firestore connection failed: ' . $e->getMessage()]);
            return;
        }
        
        // Write document to Firestore using correct method
        error_log("AFFILIATE API: Writing affiliate document to Firestore with UID: $uid");
        $result = $firestore->writeDocument("affiliates", $affiliateData, $uid);
        error_log("AFFILIATE API: Document write result: " . json_encode($result));
        
        // Verify document was created
        try {
            error_log("AFFILIATE API: Verifying document creation...");
            $verification = $firestore->getDocument("affiliates", $uid);
            if (!$verification) {
                error_log("AFFILIATE API: Document verification failed - no document found");
            } else if ($verification['data']['code'] !== $affiliateCode) {
                error_log("AFFILIATE API: Document verification failed - code mismatch. Expected: $affiliateCode, Got: " . ($verification['data']['code'] ?? 'null'));
            } else {
                error_log("AFFILIATE API: Document verification successful - affiliate profile created with code: $affiliateCode");
            }
        } catch (Exception $e) {
            error_log("AFFILIATE API: Document verification error: " . $e->getMessage());
            // Don't fail the whole process for verification issues
        }
        
        $response = [
            'success' => true,
            'affiliateId' => $uid,
            'code' => $affiliateCode
        ];
        
        ob_clean();
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log("AFFILIATE API: Create Affiliate Profile Error: " . $e->getMessage());
        error_log("AFFILIATE API: Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        ob_clean();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Get Affiliate Orders
 */
function getAffiliateOrders($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        ob_clean();
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
            ob_clean();
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
        
        ob_clean();
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
        ob_clean();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Get Affiliate Stats
 */
function getAffiliateStats($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        ob_clean();
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
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Affiliate code is required']);
            return;
        }
        
        error_log("AFFILIATE STATS: Querying stats for code=$code");
        
        // First, check if the affiliate profile exists in the affiliates collection
        error_log("AFFILIATE STATS: Checking if affiliate profile exists...");
        $affiliates = $firestore->queryDocuments('affiliates', [
            ['field' => 'code', 'op' => 'EQUAL', 'value' => $code]
        ], 1);
        
        if (empty($affiliates)) {
            error_log("AFFILIATE STATS: ❌ Affiliate profile not found for code=$code");
            // Return empty stats if affiliate profile not found
            ob_clean();
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
        
        error_log("AFFILIATE STATS: ✅ Affiliate profile found for code=$code");
        
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
            error_log("AFFILIATE STATS: ❌ Coupon not found for code=$code, but affiliate profile exists");
            // Return empty stats if coupon not found but affiliate exists
            ob_clean();
            echo json_encode([
                'success' => true,
                'totalEarnings' => 0,
                'totalReferrals' => 0,
                'monthlyEarnings' => 0,
                'conversionRate' => 0,
                'couponUsageCount' => 0,
                'couponPayoutUsage' => 0,
                'affiliateCode' => $code,
                'status' => 'active'
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
        
        ob_clean();
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
        ob_clean();
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
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'uid is required']);
            return;
        }
        
        // Try to get the affiliate document
        $doc = $firestore->getDocument("affiliates", $uid);
        
        if (!$doc) {
            // Return empty payment details if affiliate doesn't exist yet
            ob_clean();
            echo json_encode([
                'success' => true,
                'bankAccountName' => '',
                'bankAccountNumber' => '',
                'ifsc' => '',
                'upiId' => '',
                'upiMobile' => ''
            ]);
            return;
        }
        
        $paymentDetails = $doc['data']['paymentDetails'] ?? [];
        
        ob_clean();
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
        ob_clean();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Update Payment Details
 */
function updatePaymentDetails($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        error_log("AFFILIATE API: updatePaymentDetails called with input: " . json_encode($input));
        
        $uid = $input['uid'] ?? '';
        
        if (empty($uid)) {
            error_log("AFFILIATE API: updatePaymentDetails failed - missing uid");
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'uid is required']);
            return;
        }
        
        // Validate required payment fields
        $requiredFields = ['bankAccountName', 'bankAccountNumber', 'ifsc', 'upiId', 'upiMobile'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                error_log("AFFILIATE API: updatePaymentDetails failed - missing field: $field");
                http_response_code(400);
                ob_clean();
                echo json_encode(['success' => false, 'error' => "Field $field is required"]);
                return;
            }
        }
        
        // Validate IFSC format
        $ifsc = strtoupper(trim($input['ifsc']));
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
            error_log("AFFILIATE API: updatePaymentDetails failed - invalid IFSC format: $ifsc");
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Invalid IFSC code format. Please enter a valid IFSC code (e.g., SBIN0001234)']);
            return;
        }
        
        // Validate mobile number
        $upiMobile = preg_replace('/[^0-9]/', '', $input['upiMobile']);
        if (strlen($upiMobile) < 10) {
            error_log("AFFILIATE API: updatePaymentDetails failed - invalid mobile number: $upiMobile");
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Please enter a valid 10-digit mobile number']);
            return;
        }
        
        error_log("AFFILIATE API: updatePaymentDetails processing for uid: $uid");
        
        $paymentDetails = [
            'bankAccountName' => trim($input['bankAccountName']),
            'bankAccountNumber' => preg_replace('/[^0-9]/', '', $input['bankAccountNumber']),
            'ifsc' => $ifsc,
            'upiId' => trim($input['upiId']),
            'upiMobile' => $upiMobile
        ];
        
        // Check if affiliate document exists
        error_log("AFFILIATE API: Checking if affiliate document exists for uid: $uid");
        $doc = $firestore->getDocument("affiliates", $uid);
        
        if (!$doc) {
            error_log("AFFILIATE API: Affiliate document not found, creating new one with payment details");
            
            // Generate affiliate code if not provided
            $affiliateCode = generateAffiliateCode($input['name'] ?? '', $uid);
            error_log("AFFILIATE API: Generated affiliate code: $affiliateCode");
            
            // Create affiliate document with payment details
            $affiliateData = [
                'uid' => $uid,
                'email' => $input['email'] ?? '',
                'name' => $input['name'] ?? '',
                'phone' => $input['phone'] ?? '',
                'code' => $affiliateCode,
                'status' => 'pending',
                'commissionRate' => 0.10,
                'totalEarnings' => 0,
                'totalOrders' => 0,
                'paymentDetails' => $paymentDetails,
                'createdAt' => ['_seconds' => time(), '_nanoseconds' => 0],
                'updatedAt' => ['_seconds' => time(), '_nanoseconds' => 0]
            ];
            
            error_log("AFFILIATE API: Writing new affiliate document with payment details: " . json_encode($affiliateData));
            
            try {
                $result = $firestore->writeDocument("affiliates", $affiliateData, $uid);
                error_log("AFFILIATE API: New affiliate document created successfully: " . json_encode($result));
                
                // Verify the document was created
                $verification = $firestore->getDocument("affiliates", $uid);
                if ($verification) {
                    error_log("AFFILIATE API: Document verification successful - affiliate profile created with payment details");
                } else {
                    error_log("AFFILIATE API: Document verification failed - document not found after creation");
                }
            } catch (Exception $e) {
                error_log("AFFILIATE API: Error creating affiliate document: " . $e->getMessage());
                throw $e;
            }
        } else {
            error_log("AFFILIATE API: Affiliate document found, updating payment details");
            
            // Update existing document with payment details
            try {
                $result = $firestore->updateDocument("affiliates", $uid, [
                    ['path' => 'paymentDetails', 'value' => $paymentDetails],
                    ['path' => 'updatedAt', 'value' => ['_seconds' => time(), '_nanoseconds' => 0]]
                ]);
                error_log("AFFILIATE API: Payment details updated successfully: " . json_encode($result));
                
                // Verify the update was successful
                $verification = $firestore->getDocument("affiliates", $uid);
                if ($verification && isset($verification['data']['paymentDetails'])) {
                    error_log("AFFILIATE API: Payment details verification successful");
                } else {
                    error_log("AFFILIATE API: Payment details verification failed");
                }
            } catch (Exception $e) {
                error_log("AFFILIATE API: Error updating payment details: " . $e->getMessage());
                throw $e;
            }
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Payment details saved successfully',
            'data' => [
                'uid' => $uid,
                'paymentDetails' => $paymentDetails
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Update Payment Details Error: " . $e->getMessage());
        error_log("Update Payment Details Error Stack: " . $e->getTraceAsString());
        error_log("Update Payment Details Error File: " . $e->getFile());
        error_log("Update Payment Details Error Line: " . $e->getLine());
        http_response_code(500);
        ob_clean();
        
        // Provide user-friendly error messages
        $errorMessage = 'An error occurred while saving payment details.';
        if (strpos($e->getMessage(), 'Service account') !== false) {
            $errorMessage = 'Database connection failed. Please contact support.';
        } elseif (strpos($e->getMessage(), 'network') !== false || strpos($e->getMessage(), 'timeout') !== false) {
            $errorMessage = 'Network error. Please check your connection and try again.';
        } elseif (strpos($e->getMessage(), 'permission') !== false) {
            $errorMessage = 'Access denied. Please sign in again.';
        }
        
        echo json_encode([
            'success' => false, 
            'error' => $errorMessage
        ]);
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
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'affiliateId is required']);
            return;
        }
        
        $doc = $firestore->getDocument("affiliates", $affiliateId);
        
        if (!$doc) {
            // Return default settings if affiliate doesn't exist yet
            $defaultSettings = [
                'method' => 'bank_transfer',
                'minimumPayout' => 1000,
                'currency' => 'INR'
            ];
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'payoutSettings' => $defaultSettings
            ]);
            return;
        }
        
        $defaultSettings = [
            'method' => 'bank_transfer',
            'minimumPayout' => 1000,
            'currency' => 'INR'
        ];
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'payoutSettings' => $doc['data']['payoutSettings'] ?? $defaultSettings
        ]);
        
    } catch (Exception $e) {
        error_log("Get Payout Settings Error: " . $e->getMessage());
        http_response_code(500);
        ob_clean();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Update Payout Settings
 */
function updatePayoutSettings($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $affiliateId = $input['affiliateId'] ?? '';
        $payoutSettings = $input['payoutSettings'] ?? null;
        
        if (empty($affiliateId) || !$payoutSettings) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'affiliateId and payoutSettings are required']);
            return;
        }
        
        // Check if affiliate document exists
        $doc = $firestore->getDocument("affiliates", $affiliateId);
        
        if (!$doc) {
            // Create affiliate document if it doesn't exist
            $affiliateData = [
                'uid' => $affiliateId,
                'email' => $input['email'] ?? '',
                'name' => $input['name'] ?? '',
                'phone' => $input['phone'] ?? '',
                'code' => generateAffiliateCode($input['name'] ?? '', $affiliateId),
                'status' => 'pending',
                'commissionRate' => 0.10,
                'totalEarnings' => 0,
                'totalOrders' => 0,
                'payoutSettings' => $payoutSettings,
                'createdAt' => ['_seconds' => time(), '_nanoseconds' => 0],
                'updatedAt' => ['_seconds' => time(), '_nanoseconds' => 0]
            ];
            
            $firestore->writeDocument("affiliates", $affiliateData, $affiliateId);
        } else {
            // Update existing document
            $firestore->updateDocument("affiliates", $affiliateId, [
                'payoutSettings' => $payoutSettings,
                'updatedAt' => ['_seconds' => time(), '_nanoseconds' => 0]
            ]);
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Payout settings updated'
        ]);
        
    } catch (Exception $e) {
        error_log("Update Payout Settings Error: " . $e->getMessage());
        http_response_code(500);
        ob_clean();
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
            ob_clean();
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
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Affiliate not found']);
            return;
        }
        
        $affiliate = $affiliates[0]['data'];
        $affiliate['id'] = $affiliates[0]['id'];
        
        error_log("AFFILIATE LOOKUP: ✅ Found affiliate profile for code=$code");
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'affiliate' => $affiliate
        ]);
        
    } catch (Exception $e) {
        error_log("Get Affiliate by Code Error: " . $e->getMessage());
        http_response_code(500);
        ob_clean();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Helper: Generate Affiliate Code (User's Name + User ID Hash)
 */
function generateAffiliateCode($userName = '', $userId = '') {
    // Clean and format the user's name (remove special characters, convert to lowercase)
    $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $userName));
    $namePart = substr($cleanName, 0, 4); // First 4 characters of name
    
    // Create hash from user ID for uniqueness
    $hash = substr(md5($userId), 0, 6); // First 6 characters of MD5 hash
    
    return $namePart . $hash;
}

/**
 * Helper: Save Payment Details to Firestore
 */
function savePaymentDetailsToFirestore($firestore, $uid, $paymentDetails, $userData = []) {
    try {
        error_log("AFFILIATE API: Saving payment details to Firestore for uid: $uid");
        
        // Check if affiliate document exists
        $doc = $firestore->getDocument("affiliates", $uid);
        
        if (!$doc) {
            error_log("AFFILIATE API: Creating new affiliate document with payment details");
            
            // Create new affiliate document with payment details
            $affiliateData = [
                'uid' => $uid,
                'email' => $userData['email'] ?? '',
                'name' => $userData['name'] ?? '',
                'phone' => $userData['phone'] ?? '',
                'code' => generateAffiliateCode($userData['name'] ?? '', $uid),
                'status' => 'pending',
                'commissionRate' => 0.10,
                'totalEarnings' => 0,
                'totalOrders' => 0,
                'paymentDetails' => $paymentDetails,
                'createdAt' => ['_seconds' => time(), '_nanoseconds' => 0],
                'updatedAt' => ['_seconds' => time(), '_nanoseconds' => 0]
            ];
            
            $result = $firestore->writeDocument("affiliates", $affiliateData, $uid);
            error_log("AFFILIATE API: New affiliate document created with payment details: " . json_encode($result));
            
        } else {
            error_log("AFFILIATE API: Updating existing affiliate document with payment details");
            
            // Update existing document with payment details
            $result = $firestore->updateDocument("affiliates", $uid, [
                'paymentDetails' => $paymentDetails,
                'updatedAt' => ['_seconds' => time(), '_nanoseconds' => 0]
            ]);
            error_log("AFFILIATE API: Payment details updated in existing document: " . json_encode($result));
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("AFFILIATE API: Error saving payment details to Firestore: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Test Payment Details Function
 */
function testPaymentDetails($firestore) {
    try {
        error_log("TEST: Starting payment details test");
        
        // Test data
        $testData = [
            'uid' => 'test-user-123',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '+1234567890',
            'bankAccountName' => 'Test Account',
            'bankAccountNumber' => '1234567890',
            'ifsc' => 'TEST0123456',
            'upiId' => 'test@bank',
            'upiMobile' => '9876543210'
        ];
        
        error_log("TEST: Test data: " . json_encode($testData));
        
        // Test Firestore connection
        $token = $firestore->getAccessToken();
        error_log("TEST: Firestore token obtained successfully");
        
        // Test document creation
        $paymentDetails = [
            'bankAccountName' => $testData['bankAccountName'],
            'bankAccountNumber' => $testData['bankAccountNumber'],
            'ifsc' => $testData['ifsc'],
            'upiId' => $testData['upiId'],
            'upiMobile' => $testData['upiMobile']
        ];
        
        $affiliateData = [
            'uid' => $testData['uid'],
            'email' => $testData['email'],
            'name' => $testData['name'],
            'phone' => $testData['phone'],
            'code' => 'test123abc',
            'status' => 'pending',
            'commissionRate' => 0.10,
            'totalEarnings' => 0,
            'totalOrders' => 0,
            'paymentDetails' => $paymentDetails,
            'createdAt' => ['_seconds' => time(), '_nanoseconds' => 0],
            'updatedAt' => ['_seconds' => time(), '_nanoseconds' => 0]
        ];
        
        error_log("TEST: Writing test document to Firestore");
        $result = $firestore->writeDocument("affiliates", $affiliateData, $testData['uid']);
        error_log("TEST: Document write result: " . json_encode($result));
        
        // Verify document
        $verification = $firestore->getDocument("affiliates", $testData['uid']);
        if ($verification) {
            error_log("TEST: Document verification successful");
        } else {
            error_log("TEST: Document verification failed");
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Payment details test completed successfully',
            'data' => $testData,
            'result' => $result,
            'verification' => $verification ? 'success' : 'failed'
        ]);
        
    } catch (Exception $e) {
        error_log("TEST: Error in payment details test: " . $e->getMessage());
        error_log("TEST: Error stack: " . $e->getTraceAsString());
        
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'stack' => $e->getTraceAsString()
        ]);
    }
}
?>
