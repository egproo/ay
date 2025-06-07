<?php
/**
 * Accounting Period Model
 * 
 * This model handles all accounting period operations including creating, updating,
 * closing, and reopening accounting periods.
 */
class ModelAccountingPeriod extends Model {
    /**
     * Get all accounting periods
     * 
     * @param array $data Filter data
     * @return array Accounting periods
     */
    public function getPeriods($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "accounting_period";
        
        $sort_data = array(
            'name',
            'start_date',
            'end_date',
            'status'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY start_date";
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
     * Get total number of accounting periods
     * 
     * @param array $data Filter data
     * @return int Total number of periods
     */
    public function getTotalPeriods($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "accounting_period";
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * Get accounting period by ID
     * 
     * @param int $period_id Period ID
     * @return array Period data
     */
    public function getPeriod($period_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_period WHERE period_id = '" . (int)$period_id . "'");
        
        return $query->row;
    }
    
    /**
     * Get current accounting period
     * 
     * @return array|bool Current period data or false if no open period
     */
    public function getCurrentPeriod() {
        $today = date('Y-m-d');
        
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_period 
            WHERE start_date <= '" . $this->db->escape($today) . "' 
            AND end_date >= '" . $this->db->escape($today) . "' 
            AND status = '0' 
            ORDER BY start_date DESC 
            LIMIT 1");
        
        if ($query->num_rows) {
            return $query->row;
        } else {
            return false;
        }
    }
    
    /**
     * Add accounting period
     * 
     * @param array $data Period data
     * @return int Period ID
     */
    public function addPeriod($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_period SET 
            name = '" . $this->db->escape($data['name']) . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            start_date = '" . $this->db->escape($data['start_date']) . "', 
            end_date = '" . $this->db->escape($data['end_date']) . "', 
            status = '" . (int)$data['status'] . "', 
            date_added = NOW(), 
            date_modified = NOW(), 
            user_id = '" . (int)$this->user->getId() . "'");
        
        return $this->db->getLastId();
    }
    
    /**
     * Edit accounting period
     * 
     * @param int $period_id Period ID
     * @param array $data Period data
     * @return void
     */
    public function editPeriod($period_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "accounting_period SET 
            name = '" . $this->db->escape($data['name']) . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            start_date = '" . $this->db->escape($data['start_date']) . "', 
            end_date = '" . $this->db->escape($data['end_date']) . "', 
            status = '" . (int)$data['status'] . "', 
            date_modified = NOW() 
            WHERE period_id = '" . (int)$period_id . "'");
    }
    
    /**
     * Delete accounting period
     * 
     * @param int $period_id Period ID
     * @return bool Success
     */
    public function deletePeriod($period_id) {
        // Check if period has journal entries
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "accounting_journal 
            WHERE period_id = '" . (int)$period_id . "'");
        
        if ($query->row['total'] > 0) {
            return false;
        }
        
        // Check if period is closed
        $period_info = $this->getPeriod($period_id);
        
        if ($period_info && $period_info['status'] > 0) {
            return false;
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_period WHERE period_id = '" . (int)$period_id . "'");
        
        return true;
    }
    
    /**
     * Close accounting period
     * 
     * @param int $period_id Period ID
     * @param array $data Closing data
     * @return bool Success
     */
    public function closePeriod($period_id, $data) {
        try {
            $this->db->query("START TRANSACTION");
            
            // Update period status
            $this->db->query("UPDATE " . DB_PREFIX . "accounting_period SET 
                status = '1', 
                date_modified = NOW() 
                WHERE period_id = '" . (int)$period_id . "'");
            
            // Add closing record
            $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_period_closing SET 
                period_id = '" . (int)$period_id . "', 
                closing_date = NOW(), 
                closing_notes = '" . $this->db->escape($data['closing_notes']) . "', 
                user_id = '" . (int)$this->user->getId() . "'");
            
            $closing_id = $this->db->getLastId();
            
            // Create closing entries if needed
            if (isset($data['create_closing_entries']) && $data['create_closing_entries']) {
                $period_info = $this->getPeriod($period_id);
                
                if ($period_info) {
                    $this->load->model('accounting/financial_report');
                    
                    // Get income statement for the period
                    $income_statement = $this->model_accounting_financial_report->getIncomeStatement(array(
                        'date_from' => $period_info['start_date'],
                        'date_to' => $period_info['end_date']
                    ));
                    
                    // Create closing entry for income and expense accounts
                    if ($income_statement['net_income'] != 0) {
                        $this->load->model('accounting/accounting_manager');
                        
                        // Get retained earnings account
                        $retained_earnings_account = $this->getRetainedEarningsAccount();
                        
                        if ($retained_earnings_account) {
                            $journal_data = array(
                                'reference_type' => 'period_closing',
                                'reference_id' => $period_id,
                                'period_id' => $period_id,
                                'description' => 'Closing entry for period: ' . $period_info['name'],
                                'date_added' => date('Y-m-d H:i:s'),
                                'user_id' => $this->user->getId(),
                                'entries' => array()
                            );
                            
                            // Close revenue accounts
                            foreach ($income_statement['revenue'] as $account) {
                                if ($account['balance'] != 0) {
                                    $journal_data['entries'][] = array(
                                        'account_id' => $account['account_id'],
                                        'debit' => $account['balance'],
                                        'credit' => 0,
                                        'description' => 'Closing revenue account: ' . $account['name']
                                    );
                                }
                            }
                            
                            // Close expense accounts
                            foreach ($income_statement['expense'] as $account) {
                                if ($account['balance'] != 0) {
                                    $journal_data['entries'][] = array(
                                        'account_id' => $account['account_id'],
                                        'debit' => 0,
                                        'credit' => $account['balance'],
                                        'description' => 'Closing expense account: ' . $account['name']
                                    );
                                }
                            }
                            
                            // Balance with retained earnings
                            if ($income_statement['net_income'] > 0) {
                                $journal_data['entries'][] = array(
                                    'account_id' => $retained_earnings_account['account_id'],
                                    'debit' => 0,
                                    'credit' => $income_statement['net_income'],
                                    'description' => 'Net income transferred to retained earnings'
                                );
                            } else {
                                $journal_data['entries'][] = array(
                                    'account_id' => $retained_earnings_account['account_id'],
                                    'debit' => abs($income_statement['net_income']),
                                    'credit' => 0,
                                    'description' => 'Net loss transferred to retained earnings'
                                );
                            }
                            
                            // Create journal entry
                            $journal_id = $this->model_accounting_accounting_manager->createJournalEntry($journal_data);
                            
                            if ($journal_id) {
                                // Record closing entry
                                $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_period_closing_entry SET 
                                    closing_id = '" . (int)$closing_id . "', 
                                    journal_id = '" . (int)$journal_id . "'");
                            }
                        }
                    }
                }
            }
            
            $this->db->query("COMMIT");
            
            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $this->log->write("Error in closePeriod: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reopen accounting period
     * 
     * @param int $period_id Period ID
     * @return bool Success
     */
    public function reopenPeriod($period_id) {
        try {
            $this->db->query("START TRANSACTION");
            
            // Get period info
            $period_info = $this->getPeriod($period_id);
            
            if (!$period_info || $period_info['status'] == 2) {
                // Cannot reopen locked periods
                return false;
            }
            
            // Get closing entries
            $query = $this->db->query("SELECT ce.journal_id 
                FROM " . DB_PREFIX . "accounting_period_closing c 
                LEFT JOIN " . DB_PREFIX . "accounting_period_closing_entry ce ON (c.closing_id = ce.closing_id) 
                WHERE c.period_id = '" . (int)$period_id . "'");
            
            // Delete closing entries
            foreach ($query->rows as $row) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_journal_entry 
                    WHERE journal_id = '" . (int)$row['journal_id'] . "'");
                
                $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_journal 
                    WHERE journal_id = '" . (int)$row['journal_id'] . "'");
            }
            
            // Delete closing records
            $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_period_closing_entry 
                WHERE closing_id IN (SELECT closing_id FROM " . DB_PREFIX . "accounting_period_closing 
                WHERE period_id = '" . (int)$period_id . "')");
            
            $this->db->query("DELETE FROM " . DB_PREFIX . "accounting_period_closing 
                WHERE period_id = '" . (int)$period_id . "'");
            
            // Update period status
            $this->db->query("UPDATE " . DB_PREFIX . "accounting_period SET 
                status = '0', 
                date_modified = NOW() 
                WHERE period_id = '" . (int)$period_id . "'");
            
            $this->db->query("COMMIT");
            
            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $this->log->write("Error in reopenPeriod: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lock accounting period
     * 
     * @param int $period_id Period ID
     * @return bool Success
     */
    public function lockPeriod($period_id) {
        // Check if period is closed
        $period_info = $this->getPeriod($period_id);
        
        if (!$period_info || $period_info['status'] != 1) {
            // Can only lock closed periods
            return false;
        }
        
        $this->db->query("UPDATE " . DB_PREFIX . "accounting_period SET 
            status = '2', 
            date_modified = NOW() 
            WHERE period_id = '" . (int)$period_id . "'");
        
        return true;
    }
    
    /**
     * Check if date is in open period
     * 
     * @param string $date Date to check
     * @return bool True if date is in open period
     */
    public function isDateInOpenPeriod($date) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "accounting_period 
            WHERE start_date <= '" . $this->db->escape($date) . "' 
            AND end_date >= '" . $this->db->escape($date) . "' 
            AND status = '0'");
        
        return ($query->row['total'] > 0);
    }
    
    /**
     * Get period for date
     * 
     * @param string $date Date to check
     * @return array|bool Period data or false if no period found
     */
    public function getPeriodForDate($date) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_period 
            WHERE start_date <= '" . $this->db->escape($date) . "' 
            AND end_date >= '" . $this->db->escape($date) . "' 
            ORDER BY start_date DESC 
            LIMIT 1");
        
        if ($query->num_rows) {
            return $query->row;
        } else {
            return false;
        }
    }
    
    /**
     * Get retained earnings account
     * 
     * @return array|bool Account data or false if not found
     */
    private function getRetainedEarningsAccount() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_account 
            WHERE type = 'equity' AND code = '3200' 
            LIMIT 1");
        
        if ($query->num_rows) {
            return $query->row;
        } else {
            // Create retained earnings account if it doesn't exist
            $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_account SET 
                code = '3200', 
                name = 'Retained Earnings', 
                description = 'Accumulated earnings of the company that have not been distributed to shareholders', 
                type = 'equity', 
                parent_id = NULL, 
                status = '1', 
                date_added = NOW(), 
                date_modified = NOW()");
            
            return array(
                'account_id' => $this->db->getLastId(),
                'code' => '3200',
                'name' => 'Retained Earnings',
                'type' => 'equity'
            );
        }
    }
}
