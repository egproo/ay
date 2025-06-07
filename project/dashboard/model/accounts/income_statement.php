<?php
/**
 * نموذج قائمة الدخل المحسن
 * يدعم التجميع الهرمي والتحليل المالي
 */
class ModelAccountsIncomeStatement extends Model {

    /**
     * الحصول على بيانات قائمة الدخل
     */
    public function getIncomeStatementData($date_start, $date_end, $branch_id = null) {
        $language_id = (int)$this->config->get('config_language_id');
        $currency_code = $this->config->get('config_currency');

        // استخدام الجداول الجديدة المحسنة
        $sql = "SELECT a.account_code, a.account_type, a.account_nature, ad.name,
                   COALESCE(SUM(CASE WHEN je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "' AND je.status = 'posted'
                                     THEN (jel.debit_amount - jel.credit_amount) ELSE 0 END), 0) AS period_movement
                FROM " . DB_PREFIX . "accounts a
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . $language_id . "')
                LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON (jel.account_id = a.account_id)
                LEFT JOIN " . DB_PREFIX . "journal_entry je ON (jel.journal_id = je.journal_id)
                WHERE a.account_type IN ('revenue', 'expense') AND a.is_active = 1";

        if ($branch_id) {
            $sql .= " AND je.branch_id = '" . (int)$branch_id . "'";
        }

        $sql .= " GROUP BY a.account_id, a.account_code, a.account_type, a.account_nature, ad.name
                  HAVING period_movement != 0
                  ORDER BY a.account_code ASC";

        $query = $this->db->query($sql);
        $accounts = $query->rows;

        $revenues = [];
        $expenses = [];
        $total_revenues = 0;
        $total_expenses = 0;

        // معالجة الحسابات حسب النوع الصحيح
        foreach ($accounts as $acc) {
            $movement = (float)$acc['period_movement'];
            $account_type = $acc['account_type'];
            $account_nature = $acc['account_nature'];

            if ($account_type == 'revenue') {
                // الإيرادات: طبيعتها دائنة، لذا الرصيد السالب يعني إيراد
                $revenue_amount = abs($movement);
                $total_revenues += $revenue_amount;

                $revenues[] = [
                    'account_code' => $acc['account_code'],
                    'name' => $acc['name'],
                    'amount' => $revenue_amount,
                    'amount_formatted' => $this->currency->format($revenue_amount, $currency_code)
                ];
            } elseif ($account_type == 'expense') {
                // المصروفات: طبيعتها مدينة، لذا الرصيد الموجب يعني مصروف
                $expense_amount = abs($movement);
                $total_expenses += $expense_amount;

                $expenses[] = [
                    'account_code' => $acc['account_code'],
                    'name' => $acc['name'],
                    'amount' => $expense_amount,
                    'amount_formatted' => $this->currency->format($expense_amount, $currency_code)
                ];
            }
        }

        $net_income = $total_revenues - $total_expenses;

        return [
            'period' => [
                'start_date' => $date_start,
                'end_date' => $date_end,
                'start_date_formatted' => date($this->language->get('date_format_short'), strtotime($date_start)),
                'end_date_formatted' => date($this->language->get('date_format_short'), strtotime($date_end))
            ],
            'revenues' => $revenues,
            'expenses' => $expenses,
            'totals' => [
                'total_revenues' => $total_revenues,
                'total_expenses' => $total_expenses,
                'net_income' => $net_income,
                'total_revenues_formatted' => $this->currency->format($total_revenues, $currency_code),
                'total_expenses_formatted' => $this->currency->format($total_expenses, $currency_code),
                'net_income_formatted' => $this->currency->format($net_income, $currency_code),
                'net_margin_percentage' => $total_revenues > 0 ? round(($net_income / $total_revenues) * 100, 2) : 0
            ]
        ];
    }

    /**
     * مقارنة قوائم الدخل لفترات مختلفة
     */
    public function compareIncomeStatements($periods) {
        $comparison_data = [];

        foreach ($periods as $period) {
            $data = $this->getIncomeStatementData($period['start_date'], $period['end_date'], $period['branch_id'] ?? null);
            $comparison_data[] = [
                'period_name' => $period['name'],
                'data' => $data
            ];
        }

        return $comparison_data;
    }

    /**
     * حساب النسب المالية
     */
    public function calculateFinancialRatios($date_start, $date_end, $branch_id = null) {
        $income_data = $this->getIncomeStatementData($date_start, $date_end, $branch_id);

        $total_revenue = $income_data['totals']['total_revenues'];
        $total_expenses = $income_data['totals']['total_expenses'];
        $net_income = $income_data['totals']['net_income'];

        return [
            'gross_profit_margin' => $total_revenue > 0 ? round((($total_revenue - $total_expenses) / $total_revenue) * 100, 2) : 0,
            'net_profit_margin' => $total_revenue > 0 ? round(($net_income / $total_revenue) * 100, 2) : 0,
            'expense_ratio' => $total_revenue > 0 ? round(($total_expenses / $total_revenue) * 100, 2) : 0,
        ];
    }

    /**
     * الحصول على تفاصيل حساب معين
     */
    public function getAccountDetails($account_id, $date_start, $date_end) {
        $language_id = (int)$this->config->get('config_language_id');

        $sql = "SELECT je.journal_date, je.journal_number, je.description,
                       jel.debit_amount, jel.credit_amount, jel.description as line_description
                FROM " . DB_PREFIX . "journal_entry_line jel
                JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                WHERE jel.account_id = '" . (int)$account_id . "'
                AND je.status = 'posted'
                AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                ORDER BY je.journal_date, je.journal_id";

        $query = $this->db->query($sql);
        return $query->rows;
    }
}
