<?php
/**
 * نموذج قائمة الدخل المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelAccountsIncomeStatementAdvanced extends Model {

    /**
     * إنشاء قائمة الدخل المتقدمة
     */
    public function generateIncomeStatement($filter_data) {
        // الحصول على الإيرادات
        $revenues = $this->getRevenues($filter_data);

        // الحصول على المصروفات
        $expenses = $this->getExpenses($filter_data);

        // حساب الإجماليات والأرباح
        $totals = $this->calculateTotals($revenues, $expenses);

        // إضافة المقارنة إذا كانت مطلوبة
        $comparative_data = null;
        if ($filter_data['show_comparative'] && !empty($filter_data['comparative_date_start']) && !empty($filter_data['comparative_date_end'])) {
            $comparative_filter = $filter_data;
            $comparative_filter['date_start'] = $filter_data['comparative_date_start'];
            $comparative_filter['date_end'] = $filter_data['comparative_date_end'];
            $comparative_data = $this->generateIncomeStatement($comparative_filter);
        }

        return array(
            'revenues' => $revenues,
            'expenses' => $expenses,
            'totals' => $totals,
            'comparative_data' => $comparative_data,
            'filter_data' => $filter_data,
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->user->getId(),
            'currency' => $filter_data['currency']
        );
    }

    /**
     * الحصول على الإيرادات
     */
    private function getRevenues($filter_data) {
        $sql = "
            SELECT
                a.account_id,
                a.account_code,
                ad.name as account_name,
                a.account_type,
                a.account_subtype,
                a.parent_id,
                COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) as amount
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON a.account_id = jel.account_id
            LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE a.account_type = 'revenue'
            AND a.is_active = 1
            AND je.status = 'posted'
            AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
        ";

        // فلترة الأرصدة الصفرية
        if (!$filter_data['include_zero_balances']) {
            $sql .= " AND COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) != 0";
        }

        $sql .= " GROUP BY a.account_id, a.account_code, ad.name, a.account_type, a.account_subtype, a.parent_id";
        $sql .= " ORDER BY a.account_code";

        $query = $this->db->query($sql);

        $revenues = array();
        foreach ($query->rows as $row) {
            $amount = (float)$row['amount'];

            // تجميع حسب النوع الفرعي
            $subtype = $this->getRevenueSubtype($row['account_subtype']);

            if (!isset($revenues[$subtype])) {
                $revenues[$subtype] = array();
            }

            $revenues[$subtype][] = array(
                'account_id' => $row['account_id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'account_subtype' => $row['account_subtype'],
                'amount' => $amount,
                'amount_formatted' => $this->currency->format($amount, $filter_data['currency'])
            );
        }

        return $revenues;
    }

    /**
     * الحصول على المصروفات
     */
    private function getExpenses($filter_data) {
        $sql = "
            SELECT
                a.account_id,
                a.account_code,
                ad.name as account_name,
                a.account_type,
                a.account_subtype,
                a.parent_id,
                COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as amount
            FROM " . DB_PREFIX . "accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON a.account_id = jel.account_id
            LEFT JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
            WHERE a.account_type = 'expense'
            AND a.is_active = 1
            AND je.status = 'posted'
            AND je.journal_date BETWEEN '" . $this->db->escape($filter_data['date_start']) . "'
            AND '" . $this->db->escape($filter_data['date_end']) . "'
        ";

        // فلترة الأرصدة الصفرية
        if (!$filter_data['include_zero_balances']) {
            $sql .= " AND COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) != 0";
        }

        $sql .= " GROUP BY a.account_id, a.account_code, ad.name, a.account_type, a.account_subtype, a.parent_id";
        $sql .= " ORDER BY a.account_code";

        $query = $this->db->query($sql);

        $expenses = array();
        foreach ($query->rows as $row) {
            $amount = (float)$row['amount'];

            // تجميع حسب النوع الفرعي
            $subtype = $this->getExpenseSubtype($row['account_subtype']);

            if (!isset($expenses[$subtype])) {
                $expenses[$subtype] = array();
            }

            $expenses[$subtype][] = array(
                'account_id' => $row['account_id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'account_subtype' => $row['account_subtype'],
                'amount' => $amount,
                'amount_formatted' => $this->currency->format($amount, $filter_data['currency'])
            );
        }

        return $expenses;
    }

    /**
     * حساب الإجماليات والأرباح
     */
    private function calculateTotals($revenues, $expenses) {
        $total_revenues = 0;
        $total_expenses = 0;

        // حساب إجمالي الإيرادات
        foreach ($revenues as $revenue_group) {
            foreach ($revenue_group as $revenue) {
                $total_revenues += $revenue['amount'];
            }
        }

        // حساب إجمالي المصروفات
        foreach ($expenses as $expense_group) {
            foreach ($expense_group as $expense) {
                $total_expenses += $expense['amount'];
            }
        }

        // حساب الأرباح المختلفة
        $gross_profit = $total_revenues - $this->getTotalBySubtype($expenses, 'cost_of_goods_sold');
        $operating_profit = $gross_profit - $this->getTotalBySubtype($expenses, 'operating_expenses');
        $net_profit = $total_revenues - $total_expenses;

        return array(
            'total_revenues' => $total_revenues,
            'total_expenses' => $total_expenses,
            'gross_profit' => $gross_profit,
            'operating_profit' => $operating_profit,
            'net_profit' => $net_profit,
            'profit_margin' => $total_revenues > 0 ? ($net_profit / $total_revenues) * 100 : 0,
            'gross_margin' => $total_revenues > 0 ? ($gross_profit / $total_revenues) * 100 : 0,
            'operating_margin' => $total_revenues > 0 ? ($operating_profit / $total_revenues) * 100 : 0
        );
    }

    /**
     * تحديد نوع الإيراد الفرعي
     */
    private function getRevenueSubtype($subtype) {
        $subtypes = array(
            'sales_revenue' => 'sales_revenue',
            'service_revenue' => 'service_revenue',
            'other_revenue' => 'other_revenue',
            'interest_income' => 'other_revenue',
            'rental_income' => 'other_revenue'
        );

        return $subtypes[$subtype] ?? 'sales_revenue';
    }

    /**
     * تحديد نوع المصروف الفرعي
     */
    private function getExpenseSubtype($subtype) {
        $subtypes = array(
            'cost_of_goods_sold' => 'cost_of_goods_sold',
            'operating_expenses' => 'operating_expenses',
            'administrative_expenses' => 'operating_expenses',
            'selling_expenses' => 'operating_expenses',
            'financial_expenses' => 'financial_expenses',
            'other_expenses' => 'other_expenses'
        );

        return $subtypes[$subtype] ?? 'operating_expenses';
    }

    /**
     * الحصول على إجمالي نوع فرعي معين
     */
    private function getTotalBySubtype($data, $target_subtype) {
        $total = 0;

        if (isset($data[$target_subtype])) {
            foreach ($data[$target_subtype] as $item) {
                $total += $item['amount'];
            }
        }

        return $total;
    }

    /**
     * مقارنة قوائم الدخل بين فترتين
     */
    public function compareIncomeStatements($period1, $period2) {
        $income_statement_1 = $this->generateIncomeStatement($period1);
        $income_statement_2 = $this->generateIncomeStatement($period2);

        $comparison = array(
            'period_1' => $period1,
            'period_2' => $period2,
            'period_1_data' => $income_statement_1,
            'period_2_data' => $income_statement_2
        );

        // حساب الفروقات
        $comparison['variance'] = array(
            'total_revenues' => $income_statement_2['totals']['total_revenues'] - $income_statement_1['totals']['total_revenues'],
            'total_expenses' => $income_statement_2['totals']['total_expenses'] - $income_statement_1['totals']['total_expenses'],
            'gross_profit' => $income_statement_2['totals']['gross_profit'] - $income_statement_1['totals']['gross_profit'],
            'operating_profit' => $income_statement_2['totals']['operating_profit'] - $income_statement_1['totals']['operating_profit'],
            'net_profit' => $income_statement_2['totals']['net_profit'] - $income_statement_1['totals']['net_profit']
        );

        // حساب النسب المئوية
        $comparison['percentage_change'] = array();
        foreach ($comparison['variance'] as $key => $variance) {
            $base_value = $income_statement_1['totals'][$key];
            if ($base_value != 0) {
                $comparison['percentage_change'][$key] = ($variance / abs($base_value)) * 100;
            } else {
                $comparison['percentage_change'][$key] = $variance != 0 ? 100 : 0;
            }
        }

        return $comparison;
    }

    /**
     * حساب نسب الربحية
     */
    public function calculateProfitabilityRatios($income_statement_data) {
        $totals = $income_statement_data['totals'];

        $ratios = array();

        // نسب الربحية الأساسية
        $ratios['basic'] = array(
            'gross_margin' => $totals['gross_margin'],
            'operating_margin' => $totals['operating_margin'],
            'net_margin' => $totals['profit_margin']
        );

        // نسب الكفاءة
        $ratios['efficiency'] = array(
            'expense_ratio' => $totals['total_revenues'] > 0 ? ($totals['total_expenses'] / $totals['total_revenues']) * 100 : 0,
            'cost_of_sales_ratio' => $totals['total_revenues'] > 0 ? ($this->getTotalBySubtype($income_statement_data['expenses'], 'cost_of_goods_sold') / $totals['total_revenues']) * 100 : 0,
            'operating_expense_ratio' => $totals['total_revenues'] > 0 ? ($this->getTotalBySubtype($income_statement_data['expenses'], 'operating_expenses') / $totals['total_revenues']) * 100 : 0
        );

        // نسب النمو (تحتاج لبيانات مقارنة)
        if (isset($income_statement_data['comparative_data'])) {
            $comparative_totals = $income_statement_data['comparative_data']['totals'];

            $ratios['growth'] = array(
                'revenue_growth' => $comparative_totals['total_revenues'] > 0 ?
                    (($totals['total_revenues'] - $comparative_totals['total_revenues']) / $comparative_totals['total_revenues']) * 100 : 0,
                'profit_growth' => $comparative_totals['net_profit'] > 0 ?
                    (($totals['net_profit'] - $comparative_totals['net_profit']) / abs($comparative_totals['net_profit'])) * 100 : 0
            );
        }

        return $ratios;
    }

    /**
     * تحليل الإيرادات
     */
    public function analyzeRevenues($income_statement_data, $filter_data) {
        $revenues = $income_statement_data['revenues'];
        $total_revenues = $income_statement_data['totals']['total_revenues'];

        $analysis = array();

        // تحليل هيكل الإيرادات
        $analysis['structure'] = array();
        foreach ($revenues as $group_name => $group_revenues) {
            $group_total = 0;
            foreach ($group_revenues as $revenue) {
                $group_total += $revenue['amount'];
            }

            $analysis['structure'][$group_name] = array(
                'total' => $group_total,
                'percentage' => $total_revenues > 0 ? ($group_total / $total_revenues) * 100 : 0,
                'count' => count($group_revenues)
            );
        }

        // تحليل أكبر مصادر الإيرادات
        $all_revenues = array();
        foreach ($revenues as $group_revenues) {
            foreach ($group_revenues as $revenue) {
                $all_revenues[] = $revenue;
            }
        }

        // ترتيب حسب المبلغ
        usort($all_revenues, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        $analysis['top_revenues'] = array_slice($all_revenues, 0, 10);

        // تحليل الاتجاهات (إذا كانت هناك بيانات مقارنة)
        if (isset($income_statement_data['comparative_data'])) {
            $comparative_revenues = $income_statement_data['comparative_data']['totals']['total_revenues'];
            $revenue_change = $total_revenues - $comparative_revenues;
            $revenue_change_percent = $comparative_revenues > 0 ? ($revenue_change / $comparative_revenues) * 100 : 0;

            $analysis['trend'] = array(
                'change' => $revenue_change,
                'change_percent' => $revenue_change_percent,
                'trend' => $revenue_change > 0 ? 'increasing' : ($revenue_change < 0 ? 'decreasing' : 'stable')
            );
        }

        return $analysis;
    }

    /**
     * تحليل المصروفات
     */
    public function analyzeExpenses($income_statement_data, $filter_data) {
        $expenses = $income_statement_data['expenses'];
        $total_expenses = $income_statement_data['totals']['total_expenses'];
        $total_revenues = $income_statement_data['totals']['total_revenues'];

        $analysis = array();

        // تحليل هيكل المصروفات
        $analysis['structure'] = array();
        foreach ($expenses as $group_name => $group_expenses) {
            $group_total = 0;
            foreach ($group_expenses as $expense) {
                $group_total += $expense['amount'];
            }

            $analysis['structure'][$group_name] = array(
                'total' => $group_total,
                'percentage_of_expenses' => $total_expenses > 0 ? ($group_total / $total_expenses) * 100 : 0,
                'percentage_of_revenues' => $total_revenues > 0 ? ($group_total / $total_revenues) * 100 : 0,
                'count' => count($group_expenses)
            );
        }

        // تحليل أكبر المصروفات
        $all_expenses = array();
        foreach ($expenses as $group_expenses) {
            foreach ($group_expenses as $expense) {
                $all_expenses[] = $expense;
            }
        }

        // ترتيب حسب المبلغ
        usort($all_expenses, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        $analysis['top_expenses'] = array_slice($all_expenses, 0, 10);

        // تحليل كفاءة المصروفات
        $analysis['efficiency'] = array(
            'expense_to_revenue_ratio' => $total_revenues > 0 ? ($total_expenses / $total_revenues) * 100 : 0,
            'cost_control_rating' => $this->calculateCostControlRating($total_expenses, $total_revenues)
        );

        // تحليل الاتجاهات (إذا كانت هناك بيانات مقارنة)
        if (isset($income_statement_data['comparative_data'])) {
            $comparative_expenses = $income_statement_data['comparative_data']['totals']['total_expenses'];
            $expense_change = $total_expenses - $comparative_expenses;
            $expense_change_percent = $comparative_expenses > 0 ? ($expense_change / $comparative_expenses) * 100 : 0;

            $analysis['trend'] = array(
                'change' => $expense_change,
                'change_percent' => $expense_change_percent,
                'trend' => $expense_change > 0 ? 'increasing' : ($expense_change < 0 ? 'decreasing' : 'stable')
            );
        }

        return $analysis;
    }

    /**
     * تحليل شامل لقائمة الدخل
     */
    public function analyzeIncomeStatement($income_statement_data, $filter_data) {
        $analysis = array();

        // تحليل الربحية
        $analysis['profitability'] = $this->calculateProfitabilityRatios($income_statement_data);

        // تحليل الإيرادات
        $analysis['revenue_analysis'] = $this->analyzeRevenues($income_statement_data, $filter_data);

        // تحليل المصروفات
        $analysis['expense_analysis'] = $this->analyzeExpenses($income_statement_data, $filter_data);

        // تحليل الأداء العام
        $analysis['performance'] = $this->analyzePerformance($income_statement_data);

        // تحليل المخاطر
        $analysis['risk_analysis'] = $this->analyzeFinancialRisks($income_statement_data);

        // توصيات
        $analysis['recommendations'] = $this->generateRecommendations($income_statement_data);

        return $analysis;
    }

    /**
     * تحليل الأداء العام
     */
    private function analyzePerformance($income_statement_data) {
        $totals = $income_statement_data['totals'];

        $performance = array();

        // تقييم الربحية
        if ($totals['net_profit'] > 0) {
            if ($totals['profit_margin'] > 20) {
                $performance['profitability_rating'] = 'excellent';
            } elseif ($totals['profit_margin'] > 10) {
                $performance['profitability_rating'] = 'good';
            } elseif ($totals['profit_margin'] > 5) {
                $performance['profitability_rating'] = 'average';
            } else {
                $performance['profitability_rating'] = 'poor';
            }
        } else {
            $performance['profitability_rating'] = 'loss';
        }

        // تقييم كفاءة التشغيل
        $expense_ratio = $totals['total_revenues'] > 0 ? ($totals['total_expenses'] / $totals['total_revenues']) * 100 : 0;
        if ($expense_ratio < 70) {
            $performance['efficiency_rating'] = 'excellent';
        } elseif ($expense_ratio < 80) {
            $performance['efficiency_rating'] = 'good';
        } elseif ($expense_ratio < 90) {
            $performance['efficiency_rating'] = 'average';
        } else {
            $performance['efficiency_rating'] = 'poor';
        }

        // تقييم النمو (إذا كانت هناك بيانات مقارنة)
        if (isset($income_statement_data['comparative_data'])) {
            $comparative_totals = $income_statement_data['comparative_data']['totals'];
            $revenue_growth = $comparative_totals['total_revenues'] > 0 ?
                (($totals['total_revenues'] - $comparative_totals['total_revenues']) / $comparative_totals['total_revenues']) * 100 : 0;

            if ($revenue_growth > 15) {
                $performance['growth_rating'] = 'excellent';
            } elseif ($revenue_growth > 10) {
                $performance['growth_rating'] = 'good';
            } elseif ($revenue_growth > 5) {
                $performance['growth_rating'] = 'average';
            } elseif ($revenue_growth > 0) {
                $performance['growth_rating'] = 'slow';
            } else {
                $performance['growth_rating'] = 'declining';
            }
        }

        return $performance;
    }

    /**
     * تحليل المخاطر المالية
     */
    private function analyzeFinancialRisks($income_statement_data) {
        $risks = array();
        $totals = $income_statement_data['totals'];

        // فحص الخسائر
        if ($totals['net_profit'] < 0) {
            $risks[] = array(
                'type' => 'net_loss',
                'severity' => 'high',
                'description' => 'الشركة تحقق خسائر صافية',
                'value' => $totals['net_profit']
            );
        }

        // فحص انخفاض الهامش الإجمالي
        if ($totals['gross_margin'] < 20) {
            $risks[] = array(
                'type' => 'low_gross_margin',
                'severity' => 'medium',
                'description' => 'هامش ربح إجمالي منخفض',
                'value' => $totals['gross_margin']
            );
        }

        // فحص ارتفاع نسبة المصروفات
        $expense_ratio = $totals['total_revenues'] > 0 ? ($totals['total_expenses'] / $totals['total_revenues']) * 100 : 0;
        if ($expense_ratio > 90) {
            $risks[] = array(
                'type' => 'high_expense_ratio',
                'severity' => 'high',
                'description' => 'نسبة مصروفات عالية جداً',
                'value' => $expense_ratio
            );
        }

        // فحص انخفاض الإيرادات (إذا كانت هناك بيانات مقارنة)
        if (isset($income_statement_data['comparative_data'])) {
            $comparative_totals = $income_statement_data['comparative_data']['totals'];
            $revenue_change = $totals['total_revenues'] - $comparative_totals['total_revenues'];

            if ($revenue_change < 0) {
                $risks[] = array(
                    'type' => 'declining_revenues',
                    'severity' => 'medium',
                    'description' => 'انخفاض في الإيرادات مقارنة بالفترة السابقة',
                    'value' => $revenue_change
                );
            }
        }

        return $risks;
    }

    /**
     * إنشاء التوصيات
     */
    private function generateRecommendations($income_statement_data) {
        $recommendations = array();
        $totals = $income_statement_data['totals'];

        // توصيات الربحية
        if ($totals['net_profit'] < 0) {
            $recommendations[] = array(
                'category' => 'profitability',
                'priority' => 'high',
                'recommendation' => 'مراجعة شاملة للتكاليف وتحسين الإيرادات لتحقيق الربحية'
            );
        } elseif ($totals['profit_margin'] < 10) {
            $recommendations[] = array(
                'category' => 'profitability',
                'priority' => 'medium',
                'recommendation' => 'تحسين هامش الربح من خلال تحسين التسعير أو تقليل التكاليف'
            );
        }

        // توصيات التكاليف
        $expense_ratio = $totals['total_revenues'] > 0 ? ($totals['total_expenses'] / $totals['total_revenues']) * 100 : 0;
        if ($expense_ratio > 80) {
            $recommendations[] = array(
                'category' => 'cost_control',
                'priority' => 'high',
                'recommendation' => 'تطبيق برنامج شامل لتقليل التكاليف والمصروفات'
            );
        }

        // توصيات الإيرادات
        if (isset($income_statement_data['comparative_data'])) {
            $comparative_totals = $income_statement_data['comparative_data']['totals'];
            $revenue_growth = $comparative_totals['total_revenues'] > 0 ?
                (($totals['total_revenues'] - $comparative_totals['total_revenues']) / $comparative_totals['total_revenues']) * 100 : 0;

            if ($revenue_growth < 5) {
                $recommendations[] = array(
                    'category' => 'revenue_growth',
                    'priority' => 'medium',
                    'recommendation' => 'تطوير استراتيجيات جديدة لزيادة الإيرادات والنمو'
                );
            }
        }

        return $recommendations;
    }

    /**
     * حساب تقييم السيطرة على التكاليف
     */
    private function calculateCostControlRating($total_expenses, $total_revenues) {
        if ($total_revenues == 0) return 'no_data';

        $expense_ratio = ($total_expenses / $total_revenues) * 100;

        if ($expense_ratio < 60) return 'excellent';
        if ($expense_ratio < 70) return 'good';
        if ($expense_ratio < 80) return 'average';
        if ($expense_ratio < 90) return 'poor';
        return 'very_poor';
    }

    /**
     * الحصول على ملخص قائمة الدخل
     */
    public function getIncomeStatementSummary($filter_data) {
        $income_statement = $this->generateIncomeStatement($filter_data);

        return array(
            'total_revenues' => $income_statement['totals']['total_revenues'],
            'total_expenses' => $income_statement['totals']['total_expenses'],
            'gross_profit' => $income_statement['totals']['gross_profit'],
            'operating_profit' => $income_statement['totals']['operating_profit'],
            'net_profit' => $income_statement['totals']['net_profit'],
            'profit_margin' => $income_statement['totals']['profit_margin'],
            'period_start' => $filter_data['date_start'],
            'period_end' => $filter_data['date_end'],
            'generated_at' => date('Y-m-d H:i:s')
        );
    }
}
