<?php
class ModelAccountsAccountQuery extends Model {

    // جلب معلومات الحساب
    public function getAccountInfo($account_id) {
        $query = $this->db->query("SELECT
            a.account_id,
            a.account_code,
            a.account_name,
            a.account_type,
            a.parent_id,
            a.status,
            a.description,
            p.account_name as parent_name,
            p.account_code as parent_code
            FROM `cod_account` a
            LEFT JOIN `cod_account` p ON (a.parent_id = p.account_id)
            WHERE a.account_id = '" . (int)$account_id . "'");

        return $query->row;
    }

    // حساب رصيد الحساب مع التفاصيل
    public function calculateAccountBalance($account_id, $date_from = '', $date_to = '') {
        $where_date = "";

        if ($date_from && $date_to) {
            $where_date = " AND je.date BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'";
        } elseif ($date_from) {
            $where_date = " AND je.date >= '" . $this->db->escape($date_from) . "'";
        } elseif ($date_to) {
            $where_date = " AND je.date <= '" . $this->db->escape($date_to) . "'";
        }

        // حساب الرصيد الافتتاحي (قبل تاريخ البداية)
        $opening_balance = 0;
        if ($date_from) {
            $opening_query = $this->db->query("SELECT
                SUM(jed.debit) as total_debit,
                SUM(jed.credit) as total_credit
                FROM `cod_journal_entry_detail` jed
                LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
                WHERE jed.account_id = '" . (int)$account_id . "'
                AND je.date < '" . $this->db->escape($date_from) . "'
                AND je.status = 'approved'");

            if ($opening_query->row) {
                $opening_balance = (float)$opening_query->row['total_debit'] - (float)$opening_query->row['total_credit'];
            }
        }

        // حساب الحركة في الفترة المحددة
        $query = $this->db->query("SELECT
            SUM(jed.debit) as period_debit,
            SUM(jed.credit) as period_credit,
            COUNT(jed.journal_entry_detail_id) as transaction_count
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'" . $where_date);

        $period_debit = (float)$query->row['period_debit'];
        $period_credit = (float)$query->row['period_credit'];
        $transaction_count = (int)$query->row['transaction_count'];

        // الرصيد الختامي
        $closing_balance = $opening_balance + $period_debit - $period_credit;

        // صافي الحركة في الفترة
        $net_movement = $period_debit - $period_credit;

        return array(
            'opening_balance' => $opening_balance,
            'period_debit' => $period_debit,
            'period_credit' => $period_credit,
            'net_movement' => $net_movement,
            'closing_balance' => $closing_balance,
            'transaction_count' => $transaction_count,
            'date_from' => $date_from,
            'date_to' => $date_to
        );
    }

    // جلب آخر المعاملات
    public function getRecentTransactions($account_id, $limit = 10) {
        $query = $this->db->query("SELECT
            je.date,
            je.reference,
            jed.description,
            jed.debit,
            jed.credit,
            je.source_type,
            je.source_id
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'
            ORDER BY je.date DESC, je.journal_entry_id DESC
            LIMIT " . (int)$limit);

        return $query->rows;
    }

    // جلب إحصائيات الحساب
    public function getAccountStatistics($account_id, $date_from = '', $date_to = '') {
        $where_date = "";

        if ($date_from && $date_to) {
            $where_date = " AND je.date BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'";
        }

        // إحصائيات عامة
        $stats_query = $this->db->query("SELECT
            COUNT(DISTINCT je.date) as active_days,
            AVG(jed.debit) as avg_debit,
            AVG(jed.credit) as avg_credit,
            MAX(jed.debit) as max_debit,
            MAX(jed.credit) as max_credit,
            MIN(je.date) as first_transaction,
            MAX(je.date) as last_transaction
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'" . $where_date);

        // إحصائيات شهرية
        $monthly_query = $this->db->query("SELECT
            YEAR(je.date) as year,
            MONTH(je.date) as month,
            SUM(jed.debit) as monthly_debit,
            SUM(jed.credit) as monthly_credit,
            COUNT(*) as monthly_transactions
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'" . $where_date . "
            GROUP BY YEAR(je.date), MONTH(je.date)
            ORDER BY year DESC, month DESC
            LIMIT 12");

        return array(
            'general' => $stats_query->row,
            'monthly' => $monthly_query->rows
        );
    }

    // جلب تاريخ الأرصدة
    public function getBalanceHistory($account_id, $period = 'month') {
        $date_format = '';
        $group_by = '';

        switch ($period) {
            case 'day':
                $date_format = '%Y-%m-%d';
                $group_by = 'DATE(je.date)';
                break;
            case 'week':
                $date_format = '%Y-%u';
                $group_by = 'YEARWEEK(je.date)';
                break;
            case 'month':
                $date_format = '%Y-%m';
                $group_by = 'YEAR(je.date), MONTH(je.date)';
                break;
            case 'year':
                $date_format = '%Y';
                $group_by = 'YEAR(je.date)';
                break;
            default:
                $date_format = '%Y-%m';
                $group_by = 'YEAR(je.date), MONTH(je.date)';
        }

        $query = $this->db->query("SELECT
            DATE_FORMAT(je.date, '" . $date_format . "') as period,
            SUM(jed.debit) as period_debit,
            SUM(jed.credit) as period_credit,
            (SUM(jed.debit) - SUM(jed.credit)) as net_movement,
            COUNT(*) as transaction_count
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'
            AND je.date >= DATE_SUB(NOW(), INTERVAL 12 " . strtoupper($period) . ")
            GROUP BY " . $group_by . "
            ORDER BY je.date ASC");

        $history = array();
        $running_balance = 0;

        foreach ($query->rows as $row) {
            $running_balance += (float)$row['net_movement'];
            $history[] = array(
                'period' => $row['period'],
                'debit' => (float)$row['period_debit'],
                'credit' => (float)$row['period_credit'],
                'net_movement' => (float)$row['net_movement'],
                'running_balance' => $running_balance,
                'transaction_count' => (int)$row['transaction_count']
            );
        }

        return $history;
    }

    // جلب المعاملات مع الترقيم
    public function getTransactions($filter_data) {
        $sql = "SELECT
            je.date,
            je.reference,
            jed.description,
            jed.debit,
            jed.credit,
            je.source_type,
            je.source_id,
            @running_balance := @running_balance + (jed.debit - jed.credit) as running_balance
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            CROSS JOIN (SELECT @running_balance := 0) r
            WHERE jed.account_id = '" . (int)$filter_data['account_id'] . "'
            AND je.status = 'approved'";

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND je.date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND je.date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        $sql .= " ORDER BY je.date ASC, je.journal_entry_id ASC";

        if (isset($filter_data['start']) && isset($filter_data['limit'])) {
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // عدد المعاملات الإجمالي
    public function getTotalTransactions($filter_data) {
        $sql = "SELECT COUNT(*) as total
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$filter_data['account_id'] . "'
            AND je.status = 'approved'";

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND je.date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND je.date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    // جلب جميع المعاملات للتصدير
    public function getAllTransactions($account_id, $date_from = '', $date_to = '') {
        $sql = "SELECT
            je.date,
            je.reference,
            jed.description,
            jed.debit,
            jed.credit,
            je.source_type,
            @running_balance := @running_balance + (jed.debit - jed.credit) as running_balance
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            CROSS JOIN (SELECT @running_balance := 0) r
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'";

        if ($date_from) {
            $sql .= " AND je.date >= '" . $this->db->escape($date_from) . "'";
        }

        if ($date_to) {
            $sql .= " AND je.date <= '" . $this->db->escape($date_to) . "'";
        }

        $sql .= " ORDER BY je.date ASC, je.journal_entry_id ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // إنشاء ملف CSV
    public function generateCSV($account_info, $transactions) {
        $csv_content = "كشف حساب - " . $account_info['account_code'] . " - " . $account_info['account_name'] . "\n";
        $csv_content .= "تاريخ الإنشاء: " . date('Y-m-d H:i:s') . "\n";
        $csv_content .= "عدد المعاملات: " . count($transactions) . "\n\n";
        $csv_content .= "التاريخ,المرجع,البيان,مدين,دائن,الرصيد,المصدر\n";

        foreach ($transactions as $transaction) {
            $csv_content .= '"' . $transaction['date'] . '",';
            $csv_content .= '"' . $transaction['reference'] . '",';
            $csv_content .= '"' . $transaction['description'] . '",';
            $csv_content .= number_format($transaction['debit'], 2) . ',';
            $csv_content .= number_format($transaction['credit'], 2) . ',';
            $csv_content .= number_format($transaction['running_balance'], 2) . ',';
            $csv_content .= '"' . $transaction['source_type'] . '"' . "\n";
        }

        return $csv_content;
    }

    // البحث المتقدم في المعاملات
    public function advancedSearch($filter_data) {
        $sql = "SELECT
            je.date,
            je.reference,
            jed.description,
            jed.debit,
            jed.credit,
            je.source_type,
            je.source_id,
            a.account_code,
            a.account_name
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            LEFT JOIN `cod_account` a ON (jed.account_id = a.account_id)
            WHERE je.status = 'approved'";

        if (!empty($filter_data['account_id'])) {
            $sql .= " AND jed.account_id = '" . (int)$filter_data['account_id'] . "'";
        }

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND je.date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND je.date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        if (!empty($filter_data['reference'])) {
            $sql .= " AND je.reference LIKE '%" . $this->db->escape($filter_data['reference']) . "%'";
        }

        if (!empty($filter_data['description'])) {
            $sql .= " AND jed.description LIKE '%" . $this->db->escape($filter_data['description']) . "%'";
        }

        if (!empty($filter_data['min_amount'])) {
            $sql .= " AND (jed.debit >= '" . (float)$filter_data['min_amount'] . "' OR jed.credit >= '" . (float)$filter_data['min_amount'] . "')";
        }

        if (!empty($filter_data['max_amount'])) {
            $sql .= " AND (jed.debit <= '" . (float)$filter_data['max_amount'] . "' OR jed.credit <= '" . (float)$filter_data['max_amount'] . "')";
        }

        if (!empty($filter_data['source_type'])) {
            $sql .= " AND je.source_type = '" . $this->db->escape($filter_data['source_type']) . "'";
        }

        $sql .= " ORDER BY je.date DESC, je.journal_entry_id DESC";

        if (isset($filter_data['start']) && isset($filter_data['limit'])) {
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // مقارنة الحسابات
    public function compareAccounts($account_ids, $date_from = '', $date_to = '') {
        $results = array();

        foreach ($account_ids as $account_id) {
            $account_info = $this->getAccountInfo($account_id);
            $balance_data = $this->calculateAccountBalance($account_id, $date_from, $date_to);

            $results[] = array(
                'account_info' => $account_info,
                'balance_data' => $balance_data
            );
        }

        return $results;
    }

    // تحليل اتجاه الحساب
    public function analyzeTrend($account_id, $periods = 12) {
        $query = $this->db->query("SELECT
            YEAR(je.date) as year,
            MONTH(je.date) as month,
            SUM(jed.debit) as monthly_debit,
            SUM(jed.credit) as monthly_credit,
            (SUM(jed.debit) - SUM(jed.credit)) as net_movement
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'
            AND je.date >= DATE_SUB(NOW(), INTERVAL " . (int)$periods . " MONTH)
            GROUP BY YEAR(je.date), MONTH(je.date)
            ORDER BY year ASC, month ASC");

        $trend_data = $query->rows;

        // حساب معدل النمو
        $growth_rates = array();
        for ($i = 1; $i < count($trend_data); $i++) {
            $current = $trend_data[$i]['net_movement'];
            $previous = $trend_data[$i-1]['net_movement'];

            if ($previous != 0) {
                $growth_rate = (($current - $previous) / abs($previous)) * 100;
                $growth_rates[] = $growth_rate;
            }
        }

        $avg_growth_rate = count($growth_rates) > 0 ? array_sum($growth_rates) / count($growth_rates) : 0;

        return array(
            'trend_data' => $trend_data,
            'average_growth_rate' => $avg_growth_rate,
            'total_periods' => count($trend_data)
        );
    }

    // تحليل نشاط الحساب
    public function analyzeActivity($account_id, $date_from = '', $date_to = '') {
        $where_date = "";

        if ($date_from && $date_to) {
            $where_date = " AND je.date BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'";
        }

        // تحليل التوزيع الزمني
        $time_distribution = $this->db->query("SELECT
            DAYOFWEEK(je.date) as day_of_week,
            HOUR(je.created_at) as hour_of_day,
            COUNT(*) as transaction_count,
            SUM(jed.debit + jed.credit) as total_amount
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'" . $where_date . "
            GROUP BY DAYOFWEEK(je.date), HOUR(je.created_at)
            ORDER BY day_of_week, hour_of_day");

        // تحليل أنواع المصادر
        $source_analysis = $this->db->query("SELECT
            je.source_type,
            COUNT(*) as transaction_count,
            SUM(jed.debit) as total_debit,
            SUM(jed.credit) as total_credit,
            AVG(jed.debit + jed.credit) as avg_amount
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'" . $where_date . "
            GROUP BY je.source_type
            ORDER BY transaction_count DESC");

        // تحليل التكرار
        $frequency_analysis = $this->db->query("SELECT
            COUNT(DISTINCT DATE(je.date)) as active_days,
            COUNT(*) as total_transactions,
            (COUNT(*) / COUNT(DISTINCT DATE(je.date))) as avg_transactions_per_day,
            DATEDIFF(MAX(je.date), MIN(je.date)) + 1 as total_days,
            (COUNT(DISTINCT DATE(je.date)) / (DATEDIFF(MAX(je.date), MIN(je.date)) + 1)) * 100 as activity_percentage
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'" . $where_date);

        return array(
            'time_distribution' => $time_distribution->rows,
            'source_analysis' => $source_analysis->rows,
            'frequency_analysis' => $frequency_analysis->row
        );
    }

    // تحليل الأرصدة القصوى والدنيا
    public function analyzeExtremes($account_id, $date_from = '', $date_to = '') {
        $where_date = "";

        if ($date_from && $date_to) {
            $where_date = " AND je.date BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'";
        }

        // أعلى وأقل المعاملات
        $extreme_transactions = $this->db->query("SELECT
            'highest_debit' as type,
            je.date,
            je.reference,
            jed.description,
            jed.debit as amount,
            0 as credit
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'" . $where_date . "
            AND jed.debit > 0
            ORDER BY jed.debit DESC
            LIMIT 1

            UNION ALL

            SELECT
            'highest_credit' as type,
            je.date,
            je.reference,
            jed.description,
            0 as amount,
            jed.credit as credit
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'" . $where_date . "
            AND jed.credit > 0
            ORDER BY jed.credit DESC
            LIMIT 1");

        return $extreme_transactions->rows;
    }

    // إنشاء تقرير شامل
    public function generateComprehensiveReport($account_id, $date_from = '', $date_to = '') {
        $account_info = $this->getAccountInfo($account_id);
        $balance_data = $this->calculateAccountBalance($account_id, $date_from, $date_to);
        $statistics = $this->getAccountStatistics($account_id, $date_from, $date_to);
        $trend_analysis = $this->analyzeTrend($account_id);
        $activity_analysis = $this->analyzeActivity($account_id, $date_from, $date_to);
        $extremes = $this->analyzeExtremes($account_id, $date_from, $date_to);

        return array(
            'account_info' => $account_info,
            'balance_data' => $balance_data,
            'statistics' => $statistics,
            'trend_analysis' => $trend_analysis,
            'activity_analysis' => $activity_analysis,
            'extremes' => $extremes,
            'generated_at' => date('Y-m-d H:i:s')
        );
    }

    // حفظ الاستعلام المفضل
    public function saveFavoriteQuery($user_id, $query_name, $query_data) {
        $this->db->query("INSERT INTO `cod_favorite_queries` SET
            user_id = '" . (int)$user_id . "',
            query_name = '" . $this->db->escape($query_name) . "',
            query_type = 'account_query',
            query_data = '" . $this->db->escape(json_encode($query_data)) . "',
            created_at = NOW()");

        return $this->db->getLastId();
    }

    // جلب الاستعلامات المفضلة
    public function getFavoriteQueries($user_id) {
        $query = $this->db->query("SELECT * FROM `cod_favorite_queries`
            WHERE user_id = '" . (int)$user_id . "'
            AND query_type = 'account_query'
            ORDER BY created_at DESC");

        return $query->rows;
    }

    // إنشاء تقرير شامل متقدم
    public function generateAdvancedReport($account_id, $date_from = '', $date_to = '') {
        $account_info = $this->getAccountInfo($account_id);
        $balance_data = $this->calculateAccountBalance($account_id, $date_from, $date_to);
        $statistics = $this->getAccountStatistics($account_id, $date_from, $date_to);
        $transactions = $this->getAllTransactions($account_id, $date_from, $date_to);
        $trend_analysis = $this->analyzeTrend($account_id, 12);
        $activity_analysis = $this->analyzeActivity($account_id, $date_from, $date_to);

        return array(
            'account_info' => $account_info,
            'balance_data' => $balance_data,
            'statistics' => $statistics,
            'transactions' => $transactions,
            'trend_analysis' => $trend_analysis,
            'activity_analysis' => $activity_analysis,
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => array(
                'from' => $date_from,
                'to' => $date_to
            )
        );
    }

    // تحليل الأداء المالي
    public function analyzePerformance($account_id, $date_from = '', $date_to = '') {
        $where_date = "";

        if ($date_from && $date_to) {
            $where_date = " AND je.date BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'";
        }

        // حساب مؤشرات الأداء
        $performance_query = $this->db->query("SELECT
            COUNT(*) as total_transactions,
            SUM(jed.debit) as total_debit,
            SUM(jed.credit) as total_credit,
            AVG(jed.debit + jed.credit) as avg_transaction_amount,
            STDDEV(jed.debit + jed.credit) as transaction_volatility,
            MAX(jed.debit + jed.credit) as max_transaction,
            MIN(CASE WHEN (jed.debit + jed.credit) > 0 THEN (jed.debit + jed.credit) END) as min_transaction,
            COUNT(DISTINCT DATE(je.date)) as active_days,
            DATEDIFF(MAX(je.date), MIN(je.date)) + 1 as total_days
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'" . $where_date);

        $performance = $performance_query->row;

        // حساب معدل الدوران
        $turnover_ratio = 0;
        if ($performance['total_days'] > 0) {
            $turnover_ratio = $performance['total_transactions'] / $performance['total_days'];
        }

        // حساب معدل النشاط
        $activity_ratio = 0;
        if ($performance['total_days'] > 0) {
            $activity_ratio = ($performance['active_days'] / $performance['total_days']) * 100;
        }

        // تحليل الاتجاه الشهري
        $monthly_trend = $this->db->query("SELECT
            YEAR(je.date) as year,
            MONTH(je.date) as month,
            COUNT(*) as monthly_transactions,
            SUM(jed.debit + jed.credit) as monthly_volume,
            AVG(jed.debit + jed.credit) as monthly_avg
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'" . $where_date . "
            GROUP BY YEAR(je.date), MONTH(je.date)
            ORDER BY year DESC, month DESC
            LIMIT 12");

        return array(
            'performance_metrics' => array(
                'total_transactions' => (int)$performance['total_transactions'],
                'total_volume' => (float)($performance['total_debit'] + $performance['total_credit']),
                'avg_transaction_amount' => (float)$performance['avg_transaction_amount'],
                'transaction_volatility' => (float)$performance['transaction_volatility'],
                'max_transaction' => (float)$performance['max_transaction'],
                'min_transaction' => (float)$performance['min_transaction'],
                'turnover_ratio' => $turnover_ratio,
                'activity_ratio' => $activity_ratio,
                'active_days' => (int)$performance['active_days'],
                'total_days' => (int)$performance['total_days']
            ),
            'monthly_trend' => $monthly_trend->rows
        );
    }

    // تحليل المخاطر
    public function analyzeRisk($account_id, $periods = 12) {
        // جلب البيانات التاريخية
        $historical_data = $this->db->query("SELECT
            YEAR(je.date) as year,
            MONTH(je.date) as month,
            SUM(jed.debit - jed.credit) as net_movement,
            COUNT(*) as transaction_count,
            STDDEV(jed.debit - jed.credit) as volatility
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'
            AND je.date >= DATE_SUB(NOW(), INTERVAL " . (int)$periods . " MONTH)
            GROUP BY YEAR(je.date), MONTH(je.date)
            ORDER BY year ASC, month ASC");

        $movements = array();
        $volatilities = array();

        foreach ($historical_data->rows as $row) {
            $movements[] = (float)$row['net_movement'];
            $volatilities[] = (float)$row['volatility'];
        }

        // حساب مؤشرات المخاطر
        $risk_metrics = array();

        if (count($movements) > 1) {
            // التقلبات (Standard Deviation)
            $mean_movement = array_sum($movements) / count($movements);
            $variance = 0;
            foreach ($movements as $movement) {
                $variance += pow($movement - $mean_movement, 2);
            }
            $risk_metrics['volatility'] = sqrt($variance / (count($movements) - 1));

            // معامل التباين (Coefficient of Variation)
            $risk_metrics['coefficient_of_variation'] = $mean_movement != 0 ?
                ($risk_metrics['volatility'] / abs($mean_movement)) * 100 : 0;

            // أقصى انخفاض (Maximum Drawdown)
            $peak = $movements[0];
            $max_drawdown = 0;
            $running_balance = 0;

            foreach ($movements as $movement) {
                $running_balance += $movement;
                if ($running_balance > $peak) {
                    $peak = $running_balance;
                }
                $drawdown = ($peak - $running_balance) / $peak * 100;
                if ($drawdown > $max_drawdown) {
                    $max_drawdown = $drawdown;
                }
            }
            $risk_metrics['max_drawdown'] = $max_drawdown;

            // Value at Risk (VaR) - 95% confidence level
            sort($movements);
            $var_index = floor(count($movements) * 0.05);
            $risk_metrics['value_at_risk_95'] = $movements[$var_index];

            // تصنيف المخاطر
            if ($risk_metrics['coefficient_of_variation'] < 10) {
                $risk_metrics['risk_level'] = 'منخفض';
            } elseif ($risk_metrics['coefficient_of_variation'] < 25) {
                $risk_metrics['risk_level'] = 'متوسط';
            } else {
                $risk_metrics['risk_level'] = 'عالي';
            }
        }

        return array(
            'historical_data' => $historical_data->rows,
            'risk_metrics' => $risk_metrics,
            'analysis_period' => $periods . ' شهر',
            'data_points' => count($movements)
        );
    }

    // تحليل الموسمية
    public function analyzeSeasonality($account_id, $years = 3) {
        // جلب البيانات حسب الشهر لعدة سنوات
        $seasonal_data = $this->db->query("SELECT
            MONTH(je.date) as month,
            YEAR(je.date) as year,
            SUM(jed.debit) as monthly_debit,
            SUM(jed.credit) as monthly_credit,
            SUM(jed.debit - jed.credit) as net_movement,
            COUNT(*) as transaction_count
            FROM `cod_journal_entry_detail` jed
            LEFT JOIN `cod_journal_entry` je ON (jed.journal_entry_id = je.journal_entry_id)
            WHERE jed.account_id = '" . (int)$account_id . "'
            AND je.status = 'approved'
            AND je.date >= DATE_SUB(NOW(), INTERVAL " . (int)$years . " YEAR)
            GROUP BY YEAR(je.date), MONTH(je.date)
            ORDER BY year ASC, month ASC");

        // تجميع البيانات حسب الشهر
        $monthly_averages = array();
        $monthly_data = array();

        for ($month = 1; $month <= 12; $month++) {
            $monthly_data[$month] = array();
        }

        foreach ($seasonal_data->rows as $row) {
            $month = (int)$row['month'];
            $monthly_data[$month][] = array(
                'year' => $row['year'],
                'debit' => (float)$row['monthly_debit'],
                'credit' => (float)$row['monthly_credit'],
                'net_movement' => (float)$row['net_movement'],
                'transactions' => (int)$row['transaction_count']
            );
        }

        // حساب المتوسطات الشهرية
        foreach ($monthly_data as $month => $data) {
            if (!empty($data)) {
                $total_debit = array_sum(array_column($data, 'debit'));
                $total_credit = array_sum(array_column($data, 'credit'));
                $total_net = array_sum(array_column($data, 'net_movement'));
                $total_transactions = array_sum(array_column($data, 'transactions'));

                $monthly_averages[$month] = array(
                    'month' => $month,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                    'avg_debit' => $total_debit / count($data),
                    'avg_credit' => $total_credit / count($data),
                    'avg_net_movement' => $total_net / count($data),
                    'avg_transactions' => $total_transactions / count($data),
                    'data_points' => count($data)
                );
            }
        }

        // حساب مؤشر الموسمية
        $overall_avg = 0;
        $valid_months = 0;

        foreach ($monthly_averages as $avg) {
            $overall_avg += $avg['avg_net_movement'];
            $valid_months++;
        }

        if ($valid_months > 0) {
            $overall_avg = $overall_avg / $valid_months;
        }

        // حساب مؤشر الموسمية لكل شهر
        foreach ($monthly_averages as &$avg) {
            if ($overall_avg != 0) {
                $avg['seasonality_index'] = ($avg['avg_net_movement'] / $overall_avg) * 100;
            } else {
                $avg['seasonality_index'] = 100;
            }
        }

        return array(
            'monthly_averages' => array_values($monthly_averages),
            'raw_data' => $seasonal_data->rows,
            'analysis_period' => $years . ' سنوات',
            'overall_average' => $overall_avg
        );
    }

    // حفظ الاستعلام المفضل
    public function saveFavoriteQuery($user_id, $query_name, $query_data) {
        $this->db->query("INSERT INTO `cod_account_query_favorites` SET
            user_id = '" . (int)$user_id . "',
            query_name = '" . $this->db->escape($query_name) . "',
            query_data = '" . $this->db->escape(json_encode($query_data)) . "',
            created_at = NOW()");

        return $this->db->getLastId();
    }

    // جلب الاستعلامات المفضلة
    public function getFavoriteQueries($user_id) {
        $query = $this->db->query("SELECT
            favorite_id,
            query_name,
            query_data,
            created_at
            FROM `cod_account_query_favorites`
            WHERE user_id = '" . (int)$user_id . "'
            ORDER BY created_at DESC");

        $favorites = array();
        foreach ($query->rows as $row) {
            $favorites[] = array(
                'favorite_id' => $row['favorite_id'],
                'query_name' => $row['query_name'],
                'query_data' => json_decode($row['query_data'], true),
                'created_at' => $row['created_at']
            );
        }

        return $favorites;
    }

    // جلب استعلام مفضل محدد
    public function getFavoriteQuery($favorite_id, $user_id) {
        $query = $this->db->query("SELECT
            favorite_id,
            query_name,
            query_data,
            created_at
            FROM `cod_account_query_favorites`
            WHERE favorite_id = '" . (int)$favorite_id . "'
            AND user_id = '" . (int)$user_id . "'");

        if ($query->num_rows) {
            return array(
                'favorite_id' => $query->row['favorite_id'],
                'query_name' => $query->row['query_name'],
                'query_data' => json_decode($query->row['query_data'], true),
                'created_at' => $query->row['created_at']
            );
        }

        return false;
    }

    // حذف استعلام مفضل
    public function deleteFavoriteQuery($favorite_id, $user_id) {
        $this->db->query("DELETE FROM `cod_account_query_favorites`
            WHERE favorite_id = '" . (int)$favorite_id . "'
            AND user_id = '" . (int)$user_id . "'");

        return $this->db->countAffected() > 0;
    }

    // إنشاء بيانات التصدير المتقدمة
    public function generateExportData($account_id, $date_from = '', $date_to = '', $include_summary = true, $include_statistics = false) {
        $export_data = array();

        // معلومات الحساب
        $export_data['account_info'] = $this->getAccountInfo($account_id);

        // ملخص الأرصدة
        if ($include_summary) {
            $export_data['balance_summary'] = $this->calculateAccountBalance($account_id, $date_from, $date_to);
        }

        // الإحصائيات
        if ($include_statistics) {
            $export_data['statistics'] = $this->getAccountStatistics($account_id, $date_from, $date_to);
        }

        // المعاملات
        $export_data['transactions'] = $this->getAllTransactions($account_id, $date_from, $date_to);

        // معلومات التصدير
        $export_data['export_info'] = array(
            'generated_at' => date('Y-m-d H:i:s'),
            'period_from' => $date_from,
            'period_to' => $date_to,
            'total_transactions' => count($export_data['transactions']),
            'include_summary' => $include_summary,
            'include_statistics' => $include_statistics
        );

        return $export_data;
    }

    // إنشاء CSV متقدم
    public function generateAdvancedCSV($export_data) {
        $csv_content = "تقرير استعلام الحسابات المتقدم\n";
        $csv_content .= "تاريخ الإنشاء: " . $export_data['export_info']['generated_at'] . "\n";
        $csv_content .= "رمز الحساب: " . $export_data['account_info']['account_code'] . "\n";
        $csv_content .= "اسم الحساب: " . $export_data['account_info']['account_name'] . "\n";
        $csv_content .= "نوع الحساب: " . $export_data['account_info']['account_type'] . "\n";
        $csv_content .= "الفترة من: " . ($export_data['export_info']['period_from'] ?: 'البداية') . "\n";
        $csv_content .= "الفترة إلى: " . ($export_data['export_info']['period_to'] ?: 'النهاية') . "\n";
        $csv_content .= "عدد المعاملات: " . $export_data['export_info']['total_transactions'] . "\n\n";

        // ملخص الأرصدة
        if (isset($export_data['balance_summary'])) {
            $csv_content .= "ملخص الأرصدة\n";
            $csv_content .= "البند,المبلغ\n";
            $csv_content .= "الرصيد الافتتاحي," . $export_data['balance_summary']['opening_balance'] . "\n";
            $csv_content .= "إجمالي المدين," . $export_data['balance_summary']['period_debit'] . "\n";
            $csv_content .= "إجمالي الدائن," . $export_data['balance_summary']['period_credit'] . "\n";
            $csv_content .= "صافي الحركة," . $export_data['balance_summary']['net_movement'] . "\n";
            $csv_content .= "الرصيد الختامي," . $export_data['balance_summary']['closing_balance'] . "\n\n";
        }

        // الإحصائيات
        if (isset($export_data['statistics'])) {
            $csv_content .= "الإحصائيات العامة\n";
            $csv_content .= "المؤشر,القيمة\n";
            $csv_content .= "الأيام النشطة," . ($export_data['statistics']['general']['active_days'] ?: 0) . "\n";
            $csv_content .= "متوسط المدين," . ($export_data['statistics']['general']['avg_debit'] ?: 0) . "\n";
            $csv_content .= "متوسط الدائن," . ($export_data['statistics']['general']['avg_credit'] ?: 0) . "\n";
            $csv_content .= "أعلى مدين," . ($export_data['statistics']['general']['max_debit'] ?: 0) . "\n";
            $csv_content .= "أعلى دائن," . ($export_data['statistics']['general']['max_credit'] ?: 0) . "\n\n";
        }

        // المعاملات
        $csv_content .= "المعاملات التفصيلية\n";
        $csv_content .= "التاريخ,المرجع,البيان,مدين,دائن,الرصيد الجاري,المصدر\n";

        foreach ($export_data['transactions'] as $transaction) {
            $csv_content .= '"' . $transaction['date'] . '",';
            $csv_content .= '"' . $transaction['reference'] . '",';
            $csv_content .= '"' . $transaction['description'] . '",';
            $csv_content .= number_format($transaction['debit'], 2) . ',';
            $csv_content .= number_format($transaction['credit'], 2) . ',';
            $csv_content .= number_format($transaction['running_balance'], 2) . ',';
            $csv_content .= '"' . $transaction['source_type'] . '"' . "\n";
        }

        return $csv_content;
    }

    // إنشاء XML
    public function generateXML($export_data) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><account_report></account_report>');

        // معلومات الحساب
        $account_info = $xml->addChild('account_info');
        $account_info->addChild('account_id', $export_data['account_info']['account_id']);
        $account_info->addChild('account_code', htmlspecialchars($export_data['account_info']['account_code']));
        $account_info->addChild('account_name', htmlspecialchars($export_data['account_info']['account_name']));
        $account_info->addChild('account_type', htmlspecialchars($export_data['account_info']['account_type']));

        // ملخص الأرصدة
        if (isset($export_data['balance_summary'])) {
            $balance_summary = $xml->addChild('balance_summary');
            $balance_summary->addChild('opening_balance', $export_data['balance_summary']['opening_balance']);
            $balance_summary->addChild('period_debit', $export_data['balance_summary']['period_debit']);
            $balance_summary->addChild('period_credit', $export_data['balance_summary']['period_credit']);
            $balance_summary->addChild('net_movement', $export_data['balance_summary']['net_movement']);
            $balance_summary->addChild('closing_balance', $export_data['balance_summary']['closing_balance']);
        }

        // المعاملات
        $transactions = $xml->addChild('transactions');
        foreach ($export_data['transactions'] as $transaction) {
            $trans = $transactions->addChild('transaction');
            $trans->addChild('date', $transaction['date']);
            $trans->addChild('reference', htmlspecialchars($transaction['reference']));
            $trans->addChild('description', htmlspecialchars($transaction['description']));
            $trans->addChild('debit', $transaction['debit']);
            $trans->addChild('credit', $transaction['credit']);
            $trans->addChild('running_balance', $transaction['running_balance']);
            $trans->addChild('source_type', htmlspecialchars($transaction['source_type']));
        }

        // معلومات التصدير
        $export_info = $xml->addChild('export_info');
        $export_info->addChild('generated_at', $export_data['export_info']['generated_at']);
        $export_info->addChild('period_from', $export_data['export_info']['period_from']);
        $export_info->addChild('period_to', $export_data['export_info']['period_to']);
        $export_info->addChild('total_transactions', $export_data['export_info']['total_transactions']);

        return $xml->asXML();
    }
}
