<?php
/**
 * نموذج تعديلات المخزون
 * يستخدم لإدارة تعديلات المخزون (زيادة أو نقصان)
 */
class ModelInventoryAdjustment extends Model {
    /**
     * إضافة تعديل مخزون جديد
     */
    public function addAdjustment($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_adjustment SET
            reference_number = '" . $this->db->escape($data['reference_number']) . "',
            branch_id = '" . (int)$data['branch_id'] . "',
            adjustment_date = '" . $this->db->escape($data['adjustment_date']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            reason = '" . $this->db->escape($data['reason']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()");

        $adjustment_id = $this->db->getLastId();

        // إضافة المنتجات المعدلة
        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_adjustment_item SET
                    adjustment_id = '" . (int)$adjustment_id . "',
                    product_id = '" . (int)$product['product_id'] . "',
                    unit_id = '" . (int)$product['unit_id'] . "',
                    adjustment_type = '" . $this->db->escape($product['adjustment_type']) . "',
                    quantity = '" . (float)$product['quantity'] . "',
                    unit_cost = '" . (float)$product['unit_cost'] . "',
                    notes = '" . $this->db->escape($product['notes']) . "'");
            }
        }

        // إرسال إشعار بإنشاء تعديل جديد
        $this->sendNewAdjustmentNotification($adjustment_id);

        // إذا كانت الحالة معتمدة، نقوم بإنشاء حركات المخزون
        if ($data['status'] == 'approved') {
            $this->createAdjustmentMovements($adjustment_id);
        }

        return $adjustment_id;
    }

    /**
     * تعديل تعديل مخزون
     */
    public function editAdjustment($adjustment_id, $data) {
        // الحصول على معلومات التعديل قبل التعديل
        $old_adjustment_info = $this->getAdjustment($adjustment_id);
        $old_status = $old_adjustment_info ? $old_adjustment_info['status'] : '';

        $this->db->query("UPDATE " . DB_PREFIX . "inventory_adjustment SET
            reference_number = '" . $this->db->escape($data['reference_number']) . "',
            branch_id = '" . (int)$data['branch_id'] . "',
            adjustment_date = '" . $this->db->escape($data['adjustment_date']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            reason = '" . $this->db->escape($data['reason']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_at = NOW()
            WHERE adjustment_id = '" . (int)$adjustment_id . "'");

        // حذف المنتجات القديمة
        $this->db->query("DELETE FROM " . DB_PREFIX . "inventory_adjustment_item WHERE adjustment_id = '" . (int)$adjustment_id . "'");

        // إضافة المنتجات الجديدة
        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_adjustment_item SET
                    adjustment_id = '" . (int)$adjustment_id . "',
                    product_id = '" . (int)$product['product_id'] . "',
                    unit_id = '" . (int)$product['unit_id'] . "',
                    adjustment_type = '" . $this->db->escape($product['adjustment_type']) . "',
                    quantity = '" . (float)$product['quantity'] . "',
                    unit_cost = '" . (float)$product['unit_cost'] . "',
                    notes = '" . $this->db->escape($product['notes']) . "'");
            }
        }

        // إرسال إشعار بتعديل التعديل
        $this->sendEditAdjustmentNotification($adjustment_id);

        // إذا تغيرت الحالة، نرسل إشعار بتغيير الحالة
        if ($old_status != $data['status']) {
            $this->sendAdjustmentStatusNotification($adjustment_id, $data['status']);
        }

        // إذا تم تغيير الحالة إلى معتمدة، نقوم بإنشاء حركات المخزون
        if ($old_status != 'approved' && $data['status'] == 'approved') {
            $this->createAdjustmentMovements($adjustment_id);
        }
    }

    /**
     * حذف تعديل مخزون
     */
    public function deleteAdjustment($adjustment_id) {
        // التحقق من حالة التعديل قبل الحذف
        $adjustment_info = $this->getAdjustment($adjustment_id);

        if ($adjustment_info && $adjustment_info['status'] == 'approved') {
            // إذا كان التعديل معتمد، نقوم بإلغاء تأثيره على المخزون
            $this->reverseAdjustmentMovements($adjustment_id);
        }

        // حذف بنود التعديل
        $this->db->query("DELETE FROM " . DB_PREFIX . "inventory_adjustment_item WHERE adjustment_id = '" . (int)$adjustment_id . "'");

        // حذف التعديل نفسه
        $this->db->query("DELETE FROM " . DB_PREFIX . "inventory_adjustment WHERE adjustment_id = '" . (int)$adjustment_id . "'");
    }

    /**
     * تحديث حالة تعديل المخزون
     */
    public function updateAdjustmentStatus($adjustment_id, $status) {
        $this->db->query("UPDATE " . DB_PREFIX . "inventory_adjustment SET
            status = '" . $this->db->escape($status) . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_at = NOW()
            WHERE adjustment_id = '" . (int)$adjustment_id . "'");

        // إذا تم تأكيد التعديل، نقوم بإنشاء حركات المخزون
        if ($status == 'approved') {
            $this->createAdjustmentMovements($adjustment_id);
        }

        // إرسال إشعار بتغيير حالة التعديل
        $this->sendAdjustmentStatusNotification($adjustment_id, $status);
    }

    /**
     * الحصول على تعديل مخزون
     */
    public function getAdjustment($adjustment_id) {
        $query = $this->db->query("SELECT a.*,
                b.name AS branch_name,
                CONCAT(u.firstname, ' ', u.lastname) AS created_by_name
            FROM " . DB_PREFIX . "inventory_adjustment a
            LEFT JOIN " . DB_PREFIX . "branch b ON (a.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (a.created_by = u.user_id)
            WHERE a.adjustment_id = '" . (int)$adjustment_id . "'");

        return $query->row;
    }

    /**
     * الحصول على منتجات تعديل المخزون
     */
    public function getAdjustmentProducts($adjustment_id) {
        $query = $this->db->query("SELECT ai.*,
                pd.name AS product_name,
                u.desc_en AS unit_name
            FROM " . DB_PREFIX . "inventory_adjustment_item ai
            LEFT JOIN " . DB_PREFIX . "product_description pd
                ON (ai.product_id = pd.product_id AND pd.language_id = '1')
            LEFT JOIN " . DB_PREFIX . "unit u
                ON (ai.unit_id = u.unit_id)
            WHERE ai.adjustment_id = '" . (int)$adjustment_id . "'
            ORDER BY ai.adjustment_item_id");

        return $query->rows;
    }

    /**
     * الحصول على قائمة تعديلات المخزون
     */
    public function getAdjustments($data = array()) {
        $sql = "SELECT a.*,
                b.name AS branch_name,
                CONCAT(u.firstname, ' ', u.lastname) AS created_by_name
            FROM " . DB_PREFIX . "inventory_adjustment a
            LEFT JOIN " . DB_PREFIX . "branch b ON (a.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (a.created_by = u.user_id)
            WHERE 1";

        if (!empty($data['filter_reference'])) {
            $sql .= " AND a.reference_number LIKE '%" . $this->db->escape($data['filter_reference']) . "%'";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND a.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND a.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(a.adjustment_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(a.adjustment_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sort_data = array(
            'a.reference_number',
            'b.name',
            'a.adjustment_date',
            'a.status',
            'a.created_at'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY a.created_at";
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
     * الحصول على إجمالي عدد تعديلات المخزون
     */
    public function getTotalAdjustments($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "inventory_adjustment a WHERE 1";

        if (!empty($data['filter_reference'])) {
            $sql .= " AND a.reference_number LIKE '%" . $this->db->escape($data['filter_reference']) . "%'";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND a.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND a.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(a.adjustment_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(a.adjustment_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * إنشاء حركات المخزون للتعديل
     */
    private function createAdjustmentMovements($adjustment_id) {
        $adjustment_info = $this->getAdjustment($adjustment_id);
        $products = $this->getAdjustmentProducts($adjustment_id);

        if ($adjustment_info && $products) {
            $this->load->model('inventory/movement');

            foreach ($products as $product) {
                // تحديد نوع الحركة بناءً على نوع التعديل وقيمة الكمية
                $movement_type = 'in';
                $quantity = abs($product['quantity']);

                if ($product['adjustment_type'] == 'quantity' && $product['quantity'] < 0) {
                    $movement_type = 'out';
                }

                // إنشاء حركة المخزون
                $movement_data = array(
                    'branch_id' => $adjustment_info['branch_id'],
                    'product_id' => $product['product_id'],
                    'unit_id' => $product['unit_id'],
                    'movement_type' => $movement_type,
                    'quantity' => $quantity,
                    'unit_cost' => $product['unit_cost'],
                    'reference_type' => 'adjustment',
                    'reference_id' => $adjustment_id,
                    'notes' => 'تعديل مخزون - ' . $adjustment_info['reason']
                );

                $this->model_inventory_movement->addMovement($movement_data);
            }
        }
    }

    /**
     * إلغاء تأثير التعديل على المخزون
     */
    private function reverseAdjustmentMovements($adjustment_id) {
        $adjustment_info = $this->getAdjustment($adjustment_id);
        $products = $this->getAdjustmentProducts($adjustment_id);

        if ($adjustment_info && $products) {
            $this->load->model('inventory/movement');

            foreach ($products as $product) {
                // عكس نوع الحركة
                $movement_type = 'out';
                $quantity = abs($product['quantity']);

                if ($product['adjustment_type'] == 'quantity' && $product['quantity'] < 0) {
                    $movement_type = 'in';
                }

                // إنشاء حركة المخزون العكسية
                $movement_data = array(
                    'branch_id' => $adjustment_info['branch_id'],
                    'product_id' => $product['product_id'],
                    'unit_id' => $product['unit_id'],
                    'movement_type' => $movement_type,
                    'quantity' => $quantity,
                    'unit_cost' => $product['unit_cost'],
                    'reference_type' => 'adjustment_reversal',
                    'reference_id' => $adjustment_id,
                    'notes' => 'إلغاء تعديل مخزون - ' . $adjustment_info['reason']
                );

                $this->model_inventory_movement->addMovement($movement_data);
            }
        }
    }

    /**
     * إرسال إشعار بإنشاء تعديل مخزون جديد
     */
    private function sendNewAdjustmentNotification($adjustment_id) {
        $adjustment_info = $this->getAdjustment($adjustment_id);

        if ($adjustment_info) {
            $this->load->model('notification/notification');

            // إرسال إشعار للمستخدمين المعنيين
            $notification_data = array(
                'title' => 'تم إنشاء تعديل مخزون جديد #' . $adjustment_info['reference_number'],
                'message' => 'تم إنشاء تعديل مخزون جديد في فرع ' . $adjustment_info['branch_name'] . ' بتاريخ ' . $adjustment_info['adjustment_date'],
                'icon' => 'edit',
                'priority' => 'medium',
                'type' => 'inventory',
                'reference_id' => $adjustment_id,
                'reference_type' => 'inventory_adjustment',
                'url' => 'inventory/adjustment/edit&adjustment_id=' . $adjustment_id
            );

            // إرسال الإشعار للمستخدمين المعنيين (مثل مديري المخازن)
            $this->model_notification_notification->addNotification($notification_data);
        }
    }

    /**
     * إرسال إشعار بتعديل تعديل مخزون
     */
    private function sendEditAdjustmentNotification($adjustment_id) {
        $adjustment_info = $this->getAdjustment($adjustment_id);

        if ($adjustment_info) {
            $this->load->model('notification/notification');

            // إرسال إشعار للمستخدمين المعنيين
            $notification_data = array(
                'title' => 'تم تعديل تعديل مخزون #' . $adjustment_info['reference_number'],
                'message' => 'تم تعديل تعديل المخزون في فرع ' . $adjustment_info['branch_name'] . ' بتاريخ ' . $adjustment_info['adjustment_date'],
                'icon' => 'edit',
                'priority' => 'low',
                'type' => 'inventory',
                'reference_id' => $adjustment_id,
                'reference_type' => 'inventory_adjustment',
                'url' => 'inventory/adjustment/edit&adjustment_id=' . $adjustment_id
            );

            // إرسال الإشعار للمستخدمين المعنيين (مثل مديري المخازن)
            $this->model_notification_notification->addNotification($notification_data);
        }
    }

    /**
     * إرسال إشعار بتغيير حالة تعديل المخزون
     */
    private function sendAdjustmentStatusNotification($adjustment_id, $status) {
        $adjustment_info = $this->getAdjustment($adjustment_id);

        if ($adjustment_info) {
            $this->load->model('notification/notification');

            $status_text = '';
            switch ($status) {
                case 'approved':
                    $status_text = 'تم اعتماد';
                    $icon = 'check-circle';
                    $priority = 'medium';
                    break;
                case 'rejected':
                    $status_text = 'تم رفض';
                    $icon = 'times-circle';
                    $priority = 'high';
                    break;
                case 'cancelled':
                    $status_text = 'تم إلغاء';
                    $icon = 'ban';
                    $priority = 'high';
                    break;
                default:
                    $status_text = 'تم تحديث حالة';
                    $icon = 'info-circle';
                    $priority = 'low';
            }

            // إرسال إشعار للمستخدمين المعنيين
            $notification_data = array(
                'title' => $status_text . ' تعديل مخزون #' . $adjustment_info['reference_number'],
                'message' => $status_text . ' تعديل المخزون في فرع ' . $adjustment_info['branch_name'] . ' بتاريخ ' . $adjustment_info['adjustment_date'],
                'icon' => $icon,
                'priority' => $priority,
                'type' => 'inventory',
                'reference_id' => $adjustment_id,
                'reference_type' => 'inventory_adjustment',
                'url' => 'inventory/adjustment/edit&adjustment_id=' . $adjustment_id
            );

            // إرسال الإشعار للمستخدمين المعنيين (مثل مديري المخازن)
            $this->model_notification_notification->addNotification($notification_data);
        }
    }
}
