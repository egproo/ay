<?php
class ModelAccountsChangesInEquity extends Model {
    public function getChangesInEquityData($date_start, $date_end) {
        $language_id = (int)$this->config->get('config_language_id');
        $currency_code = $this->config->get('config_currency');

        // نحضر الأرصدة الافتتاحية لحقوق الملكية: الأرصدة قبل date_start
        // والأرصدة الختامية: الأرصدة حتى date_end
        // الفرق يعطينا الحركة.
        
        // الأرصدة الافتتاحية:
        $sql_opening = "SELECT a.account_code, ad.name,
                   COALESCE(SUM(CASE WHEN j.thedate < '" . $this->db->escape($date_start) . "' AND j.is_cancelled = 0 
                                     THEN (CASE WHEN je.is_debit = 1 THEN je.amount ELSE -je.amount END) ELSE 0 END), 0) AS opening_balance
                FROM `" . DB_PREFIX . "accounts` a
                LEFT JOIN `" . DB_PREFIX . "account_description` ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$language_id . "')
                LEFT JOIN `" . DB_PREFIX . "journal_entries` je ON (je.account_code = a.account_code)
                LEFT JOIN `" . DB_PREFIX . "journals` j ON (je.journal_id = j.journal_id)
                WHERE a.account_code LIKE '3%' 
                GROUP BY a.account_code, ad.name
                ORDER BY a.account_code ASC";
        
        $query_opening = $this->db->query($sql_opening);
        $opening_accounts = $query_opening->rows;
        
        // الأرصدة الختامية:
        $sql_closing = "SELECT a.account_code,
                   COALESCE(SUM(CASE WHEN j.thedate <= '" . $this->db->escape($date_end) . "' AND j.is_cancelled = 0 
                                     THEN (CASE WHEN je.is_debit = 1 THEN je.amount ELSE -je.amount END) ELSE 0 END), 0) AS closing_balance
                FROM `" . DB_PREFIX . "accounts` a
                LEFT JOIN `" . DB_PREFIX . "journal_entries` je ON (je.account_code = a.account_code)
                LEFT JOIN `" . DB_PREFIX . "journals` j ON (je.journal_id = j.journal_id)
                WHERE a.account_code LIKE '3%' 
                GROUP BY a.account_code
                ORDER BY a.account_code ASC";
        
        $query_closing = $this->db->query($sql_closing);
        $closing_accounts = $query_closing->rows;

        // فهرسة للإغلاق حسب code
        $closing_index = [];
        foreach ($closing_accounts as $c) {
            $closing_index[$c['account_code']] = $c['closing_balance'];
        }

        // الآن يمكننا حساب حركة الفترة والرصيد الختامي.
        $results = [];
        $total_opening = 0;
        $total_closing = 0;
        $total_movement = 0;

        foreach ($opening_accounts as $op) {
            $code = $op['account_code'];
            $opening = (float)$op['opening_balance'];
            $closing = isset($closing_index[$code]) ? (float)$closing_index[$code] : 0.0;
            $movement = $closing - $opening;
            
            $total_opening += $opening;
            $total_closing += $closing;
            $total_movement += $movement;

            $results[] = [
                'account_code' => $code,
                'name' => $op['name'],
                'opening_formatted' => $this->currency->format($opening, $currency_code),
                'movement_formatted' => $this->currency->format($movement, $currency_code),
                'closing_formatted' => $this->currency->format($closing, $currency_code),
                'opening' => $opening,
                'movement' => $movement,
                'closing' => $closing
            ];
        }

        $total_opening_formatted = $this->currency->format($total_opening, $currency_code);
        $total_movement_formatted = $this->currency->format($total_movement, $currency_code);
        $total_closing_formatted = $this->currency->format($total_closing, $currency_code);

        return [
            'accounts' => $results,
            'total_opening' => $total_opening_formatted,
            'total_movement' => $total_movement_formatted,
            'total_closing' => $total_closing_formatted
        ];
    }
}
