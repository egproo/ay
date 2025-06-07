<?php
class ModelInventoryTransfer extends Model {

    /**
     * إضافة تحويل مخزني جديد
     */
    public function addTransfer($data) {
        // التحقق من الكمية المتاحة قبل إنشاء التحويل
        if (!$this->validateAvailableQuantity($data)) {
            throw new Exception('الكمية المطلوبة غير متوفرة في المخزن!');
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_transfer SET
            transfer_number = '" . $this->db->escape($data['reference_number']) . "',
            from_branch_id = '" . (int)$data['from_branch_id'] . "',
            to_branch_id = '" . (int)$data['to_branch_id'] . "',
            transfer_date = '" . $this->db->escape($data['transfer_date']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()");

        $transfer_id = $this->db->getLastId();

        // إضافة المنتجات المحولة
        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_transfer_item SET
                    transfer_id = '" . (int)$transfer_id . "',
                    product_id = '" . (int)$product['product_id'] . "',
                    unit_id = '" . (int)$product['unit_id'] . "',
                    quantity = '" . (float)$product['quantity'] . "',
                    notes = '" . $this->db->escape($product['notes']) . "'");
            }
        }

        // إرسال إشعار بإنشاء تحويل جديد
        $this->sendNewTransferNotification($transfer_id);

        // إذا كانت الحالة مؤكدة، نقوم بإنشاء حركات المخزون
        if ($data['status'] == 'confirmed') {
            $this->createTransferMovements($transfer_id);
        }

        return $transfer_id;
    }

    /**
     * إرسال إشعار بإنشاء تحويل جديد
     */
    private function sendNewTransferNotification($transfer_id) {
        $transfer_info = $this->getTransfer($transfer_id);

        if ($transfer_info) {
            $this->load->model('notification/notification');

            // إرسال إشعار للمستخدمين المعنيين
            $notification_data = array(
                'title' => 'تم إنشاء تحويل مخزني جديد #' . $transfer_info['transfer_number'],
                'message' => 'تم إنشاء تحويل مخزني جديد من ' . $transfer_info['from_branch_name'] . ' إلى ' . $transfer_info['to_branch_name'],
                'icon' => 'exchange-alt',
                'priority' => 'medium',
                'type' => 'inventory',
                'reference_id' => $transfer_id,
                'reference_type' => 'inventory_transfer',
                'url' => 'inventory/transfer/edit&transfer_id=' . $transfer_id
            );

            // إرسال الإشعار للمستخدمين المعنيين (مثل مديري المخازن)
            $this->model_notification_notification->addNotification($notification_data);
        }
    }

    /**
     * تعديل تحويل مخزني
     */
    public function editTransfer($transfer_id, $data) {
        // الحصول على معلومات التحويل قبل التعديل
        $old_transfer_info = $this->getTransfer($transfer_id);
        $old_status = $old_transfer_info ? $old_transfer_info['status'] : '';

        // التحقق من الكمية المتاحة قبل التعديل
        if (!$this->validateAvailableQuantity($data)) {
            throw new Exception('الكمية المطلوبة غير متوفرة في المخزن!');
        }

        $this->db->query("UPDATE " . DB_PREFIX . "inventory_transfer SET
            transfer_number = '" . $this->db->escape($data['reference_number']) . "',
            from_branch_id = '" . (int)$data['from_branch_id'] . "',
            to_branch_id = '" . (int)$data['to_branch_id'] . "',
            transfer_date = '" . $this->db->escape($data['transfer_date']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_at = NOW()
            WHERE transfer_id = '" . (int)$transfer_id . "'");

        // حذف المنتجات القديمة
        $this->db->query("DELETE FROM " . DB_PREFIX . "inventory_transfer_item WHERE transfer_id = '" . (int)$transfer_id . "'");

        // إضافة المنتجات الجديدة
        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_transfer_item SET
                    transfer_id = '" . (int)$transfer_id . "',
                    product_id = '" . (int)$product['product_id'] . "',
                    unit_id = '" . (int)$product['unit_id'] . "',
                    quantity = '" . (float)$product['quantity'] . "',
                    notes = '" . $this->db->escape($product['notes']) . "'");
            }
        }

        // إرسال إشعار بتعديل التحويل
        $this->sendEditTransferNotification($transfer_id);

        // إذا تغيرت الحالة، نرسل إشعار بتغيير الحالة
        if ($old_status != $data['status']) {
            $this->sendTransferStatusNotification($transfer_id, $data['status']);
        }

        // إذا تم تغيير الحالة إلى مؤكدة، نقوم بإنشاء حركات المخزون
        if ($old_status != 'confirmed' && $data['status'] == 'confirmed') {
            $this->createTransferMovements($transfer_id);
        }
    }

    /**
     * إرسال إشعار بتعديل تحويل
     */
    private function sendEditTransferNotification($transfer_id) {
        $transfer_info = $this->getTransfer($transfer_id);

        if ($transfer_info) {
            $this->load->model('notification/notification');

            // إرسال إشعار للمستخدمين المعنيين
            $notification_data = array(
                'title' => 'تم تعديل تحويل مخزني #' . $transfer_info['transfer_number'],
                'message' => 'تم تعديل التحويل المخزني من ' . $transfer_info['from_branch_name'] . ' إلى ' . $transfer_info['to_branch_name'],
                'icon' => 'edit',
                'priority' => 'low',
                'type' => 'inventory',
                'reference_id' => $transfer_id,
                'reference_type' => 'inventory_transfer',
                'url' => 'inventory/transfer/edit&transfer_id=' . $transfer_id
            );

            // إرسال الإشعار للمستخدمين المعنيين (مثل مديري المخازن)
            $this->model_notification_notification->addNotification($notification_data);
        }
    }

    /**
     * التحقق من توفر الكمية في المخزن
     */
    private function validateAvailableQuantity($data) {
        $this->load->model('inventory/stock');

        foreach ($data['products'] as $product) {
            $available = $this->model_inventory_stock->getProductQuantity(
                $product['product_id'],
                $product['unit_id'],
                $data['from_branch_id']
            );

            if ($available < $product['quantity']) {
                return false;
            }
        }

        return true;
    }

    /**
     * تحديث حالة التحويل المخزني
     */
    public function updateTransferStatus($transfer_id, $status) {
        $this->db->query("UPDATE " . DB_PREFIX . "inventory_transfer SET
            status = '" . $this->db->escape($status) . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_at = NOW()
            WHERE transfer_id = '" . (int)$transfer_id . "'");

        // إذا تم تأكيد التحويل، نقوم بإنشاء حركات المخزون
        if ($status == 'confirmed') {
            $this->createTransferMovements($transfer_id);
        }

        // إرسال إشعار بتغيير حالة التحويل
        $this->sendTransferStatusNotification($transfer_id, $status);
    }

    /**
     * إرسال إشعار بتغيير حالة التحويل
     */
    private function sendTransferStatusNotification($transfer_id, $status) {
        $transfer_info = $this->getTransfer($transfer_id);

        if ($transfer_info) {
            $this->load->model('notification/notification');

            $status_text = '';
            switch ($status) {
                case 'confirmed':
                    $status_text = 'تم تأكيد';
                    $icon = 'check-circle';
                    $priority = 'medium';
                    break;
                case 'in_transit':
                    $status_text = 'قيد النقل';
                    $icon = 'truck';
                    $priority = 'medium';
                    break;
                case 'completed':
                    $status_text = 'تم استكمال';
                    $icon = 'check-double';
                    $priority = 'low';
                    break;
                case 'cancelled':
                    $status_text = 'تم إلغاء';
                    $icon = 'times-circle';
                    $priority = 'high';
                    break;
                case 'rejected':
                    $status_text = 'تم رفض';
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
                'title' => $status_text . ' تحويل مخزني #' . $transfer_info['transfer_number'],
                'message' => 'تم ' . $status_text . ' التحويل المخزني من ' . $transfer_info['from_branch_name'] . ' إلى ' . $transfer_info['to_branch_name'],
                'icon' => $icon,
                'priority' => $priority,
                'type' => 'inventory',
                'reference_id' => $transfer_id,
                'reference_type' => 'inventory_transfer',
                'url' => 'inventory/transfer/edit&transfer_id=' . $transfer_id
            );

            // إرسال الإشعار للمستخدمين المعنيين (مثل مديري المخازن)
            $this->model_notification_notification->addNotification($notification_data);
        }
    }

    /**
     * إنشاء حركات المخزون للتحويل
     */
    private function createTransferMovements($transfer_id) {
        $transfer_info = $this->getTransfer($transfer_id);
        $products = $this->getTransferProducts($transfer_id);

        if ($transfer_info && $products) {
            $this->load->model('inventory/movement');
            $this->load->model('inventory/inventory');

            foreach ($products as $product) {
                // الحصول على تكلفة المنتج من المخزون المصدر
                $product_cost = $this->getProductCostFromInventory(
                    $transfer_info['from_branch_id'],
                    $product['product_id'],
                    $product['unit_id']
                );

                // حركة خروج من الفرع المصدر
                $out_data = array(
                    'branch_id' => $transfer_info['from_branch_id'],
                    'product_id' => $product['product_id'],
                    'unit_id' => $product['unit_id'],
                    'movement_type' => 'out',
                    'quantity' => $product['quantity'],
                    'unit_cost' => $product_cost,
                    'reference_type' => 'transfer',
                    'reference_id' => $transfer_id,
                    'notes' => 'تحويل مخزني - خروج'
                );
                $this->model_inventory_movement->addMovement($out_data);

                // حركة دخول للفرع المستلم
                $in_data = array(
                    'branch_id' => $transfer_info['to_branch_id'],
                    'product_id' => $product['product_id'],
                    'unit_id' => $product['unit_id'],
                    'movement_type' => 'in',
                    'quantity' => $product['quantity'],
                    'unit_cost' => $product_cost,
                    'reference_type' => 'transfer',
                    'reference_id' => $transfer_id,
                    'notes' => 'تحويل مخزني - دخول'
                );
                $this->model_inventory_movement->addMovement($in_data);
            }
        }
    }

    /**
     * الحصول على تكلفة المنتج من المخزون
     */
    private function getProductCostFromInventory($branch_id, $product_id, $unit_id) {
        $query = $this->db->query("SELECT average_cost FROM " . DB_PREFIX . "product_inventory
            WHERE branch_id = '" . (int)$branch_id . "'
            AND product_id = '" . (int)$product_id . "'
            AND unit_id = '" . (int)$unit_id . "'");

        if ($query->num_rows && $query->row['average_cost'] > 0) {
            return $query->row['average_cost'];
        }

        // إذا لم يتم العثور على تكلفة في المخزون، نحصل عليها من بيانات المنتج
        $query = $this->db->query("SELECT cost, price FROM " . DB_PREFIX . "product
            WHERE product_id = '" . (int)$product_id . "'");

        if ($query->num_rows) {
            // استخدام التكلفة إذا كانت متوفرة، وإلا استخدام السعر
            return !empty($query->row['cost']) ? $query->row['cost'] : $query->row['price'];
        }

        return 0;
    }

    /**
     * الحصول على معلومات التحويل
     */
    public function getTransfer($transfer_id) {
        $query = $this->db->query("SELECT t.*,
                fb.name AS from_branch_name,
                tb.name AS to_branch_name,
                CONCAT(u.firstname, ' ', u.lastname) AS created_by_name
            FROM " . DB_PREFIX . "inventory_transfer t
            LEFT JOIN " . DB_PREFIX . "branch fb ON (t.from_branch_id = fb.branch_id)
            LEFT JOIN " . DB_PREFIX . "branch tb ON (t.to_branch_id = tb.branch_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (t.created_by = u.user_id)
            WHERE t.transfer_id = '" . (int)$transfer_id . "'");

        return $query->row;
    }

    /**
     * الحصول على منتجات التحويل
     */
    public function getTransferProducts($transfer_id) {
        $query = $this->db->query("SELECT tp.*,
                pd.name AS product_name,
                u.desc_en AS unit_name
            FROM " . DB_PREFIX . "inventory_transfer_item tp
            LEFT JOIN " . DB_PREFIX . "product_description pd
                ON (tp.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "unit u
                ON (tp.unit_id = u.unit_id)
            WHERE tp.transfer_id = '" . (int)$transfer_id . "'");

        return $query->rows;
    }

    /**
     * الحصول على قائمة التحويلات
     */
    public function getTransfers($data = array()) {
        $sql = "SELECT t.*,
                fb.name AS from_branch_name,
                tb.name AS to_branch_name,
                CONCAT(u.firstname, ' ', u.lastname) AS created_by_name
            FROM " . DB_PREFIX . "inventory_transfer t
            LEFT JOIN " . DB_PREFIX . "branch fb ON (t.from_branch_id = fb.branch_id)
            LEFT JOIN " . DB_PREFIX . "branch tb ON (t.to_branch_id = tb.branch_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (t.created_by = u.user_id)
            WHERE 1 ";

        if (!empty($data['filter_reference'])) {
            $sql .= " AND t.reference_number LIKE '%" . $this->db->escape($data['filter_reference']) . "%'";
        }

        if (!empty($data['filter_from_branch'])) {
            $sql .= " AND t.from_branch_id = '" . (int)$data['filter_from_branch'] . "'";
        }

        if (!empty($data['filter_to_branch'])) {
            $sql .= " AND t.to_branch_id = '" . (int)$data['filter_to_branch'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND t.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(t.transfer_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(t.transfer_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY t.transfer_date DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            $start = (int)$data['start'];
            $limit = (int)$data['limit'];
            $sql .= " LIMIT " . $start . "," . $limit;
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * حذف تحويل مخزني
     */
    public function deleteTransfer($transfer_id) {
        // التحقق من حالة التحويل قبل الحذف
        $transfer_info = $this->getTransfer($transfer_id);

        if ($transfer_info && in_array($transfer_info['status'], array('confirmed', 'completed'))) {
            // إذا كان التحويل مؤكد أو مكتمل، نقوم بإلغاء تأثيره على المخزون
            $this->reverseTransferMovements($transfer_id);
        }

        // حذف بنود التحويل
        $this->db->query("DELETE FROM " . DB_PREFIX . "inventory_transfer_item WHERE transfer_id = '" . (int)$transfer_id . "'");

        // حذف التحويل نفسه
        $this->db->query("DELETE FROM " . DB_PREFIX . "inventory_transfer WHERE transfer_id = '" . (int)$transfer_id . "'");
    }

    /**
     * إلغاء تأثير التحويل على المخزون
     */
    private function reverseTransferMovements($transfer_id) {
        $transfer_info = $this->getTransfer($transfer_id);
        $products = $this->getTransferProducts($transfer_id);

        if ($transfer_info && $products) {
            $this->load->model('inventory/movement');

            foreach ($products as $product) {
                // الحصول على تكلفة المنتج من المخزون المستلم
                $product_cost = $this->getProductCostFromInventory(
                    $transfer_info['to_branch_id'],
                    $product['product_id'],
                    $product['unit_id']
                );

                // حركة دخول للفرع المصدر (عكس الخروج السابق)
                $in_data = array(
                    'branch_id' => $transfer_info['from_branch_id'],
                    'product_id' => $product['product_id'],
                    'unit_id' => $product['unit_id'],
                    'movement_type' => 'in',
                    'quantity' => $product['quantity'],
                    'unit_cost' => $product_cost,
                    'reference_type' => 'transfer_reversal',
                    'reference_id' => $transfer_id,
                    'notes' => 'إلغاء تحويل مخزني - إعادة للمصدر'
                );
                $this->model_inventory_movement->addMovement($in_data);

                // حركة خروج من الفرع المستلم (عكس الدخول السابق)
                $out_data = array(
                    'branch_id' => $transfer_info['to_branch_id'],
                    'product_id' => $product['product_id'],
                    'unit_id' => $product['unit_id'],
                    'movement_type' => 'out',
                    'quantity' => $product['quantity'],
                    'unit_cost' => $product_cost,
                    'reference_type' => 'transfer_reversal',
                    'reference_id' => $transfer_id,
                    'notes' => 'إلغاء تحويل مخزني - خروج من المستلم'
                );
                $this->model_inventory_movement->addMovement($out_data);
            }
        }
    }

    /**
     * الحصول على إجمالي عدد التحويلات
     */
    public function getTotalTransfers($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "inventory_transfer t WHERE 1";

        if (!empty($data['filter_reference'])) {
            $sql .= " AND t.reference_number LIKE '%" . $this->db->escape($data['filter_reference']) . "%'";
        }

        if (!empty($data['filter_from_branch'])) {
            $sql .= " AND t.from_branch_id = '" . (int)$data['filter_from_branch'] . "'";
        }

        if (!empty($data['filter_to_branch'])) {
            $sql .= " AND t.to_branch_id = '" . (int)$data['filter_to_branch'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND t.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(t.transfer_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(t.transfer_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }
}