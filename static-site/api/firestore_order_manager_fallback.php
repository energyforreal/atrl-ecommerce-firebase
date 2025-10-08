<?php
/**
 * 🔄 Fallback Order Manager - SQLite Version
 * Used when Firebase SDK is not available
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

// Load configuration
$cfg = @include __DIR__.'/config.php';
$RAZORPAY_KEY_SECRET = ($cfg['RAZORPAY_KEY_SECRET'] ?? null) ?: getenv('RAZORPAY_KEY_SECRET') ?: '';

class SQLiteOrderManager {
    
    private $pdo;
    private $razorpayKeySecret;
    
    public function __construct() {
        $this->razorpayKeySecret = $GLOBALS['RAZORPAY_KEY_SECRET'];
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        try {
            $dbPath = __DIR__ . '/orders.db';
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables if they don't exist
            $this->createTables();
            error_log("SQLITE: Database initialized successfully");
            
        } catch (Exception $e) {
            error_log("SQLITE ERROR: " . $e->getMessage());
            throw new Exception('Failed to initialize database: ' . $e->getMessage());
        }
    }
    
    private function createTables() {
        $sql = "
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                razorpay_order_id TEXT UNIQUE,
                razorpay_payment_id TEXT UNIQUE,
                order_number TEXT UNIQUE,
                customer_data TEXT,
                product_data TEXT,
                pricing_data TEXT,
                shipping_data TEXT,
                payment_data TEXT,
                status TEXT DEFAULT 'confirmed',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS order_sequence (
                id INTEGER PRIMARY KEY,
                last_number INTEGER DEFAULT 0
            );
            
            INSERT OR IGNORE INTO order_sequence (id, last_number) VALUES (1, 0);
        ";
        
        $this->pdo->exec($sql);
    }
    
    public function handleRequest() {
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
                return $this->createOrder();
                
            case '/status':
                return $this->getOrderStatus();
                
            case '/update':
                return $this->updateOrderStatus();
                
            default:
                http_response_code(404);
                return ['success' => false, 'error' => 'Endpoint not found'];
        }
    }
    
    private function createOrder() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            // Validate required fields
            $required = ['order_id', 'payment_id', 'customer', 'product', 'pricing', 'shipping', 'payment'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Check if order already exists
            $stmt = $this->pdo->prepare("SELECT id FROM orders WHERE razorpay_payment_id = ?");
            $stmt->execute([$input['payment_id']]);
            if ($stmt->fetch()) {
                throw new Exception('Order already exists for this payment');
            }
            
            // Generate order number
            $orderNumber = $this->generateOrderNumber();
            
            // Prepare order data
            $orderData = [
                'orderId' => $orderNumber,
                'razorpayOrderId' => $input['order_id'],
                'razorpayPaymentId' => $input['payment_id'],
                'amount' => $input['pricing']['total'] ?? 0, // Add amount field from pricing
                'currency' => $input['pricing']['currency'] ?? 'INR', // Add currency field
                'customer' => $input['customer'],
                'product' => $input['product'],
                'pricing' => $input['pricing'],
                'shipping' => $input['shipping'],
                'payment' => $input['payment'],
                'status' => 'confirmed',
                'createdAt' => date('c')
            ];
            
            // Save to database
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (
                    razorpay_order_id, razorpay_payment_id, order_number,
                    customer_data, product_data, pricing_data, shipping_data, payment_data, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $input['order_id'],
                $input['payment_id'],
                $orderNumber,
                json_encode($input['customer']),
                json_encode($input['product']),
                json_encode($input['pricing']),
                json_encode($input['shipping']),
                json_encode($input['payment']),
                'confirmed'
            ]);
            
            error_log("SQLITE ORDER: Order created successfully - ID: {$input['order_id']}, Order Number: $orderNumber");
            
            return [
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $orderData
            ];
            
        } catch (Exception $e) {
            error_log("SQLITE ORDER ERROR: " . $e->getMessage());
            http_response_code(400);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getOrderStatus() {
        try {
            $orderId = $_GET['order_id'] ?? null;
            
            if (!$orderId) {
                throw new Exception('Order ID is required');
            }
            
            $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE razorpay_order_id = ? OR order_number = ? LIMIT 1");
            $stmt->execute([$orderId, $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            $orderData = [
                'orderId' => $order['order_number'],
                'razorpayOrderId' => $order['razorpay_order_id'],
                'razorpayPaymentId' => $order['razorpay_payment_id'],
                'customer' => json_decode($order['customer_data'], true),
                'product' => json_decode($order['product_data'], true),
                'pricing' => json_decode($order['pricing_data'], true),
                'shipping' => json_decode($order['shipping_data'], true),
                'payment' => json_decode($order['payment_data'], true),
                'status' => $order['status'],
                'createdAt' => $order['created_at']
            ];
            
            return [
                'success' => true,
                'order' => $orderData
            ];
            
        } catch (Exception $e) {
            error_log("SQLITE ORDER STATUS ERROR: " . $e->getMessage());
            http_response_code(404);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function updateOrderStatus() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['orderId']) || !isset($input['status'])) {
                throw new Exception('Order ID and status are required');
            }
            
            $orderId = $input['orderId'];
            $status = $input['status'];
            $message = $input['message'] ?? '';
            
            $stmt = $this->pdo->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE razorpay_order_id = ? OR order_number = ?");
            $stmt->execute([$status, $orderId, $orderId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Order not found');
            }
            
            error_log("SQLITE ORDER: Status updated - Order: $orderId, Status: $status");
            
            return [
                'success' => true,
                'message' => 'Order status updated successfully'
            ];
            
        } catch (Exception $e) {
            error_log("SQLITE ORDER UPDATE ERROR: " . $e->getMessage());
            http_response_code(400);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function generateOrderNumber() {
        try {
            $this->pdo->exec("UPDATE order_sequence SET last_number = last_number + 1 WHERE id = 1");
            $stmt = $this->pdo->query("SELECT last_number FROM order_sequence WHERE id = 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNumber = intval($row['last_number'] ?? 1);
            return 'ATRL-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
        } catch (Exception $e) {
            error_log("SQLITE ORDER NUMBER ERROR: " . $e->getMessage());
            // Fallback to timestamp-based number
            return 'ATRL-' . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
        }
    }
}

// Handle the request
$result = null;

try {
    $manager = new SQLiteOrderManager();
    $result = $manager->handleRequest();
} catch (Exception $e) {
    error_log("SQLITE ORDER MANAGER ERROR: " . $e->getMessage());
    $result = [
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ];
}

// Only output JSON if this file is called directly (not from main API)
if (!isset($GLOBALS['FIRESTORE_MAIN_API_CALL'])) {
    echo json_encode($result);
} else {
    // Return result to main API
    return $result;
}
?>
