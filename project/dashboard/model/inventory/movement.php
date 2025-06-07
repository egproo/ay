<?php
class ModelInventoryMovement extends Model {

    /**
     * إضافة حركة مخزنية جديدة
     */
    public function addMovement($data) {
        // تعيين القيم الافتراضية
        $unit_cost = isset($data['unit_cost']) ? (float)$data['unit_cost'] : 0;

        // إذا لم يتم تحديد التكلفة وكانت الحركة دخول، نحاول الحصول على تكلفة المنتج
        if ($unit_cost == 0 && $data['movement_type'] == 'in') {
            $unit_cost = $this->getProductCost($data['product_id']);
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_movement SET
            branch_id = '" . (int)$data['branch_id'] . "',
            product_id = '" . (int)$data['product_id'] . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            movement_type = '" . $this->db->escape($data['movement_type']) . "',
            quantity = '" . (float)$data['quantity'] . "',
            unit_cost = '" . (float)$unit_cost . "',
            reference_type = '" . $this->db->escape($data['reference_type']) . "',
            reference_id = '" . (int)$data['reference_id'] . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()");

        $movement_id = $this->db->getLastId();

        // تحديث رصيد المخزون
        $this->updateInventoryBalance($movement_id);

        return $movement_id;
    }

    /**
     * الحصول على تكلفة المنتج
     */
    private function getProductCost($product_id) {
        $query = $this->db->query("SELECT price, cost FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");

        if ($query->num_rows) {
            // استخدام التكلفة إذا كانت متوفرة، وإلا استخدام السعر
            return !empty($query->row['cost']) ? $query->row['cost'] : $query->row['price'];
        }

        return 0;
    }

    /**
     * تحديث رصيد المخزون بناءً على الحركة
     */
    public function updateInventoryBalance($movement_id) {
        $movement_info = $this->getMovement($movement_id);

        if ($movement_info) {
            // الحصول على معلومات المخزون الحالية
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_inventory
                WHERE branch_id = '" . (int)$movement_info['branch_id'] . "'
                AND product_id = '" . (int)$movement_info['product_id'] . "'
                AND unit_id = '" . (int)$movement_info['unit_id'] . "'");

            $inventory = $query->row;

            if ($inventory) {
                // تحديث المخزون الموجود
                if ($movement_info['movement_type'] == 'in') {
                    // حساب التكلفة المتوسطة الجديدة (المتوسط المرجح)
                    $current_value = $inventory['quantity'] * $inventory['average_cost'];
                    $new_value = $movement_info['quantity'] * $movement_info['unit_cost'];
                    $new_quantity = $inventory['quantity'] + $movement_info['quantity'];
                    $new_average_cost = ($new_quantity > 0) ? ($current_value + $new_value) / $new_quantity : 0;

                    $this->db->query("UPDATE " . DB_PREFIX . "product_inventory SET
                        quantity = quantity + " . (float)$movement_info['quantity'] . ",
                        average_cost = '" . (float)$new_average_cost . "',
                        last_movement_id = '" . (int)$movement_id . "'
                        WHERE branch_id = '" . (int)$movement_info['branch_id'] . "'
                        AND product_id = '" . (int)$movement_info['product_id'] . "'
                        AND unit_id = '" . (int)$movement_info['unit_id'] . "'");
                } else {
                    // للحركات الخارجة، نحدث الكمية فقط
                    $this->db->query("UPDATE " . DB_PREFIX . "product_inventory SET
                        quantity = quantity - " . (float)$movement_info['quantity'] . ",
                        last_movement_id = '" . (int)$movement_id . "'
                        WHERE branch_id = '" . (int)$movement_info['branch_id'] . "'
                        AND product_id = '" . (int)$movement_info['product_id'] . "'
                        AND unit_id = '" . (int)$movement_info['unit_id'] . "'");
                }
            } else {
                // إنشاء سجل جديد إذا لم يكن موجودًا
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory SET
                    branch_id = '" . (int)$movement_info['branch_id'] . "',
                    product_id = '" . (int)$movement_info['product_id'] . "',
                    unit_id = '" . (int)$movement_info['unit_id'] . "',
                    quantity = '" . ($movement_info['movement_type'] == 'in' ? $movement_info['quantity'] : -$movement_info['quantity']) . "',
                    average_cost = '" . (float)($movement_info['movement_type'] == 'in' ? $movement_info['unit_cost'] : 0) . "',
                    last_movement_id = '" . (int)$movement_id . "'");
            }

            // تسجيل القيد المحاسبي إذا كان مطلوبًا
            $this->createAccountingEntry($movement_info);
        }
    }

    /**
     * إنشاء قيد محاسبي للحركة المخزنية
     */
    private function createAccountingEntry($movement_info) {
        // التحقق من وجود نموذج المحاسبة
        if (file_exists(DIR_APPLICATION . 'model/accounting/journal.php')) {
            $this->load->model('accounting/journal');

            $total_value = $movement_info['quantity'] * $movement_info['unit_cost'];

            // بيانات القيد
            $entry_data = array(
                'reference_type' => 'inventory_movement',
                'reference_id' => $movement_info['movement_id'],
                'date' => date('Y-m-d'),
                'description' => 'حركة مخزنية - ' . ($movement_info['movement_type'] == 'in' ? 'إضافة' : 'صرف') . ' - ' . $movement_info['notes'],
                'entries' => array()
            );

            if ($movement_info['movement_type'] == 'in') {
                // مدين: حساب المخزون
                $entry_data['entries'][] = array(
                    'account_id' => $this->getInventoryAccountId($movement_info['product_id']),
                    'is_debit' => 1,
                    'amount' => $total_value
                );

                // دائن: حساب مقابل (يعتمد على نوع المرجع)
                $entry_data['entries'][] = array(
                    'account_id' => $this->getCounterAccountId($movement_info['reference_type']),
                    'is_debit' => 0,
                    'amount' => $total_value
                );
            } else {
                // مدين: حساب مقابل (يعتمد على نوع المرجع)
                $entry_data['entries'][] = array(
                    'account_id' => $this->getCounterAccountId($movement_info['reference_type']),
                    'is_debit' => 1,
                    'amount' => $total_value
                );

                // دائن: حساب المخزون
                $entry_data['entries'][] = array(
                    'account_id' => $this->getInventoryAccountId($movement_info['product_id']),
                    'is_debit' => 0,
                    'amount' => $total_value
                );
            }

            // إنشاء القيد المحاسبي
            if (method_exists($this->model_accounting_journal, 'addJournalEntry')) {
                $this->model_accounting_journal->addJournalEntry($entry_data);
            }
        }
    }

    /**
     * الحصول على معرف حساب المخزون
     */
    private function getInventoryAccountId($product_id) {
        // يمكن تخصيص حساب لكل منتج أو استخدام حساب افتراضي
        $query = $this->db->query("SELECT inventory_account_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");

        if ($query->num_rows && !empty($query->row['inventory_account_id'])) {
            return $query->row['inventory_account_id'];
        }

        // حساب المخزون الافتراضي
        return 150101; // رقم حساب افتراضي
    }

    /**
     * الحصول على معرف الحساب المقابل بناءً على نوع المرجع
     */
    private function getCounterAccountId($reference_type) {
        switch ($reference_type) {
            case 'purchase':
                return 200101; // حساب المشتريات
            case 'sale':
                return 400101; // حساب المبيعات
            case 'transfer':
                return 150101; // حساب المخزون (نفس الحساب لأن التحويل بين الفروع)
            case 'adjustment':
                return 500101; // حساب تسويات المخزون
            default:
                return 500101; // حساب افتراضي
        }
    }

    /**
     * الحصول على معلومات حركة مخزنية
     */
    public function getMovement($movement_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "inventory_movement
            WHERE movement_id = '" . (int)$movement_id . "'");

        return $query->row;
    }

    /**
     * الحصول على قائمة حركات المخزون
     */
    public function getMovements($data = array()) {
        $sql = "SELECT m.*,
                b.name AS branch_name,
                pd.name AS product_name,
                u.desc_en AS unit_name,
                CONCAT(u.firstname, ' ', u.lastname) AS created_by_name
            FROM " . DB_PREFIX . "inventory_movement m
            LEFT JOIN " . DB_PREFIX . "branch b ON (m.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (m.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (m.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (m.created_by = u.user_id)
            WHERE 1 ";

        // فلترة حسب الفرع
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND m.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        // فلترة حسب المنتج
        if (!empty($data['filter_product_id'])) {
            $sql .= " AND m.product_id = '" . (int)$data['filter_product_id'] . "'";
        }

        // فلترة حسب نوع الحركة
        if (!empty($data['filter_movement_type'])) {
            $sql .= " AND m.movement_type = '" . $this->db->escape($data['filter_movement_type']) . "'";
        }

        // فلترة حسب التاريخ
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(m.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(m.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        // الترتيب
        $sql .= " ORDER BY m.created_at DESC";

        // الصفحات
        if (isset($data['start']) || isset($data['limit'])) {
            $start = (int)$data['start'];
            $limit = (int)$data['limit'];
            $sql .= " LIMIT " . $start . "," . $limit;
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * الحصول على إجمالي عدد الحركات
     */
    public function getTotalMovements($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "inventory_movement m WHERE 1";

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND m.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_product_id'])) {
            $sql .= " AND m.product_id = '" . (int)$data['filter_product_id'] . "'";
        }

        if (!empty($data['filter_movement_type'])) {
            $sql .= " AND m.movement_type = '" . $this->db->escape($data['filter_movement_type']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(m.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(m.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }
}