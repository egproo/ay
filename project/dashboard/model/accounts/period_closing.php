<?php
/**
 * نموذج إغلاق الفترة المحاسبية
 * يدعم إغلاق الحسابات المؤقتة وترحيل الأرباح والخسائر
 */
class ModelAccountsPeriodClosing extends Model {

    /**
     * إغلاق فترة محاسبية
     */
    public function closePeriod($data) {
        try {
            // التحقق من عدم وجود فترة مفتوحة متداخلة
            if (!$this->validatePeriodDates($data['start_date'], $data['end_date'])) {
                return array('success' => false, 'error' => 'تواريخ الفترة متداخلة مع فترة أخرى');
            }

            // التحقق من توازن جميع القيود في الفترة
            if (!$this->validatePeriodBalance($data['start_date'], $data['end_date'])) {
                return array('success' => false, 'error' => 'يوجد قيود غير متوازنة في الفترة');
            }

            // بدء المعاملة
            $this->db->query("START TRANSACTION");

            // حساب صافي الدخل
            $net_income = $this->calculateNetIncome($data['start_date'], $data['end_date']);

            // إنشاء سجل الفترة المحاسبية
            $period_id = $this->createAccountingPeriod($data, $net_income);

            // إنشاء قيود الإغلاق
            $closing_entries = $this->createClosingEntries($period_id, $data['start_date'], $data['end_date'], $net_income);

            // تحديث أرصدة الحسابات
            $this->updateAccountBalances($data['start_date'], $data['end_date']);

            // إنهاء المعاملة
            $this->db->query("COMMIT");

            return array('success' => true, 'period_id' => $period_id, 'net_income' => $net_income);

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    /**
     * إعادة فتح فترة محاسبية
     */
    public function reopenPeriod($period_id) {
        try {
            // التحقق من حالة الفترة
            $period = $this->getAccountingPeriod($period_id);
            if (!$period || $period['status'] != 'closed') {
                return array('success' => false, 'error' => 'الفترة غير مغلقة أو غير موجودة');
            }

            // بدء المعاملة
            $this->db->query("START TRANSACTION");

            // حذف قيود الإغلاق
            $this->deleteClosingEntries($period_id);

            // تحديث حالة الفترة
            $this->db->query("UPDATE " . DB_PREFIX . "accounting_periods SET
                             status = 'open',
                             reopened_by = '" . (int)$this->user->getId() . "',
                             reopened_date = NOW()
                             WHERE period_id = '" . (int)$period_id . "'");

            // إعادة حساب أرصدة الحسابات
            $this->recalculateAccountBalances($period['start_date'], $period['end_date']);

            // إنهاء المعاملة
            $this->db->query("COMMIT");

            return array('success' => true);

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    /**
     * الحصول على الفترات المحاسبية
     */
    public function getAccountingPeriods($data = array()) {
        $sql = "SELECT ap.*, u.username as closed_by_name
                FROM " . DB_PREFIX . "accounting_periods ap
                LEFT JOIN " . DB_PREFIX . "user u ON ap.closed_by = u.user_id
                WHERE 1";

        if (!empty($data['filter_status'])) {
            $sql .= " AND ap.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $sql .= " ORDER BY ap.start_date DESC";

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
     * الحصول على فترة محاسبية واحدة
     */
    public function getAccountingPeriod($period_id) {
        $query = $this->db->query("SELECT ap.*, u.username as closed_by_name
                                  FROM " . DB_PREFIX . "accounting_periods ap
                                  LEFT JOIN " . DB_PREFIX . "user u ON ap.closed_by = u.user_id
                                  WHERE ap.period_id = '" . (int)$period_id . "'");
        return $query->row;
    }

    /**
     * الحصول على الفترة الحالية المفتوحة
     */
    public function getCurrentPeriod() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_periods
                                  WHERE status = 'open'
                                  ORDER BY start_date DESC
                                  LIMIT 1");
        return $query->row;
    }

    /**
     * الحصول على معاينة الإغلاق
     */
    public function getClosingPreview($start_date = null, $end_date = null) {
        if (!$start_date) $start_date = date('Y-01-01');
        if (!$end_date) $end_date = date('Y-12-31');

        $currency_code = $this->config->get('config_currency');

        // حساب الإيرادات
        $revenues = $this->getAccountTypeBalance('revenue', $start_date, $end_date);

        // حساب المصروفات
        $expenses = $this->getAccountTypeBalance('expense', $start_date, $end_date);

        // حساب صافي الدخل
        $net_income = $revenues - $expenses;

        // الحصول على تفاصيل الحسابات المؤقتة
        $revenue_accounts = $this->getTemporaryAccounts('revenue', $start_date, $end_date);
        $expense_accounts = $this->getTemporaryAccounts('expense', $start_date, $end_date);

        return array(
            'period' => array(
                'start_date' => $start_date,
                'end_date' => $end_date,
                'start_date_formatted' => date($this->language->get('date_format_short'), strtotime($start_date)),
                'end_date_formatted' => date($this->language->get('date_format_short'), strtotime($end_date))
            ),
            'summary' => array(
                'total_revenues' => $revenues,
                'total_expenses' => $expenses,
                'net_income' => $net_income,
                'total_revenues_formatted' => $this->currency->format($revenues, $currency_code),
                'total_expenses_formatted' => $this->currency->format($expenses, $currency_code),
                'net_income_formatted' => $this->currency->format($net_income, $currency_code)
            ),
            'accounts' => array(
                'revenues' => $revenue_accounts,
                'expenses' => $expense_accounts
            ),
            'validation' => array(
                'has_unbalanced_entries' => !$this->validatePeriodBalance($start_date, $end_date),
                'has_pending_entries' => $this->hasPendingEntries($start_date, $end_date),
                'can_close' => $this->canClosePeriod($start_date, $end_date)
            )
        );
    }

    /**
     * التحقق من صحة تواريخ الفترة
     */
    private function validatePeriodDates($start_date, $end_date) {
        $query = $this->db->query("SELECT COUNT(*) as count
                                  FROM " . DB_PREFIX . "accounting_periods
                                  WHERE status = 'closed'
                                  AND ((start_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "')
                                  OR (end_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "')
                                  OR (start_date <= '" . $this->db->escape($start_date) . "' AND end_date >= '" . $this->db->escape($end_date) . "'))");

        return $query->row['count'] == 0;
    }

    /**
     * التحقق من توازن القيود في الفترة
     */
    private function validatePeriodBalance($start_date, $end_date) {
        $query = $this->db->query("SELECT je.journal_id,
                                          SUM(jel.debit_amount) as total_debit,
                                          SUM(jel.credit_amount) as total_credit
                                  FROM " . DB_PREFIX . "journal_entry je
                                  JOIN " . DB_PREFIX . "journal_entry_line jel ON je.journal_id = jel.journal_id
                                  WHERE je.journal_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "'
                                  AND je.status = 'posted'
                                  GROUP BY je.journal_id
                                  HAVING ABS(total_debit - total_credit) > 0.01");

        return $query->num_rows == 0;
    }

    /**
     * حساب صافي الدخل
     */
    private function calculateNetIncome($start_date, $end_date) {
        $revenues = $this->getAccountTypeBalance('revenue', $start_date, $end_date);
        $expenses = $this->getAccountTypeBalance('expense', $start_date, $end_date);

        return $revenues - $expenses;
    }

    /**
     * الحصول على رصيد نوع حسابات معين
     */
    private function getAccountTypeBalance($account_type, $start_date, $end_date) {
        $query = $this->db->query("SELECT
                                    COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as balance
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                  WHERE a.account_type = '" . $this->db->escape($account_type) . "'
                                  AND je.journal_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "'
                                  AND je.status = 'posted'");

        $balance = (float)$query->row['balance'];

        // للإيرادات: الرصيد الدائن موجب، للمصروفات: الرصيد المدين موجب
        if ($account_type == 'revenue') {
            return abs($balance); // الإيرادات دائماً موجبة
        } else {
            return abs($balance); // المصروفات دائماً موجبة
        }
    }

    /**
     * إنشاء سجل الفترة المحاسبية
     */
    private function createAccountingPeriod($data, $net_income) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_periods SET
            period_name = '" . $this->db->escape($data['period_name']) . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            status = 'closed',
            net_income = '" . (float)$net_income . "',
            closing_notes = '" . $this->db->escape($data['closing_notes']) . "',
            closed_by = '" . (int)$this->user->getId() . "',
            closed_date = NOW(),
            date_added = NOW()");

        return $this->db->getLastId();
    }

    /**
     * إنشاء قيود الإغلاق
     */
    private function createClosingEntries($period_id, $start_date, $end_date, $net_income) {
        $this->load->model('accounts/journal_entry');

        $closing_entries = array();

        // 1. إغلاق حسابات الإيرادات
        $revenue_entry = $this->createRevenueClosingEntry($start_date, $end_date);
        if ($revenue_entry) {
            $closing_entries[] = $revenue_entry;
        }

        // 2. إغلاق حسابات المصروفات
        $expense_entry = $this->createExpenseClosingEntry($start_date, $end_date);
        if ($expense_entry) {
            $closing_entries[] = $expense_entry;
        }

        // 3. ترحيل صافي الدخل إلى الأرباح المحتجزة
        $income_entry = $this->createIncomeTransferEntry($net_income);
        if ($income_entry) {
            $closing_entries[] = $income_entry;
        }

        // ربط القيود بالفترة المحاسبية
        foreach ($closing_entries as $journal_id) {
            $this->db->query("UPDATE " . DB_PREFIX . "journal_entry SET
                             period_id = '" . (int)$period_id . "',
                             reference_type = 'period_closing',
                             reference_id = '" . (int)$period_id . "'
                             WHERE journal_id = '" . (int)$journal_id . "'");
        }

        return $closing_entries;
    }

    /**
     * إنشاء قيد إغلاق الإيرادات
     */
    private function createRevenueClosingEntry($start_date, $end_date) {
        $revenue_accounts = $this->getTemporaryAccounts('revenue', $start_date, $end_date);

        if (empty($revenue_accounts)) {
            return null;
        }

        $lines = array();
        $total_revenue = 0;

        // إقفال كل حساب إيراد
        foreach ($revenue_accounts as $account) {
            $balance = abs((float)$account['balance']);
            if ($balance > 0) {
                $lines[] = array(
                    'account_id' => $account['account_id'],
                    'debit_amount' => $balance,
                    'credit_amount' => 0,
                    'description' => 'إقفال حساب الإيرادات'
                );
                $total_revenue += $balance;
            }
        }

        // إلى حساب ملخص الدخل
        if ($total_revenue > 0) {
            $lines[] = array(
                'account_id' => $this->getIncomeSummaryAccountId(),
                'debit_amount' => 0,
                'credit_amount' => $total_revenue,
                'description' => 'إجمالي الإيرادات'
            );
        }

        if (!empty($lines)) {
            $journal_data = array(
                'journal_date' => $end_date,
                'journal_number' => 'CLOSE-REV-' . date('Y'),
                'description' => 'قيد إغلاق حسابات الإيرادات',
                'status' => 'posted',
                'created_by' => $this->user->getId(),
                'lines' => $lines
            );

            return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
        }

        return null;
    }

    /**
     * إنشاء قيد إغلاق المصروفات
     */
    private function createExpenseClosingEntry($start_date, $end_date) {
        $expense_accounts = $this->getTemporaryAccounts('expense', $start_date, $end_date);

        if (empty($expense_accounts)) {
            return null;
        }

        $lines = array();
        $total_expense = 0;

        // من حساب ملخص الدخل
        foreach ($expense_accounts as $account) {
            $balance = abs((float)$account['balance']);
            if ($balance > 0) {
                $total_expense += $balance;
            }
        }

        if ($total_expense > 0) {
            $lines[] = array(
                'account_id' => $this->getIncomeSummaryAccountId(),
                'debit_amount' => $total_expense,
                'credit_amount' => 0,
                'description' => 'إجمالي المصروفات'
            );
        }

        // إقفال كل حساب مصروف
        foreach ($expense_accounts as $account) {
            $balance = abs((float)$account['balance']);
            if ($balance > 0) {
                $lines[] = array(
                    'account_id' => $account['account_id'],
                    'debit_amount' => 0,
                    'credit_amount' => $balance,
                    'description' => 'إقفال حساب المصروفات'
                );
            }
        }

        if (!empty($lines)) {
            $journal_data = array(
                'journal_date' => $end_date,
                'journal_number' => 'CLOSE-EXP-' . date('Y'),
                'description' => 'قيد إغلاق حسابات المصروفات',
                'status' => 'posted',
                'created_by' => $this->user->getId(),
                'lines' => $lines
            );

            return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
        }

        return null;
    }

    /**
     * إنشاء قيد ترحيل صافي الدخل
     */
    private function createIncomeTransferEntry($net_income) {
        if (abs($net_income) < 0.01) {
            return null;
        }

        $lines = array();

        if ($net_income > 0) {
            // ربح: من ملخص الدخل إلى الأرباح المحتجزة
            $lines[] = array(
                'account_id' => $this->getIncomeSummaryAccountId(),
                'debit_amount' => $net_income,
                'credit_amount' => 0,
                'description' => 'ترحيل صافي الربح'
            );

            $lines[] = array(
                'account_id' => $this->getRetainedEarningsAccountId(),
                'debit_amount' => 0,
                'credit_amount' => $net_income,
                'description' => 'ترحيل صافي الربح'
            );
        } else {
            // خسارة: من الأرباح المحتجزة إلى ملخص الدخل
            $loss = abs($net_income);

            $lines[] = array(
                'account_id' => $this->getRetainedEarningsAccountId(),
                'debit_amount' => $loss,
                'credit_amount' => 0,
                'description' => 'ترحيل صافي الخسارة'
            );

            $lines[] = array(
                'account_id' => $this->getIncomeSummaryAccountId(),
                'debit_amount' => 0,
                'credit_amount' => $loss,
                'description' => 'ترحيل صافي الخسارة'
            );
        }

        $journal_data = array(
            'journal_date' => date('Y-12-31'),
            'journal_number' => 'CLOSE-INC-' . date('Y'),
            'description' => 'قيد ترحيل صافي الدخل',
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'lines' => $lines
        );

        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * الحصول على الحسابات المؤقتة (الإيرادات والمصروفات)
     */
    private function getTemporaryAccounts($account_type, $start_date, $end_date) {
        $query = $this->db->query("SELECT a.account_id, a.account_code, ad.name,
                                          COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as balance
                                  FROM " . DB_PREFIX . "accounts a
                                  LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON jel.account_id = a.account_id
                                  LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  WHERE a.account_type = '" . $this->db->escape($account_type) . "'
                                  AND a.is_active = 1
                                  AND (je.journal_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "' OR je.journal_date IS NULL)
                                  AND (je.status = 'posted' OR je.status IS NULL)
                                  GROUP BY a.account_id
                                  HAVING ABS(balance) > 0.01
                                  ORDER BY a.account_code");

        return $query->rows;
    }

    /**
     * التحقق من وجود قيود معلقة
     */
    private function hasPendingEntries($start_date, $end_date) {
        $query = $this->db->query("SELECT COUNT(*) as count
                                  FROM " . DB_PREFIX . "journal_entry
                                  WHERE journal_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "'
                                  AND status = 'pending'");

        return $query->row['count'] > 0;
    }

    /**
     * التحقق من إمكانية إغلاق الفترة
     */
    private function canClosePeriod($start_date, $end_date) {
        // التحقق من عدم وجود قيود معلقة
        if ($this->hasPendingEntries($start_date, $end_date)) {
            return false;
        }

        // التحقق من توازن القيود
        if (!$this->validatePeriodBalance($start_date, $end_date)) {
            return false;
        }

        // التحقق من عدم وجود فترة متداخلة
        if (!$this->validatePeriodDates($start_date, $end_date)) {
            return false;
        }

        return true;
    }

    /**
     * حذف قيود الإغلاق
     */
    private function deleteClosingEntries($period_id) {
        // الحصول على قيود الإغلاق
        $query = $this->db->query("SELECT journal_id FROM " . DB_PREFIX . "journal_entry
                                  WHERE period_id = '" . (int)$period_id . "'
                                  AND reference_type = 'period_closing'");

        foreach ($query->rows as $row) {
            // حذف تفاصيل القيد
            $this->db->query("DELETE FROM " . DB_PREFIX . "journal_entry_line WHERE journal_id = '" . (int)$row['journal_id'] . "'");

            // حذف القيد
            $this->db->query("DELETE FROM " . DB_PREFIX . "journal_entry WHERE journal_id = '" . (int)$row['journal_id'] . "'");
        }
    }

    /**
     * تحديث أرصدة الحسابات
     */
    private function updateAccountBalances($start_date, $end_date) {
        // تحديث أرصدة جميع الحسابات المتأثرة
        $query = $this->db->query("SELECT DISTINCT jel.account_id
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  WHERE je.journal_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "'
                                  AND je.status = 'posted'");

        foreach ($query->rows as $row) {
            $this->updateSingleAccountBalance($row['account_id']);
        }
    }

    /**
     * إعادة حساب أرصدة الحسابات
     */
    private function recalculateAccountBalances($start_date, $end_date) {
        // إعادة حساب أرصدة جميع الحسابات
        $this->updateAccountBalances($start_date, $end_date);
    }

    /**
     * تحديث رصيد حساب واحد
     */
    private function updateSingleAccountBalance($account_id) {
        $query = $this->db->query("SELECT
                                    a.opening_balance,
                                    COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as movement
                                  FROM " . DB_PREFIX . "accounts a
                                  LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON jel.account_id = a.account_id
                                  LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  WHERE a.account_id = '" . (int)$account_id . "'
                                  AND (je.status = 'posted' OR je.status IS NULL)
                                  GROUP BY a.account_id, a.opening_balance");

        if ($query->num_rows) {
            $opening_balance = (float)$query->row['opening_balance'];
            $movement = (float)$query->row['movement'];
            $current_balance = $opening_balance + $movement;

            $this->db->query("UPDATE " . DB_PREFIX . "accounts
                             SET current_balance = '" . (float)$current_balance . "'
                             WHERE account_id = '" . (int)$account_id . "'");
        }
    }

    /**
     * الحصول على حساب ملخص الدخل
     */
    private function getIncomeSummaryAccountId() {
        // البحث عن حساب ملخص الدخل
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts
                                  WHERE account_type = 'equity'
                                  AND account_code LIKE '%390%'
                                  AND is_active = 1
                                  LIMIT 1");

        if ($query->num_rows) {
            return $query->row['account_id'];
        }

        // إنشاء حساب ملخص الدخل إذا لم يوجد
        return $this->createIncomeSummaryAccount();
    }

    /**
     * الحصول على حساب الأرباح المحتجزة
     */
    private function getRetainedEarningsAccountId() {
        // البحث عن حساب الأرباح المحتجزة
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts
                                  WHERE account_type = 'equity'
                                  AND account_code LIKE '%310%'
                                  AND is_active = 1
                                  LIMIT 1");

        if ($query->num_rows) {
            return $query->row['account_id'];
        }

        // إنشاء حساب الأرباح المحتجزة إذا لم يوجد
        return $this->createRetainedEarningsAccount();
    }

    /**
     * إنشاء حساب ملخص الدخل
     */
    private function createIncomeSummaryAccount() {
        $this->load->model('accounts/chartaccount');

        $account_data = array(
            'account_code' => '390',
            'account_type' => 'equity',
            'status' => 1,
            'allow_posting' => 1,
            'account_description' => array(
                $this->config->get('config_language_id') => array(
                    'name' => 'ملخص الدخل',
                    'description' => 'حساب مؤقت لتجميع الإيرادات والمصروفات'
                )
            )
        );

        return $this->model_accounts_chartaccount->addAccount($account_data);
    }

    /**
     * إنشاء حساب الأرباح المحتجزة
     */
    private function createRetainedEarningsAccount() {
        $this->load->model('accounts/chartaccount');

        $account_data = array(
            'account_code' => '310',
            'account_type' => 'equity',
            'status' => 1,
            'allow_posting' => 1,
            'account_description' => array(
                $this->config->get('config_language_id') => array(
                    'name' => 'الأرباح المحتجزة',
                    'description' => 'أرباح الشركة المحتجزة من السنوات السابقة'
                )
            )
        );

        return $this->model_accounts_chartaccount->addAccount($account_data);
    }
}
