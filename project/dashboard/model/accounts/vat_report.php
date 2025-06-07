<?php
class ModelAccountsVatReport extends Model {
    public function getVatReportData($date_start, $date_end) {
        $language_id = (int)$this->config->get('config_language_id');
        $currency_code = $this->config->get('config_currency');

        // حسابات ضريبة المبيعات
        $sales_prefix = $this->config->get('config_vat_sales_account_prefix') ?: '4110'; 
        // حسابات ضريبة المشتريات
        $purchases_prefix = $this->config->get('config_vat_purchases_account_prefix') ?: '5110';

        // اجمالي ضريبة المبيعات خلال الفترة
        $sql_sales = "SELECT 
                        COALESCE(SUM(CASE WHEN j.thedate BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "' AND j.is_cancelled = 0 
                                        THEN (CASE WHEN je.is_debit = 1 THEN je.amount ELSE -je.amount END) ELSE 0 END), 0) AS vat_sales
                      FROM `" . DB_PREFIX . "accounts` a
                      LEFT JOIN `" . DB_PREFIX . "journal_entries` je ON (je.account_code = a.account_code)
                      LEFT JOIN `" . DB_PREFIX . "journals` j ON (je.journal_id = j.journal_id)
                      WHERE a.account_code LIKE '" . $this->db->escape($sales_prefix) . "%'";

        $query_sales = $this->db->query($sql_sales);
        $vat_sales = (float)$query_sales->row['vat_sales'];

        // اجمالي ضريبة المشتريات خلال الفترة
        $sql_purchases = "SELECT 
                            COALESCE(SUM(CASE WHEN j.thedate BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "' AND j.is_cancelled = 0 
                                            THEN (CASE WHEN je.is_debit = 1 THEN je.amount ELSE -je.amount END) ELSE 0 END), 0) AS vat_purchases
                          FROM `" . DB_PREFIX . "accounts` a
                          LEFT JOIN `" . DB_PREFIX . "journal_entries` je ON (je.account_code = a.account_code)
                          LEFT JOIN `" . DB_PREFIX . "journals` j ON (je.journal_id = j.journal_id)
                          WHERE a.account_code LIKE '" . $this->db->escape($purchases_prefix) . "%'";

        $query_purchases = $this->db->query($sql_purchases);
        $vat_purchases = (float)$query_purchases->row['vat_purchases'];

        // صافي الضريبة المستحقة = ضريبة المبيعات - ضريبة المشتريات
        $net_vat = $vat_sales - $vat_purchases;

        return [
            'vat_sales' => $this->currency->format($vat_sales, $currency_code),
            'vat_purchases' => $this->currency->format($vat_purchases, $currency_code),
            'net_vat' => $this->currency->format($net_vat, $currency_code)
        ];
    }
}
