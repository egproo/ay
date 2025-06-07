<?php
class ModelPosInventory extends Model {
    
    public function getProductInventory($product_id, $branch_id, $unit_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_inventory 
            WHERE product_id = '" . (int)$product_id . "' 
            AND branch_id = '" . (int)$branch_id . "' 
            AND unit_id = '" . (int)$unit_id . "'");
        
        return $query->row;
    }
    
    public function updateProductQuantity($product_id, $branch_id, $unit_id, $quantity) {
        $this->db->query("UPDATE " . DB_PREFIX . "product_inventory SET 
            quantity = '" . (float)$quantity . "',
            quantity_available = '" . (float)$quantity . "' 
            WHERE product_id = '" . (int)$product_id . "' 
            AND branch_id = '" . (int)$branch_id . "' 
            AND unit_id = '" . (int)$unit_id . "'");
    }
    
    public function addProductMovement($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_movement SET 
            product_id = '" . (int)$data['product_id'] . "',
            type = '" . $this->db->escape($data['type']) . "',
            movement_reference_type = '" . $this->db->escape($data['movement_reference_type']) . "',
            movement_reference_id = '" . (int)$data['movement_reference_id'] . "',
            date_added = NOW(),
            quantity = '" . (float)$data['quantity'] . "',
            unit_cost = '" . (float)$data['unit_cost'] . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            branch_id = '" . (int)$data['branch_id'] . "',
            reference = '" . $this->db->escape($data['reference']) . "',
            old_average_cost = '" . (float)$data['old_average_cost'] . "',
            new_average_cost = '" . (float)$data['new_average_cost'] . "',
            user_id = '" . (int)$data['user_id'] . "',
            effect_on_cost = '" . $this->db->escape($data['effect_on_cost']) . "'");
        
        return $this->db->getLastId();
    }
    
    public function addInventoryValuation($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_valuation SET 
            product_id = '" . (int)$data['product_id'] . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            branch_id = '" . (int)$data['branch_id'] . "',
            valuation_date = '" . $this->db->escape($data['valuation_date']) . "',
            average_cost = '" . (float)$data['average_cost'] . "',
            quantity = '" . (float)$data['quantity'] . "',
            total_value = '" . (float)$data['total_value'] . "',
            date_added = NOW(),
            transaction_reference_id = '" . (int)$data['transaction_reference_id'] . "',
            transaction_type = '" . $this->db->escape($data['transaction_type']) . "',
            previous_quantity = '" . (float)$data['previous_quantity'] . "',
            previous_cost = '" . (float)$data['previous_cost'] . "',
            movement_quantity = '" . (float)$data['movement_quantity'] . "',
            movement_cost = '" . (float)$data['movement_cost'] . "'");
        
        return $this->db->getLastId();
    }
    
    public function updateProductCost($product_id, $unit_id, $new_cost, $reason = 'sale') {
        // تحديث متوسط التكلفة للمنتج
        $this->db->query("UPDATE " . DB_PREFIX . "product SET 
            average_cost = '" . (float)$new_cost . "' 
            WHERE product_id = '" . (int)$product_id . "'");
        
        // تسجيل في تاريخ التكلفة
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_cost_history SET 
            product_id = '" . (int)$product_id . "',
            unit_id = '" . (int)$unit_id . "',
            old_cost = '" . (float)$old_cost . "',
            new_cost = '" . (float)$new_cost . "',
            change_reason = '" . $this->db->escape($reason) . "',
            notes = 'تحديث التكلفة من خلال نقطة البيع',
            user_id = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
    }
    
    public function calculateAverageCost($product_id, $unit_id, $branch_id) {
        // استرداد آخر قيود التقييم للمنتج
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "inventory_valuation 
            WHERE product_id = '" . (int)$product_id . "' 
            AND unit_id = '" . (int)$unit_id . "' 
            AND branch_id = '" . (int)$branch_id . "' 
            ORDER BY valuation_id DESC LIMIT 1");
        
        if ($query->num_rows) {
            return $query->row['average_cost'];
        }
        
        // إذا لم يكن هناك قيود، نعيد التكلفة من جدول المنتج
        $query = $this->db->query("SELECT average_cost FROM " . DB_PREFIX . "product 
            WHERE product_id = '" . (int)$product_id . "'");
        
        return $query->row['average_cost'] ?? 0;
    }
}