<?php
// classes/Search.php

class Search {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function globalSearch($query): array {
        $query = '%' . $this->db->escape($query) . '%';
        $results = [];
        
        try {
            // Search Products
            $sql = "SELECT 
                        'product' as type,
                        product_id as id,
                        product_name as title,
                        sku as subtitle,
                        CONCAT('Stock: ', stock_quantity) as details,
                        CONCAT('/inventory_system/modules/products/edit.php?id=', product_id) as link
                    FROM products 
                    WHERE product_name LIKE ? OR sku LIKE ?";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bind_param('ss', $query, $query);
            $stmt->execute();
            $results['products'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Search Customers
            $sql = "SELECT 
                        'customer' as type,
                        customer_id as id,
                        CONCAT(first_name, ' ', last_name) as title,
                        email as subtitle,
                        phone as details,
                        CONCAT('/inventory_system/modules/customers/view.php?id=', customer_id) as link
                    FROM customers 
                    WHERE CONCAT(first_name, ' ', last_name) LIKE ? 
                        OR email LIKE ? 
                        OR phone LIKE ?";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bind_param('sss', $query, $query, $query);
            $stmt->execute();
            $results['customers'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Search Orders
            $sql = "SELECT 
                        'order' as type,
                        o.order_id as id,
                        CONCAT('Order #', o.order_id) as title,
                        CONCAT(c.first_name, ' ', c.last_name) as subtitle,
                        CONCAT('$', o.total_amount, ' - ', o.status) as details,
                        CONCAT('/inventory_system/modules/orders/view.php?id=', o.order_id) as link
                    FROM orders o
                    LEFT JOIN customers c ON o.customer_id = c.customer_id
                    WHERE o.order_id LIKE ? 
                        OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bind_param('ss', $query, $query);
            $stmt->execute();
            $results['orders'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Search Campaigns
            $sql = "SELECT 
                        'campaign' as type,
                        campaign_id as id,
                        campaign_name as title,
                        status as subtitle,
                        CONCAT(DATE_FORMAT(start_date, '%Y-%m-%d'), ' to ', DATE_FORMAT(end_date, '%Y-%m-%d')) as details,
                        CONCAT('/inventory_system/modules/campaigns/view.php?id=', campaign_id) as link
                    FROM marketing_campaigns 
                    WHERE campaign_name LIKE ?";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bind_param('s', $query);
            $stmt->execute();
            $results['campaigns'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return [];
        }
    }
}
?>