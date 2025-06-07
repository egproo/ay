<?php
/**
 * نموذج إدارة البنوك والحسابات البنكية المحسن
 * يدعم متابعة الأرصدة والحركات البنكية والتسوية
 */
class ModelBankBank extends Model {

    /**
     * إضافة حساب بنكي جديد
     */
    public function addBankAccount($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "bank_accounts SET
            bank_name = '" . $this->db->escape($data['bank_name']) . "',
            account_number = '" . $this->db->escape($data['account_number']) . "',
            account_name = '" . $this->db->escape($data['account_name']) . "',
            account_id = '" . (int)$data['account_id'] . "',
            branch_id = '" . (int)($data['branch_id'] ?? 0) . "',
            currency_code = '" . $this->db->escape($data['currency_code']) . "',
            iban = '" . $this->db->escape($data['iban'] ?? '') . "',
            swift_code = '" . $this->db->escape($data['swift_code'] ?? '') . "',
            opening_balance = '" . (float)($data['opening_balance'] ?? 0) . "',
            current_balance = '" . (float)($data['opening_balance'] ?? 0) . "',
            is_active = '" . (int)($data['is_active'] ?? 1) . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");

        $bank_account_id = $this->db->getLastId();

        // إنشاء قيد افتتاحي إذا كان هناك رصيد افتتاحي
        if (!empty($data['opening_balance']) && $data['opening_balance'] > 0) {
            $this->createOpeningBalanceEntry($bank_account_id, $data['opening_balance'], $data['account_id']);
        }

        return $bank_account_id;
    }

    /**
     * تعديل حساب بنكي موجود
     */
    public function editBankAccount($bank_account_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "bank_accounts SET
            bank_name = '" . $this->db->escape($data['bank_name']) . "',
            account_number = '" . $this->db->escape($data['account_number']) . "',
            account_name = '" . $this->db->escape($data['account_name']) . "',
            account_id = '" . (int)$data['account_id'] . "',
            branch_id = '" . (int)($data['branch_id'] ?? 0) . "',
            currency_code = '" . $this->db->escape($data['currency_code']) . "',
            iban = '" . $this->db->escape($data['iban'] ?? '') . "',
            swift_code = '" . $this->db->escape($data['swift_code'] ?? '') . "',
            is_active = '" . (int)($data['is_active'] ?? 1) . "',
            date_modified = NOW()
            WHERE bank_account_id = '" . (int)$bank_account_id . "'");

        return true;
    }

    /**
     * حذف حساب بنكي
     */
    public function deleteBankAccount($bank_account_id) {
        // التحقق من وجود حركات
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "bank_transactions WHERE bank_account_id = '" . (int)$bank_account_id . "'");
        if ($query->row['total'] > 0) {
            return false; // لا يمكن حذف حساب له حركات
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "bank_accounts WHERE bank_account_id = '" . (int)$bank_account_id . "'");
        return true;
    }

    /**
     * الحصول على حساب بنكي واحد
     */
    public function getBankAccount($bank_account_id) {
        $query = $this->db->query("SELECT ba.*, a.account_code, ad.name as account_name
                                  FROM " . DB_PREFIX . "bank_accounts ba
                                  LEFT JOIN " . DB_PREFIX . "accounts a ON ba.account_id = a.account_id
                                  LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  WHERE ba.bank_account_id = '" . (int)$bank_account_id . "'");
        return $query->row;
    }

    /**
     * الحصول على قائمة الحسابات البنكية
     */
    public function getBankAccounts($data = array()) {
        $sql = "SELECT ba.*, a.account_code, ad.name as account_name
                FROM " . DB_PREFIX . "bank_accounts ba
                LEFT JOIN " . DB_PREFIX . "accounts a ON ba.account_id = a.account_id
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                WHERE 1";

        if (!empty($data['filter_bank_name'])) {
            $sql .= " AND ba.bank_name LIKE '%" . $this->db->escape($data['filter_bank_name']) . "%'";
        }

        if (!empty($data['filter_account_number'])) {
            $sql .= " AND ba.account_number LIKE '%" . $this->db->escape($data['filter_account_number']) . "%'";
        }

        if (isset($data['filter_active'])) {
            $sql .= " AND ba.is_active = '" . (int)$data['filter_active'] . "'";
        }

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND ba.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        $sql .= " ORDER BY ba.bank_name ASC, ba.account_name ASC";

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
     * إضافة حركة بنكية
     */
    public function addBankTransaction($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "bank_transactions SET
            bank_account_id = '" . (int)$data['bank_account_id'] . "',
            transaction_type = '" . $this->db->escape($data['transaction_type']) . "',
            amount = '" . (float)$data['amount'] . "',
            description = '" . $this->db->escape($data['description']) . "',
            reference_number = '" . $this->db->escape($data['reference_number'] ?? '') . "',
            transaction_date = '" . $this->db->escape($data['transaction_date']) . "',
            value_date = '" . $this->db->escape($data['value_date'] ?? $data['transaction_date']) . "',
            is_reconciled = 0,
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");

        $transaction_id = $this->db->getLastId();

        // تحديث رصيد الحساب البنكي
        $this->updateBankAccountBalance($data['bank_account_id']);

        // إنشاء قيد محاسبي
        $this->createJournalEntry($transaction_id, $data);

        return $transaction_id;
    }

    /**
     * تحديث رصيد الحساب البنكي
     */
    public function updateBankAccountBalance($bank_account_id) {
        $query = $this->db->query("SELECT
                                    COALESCE(SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END), 0) as total_deposits,
                                    COALESCE(SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END), 0) as total_withdrawals
                                  FROM " . DB_PREFIX . "bank_transactions
                                  WHERE bank_account_id = '" . (int)$bank_account_id . "'");

        $bank_account = $this->getBankAccount($bank_account_id);
        $opening_balance = (float)$bank_account['opening_balance'];
        $total_deposits = (float)$query->row['total_deposits'];
        $total_withdrawals = (float)$query->row['total_withdrawals'];
        $current_balance = $opening_balance + $total_deposits - $total_withdrawals;

        $this->db->query("UPDATE " . DB_PREFIX . "bank_accounts
                         SET current_balance = '" . (float)$current_balance . "'
                         WHERE bank_account_id = '" . (int)$bank_account_id . "'");

        return $current_balance;
    }

    /**
     * الحصول على حركات الحساب البنكي
     */
    public function getBankTransactions($bank_account_id, $data = array()) {
        $sql = "SELECT bt.*, u.username as created_by_name
                FROM " . DB_PREFIX . "bank_transactions bt
                LEFT JOIN " . DB_PREFIX . "user u ON bt.created_by = u.user_id
                WHERE bt.bank_account_id = '" . (int)$bank_account_id . "'";

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(bt.transaction_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(bt.transaction_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND bt.transaction_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (isset($data['filter_reconciled'])) {
            $sql .= " AND bt.is_reconciled = '" . (int)$data['filter_reconciled'] . "'";
        }

        $sql .= " ORDER BY bt.transaction_date DESC, bt.transaction_id DESC";

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
     * تسوية حركة بنكية
     */
    public function reconcileTransaction($transaction_id, $reconciled_date = null) {
        $reconciled_date = $reconciled_date ?: date('Y-m-d H:i:s');

        $this->db->query("UPDATE " . DB_PREFIX . "bank_transactions SET
                         is_reconciled = 1,
                         reconciled_date = '" . $this->db->escape($reconciled_date) . "',
                         reconciled_by = '" . (int)$this->user->getId() . "'
                         WHERE transaction_id = '" . (int)$transaction_id . "'");

        return true;
    }

    /**
     * إلغاء تسوية حركة بنكية
     */
    public function unreconciletransaction($transaction_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "bank_transactions SET
                         is_reconciled = 0,
                         reconciled_date = NULL,
                         reconciled_by = NULL
                         WHERE transaction_id = '" . (int)$transaction_id . "'");

        return true;
    }

    /**
     * إنشاء قيد افتتاحي
     */
    private function createOpeningBalanceEntry($bank_account_id, $amount, $account_id) {
        $this->load->model('accounts/journal_entry');

        $bank_account = $this->getBankAccount($bank_account_id);

        $journal_data = [
            'journal_date' => date('Y-m-d'),
            'journal_number' => 'BANK-OPEN-' . $bank_account_id,
            'description' => 'رصيد افتتاحي للحساب البنكي: ' . $bank_account['bank_name'] . ' - ' . $bank_account['account_number'],
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
     * إنشاء قيد محاسبي للحركة البنكية
     */
    private function createJournalEntry($transaction_id, $data) {
        $this->load->model('accounts/journal_entry');

        $bank_account = $this->getBankAccount($data['bank_account_id']);

        $journal_data = [
            'journal_date' => $data['transaction_date'],
            'journal_number' => 'BANK-' . $transaction_id,
            'description' => $data['description'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'bank_transaction',
            'reference_id' => $transaction_id
        ];

        if ($data['transaction_type'] == 'deposit') {
            // قيد إيداع بنكي
            $journal_data['lines'] = [
                [
                    'account_id' => $bank_account['account_id'],
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
            // قيد سحب بنكي
            $journal_data['lines'] = [
                [
                    'account_id' => $this->getDefaultExpenseAccountId(),
                    'debit_amount' => $data['amount'],
                    'credit_amount' => 0,
                    'description' => $data['description']
                ],
                [
                    'account_id' => $bank_account['account_id'],
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
     * الحصول على إجمالي عدد الحسابات البنكية
     */
    public function getTotalBankAccounts($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "bank_accounts ba WHERE 1";

        if (!empty($data['filter_bank_name'])) {
            $sql .= " AND ba.bank_name LIKE '%" . $this->db->escape($data['filter_bank_name']) . "%'";
        }

        if (!empty($data['filter_account_number'])) {
            $sql .= " AND ba.account_number LIKE '%" . $this->db->escape($data['filter_account_number']) . "%'";
        }

        if (isset($data['filter_active'])) {
            $sql .= " AND ba.is_active = '" . (int)$data['filter_active'] . "'";
        }

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND ba.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * الحصول على ملخص الحساب البنكي
     */
    public function getBankAccountSummary($bank_account_id, $date_start = null, $date_end = null) {
        $currency_code = $this->config->get('config_currency');

        $bank_account = $this->getBankAccount($bank_account_id);
        if (!$bank_account) {
            return array();
        }

        $sql = "SELECT
                    COUNT(*) as transaction_count,
                    COALESCE(SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END), 0) as total_deposits,
                    COALESCE(SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END), 0) as total_withdrawals,
                    COALESCE(SUM(CASE WHEN is_reconciled = 1 THEN 1 ELSE 0 END), 0) as reconciled_count,
                    COALESCE(SUM(CASE WHEN is_reconciled = 0 THEN 1 ELSE 0 END), 0) as unreconciled_count
                FROM " . DB_PREFIX . "bank_transactions
                WHERE bank_account_id = '" . (int)$bank_account_id . "'";

        if ($date_start) {
            $sql .= " AND DATE(transaction_date) >= '" . $this->db->escape($date_start) . "'";
        }

        if ($date_end) {
            $sql .= " AND DATE(transaction_date) <= '" . $this->db->escape($date_end) . "'";
        }

        $query = $this->db->query($sql);
        $stats = $query->row;

        $opening_balance = (float)$bank_account['opening_balance'];
        $total_deposits = (float)$stats['total_deposits'];
        $total_withdrawals = (float)$stats['total_withdrawals'];
        $current_balance = $opening_balance + $total_deposits - $total_withdrawals;

        return array(
            'bank_account' => $bank_account,
            'opening_balance' => $opening_balance,
            'opening_balance_formatted' => $this->currency->format($opening_balance, $currency_code),
            'total_deposits' => $total_deposits,
            'total_deposits_formatted' => $this->currency->format($total_deposits, $currency_code),
            'total_withdrawals' => $total_withdrawals,
            'total_withdrawals_formatted' => $this->currency->format($total_withdrawals, $currency_code),
            'current_balance' => $current_balance,
            'current_balance_formatted' => $this->currency->format($current_balance, $currency_code),
            'net_movement' => $total_deposits - $total_withdrawals,
            'net_movement_formatted' => $this->currency->format($total_deposits - $total_withdrawals, $currency_code),
            'transaction_count' => (int)$stats['transaction_count'],
            'reconciled_count' => (int)$stats['reconciled_count'],
            'unreconciled_count' => (int)$stats['unreconciled_count']
        );
    }

    /**
     * دوال التوافق مع النظام القديم
     */
    public function addBank($data) {
        return $this->addBankAccount($data);
    }

    public function editBank($bank_id, $data) {
        return $this->editBankAccount($bank_id, $data);
    }

    public function deleteBank($bank_id) {
        return $this->deleteBankAccount($bank_id);
    }

    public function getBank($bank_id) {
        return $this->getBankAccount($bank_id);
    }

    public function getBanks($data = array()) {
        return $this->getBankAccounts($data);
    }

    public function getTotalBanks() {
        return $this->getTotalBankAccounts();
    }
}
?>
