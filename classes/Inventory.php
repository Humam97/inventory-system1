<?php
// classes/Inventory.php

class Inventory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllMovements() {
        $sql = "SELECT m.*, 
                       p.product_name, 
                       p.sku,
                       p.stock_quantity as current_stock
                FROM inventory_movements m 
                LEFT JOIN products p ON m.product_id = p.product_id 
                ORDER BY m.movement_date DESC";
        return $this->db->query($sql);
    }
    
    public function addMovement($data) {
        try {
            $this->db->beginTransaction();
            
            $product_id = (int)$this->db->escape($data['product_id']);
            $movement_type = $this->db->escape($data['movement_type']);
            $quantity = (int)$this->db->escape($data['quantity']);
            $reference_id = $this->db->escape($data['reference_id']);
            $notes = $this->db->escape($data['notes'] ?? '');
            
            // Update product stock
            $sign = ($movement_type == 'in') ? '+' : '-';
            $sql = "UPDATE products 
                    SET stock_quantity = stock_quantity $sign $quantity 
                    WHERE product_id = $product_id";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Error updating product stock");
            }
            
            // Record movement
            $sql = "INSERT INTO inventory_movements 
                    (product_id, movement_type, quantity, reference_id, notes) 
                    VALUES 
                    ($product_id, '$movement_type', $quantity, '$reference_id', '$notes')";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Error recording inventory movement");
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Inventory movement error: " . $e->getMessage());
            return false;
        }
    }
    
   
public function getLowStockProducts() {
    $sql = "SELECT p.*, 
                   c.category_name,
                   p.stock_quantity as current_stock,
                   p.reorder_level,
                   (
                       SELECT COUNT(*) 
                       FROM order_details od 
                       JOIN orders o ON od.order_id = o.order_id 
                       WHERE od.product_id = p.product_id 
                       AND o.status != 'cancelled'
                   ) as times_ordered
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.stock_quantity <= p.reorder_level
                AND p.reorder_level > 0  -- Only show products with valid reorder levels
            ORDER BY (p.stock_quantity / p.reorder_level) ASC";
            
    error_log("Low stock query: " . $sql); // Debug log
    $result = $this->db->query($sql);
    error_log("Low stock products found: " . $result->num_rows); // Debug log
    return $result;
}
    
    public function getProductMovements($product_id) {
        $product_id = (int)$this->db->escape($product_id);
        $sql = "SELECT m.*, p.product_name, p.sku 
                FROM inventory_movements m
                LEFT JOIN products p ON m.product_id = p.product_id
                WHERE m.product_id = $product_id 
                ORDER BY m.movement_date DESC";
        return $this->db->query($sql);
    }
    
    public function getInventoryValue() {
        $sql = "SELECT 
                    SUM(stock_quantity * unit_price) as total_value,
                    COUNT(*) as total_products,
                    SUM(stock_quantity) as total_units
                FROM products";
        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }
    
    public function getMovementStats() {
        $sql = "SELECT 
                    movement_type,
                    COUNT(*) as movement_count,
                    SUM(quantity) as total_quantity
                FROM inventory_movements
                WHERE movement_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY movement_type";
        return $this->db->query($sql);
    }

    public function updateMovement($movement_id, $data) {
        try {
            $this->db->beginTransaction();
            
            // Get the original movement
            $sql = "SELECT * FROM inventory_movements WHERE movement_id = $movement_id";
            $result = $this->db->query($sql);
            $original = $result->fetch_assoc();
            
            // Reverse the original movement
            $reverse_sign = ($original['movement_type'] == 'in') ? '-' : '+';
            $sql = "UPDATE products 
                    SET stock_quantity = stock_quantity $reverse_sign {$original['quantity']}
                    WHERE product_id = {$original['product_id']}";
            if (!$this->db->query($sql)) {
                throw new Exception("Error reversing original movement");
            }
            
            // Apply the new movement
            $sign = ($data['movement_type'] == 'in') ? '+' : '-';
            $sql = "UPDATE products 
                    SET stock_quantity = stock_quantity $sign {$data['quantity']}
                    WHERE product_id = {$data['product_id']}";
            if (!$this->db->query($sql)) {
                throw new Exception("Error applying new movement");
            }
            
            // Update the movement record
            $sql = "UPDATE inventory_movements 
                    SET product_id = {$data['product_id']},
                        movement_type = '{$data['movement_type']}',
                        quantity = {$data['quantity']},
                        reference_id = '{$data['reference_id']}',
                        notes = '{$data['notes']}'
                    WHERE movement_id = $movement_id";
            if (!$this->db->query($sql)) {
                throw new Exception("Error updating movement record");
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error updating movement: " . $e->getMessage());
            return false;
        }
    }

    public function deleteMovement($movement_id) {
        try {
            $this->db->beginTransaction();
            
            // Get the movement details
            $sql = "SELECT * FROM inventory_movements WHERE movement_id = $movement_id";
            $result = $this->db->query($sql);
            $movement = $result->fetch_assoc();
            
            // Reverse the movement in stock
            $sign = ($movement['movement_type'] == 'in') ? '-' : '+';
            $sql = "UPDATE products 
                    SET stock_quantity = stock_quantity $sign {$movement['quantity']}
                    WHERE product_id = {$movement['product_id']}";
            if (!$this->db->query($sql)) {
                throw new Exception("Error reversing movement");
            }
            
            // Delete the movement record
            $sql = "DELETE FROM inventory_movements WHERE movement_id = $movement_id";
            if (!$this->db->query($sql)) {
                throw new Exception("Error deleting movement record");
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error deleting movement: " . $e->getMessage());
            return false;
        }
    }
}
?>