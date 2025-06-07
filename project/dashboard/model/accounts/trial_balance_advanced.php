<?php
/**
 * نموذج ميزان المراجعة المتقدم والمتكامل
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelAccountsTrialBalanceAdvanced extends Model {

    /**
     * إنشاء ميزان المراجعة المتقدم
     */
    public function generateTrialBalance($filter_data) {
        $accounts = $this->getAccountsData($filter_data);
        $totals = $this->calculateTotals($accounts);

        // تجميع حسب النوع إذا كان مطلوباً
        if ($filter_data['group_by_type']) {
            $accounts = $this->groupAccountsByType($accounts);
        }

        // فلترة الأرصدة الصفرية إذا كان مطلوباً
        if (!$filter_data['include_zero_balances']) {
            $accounts = $this->filterZeroBalances($accounts);
        }

        return array(
            'accounts' => $accounts,
            'totals' => $totals,
            'filter_data' => $filter_data,
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->user->getId(),
            'currency' => $filter_data['currency']
        );
    }

    /**
     * الحصول على بيانات الحسابات
     */
    private function getAccountsData($filter_data) {
        $sql = "
            SELECT
                a.account_id,
                a.account_code,
                ad.name as account_name,
                a.account_type,
                a.account_nature,
                a.parent_id,

                -- الرصيد الافتتاحي
                COALESCE(ob.opening_debit, 0) as opening_balance_debit,
                COALESCE(ob.opening_credit, 0) as opening_balance_credit,

                -- حركة الفترة
                COALESCE(SUM(CASE
                    WHEN jel.debit_amount > 0 AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
                    AND '" . $this->db->escape($filter_data['date_end']) . "'
                    THEN jel.debit_amount ELSE 0 END), 0) as period_debit,

                COALESCE(SUM(CASE
                    WHEN jel.credit_amount > 0 AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
                    AND '" . $this->db->escape($filter_data['date_end']) . "'
                    THEN jel.credit_amount ELSE 0 END), 0) as period_credit,

                -- الرصيد الختامي
                (COALESCE(ob.opening_debit, 0) +
                 COALESCE(SUM(CASE
                    WHEN jel.debit_amount > 0 AND je.journal_date <= '" . $this->db->escape($filter_data['date_end']) . "'
                    THEN jel.debit_amount ELSE 0 END), 0)) as total_debit,

                (COALESCE(ob.opening_credit, 0) +
                 COALESCE(SUM(CASE
                    WHEN jel.credit_amount > 0 AND je.journal_date <= '" . $this->db->escape($filter_data['date_end']) . "'
                    THEN jel.credit_amount ELSE 0 END), 0)) as total_credit

            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON a.account_id = jel.account_id
            LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id AND je.status = 'posted'
            LEFT JOIN (
                SELECT
                    account_id,
                    SUM(CASE WHEN debit_amount > 0 THEN debit_amount ELSE 0 END) as opening_debit,
                    SUM(CASE WHEN credit_amount > 0 THEN credit_amount ELSE 0 END) as opening_credit
                FROM " . DB_PREFIX . "journal_entry_line jel2
                JOIN " . DB_PREFIX . "journal_entry je2 ON jel2.journal_id = je2.journal_id
                WHERE je2.status = 'posted' AND je2.journal_date < '" . $this->db->escape($filter_data['date_start']) . "'
                GROUP BY account_id
            ) ob ON a.account_id = ob.account_id

            WHERE a.is_active = 1 AND a.allow_posting = 1
        ";

        // فلترة حسب نطاق الحسابات
        if (!empty($filter_data['account_start']) && !empty($filter_data['account_end'])) {
            $sql .= " AND a.account_code BETWEEN '" . $this->db->escape($filter_data['account_start']) . "'
                     AND '" . $this->db->escape($filter_data['account_end']) . "'";
        }

        // فلترة حسب مركز التكلفة
        if (!empty($filter_data['cost_center_id'])) {
            $sql .= " AND jel.cost_center_id = '" . (int)$filter_data['cost_center_id'] . "'";
        }

        // فلترة حسب المشروع
        if (!empty($filter_data['project_id'])) {
            $sql .= " AND jel.project_id = '" . (int)$filter_data['project_id'] . "'";
        }

        // فلترة حسب القسم
        if (!empty($filter_data['department_id'])) {
            $sql .= " AND jel.department_id = '" . (int)$filter_data['department_id'] . "'";
        }

        $sql .= " GROUP BY a.account_id, a.account_code, ad.name, a.account_type, a.account_nature, a.parent_id, ob.opening_debit, ob.opening_credit";
        $sql .= " ORDER BY a.account_code";

        $query = $this->db->query($sql);

        $accounts = array();
        foreach ($query->rows as $row) {
            // حساب الرصيد الختامي
            $closing_balance_debit = 0;
            $closing_balance_credit = 0;

            $net_balance = $row['total_debit'] - $row['total_credit'];

            if ($net_balance > 0) {
                $closing_balance_debit = $net_balance;
            } else {
                $closing_balance_credit = abs($net_balance);
            }

            $accounts[] = array(
                'account_id' => $row['account_id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'account_type' => $row['account_type'],
                'account_nature' => $row['account_nature'],
                'parent_id' => $row['parent_id'],
                'opening_balance_debit' => (float)$row['opening_balance_debit'],
                'opening_balance_credit' => (float)$row['opening_balance_credit'],
                'period_debit' => (float)$row['period_debit'],
                'period_credit' => (float)$row['period_credit'],
                'total_debit' => (float)$row['total_debit'],
                'total_credit' => (float)$row['total_credit'],
                'closing_balance_debit' => $closing_balance_debit,
                'closing_balance_credit' => $closing_balance_credit,
                'net_balance' => $net_balance,

                // تنسيق العملة
                'opening_balance_debit_formatted' => $this->currency->format($row['opening_balance_debit'], $filter_data['currency']),
                'opening_balance_credit_formatted' => $this->currency->format($row['opening_balance_credit'], $filter_data['currency']),
                'period_debit_formatted' => $this->currency->format($row['period_debit'], $filter_data['currency']),
                'period_credit_formatted' => $this->currency->format($row['period_credit'], $filter_data['currency']),
                'closing_balance_debit_formatted' => $this->currency->format($closing_balance_debit, $filter_data['currency']),
                'closing_balance_credit_formatted' => $this->currency->format($closing_balance_credit, $filter_data['currency']),
                'net_balance_formatted' => $this->currency->format($net_balance, $filter_data['currency'])
            );
        }

        return $accounts;
    }

    /**
     * حساب الإجماليات
     */
    private function calculateTotals($accounts) {
        $totals = array(
            'opening_balance_debit' => 0,
            'opening_balance_credit' => 0,
            'period_debit' => 0,
            'period_credit' => 0,
            'closing_balance_debit' => 0,
            'closing_balance_credit' => 0
        );

        foreach ($accounts as $account) {
            $totals['opening_balance_debit'] += $account['opening_balance_debit'];
            $totals['opening_balance_credit'] += $account['opening_balance_credit'];
            $totals['period_debit'] += $account['period_debit'];
            $totals['period_credit'] += $account['period_credit'];
            $totals['closing_balance_debit'] += $account['closing_balance_debit'];
            $totals['closing_balance_credit'] += $account['closing_balance_credit'];
        }

        return $totals;
    }

    /**
     * تجميع الحسابات حسب النوع
     */
    private function groupAccountsByType($accounts) {
        $grouped = array();
        $types = array('asset', 'liability', 'equity', 'revenue', 'expense');

        foreach ($types as $type) {
            $type_accounts = array_filter($accounts, function($account) use ($type) {
                return $account['account_type'] === $type;
            });

            if (!empty($type_accounts)) {
                $grouped[$type] = array(
                    'type_name' => $this->getAccountTypeName($type),
                    'accounts' => array_values($type_accounts),
                    'totals' => $this->calculateTotals($type_accounts)
                );
            }
        }

        return $grouped;
    }

    /**
     * فلترة الأرصدة الصفرية
     */
    private function filterZeroBalances($accounts) {
        return array_filter($accounts, function($account) {
            return abs($account['net_balance']) > 0.01;
        });
    }

    /**
     * الحصول على اسم نوع الحساب
     */
    private function getAccountTypeName($type) {
        $types = array(
            'asset' => 'الأصول',
            'liability' => 'الخصوم',
            'equity' => 'حقوق الملكية',
            'revenue' => 'الإيرادات',
            'expense' => 'المصروفات'
        );

        return $types[$type] ?? $type;
    }

    /**
     * مقارنة ميزان المراجعة بين فترتين
     */
    public function compareTrialBalances($period1, $period2) {
        $trial_balance_1 = $this->generateTrialBalance($period1);
        $trial_balance_2 = $this->generateTrialBalance($period2);

        $comparison = array();
        $accounts_1 = array_column($trial_balance_1['accounts'], null, 'account_id');
        $accounts_2 = array_column($trial_balance_2['accounts'], null, 'account_id');

        $all_account_ids = array_unique(array_merge(array_keys($accounts_1), array_keys($accounts_2)));

        foreach ($all_account_ids as $account_id) {
            $account_1 = $accounts_1[$account_id] ?? null;
            $account_2 = $accounts_2[$account_id] ?? null;

            $comparison[] = array(
                'account_id' => $account_id,
                'account_code' => $account_1['account_code'] ?? $account_2['account_code'],
                'account_name' => $account_1['account_name'] ?? $account_2['account_name'],
                'period_1_balance' => $account_1 ? $account_1['net_balance'] : 0,
                'period_2_balance' => $account_2 ? $account_2['net_balance'] : 0,
                'variance' => ($account_2 ? $account_2['net_balance'] : 0) - ($account_1 ? $account_1['net_balance'] : 0),
                'variance_percentage' => $account_1 && $account_1['net_balance'] != 0 ?
                    (($account_2 ? $account_2['net_balance'] : 0) - $account_1['net_balance']) / abs($account_1['net_balance']) * 100 : 0
            );
        }

        return array(
            'period_1' => $period1,
            'period_2' => $period2,
            'comparison' => $comparison,
            'period_1_totals' => $trial_balance_1['totals'],
            'period_2_totals' => $trial_balance_2['totals']
        );
    }

    /**
     * الحصول على تفاصيل الحساب (Drill Down)
     */
    public function getAccountDrillDown($account_id, $date_start, $date_end) {
        $sql = "
            SELECT
                je.journal_id,
                je.journal_number,
                je.journal_date,
                je.description as journal_description,
                jel.description as line_description,
                jel.debit_amount,
                jel.credit_amount,
                je.reference_type,
                je.reference_number,
                u.username as created_by
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            LEFT JOIN " . DB_PREFIX . "user u ON je.created_by = u.user_id
            WHERE jel.account_id = '" . (int)$account_id . "'
            AND je.status = 'posted'
            AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "'
            AND '" . $this->db->escape($date_end) . "'
            ORDER BY je.journal_date DESC, je.journal_number DESC
        ";

        $query = $this->db->query($sql);

        $movements = array();
        $running_balance = 0;

        foreach ($query->rows as $row) {
            $amount = $row['debit_amount'] - $row['credit_amount'];
            $running_balance += $amount;

            $movements[] = array(
                'journal_id' => $row['journal_id'],
                'journal_number' => $row['journal_number'],
                'journal_date' => $row['journal_date'],
                'journal_description' => $row['journal_description'],
                'line_description' => $row['line_description'],
                'debit_amount' => (float)$row['debit_amount'],
                'credit_amount' => (float)$row['credit_amount'],
                'amount' => $amount,
                'running_balance' => $running_balance,
                'reference_type' => $row['reference_type'],
                'reference_number' => $row['reference_number'],
                'created_by' => $row['created_by'],
                'debit_amount_formatted' => $this->currency->format($row['debit_amount'], $this->config->get('config_currency')),
                'credit_amount_formatted' => $this->currency->format($row['credit_amount'], $this->config->get('config_currency')),
                'amount_formatted' => $this->currency->format($amount, $this->config->get('config_currency')),
                'running_balance_formatted' => $this->currency->format($running_balance, $this->config->get('config_currency'))
            );
        }

        return $movements;
    }

    /**
     * الحصول على حركات الحساب
     */
    public function getAccountMovements($account_id, $date_start = '', $date_end = '') {
        $where_conditions = array();
        $where_conditions[] = "jel.account_id = '" . (int)$account_id . "'";
        $where_conditions[] = "je.status = 'posted'";

        if (!empty($date_start)) {
            $where_conditions[] = "je.journal_date >= '" . $this->db->escape($date_start) . "'";
        }

        if (!empty($date_end)) {
            $where_conditions[] = "je.journal_date <= '" . $this->db->escape($date_end) . "'";
        }

        $sql = "
            SELECT
                je.journal_id,
                je.journal_number,
                je.journal_date,
                je.description,
                jel.debit_amount,
                jel.credit_amount,
                je.reference_type,
                je.reference_number
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE " . implode(' AND ', $where_conditions) . "
            ORDER BY je.journal_date ASC, je.journal_number ASC
        ";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * التحقق من تكامل ميزان المراجعة
     */
    public function validateIntegrity() {
        $errors = array();
        $warnings = array();

        // التحقق من توازن جميع القيود
        $unbalanced_journals = $this->getUnbalancedJournals();
        if (!empty($unbalanced_journals)) {
            $errors[] = 'توجد قيود غير متوازنة: ' . implode(', ', $unbalanced_journals);
        }

        // التحقق من الحسابات بدون أرصدة افتتاحية
        $accounts_without_opening = $this->getAccountsWithoutOpeningBalances();
        if (!empty($accounts_without_opening)) {
            $warnings[] = 'حسابات بدون أرصدة افتتاحية: ' . count($accounts_without_opening);
        }

        // التحقق من الحسابات المعطلة مع حركات
        $disabled_accounts_with_movements = $this->getDisabledAccountsWithMovements();
        if (!empty($disabled_accounts_with_movements)) {
            $warnings[] = 'حسابات معطلة لها حركات: ' . count($disabled_accounts_with_movements);
        }

        // التحقق من تطابق الأرصدة
        $balance_discrepancies = $this->getBalanceDiscrepancies();
        if (!empty($balance_discrepancies)) {
            $errors[] = 'تضارب في الأرصدة: ' . count($balance_discrepancies);
        }

        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'checks_performed' => array(
                'journal_balance_check' => empty($unbalanced_journals),
                'opening_balance_check' => empty($accounts_without_opening),
                'disabled_accounts_check' => empty($disabled_accounts_with_movements),
                'balance_integrity_check' => empty($balance_discrepancies)
            )
        );
    }

    /**
     * تحليل ميزان المراجعة
     */
    public function analyzeTrialBalance($trial_balance_data) {
        $analysis = array();

        // تحليل الأصول
        $assets = array_filter($trial_balance_data['accounts'], function($account) {
            return $account['account_type'] === 'asset';
        });
        $total_assets = array_sum(array_column($assets, 'closing_balance_debit'));

        // تحليل الخصوم
        $liabilities = array_filter($trial_balance_data['accounts'], function($account) {
            return $account['account_type'] === 'liability';
        });
        $total_liabilities = array_sum(array_column($liabilities, 'closing_balance_credit'));

        // تحليل حقوق الملكية
        $equity = array_filter($trial_balance_data['accounts'], function($account) {
            return $account['account_type'] === 'equity';
        });
        $total_equity = array_sum(array_column($equity, 'closing_balance_credit'));

        // تحليل الإيرادات
        $revenues = array_filter($trial_balance_data['accounts'], function($account) {
            return $account['account_type'] === 'revenue';
        });
        $total_revenues = array_sum(array_column($revenues, 'closing_balance_credit'));

        // تحليل المصروفات
        $expenses = array_filter($trial_balance_data['accounts'], function($account) {
            return $account['account_type'] === 'expense';
        });
        $total_expenses = array_sum(array_column($expenses, 'closing_balance_debit'));

        // حساب النسب المالية
        $analysis['financial_ratios'] = array(
            'debt_to_equity' => $total_equity > 0 ? $total_liabilities / $total_equity : 0,
            'asset_to_liability' => $total_liabilities > 0 ? $total_assets / $total_liabilities : 0,
            'expense_to_revenue' => $total_revenues > 0 ? $total_expenses / $total_revenues : 0
        );

        // تحليل التوزيع
        $analysis['distribution'] = array(
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'total_equity' => $total_equity,
            'total_revenues' => $total_revenues,
            'total_expenses' => $total_expenses,
            'net_income' => $total_revenues - $total_expenses
        );

        // أكبر الحسابات
        $all_accounts = $trial_balance_data['accounts'];
        usort($all_accounts, function($a, $b) {
            return abs($b['net_balance']) <=> abs($a['net_balance']);
        });

        $analysis['top_accounts'] = array_slice($all_accounts, 0, 10);

        // الحسابات بدون حركة
        $inactive_accounts = array_filter($trial_balance_data['accounts'], function($account) {
            return $account['period_debit'] == 0 && $account['period_credit'] == 0;
        });

        $analysis['inactive_accounts_count'] = count($inactive_accounts);

        return $analysis;
    }

    /**
     * الحصول على القيود غير المتوازنة
     */
    private function getUnbalancedJournals() {
        $sql = "
            SELECT journal_number
            FROM " . DB_PREFIX . "journal_entry
            WHERE ABS(total_debit - total_credit) > 0.01
            AND status = 'posted'
        ";

        $query = $this->db->query($sql);
        return array_column($query->rows, 'journal_number');
    }

    /**
     * الحصول على الحسابات بدون أرصدة افتتاحية
     */
    private function getAccountsWithoutOpeningBalances() {
        $sql = "
            SELECT a.account_code
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "opening_balance ob ON a.account_id = ob.account_id
            WHERE a.is_active = 1
            AND a.allow_posting = 1
            AND ob.account_id IS NULL
        ";

        $query = $this->db->query($sql);
        return array_column($query->rows, 'account_code');
    }

    /**
     * الحصول على الحسابات المعطلة مع حركات
     */
    private function getDisabledAccountsWithMovements() {
        $sql = "
            SELECT DISTINCT a.account_code
            FROM " . DB_PREFIX . "accounts a
            JOIN " . DB_PREFIX . "journal_entry_line jel ON a.account_id = jel.account_id
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE a.is_active = 0
            AND je.status = 'posted'
        ";

        $query = $this->db->query($sql);
        return array_column($query->rows, 'account_code');
    }

    /**
     * الحصول على تضارب الأرصدة
     */
    private function getBalanceDiscrepancies() {
        // هذه دالة معقدة تتطلب مقارنة الأرصدة المحسوبة مع الأرصدة المخزنة
        // يمكن تطويرها حسب متطلبات النظام المحددة
        return array();
    }

    /**
     * الحصول على ملخص ميزان المراجعة
     */
    public function getTrialBalanceSummary($filter_data) {
        $trial_balance = $this->generateTrialBalance($filter_data);

        return array(
            'total_accounts' => count($trial_balance['accounts']),
            'total_debit' => $trial_balance['totals']['closing_balance_debit'],
            'total_credit' => $trial_balance['totals']['closing_balance_credit'],
            'is_balanced' => abs($trial_balance['totals']['closing_balance_debit'] - $trial_balance['totals']['closing_balance_credit']) < 0.01,
            'period_start' => $filter_data['date_start'],
            'period_end' => $filter_data['date_end'],
            'generated_at' => date('Y-m-d H:i:s')
        );
    }

    /**
     * حفظ ميزان المراجعة كنموذج
     */
    public function saveTrialBalanceSnapshot($trial_balance_data, $name, $description = '') {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "trial_balance_snapshots SET
            name = '" . $this->db->escape($name) . "',
            description = '" . $this->db->escape($description) . "',
            data = '" . $this->db->escape(json_encode($trial_balance_data)) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()
        ");

        return $this->db->getLastId();
    }

    /**
     * الحصول على لقطات ميزان المراجعة المحفوظة
     */
    public function getTrialBalanceSnapshots() {
        $query = $this->db->query("
            SELECT
                snapshot_id,
                name,
                description,
                created_at,
                u.username as created_by_name
            FROM " . DB_PREFIX . "trial_balance_snapshots tbs
            LEFT JOIN " . DB_PREFIX . "user u ON tbs.created_by = u.user_id
            ORDER BY created_at DESC
        ");

        return $query->rows;
    }
}
