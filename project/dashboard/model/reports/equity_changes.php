<?php
/**
 * نموذج قائمة التغير في حقوق الملكية المحسن
 * يدعم إنشاء قائمة التغير في حقوق الملكية وفقاً للمعايير المحاسبية
 */
class ModelReportsEquityChanges extends Model {
    
    /**
     * إنشاء قائمة التغير في حقوق الملكية
     */
    public function generateEquityChangesStatement($date_start, $date_end, $include_retained_earnings = 1) {
        $currency_code = $this->config->get('config_currency');
        
        // الحصول على حسابات حقوق الملكية
        $equity_accounts = $this->getEquityAccounts($include_retained_earnings);
        
        // حساب الأرصدة الافتتاحية
        foreach ($equity_accounts as &$account) {
            $account['opening_balance'] = $this->getAccountBalance($account['account_id'], $date_start, true);
            $account['closing_balance'] = $this->getAccountBalance($account['account_id'], $date_end, false);
            $account['net_change'] = $account['closing_balance'] - $account['opening_balance'];
        }
        
        // الحصول على التغيرات خلال الفترة
        $changes = $this->getEquityChanges($date_start, $date_end, $equity_accounts, $include_retained_earnings);
        
        // حساب الإجماليات
        $total_opening = array_sum(array_column($equity_accounts, 'opening_balance'));
        $total_closing = array_sum(array_column($equity_accounts, 'closing_balance'));
        $total_net_change = $total_closing - $total_opening;
        
        return array(
            'period' => array(
                'date_start' => $date_start,
                'date_end' => $date_end,
                'date_start_formatted' => date($this->language->get('date_format_short'), strtotime($date_start)),
                'date_end_formatted' => date($this->language->get('date_format_short'), strtotime($date_end))
            ),
            'equity_accounts' => $this->formatEquityAccounts($equity_accounts, $currency_code),
            'changes' => $this->formatEquityChanges($changes, $currency_code),
            'total_opening' => $total_opening,
            'total_opening_formatted' => $this->currency->format($total_opening, $currency_code),
            'total_closing' => $total_closing,
            'total_closing_formatted' => $this->currency->format($total_closing, $currency_code),
            'total_net_change' => $total_net_change,
            'total_net_change_formatted' => $this->currency->format($total_net_change, $currency_code),
            'include_retained_earnings' => $include_retained_earnings
        );
    }
    
    /**
     * الحصول على حسابات حقوق الملكية
     */
    private function getEquityAccounts($include_retained_earnings = 1) {
        $sql = "SELECT a.account_id, ad.name, a.account_code, a.account_type
                FROM " . DB_PREFIX . "accounts a
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                WHERE a.account_type = 'equity'
                AND a.is_active = 1";
        
        if (!$include_retained_earnings) {
            $sql .= " AND a.account_code NOT LIKE '%retained%' AND a.account_code NOT LIKE '%earning%'";
        }
        
        $sql .= " ORDER BY a.account_code ASC";
        
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    /**
     * الحصول على رصيد الحساب
     */
    private function getAccountBalance($account_id, $date, $is_opening = false) {
        $operator = $is_opening ? '<' : '<=';
        
        $query = $this->db->query("SELECT 
                                    COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) as balance
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  WHERE jel.account_id = '" . (int)$account_id . "'
                                  AND je.status = 'posted'
                                  AND je.journal_date " . $operator . " '" . $this->db->escape($date) . "'");
        
        return (float)$query->row['balance'];
    }
    
    /**
     * الحصول على التغيرات في حقوق الملكية خلال الفترة
     */
    private function getEquityChanges($date_start, $date_end, $equity_accounts, $include_retained_earnings) {
        $changes = array();
        
        // زيادة رأس المال
        $capital_increase = $this->getCapitalIncrease($date_start, $date_end, $equity_accounts);
        if (!empty($capital_increase['amounts']) && array_sum($capital_increase['amounts']) != 0) {
            $changes[] = $capital_increase;
        }
        
        // تخفيض رأس المال
        $capital_decrease = $this->getCapitalDecrease($date_start, $date_end, $equity_accounts);
        if (!empty($capital_decrease['amounts']) && array_sum($capital_decrease['amounts']) != 0) {
            $changes[] = $capital_decrease;
        }
        
        // الأرباح المحتجزة (إذا كانت مشمولة)
        if ($include_retained_earnings) {
            $retained_earnings = $this->getRetainedEarningsChange($date_start, $date_end, $equity_accounts);
            if (!empty($retained_earnings['amounts']) && array_sum($retained_earnings['amounts']) != 0) {
                $changes[] = $retained_earnings;
            }
        }
        
        // صافي الدخل للفترة
        $net_income = $this->getNetIncomeForPeriod($date_start, $date_end, $equity_accounts);
        if (!empty($net_income['amounts']) && array_sum($net_income['amounts']) != 0) {
            $changes[] = $net_income;
        }
        
        // توزيعات الأرباح
        $dividends = $this->getDividendsDistribution($date_start, $date_end, $equity_accounts);
        if (!empty($dividends['amounts']) && array_sum($dividends['amounts']) != 0) {
            $changes[] = $dividends;
        }
        
        // احتياطيات
        $reserves = $this->getReservesChange($date_start, $date_end, $equity_accounts);
        if (!empty($reserves['amounts']) && array_sum($reserves['amounts']) != 0) {
            $changes[] = $reserves;
        }
        
        // تعديلات أخرى
        $other_adjustments = $this->getOtherEquityAdjustments($date_start, $date_end, $equity_accounts);
        if (!empty($other_adjustments['amounts']) && array_sum($other_adjustments['amounts']) != 0) {
            $changes[] = $other_adjustments;
        }
        
        return $changes;
    }
    
    /**
     * زيادة رأس المال
     */
    private function getCapitalIncrease($date_start, $date_end, $equity_accounts) {
        $amounts = array();
        $total = 0;
        
        foreach ($equity_accounts as $account) {
            if (strpos(strtolower($account['account_code']), 'capital') !== false || 
                strpos(strtolower($account['name']), 'رأس المال') !== false) {
                
                $query = $this->db->query("SELECT 
                                            COALESCE(SUM(jel.credit_amount), 0) as increase
                                          FROM " . DB_PREFIX . "journal_entry_line jel
                                          JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                          WHERE jel.account_id = '" . (int)$account['account_id'] . "'
                                          AND je.status = 'posted'
                                          AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                                          AND je.description LIKE '%زيادة رأس المال%' OR je.description LIKE '%capital increase%'");
                
                $amount = (float)$query->row['increase'];
                $amounts[$account['account_id']] = $amount;
                $total += $amount;
            } else {
                $amounts[$account['account_id']] = 0;
            }
        }
        
        return array(
            'description' => 'زيادة رأس المال',
            'amounts' => $amounts,
            'total' => $total
        );
    }
    
    /**
     * تخفيض رأس المال
     */
    private function getCapitalDecrease($date_start, $date_end, $equity_accounts) {
        $amounts = array();
        $total = 0;
        
        foreach ($equity_accounts as $account) {
            if (strpos(strtolower($account['account_code']), 'capital') !== false || 
                strpos(strtolower($account['name']), 'رأس المال') !== false) {
                
                $query = $this->db->query("SELECT 
                                            COALESCE(SUM(jel.debit_amount), 0) as decrease
                                          FROM " . DB_PREFIX . "journal_entry_line jel
                                          JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                          WHERE jel.account_id = '" . (int)$account['account_id'] . "'
                                          AND je.status = 'posted'
                                          AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                                          AND (je.description LIKE '%تخفيض رأس المال%' OR je.description LIKE '%capital decrease%')");
                
                $amount = -(float)$query->row['decrease'];
                $amounts[$account['account_id']] = $amount;
                $total += $amount;
            } else {
                $amounts[$account['account_id']] = 0;
            }
        }
        
        return array(
            'description' => 'تخفيض رأس المال',
            'amounts' => $amounts,
            'total' => $total
        );
    }
    
    /**
     * التغير في الأرباح المحتجزة
     */
    private function getRetainedEarningsChange($date_start, $date_end, $equity_accounts) {
        $amounts = array();
        $total = 0;
        
        foreach ($equity_accounts as $account) {
            if (strpos(strtolower($account['account_code']), 'retained') !== false || 
                strpos(strtolower($account['account_code']), 'earning') !== false ||
                strpos(strtolower($account['name']), 'أرباح محتجزة') !== false) {
                
                $query = $this->db->query("SELECT 
                                            COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) as change
                                          FROM " . DB_PREFIX . "journal_entry_line jel
                                          JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                          WHERE jel.account_id = '" . (int)$account['account_id'] . "'
                                          AND je.status = 'posted'
                                          AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
                
                $amount = (float)$query->row['change'];
                $amounts[$account['account_id']] = $amount;
                $total += $amount;
            } else {
                $amounts[$account['account_id']] = 0;
            }
        }
        
        return array(
            'description' => 'التغير في الأرباح المحتجزة',
            'amounts' => $amounts,
            'total' => $total
        );
    }
    
    /**
     * صافي الدخل للفترة
     */
    private function getNetIncomeForPeriod($date_start, $date_end, $equity_accounts) {
        // حساب صافي الدخل
        $revenue_query = $this->db->query("SELECT 
                                            COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) as revenue
                                          FROM " . DB_PREFIX . "journal_entry_line jel
                                          JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                          JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                          WHERE a.account_type = 'revenue'
                                          AND je.status = 'posted'
                                          AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        
        $expense_query = $this->db->query("SELECT 
                                            COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as expense
                                          FROM " . DB_PREFIX . "journal_entry_line jel
                                          JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                          JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                          WHERE a.account_type = 'expense'
                                          AND je.status = 'posted'
                                          AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        
        $net_income = (float)$revenue_query->row['revenue'] - (float)$expense_query->row['expense'];
        
        $amounts = array();
        $total = 0;
        
        // توزيع صافي الدخل على حساب الأرباح المحتجزة أو رأس المال
        foreach ($equity_accounts as $account) {
            if (strpos(strtolower($account['account_code']), 'retained') !== false || 
                strpos(strtolower($account['name']), 'أرباح محتجزة') !== false) {
                $amounts[$account['account_id']] = $net_income;
                $total = $net_income;
                break;
            } else {
                $amounts[$account['account_id']] = 0;
            }
        }
        
        return array(
            'description' => 'صافي دخل الفترة',
            'amounts' => $amounts,
            'total' => $total
        );
    }
    
    /**
     * توزيعات الأرباح
     */
    private function getDividendsDistribution($date_start, $date_end, $equity_accounts) {
        $amounts = array();
        $total = 0;
        
        foreach ($equity_accounts as $account) {
            $query = $this->db->query("SELECT 
                                        COALESCE(SUM(jel.debit_amount), 0) as dividends
                                      FROM " . DB_PREFIX . "journal_entry_line jel
                                      JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                      WHERE jel.account_id = '" . (int)$account['account_id'] . "'
                                      AND je.status = 'posted'
                                      AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                                      AND (je.description LIKE '%توزيع أرباح%' OR je.description LIKE '%dividend%')");
            
            $amount = -(float)$query->row['dividends'];
            $amounts[$account['account_id']] = $amount;
            $total += $amount;
        }
        
        return array(
            'description' => 'توزيعات الأرباح',
            'amounts' => $amounts,
            'total' => $total
        );
    }
    
    /**
     * التغير في الاحتياطيات
     */
    private function getReservesChange($date_start, $date_end, $equity_accounts) {
        $amounts = array();
        $total = 0;
        
        foreach ($equity_accounts as $account) {
            if (strpos(strtolower($account['account_code']), 'reserve') !== false || 
                strpos(strtolower($account['name']), 'احتياطي') !== false) {
                
                $query = $this->db->query("SELECT 
                                            COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) as change
                                          FROM " . DB_PREFIX . "journal_entry_line jel
                                          JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                          WHERE jel.account_id = '" . (int)$account['account_id'] . "'
                                          AND je.status = 'posted'
                                          AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
                
                $amount = (float)$query->row['change'];
                $amounts[$account['account_id']] = $amount;
                $total += $amount;
            } else {
                $amounts[$account['account_id']] = 0;
            }
        }
        
        return array(
            'description' => 'التغير في الاحتياطيات',
            'amounts' => $amounts,
            'total' => $total
        );
    }
    
    /**
     * تعديلات أخرى في حقوق الملكية
     */
    private function getOtherEquityAdjustments($date_start, $date_end, $equity_accounts) {
        $amounts = array();
        $total = 0;
        
        foreach ($equity_accounts as $account) {
            $query = $this->db->query("SELECT 
                                        COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) as change
                                      FROM " . DB_PREFIX . "journal_entry_line jel
                                      JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                      WHERE jel.account_id = '" . (int)$account['account_id'] . "'
                                      AND je.status = 'posted'
                                      AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                                      AND je.description NOT LIKE '%زيادة رأس المال%'
                                      AND je.description NOT LIKE '%تخفيض رأس المال%'
                                      AND je.description NOT LIKE '%توزيع أرباح%'
                                      AND je.description NOT LIKE '%capital%'
                                      AND je.description NOT LIKE '%dividend%'");
            
            $amount = (float)$query->row['change'];
            
            // استبعاد التغيرات التي تم حسابها بالفعل
            if ($amount != 0) {
                $amounts[$account['account_id']] = $amount;
                $total += $amount;
            } else {
                $amounts[$account['account_id']] = 0;
            }
        }
        
        return array(
            'description' => 'تعديلات أخرى',
            'amounts' => $amounts,
            'total' => $total
        );
    }
    
    /**
     * تنسيق حسابات حقوق الملكية
     */
    private function formatEquityAccounts($equity_accounts, $currency_code) {
        $formatted_accounts = array();
        
        foreach ($equity_accounts as $account) {
            $formatted_accounts[] = array(
                'account_id' => $account['account_id'],
                'name' => $account['name'],
                'account_code' => $account['account_code'],
                'opening_balance' => $account['opening_balance'],
                'opening_balance_formatted' => $this->currency->format($account['opening_balance'], $currency_code),
                'closing_balance' => $account['closing_balance'],
                'closing_balance_formatted' => $this->currency->format($account['closing_balance'], $currency_code),
                'net_change' => $account['net_change'],
                'net_change_formatted' => $this->currency->format($account['net_change'], $currency_code)
            );
        }
        
        return $formatted_accounts;
    }
    
    /**
     * تنسيق التغيرات في حقوق الملكية
     */
    private function formatEquityChanges($changes, $currency_code) {
        $formatted_changes = array();
        
        foreach ($changes as $change) {
            $formatted_amounts = array();
            foreach ($change['amounts'] as $account_id => $amount) {
                $formatted_amounts[$account_id] = array(
                    'amount' => $amount,
                    'amount_formatted' => $this->currency->format($amount, $currency_code)
                );
            }
            
            $formatted_changes[] = array(
                'description' => $change['description'],
                'amounts' => $change['amounts'],
                'amounts_formatted' => $formatted_amounts,
                'total' => $change['total'],
                'total_formatted' => $this->currency->format($change['total'], $currency_code)
            );
        }
        
        return $formatted_changes;
    }
}
