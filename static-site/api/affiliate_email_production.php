<?php
/**
 * 🚀 Production Affiliate Email API
 * Real email sending using PHPMailer with Brevo SMTP
 * Based on send_email_real.php template
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

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    if (!isset($input['action'])) {
        throw new Exception('Action is required');
    }
    
    // Include the affiliate email sender functions
    require_once __DIR__ . '/affiliate_email_sender_functions.php';
    
    // PRODUCTION MODE: No test_mode - will send real emails
    // $_POST['test_mode'] is not set, so initializePHPMailer() will use real PHPMailer
    
    $action = $input['action'];
    
    switch ($action) {
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
?>
