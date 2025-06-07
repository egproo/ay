<?php
class ModelSupplierAccounts extends Model {

    /**
     * الحصول على قائمة حسابات الموردين
     */
    public function getSupplierAccounts($data = array()) {
        $sql = "SELECT s.supplier_id, 
                       CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                       sa.account_number,
                       sa.current_balance,
                       sa.credit_limit,
                       sa.payment_terms,
                       sa.account_status,
                       sa.last_transaction_date as last_transaction
                FROM " . DB_PREFIX . "supplier s
                LEFT JOIN " . DB_PREFIX . "supplier_account sa ON (s.supplier_id = sa.supplier_id)
                WHERE 1 = 1";

        if (!empty($data['filter_supplier_name'])) {
            $sql .= " AND CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) LIKE '%" . $this->db->escape($data['filter_supplier_name']) . "%'";
        }

        if (!empty($data['filter_account_status'])) {
            $sql .= " AND sa.account_status = '" . $this->db->escape($data['filter_account_status']) . "'";
        }

        if (!empty($data['filter_balance_min'])) {
            $sql .= " AND sa.current_balance >= '" . (float)$data['filter_balance_min'] . "'";
        }

        if (!empty($data['filter_balance_max'])) {
            $sql .= " AND sa.current_balance <= '" . (float)$data['filter_balance_max'] . "'";
        }

        $sort_data = array(
            'supplier_name',
            'sa.account_number',
            'sa.current_balance',
            'sa.credit_limit',
            'sa.account_status',
            'sa.last_transaction_date'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY supplier_name";
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
     * الحصول على إجمالي عدد حسابات الموردين
     */
    public function getTotalSupplierAccounts($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier s
                LEFT JOIN " . DB_PREFIX . "supplier_account sa ON (s.supplier_id = sa.supplier_id)
                WHERE 1 = 1";

        if (!empty($data['filter_supplier_name'])) {
            $sql .= " AND CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) LIKE '%" . $this->db->escape($data['filter_supplier_name']) . "%'";
        }

        if (!empty($data['filter_account_status'])) {
            $sql .= " AND sa.account_status = '" . $this->db->escape($data['filter_account_status']) . "'";
        }

        if (!empty($data['filter_balance_min'])) {
            $sql .= " AND sa.current_balance >= '" . (float)$data['filter_balance_min'] . "'";
        }

        if (!empty($data['filter_balance_max'])) {
            $sql .= " AND sa.current_balance <= '" . (float)$data['filter_balance_max'] . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على حساب مورد
     */
    public function getSupplierAccount($supplier_id) {
        $query = $this->db->query("SELECT s.*, 
                                          CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          sa.*
                                   FROM " . DB_PREFIX . "supplier s
                                   LEFT JOIN " . DB_PREFIX . "supplier_account sa ON (s.supplier_id = sa.supplier_id)
                                   WHERE s.supplier_id = '" . (int)$supplier_id . "'");

        return $query->row;
    }

    /**
     * الحصول على معاملات مورد
     */
    public function getSupplierTransactions($supplier_id, $limit = 50) {
        $query = $this->db->query("SELECT st.*, 
                                          u.firstname AS user_firstname, u.lastname AS user_lastname,
                                          CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS user_name
                                   FROM " . DB_PREFIX . "supplier_transaction st
                                   LEFT JOIN " . DB_PREFIX . "user u ON (st.user_id = u.user_id)
                                   WHERE st.supplier_id = '" . (int)$supplier_id . "'
                                   ORDER BY st.transaction_date DESC, st.transaction_id DESC
                                   LIMIT " . (int)$limit);

        return $query->rows;
    }

    /**
     * الحصول على ملخص حساب مورد
     */
    public function getSupplierAccountSummary($supplier_id) {
        $summary = array();

        // الرصيد الحالي
        $query = $this->db->query("SELECT current_balance, credit_limit FROM " . DB_PREFIX . "supplier_account WHERE supplier_id = '" . (int)$supplier_id . "'");
        if ($query->num_rows) {
            $summary['current_balance'] = $query->row['current_balance'];
            $summary['credit_limit'] = $query->row['credit_limit'];
            $summary['available_credit'] = $query->row['credit_limit'] - $query->row['current_balance'];
        } else {
            $summary['current_balance'] = 0;
            $summary['credit_limit'] = 0;
            $summary['available_credit'] = 0;
        }

        // إجمالي المشتريات هذا الشهر
        $query = $this->db->query("SELECT SUM(amount) as total FROM " . DB_PREFIX . "supplier_transaction 
                                   WHERE supplier_id = '" . (int)$supplier_id . "' 
                                   AND transaction_type = 'purchase' 
                                   AND MONTH(transaction_date) = MONTH(CURDATE()) 
                                   AND YEAR(transaction_date) = YEAR(CURDATE())");
        $summary['monthly_purchases'] = $query->row['total'] ? $query->row['total'] : 0;

        // إجمالي المدفوعات هذا الشهر
        $query = $this->db->query("SELECT SUM(amount) as total FROM " . DB_PREFIX . "supplier_transaction 
                                   WHERE supplier_id = '" . (int)$supplier_id . "' 
                                   AND transaction_type = 'payment' 
                                   AND MONTH(transaction_date) = MONTH(CURDATE()) 
                                   AND YEAR(transaction_date) = YEAR(CURDATE())");
        $summary['monthly_payments'] = $query->row['total'] ? $query->row['total'] : 0;

        // عدد الفواتير المعلقة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_invoice 
                                   WHERE supplier_id = '" . (int)$supplier_id . "' 
                                   AND status = 'pending'");
        $summary['pending_invoices'] = $query->row['total'];

        return $summary;
    }

    /**
     * الحصول على تاريخ المدفوعات
     */
    public function getSupplierPaymentHistory($supplier_id, $limit = 20) {
        $query = $this->db->query("SELECT sp.*, 
                                          pm.name as payment_method_name
                                   FROM " . DB_PREFIX . "supplier_payment sp
                                   LEFT JOIN " . DB_PREFIX . "payment_method pm ON (sp.payment_method_id = pm.payment_method_id)
                                   WHERE sp.supplier_id = '" . (int)$supplier_id . "'
                                   ORDER BY sp.payment_date DESC
                                   LIMIT " . (int)$limit);

        return $query->rows;
    }

    /**
     * إضافة معاملة جديدة
     */
    public function addTransaction($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_transaction SET 
                         supplier_id = '" . (int)$data['supplier_id'] . "',
                         transaction_type = '" . $this->db->escape($data['transaction_type']) . "',
                         amount = '" . (float)$data['amount'] . "',
                         transaction_date = '" . $this->db->escape($data['transaction_date']) . "',
                         reference = '" . $this->db->escape($data['reference']) . "',
                         description = '" . $this->db->escape($data['description']) . "',
                         user_id = '" . (int)$this->user->getId() . "',
                         date_added = NOW()");

        $transaction_id = $this->db->getLastId();

        // تحديث رصيد المورد
        $this->updateSupplierBalance($data['supplier_id'], $data['amount'], $data['transaction_type']);

        return $transaction_id;
    }

    /**
     * إضافة دفعة جديدة
     */
    public function addPayment($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_payment SET 
                         supplier_id = '" . (int)$data['supplier_id'] . "',
                         payment_amount = '" . (float)$data['payment_amount'] . "',
                         payment_method_id = '" . (int)$data['payment_method'] . "',
                         payment_date = '" . $this->db->escape($data['payment_date']) . "',
                         reference_number = '" . $this->db->escape($data['reference_number']) . "',
                         notes = '" . $this->db->escape($data['notes']) . "',
                         created_by = '" . (int)$this->user->getId() . "',
                         date_added = NOW()");

        $payment_id = $this->db->getLastId();

        // إضافة معاملة دفع
        $transaction_data = array(
            'supplier_id' => $data['supplier_id'],
            'transaction_type' => 'payment',
            'amount' => $data['payment_amount'],
            'transaction_date' => $data['payment_date'],
            'reference' => $data['reference_number'],
            'description' => 'دفعة - ' . $data['notes']
        );

        $this->addTransaction($transaction_data);

        return $payment_id;
    }

    /**
     * تحديث رصيد المورد
     */
    public function updateSupplierBalance($supplier_id, $amount, $transaction_type) {
        // التأكد من وجود حساب للمورد
        $this->ensureSupplierAccount($supplier_id);

        if ($transaction_type == 'purchase' || $transaction_type == 'invoice') {
            // زيادة الرصيد (دين على الشركة)
            $this->db->query("UPDATE " . DB_PREFIX . "supplier_account 
                             SET current_balance = current_balance + '" . (float)$amount . "',
                                 last_transaction_date = NOW()
                             WHERE supplier_id = '" . (int)$supplier_id . "'");
        } elseif ($transaction_type == 'payment' || $transaction_type == 'credit') {
            // تقليل الرصيد (دفع للمورد)
            $this->db->query("UPDATE " . DB_PREFIX . "supplier_account 
                             SET current_balance = current_balance - '" . (float)$amount . "',
                                 last_transaction_date = NOW()
                             WHERE supplier_id = '" . (int)$supplier_id . "'");
        }
    }

    /**
     * التأكد من وجود حساب للمورد
     */
    public function ensureSupplierAccount($supplier_id) {
        $query = $this->db->query("SELECT supplier_id FROM " . DB_PREFIX . "supplier_account WHERE supplier_id = '" . (int)$supplier_id . "'");

        if (!$query->num_rows) {
            // إنشاء حساب جديد للمورد
            $account_number = 'SUP' . str_pad($supplier_id, 6, '0', STR_PAD_LEFT);
            
            $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_account SET 
                             supplier_id = '" . (int)$supplier_id . "',
                             account_number = '" . $this->db->escape($account_number) . "',
                             current_balance = '0.0000',
                             credit_limit = '0.0000',
                             payment_terms = 'net_30',
                             account_status = 'active',
                             date_created = NOW()");
        }
    }

    /**
     * الحصول على إحصائيات الحسابات
     */
    public function getAccountStatistics() {
        $statistics = array();

        // إجمالي عدد الحسابات
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_account");
        $statistics['total_accounts'] = $query->row['total'];

        // الحسابات النشطة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_account WHERE account_status = 'active'");
        $statistics['active_accounts'] = $query->row['total'];

        // إجمالي الأرصدة
        $query = $this->db->query("SELECT SUM(current_balance) as total FROM " . DB_PREFIX . "supplier_account");
        $statistics['total_balance'] = $query->row['total'] ? $query->row['total'] : 0;

        // الحسابات ذات الرصيد الموجب
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_account WHERE current_balance > 0");
        $statistics['positive_balance_accounts'] = $query->row['total'];

        // الحسابات ذات الرصيد السالب
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_account WHERE current_balance < 0");
        $statistics['negative_balance_accounts'] = $query->row['total'];

        return $statistics;
    }

    /**
     * تقرير أعمار الديون
     */
    public function getAgingReport() {
        $query = $this->db->query("SELECT s.supplier_id,
                                          CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          sa.current_balance,
                                          SUM(CASE WHEN DATEDIFF(CURDATE(), st.transaction_date) <= 30 THEN st.amount ELSE 0 END) as current_30,
                                          SUM(CASE WHEN DATEDIFF(CURDATE(), st.transaction_date) BETWEEN 31 AND 60 THEN st.amount ELSE 0 END) as days_31_60,
                                          SUM(CASE WHEN DATEDIFF(CURDATE(), st.transaction_date) BETWEEN 61 AND 90 THEN st.amount ELSE 0 END) as days_61_90,
                                          SUM(CASE WHEN DATEDIFF(CURDATE(), st.transaction_date) > 90 THEN st.amount ELSE 0 END) as over_90
                                   FROM " . DB_PREFIX . "supplier s
                                   LEFT JOIN " . DB_PREFIX . "supplier_account sa ON (s.supplier_id = sa.supplier_id)
                                   LEFT JOIN " . DB_PREFIX . "supplier_transaction st ON (s.supplier_id = st.supplier_id AND st.transaction_type IN ('purchase', 'invoice'))
                                   WHERE sa.current_balance > 0
                                   GROUP BY s.supplier_id
                                   ORDER BY sa.current_balance DESC");

        return $query->rows;
    }

    /**
     * ملخص تقرير أعمار الديون
     */
    public function getAgingSummary() {
        $query = $this->db->query("SELECT 
                                   SUM(CASE WHEN DATEDIFF(CURDATE(), st.transaction_date) <= 30 THEN st.amount ELSE 0 END) as total_current_30,
                                   SUM(CASE WHEN DATEDIFF(CURDATE(), st.transaction_date) BETWEEN 31 AND 60 THEN st.amount ELSE 0 END) as total_days_31_60,
                                   SUM(CASE WHEN DATEDIFF(CURDATE(), st.transaction_date) BETWEEN 61 AND 90 THEN st.amount ELSE 0 END) as total_days_61_90,
                                   SUM(CASE WHEN DATEDIFF(CURDATE(), st.transaction_date) > 90 THEN st.amount ELSE 0 END) as total_over_90,
                                   SUM(st.amount) as grand_total
                                   FROM " . DB_PREFIX . "supplier_transaction st
                                   WHERE st.transaction_type IN ('purchase', 'invoice')");

        return $query->row;
    }

    /**
     * كشف حساب مورد
     */
    public function getStatementTransactions($supplier_id, $date_start, $date_end) {
        $query = $this->db->query("SELECT st.*, 
                                          u.firstname AS user_firstname, u.lastname AS user_lastname,
                                          CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS user_name
                                   FROM " . DB_PREFIX . "supplier_transaction st
                                   LEFT JOIN " . DB_PREFIX . "user u ON (st.user_id = u.user_id)
                                   WHERE st.supplier_id = '" . (int)$supplier_id . "'
                                   AND DATE(st.transaction_date) BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                                   ORDER BY st.transaction_date ASC, st.transaction_id ASC");

        return $query->rows;
    }

    /**
     * الرصيد الافتتاحي
     */
    public function getOpeningBalance($supplier_id, $date_start) {
        $query = $this->db->query("SELECT SUM(
                                   CASE 
                                       WHEN transaction_type IN ('purchase', 'invoice') THEN amount
                                       WHEN transaction_type IN ('payment', 'credit') THEN -amount
                                       ELSE 0
                                   END
                                   ) as opening_balance
                                   FROM " . DB_PREFIX . "supplier_transaction 
                                   WHERE supplier_id = '" . (int)$supplier_id . "'
                                   AND DATE(transaction_date) < '" . $this->db->escape($date_start) . "'");

        return $query->row['opening_balance'] ? $query->row['opening_balance'] : 0;
    }

    /**
     * الرصيد الختامي
     */
    public function getClosingBalance($supplier_id, $date_end) {
        $query = $this->db->query("SELECT SUM(
                                   CASE 
                                       WHEN transaction_type IN ('purchase', 'invoice') THEN amount
                                       WHEN transaction_type IN ('payment', 'credit') THEN -amount
                                       ELSE 0
                                   END
                                   ) as closing_balance
                                   FROM " . DB_PREFIX . "supplier_transaction 
                                   WHERE supplier_id = '" . (int)$supplier_id . "'
                                   AND DATE(transaction_date) <= '" . $this->db->escape($date_end) . "'");

        return $query->row['closing_balance'] ? $query->row['closing_balance'] : 0;
    }

    /**
     * تحديث حد الائتمان
     */
    public function updateCreditLimit($supplier_id, $credit_limit) {
        $this->ensureSupplierAccount($supplier_id);
        
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_account 
                         SET credit_limit = '" . (float)$credit_limit . "'
                         WHERE supplier_id = '" . (int)$supplier_id . "'");
    }

    /**
     * تبديل حالة الحساب
     */
    public function toggleAccountStatus($supplier_id) {
        $query = $this->db->query("SELECT account_status FROM " . DB_PREFIX . "supplier_account WHERE supplier_id = '" . (int)$supplier_id . "'");

        if ($query->num_rows) {
            $new_status = ($query->row['account_status'] == 'active') ? 'suspended' : 'active';
            
            $this->db->query("UPDATE " . DB_PREFIX . "supplier_account 
                             SET account_status = '" . $this->db->escape($new_status) . "'
                             WHERE supplier_id = '" . (int)$supplier_id . "'");
            
            return $new_status;
        }

        return false;
    }
}
