<?php
/**
 * نموذج إدارة الحسابات المصرفية المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelAccountsBankAccountsAdvanced extends Model {

    /**
     * إضافة حساب مصرفي جديد
     */
    public function addBankAccount($data) {
        $sql = "
            INSERT INTO " . DB_PREFIX . "bank_accounts SET
            account_number = '" . $this->db->escape($data['account_number']) . "',
            account_name = '" . $this->db->escape($data['account_name']) . "',
            bank_name = '" . $this->db->escape($data['bank_name']) . "',
            bank_code = '" . $this->db->escape($data['bank_code']) . "',
            branch_name = '" . $this->db->escape($data['branch_name']) . "',
            branch_code = '" . $this->db->escape($data['branch_code']) . "',
            swift_code = '" . $this->db->escape($data['swift_code']) . "',
            iban = '" . $this->db->escape($data['iban']) . "',
            currency = '" . $this->db->escape($data['currency']) . "',
            account_type = '" . $this->db->escape($data['account_type']) . "',
            opening_balance = '" . (float)$data['opening_balance'] . "',
            current_balance = '" . (float)$data['opening_balance'] . "',
            credit_limit = '" . (float)$data['credit_limit'] . "',
            minimum_balance = '" . (float)$data['minimum_balance'] . "',
            interest_rate = '" . (float)$data['interest_rate'] . "',
            bank_charges = '" . (float)$data['bank_charges'] . "',
            contact_person = '" . $this->db->escape($data['contact_person']) . "',
            contact_phone = '" . $this->db->escape($data['contact_phone']) . "',
            contact_email = '" . $this->db->escape($data['contact_email']) . "',
            address = '" . $this->db->escape($data['address']) . "',
            chart_account_id = '" . (int)$data['chart_account_id'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_date = NOW(),
            modified_date = NOW()
        ";

        $this->db->query($sql);
        $account_id = $this->db->getLastId();

        // إنشاء قيد افتتاحي إذا كان هناك رصيد افتتاحي
        if ($data['opening_balance'] != 0) {
            $this->createOpeningBalanceEntry($account_id, $data);
        }

        return $account_id;
    }

    /**
     * تعديل حساب مصرفي
     */
    public function editBankAccount($account_id, $data) {
        $sql = "
            UPDATE " . DB_PREFIX . "bank_accounts SET
            account_number = '" . $this->db->escape($data['account_number']) . "',
            account_name = '" . $this->db->escape($data['account_name']) . "',
            bank_name = '" . $this->db->escape($data['bank_name']) . "',
            bank_code = '" . $this->db->escape($data['bank_code']) . "',
            branch_name = '" . $this->db->escape($data['branch_name']) . "',
            branch_code = '" . $this->db->escape($data['branch_code']) . "',
            swift_code = '" . $this->db->escape($data['swift_code']) . "',
            iban = '" . $this->db->escape($data['iban']) . "',
            currency = '" . $this->db->escape($data['currency']) . "',
            account_type = '" . $this->db->escape($data['account_type']) . "',
            credit_limit = '" . (float)$data['credit_limit'] . "',
            minimum_balance = '" . (float)$data['minimum_balance'] . "',
            interest_rate = '" . (float)$data['interest_rate'] . "',
            bank_charges = '" . (float)$data['bank_charges'] . "',
            contact_person = '" . $this->db->escape($data['contact_person']) . "',
            contact_phone = '" . $this->db->escape($data['contact_phone']) . "',
            contact_email = '" . $this->db->escape($data['contact_email']) . "',
            address = '" . $this->db->escape($data['address']) . "',
            chart_account_id = '" . (int)$data['chart_account_id'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_date = NOW()
            WHERE account_id = '" . (int)$account_id . "'
        ";

        $this->db->query($sql);
    }

    /**
     * حذف حساب مصرفي
     */
    public function deleteBankAccount($account_id) {
        // التحقق من وجود معاملات
        $transaction_count = $this->getTransactionCount($account_id);

        if ($transaction_count > 0) {
            throw new Exception('لا يمكن حذف الحساب المصرفي لوجود معاملات مرتبطة به');
        }

        // حذف سجلات التسوية
        $this->db->query("DELETE FROM " . DB_PREFIX . "bank_reconciliation WHERE account_id = '" . (int)$account_id . "'");

        // حذف الحساب
        $this->db->query("DELETE FROM " . DB_PREFIX . "bank_accounts WHERE account_id = '" . (int)$account_id . "'");
    }

    /**
     * الحصول على حساب مصرفي
     */
    public function getBankAccount($account_id) {
        $query = $this->db->query("
            SELECT ba.*,
                   coa.account_code, coa.account_name as chart_account_name,
                   CONCAT(u1.firstname, ' ', u1.lastname) as created_by_name,
                   CONCAT(u2.firstname, ' ', u2.lastname) as modified_by_name
            FROM " . DB_PREFIX . "bank_accounts ba
            LEFT JOIN " . DB_PREFIX . "chart_of_accounts coa ON ba.chart_account_id = coa.account_id
            LEFT JOIN " . DB_PREFIX . "user u1 ON ba.created_by = u1.user_id
            LEFT JOIN " . DB_PREFIX . "user u2 ON ba.modified_by = u2.user_id
            WHERE ba.account_id = '" . (int)$account_id . "'
        ");

        return $query->row;
    }

    /**
     * الحصول على قائمة الحسابات المصرفية
     */
    public function getBankAccounts($data = array()) {
        $sql = "
            SELECT ba.*,
                   coa.account_code,
                   CONCAT(u.firstname, ' ', u.lastname) as created_by_name
            FROM " . DB_PREFIX . "bank_accounts ba
            LEFT JOIN " . DB_PREFIX . "chart_of_accounts coa ON ba.chart_account_id = coa.account_id
            LEFT JOIN " . DB_PREFIX . "user u ON ba.created_by = u.user_id
            WHERE 1
        ";

        if (!empty($data['filter_name'])) {
            $sql .= " AND (ba.account_name LIKE '%" . $this->db->escape($data['filter_name']) . "%' OR ba.account_number LIKE '%" . $this->db->escape($data['filter_name']) . "%')";
        }

        if (!empty($data['filter_bank'])) {
            $sql .= " AND ba.bank_name LIKE '%" . $this->db->escape($data['filter_bank']) . "%'";
        }

        if (!empty($data['filter_currency'])) {
            $sql .= " AND ba.currency = '" . $this->db->escape($data['filter_currency']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND ba.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $sort_data = array(
            'account_name',
            'account_number',
            'bank_name',
            'currency',
            'current_balance',
            'status',
            'created_date'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY account_name";
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
     * الحصول على إجمالي عدد الحسابات المصرفية
     */
    public function getTotalBankAccounts($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "bank_accounts ba WHERE 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND (ba.account_name LIKE '%" . $this->db->escape($data['filter_name']) . "%' OR ba.account_number LIKE '%" . $this->db->escape($data['filter_name']) . "%')";
        }

        if (!empty($data['filter_bank'])) {
            $sql .= " AND ba.bank_name LIKE '%" . $this->db->escape($data['filter_bank']) . "%'";
        }

        if (!empty($data['filter_currency'])) {
            $sql .= " AND ba.currency = '" . $this->db->escape($data['filter_currency']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND ba.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * إجراء التسوية البنكية
     */
    public function performReconciliation($data) {
        $account_id = $data['account_id'];
        $statement_date = $data['statement_date'];
        $statement_balance = $data['statement_balance'];
        $reconciled_transactions = $data['reconciled_transactions'] ?? array();

        // إنشاء سجل التسوية
        $reconciliation_id = $this->createReconciliationRecord($account_id, $statement_date, $statement_balance);

        // تحديث المعاملات المسواة
        $total_reconciled = 0;
        foreach ($reconciled_transactions as $transaction_id) {
            $this->markTransactionAsReconciled($transaction_id, $reconciliation_id);
            $transaction = $this->getTransaction($transaction_id);
            $total_reconciled += $transaction['amount'];
        }

        // حساب الفروقات
        $book_balance = $this->getCurrentBalance($account_id);
        $difference = $statement_balance - $book_balance;

        // تحديث سجل التسوية
        $this->updateReconciliationRecord($reconciliation_id, $total_reconciled, $difference);

        // تحديث تاريخ آخر تسوية
        $this->updateLastReconciledDate($account_id, $statement_date);

        return array(
            'reconciliation_id' => $reconciliation_id,
            'statement_balance' => $statement_balance,
            'book_balance' => $book_balance,
            'difference' => $difference,
            'reconciled_count' => count($reconciled_transactions),
            'total_reconciled' => $total_reconciled
        );
    }

    /**
     * معالجة التحويل البنكي
     */
    public function processTransfer($data) {
        $from_account_id = $data['from_account_id'];
        $to_account_id = $data['to_account_id'];
        $amount = $data['amount'];
        $transfer_date = $data['transfer_date'];
        $description = $data['description'];
        $reference = $data['reference'];

        // التحقق من الرصيد الكافي
        $from_account = $this->getBankAccount($from_account_id);
        if ($from_account['current_balance'] < $amount) {
            throw new Exception('الرصيد غير كافي للتحويل');
        }

        // إنشاء سجل التحويل
        $transfer_id = $this->createTransferRecord($data);

        // إنشاء القيود المحاسبية
        $journal_entry_id = $this->createTransferJournalEntry($data, $transfer_id);

        // تحديث أرصدة الحسابات
        $this->updateAccountBalance($from_account_id, -$amount);
        $this->updateAccountBalance($to_account_id, $amount);

        return array(
            'transfer_id' => $transfer_id,
            'journal_entry_id' => $journal_entry_id,
            'from_account_balance' => $from_account['current_balance'] - $amount,
            'to_account_balance' => $this->getCurrentBalance($to_account_id)
        );
    }

    /**
     * تحليل الحساب المصرفي
     */
    public function analyzeAccount($account_id) {
        $account = $this->getBankAccount($account_id);

        $analysis = array();

        // التحليل المالي
        $analysis['financial'] = $this->analyzeAccountFinancial($account);

        // تحليل التدفقات النقدية
        $analysis['cash_flow'] = $this->analyzeCashFlow($account_id);

        // تحليل المعاملات
        $analysis['transactions'] = $this->analyzeTransactions($account_id);

        // تحليل الأداء
        $analysis['performance'] = $this->analyzeAccountPerformance($account_id);

        // التوصيات
        $analysis['recommendations'] = $this->generateAccountRecommendations($account, $analysis);

        return $analysis;
    }

    /**
     * حساب التدفقات النقدية
     */
    public function calculateCashFlow($account_id, $period_days) {
        $start_date = date('Y-m-d', strtotime("-{$period_days} days"));
        $end_date = date('Y-m-d');

        $query = $this->db->query("
            SELECT
                DATE(bt.transaction_date) as transaction_date,
                SUM(CASE WHEN bt.transaction_type = 'credit' THEN bt.amount ELSE 0 END) as inflow,
                SUM(CASE WHEN bt.transaction_type = 'debit' THEN bt.amount ELSE 0 END) as outflow,
                SUM(CASE WHEN bt.transaction_type = 'credit' THEN bt.amount ELSE -bt.amount END) as net_flow
            FROM " . DB_PREFIX . "bank_transactions bt
            WHERE bt.account_id = '" . (int)$account_id . "'
            AND bt.transaction_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "'
            AND bt.status = 'completed'
            GROUP BY DATE(bt.transaction_date)
            ORDER BY transaction_date
        ");

        $cash_flow = array();
        $running_balance = $this->getBalanceAsOf($account_id, $start_date);

        foreach ($query->rows as $row) {
            $running_balance += $row['net_flow'];

            $cash_flow[] = array(
                'date' => $row['transaction_date'],
                'inflow' => (float)$row['inflow'],
                'outflow' => (float)$row['outflow'],
                'net_flow' => (float)$row['net_flow'],
                'running_balance' => $running_balance
            );
        }

        return $cash_flow;
    }

    /**
     * الحصول على تاريخ المعاملات
     */
    public function getTransactionHistory($account_id, $limit = 50, $offset = 0) {
        $query = $this->db->query("
            SELECT bt.*,
                   CASE
                       WHEN bt.transaction_type = 'credit' THEN 'إيداع'
                       WHEN bt.transaction_type = 'debit' THEN 'سحب'
                       ELSE 'أخرى'
                   END as transaction_type_name
            FROM " . DB_PREFIX . "bank_transactions bt
            WHERE bt.account_id = '" . (int)$account_id . "'
            ORDER BY bt.transaction_date DESC, bt.transaction_id DESC
            LIMIT " . (int)$offset . ", " . (int)$limit . "
        ");

        return $query->rows;
    }

    /**
     * الحصول على تاريخ الأرصدة
     */
    public function getBalanceHistory($account_id, $period_days) {
        $start_date = date('Y-m-d', strtotime("-{$period_days} days"));
        $end_date = date('Y-m-d');

        $query = $this->db->query("
            SELECT
                DATE(bh.balance_date) as balance_date,
                bh.closing_balance
            FROM " . DB_PREFIX . "bank_balance_history bh
            WHERE bh.account_id = '" . (int)$account_id . "'
            AND bh.balance_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "'
            ORDER BY bh.balance_date
        ");

        return $query->rows;
    }

    /**
     * الحصول على البيانات للتصدير
     */
    public function getAccountsForExport($filter_data) {
        $accounts = $this->getBankAccounts($filter_data);

        $export_data = array();

        foreach ($accounts as $account) {
            $export_data[] = array(
                'account_number' => $account['account_number'],
                'account_name' => $account['account_name'],
                'bank_name' => $account['bank_name'],
                'branch_name' => $account['branch_name'],
                'currency' => $account['currency'],
                'current_balance' => $account['current_balance'],
                'status' => $account['status'],
                'created_date' => $account['created_date']
            );
        }

        return $export_data;
    }

    /**
     * إنشاء قيد الرصيد الافتتاحي
     */
    private function createOpeningBalanceEntry($account_id, $data) {
        $this->load->model('accounts/journal_entry');

        $account_info = $this->getBankAccount($account_id);

        $journal_data = array(
            'entry_date' => date('Y-m-d'),
            'reference' => 'OB-' . $data['account_number'],
            'description' => 'رصيد افتتاحي للحساب المصرفي: ' . $data['account_name'],
            'total_debit' => abs($data['opening_balance']),
            'total_credit' => abs($data['opening_balance']),
            'status' => 'posted',
            'lines' => array()
        );

        if ($data['opening_balance'] > 0) {
            // رصيد مدين
            $journal_data['lines'][] = array(
                'account_id' => $data['chart_account_id'],
                'debit' => $data['opening_balance'],
                'credit' => 0,
                'description' => 'رصيد افتتاحي مدين'
            );

            // حساب رأس المال أو الأرباح المحتجزة (دائن)
            $equity_account_id = $this->getEquityAccountId();
            $journal_data['lines'][] = array(
                'account_id' => $equity_account_id,
                'debit' => 0,
                'credit' => $data['opening_balance'],
                'description' => 'رصيد افتتاحي دائن'
            );
        } else {
            // رصيد دائن
            $journal_data['lines'][] = array(
                'account_id' => $data['chart_account_id'],
                'debit' => 0,
                'credit' => abs($data['opening_balance']),
                'description' => 'رصيد افتتاحي دائن'
            );

            // حساب رأس المال أو الأرباح المحتجزة (مدين)
            $equity_account_id = $this->getEquityAccountId();
            $journal_data['lines'][] = array(
                'account_id' => $equity_account_id,
                'debit' => abs($data['opening_balance']),
                'credit' => 0,
                'description' => 'رصيد افتتاحي مدين'
            );
        }

        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * الحصول على عدد المعاملات
     */
    private function getTransactionCount($account_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "bank_transactions
            WHERE account_id = '" . (int)$account_id . "'
        ");

        return (int)$query->row['count'];
    }

    /**
     * إنشاء سجل التسوية
     */
    private function createReconciliationRecord($account_id, $statement_date, $statement_balance) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "bank_reconciliation SET
            account_id = '" . (int)$account_id . "',
            statement_date = '" . $this->db->escape($statement_date) . "',
            statement_balance = '" . (float)$statement_balance . "',
            reconciliation_date = NOW(),
            reconciled_by = '" . (int)$this->user->getId() . "',
            status = 'in_progress'
        ");

        return $this->db->getLastId();
    }

    /**
     * تحديث سجل التسوية
     */
    private function updateReconciliationRecord($reconciliation_id, $total_reconciled, $difference) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "bank_reconciliation SET
            total_reconciled = '" . (float)$total_reconciled . "',
            difference = '" . (float)$difference . "',
            status = '" . ($difference == 0 ? 'completed' : 'pending') . "'
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
        ");
    }

    /**
     * تحديث تاريخ آخر تسوية
     */
    private function updateLastReconciledDate($account_id, $statement_date) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "bank_accounts SET
            last_reconciled = '" . $this->db->escape($statement_date) . "'
            WHERE account_id = '" . (int)$account_id . "'
        ");
    }

    /**
     * إنشاء سجل التحويل
     */
    private function createTransferRecord($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "bank_transfers SET
            from_account_id = '" . (int)$data['from_account_id'] . "',
            to_account_id = '" . (int)$data['to_account_id'] . "',
            amount = '" . (float)$data['amount'] . "',
            transfer_date = '" . $this->db->escape($data['transfer_date']) . "',
            reference = '" . $this->db->escape($data['reference']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            transfer_fees = '" . (float)($data['transfer_fees'] ?? 0) . "',
            exchange_rate = '" . (float)($data['exchange_rate'] ?? 1) . "',
            status = 'completed',
            created_by = '" . (int)$this->user->getId() . "',
            created_date = NOW()
        ");

        return $this->db->getLastId();
    }

    /**
     * إنشاء قيد التحويل المحاسبي
     */
    private function createTransferJournalEntry($data, $transfer_id) {
        $this->load->model('accounts/journal_entry');

        $from_account = $this->getBankAccount($data['from_account_id']);
        $to_account = $this->getBankAccount($data['to_account_id']);

        $journal_data = array(
            'entry_date' => $data['transfer_date'],
            'reference' => 'TRF-' . $transfer_id,
            'description' => 'تحويل بنكي: ' . $data['description'],
            'total_debit' => $data['amount'],
            'total_credit' => $data['amount'],
            'status' => 'posted',
            'lines' => array(
                array(
                    'account_id' => $to_account['chart_account_id'],
                    'debit' => $data['amount'],
                    'credit' => 0,
                    'description' => 'تحويل إلى: ' . $to_account['account_name']
                ),
                array(
                    'account_id' => $from_account['chart_account_id'],
                    'debit' => 0,
                    'credit' => $data['amount'],
                    'description' => 'تحويل من: ' . $from_account['account_name']
                )
            )
        );

        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * تحديث رصيد الحساب
     */
    private function updateAccountBalance($account_id, $amount) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "bank_accounts SET
            current_balance = current_balance + (" . (float)$amount . "),
            modified_date = NOW()
            WHERE account_id = '" . (int)$account_id . "'
        ");
    }

    /**
     * الحصول على الرصيد الحالي
     */
    private function getCurrentBalance($account_id) {
        $query = $this->db->query("
            SELECT current_balance
            FROM " . DB_PREFIX . "bank_accounts
            WHERE account_id = '" . (int)$account_id . "'
        ");

        return (float)$query->row['current_balance'];
    }

    /**
     * الحصول على حساب حقوق الملكية
     */
    private function getEquityAccountId() {
        $query = $this->db->query("
            SELECT account_id
            FROM " . DB_PREFIX . "chart_of_accounts
            WHERE account_code LIKE '3%'
            AND parent_id = 0
            ORDER BY account_code
            LIMIT 1
        ");

        return $query->row ? $query->row['account_id'] : 1;
    }
}
