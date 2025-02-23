<?php
// classes/Product.php

class Product {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllProducts() {
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                ORDER BY p.product_name";
        return $this->db->query($sql);
    }
    
    public function getProduct($id) {
        $id = $this->db->escape($id);
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE p.product_id = '$id'";
        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }
    
    public function addProduct($data) {
        $category_id = $this->db->escape($data['category_id']);
        $product_name = $this->db->escape($data['product_name']);
        $description = $this->db->escape($data['description']);
        $sku = $this->db->escape($data['sku']);
        $unit_price = $this->db->escape($data['unit_price']);
        $stock_quantity = $this->db->escape($data['stock_quantity']);
        $reorder_level = $this->db->escape($data['reorder_level']);
        
        $sql = "INSERT INTO products (category_id, product_name, description, sku, unit_price, stock_quantity, reorder_level) 
                VALUES ('$category_id', '$product_name', '$description', '$sku', '$unit_price', '$stock_quantity', '$reorder_level')";
        
        return $this->db->query($sql);
    }
    
    public function updateProduct($id, $data) {
        $id = $this->db->escape($id);
        $category_id = $this->db->escape($data['category_id']);
        $product_name = $this->db->escape($data['product_name']);
        $description = $this->db->escape($data['description']);
        $sku = $this->db->escape($data['sku']);
        $unit_price = $this->db->escape($data['unit_price']);
        $stock_quantity = $this->db->escape($data['stock_quantity']);
        $reorder_level = $this->db->escape($data['reorder_level']);
        
        $sql = "UPDATE products 
                SET category_id = '$category_id',
                    product_name = '$product_name',
                    description = '$description',
                    sku = '$sku',
                    unit_price = '$unit_price',
                    stock_quantity = '$stock_quantity',
                    reorder_level = '$reorder_level'
                WHERE product_id = '$id'";
        
        return $this->db->query($sql);
    }
    
    public function deleteProduct($id) {
        $id = $this->db->escape($id);
        $sql = "DELETE FROM products WHERE product_id = '$id'";
        return $this->db->query($sql);
    }
    
    public function getLowStockProducts() {
        $sql = "SELECT * FROM products WHERE stock_quantity <= reorder_level";
        return $this->db->query($sql);
    }
}
?>