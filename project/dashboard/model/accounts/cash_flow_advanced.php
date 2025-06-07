<?php
/**
 * نموذج قائمة التدفقات النقدية المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelAccountsCashFlowAdvanced extends Model {

    /**
     * إنشاء قائمة التدفقات النقدية المتقدمة
     */
    public function generateCashFlowStatement($filter_data) {
        // الحصول على رصيد النقدية أول وآخر الفترة
        $opening_cash = $this->getCashBalance($filter_data['date_start'], true);
        $closing_cash = $this->getCashBalance($filter_data['date_end'], false);

        // الحصول على التدفقات حسب النوع
        if ($filter_data['method'] == 'indirect') {
            $operating_activities = $this->getOperatingCashFlowsIndirect($filter_data);
        } else {
            $operating_activities = $this->getOperatingCashFlowsDirect($filter_data);
        }

        $investing_activities = $this->getInvestingCashFlows($filter_data);
        $financing_activities = $this->getFinancingCashFlows($filter_data);

        // حساب الإجماليات
        $totals = $this->calculateCashFlowTotals($operating_activities, $investing_activities, $financing_activities);

        // إضافة المقارنة إذا كانت مطلوبة
        $comparative_data = null;
        if ($filter_data['show_comparative'] && !empty($filter_data['comparative_date_start']) && !empty($filter_data['comparative_date_end'])) {
            $comparative_filter = $filter_data;
            $comparative_filter['date_start'] = $filter_data['comparative_date_start'];
            $comparative_filter['date_end'] = $filter_data['comparative_date_end'];
            $comparative_data = $this->generateCashFlowStatement($comparative_filter);
        }

        return array(
            'opening_cash' => $opening_cash,
            'closing_cash' => $closing_cash,
            'operating_activities' => $operating_activities,
            'investing_activities' => $investing_activities,
            'financing_activities' => $financing_activities,
            'totals' => $totals,
            'comparative_data' => $comparative_data,
            'filter_data' => $filter_data,
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->user->getId(),
            'currency' => $filter_data['currency'],
            'method' => $filter_data['method']
        );
    }

    /**
     * الحصول على رصيد النقدية
     */
    private function getCashBalance($date, $is_opening = false) {
        $cash_accounts = $this->getCashAccountIds();

        if (empty($cash_accounts)) {
            return 0;
        }

        $date_condition = $is_opening ? "< '" . $this->db->escape($date) . "'" : "<= '" . $this->db->escape($date) . "'";

        $sql = "
            SELECT COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as balance
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE jel.account_id IN (" . implode(',', array_map('intval', $cash_accounts)) . ")
            AND je.status = 'posted'
            AND je.journal_date " . $date_condition . "
        ";

        $query = $this->db->query($sql);
        return (float)$query->row['balance'];
    }

    /**
     * الحصول على التدفقات التشغيلية - الطريقة المباشرة
     */
    private function getOperatingCashFlowsDirect($filter_data) {
        $cash_accounts = $this->getCashAccountIds();

        if (empty($cash_accounts)) {
            return array();
        }

        $sql = "
            SELECT
                jel2.account_id as other_account_id,
                ad.name as other_account_name,
                a.account_code as other_account_code,
                a.account_type as other_account_type,
                a.account_subtype as other_account_subtype,
                SUM(CASE
                    WHEN jel.debit_amount > 0 THEN jel2.credit_amount - jel2.debit_amount
                    ELSE jel2.debit_amount - jel2.credit_amount
                END) as amount
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            JOIN " . DB_PREFIX . "journal_entry_line jel2 ON je.journal_id = jel2.journal_id AND jel2.account_id != jel.account_id
            JOIN " . DB_PREFIX . "accounts a ON jel2.account_id = a.account_id
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE jel.account_id IN (" . implode(',', array_map('intval', $cash_accounts)) . ")
            AND je.status = 'posted'
            AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
            AND a.account_type IN ('revenue', 'expense')
        ";

        // فلترة التدفقات الصفرية
        if (!$filter_data['include_zero_flows']) {
            $sql .= " AND jel.debit_amount != jel.credit_amount";
        }

        $sql .= " GROUP BY jel2.account_id, ad.name, a.account_code, a.account_type, a.account_subtype";
        $sql .= " ORDER BY a.account_code";

        $query = $this->db->query($sql);

        $activities = array();
        foreach ($query->rows as $row) {
            $amount = (float)$row['amount'];

            if ($amount != 0 || $filter_data['include_zero_flows']) {
                $activities[] = array(
                    'account_id' => $row['other_account_id'],
                    'account_code' => $row['other_account_code'],
                    'account_name' => $row['other_account_name'],
                    'account_type' => $row['other_account_type'],
                    'account_subtype' => $row['other_account_subtype'],
                    'description' => $this->getOperatingActivityDescription($row['other_account_name'], $row['other_account_type'], $amount),
                    'amount' => $amount,
                    'amount_formatted' => $this->currency->format($amount, $filter_data['currency'])
                );
            }
        }

        return $activities;
    }

    /**
     * الحصول على التدفقات التشغيلية - الطريقة غير المباشرة
     */
    private function getOperatingCashFlowsIndirect($filter_data) {
        // البدء بصافي الربح
        $net_income = $this->getNetIncome($filter_data);

        $activities = array();

        // إضافة صافي الربح
        $activities[] = array(
            'description' => 'صافي الربح',
            'amount' => $net_income,
            'amount_formatted' => $this->currency->format($net_income, $filter_data['currency'])
        );

        // تعديلات للبنود غير النقدية
        $non_cash_adjustments = $this->getNonCashAdjustments($filter_data);
        foreach ($non_cash_adjustments as $adjustment) {
            $activities[] = $adjustment;
        }

        // التغيرات في رأس المال العامل
        $working_capital_changes = $this->getWorkingCapitalChanges($filter_data);
        foreach ($working_capital_changes as $change) {
            $activities[] = $change;
        }

        return $activities;
    }

    /**
     * الحصول على التدفقات الاستثمارية
     */
    private function getInvestingCashFlows($filter_data) {
        $cash_accounts = $this->getCashAccountIds();

        if (empty($cash_accounts)) {
            return array();
        }

        $sql = "
            SELECT
                jel2.account_id as other_account_id,
                ad.name as other_account_name,
                a.account_code as other_account_code,
                a.account_type as other_account_type,
                a.account_subtype as other_account_subtype,
                SUM(CASE
                    WHEN jel.debit_amount > 0 THEN jel2.credit_amount - jel2.debit_amount
                    ELSE jel2.debit_amount - jel2.credit_amount
                END) as amount
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            JOIN " . DB_PREFIX . "journal_entry_line jel2 ON je.journal_id = jel2.journal_id AND jel2.account_id != jel.account_id
            JOIN " . DB_PREFIX . "accounts a ON jel2.account_id = a.account_id
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE jel.account_id IN (" . implode(',', array_map('intval', $cash_accounts)) . ")
            AND je.status = 'posted'
            AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
            AND (a.account_subtype IN ('fixed_assets', 'investments', 'intangible_assets')
                 OR a.account_type = 'asset' AND a.account_subtype NOT IN ('cash', 'current_assets'))
        ";

        // فلترة التدفقات الصفرية
        if (!$filter_data['include_zero_flows']) {
            $sql .= " AND jel.debit_amount != jel.credit_amount";
        }

        $sql .= " GROUP BY jel2.account_id, ad.name, a.account_code, a.account_type, a.account_subtype";
        $sql .= " ORDER BY a.account_code";

        $query = $this->db->query($sql);

        $activities = array();
        foreach ($query->rows as $row) {
            $amount = (float)$row['amount'];

            if ($amount != 0 || $filter_data['include_zero_flows']) {
                $activities[] = array(
                    'account_id' => $row['other_account_id'],
                    'account_code' => $row['other_account_code'],
                    'account_name' => $row['other_account_name'],
                    'account_type' => $row['other_account_type'],
                    'account_subtype' => $row['other_account_subtype'],
                    'description' => $this->getInvestingActivityDescription($row['other_account_name'], $row['other_account_subtype'], $amount),
                    'amount' => $amount,
                    'amount_formatted' => $this->currency->format($amount, $filter_data['currency'])
                );
            }
        }

        return $activities;
    }

    /**
     * الحصول على التدفقات التمويلية
     */
    private function getFinancingCashFlows($filter_data) {
        $cash_accounts = $this->getCashAccountIds();

        if (empty($cash_accounts)) {
            return array();
        }

        $sql = "
            SELECT
                jel2.account_id as other_account_id,
                ad.name as other_account_name,
                a.account_code as other_account_code,
                a.account_type as other_account_type,
                a.account_subtype as other_account_subtype,
                SUM(CASE
                    WHEN jel.debit_amount > 0 THEN jel2.credit_amount - jel2.debit_amount
                    ELSE jel2.debit_amount - jel2.credit_amount
                END) as amount
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            JOIN " . DB_PREFIX . "journal_entry_line jel2 ON je.journal_id = jel2.journal_id AND jel2.account_id != jel.account_id
            JOIN " . DB_PREFIX . "accounts a ON jel2.account_id = a.account_id
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE jel.account_id IN (" . implode(',', array_map('intval', $cash_accounts)) . ")
            AND je.status = 'posted'
            AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
            AND (a.account_type IN ('liability', 'equity')
                 OR a.account_subtype IN ('long_term_debt', 'short_term_debt', 'capital', 'retained_earnings'))
        ";

        // فلترة التدفقات الصفرية
        if (!$filter_data['include_zero_flows']) {
            $sql .= " AND jel.debit_amount != jel.credit_amount";
        }

        $sql .= " GROUP BY jel2.account_id, ad.name, a.account_code, a.account_type, a.account_subtype";
        $sql .= " ORDER BY a.account_code";

        $query = $this->db->query($sql);

        $activities = array();
        foreach ($query->rows as $row) {
            $amount = (float)$row['amount'];

            if ($amount != 0 || $filter_data['include_zero_flows']) {
                $activities[] = array(
                    'account_id' => $row['other_account_id'],
                    'account_code' => $row['other_account_code'],
                    'account_name' => $row['other_account_name'],
                    'account_type' => $row['other_account_type'],
                    'account_subtype' => $row['other_account_subtype'],
                    'description' => $this->getFinancingActivityDescription($row['other_account_name'], $row['other_account_subtype'], $amount),
                    'amount' => $amount,
                    'amount_formatted' => $this->currency->format($amount, $filter_data['currency'])
                );
            }
        }

        return $activities;
    }

    /**
     * حساب إجماليات التدفقات النقدية
     */
    private function calculateCashFlowTotals($operating_activities, $investing_activities, $financing_activities) {
        $operating_total = 0;
        $investing_total = 0;
        $financing_total = 0;

        // حساب إجمالي التدفقات التشغيلية
        foreach ($operating_activities as $activity) {
            $operating_total += $activity['amount'];
        }

        // حساب إجمالي التدفقات الاستثمارية
        foreach ($investing_activities as $activity) {
            $investing_total += $activity['amount'];
        }

        // حساب إجمالي التدفقات التمويلية
        foreach ($financing_activities as $activity) {
            $financing_total += $activity['amount'];
        }

        $net_change = $operating_total + $investing_total + $financing_total;

        return array(
            'operating_total' => $operating_total,
            'investing_total' => $investing_total,
            'financing_total' => $financing_total,
            'net_change' => $net_change
        );
    }

    /**
     * الحصول على معرفات الحسابات النقدية
     */
    private function getCashAccountIds() {
        $sql = "
            SELECT account_id
            FROM " . DB_PREFIX . "accounts
            WHERE account_type = 'asset'
            AND account_subtype IN ('cash', 'bank')
            AND is_active = 1
        ";

        $query = $this->db->query($sql);

        $cash_accounts = array();
        foreach ($query->rows as $row) {
            $cash_accounts[] = $row['account_id'];
        }

        return $cash_accounts;
    }

    /**
     * الحصول على وصف النشاط التشغيلي
     */
    private function getOperatingActivityDescription($account_name, $account_type, $amount) {
        if ($account_type == 'revenue') {
            return $amount > 0 ? 'مقبوضات من ' . $account_name : 'مردودات ' . $account_name;
        } else {
            return $amount > 0 ? 'مدفوعات ' . $account_name : 'استرداد ' . $account_name;
        }
    }

    /**
     * الحصول على وصف النشاط الاستثماري
     */
    private function getInvestingActivityDescription($account_name, $account_subtype, $amount) {
        if ($amount > 0) {
            return 'بيع ' . $account_name;
        } else {
            return 'شراء ' . $account_name;
        }
    }

    /**
     * الحصول على وصف النشاط التمويلي
     */
    private function getFinancingActivityDescription($account_name, $account_subtype, $amount) {
        if ($account_subtype == 'capital' || $account_subtype == 'equity') {
            return $amount > 0 ? 'زيادة رأس المال' : 'تخفيض رأس المال';
        } elseif (strpos($account_subtype, 'debt') !== false) {
            return $amount > 0 ? 'اقتراض من ' . $account_name : 'سداد ' . $account_name;
        } else {
            return $amount > 0 ? 'تمويل من ' . $account_name : 'سداد إلى ' . $account_name;
        }
    }

    /**
     * الحصول على صافي الربح للطريقة غير المباشرة
     */
    private function getNetIncome($filter_data) {
        $sql = "
            SELECT
                COALESCE(SUM(CASE WHEN a.account_type = 'revenue' THEN jel.credit_amount - jel.debit_amount ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN a.account_type = 'expense' THEN jel.debit_amount - jel.credit_amount ELSE 0 END), 0) as total_expense
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
            WHERE je.status = 'posted'
            AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
            AND a.account_type IN ('revenue', 'expense')
        ";

        $query = $this->db->query($sql);
        $row = $query->row;

        return (float)$row['total_revenue'] - (float)$row['total_expense'];
    }

    /**
     * الحصول على التعديلات للبنود غير النقدية
     */
    private function getNonCashAdjustments($filter_data) {
        $adjustments = array();

        // الاستهلاك
        $depreciation = $this->getDepreciation($filter_data);
        if ($depreciation != 0) {
            $adjustments[] = array(
                'description' => 'الاستهلاك',
                'amount' => $depreciation,
                'amount_formatted' => $this->currency->format($depreciation, $filter_data['currency'])
            );
        }

        // إطفاء الأصول غير الملموسة
        $amortization = $this->getAmortization($filter_data);
        if ($amortization != 0) {
            $adjustments[] = array(
                'description' => 'إطفاء الأصول غير الملموسة',
                'amount' => $amortization,
                'amount_formatted' => $this->currency->format($amortization, $filter_data['currency'])
            );
        }

        return $adjustments;
    }

    /**
     * الحصول على التغيرات في رأس المال العامل
     */
    private function getWorkingCapitalChanges($filter_data) {
        $changes = array();

        // التغير في المدينين
        $receivables_change = $this->getReceivablesChange($filter_data);
        if ($receivables_change != 0) {
            $changes[] = array(
                'description' => 'التغير في المدينين',
                'amount' => -$receivables_change, // عكس الإشارة للتدفق النقدي
                'amount_formatted' => $this->currency->format(-$receivables_change, $filter_data['currency'])
            );
        }

        // التغير في المخزون
        $inventory_change = $this->getInventoryChange($filter_data);
        if ($inventory_change != 0) {
            $changes[] = array(
                'description' => 'التغير في المخزون',
                'amount' => -$inventory_change, // عكس الإشارة للتدفق النقدي
                'amount_formatted' => $this->currency->format(-$inventory_change, $filter_data['currency'])
            );
        }

        // التغير في الدائنين
        $payables_change = $this->getPayablesChange($filter_data);
        if ($payables_change != 0) {
            $changes[] = array(
                'description' => 'التغير في الدائنين',
                'amount' => $payables_change,
                'amount_formatted' => $this->currency->format($payables_change, $filter_data['currency'])
            );
        }

        return $changes;
    }

    /**
     * الحصول على الاستهلاك
     */
    private function getDepreciation($filter_data) {
        $sql = "
            SELECT COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as depreciation
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
            WHERE je.status = 'posted'
            AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
            AND (a.account_subtype = 'depreciation' OR LOWER(a.account_code) LIKE '%depreciation%' OR LOWER(a.account_code) LIKE '%استهلاك%')
        ";

        $query = $this->db->query($sql);
        return (float)$query->row['depreciation'];
    }

    /**
     * الحصول على الإطفاء
     */
    private function getAmortization($filter_data) {
        $sql = "
            SELECT COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as amortization
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
            WHERE je.status = 'posted'
            AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
            AND (a.account_subtype = 'amortization' OR LOWER(a.account_code) LIKE '%amortization%' OR LOWER(a.account_code) LIKE '%إطفاء%')
        ";

        $query = $this->db->query($sql);
        return (float)$query->row['amortization'];
    }

    /**
     * الحصول على التغير في المدينين
     */
    private function getReceivablesChange($filter_data) {
        $opening_balance = $this->getAccountTypeBalance($filter_data['date_start'], 'receivables', true);
        $closing_balance = $this->getAccountTypeBalance($filter_data['date_end'], 'receivables', false);

        return $closing_balance - $opening_balance;
    }

    /**
     * الحصول على التغير في المخزون
     */
    private function getInventoryChange($filter_data) {
        $opening_balance = $this->getAccountTypeBalance($filter_data['date_start'], 'inventory', true);
        $closing_balance = $this->getAccountTypeBalance($filter_data['date_end'], 'inventory', false);

        return $closing_balance - $opening_balance;
    }

    /**
     * الحصول على التغير في الدائنين
     */
    private function getPayablesChange($filter_data) {
        $opening_balance = $this->getAccountTypeBalance($filter_data['date_start'], 'payables', true);
        $closing_balance = $this->getAccountTypeBalance($filter_data['date_end'], 'payables', false);

        return $closing_balance - $opening_balance;
    }

    /**
     * الحصول على رصيد نوع حساب معين
     */
    private function getAccountTypeBalance($date, $account_subtype, $is_opening = false) {
        $date_condition = $is_opening ? "< '" . $this->db->escape($date) . "'" : "<= '" . $this->db->escape($date) . "'";

        $sql = "
            SELECT COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as balance
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
            WHERE je.status = 'posted'
            AND je.journal_date " . $date_condition . "
            AND a.account_subtype = '" . $this->db->escape($account_subtype) . "'
        ";

        $query = $this->db->query($sql);
        return (float)$query->row['balance'];
    }

    /**
     * مقارنة قوائم التدفقات النقدية بين فترتين
     */
    public function compareCashFlows($period1, $period2) {
        $cash_flow_1 = $this->generateCashFlowStatement($period1);
        $cash_flow_2 = $this->generateCashFlowStatement($period2);

        $comparison = array(
            'period_1' => $period1,
            'period_2' => $period2,
            'period_1_data' => $cash_flow_1,
            'period_2_data' => $cash_flow_2
        );

        // حساب الفروقات
        $comparison['variance'] = array(
            'operating_total' => $cash_flow_2['totals']['operating_total'] - $cash_flow_1['totals']['operating_total'],
            'investing_total' => $cash_flow_2['totals']['investing_total'] - $cash_flow_1['totals']['investing_total'],
            'financing_total' => $cash_flow_2['totals']['financing_total'] - $cash_flow_1['totals']['financing_total'],
            'net_change' => $cash_flow_2['totals']['net_change'] - $cash_flow_1['totals']['net_change']
        );

        // حساب النسب المئوية
        $comparison['percentage_change'] = array();
        foreach ($comparison['variance'] as $key => $variance) {
            $base_value = $cash_flow_1['totals'][$key];
            if ($base_value != 0) {
                $comparison['percentage_change'][$key] = ($variance / abs($base_value)) * 100;
            } else {
                $comparison['percentage_change'][$key] = $variance != 0 ? 100 : 0;
            }
        }

        return $comparison;
    }

    /**
     * حساب نسب التدفقات النقدية
     */
    public function calculateCashFlowRatios($cash_flow_data) {
        $totals = $cash_flow_data['totals'];

        $ratios = array();

        // نسب جودة الأرباح
        $ratios['quality'] = array(
            'operating_cash_flow_ratio' => $totals['operating_total'],
            'cash_flow_margin' => 0, // يحتاج لبيانات المبيعات
            'cash_return_on_assets' => 0 // يحتاج لبيانات الأصول
        );

        // نسب السيولة النقدية
        $ratios['liquidity'] = array(
            'cash_ratio' => 0, // يحتاج لبيانات الخصوم المتداولة
            'operating_cash_flow_to_current_liabilities' => 0, // يحتاج لبيانات الخصوم المتداولة
            'cash_coverage_ratio' => 0 // يحتاج لبيانات الفوائد
        );

        // نسب الكفاءة
        $ratios['efficiency'] = array(
            'cash_conversion_efficiency' => $totals['operating_total'] != 0 ? ($totals['net_change'] / $totals['operating_total']) * 100 : 0,
            'investment_efficiency' => $totals['investing_total'] != 0 ? abs($totals['operating_total'] / $totals['investing_total']) : 0,
            'financing_dependency' => $totals['net_change'] != 0 ? ($totals['financing_total'] / $totals['net_change']) * 100 : 0
        );

        // نسب الهيكل
        $total_inflows = abs($totals['operating_total']) + abs($totals['investing_total']) + abs($totals['financing_total']);
        if ($total_inflows > 0) {
            $ratios['structure'] = array(
                'operating_percentage' => (abs($totals['operating_total']) / $total_inflows) * 100,
                'investing_percentage' => (abs($totals['investing_total']) / $total_inflows) * 100,
                'financing_percentage' => (abs($totals['financing_total']) / $total_inflows) * 100
            );
        }

        return $ratios;
    }

    /**
     * تحليل التدفقات النقدية
     */
    public function analyzeCashFlow($cash_flow_data, $filter_data) {
        $analysis = array();

        // تحليل الأنشطة التشغيلية
        $analysis['operating_analysis'] = $this->analyzeOperatingCashFlow($cash_flow_data['operating_activities'], $cash_flow_data['totals']['operating_total']);

        // تحليل الأنشطة الاستثمارية
        $analysis['investing_analysis'] = $this->analyzeInvestingCashFlow($cash_flow_data['investing_activities'], $cash_flow_data['totals']['investing_total']);

        // تحليل الأنشطة التمويلية
        $analysis['financing_analysis'] = $this->analyzeFinancingCashFlow($cash_flow_data['financing_activities'], $cash_flow_data['totals']['financing_total']);

        // تحليل الوضع النقدي العام
        $analysis['overall_analysis'] = $this->analyzeOverallCashPosition($cash_flow_data);

        // تحليل المخاطر
        $analysis['risk_analysis'] = $this->analyzeCashFlowRisks($cash_flow_data);

        // التوصيات
        $analysis['recommendations'] = $this->generateCashFlowRecommendations($cash_flow_data);

        return $analysis;
    }

    /**
     * تحليل التدفقات التشغيلية
     */
    private function analyzeOperatingCashFlow($operating_activities, $operating_total) {
        $analysis = array();

        // تحليل الهيكل
        $inflows = 0;
        $outflows = 0;

        foreach ($operating_activities as $activity) {
            if ($activity['amount'] > 0) {
                $inflows += $activity['amount'];
            } else {
                $outflows += abs($activity['amount']);
            }
        }

        $analysis['structure'] = array(
            'total_inflows' => $inflows,
            'total_outflows' => $outflows,
            'net_flow' => $operating_total,
            'inflow_percentage' => $inflows + $outflows > 0 ? ($inflows / ($inflows + $outflows)) * 100 : 0
        );

        // تقييم الأداء
        if ($operating_total > 0) {
            if ($operating_total > 100000) {
                $analysis['performance_rating'] = 'excellent';
            } elseif ($operating_total > 50000) {
                $analysis['performance_rating'] = 'good';
            } elseif ($operating_total > 0) {
                $analysis['performance_rating'] = 'average';
            }
        } else {
            $analysis['performance_rating'] = 'poor';
        }

        return $analysis;
    }

    /**
     * تحليل التدفقات الاستثمارية
     */
    private function analyzeInvestingCashFlow($investing_activities, $investing_total) {
        $analysis = array();

        // تحليل نوع الاستثمارات
        $acquisitions = 0;
        $disposals = 0;

        foreach ($investing_activities as $activity) {
            if ($activity['amount'] < 0) {
                $acquisitions += abs($activity['amount']);
            } else {
                $disposals += $activity['amount'];
            }
        }

        $analysis['investment_pattern'] = array(
            'acquisitions' => $acquisitions,
            'disposals' => $disposals,
            'net_investment' => $investing_total,
            'investment_intensity' => $acquisitions > 0 ? 'high' : ($acquisitions > 0 ? 'moderate' : 'low')
        );

        // تقييم استراتيجية الاستثمار
        if ($investing_total < 0) {
            $analysis['strategy'] = 'expansion'; // استثمار في النمو
        } elseif ($investing_total > 0) {
            $analysis['strategy'] = 'divestment'; // بيع الأصول
        } else {
            $analysis['strategy'] = 'maintenance'; // الحفاظ على الوضع الحالي
        }

        return $analysis;
    }

    /**
     * تحليل التدفقات التمويلية
     */
    private function analyzeFinancingCashFlow($financing_activities, $financing_total) {
        $analysis = array();

        // تحليل مصادر التمويل
        $borrowing = 0;
        $repayment = 0;
        $equity_financing = 0;

        foreach ($financing_activities as $activity) {
            if (strpos($activity['account_subtype'], 'debt') !== false) {
                if ($activity['amount'] > 0) {
                    $borrowing += $activity['amount'];
                } else {
                    $repayment += abs($activity['amount']);
                }
            } elseif (strpos($activity['account_subtype'], 'equity') !== false || strpos($activity['account_subtype'], 'capital') !== false) {
                $equity_financing += $activity['amount'];
            }
        }

        $analysis['financing_structure'] = array(
            'borrowing' => $borrowing,
            'repayment' => $repayment,
            'equity_financing' => $equity_financing,
            'net_financing' => $financing_total
        );

        // تقييم استراتيجية التمويل
        if ($financing_total > 0) {
            $analysis['strategy'] = 'raising_capital'; // جمع رأس المال
        } elseif ($financing_total < 0) {
            $analysis['strategy'] = 'returning_capital'; // إرجاع رأس المال
        } else {
            $analysis['strategy'] = 'neutral'; // محايد
        }

        return $analysis;
    }

    /**
     * تحليل الوضع النقدي العام
     */
    private function analyzeOverallCashPosition($cash_flow_data) {
        $analysis = array();
        $totals = $cash_flow_data['totals'];

        // تحليل التوازن
        $analysis['balance_analysis'] = array(
            'operating_strength' => $totals['operating_total'] > 0 ? 'positive' : 'negative',
            'investment_activity' => $totals['investing_total'] != 0 ? 'active' : 'inactive',
            'financing_dependency' => abs($totals['financing_total']) > abs($totals['operating_total']) ? 'high' : 'low'
        );

        // تقييم الصحة النقدية
        $cash_health_score = 0;

        if ($totals['operating_total'] > 0) $cash_health_score += 40;
        if ($totals['net_change'] > 0) $cash_health_score += 30;
        if ($totals['investing_total'] < 0) $cash_health_score += 20; // الاستثمار في النمو
        if (abs($totals['financing_total']) < abs($totals['operating_total'])) $cash_health_score += 10;

        if ($cash_health_score >= 80) {
            $analysis['cash_health'] = 'excellent';
        } elseif ($cash_health_score >= 60) {
            $analysis['cash_health'] = 'good';
        } elseif ($cash_health_score >= 40) {
            $analysis['cash_health'] = 'average';
        } else {
            $analysis['cash_health'] = 'poor';
        }

        $analysis['cash_health_score'] = $cash_health_score;

        return $analysis;
    }

    /**
     * تحليل مخاطر التدفقات النقدية
     */
    private function analyzeCashFlowRisks($cash_flow_data) {
        $risks = array();
        $totals = $cash_flow_data['totals'];

        // مخاطر التدفق التشغيلي السلبي
        if ($totals['operating_total'] < 0) {
            $risks[] = array(
                'type' => 'negative_operating_cash_flow',
                'severity' => 'high',
                'description' => 'التدفق النقدي التشغيلي سلبي',
                'value' => $totals['operating_total']
            );
        }

        // مخاطر الاعتماد المفرط على التمويل الخارجي
        if (abs($totals['financing_total']) > abs($totals['operating_total']) * 2) {
            $risks[] = array(
                'type' => 'high_financing_dependency',
                'severity' => 'medium',
                'description' => 'اعتماد مفرط على التمويل الخارجي',
                'value' => $totals['financing_total']
            );
        }

        // مخاطر انخفاض النقدية
        if ($totals['net_change'] < 0 && abs($totals['net_change']) > $cash_flow_data['opening_cash'] * 0.2) {
            $risks[] = array(
                'type' => 'significant_cash_decline',
                'severity' => 'medium',
                'description' => 'انخفاض كبير في النقدية',
                'value' => $totals['net_change']
            );
        }

        return $risks;
    }

    /**
     * إنشاء توصيات التدفقات النقدية
     */
    private function generateCashFlowRecommendations($cash_flow_data) {
        $recommendations = array();
        $totals = $cash_flow_data['totals'];

        // توصيات التدفق التشغيلي
        if ($totals['operating_total'] < 0) {
            $recommendations[] = array(
                'category' => 'operating_cash_flow',
                'priority' => 'high',
                'recommendation' => 'تحسين التدفق النقدي التشغيلي من خلال تسريع التحصيل وتأخير المدفوعات'
            );
        }

        // توصيات الاستثمار
        if ($totals['investing_total'] > 0 && $totals['operating_total'] < 0) {
            $recommendations[] = array(
                'category' => 'investment',
                'priority' => 'medium',
                'recommendation' => 'إعادة النظر في بيع الأصول عند وجود ضغط نقدي تشغيلي'
            );
        }

        // توصيات التمويل
        if (abs($totals['financing_total']) > abs($totals['operating_total'])) {
            $recommendations[] = array(
                'category' => 'financing',
                'priority' => 'medium',
                'recommendation' => 'تقليل الاعتماد على التمويل الخارجي وتحسين التدفق التشغيلي'
            );
        }

        // توصيات عامة
        if ($totals['net_change'] < 0) {
            $recommendations[] = array(
                'category' => 'cash_management',
                'priority' => 'high',
                'recommendation' => 'وضع خطة شاملة لإدارة النقدية ومراقبة التدفقات بشكل دوري'
            );
        }

        return $recommendations;
    }

    /**
     * التنبؤ بالوضع النقدي
     */
    public function forecastCashPosition($cash_flow_data, $filter_data) {
        $forecast = array();

        // حساب المتوسطات الشهرية
        $period_months = $this->calculatePeriodMonths($filter_data['date_start'], $filter_data['date_end']);

        if ($period_months > 0) {
            $monthly_operating = $cash_flow_data['totals']['operating_total'] / $period_months;
            $monthly_investing = $cash_flow_data['totals']['investing_total'] / $period_months;
            $monthly_financing = $cash_flow_data['totals']['financing_total'] / $period_months;
            $monthly_net_change = $cash_flow_data['totals']['net_change'] / $period_months;

            // التنبؤ للأشهر القادمة
            $current_cash = $cash_flow_data['closing_cash'];

            for ($i = 1; $i <= 6; $i++) {
                $forecasted_cash = $current_cash + ($monthly_net_change * $i);

                $forecast[] = array(
                    'month' => $i,
                    'forecasted_cash' => $forecasted_cash,
                    'forecasted_operating' => $monthly_operating * $i,
                    'forecasted_investing' => $monthly_investing * $i,
                    'forecasted_financing' => $monthly_financing * $i,
                    'risk_level' => $forecasted_cash < 0 ? 'high' : ($forecasted_cash < $cash_flow_data['opening_cash'] * 0.5 ? 'medium' : 'low')
                );
            }
        }

        return $forecast;
    }

    /**
     * تحليل اتجاهات التدفقات النقدية
     */
    public function analyzeCashFlowTrends($cash_flow_data, $filter_data) {
        $trends = array();

        // تحليل الاتجاه العام
        $net_change = $cash_flow_data['totals']['net_change'];
        $opening_cash = $cash_flow_data['opening_cash'];

        if ($opening_cash > 0) {
            $change_percentage = ($net_change / $opening_cash) * 100;

            $trends['overall_trend'] = array(
                'direction' => $net_change > 0 ? 'improving' : ($net_change < 0 ? 'declining' : 'stable'),
                'change_percentage' => $change_percentage,
                'magnitude' => abs($change_percentage) > 20 ? 'significant' : (abs($change_percentage) > 10 ? 'moderate' : 'minor')
            );
        }

        // تحليل اتجاهات الأنشطة
        $trends['activity_trends'] = array(
            'operating' => array(
                'trend' => $cash_flow_data['totals']['operating_total'] > 0 ? 'positive' : 'negative',
                'strength' => abs($cash_flow_data['totals']['operating_total']) > 50000 ? 'strong' : 'weak'
            ),
            'investing' => array(
                'trend' => $cash_flow_data['totals']['investing_total'] < 0 ? 'investing' : 'divesting',
                'activity_level' => abs($cash_flow_data['totals']['investing_total']) > 10000 ? 'high' : 'low'
            ),
            'financing' => array(
                'trend' => $cash_flow_data['totals']['financing_total'] > 0 ? 'raising_capital' : 'returning_capital',
                'dependency' => abs($cash_flow_data['totals']['financing_total']) > abs($cash_flow_data['totals']['operating_total']) ? 'high' : 'low'
            )
        );

        return $trends;
    }

    /**
     * حساب عدد الأشهر في الفترة
     */
    private function calculatePeriodMonths($date_start, $date_end) {
        $start = new DateTime($date_start);
        $end = new DateTime($date_end);
        $interval = $start->diff($end);

        return $interval->m + ($interval->y * 12) + ($interval->d / 30);
    }

    /**
     * الحصول على ملخص التدفقات النقدية
     */
    public function getCashFlowSummary($filter_data) {
        $cash_flow = $this->generateCashFlowStatement($filter_data);

        return array(
            'opening_cash' => $cash_flow['opening_cash'],
            'closing_cash' => $cash_flow['closing_cash'],
            'operating_total' => $cash_flow['totals']['operating_total'],
            'investing_total' => $cash_flow['totals']['investing_total'],
            'financing_total' => $cash_flow['totals']['financing_total'],
            'net_change' => $cash_flow['totals']['net_change'],
            'period_start' => $filter_data['date_start'],
            'period_end' => $filter_data['date_end'],
            'method' => $filter_data['method'],
            'generated_at' => date('Y-m-d H:i:s')
        );
    }
}
