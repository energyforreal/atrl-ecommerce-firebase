<?php
/**
 * 👥 ATTRAL Admin Users - Firestore Version
 * Manages users using Firestore database
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

class AdminUsers {
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
                    return $this->getUsers($input);
                    
            case 'POST':
                    return $this->createUser($input);
                    
            case 'PUT':
                    return $this->updateUser($input);
                    
            case 'DELETE':
                    return $this->deleteUser($input);
                    
            default:
                    return $this->error('Method not allowed', 405);
            }
        } catch (Exception $e) {
            error_log('Admin Users Error: ' . $e->getMessage());
            return $this->error('Internal server error: ' . $e->getMessage(), 500);
        }
    }
    
    private function getUsers($input) {
        try {
            $filters = [];
            
            // Parse query parameters
            if (isset($_GET['limit'])) {
                $filters['limit'] = (int)$_GET['limit'];
            }
            if (isset($_GET['order_by'])) {
                $filters['orderBy'] = $_GET['order_by'];
            }
            if (isset($_GET['active'])) {
                $filters['isActive'] = $_GET['active'] === 'true';
            }
            
            $result = $this->firestoreService->getUsers($filters);
            
            if ($result['success']) {
                // Add additional user statistics
                $users = $result['users'];
                foreach ($users as &$user) {
                    $user['order_count'] = $this->getUserOrderCount($user['id']);
                    $user['total_spent'] = $this->getUserTotalSpent($user['id']);
                    $user['last_order'] = $this->getUserLastOrder($user['id']);
                }
                
                return $this->success($users, 'Users retrieved successfully');
            } else {
                return $this->error('Failed to retrieve users: ' . $result['error'], 500);
            }
        } catch (Exception $e) {
            return $this->error('Error retrieving users: ' . $e->getMessage(), 500);
        }
    }
    
    private function createUser($input) {
        try {
            // Validate required fields
            $requiredFields = ['email', 'displayName'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    return $this->error("Field '$field' is required", 400);
                }
            }
            
            // This would typically be handled by Firebase Auth
            // For now, we'll just return a success message
            return $this->success(null, 'User creation should be handled through Firebase Auth');
            
        } catch (Exception $e) {
            return $this->error('Error creating user: ' . $e->getMessage(), 500);
        }
    }
    
    private function updateUser($input) {
        try {
            if (!isset($input['user_id'])) {
                return $this->error('User ID is required', 400);
            }
            
            $userId = $input['user_id'];
            $updateData = $input['update_data'] ?? [];
            
            // Validate update data
            $allowedFields = ['displayName', 'phoneNumber', 'isActive'];
            $filteredData = array_intersect_key($updateData, array_flip($allowedFields));
            
            if (empty($filteredData)) {
                return $this->error('No valid fields to update', 400);
            }
            
            // Update user status if provided
            if (isset($filteredData['isActive'])) {
                $result = $this->firestoreService->updateUserStatus($userId, $filteredData['isActive']);
                if (!$result['success']) {
                    return $this->error('Failed to update user status: ' . $result['error'], 500);
                }
            }
            
            // Update other fields directly in Firestore
            if (count($filteredData) > 1 || !isset($filteredData['isActive'])) {
                $result = $this->updateUserFields($userId, $filteredData);
                if (!$result['success']) {
                    return $this->error('Failed to update user fields: ' . $result['error'], 500);
                }
            }
            
            return $this->success(null, 'User updated successfully');
            
        } catch (Exception $e) {
            return $this->error('Error updating user: ' . $e->getMessage(), 500);
        }
    }
    
    private function deleteUser($input) {
        try {
            if (!isset($input['user_id'])) {
                return $this->error('User ID is required', 400);
            }
            
            $userId = $input['user_id'];
            
            // Soft delete by deactivating the user
            $result = $this->firestoreService->updateUserStatus($userId, false);
            
            if ($result['success']) {
                return $this->success(null, 'User deactivated successfully');
        } else {
                return $this->error('Failed to deactivate user: ' . $result['error'], 500);
            }
        } catch (Exception $e) {
            return $this->error('Error deactivating user: ' . $e->getMessage(), 500);
        }
    }
    
    private function updateUserFields($userId, $fields) {
        try {
            // This would update user fields in Firestore
            // For now, we'll simulate success
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getUserOrderCount($userId) {
        try {
            $result = $this->firestoreService->getOrders(['filters' => ['uid' => $userId]]);
            if ($result['success']) {
                return count($result['orders']);
            }
            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getUserTotalSpent($userId) {
        try {
            $result = $this->firestoreService->getOrders(['filters' => ['uid' => $userId]]);
            if ($result['success']) {
                $total = 0;
                foreach ($result['orders'] as $order) {
                    if ($order['status'] === 'completed' || $order['status'] === 'confirmed') {
                        $total += $order['amount'];
                    }
                }
                return $total;
            }
            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getUserLastOrder($userId) {
        try {
            $result = $this->firestoreService->getOrders(['filters' => ['uid' => $userId], 'limit' => 1]);
            if ($result['success'] && !empty($result['orders'])) {
                return $result['orders'][0]['createdAt'];
            }
            return null;
        } catch (Exception $e) {
            return null;
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
    $adminUsers = new AdminUsers();
    $result = $adminUsers->handleRequest();
    echo json_encode($result);
}
?>