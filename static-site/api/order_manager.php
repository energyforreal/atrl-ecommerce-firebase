<?php
// Composer autoloader for Firestore SDK (if installed)
@include_once __DIR__ . '/vendor/autoload.php';
// Comprehensive Order Management System
// Handles order creation, status tracking, and inventory management

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

// Load configuration
$cfg = @include __DIR__.'/config.php';
$RAZORPAY_KEY_SECRET = ($cfg['RAZORPAY_KEY_SECRET'] ?? null) ?: getenv('RAZORPAY_KEY_SECRET') ?: '';

// Database configuration (using SQLite for simplicity)
$dbFile = __DIR__ . '/orders.db';
$pdo = new PDO("sqlite:$dbFile");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize database tables
initializeDatabase($pdo);

// Route handling - parse from URL path since PATH_INFO may not be available
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Extract path after the script name
$path = '/';
if (strpos($requestUri, $scriptName) === 0) {
    $path = substr($requestUri, strlen($scriptName));
    if ($path === '') $path = '/';
}

// Handle query parameters for GET requests
if ($method === 'GET' && strpos($path, '?') !== false) {
    $path = substr($path, 0, strpos($path, '?'));
}

switch ($path) {
    case '/create':
        if ($method === 'POST') {
            createOrder($pdo, $RAZORPAY_KEY_SECRET);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
    case '/status':
        if ($method === 'GET') {
            getOrderStatus($pdo);
        } elseif ($method === 'PUT') {
            updateOrderStatus($pdo);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
    case '/list':
        if ($method === 'GET') {
            listOrders($pdo);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
    case '/webhook':
        if ($method === 'POST') {
            handleWebhook($pdo, $RAZORPAY_KEY_SECRET);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found', 'path' => $path, 'method' => $method]);
        break;
}

function initializeDatabase($pdo) {
    // Orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            razorpay_order_id TEXT UNIQUE NOT NULL,
            razorpay_payment_id TEXT UNIQUE NOT NULL,
            order_number TEXT UNIQUE NOT NULL,
            status TEXT DEFAULT 'pending',
            customer_data TEXT NOT NULL,
            product_data TEXT NOT NULL,
            pricing_data TEXT NOT NULL,
            shipping_data TEXT NOT NULL,
            payment_data TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            notes TEXT
        )
    ");
    
    // Sequence for human-friendly order numbers (ATRL-0001, ...)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_sequence (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            last_number INTEGER NOT NULL
        )
    ");
    // Ensure singleton row exists
    $pdo->exec("INSERT OR IGNORE INTO order_sequence (id, last_number) VALUES (1, 0)");
    
    // Order status history
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_status_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER,
            status TEXT NOT NULL,
            message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders (id)
        )
    ");
    
    // Inventory tracking
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventory (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id TEXT NOT NULL,
            quantity_available INTEGER DEFAULT 0,
            quantity_reserved INTEGER DEFAULT 0,
            last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function createOrder($pdo, $keySecret) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }
    
    // Check if this is a webhook order (bypass signature verification)
    $isWebhookOrder = isset($_SERVER['HTTP_X_WEBHOOK_SOURCE']) && $_SERVER['HTTP_X_WEBHOOK_SOURCE'] === 'razorpay';
    
    if (!$isWebhookOrder) {
        // Verify payment signature for regular orders
        if (!verifyPaymentSignature($input, $keySecret)) {
            http_response_code(401);
            echo json_encode(['error' => 'Payment verification failed']);
            return;
        }
    } else {
        error_log("ORDER_MANAGER: Processing webhook order for payment: " . ($input['payment_id'] ?? 'unknown'));
    }
    
    try {
        $pdo->beginTransaction();
        
        // Generate unique order number in ATRL-0001 format (atomic)
        $pdo->exec("UPDATE order_sequence SET last_number = last_number + 1 WHERE id = 1");
        $seqRow = $pdo->query("SELECT last_number FROM order_sequence WHERE id = 1") -> fetch(PDO::FETCH_ASSOC);
        $nextNumber = intval($seqRow['last_number'] ?? 1);
        $orderNumber = 'ATRL-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        // Check if order already exists
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE razorpay_payment_id = ?");
        $stmt->execute([$input['payment_id']]);
        if ($stmt->fetch()) {
            throw new Exception('Order already exists for this payment');
        }
        
        // Create order record
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                razorpay_order_id, razorpay_payment_id, order_number,
                customer_data, product_data, pricing_data, shipping_data, payment_data, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
        ");
        
        $stmt->execute([
            $input['order_id'],
            $input['payment_id'],
            $orderNumber,
            json_encode($input['customer']),
            json_encode($input['product']),
            json_encode($input['pricing']),
            json_encode($input['shipping']),
            json_encode($input['payment'])
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Add status history
        addStatusHistory($pdo, $orderId, 'confirmed', 'Order created and payment verified');
        
        // Update inventory
        updateInventory($pdo, $input['product']);
        
        // 🤝 Check for affiliate code and process commission
        processAffiliateCommission($pdo, $orderId, $orderNumber, $input);
        
        // Note: Email and invoice generation moved to order success page for better UX
        
        // Write to Firestore (if available - non-critical)
        try {
            writeToFirestore($orderNumber, $input, $orderId);
        } catch (Exception $firestoreError) {
            error_log("FIRESTORE ERROR: " . $firestoreError->getMessage() . " - Order created but Firestore write failed");
        }
        
        $pdo->commit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'status' => 'confirmed',
            'message' => 'Order created successfully',
            'invoice_url' => $invoicePath ? relativeInvoiceUrl($invoicePath) : null,
            'api_source' => 'order_manager_sqlite',
            'timestamp' => date('c'),
            'request_id' => uniqid('sqlite_', true)
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to create order: ' . $e->getMessage(),
            'api_source' => 'order_manager_sqlite',
            'timestamp' => date('c'),
            'request_id' => uniqid('sqlite_', true)
        ]);
    }
}

function verifyPaymentSignature($input, $keySecret) {
    $orderId = $input['order_id'] ?? '';
    $paymentId = $input['payment_id'] ?? '';
    $signature = $input['signature'] ?? '';
    
    if (!$orderId || !$paymentId || !$signature) {
        return false;
    }
    
    $payload = $orderId . '|' . $paymentId;
    $expectedSignature = hash_hmac('sha256', $payload, $keySecret);
    
    return hash_equals($expectedSignature, $signature);
}

function updateInventory($pdo, $productData) {
    if (isset($productData['items']) && is_array($productData['items'])) {
        foreach ($productData['items'] as $item) {
            $productId = $item['id'];
            $quantity = $item['quantity'] ?? 1;
            
            // Update or insert inventory record
            $stmt = $pdo->prepare("
                INSERT INTO inventory (product_id, quantity_available, quantity_reserved)
                VALUES (?, 100, ?)
                ON CONFLICT(product_id) DO UPDATE SET
                quantity_reserved = quantity_reserved + ?,
                last_updated = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$productId, $quantity, $quantity]);
        }
    }
}

function addStatusHistory($pdo, $orderId, $status, $message = '') {
    $stmt = $pdo->prepare("
        INSERT INTO order_status_history (order_id, status, message)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$orderId, $status, $message]);
}

function sendInvoiceEmailToCustomer($orderNumber, $invoicePath, $orderData) {
    // 📧 Email sending removed - handled by order-success.html page
    error_log("INVOICE EMAIL: Email sending removed. Handled by order-success page.");
    return false;
}

    function sendOrderNotifications($orderNumber, $orderData) {
        // 📧 Email sending removed - handled by order-success.html page
        error_log("ORDER MANAGER: Email sending removed. Handled by order-success page.");
        return false;
    }
    
    /**
     * Process affiliate commission for an order
     */
    function processAffiliateCommission($pdo, $orderId, $orderNumber, $orderData) {
        try {
            // Check for affiliate code in order data
            $affiliateCode = extractAffiliateCode($orderData);
            
            if (!$affiliateCode) {
                error_log("AFFILIATE: No affiliate code found in order $orderNumber");
                return;
            }
            
            // Look up affiliate information
            $affiliateInfo = getAffiliateByCode($affiliateCode);
            
            if (!$affiliateInfo) {
                error_log("AFFILIATE: Affiliate not found for code: $affiliateCode");
                return;
            }
            
            // Calculate commission (10% of order total)
            $orderTotal = $orderData['pricing']['total'] ?? 0;
            $commissionAmount = $orderTotal * 0.10; // 10% commission
            
            if ($commissionAmount <= 0) {
                error_log("AFFILIATE: No commission to process for order $orderNumber");
                return;
            }
            
            // Create commission record
            createCommissionRecord($pdo, $affiliateInfo['id'], $orderId, $commissionAmount);
            
            // Send commission notification email
            sendCommissionEmail($affiliateInfo, $commissionAmount, $orderNumber);
            
            error_log("AFFILIATE: Commission processed - ₹$commissionAmount for affiliate {$affiliateInfo['email']} on order $orderNumber");
            
        } catch (Exception $e) {
            error_log("AFFILIATE COMMISSION ERROR: " . $e->getMessage());
        }
    }
    
    /**
     * Extract affiliate code from order data
     */
    function extractAffiliateCode($orderData) {
        // Check multiple possible locations for affiliate code
        $affiliateCode = null;
        
        // Check URL parameters in payment data
        if (isset($orderData['payment']['url_params']['ref'])) {
            $affiliateCode = $orderData['payment']['url_params']['ref'];
        }
        
        // Check customer data for affiliate reference
        if (!$affiliateCode && isset($orderData['customer']['affiliate_code'])) {
            $affiliateCode = $orderData['customer']['affiliate_code'];
        }
        
        // Check if affiliate code is in the order notes
        if (!$affiliateCode && isset($orderData['notes']['affiliate_code'])) {
            $affiliateCode = $orderData['notes']['affiliate_code'];
        }
        
        return $affiliateCode;
    }
    
    /**
     * Get affiliate information by code from Firestore
     */
    function getAffiliateByCode($affiliateCode) {
        try {
            // Initialize Firebase
            $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
            if (!file_exists($serviceAccountPath)) {
                error_log("AFFILIATE: Firebase service account file not found");
                return null;
            }
            
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            $firebase = (new \Kreait\Firebase\Factory())
                ->withServiceAccount($serviceAccount)
                ->create();
            
            $firestore = $firebase->firestore();
            
            // Query affiliates collection by code
            $affiliatesRef = $firestore->collection('affiliates');
            $query = $affiliatesRef->where('code', '=', $affiliateCode);
            $documents = $query->documents();
            
            foreach ($documents as $doc) {
                if ($doc->exists()) {
                    $data = $doc->data();
                    return [
                        'id' => $doc->id(),
                        'email' => $data['email'] ?? '',
                        'name' => $data['displayName'] ?? $data['name'] ?? 'Affiliate',
                        'code' => $affiliateCode,
                        'status' => $data['status'] ?? 'active'
                    ];
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("AFFILIATE LOOKUP ERROR: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create commission record in database
     */
    function createCommissionRecord($pdo, $affiliateId, $orderId, $commissionAmount) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO affiliate_earnings (user_id, order_id, commission_amount, commission_rate, status)
                VALUES (?, ?, ?, 10.00, 'pending')
            ");
            $stmt->execute([$affiliateId, $orderId, $commissionAmount]);
            
            error_log("AFFILIATE: Commission record created - ID: {$pdo->lastInsertId()}, Amount: ₹$commissionAmount");
            
        } catch (Exception $e) {
            error_log("AFFILIATE COMMISSION RECORD ERROR: " . $e->getMessage());
        }
    }
    
    /**
     * Send commission notification email using new affiliate email sender
     */
    function sendCommissionEmail($affiliateInfo, $commissionAmount, $orderNumber) {
        try {
            // Use the new affiliate email sender
            $affiliateEmailSenderPath = __DIR__ . '/affiliate_email_sender.php';
            if (!file_exists($affiliateEmailSenderPath)) {
                error_log("AFFILIATE EMAIL: Affiliate email sender not found");
                return;
            }
            
            require_once $affiliateEmailSenderPath;
            
            $result = sendAffiliateCommissionEmail(null, [
                'email' => $affiliateInfo['email'],
                'name' => $affiliateInfo['name'],
                'commission' => $commissionAmount,
                'orderId' => $orderNumber
            ]);
            
            if ($result['success']) {
                error_log("AFFILIATE EMAIL: Enhanced commission notification sent to {$affiliateInfo['email']}");
            } else {
                error_log("AFFILIATE EMAIL ERROR: Failed to send commission notification: " . ($result['error'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            error_log("AFFILIATE EMAIL EXCEPTION: " . $e->getMessage());
        }
    }

function getOrderStatus($pdo) {
    $orderId = $_GET['order_id'] ?? $_GET['order_number'] ?? '';
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT json_group_array(
                   json_object('status', h.status, 'message', h.message, 'created_at', h.created_at)
               ) FROM order_status_history h WHERE h.order_id = o.id) as status_history
        FROM orders o 
        WHERE o.razorpay_order_id = ? OR o.order_number = ?
    ");
    $stmt->execute([$orderId, $orderId]);
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        return;
    }
    
    // Parse JSON fields
    $order['customer_data'] = json_decode($order['customer_data'], true);
    $order['product_data'] = json_decode($order['product_data'], true);
    $order['pricing_data'] = json_decode($order['pricing_data'], true);
    $order['shipping_data'] = json_decode($order['shipping_data'], true);
    $order['payment_data'] = json_decode($order['payment_data'], true);
    $order['status_history'] = json_decode($order['status_history'], true);
    
    echo json_encode(['success' => true, 'order' => $order]);
}

function updateOrderStatus($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = $input['order_id'] ?? '';
    $status = $input['status'] ?? '';
    $message = $input['message'] ?? '';
    
    if (!$orderId || !$status) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID and status required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE razorpay_order_id = ? OR order_number = ?
        ");
        $stmt->execute([$status, $orderId, $orderId]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Order not found');
        }
        
        // Add status history
        $orderStmt = $pdo->prepare("SELECT id FROM orders WHERE razorpay_order_id = ? OR order_number = ?");
        $orderStmt->execute([$orderId, $orderId]);
        $order = $orderStmt->fetch();
        
        addStatusHistory($pdo, $order['id'], $status, $message);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update order status: ' . $e->getMessage()]);
    }
}

function listOrders($pdo) {
    $limit = min(50, intval($_GET['limit'] ?? 10));
    $offset = intval($_GET['offset'] ?? 0);
    
    $stmt = $pdo->prepare("
        SELECT razorpay_order_id, order_number, status, created_at, updated_at
        FROM orders 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'orders' => $orders]);
}

function handleWebhook($pdo, $keySecret) {
    $raw = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
    
    // Verify webhook signature
    $expectedSignature = hash_hmac('sha256', $raw, $keySecret);
    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid webhook signature']);
        return;
    }
    
    $event = json_decode($raw, true);
    
    if ($event['event'] === 'payment.captured') {
        // Payment was captured successfully
        $paymentId = $event['payload']['payment']['entity']['id'];
        $razorpayOrderId = $event['payload']['payment']['entity']['order_id'] ?? null;
        
        // Update order status
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE razorpay_payment_id = ?");
        $stmt->execute([$paymentId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing && $razorpayOrderId) {
            // Create minimal order if missing (idempotent best-effort)
            try {
                $pdo->beginTransaction();
                $pdo->exec("UPDATE order_sequence SET last_number = last_number + 1 WHERE id = 1");
                $seqRow = $pdo->query("SELECT last_number FROM order_sequence WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
                $nextNumber = intval($seqRow['last_number'] ?? 1);
                $orderNumber = 'ATRL-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                $ins = $pdo->prepare("INSERT INTO orders (razorpay_order_id, razorpay_payment_id, order_number, customer_data, product_data, pricing_data, shipping_data, payment_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'paid')");
                $empty = json_encode(new stdClass());
                $ins->execute([$razorpayOrderId, $paymentId, $orderNumber, $empty, $empty, $empty, $empty, json_encode(['provider'=>'razorpay'])]);
                $orderId = $pdo->lastInsertId();
                addStatusHistory($pdo, $orderId, 'paid', 'Payment captured webhook created order');
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
            }
        } else if ($existing) {
            $upd = $pdo->prepare("UPDATE orders SET status = 'paid', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upd->execute([$existing['id']]);
            addStatusHistory($pdo, $existing['id'], 'paid', 'Payment captured successfully');
        }
    }
    
    echo json_encode(['success' => true, 'processed' => true]);
}

function writeToFirestore($orderNumber, $orderData, $orderId) {
    try {
        // Check if Firebase Admin SDK is available
        if (!class_exists('Google\Cloud\Firestore\FirestoreClient')) {
            error_log("FIRESTORE: Firebase Admin SDK not available. Install with: composer require google/cloud-firestore");
            return writeToFirestoreFallback($orderNumber, $orderData, $orderId, 'SDK not available');
        }
        
        // Check if service account file exists
        $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
        if (!file_exists($serviceAccountPath)) {
            error_log("FIRESTORE: Service account file not found at: $serviceAccountPath");
            return writeToFirestoreFallback($orderNumber, $orderData, $orderId, 'Service account file not found');
        }
        
        // Initialize Firestore client
        $firestore = new Google\Cloud\Firestore\FirestoreClient([
            'projectId' => 'e-commerce-1d40f',
            'keyFilePath' => $serviceAccountPath
        ]);
        
        $firestoreData = [
            'orderId' => $orderNumber,
            'razorpayOrderId' => $orderData['order_id'],
            'razorpayPaymentId' => $orderData['payment_id'],
            'status' => 'confirmed',
            'amount' => $orderData['pricing']['total'] ?? 0, // Add amount field from pricing
            'currency' => $orderData['pricing']['currency'] ?? 'INR', // Add currency field
            'customer' => $orderData['customer'],
            'product' => $orderData['product'],
            'pricing' => $orderData['pricing'],
            'shipping' => $orderData['shipping'],
            'payment' => $orderData['payment'],
            'uid' => $orderData['user_id'] ?? null,
            'createdAt' => new Google\Cloud\Core\Timestamp(new DateTime()),
            'updatedAt' => new Google\Cloud\Core\Timestamp(new DateTime()),
            'serverOrderId' => $orderId,
            'source' => 'server'
        ];
        
        // Write to Firestore
        $collection = $firestore->collection('orders');
        $docRef = $collection->add($firestoreData);
        
        error_log("FIRESTORE SUCCESS: Order $orderNumber written to Firestore with ID: " . $docRef->id());
        return true;
        
    } catch (Exception $e) {
        error_log("FIRESTORE ERROR: " . $e->getMessage());
        return writeToFirestoreFallback($orderNumber, $orderData, $orderId, $e->getMessage());
    }
}

function writeToFirestoreFallback($orderNumber, $orderData, $orderId, $error) {
    try {
        // Fallback: Write to a local JSON file for manual reconciliation
        $fallbackData = [
            'orderId' => $orderNumber,
            'razorpayOrderId' => $orderData['order_id'],
            'razorpayPaymentId' => $orderData['payment_id'],
            'status' => 'confirmed',
            'customer' => $orderData['customer'],
            'product' => $orderData['product'],
            'pricing' => $orderData['pricing'],
            'shipping' => $orderData['shipping'],
            'payment' => $orderData['payment'],
            'createdAt' => date('c'),
            'updatedAt' => date('c'),
            'serverOrderId' => $orderId,
            'source' => 'server_fallback',
            'error' => $error
        ];
        
        $fallbackFile = __DIR__ . '/firestore_fallback.json';
        $existing = [];
        if (file_exists($fallbackFile)) {
            $existing = json_decode(file_get_contents($fallbackFile), true) ?: [];
        }
        $existing[] = $fallbackData;
        file_put_contents($fallbackFile, json_encode($existing, JSON_PRETTY_PRINT));
        
        error_log("FIRESTORE FALLBACK: Order $orderNumber saved to fallback file");
        return false;
        
    } catch (Exception $e) {
        error_log("FIRESTORE FALLBACK ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate an invoice PDF for the given order number and payload.
 * Returns the absolute file path on success, or null on failure.
 */
function generateInvoicePDF($orderNumber, $orderData) {
    try {
        // Ensure invoices directory exists
        $dir = __DIR__ . '/invoices';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // Lazy include a tiny FPDF build bundled in repo
        $fpdfPath = __DIR__ . '/lib/fpdf/fpdf.php';
        if (!file_exists($fpdfPath)) {
            error_log('INVOICE: FPDF library missing at ' . $fpdfPath);
            return null;
        }
        require_once $fpdfPath;

        // Build invoice
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

        // Shipping block
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

        // Items header
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(120, 8, 'Item', 0, 0);
        $pdf->Cell(30, 8, 'Qty', 0, 0);
        $pdf->Cell(40, 8, 'Amount (INR)', 0, 1);
        $pdf->SetFont('Helvetica', '', 12);

        $items = $orderData['product']['items'] ?? [];
        if (!is_array($items) || count($items) === 0) {
            // Support single product fallback
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

        $file = $dir . '/' . $orderNumber . '.pdf';
        $pdf->Output('F', $file);
        return $file;
    } catch (Exception $e) {
        error_log('INVOICE ERROR: ' . $e->getMessage());
        return null;
    }
}

function relativeInvoiceUrl($absolutePath) {
    // Expose as relative path from api/ root for simple hosting setups
    // If running behind a web server, ensure the invoices directory is web-accessible or add a file-serving endpoint.
    $apiDir = realpath(__DIR__);
    $abs = realpath($absolutePath);
    if ($apiDir && $abs && strpos($abs, $apiDir) === 0) {
        $rel = str_replace($apiDir, '', $abs);
        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        return './' . $rel; // e.g., ./invoices/ATRL-0001.pdf
    }
    return basename($absolutePath);
}
?>
