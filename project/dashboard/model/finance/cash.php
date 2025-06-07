<?php
/**
 * نموذج إدارة النقدية والصناديق المحسن
 * يدعم متابعة الأرصدة والحركات النقدية والتكامل المحاسبي
 */
class ModelFinanceCash extends Model {

    /**
     * إضافة صندوق جديد
     */
    public function addCashBox($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "cash_boxes SET
            name = '" . $this->db->escape($data['name']) . "',
            description = '" . $this->db->escape($data['description'] ?? '') . "',
            account_id = '" . (int)$data['account_id'] . "',
            branch_id = '" . (int)($data['branch_id'] ?? 0) . "',
            currency_code = '" . $this->db->escape($data['currency_code'] ?? $this->config->get('config_currency')) . "',
            opening_balance = '" . (float)($data['opening_balance'] ?? 0) . "',
            current_balance = '" . (float)($data['opening_balance'] ?? 0) . "',
            is_active = '" . (int)($data['is_active'] ?? 1) . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");

        $cash_box_id = $this->db->getLastId();

        // إنشاء قيد افتتاحي إذا كان هناك رصيد افتتاحي
        if (!empty($data['opening_balance']) && $data['opening_balance'] > 0) {
            $this->createOpeningBalanceEntry($cash_box_id, $data['opening_balance'], $data['account_id']);
        }

        return $cash_box_id;
    }

    /**
     * تعديل صندوق موجود
     */
    public function editCashBox($cash_box_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "cash_boxes SET
            name = '" . $this->db->escape($data['name']) . "',
            description = '" . $this->db->escape($data['description'] ?? '') . "',
            account_id = '" . (int)$data['account_id'] . "',
            branch_id = '" . (int)($data['branch_id'] ?? 0) . "',
            currency_code = '" . $this->db->escape($data['currency_code'] ?? $this->config->get('config_currency')) . "',
            is_active = '" . (int)($data['is_active'] ?? 1) . "',
            date_modified = NOW()
            WHERE cash_box_id = '" . (int)$cash_box_id . "'");

        return true;
    }

    /**
     * حذف صندوق
     */
    public function deleteCashBox($cash_box_id) {
        // التحقق من وجود حركات
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "cash_transactions WHERE cash_box_id = '" . (int)$cash_box_id . "'");
        if ($query->row['total'] > 0) {
            return false; // لا يمكن حذف صندوق له حركات
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "cash_boxes WHERE cash_box_id = '" . (int)$cash_box_id . "'");
        return true;
    }

    /**
     * الحصول على صندوق واحد
     */
    public function getCashBox($cash_box_id) {
        $query = $this->db->query("SELECT cb.*, a.account_code, ad.name as account_name
                                  FROM " . DB_PREFIX . "cash_boxes cb
                                  LEFT JOIN " . DB_PREFIX . "accounts a ON cb.account_id = a.account_id
                                  LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  WHERE cb.cash_box_id = '" . (int)$cash_box_id . "'");
        return $query->row;
    }

    /**
     * الحصول على قائمة الصناديق
     */
    public function getCashBoxes($data = array()) {
        $sql = "SELECT cb.*, a.account_code, ad.name as account_name
                FROM " . DB_PREFIX . "cash_boxes cb
                LEFT JOIN " . DB_PREFIX . "accounts a ON cb.account_id = a.account_id
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                WHERE 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND cb.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (isset($data['filter_active'])) {
            $sql .= " AND cb.is_active = '" . (int)$data['filter_active'] . "'";
        }

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND cb.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        $sql .= " ORDER BY cb.name ASC";

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
     * الحصول على إجمالي عدد الصناديق
     */
    public function getTotalCashBoxes($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cash_boxes cb WHERE 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND cb.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (isset($data['filter_active'])) {
            $sql .= " AND cb.is_active = '" . (int)$data['filter_active'] . "'";
        }

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND cb.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * إضافة حركة نقدية
     */
    public function addCashTransaction($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "cash_transactions SET
            cash_box_id = '" . (int)$data['cash_box_id'] . "',
            transaction_type = '" . $this->db->escape($data['transaction_type']) . "',
            amount = '" . (float)$data['amount'] . "',
            description = '" . $this->db->escape($data['description']) . "',
            reference_number = '" . $this->db->escape($data['reference_number'] ?? '') . "',
            transaction_date = '" . $this->db->escape($data['transaction_date']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");

        $transaction_id = $this->db->getLastId();

        // تحديث رصيد الصندوق
        $this->updateCashBoxBalance($data['cash_box_id']);

        // إنشاء قيد محاسبي
        $this->createJournalEntry($transaction_id, $data);

        return $transaction_id;
    }

    /**
     * تحديث رصيد الصندوق
     */
    public function updateCashBoxBalance($cash_box_id) {
        $query = $this->db->query("SELECT
                                    COALESCE(SUM(CASE WHEN transaction_type = 'in' THEN amount ELSE 0 END), 0) as total_in,
                                    COALESCE(SUM(CASE WHEN transaction_type = 'out' THEN amount ELSE 0 END), 0) as total_out
                                  FROM " . DB_PREFIX . "cash_transactions
                                  WHERE cash_box_id = '" . (int)$cash_box_id . "'");

        $cash_box = $this->getCashBox($cash_box_id);
        $opening_balance = (float)$cash_box['opening_balance'];
        $total_in = (float)$query->row['total_in'];
        $total_out = (float)$query->row['total_out'];
        $current_balance = $opening_balance + $total_in - $total_out;

        $this->db->query("UPDATE " . DB_PREFIX . "cash_boxes
                         SET current_balance = '" . (float)$current_balance . "'
                         WHERE cash_box_id = '" . (int)$cash_box_id . "'");

        return $current_balance;
    }

    /**
     * إنشاء قيد افتتاحي
     */
    private function createOpeningBalanceEntry($cash_box_id, $amount, $account_id) {
        $this->load->model('accounts/journal_entry');

        $cash_box = $this->getCashBox($cash_box_id);

        $journal_data = [
            'journal_date' => date('Y-m-d'),
            'journal_number' => 'CASH-OPEN-' . $cash_box_id,
            'description' => 'رصيد افتتاحي للصندوق: ' . $cash_box['name'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'lines' => [
                [
                    'account_id' => $account_id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => 'رصيد افتتاحي'
                ],
                [
                    'account_id' => $this->getEquityAccountId(),
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => 'رصيد افتتاحي'
                ]
            ]
        ];

        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * إنشاء قيد محاسبي للحركة النقدية
     */
    private function createJournalEntry($transaction_id, $data) {
        $this->load->model('accounts/journal_entry');

        $cash_box = $this->getCashBox($data['cash_box_id']);

        $journal_data = [
            'journal_date' => $data['transaction_date'],
            'journal_number' => 'CASH-' . $transaction_id,
            'description' => $data['description'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'cash_transaction',
            'reference_id' => $transaction_id
        ];

        if ($data['transaction_type'] == 'in') {
            // قيد إيداع نقدي
            $journal_data['lines'] = [
                [
                    'account_id' => $cash_box['account_id'],
                    'debit_amount' => $data['amount'],
                    'credit_amount' => 0,
                    'description' => $data['description']
                ],
                [
                    'account_id' => $this->getDefaultIncomeAccountId(),
                    'debit_amount' => 0,
                    'credit_amount' => $data['amount'],
                    'description' => $data['description']
                ]
            ];
        } else {
            // قيد سحب نقدي
            $journal_data['lines'] = [
                [
                    'account_id' => $this->getDefaultExpenseAccountId(),
                    'debit_amount' => $data['amount'],
                    'credit_amount' => 0,
                    'description' => $data['description']
                ],
                [
                    'account_id' => $cash_box['account_id'],
                    'debit_amount' => 0,
                    'credit_amount' => $data['amount'],
                    'description' => $data['description']
                ]
            ];
        }

        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * الحصول على حساب حقوق الملكية الافتراضي
     */
    private function getEquityAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'equity' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }

    /**
     * الحصول على حساب الإيرادات الافتراضي
     */
    private function getDefaultIncomeAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'revenue' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }

    /**
     * الحصول على حساب المصروفات الافتراضي
     */
    private function getDefaultExpenseAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'expense' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }

    /**
     * الحصول على حركات الصندوق
     */
    public function getCashTransactions($cash_box_id, $data = array()) {
        $sql = "SELECT ct.*, u.username as created_by_name
                FROM " . DB_PREFIX . "cash_transactions ct
                LEFT JOIN " . DB_PREFIX . "user u ON ct.created_by = u.user_id
                WHERE ct.cash_box_id = '" . (int)$cash_box_id . "'";

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(ct.transaction_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(ct.transaction_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND ct.transaction_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        $sql .= " ORDER BY ct.transaction_date DESC, ct.transaction_id DESC";

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
     * الحصول على حركة نقدية واحدة
     */
    public function getCashTransaction($transaction_id) {
        $query = $this->db->query("SELECT ct.*, cb.name as cash_box_name, u.username as created_by_name
                                  FROM " . DB_PREFIX . "cash_transactions ct
                                  LEFT JOIN " . DB_PREFIX . "cash_boxes cb ON ct.cash_box_id = cb.cash_box_id
                                  LEFT JOIN " . DB_PREFIX . "user u ON ct.created_by = u.user_id
                                  WHERE ct.transaction_id = '" . (int)$transaction_id . "'");
        return $query->row;
    }

    /**
     * الحصول على ملخص الصندوق
     */
    public function getCashBoxSummary($cash_box_id, $date_start = null, $date_end = null) {
        $currency_code = $this->config->get('config_currency');

        $cash_box = $this->getCashBox($cash_box_id);
        if (!$cash_box) {
            return array();
        }

        $sql = "SELECT
                    COUNT(*) as transaction_count,
                    COALESCE(SUM(CASE WHEN transaction_type = 'in' THEN amount ELSE 0 END), 0) as total_in,
                    COALESCE(SUM(CASE WHEN transaction_type = 'out' THEN amount ELSE 0 END), 0) as total_out
                FROM " . DB_PREFIX . "cash_transactions
                WHERE cash_box_id = '" . (int)$cash_box_id . "'";

        if ($date_start) {
            $sql .= " AND DATE(transaction_date) >= '" . $this->db->escape($date_start) . "'";
        }

        if ($date_end) {
            $sql .= " AND DATE(transaction_date) <= '" . $this->db->escape($date_end) . "'";
        }

        $query = $this->db->query($sql);
        $stats = $query->row;

        $opening_balance = (float)$cash_box['opening_balance'];
        $total_in = (float)$stats['total_in'];
        $total_out = (float)$stats['total_out'];
        $current_balance = $opening_balance + $total_in - $total_out;

        return array(
            'cash_box' => $cash_box,
            'opening_balance' => $opening_balance,
            'opening_balance_formatted' => $this->currency->format($opening_balance, $currency_code),
            'total_in' => $total_in,
            'total_in_formatted' => $this->currency->format($total_in, $currency_code),
            'total_out' => $total_out,
            'total_out_formatted' => $this->currency->format($total_out, $currency_code),
            'current_balance' => $current_balance,
            'current_balance_formatted' => $this->currency->format($current_balance, $currency_code),
            'net_movement' => $total_in - $total_out,
            'net_movement_formatted' => $this->currency->format($total_in - $total_out, $currency_code),
            'transaction_count' => (int)$stats['transaction_count']
        );
    }

    /**
     * دوال التوافق مع النظام القديم
     */
    public function addCash($data) {
        return $this->addCashBox($data);
    }

    public function editCash($cash_id, $data) {
        return $this->editCashBox($cash_id, $data);
    }

    public function deleteCash($cash_id) {
        return $this->deleteCashBox($cash_id);
    }

    public function getCash($cash_id) {
        return $this->getCashBox($cash_id);
    }

    public function getCashes($data = array()) {
        return $this->getCashBoxes($data);
    }

    public function getTotalCashes() {
        return $this->getTotalCashBoxes();
    }
}