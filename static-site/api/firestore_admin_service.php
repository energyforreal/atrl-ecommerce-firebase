<?php
/**
 * 🔥 ATTRAL Firestore Admin Service
 * Centralized Firestore service for all admin functions
 * 
 * Features:
 * - Orders management
 * - Users management
 * - Analytics and statistics
 * - Affiliates management
 * - Messages management
 */

// Only set headers if running in web context
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Composer autoload for Firebase Admin SDK
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Core\Timestamp;

class FirestoreAdminService {
    private $firestore;
    private $projectId = 'e-commerce-1d40f';
    
    public function __construct() {
        $this->initFirestore();
    }
    
    private function initFirestore() {
        try {
            $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
            if (!file_exists($serviceAccountPath)) {
                throw new Exception('Firebase service account file not found');
            }
            
            $this->firestore = new FirestoreClient([
                'projectId' => $this->projectId,
                'keyFilePath' => $serviceAccountPath
            ]);
            
            error_log('Firestore Admin Service initialized successfully');
        } catch (Exception $e) {
            error_log('Firestore Admin Service initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all orders with optional filtering
     */
    public function getOrders($filters = []) {
        try {
            $query = $this->firestore->collection('orders');
            
            // Apply filters
            if (isset($filters['status'])) {
                $query = $query->where('status', '=', $filters['status']);
            }
            if (isset($filters['limit'])) {
                $query = $query->limit($filters['limit']);
            }
            if (isset($filters['orderBy'])) {
                $query = $query->orderBy($filters['orderBy'], 'desc');
            } else {
                $query = $query->orderBy('createdAt', 'desc');
            }
            
            $snapshot = $query->get();
            $orders = [];
            
            foreach ($snapshot as $doc) {
                $data = $doc->data();
                $orders[] = [
                    'id' => $doc->id(),
                    'orderId' => $data['orderId'] ?? 'N/A',
                    'razorpayOrderId' => $data['razorpayOrderId'] ?? 'N/A',
                    'razorpayPaymentId' => $data['razorpayPaymentId'] ?? 'N/A',
                    'status' => $data['status'] ?? 'unknown',
                    'amount' => $data['pricing']['total'] ?? 0,
                    'currency' => $data['pricing']['currency'] ?? 'INR',
                    'customer' => $data['customer'] ?? [],
                    'product' => $data['product'] ?? [],
                    'shipping' => $data['shipping'] ?? [],
                    'payment' => $data['payment'] ?? [],
                    'uid' => $data['uid'] ?? null,
                    'createdAt' => $data['createdAt'] ? $data['createdAt']->format('Y-m-d H:i:s') : null,
                    'updatedAt' => $data['updatedAt'] ? $data['updatedAt']->format('Y-m-d H:i:s') : null,
                    'source' => $data['source'] ?? 'unknown'
                ];
            }
            
            return ['success' => true, 'orders' => $orders];
        } catch (Exception $e) {
            error_log('Error fetching orders: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get order by ID
     */
    public function getOrderById($orderId) {
        try {
            $docRef = $this->firestore->collection('orders')->document($orderId);
            $doc = $docRef->get();
            
            if (!$doc->exists()) {
                return ['success' => false, 'error' => 'Order not found'];
            }
            
            $data = $doc->data();
            $order = [
                'id' => $doc->id(),
                'orderId' => $data['orderId'] ?? 'N/A',
                'razorpayOrderId' => $data['razorpayOrderId'] ?? 'N/A',
                'razorpayPaymentId' => $data['razorpayPaymentId'] ?? 'N/A',
                'status' => $data['status'] ?? 'unknown',
                'amount' => $data['pricing']['total'] ?? 0,
                'currency' => $data['pricing']['currency'] ?? 'INR',
                'customer' => $data['customer'] ?? [],
                'product' => $data['product'] ?? [],
                'shipping' => $data['shipping'] ?? [],
                'payment' => $data['payment'] ?? [],
                'uid' => $data['uid'] ?? null,
                'createdAt' => $data['createdAt'] ? $data['createdAt']->format('Y-m-d H:i:s') : null,
                'updatedAt' => $data['updatedAt'] ? $data['updatedAt']->format('Y-m-d H:i:s') : null,
                'source' => $data['source'] ?? 'unknown'
            ];
            
            return ['success' => true, 'order' => $order];
        } catch (Exception $e) {
            error_log('Error fetching order: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $status, $additionalData = []) {
        try {
            $docRef = $this->firestore->collection('orders')->document($orderId);
            
            $updateData = array_merge([
                'status' => $status,
                'updatedAt' => new Timestamp(new DateTime())
            ], $additionalData);
            
            $docRef->update($updateData);
            
            return ['success' => true, 'message' => 'Order status updated successfully'];
        } catch (Exception $e) {
            error_log('Error updating order status: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all users
     */
    public function getUsers($filters = []) {
        try {
            $query = $this->firestore->collection('users');
            
            if (isset($filters['limit'])) {
                $query = $query->limit($filters['limit']);
            }
            if (isset($filters['orderBy'])) {
                $query = $query->orderBy($filters['orderBy'], 'desc');
            } else {
                $query = $query->orderBy('createdAt', 'desc');
            }
            
            $snapshot = $query->get();
            $users = [];
            
            foreach ($snapshot as $doc) {
                $data = $doc->data();
                $users[] = [
                    'id' => $doc->id(),
                    'uid' => $data['uid'] ?? $doc->id(),
                    'email' => $data['email'] ?? 'N/A',
                    'displayName' => $data['displayName'] ?? 'N/A',
                    'phoneNumber' => $data['phoneNumber'] ?? 'N/A',
                    'photoURL' => $data['photoURL'] ?? null,
                    'createdAt' => $data['createdAt'] ? $data['createdAt']->format('Y-m-d H:i:s') : null,
                    'lastLoginAt' => $data['lastLoginAt'] ? $data['lastLoginAt']->format('Y-m-d H:i:s') : null,
                    'isActive' => $data['isActive'] ?? true
                ];
            }
            
            return ['success' => true, 'users' => $users];
        } catch (Exception $e) {
            error_log('Error fetching users: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all affiliates
     */
    public function getAffiliates($filters = []) {
        try {
            $query = $this->firestore->collection('affiliates');
            
            if (isset($filters['limit'])) {
                $query = $query->limit($filters['limit']);
            }
            if (isset($filters['orderBy'])) {
                $query = $query->orderBy($filters['orderBy'], 'desc');
            } else {
                $query = $query->orderBy('createdAt', 'desc');
            }
            
            $snapshot = $query->get();
            $affiliates = [];
            
            foreach ($snapshot as $doc) {
                $data = $doc->data();
                $affiliates[] = [
                    'id' => $doc->id(),
                    'uid' => $data['uid'] ?? $doc->id(),
                    'email' => $data['email'] ?? 'N/A',
                    'displayName' => $data['displayName'] ?? $data['name'] ?? 'N/A',
                    'code' => $data['code'] ?? 'N/A',
                    'status' => $data['status'] ?? 'active',
                    'totalEarnings' => $data['totalEarnings'] ?? 0,
                    'totalReferrals' => $data['totalReferrals'] ?? 0,
                    'createdAt' => $data['createdAt'] ? $data['createdAt']->format('Y-m-d H:i:s') : null,
                    'lastSync' => $data['lastSync'] ? $data['lastSync']->format('Y-m-d H:i:s') : null
                ];
            }
            
            return ['success' => true, 'affiliates' => $affiliates];
        } catch (Exception $e) {
            error_log('Error fetching affiliates: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all contact messages
     */
    public function getContactMessages($filters = []) {
        try {
            $query = $this->firestore->collection('contact_messages');
            
            if (isset($filters['status'])) {
                $query = $query->where('status', '=', $filters['status']);
            }
            if (isset($filters['limit'])) {
                $query = $query->limit($filters['limit']);
            }
            if (isset($filters['orderBy'])) {
                $query = $query->orderBy($filters['orderBy'], 'desc');
            } else {
                $query = $query->orderBy('createdAt', 'desc');
            }
            
            $snapshot = $query->get();
            $messages = [];
            
            foreach ($snapshot as $doc) {
                $data = $doc->data();
                $messages[] = [
                    'id' => $doc->id(),
                    'name' => $data['name'] ?? 'N/A',
                    'email' => $data['email'] ?? 'N/A',
                    'phone' => $data['phone'] ?? 'N/A',
                    'message' => $data['message'] ?? 'N/A',
                    'status' => $data['status'] ?? 'new',
                    'source' => $data['source'] ?? 'contact_form',
                    'createdAt' => $data['createdAt'] ? $data['createdAt']->format('Y-m-d H:i:s') : null
                ];
            }
            
            return ['success' => true, 'messages' => $messages];
        } catch (Exception $e) {
            error_log('Error fetching contact messages: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get analytics and statistics
     */
    public function getAnalytics($period = '30d') {
        try {
            $endDate = new DateTime();
            $startDate = new DateTime();
            
            switch ($period) {
                case '7d':
                    $startDate->modify('-7 days');
                    break;
                case '30d':
                    $startDate->modify('-30 days');
                    break;
                case '90d':
                    $startDate->modify('-90 days');
                    break;
                case '1y':
                    $startDate->modify('-1 year');
                    break;
                default:
                    $startDate->modify('-30 days');
            }
            
            // Get orders in period
            $ordersQuery = $this->firestore->collection('orders')
                ->where('createdAt', '>=', new Timestamp($startDate))
                ->where('createdAt', '<=', new Timestamp($endDate));
            
            $ordersSnapshot = $ordersQuery->get();
            
            $totalOrders = 0;
            $totalRevenue = 0;
            $statusCounts = [];
            $dailyStats = [];
            
            foreach ($ordersSnapshot as $doc) {
                $data = $doc->data();
                $totalOrders++;
                
                if (isset($data['pricing']['total'])) {
                    $totalRevenue += $data['pricing']['total'];
                }
                
                $status = $data['status'] ?? 'unknown';
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                
                $createdAt = $data['createdAt'] ? $data['createdAt']->format('Y-m-d') : date('Y-m-d');
                $dailyStats[$createdAt] = ($dailyStats[$createdAt] ?? 0) + 1;
            }
            
            // Get total users
            $usersSnapshot = $this->firestore->collection('users')->get();
            $totalUsers = $usersSnapshot->size();
            
            // Get total affiliates
            $affiliatesSnapshot = $this->firestore->collection('affiliates')->get();
            $totalAffiliates = $affiliatesSnapshot->size();
            
            // Get total messages
            $messagesSnapshot = $this->firestore->collection('contact_messages')->get();
            $totalMessages = $messagesSnapshot->size();
            
            return [
                'success' => true,
                'analytics' => [
                    'period' => $period,
                    'totalOrders' => $totalOrders,
                    'totalRevenue' => $totalRevenue,
                    'totalUsers' => $totalUsers,
                    'totalAffiliates' => $totalAffiliates,
                    'totalMessages' => $totalMessages,
                    'statusCounts' => $statusCounts,
                    'dailyStats' => $dailyStats,
                    'startDate' => $startDate->format('Y-m-d'),
                    'endDate' => $endDate->format('Y-m-d')
                ]
            ];
        } catch (Exception $e) {
            error_log('Error fetching analytics: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update message status
     */
    public function updateMessageStatus($messageId, $status) {
        try {
            $docRef = $this->firestore->collection('contact_messages')->document($messageId);
            $docRef->update([
                'status' => $status,
                'updatedAt' => new Timestamp(new DateTime())
            ]);
            
            return ['success' => true, 'message' => 'Message status updated successfully'];
        } catch (Exception $e) {
            error_log('Error updating message status: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update affiliate status
     */
    public function updateAffiliateStatus($affiliateId, $status) {
        try {
            $docRef = $this->firestore->collection('affiliates')->document($affiliateId);
            $docRef->update([
                'status' => $status,
                'updatedAt' => new Timestamp(new DateTime())
            ]);
            
            return ['success' => true, 'message' => 'Affiliate status updated successfully'];
        } catch (Exception $e) {
            error_log('Error updating affiliate status: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update user status
     */
    public function updateUserStatus($userId, $isActive) {
        try {
            $docRef = $this->firestore->collection('users')->document($userId);
            $docRef->update([
                'isActive' => $isActive,
                'updatedAt' => new Timestamp(new DateTime())
            ]);
            
            return ['success' => true, 'message' => 'User status updated successfully'];
        } catch (Exception $e) {
            error_log('Error updating user status: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// ==================== API ENDPOINTS ====================

if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    
    $firestoreService = new FirestoreAdminService();
    $action = $input['action'];
    
    try {
        switch ($action) {
            case 'get_orders':
                $filters = $input['filters'] ?? [];
                $result = $firestoreService->getOrders($filters);
                break;
                
            case 'get_order':
                $orderId = $input['order_id'] ?? null;
                if (!$orderId) {
                    throw new Exception('Order ID is required');
                }
                $result = $firestoreService->getOrderById($orderId);
                break;
                
            case 'update_order_status':
                $orderId = $input['order_id'] ?? null;
                $status = $input['status'] ?? null;
                $additionalData = $input['additional_data'] ?? [];
                if (!$orderId || !$status) {
                    throw new Exception('Order ID and status are required');
                }
                $result = $firestoreService->updateOrderStatus($orderId, $status, $additionalData);
                break;
                
            case 'get_users':
                $filters = $input['filters'] ?? [];
                $result = $firestoreService->getUsers($filters);
                break;
                
            case 'get_affiliates':
                $filters = $input['filters'] ?? [];
                $result = $firestoreService->getAffiliates($filters);
                break;
                
            case 'get_contact_messages':
                $filters = $input['filters'] ?? [];
                $result = $firestoreService->getContactMessages($filters);
                break;
                
            case 'get_analytics':
                $period = $input['period'] ?? '30d';
                $result = $firestoreService->getAnalytics($period);
                break;
                
            case 'update_message_status':
                $messageId = $input['message_id'] ?? null;
                $status = $input['status'] ?? null;
                if (!$messageId || !$status) {
                    throw new Exception('Message ID and status are required');
                }
                $result = $firestoreService->updateMessageStatus($messageId, $status);
                break;
                
            case 'update_affiliate_status':
                $affiliateId = $input['affiliate_id'] ?? null;
                $status = $input['status'] ?? null;
                if (!$affiliateId || !$status) {
                    throw new Exception('Affiliate ID and status are required');
                }
                $result = $firestoreService->updateAffiliateStatus($affiliateId, $status);
                break;
                
            case 'update_user_status':
                $userId = $input['user_id'] ?? null;
                $isActive = $input['is_active'] ?? null;
                if (!$userId || $isActive === null) {
                    throw new Exception('User ID and active status are required');
                }
                $result = $firestoreService->updateUserStatus($userId, $isActive);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
                exit;
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Firestore Admin Service Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif (php_sapi_name() !== 'cli') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
