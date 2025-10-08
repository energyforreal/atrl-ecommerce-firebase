<?php
// Backfill invoice PDFs for existing orders in Firestore
// Can be executed via CLI: php backfill_invoices.php --limit=100
// Or via HTTP: /api/tools/backfill_invoices.php?limit=100

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

@include_once __DIR__ . '/../vendor/autoload.php';

$serviceAccountPath = __DIR__ . '/../firebase-service-account.json';
if (!file_exists($serviceAccountPath)) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Firebase service account file missing']);
  exit;
}

if (!class_exists('Google\Cloud\Firestore\FirestoreClient')) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Firestore SDK not installed. Run composer require google/cloud-firestore in api/']);
  exit;
}

// Parse input params
$limit = 50; $startAfter = null; $dryRun = false;
if (php_sapi_name() === 'cli') {
  foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0) { $limit = max(1, intval(substr($arg, 8))); }
    if (strpos($arg, '--startAfter=') === 0) { $startAfter = substr($arg, 13); }
    if ($arg === '--dryRun') { $dryRun = true; }
  }
} else {
  $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
  $startAfter = $_GET['startAfter'] ?? null;
  $dryRun = isset($_GET['dryRun']) && ($_GET['dryRun'] === '1' || $_GET['dryRun'] === 'true');
}

// Initialize Firestore
$firestore = new Google\Cloud\Firestore\FirestoreClient([
  'keyFilePath' => $serviceAccountPath
]);

$orders = $firestore->collection('orders')
  ->orderBy('orderId')
  ->limit($limit);

if ($startAfter) {
  $orders = $orders->startAfter([$startAfter]);
}

$docs = $orders->documents();

$results = [
  'attempted' => 0,
  'created' => 0,
  'skipped' => 0,
  'errors' => 0,
  'details' => []
];

// Ensure invoices directory exists
$invoiceDir = realpath(__DIR__ . '/..') . '/invoices';
if (!is_dir($invoiceDir)) { @mkdir($invoiceDir, 0775, true); }

foreach ($docs as $doc) {
  if (!$doc->exists()) { continue; }
  $data = $doc->data();
  $orderNumber = $data['orderId'] ?? ($data['order_id'] ?? null);
  if (!$orderNumber) {
    $results['errors']++;
    $results['details'][] = ['id' => $doc->id(), 'status' => 'error', 'reason' => 'Missing orderId field'];
    continue;
  }
  $results['attempted']++;

  $target = $invoiceDir . '/' . $orderNumber . '.pdf';
  if (file_exists($target)) {
    $results['skipped']++;
    $results['details'][] = ['orderId' => $orderNumber, 'status' => 'exists'];
    continue;
  }

  if ($dryRun) {
    $results['skipped']++;
    $results['details'][] = ['orderId' => $orderNumber, 'status' => 'dryRun'];
    continue;
  }

  try {
    $generated = generateInvoicePDFFromFirestore($orderNumber, $data, $target);
    if ($generated) {
      $results['created']++;
      $results['details'][] = ['orderId' => $orderNumber, 'status' => 'created', 'path' => relativeInvoiceUrl($target)];
      
      // Send invoice email if customer email is available
      $customerEmail = $data['customer']['email'] ?? ($data['customer_email'] ?? '');
      if ($customerEmail && !$dryRun) {
        $emailSent = sendInvoiceEmailFromBackfill($orderNumber, $target, $data);
        if ($emailSent) {
          $results['details'][count($results['details'])-1]['emailSent'] = true;
        }
      }
    } else {
      $results['errors']++;
      $results['details'][] = ['orderId' => $orderNumber, 'status' => 'error', 'reason' => 'Generate returned false'];
    }
  } catch (Exception $e) {
    $results['errors']++;
    $results['details'][] = ['orderId' => $orderNumber, 'status' => 'error', 'reason' => $e->getMessage()];
  }
}

echo json_encode(['success' => true, 'result' => $results]);
exit;

// Helpers
function generateInvoicePDFFromFirestore($orderNumber, $docData, $targetPath) {
  $fpdfPath = __DIR__ . '/../lib/fpdf/fpdf.php';
  if (!file_exists($fpdfPath)) { error_log('INVOICE: Missing FPDF'); return false; }
  require_once $fpdfPath;

  // Normalize to the structure expected by invoice generator
  $orderData = [
    'customer' => $docData['customer'] ?? [
      'firstName' => $docData['customer']['firstName'] ?? ($docData['customer_name'] ?? 'Customer'),
      'lastName' => $docData['customer']['lastName'] ?? '',
      'email' => $docData['customer']['email'] ?? ($docData['customer_email'] ?? ''),
      'phone' => $docData['customer']['phone'] ?? ($docData['customer_phone'] ?? '')
    ],
    'shipping' => $docData['shipping'] ?? [
      'address' => $docData['shipping']['address'] ?? ($docData['customer_address'] ?? ''),
      'city' => $docData['shipping']['city'] ?? '',
      'state' => $docData['shipping']['state'] ?? '',
      'pincode' => $docData['shipping']['pincode'] ?? '',
      'country' => $docData['shipping']['country'] ?? 'India'
    ],
    'product' => $docData['product'] ?? [
      'title' => $docData['product']['title'] ?? 'Product',
      'price' => $docData['product']['price'] ?? 0,
      'items' => $docData['product']['items'] ?? []
    ],
    'pricing' => $docData['pricing'] ?? [
      'subtotal' => $docData['pricing']['subtotal'] ?? ($docData['amount'] ?? 0),
      'shipping' => $docData['pricing']['shipping'] ?? 0,
      'discount' => $docData['pricing']['discount'] ?? 0,
      'total' => $docData['pricing']['total'] ?? ($docData['amount'] ?? 0),
      'currency' => $docData['pricing']['currency'] ?? ($docData['currency'] ?? 'INR')
    ]
  ];

  $pdf = new FPDF('P', 'mm', 'A4');
  $pdf->AddPage();
  $pdf->SetFont('Helvetica', 'B', 16);
  $pdf->Cell(190, 10, 'ATTRAL - Tax Invoice', 0, 1);

  $pdf->SetFont('Helvetica', '', 12);
  $pdf->Cell(95, 8, 'Invoice No: ' . $orderNumber, 0, 0);
  $pdf->Cell(95, 8, 'Date: ' . date('Y-m-d H:i'), 0, 1);

  $customerName = trim(($orderData['customer']['firstName'] ?? '') . ' ' . ($orderData['customer']['lastName'] ?? ''));
  $customerEmail = $orderData['customer']['email'] ?? '';
  $customerPhone = $orderData['customer']['phone'] ?? '';

  $pdf->Ln(2);
  $pdf->SetFont('Helvetica', 'B', 12);
  $pdf->Cell(190, 8, 'Billed To', 0, 1);
  $pdf->SetFont('Helvetica', '', 12);
  $pdf->MultiCell(190, 6, trim($customerName . "\n" . $customerEmail . "\n" . $customerPhone));

  $addrParts = [];
  $ship = $orderData['shipping'] ?? [];
  foreach (['address', 'city', 'state', 'pincode', 'country'] as $k) {
    if (!empty($ship[$k])) { $addrParts[] = $ship[$k]; }
  }
  $pdf->Ln(2);
  $pdf->SetFont('Helvetica', 'B', 12);
  $pdf->Cell(190, 8, 'Shipping Address', 0, 1);
  $pdf->SetFont('Helvetica', '', 12);
  $pdf->MultiCell(190, 6, implode(', ', $addrParts));

  $pdf->Ln(4);
  $pdf->SetFont('Helvetica', 'B', 12);
  $pdf->Cell(120, 8, 'Item', 0, 0);
  $pdf->Cell(30, 8, 'Qty', 0, 0);
  $pdf->Cell(40, 8, 'Amount (INR)', 0, 1);
  $pdf->SetFont('Helvetica', '', 12);

  $items = $orderData['product']['items'] ?? [];
  if (!is_array($items) || count($items) === 0) {
    $items = [[
      'title' => $orderData['product']['title'] ?? 'Product',
      'price' => $orderData['product']['price'] ?? ($orderData['pricing']['total'] ?? 0),
      'quantity' => 1
    ]];
  }

  $subtotal = 0.0;
  foreach ($items as $item) {
    $title = (string)($item['title'] ?? 'Item');
    $qty = (int)($item['quantity'] ?? 1);
    $price = (float)($item['price'] ?? 0);
    $line = $qty * $price;
    $subtotal += $line;
    $pdf->Cell(120, 7, $title, 0, 0);
    $pdf->Cell(30, 7, (string)$qty, 0, 0);
    $pdf->Cell(40, 7, number_format($line, 2), 0, 1);
  }

  $pricing = $orderData['pricing'] ?? [];
  $shipping = (float)($pricing['shipping'] ?? 0);
  $discount = (float)($pricing['discount'] ?? 0);
  $total = (float)($pricing['total'] ?? ($subtotal + $shipping - $discount));

  $pdf->Ln(2);
  $pdf->Cell(150, 7, 'Subtotal', 0, 0);
  $pdf->Cell(40, 7, number_format($subtotal, 2), 0, 1);
  $pdf->Cell(150, 7, 'Shipping', 0, 0);
  $pdf->Cell(40, 7, number_format($shipping, 2), 0, 1);
  $pdf->Cell(150, 7, 'Discount', 0, 0);
  $pdf->Cell(40, 7, '-' . number_format($discount, 2), 0, 1);
  $pdf->SetFont('Helvetica', 'B', 12);
  $pdf->Cell(150, 8, 'Total', 0, 0);
  $pdf->Cell(40, 8, number_format($total, 2), 0, 1);

  $pdf->Ln(6);
  $pdf->SetFont('Helvetica', '', 10);
  $pdf->MultiCell(190, 5, "This is a system-generated invoice for your Razorpay payment. Thank you for shopping with ATTRAL.");

  $pdf->Output('F', $targetPath);
  return true;
}

function sendInvoiceEmailFromBackfill($orderNumber, $invoicePath, $orderData) {
  try {
    // Include Brevo email service
    $brevoServicePath = __DIR__ . '/../brevo_email_service.php';
    if (!file_exists($brevoServicePath)) {
      error_log("BACKFILL EMAIL: Brevo service not found at $brevoServicePath");
      return false;
    }
    
    require_once $brevoServicePath;
    
    // Prepare order data for email template
    $customerEmail = $orderData['customer']['email'] ?? ($orderData['customer_email'] ?? '');
    $customerName = trim(($orderData['customer']['firstName'] ?? ($orderData['customer']['name'] ?? '')) . ' ' . ($orderData['customer']['lastName'] ?? ''));
    if (empty($customerName)) {
      $customerName = 'Valued Customer';
    }
    
    $emailOrderData = [
      'customerName' => $customerName,
      'total' => $orderData['pricing']['total'] ?? ($orderData['amount'] ?? 0),
      'orderDate' => date('F j, Y')
    ];
    
    // Send invoice email via Brevo
    $brevoService = new BrevoEmailService();
    $result = $brevoService->sendInvoiceEmail($customerEmail, $orderNumber, $invoicePath, $emailOrderData);
    
    if ($result['success']) {
      error_log("BACKFILL EMAIL: Successfully sent invoice for order $orderNumber to $customerEmail");
      return true;
    } else {
      error_log("BACKFILL EMAIL ERROR: Failed to send invoice for order $orderNumber: " . ($result['error'] ?? 'Unknown error'));
      return false;
    }
    
  } catch (Exception $e) {
    error_log("BACKFILL EMAIL EXCEPTION: " . $e->getMessage());
    return false;
  }
}

function relativeInvoiceUrl($absolutePath) {
  $apiDir = realpath(__DIR__ . '/..');
  $abs = realpath($absolutePath);
  if ($apiDir && $abs && strpos($abs, $apiDir) === 0) {
    $rel = str_replace($apiDir, '', $abs);
    $rel = ltrim(str_replace('\\\\', '/', $rel), '/');
    return '../' . $rel; // ../invoices/ATRL-0001.pdf
  }
  return basename($absolutePath);
}

?>


