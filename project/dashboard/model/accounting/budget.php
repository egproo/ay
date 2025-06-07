<?php
/**
 * Budget Model
 * 
 * This model handles all budget operations including creating, updating,
 * and comparing budgets with actual results.
 */
class ModelAccountingBudget extends Model {
    /**
     * Get all budgets
     * 
     * @param array $data Filter data
     * @return array Budgets
     */
    public function getBudgets($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "accounting_budget";
        
        $sort_data = array(
            'name',
            'year',
            'status'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY year DESC, name";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * Get total number of budgets
     * 
     * @param array $data Filter data
     * @return int Total number of budgets
     */
    public function getTotalBudgets($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "accounting_budget";
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * Get budget by ID
     * 
     * @param int $budget_id Budget ID
     * @return array Budget data
     */
    public function getBudget($budget_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_budget WHERE budget_id = '" . (int)$budget_id . "'");
        
        return $query->row;
    }
    
    /**
     * Get budget by year
     * 
     * @param int $year Year
     * @return array Budget data
     */
    public function getBudgetByYear($year) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_budget WHERE year = '" . (int)$year . "' ORDER BY budget_id DESC LIMIT 1");
        
        return $query->row;
    }
    
    /**
     * Add budget
     * 
     * @param array $data Budget data
     * @return int Budget ID
     */
    public function addBudget($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_budget SET 
            name = '" . $this->db->escape($data['name']) . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            year = '" . (int)$data['year'] . "', 
            status = '" . (int)$data['status'] . "', 
            date_added = NOW(), 
            date_modified = NOW(), 
            user_id = '" . (int)$this->user->getId() . "'");
        
        $budget_id = $this->db->getLastId();
        
        // Add budget accounts
        if (isset($data['accounts']) && is_array($data['accounts'])) {
            foreach ($data['accounts'] as $account) {
                $this->addBudgetAccount($budget_id, $account);
            }
        }
        
        return $budget_id;
    }
    
    /**
     * Edit budget
     * 
     * @param int $budget_id Budget ID
     * @param array $data Budget data
     * @return void
     */
    public function editBudget($budget_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "accounting_budget SET 
            name = '" . $this->db->escape($data['name']) . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            year = '" . (int)$data['year'] . "', 
            status = '" . (int)$data['status'] . "', 
            date_modified = NOW() 
            WHERE budget_id = '" . (int)$budget_id . "'");
        
        // Update budget accounts
        if (isset($data['accounts']) && is_array($data['accounts'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_budget_account WHERE budget_id = '" . (int)$budget_id . "'");
            
            foreach ($data['accounts'] as $account) {
                $this->addBudgetAccount($budget_id, $account);
            }
        }
    }
    
    /**
     * Delete budget
     * 
     * @param int $budget_id Budget ID
     * @return void
     */
    public function deleteBudget($budget_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_budget_account WHERE budget_id = '" . (int)$budget_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_budget_note WHERE budget_id = '" . (int)$budget_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_budget_version_account WHERE version_id IN (SELECT version_id FROM " . DB_PREFIX . "accounting_budget_version WHERE budget_id = '" . (int)$budget_id . "')");
        $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_budget_version WHERE budget_id = '" . (int)$budget_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_budget WHERE budget_id = '" . (int)$budget_id . "'");
    }
    
    /**
     * Add budget account
     * 
     * @param int $budget_id Budget ID
     * @param array $data Account data
     * @return int Budget account ID
     */
    public function addBudgetAccount($budget_id, $data) {
        $total = 0;
        
        foreach (array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december') as $month) {
            $total += isset($data[$month]) ? (float)$data[$month] : 0;
        }
        
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_budget_account SET 
            budget_id = '" . (int)$budget_id . "', 
            account_id = '" . (int)$data['account_id'] . "', 
            january = '" . (isset($data['january']) ? (float)$data['january'] : 0) . "', 
            february = '" . (isset($data['february']) ? (float)$data['february'] : 0) . "', 
            march = '" . (isset($data['march']) ? (float)$data['march'] : 0) . "', 
            april = '" . (isset($data['april']) ? (float)$data['april'] : 0) . "', 
            may = '" . (isset($data['may']) ? (float)$data['may'] : 0) . "', 
            june = '" . (isset($data['june']) ? (float)$data['june'] : 0) . "', 
            july = '" . (isset($data['july']) ? (float)$data['july'] : 0) . "', 
            august = '" . (isset($data['august']) ? (float)$data['august'] : 0) . "', 
            september = '" . (isset($data['september']) ? (float)$data['september'] : 0) . "', 
            october = '" . (isset($data['october']) ? (float)$data['october'] : 0) . "', 
            november = '" . (isset($data['november']) ? (float)$data['november'] : 0) . "', 
            december = '" . (isset($data['december']) ? (float)$data['december'] : 0) . "', 
            total = '" . (float)$total . "'");
        
        return $this->db->getLastId();
    }
    
    /**
     * Get budget accounts
     * 
     * @param int $budget_id Budget ID
     * @return array Budget accounts
     */
    public function getBudgetAccounts($budget_id) {
        $query = $this->db->query("SELECT ba.*, a.code, a.name, a.type 
            FROM " . DB_PREFIX . "accounting_budget_account ba 
            LEFT JOIN " . DB_PREFIX . "accounting_account a ON (ba.account_id = a.account_id) 
            WHERE ba.budget_id = '" . (int)$budget_id . "' 
            ORDER BY a.code");
        
        return $query->rows;
    }
    
    /**
     * Get budget account
     * 
     * @param int $budget_id Budget ID
     * @param int $account_id Account ID
     * @return array Budget account data
     */
    public function getBudgetAccount($budget_id, $account_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_budget_account 
            WHERE budget_id = '" . (int)$budget_id . "' 
            AND account_id = '" . (int)$account_id . "'");
        
        return $query->row;
    }
    
    /**
     * Add budget note
     * 
     * @param int $budget_id Budget ID
     * @param int $account_id Account ID
     * @param int $month Month (1-12)
     * @param string $note Note text
     * @return int Note ID
     */
    public function addBudgetNote($budget_id, $account_id, $month, $note) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_budget_note SET 
            budget_id = '" . (int)$budget_id . "', 
            account_id = '" . (int)$account_id . "', 
            month = '" . (int)$month . "', 
            note = '" . $this->db->escape($note) . "', 
            date_added = NOW(), 
            user_id = '" . (int)$this->user->getId() . "'");
        
        return $this->db->getLastId();
    }
    
    /**
     * Get budget notes
     * 
     * @param int $budget_id Budget ID
     * @param int $account_id Account ID (optional)
     * @param int $month Month (1-12) (optional)
     * @return array Budget notes
     */
    public function getBudgetNotes($budget_id, $account_id = null, $month = null) {
        $sql = "SELECT bn.*, a.code, a.name, u.username 
            FROM " . DB_PREFIX . "accounting_budget_note bn 
            LEFT JOIN " . DB_PREFIX . "accounting_account a ON (bn.account_id = a.account_id) 
            LEFT JOIN " . DB_PREFIX . "user u ON (bn.user_id = u.user_id) 
            WHERE bn.budget_id = '" . (int)$budget_id . "'";
        
        if ($account_id !== null) {
            $sql .= " AND bn.account_id = '" . (int)$account_id . "'";
        }
        
        if ($month !== null) {
            $sql .= " AND bn.month = '" . (int)$month . "'";
        }
        
        $sql .= " ORDER BY bn.date_added DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * Delete budget note
     * 
     * @param int $note_id Note ID
     * @return void
     */
    public function deleteBudgetNote($note_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_budget_note WHERE note_id = '" . (int)$note_id . "'");
    }
    
    /**
     * Create budget version
     * 
     * @param int $budget_id Budget ID
     * @param array $data Version data
     * @return int Version ID
     */
    public function createBudgetVersion($budget_id, $data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_budget_version SET 
            budget_id = '" . (int)$budget_id . "', 
            name = '" . $this->db->escape($data['name']) . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            is_active = '" . (int)$data['is_active'] . "', 
            date_added = NOW(), 
            user_id = '" . (int)$this->user->getId() . "'");
        
        $version_id = $this->db->getLastId();
        
        // Copy budget accounts to version
        $budget_accounts = $this->getBudgetAccounts($budget_id);
        
        foreach ($budget_accounts as $account) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_budget_version_account SET 
                version_id = '" . (int)$version_id . "', 
                account_id = '" . (int)$account['account_id'] . "', 
                january = '" . (float)$account['january'] . "', 
                february = '" . (float)$account['february'] . "', 
                march = '" . (float)$account['march'] . "', 
                april = '" . (float)$account['april'] . "', 
                may = '" . (float)$account['may'] . "', 
                june = '" . (float)$account['june'] . "', 
                july = '" . (float)$account['july'] . "', 
                august = '" . (float)$account['august'] . "', 
                september = '" . (float)$account['september'] . "', 
                october = '" . (float)$account['october'] . "', 
                november = '" . (float)$account['november'] . "', 
                december = '" . (float)$account['december'] . "', 
                total = '" . (float)$account['total'] . "'");
        }
        
        // If this version is active, deactivate other versions
        if ($data['is_active']) {
            $this->db->query("UPDATE " . DB_PREFIX . "accounting_budget_version SET 
                is_active = '0' 
                WHERE budget_id = '" . (int)$budget_id . "' 
                AND version_id != '" . (int)$version_id . "'");
        }
        
        return $version_id;
    }
    
    /**
     * Get budget versions
     * 
     * @param int $budget_id Budget ID
     * @return array Budget versions
     */
    public function getBudgetVersions($budget_id) {
        $query = $this->db->query("SELECT bv.*, u.username 
            FROM " . DB_PREFIX . "accounting_budget_version bv 
            LEFT JOIN " . DB_PREFIX . "user u ON (bv.user_id = u.user_id) 
            WHERE bv.budget_id = '" . (int)$budget_id . "' 
            ORDER BY bv.date_added DESC");
        
        return $query->rows;
    }
    
    /**
     * Get active budget version
     * 
     * @param int $budget_id Budget ID
     * @return array|bool Active version or false if none
     */
    public function getActiveBudgetVersion($budget_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_budget_version 
            WHERE budget_id = '" . (int)$budget_id . "' 
            AND is_active = '1' 
            LIMIT 1");
        
        if ($query->num_rows) {
            return $query->row;
        } else {
            return false;
        }
    }
    
    /**
     * Set active budget version
     * 
     * @param int $version_id Version ID
     * @return void
     */
    public function setActiveBudgetVersion($version_id) {
        // Get budget ID for this version
        $query = $this->db->query("SELECT budget_id FROM " . DB_PREFIX . "accounting_budget_version 
            WHERE version_id = '" . (int)$version_id . "'");
        
        if ($query->num_rows) {
            $budget_id = $query->row['budget_id'];
            
            // Deactivate all versions for this budget
            $this->db->query("UPDATE " . DB_PREFIX . "accounting_budget_version SET 
                is_active = '0' 
                WHERE budget_id = '" . (int)$budget_id . "'");
            
            // Activate the specified version
            $this->db->query("UPDATE " . DB_PREFIX . "accounting_budget_version SET 
                is_active = '1' 
                WHERE version_id = '" . (int)$version_id . "'");
        }
    }
    
    /**
     * Get budget vs actual comparison
     * 
     * @param int $budget_id Budget ID
     * @param array $data Filter data
     * @return array Comparison data
     */
    public function getBudgetVsActual($budget_id, $data = array()) {
        $budget_info = $this->getBudget($budget_id);
        
        if (!$budget_info) {
            return array();
        }
        
        $year = $budget_info['year'];
        $month_from = isset($data['month_from']) ? (int)$data['month_from'] : 1;
        $month_to = isset($data['month_to']) ? (int)$data['month_to'] : 12;
        
        // Get budget accounts
        $budget_accounts = $this->getBudgetAccounts($budget_id);
        
        // Get actual data
        $this->load->model('accounting/financial_report');
        
        $date_from = $year . '-' . str_pad($month_from, 2, '0', STR_PAD_LEFT) . '-01';
        $date_to = $year . '-' . str_pad($month_to, 2, '0', STR_PAD_LEFT) . '-' . date('t', strtotime($year . '-' . $month_to . '-01'));
        
        $actual_data = $this->model_accounting_financial_report->getIncomeStatement(array(
            'date_from' => $date_from,
            'date_to' => $date_to
        ));
        
        // Combine budget and actual data
        $comparison = array();
        $months = array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
        
        // Process revenue accounts
        $comparison['revenue'] = array();
        $comparison['total_revenue_budget'] = 0;
        $comparison['total_revenue_actual'] = 0;
        $comparison['total_revenue_variance'] = 0;
        
        foreach ($budget_accounts as $account) {
            if ($account['type'] == 'revenue') {
                $budget_amount = 0;
                
                for ($i = $month_from - 1; $i < $month_to; $i++) {
                    $budget_amount += $account[$months[$i]];
                }
                
                $actual_amount = 0;
                
                foreach ($actual_data['revenue'] as $actual_account) {
                    if ($actual_account['account_id'] == $account['account_id']) {
                        $actual_amount = $actual_account['balance'];
                        break;
                    }
                }
                
                $variance = $actual_amount - $budget_amount;
                $variance_percent = $budget_amount != 0 ? ($variance / $budget_amount) * 100 : 0;
                
                $comparison['revenue'][] = array(
                    'account_id' => $account['account_id'],
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'budget' => $budget_amount,
                    'actual' => $actual_amount,
                    'variance' => $variance,
                    'variance_percent' => $variance_percent
                );
                
                $comparison['total_revenue_budget'] += $budget_amount;
                $comparison['total_revenue_actual'] += $actual_amount;
                $comparison['total_revenue_variance'] += $variance;
            }
        }
        
        $comparison['total_revenue_variance_percent'] = $comparison['total_revenue_budget'] != 0 ? 
            ($comparison['total_revenue_variance'] / $comparison['total_revenue_budget']) * 100 : 0;
        
        // Process expense accounts
        $comparison['expense'] = array();
        $comparison['total_expense_budget'] = 0;
        $comparison['total_expense_actual'] = 0;
        $comparison['total_expense_variance'] = 0;
        
        foreach ($budget_accounts as $account) {
            if ($account['type'] == 'expense') {
                $budget_amount = 0;
                
                for ($i = $month_from - 1; $i < $month_to; $i++) {
                    $budget_amount += $account[$months[$i]];
                }
                
                $actual_amount = 0;
                
                foreach ($actual_data['expense'] as $actual_account) {
                    if ($actual_account['account_id'] == $account['account_id']) {
                        $actual_amount = $actual_account['balance'];
                        break;
                    }
                }
                
                $variance = $budget_amount - $actual_amount; // For expenses, positive variance is good
                $variance_percent = $budget_amount != 0 ? ($variance / $budget_amount) * 100 : 0;
                
                $comparison['expense'][] = array(
                    'account_id' => $account['account_id'],
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'budget' => $budget_amount,
                    'actual' => $actual_amount,
                    'variance' => $variance,
                    'variance_percent' => $variance_percent
                );
                
                $comparison['total_expense_budget'] += $budget_amount;
                $comparison['total_expense_actual'] += $actual_amount;
                $comparison['total_expense_variance'] += $variance;
            }
        }
        
        $comparison['total_expense_variance_percent'] = $comparison['total_expense_budget'] != 0 ? 
            ($comparison['total_expense_variance'] / $comparison['total_expense_budget']) * 100 : 0;
        
        // Calculate net income
        $comparison['net_income_budget'] = $comparison['total_revenue_budget'] - $comparison['total_expense_budget'];
        $comparison['net_income_actual'] = $comparison['total_revenue_actual'] - $comparison['total_expense_actual'];
        $comparison['net_income_variance'] = $comparison['net_income_actual'] - $comparison['net_income_budget'];
        $comparison['net_income_variance_percent'] = $comparison['net_income_budget'] != 0 ? 
            ($comparison['net_income_variance'] / $comparison['net_income_budget']) * 100 : 0;
        
        return $comparison;
    }
}
