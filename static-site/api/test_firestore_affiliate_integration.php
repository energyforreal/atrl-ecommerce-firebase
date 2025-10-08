<?php
/**
 * 🧪 Firestore Affiliate Integration Test API
 * Test the complete flow: Order with affiliate code → Firestore lookup → Email sending
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

require_once __DIR__ . '/order_manager.php';
require_once __DIR__ . '/firestore_order_manager.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Action is required');
    }
    
    $action = $input['action'];
    
    switch ($action) {
        case 'lookup':
            $result = handleAffiliateLookup($input);
            break;
            
        case 'simulate_order':
            $result = handleOrderSimulation($input);
            break;
            
        case 'test_commission_email':
            $result = handleCommissionEmailTest($input);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("AFFILIATE TEST ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Handle affiliate lookup request
 */
function handleAffiliateLookup($input) {
    if (!isset($input['affiliateCode'])) {
        throw new Exception('affiliateCode is required');
    }
    
    $affiliateCode = $input['affiliateCode'];
    
    // Test affiliate lookup
    $affiliateInfo = getAffiliateByCode($affiliateCode);
    
    if ($affiliateInfo) {
        return [
            'success' => true,
            'message' => 'Affiliate found successfully',
            'affiliateInfo' => $affiliateInfo,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else {
        return [
            'success' => false,
            'error' => "Affiliate not found for code: {$affiliateCode}",
            'affiliateCode' => $affiliateCode,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * Handle order simulation request
 */
function handleOrderSimulation($input) {
    if (!isset($input['orderData'])) {
        throw new Exception('orderData is required');
    }
    
    $orderData = $input['orderData'];
    
    // Validate required fields
    $required = ['orderId', 'affiliateCode', 'orderTotal', 'customerEmail'];
    foreach ($required as $field) {
        if (!isset($orderData[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    // Step 1: Extract affiliate code
    $affiliateCode = $orderData['affiliateCode'];
    
    // Step 2: Look up affiliate information
    $affiliateInfo = getAffiliateByCode($affiliateCode);
    
    if (!$affiliateInfo) {
        return [
            'success' => false,
            'error' => "Affiliate not found for code: {$affiliateCode}",
            'step' => 'affiliate_lookup',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // Step 3: Calculate commission
    $orderTotal = floatval($orderData['orderTotal']);
    $commissionAmount = $orderTotal * 0.10; // 10% commission
    
    // Step 4: Simulate commission email sending
    require_once __DIR__ . '/affiliate_email_sender.php';
    
    try {
        $emailResult = sendAffiliateCommissionEmail(null, [
            'email' => $affiliateInfo['email'],
            'name' => $affiliateInfo['name'],
            'commission' => $commissionAmount,
            'orderId' => $orderData['orderId']
        ]);
        
        return [
            'success' => true,
            'message' => 'Order simulation completed successfully',
            'orderData' => $orderData,
            'affiliateInfo' => $affiliateInfo,
            'commission' => [
                'amount' => $commissionAmount,
                'percentage' => '10%',
                'calculated_from' => $orderTotal
            ],
            'emailSent' => $emailResult['success'],
            'emailResult' => $emailResult,
            'flow' => [
                'step1' => '✅ Affiliate code extracted',
                'step2' => '✅ Affiliate looked up in Firestore',
                'step3' => '✅ Commission calculated',
                'step4' => $emailResult['success'] ? '✅ Commission email sent' : '❌ Email sending failed'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Email sending failed: ' . $e->getMessage(),
            'orderData' => $orderData,
            'affiliateInfo' => $affiliateInfo,
            'commission' => [
                'amount' => $commissionAmount,
                'percentage' => '10%',
                'calculated_from' => $orderTotal
            ],
            'flow' => [
                'step1' => '✅ Affiliate code extracted',
                'step2' => '✅ Affiliate looked up in Firestore',
                'step3' => '✅ Commission calculated',
                'step4' => '❌ Email sending failed: ' . $e->getMessage()
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * Handle commission email test request
 */
function handleCommissionEmailTest($input) {
    if (!isset($input['affiliateInfo'])) {
        throw new Exception('affiliateInfo is required');
    }
    
    $affiliateInfo = $input['affiliateInfo'];
    $commissionAmount = $input['commission'] ?? 150.00;
    $orderId = $input['orderId'] ?? 'ATRL-TEST-001';
    
    require_once __DIR__ . '/affiliate_email_sender.php';
    
    try {
        $result = sendAffiliateCommissionEmail(null, [
            'email' => $affiliateInfo['email'],
            'name' => $affiliateInfo['name'],
            'commission' => $commissionAmount,
            'orderId' => $orderId
        ]);
        
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Commission email sent successfully' : 'Failed to send commission email',
            'affiliateInfo' => $affiliateInfo,
            'commission' => $commissionAmount,
            'orderId' => $orderId,
            'emailResult' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Commission email test failed: ' . $e->getMessage(),
            'affiliateInfo' => $affiliateInfo,
            'commission' => $commissionAmount,
            'orderId' => $orderId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
?>