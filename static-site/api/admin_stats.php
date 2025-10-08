<?php
/**
 * 📊 ATTRAL Admin Stats - Firestore Version
 * Provides comprehensive analytics and statistics using Firestore
 */

// Only set headers if running in web context
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

require_once 'firestore_admin_service.php';

class AdminStats {
    private $firestoreService;
    
    public function __construct() {
        $this->firestoreService = new FirestoreAdminService();
    }
    
    public function getStats() {
        try {
            // Get analytics for different periods
            $analytics30d = $this->firestoreService->getAnalytics('30d');
            $analytics7d = $this->firestoreService->getAnalytics('7d');
            $analyticsToday = $this->getTodayStats();
            
            if (!$analytics30d['success']) {
                throw new Exception('Failed to fetch analytics: ' . $analytics30d['error']);
            }
            
            $stats30d = $analytics30d['analytics'];
            $stats7d = $analytics7d['success'] ? $analytics7d['analytics'] : null;
            $statsToday = $analyticsToday['success'] ? $analyticsToday['analytics'] : null;
            
            $stats = [
                // Overall stats
                'total_orders' => $stats30d['totalOrders'],
                'total_revenue' => $stats30d['totalRevenue'],
                'total_users' => $stats30d['totalUsers'],
                'total_affiliates' => $stats30d['totalAffiliates'],
                'total_messages' => $stats30d['totalMessages'],
                
                // Order status breakdown
                'pending_orders' => $stats30d['statusCounts']['pending'] ?? 0,
                'completed_orders' => $stats30d['statusCounts']['completed'] ?? 0,
                'cancelled_orders' => $stats30d['statusCounts']['cancelled'] ?? 0,
                'failed_orders' => $stats30d['statusCounts']['failed'] ?? 0,
                
                // Time-based stats
                'today_orders' => $statsToday['orders'] ?? 0,
                'today_revenue' => $statsToday['revenue'] ?? 0,
                'week_orders' => $stats7d['totalOrders'] ?? 0,
                'week_revenue' => $stats7d['totalRevenue'] ?? 0,
                'month_orders' => $stats30d['totalOrders'],
                'month_revenue' => $stats30d['totalRevenue'],
                
                // Calculated metrics
                'average_order_value' => $this->calculateAverageOrderValue($stats30d),
                'conversion_rate' => $this->calculateConversionRate($stats30d),
                'growth_rate' => $this->calculateGrowthRate($stats7d, $stats30d),
                
                // Daily stats for charts
                'daily_stats' => $stats30d['dailyStats'],
                'status_breakdown' => $stats30d['statusCounts'],
                
                // Recent activity
                'recent_orders' => $this->getRecentOrders(),
                'recent_users' => $this->getRecentUsers(),
                'recent_affiliates' => $this->getRecentAffiliates(),
                
                // Period info
                'period' => $stats30d['period'],
                'start_date' => $stats30d['startDate'],
                'end_date' => $stats30d['endDate'],
                'last_updated' => date('Y-m-d H:i:s')
            ];
            
            return $this->success($stats);
            
        } catch (Exception $e) {
            error_log('Error getting admin stats: ' . $e->getMessage());
            return $this->error('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
    
    private function getTodayStats() {
        try {
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $tomorrow = clone $today;
            $tomorrow->modify('+1 day');
            
            // Get today's orders
            $ordersResult = $this->firestoreService->getOrders([
                'limit' => 1000 // Get all orders for today
            ]);
            
            if (!$ordersResult['success']) {
                return ['success' => false, 'error' => $ordersResult['error']];
            }
            
            $todayOrders = 0;
            $todayRevenue = 0;
            
            foreach ($ordersResult['orders'] as $order) {
                $orderDate = new DateTime($order['createdAt']);
                if ($orderDate >= $today && $orderDate < $tomorrow) {
                    $todayOrders++;
                    if ($order['status'] === 'completed' || $order['status'] === 'confirmed') {
                        $todayRevenue += $order['amount'];
                    }
                }
            }
            
            return [
                'success' => true,
                'analytics' => [
                    'orders' => $todayOrders,
                    'revenue' => $todayRevenue
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function calculateAverageOrderValue($stats) {
        if ($stats['totalOrders'] > 0) {
            return round($stats['totalRevenue'] / $stats['totalOrders'], 2);
        }
        return 0;
    }
    
    private function calculateConversionRate($stats) {
        // This would need to be calculated based on your conversion tracking
        // For now, we'll use a simple formula
        if ($stats['totalUsers'] > 0) {
            return round(($stats['totalOrders'] / $stats['totalUsers']) * 100, 2);
        }
        return 0;
    }
    
    private function calculateGrowthRate($weekStats, $monthStats) {
        if (!$weekStats || $weekStats['totalOrders'] == 0) {
            return 0;
        }
        
        $weekAverage = $weekStats['totalOrders'] / 7; // Daily average for the week
        $monthAverage = $monthStats['totalOrders'] / 30; // Daily average for the month
        
        if ($monthAverage > 0) {
            return round((($weekAverage - $monthAverage) / $monthAverage) * 100, 2);
        }
        return 0;
    }
    
    private function getRecentOrders() {
        $result = $this->firestoreService->getOrders(['limit' => 10]);
        if ($result['success']) {
            return array_slice($result['orders'], 0, 5); // Return last 5
        }
        return [];
    }
    
    private function getRecentUsers() {
        $result = $this->firestoreService->getUsers(['limit' => 10]);
        if ($result['success']) {
            return array_slice($result['users'], 0, 5); // Return last 5
        }
        return [];
    }
    
    private function getRecentAffiliates() {
        $result = $this->firestoreService->getAffiliates(['limit' => 10]);
        if ($result['success']) {
            return array_slice($result['affiliates'], 0, 5); // Return last 5
        }
        return [];
    }
    
    private function success($data) {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function error($message) {
        return [
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// ==================== API ENDPOINT ====================

if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $adminStats = new AdminStats();
        $result = $adminStats->getStats();
        echo json_encode($result);
    } catch (Exception $e) {
        error_log('Admin Stats API Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
} elseif (php_sapi_name() !== 'cli') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>