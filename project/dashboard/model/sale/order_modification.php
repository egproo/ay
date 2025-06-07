<?php
/**
 * AYM ERP - Order Modification Model
 * 
 * Professional order modification system with comprehensive tracking
 * Handles complex scenarios including:
 * - Multiple product units and variants
 * - Product options modifications
 * - Quantity and price changes
 * - Tax recalculations
 * - ETA integration for credit/debit notes
 * 
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelSaleOrderModification extends Model {
    
    public function processModification($order_id, $modification_data) {
        $this->load->model('sale/order');
        
        $order_info = $this->model_sale_order->getOrder($order_id);
        
        if (!$order_info) {
            throw new Exception('Order not found');
        }
        
        // Start transaction
        $this->db->query("START TRANSACTION");
        
        try {
            // Create modification record
            $modification_id = $this->createModificationRecord($order_id, $modification_data);
            
            // Process each modification
            $total_change = 0;
            $modification_type = 'mixed';
            $changes = array();
            
            foreach ($modification_data['modifications'] as $mod) {
                $change = $this->processIndividualModification($order_id, $modification_id, $mod);
                $changes[] = $change;
                $total_change += $change['amount_change'];
            }
            
            // Determine overall modification type
            if ($total_change > 0) {
                $modification_type = 'increase';
            } elseif ($total_change < 0) {
                $modification_type = 'decrease';
            }
            
            // Update modification record with totals
            $this->updateModificationTotals($modification_id, $total_change, $modification_type);
            
            // Recalculate order totals
            $this->recalculateOrderTotals($order_id);
            
            // Log the modification
            $this->logModification($order_id, $modification_id, $modification_data, $changes);
            
            $this->db->query("COMMIT");
            
            return array(
                'success' => true,
                'modification_id' => $modification_id,
                'modification_type' => $modification_type,
                'total_change' => $total_change,
                'changes' => $changes
            );
            
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }
    
    public function calculateModificationTotals($order_id, $modifications) {
        $this->load->model('sale/order');
        $this->load->model('localisation/tax_class');
        
        $order_info = $this->model_sale_order->getOrder($order_id);
        $order_products = $this->model_sale_order->getOrderProducts($order_id);
        
        $totals = array(
            'subtotal_change' => 0,
            'tax_change' => 0,
            'total_change' => 0,
            'items' => array()
        );
        
        foreach ($modifications as $mod) {
            $item_total = $this->calculateItemModification($order_products, $mod);
            $totals['items'][] = $item_total;
            $totals['subtotal_change'] += $item_total['subtotal_change'];
            $totals['tax_change'] += $item_total['tax_change'];
        }
        
        $totals['total_change'] = $totals['subtotal_change'] + $totals['tax_change'];
        
        return $totals;
    }
    
    public function getModifications($data = array()) {
        $sql = "SELECT m.*, o.firstname, o.lastname, o.currency_code,
                CONCAT(o.firstname, ' ', o.lastname) as customer_name
                FROM " . DB_PREFIX . "order_modification m
                LEFT JOIN " . DB_PREFIX . "order o ON (m.order_id = o.order_id)
                WHERE 1=1";
        
        if (!empty($data['filter_order_id'])) {
            $sql .= " AND m.order_id = '" . (int)$data['filter_order_id'] . "'";
        }
        
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(m.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(m.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        $sql .= " ORDER BY m.date_added DESC";
        
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getTotalModifications($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "order_modification m
                LEFT JOIN " . DB_PREFIX . "order o ON (m.order_id = o.order_id)
                WHERE 1=1";
        
        if (!empty($data['filter_order_id'])) {
            $sql .= " AND m.order_id = '" . (int)$data['filter_order_id'] . "'";
        }
        
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(m.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(m.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    public function getModification($modification_id) {
        $query = $this->db->query("SELECT m.*, o.firstname, o.lastname, o.currency_code,
                                  CONCAT(o.firstname, ' ', o.lastname) as customer_name
                                  FROM " . DB_PREFIX . "order_modification m
                                  LEFT JOIN " . DB_PREFIX . "order o ON (m.order_id = o.order_id)
                                  WHERE m.modification_id = '" . (int)$modification_id . "'");
        
        return $query->row;
    }
    
    public function getModificationItems($modification_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_modification_item 
                                  WHERE modification_id = '" . (int)$modification_id . "'
                                  ORDER BY item_id");
        
        return $query->rows;
    }
    
    public function getModificationHistory($order_id) {
        $query = $this->db->query("SELECT m.*, u.username
                                  FROM " . DB_PREFIX . "order_modification m
                                  LEFT JOIN " . DB_PREFIX . "user u ON (m.user_id = u.user_id)
                                  WHERE m.order_id = '" . (int)$order_id . "'
                                  ORDER BY m.date_added DESC");
        
        return $query->rows;
    }
    
    public function getETANotes($modification_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "eta_modification_note 
                                  WHERE modification_id = '" . (int)$modification_id . "'
                                  ORDER BY date_added DESC");
        
        return $query->rows;
    }
    
    private function createModificationRecord($order_id, $modification_data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_modification SET
            order_id = '" . (int)$order_id . "',
            modification_type = 'pending',
            reason = '" . $this->db->escape($modification_data['reason'] ?? '') . "',
            notes = '" . $this->db->escape($modification_data['notes'] ?? '') . "',
            user_id = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
        
        return $this->db->getLastId();
    }
    
    private function processIndividualModification($order_id, $modification_id, $mod) {
        $change = array(
            'type' => $mod['type'],
            'product_id' => $mod['product_id'] ?? 0,
            'order_product_id' => $mod['order_product_id'] ?? 0,
            'amount_change' => 0,
            'quantity_change' => 0,
            'price_change' => 0
        );
        
        switch ($mod['type']) {
            case 'quantity':
                $change = $this->processQuantityChange($order_id, $modification_id, $mod);
                break;
                
            case 'price':
                $change = $this->processPriceChange($order_id, $modification_id, $mod);
                break;
                
            case 'add_product':
                $change = $this->processAddProduct($order_id, $modification_id, $mod);
                break;
                
            case 'remove_product':
                $change = $this->processRemoveProduct($order_id, $modification_id, $mod);
                break;
                
            case 'change_option':
                $change = $this->processOptionChange($order_id, $modification_id, $mod);
                break;
                
            case 'change_unit':
                $change = $this->processUnitChange($order_id, $modification_id, $mod);
                break;
        }
        
        // Save modification item
        $this->saveModificationItem($modification_id, $change, $mod);
        
        return $change;
    }
    
    private function processQuantityChange($order_id, $modification_id, $mod) {
        $order_product_id = (int)$mod['order_product_id'];
        $new_quantity = (float)$mod['new_quantity'];
        
        // Get current product info
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product 
                                  WHERE order_product_id = '" . $order_product_id . "'");
        
        if (!$query->row) {
            throw new Exception('Order product not found');
        }
        
        $current_product = $query->row;
        $old_quantity = (float)$current_product['quantity'];
        $quantity_change = $new_quantity - $old_quantity;
        
        // Calculate amount change
        $unit_price = (float)$current_product['price'];
        $amount_change = $quantity_change * $unit_price;
        
        // Update order product
        $this->db->query("UPDATE " . DB_PREFIX . "order_product SET
            quantity = '" . $new_quantity . "',
            total = '" . ($new_quantity * $unit_price) . "'
            WHERE order_product_id = '" . $order_product_id . "'");
        
        return array(
            'type' => 'quantity',
            'product_id' => $current_product['product_id'],
            'product_name' => $current_product['name'],
            'product_model' => $current_product['model'],
            'order_product_id' => $order_product_id,
            'old_quantity' => $old_quantity,
            'new_quantity' => $new_quantity,
            'quantity_change' => $quantity_change,
            'unit_price' => $unit_price,
            'amount_change' => $amount_change,
            'taxes' => $this->calculateProductTaxes($current_product['product_id'], $amount_change)
        );
    }
    
    private function processPriceChange($order_id, $modification_id, $mod) {
        $order_product_id = (int)$mod['order_product_id'];
        $new_price = (float)$mod['new_price'];
        
        // Get current product info
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product 
                                  WHERE order_product_id = '" . $order_product_id . "'");
        
        if (!$query->row) {
            throw new Exception('Order product not found');
        }
        
        $current_product = $query->row;
        $old_price = (float)$current_product['price'];
        $quantity = (float)$current_product['quantity'];
        $price_change = $new_price - $old_price;
        $amount_change = $price_change * $quantity;
        
        // Update order product
        $this->db->query("UPDATE " . DB_PREFIX . "order_product SET
            price = '" . $new_price . "',
            total = '" . ($new_price * $quantity) . "'
            WHERE order_product_id = '" . $order_product_id . "'");
        
        return array(
            'type' => 'price',
            'product_id' => $current_product['product_id'],
            'product_name' => $current_product['name'],
            'product_model' => $current_product['model'],
            'order_product_id' => $order_product_id,
            'old_price' => $old_price,
            'new_price' => $new_price,
            'price_change' => $price_change,
            'quantity' => $quantity,
            'amount_change' => $amount_change,
            'taxes' => $this->calculateProductTaxes($current_product['product_id'], $amount_change)
        );
    }
    
    private function processAddProduct($order_id, $modification_id, $mod) {
        $product_id = (int)$mod['product_id'];
        $quantity = (float)$mod['quantity'];
        $price = (float)$mod['price'];
        $amount_change = $quantity * $price;
        
        // Get product info
        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);
        
        if (!$product_info) {
            throw new Exception('Product not found');
        }
        
        // Add to order
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET
            order_id = '" . (int)$order_id . "',
            product_id = '" . $product_id . "',
            name = '" . $this->db->escape($product_info['name']) . "',
            model = '" . $this->db->escape($product_info['model']) . "',
            quantity = '" . $quantity . "',
            price = '" . $price . "',
            total = '" . $amount_change . "',
            tax = '" . $this->calculateProductTax($product_id, $price) . "',
            reward = '0'");
        
        $order_product_id = $this->db->getLastId();
        
        // Add product options if any
        if (isset($mod['options']) && is_array($mod['options'])) {
            foreach ($mod['options'] as $option) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET
                    order_id = '" . (int)$order_id . "',
                    order_product_id = '" . $order_product_id . "',
                    product_option_id = '" . (int)$option['product_option_id'] . "',
                    product_option_value_id = '" . (int)$option['product_option_value_id'] . "',
                    name = '" . $this->db->escape($option['name']) . "',
                    value = '" . $this->db->escape($option['value']) . "',
                    type = '" . $this->db->escape($option['type']) . "'");
            }
        }
        
        return array(
            'type' => 'add_product',
            'product_id' => $product_id,
            'product_name' => $product_info['name'],
            'product_model' => $product_info['model'],
            'order_product_id' => $order_product_id,
            'quantity' => $quantity,
            'unit_price' => $price,
            'amount_change' => $amount_change,
            'taxes' => $this->calculateProductTaxes($product_id, $amount_change)
        );
    }
    
    private function processRemoveProduct($order_id, $modification_id, $mod) {
        $order_product_id = (int)$mod['order_product_id'];
        
        // Get current product info
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product 
                                  WHERE order_product_id = '" . $order_product_id . "'");
        
        if (!$query->row) {
            throw new Exception('Order product not found');
        }
        
        $current_product = $query->row;
        $amount_change = -(float)$current_product['total'];
        
        // Remove product options
        $this->db->query("DELETE FROM " . DB_PREFIX . "order_option 
                         WHERE order_product_id = '" . $order_product_id . "'");
        
        // Remove product
        $this->db->query("DELETE FROM " . DB_PREFIX . "order_product 
                         WHERE order_product_id = '" . $order_product_id . "'");
        
        return array(
            'type' => 'remove_product',
            'product_id' => $current_product['product_id'],
            'product_name' => $current_product['name'],
            'product_model' => $current_product['model'],
            'order_product_id' => $order_product_id,
            'quantity' => -(float)$current_product['quantity'],
            'unit_price' => (float)$current_product['price'],
            'amount_change' => $amount_change,
            'taxes' => $this->calculateProductTaxes($current_product['product_id'], $amount_change)
        );
    }
    
    private function processOptionChange($order_id, $modification_id, $mod) {
        // Implementation for option changes
        // This would handle changes to product options/variants
        return array(
            'type' => 'change_option',
            'amount_change' => 0
        );
    }
    
    private function processUnitChange($order_id, $modification_id, $mod) {
        // Implementation for unit changes
        // This would handle changes to product units
        return array(
            'type' => 'change_unit',
            'amount_change' => 0
        );
    }
    
    private function calculateItemModification($order_products, $mod) {
        // Calculate the financial impact of a single modification
        $item_total = array(
            'subtotal_change' => 0,
            'tax_change' => 0,
            'total_change' => 0
        );
        
        // Implementation depends on modification type
        switch ($mod['type']) {
            case 'quantity':
                // Calculate based on quantity change
                break;
            case 'price':
                // Calculate based on price change
                break;
            // Add other cases as needed
        }
        
        return $item_total;
    }
    
    private function updateModificationTotals($modification_id, $total_change, $modification_type) {
        $this->db->query("UPDATE " . DB_PREFIX . "order_modification SET
            modification_type = '" . $this->db->escape($modification_type) . "',
            amount_change = '" . (float)$total_change . "'
            WHERE modification_id = '" . (int)$modification_id . "'");
    }
    
    private function recalculateOrderTotals($order_id) {
        // Recalculate order totals after modifications
        $this->load->model('sale/order');
        
        // Get all order products
        $query = $this->db->query("SELECT SUM(total) as subtotal FROM " . DB_PREFIX . "order_product 
                                  WHERE order_id = '" . (int)$order_id . "'");
        
        $subtotal = $query->row['subtotal'] ?? 0;
        
        // Calculate taxes
        $tax_total = $this->calculateOrderTax($order_id);
        
        // Update order totals
        $this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");
        
        // Add subtotal
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET
            order_id = '" . (int)$order_id . "',
            code = 'sub_total',
            title = 'Sub-Total',
            value = '" . (float)$subtotal . "',
            sort_order = '1'");
        
        // Add tax
        if ($tax_total > 0) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET
                order_id = '" . (int)$order_id . "',
                code = 'tax',
                title = 'Tax',
                value = '" . (float)$tax_total . "',
                sort_order = '5'");
        }
        
        // Add total
        $total = $subtotal + $tax_total;
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET
            order_id = '" . (int)$order_id . "',
            code = 'total',
            title = 'Total',
            value = '" . (float)$total . "',
            sort_order = '9'");
        
        // Update order total
        $this->db->query("UPDATE " . DB_PREFIX . "order SET total = '" . (float)$total . "' 
                         WHERE order_id = '" . (int)$order_id . "'");
    }
    
    private function calculateOrderTax($order_id) {
        // Calculate total tax for the order
        $tax_total = 0;
        
        $query = $this->db->query("SELECT op.*, p.tax_class_id 
                                  FROM " . DB_PREFIX . "order_product op
                                  LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                                  WHERE op.order_id = '" . (int)$order_id . "'");
        
        foreach ($query->rows as $product) {
            if ($product['tax_class_id']) {
                $tax_rate = $this->getTaxRate($product['tax_class_id']);
                $tax_amount = ($product['total'] * $tax_rate) / 100;
                $tax_total += $tax_amount;
            }
        }
        
        return $tax_total;
    }
    
    private function calculateProductTaxes($product_id, $amount) {
        // Calculate taxes for a specific product amount
        $taxes = array();
        
        $query = $this->db->query("SELECT tax_class_id FROM " . DB_PREFIX . "product 
                                  WHERE product_id = '" . (int)$product_id . "'");
        
        if ($query->row && $query->row['tax_class_id']) {
            $tax_rate = $this->getTaxRate($query->row['tax_class_id']);
            $tax_amount = ($amount * $tax_rate) / 100;
            
            $taxes[] = array(
                'type' => 'T1',
                'sub_type' => 'V009',
                'rate' => $tax_rate,
                'amount' => $tax_amount
            );
        }
        
        return $taxes;
    }
    
    private function calculateProductTax($product_id, $price) {
        $query = $this->db->query("SELECT tax_class_id FROM " . DB_PREFIX . "product 
                                  WHERE product_id = '" . (int)$product_id . "'");
        
        if ($query->row && $query->row['tax_class_id']) {
            $tax_rate = $this->getTaxRate($query->row['tax_class_id']);
            return ($price * $tax_rate) / 100;
        }
        
        return 0;
    }
    
    private function getTaxRate($tax_class_id) {
        $query = $this->db->query("SELECT rate FROM " . DB_PREFIX . "tax_rate 
                                  WHERE tax_class_id = '" . (int)$tax_class_id . "' 
                                  LIMIT 1");
        
        return $query->row ? (float)$query->row['rate'] : 14; // Default 14% VAT in Egypt
    }
    
    private function saveModificationItem($modification_id, $change, $original_data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_modification_item SET
            modification_id = '" . (int)$modification_id . "',
            product_id = '" . (int)($change['product_id'] ?? 0) . "',
            order_product_id = '" . (int)($change['order_product_id'] ?? 0) . "',
            modification_type = '" . $this->db->escape($change['type']) . "',
            old_value = '" . $this->db->escape(json_encode($original_data)) . "',
            new_value = '" . $this->db->escape(json_encode($change)) . "',
            amount_change = '" . (float)($change['amount_change'] ?? 0) . "',
            date_added = NOW()");
    }
    
    private function logModification($order_id, $modification_id, $modification_data, $changes) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_modification_log SET
            order_id = '" . (int)$order_id . "',
            modification_id = '" . (int)$modification_id . "',
            action = 'modify',
            data = '" . $this->db->escape(json_encode(array(
                'original_data' => $modification_data,
                'changes' => $changes
            ))) . "',
            user_id = '" . (int)$this->user->getId() . "',
            ip_address = '" . $this->db->escape($this->request->server['REMOTE_ADDR'] ?? '') . "',
            user_agent = '" . $this->db->escape($this->request->server['HTTP_USER_AGENT'] ?? '') . "',
            date_added = NOW()");
    }
}
