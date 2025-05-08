<?php
// classes/Campaign.php

class Campaign {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllCampaigns(): ?mysqli_result {
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT cp.product_id) as total_products,
                       SUM(o.total_amount) as revenue
                FROM marketing_campaigns c
                LEFT JOIN campaign_products cp ON c.campaign_id = cp.campaign_id
                LEFT JOIN orders o ON o.order_date BETWEEN c.start_date AND c.end_date
                GROUP BY c.campaign_id
                ORDER BY c.start_date DESC";
        return $this->db->query($sql);
    }
    
    public function getCampaign(int $id): ?array {
        $id = (int)$this->db->escape($id);
        
        // Get campaign details
        $sql = "SELECT * FROM marketing_campaigns WHERE campaign_id = $id";
        $result = $this->db->query($sql);
        if (!$result) return null;
        
        $campaign = $result->fetch_assoc();
        if (!$campaign) return null;
        
        // Get campaign products
        $sql = "SELECT cp.*, p.product_name, p.sku, p.unit_price 
                FROM campaign_products cp
                JOIN products p ON cp.product_id = p.product_id
                WHERE cp.campaign_id = $id";
        $result = $this->db->query($sql);
        
        $campaign['products'] = [];
        while ($row = $result->fetch_assoc()) {
            $campaign['products'][] = $row;
        }
        
        return $campaign;
    }
    
    public function addCampaign(array $data): bool {
        try {
            $this->db->beginTransaction();
            
            $name = $this->db->escape($data['campaign_name']);
            $description = $this->db->escape($data['description']);
            $start_date = $this->db->escape($data['start_date']);
            $end_date = $this->db->escape($data['end_date']);
            $status = $this->db->escape($data['status']);
            
            $sql = "INSERT INTO marketing_campaigns 
                    (campaign_name, description, start_date, end_date, status) 
                    VALUES 
                    ('$name', '$description', '$start_date', '$end_date', '$status')";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Error creating campaign");
            }
            
            $campaign_id = $this->db->lastInsertId();
            
            // Add campaign products
            if (!empty($data['products'])) {
                foreach ($data['products'] as $product) {
                    $product_id = (int)$this->db->escape($product['product_id']);
                    $discount = (float)$this->db->escape($product['discount_percentage']);
                    
                    $sql = "INSERT INTO campaign_products 
                            (campaign_id, product_id, discount_percentage) 
                            VALUES 
                            ($campaign_id, $product_id, $discount)";
                    
                    if (!$this->db->query($sql)) {
                        throw new Exception("Error adding campaign products");
                    }
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Campaign creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateCampaign(int $id, array $data): bool {
        try {
            $this->db->beginTransaction();
            
            $id = (int)$this->db->escape($id);
            $name = $this->db->escape($data['campaign_name']);
            $description = $this->db->escape($data['description']);
            $start_date = $this->db->escape($data['start_date']);
            $end_date = $this->db->escape($data['end_date']);
            $status = $this->db->escape($data['status']);
            
            $sql = "UPDATE marketing_campaigns 
                    SET campaign_name = '$name',
                        description = '$description',
                        start_date = '$start_date',
                        end_date = '$end_date',
                        status = '$status'
                    WHERE campaign_id = $id";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Error updating campaign");
            }
            
            // Remove existing campaign products
            $sql = "DELETE FROM campaign_products WHERE campaign_id = $id";
            if (!$this->db->query($sql)) {
                throw new Exception("Error removing old campaign products");
            }
            
            // Add updated campaign products
            if (!empty($data['products'])) {
                foreach ($data['products'] as $product) {
                    $product_id = (int)$this->db->escape($product['product_id']);
                    $discount = (float)$this->db->escape($product['discount_percentage']);
                    
                    $sql = "INSERT INTO campaign_products 
                            (campaign_id, product_id, discount_percentage) 
                            VALUES 
                            ($id, $product_id, $discount)";
                    
                    if (!$this->db->query($sql)) {
                        throw new Exception("Error updating campaign products");
                    }
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Campaign update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteCampaign(int $id): bool {
        try {
            $this->db->beginTransaction();
            
            $id = (int)$this->db->escape($id);
            
            // Delete campaign products first (foreign key constraint)
            $sql = "DELETE FROM campaign_products WHERE campaign_id = $id";
            if (!$this->db->query($sql)) {
                throw new Exception("Error deleting campaign products");
            }
            
            // Delete campaign
            $sql = "DELETE FROM marketing_campaigns WHERE campaign_id = $id";
            if (!$this->db->query($sql)) {
                throw new Exception("Error deleting campaign");
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Campaign deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCampaignStats(int $id): ?array {
        $id = (int)$this->db->escape($id);
        
        $sql = "SELECT 
                    COUNT(DISTINCT o.order_id) as total_orders,
                    SUM(o.total_amount) as total_revenue,
                    AVG(o.total_amount) as average_order_value
                FROM marketing_campaigns c
                LEFT JOIN orders o ON o.order_date BETWEEN c.start_date AND c.end_date
                WHERE c.campaign_id = $id";
        
        $result = $this->db->query($sql);
        return $result ? $result->fetch_assoc() : null;
    }
}
?>