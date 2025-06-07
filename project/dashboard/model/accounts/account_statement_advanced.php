<?php
/**
 * نموذج كشف الحساب المتقدم والمتكامل
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelAccountsAccountStatementAdvanced extends Model {

    /**
     * إنشاء كشف الحساب المتقدم
     */
    public function generateAccountStatement($filter_data) {
        // الحصول على معلومات الحساب
        $account = $this->getAccountInfo($filter_data['account_id']);

        if (!$account) {
            throw new Exception('الحساب غير موجود');
        }

        // الحصول على الرصيد الافتتاحي
        $opening_balance = $this->getOpeningBalance($filter_data['account_id'], $filter_data['date_start']);

        // الحصول على المعاملات خلال الفترة
        $transactions = $this->getAccountTransactions($filter_data);

        // حساب الرصيد الجاري لكل معاملة
        $running_balance = $opening_balance;
        $total_debit = 0;
        $total_credit = 0;

        foreach ($transactions as &$transaction) {
            $amount = $transaction['debit_amount'] - $transaction['credit_amount'];
            $running_balance += $amount;
            $transaction['running_balance'] = $running_balance;
            $transaction['running_balance_formatted'] = $this->currency->format($running_balance, $filter_data['currency']);

            $total_debit += $transaction['debit_amount'];
            $total_credit += $transaction['credit_amount'];
        }

        // حساب الرصيد الختامي
        $closing_balance = $running_balance;

        return array(
            'account' => $account,
            'opening_balance' => $opening_balance,
            'closing_balance' => $closing_balance,
            'transactions' => $transactions,
            'summary' => array(
                'total_debit' => $total_debit,
                'total_credit' => $total_credit,
                'net_movement' => $total_debit - $total_credit,
                'transaction_count' => count($transactions),
                'opening_balance_formatted' => $this->currency->format($opening_balance, $filter_data['currency']),
                'closing_balance_formatted' => $this->currency->format($closing_balance, $filter_data['currency']),
                'total_debit_formatted' => $this->currency->format($total_debit, $filter_data['currency']),
                'total_credit_formatted' => $this->currency->format($total_credit, $filter_data['currency']),
                'net_movement_formatted' => $this->currency->format($total_debit - $total_credit, $filter_data['currency'])
            ),
            'filter_data' => $filter_data,
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->user->getId()
        );
    }

    /**
     * الحصول على معلومات الحساب
     */
    private function getAccountInfo($account_id) {
        $sql = "
            SELECT
                a.account_id,
                a.account_code,
                ad.name as account_name,
                a.account_type,
                a.account_nature,
                a.parent_id,
                a.is_active,
                a.allow_posting,
                parent.account_code as parent_code,
                parent_desc.name as parent_name
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "accounts parent ON a.parent_id = parent.account_id
            LEFT JOIN " . DB_PREFIX . "account_description parent_desc ON (parent.account_id = parent_desc.account_id AND parent_desc.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE a.account_id = '" . (int)$account_id . "'
        ";

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            return $query->row;
        }

        return false;
    }

    /**
     * الحصول على الرصيد الافتتاحي
     */
    private function getOpeningBalance($account_id, $date_start) {
        $sql = "
            SELECT
                COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as opening_balance
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE jel.account_id = '" . (int)$account_id . "'
            AND je.status = 'posted'
            AND je.journal_date < '" . $this->db->escape($date_start) . "'
        ";

        $query = $this->db->query($sql);

        return (float)$query->row['opening_balance'];
    }

    /**
     * الحصول على معاملات الحساب خلال الفترة
     */
    private function getAccountTransactions($filter_data) {
        $sql = "
            SELECT
                je.journal_id,
                je.journal_number,
                je.journal_date as transaction_date,
                je.description as journal_description,
                jel.description as line_description,
                COALESCE(jel.description, je.description) as description,
                jel.debit_amount,
                jel.credit_amount,
                je.reference_type,
                je.reference_number,
                je.created_by,
                u.username as created_by_name,
                jel.cost_center_id,
                cc.name as cost_center_name,
                jel.project_id,
                p.name as project_name,
                jel.department_id,
                d.name as department_name
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            LEFT JOIN " . DB_PREFIX . "user u ON je.created_by = u.user_id
            LEFT JOIN " . DB_PREFIX . "cost_center cc ON jel.cost_center_id = cc.cost_center_id
            LEFT JOIN " . DB_PREFIX . "project p ON jel.project_id = p.project_id
            LEFT JOIN " . DB_PREFIX . "department d ON jel.department_id = d.department_id
            WHERE jel.account_id = '" . (int)$filter_data['account_id'] . "'
            AND je.status = 'posted'
            AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
        ";

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

        $sql .= " ORDER BY je.journal_date ASC, je.journal_number ASC, jel.line_id ASC";

        $query = $this->db->query($sql);

        $transactions = array();
        foreach ($query->rows as $row) {
            $transactions[] = array(
                'journal_id' => $row['journal_id'],
                'journal_number' => $row['journal_number'],
                'transaction_date' => $row['transaction_date'],
                'transaction_date_formatted' => date($this->language->get('date_format_short'), strtotime($row['transaction_date'])),
                'description' => $row['description'],
                'journal_description' => $row['journal_description'],
                'line_description' => $row['line_description'],
                'debit_amount' => (float)$row['debit_amount'],
                'credit_amount' => (float)$row['credit_amount'],
                'debit_amount_formatted' => $this->currency->format($row['debit_amount'], $filter_data['currency']),
                'credit_amount_formatted' => $this->currency->format($row['credit_amount'], $filter_data['currency']),
                'reference_type' => $row['reference_type'],
                'reference_number' => $row['reference_number'],
                'created_by' => $row['created_by'],
                'created_by_name' => $row['created_by_name'],
                'cost_center_id' => $row['cost_center_id'],
                'cost_center_name' => $row['cost_center_name'],
                'project_id' => $row['project_id'],
                'project_name' => $row['project_name'],
                'department_id' => $row['department_id'],
                'department_name' => $row['department_name']
            );
        }

        return $transactions;
    }

    /**
     * الحصول على الرصيد الحالي للحساب
     */
    public function getCurrentBalance($account_id) {
        $sql = "
            SELECT
                COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as current_balance
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE jel.account_id = '" . (int)$account_id . "'
            AND je.status = 'posted'
        ";

        $query = $this->db->query($sql);

        return (float)$query->row['current_balance'];
    }

    /**
     * الحصول على آخر معاملة للحساب
     */
    public function getLastTransaction($account_id) {
        $sql = "
            SELECT
                je.journal_date as transaction_date,
                je.journal_number,
                je.description,
                jel.debit_amount,
                jel.credit_amount
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE jel.account_id = '" . (int)$account_id . "'
            AND je.status = 'posted'
            ORDER BY je.journal_date DESC, je.journal_id DESC
            LIMIT 1
        ";

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            return $query->row;
        }

        return array();
    }

    /**
     * الحصول على ملخص الحساب
     */
    public function getAccountSummary($account_id, $date_start = '', $date_end = '') {
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
                COUNT(*) as transaction_count,
                SUM(jel.debit_amount) as total_debit,
                SUM(jel.credit_amount) as total_credit,
                SUM(jel.debit_amount - jel.credit_amount) as net_movement,
                MIN(je.journal_date) as first_transaction_date,
                MAX(je.journal_date) as last_transaction_date,
                AVG(ABS(jel.debit_amount - jel.credit_amount)) as average_transaction_amount
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE " . implode(' AND ', $where_conditions) . "
        ";

        $query = $this->db->query($sql);

        $summary = $query->row;

        // إضافة التنسيق
        $currency = $this->config->get('config_currency');
        $summary['total_debit_formatted'] = $this->currency->format($summary['total_debit'], $currency);
        $summary['total_credit_formatted'] = $this->currency->format($summary['total_credit'], $currency);
        $summary['net_movement_formatted'] = $this->currency->format($summary['net_movement'], $currency);
        $summary['average_transaction_amount_formatted'] = $this->currency->format($summary['average_transaction_amount'], $currency);

        return $summary;
    }

    /**
     * الحصول على تفاصيل المعاملة
     */
    public function getTransactionDetails($journal_id) {
        $sql = "
            SELECT
                je.*,
                u.username as created_by_name,
                u2.username as modified_by_name
            FROM " . DB_PREFIX . "journal_entry je
            LEFT JOIN " . DB_PREFIX . "user u ON je.created_by = u.user_id
            LEFT JOIN " . DB_PREFIX . "user u2 ON je.modified_by = u2.user_id
            WHERE je.journal_id = '" . (int)$journal_id . "'
        ";

        $query = $this->db->query($sql);

        if (!$query->num_rows) {
            return array();
        }

        $journal = $query->row;

        // الحصول على بنود القيد
        $sql = "
            SELECT
                jel.*,
                a.account_code,
                ad.name as account_name,
                cc.name as cost_center_name,
                p.name as project_name,
                d.name as department_name
            FROM " . DB_PREFIX . "journal_entry_line jel
            LEFT JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "cost_center cc ON jel.cost_center_id = cc.cost_center_id
            LEFT JOIN " . DB_PREFIX . "project p ON jel.project_id = p.project_id
            LEFT JOIN " . DB_PREFIX . "department d ON jel.department_id = d.department_id
            WHERE jel.journal_id = '" . (int)$journal_id . "'
            ORDER BY jel.line_id
        ";

        $query = $this->db->query($sql);
        $journal['lines'] = $query->rows;

        return $journal;
    }

    /**
     * مقارنة كشوف الحساب بين فترتين
     */
    public function compareStatements($account_id, $period1, $period2) {
        $filter_data_1 = array_merge($period1, ['account_id' => $account_id]);
        $filter_data_2 = array_merge($period2, ['account_id' => $account_id]);

        $statement_1 = $this->generateAccountStatement($filter_data_1);
        $statement_2 = $this->generateAccountStatement($filter_data_2);

        $comparison = array(
            'account' => $statement_1['account'],
            'period_1' => array(
                'date_start' => $period1['date_start'],
                'date_end' => $period1['date_end'],
                'opening_balance' => $statement_1['opening_balance'],
                'closing_balance' => $statement_1['closing_balance'],
                'total_debit' => $statement_1['summary']['total_debit'],
                'total_credit' => $statement_1['summary']['total_credit'],
                'net_movement' => $statement_1['summary']['net_movement'],
                'transaction_count' => $statement_1['summary']['transaction_count']
            ),
            'period_2' => array(
                'date_start' => $period2['date_start'],
                'date_end' => $period2['date_end'],
                'opening_balance' => $statement_2['opening_balance'],
                'closing_balance' => $statement_2['closing_balance'],
                'total_debit' => $statement_2['summary']['total_debit'],
                'total_credit' => $statement_2['summary']['total_credit'],
                'net_movement' => $statement_2['summary']['net_movement'],
                'transaction_count' => $statement_2['summary']['transaction_count']
            )
        );

        // حساب الفروقات
        $comparison['variance'] = array(
            'opening_balance' => $statement_2['opening_balance'] - $statement_1['opening_balance'],
            'closing_balance' => $statement_2['closing_balance'] - $statement_1['closing_balance'],
            'total_debit' => $statement_2['summary']['total_debit'] - $statement_1['summary']['total_debit'],
            'total_credit' => $statement_2['summary']['total_credit'] - $statement_1['summary']['total_credit'],
            'net_movement' => $statement_2['summary']['net_movement'] - $statement_1['summary']['net_movement'],
            'transaction_count' => $statement_2['summary']['transaction_count'] - $statement_1['summary']['transaction_count']
        );

        // حساب النسب المئوية
        $comparison['percentage_change'] = array();
        foreach ($comparison['variance'] as $key => $variance) {
            $base_value = $comparison['period_1'][$key];
            if ($base_value != 0) {
                $comparison['percentage_change'][$key] = ($variance / abs($base_value)) * 100;
            } else {
                $comparison['percentage_change'][$key] = $variance != 0 ? 100 : 0;
            }
        }

        return $comparison;
    }

    /**
     * تحليل كشف الحساب
     */
    public function analyzeAccountStatement($statement_data, $filter_data) {
        $analysis = array();
        $transactions = $statement_data['transactions'];

        // تحليل الاتجاهات
        $analysis['trends'] = $this->analyzeTrends($transactions);

        // تحليل التوزيع الشهري
        $analysis['monthly_distribution'] = $this->analyzeMonthlyDistribution($transactions);

        // تحليل أكبر المعاملات
        $analysis['largest_transactions'] = $this->getLargestTransactions($transactions, 10);

        // تحليل تكرار المعاملات
        $analysis['transaction_frequency'] = $this->analyzeTransactionFrequency($transactions);

        // تحليل الأرصدة
        $analysis['balance_analysis'] = array(
            'opening_balance' => $statement_data['opening_balance'],
            'closing_balance' => $statement_data['closing_balance'],
            'highest_balance' => $this->getHighestBalance($transactions, $statement_data['opening_balance']),
            'lowest_balance' => $this->getLowestBalance($transactions, $statement_data['opening_balance']),
            'average_balance' => $this->getAverageBalance($transactions, $statement_data['opening_balance'])
        );

        // تحليل المخاطر
        $analysis['risk_analysis'] = $this->analyzeRisks($statement_data, $filter_data);

        return $analysis;
    }

    /**
     * تحليل الاتجاهات
     */
    private function analyzeTrends($transactions) {
        if (empty($transactions)) {
            return array();
        }

        $monthly_totals = array();

        foreach ($transactions as $transaction) {
            $month = date('Y-m', strtotime($transaction['transaction_date']));

            if (!isset($monthly_totals[$month])) {
                $monthly_totals[$month] = array(
                    'debit' => 0,
                    'credit' => 0,
                    'net' => 0,
                    'count' => 0
                );
            }

            $monthly_totals[$month]['debit'] += $transaction['debit_amount'];
            $monthly_totals[$month]['credit'] += $transaction['credit_amount'];
            $monthly_totals[$month]['net'] += ($transaction['debit_amount'] - $transaction['credit_amount']);
            $monthly_totals[$month]['count']++;
        }

        // حساب الاتجاه العام
        $months = array_keys($monthly_totals);
        sort($months);

        $trend = 'stable';
        if (count($months) >= 2) {
            $first_month = $monthly_totals[$months[0]]['net'];
            $last_month = $monthly_totals[$months[count($months) - 1]]['net'];

            if ($last_month > $first_month * 1.1) {
                $trend = 'increasing';
            } elseif ($last_month < $first_month * 0.9) {
                $trend = 'decreasing';
            }
        }

        return array(
            'monthly_totals' => $monthly_totals,
            'overall_trend' => $trend
        );
    }

    /**
     * تحليل التوزيع الشهري
     */
    private function analyzeMonthlyDistribution($transactions) {
        $distribution = array();

        foreach ($transactions as $transaction) {
            $month = date('F Y', strtotime($transaction['transaction_date']));

            if (!isset($distribution[$month])) {
                $distribution[$month] = array(
                    'count' => 0,
                    'total_debit' => 0,
                    'total_credit' => 0,
                    'net_amount' => 0
                );
            }

            $distribution[$month]['count']++;
            $distribution[$month]['total_debit'] += $transaction['debit_amount'];
            $distribution[$month]['total_credit'] += $transaction['credit_amount'];
            $distribution[$month]['net_amount'] += ($transaction['debit_amount'] - $transaction['credit_amount']);
        }

        return $distribution;
    }

    /**
     * الحصول على أكبر المعاملات
     */
    private function getLargestTransactions($transactions, $limit = 10) {
        $sorted_transactions = $transactions;

        usort($sorted_transactions, function($a, $b) {
            $amount_a = abs($a['debit_amount'] - $a['credit_amount']);
            $amount_b = abs($b['debit_amount'] - $b['credit_amount']);
            return $amount_b <=> $amount_a;
        });

        return array_slice($sorted_transactions, 0, $limit);
    }

    /**
     * تحليل تكرار المعاملات
     */
    private function analyzeTransactionFrequency($transactions) {
        $frequency = array(
            'daily_average' => 0,
            'weekly_average' => 0,
            'monthly_average' => 0,
            'busiest_day' => '',
            'quietest_day' => ''
        );

        if (empty($transactions)) {
            return $frequency;
        }

        // تحليل التكرار اليومي
        $daily_counts = array();
        foreach ($transactions as $transaction) {
            $date = $transaction['transaction_date'];
            $daily_counts[$date] = ($daily_counts[$date] ?? 0) + 1;
        }

        if (!empty($daily_counts)) {
            $frequency['daily_average'] = array_sum($daily_counts) / count($daily_counts);

            arsort($daily_counts);
            $frequency['busiest_day'] = array_key_first($daily_counts);
            $frequency['quietest_day'] = array_key_last($daily_counts);
        }

        return $frequency;
    }

    /**
     * الحصول على أعلى رصيد
     */
    private function getHighestBalance($transactions, $opening_balance) {
        $highest = $opening_balance;
        $running_balance = $opening_balance;

        foreach ($transactions as $transaction) {
            $running_balance += ($transaction['debit_amount'] - $transaction['credit_amount']);
            if ($running_balance > $highest) {
                $highest = $running_balance;
            }
        }

        return $highest;
    }

    /**
     * الحصول على أقل رصيد
     */
    private function getLowestBalance($transactions, $opening_balance) {
        $lowest = $opening_balance;
        $running_balance = $opening_balance;

        foreach ($transactions as $transaction) {
            $running_balance += ($transaction['debit_amount'] - $transaction['credit_amount']);
            if ($running_balance < $lowest) {
                $lowest = $running_balance;
            }
        }

        return $lowest;
    }

    /**
     * الحصول على متوسط الرصيد
     */
    private function getAverageBalance($transactions, $opening_balance) {
        if (empty($transactions)) {
            return $opening_balance;
        }

        $total_balance = $opening_balance;
        $running_balance = $opening_balance;

        foreach ($transactions as $transaction) {
            $running_balance += ($transaction['debit_amount'] - $transaction['credit_amount']);
            $total_balance += $running_balance;
        }

        return $total_balance / (count($transactions) + 1);
    }

    /**
     * تحليل المخاطر
     */
    private function analyzeRisks($statement_data, $filter_data) {
        $risks = array();

        // فحص الأرصدة السالبة
        if ($statement_data['closing_balance'] < 0) {
            $risks[] = array(
                'type' => 'negative_balance',
                'severity' => 'high',
                'description' => 'الحساب له رصيد سالب',
                'value' => $statement_data['closing_balance']
            );
        }

        // فحص المعاملات الكبيرة
        $large_transactions = array_filter($statement_data['transactions'], function($transaction) {
            return abs($transaction['debit_amount'] - $transaction['credit_amount']) > 100000;
        });

        if (!empty($large_transactions)) {
            $risks[] = array(
                'type' => 'large_transactions',
                'severity' => 'medium',
                'description' => 'توجد معاملات بمبالغ كبيرة',
                'count' => count($large_transactions)
            );
        }

        // فحص عدم وجود حركة
        if (empty($statement_data['transactions'])) {
            $risks[] = array(
                'type' => 'no_activity',
                'severity' => 'low',
                'description' => 'لا توجد حركة في الفترة المحددة'
            );
        }

        return $risks;
    }

    /**
     * الحصول على إحصائيات سريعة للحساب
     */
    public function getQuickStats($account_id) {
        $current_balance = $this->getCurrentBalance($account_id);
        $last_transaction = $this->getLastTransaction($account_id);

        // إحصائيات الشهر الحالي
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');
        $current_month_summary = $this->getAccountSummary($account_id, $current_month_start, $current_month_end);

        return array(
            'current_balance' => $current_balance,
            'current_balance_formatted' => $this->currency->format($current_balance, $this->config->get('config_currency')),
            'last_transaction_date' => $last_transaction['transaction_date'] ?? '',
            'current_month_transactions' => $current_month_summary['transaction_count'],
            'current_month_debit' => $current_month_summary['total_debit'],
            'current_month_credit' => $current_month_summary['total_credit'],
            'current_month_net' => $current_month_summary['net_movement']
        );
    }
}
