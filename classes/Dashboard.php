<?php
// classes/Dashboard.php

class Dashboard {
    private $db;
    private $userId;
    private $widgets = [
        'revenue_stats' => [
            'title' => 'Revenue Statistics',
            'size' => 'col-md-6',
            'default_enabled' => true,
            'method' => 'getRevenueStats'
        ],
        'inventory_alerts' => [
            'title' => 'Low Stock Alerts',
            'size' => 'col-md-6',
            'default_enabled' => true,
            'method' => 'getLowStockAlerts'
        ],
        'recent_orders' => [
            'title' => 'Recent Orders',
            'size' => 'col-md-6',
            'default_enabled' => true,
            'method' => 'getRecentOrders'
        ],
        'top_products' => [
            'title' => 'Top Products',
            'size' => 'col-md-6',
            'default_enabled' => true,
            'method' => 'getTopProducts'
        ],
        'customer_stats' => [
            'title' => 'Customer Statistics',
            'size' => 'col-md-6',
            'default_enabled' => true,
            'method' => 'getCustomerStats'
        ],
        'active_campaigns' => [
            'title' => 'Active Campaigns',
            'size' => 'col-md-6',
            'default_enabled' => true,
            'method' => 'getActiveCampaigns'
        ]
    ];
    
    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        $this->userId = $userId ?? ($_SESSION['user_id'] ?? null);
    }
    
    public function getAvailableWidgets(): array {
        return $this->widgets;
    }
    
    public function getEnabledWidgets(): array {
        // Check if user-specific widget configuration exists
        if ($this->userId) {
            $enabledWidgets = $this->getUserWidgetPreferences();
            if (!empty($enabledWidgets)) {
                return array_intersect_key($this->widgets, array_flip($enabledWidgets));
            }
        }
        
        // Fallback to default enabled widgets
        return array_filter($this->widgets, function($widget) {
            return $widget['default_enabled'];
        });
    }
    
    /**
     * Get user-specific widget preferences from database
     * @return array
     */
    private function getUserWidgetPreferences(): array {
        if (!$this->userId) {
            return [];
        }
        
        $stmt = $this->db->prepare("
            SELECT widget_id 
            FROM user_dashboard_widgets 
            WHERE user_id = ? AND is_enabled = 1
        ");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $enabledWidgets = [];
        while ($row = $result->fetch_assoc()) {
            $enabledWidgets[] = $row['widget_id'];
        }
        
        return $enabledWidgets;
    }
    
    /**
     * Render a specific widget's content
     * @param string $widgetId
     * @return array|null
     */
    public function renderWidgetContent(string $widgetId): ?array {
        $widget = $this->widgets[$widgetId] ?? null;
        
        if (!$widget || !method_exists($this, $widget['method'])) {
            return null;
        }
        
        return [
            'id' => $widgetId,
            'title' => $widget['title'],
            'size' => $widget['size'],
            'data' => $this->{$widget['method']}()
        ];
    }
    
    public function getRevenueStats(): array {
        $sql = "SELECT 
                    SUM(total_amount) as total_revenue,
                    COUNT(*) as total_orders,
                    AVG(total_amount) as average_order
                FROM orders 
                WHERE status != 'cancelled'
                    AND order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
        
        $result = $this->db->query($sql);
        return $result->fetch_assoc() ?? [
            'total_revenue' => 0,
            'total_orders' => 0,
            'average_order' => 0
        ];
    }
    
    public function getLowStockAlerts(): array {
        $sql = "SELECT p.*, c.category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.stock_quantity <= p.reorder_level
                ORDER BY (p.stock_quantity / p.reorder_level) ASC
                LIMIT 5";
        
        $result = $this->db->query($sql);
        $alerts = [];
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }
        return $alerts;
    }
    
    public function getRecentOrders(): array {
        $sql = "SELECT o.*, 
                       c.first_name, 
                       c.last_name,
                       COUNT(od.order_detail_id) as items_count
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                LEFT JOIN order_details od ON o.order_id = od.order_id
                GROUP BY o.order_id
                ORDER BY o.order_date DESC
                LIMIT 5";
        
        $result = $this->db->query($sql);
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        return $orders;
    }
    
    public function getTopProducts(): array {
        $sql = "SELECT p.*, 
                       c.category_name,
                       COUNT(DISTINCT o.order_id) as order_count,
                       SUM(od.quantity) as units_sold
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN order_details od ON p.product_id = od.product_id
                LEFT JOIN orders o ON od.order_id = o.order_id
                WHERE o.status != 'cancelled'
                GROUP BY p.product_id
                ORDER BY units_sold DESC
                LIMIT 5";
        
        $result = $this->db->query($sql);
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }
    
    public function getCustomerStats(): array {
        $sql = "SELECT 
                    COUNT(DISTINCT customer_id) as total_customers,
                    COUNT(DISTINCT CASE WHEN order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) THEN customer_id END) as active_customers,
                    AVG(total_amount) as average_customer_value
                FROM orders
                WHERE status != 'cancelled'";
        
        $result = $this->db->query($sql);
        return $result->fetch_assoc() ?? [
            'total_customers' => 0,
            'active_customers' => 0,
            'average_customer_value' => 0
        ];
    }
    
    public function getActiveCampaigns(): array {
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT o.order_id) as order_count,
                       SUM(o.total_amount) as revenue
                FROM marketing_campaigns c
                LEFT JOIN orders o ON o.order_date BETWEEN c.start_date AND c.end_date
                WHERE c.status = 'active'
                    AND c.start_date <= CURRENT_DATE()
                    AND c.end_date >= CURRENT_DATE()
                GROUP BY c.campaign_id
                ORDER BY revenue DESC
                LIMIT 5";
        
        $result = $this->db->query($sql);
        $campaigns = [];
        while ($row = $result->fetch_assoc()) {
            $campaigns[] = $row;
        }
        return $campaigns;
    }
}
?>