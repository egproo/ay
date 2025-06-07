<?php
/**
 * نموذج الميزانية العمومية المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelAccountsBalanceSheetAdvanced extends Model {

    /**
     * إنشاء الميزانية العمومية المتقدمة
     */
    public function generateBalanceSheet($filter_data) {
        // الحصول على الأصول
        $assets = $this->getAssets($filter_data);

        // الحصول على الخصوم
        $liabilities = $this->getLiabilities($filter_data);

        // الحصول على حقوق الملكية
        $equity = $this->getEquity($filter_data);

        // حساب الإجماليات
        $totals = $this->calculateTotals($assets, $liabilities, $equity);

        // إضافة المقارنة إذا كانت مطلوبة
        $comparative_data = null;
        if ($filter_data['show_comparative'] && !empty($filter_data['comparative_date'])) {
            $comparative_filter = $filter_data;
            $comparative_filter['date_end'] = $filter_data['comparative_date'];
            $comparative_data = $this->generateBalanceSheet($comparative_filter);
        }

        return array(
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totals' => $totals,
            'comparative_data' => $comparative_data,
            'filter_data' => $filter_data,
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->user->getId(),
            'currency' => $filter_data['currency']
        );
    }

    /**
     * الحصول على الأصول
     */
    private function getAssets($filter_data) {
        $sql = "
            SELECT
                a.account_id,
                a.account_code,
                ad.name as account_name,
                a.account_type,
                a.account_subtype,
                a.parent_id,
                COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as balance
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON a.account_id = jel.account_id
            LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE a.account_type = 'asset'
            AND a.is_active = 1
            AND (je.status = 'posted' OR je.status IS NULL)
            AND (je.journal_date <= '" . $this->db->escape($filter_data['date_end']) . "' OR je.journal_date IS NULL)
        ";

        // فلترة الأرصدة الصفرية
        if (!$filter_data['include_zero_balances']) {
            $sql .= " AND COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) != 0";
        }

        $sql .= " GROUP BY a.account_id, a.account_code, ad.name, a.account_type, a.account_subtype, a.parent_id";
        $sql .= " ORDER BY a.account_code";

        $query = $this->db->query($sql);

        $assets = array();
        foreach ($query->rows as $row) {
            $balance = (float)$row['balance'];

            // تجميع حسب النوع الفرعي
            $subtype = $this->getAssetSubtype($row['account_subtype']);

            if (!isset($assets[$subtype])) {
                $assets[$subtype] = array();
            }

            $assets[$subtype][] = array(
                'account_id' => $row['account_id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'account_subtype' => $row['account_subtype'],
                'balance' => $balance,
                'balance_formatted' => $this->currency->format($balance, $filter_data['currency'])
            );
        }

        return $assets;
    }

    /**
     * الحصول على الخصوم
     */
    private function getLiabilities($filter_data) {
        $sql = "
            SELECT
                a.account_id,
                a.account_code,
                ad.name as account_name,
                a.account_type,
                a.account_subtype,
                a.parent_id,
                COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) as balance
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON a.account_id = jel.account_id
            LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE a.account_type = 'liability'
            AND a.is_active = 1
            AND (je.status = 'posted' OR je.status IS NULL)
            AND (je.journal_date <= '" . $this->db->escape($filter_data['date_end']) . "' OR je.journal_date IS NULL)
        ";

        // فلترة الأرصدة الصفرية
        if (!$filter_data['include_zero_balances']) {
            $sql .= " AND COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) != 0";
        }

        $sql .= " GROUP BY a.account_id, a.account_code, ad.name, a.account_type, a.account_subtype, a.parent_id";
        $sql .= " ORDER BY a.account_code";

        $query = $this->db->query($sql);

        $liabilities = array();
        foreach ($query->rows as $row) {
            $balance = (float)$row['balance'];

            // تجميع حسب النوع الفرعي
            $subtype = $this->getLiabilitySubtype($row['account_subtype']);

            if (!isset($liabilities[$subtype])) {
                $liabilities[$subtype] = array();
            }

            $liabilities[$subtype][] = array(
                'account_id' => $row['account_id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'account_subtype' => $row['account_subtype'],
                'balance' => $balance,
                'balance_formatted' => $this->currency->format($balance, $filter_data['currency'])
            );
        }

        return $liabilities;
    }

    /**
     * الحصول على حقوق الملكية
     */
    private function getEquity($filter_data) {
        $sql = "
            SELECT
                a.account_id,
                a.account_code,
                ad.name as account_name,
                a.account_type,
                a.account_subtype,
                a.parent_id,
                COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) as balance
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON a.account_id = jel.account_id
            LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE a.account_type = 'equity'
            AND a.is_active = 1
            AND (je.status = 'posted' OR je.status IS NULL)
            AND (je.journal_date <= '" . $this->db->escape($filter_data['date_end']) . "' OR je.journal_date IS NULL)
        ";

        // فلترة الأرصدة الصفرية
        if (!$filter_data['include_zero_balances']) {
            $sql .= " AND COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) != 0";
        }

        $sql .= " GROUP BY a.account_id, a.account_code, ad.name, a.account_type, a.account_subtype, a.parent_id";
        $sql .= " ORDER BY a.account_code";

        $query = $this->db->query($sql);

        $equity = array();
        foreach ($query->rows as $row) {
            $balance = (float)$row['balance'];

            $equity[] = array(
                'account_id' => $row['account_id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'account_subtype' => $row['account_subtype'],
                'balance' => $balance,
                'balance_formatted' => $this->currency->format($balance, $filter_data['currency'])
            );
        }

        return $equity;
    }

    /**
     * حساب الإجماليات
     */
    private function calculateTotals($assets, $liabilities, $equity) {
        $total_assets = 0;
        $total_liabilities = 0;
        $total_equity = 0;

        // حساب إجمالي الأصول
        foreach ($assets as $asset_group) {
            foreach ($asset_group as $asset) {
                $total_assets += $asset['balance'];
            }
        }

        // حساب إجمالي الخصوم
        foreach ($liabilities as $liability_group) {
            foreach ($liability_group as $liability) {
                $total_liabilities += $liability['balance'];
            }
        }

        // حساب إجمالي حقوق الملكية
        foreach ($equity as $equity_item) {
            $total_equity += $equity_item['balance'];
        }

        return array(
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'total_equity' => $total_equity,
            'total_liabilities_equity' => $total_liabilities + $total_equity,
            'balance_difference' => $total_assets - ($total_liabilities + $total_equity),
            'is_balanced' => abs($total_assets - ($total_liabilities + $total_equity)) < 0.01
        );
    }

    /**
     * تحديد نوع الأصل الفرعي
     */
    private function getAssetSubtype($subtype) {
        $subtypes = array(
            'current_assets' => 'current_assets',
            'cash' => 'current_assets',
            'inventory' => 'current_assets',
            'receivables' => 'current_assets',
            'fixed_assets' => 'non_current_assets',
            'property' => 'non_current_assets',
            'equipment' => 'non_current_assets',
            'intangible' => 'intangible_assets'
        );

        return $subtypes[$subtype] ?? 'current_assets';
    }

    /**
     * تحديد نوع الخصم الفرعي
     */
    private function getLiabilitySubtype($subtype) {
        $subtypes = array(
            'current_liabilities' => 'current_liabilities',
            'payables' => 'current_liabilities',
            'short_term_debt' => 'current_liabilities',
            'long_term_debt' => 'non_current_liabilities',
            'bonds' => 'non_current_liabilities',
            'mortgage' => 'non_current_liabilities'
        );

        return $subtypes[$subtype] ?? 'current_liabilities';
    }

    /**
     * مقارنة الميزانيات العمومية
     */
    public function compareBalanceSheets($period1, $period2) {
        $balance_sheet_1 = $this->generateBalanceSheet($period1);
        $balance_sheet_2 = $this->generateBalanceSheet($period2);

        $comparison = array(
            'period_1' => $period1,
            'period_2' => $period2,
            'period_1_data' => $balance_sheet_1,
            'period_2_data' => $balance_sheet_2
        );

        // حساب الفروقات
        $comparison['variance'] = array(
            'total_assets' => $balance_sheet_2['totals']['total_assets'] - $balance_sheet_1['totals']['total_assets'],
            'total_liabilities' => $balance_sheet_2['totals']['total_liabilities'] - $balance_sheet_1['totals']['total_liabilities'],
            'total_equity' => $balance_sheet_2['totals']['total_equity'] - $balance_sheet_1['totals']['total_equity']
        );

        // حساب النسب المئوية
        $comparison['percentage_change'] = array();
        foreach ($comparison['variance'] as $key => $variance) {
            $base_value = $balance_sheet_1['totals'][$key];
            if ($base_value != 0) {
                $comparison['percentage_change'][$key] = ($variance / abs($base_value)) * 100;
            } else {
                $comparison['percentage_change'][$key] = $variance != 0 ? 100 : 0;
            }
        }

        return $comparison;
    }

    /**
     * حساب النسب المالية
     */
    public function calculateFinancialRatios($balance_sheet_data) {
        $totals = $balance_sheet_data['totals'];

        $ratios = array();

        // نسب السيولة
        $current_assets = $this->getTotalByGroup($balance_sheet_data['assets'], 'current_assets');
        $current_liabilities = $this->getTotalByGroup($balance_sheet_data['liabilities'], 'current_liabilities');

        $ratios['liquidity'] = array(
            'current_ratio' => $current_liabilities > 0 ? $current_assets / $current_liabilities : 0,
            'quick_ratio' => $current_liabilities > 0 ? ($current_assets - $this->getInventoryValue($balance_sheet_data['assets'])) / $current_liabilities : 0
        );

        // نسب الرافعة المالية
        $ratios['leverage'] = array(
            'debt_to_equity' => $totals['total_equity'] > 0 ? $totals['total_liabilities'] / $totals['total_equity'] : 0,
            'debt_to_assets' => $totals['total_assets'] > 0 ? $totals['total_liabilities'] / $totals['total_assets'] : 0,
            'equity_ratio' => $totals['total_assets'] > 0 ? $totals['total_equity'] / $totals['total_assets'] : 0
        );

        // نسب الهيكل المالي
        $ratios['structure'] = array(
            'asset_turnover' => 0, // يحتاج لبيانات المبيعات
            'fixed_asset_ratio' => $totals['total_assets'] > 0 ? $this->getTotalByGroup($balance_sheet_data['assets'], 'non_current_assets') / $totals['total_assets'] : 0,
            'working_capital' => $current_assets - $current_liabilities
        );

        return $ratios;
    }

    /**
     * الحصول على إجمالي مجموعة معينة
     */
    private function getTotalByGroup($data, $group) {
        $total = 0;

        if (isset($data[$group])) {
            foreach ($data[$group] as $item) {
                $total += $item['balance'];
            }
        }

        return $total;
    }

    /**
     * الحصول على قيمة المخزون
     */
    private function getInventoryValue($assets) {
        $inventory_value = 0;

        foreach ($assets as $asset_group) {
            foreach ($asset_group as $asset) {
                if (strpos(strtolower($asset['account_name']), 'مخزون') !== false ||
                    strpos(strtolower($asset['account_name']), 'inventory') !== false) {
                    $inventory_value += $asset['balance'];
                }
            }
        }

        return $inventory_value;
    }

    /**
     * تحليل الميزانية العمومية
     */
    public function analyzeBalanceSheet($balance_sheet_data, $filter_data) {
        $analysis = array();

        // تحليل هيكل الأصول
        $analysis['asset_structure'] = $this->analyzeAssetStructure($balance_sheet_data['assets'], $balance_sheet_data['totals']['total_assets']);

        // تحليل هيكل الخصوم
        $analysis['liability_structure'] = $this->analyzeLiabilityStructure($balance_sheet_data['liabilities'], $balance_sheet_data['totals']['total_liabilities']);

        // تحليل النسب المالية
        $analysis['financial_ratios'] = $this->calculateFinancialRatios($balance_sheet_data);

        // تحليل المخاطر
        $analysis['risk_analysis'] = $this->analyzeFinancialRisks($balance_sheet_data);

        // تحليل الاتجاهات (إذا كانت هناك بيانات مقارنة)
        if ($balance_sheet_data['comparative_data']) {
            $analysis['trend_analysis'] = $this->analyzeTrends($balance_sheet_data, $balance_sheet_data['comparative_data']);
        }

        return $analysis;
    }

    /**
     * تحليل هيكل الأصول
     */
    private function analyzeAssetStructure($assets, $total_assets) {
        $structure = array();

        foreach ($assets as $group_name => $group_assets) {
            $group_total = 0;
            foreach ($group_assets as $asset) {
                $group_total += $asset['balance'];
            }

            $structure[$group_name] = array(
                'total' => $group_total,
                'percentage' => $total_assets > 0 ? ($group_total / $total_assets) * 100 : 0,
                'count' => count($group_assets)
            );
        }

        return $structure;
    }

    /**
     * تحليل هيكل الخصوم
     */
    private function analyzeLiabilityStructure($liabilities, $total_liabilities) {
        $structure = array();

        foreach ($liabilities as $group_name => $group_liabilities) {
            $group_total = 0;
            foreach ($group_liabilities as $liability) {
                $group_total += $liability['balance'];
            }

            $structure[$group_name] = array(
                'total' => $group_total,
                'percentage' => $total_liabilities > 0 ? ($group_total / $total_liabilities) * 100 : 0,
                'count' => count($group_liabilities)
            );
        }

        return $structure;
    }

    /**
     * تحليل المخاطر المالية
     */
    private function analyzeFinancialRisks($balance_sheet_data) {
        $risks = array();
        $totals = $balance_sheet_data['totals'];

        // فحص عدم التوازن
        if (!$totals['is_balanced']) {
            $risks[] = array(
                'type' => 'unbalanced_sheet',
                'severity' => 'high',
                'description' => 'الميزانية العمومية غير متوازنة',
                'value' => $totals['balance_difference']
            );
        }

        // فحص نسبة الديون العالية
        $debt_ratio = $totals['total_assets'] > 0 ? $totals['total_liabilities'] / $totals['total_assets'] : 0;
        if ($debt_ratio > 0.7) {
            $risks[] = array(
                'type' => 'high_debt_ratio',
                'severity' => 'medium',
                'description' => 'نسبة ديون عالية',
                'value' => $debt_ratio * 100
            );
        }

        // فحص السيولة المنخفضة
        $current_assets = $this->getTotalByGroup($balance_sheet_data['assets'], 'current_assets');
        $current_liabilities = $this->getTotalByGroup($balance_sheet_data['liabilities'], 'current_liabilities');
        $current_ratio = $current_liabilities > 0 ? $current_assets / $current_liabilities : 0;

        if ($current_ratio < 1) {
            $risks[] = array(
                'type' => 'low_liquidity',
                'severity' => 'high',
                'description' => 'نسبة سيولة منخفضة',
                'value' => $current_ratio
            );
        }

        return $risks;
    }

    /**
     * تحليل الاتجاهات
     */
    private function analyzeTrends($current_data, $comparative_data) {
        $trends = array();

        $current_totals = $current_data['totals'];
        $comparative_totals = $comparative_data['totals'];

        // اتجاه الأصول
        $asset_change = $current_totals['total_assets'] - $comparative_totals['total_assets'];
        $asset_change_percent = $comparative_totals['total_assets'] > 0 ?
            ($asset_change / $comparative_totals['total_assets']) * 100 : 0;

        $trends['assets'] = array(
            'change' => $asset_change,
            'change_percent' => $asset_change_percent,
            'trend' => $asset_change > 0 ? 'increasing' : ($asset_change < 0 ? 'decreasing' : 'stable')
        );

        // اتجاه الخصوم
        $liability_change = $current_totals['total_liabilities'] - $comparative_totals['total_liabilities'];
        $liability_change_percent = $comparative_totals['total_liabilities'] > 0 ?
            ($liability_change / $comparative_totals['total_liabilities']) * 100 : 0;

        $trends['liabilities'] = array(
            'change' => $liability_change,
            'change_percent' => $liability_change_percent,
            'trend' => $liability_change > 0 ? 'increasing' : ($liability_change < 0 ? 'decreasing' : 'stable')
        );

        // اتجاه حقوق الملكية
        $equity_change = $current_totals['total_equity'] - $comparative_totals['total_equity'];
        $equity_change_percent = $comparative_totals['total_equity'] > 0 ?
            ($equity_change / $comparative_totals['total_equity']) * 100 : 0;

        $trends['equity'] = array(
            'change' => $equity_change,
            'change_percent' => $equity_change_percent,
            'trend' => $equity_change > 0 ? 'increasing' : ($equity_change < 0 ? 'decreasing' : 'stable')
        );

        return $trends;
    }

    /**
     * التحقق من تكامل الميزانية العمومية
     */
    public function validateIntegrity($filter_data) {
        $errors = array();
        $warnings = array();

        // التحقق من توازن الميزانية
        $balance_sheet = $this->generateBalanceSheet($filter_data);
        if (!$balance_sheet['totals']['is_balanced']) {
            $errors[] = 'الميزانية العمومية غير متوازنة - الفرق: ' . $balance_sheet['totals']['balance_difference'];
        }

        // التحقق من الحسابات بدون أرصدة
        $empty_accounts = $this->getEmptyAccounts($filter_data);
        if (!empty($empty_accounts)) {
            $warnings[] = 'توجد حسابات بدون أرصدة: ' . count($empty_accounts);
        }

        // التحقق من الحسابات المعطلة مع أرصدة
        $disabled_accounts_with_balance = $this->getDisabledAccountsWithBalance($filter_data);
        if (!empty($disabled_accounts_with_balance)) {
            $warnings[] = 'حسابات معطلة لها أرصدة: ' . count($disabled_accounts_with_balance);
        }

        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'checks_performed' => array(
                'balance_check' => $balance_sheet['totals']['is_balanced'],
                'empty_accounts_check' => empty($empty_accounts),
                'disabled_accounts_check' => empty($disabled_accounts_with_balance)
            )
        );
    }

    /**
     * الحصول على الحسابات الفارغة
     */
    private function getEmptyAccounts($filter_data) {
        $sql = "
            SELECT a.account_code, ad.name
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON a.account_id = jel.account_id
            LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE a.account_type IN ('asset', 'liability', 'equity')
            AND a.is_active = 1
            AND (je.status = 'posted' OR je.status IS NULL)
            AND (je.journal_date <= '" . $this->db->escape($filter_data['date_end']) . "' OR je.journal_date IS NULL)
            GROUP BY a.account_id, a.account_code, ad.name
            HAVING COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) = 0
        ";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * الحصول على الحسابات المعطلة مع أرصدة
     */
    private function getDisabledAccountsWithBalance($filter_data) {
        $sql = "
            SELECT a.account_code, ad.name, SUM(jel.debit_amount - jel.credit_amount) as balance
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            JOIN " . DB_PREFIX . "journal_entry_line jel ON a.account_id = jel.account_id
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE a.account_type IN ('asset', 'liability', 'equity')
            AND a.is_active = 0
            AND je.status = 'posted'
            AND je.journal_date <= '" . $this->db->escape($filter_data['date_end']) . "'
            GROUP BY a.account_id, a.account_code, ad.name
            HAVING SUM(jel.debit_amount - jel.credit_amount) != 0
        ";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * الحصول على تفاصيل الحساب
     */
    public function getAccountDetails($account_id, $date_end) {
        $sql = "
            SELECT
                a.account_id,
                a.account_code,
                ad.name as account_name,
                a.account_type,
                a.account_subtype,
                COALESCE(SUM(jel.debit_amount), 0) as total_debit,
                COALESCE(SUM(jel.credit_amount), 0) as total_credit,
                COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as balance,
                COUNT(jel.line_id) as transaction_count
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON a.account_id = jel.account_id
            LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE a.account_id = '" . (int)$account_id . "'
            AND (je.status = 'posted' OR je.status IS NULL)
            AND (je.journal_date <= '" . $this->db->escape($date_end) . "' OR je.journal_date IS NULL)
            GROUP BY a.account_id, a.account_code, ad.name, a.account_type, a.account_subtype
        ";

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            $details = $query->row;

            // إضافة التنسيق
            $details['balance_formatted'] = $this->currency->format($details['balance'], $this->config->get('config_currency'));
            $details['total_debit_formatted'] = $this->currency->format($details['total_debit'], $this->config->get('config_currency'));
            $details['total_credit_formatted'] = $this->currency->format($details['total_credit'], $this->config->get('config_currency'));

            return $details;
        }

        return array();
    }

    /**
     * الحصول على ملخص الميزانية العمومية
     */
    public function getBalanceSheetSummary($filter_data) {
        $balance_sheet = $this->generateBalanceSheet($filter_data);

        return array(
            'total_assets' => $balance_sheet['totals']['total_assets'],
            'total_liabilities' => $balance_sheet['totals']['total_liabilities'],
            'total_equity' => $balance_sheet['totals']['total_equity'],
            'is_balanced' => $balance_sheet['totals']['is_balanced'],
            'balance_difference' => $balance_sheet['totals']['balance_difference'],
            'date_end' => $filter_data['date_end'],
            'generated_at' => date('Y-m-d H:i:s')
        );
    }
}
