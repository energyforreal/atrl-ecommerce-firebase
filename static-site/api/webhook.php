<?php
// Enhanced Razorpay webhook handler with order creation
// Handles payment.captured events and creates orders in database

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Razorpay-Signature');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$cfg = @include __DIR__.'/config.php';
$WEBHOOK_SECRET = ($cfg['RAZORPAY_WEBHOOK_SECRET'] ?? null) ?: getenv('RAZORPAY_WEBHOOK_SECRET') ?: 'Rakeshmurali@10';

$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

function respond($obj, $code = 200) {
    http_response_code($code);
    echo json_encode($obj);
    exit;
}

// Log webhook attempts for debugging
error_log("WEBHOOK: Received webhook with signature: " . substr($sig, 0, 10) . "...");

if (!$sig) { 
    error_log("WEBHOOK: Missing signature");
    respond([ 'error' => 'Missing signature' ], 400); 
}

$expected = hash_hmac('sha256', $raw, $WEBHOOK_SECRET);
if (!hash_equals($expected, $sig)) {
    error_log("WEBHOOK: Invalid signature. Expected: " . substr($expected, 0, 10) . ", Got: " . substr($sig, 0, 10));
    respond([ 'error' => 'Invalid signature' ], 401);
}

$event = json_decode($raw, true);
error_log("WEBHOOK: Event received: " . ($event['event'] ?? 'unknown'));

// Handle payment.captured event
if ($event['event'] === 'payment.captured') {
    try {
        $payment = $event['payload']['payment']['entity'];
        $orderId = $payment['order_id'];
        $paymentId = $payment['id'];
        $amount = $payment['amount'];
        $currency = $payment['currency'];
        
        error_log("WEBHOOK: Processing payment.captured for order: $orderId, payment: $paymentId");
        
        // Create order in database using order_manager.php
        $orderData = [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'signature' => $sig, // Use webhook signature for verification
            'customer' => [
                'firstName' => 'Webhook',
                'lastName' => 'Customer',
                'email' => 'webhook@attral.in',
                'phone' => '0000000000'
            ],
            'product' => [
                'id' => 'webhook_order',
                'title' => 'Webhook Order',
                'price' => $amount / 100, // Convert from paise
                'items' => [[
                    'id' => 'webhook_item',
                    'title' => 'Webhook Item',
                    'price' => $amount / 100,
                    'quantity' => 1
                ]]
            ],
            'pricing' => [
                'subtotal' => $amount / 100,
                'shipping' => 0,
                'discount' => 0,
                'total' => $amount / 100,
                'currency' => $currency
            ],
            'shipping' => [
                'address' => 'Webhook Address',
                'city' => 'Webhook City',
                'state' => 'Webhook State',
                'pincode' => '000000',
                'country' => 'India'
            ],
            'payment' => [
                'method' => 'razorpay',
                'transaction_id' => $paymentId,
                'signature' => $sig
            ],
            'user_id' => null
        ];
        
        // Extract customer data from Razorpay notes
        $notes = $payment['notes'] ?? [];
        $customerEmail = $notes['email'] ?? 'customer@example.com';
        $customerFirstName = $notes['firstName'] ?? 'Valued';
        $customerLastName = $notes['lastName'] ?? 'Customer';
        $customerPhone = $notes['phone'] ?? '';
        
        // Build shipping address from notes
        $shippingAddress = implode("\n", array_filter([
            $notes['address'] ?? '',
            ($notes['city'] ?? '') . ', ' . ($notes['state'] ?? ''),
            'PIN: ' . ($notes['pincode'] ?? ''),
            $notes['country'] ?? 'India'
        ]));
        
        // Update orderData with real customer info
        $orderData['customer'] = [
            'firstName' => $customerFirstName,
            'lastName' => $customerLastName,
            'email' => $customerEmail,
            'phone' => $customerPhone
        ];
        $orderData['shipping']['address'] = $shippingAddress;
        
        // Save directly to Firestore using Firebase Admin SDK
        try {
            // Check if Firebase Admin SDK is available
            if (class_exists('Google\Cloud\Firestore\FirestoreClient')) {
                $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
                if (file_exists($serviceAccountPath)) {
                    $firestore = new Google\Cloud\Firestore\FirestoreClient([
                        'projectId' => 'e-commerce-1d40f',
                        'keyFilePath' => $serviceAccountPath
                    ]);
                    
                    $firestoreData = [
                        'orderId' => $orderId,
                        'razorpayOrderId' => $orderId,
                        'razorpayPaymentId' => $paymentId,
                        'status' => 'confirmed',
                        'amount' => $amount / 100,
                        'currency' => $currency,
                        'customer' => [
                            'firstName' => $customerFirstName,
                            'lastName' => $customerLastName,
                            'email' => $customerEmail,
                            'phone' => $customerPhone
                        ],
                        'product' => [
                            'id' => 'webhook_order',
                            'title' => 'Webhook Order',
                            'price' => $amount / 100,
                            'items' => [[
                                'id' => 'webhook_item',
                                'title' => 'ATTRAL 100W GaN Charger',
                                'price' => $amount / 100,
                                'quantity' => 1
                            ]]
                        ],
                        'pricing' => [
                            'subtotal' => $amount / 100,
                            'shipping' => 0,
                            'discount' => 0,
                            'total' => $amount / 100,
                            'currency' => $currency
                        ],
                        'shipping' => [
                            'address' => $notes['address'] ?? '',
                            'city' => $notes['city'] ?? '',
                            'state' => $notes['state'] ?? '',
                            'pincode' => $notes['pincode'] ?? '',
                            'country' => $notes['country'] ?? 'India'
                        ],
                        'payment' => [
                            'method' => 'razorpay',
                            'transaction_id' => $paymentId,
                            'signature' => $sig
                        ],
                        'createdAt' => new Google\Cloud\Core\Timestamp(new DateTime()),
                        'updatedAt' => new Google\Cloud\Core\Timestamp(new DateTime()),
                        'source' => 'webhook'
                    ];
                    
                    $docRef = $firestore->collection('orders')->add($firestoreData);
                    error_log("WEBHOOK: Order saved to Firestore with ID: " . $docRef->id());
                    
                    // 📧 Email sending removed - handled by order-success.html page
                    error_log("WEBHOOK: Order saved to Firestore. Email will be sent from order-success page.");
                } else {
                    error_log("WEBHOOK: Firebase service account file not found");
                }
            } else {
                error_log("WEBHOOK: Firebase Admin SDK not available");
            }
        } catch (Exception $e) {
            error_log("WEBHOOK: Firestore save error: " . $e->getMessage());
        }
        
        // Process order using Firestore-only order manager
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://attral.in/api/firestore_order_manager.php/create');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Webhook-Source: razorpay'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result['success']) {
                error_log("WEBHOOK: Order processed via Firestore manager: " . ($result['orderNumber'] ?? 'unknown'));
            } else {
                error_log("WEBHOOK: Firestore order processing failed: " . ($result['error'] ?? 'unknown error'));
            }
        } else {
            error_log("WEBHOOK: Firestore order processing failed with code $httpCode: $response");
        }
        
    } catch (Exception $e) {
        error_log("WEBHOOK: Error processing payment.captured: " . $e->getMessage());
    }
}

// Log successful webhook processing
error_log("WEBHOOK: Webhook processed successfully");
respond([ 'status' => 'ok', 'processed' => true ]);
?>


