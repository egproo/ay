<?php
/**
 * نموذج كشوف الحسابات المحاسبية
 * يدعم عرض كشف حساب مفصل مع الأرصدة الجارية
 */
class ModelAccountsStatementAccount extends Model {
    
    /**
     * الحصول على كشف حساب مفصل
     */
    public function getAccountStatement($account_id, $date_start, $date_end) {
        $currency_code = $this->config->get('config_currency');
        
        // الحصول على الرصيد الافتتاحي
        $opening_balance = $this->getOpeningBalance($account_id, $date_start);
        
        // الحصول على المعاملات خلال الفترة
        $transactions = $this->getAccountTransactions($account_id, $date_start, $date_end);
        
        // حساب الرصيد الجاري لكل معاملة
        $running_balance = $opening_balance;
        $total_debit = 0;
        $total_credit = 0;
        
        foreach ($transactions as &$transaction) {
            $debit = (float)$transaction['debit_amount'];
            $credit = (float)$transaction['credit_amount'];
            
            $running_balance += ($debit - $credit);
            $transaction['running_balance'] = $running_balance;
            $transaction['running_balance_formatted'] = $this->currency->format($running_balance, $currency_code);
            $transaction['debit_amount_formatted'] = $this->currency->format($debit, $currency_code);
            $transaction['credit_amount_formatted'] = $this->currency->format($credit, $currency_code);
            $transaction['journal_date_formatted'] = date($this->language->get('date_format_short'), strtotime($transaction['journal_date']));
            
            $total_debit += $debit;
            $total_credit += $credit;
        }
        
        $closing_balance = $running_balance;
        
        return array(
            'account_id' => $account_id,
            'date_start' => $date_start,
            'date_end' => $date_end,
            'opening_balance' => $opening_balance,
            'opening_balance_formatted' => $this->currency->format($opening_balance, $currency_code),
            'closing_balance' => $closing_balance,
            'closing_balance_formatted' => $this->currency->format($closing_balance, $currency_code),
            'total_debit' => $total_debit,
            'total_debit_formatted' => $this->currency->format($total_debit, $currency_code),
            'total_credit' => $total_credit,
            'total_credit_formatted' => $this->currency->format($total_credit, $currency_code),
            'net_movement' => $total_debit - $total_credit,
            'net_movement_formatted' => $this->currency->format($total_debit - $total_credit, $currency_code),
            'transactions' => $transactions,
            'transaction_count' => count($transactions)
        );
    }
    
    /**
     * الحصول على الرصيد الافتتاحي للحساب
     */
    private function getOpeningBalance($account_id, $date_start) {
        // الحصول على معلومات الحساب
        $account_query = $this->db->query("SELECT opening_balance, account_nature FROM " . DB_PREFIX . "accounts WHERE account_id = '" . (int)$account_id . "'");
        
        if (!$account_query->num_rows) {
            return 0;
        }
        
        $opening_balance = (float)$account_query->row['opening_balance'];
        
        // حساب الحركات قبل تاريخ البداية
        $query = $this->db->query("SELECT 
                                    COALESCE(SUM(jel.debit_amount), 0) as total_debit,
                                    COALESCE(SUM(jel.credit_amount), 0) as total_credit
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  WHERE jel.account_id = '" . (int)$account_id . "'
                                  AND je.status = 'posted'
                                  AND je.journal_date < '" . $this->db->escape($date_start) . "'");
        
        $total_debit = (float)$query->row['total_debit'];
        $total_credit = (float)$query->row['total_credit'];
        
        return $opening_balance + ($total_debit - $total_credit);
    }
    
    /**
     * الحصول على معاملات الحساب خلال فترة محددة
     */
    private function getAccountTransactions($account_id, $date_start, $date_end) {
        $query = $this->db->query("SELECT 
                                    je.journal_id,
                                    je.journal_number,
                                    je.journal_date,
                                    je.description as journal_description,
                                    jel.line_id,
                                    jel.debit_amount,
                                    jel.credit_amount,
                                    jel.description as line_description,
                                    je.reference_type,
                                    je.reference_id
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  WHERE jel.account_id = '" . (int)$account_id . "'
                                  AND je.status = 'posted'
                                  AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                                  ORDER BY je.journal_date ASC, je.journal_id ASC, jel.line_id ASC");
        
        $transactions = array();
        
        foreach ($query->rows as $row) {
            $description = !empty($row['line_description']) ? $row['line_description'] : $row['journal_description'];
            
            $transactions[] = array(
                'journal_id' => $row['journal_id'],
                'journal_number' => $row['journal_number'],
                'journal_date' => $row['journal_date'],
                'description' => $description,
                'debit_amount' => $row['debit_amount'],
                'credit_amount' => $row['credit_amount'],
                'reference_type' => $row['reference_type'],
                'reference_id' => $row['reference_id']
            );
        }
        
        return $transactions;
    }
    
    /**
     * الحصول على ملخص الحساب
     */
    public function getAccountSummary($account_id, $date_start, $date_end) {
        $currency_code = $this->config->get('config_currency');
        
        // الحصول على معلومات الحساب
        $account_query = $this->db->query("SELECT a.*, ad.name 
                                          FROM " . DB_PREFIX . "accounts a
                                          LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                          WHERE a.account_id = '" . (int)$account_id . "'");
        
        if (!$account_query->num_rows) {
            return array();
        }
        
        $account = $account_query->row;
        
        // الحصول على الإحصائيات
        $stats_query = $this->db->query("SELECT 
                                          COUNT(*) as transaction_count,
                                          COALESCE(SUM(jel.debit_amount), 0) as total_debit,
                                          COALESCE(SUM(jel.credit_amount), 0) as total_credit,
                                          MIN(je.journal_date) as first_transaction_date,
                                          MAX(je.journal_date) as last_transaction_date
                                        FROM " . DB_PREFIX . "journal_entry_line jel
                                        JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                        WHERE jel.account_id = '" . (int)$account_id . "'
                                        AND je.status = 'posted'
                                        AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        
        $stats = $stats_query->row;
        
        $opening_balance = $this->getOpeningBalance($account_id, $date_start);
        $closing_balance = $opening_balance + ((float)$stats['total_debit'] - (float)$stats['total_credit']);
        
        return array(
            'account' => $account,
            'period' => array(
                'date_start' => $date_start,
                'date_end' => $date_end,
                'date_start_formatted' => date($this->language->get('date_format_short'), strtotime($date_start)),
                'date_end_formatted' => date($this->language->get('date_format_short'), strtotime($date_end))
            ),
            'balances' => array(
                'opening_balance' => $opening_balance,
                'opening_balance_formatted' => $this->currency->format($opening_balance, $currency_code),
                'closing_balance' => $closing_balance,
                'closing_balance_formatted' => $this->currency->format($closing_balance, $currency_code),
                'net_movement' => (float)$stats['total_debit'] - (float)$stats['total_credit'],
                'net_movement_formatted' => $this->currency->format((float)$stats['total_debit'] - (float)$stats['total_credit'], $currency_code)
            ),
            'totals' => array(
                'total_debit' => (float)$stats['total_debit'],
                'total_debit_formatted' => $this->currency->format((float)$stats['total_debit'], $currency_code),
                'total_credit' => (float)$stats['total_credit'],
                'total_credit_formatted' => $this->currency->format((float)$stats['total_credit'], $currency_code)
            ),
            'statistics' => array(
                'transaction_count' => (int)$stats['transaction_count'],
                'first_transaction_date' => $stats['first_transaction_date'],
                'last_transaction_date' => $stats['last_transaction_date'],
                'average_transaction_amount' => $stats['transaction_count'] > 0 ? ((float)$stats['total_debit'] + (float)$stats['total_credit']) / (2 * (int)$stats['transaction_count']) : 0
            )
        );
    }
    
    /**
     * الحصول على أكبر المعاملات في الحساب
     */
    public function getTopTransactions($account_id, $date_start, $date_end, $limit = 10) {
        $currency_code = $this->config->get('config_currency');
        
        $query = $this->db->query("SELECT 
                                    je.journal_id,
                                    je.journal_number,
                                    je.journal_date,
                                    je.description as journal_description,
                                    jel.debit_amount,
                                    jel.credit_amount,
                                    jel.description as line_description,
                                    (jel.debit_amount + jel.credit_amount) as transaction_amount
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  WHERE jel.account_id = '" . (int)$account_id . "'
                                  AND je.status = 'posted'
                                  AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                                  ORDER BY transaction_amount DESC
                                  LIMIT " . (int)$limit);
        
        $transactions = array();
        
        foreach ($query->rows as $row) {
            $description = !empty($row['line_description']) ? $row['line_description'] : $row['journal_description'];
            
            $transactions[] = array(
                'journal_id' => $row['journal_id'],
                'journal_number' => $row['journal_number'],
                'journal_date' => $row['journal_date'],
                'journal_date_formatted' => date($this->language->get('date_format_short'), strtotime($row['journal_date'])),
                'description' => $description,
                'debit_amount' => $row['debit_amount'],
                'credit_amount' => $row['credit_amount'],
                'debit_amount_formatted' => $this->currency->format($row['debit_amount'], $currency_code),
                'credit_amount_formatted' => $this->currency->format($row['credit_amount'], $currency_code),
                'transaction_amount' => $row['transaction_amount'],
                'transaction_amount_formatted' => $this->currency->format($row['transaction_amount'], $currency_code)
            );
        }
        
        return $transactions;
    }
    
    /**
     * الحصول على الحركة الشهرية للحساب
     */
    public function getMonthlyMovement($account_id, $year) {
        $currency_code = $this->config->get('config_currency');
        $monthly_data = array();
        
        for ($month = 1; $month <= 12; $month++) {
            $date_start = sprintf('%04d-%02d-01', $year, $month);
            $date_end = date('Y-m-t', strtotime($date_start));
            
            $query = $this->db->query("SELECT 
                                        COALESCE(SUM(jel.debit_amount), 0) as total_debit,
                                        COALESCE(SUM(jel.credit_amount), 0) as total_credit,
                                        COUNT(*) as transaction_count
                                      FROM " . DB_PREFIX . "journal_entry_line jel
                                      JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                      WHERE jel.account_id = '" . (int)$account_id . "'
                                      AND je.status = 'posted'
                                      AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
            
            $result = $query->row;
            
            $monthly_data[] = array(
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'year' => $year,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'total_debit' => (float)$result['total_debit'],
                'total_credit' => (float)$result['total_credit'],
                'net_movement' => (float)$result['total_debit'] - (float)$result['total_credit'],
                'transaction_count' => (int)$result['transaction_count'],
                'total_debit_formatted' => $this->currency->format((float)$result['total_debit'], $currency_code),
                'total_credit_formatted' => $this->currency->format((float)$result['total_credit'], $currency_code),
                'net_movement_formatted' => $this->currency->format((float)$result['total_debit'] - (float)$result['total_credit'], $currency_code)
            );
        }
        
        return $monthly_data;
    }
}
?>
