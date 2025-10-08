<?php
/**
 * 🧪 Clean Affiliate Email Test API
 * Simple, clean API for testing affiliate emails
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    if (!isset($input['action'])) {
        throw new Exception('Action is required');
    }
    
    $action = $input['action'];
    
    // Include the affiliate email sender functions
    require_once __DIR__ . '/affiliate_email_sender_functions.php';
    
    // Enable test mode for all API calls to prevent SMTP connection issues
    $_POST['test_mode'] = true;
    
    switch ($action) {
        case 'test':
            $result = [
                'success' => true,
                'message' => 'API is working correctly',
                'timestamp' => date('Y-m-d H:i:s'),
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'server_time' => date('Y-m-d H:i:s'),
                    'action' => $action
                ]
            ];
            break;
            
        case 'welcome':
            $result = sendAffiliateWelcomeEmail(null, $input);
            break;
            
        case 'commission':
            $result = sendAffiliateCommissionEmail(null, $input);
            break;
            
        case 'payout':
            $result = sendAffiliatePayoutEmail(null, $input);
            break;
            
        case 'milestone':
            $result = sendAffiliateMilestoneEmail(null, $input);
            break;
            
        case 'lookup_affiliate':
            $result = handleAffiliateLookup($input);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
    echo json_encode($result);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

/**
 * Handle affiliate lookup
 */
function handleAffiliateLookup($input) {
    if (!isset($input['affiliateCode'])) {
        return [
            'success' => false,
            'error' => 'affiliateCode is required'
        ];
    }
    
    $affiliateCode = $input['affiliateCode'];
    
    try {
        // Try to include order_manager for Firestore lookup
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
    } catch (Exception $e) {
        // Fallback: Return mock data for testing when Firestore is not available
        if (strpos($e->getMessage(), 'could not find driver') !== false || 
            strpos($e->getMessage(), 'PDO') !== false) {
            
            return [
                'success' => true,
                'message' => 'Affiliate found (mock data - Firestore not available)',
                'affiliateInfo' => [
                    'id' => 'mock-affiliate-id',
                    'email' => 'lokeshzen@gmail.com',
                    'name' => 'Lokesh Murali',
                    'code' => $affiliateCode,
                    'status' => 'active'
                ],
                'timestamp' => date('Y-m-d H:i:s'),
                'note' => 'Using mock data because Firestore driver is not available'
            ];
        } else {
            return [
                'success' => false,
                'error' => "Firestore lookup error: " . $e->getMessage(),
                'affiliateCode' => $affiliateCode,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
}
?>
