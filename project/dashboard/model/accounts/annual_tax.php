<?php
class ModelAccountsAnnualTax extends Model {
    public function getAnnualTaxData($year) {
        $currency_code = $this->config->get('config_currency');

        $date_start = $year . '-01-01';
        $date_end = $year . '-12-31';

        // نفترض أن جميع الحسابات الضريبية (دخل) تبدأ بـ 24xx و 25xx
        $total_taxes = $this->getSumForAccounts('24', $date_start, $date_end) + $this->getSumForAccounts('25', $date_start, $date_end);

        return [
            'year' => $year,
            'total_taxes' => $this->currency->format($total_taxes, $currency_code)
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
