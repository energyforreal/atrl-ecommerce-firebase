<?php
// Verify Razorpay payment signature on server
// Docs: signature = hmac_sha256(order_id + '|' + payment_id, KEY_SECRET)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$cfg = @include __DIR__.'/config.php';
$KEY_SECRET = ($cfg['RAZORPAY_KEY_SECRET'] ?? null) ?: getenv('RAZORPAY_KEY_SECRET') ?: '';
if (!$KEY_SECRET) { http_response_code(500); echo json_encode(['error'=>'Missing KEY_SECRET']); exit; }

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) { http_response_code(400); echo json_encode(['error'=>'Invalid JSON']); exit; }

$orderId = $data['order_id'] ?? '';
$paymentId = $data['payment_id'] ?? '';
$signature = $data['signature'] ?? '';
if (!$orderId || !$paymentId || !$signature) { http_response_code(400); echo json_encode(['error'=>'Missing fields']); exit; }

$payload = $orderId . '|' . $paymentId;
$expected = hash_hmac('sha256', $payload, $KEY_SECRET);

if (!hash_equals($expected, $signature)) {
  http_response_code(401);
  echo json_encode(['valid'=>false]);
  exit;
}

echo json_encode(['valid'=>true]);
?>


