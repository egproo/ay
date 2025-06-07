<?php
class ModelPurchaseQualityCheck extends Model {
    
    /**
     * Retrieve a list of goods receipts that need quality check
     *
     * @param array $data Filter parameters
     * @return array List of receipts
     */
    public function getQualityCheckList($data = array()) {
        $sql = "SELECT gr.goods_receipt_id, gr.receipt_number, po.po_number, s.name AS supplier_name, 
                gr.receipt_date, gr.quality_status 
                FROM " . DB_PREFIX . "goods_receipt gr 
                LEFT JOIN " . DB_PREFIX . "purchase_order po ON (gr.purchase_order_id = po.purchase_order_id) 
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id) 
                WHERE gr.goods_receipt_id > 0";
        
        // Apply filters
        if (!empty($data['filter_receipt_number'])) {
            $sql .= " AND gr.receipt_number LIKE '%" . $this->db->escape($data['filter_receipt_number']) . "%'";
        }
        
        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%'";
        }
        
        if (!empty($data['filter_supplier'])) {
            $sql .= " AND s.name LIKE '%" . $this->db->escape($data['filter_supplier']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND gr.quality_status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(gr.receipt_date) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(gr.receipt_date) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        
        // Order by
        $sort_data = array(
            'gr.receipt_number',
            'po.po_number',
            's.name',
            'gr.receipt_date',
            'gr.quality_status'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY gr.receipt_date";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        // Pagination
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
    
    /**
     * Count total goods receipts that match filters
     *
     * @param array $data Filter parameters
     * @return int Total count
     */
    public function getTotalQualityCheckList($data = array()) {
        $sql = "SELECT COUNT(*) AS total 
                FROM " . DB_PREFIX . "goods_receipt gr 
                LEFT JOIN " . DB_PREFIX . "purchase_order po ON (gr.purchase_order_id = po.purchase_order_id) 
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id) 
                WHERE gr.goods_receipt_id > 0";
        
        // Apply filters
        if (!empty($data['filter_receipt_number'])) {
            $sql .= " AND gr.receipt_number LIKE '%" . $this->db->escape($data['filter_receipt_number']) . "%'";
        }
        
        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%'";
        }
        
        if (!empty($data['filter_supplier'])) {
            $sql .= " AND s.name LIKE '%" . $this->db->escape($data['filter_supplier']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND gr.quality_status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(gr.receipt_date) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(gr.receipt_date) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * Get receipt details by ID
     *
     * @param int $goods_receipt_id Receipt ID
     * @return array Receipt details
     */
    public function getReceipt($goods_receipt_id) {
        $sql = "SELECT gr.*, po.po_number, s.name AS supplier_name 
                FROM " . DB_PREFIX . "goods_receipt gr 
                LEFT JOIN " . DB_PREFIX . "purchase_order po ON (gr.purchase_order_id = po.purchase_order_id) 
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id) 
                WHERE gr.goods_receipt_id = '" . (int)$goods_receipt_id . "'";
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    /**
     * Get receipt items details
     *
     * @param int $goods_receipt_id Receipt ID
     * @return array Receipt items
     */
    public function getReceiptItems($goods_receipt_id) {
        $sql = "SELECT gri.*, p.name AS product_name, p.model, p.sku, p.upc, 
                p.unit, p.image, gri.quality_status, gri.quality_notes 
                FROM " . DB_PREFIX . "goods_receipt_item gri 
                LEFT JOIN " . DB_PREFIX . "product p ON (gri.product_id = p.product_id) 
                WHERE gri.goods_receipt_id = '" . (int)$goods_receipt_id . "' 
                ORDER BY gri.goods_receipt_item_id ASC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * Update item quality status
     *
     * @param int $item_id Item ID
     * @param string $status Quality status (pass, fail, partial)
     * @param string $notes Optional notes about quality check
     * @return bool Success status
     */
    public function updateItemQualityStatus($item_id, $status, $notes = '') {
        // Validate status
        if (!in_array($status, array('pass', 'fail', 'partial'))) {
            return false;
        }
        
        $this->db->query("UPDATE " . DB_PREFIX . "goods_receipt_item 
                SET quality_status = '" . $this->db->escape($status) . "', 
                quality_notes = '" . $this->db->escape($notes) . "', 
                quality_date = NOW() 
                WHERE goods_receipt_item_id = '" . (int)$item_id . "'");
        
        // Check if all items have been quality checked
        $item_info = $this->db->query("SELECT goods_receipt_id FROM " . DB_PREFIX . "goods_receipt_item 
                WHERE goods_receipt_item_id = '" . (int)$item_id . "'");
        
        if ($item_info->row) {
            $this->updateReceiptQualityStatus($item_info->row['goods_receipt_id']);
        }
        
        return true;
    }
    
    /**
     * Get quality check item details
     *
     * @param int $item_id Item ID
     * @return array Item details
     */
    public function getQualityCheckItem($item_id) {
        $sql = "SELECT gri.*, p.name AS product_name 
                FROM " . DB_PREFIX . "goods_receipt_item gri 
                LEFT JOIN " . DB_PREFIX . "product p ON (gri.product_id = p.product_id) 
                WHERE gri.goods_receipt_item_id = '" . (int)$item_id . "'";
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    /**
     * Update receipt quality notes
     *
     * @param int $goods_receipt_id Receipt ID
     * @param string $notes Quality notes
     * @return bool Success status
     */
    public function updateReceiptQualityNotes($goods_receipt_id, $notes) {
        $this->db->query("UPDATE " . DB_PREFIX . "goods_receipt 
                SET quality_notes = '" . $this->db->escape($notes) . "' 
                WHERE goods_receipt_id = '" . (int)$goods_receipt_id . "'");
        
        return true;
    }
    
    /**
     * Update receipt quality status based on item statuses
     *
     * @param int $goods_receipt_id Receipt ID
     * @return string New status
     */
    private function updateReceiptQualityStatus($goods_receipt_id) {
        // Get all items for this receipt
        $items_query = $this->db->query("SELECT quality_status 
                FROM " . DB_PREFIX . "goods_receipt_item 
                WHERE goods_receipt_id = '" . (int)$goods_receipt_id . "'");
        
        $all_passed = true;
        $all_checked = true;
        $has_failed = false;
        $has_partial = false;
        
        foreach ($items_query->rows as $item) {
            if (!$item['quality_status'] || $item['quality_status'] == 'pending') {
                $all_checked = false;
                $all_passed = false;
            } else if ($item['quality_status'] == 'fail') {
                $all_passed = false;
                $has_failed = true;
            } else if ($item['quality_status'] == 'partial') {
                $all_passed = false;
                $has_partial = true;
            }
        }
        
        // Determine overall status
        $new_status = 'pending';
        
        if ($all_checked) {
            if ($all_passed) {
                $new_status = 'passed';
            } else if ($has_failed && !$has_partial) {
                $new_status = 'failed';
            } else {
                $new_status = 'partial';
            }
        }
        
        // Update receipt status
        $this->db->query("UPDATE " . DB_PREFIX . "goods_receipt 
                SET quality_status = '" . $this->db->escape($new_status) . "', 
                quality_date = NOW() 
                WHERE goods_receipt_id = '" . (int)$goods_receipt_id . "'");
        
        return $new_status;
    }
    
    /**
     * Add quality check history record
     *
     * @param int $goods_receipt_id Receipt ID
     * @param string $status Quality status
     * @param string $comment Comment
     * @return int History ID
     */
    public function addQualityCheckHistory($goods_receipt_id, $status, $comment = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "goods_receipt_history 
                SET goods_receipt_id = '" . (int)$goods_receipt_id . "', 
                user_id = '" . (int)$this->user->getId() . "', 
                status = '" . $this->db->escape($status) . "', 
                comment = '" . $this->db->escape($comment) . "', 
                type = 'quality_check', 
                date_added = NOW()");
        
        return $this->db->getLastId();
    }
    
    /**
     * Update inventory after quality check is completed
     * Only items that passed quality check will be added to inventory
     *
     * @param int $goods_receipt_id Receipt ID
     * @return bool Success status
     */
    public function updateInventoryAfterQualityCheck($goods_receipt_id) {
        // Get receipt status
        $receipt_query = $this->db->query("SELECT quality_status, inventory_updated 
                FROM " . DB_PREFIX . "goods_receipt 
                WHERE goods_receipt_id = '" . (int)$goods_receipt_id . "'");
        
        if (!$receipt_query->row) {
            return false;
        }
        
        // Only update inventory if items have passed quality check and inventory not already updated
        if (($receipt_query->row['quality_status'] == 'passed' || $receipt_query->row['quality_status'] == 'partial') 
            && !$receipt_query->row['inventory_updated']) {
            
            // Get all items that passed quality check
            $items_query = $this->db->query("SELECT gri.*, p.name AS product_name 
                    FROM " . DB_PREFIX . "goods_receipt_item gri 
                    LEFT JOIN " . DB_PREFIX . "product p ON (gri.product_id = p.product_id) 
                    WHERE gri.goods_receipt_id = '" . (int)$goods_receipt_id . "' 
                    AND (gri.quality_status = 'pass' OR gri.quality_status = 'partial')");
            
            if ($items_query->num_rows) {
                // Load required models
                $this->load->model('catalog/product');
                $this->load->model('inventory/stock');
                
                foreach ($items_query->rows as $item) {
                    $quantity = ($item['quality_status'] == 'partial') ? 
                        (float)$item['received_qty'] * ((float)$item['accepted_percentage'] / 100) : 
                        (float)$item['received_qty'];
                    
                    // Update product stock
                    if ($quantity > 0) {
                        $this->model_inventory_stock->addStock(array(
                            'product_id' => $item['product_id'],
                            'branch_id' => $item['branch_id'],
                            'location_id' => $item['location_id'],
                            'quantity' => $quantity,
                            'unit_cost' => $item['unit_cost'],
                            'reference' => 'GR-' . $goods_receipt_id,
                            'type' => 'receipt',
                            'comment' => 'Quality Check Passed'
                        ));
                        
                        // Mark item as inventory updated
                        $this->db->query("UPDATE " . DB_PREFIX . "goods_receipt_item 
                                SET inventory_updated = '1' 
                                WHERE goods_receipt_item_id = '" . (int)$item['goods_receipt_item_id'] . "'");
                    }
                }
                
                // Mark receipt as inventory updated
                $this->db->query("UPDATE " . DB_PREFIX . "goods_receipt 
                        SET inventory_updated = '1', 
                        date_modified = NOW() 
                        WHERE goods_receipt_id = '" . (int)$goods_receipt_id . "'");
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get quality check statistics
     *
     * @return array Statistics data
     */
    public function getQualityCheckStats() {
        $stats = array(
            'pending' => 0,
            'passed' => 0,
            'failed' => 0,
            'partial' => 0,
            'total' => 0
        );
        
        $query = $this->db->query("SELECT quality_status, COUNT(*) AS total 
                FROM " . DB_PREFIX . "goods_receipt 
                GROUP BY quality_status");
        
        foreach ($query->rows as $row) {
            if (isset($stats[$row['quality_status']])) {
                $stats[$row['quality_status']] = (int)$row['total'];
                $stats['total'] += (int)$row['total'];
            }
        }
        
        return $stats;
    }
}