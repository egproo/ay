<?php
class ModelAccountsProfitabilityAnalysis extends Model {
    public function getProfitabilityData($date_start, $date_end) {
        $currency_code = $this->config->get('config_currency');

        // إيرادات المبيعات (نفترض جميع الإيرادات 4xxx)
        $revenue = $this->getSumForAccounts('4', $date_start, $date_end);

        // تكلفة المبيعات (مثلا 41xx أو 42xx حسب تصميم الحسابات، نفترض 42xx)
        $cogs = $this->getSumForAccounts('42', $date_start, $date_end);

        // المصاريف التشغيلية (نفترض 43xx و44xx)
        $operating_expenses = $this->getSumForAccounts('43', $date_start, $date_end) + $this->getSumForAccounts('44', $date_start, $date_end);

        // الربح الإجمالي = المبيعات - تكلفة المبيعات
        $gross_profit = $revenue - $cogs;
        // الربح التشغيلي = الربح الإجمالي - المصاريف التشغيلية
        $operating_profit = $gross_profit - $operating_expenses;

        // المصاريف الأخرى مثل الفوائد والضرائب (نفترض 45xx)
        $other_expenses = $this->getSumForAccounts('45', $date_start, $date_end);

        // صافي الربح = الربح التشغيلي - المصاريف الأخرى
        $net_profit = $operating_profit - $other_expenses;

        // حساب الهوامش
        $gross_margin = ($revenue != 0) ? ($gross_profit / $revenue) * 100 : 0;
        $operating_margin = ($revenue != 0) ? ($operating_profit / $revenue) * 100 : 0;
        $net_margin = ($revenue != 0) ? ($net_profit / $revenue) * 100 : 0;

        return [
            'revenue' => $this->currency->format($revenue, $currency_code),
            'cogs' => $this->currency->format($cogs, $currency_code),
            'operating_expenses' => $this->currency->format($operating_expenses, $currency_code),
            'gross_profit' => $this->currency->format($gross_profit, $currency_code),
            'operating_profit' => $this->currency->format($operating_profit, $currency_code),
            'other_expenses' => $this->currency->format($other_expenses, $currency_code),
            'net_profit' => $this->currency->format($net_profit, $currency_code),
            'gross_margin' => number_format($gross_margin, 2) . '%',
            'operating_margin' => number_format($operating_margin, 2) . '%',
            'net_margin' => number_format($net_margin, 2) . '%',
        ];
    }

    private function getSumForAccounts($prefix, $date_start, $date_end) {
        $sql = "SELECT COALESCE(SUM(CASE WHEN je.is_debit=1 THEN je.amount ELSE -je.amount END),0) AS sum_amount
                FROM " . DB_PREFIX . "journal_entries je
                LEFT JOIN " . DB_PREFIX . "journals j ON (je.journal_id=j.journal_id)
                LEFT JOIN " . DB_PREFIX . "accounts a ON (je.account_code=a.account_code)
                WHERE a.account_code LIKE '".$this->db->escape($prefix)."%'
                AND j.thedate BETWEEN '".$this->db->escape($date_start)."' AND '".$this->db->escape($date_end)."'
                AND j.is_cancelled=0";
        $q = $this->db->query($sql);
        return (float)$q->row['sum_amount'];
    }
}
