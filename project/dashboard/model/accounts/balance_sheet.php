<?php
/**
 * نموذج قائمة المركز المالي (الميزانية العمومية) المحسن
 * يدعم التجميع الهرمي والتحليل المالي
 */
class ModelAccountsBalanceSheet extends Model {

    /**
     * الحصول على بيانات قائمة المركز المالي
     */
    public function getBalanceSheetData($date_end, $branch_id = null) {
        $language_id = (int)$this->config->get('config_language_id');
        $currency_code = $this->config->get('config_currency');

        // استخدام الجداول الجديدة المحسنة
        $sql = "SELECT a.account_code, a.account_type, a.account_nature, a.parent_id, ad.name,
                       a.opening_balance,
                       COALESCE(SUM(CASE WHEN je.journal_date <= '" . $this->db->escape($date_end) . "' AND je.status = 'posted'
                                         THEN (jel.debit_amount - jel.credit_amount) ELSE 0 END), 0) AS balance_movement
                FROM " . DB_PREFIX . "accounts a
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$language_id . "')
                LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON (jel.account_id = a.account_id)
                LEFT JOIN " . DB_PREFIX . "journal_entry je ON (jel.journal_id = je.journal_id)
                WHERE a.account_type IN ('asset', 'liability', 'equity') AND a.is_active = 1";

        if ($branch_id) {
            $sql .= " AND (je.branch_id = '" . (int)$branch_id . "' OR je.branch_id IS NULL)";
        }

        $sql .= " GROUP BY a.account_id, a.account_code, a.account_type, a.account_nature, a.parent_id, ad.name
                  ORDER BY a.account_code ASC";

        $query = $this->db->query($sql);
        $accounts = $query->rows;

        $assets = [];
        $liabilities = [];
        $equity = [];
        $total_assets = 0;
        $total_liabilities = 0;
        $total_equity = 0;

        // معالجة الحسابات حسب النوع الصحيح
        foreach ($accounts as $acc) {
            $opening_balance = (float)$acc['opening_balance'];
            $balance_movement = (float)$acc['balance_movement'];
            $final_balance = $opening_balance + $balance_movement;
            $account_type = $acc['account_type'];
            $account_nature = $acc['account_nature'];

            if ($account_type == 'asset') {
                // الأصول: طبيعتها مدينة
                $value = $final_balance;
                $total_assets += $value;

                $assets[] = [
                    'account_code' => $acc['account_code'],
                    'name' => $acc['name'],
                    'amount' => $value,
                    'amount_formatted' => $this->currency->format($value, $currency_code)
                ];
            } elseif ($account_type == 'liability') {
                // الخصوم: طبيعتها دائنة
                $value = abs($final_balance);
                $total_liabilities += $value;

                $liabilities[] = [
                    'account_code' => $acc['account_code'],
                    'name' => $acc['name'],
                    'amount' => $value,
                    'amount_formatted' => $this->currency->format($value, $currency_code)
                ];
            } elseif ($account_type == 'equity') {
                // حقوق الملكية: طبيعتها دائنة
                $value = abs($final_balance);
                $total_equity += $value;

                $equity[] = [
                    'account_code' => $acc['account_code'],
                    'name' => $acc['name'],
                    'amount' => $value,
                    'amount_formatted' => $this->currency->format($value, $currency_code)
                ];
            }
        }

        $total_liabilities_equity = $total_liabilities + $total_equity;
        $balance_difference = $total_assets - $total_liabilities_equity;

        return [
            'date' => [
                'date_end' => $date_end,
                'date_end_formatted' => date($this->language->get('date_format_short'), strtotime($date_end))
            ],
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totals' => [
                'total_assets' => $total_assets,
                'total_liabilities' => $total_liabilities,
                'total_equity' => $total_equity,
                'total_liabilities_equity' => $total_liabilities_equity,
                'balance_difference' => $balance_difference,
                'total_assets_formatted' => $this->currency->format($total_assets, $currency_code),
                'total_liabilities_formatted' => $this->currency->format($total_liabilities, $currency_code),
                'total_equity_formatted' => $this->currency->format($total_equity, $currency_code),
                'total_liabilities_equity_formatted' => $this->currency->format($total_liabilities_equity, $currency_code),
                'balance_difference_formatted' => $this->currency->format($balance_difference, $currency_code),
                'is_balanced' => abs($balance_difference) < 0.01
            ]
        ];
    }

    /**
     * مقارنة قوائم المركز المالي لتواريخ مختلفة
     */
    public function compareBalanceSheets($dates) {
        $comparison_data = [];

        foreach ($dates as $date_info) {
            $data = $this->getBalanceSheetData($date_info['date'], $date_info['branch_id'] ?? null);
            $comparison_data[] = [
                'date_name' => $date_info['name'],
                'data' => $data
            ];
        }

        return $comparison_data;
    }

    /**
     * حساب النسب المالية
     */
    public function calculateFinancialRatios($date_end, $branch_id = null) {
        $balance_data = $this->getBalanceSheetData($date_end, $branch_id);

        $total_assets = $balance_data['totals']['total_assets'];
        $total_liabilities = $balance_data['totals']['total_liabilities'];
        $total_equity = $balance_data['totals']['total_equity'];

        return [
            'debt_to_equity_ratio' => $total_equity > 0 ? round($total_liabilities / $total_equity, 2) : 0,
            'debt_to_assets_ratio' => $total_assets > 0 ? round($total_liabilities / $total_assets, 2) : 0,
            'equity_ratio' => $total_assets > 0 ? round($total_equity / $total_assets, 2) : 0,
            'asset_turnover' => 0, // يحتاج لبيانات المبيعات
        ];
    }
}
