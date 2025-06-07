<?php
/**
 * نموذج معاملات بوابات الدفع المحسن
 * يدعم إدارة جميع معاملات بوابات الدفع مع التكامل المحاسبي الكامل
 */
class ModelPaymentTransaction extends Model {
    
    /**
     * إضافة معاملة دفع جديدة
     */
    public function addTransaction($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "payment_transactions SET 
            gateway_id = '" . (int)$data['gateway_id'] . "',
            external_transaction_id = '" . $this->db->escape($data['external_transaction_id']) . "',
            transaction_type = '" . $this->db->escape($data['transaction_type']) . "',
            amount = '" . (float)$data['amount'] . "',
            commission_amount = '" . (float)($data['commission_amount'] ?? 0) . "',
            net_amount = '" . (float)($data['net_amount'] ?? $data['amount']) . "',
            currency_code = '" . $this->db->escape($data['currency_code'] ?? $this->config->get('config_currency')) . "',
            status = '" . $this->db->escape($data['status'] ?? 'pending') . "',
            transaction_date = '" . $this->db->escape($data['transaction_date'] ?? date('Y-m-d H:i:s')) . "',
            customer_reference = '" . $this->db->escape($data['customer_reference'] ?? '') . "',
            order_id = '" . (int)($data['order_id'] ?? 0) . "',
            customer_id = '" . (int)($data['customer_id'] ?? 0) . "',
            description = '" . $this->db->escape($data['description'] ?? '') . "',
            metadata = '" . $this->db->escape($data['metadata'] ?? '{}') . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
        
        $transaction_id = $this->db->getLastId();
        
        // إنشاء قيد محاسبي للمعاملة
        $this->createTransactionEntry($transaction_id, $data);
        
        // تحديث حالة الطلب إذا كان موجود
        if (!empty($data['order_id'])) {
            $this->updateOrderPaymentStatus($data['order_id'], $data['status']);
        }
        
        return $transaction_id;
    }
    
    /**
     * تعديل معاملة دفع موجودة
     */
    public function editTransaction($transaction_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "payment_transactions SET 
            status = '" . $this->db->escape($data['status']) . "',
            commission_amount = '" . (float)($data['commission_amount'] ?? 0) . "',
            net_amount = '" . (float)($data['net_amount'] ?? 0) . "',
            description = '" . $this->db->escape($data['description'] ?? '') . "',
            metadata = '" . $this->db->escape($data['metadata'] ?? '{}') . "',
            date_modified = NOW()
            WHERE transaction_id = '" . (int)$transaction_id . "'");
        
        // تحديث القيد المحاسبي إذا تغيرت الحالة
        if (isset($data['status'])) {
            $this->updateTransactionEntry($transaction_id, $data['status']);
        }
        
        return true;
    }
    
    /**
     * حذف معاملة دفع
     */
    public function deleteTransaction($transaction_id) {
        // التحقق من إمكانية الحذف
        $transaction = $this->getTransaction($transaction_id);
        if ($transaction && in_array($transaction['status'], ['completed', 'settled'])) {
            return false; // لا يمكن حذف معاملة مكتملة
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "payment_transactions WHERE transaction_id = '" . (int)$transaction_id . "'");
        return true;
    }
    
    /**
     * الحصول على معاملة دفع واحدة
     */
    public function getTransaction($transaction_id) {
        $query = $this->db->query("SELECT pt.*, pg.name as gateway_name, pg.provider_id,
                                          pgp.name as provider_name, pgp.logo as provider_logo,
                                          o.order_id, o.total as order_total,
                                          CONCAT(c.firstname, ' ', c.lastname) as customer_name
                                  FROM " . DB_PREFIX . "payment_transactions pt
                                  LEFT JOIN " . DB_PREFIX . "payment_gateways pg ON pt.gateway_id = pg.gateway_id
                                  LEFT JOIN " . DB_PREFIX . "payment_gateway_providers pgp ON pg.provider_id = pgp.provider_id
                                  LEFT JOIN " . DB_PREFIX . "order o ON pt.order_id = o.order_id
                                  LEFT JOIN " . DB_PREFIX . "customer c ON pt.customer_id = c.customer_id
                                  WHERE pt.transaction_id = '" . (int)$transaction_id . "'");
        return $query->row;
    }
    
    /**
     * الحصول على قائمة معاملات الدفع
     */
    public function getTransactions($data = array()) {
        $sql = "SELECT pt.*, pg.name as gateway_name, pgp.name as provider_name,
                       o.order_id, CONCAT(c.firstname, ' ', c.lastname) as customer_name
                FROM " . DB_PREFIX . "payment_transactions pt
                LEFT JOIN " . DB_PREFIX . "payment_gateways pg ON pt.gateway_id = pg.gateway_id
                LEFT JOIN " . DB_PREFIX . "payment_gateway_providers pgp ON pg.provider_id = pgp.provider_id
                LEFT JOIN " . DB_PREFIX . "order o ON pt.order_id = o.order_id
                LEFT JOIN " . DB_PREFIX . "customer c ON pt.customer_id = c.customer_id
                WHERE 1";
        
        if (!empty($data['filter_gateway_id'])) {
            $sql .= " AND pt.gateway_id = '" . (int)$data['filter_gateway_id'] . "'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND pt.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_transaction_type'])) {
            $sql .= " AND pt.transaction_type = '" . $this->db->escape($data['filter_transaction_type']) . "'";
        }
        
        if (!empty($data['filter_external_id'])) {
            $sql .= " AND pt.external_transaction_id LIKE '%" . $this->db->escape($data['filter_external_id']) . "%'";
        }
        
        if (!empty($data['filter_customer_reference'])) {
            $sql .= " AND pt.customer_reference LIKE '%" . $this->db->escape($data['filter_customer_reference']) . "%'";
        }
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pt.transaction_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pt.transaction_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_amount_min'])) {
            $sql .= " AND pt.amount >= '" . (float)$data['filter_amount_min'] . "'";
        }
        
        if (!empty($data['filter_amount_max'])) {
            $sql .= " AND pt.amount <= '" . (float)$data['filter_amount_max'] . "'";
        }
        
        $sql .= " ORDER BY pt.transaction_date DESC, pt.transaction_id DESC";
        
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
     * الحصول على إجمالي عدد المعاملات
     */
    public function getTotalTransactions($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "payment_transactions pt WHERE 1";
        
        if (!empty($data['filter_gateway_id'])) {
            $sql .= " AND pt.gateway_id = '" . (int)$data['filter_gateway_id'] . "'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND pt.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_transaction_type'])) {
            $sql .= " AND pt.transaction_type = '" . $this->db->escape($data['filter_transaction_type']) . "'";
        }
        
        if (!empty($data['filter_external_id'])) {
            $sql .= " AND pt.external_transaction_id LIKE '%" . $this->db->escape($data['filter_external_id']) . "%'";
        }
        
        if (!empty($data['filter_customer_reference'])) {
            $sql .= " AND pt.customer_reference LIKE '%" . $this->db->escape($data['filter_customer_reference']) . "%'";
        }
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pt.transaction_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pt.transaction_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_amount_min'])) {
            $sql .= " AND pt.amount >= '" . (float)$data['filter_amount_min'] . "'";
        }
        
        if (!empty($data['filter_amount_max'])) {
            $sql .= " AND pt.amount <= '" . (float)$data['filter_amount_max'] . "'";
        }
        
        $query = $this->db->query($sql);
        return $query->row['total'];
    }
    
    /**
     * تسوية معاملة
     */
    public function settleTransaction($transaction_id, $settlement_data) {
        try {
            $this->db->query("START TRANSACTION");
            
            $transaction = $this->getTransaction($transaction_id);
            if (!$transaction || $transaction['status'] != 'completed') {
                throw new Exception('المعاملة غير متاحة للتسوية');
            }
            
            // تحديث حالة المعاملة
            $this->db->query("UPDATE " . DB_PREFIX . "payment_transactions SET 
                status = 'settled',
                settlement_date = '" . $this->db->escape($settlement_data['settlement_date'] ?? date('Y-m-d H:i:s')) . "',
                settlement_amount = '" . (float)($settlement_data['settlement_amount'] ?? $transaction['net_amount']) . "',
                settlement_reference = '" . $this->db->escape($settlement_data['settlement_reference'] ?? '') . "',
                date_modified = NOW()
                WHERE transaction_id = '" . (int)$transaction_id . "'");
            
            // إنشاء قيد التسوية
            $this->createSettlementEntry($transaction_id, $settlement_data);
            
            $this->db->query("COMMIT");
            
            return array('success' => true);
            
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * استرداد معاملة
     */
    public function refundTransaction($transaction_id, $refund_data) {
        try {
            $this->db->query("START TRANSACTION");
            
            $transaction = $this->getTransaction($transaction_id);
            if (!$transaction || !in_array($transaction['status'], ['completed', 'settled'])) {
                throw new Exception('المعاملة غير متاحة للاسترداد');
            }
            
            $refund_amount = (float)($refund_data['refund_amount'] ?? $transaction['amount']);
            
            // إنشاء معاملة استرداد
            $refund_transaction_data = array(
                'gateway_id' => $transaction['gateway_id'],
                'external_transaction_id' => $refund_data['external_refund_id'] ?? 'REFUND-' . $transaction_id,
                'transaction_type' => 'refund',
                'amount' => $refund_amount,
                'commission_amount' => 0,
                'net_amount' => $refund_amount,
                'currency_code' => $transaction['currency_code'],
                'status' => 'completed',
                'transaction_date' => $refund_data['refund_date'] ?? date('Y-m-d H:i:s'),
                'customer_reference' => $transaction['customer_reference'],
                'order_id' => $transaction['order_id'],
                'customer_id' => $transaction['customer_id'],
                'description' => 'استرداد للمعاملة #' . $transaction_id . ' - ' . ($refund_data['reason'] ?? ''),
                'metadata' => json_encode(array(
                    'original_transaction_id' => $transaction_id,
                    'refund_reason' => $refund_data['reason'] ?? '',
                    'refund_type' => $refund_data['refund_type'] ?? 'full'
                ))
            );
            
            $refund_transaction_id = $this->addTransaction($refund_transaction_data);
            
            // تحديث المعاملة الأصلية
            $new_status = ($refund_amount >= $transaction['amount']) ? 'refunded' : 'partially_refunded';
            $this->db->query("UPDATE " . DB_PREFIX . "payment_transactions SET 
                status = '" . $this->db->escape($new_status) . "',
                refund_amount = COALESCE(refund_amount, 0) + '" . (float)$refund_amount . "',
                date_modified = NOW()
                WHERE transaction_id = '" . (int)$transaction_id . "'");
            
            $this->db->query("COMMIT");
            
            return array('success' => true, 'refund_transaction_id' => $refund_transaction_id);
            
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * إنشاء قيد محاسبي للمعاملة
     */
    private function createTransactionEntry($transaction_id, $data) {
        $this->load->model('accounts/journal_entry');
        
        $gateway = $this->getGatewayInfo($data['gateway_id']);
        
        $journal_data = [
            'journal_date' => $data['transaction_date'] ?? date('Y-m-d'),
            'journal_number' => 'PAY-' . $transaction_id,
            'description' => $data['description'] ?: 'معاملة دفع إلكتروني عبر ' . $gateway['name'],
            'status' => ($data['status'] == 'completed') ? 'posted' : 'draft',
            'created_by' => $this->user->getId(),
            'reference_type' => 'payment_transaction',
            'reference_id' => $transaction_id
        ];
        
        $lines = [];
        
        if ($data['transaction_type'] == 'payment') {
            // قيد استلام دفعة
            $lines[] = [
                'account_id' => $gateway['account_id'],
                'debit_amount' => $data['net_amount'],
                'credit_amount' => 0,
                'description' => 'استلام دفعة إلكترونية'
            ];
            
            // عمولة البوابة
            if (!empty($data['commission_amount']) && $data['commission_amount'] > 0) {
                $lines[] = [
                    'account_id' => $gateway['commission_account_id'],
                    'debit_amount' => $data['commission_amount'],
                    'credit_amount' => 0,
                    'description' => 'عمولة بوابة الدفع'
                ];
            }
            
            // حساب العميل أو المبيعات
            $lines[] = [
                'account_id' => $this->getCustomerAccountId($data['customer_id']),
                'debit_amount' => 0,
                'credit_amount' => $data['amount'],
                'description' => 'تحصيل من العميل'
            ];
            
        } elseif ($data['transaction_type'] == 'refund') {
            // قيد استرداد
            $lines[] = [
                'account_id' => $this->getCustomerAccountId($data['customer_id']),
                'debit_amount' => $data['amount'],
                'credit_amount' => 0,
                'description' => 'استرداد للعميل'
            ];
            
            $lines[] = [
                'account_id' => $gateway['account_id'],
                'debit_amount' => 0,
                'credit_amount' => $data['amount'],
                'description' => 'استرداد عبر بوابة الدفع'
            ];
        }
        
        $journal_data['lines'] = $lines;
        
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }
    
    /**
     * تحديث القيد المحاسبي
     */
    private function updateTransactionEntry($transaction_id, $status) {
        if ($status == 'completed') {
            // تحويل القيد من مسودة إلى مرحل
            $this->db->query("UPDATE " . DB_PREFIX . "journal_entry SET 
                status = 'posted' 
                WHERE reference_type = 'payment_transaction' 
                AND reference_id = '" . (int)$transaction_id . "'");
        } elseif ($status == 'failed' || $status == 'cancelled') {
            // حذف القيد أو تحويله إلى ملغي
            $this->db->query("UPDATE " . DB_PREFIX . "journal_entry SET 
                status = 'cancelled' 
                WHERE reference_type = 'payment_transaction' 
                AND reference_id = '" . (int)$transaction_id . "'");
        }
    }
    
    /**
     * إنشاء قيد التسوية
     */
    private function createSettlementEntry($transaction_id, $settlement_data) {
        $this->load->model('accounts/journal_entry');
        
        $transaction = $this->getTransaction($transaction_id);
        $gateway = $this->getGatewayInfo($transaction['gateway_id']);
        
        $journal_data = [
            'journal_date' => $settlement_data['settlement_date'] ?? date('Y-m-d'),
            'journal_number' => 'SETTLE-' . $transaction_id,
            'description' => 'تسوية معاملة دفع إلكتروني #' . $transaction_id,
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'payment_settlement',
            'reference_id' => $transaction_id,
            'lines' => [
                [
                    'account_id' => $this->getBankAccountId(),
                    'debit_amount' => $settlement_data['settlement_amount'] ?? $transaction['net_amount'],
                    'credit_amount' => 0,
                    'description' => 'تسوية من بوابة الدفع'
                ],
                [
                    'account_id' => $gateway['account_id'],
                    'debit_amount' => 0,
                    'credit_amount' => $settlement_data['settlement_amount'] ?? $transaction['net_amount'],
                    'description' => 'تسوية بوابة الدفع'
                ]
            ]
        ];
        
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }
    
    /**
     * تحديث حالة دفع الطلب
     */
    private function updateOrderPaymentStatus($order_id, $status) {
        $payment_status = 'pending';
        
        switch ($status) {
            case 'completed':
                $payment_status = 'paid';
                break;
            case 'failed':
            case 'cancelled':
                $payment_status = 'failed';
                break;
            case 'refunded':
                $payment_status = 'refunded';
                break;
        }
        
        $this->db->query("UPDATE " . DB_PREFIX . "order SET 
            payment_status = '" . $this->db->escape($payment_status) . "' 
            WHERE order_id = '" . (int)$order_id . "'");
    }
    
    /**
     * دوال مساعدة
     */
    private function getGatewayInfo($gateway_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "payment_gateways WHERE gateway_id = '" . (int)$gateway_id . "'");
        return $query->row;
    }
    
    private function getCustomerAccountId($customer_id) {
        if ($customer_id) {
            $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "customers WHERE customer_id = '" . (int)$customer_id . "'");
            if ($query->num_rows) {
                return $query->row['account_id'];
            }
        }
        return $this->getDefaultReceivableAccountId();
    }
    
    private function getDefaultReceivableAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'asset' AND account_code LIKE '%receivable%' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
    
    private function getBankAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'asset' AND account_code LIKE '%bank%' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
}
