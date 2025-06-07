<?php
/**
 * Financial Report Model
 * 
 * This model handles all financial reporting operations including trial balance,
 * income statement, and balance sheet.
 */
class ModelAccountingFinancialReport extends Model {
    /**
     * Get trial balance data
     * 
     * @param array $data Filter data
     * @return array Trial balance data
     */
    public function getTrialBalance($data = array()) {
        $date_from = isset($data['date_from']) ? $data['date_from'] : '';
        $date_to = isset($data['date_to']) ? $data['date_to'] : date('Y-m-d');
        
        // Get all accounts
        $accounts_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_account 
            WHERE status = '1' 
            ORDER BY code ASC");
        
        $accounts = $accounts_query->rows;
        $trial_balance = array();
        
        foreach ($accounts as $account) {
            // Build query to get account balance
            $sql = "SELECT 
                SUM(je.debit) as total_debit, 
                SUM(je.credit) as total_credit 
                FROM " . DB_PREFIX . "accounting_journal_entry je 
                LEFT JOIN " . DB_PREFIX . "accounting_journal j ON (je.journal_id = j.journal_id) 
                WHERE je.account_id = '" . (int)$account['account_id'] . "' 
                AND j.status = '1'";
            
            if ($date_from) {
                $sql .= " AND DATE(j.date_added) >= '" . $this->db->escape($date_from) . "'";
            }
            
            if ($date_to) {
                $sql .= " AND DATE(j.date_added) <= '" . $this->db->escape($date_to) . "'";
            }
            
            $balance_query = $this->db->query($sql);
            
            $total_debit = $balance_query->row['total_debit'] ? $balance_query->row['total_debit'] : 0;
            $total_credit = $balance_query->row['total_credit'] ? $balance_query->row['total_credit'] : 0;
            
            // Calculate balance based on account type
            $balance = 0;
            
            if (in_array($account['type'], array('asset', 'expense'))) {
                $balance = $total_debit - $total_credit;
            } else {
                $balance = $total_credit - $total_debit;
            }
            
            // Only include accounts with activity
            if ($total_debit > 0 || $total_credit > 0) {
                $trial_balance[] = array(
                    'account_id' => $account['account_id'],
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'debit' => $total_debit,
                    'credit' => $total_credit,
                    'balance' => $balance
                );
            }
        }
        
        return $trial_balance;
    }
    
    /**
     * Get income statement data
     * 
     * @param array $data Filter data
     * @return array Income statement data
     */
    public function getIncomeStatement($data = array()) {
        $date_from = isset($data['date_from']) ? $data['date_from'] : '';
        $date_to = isset($data['date_to']) ? $data['date_to'] : date('Y-m-d');
        
        // Get revenue accounts
        $revenue_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_account 
            WHERE type = 'revenue' AND status = '1' 
            ORDER BY code ASC");
        
        $revenue_accounts = $revenue_query->rows;
        $revenue_data = array();
        $total_revenue = 0;
        
        foreach ($revenue_accounts as $account) {
            // Build query to get account balance
            $sql = "SELECT 
                SUM(je.debit) as total_debit, 
                SUM(je.credit) as total_credit 
                FROM " . DB_PREFIX . "accounting_journal_entry je 
                LEFT JOIN " . DB_PREFIX . "accounting_journal j ON (je.journal_id = j.journal_id) 
                WHERE je.account_id = '" . (int)$account['account_id'] . "' 
                AND j.status = '1'";
            
            if ($date_from) {
                $sql .= " AND DATE(j.date_added) >= '" . $this->db->escape($date_from) . "'";
            }
            
            if ($date_to) {
                $sql .= " AND DATE(j.date_added) <= '" . $this->db->escape($date_to) . "'";
            }
            
            $balance_query = $this->db->query($sql);
            
            $total_debit = $balance_query->row['total_debit'] ? $balance_query->row['total_debit'] : 0;
            $total_credit = $balance_query->row['total_credit'] ? $balance_query->row['total_credit'] : 0;
            
            // For revenue accounts, credit increases the balance
            $balance = $total_credit - $total_debit;
            $total_revenue += $balance;
            
            // Only include accounts with activity
            if ($total_debit > 0 || $total_credit > 0) {
                $revenue_data[] = array(
                    'account_id' => $account['account_id'],
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'balance' => $balance
                );
            }
        }
        
        // Get expense accounts
        $expense_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_account 
            WHERE type = 'expense' AND status = '1' 
            ORDER BY code ASC");
        
        $expense_accounts = $expense_query->rows;
        $expense_data = array();
        $total_expense = 0;
        
        foreach ($expense_accounts as $account) {
            // Build query to get account balance
            $sql = "SELECT 
                SUM(je.debit) as total_debit, 
                SUM(je.credit) as total_credit 
                FROM " . DB_PREFIX . "accounting_journal_entry je 
                LEFT JOIN " . DB_PREFIX . "accounting_journal j ON (je.journal_id = j.journal_id) 
                WHERE je.account_id = '" . (int)$account['account_id'] . "' 
                AND j.status = '1'";
            
            if ($date_from) {
                $sql .= " AND DATE(j.date_added) >= '" . $this->db->escape($date_from) . "'";
            }
            
            if ($date_to) {
                $sql .= " AND DATE(j.date_added) <= '" . $this->db->escape($date_to) . "'";
            }
            
            $balance_query = $this->db->query($sql);
            
            $total_debit = $balance_query->row['total_debit'] ? $balance_query->row['total_debit'] : 0;
            $total_credit = $balance_query->row['total_credit'] ? $balance_query->row['total_credit'] : 0;
            
            // For expense accounts, debit increases the balance
            $balance = $total_debit - $total_credit;
            $total_expense += $balance;
            
            // Only include accounts with activity
            if ($total_debit > 0 || $total_credit > 0) {
                $expense_data[] = array(
                    'account_id' => $account['account_id'],
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'balance' => $balance
                );
            }
        }
        
        // Calculate net income
        $net_income = $total_revenue - $total_expense;
        
        return array(
            'revenue' => $revenue_data,
            'total_revenue' => $total_revenue,
            'expense' => $expense_data,
            'total_expense' => $total_expense,
            'net_income' => $net_income
        );
    }
    
    /**
     * Get balance sheet data
     * 
     * @param array $data Filter data
     * @return array Balance sheet data
     */
    public function getBalanceSheet($data = array()) {
        $date_to = isset($data['date_to']) ? $data['date_to'] : date('Y-m-d');
        
        // Get asset accounts
        $asset_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_account 
            WHERE type = 'asset' AND status = '1' 
            ORDER BY code ASC");
        
        $asset_accounts = $asset_query->rows;
        $asset_data = array();
        $total_assets = 0;
        
        foreach ($asset_accounts as $account) {
            // Build query to get account balance
            $sql = "SELECT 
                SUM(je.debit) as total_debit, 
                SUM(je.credit) as total_credit 
                FROM " . DB_PREFIX . "accounting_journal_entry je 
                LEFT JOIN " . DB_PREFIX . "accounting_journal j ON (je.journal_id = j.journal_id) 
                WHERE je.account_id = '" . (int)$account['account_id'] . "' 
                AND j.status = '1'";
            
            if ($date_to) {
                $sql .= " AND DATE(j.date_added) <= '" . $this->db->escape($date_to) . "'";
            }
            
            $balance_query = $this->db->query($sql);
            
            $total_debit = $balance_query->row['total_debit'] ? $balance_query->row['total_debit'] : 0;
            $total_credit = $balance_query->row['total_credit'] ? $balance_query->row['total_credit'] : 0;
            
            // For asset accounts, debit increases the balance
            $balance = $total_debit - $total_credit;
            $total_assets += $balance;
            
            // Only include accounts with activity
            if ($total_debit > 0 || $total_credit > 0) {
                $asset_data[] = array(
                    'account_id' => $account['account_id'],
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'balance' => $balance
                );
            }
        }
        
        // Get liability accounts
        $liability_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_account 
            WHERE type = 'liability' AND status = '1' 
            ORDER BY code ASC");
        
        $liability_accounts = $liability_query->rows;
        $liability_data = array();
        $total_liabilities = 0;
        
        foreach ($liability_accounts as $account) {
            // Build query to get account balance
            $sql = "SELECT 
                SUM(je.debit) as total_debit, 
                SUM(je.credit) as total_credit 
                FROM " . DB_PREFIX . "accounting_journal_entry je 
                LEFT JOIN " . DB_PREFIX . "accounting_journal j ON (je.journal_id = j.journal_id) 
                WHERE je.account_id = '" . (int)$account['account_id'] . "' 
                AND j.status = '1'";
            
            if ($date_to) {
                $sql .= " AND DATE(j.date_added) <= '" . $this->db->escape($date_to) . "'";
            }
            
            $balance_query = $this->db->query($sql);
            
            $total_debit = $balance_query->row['total_debit'] ? $balance_query->row['total_debit'] : 0;
            $total_credit = $balance_query->row['total_credit'] ? $balance_query->row['total_credit'] : 0;
            
            // For liability accounts, credit increases the balance
            $balance = $total_credit - $total_debit;
            $total_liabilities += $balance;
            
            // Only include accounts with activity
            if ($total_debit > 0 || $total_credit > 0) {
                $liability_data[] = array(
                    'account_id' => $account['account_id'],
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'balance' => $balance
                );
            }
        }
        
        // Get equity accounts
        $equity_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_account 
            WHERE type = 'equity' AND status = '1' 
            ORDER BY code ASC");
        
        $equity_accounts = $equity_query->rows;
        $equity_data = array();
        $total_equity = 0;
        
        foreach ($equity_accounts as $account) {
            // Build query to get account balance
            $sql = "SELECT 
                SUM(je.debit) as total_debit, 
                SUM(je.credit) as total_credit 
                FROM " . DB_PREFIX . "accounting_journal_entry je 
                LEFT JOIN " . DB_PREFIX . "accounting_journal j ON (je.journal_id = j.journal_id) 
                WHERE je.account_id = '" . (int)$account['account_id'] . "' 
                AND j.status = '1'";
            
            if ($date_to) {
                $sql .= " AND DATE(j.date_added) <= '" . $this->db->escape($date_to) . "'";
            }
            
            $balance_query = $this->db->query($sql);
            
            $total_debit = $balance_query->row['total_debit'] ? $balance_query->row['total_debit'] : 0;
            $total_credit = $balance_query->row['total_credit'] ? $balance_query->row['total_credit'] : 0;
            
            // For equity accounts, credit increases the balance
            $balance = $total_credit - $total_debit;
            $total_equity += $balance;
            
            // Only include accounts with activity
            if ($total_debit > 0 || $total_credit > 0) {
                $equity_data[] = array(
                    'account_id' => $account['account_id'],
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'balance' => $balance
                );
            }
        }
        
        // Calculate retained earnings (net income for the period)
        $income_statement = $this->getIncomeStatement(array('date_to' => $date_to));
        $retained_earnings = $income_statement['net_income'];
        
        // Add retained earnings to equity
        $total_equity += $retained_earnings;
        
        // Calculate total liabilities and equity
        $total_liabilities_equity = $total_liabilities + $total_equity;
        
        return array(
            'assets' => $asset_data,
            'total_assets' => $total_assets,
            'liabilities' => $liability_data,
            'total_liabilities' => $total_liabilities,
            'equity' => $equity_data,
            'total_equity' => $total_equity,
            'retained_earnings' => $retained_earnings,
            'total_liabilities_equity' => $total_liabilities_equity
        );
    }
}
