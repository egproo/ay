<?php
/**
 * نموذج نقل المخزون بين الفروع المتطور (Advanced Stock Transfer Model) - الجزء الأول
 *
 * الهدف: توفير نظام نقل شامل بين المستودعات والمتاجر مع workflow متقدم
 * الميزات: طلبات نقل، موافقات، تتبع الشحنات، تسويات تلقائية، تقارير متقدمة
 * التكامل: مع المخزون والجرد والتسويات والشحن والإشعارات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryStockTransfer extends Model {

    /**
     * الحصول على طلبات النقل مع فلاتر متقدمة
     */
    public function getStockTransfers($data = array()) {
        $sql = "
            SELECT
                st.transfer_id,
                st.transfer_number,
                st.transfer_name,
                st.transfer_type,
                st.status,
                st.priority,
                st.from_branch_id,
                bf.name as from_branch_name,
                bf.type as from_branch_type,
                st.to_branch_id,
                bt.name as to_branch_name,
                bt.type as to_branch_type,
                st.reason_id,
                str.name as reason_name,
                st.user_id,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                st.approved_by,
                CONCAT(u2.firstname, ' ', u2.lastname) as approved_by_name,
                st.shipped_by,
                CONCAT(u3.firstname, ' ', u3.lastname) as shipped_by_name,
                st.received_by,
                CONCAT(u4.firstname, ' ', u4.lastname) as received_by_name,
                st.request_date,
                st.approval_date,
                st.ship_date,
                st.expected_delivery_date,
                st.actual_delivery_date,
                st.notes,
                st.date_added,
                st.date_modified,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_stock_transfer_item sti
                 WHERE sti.transfer_id = st.transfer_id) as total_items,
                (SELECT SUM(sti.quantity) FROM " . DB_PREFIX . "cod_stock_transfer_item sti
                 WHERE sti.transfer_id = st.transfer_id) as total_quantity,
                (SELECT SUM(sti.quantity * sti.unit_cost) FROM " . DB_PREFIX . "cod_stock_transfer_item sti
                 WHERE sti.transfer_id = st.transfer_id) as total_value,
                (SELECT SUM(CASE WHEN sti.received_quantity IS NOT NULL THEN sti.received_quantity ELSE 0 END)
                 FROM " . DB_PREFIX . "cod_stock_transfer_item sti
                 WHERE sti.transfer_id = st.transfer_id) as total_received_quantity,
                CASE st.status
                    WHEN 'draft' THEN 'مسودة'
                    WHEN 'pending_approval' THEN 'في انتظار الموافقة'
                    WHEN 'approved' THEN 'معتمد'
                    WHEN 'shipped' THEN 'تم الشحن'
                    WHEN 'in_transit' THEN 'في الطريق'
                    WHEN 'delivered' THEN 'تم التسليم'
                    WHEN 'received' THEN 'تم الاستلام'
                    WHEN 'completed' THEN 'مكتمل'
                    WHEN 'cancelled' THEN 'ملغي'
                    WHEN 'rejected' THEN 'مرفوض'
                    ELSE st.status
                END as status_text,
                CASE st.transfer_type
                    WHEN 'regular' THEN 'نقل عادي'
                    WHEN 'emergency' THEN 'نقل طارئ'
                    WHEN 'restock' THEN 'إعادة تخزين'
                    WHEN 'redistribution' THEN 'إعادة توزيع'
                    WHEN 'return' THEN 'إرجاع'
                    ELSE st.transfer_type
                END as transfer_type_text,
                CASE st.priority
                    WHEN 'low' THEN 'منخفضة'
                    WHEN 'normal' THEN 'عادية'
                    WHEN 'high' THEN 'عالية'
                    WHEN 'urgent' THEN 'عاجلة'
                    ELSE st.priority
                END as priority_text
            FROM " . DB_PREFIX . "cod_stock_transfer st
            LEFT JOIN " . DB_PREFIX . "cod_branch bf ON (st.from_branch_id = bf.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch bt ON (st.to_branch_id = bt.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_stock_transfer_reason str ON (st.reason_id = str.reason_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (st.user_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user u2 ON (st.approved_by = u2.user_id)
            LEFT JOIN " . DB_PREFIX . "user u3 ON (st.shipped_by = u3.user_id)
            LEFT JOIN " . DB_PREFIX . "user u4 ON (st.received_by = u4.user_id)
            WHERE 1=1
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_transfer_number'])) {
            $sql .= " AND st.transfer_number LIKE '%" . $this->db->escape($data['filter_transfer_number']) . "%'";
        }

        if (!empty($data['filter_transfer_name'])) {
            $sql .= " AND st.transfer_name LIKE '%" . $this->db->escape($data['filter_transfer_name']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND st.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_transfer_type'])) {
            $sql .= " AND st.transfer_type = '" . $this->db->escape($data['filter_transfer_type']) . "'";
        }

        if (!empty($data['filter_priority'])) {
            $sql .= " AND st.priority = '" . $this->db->escape($data['filter_priority']) . "'";
        }

        if (!empty($data['filter_from_branch_id'])) {
            $sql .= " AND st.from_branch_id = '" . (int)$data['filter_from_branch_id'] . "'";
        }

        if (!empty($data['filter_to_branch_id'])) {
            $sql .= " AND st.to_branch_id = '" . (int)$data['filter_to_branch_id'] . "'";
        }

        if (!empty($data['filter_reason_id'])) {
            $sql .= " AND st.reason_id = '" . (int)$data['filter_reason_id'] . "'";
        }

        if (!empty($data['filter_user_id'])) {
            $sql .= " AND st.user_id = '" . (int)$data['filter_user_id'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(st.request_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(st.request_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        if (!empty($data['filter_min_value'])) {
            $sql .= " AND (SELECT SUM(sti.quantity * sti.unit_cost) FROM " . DB_PREFIX . "cod_stock_transfer_item sti WHERE sti.transfer_id = st.transfer_id) >= '" . (float)$data['filter_min_value'] . "'";
        }

        if (!empty($data['filter_max_value'])) {
            $sql .= " AND (SELECT SUM(sti.quantity * sti.unit_cost) FROM " . DB_PREFIX . "cod_stock_transfer_item sti WHERE sti.transfer_id = st.transfer_id) <= '" . (float)$data['filter_max_value'] . "'";
        }

        // ترتيب النتائج
        $sort_data = array(
            'st.transfer_number',
            'st.transfer_name',
            'st.status',
            'st.transfer_type',
            'st.priority',
            'bf.name',
            'bt.name',
            'st.request_date',
            'st.date_added',
            'total_items',
            'total_value'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY st.date_added DESC";
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
     * الحصول على إجمالي عدد طلبات النقل
     */
    public function getTotalStockTransfers($data = array()) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "cod_stock_transfer st
            LEFT JOIN " . DB_PREFIX . "cod_branch bf ON (st.from_branch_id = bf.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch bt ON (st.to_branch_id = bt.branch_id)
            WHERE 1=1
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_transfer_number'])) {
            $sql .= " AND st.transfer_number LIKE '%" . $this->db->escape($data['filter_transfer_number']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND st.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_from_branch_id'])) {
            $sql .= " AND st.from_branch_id = '" . (int)$data['filter_from_branch_id'] . "'";
        }

        if (!empty($data['filter_to_branch_id'])) {
            $sql .= " AND st.to_branch_id = '" . (int)$data['filter_to_branch_id'] . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على طلب نقل محدد
     */
    public function getStockTransfer($transfer_id) {
        $query = $this->db->query("
            SELECT
                st.*,
                bf.name as from_branch_name,
                bt.name as to_branch_name,
                str.name as reason_name,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                CONCAT(u2.firstname, ' ', u2.lastname) as approved_by_name,
                CONCAT(u3.firstname, ' ', u3.lastname) as shipped_by_name,
                CONCAT(u4.firstname, ' ', u4.lastname) as received_by_name
            FROM " . DB_PREFIX . "cod_stock_transfer st
            LEFT JOIN " . DB_PREFIX . "cod_branch bf ON (st.from_branch_id = bf.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch bt ON (st.to_branch_id = bt.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_stock_transfer_reason str ON (st.reason_id = str.reason_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (st.user_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user u2 ON (st.approved_by = u2.user_id)
            LEFT JOIN " . DB_PREFIX . "user u3 ON (st.shipped_by = u3.user_id)
            LEFT JOIN " . DB_PREFIX . "user u4 ON (st.received_by = u4.user_id)
            WHERE st.transfer_id = '" . (int)$transfer_id . "'
        ");

        return $query->row;
    }

    /**
     * إضافة طلب نقل جديد
     */
    public function addStockTransfer($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_stock_transfer
            SET transfer_number = '" . $this->db->escape($data['transfer_number']) . "',
                transfer_name = '" . $this->db->escape($data['transfer_name']) . "',
                transfer_type = '" . $this->db->escape($data['transfer_type']) . "',
                status = 'draft',
                priority = '" . $this->db->escape($data['priority']) . "',
                from_branch_id = '" . (int)$data['from_branch_id'] . "',
                to_branch_id = '" . (int)$data['to_branch_id'] . "',
                reason_id = " . (!empty($data['reason_id']) ? "'" . (int)$data['reason_id'] . "'" : "NULL") . ",
                user_id = '" . (int)$this->user->getId() . "',
                request_date = '" . $this->db->escape($data['request_date']) . "',
                expected_delivery_date = " . (!empty($data['expected_delivery_date']) ? "'" . $this->db->escape($data['expected_delivery_date']) . "'" : "NULL") . ",
                notes = '" . $this->db->escape($data['notes']) . "',
                date_added = NOW(),
                date_modified = NOW()
        ");

        $transfer_id = $this->db->getLastId();

        // إضافة عناصر النقل
        if (isset($data['transfer_items'])) {
            foreach ($data['transfer_items'] as $item) {
                $this->addTransferItem($transfer_id, $item);
            }
        }

        return $transfer_id;
    }

    /**
     * تحديث طلب نقل
     */
    public function editStockTransfer($transfer_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_stock_transfer
            SET transfer_name = '" . $this->db->escape($data['transfer_name']) . "',
                transfer_type = '" . $this->db->escape($data['transfer_type']) . "',
                priority = '" . $this->db->escape($data['priority']) . "',
                from_branch_id = '" . (int)$data['from_branch_id'] . "',
                to_branch_id = '" . (int)$data['to_branch_id'] . "',
                reason_id = " . (!empty($data['reason_id']) ? "'" . (int)$data['reason_id'] . "'" : "NULL") . ",
                request_date = '" . $this->db->escape($data['request_date']) . "',
                expected_delivery_date = " . (!empty($data['expected_delivery_date']) ? "'" . $this->db->escape($data['expected_delivery_date']) . "'" : "NULL") . ",
                notes = '" . $this->db->escape($data['notes']) . "',
                date_modified = NOW()
            WHERE transfer_id = '" . (int)$transfer_id . "'
            AND status = 'draft'
        ");

        // تحديث عناصر النقل
        if (isset($data['transfer_items'])) {
            // حذف العناصر الموجودة
            $this->db->query("DELETE FROM " . DB_PREFIX . "cod_stock_transfer_item WHERE transfer_id = '" . (int)$transfer_id . "'");

            // إضافة العناصر الجديدة
            foreach ($data['transfer_items'] as $item) {
                $this->addTransferItem($transfer_id, $item);
            }
        }
    }

    /**
     * حذف طلب نقل
     */
    public function deleteStockTransfer($transfer_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_stock_transfer WHERE transfer_id = '" . (int)$transfer_id . "' AND status = 'draft'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_stock_transfer_item WHERE transfer_id = '" . (int)$transfer_id . "'");
    }

    /**
     * إضافة عنصر نقل
     */
    public function addTransferItem($transfer_id, $item_data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_stock_transfer_item
            SET transfer_id = '" . (int)$transfer_id . "',
                product_id = '" . (int)$item_data['product_id'] . "',
                quantity = '" . (float)$item_data['quantity'] . "',
                unit_cost = '" . (float)$item_data['unit_cost'] . "',
                unit_id = '" . (int)$item_data['unit_id'] . "',
                lot_number = " . (!empty($item_data['lot_number']) ? "'" . $this->db->escape($item_data['lot_number']) . "'" : "NULL") . ",
                expiry_date = " . (!empty($item_data['expiry_date']) ? "'" . $this->db->escape($item_data['expiry_date']) . "'" : "NULL") . ",
                notes = '" . $this->db->escape($item_data['notes']) . "',
                date_added = NOW()
        ");
    }

    /**
     * الحصول على عناصر النقل
     */
    public function getTransferItems($transfer_id) {
        $sql = "
            SELECT
                sti.*,
                p.product_id,
                pd.name as product_name,
                p.model,
                p.sku,
                ud.name as unit_name,
                u.symbol as unit_symbol,
                (sti.quantity * sti.unit_cost) as total_cost,
                pi.quantity as available_quantity
            FROM " . DB_PREFIX . "cod_stock_transfer_item sti
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (sti.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (sti.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (sti.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id AND pi.branch_id = (SELECT from_branch_id FROM " . DB_PREFIX . "cod_stock_transfer WHERE transfer_id = '" . (int)$transfer_id . "'))
            WHERE sti.transfer_id = '" . (int)$transfer_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY pd.name ASC
        ";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * تغيير حالة النقل
     */
    public function changeStatus($transfer_id, $status, $notes = '') {
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
            case 'shipped':
                $update_fields[] = "shipped_by = '" . (int)$current_user_id . "'";
                $update_fields[] = "ship_date = NOW()";
                // خصم من المخزون المرسل
                $this->processShipment($transfer_id);
                break;
            case 'received':
                $update_fields[] = "received_by = '" . (int)$current_user_id . "'";
                $update_fields[] = "actual_delivery_date = NOW()";
                break;
            case 'completed':
                // إضافة للمخزون المستلم
                $this->processReceipt($transfer_id);
                break;
            case 'rejected':
                $update_fields[] = "rejected_by = '" . (int)$current_user_id . "'";
                $update_fields[] = "rejection_date = NOW()";
                if ($notes) {
                    $update_fields[] = "rejection_reason = '" . $this->db->escape($notes) . "'";
                }
                break;
        }

        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_stock_transfer
            SET " . implode(', ', $update_fields) . "
            WHERE transfer_id = '" . (int)$transfer_id . "'
        ");

        // إضافة سجل في تاريخ النقل
        $this->addTransferHistory($transfer_id, $status, $notes);

        // إرسال إشعارات
        $this->sendTransferNotifications($transfer_id, $status);
    }

    /**
     * معالجة الشحن - خصم من المخزون المرسل
     */
    private function processShipment($transfer_id) {
        $transfer = $this->getStockTransfer($transfer_id);
        $items = $this->getTransferItems($transfer_id);

        foreach ($items as $item) {
            // خصم من المخزون المرسل
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_product_inventory
                SET quantity = quantity - '" . (float)$item['quantity'] . "'
                WHERE product_id = '" . (int)$item['product_id'] . "'
                AND branch_id = '" . (int)$transfer['from_branch_id'] . "'
            ");

            // إضافة حركة مخزنية للخصم
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cod_product_movement
                SET product_id = '" . (int)$item['product_id'] . "',
                    branch_id = '" . (int)$transfer['from_branch_id'] . "',
                    movement_type = 'transfer_out',
                    reference_type = 'stock_transfer',
                    reference_id = '" . (int)$transfer_id . "',
                    reference_number = '" . $this->db->escape($transfer['transfer_number']) . "',
                    lot_number = " . (!empty($item['lot_number']) ? "'" . $this->db->escape($item['lot_number']) . "'" : "NULL") . ",
                    expiry_date = " . (!empty($item['expiry_date']) ? "'" . $this->db->escape($item['expiry_date']) . "'" : "NULL") . ",
                    unit_id = '" . (int)$item['unit_id'] . "',
                    quantity_out = '" . (float)$item['quantity'] . "',
                    unit_cost = '" . (float)$item['unit_cost'] . "',
                    total_cost = '" . (float)($item['quantity'] * $item['unit_cost']) . "',
                    user_id = '" . (int)$this->user->getId() . "',
                    notes = 'نقل إلى: " . $this->db->escape($transfer['to_branch_name']) . "',
                    date_added = NOW()
            ");
        }
    }

    /**
     * معالجة الاستلام - إضافة للمخزون المستلم
     */
    private function processReceipt($transfer_id) {
        $transfer = $this->getStockTransfer($transfer_id);
        $items = $this->getTransferItems($transfer_id);

        foreach ($items as $item) {
            $received_quantity = $item['received_quantity'] ? $item['received_quantity'] : $item['quantity'];

            // إضافة للمخزون المستلم
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_product_inventory
                SET quantity = quantity + '" . (float)$received_quantity . "'
                WHERE product_id = '" . (int)$item['product_id'] . "'
                AND branch_id = '" . (int)$transfer['to_branch_id'] . "'
            ");

            // إضافة حركة مخزنية للإضافة
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cod_product_movement
                SET product_id = '" . (int)$item['product_id'] . "',
                    branch_id = '" . (int)$transfer['to_branch_id'] . "',
                    movement_type = 'transfer_in',
                    reference_type = 'stock_transfer',
                    reference_id = '" . (int)$transfer_id . "',
                    reference_number = '" . $this->db->escape($transfer['transfer_number']) . "',
                    lot_number = " . (!empty($item['lot_number']) ? "'" . $this->db->escape($item['lot_number']) . "'" : "NULL") . ",
                    expiry_date = " . (!empty($item['expiry_date']) ? "'" . $this->db->escape($item['expiry_date']) . "'" : "NULL") . ",
                    unit_id = '" . (int)$item['unit_id'] . "',
                    quantity_in = '" . (float)$received_quantity . "',
                    unit_cost = '" . (float)$item['unit_cost'] . "',
                    total_cost = '" . (float)($received_quantity * $item['unit_cost']) . "',
                    user_id = '" . (int)$this->user->getId() . "',
                    notes = 'نقل من: " . $this->db->escape($transfer['from_branch_name']) . "',
                    date_added = NOW()
            ");

            // إنشاء تسوية للفرق إذا كانت الكمية المستلمة مختلفة
            if ($received_quantity != $item['quantity']) {
                $this->createVarianceAdjustment($transfer_id, $item, $received_quantity);
            }
        }
    }

    /**
     * إنشاء تسوية للفرق في الكمية
     */
    private function createVarianceAdjustment($transfer_id, $item, $received_quantity) {
        $transfer = $this->getStockTransfer($transfer_id);
        $variance = $received_quantity - $item['quantity'];

        if ($variance != 0) {
            // إنشاء تسوية للفرق
            $adjustment_number = 'ADJ-TRF-' . date('Y') . '-' . str_pad($transfer_id, 6, '0', STR_PAD_LEFT);

            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cod_stock_adjustment
                SET adjustment_number = '" . $this->db->escape($adjustment_number) . "',
                    adjustment_name = 'تسوية فرق النقل: " . $this->db->escape($transfer['transfer_name']) . "',
                    adjustment_type = 'transfer_variance',
                    branch_id = '" . (int)$transfer['to_branch_id'] . "',
                    reference_type = 'stock_transfer',
                    reference_id = '" . (int)$transfer_id . "',
                    user_id = '" . (int)$this->user->getId() . "',
                    adjustment_date = NOW(),
                    status = 'posted',
                    notes = 'تسوية تلقائية لفرق النقل - المطلوب: " . $item['quantity'] . " المستلم: " . $received_quantity . "',
                    date_added = NOW(),
                    date_modified = NOW()
            ");

            $adjustment_id = $this->db->getLastId();

            // إضافة عنصر التسوية
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cod_stock_adjustment_item
                SET adjustment_id = '" . (int)$adjustment_id . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . (float)$variance . "',
                    unit_cost = '" . (float)$item['unit_cost'] . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    reason = 'فرق في النقل',
                    notes = 'فرق بين المطلوب والمستلم في النقل',
                    date_added = NOW()
            ");
        }
    }

    /**
     * إضافة سجل في تاريخ النقل
     */
    private function addTransferHistory($transfer_id, $status, $notes = '') {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_stock_transfer_history
            SET transfer_id = '" . (int)$transfer_id . "',
                status = '" . $this->db->escape($status) . "',
                user_id = '" . (int)$this->user->getId() . "',
                notes = '" . $this->db->escape($notes) . "',
                date_added = NOW()
        ");
    }

    /**
     * إرسال إشعارات النقل المتقدمة
     */
    private function sendTransferNotifications($transfer_id, $status) {
        $transfer = $this->getStockTransfer($transfer_id);

        // تحديد المستقبلين حسب الحالة
        $recipients = $this->getNotificationRecipients($transfer, $status);

        // إنشاء رسالة الإشعار
        $notification_data = array(
            'type' => 'stock_transfer',
            'reference_id' => $transfer_id,
            'reference_number' => $transfer['transfer_number'],
            'title' => $this->getNotificationTitle($status),
            'message' => $this->getNotificationMessage($transfer, $status),
            'priority' => $this->getNotificationPriority($transfer['priority'], $status),
            'action_url' => 'inventory/stock_transfer/view&transfer_id=' . $transfer_id,
            'created_by' => $this->user->getId(),
            'date_created' => date('Y-m-d H:i:s')
        );

        // إرسال الإشعارات
        foreach ($recipients as $user_id) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cod_notification
                SET user_id = '" . (int)$user_id . "',
                    type = '" . $this->db->escape($notification_data['type']) . "',
                    reference_id = '" . (int)$notification_data['reference_id'] . "',
                    reference_number = '" . $this->db->escape($notification_data['reference_number']) . "',
                    title = '" . $this->db->escape($notification_data['title']) . "',
                    message = '" . $this->db->escape($notification_data['message']) . "',
                    priority = '" . $this->db->escape($notification_data['priority']) . "',
                    action_url = '" . $this->db->escape($notification_data['action_url']) . "',
                    status = 'unread',
                    created_by = '" . (int)$notification_data['created_by'] . "',
                    date_created = '" . $this->db->escape($notification_data['date_created']) . "'
            ");
        }

        // إرسال إشعارات البريد الإلكتروني للحالات المهمة
        if (in_array($status, array('pending_approval', 'approved', 'shipped', 'completed'))) {
            $this->sendEmailNotifications($transfer, $status, $recipients);
        }

        // إرسال إشعارات SMS للحالات العاجلة
        if ($transfer['priority'] == 'urgent' && in_array($status, array('approved', 'shipped'))) {
            $this->sendSMSNotifications($transfer, $status, $recipients);
        }
    }

    /**
     * تحديد مستقبلي الإشعارات
     */
    private function getNotificationRecipients($transfer, $status) {
        $recipients = array();

        // إضافة منشئ النقل دائماً
        $recipients[] = $transfer['user_id'];

        // إضافة مدراء الفروع
        $branch_managers = $this->getBranchManagers(array($transfer['from_branch_id'], $transfer['to_branch_id']));
        $recipients = array_merge($recipients, $branch_managers);

        // إضافة مستخدمين حسب الحالة
        switch ($status) {
            case 'pending_approval':
                // إضافة المعتمدين
                $approvers = $this->getTransferApprovers($transfer['transfer_type'], $transfer['priority']);
                $recipients = array_merge($recipients, $approvers);
                break;

            case 'approved':
                // إضافة مسؤولي الشحن في الفرع المرسل
                $shipping_users = $this->getBranchUsers($transfer['from_branch_id'], 'shipping');
                $recipients = array_merge($recipients, $shipping_users);
                break;

            case 'shipped':
                // إضافة مسؤولي الاستلام في الفرع المستقبل
                $receiving_users = $this->getBranchUsers($transfer['to_branch_id'], 'receiving');
                $recipients = array_merge($recipients, $receiving_users);
                break;

            case 'delivered':
            case 'received':
                // إضافة مسؤولي المخزون في الفرع المستقبل
                $inventory_users = $this->getBranchUsers($transfer['to_branch_id'], 'inventory');
                $recipients = array_merge($recipients, $inventory_users);
                break;
        }

        // إزالة التكرارات
        return array_unique($recipients);
    }

    /**
     * الحصول على عنوان الإشعار
     */
    private function getNotificationTitle($status) {
        $titles = array(
            'pending_approval' => 'طلب نقل مخزون يحتاج موافقة',
            'approved' => 'تم اعتماد طلب نقل المخزون',
            'rejected' => 'تم رفض طلب نقل المخزون',
            'shipped' => 'تم شحن طلب النقل',
            'in_transit' => 'طلب النقل في الطريق',
            'delivered' => 'تم تسليم طلب النقل',
            'received' => 'تم استلام طلب النقل',
            'completed' => 'تم إكمال نقل المخزون',
            'cancelled' => 'تم إلغاء طلب النقل'
        );

        return isset($titles[$status]) ? $titles[$status] : 'تحديث حالة نقل المخزون';
    }

    /**
     * الحصول على رسالة الإشعار
     */
    private function getNotificationMessage($transfer, $status) {
        $base_message = "طلب النقل رقم: {$transfer['transfer_number']}\n";
        $base_message .= "من: {$transfer['from_branch_name']}\n";
        $base_message .= "إلى: {$transfer['to_branch_name']}\n";

        $status_messages = array(
            'pending_approval' => 'يحتاج إلى موافقتك للمتابعة',
            'approved' => 'تم اعتماده ويمكن البدء في الشحن',
            'rejected' => 'تم رفضه من قبل المعتمد',
            'shipped' => 'تم شحنه وهو في الطريق',
            'in_transit' => 'في الطريق للوجهة المحددة',
            'delivered' => 'وصل للوجهة وجاهز للاستلام',
            'received' => 'تم استلامه بنجاح',
            'completed' => 'تم إكماله وتحديث المخزون',
            'cancelled' => 'تم إلغاؤه'
        );

        $status_message = isset($status_messages[$status]) ? $status_messages[$status] : 'تم تحديث حالته';

        return $base_message . $status_message;
    }

    /**
     * تحديد أولوية الإشعار
     */
    private function getNotificationPriority($transfer_priority, $status) {
        // الحالات العاجلة
        if (in_array($status, array('pending_approval', 'shipped', 'delivered'))) {
            return $transfer_priority == 'urgent' ? 'critical' : 'high';
        }

        // الحالات المهمة
        if (in_array($status, array('approved', 'received', 'completed'))) {
            return $transfer_priority == 'urgent' ? 'high' : 'medium';
        }

        // الحالات العادية
        return 'low';
    }

    /**
     * إرسال إشعارات البريد الإلكتروني
     */
    private function sendEmailNotifications($transfer, $status, $recipients) {
        // تحميل مكتبة البريد الإلكتروني
        $this->load->library('mail');

        foreach ($recipients as $user_id) {
            $user_info = $this->getUserInfo($user_id);
            if ($user_info && $user_info['email']) {
                $subject = $this->getNotificationTitle($status);
                $message = $this->getEmailTemplate($transfer, $status, $user_info);

                $mail = new Mail();
                $mail->protocol = $this->config->get('config_mail_protocol');
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

                $mail->setTo($user_info['email']);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setSubject($subject);
                $mail->setHtml($message);
                $mail->send();
            }
        }
    }

    /**
     * إرسال إشعارات SMS
     */
    private function sendSMSNotifications($transfer, $status, $recipients) {
        // تحميل مكتبة SMS
        if (class_exists('SMS')) {
            foreach ($recipients as $user_id) {
                $user_info = $this->getUserInfo($user_id);
                if ($user_info && $user_info['phone']) {
                    $message = $this->getSMSMessage($transfer, $status);

                    $sms = new SMS();
                    $sms->send($user_info['phone'], $message);
                }
            }
        }
    }

    /**
     * الحصول على ملخص النقل
     */
    public function getTransferSummary($data = array()) {
        $sql = "
            SELECT
                COUNT(*) as total_transfers,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending_approval_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_count,
                SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as in_transit_count,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                (SELECT SUM(sti.quantity * sti.unit_cost)
                 FROM " . DB_PREFIX . "cod_stock_transfer_item sti
                 INNER JOIN " . DB_PREFIX . "cod_stock_transfer st2 ON (sti.transfer_id = st2.transfer_id)
                 WHERE st2.status = 'completed') as total_completed_value,
                AVG((SELECT COUNT(*) FROM " . DB_PREFIX . "cod_stock_transfer_item sti WHERE sti.transfer_id = st.transfer_id)) as avg_items_per_transfer
            FROM " . DB_PREFIX . "cod_stock_transfer st
            WHERE 1=1
        ";

        if (!empty($data['filter_from_branch_id'])) {
            $sql .= " AND st.from_branch_id = '" . (int)$data['filter_from_branch_id'] . "'";
        }

        if (!empty($data['filter_to_branch_id'])) {
            $sql .= " AND st.to_branch_id = '" . (int)$data['filter_to_branch_id'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(st.request_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(st.request_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row;
    }

    /**
     * الحصول على النقل حسب الفرع
     */
    public function getTransfersByBranch($data = array()) {
        $sql = "
            SELECT
                b.branch_id,
                b.name as branch_name,
                b.type as branch_type,
                COUNT(CASE WHEN st.from_branch_id = b.branch_id THEN 1 END) as outgoing_count,
                COUNT(CASE WHEN st.to_branch_id = b.branch_id THEN 1 END) as incoming_count,
                SUM(CASE WHEN st.from_branch_id = b.branch_id THEN (SELECT SUM(sti.quantity * sti.unit_cost) FROM " . DB_PREFIX . "cod_stock_transfer_item sti WHERE sti.transfer_id = st.transfer_id) ELSE 0 END) as outgoing_value,
                SUM(CASE WHEN st.to_branch_id = b.branch_id THEN (SELECT SUM(sti.quantity * sti.unit_cost) FROM " . DB_PREFIX . "cod_stock_transfer_item sti WHERE sti.transfer_id = st.transfer_id) ELSE 0 END) as incoming_value
            FROM " . DB_PREFIX . "cod_branch b
            LEFT JOIN " . DB_PREFIX . "cod_stock_transfer st ON (b.branch_id = st.from_branch_id OR b.branch_id = st.to_branch_id)
            WHERE b.status = 1
        ";

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(st.request_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(st.request_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " GROUP BY b.branch_id ORDER BY (outgoing_value + incoming_value) DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على أسباب النقل
     */
    public function getTransferReasons() {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cod_stock_transfer_reason
            WHERE status = 1
            ORDER BY sort_order ASC, name ASC
        ");

        return $query->rows;
    }

    /**
     * توليد رقم نقل جديد
     */
    public function generateTransferNumber() {
        $query = $this->db->query("
            SELECT transfer_number
            FROM " . DB_PREFIX . "cod_stock_transfer
            WHERE transfer_number LIKE 'TRF-" . date('Y') . "-%'
            ORDER BY transfer_id DESC
            LIMIT 1
        ");

        if ($query->num_rows > 0) {
            $last_number = $query->row['transfer_number'];
            $number_part = (int)substr($last_number, -6);
            $new_number = $number_part + 1;
        } else {
            $new_number = 1;
        }

        return 'TRF-' . date('Y') . '-' . str_pad($new_number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * التحقق من توفر المخزون
     */
    public function checkStockAvailability($transfer_id) {
        $transfer = $this->getStockTransfer($transfer_id);
        $items = $this->getTransferItems($transfer_id);

        $availability = array();

        foreach ($items as $item) {
            $available = $item['available_quantity'] ? $item['available_quantity'] : 0;
            $availability[] = array(
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'requested_quantity' => $item['quantity'],
                'available_quantity' => $available,
                'shortage' => max(0, $item['quantity'] - $available),
                'is_available' => $available >= $item['quantity']
            );
        }

        return $availability;
    }

    /**
     * تحديث الكمية المستلمة
     */
    public function updateReceivedQuantity($item_id, $received_quantity, $notes = '') {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_stock_transfer_item
            SET received_quantity = '" . (float)$received_quantity . "',
                received_notes = '" . $this->db->escape($notes) . "',
                date_modified = NOW()
            WHERE item_id = '" . (int)$item_id . "'
        ");
    }

    /**
     * الحصول على تاريخ النقل
     */
    public function getTransferHistory($transfer_id) {
        $query = $this->db->query("
            SELECT
                sth.*,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                CASE sth.status
                    WHEN 'draft' THEN 'مسودة'
                    WHEN 'pending_approval' THEN 'في انتظار الموافقة'
                    WHEN 'approved' THEN 'معتمد'
                    WHEN 'shipped' THEN 'تم الشحن'
                    WHEN 'in_transit' THEN 'في الطريق'
                    WHEN 'delivered' THEN 'تم التسليم'
                    WHEN 'received' THEN 'تم الاستلام'
                    WHEN 'completed' THEN 'مكتمل'
                    WHEN 'cancelled' THEN 'ملغي'
                    WHEN 'rejected' THEN 'مرفوض'
                    ELSE sth.status
                END as status_text
            FROM " . DB_PREFIX . "cod_stock_transfer_history sth
            LEFT JOIN " . DB_PREFIX . "user u ON (sth.user_id = u.user_id)
            WHERE sth.transfer_id = '" . (int)$transfer_id . "'
            ORDER BY sth.date_added ASC
        ");

        return $query->rows;
    }

    /**
     * تصدير البيانات للإكسل
     */
    public function exportToExcel($data = array()) {
        // إزالة حدود الصفحات للتصدير
        unset($data['start']);
        unset($data['limit']);

        return $this->getStockTransfers($data);
    }

    /**
     * اعتماد طلب النقل
     */
    public function approveTransfer($transfer_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "stock_transfer SET
            status = 'approved',
            approved_by = '" . (int)$this->user->getId() . "',
            approval_date = NOW()
            WHERE transfer_id = '" . (int)$transfer_id . "'");

        // إضافة سجل في تاريخ النقل
        $this->addTransferHistory($transfer_id, 'approved', 'تم اعتماد طلب النقل');

        return true;
    }

    /**
     * شحن طلب النقل
     */
    public function shipTransfer($transfer_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "stock_transfer SET
            status = 'shipped',
            shipped_by = '" . (int)$this->user->getId() . "',
            ship_date = NOW()
            WHERE transfer_id = '" . (int)$transfer_id . "'");

        // إضافة سجل في تاريخ النقل
        $this->addTransferHistory($transfer_id, 'shipped', 'تم شحن طلب النقل');

        // تحديث المخزون - خصم من الفرع المرسل
        $this->updateInventoryOnShip($transfer_id);

        return true;
    }

    /**
     * إلغاء طلب النقل
     */
    public function cancelTransfer($transfer_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "stock_transfer SET
            status = 'cancelled',
            cancelled_by = '" . (int)$this->user->getId() . "',
            cancel_date = NOW()
            WHERE transfer_id = '" . (int)$transfer_id . "'");

        // إضافة سجل في تاريخ النقل
        $this->addTransferHistory($transfer_id, 'cancelled', 'تم إلغاء طلب النقل');

        return true;
    }

    /**
     * تحديث حالة طلب النقل
     */
    public function updateStatus($transfer_id, $status) {
        $allowed_statuses = ['draft', 'pending_approval', 'approved', 'shipped', 'in_transit', 'delivered', 'received', 'completed', 'cancelled'];

        if (!in_array($status, $allowed_statuses)) {
            throw new Exception('حالة غير صحيحة');
        }

        $this->db->query("UPDATE " . DB_PREFIX . "stock_transfer SET
            status = '" . $this->db->escape($status) . "',
            date_modified = NOW()
            WHERE transfer_id = '" . (int)$transfer_id . "'");

        // إضافة سجل في تاريخ النقل
        $status_text = $this->getStatusText($status);
        $this->addTransferHistory($transfer_id, $status, 'تم تحديث الحالة إلى: ' . $status_text);

        return true;
    }

    /**
     * إضافة سجل في تاريخ النقل
     */
    private function addTransferHistory($transfer_id, $status, $notes = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "stock_transfer_history SET
            transfer_id = '" . (int)$transfer_id . "',
            status = '" . $this->db->escape($status) . "',
            notes = '" . $this->db->escape($notes) . "',
            user_id = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
    }

    /**
     * تحديث المخزون عند الشحن
     */
    private function updateInventoryOnShip($transfer_id) {
        // الحصول على عناصر النقل
        $items_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "stock_transfer_item
            WHERE transfer_id = '" . (int)$transfer_id . "'");

        foreach ($items_query->rows as $item) {
            // خصم الكمية من الفرع المرسل
            $this->db->query("UPDATE " . DB_PREFIX . "product_to_branch SET
                quantity = quantity - '" . (float)$item['quantity'] . "'
                WHERE product_id = '" . (int)$item['product_id'] . "'
                AND branch_id = (SELECT from_branch_id FROM " . DB_PREFIX . "stock_transfer WHERE transfer_id = '" . (int)$transfer_id . "')");

            // إضافة حركة مخزون
            $this->db->query("INSERT INTO " . DB_PREFIX . "stock_movement SET
                product_id = '" . (int)$item['product_id'] . "',
                branch_id = (SELECT from_branch_id FROM " . DB_PREFIX . "stock_transfer WHERE transfer_id = '" . (int)$transfer_id . "'),
                movement_type = 'transfer_out',
                quantity = '-" . (float)$item['quantity'] . "',
                unit_cost = '" . (float)$item['unit_cost'] . "',
                reference_type = 'stock_transfer',
                reference_id = '" . (int)$transfer_id . "',
                notes = 'نقل مخزون - صادر',
                user_id = '" . (int)$this->user->getId() . "',
                date_added = NOW()");
        }
    }

    /**
     * الحصول على نص الحالة
     */
    private function getStatusText($status) {
        $statuses = [
            'draft' => 'مسودة',
            'pending_approval' => 'في انتظار الموافقة',
            'approved' => 'معتمد',
            'shipped' => 'مشحون',
            'in_transit' => 'في الطريق',
            'delivered' => 'مسلم',
            'received' => 'مستلم',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي'
        ];

        return $statuses[$status] ?? $status;
    }
}
