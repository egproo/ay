<?php
/**
 * نموذج إدارة المحافظ الإلكترونية المحسن
 * يدعم إدارة المحافظ الإلكترونية الشائعة في مصر والعالم العربي
 * مثل فودافون كاش، أورانج موني، إتصالات كاش، فوري، أمان، PayPal، إلخ
 */
class ModelFinanceEwallet extends Model {
    
    /**
     * إضافة محفظة إلكترونية جديدة
     */
    public function addEwallet($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "ewallets SET 
            name = '" . $this->db->escape($data['name']) . "',
            provider_id = '" . (int)$data['provider_id'] . "',
            account_number = '" . $this->db->escape($data['account_number']) . "',
            account_id = '" . (int)$data['account_id'] . "',
            currency_code = '" . $this->db->escape($data['currency_code'] ?? $this->config->get('config_currency')) . "',
            opening_balance = '" . (float)($data['opening_balance'] ?? 0) . "',
            current_balance = '" . (float)($data['opening_balance'] ?? 0) . "',
            commission_rate = '" . (float)($data['commission_rate'] ?? 0) . "',
            is_active = '" . (int)($data['is_active'] ?? 1) . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
        
        $ewallet_id = $this->db->getLastId();
        
        // إنشاء قيد افتتاحي إذا كان هناك رصيد افتتاحي
        if (!empty($data['opening_balance']) && $data['opening_balance'] > 0) {
            $this->createOpeningBalanceEntry($ewallet_id, $data['opening_balance'], $data['account_id']);
        }
        
        return $ewallet_id;
    }
    
    /**
     * تعديل محفظة إلكترونية موجودة
     */
    public function editEwallet($ewallet_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "ewallets SET 
            name = '" . $this->db->escape($data['name']) . "',
            provider_id = '" . (int)$data['provider_id'] . "',
            account_number = '" . $this->db->escape($data['account_number']) . "',
            account_id = '" . (int)$data['account_id'] . "',
            currency_code = '" . $this->db->escape($data['currency_code'] ?? $this->config->get('config_currency')) . "',
            commission_rate = '" . (float)($data['commission_rate'] ?? 0) . "',
            is_active = '" . (int)($data['is_active'] ?? 1) . "',
            date_modified = NOW()
            WHERE ewallet_id = '" . (int)$ewallet_id . "'");
        
        return true;
    }
    
    /**
     * حذف محفظة إلكترونية
     */
    public function deleteEwallet($ewallet_id) {
        // التحقق من وجود حركات
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "ewallet_transactions WHERE ewallet_id = '" . (int)$ewallet_id . "'");
        if ($query->row['total'] > 0) {
            return false; // لا يمكن حذف محفظة لها حركات
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "ewallets WHERE ewallet_id = '" . (int)$ewallet_id . "'");
        return true;
    }
    
    /**
     * الحصول على محفظة إلكترونية واحدة
     */
    public function getEwallet($ewallet_id) {
        $query = $this->db->query("SELECT ew.*, ep.name as provider_name, ep.logo as provider_logo,
                                          a.account_code, ad.name as account_name
                                  FROM " . DB_PREFIX . "ewallets ew
                                  LEFT JOIN " . DB_PREFIX . "ewallet_providers ep ON ew.provider_id = ep.provider_id
                                  LEFT JOIN " . DB_PREFIX . "accounts a ON ew.account_id = a.account_id
                                  LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  WHERE ew.ewallet_id = '" . (int)$ewallet_id . "'");
        return $query->row;
    }
    
    /**
     * الحصول على قائمة المحافظ الإلكترونية
     */
    public function getEwallets($data = array()) {
        $sql = "SELECT ew.*, ep.name as provider_name, ep.logo as provider_logo,
                       a.account_code, ad.name as account_name
                FROM " . DB_PREFIX . "ewallets ew
                LEFT JOIN " . DB_PREFIX . "ewallet_providers ep ON ew.provider_id = ep.provider_id
                LEFT JOIN " . DB_PREFIX . "accounts a ON ew.account_id = a.account_id
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                WHERE 1";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND ew.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (!empty($data['filter_provider'])) {
            $sql .= " AND ew.provider_id = '" . (int)$data['filter_provider'] . "'";
        }
        
        if (isset($data['filter_active'])) {
            $sql .= " AND ew.is_active = '" . (int)$data['filter_active'] . "'";
        }
        
        $sql .= " ORDER BY ep.name ASC, ew.name ASC";
        
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
     * الحصول على إجمالي عدد المحافظ الإلكترونية
     */
    public function getTotalEwallets($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ewallets ew WHERE 1";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND ew.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (!empty($data['filter_provider'])) {
            $sql .= " AND ew.provider_id = '" . (int)$data['filter_provider'] . "'";
        }
        
        if (isset($data['filter_active'])) {
            $sql .= " AND ew.is_active = '" . (int)$data['filter_active'] . "'";
        }
        
        $query = $this->db->query($sql);
        return $query->row['total'];
    }
    
    /**
     * الحصول على مقدمي خدمة المحافظ الإلكترونية
     */
    public function getEwalletProviders() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ewallet_providers WHERE is_active = 1 ORDER BY name ASC");
        return $query->rows;
    }
    
    /**
     * إضافة حركة محفظة إلكترونية
     */
    public function addEwalletTransaction($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "ewallet_transactions SET 
            ewallet_id = '" . (int)$data['ewallet_id'] . "',
            transaction_type = '" . $this->db->escape($data['transaction_type']) . "',
            amount = '" . (float)$data['amount'] . "',
            commission_amount = '" . (float)($data['commission_amount'] ?? 0) . "',
            net_amount = '" . (float)($data['net_amount'] ?? $data['amount']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            reference_number = '" . $this->db->escape($data['reference_number'] ?? '') . "',
            transaction_date = '" . $this->db->escape($data['transaction_date']) . "',
            external_reference = '" . $this->db->escape($data['external_reference'] ?? '') . "',
            status = '" . $this->db->escape($data['status'] ?? 'completed') . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
        
        $transaction_id = $this->db->getLastId();
        
        // تحديث رصيد المحفظة
        $this->updateEwalletBalance($data['ewallet_id']);
        
        // إنشاء قيد محاسبي
        $this->createJournalEntry($transaction_id, $data);
        
        return $transaction_id;
    }
    
    /**
     * تحديث رصيد المحفظة الإلكترونية
     */
    public function updateEwalletBalance($ewallet_id) {
        $query = $this->db->query("SELECT 
                                    COALESCE(SUM(CASE WHEN transaction_type = 'deposit' THEN net_amount ELSE 0 END), 0) as total_deposits,
                                    COALESCE(SUM(CASE WHEN transaction_type = 'withdrawal' THEN net_amount ELSE 0 END), 0) as total_withdrawals
                                  FROM " . DB_PREFIX . "ewallet_transactions 
                                  WHERE ewallet_id = '" . (int)$ewallet_id . "'
                                  AND status = 'completed'");
        
        $ewallet = $this->getEwallet($ewallet_id);
        $opening_balance = (float)$ewallet['opening_balance'];
        $total_deposits = (float)$query->row['total_deposits'];
        $total_withdrawals = (float)$query->row['total_withdrawals'];
        $current_balance = $opening_balance + $total_deposits - $total_withdrawals;
        
        $this->db->query("UPDATE " . DB_PREFIX . "ewallets 
                         SET current_balance = '" . (float)$current_balance . "' 
                         WHERE ewallet_id = '" . (int)$ewallet_id . "'");
        
        return $current_balance;
    }
    
    /**
     * إنشاء قيد افتتاحي
     */
    private function createOpeningBalanceEntry($ewallet_id, $amount, $account_id) {
        $this->load->model('accounts/journal_entry');
        
        $ewallet = $this->getEwallet($ewallet_id);
        
        $journal_data = [
            'journal_date' => date('Y-m-d'),
            'journal_number' => 'EWALLET-OPEN-' . $ewallet_id,
            'description' => 'رصيد افتتاحي للمحفظة الإلكترونية: ' . $ewallet['name'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'lines' => [
                [
                    'account_id' => $account_id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => 'رصيد افتتاحي'
                ],
                [
                    'account_id' => $this->getEquityAccountId(),
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => 'رصيد افتتاحي'
                ]
            ]
        ];
        
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }
    
    /**
     * إنشاء قيد محاسبي للحركة
     */
    private function createJournalEntry($transaction_id, $data) {
        $this->load->model('accounts/journal_entry');
        
        $ewallet = $this->getEwallet($data['ewallet_id']);
        
        $journal_data = [
            'journal_date' => $data['transaction_date'],
            'journal_number' => 'EWALLET-' . $transaction_id,
            'description' => $data['description'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'ewallet_transaction',
            'reference_id' => $transaction_id
        ];
        
        $lines = [];
        
        if ($data['transaction_type'] == 'deposit') {
            // قيد إيداع في المحفظة
            $lines[] = [
                'account_id' => $ewallet['account_id'],
                'debit_amount' => $data['net_amount'],
                'credit_amount' => 0,
                'description' => $data['description']
            ];
            
            // عمولة المحفظة (إن وجدت)
            if (!empty($data['commission_amount']) && $data['commission_amount'] > 0) {
                $lines[] = [
                    'account_id' => $this->getCommissionExpenseAccountId(),
                    'debit_amount' => $data['commission_amount'],
                    'credit_amount' => 0,
                    'description' => 'عمولة المحفظة الإلكترونية'
                ];
            }
            
            // المصدر (نقدية أو بنك)
            $lines[] = [
                'account_id' => $this->getDefaultIncomeAccountId(),
                'debit_amount' => 0,
                'credit_amount' => $data['amount'],
                'description' => $data['description']
            ];
            
        } else {
            // قيد سحب من المحفظة
            $lines[] = [
                'account_id' => $this->getDefaultExpenseAccountId(),
                'debit_amount' => $data['net_amount'],
                'credit_amount' => 0,
                'description' => $data['description']
            ];
            
            // عمولة المحفظة (إن وجدت)
            if (!empty($data['commission_amount']) && $data['commission_amount'] > 0) {
                $lines[] = [
                    'account_id' => $this->getCommissionExpenseAccountId(),
                    'debit_amount' => $data['commission_amount'],
                    'credit_amount' => 0,
                    'description' => 'عمولة المحفظة الإلكترونية'
                ];
            }
            
            // المحفظة
            $lines[] = [
                'account_id' => $ewallet['account_id'],
                'debit_amount' => 0,
                'credit_amount' => $data['amount'],
                'description' => $data['description']
            ];
        }
        
        $journal_data['lines'] = $lines;
        
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }
    
    /**
     * الحصول على ملخص المحفظة الإلكترونية
     */
    public function getEwalletSummary($ewallet_id, $date_start = null, $date_end = null) {
        $currency_code = $this->config->get('config_currency');
        
        $ewallet = $this->getEwallet($ewallet_id);
        if (!$ewallet) {
            return array();
        }
        
        $sql = "SELECT 
                    COUNT(*) as transaction_count,
                    COALESCE(SUM(CASE WHEN transaction_type = 'deposit' THEN net_amount ELSE 0 END), 0) as total_deposits,
                    COALESCE(SUM(CASE WHEN transaction_type = 'withdrawal' THEN net_amount ELSE 0 END), 0) as total_withdrawals,
                    COALESCE(SUM(commission_amount), 0) as total_commissions
                FROM " . DB_PREFIX . "ewallet_transactions 
                WHERE ewallet_id = '" . (int)$ewallet_id . "'
                AND status = 'completed'";
        
        if ($date_start) {
            $sql .= " AND DATE(transaction_date) >= '" . $this->db->escape($date_start) . "'";
        }
        
        if ($date_end) {
            $sql .= " AND DATE(transaction_date) <= '" . $this->db->escape($date_end) . "'";
        }
        
        $query = $this->db->query($sql);
        $stats = $query->row;
        
        $opening_balance = (float)$ewallet['opening_balance'];
        $total_deposits = (float)$stats['total_deposits'];
        $total_withdrawals = (float)$stats['total_withdrawals'];
        $current_balance = $opening_balance + $total_deposits - $total_withdrawals;
        
        return array(
            'ewallet' => $ewallet,
            'opening_balance' => $opening_balance,
            'opening_balance_formatted' => $this->currency->format($opening_balance, $currency_code),
            'total_deposits' => $total_deposits,
            'total_deposits_formatted' => $this->currency->format($total_deposits, $currency_code),
            'total_withdrawals' => $total_withdrawals,
            'total_withdrawals_formatted' => $this->currency->format($total_withdrawals, $currency_code),
            'current_balance' => $current_balance,
            'current_balance_formatted' => $this->currency->format($current_balance, $currency_code),
            'net_movement' => $total_deposits - $total_withdrawals,
            'net_movement_formatted' => $this->currency->format($total_deposits - $total_withdrawals, $currency_code),
            'total_commissions' => (float)$stats['total_commissions'],
            'total_commissions_formatted' => $this->currency->format((float)$stats['total_commissions'], $currency_code),
            'transaction_count' => (int)$stats['transaction_count']
        );
    }
    
    /**
     * الحصول على حساب حقوق الملكية الافتراضي
     */
    private function getEquityAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'equity' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
    
    /**
     * الحصول على حساب الإيرادات الافتراضي
     */
    private function getDefaultIncomeAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'revenue' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
    
    /**
     * الحصول على حساب المصروفات الافتراضي
     */
    private function getDefaultExpenseAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'expense' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
    
    /**
     * الحصول على حساب مصروفات العمولات
     */
    private function getCommissionExpenseAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'expense' AND account_code LIKE '%commission%' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : $this->getDefaultExpenseAccountId();
    }
}
