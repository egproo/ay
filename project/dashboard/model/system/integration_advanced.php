<?php
/**
 * نموذج نظام الترابط المتقدم بين الوحدات
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ModelSystemIntegrationAdvanced extends Model {

    /**
     * فحص تكامل النظام الشامل
     */
    public function performIntegrityCheck() {
        $integrity_report = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'overall_status' => 'healthy',
            'modules' => array(),
            'issues' => array(),
            'recommendations' => array()
        );

        // فحص تكامل المحاسبة
        $accounting_integrity = $this->checkAccountingIntegrity();
        $integrity_report['modules']['accounting'] = $accounting_integrity;

        // فحص تكامل المخزون
        $inventory_integrity = $this->checkInventoryIntegrity();
        $integrity_report['modules']['inventory'] = $inventory_integrity;

        // فحص تكامل المشتريات
        $purchase_integrity = $this->checkPurchaseIntegrity();
        $integrity_report['modules']['purchase'] = $purchase_integrity;

        // فحص تكامل المبيعات
        $sales_integrity = $this->checkSalesIntegrity();
        $integrity_report['modules']['sales'] = $sales_integrity;

        // فحص تكامل الموارد البشرية
        $hr_integrity = $this->checkHRIntegrity();
        $integrity_report['modules']['hr'] = $hr_integrity;

        // تحديد الحالة العامة
        $total_issues = 0;
        foreach ($integrity_report['modules'] as $module => $data) {
            $total_issues += count($data['issues']);
            if ($data['status'] == 'critical') {
                $integrity_report['overall_status'] = 'critical';
            } elseif ($data['status'] == 'warning' && $integrity_report['overall_status'] != 'critical') {
                $integrity_report['overall_status'] = 'warning';
            }
        }

        $integrity_report['total_issues'] = $total_issues;

        return $integrity_report;
    }

    /**
     * فحص تكامل المحاسبة
     */
    private function checkAccountingIntegrity() {
        $issues = array();
        $status = 'healthy';

        // فحص توازن القيود
        $unbalanced_entries = $this->checkUnbalancedJournalEntries();
        if ($unbalanced_entries > 0) {
            $issues[] = array(
                'type' => 'critical',
                'message' => "يوجد {$unbalanced_entries} قيد محاسبي غير متوازن",
                'action' => 'review_journal_entries'
            );
            $status = 'critical';
        }

        // فحص ربط المخزون بالمحاسبة
        $inventory_account_issues = $this->checkInventoryAccountMapping();
        if (!empty($inventory_account_issues)) {
            $issues = array_merge($issues, $inventory_account_issues);
            if ($status != 'critical') $status = 'warning';
        }

        // فحص القيود التلقائية
        $auto_journal_issues = $this->checkAutoJournalEntries();
        if (!empty($auto_journal_issues)) {
            $issues = array_merge($issues, $auto_journal_issues);
            if ($status != 'critical') $status = 'warning';
        }

        return array(
            'status' => $status,
            'issues' => $issues,
            'last_check' => date('Y-m-d H:i:s')
        );
    }

    /**
     * فحص تكامل المخزون
     */
    private function checkInventoryIntegrity() {
        $issues = array();
        $status = 'healthy';

        // فحص الكميات السالبة
        $negative_stock = $this->checkNegativeStock();
        if ($negative_stock > 0) {
            $issues[] = array(
                'type' => 'warning',
                'message' => "يوجد {$negative_stock} منتج بكمية سالبة",
                'action' => 'review_stock_levels'
            );
            $status = 'warning';
        }

        // فحص حركات المخزون بدون قيود
        $movements_without_journals = $this->checkMovementsWithoutJournals();
        if ($movements_without_journals > 0) {
            $issues[] = array(
                'type' => 'critical',
                'message' => "يوجد {$movements_without_journals} حركة مخزون بدون قيد محاسبي",
                'action' => 'create_missing_journals'
            );
            $status = 'critical';
        }

        // فحص التكلفة المتوسطة المرجحة
        $wac_issues = $this->checkWeightedAverageCost();
        if (!empty($wac_issues)) {
            $issues = array_merge($issues, $wac_issues);
            if ($status != 'critical') $status = 'warning';
        }

        return array(
            'status' => $status,
            'issues' => $issues,
            'last_check' => date('Y-m-d H:i:s')
        );
    }

    /**
     * فحص تكامل المشتريات
     */
    private function checkPurchaseIntegrity() {
        $issues = array();
        $status = 'healthy';

        // فحص فواتير الشراء بدون قيود
        $invoices_without_journals = $this->checkPurchaseInvoicesWithoutJournals();
        if ($invoices_without_journals > 0) {
            $issues[] = array(
                'type' => 'critical',
                'message' => "يوجد {$invoices_without_journals} فاتورة شراء بدون قيد محاسبي",
                'action' => 'create_purchase_journals'
            );
            $status = 'critical';
        }

        // فحص أوامر الشراء المعلقة
        $pending_orders = $this->checkPendingPurchaseOrders();
        if ($pending_orders > 50) { // حد تحذيري
            $issues[] = array(
                'type' => 'warning',
                'message' => "يوجد {$pending_orders} أمر شراء معلق",
                'action' => 'review_pending_orders'
            );
            if ($status != 'critical') $status = 'warning';
        }

        return array(
            'status' => $status,
            'issues' => $issues,
            'last_check' => date('Y-m-d H:i:s')
        );
    }

    /**
     * فحص تكامل المبيعات
     */
    private function checkSalesIntegrity() {
        $issues = array();
        $status = 'healthy';

        // فحص فواتير البيع بدون قيود
        $invoices_without_journals = $this->checkSalesInvoicesWithoutJournals();
        if ($invoices_without_journals > 0) {
            $issues[] = array(
                'type' => 'critical',
                'message' => "يوجد {$invoices_without_journals} فاتورة بيع بدون قيد محاسبي",
                'action' => 'create_sales_journals'
            );
            $status = 'critical';
        }

        // فحص أرصدة العملاء
        $customer_balance_issues = $this->checkCustomerBalances();
        if (!empty($customer_balance_issues)) {
            $issues = array_merge($issues, $customer_balance_issues);
            if ($status != 'critical') $status = 'warning';
        }

        return array(
            'status' => $status,
            'issues' => $issues,
            'last_check' => date('Y-m-d H:i:s')
        );
    }

    /**
     * فحص تكامل الموارد البشرية
     */
    private function checkHRIntegrity() {
        $issues = array();
        $status = 'healthy';

        // فحص رواتب بدون قيود
        $payroll_without_journals = $this->checkPayrollWithoutJournals();
        if ($payroll_without_journals > 0) {
            $issues[] = array(
                'type' => 'critical',
                'message' => "يوجد {$payroll_without_journals} راتب بدون قيد محاسبي",
                'action' => 'create_payroll_journals'
            );
            $status = 'critical';
        }

        return array(
            'status' => $status,
            'issues' => $issues,
            'last_check' => date('Y-m-d H:i:s')
        );
    }

    /**
     * مزامنة الوحدات
     */
    public function synchronizeModules($modules) {
        $sync_results = array();

        foreach ($modules as $module) {
            switch ($module) {
                case 'inventory_accounting':
                    $sync_results[$module] = $this->syncInventoryWithAccounting();
                    break;
                case 'purchase_accounting':
                    $sync_results[$module] = $this->syncPurchaseWithAccounting();
                    break;
                case 'sales_accounting':
                    $sync_results[$module] = $this->syncSalesWithAccounting();
                    break;
                case 'hr_accounting':
                    $sync_results[$module] = $this->syncHRWithAccounting();
                    break;
                default:
                    $sync_results[$module] = array('status' => 'error', 'message' => 'وحدة غير مدعومة');
            }
        }

        return $sync_results;
    }

    /**
     * مزامنة المخزون مع المحاسبة
     */
    private function syncInventoryWithAccounting() {
        $synced_items = 0;
        $errors = array();

        try {
            // البحث عن حركات المخزون بدون قيود
            $query = $this->db->query("
                SELECT sm.* 
                FROM " . DB_PREFIX . "stock_movements sm
                LEFT JOIN " . DB_PREFIX . "journal_entries je ON sm.movement_id = je.reference_id AND je.reference_type = 'stock_movement'
                WHERE je.journal_id IS NULL
                AND sm.movement_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");

            foreach ($query->rows as $movement) {
                // إنشاء قيد محاسبي للحركة
                $journal_result = $this->createInventoryJournalEntry($movement);
                if ($journal_result) {
                    $synced_items++;
                } else {
                    $errors[] = "فشل في إنشاء قيد للحركة رقم: " . $movement['movement_id'];
                }
            }

            return array(
                'status' => 'success',
                'synced_items' => $synced_items,
                'errors' => $errors
            );

        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * إنشاء قيد محاسبي لحركة المخزون
     */
    private function createInventoryJournalEntry($movement) {
        // تحديد الحسابات المحاسبية
        $inventory_account = $this->getInventoryAccount($movement['product_id']);
        $cost_account = $this->getCostOfGoodsAccount($movement['product_id']);

        if (!$inventory_account || !$cost_account) {
            return false;
        }

        // إنشاء القيد
        $journal_data = array(
            'journal_date' => $movement['movement_date'],
            'description' => 'قيد تلقائي - حركة مخزون رقم: ' . $movement['movement_id'],
            'reference_type' => 'stock_movement',
            'reference_id' => $movement['movement_id'],
            'total_amount' => $movement['total_cost'],
            'created_by' => 0, // نظام تلقائي
            'is_auto_generated' => 1
        );

        // إدراج القيد الرئيسي
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "journal_entries SET
            journal_date = '" . $this->db->escape($journal_data['journal_date']) . "',
            description = '" . $this->db->escape($journal_data['description']) . "',
            reference_type = '" . $this->db->escape($journal_data['reference_type']) . "',
            reference_id = '" . (int)$journal_data['reference_id'] . "',
            total_amount = '" . (float)$journal_data['total_amount'] . "',
            created_by = '" . (int)$journal_data['created_by'] . "',
            is_auto_generated = '" . (int)$journal_data['is_auto_generated'] . "',
            created_date = NOW()
        ");

        $journal_id = $this->db->getLastId();

        // إدراج تفاصيل القيد
        if ($movement['movement_type'] == 'in') {
            // دخول مخزون: مدين المخزون - دائن الموردين/النقدية
            $this->insertJournalDetail($journal_id, $inventory_account, $movement['total_cost'], 0);
            $this->insertJournalDetail($journal_id, $cost_account, 0, $movement['total_cost']);
        } else {
            // خروج مخزون: مدين تكلفة البضاعة - دائن المخزون
            $this->insertJournalDetail($journal_id, $cost_account, $movement['total_cost'], 0);
            $this->insertJournalDetail($journal_id, $inventory_account, 0, $movement['total_cost']);
        }

        return $journal_id;
    }

    /**
     * إدراج تفاصيل القيد
     */
    private function insertJournalDetail($journal_id, $account_id, $debit, $credit) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "journal_entry_details SET
            journal_id = '" . (int)$journal_id . "',
            account_id = '" . (int)$account_id . "',
            debit_amount = '" . (float)$debit . "',
            credit_amount = '" . (float)$credit . "'
        ");
    }

    /**
     * الحصول على حساب المخزون للمنتج
     */
    private function getInventoryAccount($product_id) {
        $query = $this->db->query("
            SELECT inventory_account_id 
            FROM " . DB_PREFIX . "product_account_mapping 
            WHERE product_id = '" . (int)$product_id . "'
        ");

        if ($query->num_rows) {
            return $query->row['inventory_account_id'];
        }

        // حساب افتراضي
        return $this->getDefaultInventoryAccount();
    }

    /**
     * الحصول على حساب تكلفة البضاعة
     */
    private function getCostOfGoodsAccount($product_id) {
        $query = $this->db->query("
            SELECT cost_account_id 
            FROM " . DB_PREFIX . "product_account_mapping 
            WHERE product_id = '" . (int)$product_id . "'
        ");

        if ($query->num_rows) {
            return $query->row['cost_account_id'];
        }

        // حساب افتراضي
        return $this->getDefaultCostAccount();
    }

    /**
     * فحص القيود غير المتوازنة
     */
    private function checkUnbalancedJournalEntries() {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "journal_entries je
            WHERE ABS((
                SELECT COALESCE(SUM(debit_amount), 0) 
                FROM " . DB_PREFIX . "journal_entry_details 
                WHERE journal_id = je.journal_id
            ) - (
                SELECT COALESCE(SUM(credit_amount), 0) 
                FROM " . DB_PREFIX . "journal_entry_details 
                WHERE journal_id = je.journal_id
            )) > 0.01
        ");

        return $query->row['count'];
    }

    /**
     * فحص الكميات السالبة
     */
    private function checkNegativeStock() {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "stock
            WHERE quantity < 0
        ");

        return $query->row['count'];
    }

    /**
     * فحص حركات المخزون بدون قيود
     */
    private function checkMovementsWithoutJournals() {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "stock_movements sm
            LEFT JOIN " . DB_PREFIX . "journal_entries je ON sm.movement_id = je.reference_id AND je.reference_type = 'stock_movement'
            WHERE je.journal_id IS NULL
        ");

        return $query->row['count'];
    }
}
