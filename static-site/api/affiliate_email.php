<?php
/**
 * 🎯 Affiliate Email API
 * Handles affiliate-related email sending
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Include required files
require_once __DIR__ . '/affiliate_email_sender.php';

class AffiliateEmailHandler {
    
    public function handleRequest() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['action'])) {
                http_response_code(400);
                return [
                    'success' => false,
                    'error' => 'Action is required'
                ];
            }
            
            $action = $input['action'];
            
            // Forward to the new affiliate email sender
            $input['action'] = $action;
            
            // Make internal request to affiliate_email_sender.php
            $result = $this->forwardToSender($input);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("AFFILIATE EMAIL ERROR: " . $e->getMessage());
            http_response_code(500);
            return [
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage()
            ];
        }
    }
    
            private function forwardToSender($input) {
                // Since we're including the affiliate_email_sender.php, we can call its functions directly
                switch ($input['action']) {
                    case 'welcome':
                        return sendAffiliateWelcomeEmail(null, $input);
                        
                    case 'commission':
                        return sendAffiliateCommissionEmail(null, $input);
                        
                    case 'payout':
                        return sendAffiliatePayoutEmail(null, $input);
                        
                    case 'milestone':
                        return sendAffiliateMilestoneEmail(null, $input);
                        
                    case 'lookup_affiliate':
                        return $this->handleAffiliateLookup($input);
                        
                    case 'simulate_order':
                        return $this->handleOrderSimulation($input);
                        
                    default:
                        http_response_code(400);
                        return [
                            'success' => false,
                            'error' => 'Unknown action: ' . $input['action']
                        ];
                }
            }
            
            private function handleAffiliateLookup($input) {
                if (!isset($input['affiliateCode'])) {
                    return [
                        'success' => false,
                        'error' => 'affiliateCode is required'
                    ];
                }
                
                $affiliateCode = $input['affiliateCode'];
                
                // Include order_manager to get the lookup function
                require_once __DIR__ . '/order_manager.php';
                
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
            
            private function handleOrderSimulation($input) {
                if (!isset($input['orderData'])) {
                    return [
                        'success' => false,
                        'error' => 'orderData is required'
                    ];
                }
                
                $orderData = $input['orderData'];
                
                // Validate required fields
                $required = ['orderId', 'affiliateCode', 'orderTotal', 'customerEmail'];
                foreach ($required as $field) {
                    if (!isset($orderData[$field])) {
                        return [
                            'success' => false,
                            'error' => "Missing required field: {$field}"
                        ];
                    }
                }
                
                // Include order_manager to get the lookup function
                require_once __DIR__ . '/order_manager.php';
                
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
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handler = new AffiliateEmailHandler();
    $result = $handler->handleRequest();
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}
?>
