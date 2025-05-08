<?php
// classes/Category.php

class Category {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllCategories() {
        $sql = "SELECT c.*, COUNT(p.product_id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.category_id = p.category_id 
                GROUP BY c.category_id, c.category_name, c.description, c.created_at, c.updated_at 
                ORDER BY c.category_name";
        return $this->db->query($sql);
    }
    
    public function getCategory($id) {
        $id = (int)$this->db->escape($id);
        $sql = "SELECT * FROM categories WHERE category_id = $id";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_assoc() : false;
    }
    
    public function addCategory($data) {
        $category_name = $this->db->escape($data['category_name']);
        $description = $this->db->escape($data['description']);
        
        $sql = "INSERT INTO categories (category_name, description) 
                VALUES ('$category_name', '$description')";
        
        return $this->db->query($sql);
    }
    
    public function updateCategory($id, $data) {
        // Debug
        error_log("Updating category. ID: $id, Data: " . print_r($data, true));
        
        // Validate input
        if (empty($id) || empty($data['category_name'])) {
            error_log("Invalid input data for category update");
            return false;
        }
        
        $id = (int)$this->db->escape($id);
        $category_name = $this->db->escape($data['category_name']);
        $description = $this->db->escape($data['description']);
        
        $sql = "UPDATE categories 
                SET category_name = '$category_name',
                    description = '$description',
                    updated_at = CURRENT_TIMESTAMP
                WHERE category_id = $id";
        
        // Debug
        error_log("Update SQL: $sql");
        
        $result = $this->db->query($sql);
        
        // Debug
        if (!$result) {
            error_log("Update failed. MySQL Error: " . mysqli_error($this->db->getConnection()));
        }
        
        return $result;
    }
    
    public function deleteCategory($id) {
        $id = (int)$this->db->escape($id);
        
        // First check if category has products
        $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = $id";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            return false; // Cannot delete category with products
        }
        
        $sql = "DELETE FROM categories WHERE category_id = $id";
        return $this->db->query($sql);
    }
}
?>