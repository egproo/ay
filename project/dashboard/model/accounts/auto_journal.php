<?php
/**
 * نموذج القيود التلقائية المتقدم
 * يدعم إنشاء القيود التلقائية من جميع معاملات النظام
 */
class ModelAccountsAutoJournal extends Model {

    /**
     * إنشاء قيد تلقائي من طلب مبيعات
     */
    public function createSalesOrderJournal($order_id) {
        $this->load->model('sale/order');
        $order = $this->model_sale_order->getOrder($order_id);
        
        if (!$order) {
            throw new Exception('الطلب غير موجود');
        }

        $journal_data = array(
            'journal_date' => date('Y-m-d', strtotime($order['date_added'])),
            'description' => 'قيد مبيعات - طلب رقم ' . $order['order_id'],
            'reference_type' => 'sales_order',
            'reference_id' => $order_id,
            'reference_number' => $order['order_id'],
            'status' => 'posted',
            'auto_generated' => 1,
            'lines' => array()
        );

        // حساب العميل أو المبيعات النقدية
        $customer_account_id = $this->getCustomerAccountId($order['customer_id']);
        $sales_account_id = $this->getSalesAccountId();
        $tax_account_id = $this->getTaxAccountId();
        $cost_account_id = $this->getCostOfGoodsAccountId();
        $inventory_account_id = $this->getInventoryAccountId();

        // قيد المبيعات
        $journal_data['lines'][] = array(
            'account_id' => $customer_account_id,
            'debit_amount' => $order['total'],
            'credit_amount' => 0,
            'description' => 'مبيعات للعميل ' . $order['firstname'] . ' ' . $order['lastname']
        );

        // قيد إيراد المبيعات
        $net_sales = $order['total'] - $this->getOrderTaxAmount($order_id);
        $journal_data['lines'][] = array(
            'account_id' => $sales_account_id,
            'debit_amount' => 0,
            'credit_amount' => $net_sales,
            'description' => 'إيراد مبيعات'
        );

        // قيد ضريبة القيمة المضافة
        $tax_amount = $this->getOrderTaxAmount($order_id);
        if ($tax_amount > 0) {
            $journal_data['lines'][] = array(
                'account_id' => $tax_account_id,
                'debit_amount' => 0,
                'credit_amount' => $tax_amount,
                'description' => 'ضريبة القيمة المضافة'
            );
        }

        // قيد تكلفة البضاعة المباعة والمخزون
        $this->addCostOfGoodsEntries($journal_data, $order_id, $cost_account_id, $inventory_account_id);

        $this->load->model('accounts/journal_entry');
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * إنشاء قيد تلقائي من طلب شراء
     */
    public function createPurchaseOrderJournal($purchase_id) {
        $this->load->model('purchase/purchase');
        $purchase = $this->model_purchase_purchase->getPurchase($purchase_id);
        
        if (!$purchase) {
            throw new Exception('طلب الشراء غير موجود');
        }

        $journal_data = array(
            'journal_date' => date('Y-m-d', strtotime($purchase['date_added'])),
            'description' => 'قيد مشتريات - طلب رقم ' . $purchase['purchase_id'],
            'reference_type' => 'purchase_order',
            'reference_id' => $purchase_id,
            'reference_number' => $purchase['purchase_number'],
            'status' => 'posted',
            'auto_generated' => 1,
            'lines' => array()
        );

        $supplier_account_id = $this->getSupplierAccountId($purchase['supplier_id']);
        $purchases_account_id = $this->getPurchasesAccountId();
        $inventory_account_id = $this->getInventoryAccountId();
        $tax_account_id = $this->getTaxAccountId();

        // قيد المشتريات
        $net_purchases = $purchase['total'] - $this->getPurchaseTaxAmount($purchase_id);
        $journal_data['lines'][] = array(
            'account_id' => $purchases_account_id,
            'debit_amount' => $net_purchases,
            'credit_amount' => 0,
            'description' => 'مشتريات من المورد ' . $purchase['supplier_name']
        );

        // قيد ضريبة القيمة المضافة على المشتريات
        $tax_amount = $this->getPurchaseTaxAmount($purchase_id);
        if ($tax_amount > 0) {
            $journal_data['lines'][] = array(
                'account_id' => $tax_account_id,
                'debit_amount' => $tax_amount,
                'credit_amount' => 0,
                'description' => 'ضريبة القيمة المضافة على المشتريات'
            );
        }

        // قيد المورد
        $journal_data['lines'][] = array(
            'account_id' => $supplier_account_id,
            'debit_amount' => 0,
            'credit_amount' => $purchase['total'],
            'description' => 'مستحق للمورد ' . $purchase['supplier_name']
        );

        $this->load->model('accounts/journal_entry');
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * إنشاء قيد تلقائي من دفعة نقدية
     */
    public function createPaymentJournal($payment_id, $payment_type = 'customer') {
        if ($payment_type == 'customer') {
            $this->load->model('finance/customer_payment');
            $payment = $this->model_finance_customer_payment->getPayment($payment_id);
            $account_id = $this->getCustomerAccountId($payment['customer_id']);
            $description = 'تحصيل من العميل ' . $payment['customer_name'];
        } else {
            $this->load->model('finance/supplier_payment');
            $payment = $this->model_finance_supplier_payment->getPayment($payment_id);
            $account_id = $this->getSupplierAccountId($payment['supplier_id']);
            $description = 'دفع للمورد ' . $payment['supplier_name'];
        }

        if (!$payment) {
            throw new Exception('الدفعة غير موجودة');
        }

        $journal_data = array(
            'journal_date' => date('Y-m-d', strtotime($payment['payment_date'])),
            'description' => $description . ' - دفعة رقم ' . $payment_id,
            'reference_type' => $payment_type . '_payment',
            'reference_id' => $payment_id,
            'reference_number' => $payment['payment_number'] ?? $payment_id,
            'status' => 'posted',
            'auto_generated' => 1,
            'lines' => array()
        );

        $cash_account_id = $this->getCashAccountId($payment['payment_method']);

        if ($payment_type == 'customer') {
            // قيد تحصيل من العميل
            $journal_data['lines'][] = array(
                'account_id' => $cash_account_id,
                'debit_amount' => $payment['amount'],
                'credit_amount' => 0,
                'description' => 'تحصيل نقدي'
            );

            $journal_data['lines'][] = array(
                'account_id' => $account_id,
                'debit_amount' => 0,
                'credit_amount' => $payment['amount'],
                'description' => 'تحصيل من العميل'
            );
        } else {
            // قيد دفع للمورد
            $journal_data['lines'][] = array(
                'account_id' => $account_id,
                'debit_amount' => $payment['amount'],
                'credit_amount' => 0,
                'description' => 'دفع للمورد'
            );

            $journal_data['lines'][] = array(
                'account_id' => $cash_account_id,
                'debit_amount' => 0,
                'credit_amount' => $payment['amount'],
                'description' => 'دفع نقدي'
            );
        }

        $this->load->model('accounts/journal_entry');
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * إنشاء قيد تلقائي من معاملة بوابة دفع
     */
    public function createPaymentGatewayJournal($transaction_id) {
        $this->load->model('payment/transaction');
        $transaction = $this->model_payment_transaction->getTransaction($transaction_id);
        
        if (!$transaction) {
            throw new Exception('المعاملة غير موجودة');
        }

        $journal_data = array(
            'journal_date' => date('Y-m-d', strtotime($transaction['transaction_date'])),
            'description' => 'معاملة دفع إلكتروني - ' . $transaction['gateway_name'],
            'reference_type' => 'payment_gateway',
            'reference_id' => $transaction_id,
            'reference_number' => $transaction['external_transaction_id'],
            'status' => 'posted',
            'auto_generated' => 1,
            'lines' => array()
        );

        $gateway_account_id = $this->getPaymentGatewayAccountId($transaction['gateway_id']);
        $commission_account_id = $this->getCommissionAccountId();
        $customer_account_id = $this->getCustomerAccountId($transaction['customer_id']);

        if ($transaction['transaction_type'] == 'payment') {
            // قيد استلام دفعة إلكترونية
            $journal_data['lines'][] = array(
                'account_id' => $gateway_account_id,
                'debit_amount' => $transaction['net_amount'],
                'credit_amount' => 0,
                'description' => 'استلام دفعة إلكترونية'
            );

            // عمولة البوابة
            if ($transaction['commission_amount'] > 0) {
                $journal_data['lines'][] = array(
                    'account_id' => $commission_account_id,
                    'debit_amount' => $transaction['commission_amount'],
                    'credit_amount' => 0,
                    'description' => 'عمولة بوابة الدفع'
                );
            }

            // حساب العميل
            $journal_data['lines'][] = array(
                'account_id' => $customer_account_id,
                'debit_amount' => 0,
                'credit_amount' => $transaction['amount'],
                'description' => 'تحصيل من العميل'
            );

        } elseif ($transaction['transaction_type'] == 'refund') {
            // قيد استرداد
            $journal_data['lines'][] = array(
                'account_id' => $customer_account_id,
                'debit_amount' => $transaction['amount'],
                'credit_amount' => 0,
                'description' => 'استرداد للعميل'
            );

            $journal_data['lines'][] = array(
                'account_id' => $gateway_account_id,
                'debit_amount' => 0,
                'credit_amount' => $transaction['amount'],
                'description' => 'استرداد عبر بوابة الدفع'
            );
        }

        $this->load->model('accounts/journal_entry');
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * إنشاء قيد تلقائي من حركة مخزون
     */
    public function createInventoryJournal($movement_id) {
        $this->load->model('inventory/movement');
        $movement = $this->model_inventory_movement->getMovement($movement_id);
        
        if (!$movement) {
            throw new Exception('حركة المخزون غير موجودة');
        }

        $journal_data = array(
            'journal_date' => date('Y-m-d', strtotime($movement['movement_date'])),
            'description' => 'حركة مخزون - ' . $movement['movement_type'],
            'reference_type' => 'inventory_movement',
            'reference_id' => $movement_id,
            'reference_number' => $movement['movement_number'],
            'status' => 'posted',
            'auto_generated' => 1,
            'lines' => array()
        );

        $inventory_account_id = $this->getInventoryAccountId();
        $adjustment_account_id = $this->getInventoryAdjustmentAccountId();

        $total_value = $movement['quantity'] * $movement['unit_cost'];

        if ($movement['movement_type'] == 'in') {
            // إدخال مخزون
            $journal_data['lines'][] = array(
                'account_id' => $inventory_account_id,
                'debit_amount' => $total_value,
                'credit_amount' => 0,
                'description' => 'إدخال مخزون - ' . $movement['product_name']
            );

            $journal_data['lines'][] = array(
                'account_id' => $adjustment_account_id,
                'debit_amount' => 0,
                'credit_amount' => $total_value,
                'description' => 'تسوية مخزون'
            );

        } elseif ($movement['movement_type'] == 'out') {
            // إخراج مخزون
            $journal_data['lines'][] = array(
                'account_id' => $adjustment_account_id,
                'debit_amount' => $total_value,
                'credit_amount' => 0,
                'description' => 'تسوية مخزون'
            );

            $journal_data['lines'][] = array(
                'account_id' => $inventory_account_id,
                'debit_amount' => 0,
                'credit_amount' => $total_value,
                'description' => 'إخراج مخزون - ' . $movement['product_name']
            );
        }

        $this->load->model('accounts/journal_entry');
        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * إضافة قيود تكلفة البضاعة المباعة
     */
    private function addCostOfGoodsEntries(&$journal_data, $order_id, $cost_account_id, $inventory_account_id) {
        $this->load->model('sale/order');
        $order_products = $this->model_sale_order->getOrderProducts($order_id);

        $total_cost = 0;
        foreach ($order_products as $product) {
            $cost = $this->getProductCost($product['product_id']);
            $total_cost += $cost * $product['quantity'];
        }

        if ($total_cost > 0) {
            // قيد تكلفة البضاعة المباعة
            $journal_data['lines'][] = array(
                'account_id' => $cost_account_id,
                'debit_amount' => $total_cost,
                'credit_amount' => 0,
                'description' => 'تكلفة البضاعة المباعة'
            );

            // قيد تخفيض المخزون
            $journal_data['lines'][] = array(
                'account_id' => $inventory_account_id,
                'debit_amount' => 0,
                'credit_amount' => $total_cost,
                'description' => 'تخفيض المخزون'
            );
        }
    }

    /**
     * دوال مساعدة للحصول على معرفات الحسابات
     */
    private function getCustomerAccountId($customer_id) {
        if ($customer_id) {
            $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer_id . "'");
            if ($query->num_rows) {
                return $query->row['account_id'];
            }
        }
        return $this->getDefaultAccountId('customer_receivable');
    }

    private function getSupplierAccountId($supplier_id) {
        if ($supplier_id) {
            $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "supplier WHERE supplier_id = '" . (int)$supplier_id . "'");
            if ($query->num_rows) {
                return $query->row['account_id'];
            }
        }
        return $this->getDefaultAccountId('supplier_payable');
    }

    private function getSalesAccountId() {
        return $this->getDefaultAccountId('sales_revenue');
    }

    private function getPurchasesAccountId() {
        return $this->getDefaultAccountId('purchases');
    }

    private function getTaxAccountId() {
        return $this->getDefaultAccountId('vat_payable');
    }

    private function getCostOfGoodsAccountId() {
        return $this->getDefaultAccountId('cost_of_goods_sold');
    }

    private function getInventoryAccountId() {
        return $this->getDefaultAccountId('inventory');
    }

    private function getInventoryAdjustmentAccountId() {
        return $this->getDefaultAccountId('inventory_adjustment');
    }

    private function getCashAccountId($payment_method = 'cash') {
        return $this->getDefaultAccountId('cash_' . $payment_method);
    }

    private function getPaymentGatewayAccountId($gateway_id) {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "payment_gateways WHERE gateway_id = '" . (int)$gateway_id . "'");
        if ($query->num_rows) {
            return $query->row['account_id'];
        }
        return $this->getDefaultAccountId('payment_gateway');
    }

    private function getCommissionAccountId() {
        return $this->getDefaultAccountId('payment_commission');
    }

    private function getDefaultAccountId($account_type) {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "account_mapping WHERE mapping_type = '" . $this->db->escape($account_type) . "' LIMIT 1");
        if ($query->num_rows) {
            return $query->row['account_id'];
        }
        
        // إنشاء الحساب إذا لم يكن موجود
        return $this->createDefaultAccount($account_type);
    }

    private function createDefaultAccount($account_type) {
        // تنفيذ إنشاء الحسابات الافتراضية
        $account_configs = $this->getDefaultAccountConfigs();
        
        if (isset($account_configs[$account_type])) {
            $config = $account_configs[$account_type];
            
            $this->load->model('accounts/chartaccount');
            $account_id = $this->model_accounts_chartaccount->addAccount($config);
            
            // حفظ الربط
            $this->db->query("INSERT INTO " . DB_PREFIX . "account_mapping SET
                mapping_type = '" . $this->db->escape($account_type) . "',
                account_id = '" . (int)$account_id . "'");
            
            return $account_id;
        }
        
        return 1; // حساب افتراضي
    }

    private function getDefaultAccountConfigs() {
        return array(
            'customer_receivable' => array(
                'account_code' => '1200',
                'account_type' => 'asset',
                'account_description' => array(
                    $this->config->get('config_language_id') => array(
                        'name' => 'العملاء',
                        'description' => 'حساب العملاء والذمم المدينة'
                    )
                )
            ),
            'supplier_payable' => array(
                'account_code' => '2100',
                'account_type' => 'liability',
                'account_description' => array(
                    $this->config->get('config_language_id') => array(
                        'name' => 'الموردين',
                        'description' => 'حساب الموردين والذمم الدائنة'
                    )
                )
            ),
            'sales_revenue' => array(
                'account_code' => '4100',
                'account_type' => 'revenue',
                'account_description' => array(
                    $this->config->get('config_language_id') => array(
                        'name' => 'إيرادات المبيعات',
                        'description' => 'إيرادات المبيعات الرئيسية'
                    )
                )
            ),
            'purchases' => array(
                'account_code' => '5100',
                'account_type' => 'expense',
                'account_description' => array(
                    $this->config->get('config_language_id') => array(
                        'name' => 'المشتريات',
                        'description' => 'مشتريات البضائع والمواد'
                    )
                )
            ),
            'inventory' => array(
                'account_code' => '1300',
                'account_type' => 'asset',
                'account_description' => array(
                    $this->config->get('config_language_id') => array(
                        'name' => 'المخزون',
                        'description' => 'مخزون البضائع والمواد'
                    )
                )
            ),
            'cost_of_goods_sold' => array(
                'account_code' => '5200',
                'account_type' => 'expense',
                'account_description' => array(
                    $this->config->get('config_language_id') => array(
                        'name' => 'تكلفة البضاعة المباعة',
                        'description' => 'تكلفة البضائع المباعة'
                    )
                )
            ),
            'vat_payable' => array(
                'account_code' => '2200',
                'account_type' => 'liability',
                'account_description' => array(
                    $this->config->get('config_language_id') => array(
                        'name' => 'ضريبة القيمة المضافة',
                        'description' => 'ضريبة القيمة المضافة المستحقة'
                    )
                )
            )
        );
    }

    private function getOrderTaxAmount($order_id) {
        $query = $this->db->query("SELECT SUM(value) as tax_amount FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' AND code = 'tax'");
        return $query->num_rows ? (float)$query->row['tax_amount'] : 0;
    }

    private function getPurchaseTaxAmount($purchase_id) {
        $query = $this->db->query("SELECT tax_amount FROM " . DB_PREFIX . "purchase WHERE purchase_id = '" . (int)$purchase_id . "'");
        return $query->num_rows ? (float)$query->row['tax_amount'] : 0;
    }

    private function getProductCost($product_id) {
        $query = $this->db->query("SELECT cost FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
        return $query->num_rows ? (float)$query->row['cost'] : 0;
    }
}
