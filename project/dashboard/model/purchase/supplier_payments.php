<?php
class ModelPurchaseSupplierPayments extends Model {

    /**
     * إضافة دفعة مورد جديدة
     */
    public function addPayment($data) {
        // إنشاء رقم دفعة تلقائي
        $payment_number = $this->generatePaymentNumber();

        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_payment SET 
                         payment_number = '" . $this->db->escape($payment_number) . "',
                         supplier_id = '" . (int)$data['supplier_id'] . "',
                         payment_amount = '" . (float)$data['payment_amount'] . "',
                         currency_id = '" . (int)$this->config->get('config_currency_id') . "',
                         payment_method_id = '" . (int)$data['payment_method_id'] . "',
                         payment_date = '" . $this->db->escape($data['payment_date']) . "',
                         reference_number = '" . $this->db->escape($data['reference_number']) . "',
                         bank_account_id = '" . (int)($data['bank_account_id'] ?? 0) . "',
                         check_number = '" . $this->db->escape($data['check_number'] ?? '') . "',
                         check_date = '" . $this->db->escape($data['check_date'] ?? null) . "',
                         notes = '" . $this->db->escape($data['notes']) . "',
                         status = '" . $this->db->escape($data['status']) . "',
                         created_by = '" . (int)$this->user->getId() . "'");

        $payment_id = $this->db->getLastId();

        // إضافة معاملة في حساب المورد إذا كانت الدفعة معتمدة
        if ($data['status'] == 'approved' || $data['status'] == 'paid') {
            $this->load->model('supplier/accounts');
            $transaction_data = array(
                'supplier_id' => $data['supplier_id'],
                'transaction_type' => 'payment',
                'amount' => $data['payment_amount'],
                'transaction_date' => $data['payment_date'],
                'reference' => $payment_number,
                'description' => 'دفعة رقم ' . $payment_number
            );
            $this->model_supplier_accounts->addTransaction($transaction_data);
        }

        return $payment_id;
    }

    /**
     * تعديل دفعة مورد
     */
    public function editPayment($payment_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_payment SET 
                         supplier_id = '" . (int)$data['supplier_id'] . "',
                         payment_amount = '" . (float)$data['payment_amount'] . "',
                         payment_method_id = '" . (int)$data['payment_method_id'] . "',
                         payment_date = '" . $this->db->escape($data['payment_date']) . "',
                         reference_number = '" . $this->db->escape($data['reference_number']) . "',
                         bank_account_id = '" . (int)($data['bank_account_id'] ?? 0) . "',
                         check_number = '" . $this->db->escape($data['check_number'] ?? '') . "',
                         check_date = '" . $this->db->escape($data['check_date'] ?? null) . "',
                         notes = '" . $this->db->escape($data['notes']) . "',
                         status = '" . $this->db->escape($data['status']) . "',
                         modified_by = '" . (int)$this->user->getId() . "',
                         date_modified = NOW()
                         WHERE payment_id = '" . (int)$payment_id . "'");
    }

    /**
     * حذف دفعة مورد
     */
    public function deletePayment($payment_id) {
        // التحقق من إمكانية الحذف
        $payment = $this->getPayment($payment_id);
        if ($payment && $payment['status'] != 'paid') {
            $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_payment_detail WHERE payment_id = '" . (int)$payment_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_payment WHERE payment_id = '" . (int)$payment_id . "'");
            return true;
        }
        return false;
    }

    /**
     * الحصول على دفعة مورد
     */
    public function getPayment($payment_id) {
        $query = $this->db->query("SELECT sp.*, 
                                          CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          pm.name AS payment_method_name,
                                          c.code AS currency_code,
                                          ba.account_name AS bank_account_name,
                                          CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS created_by_name
                                   FROM " . DB_PREFIX . "supplier_payment sp
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (sp.supplier_id = s.supplier_id)
                                   LEFT JOIN " . DB_PREFIX . "payment_method pm ON (sp.payment_method_id = pm.payment_method_id)
                                   LEFT JOIN " . DB_PREFIX . "currency c ON (sp.currency_id = c.currency_id)
                                   LEFT JOIN " . DB_PREFIX . "bank_account ba ON (sp.bank_account_id = ba.bank_account_id)
                                   LEFT JOIN " . DB_PREFIX . "user u ON (sp.created_by = u.user_id)
                                   WHERE sp.payment_id = '" . (int)$payment_id . "'");

        return $query->row;
    }

    /**
     * الحصول على قائمة دفعات الموردين
     */
    public function getPayments($data = array()) {
        $sql = "SELECT sp.*, 
                       CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                       pm.name AS payment_method_name,
                       c.code AS currency_code,
                       CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS created_by_name
                FROM " . DB_PREFIX . "supplier_payment sp
                LEFT JOIN " . DB_PREFIX . "supplier s ON (sp.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "payment_method pm ON (sp.payment_method_id = pm.payment_method_id)
                LEFT JOIN " . DB_PREFIX . "currency c ON (sp.currency_id = c.currency_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (sp.created_by = u.user_id)
                WHERE 1 = 1";

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND sp.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_payment_method'])) {
            $sql .= " AND sp.payment_method_id = '" . (int)$data['filter_payment_method'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND sp.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(sp.payment_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(sp.payment_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sort_data = array(
            'sp.payment_id',
            'sp.payment_number',
            'supplier_name',
            'sp.payment_amount',
            'sp.payment_date',
            'sp.status',
            'pm.name'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sp.payment_date";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
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
     * الحصول على إجمالي عدد دفعات الموردين
     */
    public function getTotalPayments($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier_payment sp
                LEFT JOIN " . DB_PREFIX . "supplier s ON (sp.supplier_id = s.supplier_id)
                WHERE 1 = 1";

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND sp.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_payment_method'])) {
            $sql .= " AND sp.payment_method_id = '" . (int)$data['filter_payment_method'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND sp.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(sp.payment_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(sp.payment_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * إنشاء رقم دفعة تلقائي
     */
    private function generatePaymentNumber() {
        $query = $this->db->query("SELECT payment_number FROM " . DB_PREFIX . "supplier_payment ORDER BY payment_id DESC LIMIT 1");

        if ($query->num_rows) {
            $last_number = $query->row['payment_number'];
            $number = (int)substr($last_number, 3) + 1;
        } else {
            $number = 1;
        }

        return 'PAY' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * اعتماد دفعة
     */
    public function approvePayment($payment_id) {
        $payment = $this->getPayment($payment_id);
        
        if ($payment && $payment['status'] == 'pending') {
            $this->db->query("UPDATE " . DB_PREFIX . "supplier_payment SET 
                             status = 'approved',
                             approved_by = '" . (int)$this->user->getId() . "',
                             approval_date = NOW()
                             WHERE payment_id = '" . (int)$payment_id . "'");

            // إضافة معاملة في حساب المورد
            $this->load->model('supplier/accounts');
            $transaction_data = array(
                'supplier_id' => $payment['supplier_id'],
                'transaction_type' => 'payment',
                'amount' => $payment['payment_amount'],
                'transaction_date' => $payment['payment_date'],
                'reference' => $payment['payment_number'],
                'description' => 'دفعة معتمدة رقم ' . $payment['payment_number']
            );
            $this->model_supplier_accounts->addTransaction($transaction_data);

            return true;
        }

        return false;
    }

    /**
     * إلغاء دفعة
     */
    public function cancelPayment($payment_id, $cancellation_reason = '') {
        $payment = $this->getPayment($payment_id);
        
        if ($payment && in_array($payment['status'], ['pending', 'approved'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "supplier_payment SET 
                             status = 'cancelled',
                             cancelled_by = '" . (int)$this->user->getId() . "',
                             cancellation_date = NOW(),
                             cancellation_reason = '" . $this->db->escape($cancellation_reason) . "'
                             WHERE payment_id = '" . (int)$payment_id . "'");

            return true;
        }

        return false;
    }

    /**
     * الحصول على إحصائيات الدفعات
     */
    public function getPaymentStatistics() {
        $statistics = array();

        // إجمالي الدفعات
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_payment");
        $statistics['total_payments'] = $query->row['total'];

        // الدفعات المعلقة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_payment WHERE status = 'pending'");
        $statistics['pending_payments'] = $query->row['total'];

        // الدفعات المعتمدة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_payment WHERE status = 'approved'");
        $statistics['approved_payments'] = $query->row['total'];

        // الدفعات المدفوعة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_payment WHERE status = 'paid'");
        $statistics['paid_payments'] = $query->row['total'];

        // إجمالي المبالغ
        $query = $this->db->query("SELECT SUM(payment_amount) as total FROM " . DB_PREFIX . "supplier_payment WHERE status IN ('approved', 'paid')");
        $statistics['total_amount'] = $query->row['total'] ? $query->row['total'] : 0;

        // دفعات هذا الشهر
        $query = $this->db->query("SELECT COUNT(*) as total, SUM(payment_amount) as amount FROM " . DB_PREFIX . "supplier_payment 
                                   WHERE MONTH(payment_date) = MONTH(CURDATE()) 
                                   AND YEAR(payment_date) = YEAR(CURDATE())
                                   AND status IN ('approved', 'paid')");
        $statistics['monthly_payments'] = $query->row['total'];
        $statistics['monthly_amount'] = $query->row['amount'] ? $query->row['amount'] : 0;

        return $statistics;
    }

    /**
     * تقرير دفعات الموردين
     */
    public function getPaymentReport($data = array()) {
        $sql = "SELECT sp.*, 
                       CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                       pm.name AS payment_method_name,
                       c.code AS currency_code,
                       ba.account_name AS bank_account_name
                FROM " . DB_PREFIX . "supplier_payment sp
                LEFT JOIN " . DB_PREFIX . "supplier s ON (sp.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "payment_method pm ON (sp.payment_method_id = pm.payment_method_id)
                LEFT JOIN " . DB_PREFIX . "currency c ON (sp.currency_id = c.currency_id)
                LEFT JOIN " . DB_PREFIX . "bank_account ba ON (sp.bank_account_id = ba.bank_account_id)
                WHERE 1 = 1";

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND sp.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(sp.payment_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(sp.payment_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY sp.payment_date DESC, sp.payment_id DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * ملخص تقرير الدفعات
     */
    public function getPaymentSummary($data = array()) {
        $sql = "SELECT 
                COUNT(*) as total_payments,
                SUM(payment_amount) as total_amount,
                SUM(CASE WHEN status = 'pending' THEN payment_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'approved' THEN payment_amount ELSE 0 END) as approved_amount,
                SUM(CASE WHEN status = 'paid' THEN payment_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = 'cancelled' THEN payment_amount ELSE 0 END) as cancelled_amount
                FROM " . DB_PREFIX . "supplier_payment sp
                WHERE 1 = 1";

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND sp.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(sp.payment_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(sp.payment_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row;
    }

    /**
     * الحصول على دفعات مورد معين
     */
    public function getSupplierPayments($supplier_id, $limit = 10) {
        $query = $this->db->query("SELECT sp.*, 
                                          pm.name AS payment_method_name,
                                          c.code AS currency_code
                                   FROM " . DB_PREFIX . "supplier_payment sp
                                   LEFT JOIN " . DB_PREFIX . "payment_method pm ON (sp.payment_method_id = pm.payment_method_id)
                                   LEFT JOIN " . DB_PREFIX . "currency c ON (sp.currency_id = c.currency_id)
                                   WHERE sp.supplier_id = '" . (int)$supplier_id . "'
                                   ORDER BY sp.payment_date DESC
                                   LIMIT " . (int)$limit);

        return $query->rows;
    }

    /**
     * البحث في الدفعات
     */
    public function searchPayments($search_term) {
        $query = $this->db->query("SELECT sp.*, 
                                          CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          pm.name AS payment_method_name
                                   FROM " . DB_PREFIX . "supplier_payment sp
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (sp.supplier_id = s.supplier_id)
                                   LEFT JOIN " . DB_PREFIX . "payment_method pm ON (sp.payment_method_id = pm.payment_method_id)
                                   WHERE sp.payment_number LIKE '%" . $this->db->escape($search_term) . "%'
                                   OR sp.reference_number LIKE '%" . $this->db->escape($search_term) . "%'
                                   OR CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) LIKE '%" . $this->db->escape($search_term) . "%'
                                   ORDER BY sp.payment_date DESC
                                   LIMIT 10");

        return $query->rows;
    }
}
