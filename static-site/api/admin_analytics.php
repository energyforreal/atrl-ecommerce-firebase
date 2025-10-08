<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

class AdminAnalytics {
    private $db;
    
    public function __construct() {
        $this->db = new SQLite3('orders.db');
    }
    
    public function getAnalytics() {
        try {
            $analytics = [
                'revenue_data' => $this->getRevenueData(),
                'order_data' => $this->getOrderData(),
                'user_growth' => $this->getUserGrowthData(),
                'product_performance' => $this->getProductPerformance(),
                'geographic_data' => $this->getGeographicData(),
                'payment_methods' => $this->getPaymentMethodData(),
                'conversion_funnel' => $this->getConversionFunnel(),
                'customer_insights' => $this->getCustomerInsights(),
                'affiliate_performance' => $this->getAffiliatePerformance()
            ];
            
            return $this->success($analytics);
            
        } catch (Exception $e) {
            error_log('Error getting analytics: ' . $e->getMessage());
            return $this->error('Failed to retrieve analytics data');
        }
    }
    
    private function getRevenueData() {
        // Last 30 days revenue data
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                SUM(total_amount) as revenue,
                COUNT(*) as orders
            FROM orders 
            WHERE created_at >= date('now', '-30 days') AND status = 'completed'
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        
        $result = $stmt->execute();
        $data = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = [
                'date' => $row['date'],
                'revenue' => (float)$row['revenue'],
                'orders' => (int)$row['orders']
            ];
        }
        
        return $data;
    }
    
    private function getOrderData() {
        // Order status distribution
        $stmt = $this->db->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM orders 
            GROUP BY status
        ");
        
        $result = $stmt->execute();
        $data = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = [
                'status' => $row['status'],
                'count' => (int)$row['count']
            ];
        }
        
        return $data;
    }
    
    private function getUserGrowthData() {
        // User registration growth over time
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as new_users
            FROM (
                SELECT DISTINCT customer_email, MIN(created_at) as created_at
                FROM orders
                GROUP BY customer_email
            ) user_registrations
            WHERE created_at >= date('now', '-90 days')
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        
        $result = $stmt->execute();
        $data = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = [
                'date' => $row['date'],
                'new_users' => (int)$row['new_users']
            ];
        }
        
        return $data;
    }
    
    private function getProductPerformance() {
        // Top performing products
        $stmt = $this->db->prepare("
            SELECT 
                oi.product_name,
                SUM(oi.quantity) as total_sold,
                SUM(oi.price * oi.quantity) as total_revenue,
                COUNT(DISTINCT oi.order_id) as order_count,
                AVG(oi.price) as avg_price
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'completed'
            GROUP BY oi.product_name
            ORDER BY total_revenue DESC
            LIMIT 20
        ");
        
        $result = $stmt->execute();
        $data = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = [
                'product_name' => $row['product_name'],
                'total_sold' => (int)$row['total_sold'],
                'total_revenue' => (float)$row['total_revenue'],
                'order_count' => (int)$row['order_count'],
                'avg_price' => (float)$row['avg_price']
            ];
        }
        
        return $data;
    }
    
    private function getGeographicData() {
        // Orders by location (simplified - based on address)
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN customer_address LIKE '%Delhi%' OR customer_address LIKE '%New Delhi%' THEN 'Delhi'
                    WHEN customer_address LIKE '%Mumbai%' OR customer_address LIKE '%Maharashtra%' THEN 'Mumbai'
                    WHEN customer_address LIKE '%Bangalore%' OR customer_address LIKE '%Bengaluru%' THEN 'Bangalore'
                    WHEN customer_address LIKE '%Chennai%' OR customer_address LIKE '%Tamil Nadu%' THEN 'Chennai'
                    WHEN customer_address LIKE '%Kolkata%' OR customer_address LIKE '%West Bengal%' THEN 'Kolkata'
                    WHEN customer_address LIKE '%Hyderabad%' OR customer_address LIKE '%Telangana%' THEN 'Hyderabad'
                    WHEN customer_address LIKE '%Pune%' THEN 'Pune'
                    WHEN customer_address LIKE '%Ahmedabad%' OR customer_address LIKE '%Gujarat%' THEN 'Ahmedabad'
                    ELSE 'Other'
                END as location,
                COUNT(*) as order_count,
                SUM(total_amount) as total_revenue
            FROM orders 
            WHERE status = 'completed'
            GROUP BY location
            ORDER BY total_revenue DESC
        ");
        
        $result = $stmt->execute();
        $data = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = [
                'location' => $row['location'],
                'order_count' => (int)$row['order_count'],
                'total_revenue' => (float)$row['total_revenue']
            ];
        }
        
        return $data;
    }
    
    private function getPaymentMethodData() {
        // Payment method distribution
        $stmt = $this->db->prepare("
            SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as total_amount
            FROM orders 
            WHERE status = 'completed'
            GROUP BY payment_method
            ORDER BY count DESC
        ");
        
        $result = $stmt->execute();
        $data = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = [
                'payment_method' => $row['payment_method'],
                'count' => (int)$row['count'],
                'total_amount' => (float)$row['total_amount']
            ];
        }
        
        return $data;
    }
    
    private function getConversionFunnel() {
        // Simplified conversion funnel
        $totalVisitors = $this->db->querySingle("
            SELECT COUNT(DISTINCT customer_email) FROM orders
        ") ?: 1;
        
        $totalOrders = $this->db->querySingle("
            SELECT COUNT(*) FROM orders
        ") ?: 0;
        
        $completedOrders = $this->db->querySingle("
            SELECT COUNT(*) FROM orders WHERE status = 'completed'
        ") ?: 0;
        
        $totalRevenue = $this->db->querySingle("
            SELECT SUM(total_amount) FROM orders WHERE status = 'completed'
        ") ?: 0;
        
        return [
            'visitors' => $totalVisitors,
            'orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'revenue' => $totalRevenue,
            'conversion_rate' => $totalVisitors > 0 ? round(($totalOrders / $totalVisitors) * 100, 2) : 0,
            'completion_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 0
        ];
    }
    
    private function getCustomerInsights() {
        // Customer behavior insights
        $avgOrderValue = $this->db->querySingle("
            SELECT AVG(total_amount) FROM orders WHERE status = 'completed'
        ") ?: 0;
        
        $repeatCustomers = $this->db->querySingle("
            SELECT COUNT(*) FROM (
                SELECT customer_email 
                FROM orders 
                GROUP BY customer_email 
                HAVING COUNT(*) > 1
            )
        ") ?: 0;
        
        $totalCustomers = $this->db->querySingle("
            SELECT COUNT(DISTINCT customer_email) FROM orders
        ") ?: 0;
        
        return [
            'avg_order_value' => round($avgOrderValue, 2),
            'repeat_customers' => $repeatCustomers,
            'total_customers' => $totalCustomers,
            'repeat_rate' => $totalCustomers > 0 ? round(($repeatCustomers / $totalCustomers) * 100, 2) : 0
        ];
    }
    
    private function getAffiliatePerformance() {
        // Affiliate performance data
        $stmt = $this->db->prepare("
            SELECT 
                affiliate_id,
                COUNT(*) as orders_generated,
                SUM(total_amount) as total_sales,
                COUNT(DISTINCT customer_email) as unique_customers
            FROM orders 
            WHERE affiliate_id IS NOT NULL AND status = 'completed'
            GROUP BY affiliate_id
            ORDER BY total_sales DESC
            LIMIT 10
        ");
        
        $result = $stmt->execute();
        $data = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = [
                'affiliate_id' => $row['affiliate_id'],
                'orders_generated' => (int)$row['orders_generated'],
                'total_sales' => (float)$row['total_sales'],
                'unique_customers' => (int)$row['unique_customers']
            ];
        }
        
        return $data;
    }
    
    private function success($data) {
        return json_encode([
            'success' => true,
            'analytics' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function error($message) {
        return json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $analytics = new AdminAnalytics();
    echo $analytics->getAnalytics();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
