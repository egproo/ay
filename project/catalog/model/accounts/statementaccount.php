<?php
class ModelAccountsStatementaccount extends Model {

    private function getRelatedAccountCodes($parent_code) {
        $account_codes = [];
        $this->getAccountCodesRecursive($parent_code, $account_codes);
        error_log('Final account codes: ' . json_encode($account_codes));
        return $account_codes;
    }
    
    private function getAccountCodesRecursive($account_code, &$account_codes) {
        if (!in_array($account_code, $account_codes)) {
            array_push($account_codes, $account_code);
        }
        $result = $this->db->query("SELECT account_code FROM `" . DB_PREFIX . "accounts` WHERE parent_id = '" . $this->db->escape($account_code) . "'");
        error_log('Querying children of ' . $account_code . ': ' . json_encode($result));
    
        if ($result->num_rows > 0) {
            foreach ($result->rows as $row) {
                $this->getAccountCodesRecursive($row['account_code'], $account_codes);
            }
        }
    }


    public function getOpeningBalance($account_code, $date_start) {
        if (!$date_start) {
            $date_start = '0000-00-00';
        }
        $account_codes = $this->getRelatedAccountCodes($account_code);
        $inQuery = implode("','", $account_codes);
        $sql = "SELECT SUM(je.amount) AS opening_balance FROM `" . DB_PREFIX . "journal_entries` je
                JOIN `" . DB_PREFIX . "journals` j ON je.journal_id = j.journal_id
                WHERE je.account_code IN ('" . $this->db->escape($inQuery) . "') AND j.thedate < '" . $this->db->escape($date_start) . "'";
        $query = $this->db->query($sql);
        return $query->row['opening_balance'] ?? 0;
    }

    public function getClosingBalance($account_code, $date_end) {
        if (!$date_end) {
            $date_end = date('Y-m-d');
        }
        $account_codes = $this->getRelatedAccountCodes($account_code);
        $inQuery = implode("','", $account_codes);
        $sql = "SELECT SUM(je.amount) AS closing_balance FROM `" . DB_PREFIX . "journal_entries` je
                JOIN `" . DB_PREFIX . "journals` j ON je.journal_id = j.journal_id
                WHERE je.account_code IN ('" . $this->db->escape($inQuery) . "') AND j.thedate <= '" . $this->db->escape($date_end) . "'";
        $query = $this->db->query($sql);
        return $query->row['closing_balance'] ?? 0;
    }

    public function getAccountTransactions($account_code, $date_start, $date_end) {
        if (!$date_start) {
            $date_start = '0000-00-00';
        }
        if (!$date_end) {
            $date_end = date('Y-m-d');
        }
        $account_codes = $this->getRelatedAccountCodes($account_code);
    //    $inQuery = implode("','", $account_codes);
// استعلام لجلب المعاملات بناءً على الحسابات والتواريخ
$sql = "SELECT j.journal_id,j.thedate, j.description, je.account_code, je.amount, je.is_debit FROM `" . DB_PREFIX . "journal_entries` je
        JOIN `" . DB_PREFIX . "journals` j ON je.journal_id = j.journal_id
        WHERE je.account_code IN ('" . implode("','", $account_codes) . "') 
        AND j.thedate BETWEEN '" . $date_start . "' AND '" . $date_end . "' 
        ORDER BY j.thedate";
$transactions = $this->db->query($sql)->rows;

        return $transactions;
    }

     public function getAccountsRange($account_start, $account_end, $date_start, $date_end) {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "accounts`
                                       WHERE account_code BETWEEN '" . $this->db->escape($account_start) . "' 
                                       AND '" . $this->db->escape($account_end) . "'");
            $accounts = $query->rows;
    
            foreach ($accounts as $key => $account) {
                
                $account_info = $this->getAccount($account['account_code']);
                $accounts[$key]['account_code'] = $account_info['account_code'];
                $accounts[$key]['name'] = $account_info['name'];
                $account_code = $account['account_code'];
                $opening_balance = $this->getOpeningBalance($account_code, $date_start);
                $closing_balance = $this->getClosingBalance($account_code, $date_end);
                $transactions = $this->getAccountTransactions($account_code, $date_start, $date_end);   
                $balance = $opening_balance;
                $total_debit = 0;
                $total_credit = 0;
                foreach ($transactions as &$transaction) {
                    $transaction['journal_url_edit'] = $this->url->link('accounts/journal/edit', 'user_token=' . $this->session->data['user_token']. '&journal_id=' . (int)$transaction['journal_id']);
                    $balance += ($transaction['is_debit'] ? $transaction['amount'] : -$transaction['amount']);
                    $transaction['amount_formatted'] = $this->currency->format($transaction['amount'], $this->config->get('config_currency'));
                    $transaction['balance_formatted'] = $this->currency->format($balance, $this->config->get('config_currency'));
                    if ($transaction['is_debit']) {
                        $total_debit += $transaction['amount'];
                    } else {
                        $total_credit += $transaction['amount'];
                    }
                }
        
                $accounts[$key]['transactions'] = $transactions;
                $accounts[$key]['opening_balance'] = $opening_balance;
                $accounts[$key]['closing_balance'] = $closing_balance;
                $accounts[$key]['opening_balance_formatted'] = $this->currency->format($opening_balance, $this->config->get('config_currency'));
                $accounts[$key]['closing_balance_formatted'] = $this->currency->format($closing_balance, $this->config->get('config_currency'));                
                $accounts[$key]['total_debit'] = $this->currency->format($total_debit, $this->config->get('config_currency'));
                $accounts[$key]['total_credit'] = $this->currency->format($total_credit, $this->config->get('config_currency'));
                $accounts[$key]['accountname'] = $account_info['name'] . ' (' . $account_code . ')';
            }
    


        
    
            return $accounts;
        }



    public function getAccount($account_code) {
        $query = $this->db->query("SELECT a.account_id, ad.name, a.account_code, a.status, a.parent_id FROM `" . DB_PREFIX . "accounts` a
                                   LEFT JOIN `" . DB_PREFIX . "account_description` ad ON a.account_id = ad.account_id
                                   WHERE a.account_code = '" . $this->db->escape($account_code) . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->row;
    }
}
