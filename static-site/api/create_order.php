<?php
// Minimal Razorpay order creation using cURL (no Composer dependency)
// Configure your credentials below before deploying.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// TODO: Set these to your live/test keys
$cfg = @include __DIR__.'/config.php';
$RAZORPAY_KEY_ID = ($cfg['RAZORPAY_KEY_ID'] ?? null) ?: getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_xxxxxxxxxxxx';
$RAZORPAY_KEY_SECRET = ($cfg['RAZORPAY_KEY_SECRET'] ?? null) ?: getenv('RAZORPAY_KEY_SECRET') ?: 'xxxxxxxxxxxxxxxx';

function bad_request($msg, $code = 400) {
  http_response_code($code);
  echo json_encode([ 'error' => $msg ]);
  exit;
}

$raw = file_get_contents('php://input');
$req = json_decode($raw, true);
if (!$req) { bad_request('Invalid JSON'); }

$amount = isset($req['amount']) ? intval($req['amount']) : 0; // in paise
$currency = isset($req['currency']) ? $req['currency'] : 'INR';
$receipt = isset($req['receipt']) ? $req['receipt'] : ('rcpt_'.time());
$notes = isset($req['notes']) && is_array($req['notes']) ? $req['notes'] : [];
if ($amount <= 0) { bad_request('Amount must be > 0'); }

$payload = [
  'amount' => $amount,
  'currency' => $currency,
  'receipt' => $receipt,
  'notes' => $notes,
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
curl_setopt($ch, CURLOPT_USERPWD, $RAZORPAY_KEY_ID . ':' . $RAZORPAY_KEY_SECRET);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Enhanced error logging
if ($response === false) {
  error_log("Razorpay API Error: cURL failed - " . $curlError);
  bad_request('Payment service unavailable. Please try again later.', 500);
}

// Log the response for debugging
error_log("Razorpay API Response (HTTP $httpCode): " . $response);

// Check for API errors
if ($httpCode !== 200) {
  $errorData = json_decode($response, true);
  $errorMsg = isset($errorData['error']['description']) ? $errorData['error']['description'] : 'Payment service error';
  error_log("Razorpay API Error: " . $errorMsg);
  bad_request($errorMsg, $httpCode);
}

http_response_code($httpCode);
echo $response;
?>


