<?php
/**
 * Fulfillment Status Webhook
 * Handles Firestore fulfillment status changes and sends emails
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
    
    // Validate required fields
    if (!isset($input['orderId'])) {
        throw new Exception('orderId is required');
    }
    
    if (!isset($input['fulfillmentStatus'])) {
        throw new Exception('fulfillmentStatus is required');
    }
    
    if (!isset($input['customerEmail'])) {
        throw new Exception('customerEmail is required');
    }
    
    // Log the webhook call
    error_log("FULFILLMENT WEBHOOK: Order {$input['orderId']} status changed to {$input['fulfillmentStatus']}");
    
    // Call the fulfillment email API
    $emailData = [
        'orderId' => $input['orderId'],
        'customerEmail' => $input['customerEmail'],
        'customerName' => $input['customerName'] ?? 'Customer',
        'fulfillmentStatus' => $input['fulfillmentStatus'],
        'productTitle' => $input['productTitle'] ?? 'Your Product',
        'trackingNumber' => $input['trackingNumber'] ?? '',
        'estimatedDelivery' => $input['estimatedDelivery'] ?? ''
    ];
    
    // ✅ FIX: Use correct API URL (not localhost) for Hostinger compatibility
    $apiBaseUrl = isset($_SERVER['HTTP_HOST']) ? 
                  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] : 
                  'https://attral.in';
    
    $emailApiUrl = $apiBaseUrl . '/api/send_fulfillment_email.php';
    
    error_log("FULFILLMENT WEBHOOK: Calling email API at: {$emailApiUrl}");
    
    // Make internal API call to send email
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $emailApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Failed to call email API: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('Email API returned HTTP ' . $httpCode . ': ' . $response);
    }
    
    $emailResult = json_decode($response, true);
    
    if (!$emailResult || !$emailResult['success']) {
        throw new Exception('Email sending failed: ' . ($emailResult['error'] ?? 'Unknown error'));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Fulfillment status email sent successfully',
        'orderId' => $input['orderId'],
        'fulfillmentStatus' => $input['fulfillmentStatus'],
        'emailResult' => $emailResult,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("FULFILLMENT WEBHOOK ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
