<?php
// classes/Order.php

class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllOrders() {
        $sql = "SELECT o.*, 
                       c.first_name, 
                       c.last_name,
                       COUNT(od.order_detail_id) as total_items,
                       SUM(od.quantity) as total_quantity
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                LEFT JOIN order_details od ON o.order_id = od.order_id
                GROUP BY o.order_id
                ORDER BY o.order_date DESC";
        return $this->db->query($sql);
    }

    
    
    public function getOrder($id) {
        $id = (int)$this->db->escape($id);
        
        // Get order header
        $sql = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                WHERE o.order_id = $id";
        
        $result = $this->db->query($sql);
        $order = $result->fetch_assoc();
        
        if (!$order) return false;
        
        // Get order details
        $sql = "SELECT od.*, p.product_name, p.sku
                FROM order_details od
                LEFT JOIN products p ON od.product_id = p.product_id
                WHERE od.order_id = $id";
        
        $result = $this->db->query($sql);
        $order['details'] = [];
        while ($row = $result->fetch_assoc()) {
            $order['details'][] = $row;
        }
        
        return $order;
    }
    
    public function createOrder($customerData, $orderItems) {
        try {
            $this->db->beginTransaction();
            
            // Insert or update customer
            $customer_id = $this->getOrCreateCustomer($customerData);
            
            // Calculate total amount
            $total_amount = 0;
            foreach ($orderItems as $item) {
                $total_amount += $item['quantity'] * $item['unit_price'];
            }
            
            // Create order
            $sql = "INSERT INTO orders (customer_id, total_amount, status) 
                    VALUES ($customer_id, $total_amount, 'pending')";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Error creating order");
            }
            
            $order_id = $this->db->lastInsertId();
            
            // Insert order details
            foreach ($orderItems as $item) {
                $product_id = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                $unit_price = (float)$item['unit_price'];
                $subtotal = $quantity * $unit_price;
                
                $sql = "INSERT INTO order_details (order_id, product_id, quantity, unit_price, subtotal)
                        VALUES ($order_id, $product_id, $quantity, $unit_price, $subtotal)";
                
                if (!$this->db->query($sql)) {
                    throw new Exception("Error adding order details");
                }
                
                // Update inventory
                $sql = "UPDATE products 
                        SET stock_quantity = stock_quantity - $quantity 
                        WHERE product_id = $product_id";
                
                if (!$this->db->query($sql)) {
                    throw new Exception("Error updating inventory");
                }
            }
            
            $this->db->commit();
            return $order_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Order creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateOrderStatus($orderId, $status) {
        $orderId = (int)$this->db->escape($orderId);
        $status = $this->db->escape($status);
        
        $sql = "UPDATE orders SET status = '$status' WHERE order_id = $orderId";
        return $this->db->query($sql);
    }
    
    private function getOrCreateCustomer($customerData) {
        $email = $this->db->escape($customerData['email']);
        
        // Check if customer exists
        $sql = "SELECT customer_id FROM customers WHERE email = '$email'";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            return $customer['customer_id'];
        }
        
        // Create new customer
        $first_name = $this->db->escape($customerData['first_name']);
        $last_name = $this->db->escape($customerData['last_name']);
        $phone = $this->db->escape($customerData['phone']);
        $address = $this->db->escape($customerData['address']);
        
        $sql = "INSERT INTO customers (first_name, last_name, email, phone, address)
                VALUES ('$first_name', '$last_name', '$email', '$phone', '$address')";
        
        if ($this->db->query($sql)) {
            return $this->db->lastInsertId();
        }
        
        throw new Exception("Error creating customer");
    }
}
?>