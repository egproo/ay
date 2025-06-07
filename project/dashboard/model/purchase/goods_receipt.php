<?php
class ModelPurchaseGoodsReceipt extends Model {

    /**
     * إحصائيات سندات الاستلام
     */
    public function getReceiptStats($filter_data = array()) {
        $stats = array(
            'total_receipts'     => 0,
            'pending_receipts'   => 0,
            'received_receipts'  => 0,
            'partial_receipts'   => 0, // Note: This status might not be directly on GRN, but on PO
            'total_amount'       => 0 // Based on linked invoice amount if available
        );

        // بناء شرط WHERE بناءً على الفلاتر
        $where = " WHERE 1 ";
        $join = " LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (gr.po_id = po.po_id) "; // Join needed for PO number filter

        if (!empty($filter_data['filter_receipt_number'])) {
            $where .= " AND gr.receipt_number LIKE '%" . $this->db->escape($filter_data['filter_receipt_number']) . "%' ";
        }
        if (!empty($filter_data['filter_po_number'])) {
            $where .= " AND po.po_number LIKE '%" . $this->db->escape($filter_data['filter_po_number']) . "%' ";
        }
        if (!empty($filter_data['filter_status'])) {
            $where .= " AND gr.status = '" . $this->db->escape($filter_data['filter_status']) . "' ";
        }
        if (!empty($filter_data['filter_date_start'])) {
            $where .= " AND DATE(gr.receipt_date) >= '" . $this->db->escape($filter_data['filter_date_start']) . "' ";
        }
        if (!empty($filter_data['filter_date_end'])) {
            $where .= " AND DATE(gr.receipt_date) <= '" . $this->db->escape($filter_data['filter_date_end']) . "' ";
        }

        // استخدام subqueries لحساب الإحصائيات
        $sql = "SELECT
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "goods_receipt` gr $join $where) AS total_rec,
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "goods_receipt` gr $join $where AND gr.status = 'pending') AS pending_rec,
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "goods_receipt` gr $join $where AND gr.status = 'received') AS received_rec,
                (SELECT SUM(gr.invoice_amount) FROM `" . DB_PREFIX . "goods_receipt` gr $join $where) AS total_amount";
                // Removed partial_receipts count as it's not a standard GRN status

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            $stats['total_receipts']     = (int)$query->row['total_rec'];
            $stats['pending_receipts']   = (int)$query->row['pending_rec'];
            $stats['received_receipts']  = (int)$query->row['received_rec'];
            // $stats['partial_receipts']   = (int)$query->row['partial_rec']; // Removed
            $stats['total_amount']       = $query->row['total_amount'] ? $this->currency->format($query->row['total_amount'], $this->config->get('config_currency')) : $this->currency->format(0, $this->config->get('config_currency')); // Format even if 0
        }

        return $stats;
    }

    /**
     * جلب قائمة سندات الاستلام
     */
    public function getReceipts($data = array()) {
        $sql = "SELECT gr.*,
                       b.name AS branch_name,
                       po.po_number,
                       u.firstname AS created_by_name,
                       c.title AS currency_name,
                       CONCAT(qc.firstname, ' ', qc.lastname) as checked_by_name
                FROM `" . DB_PREFIX . "goods_receipt` gr
                LEFT JOIN `" . DB_PREFIX . "branch` b ON (gr.branch_id = b.branch_id)
                LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (gr.po_id = po.po_id)
                LEFT JOIN `" . DB_PREFIX . "user` u ON (gr.created_by = u.user_id)
                LEFT JOIN `" . DB_PREFIX . "currency` c ON (gr.currency_id = c.currency_id)
                LEFT JOIN `" . DB_PREFIX . "user` qc ON (gr.quality_checked_by = qc.user_id)
                WHERE 1";

        if (!empty($data['filter_receipt_number'])) {
            $sql .= " AND gr.receipt_number LIKE '%" . $this->db->escape($data['filter_receipt_number']) . "%'";
        }
        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%'";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND gr.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(gr.receipt_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(gr.receipt_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY gr.goods_receipt_id DESC";

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
     * جلب عدد سندات الاستلام (للصفحات)
     */
    public function getTotalReceipts($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM `" . DB_PREFIX . "goods_receipt` gr
                LEFT JOIN `" . DB_PREFIX . "branch` b ON (gr.branch_id = b.branch_id)
                LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (gr.po_id = po.po_id)
                WHERE 1";

        if (!empty($data['filter_receipt_number'])) {
            $sql .= " AND gr.receipt_number LIKE '%" . $this->db->escape($data['filter_receipt_number']) . "%'";
        }
        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%'";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND gr.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(gr.receipt_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(gr.receipt_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * جلب منتجات أمر الشراء للاستلام
     */
    public function getPurchaseOrderProducts($po_id) {
        $sql = "SELECT poi.*, pd.name, u.desc_en AS unit_name,
                       (SELECT COALESCE(SUM(gri.quantity_received), 0)
                        FROM " . DB_PREFIX . "goods_receipt_item gri
                        LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (gri.goods_receipt_id = gr.goods_receipt_id)
                        WHERE gri.po_item_id = poi.po_item_id
                        AND gr.status != 'cancelled') as received_qty
                FROM " . DB_PREFIX . "purchase_order_item poi
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (poi.product_id = pd.product_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (poi.unit_id = u.unit_id)
                WHERE poi.po_id = '" . (int)$po_id . "'
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'"; // Use config language

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * إكمال سند الاستلام (تحديث الحالة فقط)
     * Inventory and Journal entries are handled during addGoodsReceipt in ModelPurchaseOrder
     */
    public function completeGoodsReceipt($receipt_id, $user_id) {
        $json = array();
        $this->load->language('purchase/goods_receipt'); // Load language file

        try {
            // التحقق من السند وحالته
            $receipt_info = $this->getGoodsReceipt($receipt_id);
            if (!$receipt_info) {
                throw new Exception($this->language->get('error_receipt_not_found'));
            }
            // Allow completion if pending or partially received (status on PO, not GRN)
            // GRN status should likely only be pending or received/cancelled
            if (!in_array($receipt_info['status'], ['pending'])) {
                 throw new Exception($this->language->get('error_already_received')); // Or a more specific error like 'error_invalid_status_for_completion'
            }
            // Check if QC is required and not done
            if ($receipt_info['quality_check_required'] && !$receipt_info['quality_checked_by']) {
                throw new Exception($this->language->get('error_quality_check_required'));
            }

            $this->db->query("START TRANSACTION");

            // تحديث حالة السند إلى مستلم
            $this->db->query("UPDATE `" . DB_PREFIX . "goods_receipt` SET
                status = 'received', // Final 'received' status
                updated_at = NOW(),
                updated_by = '" . (int)$user_id . "'
                WHERE goods_receipt_id = '" . (int)$receipt_id . "'");

            // إضافة سجل في التاريخ
            $this->addReceiptHistory(array(
                'goods_receipt_id' => $receipt_id,
                'user_id' => $user_id,
                'action' => 'completed', // Use 'completed' or 'received'
                'description' => $this->language->get('text_history_completed') // Add this lang string
            ));

            // Note: No Journal Entry or Inventory update here, as it's done on creation via PO model.

            $this->db->query("COMMIT");

            $json['success'] = true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $json['error'] = $e->getMessage();
        }

        return $json;
    }

    // Removed updateProductCost function

    /**
     * تسجيل فحص الجودة
     */
    public function addQualityCheck($data) {
        $json = array();
        $this->load->language('purchase/goods_receipt'); // Load language

        try {
            // التحقق من السند
            $receipt_info = $this->getGoodsReceipt($data['goods_receipt_id']);
            if (!$receipt_info) {
                throw new Exception($this->language->get('error_receipt_not_found'));
            }
            if (!$receipt_info['quality_check_required']) {
                throw new Exception($this->language->get('error_quality_check_not_required'));
            }
            if ($receipt_info['quality_checked_by']) {
                throw new Exception($this->language->get('error_already_checked'));
            }

            $this->db->query("START TRANSACTION");

            // إنشاء سجل فحص الجودة (if a separate header table exists)
            // $this->db->query("INSERT INTO `" . DB_PREFIX . "quality_inspection` SET ... ");
            // $inspection_id = $this->db->getLastId();

            // تحديث نتائج الفحص على البنود
            $overall_status = 'passed'; // Assume passed unless an item fails
            foreach ($data['items'] as $item) {
                // Insert into quality_inspection_result if it exists
                // $this->db->query("INSERT INTO `" . DB_PREFIX . "quality_inspection_result` SET ... ");

                // تحديث نتيجة الفحص في بند السند
                $this->db->query("UPDATE `" . DB_PREFIX . "goods_receipt_item` SET
                    quality_result = '" . $this->db->escape($item['result']) . "',
                    remarks = CONCAT(COALESCE(remarks, ''), '\nQC: ', '" . $this->db->escape($item['notes']) . "')
                    WHERE receipt_item_id = '" . (int)$item['receipt_item_id'] . "'");

                if ($item['result'] == 'failed') {
                    $overall_status = 'failed';
                }
            }

            // تحديث سند الاستلام بأنه تم فحصه
            $this->db->query("UPDATE `" . DB_PREFIX . "goods_receipt` SET
                quality_checked_by = '" . (int)$data['inspector_id'] . "',
                quality_check_date = NOW(),
                quality_status = '" . $this->db->escape($overall_status) . "' // Update overall QC status
                WHERE goods_receipt_id = '" . (int)$data['goods_receipt_id'] . "'");

            // إضافة سجل في التاريخ
            $this->addReceiptHistory(array(
                'goods_receipt_id' => $data['goods_receipt_id'],
                'user_id' => $data['inspector_id'],
                'action' => 'quality_check',
                'description' => $this->language->get('text_history_qc') . ' ' . $this->getQualityResultText($overall_status) // Add lang string
            ));

            $this->db->query("COMMIT");

            $json['success'] = true;
            // $json['inspection_id'] = $inspection_id; // If header table exists

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $json['error'] = $e->getMessage();
        }

        return $json;
    }

    /**
     * جلب تفاصيل سند استلام معين
     */
    public function getGoodsReceipt($receipt_id) {
        $sql = "SELECT gr.*,
                       b.name AS branch_name,
                       po.po_number,
                       po.supplier_id,
                       s.account_code AS supplier_account_code,
                       CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                       c.title AS currency_name,
                       c.code AS currency_code,
                       CONCAT(u1.firstname, ' ', u1.lastname) AS created_by_name,
                       CONCAT(u2.firstname, ' ', u2.lastname) AS updated_by_name,
                       CONCAT(u3.firstname, ' ', u3.lastname) AS checked_by_name
                FROM `" . DB_PREFIX . "goods_receipt` gr
                LEFT JOIN `" . DB_PREFIX . "branch` b ON (gr.branch_id = b.branch_id)
                LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (gr.po_id = po.po_id)
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (po.supplier_id = s.supplier_id)
                LEFT JOIN `" . DB_PREFIX . "currency` c ON (gr.currency_id = c.currency_id)
                LEFT JOIN `" . DB_PREFIX . "user` u1 ON (gr.created_by = u1.user_id)
                LEFT JOIN `" . DB_PREFIX . "user` u2 ON (gr.updated_by = u2.user_id)
                LEFT JOIN `" . DB_PREFIX . "user` u3 ON (gr.quality_checked_by = u3.user_id)
                WHERE gr.goods_receipt_id = '" . (int)$receipt_id . "'";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * جلب بنود سند استلام معين
     */
    public function getGoodsReceiptItems($receipt_id) {
        $sql = "SELECT gri.*,
                       pd.name AS product_name,
                       u.desc_en AS unit_name,
                       poi.unit_price AS po_unit_price,
                       poi.quantity AS ordered_quantity
                       -- Removed quality result join, get it separately if needed or rely on gri.quality_result
                FROM `" . DB_PREFIX . "goods_receipt_item` gri
                LEFT JOIN `" . DB_PREFIX . "product_description` pd
                    ON (gri.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN `" . DB_PREFIX . "unit` u ON (gri.unit_id = u.unit_id)
                LEFT JOIN `" . DB_PREFIX . "purchase_order_item` poi ON (gri.po_item_id = poi.po_item_id)
                WHERE gri.goods_receipt_id = '" . (int)$receipt_id . "'";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * إضافة سجل في تاريخ السند
     */
    public function addReceiptHistory($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "goods_receipt_history` SET
            goods_receipt_id = '" . (int)$data['goods_receipt_id'] . "',
            user_id = '" . (int)$data['user_id'] . "',
            action = '" . $this->db->escape($data['action']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            created_at = NOW()");
        return $this->db->getLastId(); // Return ID
    }

    /**
     * جلب تاريخ السند
     */
    public function getReceiptHistory($receipt_id) {
        $sql = "SELECT h.*,
                       CONCAT(u.firstname, ' ', u.lastname) AS user_name
                FROM `" . DB_PREFIX . "goods_receipt_history` h
                LEFT JOIN `" . DB_PREFIX . "user` u ON (h.user_id = u.user_id)
                WHERE h.goods_receipt_id = '" . (int)$receipt_id . "'
                ORDER BY h.created_at DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * التحقق من الكمية المستلمة سابقاً
     */
    public function getReceivedQuantity($po_item_id, $exclude_receipt_id = null) {
        $sql = "SELECT COALESCE(SUM(gri.quantity_received), 0) as total_received
                FROM `" . DB_PREFIX . "goods_receipt_item` gri
                LEFT JOIN `" . DB_PREFIX . "goods_receipt` gr
                    ON (gri.goods_receipt_id = gr.goods_receipt_id)
                WHERE gri.po_item_id = '" . (int)$po_item_id . "'
                AND gr.status != 'cancelled'";

        if ($exclude_receipt_id) {
            $sql .= " AND gr.goods_receipt_id != '" . (int)$exclude_receipt_id . "'";
        }

        $query = $this->db->query($sql);
        return (float)$query->row['total_received'];
    }

    /**
     * حفظ ملف مرفق مع السند
     */
    public function addReceiptDocument($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_document` SET
            reference_type = 'goods_receipt',
            reference_id = '" . (int)$data['goods_receipt_id'] . "',
            document_name = '" . $this->db->escape($data['document_name']) . "',
            file_path = '" . $this->db->escape($data['file_path']) . "',
            uploaded_by = '" . (int)$data['uploaded_by'] . "',
            upload_date = NOW()");
        return $this->db->getLastId(); // Return ID
    }

    /**
     * جلب ملفات السند
     */
    public function getReceiptDocuments($receipt_id) {
        $sql = "SELECT d.*,
                       CONCAT(u.firstname, ' ', u.lastname) AS uploaded_by_name
                FROM `" . DB_PREFIX . "purchase_document` d
                LEFT JOIN `" . DB_PREFIX . "user` u ON (d.uploaded_by = u.user_id)
                WHERE d.reference_type = 'goods_receipt'
                AND d.reference_id = '" . (int)$receipt_id . "'
                ORDER BY d.upload_date DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * بيانات المطابقة مع الفاتورة وأمر الشراء
     */
    public function updateMatchingStatus($receipt_id) {
        $items = $this->getGoodsReceiptItems($receipt_id);
        $has_mismatch = false;
        $all_matched = true;

        foreach ($items as $item) {
            // Compare invoice price (if available) with PO price
            if (isset($item['invoice_unit_price']) && $item['invoice_unit_price'] != $item['po_unit_price']) {
                $has_mismatch = true;
            }
            // Check if quantity received is less than ordered
            if ($item['quantity_received'] < $item['ordered_quantity']) {
                $all_matched = false;
            }
        }

        $status = 'pending'; // Default
        if ($has_mismatch) {
            $status = 'mismatch';
        } elseif ($all_matched) {
            // Only mark as matched if all items are fully received and prices match (or no invoice price yet)
            // This logic might need refinement based on when matching is triggered
            $status = 'matched';
        } else {
            // If not fully matched and no mismatch found yet, it's partial
            $status = 'partial';
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "goods_receipt` SET
            matching_status = '" . $this->db->escape($status) . "'
            WHERE goods_receipt_id = '" . (int)$receipt_id . "'");

        return $status;
    }

   /**
     * جلب قائمة الموردين (للاستخدام في عرض الشراء)
     */
    public function getSuppliers() {
        $sql = "SELECT supplier_id, CONCAT(firstname, ' ', lastname) AS name
               FROM `" . DB_PREFIX . "supplier`
                ORDER BY lastname, firstname ASC";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getCurrencies() {
        $sql = "SELECT currency_id, title, code, value
                FROM `" . DB_PREFIX . "currency`
               ORDER BY title ASC";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * جلب الفروع
     */
     public function getBranches(){
      $q = $this->db->query("SELECT branch_id, name FROM `" . DB_PREFIX . "branch` ORDER BY name ASC");
        return $q->rows;
    }

      /**
        * جلب مجموعات المستخدمين
       */
     public function getUserGroups(){
        $q = $this->db->query("SELECT user_group_id, name FROM `" . DB_PREFIX . "user_group` ORDER BY name ASC");
         return $q->rows;
    }

    /**
     * Get status text based on status code
     * @param string $status
     * @return string
     */
    public function getStatusText($status) {
        $this->load->language('purchase/goods_receipt');
        $statuses = [
            'pending'            => $this->language->get('text_status_pending'),
            // 'partially_received' => $this->language->get('text_status_partially_received'), // Not a GRN status
            'received'           => $this->language->get('text_status_received'),
            'cancelled'          => $this->language->get('text_status_cancelled')
        ];
        return $statuses[$status] ?? $status;
    }

    /**
     * Get CSS class based on status code
     * @param string $status
     * @return string
     */
    public function getStatusClass($status) {
        $classes = [
            'pending'            => 'warning',
            // 'partially_received' => 'info',
            'received'           => 'success',
            'cancelled'          => 'danger'
        ];
        return $classes[$status] ?? 'default';
    }

     /**
     * Get Quality Result text based on result code
     * @param string $result
     * @return string
     */
    public function getQualityResultText($result) {
        $this->load->language('purchase/goods_receipt');
        $results = [
            'pending' => $this->language->get('text_qc_pending'),
            'passed'  => $this->language->get('text_qc_passed'),
            'failed'  => $this->language->get('text_qc_failed')
        ];
        return $results[$result] ?? $result;
    }

    /**
     * Get CSS class based on Quality Result code
     * @param string $result
     * @return string
     */
    public function getQualityResultClass($result) {
        $classes = [
            'pending' => 'warning',
            'passed'  => 'success',
            'failed'  => 'danger'
        ];
        return $classes[$result] ?? 'default';
    }

    /**
     * Delete goods receipt
     *
     * @param int $receipt_id Goods Receipt ID
     * @return array Result
     */
    public function deleteGoodsReceipt($receipt_id) {
        $receipt = $this->getGoodsReceipt($receipt_id);

        if (!$receipt) {
            return array('error' => 'Goods receipt not found');
        }

        if ($receipt['status'] != 'pending') {
            return array('error' => 'Cannot delete goods receipt in current status');
        }

        // Delete receipt items first
        $this->db->query("DELETE FROM " . DB_PREFIX . "goods_receipt_item
            WHERE goods_receipt_id = '" . (int)$receipt_id . "'");

        // Delete receipt history
        $this->db->query("DELETE FROM " . DB_PREFIX . "goods_receipt_history
            WHERE goods_receipt_id = '" . (int)$receipt_id . "'");

        // Delete receipt documents
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_document
            WHERE reference_type = 'goods_receipt' AND reference_id = '" . (int)$receipt_id . "'");

        // Delete goods receipt
        $this->db->query("DELETE FROM " . DB_PREFIX . "goods_receipt
            WHERE goods_receipt_id = '" . (int)$receipt_id . "'");

        return array('success' => true);
    }

    /**
     * Approve goods receipt
     *
     * @param int $receipt_id Goods Receipt ID
     * @param int $approved_by User ID who approved
     * @return array Result
     */
    public function approveGoodsReceipt($receipt_id, $approved_by) {
        $receipt = $this->getGoodsReceipt($receipt_id);

        if (!$receipt) {
            return array('error' => 'Goods receipt not found');
        }

        if ($receipt['status'] != 'pending') {
            return array('error' => 'Goods receipt cannot be approved in current status');
        }

        $this->db->query("UPDATE " . DB_PREFIX . "goods_receipt SET
            status = 'received',
            updated_by = '" . (int)$approved_by . "',
            updated_at = NOW()
            WHERE goods_receipt_id = '" . (int)$receipt_id . "'");

        // Add history record
        $this->addReceiptHistory(array(
            'goods_receipt_id' => $receipt_id,
            'user_id' => $approved_by,
            'action' => 'approved',
            'description' => 'Goods receipt approved'
        ));

        return array('success' => true);
    }

    /**
     * Update item quality check result
     *
     * @param int $receipt_item_id Receipt Item ID
     * @param string $quality_result Quality result (approved/rejected/partial)
     * @param string $remarks Remarks
     * @return bool Success
     */
    public function updateItemQualityCheck($receipt_item_id, $quality_result, $remarks = '') {
        $this->db->query("UPDATE " . DB_PREFIX . "goods_receipt_item SET
            quality_result = '" . $this->db->escape($quality_result) . "',
            remarks = '" . $this->db->escape($remarks) . "'
            WHERE receipt_item_id = '" . (int)$receipt_item_id . "'");

        return $this->db->countAffected() > 0;
    }

    /**
     * Get documents for goods receipt
     *
     * @param int $receipt_id Goods Receipt ID
     * @return array Documents
     */
    public function getDocuments($receipt_id) {
        return $this->getReceiptDocuments($receipt_id);
    }

}
