<?php
/**
 * نموذج تتبع التشغيلات/الدفعات وتواريخ الصلاحية
 * يستخدم لإدارة ومتابعة الأصناف حسب رقم التشغيلة وتاريخ الصلاحية
 */
class ModelInventoryBatchTracking extends Model {
    /**
     * الحصول على قائمة الدفعات/التشغيلات
     */
    public function getBatches($data = array()) {
        $sql = "SELECT b.batch_id, b.product_id, b.branch_id, b.unit_id, b.batch_number, 
                    b.quantity, b.manufacturing_date, b.expiry_date, b.status, b.notes,
                    pd.name AS product_name, br.name AS branch_name, u.desc_en AS unit_name,
                    p.expiry_warning_days
                FROM " . DB_PREFIX . "product_batch b
                LEFT JOIN " . DB_PREFIX . "product p ON (b.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch br ON (b.branch_id = br.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (b.unit_id = u.unit_id)
                WHERE 1=1";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_batch_number'])) {
            $sql .= " AND b.batch_number LIKE '%" . $this->db->escape($data['filter_batch_number']) . "%'";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND b.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_expiry_from'])) {
            $sql .= " AND b.expiry_date >= '" . $this->db->escape($data['filter_expiry_from']) . "'";
        }

        if (!empty($data['filter_expiry_to'])) {
            $sql .= " AND b.expiry_date <= '" . $this->db->escape($data['filter_expiry_to']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND b.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $sort_data = array(
            'pd.name',
            'b.batch_number',
            'br.name',
            'b.quantity',
            'b.manufacturing_date',
            'b.expiry_date',
            'b.status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY b.expiry_date";
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

    /**
     * الحصول على إجمالي عدد الدفعات/التشغيلات
     */
    public function getTotalBatches($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "product_batch b
                LEFT JOIN " . DB_PREFIX . "product p ON (b.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch br ON (b.branch_id = br.branch_id)
                WHERE 1=1";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_batch_number'])) {
            $sql .= " AND b.batch_number LIKE '%" . $this->db->escape($data['filter_batch_number']) . "%'";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND b.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_expiry_from'])) {
            $sql .= " AND b.expiry_date >= '" . $this->db->escape($data['filter_expiry_from']) . "'";
        }

        if (!empty($data['filter_expiry_to'])) {
            $sql .= " AND b.expiry_date <= '" . $this->db->escape($data['filter_expiry_to']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND b.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على معلومات دفعة/تشغيلة محددة
     */
    public function getBatch($batch_id) {
        $query = $this->db->query("SELECT b.*, pd.name AS product_name, br.name AS branch_name, u.desc_en AS unit_name, p.expiry_warning_days
                FROM " . DB_PREFIX . "product_batch b
                LEFT JOIN " . DB_PREFIX . "product p ON (b.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch br ON (b.branch_id = br.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (b.unit_id = u.unit_id)
                WHERE b.batch_id = '" . (int)$batch_id . "'");

        return $query->row;
    }

    /**
     * إضافة دفعة/تشغيلة جديدة
     */
    public function addBatch($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_batch SET 
                product_id = '" . (int)$data['product_id'] . "', 
                branch_id = '" . (int)$data['branch_id'] . "', 
                unit_id = '" . (int)$data['unit_id'] . "', 
                batch_number = '" . $this->db->escape($data['batch_number']) . "', 
                quantity = '" . (float)$data['quantity'] . "', 
                manufacturing_date = " . ($data['manufacturing_date'] ? "'" . $this->db->escape($data['manufacturing_date']) . "'" : "NULL") . ", 
                expiry_date = " . ($data['expiry_date'] ? "'" . $this->db->escape($data['expiry_date']) . "'" : "NULL") . ", 
                status = '" . $this->db->escape($data['status']) . "', 
                notes = '" . $this->db->escape($data['notes']) . "', 
                created_by = '" . (int)$this->user->getId() . "', 
                created_at = NOW()");

        $batch_id = $this->db->getLastId();

        // إضافة سجل في تاريخ الدفعة
        $this->addBatchHistory($batch_id, 'created', $data['quantity'], $this->user->getId());

        return $batch_id;
    }

    /**
     * تعديل دفعة/تشغيلة
     */
    public function editBatch($batch_id, $data) {
        // الحصول على بيانات الدفعة قبل التعديل
        $old_batch = $this->getBatch($batch_id);
        
        $this->db->query("UPDATE " . DB_PREFIX . "product_batch SET 
                product_id = '" . (int)$data['product_id'] . "', 
                branch_id = '" . (int)$data['branch_id'] . "', 
                unit_id = '" . (int)$data['unit_id'] . "', 
                batch_number = '" . $this->db->escape($data['batch_number']) . "', 
                quantity = '" . (float)$data['quantity'] . "', 
                manufacturing_date = " . ($data['manufacturing_date'] ? "'" . $this->db->escape($data['manufacturing_date']) . "'" : "NULL") . ", 
                expiry_date = " . ($data['expiry_date'] ? "'" . $this->db->escape($data['expiry_date']) . "'" : "NULL") . ", 
                status = '" . $this->db->escape($data['status']) . "', 
                notes = '" . $this->db->escape($data['notes']) . "', 
                modified_by = '" . (int)$this->user->getId() . "', 
                modified_at = NOW() 
                WHERE batch_id = '" . (int)$batch_id . "'");

        // إضافة سجل في تاريخ الدفعة إذا تغيرت الكمية
        if ($old_batch['quantity'] != $data['quantity']) {
            $quantity_change = $data['quantity'] - $old_batch['quantity'];
            $action = ($quantity_change > 0) ? 'increased' : 'decreased';
            $this->addBatchHistory($batch_id, $action, abs($quantity_change), $this->user->getId());
        }

        // إضافة سجل في تاريخ الدفعة إذا تغيرت الحالة
        if ($old_batch['status'] != $data['status']) {
            $this->addBatchHistory($batch_id, 'status_changed', 0, $this->user->getId(), 'Status changed from ' . $old_batch['status'] . ' to ' . $data['status']);
        }
    }

    /**
     * حذف دفعة/تشغيلة
     */
    public function deleteBatch($batch_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_batch WHERE batch_id = '" . (int)$batch_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_batch_history WHERE batch_id = '" . (int)$batch_id . "'");
    }

    /**
     * إضافة سجل في تاريخ الدفعة/التشغيلة
     */
    public function addBatchHistory($batch_id, $action, $quantity, $user_id, $notes = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_batch_history SET 
                batch_id = '" . (int)$batch_id . "', 
                action = '" . $this->db->escape($action) . "', 
                quantity = '" . (float)$quantity . "', 
                user_id = '" . (int)$user_id . "', 
                notes = '" . $this->db->escape($notes) . "', 
                created_at = NOW()");
    }

    /**
     * الحصول على تاريخ حركة الدفعة/التشغيلة
     */
    public function getBatchHistory($batch_id) {
        $query = $this->db->query("SELECT h.*, CONCAT(u.firstname, ' ', u.lastname) AS username 
                FROM " . DB_PREFIX . "product_batch_history h 
                LEFT JOIN " . DB_PREFIX . "user u ON (h.user_id = u.user_id) 
                WHERE h.batch_id = '" . (int)$batch_id . "' 
                ORDER BY h.created_at DESC");

        return $query->rows;
    }

    /**
     * الحصول على المنتجات قريبة انتهاء الصلاحية
     */
    public function getExpiringProducts($data = array()) {
        $sql = "SELECT b.batch_id, b.product_id, b.branch_id, b.unit_id, b.batch_number, 
                    b.quantity, b.manufacturing_date, b.expiry_date, b.status, b.notes,
                    pd.name AS product_name, br.name AS branch_name, u.desc_en AS unit_name,
                    p.expiry_warning_days,
                    DATEDIFF(b.expiry_date, CURDATE()) AS days_remaining
                FROM " . DB_PREFIX . "product_batch b
                LEFT JOIN " . DB_PREFIX . "product p ON (b.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch br ON (b.branch_id = br.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (b.unit_id = u.unit_id)
                WHERE b.quantity > 0 
                AND b.expiry_date IS NOT NULL 
                AND b.expiry_date >= CURDATE()";

        if (!empty($data['filter_days'])) {
            $sql .= " AND DATEDIFF(b.expiry_date, CURDATE()) <= '" . (int)$data['filter_days'] . "'";
        } else {
            $sql .= " AND DATEDIFF(b.expiry_date, CURDATE()) <= p.expiry_warning_days";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND b.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        $sql .= " ORDER BY days_remaining ASC";

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
     * الحصول على إجمالي عدد المنتجات قريبة انتهاء الصلاحية
     */
    public function getTotalExpiringProducts($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "product_batch b
                LEFT JOIN " . DB_PREFIX . "product p ON (b.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch br ON (b.branch_id = br.branch_id)
                WHERE b.quantity > 0 
                AND b.expiry_date IS NOT NULL 
                AND b.expiry_date >= CURDATE()";

        if (!empty($data['filter_days'])) {
            $sql .= " AND DATEDIFF(b.expiry_date, CURDATE()) <= '" . (int)$data['filter_days'] . "'";
        } else {
            $sql .= " AND DATEDIFF(b.expiry_date, CURDATE()) <= p.expiry_warning_days";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND b.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
}
