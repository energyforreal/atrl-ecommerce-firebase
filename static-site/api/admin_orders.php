<?php
/**
 * 📦 ATTRAL Admin Orders - Firestore Version
 * Manages orders using Firestore database
 */

// Only set headers if running in web context
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

require_once 'firestore_admin_service.php';

class AdminOrders {
    private $firestoreService;
    
    public function __construct() {
        $this->firestoreService = new FirestoreAdminService();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            switch ($method) {
                case 'GET':
                    return $this->getOrders($input);
                    
                case 'POST':
                    return $this->updateOrder($input);
                    
                case 'PUT':
                    return $this->updateOrderStatus($input);
                    
                case 'DELETE':
                    return $this->deleteOrder($input);
                    
                default:
                    return $this->error('Method not allowed', 405);
            }
        } catch (Exception $e) {
            error_log('Admin Orders Error: ' . $e->getMessage());
            return $this->error('Internal server error: ' . $e->getMessage(), 500);
        }
    }
    
    private function getOrders($input) {
        try {
            $filters = [];
            
            // Parse query parameters
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (isset($_GET['limit'])) {
                $filters['limit'] = (int)$_GET['limit'];
            }
            if (isset($_GET['order_by'])) {
                $filters['orderBy'] = $_GET['order_by'];
            }
            
            $result = $this->firestoreService->getOrders($filters);
            
            if ($result['success']) {
                return $this->success($result['orders'], 'Orders retrieved successfully');
            } else {
                return $this->error('Failed to retrieve orders: ' . $result['error'], 500);
            }
        } catch (Exception $e) {
            return $this->error('Error retrieving orders: ' . $e->getMessage(), 500);
        }
    }
    
    private function updateOrder($input) {
        try {
            if (!isset($input['order_id'])) {
                return $this->error('Order ID is required', 400);
            }
            
            $orderId = $input['order_id'];
            $updateData = $input['update_data'] ?? [];
            
            // Validate update data
            $allowedFields = ['status', 'shipping', 'notes', 'priority'];
            $filteredData = array_intersect_key($updateData, array_flip($allowedFields));
            
            if (empty($filteredData)) {
                return $this->error('No valid fields to update', 400);
            }
            
            $result = $this->firestoreService->updateOrderStatus($orderId, $updateData['status'] ?? 'pending', $filteredData);
            
            if ($result['success']) {
                return $this->success(null, 'Order updated successfully');
            } else {
                return $this->error('Failed to update order: ' . $result['error'], 500);
            }
        } catch (Exception $e) {
            return $this->error('Error updating order: ' . $e->getMessage(), 500);
        }
    }
    
    private function updateOrderStatus($input) {
        try {
            if (!isset($input['order_id']) || !isset($input['status'])) {
                return $this->error('Order ID and status are required', 400);
            }
            
            $orderId = $input['order_id'];
            $status = $input['status'];
            $additionalData = $input['additional_data'] ?? [];
            
            // Validate status
            $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
            if (!in_array($status, $validStatuses)) {
                return $this->error('Invalid status. Must be one of: ' . implode(', ', $validStatuses), 400);
            }
            
            $result = $this->firestoreService->updateOrderStatus($orderId, $status, $additionalData);
            
            if ($result['success']) {
                return $this->success(null, 'Order status updated successfully');
            } else {
                return $this->error('Failed to update order status: ' . $result['error'], 500);
            }
        } catch (Exception $e) {
            return $this->error('Error updating order status: ' . $e->getMessage(), 500);
        }
    }
    
    private function deleteOrder($input) {
        try {
            if (!isset($input['order_id'])) {
                return $this->error('Order ID is required', 400);
            }
            
            $orderId = $input['order_id'];
            
            // Get order first to check if it exists
            $orderResult = $this->firestoreService->getOrderById($orderId);
            if (!$orderResult['success']) {
                return $this->error('Order not found', 404);
            }
            
            // Soft delete by updating status to 'deleted'
            $result = $this->firestoreService->updateOrderStatus($orderId, 'deleted', [
                'deletedAt' => new DateTime(),
                'deletedBy' => 'admin'
            ]);
            
            if ($result['success']) {
                return $this->success(null, 'Order deleted successfully');
            } else {
                return $this->error('Failed to delete order: ' . $result['error'], 500);
            }
        } catch (Exception $e) {
            return $this->error('Error deleting order: ' . $e->getMessage(), 500);
        }
    }
    
    private function success($data = null, $message = 'Success') {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }
    
    private function error($message, $code = 400) {
        http_response_code($code);
        return [
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// ==================== API ENDPOINT ====================

if (php_sapi_name() !== 'cli') {
    $adminOrders = new AdminOrders();
    $result = $adminOrders->handleRequest();
    echo json_encode($result);
}
?>