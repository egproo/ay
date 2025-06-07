<?php
/**
 * نموذج التقارير المالية المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelAccountsFinancialReportsAdvanced extends Model {

    /**
     * إنشاء التقرير المالي المتقدم
     */
    public function generateFinancialReport($filter_data) {
        $report_data = array();

        switch ($filter_data['report_type']) {
            case 'comprehensive':
                $report_data = $this->generateComprehensiveReport($filter_data);
                break;
            case 'income_statement':
                $report_data = $this->generateIncomeStatement($filter_data);
                break;
            case 'balance_sheet':
                $report_data = $this->generateBalanceSheet($filter_data);
                break;
            case 'cash_flow':
                $report_data = $this->generateCashFlowStatement($filter_data);
                break;
            case 'equity_changes':
                $report_data = $this->generateEquityChangesStatement($filter_data);
                break;
            case 'financial_ratios':
                $report_data = $this->generateFinancialRatiosReport($filter_data);
                break;
            case 'performance_analysis':
                $report_data = $this->generatePerformanceAnalysisReport($filter_data);
                break;
            default:
                $report_data = $this->generateComprehensiveReport($filter_data);
        }

        // إضافة بيانات المقارنة إذا طُلبت
        if ($filter_data['comparison_period'] != 'none') {
            $report_data['comparison'] = $this->generateComparisonData($filter_data);
        }

        // إضافة بيانات الموازنة إذا طُلبت
        if ($filter_data['include_budget']) {
            $report_data['budget'] = $this->generateBudgetData($filter_data);
        }

        // إضافة تحليل القطاعات إذا طُلب
        if ($filter_data['segment_analysis']) {
            $report_data['segments'] = $this->generateSegmentData($filter_data);
        }

        return array(
            'data' => $report_data,
            'filter_data' => $filter_data,
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->user->getId(),
            'currency' => $filter_data['currency']
        );
    }

    /**
     * إنشاء التقرير المالي الشامل
     */
    private function generateComprehensiveReport($filter_data) {
        $comprehensive = array();

        // قائمة الدخل
        $comprehensive['income_statement'] = $this->generateIncomeStatement($filter_data);

        // الميزانية العمومية
        $comprehensive['balance_sheet'] = $this->generateBalanceSheet($filter_data);

        // قائمة التدفقات النقدية
        $comprehensive['cash_flow'] = $this->generateCashFlowStatement($filter_data);

        // النسب المالية الرئيسية
        $comprehensive['key_ratios'] = $this->calculateKeyFinancialRatios($filter_data);

        // مؤشرات الأداء الرئيسية
        $comprehensive['kpis'] = $this->calculateKPIs($filter_data);

        // ملخص تنفيذي
        $comprehensive['executive_summary'] = $this->generateExecutiveSummary($comprehensive);

        return $comprehensive;
    }

    /**
     * إنشاء قائمة الدخل
     */
    private function generateIncomeStatement($filter_data) {
        $income_statement = array();

        // الإيرادات
        $revenues = $this->getAccountsData('4', $filter_data['date_start'], $filter_data['date_end']);
        $income_statement['revenues'] = array(
            'sales_revenue' => $this->getAccountBalance('4100', $filter_data['date_start'], $filter_data['date_end']),
            'service_revenue' => $this->getAccountBalance('4200', $filter_data['date_start'], $filter_data['date_end']),
            'other_revenue' => $this->getAccountBalance('4900', $filter_data['date_start'], $filter_data['date_end']),
            'total_revenues' => array_sum($revenues)
        );

        // تكلفة البضاعة المباعة
        $cogs = $this->getAccountsData('5', $filter_data['date_start'], $filter_data['date_end']);
        $income_statement['cost_of_goods_sold'] = array(
            'beginning_inventory' => $this->getInventoryValue($filter_data['date_start'], true),
            'purchases' => $this->getAccountBalance('5100', $filter_data['date_start'], $filter_data['date_end']),
            'ending_inventory' => $this->getInventoryValue($filter_data['date_end'], false),
            'total_cogs' => array_sum($cogs)
        );

        // إجمالي الربح
        $income_statement['gross_profit'] = $income_statement['revenues']['total_revenues'] - $income_statement['cost_of_goods_sold']['total_cogs'];

        // المصروفات التشغيلية
        $operating_expenses = $this->getAccountsData('6', $filter_data['date_start'], $filter_data['date_end']);
        $income_statement['operating_expenses'] = array(
            'selling_expenses' => $this->getAccountBalance('6100', $filter_data['date_start'], $filter_data['date_end']),
            'administrative_expenses' => $this->getAccountBalance('6200', $filter_data['date_start'], $filter_data['date_end']),
            'general_expenses' => $this->getAccountBalance('6300', $filter_data['date_start'], $filter_data['date_end']),
            'total_operating_expenses' => array_sum($operating_expenses)
        );

        // الربح التشغيلي
        $income_statement['operating_profit'] = $income_statement['gross_profit'] - $income_statement['operating_expenses']['total_operating_expenses'];

        // الإيرادات والمصروفات الأخرى
        $other_income = $this->getAccountBalance('7100', $filter_data['date_start'], $filter_data['date_end']);
        $other_expenses = $this->getAccountBalance('7200', $filter_data['date_start'], $filter_data['date_end']);
        $income_statement['other_income_expenses'] = array(
            'other_income' => $other_income,
            'other_expenses' => $other_expenses,
            'net_other' => $other_income - $other_expenses
        );

        // الربح قبل الفوائد والضرائب
        $income_statement['ebit'] = $income_statement['operating_profit'] + $income_statement['other_income_expenses']['net_other'];

        // الفوائد والضرائب
        $interest_expense = $this->getAccountBalance('7300', $filter_data['date_start'], $filter_data['date_end']);
        $tax_expense = $this->getAccountBalance('7400', $filter_data['date_start'], $filter_data['date_end']);

        $income_statement['interest_tax'] = array(
            'interest_expense' => $interest_expense,
            'tax_expense' => $tax_expense,
            'total_interest_tax' => $interest_expense + $tax_expense
        );

        // صافي الربح
        $income_statement['net_profit'] = $income_statement['ebit'] - $income_statement['interest_tax']['total_interest_tax'];

        return $income_statement;
    }

    /**
     * إنشاء الميزانية العمومية
     */
    private function generateBalanceSheet($filter_data) {
        $balance_sheet = array();

        // الأصول المتداولة
        $balance_sheet['current_assets'] = array(
            'cash_and_equivalents' => $this->getAccountBalance('1100', $filter_data['date_end']),
            'accounts_receivable' => $this->getAccountBalance('1200', $filter_data['date_end']),
            'inventory' => $this->getInventoryValue($filter_data['date_end']),
            'prepaid_expenses' => $this->getAccountBalance('1300', $filter_data['date_end']),
            'other_current_assets' => $this->getAccountBalance('1400', $filter_data['date_end'])
        );
        $balance_sheet['current_assets']['total'] = array_sum($balance_sheet['current_assets']);

        // الأصول غير المتداولة
        $balance_sheet['non_current_assets'] = array(
            'property_plant_equipment' => $this->getAccountBalance('1500', $filter_data['date_end']),
            'accumulated_depreciation' => $this->getAccountBalance('1600', $filter_data['date_end']),
            'intangible_assets' => $this->getAccountBalance('1700', $filter_data['date_end']),
            'investments' => $this->getAccountBalance('1800', $filter_data['date_end']),
            'other_non_current_assets' => $this->getAccountBalance('1900', $filter_data['date_end'])
        );
        $balance_sheet['non_current_assets']['net_ppe'] = $balance_sheet['non_current_assets']['property_plant_equipment'] - abs($balance_sheet['non_current_assets']['accumulated_depreciation']);
        $balance_sheet['non_current_assets']['total'] = $balance_sheet['non_current_assets']['net_ppe'] + $balance_sheet['non_current_assets']['intangible_assets'] + $balance_sheet['non_current_assets']['investments'] + $balance_sheet['non_current_assets']['other_non_current_assets'];

        // إجمالي الأصول
        $balance_sheet['total_assets'] = $balance_sheet['current_assets']['total'] + $balance_sheet['non_current_assets']['total'];

        // الخصوم المتداولة
        $balance_sheet['current_liabilities'] = array(
            'accounts_payable' => $this->getAccountBalance('2100', $filter_data['date_end']),
            'short_term_debt' => $this->getAccountBalance('2200', $filter_data['date_end']),
            'accrued_expenses' => $this->getAccountBalance('2300', $filter_data['date_end']),
            'other_current_liabilities' => $this->getAccountBalance('2400', $filter_data['date_end'])
        );
        $balance_sheet['current_liabilities']['total'] = array_sum($balance_sheet['current_liabilities']);

        // الخصوم غير المتداولة
        $balance_sheet['non_current_liabilities'] = array(
            'long_term_debt' => $this->getAccountBalance('2500', $filter_data['date_end']),
            'deferred_tax_liabilities' => $this->getAccountBalance('2600', $filter_data['date_end']),
            'other_non_current_liabilities' => $this->getAccountBalance('2700', $filter_data['date_end'])
        );
        $balance_sheet['non_current_liabilities']['total'] = array_sum($balance_sheet['non_current_liabilities']);

        // إجمالي الخصوم
        $balance_sheet['total_liabilities'] = $balance_sheet['current_liabilities']['total'] + $balance_sheet['non_current_liabilities']['total'];

        // حقوق الملكية
        $balance_sheet['equity'] = array(
            'share_capital' => $this->getAccountBalance('3100', $filter_data['date_end']),
            'retained_earnings' => $this->getAccountBalance('3200', $filter_data['date_end']),
            'other_equity' => $this->getAccountBalance('3300', $filter_data['date_end'])
        );
        $balance_sheet['equity']['total'] = array_sum($balance_sheet['equity']);

        // إجمالي الخصوم وحقوق الملكية
        $balance_sheet['total_liabilities_equity'] = $balance_sheet['total_liabilities'] + $balance_sheet['equity']['total'];

        return $balance_sheet;
    }

    /**
     * إنشاء قائمة التدفقات النقدية
     */
    private function generateCashFlowStatement($filter_data) {
        // استخدام النموذج المتقدم للتدفقات النقدية
        $this->load->model('accounts/cash_flow_advanced');

        $cash_flow_filter = array(
            'date_start' => $filter_data['date_start'],
            'date_end' => $filter_data['date_end'],
            'method' => 'direct',
            'currency' => $filter_data['currency']
        );

        return $this->model_accounts_cash_flow_advanced->generateCashFlowStatement($cash_flow_filter);
    }

    /**
     * إنشاء قائمة التغير في حقوق الملكية
     */
    private function generateEquityChangesStatement($filter_data) {
        $equity_changes = array();

        // رصيد بداية الفترة
        $beginning_balance = $this->getAccountBalance('3', $filter_data['date_start'], null, true);

        // التغيرات خلال الفترة
        $changes = array(
            'net_profit' => $this->getNetProfit($filter_data['date_start'], $filter_data['date_end']),
            'dividends_paid' => $this->getAccountBalance('3400', $filter_data['date_start'], $filter_data['date_end']),
            'capital_contributions' => $this->getAccountBalance('3500', $filter_data['date_start'], $filter_data['date_end']),
            'other_comprehensive_income' => $this->getAccountBalance('3600', $filter_data['date_start'], $filter_data['date_end'])
        );

        // رصيد نهاية الفترة
        $ending_balance = $beginning_balance + $changes['net_profit'] - $changes['dividends_paid'] + $changes['capital_contributions'] + $changes['other_comprehensive_income'];

        $equity_changes = array(
            'beginning_balance' => $beginning_balance,
            'changes' => $changes,
            'ending_balance' => $ending_balance,
            'total_change' => $ending_balance - $beginning_balance
        );

        return $equity_changes;
    }

    /**
     * إنشاء تقرير النسب المالية
     */
    private function generateFinancialRatiosReport($filter_data) {
        return $this->calculateFinancialRatios($filter_data);
    }

    /**
     * إنشاء تقرير تحليل الأداء
     */
    private function generatePerformanceAnalysisReport($filter_data) {
        $performance = array();

        // مؤشرات الربحية
        $performance['profitability'] = $this->calculateProfitabilityMetrics($filter_data);

        // مؤشرات الكفاءة
        $performance['efficiency'] = $this->calculateEfficiencyMetrics($filter_data);

        // مؤشرات النمو
        $performance['growth'] = $this->calculateGrowthMetrics($filter_data);

        // مؤشرات السوق
        $performance['market'] = $this->calculateMarketMetrics($filter_data);

        return $performance;
    }

    /**
     * حساب النسب المالية
     */
    public function calculateFinancialRatios($filter_data) {
        $ratios = array();

        // الحصول على البيانات الأساسية
        $income_statement = $this->generateIncomeStatement($filter_data);
        $balance_sheet = $this->generateBalanceSheet($filter_data);

        // نسب السيولة
        $ratios['liquidity'] = array(
            'current_ratio' => $balance_sheet['current_liabilities']['total'] != 0 ?
                $balance_sheet['current_assets']['total'] / $balance_sheet['current_liabilities']['total'] : 0,
            'quick_ratio' => $balance_sheet['current_liabilities']['total'] != 0 ?
                ($balance_sheet['current_assets']['total'] - $balance_sheet['current_assets']['inventory']) / $balance_sheet['current_liabilities']['total'] : 0,
            'cash_ratio' => $balance_sheet['current_liabilities']['total'] != 0 ?
                $balance_sheet['current_assets']['cash_and_equivalents'] / $balance_sheet['current_liabilities']['total'] : 0
        );

        // نسب الربحية
        $ratios['profitability'] = array(
            'gross_profit_margin' => $income_statement['revenues']['total_revenues'] != 0 ?
                ($income_statement['gross_profit'] / $income_statement['revenues']['total_revenues']) * 100 : 0,
            'operating_profit_margin' => $income_statement['revenues']['total_revenues'] != 0 ?
                ($income_statement['operating_profit'] / $income_statement['revenues']['total_revenues']) * 100 : 0,
            'net_profit_margin' => $income_statement['revenues']['total_revenues'] != 0 ?
                ($income_statement['net_profit'] / $income_statement['revenues']['total_revenues']) * 100 : 0,
            'return_on_assets' => $balance_sheet['total_assets'] != 0 ?
                ($income_statement['net_profit'] / $balance_sheet['total_assets']) * 100 : 0,
            'return_on_equity' => $balance_sheet['equity']['total'] != 0 ?
                ($income_statement['net_profit'] / $balance_sheet['equity']['total']) * 100 : 0
        );

        // نسب النشاط
        $ratios['activity'] = array(
            'asset_turnover' => $balance_sheet['total_assets'] != 0 ?
                $income_statement['revenues']['total_revenues'] / $balance_sheet['total_assets'] : 0,
            'inventory_turnover' => $balance_sheet['current_assets']['inventory'] != 0 ?
                $income_statement['cost_of_goods_sold']['total_cogs'] / $balance_sheet['current_assets']['inventory'] : 0,
            'receivables_turnover' => $balance_sheet['current_assets']['accounts_receivable'] != 0 ?
                $income_statement['revenues']['total_revenues'] / $balance_sheet['current_assets']['accounts_receivable'] : 0
        );

        // نسب الرافعة المالية
        $ratios['leverage'] = array(
            'debt_to_assets' => $balance_sheet['total_assets'] != 0 ?
                ($balance_sheet['total_liabilities'] / $balance_sheet['total_assets']) * 100 : 0,
            'debt_to_equity' => $balance_sheet['equity']['total'] != 0 ?
                ($balance_sheet['total_liabilities'] / $balance_sheet['equity']['total']) * 100 : 0,
            'equity_ratio' => $balance_sheet['total_assets'] != 0 ?
                ($balance_sheet['equity']['total'] / $balance_sheet['total_assets']) * 100 : 0,
            'interest_coverage' => $income_statement['interest_tax']['interest_expense'] != 0 ?
                $income_statement['ebit'] / $income_statement['interest_tax']['interest_expense'] : 0
        );

        return $ratios;
    }

    /**
     * حساب مؤشرات الأداء الرئيسية
     */
    public function calculateKPIs($filter_data) {
        $kpis = array();

        // مؤشرات الإيرادات
        $kpis['revenue'] = array(
            'total_revenue' => $this->getAccountBalance('4', $filter_data['date_start'], $filter_data['date_end']),
            'revenue_growth' => $this->calculateGrowthRate('4', $filter_data),
            'revenue_per_employee' => $this->calculateRevenuePerEmployee($filter_data),
            'average_order_value' => $this->calculateAverageOrderValue($filter_data)
        );

        // مؤشرات الربحية
        $net_profit = $this->getNetProfit($filter_data['date_start'], $filter_data['date_end']);
        $kpis['profitability'] = array(
            'net_profit' => $net_profit,
            'profit_growth' => $this->calculateProfitGrowthRate($filter_data),
            'ebitda' => $this->calculateEBITDA($filter_data),
            'profit_per_employee' => $this->calculateProfitPerEmployee($filter_data)
        );

        // مؤشرات التكلفة
        $kpis['cost'] = array(
            'total_costs' => $this->getTotalCosts($filter_data),
            'cost_of_sales_ratio' => $this->calculateCostOfSalesRatio($filter_data),
            'operating_expense_ratio' => $this->calculateOperatingExpenseRatio($filter_data),
            'cost_per_unit' => $this->calculateCostPerUnit($filter_data)
        );

        // مؤشرات الكفاءة
        $kpis['efficiency'] = array(
            'asset_utilization' => $this->calculateAssetUtilization($filter_data),
            'working_capital_turnover' => $this->calculateWorkingCapitalTurnover($filter_data),
            'cash_conversion_cycle' => $this->calculateCashConversionCycle($filter_data),
            'productivity_index' => $this->calculateProductivityIndex($filter_data)
        );

        return $kpis;
    }

    /**
     * تحليل البيانات المالية
     */
    public function analyzeFinancialData($report_data, $filter_data) {
        $analysis = array();

        // تحليل الاتجاهات
        $analysis['trends'] = $this->analyzeTrends($filter_data);

        // تحليل الأداء
        $analysis['performance'] = $this->analyzePerformance($report_data);

        // تحليل المخاطر
        $analysis['risks'] = $this->analyzeFinancialRisks($report_data);

        // تحليل الفرص
        $analysis['opportunities'] = $this->identifyOpportunities($report_data);

        // التوصيات
        $analysis['recommendations'] = $this->generateRecommendations($report_data);

        return $analysis;
    }

    /**
     * تحليل الاتجاهات
     */
    public function analyzeTrends($filter_data) {
        $trends = array();

        // اتجاهات الإيرادات
        $trends['revenue'] = $this->analyzeRevenueTrends($filter_data);

        // اتجاهات الربحية
        $trends['profitability'] = $this->analyzeProfitabilityTrends($filter_data);

        // اتجاهات التكاليف
        $trends['costs'] = $this->analyzeCostTrends($filter_data);

        // اتجاهات السيولة
        $trends['liquidity'] = $this->analyzeLiquidityTrends($filter_data);

        return $trends;
    }

    /**
     * المقارنة المرجعية
     */
    public function benchmarkAnalysis($filter_data) {
        $benchmark = array();

        // مقارنة مع الصناعة
        $benchmark['industry'] = $this->compareWithIndustry($filter_data);

        // مقارنة مع المنافسين
        $benchmark['competitors'] = $this->compareWithCompetitors($filter_data);

        // مقارنة مع الأهداف
        $benchmark['targets'] = $this->compareWithTargets($filter_data);

        // مقارنة مع الفترات السابقة
        $benchmark['historical'] = $this->compareWithHistorical($filter_data);

        return $benchmark;
    }

    /**
     * تحليل الانحرافات
     */
    public function varianceAnalysis($filter_data) {
        $variance = array();

        // انحرافات الإيرادات
        $variance['revenue'] = $this->analyzeRevenueVariance($filter_data);

        // انحرافات التكاليف
        $variance['costs'] = $this->analyzeCostVariance($filter_data);

        // انحرافات الربحية
        $variance['profitability'] = $this->analyzeProfitabilityVariance($filter_data);

        // انحرافات الموازنة
        $variance['budget'] = $this->analyzeBudgetVariance($filter_data);

        return $variance;
    }

    /**
     * تحليل القطاعات
     */
    public function segmentAnalysis($filter_data) {
        $segments = array();

        // تحليل حسب المنتج
        $segments['product'] = $this->analyzeByProduct($filter_data);

        // تحليل حسب المنطقة الجغرافية
        $segments['geographic'] = $this->analyzeByGeography($filter_data);

        // تحليل حسب العميل
        $segments['customer'] = $this->analyzeByCustomer($filter_data);

        // تحليل حسب القناة
        $segments['channel'] = $this->analyzeByChannel($filter_data);

        return $segments;
    }

    /**
     * الحصول على رصيد الحساب
     */
    private function getAccountBalance($account_code, $date_start, $date_end = null, $beginning_balance = false) {
        if ($beginning_balance) {
            $sql = "
                SELECT COALESCE(SUM(debit - credit), 0) as balance
                FROM " . DB_PREFIX . "journal_entry_line jel
                JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_entry_id = je.journal_entry_id
                JOIN " . DB_PREFIX . "chart_of_accounts coa ON jel.account_id = coa.account_id
                WHERE coa.account_code LIKE '" . $this->db->escape($account_code) . "%'
                AND je.entry_date < '" . $this->db->escape($date_start) . "'
                AND je.status = 'posted'
            ";
        } else {
            $date_condition = $date_end ?
                "AND je.entry_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'" :
                "AND je.entry_date <= '" . $this->db->escape($date_start) . "'";

            $sql = "
                SELECT COALESCE(SUM(debit - credit), 0) as balance
                FROM " . DB_PREFIX . "journal_entry_line jel
                JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_entry_id = je.journal_entry_id
                JOIN " . DB_PREFIX . "chart_of_accounts coa ON jel.account_id = coa.account_id
                WHERE coa.account_code LIKE '" . $this->db->escape($account_code) . "%'
                " . $date_condition . "
                AND je.status = 'posted'
            ";
        }

        $query = $this->db->query($sql);
        return (float)$query->row['balance'];
    }

    /**
     * الحصول على بيانات مجموعة حسابات
     */
    private function getAccountsData($account_group, $date_start, $date_end) {
        $sql = "
            SELECT coa.account_code, coa.account_name,
                   COALESCE(SUM(jel.debit - jel.credit), 0) as balance
            FROM " . DB_PREFIX . "chart_of_accounts coa
            LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON coa.account_id = jel.account_id
            LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_entry_id = je.journal_entry_id
                AND je.entry_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                AND je.status = 'posted'
            WHERE coa.account_code LIKE '" . $this->db->escape($account_group) . "%'
            GROUP BY coa.account_id, coa.account_code, coa.account_name
            ORDER BY coa.account_code
        ";

        $query = $this->db->query($sql);

        $accounts = array();
        foreach ($query->rows as $row) {
            $accounts[$row['account_code']] = (float)$row['balance'];
        }

        return $accounts;
    }

    /**
     * الحصول على قيمة المخزون
     */
    private function getInventoryValue($date, $beginning = false) {
        $condition = $beginning ? "< '" . $this->db->escape($date) . "'" : "<= '" . $this->db->escape($date) . "'";

        $sql = "
            SELECT COALESCE(SUM(quantity * unit_cost), 0) as inventory_value
            FROM " . DB_PREFIX . "inventory_transactions
            WHERE transaction_date " . $condition . "
        ";

        $query = $this->db->query($sql);
        return (float)$query->row['inventory_value'];
    }

    /**
     * الحصول على صافي الربح
     */
    private function getNetProfit($date_start, $date_end) {
        $revenues = $this->getAccountBalance('4', $date_start, $date_end);
        $expenses = $this->getAccountBalance('5', $date_start, $date_end) +
                   $this->getAccountBalance('6', $date_start, $date_end) +
                   $this->getAccountBalance('7', $date_start, $date_end);

        return $revenues - $expenses;
    }

    /**
     * حساب معدل النمو
     */
    private function calculateGrowthRate($account_code, $filter_data) {
        $current_period = $this->getAccountBalance($account_code, $filter_data['date_start'], $filter_data['date_end']);

        // حساب الفترة السابقة
        $period_length = strtotime($filter_data['date_end']) - strtotime($filter_data['date_start']);
        $previous_start = date('Y-m-d', strtotime($filter_data['date_start']) - $period_length);
        $previous_end = date('Y-m-d', strtotime($filter_data['date_end']) - $period_length);

        $previous_period = $this->getAccountBalance($account_code, $previous_start, $previous_end);

        if ($previous_period != 0) {
            return (($current_period - $previous_period) / $previous_period) * 100;
        }

        return 0;
    }

    /**
     * حساب الإيرادات لكل موظف
     */
    private function calculateRevenuePerEmployee($filter_data) {
        $total_revenue = $this->getAccountBalance('4', $filter_data['date_start'], $filter_data['date_end']);
        $employee_count = $this->getEmployeeCount($filter_data['date_end']);

        return $employee_count > 0 ? $total_revenue / $employee_count : 0;
    }

    /**
     * حساب متوسط قيمة الطلب
     */
    private function calculateAverageOrderValue($filter_data) {
        $sql = "
            SELECT AVG(total_amount) as avg_order_value
            FROM " . DB_PREFIX . "orders
            WHERE order_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
            AND status = 'completed'
        ";

        $query = $this->db->query($sql);
        return (float)$query->row['avg_order_value'];
    }

    /**
     * حساب معدل نمو الربح
     */
    private function calculateProfitGrowthRate($filter_data) {
        $current_profit = $this->getNetProfit($filter_data['date_start'], $filter_data['date_end']);

        // حساب الفترة السابقة
        $period_length = strtotime($filter_data['date_end']) - strtotime($filter_data['date_start']);
        $previous_start = date('Y-m-d', strtotime($filter_data['date_start']) - $period_length);
        $previous_end = date('Y-m-d', strtotime($filter_data['date_end']) - $period_length);

        $previous_profit = $this->getNetProfit($previous_start, $previous_end);

        if ($previous_profit != 0) {
            return (($current_profit - $previous_profit) / $previous_profit) * 100;
        }

        return 0;
    }

    /**
     * حساب EBITDA
     */
    private function calculateEBITDA($filter_data) {
        $net_profit = $this->getNetProfit($filter_data['date_start'], $filter_data['date_end']);
        $interest = $this->getAccountBalance('7300', $filter_data['date_start'], $filter_data['date_end']);
        $tax = $this->getAccountBalance('7400', $filter_data['date_start'], $filter_data['date_end']);
        $depreciation = $this->getAccountBalance('6400', $filter_data['date_start'], $filter_data['date_end']);
        $amortization = $this->getAccountBalance('6500', $filter_data['date_start'], $filter_data['date_end']);

        return $net_profit + $interest + $tax + $depreciation + $amortization;
    }

    /**
     * حساب الربح لكل موظف
     */
    private function calculateProfitPerEmployee($filter_data) {
        $net_profit = $this->getNetProfit($filter_data['date_start'], $filter_data['date_end']);
        $employee_count = $this->getEmployeeCount($filter_data['date_end']);

        return $employee_count > 0 ? $net_profit / $employee_count : 0;
    }

    /**
     * الحصول على إجمالي التكاليف
     */
    private function getTotalCosts($filter_data) {
        return $this->getAccountBalance('5', $filter_data['date_start'], $filter_data['date_end']) +
               $this->getAccountBalance('6', $filter_data['date_start'], $filter_data['date_end']);
    }

    /**
     * حساب نسبة تكلفة المبيعات
     */
    private function calculateCostOfSalesRatio($filter_data) {
        $cogs = $this->getAccountBalance('5', $filter_data['date_start'], $filter_data['date_end']);
        $revenue = $this->getAccountBalance('4', $filter_data['date_start'], $filter_data['date_end']);

        return $revenue > 0 ? ($cogs / $revenue) * 100 : 0;
    }

    /**
     * حساب نسبة المصروفات التشغيلية
     */
    private function calculateOperatingExpenseRatio($filter_data) {
        $operating_expenses = $this->getAccountBalance('6', $filter_data['date_start'], $filter_data['date_end']);
        $revenue = $this->getAccountBalance('4', $filter_data['date_start'], $filter_data['date_end']);

        return $revenue > 0 ? ($operating_expenses / $revenue) * 100 : 0;
    }

    /**
     * حساب التكلفة لكل وحدة
     */
    private function calculateCostPerUnit($filter_data) {
        $total_costs = $this->getTotalCosts($filter_data);
        $units_sold = $this->getUnitsSold($filter_data);

        return $units_sold > 0 ? $total_costs / $units_sold : 0;
    }

    /**
     * حساب استغلال الأصول
     */
    private function calculateAssetUtilization($filter_data) {
        $revenue = $this->getAccountBalance('4', $filter_data['date_start'], $filter_data['date_end']);
        $total_assets = $this->getAccountBalance('1', $filter_data['date_end']);

        return $total_assets > 0 ? $revenue / $total_assets : 0;
    }

    /**
     * حساب دوران رأس المال العامل
     */
    private function calculateWorkingCapitalTurnover($filter_data) {
        $revenue = $this->getAccountBalance('4', $filter_data['date_start'], $filter_data['date_end']);
        $current_assets = $this->getAccountBalance('11', $filter_data['date_end']) +
                         $this->getAccountBalance('12', $filter_data['date_end']) +
                         $this->getAccountBalance('13', $filter_data['date_end']);
        $current_liabilities = $this->getAccountBalance('21', $filter_data['date_end']) +
                              $this->getAccountBalance('22', $filter_data['date_end']);

        $working_capital = $current_assets - $current_liabilities;

        return $working_capital > 0 ? $revenue / $working_capital : 0;
    }

    /**
     * حساب دورة تحويل النقد
     */
    private function calculateCashConversionCycle($filter_data) {
        // أيام المخزون
        $inventory = $this->getInventoryValue($filter_data['date_end']);
        $cogs = $this->getAccountBalance('5', $filter_data['date_start'], $filter_data['date_end']);
        $days_in_period = (strtotime($filter_data['date_end']) - strtotime($filter_data['date_start'])) / (60 * 60 * 24);
        $inventory_days = $cogs > 0 ? ($inventory / $cogs) * $days_in_period : 0;

        // أيام المدينين
        $receivables = $this->getAccountBalance('12', $filter_data['date_end']);
        $revenue = $this->getAccountBalance('4', $filter_data['date_start'], $filter_data['date_end']);
        $receivables_days = $revenue > 0 ? ($receivables / $revenue) * $days_in_period : 0;

        // أيام الدائنين
        $payables = $this->getAccountBalance('21', $filter_data['date_end']);
        $payables_days = $cogs > 0 ? ($payables / $cogs) * $days_in_period : 0;

        return $inventory_days + $receivables_days - $payables_days;
    }

    /**
     * حساب مؤشر الإنتاجية
     */
    private function calculateProductivityIndex($filter_data) {
        $revenue = $this->getAccountBalance('4', $filter_data['date_start'], $filter_data['date_end']);
        $total_costs = $this->getTotalCosts($filter_data);

        return $total_costs > 0 ? $revenue / $total_costs : 0;
    }

    /**
     * الحصول على عدد الموظفين
     */
    private function getEmployeeCount($date) {
        $sql = "
            SELECT COUNT(*) as employee_count
            FROM " . DB_PREFIX . "employees
            WHERE status = 'active'
            AND hire_date <= '" . $this->db->escape($date) . "'
            AND (termination_date IS NULL OR termination_date > '" . $this->db->escape($date) . "')
        ";

        $query = $this->db->query($sql);
        return (int)$query->row['employee_count'];
    }

    /**
     * الحصول على الوحدات المباعة
     */
    private function getUnitsSold($filter_data) {
        $sql = "
            SELECT SUM(quantity) as units_sold
            FROM " . DB_PREFIX . "order_items oi
            JOIN " . DB_PREFIX . "orders o ON oi.order_id = o.order_id
            WHERE o.order_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
            AND o.status = 'completed'
        ";

        $query = $this->db->query($sql);
        return (int)$query->row['units_sold'];
    }

    /**
     * إنشاء الملخص التنفيذي
     */
    private function generateExecutiveSummary($comprehensive_data) {
        $summary = array();

        // أداء الإيرادات
        $revenue = $comprehensive_data['income_statement']['revenues']['total_revenues'];
        $summary['revenue_performance'] = array(
            'total_revenue' => $revenue,
            'revenue_status' => $revenue > 0 ? 'positive' : 'negative'
        );

        // أداء الربحية
        $net_profit = $comprehensive_data['income_statement']['net_profit'];
        $summary['profitability_performance'] = array(
            'net_profit' => $net_profit,
            'profit_margin' => $revenue > 0 ? ($net_profit / $revenue) * 100 : 0,
            'profitability_status' => $net_profit > 0 ? 'profitable' : 'loss'
        );

        // الوضع المالي
        $total_assets = $comprehensive_data['balance_sheet']['total_assets'];
        $total_liabilities = $comprehensive_data['balance_sheet']['total_liabilities'];
        $equity = $comprehensive_data['balance_sheet']['equity']['total'];

        $summary['financial_position'] = array(
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'equity' => $equity,
            'debt_to_equity' => $equity > 0 ? ($total_liabilities / $equity) * 100 : 0,
            'financial_health' => $this->assessFinancialHealth($comprehensive_data)
        );

        // النسب الرئيسية
        $summary['key_metrics'] = array(
            'current_ratio' => $comprehensive_data['key_ratios']['liquidity']['current_ratio'],
            'gross_margin' => $comprehensive_data['key_ratios']['profitability']['gross_profit_margin'],
            'roe' => $comprehensive_data['key_ratios']['profitability']['return_on_equity']
        );

        return $summary;
    }

    /**
     * تقييم الصحة المالية
     */
    private function assessFinancialHealth($comprehensive_data) {
        $score = 0;

        // نقاط الربحية
        if ($comprehensive_data['income_statement']['net_profit'] > 0) $score += 25;

        // نقاط السيولة
        if ($comprehensive_data['key_ratios']['liquidity']['current_ratio'] >= 1.5) $score += 25;

        // نقاط الرافعة المالية
        if ($comprehensive_data['key_ratios']['leverage']['debt_to_equity'] <= 50) $score += 25;

        // نقاط الكفاءة
        if ($comprehensive_data['key_ratios']['activity']['asset_turnover'] >= 1) $score += 25;

        if ($score >= 75) return 'excellent';
        if ($score >= 50) return 'good';
        if ($score >= 25) return 'fair';
        return 'poor';
    }

    /**
     * الحصول على ملخص التقرير المالي
     */
    public function getFinancialReportSummary($filter_data) {
        $report = $this->generateFinancialReport($filter_data);

        return array(
            'report_type' => $filter_data['report_type'],
            'period_start' => $filter_data['date_start'],
            'period_end' => $filter_data['date_end'],
            'currency' => $filter_data['currency'],
            'generated_at' => date('Y-m-d H:i:s'),
            'summary' => $report['data']['executive_summary'] ?? array()
        );
    }
}
