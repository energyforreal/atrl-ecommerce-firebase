<?php
/**
 * 🎛️ ATTRAL Admin API - Unified Admin System
 * Comprehensive admin management API with proper error handling
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files
require_once 'config.php';
require_once 'firestore_admin_service.php';

class AttralAdminAPI {
    private $firestore;
    private $adminSession;
    
    public function __construct() {
        try {
            $this->firestore = new FirestoreAdminService();
            $this->validateAdminSession();
        } catch (Exception $e) {
            $this->sendError('Failed to initialize admin API: ' . $e->getMessage());
        }
    }
    
    private function validateAdminSession() {
        // Check for admin session in headers or request
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $sessionToken = $_POST['admin_session'] ?? $_GET['admin_session'] ?? '';
        
        if (empty($sessionToken) && empty($authHeader)) {
            $this->sendError('Admin authentication required', 401);
        }
        
        // For now, we'll use a simple session validation
        // In production, implement proper JWT or session validation
        $this->adminSession = [
            'username' => 'attral',
            'role' => 'admin',
            'authenticated' => true
        ];
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            switch ($action) {
                case 'get_dashboard_stats':
                    $this->getDashboardStats();
                    break;
                    
                case 'get_orders':
                    $this->getOrders();
                    break;
                    
                case 'get_users':
                    $this->getUsers();
                    break;
                    
                case 'get_messages':
                    $this->getMessages();
                    break;
                    
                case 'get_affiliates':
                    $this->getAffiliates();
                    break;
                    
                case 'get_products':
                    $this->getProducts();
                    break;
                    
                case 'get_coupons':
                    $this->getCoupons();
                    break;
                    
                case 'update_order_status':
                    $this->updateOrderStatus();
                    break;
                    
                case 'update_message_status':
                    $this->updateMessageStatus();
                    break;
                    
                case 'create_coupon':
                    $this->createCoupon();
                    break;
                    
                case 'delete_coupon':
                    $this->deleteCoupon();
                    break;
                    
                case 'get_analytics':
                    $this->getAnalytics();
                    break;
                    
                case 'export_data':
                    $this->exportData();
                    break;
                    
                default:
                    $this->sendError('Invalid action specified', 400);
            }
        } catch (Exception $e) {
            $this->sendError('API Error: ' . $e->getMessage(), 500);
        }
    }
    
    private function getDashboardStats() {
        try {
            $stats = [
                'total_orders' => 0,
                'total_revenue' => 0,
                'total_users' => 0,
                'total_affiliates' => 0,
                'pending_orders' => 0,
                'new_messages' => 0,
                'conversion_rate' => 0,
                'average_order_value' => 0
            ];
            
            // Get orders data
            $orders = $this->firestore->getCollection('orders');
            $stats['total_orders'] = count($orders);
            
            $totalRevenue = 0;
            $pendingOrders = 0;
            foreach ($orders as $order) {
                $amount = floatval($order['total_amount'] ?? $order['amount'] ?? 0);
                $status = strtolower($order['payment_status'] ?? $order['status'] ?? '');
                
                if (in_array($status, ['paid', 'completed', 'captured'])) {
                    $totalRevenue += $amount;
                } elseif ($status === 'pending') {
                    $pendingOrders++;
                }
            }
            
            $stats['total_revenue'] = $totalRevenue;
            $stats['pending_orders'] = $pendingOrders;
            
            // Get users data
            $users = $this->firestore->getCollection('users');
            $stats['total_users'] = count($users);
            
            // Get affiliates data
            $affiliates = $this->firestore->getCollection('affiliates');
            $stats['total_affiliates'] = count($affiliates);
            
            // Get messages data
            $messages = $this->firestore->getCollection('contact_messages');
            $newMessages = 0;
            foreach ($messages as $message) {
                if (($message['status'] ?? '') === 'new') {
                    $newMessages++;
                }
            }
            $stats['new_messages'] = $newMessages;
            
            // Calculate derived metrics
            $stats['conversion_rate'] = $stats['total_users'] > 0 ? 
                round(($stats['total_orders'] / $stats['total_users']) * 100, 2) : 0;
            $stats['average_order_value'] = $stats['total_orders'] > 0 ? 
                round($totalRevenue / $stats['total_orders'], 2) : 0;
            
            $this->sendSuccess('Dashboard stats retrieved successfully', $stats);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get dashboard stats: ' . $e->getMessage());
        }
    }
    
    private function getOrders() {
        try {
            $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 50);
            $status = $_POST['status'] ?? $_GET['status'] ?? '';
            
            $orders = $this->firestore->getCollection('orders', [
                'orderBy' => 'created_at',
                'direction' => 'desc',
                'limit' => $limit
            ]);
            
            $filteredOrders = [];
            foreach ($orders as $order) {
                if ($status && ($order['status'] ?? '') !== $status) {
                    continue;
                }
                
                $filteredOrders[] = [
                    'id' => $order['id'] ?? '',
                    'order_id' => $order['order_id'] ?? $order['id'] ?? '',
                    'customer_name' => $order['customer_name'] ?? $order['name'] ?? 'Unknown',
                    'customer_email' => $order['customer_email'] ?? $order['email'] ?? '',
                    'total_amount' => floatval($order['total_amount'] ?? $order['amount'] ?? 0),
                    'status' => $order['status'] ?? 'pending',
                    'payment_status' => $order['payment_status'] ?? 'pending',
                    'created_at' => $order['created_at'] ?? date('Y-m-d H:i:s'),
                    'items' => $order['items'] ?? [],
                    'shipping_address' => $order['shipping_address'] ?? []
                ];
            }
            
            $this->sendSuccess('Orders retrieved successfully', [
                'orders' => $filteredOrders,
                'total' => count($filteredOrders)
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get orders: ' . $e->getMessage());
        }
    }
    
    private function getUsers() {
        try {
            $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 50);
            $type = $_POST['type'] ?? $_GET['type'] ?? '';
            
            $users = $this->firestore->getCollection('users', [
                'orderBy' => 'created_at',
                'direction' => 'desc',
                'limit' => $limit
            ]);
            
            $filteredUsers = [];
            foreach ($users as $user) {
                if ($type === 'affiliate' && !($user['is_affiliate'] ?? false)) {
                    continue;
                }
                
                $filteredUsers[] = [
                    'id' => $user['id'] ?? '',
                    'uid' => $user['uid'] ?? $user['id'] ?? '',
                    'name' => $user['displayName'] ?? $user['name'] ?? 'Unknown',
                    'email' => $user['email'] ?? '',
                    'phone' => $user['phone'] ?? '',
                    'is_affiliate' => $user['is_affiliate'] ?? false,
                    'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
                    'last_login_at' => $user['lastLoginAt'] ?? null
                ];
            }
            
            $this->sendSuccess('Users retrieved successfully', [
                'users' => $filteredUsers,
                'total' => count($filteredUsers)
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get users: ' . $e->getMessage());
        }
    }
    
    private function getMessages() {
        try {
            $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 50);
            $status = $_POST['status'] ?? $_GET['status'] ?? '';
            
            $messages = $this->firestore->getCollection('contact_messages', [
                'orderBy' => 'timestamp',
                'direction' => 'desc',
                'limit' => $limit
            ]);
            
            $filteredMessages = [];
            foreach ($messages as $message) {
                if ($status && ($message['status'] ?? '') !== $status) {
                    continue;
                }
                
                $filteredMessages[] = [
                    'id' => $message['id'] ?? '',
                    'name' => $message['name'] ?? 'Unknown',
                    'email' => $message['email'] ?? '',
                    'message' => $message['message'] ?? $message['text'] ?? '',
                    'status' => $message['status'] ?? 'new',
                    'priority' => $message['priority'] ?? 'normal',
                    'is_authenticated' => $message['isAuthenticated'] ?? false,
                    'created_at' => $message['timestamp'] ?? date('Y-m-d H:i:s'),
                    'updated_at' => $message['updatedAt'] ?? null
                ];
            }
            
            $this->sendSuccess('Messages retrieved successfully', [
                'messages' => $filteredMessages,
                'total' => count($filteredMessages)
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get messages: ' . $e->getMessage());
        }
    }
    
    private function getAffiliates() {
        try {
            $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 50);
            
            $affiliates = $this->firestore->getCollection('affiliates', [
                'orderBy' => 'created_at',
                'direction' => 'desc',
                'limit' => $limit
            ]);
            
            $filteredAffiliates = [];
            foreach ($affiliates as $affiliate) {
                $filteredAffiliates[] = [
                    'id' => $affiliate['id'] ?? '',
                    'uid' => $affiliate['uid'] ?? $affiliate['id'] ?? '',
                    'name' => $affiliate['displayName'] ?? $affiliate['name'] ?? 'Unknown',
                    'email' => $affiliate['email'] ?? '',
                    'code' => $affiliate['code'] ?? '',
                    'status' => $affiliate['status'] ?? 'active',
                    'commission_rate' => $affiliate['commission_rate'] ?? 5,
                    'total_earnings' => $affiliate['total_earnings'] ?? 0,
                    'created_at' => $affiliate['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
            
            $this->sendSuccess('Affiliates retrieved successfully', [
                'affiliates' => $filteredAffiliates,
                'total' => count($filteredAffiliates)
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get affiliates: ' . $e->getMessage());
        }
    }
    
    private function getProducts() {
        try {
            $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 50);
            $category = $_POST['category'] ?? $_GET['category'] ?? '';
            
            $products = $this->firestore->getCollection('products', [
                'orderBy' => 'created_at',
                'direction' => 'desc',
                'limit' => $limit
            ]);
            
            $filteredProducts = [];
            foreach ($products as $product) {
                if ($category && ($product['category'] ?? '') !== $category) {
                    continue;
                }
                
                $filteredProducts[] = [
                    'id' => $product['id'] ?? '',
                    'name' => $product['name'] ?? 'Unknown Product',
                    'price' => floatval($product['price'] ?? 0),
                    'category' => $product['category'] ?? 'uncategorized',
                    'status' => $product['status'] ?? 'active',
                    'stock' => intval($product['stock'] ?? 0),
                    'description' => $product['description'] ?? '',
                    'images' => $product['images'] ?? [],
                    'created_at' => $product['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
            
            $this->sendSuccess('Products retrieved successfully', [
                'products' => $filteredProducts,
                'total' => count($filteredProducts)
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get products: ' . $e->getMessage());
        }
    }
    
    private function getCoupons() {
        try {
            $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 50);
            
            $coupons = $this->firestore->getCollection('coupons', [
                'orderBy' => 'created_at',
                'direction' => 'desc',
                'limit' => $limit
            ]);
            
            $filteredCoupons = [];
            foreach ($coupons as $coupon) {
                $filteredCoupons[] = [
                    'id' => $coupon['id'] ?? '',
                    'code' => $coupon['code'] ?? '',
                    'name' => $coupon['name'] ?? '',
                    'type' => $coupon['type'] ?? 'percentage',
                    'value' => floatval($coupon['value'] ?? 0),
                    'is_active' => $coupon['isActive'] ?? true,
                    'usage_count' => intval($coupon['usageCount'] ?? 0),
                    'usage_limit' => $coupon['usageLimit'] ?? null,
                    'valid_until' => $coupon['validUntil'] ?? date('Y-m-d H:i:s'),
                    'created_at' => $coupon['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
            
            $this->sendSuccess('Coupons retrieved successfully', [
                'coupons' => $filteredCoupons,
                'total' => count($filteredCoupons)
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get coupons: ' . $e->getMessage());
        }
    }
    
    private function updateOrderStatus() {
        try {
            $orderId = $_POST['order_id'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if (empty($orderId) || empty($status)) {
                $this->sendError('Order ID and status are required');
            }
            
            $result = $this->firestore->updateDocument('orders', $orderId, [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $this->sendSuccess('Order status updated successfully');
            } else {
                $this->sendError('Failed to update order status');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to update order status: ' . $e->getMessage());
        }
    }
    
    private function updateMessageStatus() {
        try {
            $messageId = $_POST['message_id'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if (empty($messageId) || empty($status)) {
                $this->sendError('Message ID and status are required');
            }
            
            $result = $this->firestore->updateDocument('contact_messages', $messageId, [
                'status' => $status,
                'updatedAt' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $this->sendSuccess('Message status updated successfully');
            } else {
                $this->sendError('Failed to update message status');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to update message status: ' . $e->getMessage());
        }
    }
    
    private function createCoupon() {
        try {
            // Enhanced server-side validation
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? 'percentage';
            $value = floatval($_POST['value'] ?? 0);
            $minAmount = floatval($_POST['min_amount'] ?? 0);
            $maxDiscount = $_POST['max_discount'] ? floatval($_POST['max_discount']) : null;
            $validUntil = $_POST['valid_until'] ?? date('Y-m-d H:i:s', strtotime('+1 year'));
            $usageLimit = $_POST['usage_limit'] ? intval($_POST['usage_limit']) : null;
            $description = trim($_POST['description'] ?? '');
            
            // Validation checks
            if (empty($code) || empty($name)) {
                $this->sendError('Coupon code and name are required');
            }
            
            if (!preg_match('/^[A-Z0-9_-]+$/', $code)) {
                $this->sendError('Coupon code can only contain letters, numbers, underscores, and hyphens');
            }
            
            if (strlen($code) < 3 || strlen($code) > 20) {
                $this->sendError('Coupon code must be between 3 and 20 characters');
            }
            
            if (!in_array($type, ['percentage', 'fixed'])) {
                $this->sendError('Coupon type must be either "percentage" or "fixed"');
            }
            
            if ($value <= 0) {
                $this->sendError('Coupon value must be greater than 0');
            }
            
            if ($type === 'percentage' && $value > 100) {
                $this->sendError('Percentage discount cannot exceed 100%');
            }
            
            if ($usageLimit !== null && $usageLimit <= 0) {
                $this->sendError('Usage limit must be greater than 0');
            }
            
            if ($minAmount < 0) {
                $this->sendError('Minimum amount cannot be negative');
            }
            
            if ($maxDiscount !== null && $maxDiscount <= 0) {
                $this->sendError('Maximum discount must be greater than 0');
            }
            
            // Check if coupon code already exists
            $existingCoupons = $this->firestore->getCollection('coupons');
            foreach ($existingCoupons as $coupon) {
                if (strtoupper($coupon['code'] ?? '') === $code) {
                    $this->sendError('Coupon code already exists');
                }
            }
            
            $couponData = [
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'value' => $value,
                'minAmount' => $minAmount,
                'maxDiscount' => $maxDiscount,
                'isActive' => true,
                'validUntil' => $validUntil,
                'usageLimit' => $usageLimit,
                'usageCount' => 0,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => 'admin', // Track who created the coupon
                'source' => 'server' // Mark as server-created
            ];
            
            $result = $this->firestore->addDocument('coupons', $couponData);
            
            if ($result) {
                // Log admin action
                $this->logAdminAction('coupon_created', [
                    'coupon_id' => $result,
                    'coupon_code' => $code,
                    'coupon_name' => $name
                ]);
                
                $this->sendSuccess('Coupon created successfully', ['coupon_id' => $result]);
            } else {
                $this->sendError('Failed to create coupon');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to create coupon: ' . $e->getMessage());
        }
    }
    
    private function deleteCoupon() {
        try {
            $couponId = $_POST['coupon_id'] ?? '';
            
            if (empty($couponId)) {
                $this->sendError('Coupon ID is required');
            }
            
            $result = $this->firestore->deleteDocument('coupons', $couponId);
            
            if ($result) {
                $this->sendSuccess('Coupon deleted successfully');
            } else {
                $this->sendError('Failed to delete coupon');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to delete coupon: ' . $e->getMessage());
        }
    }
    
    private function getAnalytics() {
        try {
            $period = $_POST['period'] ?? $_GET['period'] ?? '30d';
            
            // Calculate analytics based on period
            $analytics = [
                'revenue_data' => [],
                'order_data' => [],
                'user_growth' => [],
                'conversion_rate' => 0,
                'average_order_value' => 0
            ];
            
            // Get orders for the period
            $orders = $this->firestore->getCollection('orders');
            $users = $this->firestore->getCollection('users');
            
            // Calculate metrics
            $totalRevenue = 0;
            $totalOrders = count($orders);
            $totalUsers = count($users);
            
            foreach ($orders as $order) {
                $amount = floatval($order['total_amount'] ?? $order['amount'] ?? 0);
                $status = strtolower($order['payment_status'] ?? $order['status'] ?? '');
                
                if (in_array($status, ['paid', 'completed', 'captured'])) {
                    $totalRevenue += $amount;
                }
            }
            
            $analytics['conversion_rate'] = $totalUsers > 0 ? round(($totalOrders / $totalUsers) * 100, 2) : 0;
            $analytics['average_order_value'] = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;
            
            $this->sendSuccess('Analytics retrieved successfully', $analytics);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get analytics: ' . $e->getMessage());
        }
    }
    
    private function exportData() {
        try {
            $type = $_POST['type'] ?? $_GET['type'] ?? 'orders';
            $format = $_POST['format'] ?? $_GET['format'] ?? 'json';
            
            $data = [];
            switch ($type) {
                case 'orders':
                    $data = $this->firestore->getCollection('orders');
                    break;
                case 'users':
                    $data = $this->firestore->getCollection('users');
                    break;
                case 'messages':
                    $data = $this->firestore->getCollection('contact_messages');
                    break;
                default:
                    $this->sendError('Invalid export type');
            }
            
            if ($format === 'csv') {
                // Convert to CSV format
                $csv = $this->arrayToCsv($data);
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $type . '_export.csv"');
                echo $csv;
                exit;
            } else {
                $this->sendSuccess('Data exported successfully', $data);
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to export data: ' . $e->getMessage());
        }
    }
    
    private function arrayToCsv($data) {
        if (empty($data)) return '';
        
        $csv = '';
        $headers = array_keys($data[0]);
        $csv .= implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $csv .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
        }
        
        return $csv;
    }
    
    private function sendSuccess($message, $data = null) {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    private function logAdminAction($action, $data = []) {
        try {
            $logData = [
                'action' => $action,
                'admin_user' => $this->adminSession['username'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s'),
                'data' => $data,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'source' => 'server'
            ];
            
            $this->firestore->addDocument('adminLogs', $logData);
        } catch (Exception $e) {
            // Don't fail the main operation if logging fails
            error_log('Failed to log admin action: ' . $e->getMessage());
        }
    }
}

// Initialize and handle the request
try {
    $api = new AttralAdminAPI();
    $api->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
