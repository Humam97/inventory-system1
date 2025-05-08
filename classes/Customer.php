<?php
// classes/Customer.php

class Customer {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllCustomers() {
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT o.order_id) as total_orders,
                       SUM(o.total_amount) as total_spent
                FROM customers c 
                LEFT JOIN orders o ON c.customer_id = o.customer_id 
                GROUP BY c.customer_id
                ORDER BY c.first_name, c.last_name";
        return $this->db->query($sql);
    }
    
    public function getCustomer($id) {
        $id = (int)$this->db->escape($id);
        
        // Get customer details
        $sql = "SELECT * FROM customers WHERE customer_id = $id";
        $result = $this->db->query($sql);
        $customer = $result->fetch_assoc();
        
        if (!$customer) return false;
        
        // Get customer's orders
        $sql = "SELECT * FROM orders WHERE customer_id = $id ORDER BY order_date DESC";
        $result = $this->db->query($sql);
        $customer['orders'] = [];
        while ($row = $result->fetch_assoc()) {
            $customer['orders'][] = $row;
        }
        
        return $customer;
    }
    
    public function addCustomer($data) {
        $first_name = $this->db->escape($data['first_name']);
        $last_name = $this->db->escape($data['last_name']);
        $email = $this->db->escape($data['email']);
        $phone = $this->db->escape($data['phone']);
        $address = $this->db->escape($data['address']);
        
        // Check if email already exists
        $sql = "SELECT customer_id FROM customers WHERE email = '$email'";
        $result = $this->db->query($sql);
        if ($result->num_rows > 0) {
            return false; // Email already exists
        }
        
        $sql = "INSERT INTO customers (first_name, last_name, email, phone, address) 
                VALUES ('$first_name', '$last_name', '$email', '$phone', '$address')";
        
        return $this->db->query($sql);
    }
    
    public function updateCustomer($id, $data): bool {
        $id = (int)$this->db->escape($id);
        $first_name = $this->db->escape($data['first_name']);
        $last_name = $this->db->escape($data['last_name']);
        $email = $this->db->escape($data['email']);
        $phone = $this->db->escape($data['phone']);
        $address = $this->db->escape($data['address']);
        
        // Check if email already exists for another customer
        $sql = "SELECT customer_id FROM customers 
                WHERE email = '$email' AND customer_id != $id";
        
        $result = $this->db->query($sql);
        if (!$result) {
            return false;
        }
        
        if ($result->num_rows > 0) {
            return false; // Email already exists
        }
        
        $sql = "UPDATE customers 
                SET first_name = '$first_name',
                    last_name = '$last_name',
                    email = '$email',
                    phone = '$phone',
                    address = '$address',
                    updated_at = CURRENT_TIMESTAMP
                WHERE customer_id = $id";
        
        return (bool)$this->db->query($sql);
    }
    
    public function deleteCustomer($id) {
        $id = (int)$this->db->escape($id);
        
        // Check if customer has orders
        $sql = "SELECT COUNT(*) as count FROM orders WHERE customer_id = $id";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            return false; // Cannot delete customer with orders
        }
        
        $sql = "DELETE FROM customers WHERE customer_id = $id";
        return $this->db->query($sql);
    }
    
    public function getCustomerStats($id) {
        $id = (int)$this->db->escape($id);
        
        $sql = "SELECT 
                    COUNT(o.order_id) as total_orders,
                    SUM(o.total_amount) as total_spent,
                    AVG(o.total_amount) as average_order_value,
                    MAX(o.order_date) as last_order_date
                FROM customers c
                LEFT JOIN orders o ON c.customer_id = o.customer_id
                WHERE c.customer_id = $id
                GROUP BY c.customer_id";
        
        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }
}
?>