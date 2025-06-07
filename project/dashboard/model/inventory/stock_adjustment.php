<?php
/**
 * نموذج التسويات المخزنية المتطور (Advanced Stock Adjustment Model) - الجزء الأول
 *
 * الهدف: توفير نظام تسويات شامل مع موافقات متعددة المستويات وتتبع الأسباب
 * الميزات: تسويات يدوية/تلقائية، workflow متقدم، ربط محاسبي، تنبيهات ذكية
 * التكامل: مع المحاسبة والجرد والموافقات والتقارير والتنبيهات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryStockAdjustment extends Model {

    /**
     * الحصول على التسويات مع فلاتر متقدمة
     */
    public function getStockAdjustments($data = array()) {
        $sql = "
            SELECT
                sa.adjustment_id,
                sa.adjustment_number,
                sa.adjustment_name,
                sa.adjustment_type,
                sa.status,
                sa.branch_id,
                b.name as branch_name,
                b.type as branch_type,
                sa.reason_id,
                sar.name as reason_name,
                sar.category as reason_category,
                sa.reference_type,
                sa.reference_id,
                sa.reference_number,
                sa.user_id,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                sa.approved_by,
                CONCAT(u2.firstname, ' ', u2.lastname) as approved_by_name,
                sa.adjustment_date,
                sa.approval_date,
                sa.notes,
                sa.date_added,
                sa.date_modified,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai
                 WHERE sai.adjustment_id = sa.adjustment_id) as total_items,
                (SELECT SUM(ABS(sai.quantity)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai
                 WHERE sai.adjustment_id = sa.adjustment_id) as total_quantity,
                (SELECT SUM(ABS(sai.quantity * sai.unit_cost)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai
                 WHERE sai.adjustment_id = sa.adjustment_id) as total_value,
                (SELECT SUM(sai.quantity) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai
                 WHERE sai.adjustment_id = sa.adjustment_id
                 AND sai.quantity > 0) as total_increase_quantity,
                (SELECT SUM(ABS(sai.quantity)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai
                 WHERE sai.adjustment_id = sa.adjustment_id
                 AND sai.quantity < 0) as total_decrease_quantity,
                (SELECT SUM(sai.quantity * sai.unit_cost) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai
                 WHERE sai.adjustment_id = sa.adjustment_id
                 AND sai.quantity > 0) as total_increase_value,
                (SELECT SUM(ABS(sai.quantity * sai.unit_cost)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai
                 WHERE sai.adjustment_id = sa.adjustment_id
                 AND sai.quantity < 0) as total_decrease_value,
                CASE sa.status
                    WHEN 'draft' THEN 'مسودة'
                    WHEN 'pending_approval' THEN 'في انتظار الموافقة'
                    WHEN 'approved' THEN 'معتمد'
                    WHEN 'posted' THEN 'مرحل'
                    WHEN 'rejected' THEN 'مرفوض'
                    WHEN 'cancelled' THEN 'ملغي'
                    ELSE sa.status
                END as status_text,
                CASE sa.adjustment_type
                    WHEN 'manual' THEN 'تسوية يدوية'
                    WHEN 'counting' THEN 'تسوية من الجرد'
                    WHEN 'damage' THEN 'تسوية تلف'
                    WHEN 'loss' THEN 'تسوية فقدان'
                    WHEN 'found' THEN 'تسوية عثور'
                    WHEN 'expiry' THEN 'تسوية انتهاء صلاحية'
                    WHEN 'system' THEN 'تسوية نظام'
                    ELSE sa.adjustment_type
                END as adjustment_type_text,
                CASE sar.category
                    WHEN 'increase' THEN 'زيادة'
                    WHEN 'decrease' THEN 'نقص'
                    WHEN 'correction' THEN 'تصحيح'
                    WHEN 'transfer' THEN 'تحويل'
                    ELSE sar.category
                END as reason_category_text
            FROM " . DB_PREFIX . "cod_stock_adjustment sa
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (sa.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_stock_adjustment_reason sar ON (sa.reason_id = sar.reason_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (sa.user_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user u2 ON (sa.approved_by = u2.user_id)
            WHERE 1=1
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_adjustment_number'])) {
            $sql .= " AND sa.adjustment_number LIKE '%" . $this->db->escape($data['filter_adjustment_number']) . "%'";
        }

        if (!empty($data['filter_adjustment_name'])) {
            $sql .= " AND sa.adjustment_name LIKE '%" . $this->db->escape($data['filter_adjustment_name']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND sa.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_adjustment_type'])) {
            $sql .= " AND sa.adjustment_type = '" . $this->db->escape($data['filter_adjustment_type']) . "'";
        }

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND sa.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_reason_id'])) {
            $sql .= " AND sa.reason_id = '" . (int)$data['filter_reason_id'] . "'";
        }

        if (!empty($data['filter_reason_category'])) {
            $sql .= " AND sar.category = '" . $this->db->escape($data['filter_reason_category']) . "'";
        }

        if (!empty($data['filter_user_id'])) {
            $sql .= " AND sa.user_id = '" . (int)$data['filter_user_id'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(sa.adjustment_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(sa.adjustment_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        if (!empty($data['filter_min_value'])) {
            $sql .= " AND (SELECT SUM(ABS(sai.quantity * sai.unit_cost)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai WHERE sai.adjustment_id = sa.adjustment_id) >= '" . (float)$data['filter_min_value'] . "'";
        }

        if (!empty($data['filter_max_value'])) {
            $sql .= " AND (SELECT SUM(ABS(sai.quantity * sai.unit_cost)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai WHERE sai.adjustment_id = sa.adjustment_id) <= '" . (float)$data['filter_max_value'] . "'";
        }

        // ترتيب النتائج
        $sort_data = array(
            'sa.adjustment_number',
            'sa.adjustment_name',
            'sa.status',
            'sa.adjustment_type',
            'b.name',
            'sar.name',
            'sa.adjustment_date',
            'sa.date_added',
            'total_items',
            'total_value'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sa.date_added DESC";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
        }

        // تحديد عدد النتائج
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
     * الحصول على إجمالي عدد التسويات
     */
    public function getTotalStockAdjustments($data = array()) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "cod_stock_adjustment sa
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (sa.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_stock_adjustment_reason sar ON (sa.reason_id = sar.reason_id)
            WHERE 1=1
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_adjustment_number'])) {
            $sql .= " AND sa.adjustment_number LIKE '%" . $this->db->escape($data['filter_adjustment_number']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND sa.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND sa.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على تسوية محددة
     */
    public function getStockAdjustment($adjustment_id) {
        $query = $this->db->query("
            SELECT
                sa.*,
                b.name as branch_name,
                sar.name as reason_name,
                sar.category as reason_category,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                CONCAT(u2.firstname, ' ', u2.lastname) as approved_by_name
            FROM " . DB_PREFIX . "cod_stock_adjustment sa
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (sa.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_stock_adjustment_reason sar ON (sa.reason_id = sar.reason_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (sa.user_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user u2 ON (sa.approved_by = u2.user_id)
            WHERE sa.adjustment_id = '" . (int)$adjustment_id . "'
        ");

        return $query->row;
    }

    /**
     * إضافة تسوية جديدة
     */
    public function addStockAdjustment($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_stock_adjustment
            SET adjustment_number = '" . $this->db->escape($data['adjustment_number']) . "',
                adjustment_name = '" . $this->db->escape($data['adjustment_name']) . "',
                adjustment_type = '" . $this->db->escape($data['adjustment_type']) . "',
                status = 'draft',
                branch_id = '" . (int)$data['branch_id'] . "',
                reason_id = " . (!empty($data['reason_id']) ? "'" . (int)$data['reason_id'] . "'" : "NULL") . ",
                reference_type = " . (!empty($data['reference_type']) ? "'" . $this->db->escape($data['reference_type']) . "'" : "NULL") . ",
                reference_id = " . (!empty($data['reference_id']) ? "'" . (int)$data['reference_id'] . "'" : "NULL") . ",
                reference_number = " . (!empty($data['reference_number']) ? "'" . $this->db->escape($data['reference_number']) . "'" : "NULL") . ",
                user_id = '" . (int)$this->user->getId() . "',
                adjustment_date = '" . $this->db->escape($data['adjustment_date']) . "',
                notes = '" . $this->db->escape($data['notes']) . "',
                date_added = NOW(),
                date_modified = NOW()
        ");

        $adjustment_id = $this->db->getLastId();

        // إضافة عناصر التسوية
        if (isset($data['adjustment_items'])) {
            foreach ($data['adjustment_items'] as $item) {
                $this->addAdjustmentItem($adjustment_id, $item);
            }
        }

        return $adjustment_id;
    }

    /**
     * تحديث تسوية
     */
    public function editStockAdjustment($adjustment_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_stock_adjustment
            SET adjustment_name = '" . $this->db->escape($data['adjustment_name']) . "',
                adjustment_type = '" . $this->db->escape($data['adjustment_type']) . "',
                branch_id = '" . (int)$data['branch_id'] . "',
                reason_id = " . (!empty($data['reason_id']) ? "'" . (int)$data['reason_id'] . "'" : "NULL") . ",
                reference_type = " . (!empty($data['reference_type']) ? "'" . $this->db->escape($data['reference_type']) . "'" : "NULL") . ",
                reference_id = " . (!empty($data['reference_id']) ? "'" . (int)$data['reference_id'] . "'" : "NULL") . ",
                reference_number = " . (!empty($data['reference_number']) ? "'" . $this->db->escape($data['reference_number']) . "'" : "NULL") . ",
                adjustment_date = '" . $this->db->escape($data['adjustment_date']) . "',
                notes = '" . $this->db->escape($data['notes']) . "',
                date_modified = NOW()
            WHERE adjustment_id = '" . (int)$adjustment_id . "'
            AND status = 'draft'
        ");

        // تحديث عناصر التسوية
        if (isset($data['adjustment_items'])) {
            // حذف العناصر الموجودة
            $this->db->query("DELETE FROM " . DB_PREFIX . "cod_stock_adjustment_item WHERE adjustment_id = '" . (int)$adjustment_id . "'");

            // إضافة العناصر الجديدة
            foreach ($data['adjustment_items'] as $item) {
                $this->addAdjustmentItem($adjustment_id, $item);
            }
        }
    }

    /**
     * حذف تسوية
     */
    public function deleteStockAdjustment($adjustment_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_stock_adjustment WHERE adjustment_id = '" . (int)$adjustment_id . "' AND status = 'draft'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_stock_adjustment_item WHERE adjustment_id = '" . (int)$adjustment_id . "'");
    }

    /**
     * إضافة عنصر تسوية
     */
    public function addAdjustmentItem($adjustment_id, $item_data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_stock_adjustment_item
            SET adjustment_id = '" . (int)$adjustment_id . "',
                product_id = '" . (int)$item_data['product_id'] . "',
                quantity = '" . (float)$item_data['quantity'] . "',
                unit_cost = '" . (float)$item_data['unit_cost'] . "',
                unit_id = '" . (int)$item_data['unit_id'] . "',
                lot_number = " . (!empty($item_data['lot_number']) ? "'" . $this->db->escape($item_data['lot_number']) . "'" : "NULL") . ",
                expiry_date = " . (!empty($item_data['expiry_date']) ? "'" . $this->db->escape($item_data['expiry_date']) . "'" : "NULL") . ",
                reason = '" . $this->db->escape($item_data['reason']) . "',
                notes = '" . $this->db->escape($item_data['notes']) . "',
                date_added = NOW()
        ");
    }

    /**
     * الحصول على عناصر التسوية
     */
    public function getAdjustmentItems($adjustment_id) {
        $sql = "
            SELECT
                sai.*,
                p.product_id,
                pd.name as product_name,
                p.model,
                p.sku,
                ud.name as unit_name,
                u.symbol as unit_symbol,
                (sai.quantity * sai.unit_cost) as total_cost
            FROM " . DB_PREFIX . "cod_stock_adjustment_item sai
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (sai.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (sai.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (sai.unit_id = u.unit_id)
            WHERE sai.adjustment_id = '" . (int)$adjustment_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY pd.name ASC
        ";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * تغيير حالة التسوية
     */
    public function changeStatus($adjustment_id, $status, $notes = '') {
        $current_user_id = $this->user->getId();

        $update_fields = array(
            "status = '" . $this->db->escape($status) . "'",
            "date_modified = NOW()"
        );

        // إضافة حقول خاصة بكل حالة
        switch ($status) {
            case 'pending_approval':
                $update_fields[] = "submitted_date = NOW()";
                break;
            case 'approved':
                $update_fields[] = "approved_by = '" . (int)$current_user_id . "'";
                $update_fields[] = "approval_date = NOW()";
                break;
            case 'rejected':
                $update_fields[] = "rejected_by = '" . (int)$current_user_id . "'";
                $update_fields[] = "rejection_date = NOW()";
                if ($notes) {
                    $update_fields[] = "rejection_reason = '" . $this->db->escape($notes) . "'";
                }
                break;
            case 'posted':
                $update_fields[] = "posted_by = '" . (int)$current_user_id . "'";
                $update_fields[] = "posted_date = NOW()";
                break;
        }

        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_stock_adjustment
            SET " . implode(', ', $update_fields) . "
            WHERE adjustment_id = '" . (int)$adjustment_id . "'
        ");

        // إذا تم ترحيل التسوية، تحديث المخزون
        if ($status == 'posted') {
            $this->postAdjustment($adjustment_id);
        }

        // إضافة سجل في تاريخ الموافقات
        $this->addApprovalHistory($adjustment_id, $status, $notes);
    }

    /**
     * ترحيل التسوية وتحديث المخزون
     */
    public function postAdjustment($adjustment_id) {
        // الحصول على معلومات التسوية
        $adjustment = $this->getStockAdjustment($adjustment_id);
        $items = $this->getAdjustmentItems($adjustment_id);

        foreach ($items as $item) {
            // تحديث كمية المخزون
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_product_inventory
                SET quantity = quantity + '" . (float)$item['quantity'] . "'
                WHERE product_id = '" . (int)$item['product_id'] . "'
                AND branch_id = '" . (int)$adjustment['branch_id'] . "'
            ");

            // إضافة حركة مخزنية
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cod_product_movement
                SET product_id = '" . (int)$item['product_id'] . "',
                    branch_id = '" . (int)$adjustment['branch_id'] . "',
                    movement_type = '" . ($item['quantity'] > 0 ? 'adjustment_in' : 'adjustment_out') . "',
                    reference_type = 'stock_adjustment',
                    reference_id = '" . (int)$adjustment_id . "',
                    reference_number = '" . $this->db->escape($adjustment['adjustment_number']) . "',
                    lot_number = " . (!empty($item['lot_number']) ? "'" . $this->db->escape($item['lot_number']) . "'" : "NULL") . ",
                    expiry_date = " . (!empty($item['expiry_date']) ? "'" . $this->db->escape($item['expiry_date']) . "'" : "NULL") . ",
                    unit_id = '" . (int)$item['unit_id'] . "',
                    quantity_in = '" . ($item['quantity'] > 0 ? (float)$item['quantity'] : 0) . "',
                    quantity_out = '" . ($item['quantity'] < 0 ? (float)abs($item['quantity']) : 0) . "',
                    unit_cost = '" . (float)$item['unit_cost'] . "',
                    total_cost = '" . (float)($item['quantity'] * $item['unit_cost']) . "',
                    user_id = '" . (int)$this->user->getId() . "',
                    notes = '" . $this->db->escape($item['reason']) . "',
                    date_added = NOW()
            ");
        }

        // إنشاء قيد محاسبي إذا كان مفعل
        if ($this->config->get('config_accounting_integration')) {
            $this->createAccountingEntry($adjustment_id);
        }
    }

    /**
     * إنشاء قيد محاسبي للتسوية
     */
    private function createAccountingEntry($adjustment_id) {
        // سيتم تطوير هذه الوظيفة في الجزء التالي
        // لربط التسويات بالنظام المحاسبي
    }

    /**
     * إضافة سجل في تاريخ الموافقات
     */
    private function addApprovalHistory($adjustment_id, $status, $notes = '') {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_stock_adjustment_approval_history
            SET adjustment_id = '" . (int)$adjustment_id . "',
                status = '" . $this->db->escape($status) . "',
                user_id = '" . (int)$this->user->getId() . "',
                notes = '" . $this->db->escape($notes) . "',
                date_added = NOW()
        ");
    }

    /**
     * الحصول على ملخص التسويات
     */
    public function getAdjustmentSummary($data = array()) {
        $sql = "
            SELECT
                COUNT(*) as total_adjustments,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending_approval_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                (SELECT SUM(ABS(sai.quantity * sai.unit_cost))
                 FROM " . DB_PREFIX . "cod_stock_adjustment_item sai
                 INNER JOIN " . DB_PREFIX . "cod_stock_adjustment sa2 ON (sai.adjustment_id = sa2.adjustment_id)
                 WHERE sa2.status = 'posted'
                 AND sai.quantity > 0) as total_increase_value,
                (SELECT SUM(ABS(sai.quantity * sai.unit_cost))
                 FROM " . DB_PREFIX . "cod_stock_adjustment_item sai
                 INNER JOIN " . DB_PREFIX . "cod_stock_adjustment sa2 ON (sai.adjustment_id = sa2.adjustment_id)
                 WHERE sa2.status = 'posted'
                 AND sai.quantity < 0) as total_decrease_value,
                AVG((SELECT COUNT(*) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai WHERE sai.adjustment_id = sa.adjustment_id)) as avg_items_per_adjustment
            FROM " . DB_PREFIX . "cod_stock_adjustment sa
            WHERE 1=1
        ";

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND sa.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(sa.adjustment_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(sa.adjustment_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row;
    }

    /**
     * الحصول على التسويات حسب السبب
     */
    public function getAdjustmentsByReason($data = array()) {
        $sql = "
            SELECT
                sar.reason_id,
                sar.name as reason_name,
                sar.category as reason_category,
                COUNT(sa.adjustment_id) as adjustment_count,
                SUM((SELECT SUM(ABS(sai.quantity * sai.unit_cost)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai WHERE sai.adjustment_id = sa.adjustment_id)) as total_value,
                AVG((SELECT SUM(ABS(sai.quantity * sai.unit_cost)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai WHERE sai.adjustment_id = sa.adjustment_id)) as avg_value
            FROM " . DB_PREFIX . "cod_stock_adjustment sa
            INNER JOIN " . DB_PREFIX . "cod_stock_adjustment_reason sar ON (sa.reason_id = sar.reason_id)
            WHERE sa.status = 'posted'
        ";

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND sa.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(sa.adjustment_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(sa.adjustment_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " GROUP BY sar.reason_id ORDER BY total_value DESC LIMIT 10";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على التسويات حسب الفرع
     */
    public function getAdjustmentsByBranch($data = array()) {
        $sql = "
            SELECT
                b.branch_id,
                b.name as branch_name,
                b.type as branch_type,
                COUNT(sa.adjustment_id) as adjustment_count,
                SUM((SELECT SUM(ABS(sai.quantity * sai.unit_cost)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai WHERE sai.adjustment_id = sa.adjustment_id)) as total_value,
                SUM((SELECT SUM(sai.quantity * sai.unit_cost) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai WHERE sai.adjustment_id = sa.adjustment_id AND sai.quantity > 0)) as increase_value,
                SUM((SELECT SUM(ABS(sai.quantity * sai.unit_cost)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai WHERE sai.adjustment_id = sa.adjustment_id AND sai.quantity < 0)) as decrease_value
            FROM " . DB_PREFIX . "cod_stock_adjustment sa
            INNER JOIN " . DB_PREFIX . "cod_branch b ON (sa.branch_id = b.branch_id)
            WHERE sa.status = 'posted'
        ";

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(sa.adjustment_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(sa.adjustment_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " GROUP BY b.branch_id ORDER BY total_value DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على أكبر التسويات قيمة
     */
    public function getTopValueAdjustments($data = array()) {
        $sql = "
            SELECT
                sa.adjustment_id,
                sa.adjustment_number,
                sa.adjustment_name,
                sa.adjustment_type,
                b.name as branch_name,
                sar.name as reason_name,
                sa.adjustment_date,
                (SELECT SUM(ABS(sai.quantity * sai.unit_cost)) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai WHERE sai.adjustment_id = sa.adjustment_id) as total_value,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_stock_adjustment_item sai WHERE sai.adjustment_id = sa.adjustment_id) as total_items
            FROM " . DB_PREFIX . "cod_stock_adjustment sa
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (sa.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_stock_adjustment_reason sar ON (sa.reason_id = sar.reason_id)
            WHERE sa.status = 'posted'
        ";

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND sa.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(sa.adjustment_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(sa.adjustment_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " ORDER BY total_value DESC LIMIT 5";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على أسباب التسوية
     */
    public function getAdjustmentReasons($category = '') {
        $sql = "
            SELECT * FROM " . DB_PREFIX . "cod_stock_adjustment_reason
            WHERE status = 1
        ";

        if ($category) {
            $sql .= " AND category = '" . $this->db->escape($category) . "'";
        }

        $sql .= " ORDER BY sort_order ASC, name ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * توليد رقم تسوية جديد
     */
    public function generateAdjustmentNumber() {
        $query = $this->db->query("
            SELECT adjustment_number
            FROM " . DB_PREFIX . "cod_stock_adjustment
            WHERE adjustment_number LIKE 'ADJ-" . date('Y') . "-%'
            ORDER BY adjustment_id DESC
            LIMIT 1
        ");

        if ($query->num_rows > 0) {
            $last_number = $query->row['adjustment_number'];
            $number_part = (int)substr($last_number, -6);
            $new_number = $number_part + 1;
        } else {
            $new_number = 1;
        }

        return 'ADJ-' . date('Y') . '-' . str_pad($new_number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * التحقق من صلاحيات الموافقة
     */
    public function canApprove($adjustment_id, $user_id) {
        // الحصول على معلومات التسوية
        $adjustment = $this->getStockAdjustment($adjustment_id);

        if (!$adjustment) {
            return false;
        }

        // لا يمكن للمستخدم الموافقة على تسويته الخاصة
        if ($adjustment['user_id'] == $user_id) {
            return false;
        }

        // التحقق من قيمة التسوية وصلاحيات المستخدم
        $total_value = $this->db->query("
            SELECT SUM(ABS(quantity * unit_cost)) as total_value
            FROM " . DB_PREFIX . "cod_stock_adjustment_item
            WHERE adjustment_id = '" . (int)$adjustment_id . "'
        ");

        $adjustment_value = $total_value->row['total_value'];

        // الحصول على حد الموافقة للمستخدم
        $user_approval_limit = $this->db->query("
            SELECT approval_limit
            FROM " . DB_PREFIX . "user
            WHERE user_id = '" . (int)$user_id . "'
        ");

        if ($user_approval_limit->num_rows > 0) {
            $approval_limit = $user_approval_limit->row['approval_limit'];
            return $adjustment_value <= $approval_limit;
        }

        return false;
    }

    /**
     * إرسال تنبيهات التسوية
     */
    public function sendAdjustmentNotifications($adjustment_id, $action) {
        $adjustment = $this->getStockAdjustment($adjustment_id);

        if (!$adjustment) {
            return;
        }

        // تحديد المستلمين حسب الإجراء
        $recipients = array();

        switch ($action) {
            case 'submitted':
                // إرسال للمديرين والمعتمدين
                $recipients = $this->getApprovers($adjustment_id);
                break;
            case 'approved':
                // إرسال لمنشئ التسوية
                $recipients[] = $adjustment['user_id'];
                break;
            case 'rejected':
                // إرسال لمنشئ التسوية
                $recipients[] = $adjustment['user_id'];
                break;
            case 'posted':
                // إرسال للمحاسبين ومديري المخزون
                $recipients = $this->getInventoryManagers();
                break;
        }

        // إرسال الإشعارات
        foreach ($recipients as $user_id) {
            $this->sendNotification($user_id, $adjustment_id, $action);
        }
    }

    /**
     * الحصول على المعتمدين
     */
    private function getApprovers($adjustment_id) {
        $query = $this->db->query("
            SELECT user_id
            FROM " . DB_PREFIX . "user
            WHERE status = 1
            AND (user_group_id IN (1, 2) OR approval_limit > 0)
        ");

        $approvers = array();
        foreach ($query->rows as $row) {
            $approvers[] = $row['user_id'];
        }

        return $approvers;
    }

    /**
     * الحصول على مديري المخزون
     */
    private function getInventoryManagers() {
        $query = $this->db->query("
            SELECT user_id
            FROM " . DB_PREFIX . "user
            WHERE status = 1
            AND user_group_id IN (1, 2, 3)
        ");

        $managers = array();
        foreach ($query->rows as $row) {
            $managers[] = $row['user_id'];
        }

        return $managers;
    }

    /**
     * إرسال إشعار
     */
    private function sendNotification($user_id, $adjustment_id, $action) {
        // سيتم تطوير نظام الإشعارات في الجزء التالي
        // لإرسال إشعارات البريد الإلكتروني والرسائل النصية
    }

    /**
     * تصدير البيانات للإكسل
     */
    public function exportToExcel($data = array()) {
        // إزالة حدود الصفحات للتصدير
        unset($data['start']);
        unset($data['limit']);

        return $this->getStockAdjustments($data);
    }

    /**
     * الحصول على تاريخ الموافقات
     */
    public function getApprovalHistory($adjustment_id) {
        $query = $this->db->query("
            SELECT
                sah.*,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                CASE sah.status
                    WHEN 'draft' THEN 'مسودة'
                    WHEN 'pending_approval' THEN 'في انتظار الموافقة'
                    WHEN 'approved' THEN 'معتمد'
                    WHEN 'posted' THEN 'مرحل'
                    WHEN 'rejected' THEN 'مرفوض'
                    WHEN 'cancelled' THEN 'ملغي'
                    ELSE sah.status
                END as status_text
            FROM " . DB_PREFIX . "cod_stock_adjustment_approval_history sah
            LEFT JOIN " . DB_PREFIX . "user u ON (sah.user_id = u.user_id)
            WHERE sah.adjustment_id = '" . (int)$adjustment_id . "'
            ORDER BY sah.date_added ASC
        ");

        return $query->rows;
    }
}
