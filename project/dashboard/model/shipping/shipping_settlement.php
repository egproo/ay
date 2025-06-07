<?php
/**
 * نموذج نظام تسويات شركات الشحن مع التكامل المحاسبي
 * 
 * يوفر إدارة شاملة لتسويات شركات الشحن مع:
 * - تسويات الدفع عند الاستلام (COD)
 * - حساب رسوم الشحن والعمولات
 * - التكامل المحاسبي الكامل
 * - تقارير التسويات المتقدمة
 * - التوافق مع أرامكس وبوسطة
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelShippingShippingSettlement extends Model {
    
    /**
     * إنشاء تسوية جديدة لشركة الشحن
     */
    public function createSettlement($data) {
        // التحقق من صحة البيانات
        if (!$this->validateSettlementData($data)) {
            throw new Exception('بيانات التسوية غير صحيحة');
        }
        
        // بدء المعاملة
        $this->db->query("START TRANSACTION");
        
        try {
            // إنشاء سجل التسوية
            $settlement_id = $this->createSettlementRecord($data);
            
            // إضافة الشحنات للتسوية
            $this->addShipmentsToSettlement($settlement_id, $data['shipments']);
            
            // حساب إجماليات التسوية
            $totals = $this->calculateSettlementTotals($settlement_id);
            
            // تحديث سجل التسوية بالإجماليات
            $this->updateSettlementTotals($settlement_id, $totals);
            
            // إنشاء القيد المحاسبي
            if ($data['create_journal_entry']) {
                $journal_id = $this->createSettlementJournalEntry($settlement_id, $totals);
                
                // ربط القيد بالتسوية
                $this->db->query("
                    UPDATE cod_shipping_settlement SET 
                    journal_id = '" . (int)$journal_id . "'
                    WHERE settlement_id = '" . (int)$settlement_id . "'
                ");
            }
            
            // تأكيد المعاملة
            $this->db->query("COMMIT");
            
            return $settlement_id;
            
        } catch (Exception $e) {
            // إلغاء المعاملة
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }
    
    /**
     * إنشاء سجل التسوية
     */
    private function createSettlementRecord($data) {
        $this->db->query("
            INSERT INTO cod_shipping_settlement SET 
            company_id = '" . (int)$data['company_id'] . "',
            settlement_date = '" . $this->db->escape($data['settlement_date']) . "',
            reference_number = '" . $this->db->escape($data['reference_number']) . "',
            settlement_period_start = '" . $this->db->escape($data['period_start']) . "',
            settlement_period_end = '" . $this->db->escape($data['period_end']) . "',
            status = 'pending',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * إضافة الشحنات للتسوية
     */
    private function addShipmentsToSettlement($settlement_id, $shipments) {
        foreach ($shipments as $shipment_id) {
            // التحقق من أن الشحنة لم تتم تسويتها مسبقاً
            $existing_query = $this->db->query("
                SELECT settlement_id FROM cod_shipping_settlement_item 
                WHERE shipping_order_id = '" . (int)$shipment_id . "'
            ");
            
            if ($existing_query->num_rows == 0) {
                // الحصول على بيانات الشحنة
                $shipment = $this->getShipmentDetails($shipment_id);
                
                if ($shipment) {
                    $this->db->query("
                        INSERT INTO cod_shipping_settlement_item SET 
                        settlement_id = '" . (int)$settlement_id . "',
                        shipping_order_id = '" . (int)$shipment_id . "',
                        order_id = '" . (int)$shipment['order_id'] . "',
                        tracking_number = '" . $this->db->escape($shipment['tracking_number']) . "',
                        cod_amount = '" . (float)$shipment['cod_amount'] . "',
                        shipping_cost = '" . (float)$shipment['shipping_cost'] . "',
                        delivery_status = '" . $this->db->escape($shipment['status']) . "',
                        delivery_date = '" . $this->db->escape($shipment['actual_delivery_date']) . "'
                    ");
                }
            }
        }
    }
    
    /**
     * حساب إجماليات التسوية
     */
    private function calculateSettlementTotals($settlement_id) {
        $query = $this->db->query("
            SELECT 
                COUNT(*) as total_orders,
                SUM(cod_amount) as total_cod_amount,
                SUM(shipping_cost) as total_shipping_fees,
                COUNT(CASE WHEN delivery_status = 'delivered' THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN delivery_status = 'returned' THEN 1 END) as returned_orders,
                SUM(CASE WHEN delivery_status = 'delivered' THEN cod_amount ELSE 0 END) as delivered_cod_amount,
                SUM(CASE WHEN delivery_status = 'returned' THEN cod_amount ELSE 0 END) as returned_cod_amount
            FROM cod_shipping_settlement_item 
            WHERE settlement_id = '" . (int)$settlement_id . "'
        ");
        
        $totals = $query->row;
        
        // حساب العمولات والرسوم
        $settlement = $this->getSettlement($settlement_id);
        $company_rates = $this->getCompanyRates($settlement['company_id']);
        
        // حساب عمولة COD
        $cod_fee_rate = $company_rates['cod_fee_percentage'] ?? 2.5; // افتراضي 2.5%
        $cod_fees = $totals['delivered_cod_amount'] * ($cod_fee_rate / 100);
        
        // حساب رسوم إضافية (إرجاع، فشل تسليم، إلخ)
        $additional_fees = $this->calculateAdditionalFees($settlement_id, $company_rates);
        
        // حساب المبلغ الصافي
        $net_amount = $totals['delivered_cod_amount'] - $totals['total_shipping_fees'] - $cod_fees - $additional_fees;
        
        // تحديد اتجاه الدفع
        $payment_direction = ($net_amount >= 0) ? 'to_company' : 'from_company';
        
        return [
            'total_orders' => $totals['total_orders'],
            'delivered_orders' => $totals['delivered_orders'],
            'returned_orders' => $totals['returned_orders'],
            'total_cod_amount' => $totals['total_cod_amount'],
            'delivered_cod_amount' => $totals['delivered_cod_amount'],
            'returned_cod_amount' => $totals['returned_cod_amount'],
            'shipping_fees' => $totals['total_shipping_fees'],
            'cod_fees' => $cod_fees,
            'additional_fees' => $additional_fees,
            'net_amount' => abs($net_amount),
            'payment_direction' => $payment_direction
        ];
    }
    
    /**
     * حساب الرسوم الإضافية
     */
    private function calculateAdditionalFees($settlement_id, $company_rates) {
        $additional_fees = 0;
        
        // رسوم الإرجاع
        $returned_query = $this->db->query("
            SELECT COUNT(*) as returned_count 
            FROM cod_shipping_settlement_item 
            WHERE settlement_id = '" . (int)$settlement_id . "' 
            AND delivery_status = 'returned'
        ");
        
        if ($returned_query->row['returned_count'] > 0) {
            $return_fee = $company_rates['return_fee'] ?? 10; // رسم ثابت للإرجاع
            $additional_fees += $returned_query->row['returned_count'] * $return_fee;
        }
        
        // رسوم فشل التسليم
        $failed_query = $this->db->query("
            SELECT COUNT(*) as failed_count 
            FROM cod_shipping_settlement_item 
            WHERE settlement_id = '" . (int)$settlement_id . "' 
            AND delivery_status = 'failed'
        ");
        
        if ($failed_query->row['failed_count'] > 0) {
            $failed_fee = $company_rates['failed_delivery_fee'] ?? 5; // رسم ثابت لفشل التسليم
            $additional_fees += $failed_query->row['failed_count'] * $failed_fee;
        }
        
        return $additional_fees;
    }
    
    /**
     * تحديث إجماليات التسوية
     */
    private function updateSettlementTotals($settlement_id, $totals) {
        $this->db->query("
            UPDATE cod_shipping_settlement SET 
            total_orders = '" . (int)$totals['total_orders'] . "',
            delivered_orders = '" . (int)$totals['delivered_orders'] . "',
            returned_orders = '" . (int)$totals['returned_orders'] . "',
            total_cod_amount = '" . (float)$totals['total_cod_amount'] . "',
            delivered_cod_amount = '" . (float)$totals['delivered_cod_amount'] . "',
            returned_cod_amount = '" . (float)$totals['returned_cod_amount'] . "',
            shipping_fees = '" . (float)$totals['shipping_fees'] . "',
            cod_fees = '" . (float)$totals['cod_fees'] . "',
            additional_fees = '" . (float)$totals['additional_fees'] . "',
            net_amount = '" . (float)$totals['net_amount'] . "',
            payment_direction = '" . $this->db->escape($totals['payment_direction']) . "'
            WHERE settlement_id = '" . (int)$settlement_id . "'
        ");
    }
    
    /**
     * إنشاء القيد المحاسبي للتسوية
     */
    private function createSettlementJournalEntry($settlement_id, $totals) {
        $settlement = $this->getSettlement($settlement_id);
        
        $this->load->model('accounts/journal');
        
        $journal_data = [
            'reference' => 'SHIP-SETTLE-' . $settlement['reference_number'],
            'description' => 'تسوية شركة الشحن: ' . $settlement['company_name'],
            'date' => $settlement['settlement_date'],
            'entries' => []
        ];
        
        if ($totals['payment_direction'] == 'to_company') {
            // المبلغ لصالحنا (نستلم من شركة الشحن)
            
            // مدين: البنك أو النقدية (المبلغ المستلم)
            $journal_data['entries'][] = [
                'account_id' => $this->getAccountId('bank'),
                'debit' => $totals['net_amount'],
                'credit' => 0,
                'description' => 'تسوية COD مستلمة من شركة الشحن'
            ];
            
            // مدين: مصروف رسوم الشحن
            $journal_data['entries'][] = [
                'account_id' => $this->getAccountId('shipping_expense'),
                'debit' => $totals['shipping_fees'] + $totals['cod_fees'] + $totals['additional_fees'],
                'credit' => 0,
                'description' => 'رسوم الشحن والعمولات'
            ];
            
            // دائن: حساب شركة الشحن (إقفال المستحق)
            $journal_data['entries'][] = [
                'account_id' => $this->getAccountId('shipping_company_receivable'),
                'debit' => 0,
                'credit' => $totals['delivered_cod_amount'],
                'description' => 'إقفال مستحق COD من شركة الشحن'
            ];
            
        } else {
            // المبلغ علينا (ندفع لشركة الشحن)
            
            // مدين: حساب شركة الشحن (إقفال المستحق)
            $journal_data['entries'][] = [
                'account_id' => $this->getAccountId('shipping_company_payable'),
                'debit' => $totals['shipping_fees'],
                'credit' => 0,
                'description' => 'سداد رسوم الشحن لشركة الشحن'
            ];
            
            // دائن: البنك أو النقدية (المبلغ المدفوع)
            $journal_data['entries'][] = [
                'account_id' => $this->getAccountId('bank'),
                'debit' => 0,
                'credit' => $totals['net_amount'],
                'description' => 'دفع تسوية لشركة الشحن'
            ];
        }
        
        return $this->model_accounts_journal->addJournalEntry($journal_data);
    }
    
    /**
     * اعتماد التسوية
     */
    public function approveSettlement($settlement_id, $approval_notes = '') {
        $this->db->query("
            UPDATE cod_shipping_settlement SET 
            status = 'approved',
            approved_by = '" . (int)$this->user->getId() . "',
            approval_notes = '" . $this->db->escape($approval_notes) . "',
            date_approved = NOW()
            WHERE settlement_id = '" . (int)$settlement_id . "'
        ");
        
        // تحديث حالة الشحنات المضمنة في التسوية
        $this->db->query("
            UPDATE cod_shipping_order so
            INNER JOIN cod_shipping_settlement_item ssi ON (so.shipping_order_id = ssi.shipping_order_id)
            SET so.settlement_status = 'settled'
            WHERE ssi.settlement_id = '" . (int)$settlement_id . "'
        ");
        
        return true;
    }
    
    /**
     * الحصول على تفاصيل التسوية
     */
    public function getSettlement($settlement_id) {
        $query = $this->db->query("
            SELECT ss.*, sc.name as company_name, sc.code as company_code,
                CONCAT(u1.firstname, ' ', u1.lastname) as created_by_name,
                CONCAT(u2.firstname, ' ', u2.lastname) as approved_by_name
            FROM cod_shipping_settlement ss
            LEFT JOIN cod_shipping_company sc ON (ss.company_id = sc.company_id)
            LEFT JOIN cod_user u1 ON (ss.created_by = u1.user_id)
            LEFT JOIN cod_user u2 ON (ss.approved_by = u2.user_id)
            WHERE ss.settlement_id = '" . (int)$settlement_id . "'
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * الحصول على عناصر التسوية
     */
    public function getSettlementItems($settlement_id) {
        $query = $this->db->query("
            SELECT ssi.*, o.firstname, o.lastname, o.telephone,
                CONCAT(o.firstname, ' ', o.lastname) as customer_name
            FROM cod_shipping_settlement_item ssi
            LEFT JOIN cod_order o ON (ssi.order_id = o.order_id)
            WHERE ssi.settlement_id = '" . (int)$settlement_id . "'
            ORDER BY ssi.delivery_date DESC
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على قائمة التسويات
     */
    public function getSettlements($filter_data = []) {
        $sql = "SELECT ss.*, sc.name as company_name,
                CONCAT(u.firstname, ' ', u.lastname) as created_by_name
                FROM cod_shipping_settlement ss
                LEFT JOIN cod_shipping_company sc ON (ss.company_id = sc.company_id)
                LEFT JOIN cod_user u ON (ss.created_by = u.user_id)
                WHERE 1";
        
        if (!empty($filter_data['filter_company'])) {
            $sql .= " AND ss.company_id = '" . (int)$filter_data['filter_company'] . "'";
        }
        
        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND ss.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }
        
        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND ss.settlement_date >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }
        
        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND ss.settlement_date <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }
        
        $sql .= " ORDER BY ss.settlement_date DESC";
        
        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
            
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على الشحنات المؤهلة للتسوية
     */
    public function getEligibleShipments($company_id, $period_start, $period_end) {
        $query = $this->db->query("
            SELECT so.*, o.firstname, o.lastname, o.telephone,
                CONCAT(o.firstname, ' ', o.lastname) as customer_name
            FROM cod_shipping_order so
            LEFT JOIN cod_order o ON (so.order_id = o.order_id)
            WHERE so.company_id = '" . (int)$company_id . "'
            AND so.status IN ('delivered', 'returned', 'failed')
            AND so.actual_delivery_date BETWEEN '" . $this->db->escape($period_start) . "' 
            AND '" . $this->db->escape($period_end) . "'
            AND so.settlement_status != 'settled'
            ORDER BY so.actual_delivery_date DESC
        ");
        
        return $query->rows;
    }
    
    /**
     * التحقق من صحة بيانات التسوية
     */
    private function validateSettlementData($data) {
        if (empty($data['company_id']) || empty($data['settlement_date'])) {
            return false;
        }
        
        if (empty($data['shipments']) || !is_array($data['shipments'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * الحصول على تفاصيل الشحنة
     */
    private function getShipmentDetails($shipment_id) {
        $query = $this->db->query("
            SELECT * FROM cod_shipping_order 
            WHERE shipping_order_id = '" . (int)$shipment_id . "'
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * الحصول على أسعار شركة الشحن
     */
    private function getCompanyRates($company_id) {
        $query = $this->db->query("
            SELECT `key`, `value` FROM cod_shipping_company_config 
            WHERE company_id = '" . (int)$company_id . "'
            AND `key` IN ('cod_fee_percentage', 'return_fee', 'failed_delivery_fee')
        ");
        
        $rates = [];
        foreach ($query->rows as $row) {
            $rates[$row['key']] = $row['value'];
        }
        
        return $rates;
    }
    
    /**
     * الحصول على معرف الحساب المحاسبي
     */
    private function getAccountId($account_key) {
        $account_mapping = [
            'bank' => 1002,                           // البنك
            'cash' => 1001,                           // النقدية
            'shipping_expense' => 5101,               // مصروف الشحن والتوصيل
            'shipping_company_receivable' => 1301,   // مستحق من شركات الشحن
            'shipping_company_payable' => 2101       // مستحق لشركات الشحن
        ];
        
        return isset($account_mapping[$account_key]) ? $account_mapping[$account_key] : 0;
    }
}
