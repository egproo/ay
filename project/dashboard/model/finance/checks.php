<?php
/**
 * نموذج إدارة الشيكات المحسن
 * يدعم إدارة الشيكات الواردة والصادرة وتتبع حالاتها والتكامل المحاسبي
 */
class ModelFinanceChecks extends Model {
    
    /**
     * إضافة شيك جديد
     */
    public function addCheck($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "checks SET 
            check_type = '" . $this->db->escape($data['check_type']) . "',
            check_number = '" . $this->db->escape($data['check_number']) . "',
            amount = '" . (float)$data['amount'] . "',
            check_date = '" . $this->db->escape($data['check_date']) . "',
            due_date = '" . $this->db->escape($data['due_date']) . "',
            bank_id = '" . (int)$data['bank_id'] . "',
            drawer_name = '" . $this->db->escape($data['drawer_name']) . "',
            drawer_id = '" . (int)($data['drawer_id'] ?? 0) . "',
            drawer_type = '" . $this->db->escape($data['drawer_type'] ?? 'customer') . "',
            notes = '" . $this->db->escape($data['notes'] ?? '') . "',
            status = 'pending',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
        
        $check_id = $this->db->getLastId();
        
        // إنشاء قيد محاسبي للشيك
        $this->createCheckEntry($check_id, $data);
        
        return $check_id;
    }
    
    /**
     * تعديل شيك موجود
     */
    public function editCheck($check_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "checks SET 
            check_type = '" . $this->db->escape($data['check_type']) . "',
            check_number = '" . $this->db->escape($data['check_number']) . "',
            amount = '" . (float)$data['amount'] . "',
            check_date = '" . $this->db->escape($data['check_date']) . "',
            due_date = '" . $this->db->escape($data['due_date']) . "',
            bank_id = '" . (int)$data['bank_id'] . "',
            drawer_name = '" . $this->db->escape($data['drawer_name']) . "',
            drawer_id = '" . (int)($data['drawer_id'] ?? 0) . "',
            drawer_type = '" . $this->db->escape($data['drawer_type'] ?? 'customer') . "',
            notes = '" . $this->db->escape($data['notes'] ?? '') . "',
            date_modified = NOW()
            WHERE check_id = '" . (int)$check_id . "'");
        
        return true;
    }
    
    /**
     * حذف شيك
     */
    public function deleteCheck($check_id) {
        // التحقق من حالة الشيك
        $check = $this->getCheck($check_id);
        if ($check && $check['status'] != 'pending') {
            return false; // لا يمكن حذف شيك تم تحصيله أو ارتد
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "checks WHERE check_id = '" . (int)$check_id . "'");
        return true;
    }
    
    /**
     * الحصول على شيك واحد
     */
    public function getCheck($check_id) {
        $query = $this->db->query("SELECT c.*, ba.bank_name, ba.account_number
                                  FROM " . DB_PREFIX . "checks c
                                  LEFT JOIN " . DB_PREFIX . "bank_accounts ba ON c.bank_id = ba.bank_account_id
                                  WHERE c.check_id = '" . (int)$check_id . "'");
        return $query->row;
    }
    
    /**
     * الحصول على قائمة الشيكات
     */
    public function getChecks($data = array()) {
        $sql = "SELECT c.*, ba.bank_name, ba.account_number
                FROM " . DB_PREFIX . "checks c
                LEFT JOIN " . DB_PREFIX . "bank_accounts ba ON c.bank_id = ba.bank_account_id
                WHERE 1";
        
        if (!empty($data['filter_check_number'])) {
            $sql .= " AND c.check_number LIKE '%" . $this->db->escape($data['filter_check_number']) . "%'";
        }
        
        if (!empty($data['filter_type'])) {
            $sql .= " AND c.check_type = '" . $this->db->escape($data['filter_type']) . "'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND c.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_drawer_name'])) {
            $sql .= " AND c.drawer_name LIKE '%" . $this->db->escape($data['filter_drawer_name']) . "%'";
        }
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(c.check_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(c.check_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        $sql .= " ORDER BY c.check_date DESC, c.check_id DESC";
        
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
     * الحصول على إجمالي عدد الشيكات
     */
    public function getTotalChecks($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "checks c WHERE 1";
        
        if (!empty($data['filter_check_number'])) {
            $sql .= " AND c.check_number LIKE '%" . $this->db->escape($data['filter_check_number']) . "%'";
        }
        
        if (!empty($data['filter_type'])) {
            $sql .= " AND c.check_type = '" . $this->db->escape($data['filter_type']) . "'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND c.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_drawer_name'])) {
            $sql .= " AND c.drawer_name LIKE '%" . $this->db->escape($data['filter_drawer_name']) . "%'";
        }
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(c.check_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(c.check_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        $query = $this->db->query($sql);
        return $query->row['total'];
    }
    
    /**
     * تحصيل شيك
     */
    public function collectCheck($data) {
        try {
            $check_id = (int)$data['check_id'];
            $collection_date = $data['collection_date'];
            
            // التحقق من حالة الشيك
            $check = $this->getCheck($check_id);
            if (!$check || $check['status'] != 'pending') {
                return array('success' => false, 'error' => 'الشيك غير متاح للتحصيل');
            }
            
            $this->db->query("START TRANSACTION");
            
            // تحديث حالة الشيك
            $this->db->query("UPDATE " . DB_PREFIX . "checks SET 
                status = 'collected',
                collection_date = '" . $this->db->escape($collection_date) . "',
                collected_by = '" . (int)$this->user->getId() . "',
                date_modified = NOW()
                WHERE check_id = '" . (int)$check_id . "'");
            
            // إنشاء قيد التحصيل
            $this->createCollectionEntry($check, $collection_date);
            
            $this->db->query("COMMIT");
            
            return array('success' => true);
            
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * ارتداد شيك
     */
    public function bounceCheck($data) {
        try {
            $check_id = (int)$data['check_id'];
            $bounce_date = $data['bounce_date'];
            $bounce_reason = $data['bounce_reason'];
            
            // التحقق من حالة الشيك
            $check = $this->getCheck($check_id);
            if (!$check || !in_array($check['status'], ['pending', 'deposited'])) {
                return array('success' => false, 'error' => 'الشيك غير متاح للارتداد');
            }
            
            $this->db->query("START TRANSACTION");
            
            // تحديث حالة الشيك
            $this->db->query("UPDATE " . DB_PREFIX . "checks SET 
                status = 'bounced',
                bounce_date = '" . $this->db->escape($bounce_date) . "',
                bounce_reason = '" . $this->db->escape($bounce_reason) . "',
                bounced_by = '" . (int)$this->user->getId() . "',
                date_modified = NOW()
                WHERE check_id = '" . (int)$check_id . "'");
            
            // إنشاء قيد الارتداد
            $this->createBounceEntry($check, $bounce_date, $bounce_reason);
            
            $this->db->query("COMMIT");
            
            return array('success' => true);
            
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * إيداع شيكات في البنك
     */
    public function depositChecks($data) {
        try {
            $selected_checks = $data['selected_checks'];
            $deposit_date = $data['deposit_date'];
            $bank_account_id = (int)$data['bank_account_id'];
            
            $this->db->query("START TRANSACTION");
            
            $total_amount = 0;
            $check_numbers = array();
            
            foreach ($selected_checks as $check_id) {
                $check = $this->getCheck($check_id);
                if ($check && $check['status'] == 'pending') {
                    // تحديث حالة الشيك
                    $this->db->query("UPDATE " . DB_PREFIX . "checks SET 
                        status = 'deposited',
                        deposit_date = '" . $this->db->escape($deposit_date) . "',
                        deposited_bank_id = '" . (int)$bank_account_id . "',
                        deposited_by = '" . (int)$this->user->getId() . "',
                        date_modified = NOW()
                        WHERE check_id = '" . (int)$check_id . "'");
                    
                    $total_amount += $check['amount'];
                    $check_numbers[] = $check['check_number'];
                }
            }
            
            if ($total_amount > 0) {
                // إنشاء قيد الإيداع
                $this->createDepositEntry($bank_account_id, $total_amount, $deposit_date, $check_numbers);
            }
            
            $this->db->query("COMMIT");
            
            return array('success' => true, 'total_amount' => $total_amount);
            
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * إنشاء قيد محاسبي للشيك
     */
    private function createCheckEntry($check_id, $data) {
        $this->load->model('accounts/journal_entry');
        
        $journal_data = [
            'journal_date' => $data['check_date'],
            'journal_number' => 'CHECK-' . $check_id,
            'description' => 'شيك رقم ' . $data['check_number'] . ' من ' . $data['drawer_name'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'check',
            'reference_id' => $check_id
        ];
        
        if ($data['check_type'] == 'incoming') {
            // شيك وارد
            $journal_data['lines'] = [
                [
                    'account_id' => $this->getChecksReceivableAccountId(),
                    'debit_amount' => $data['amount'],
                    'credit_amount' => 0,
                    'description' => 'شيك تحت التحصيل'
                ],
                [
                    'account_id' => $this->getCustomerAccountId($data['drawer_id']),
                    'debit_amount' => 0,
                    'credit_amount' => $data['amount'],
                    'description' => 'تحصيل من العميل'
                ]
            ];
        } else {
            // شيك صادر
            $journal_data['lines'] = [
                [
                    'account_id' => $this->getSupplierAccountId($data['drawer_id']),
                    'debit_amount' => $data['amount'],
                    'credit_amount' => 0,
                    'description' => 'دفع للمورد'
                ],
                [
                    'account_id' => $this->getChecksPayableAccountId(),
                    'debit_amount' => 0,
                    'credit_amount' => $data['amount'],
                    'description' => 'شيك مؤجل الدفع'
                ]
            ];
        }
        
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }
    
    /**
     * إنشاء قيد التحصيل
     */
    private function createCollectionEntry($check, $collection_date) {
        $this->load->model('accounts/journal_entry');
        
        $journal_data = [
            'journal_date' => $collection_date,
            'journal_number' => 'COLLECT-' . $check['check_id'],
            'description' => 'تحصيل شيك رقم ' . $check['check_number'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'check_collection',
            'reference_id' => $check['check_id'],
            'lines' => [
                [
                    'account_id' => $this->getBankAccountId($check['bank_id']),
                    'debit_amount' => $check['amount'],
                    'credit_amount' => 0,
                    'description' => 'تحصيل الشيك'
                ],
                [
                    'account_id' => $this->getChecksReceivableAccountId(),
                    'debit_amount' => 0,
                    'credit_amount' => $check['amount'],
                    'description' => 'إقفال شيك تحت التحصيل'
                ]
            ]
        ];
        
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }
    
    /**
     * إنشاء قيد الارتداد
     */
    private function createBounceEntry($check, $bounce_date, $bounce_reason) {
        $this->load->model('accounts/journal_entry');
        
        $journal_data = [
            'journal_date' => $bounce_date,
            'journal_number' => 'BOUNCE-' . $check['check_id'],
            'description' => 'ارتداد شيك رقم ' . $check['check_number'] . ' - ' . $bounce_reason,
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'check_bounce',
            'reference_id' => $check['check_id'],
            'lines' => [
                [
                    'account_id' => $this->getCustomerAccountId($check['drawer_id']),
                    'debit_amount' => $check['amount'],
                    'credit_amount' => 0,
                    'description' => 'إعادة المبلغ للعميل'
                ],
                [
                    'account_id' => $this->getChecksReceivableAccountId(),
                    'debit_amount' => 0,
                    'credit_amount' => $check['amount'],
                    'description' => 'إقفال شيك تحت التحصيل'
                ]
            ]
        ];
        
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }
    
    /**
     * إنشاء قيد الإيداع
     */
    private function createDepositEntry($bank_account_id, $total_amount, $deposit_date, $check_numbers) {
        $this->load->model('accounts/journal_entry');
        
        $journal_data = [
            'journal_date' => $deposit_date,
            'journal_number' => 'DEPOSIT-' . date('Ymd'),
            'description' => 'إيداع شيكات: ' . implode(', ', $check_numbers),
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'check_deposit',
            'reference_id' => 0,
            'lines' => [
                [
                    'account_id' => $this->getBankAccountId($bank_account_id),
                    'debit_amount' => $total_amount,
                    'credit_amount' => 0,
                    'description' => 'إيداع الشيكات'
                ],
                [
                    'account_id' => $this->getChecksReceivableAccountId(),
                    'debit_amount' => 0,
                    'credit_amount' => $total_amount,
                    'description' => 'إقفال شيكات تحت التحصيل'
                ]
            ]
        ];
        
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }
    
    /**
     * الحصول على حساب الشيكات تحت التحصيل
     */
    private function getChecksReceivableAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'asset' AND account_code LIKE '%check%' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
    
    /**
     * الحصول على حساب الشيكات مؤجلة الدفع
     */
    private function getChecksPayableAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'liability' AND account_code LIKE '%check%' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
    
    /**
     * الحصول على حساب العميل
     */
    private function getCustomerAccountId($customer_id) {
        if ($customer_id) {
            $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "customers WHERE customer_id = '" . (int)$customer_id . "'");
            if ($query->num_rows) {
                return $query->row['account_id'];
            }
        }
        return $this->getDefaultReceivableAccountId();
    }
    
    /**
     * الحصول على حساب المورد
     */
    private function getSupplierAccountId($supplier_id) {
        if ($supplier_id) {
            $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "suppliers WHERE supplier_id = '" . (int)$supplier_id . "'");
            if ($query->num_rows) {
                return $query->row['account_id'];
            }
        }
        return $this->getDefaultPayableAccountId();
    }
    
    /**
     * الحصول على حساب البنك
     */
    private function getBankAccountId($bank_id) {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "bank_accounts WHERE bank_account_id = '" . (int)$bank_id . "'");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
    
    /**
     * الحصول على حساب العملاء الافتراضي
     */
    private function getDefaultReceivableAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'asset' AND account_code LIKE '%receivable%' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
    
    /**
     * الحصول على حساب الموردين الافتراضي
     */
    private function getDefaultPayableAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'liability' AND account_code LIKE '%payable%' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
}
