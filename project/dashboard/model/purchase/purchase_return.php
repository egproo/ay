<?php
class ModelPurchasePurchaseReturn extends Model {
    public function addReturn($data) {
        // Set default status to pending if not specified
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }
        
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_return SET 
            order_id = '" . (int)$data['order_id'] . "', 
            receipt_id = '" . (int)$data['receipt_id'] . "', 
            supplier_id = '" . (int)$data['supplier_id'] . "', 
            user_id = '" . (int)$this->user->getId() . "', 
            return_number = '" . $this->db->escape($this->generateReturnNumber()) . "', 
            reason_id = '" . (int)$data['reason_id'] . "', 
            date_added = '" . $this->db->escape($data['date_added']) . "', 
            status = '" . $this->db->escape($data['status']) . "', 
            note = '" . $this->db->escape($data['note']) . "'");

        $return_id = $this->db->getLastId();

        // Add return items
        if (isset($data['return_item'])) {
            foreach ($data['return_item'] as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_return_item SET 
                    return_id = '" . (int)$return_id . "', 
                    receipt_item_id = '" . (int)$item['receipt_item_id'] . "', 
                    product_id = '" . (int)$item['product_id'] . "', 
                    quantity = '" . (float)$item['quantity'] . "', 
                    unit_price = '" . (float)$item['unit_price'] . "', 
                    total = '" . (float)$item['total'] . "'");
            }
        }

        // Add return history
        $this->addReturnHistory($return_id, $data['status'], $data['note'], true);

        return $return_id;
    }

    public function editReturn($return_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET 
            order_id = '" . (int)$data['order_id'] . "', 
            receipt_id = '" . (int)$data['receipt_id'] . "', 
            supplier_id = '" . (int)$data['supplier_id'] . "', 
            reason_id = '" . (int)$data['reason_id'] . "', 
            date_added = '" . $this->db->escape($data['date_added']) . "', 
            status = '" . $this->db->escape($data['status']) . "', 
            note = '" . $this->db->escape($data['note']) . "' 
            WHERE return_id = '" . (int)$return_id . "'");

        // Delete existing return items
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_return_item WHERE return_id = '" . (int)$return_id . "'");

        // Add updated return items
        if (isset($data['return_item'])) {
            foreach ($data['return_item'] as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_return_item SET 
                    return_id = '" . (int)$return_id . "', 
                    receipt_item_id = '" . (int)$item['receipt_item_id'] . "', 
                    product_id = '" . (int)$item['product_id'] . "', 
                    quantity = '" . (float)$item['quantity'] . "', 
                    unit_price = '" . (float)$item['unit_price'] . "', 
                    total = '" . (float)$item['total'] . "'");
            }
        }

        // Add return history if status changed
        $return_info = $this->getReturn($return_id);
        
        if ($return_info['status'] != $data['status']) {
            $this->addReturnHistory($return_id, $data['status'], $data['note'], true);
        }
    }

    public function deleteReturn($return_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_return WHERE return_id = '" . (int)$return_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_return_item WHERE return_id = '" . (int)$return_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_return_history WHERE return_id = '" . (int)$return_id . "'");
    }

    public function getReturn($return_id) {
        $query = $this->db->query("SELECT r.*, 
            po.po_number AS order_number, 
            gr.receipt_number, 
            s.name AS supplier, 
            u.username AS created_by 
            FROM " . DB_PREFIX . "purchase_return r 
            LEFT JOIN " . DB_PREFIX . "purchase_order po ON (r.order_id = po.purchase_order_id) 
            LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (r.receipt_id = gr.receipt_id) 
            LEFT JOIN " . DB_PREFIX . "supplier s ON (r.supplier_id = s.supplier_id) 
            LEFT JOIN " . DB_PREFIX . "user u ON (r.user_id = u.user_id) 
            WHERE r.return_id = '" . (int)$return_id . "'");

        return $query->row;
    }

    public function getReturns($data = array()) {
        $sql = "SELECT r.return_id, r.return_number, r.date_added AS return_date, r.status, 
            po.po_number AS order_number, 
            gr.receipt_number, 
            s.name AS supplier, 
            u.username AS created_by, 
            (SELECT SUM(ri.total) FROM " . DB_PREFIX . "purchase_return_item ri WHERE ri.return_id = r.return_id) AS total_amount 
            FROM " . DB_PREFIX . "purchase_return r 
            LEFT JOIN " . DB_PREFIX . "purchase_order po ON (r.order_id = po.purchase_order_id) 
            LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (r.receipt_id = gr.receipt_id) 
            LEFT JOIN " . DB_PREFIX . "supplier s ON (r.supplier_id = s.supplier_id) 
            LEFT JOIN " . DB_PREFIX . "user u ON (r.user_id = u.user_id)";

        $where = " WHERE 1=1";

        if (!empty($data['filter_return_number'])) {
            $where .= " AND r.return_number LIKE '%" . $this->db->escape($data['filter_return_number']) . "%'";
        }

        if (!empty($data['filter_order_number'])) {
            $where .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_order_number']) . "%'";
        }

        if (!empty($data['filter_receipt_number'])) {
            $where .= " AND gr.receipt_number LIKE '%" . $this->db->escape($data['filter_receipt_number']) . "%'";
        }

        if (!empty($data['filter_supplier'])) {
            $where .= " AND s.name LIKE '%" . $this->db->escape($data['filter_supplier']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $where .= " AND r.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_added'])) {
            $where .= " AND DATE(r.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        $sql .= $where;

        $sort_data = array(
            'r.return_number',
            'po.po_number',
            'gr.receipt_number',
            's.name',
            'total_amount',
            'r.status',
            'r.date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY r.return_id";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

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

    public function getTotalReturns($data = array()) {
        $sql = "SELECT COUNT(DISTINCT r.return_id) AS total FROM " . DB_PREFIX . "purchase_return r";
        
        $sql .= " LEFT JOIN " . DB_PREFIX . "purchase_order po ON (r.order_id = po.purchase_order_id)";
        $sql .= " LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (r.receipt_id = gr.receipt_id)";
        $sql .= " LEFT JOIN " . DB_PREFIX . "supplier s ON (r.supplier_id = s.supplier_id)";
        
        $where = " WHERE 1=1";

        if (!empty($data['filter_return_number'])) {
            $where .= " AND r.return_number LIKE '%" . $this->db->escape($data['filter_return_number']) . "%'";
        }

        if (!empty($data['filter_order_number'])) {
            $where .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_order_number']) . "%'";
        }

        if (!empty($data['filter_receipt_number'])) {
            $where .= " AND gr.receipt_number LIKE '%" . $this->db->escape($data['filter_receipt_number']) . "%'";
        }

        if (!empty($data['filter_supplier'])) {
            $where .= " AND s.name LIKE '%" . $this->db->escape($data['filter_supplier']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $where .= " AND r.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_added'])) {
            $where .= " AND DATE(r.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        $sql .= $where;
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }

    public function getReturnItems($return_id) {
        $query = $this->db->query("SELECT ri.*, 
            p.name AS product_name, 
            p.model, 
            p.sku, 
            p.upc,
            p.unit 
            FROM " . DB_PREFIX . "purchase_return_item ri 
            LEFT JOIN " . DB_PREFIX . "product p ON (ri.product_id = p.product_id) 
            WHERE ri.return_id = '" . (int)$return_id . "'");
            
        return $query->rows;
    }

    public function getReturnHistories($return_id) {
        $query = $this->db->query("SELECT rh.*, 
            u.username AS user 
            FROM " . DB_PREFIX . "purchase_return_history rh 
            LEFT JOIN " . DB_PREFIX . "user u ON (rh.user_id = u.user_id) 
            WHERE rh.return_id = '" . (int)$return_id . "' 
            ORDER BY rh.date_added ASC");
            
        return $query->rows;
    }

    public function addReturnHistory($return_id, $status, $comment = '', $notify = false) {
        $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET status = '" . $this->db->escape($status) . "', date_modified = NOW() WHERE return_id = '" . (int)$return_id . "'");
        
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_return_history SET 
            return_id = '" . (int)$return_id . "', 
            status = '" . $this->db->escape($status) . "', 
            comment = '" . $this->db->escape($comment) . "', 
            user_id = '" . (int)$this->user->getId() . "', 
            notify = '" . (int)$notify . "', 
            date_added = NOW()");
            
        return $this->db->getLastId();
    }

    public function approveReturn($return_id) {
        $return_info = $this->getReturn($return_id);
        
        if ($return_info && $return_info['status'] == 'pending') {
            // Update status to approved
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET 
                status = 'approved', 
                date_modified = NOW() 
                WHERE return_id = '" . (int)$return_id . "'");
                
            // Add history record
            $this->addReturnHistory($return_id, 'approved', $this->language->get('text_approve_success'), true);
            
            // Process inventory adjustments
            $this->processInventoryAdjustments($return_id);
            
            return true;
        }
        
        return false;
    }
    
    public function rejectReturn($return_id) {
        $return_info = $this->getReturn($return_id);
        
        if ($return_info && $return_info['status'] == 'pending') {
            // Update status to rejected
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET 
                status = 'rejected', 
                date_modified = NOW() 
                WHERE return_id = '" . (int)$return_id . "'");
                
            // Add history record
            $this->addReturnHistory($return_id, 'rejected', $this->language->get('text_reject_success'), true);
            
            return true;
        }
        
        return false;
    }
    
    public function cancelReturn($return_id) {
        $return_info = $this->getReturn($return_id);
        
        if ($return_info && ($return_info['status'] == 'pending' || $return_info['status'] == 'active')) {
            // Update status to canceled
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET 
                status = 'canceled', 
                date_modified = NOW() 
                WHERE return_id = '" . (int)$return_id . "'");
                
            // Add history record
            $this->addReturnHistory($return_id, 'canceled', $this->language->get('text_cancel_success'), true);
            
            return true;
        }
        
        return false;
    }
    
    public function completeReturn($return_id) {
        $return_info = $this->getReturn($return_id);
        
        if ($return_info && $return_info['status'] == 'approved') {
            // Update status to completed
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET 
                status = 'completed', 
                date_modified = NOW() 
                WHERE return_id = '" . (int)$return_id . "'");
                
            // Add history record
            $this->addReturnHistory($return_id, 'completed', '', true);
            
            return true;
        }
        
        return false;
    }
    
    protected function processInventoryAdjustments($return_id) {
        // Get return items
        $return_items = $this->getReturnItems($return_id);
        
        foreach ($return_items as $item) {
            // Adjust inventory quantity (reduce stock)
            $this->db->query("UPDATE " . DB_PREFIX . "product SET 
                quantity = (quantity - " . (float)$item['quantity'] . ") 
                WHERE product_id = '" . (int)$item['product_id'] . "'");
                
            // Add stock movement record
            $this->addStockMovement(
                $item['product_id'], 
                -$item['quantity'], 
                'Return - #' . $return_id, 
                'purchase_return', 
                $return_id
            );
        }
    }
    
    protected function addStockMovement($product_id, $quantity, $description, $type, $reference_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "stock_movement SET 
            product_id = '" . (int)$product_id . "', 
            quantity = '" . (float)$quantity . "', 
            description = '" . $this->db->escape($description) . "', 
            type = '" . $this->db->escape($type) . "', 
            reference_id = '" . (int)$reference_id . "', 
            date_added = NOW()");
    }
    
    public function generateReturnNumber() {
        // Get the next auto increment value for return_id
        $query = $this->db->query("SHOW TABLE STATUS LIKE '" . DB_PREFIX . "purchase_return'");
        $next_id = $query->row['Auto_increment'];
        
        // Format: RTN-YYYYMMDD-00001
        return 'RTN-' . date('Ymd') . '-' . str_pad($next_id, 5, '0', STR_PAD_LEFT);
    }
    
    public function generateCreditNote($return_id) {
        $return_info = $this->getReturn($return_id);
        
        if (!$return_info || $return_info['status'] != 'approved') {
            return false;
        }
        
        // Implementation for generating credit note would go here
        // This would typically create a record in a credit_note table
        // and link it to the return
        
        return true;
    }
} 