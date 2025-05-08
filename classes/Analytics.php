<?php
// classes/Analytics.php

class Analytics {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getSalesOverview(): ?array {
        $sql = "SELECT 
                    COUNT(DISTINCT o.order_id) as total_orders,
                    SUM(o.total_amount) as total_revenue,
                    AVG(o.total_amount) as average_order_value,
                    COUNT(DISTINCT o.customer_id) as unique_customers
                FROM orders o
                WHERE o.status != 'cancelled'";
        
        try {
            $result = $this->db->getConnection()->query($sql);
            if (!$result) {
                throw new Exception("Query failed: " . $this->db->getConnection()->error);
            }
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error in getSalesOverview: " . $e->getMessage());
            return null;
        }
    }
    
    public function getMonthlySales($months = 12): ?mysqli_result {
        $sql = "SELECT 
                    DATE_FORMAT(order_date, '%Y-%m') as month,
                    COUNT(DISTINCT order_id) as order_count,
                    SUM(total_amount) as revenue
                FROM orders 
                WHERE status != 'cancelled'
                    AND order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY month ASC";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('i', $months);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            return $stmt->get_result();
        } catch (Exception $e) {
            error_log("Error in getMonthlySales: " . $e->getMessage());
            return null;
        }
    }
    
    public function getTopProducts($limit = 10): ?mysqli_result {
        $sql = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.sku,
                    p.stock_quantity,
                    p.reorder_level,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(od.quantity) as units_sold,
                    SUM(od.subtotal) as revenue
                FROM products p
                LEFT JOIN order_details od ON p.product_id = od.product_id
                LEFT JOIN orders o ON od.order_id = o.order_id AND o.status != 'cancelled'
                GROUP BY p.product_id, p.product_name, p.sku, p.stock_quantity, p.reorder_level
                ORDER BY revenue DESC
                LIMIT ?";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('i', $limit);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            return $stmt->get_result();
        } catch (Exception $e) {
            error_log("Error in getTopProducts: " . $e->getMessage());
            return null;
        }
    }
    
    public function getProductAssociations($minSupport = 0.01, $minConfidence = 0.1): ?array {
        try {
            // First, get total number of orders
            $sql = "SELECT COUNT(DISTINCT order_id) as total_orders FROM orders WHERE status != 'cancelled'";
            $result = $this->db->getConnection()->query($sql);
            if (!$result) {
                throw new Exception("Query failed: " . $this->db->getConnection()->error);
            }
            $totalOrders = $result->fetch_assoc()['total_orders'];
            
            // Get product pairs that appear together
            $sql = "SELECT 
                        od1.product_id as product1_id,
                        p1.product_name as product1_name,
                        od2.product_id as product2_id,
                        p2.product_name as product2_name,
                        COUNT(DISTINCT od1.order_id) as support_count
                    FROM order_details od1
                    JOIN order_details od2 ON od1.order_id = od2.order_id AND od1.product_id < od2.product_id
                    JOIN products p1 ON od1.product_id = p1.product_id
                    JOIN products p2 ON od2.product_id = p2.product_id
                    JOIN orders o ON od1.order_id = o.order_id
                    WHERE o.status != 'cancelled'
                    GROUP BY od1.product_id, od2.product_id
                    HAVING support_count >= ? * ?
                    ORDER BY support_count DESC";
            
            $minSupportCount = $minSupport * $totalOrders;
            $stmt = $this->db->getConnection()->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('dd', $minSupportCount, $totalOrders);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $pairs = $stmt->get_result();
            $associations = [];
            
            while ($pair = $pairs->fetch_assoc()) {
                // Calculate individual product counts
                $sql = "SELECT COUNT(DISTINCT order_id) as count 
                       FROM order_details 
                       WHERE product_id = ? AND order_id IN (SELECT order_id FROM orders WHERE status != 'cancelled')";
                
                $stmt = $this->db->getConnection()->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $this->db->getConnection()->error);
                }
                
                // Product1 -> Product2
                $stmt->bind_param('i', $pair['product1_id']);
                $stmt->execute();
                $count1 = $stmt->get_result()->fetch_assoc()['count'];
                $confidence1 = $pair['support_count'] / $count1;
                
                // Product2 -> Product1
                $stmt->bind_param('i', $pair['product2_id']);
                $stmt->execute();
                $count2 = $stmt->get_result()->fetch_assoc()['count'];
                $confidence2 = $pair['support_count'] / $count2;
                
                if ($confidence1 >= $minConfidence || $confidence2 >= $minConfidence) {
                    $associations[] = [
                        'product1_id' => $pair['product1_id'],
                        'product1_name' => $pair['product1_name'],
                        'product2_id' => $pair['product2_id'],
                        'product2_name' => $pair['product2_name'],
                        'support' => $pair['support_count'] / $totalOrders,
                        'confidence1' => $confidence1,
                        'confidence2' => $confidence2
                    ];
                }
            }
            
            return $associations;
            
        } catch (Exception $e) {
            error_log("Error in getProductAssociations: " . $e->getMessage());
            return null;
        }
    }
    
    public function getCustomerSegments(): ?mysqli_result {
        $sql = "SELECT 
                    c.customer_id,
                    c.first_name,
                    c.last_name,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(o.total_amount) as total_spent,
                    AVG(o.total_amount) as avg_order_value,
                    MAX(o.order_date) as last_order_date,
                    DATEDIFF(NOW(), MIN(o.order_date)) as days_since_first_order
                FROM customers c
                JOIN orders o ON c.customer_id = o.customer_id
                WHERE o.status != 'cancelled'
                GROUP BY c.customer_id";
        
        try {
            $result = $this->db->getConnection()->query($sql);
            if (!$result) {
                throw new Exception("Query failed: " . $this->db->getConnection()->error);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Error in getCustomerSegments: " . $e->getMessage());
            return null;
        }
    }
    
    public function getCategoryPerformance(): ?mysqli_result {
        $sql = "SELECT 
                    c.category_id,
                    c.category_name,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(od.quantity) as units_sold,
                    SUM(od.subtotal) as revenue
                FROM categories c
                JOIN products p ON c.category_id = p.category_id
                JOIN order_details od ON p.product_id = od.product_id
                JOIN orders o ON od.order_id = o.order_id
                WHERE o.status != 'cancelled'
                GROUP BY c.category_id
                ORDER BY revenue DESC";
        
        try {
            $result = $this->db->getConnection()->query($sql);
            if (!$result) {
                throw new Exception("Query failed: " . $this->db->getConnection()->error);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Error in getCategoryPerformance: " . $e->getMessage());
            return null;
        }
    }
    
    public function getCampaignEffectiveness(): ?mysqli_result {
        $sql = "SELECT 
                    mc.campaign_id,
                    mc.campaign_name,
                    mc.description,
                    mc.start_date,
                    mc.end_date,
                    mc.status,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(o.total_amount) as revenue,
                    AVG(o.total_amount) as avg_order_value
                FROM marketing_campaigns mc
                LEFT JOIN orders o ON o.order_date BETWEEN mc.start_date AND mc.end_date
                    AND o.status != 'cancelled'
                GROUP BY mc.campaign_id, mc.campaign_name, mc.description, 
                         mc.start_date, mc.end_date, mc.status
                ORDER BY mc.start_date DESC";
        
        try {
            $result = $this->db->getConnection()->query($sql);
            if (!$result) {
                throw new Exception("Query failed: " . $this->db->getConnection()->error);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Error in getCampaignEffectiveness: " . $e->getMessage());
            return null;
        }
    }
}
?>