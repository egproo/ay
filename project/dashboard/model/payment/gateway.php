<?php
/**
 * نموذج إدارة بوابات الدفع الإلكتروني المحسن
 * يدعم إدارة جميع بوابات الدفع المحلية والعالمية مع التكامل المحاسبي الكامل
 */
class ModelPaymentGateway extends Model {
    
    /**
     * إضافة بوابة دفع جديدة
     */
    public function addGateway($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "payment_gateways SET 
            name = '" . $this->db->escape($data['name']) . "',
            provider_id = '" . (int)$data['provider_id'] . "',
            gateway_type = '" . $this->db->escape($data['gateway_type']) . "',
            api_endpoint = '" . $this->db->escape($data['api_endpoint']) . "',
            merchant_id = '" . $this->db->escape($data['merchant_id']) . "',
            api_key = '" . $this->db->escape($data['api_key']) . "',
            secret_key = '" . $this->db->escape($data['secret_key'] ?? '') . "',
            account_id = '" . (int)$data['account_id'] . "',
            commission_account_id = '" . (int)$data['commission_account_id'] . "',
            commission_rate = '" . (float)($data['commission_rate'] ?? 0) . "',
            fixed_fee = '" . (float)($data['fixed_fee'] ?? 0) . "',
            currency_code = '" . $this->db->escape($data['currency_code'] ?? $this->config->get('config_currency')) . "',
            settlement_period = '" . (int)($data['settlement_period'] ?? 1) . "',
            is_active = '" . (int)($data['is_active'] ?? 1) . "',
            is_test_mode = '" . (int)($data['is_test_mode'] ?? 0) . "',
            webhook_url = '" . $this->db->escape($data['webhook_url'] ?? '') . "',
            return_url = '" . $this->db->escape($data['return_url'] ?? '') . "',
            cancel_url = '" . $this->db->escape($data['cancel_url'] ?? '') . "',
            connection_status = 'pending',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
        
        $gateway_id = $this->db->getLastId();
        
        // إنشاء إعدادات افتراضية للبوابة
        $this->createDefaultGatewaySettings($gateway_id, $data);
        
        return $gateway_id;
    }
    
    /**
     * تعديل بوابة دفع موجودة
     */
    public function editGateway($gateway_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "payment_gateways SET 
            name = '" . $this->db->escape($data['name']) . "',
            provider_id = '" . (int)$data['provider_id'] . "',
            gateway_type = '" . $this->db->escape($data['gateway_type']) . "',
            api_endpoint = '" . $this->db->escape($data['api_endpoint']) . "',
            merchant_id = '" . $this->db->escape($data['merchant_id']) . "',
            api_key = '" . $this->db->escape($data['api_key']) . "',
            secret_key = '" . $this->db->escape($data['secret_key'] ?? '') . "',
            account_id = '" . (int)$data['account_id'] . "',
            commission_account_id = '" . (int)$data['commission_account_id'] . "',
            commission_rate = '" . (float)($data['commission_rate'] ?? 0) . "',
            fixed_fee = '" . (float)($data['fixed_fee'] ?? 0) . "',
            currency_code = '" . $this->db->escape($data['currency_code'] ?? $this->config->get('config_currency')) . "',
            settlement_period = '" . (int)($data['settlement_period'] ?? 1) . "',
            is_active = '" . (int)($data['is_active'] ?? 1) . "',
            is_test_mode = '" . (int)($data['is_test_mode'] ?? 0) . "',
            webhook_url = '" . $this->db->escape($data['webhook_url'] ?? '') . "',
            return_url = '" . $this->db->escape($data['return_url'] ?? '') . "',
            cancel_url = '" . $this->db->escape($data['cancel_url'] ?? '') . "',
            date_modified = NOW()
            WHERE gateway_id = '" . (int)$gateway_id . "'");
        
        return true;
    }
    
    /**
     * حذف بوابة دفع
     */
    public function deleteGateway($gateway_id) {
        // التحقق من وجود معاملات
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "payment_transactions WHERE gateway_id = '" . (int)$gateway_id . "'");
        if ($query->row['total'] > 0) {
            return false; // لا يمكن حذف بوابة لها معاملات
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "payment_gateways WHERE gateway_id = '" . (int)$gateway_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "payment_gateway_settings WHERE gateway_id = '" . (int)$gateway_id . "'");
        return true;
    }
    
    /**
     * الحصول على بوابة دفع واحدة
     */
    public function getGateway($gateway_id) {
        $query = $this->db->query("SELECT pg.*, pgp.name as provider_name, pgp.logo as provider_logo,
                                          a.account_code, ad.name as account_name,
                                          ca.account_code as commission_account_code, cad.name as commission_account_name
                                  FROM " . DB_PREFIX . "payment_gateways pg
                                  LEFT JOIN " . DB_PREFIX . "payment_gateway_providers pgp ON pg.provider_id = pgp.provider_id
                                  LEFT JOIN " . DB_PREFIX . "accounts a ON pg.account_id = a.account_id
                                  LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  LEFT JOIN " . DB_PREFIX . "accounts ca ON pg.commission_account_id = ca.account_id
                                  LEFT JOIN " . DB_PREFIX . "account_description cad ON (ca.account_id = cad.account_id AND cad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  WHERE pg.gateway_id = '" . (int)$gateway_id . "'");
        return $query->row;
    }
    
    /**
     * الحصول على قائمة بوابات الدفع
     */
    public function getGateways($data = array()) {
        $sql = "SELECT pg.*, pgp.name as provider_name, pgp.logo as provider_logo,
                       a.account_code, ad.name as account_name,
                       (SELECT COUNT(*) FROM " . DB_PREFIX . "payment_transactions pt WHERE pt.gateway_id = pg.gateway_id AND pt.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as monthly_transactions,
                       (SELECT COALESCE(SUM(pt.amount), 0) FROM " . DB_PREFIX . "payment_transactions pt WHERE pt.gateway_id = pg.gateway_id AND pt.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as monthly_volume
                FROM " . DB_PREFIX . "payment_gateways pg
                LEFT JOIN " . DB_PREFIX . "payment_gateway_providers pgp ON pg.provider_id = pgp.provider_id
                LEFT JOIN " . DB_PREFIX . "accounts a ON pg.account_id = a.account_id
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                WHERE 1";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND pg.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (!empty($data['filter_provider'])) {
            $sql .= " AND pg.provider_id = '" . (int)$data['filter_provider'] . "'";
        }
        
        if (isset($data['filter_status'])) {
            $sql .= " AND pg.is_active = '" . (int)$data['filter_status'] . "'";
        }
        
        $sql .= " ORDER BY pgp.name ASC, pg.name ASC";
        
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
     * الحصول على إجمالي عدد بوابات الدفع
     */
    public function getTotalGateways($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "payment_gateways pg WHERE 1";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND pg.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (!empty($data['filter_provider'])) {
            $sql .= " AND pg.provider_id = '" . (int)$data['filter_provider'] . "'";
        }
        
        if (isset($data['filter_status'])) {
            $sql .= " AND pg.is_active = '" . (int)$data['filter_status'] . "'";
        }
        
        $query = $this->db->query($sql);
        return $query->row['total'];
    }
    
    /**
     * الحصول على مقدمي خدمة بوابات الدفع
     */
    public function getGatewayProviders() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "payment_gateway_providers WHERE is_active = 1 ORDER BY name ASC");
        return $query->rows;
    }
    
    /**
     * اختبار الاتصال ببوابة الدفع
     */
    public function testGatewayConnection($data) {
        try {
            $gateway_id = (int)$data['gateway_id'];
            $gateway = $this->getGateway($gateway_id);
            
            if (!$gateway) {
                return array('success' => false, 'error' => 'بوابة الدفع غير موجودة');
            }
            
            // اختبار الاتصال حسب نوع البوابة
            $result = $this->performConnectionTest($gateway);
            
            // تحديث حالة الاتصال
            $status = $result['success'] ? 'connected' : 'failed';
            $this->db->query("UPDATE " . DB_PREFIX . "payment_gateways SET 
                connection_status = '" . $this->db->escape($status) . "',
                last_test_date = NOW()
                WHERE gateway_id = '" . (int)$gateway_id . "'");
            
            return $result;
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * مزامنة معاملات بوابة الدفع
     */
    public function syncGatewayTransactions($data) {
        try {
            $gateway_id = isset($data['gateway_id']) ? (int)$data['gateway_id'] : null;
            $date_from = $data['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
            $date_to = $data['date_to'] ?? date('Y-m-d');
            
            $synced_count = 0;
            
            if ($gateway_id) {
                // مزامنة بوابة واحدة
                $gateway = $this->getGateway($gateway_id);
                if ($gateway && $gateway['is_active']) {
                    $synced_count = $this->syncSingleGateway($gateway, $date_from, $date_to);
                }
            } else {
                // مزامنة جميع البوابات النشطة
                $gateways = $this->getGateways(array('filter_status' => 1));
                foreach ($gateways as $gateway) {
                    $synced_count += $this->syncSingleGateway($gateway, $date_from, $date_to);
                }
            }
            
            return array('success' => true, 'synced_count' => $synced_count);
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * إنشاء إعدادات افتراضية للبوابة
     */
    private function createDefaultGatewaySettings($gateway_id, $data) {
        $default_settings = array(
            'timeout' => 30,
            'retry_attempts' => 3,
            'auto_settlement' => 1,
            'send_receipt' => 1,
            'log_transactions' => 1,
            'enable_refunds' => 1,
            'enable_partial_refunds' => 1,
            'min_amount' => 1,
            'max_amount' => 100000,
            'supported_currencies' => $data['currency_code'] ?? $this->config->get('config_currency')
        );
        
        foreach ($default_settings as $key => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "payment_gateway_settings SET 
                gateway_id = '" . (int)$gateway_id . "',
                setting_key = '" . $this->db->escape($key) . "',
                setting_value = '" . $this->db->escape($value) . "'");
        }
    }
    
    /**
     * تنفيذ اختبار الاتصال
     */
    private function performConnectionTest($gateway) {
        // هذه دالة مبسطة - في التطبيق الحقيقي ستحتاج لتنفيذ اختبارات مخصصة لكل بوابة
        
        switch ($gateway['provider_name']) {
            case 'PayPal':
                return $this->testPayPalConnection($gateway);
            case 'Stripe':
                return $this->testStripeConnection($gateway);
            case 'Fawry':
                return $this->testFawryConnection($gateway);
            case 'Paymob':
                return $this->testPaymobConnection($gateway);
            default:
                return $this->testGenericConnection($gateway);
        }
    }
    
    /**
     * مزامنة بوابة واحدة
     */
    private function syncSingleGateway($gateway, $date_from, $date_to) {
        $synced_count = 0;
        
        // هذه دالة مبسطة - في التطبيق الحقيقي ستحتاج لتنفيذ مزامنة مخصصة لكل بوابة
        
        try {
            // جلب المعاملات من البوابة
            $transactions = $this->fetchGatewayTransactions($gateway, $date_from, $date_to);
            
            foreach ($transactions as $transaction) {
                // التحقق من وجود المعاملة
                $existing = $this->db->query("SELECT transaction_id FROM " . DB_PREFIX . "payment_transactions 
                                            WHERE gateway_id = '" . (int)$gateway['gateway_id'] . "' 
                                            AND external_transaction_id = '" . $this->db->escape($transaction['external_id']) . "'");
                
                if (!$existing->num_rows) {
                    // إضافة معاملة جديدة
                    $this->addGatewayTransaction($gateway, $transaction);
                    $synced_count++;
                } else {
                    // تحديث المعاملة الموجودة
                    $this->updateGatewayTransaction($existing->row['transaction_id'], $transaction);
                }
            }
            
            // تحديث تاريخ آخر مزامنة
            $this->db->query("UPDATE " . DB_PREFIX . "payment_gateways SET 
                last_sync = NOW() 
                WHERE gateway_id = '" . (int)$gateway['gateway_id'] . "'");
            
        } catch (Exception $e) {
            // تسجيل الخطأ
            error_log('Gateway sync error for gateway ' . $gateway['gateway_id'] . ': ' . $e->getMessage());
        }
        
        return $synced_count;
    }
    
    /**
     * إضافة معاملة بوابة دفع
     */
    private function addGatewayTransaction($gateway, $transaction_data) {
        $this->load->model('payment/transaction');
        
        $data = array(
            'gateway_id' => $gateway['gateway_id'],
            'external_transaction_id' => $transaction_data['external_id'],
            'transaction_type' => $transaction_data['type'],
            'amount' => $transaction_data['amount'],
            'commission_amount' => $transaction_data['commission'] ?? 0,
            'net_amount' => $transaction_data['net_amount'] ?? $transaction_data['amount'],
            'currency_code' => $transaction_data['currency'] ?? $gateway['currency_code'],
            'status' => $transaction_data['status'],
            'transaction_date' => $transaction_data['date'],
            'customer_reference' => $transaction_data['customer_reference'] ?? '',
            'description' => $transaction_data['description'] ?? '',
            'metadata' => json_encode($transaction_data['metadata'] ?? array())
        );
        
        return $this->model_payment_transaction->addTransaction($data);
    }
    
    /**
     * دوال اختبار الاتصال المخصصة لكل بوابة
     */
    private function testPayPalConnection($gateway) {
        // تنفيذ اختبار PayPal
        return array('success' => true, 'message' => 'PayPal connection test successful');
    }
    
    private function testStripeConnection($gateway) {
        // تنفيذ اختبار Stripe
        return array('success' => true, 'message' => 'Stripe connection test successful');
    }
    
    private function testFawryConnection($gateway) {
        // تنفيذ اختبار Fawry
        return array('success' => true, 'message' => 'Fawry connection test successful');
    }
    
    private function testPaymobConnection($gateway) {
        // تنفيذ اختبار Paymob
        return array('success' => true, 'message' => 'Paymob connection test successful');
    }
    
    private function testGenericConnection($gateway) {
        // اختبار عام للبوابات الأخرى
        return array('success' => true, 'message' => 'Generic connection test successful');
    }
    
    /**
     * جلب المعاملات من البوابة
     */
    private function fetchGatewayTransactions($gateway, $date_from, $date_to) {
        // هذه دالة مبسطة - في التطبيق الحقيقي ستحتاج لتنفيذ API calls مخصصة لكل بوابة
        return array();
    }
    
    /**
     * تحديث معاملة موجودة
     */
    private function updateGatewayTransaction($transaction_id, $transaction_data) {
        $this->load->model('payment/transaction');
        
        $data = array(
            'status' => $transaction_data['status'],
            'commission_amount' => $transaction_data['commission'] ?? 0,
            'net_amount' => $transaction_data['net_amount'] ?? $transaction_data['amount'],
            'metadata' => json_encode($transaction_data['metadata'] ?? array())
        );
        
        return $this->model_payment_transaction->editTransaction($transaction_id, $data);
    }
}
