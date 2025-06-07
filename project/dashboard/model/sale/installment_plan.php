<?php
/**
 * نموذج خطط التقسيط للعملاء (Customer Installment Plans Model)
 * 
 * الهدف: إدارة خطط التقسيط الفردية للعملاء في قاعدة البيانات
 * الميزات: CRUD operations، حسابات الأقساط، التكامل المحاسبي، تتبع المدفوعات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelSaleInstallmentPlan extends Model {
    
    /**
     * إضافة خطة تقسيط جديدة
     */
    public function addPlan($data) {
        // الحصول على قالب التقسيط
        $this->load->model('sale/installment_template');
        $template = $this->model_sale_installment_template->getTemplate($data['template_id']);
        
        if (!$template) {
            return false;
        }
        
        // حساب تفاصيل الخطة
        $total_amount = (float)$data['total_amount'];
        $down_payment = $total_amount * ($template['down_payment_percentage'] / 100);
        $financed_amount = $total_amount - $down_payment;
        
        // حساب مبلغ القسط حسب نوع الفائدة
        $installment_calculation = $this->calculateInstallmentAmount($template, $financed_amount);
        
        // إدراج الخطة الرئيسية
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "installment_plan SET 
                customer_id = '" . (int)$data['customer_id'] . "',
                template_id = '" . (int)$data['template_id'] . "',
                order_id = '" . (int)($data['order_id'] ?? 0) . "',
                total_amount = '" . (float)$total_amount . "',
                down_payment = '" . (float)$down_payment . "',
                financed_amount = '" . (float)$financed_amount . "',
                installment_amount = '" . (float)$installment_calculation['installment_amount'] . "',
                installments_count = '" . (int)$template['installments_count'] . "',
                interest_rate = '" . (float)$template['interest_rate'] . "',
                interest_type = '" . $this->db->escape($template['interest_type']) . "',
                total_interest = '" . (float)$installment_calculation['total_interest'] . "',
                start_date = '" . $this->db->escape($data['start_date'] ?? date('Y-m-d')) . "',
                status = 'active',
                notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                created_by = '" . (int)$this->user->getId() . "',
                date_created = NOW(),
                date_modified = NOW()
        ");
        
        $plan_id = $this->db->getLastId();
        
        // إنشاء جدول الأقساط
        $this->createInstallmentSchedule($plan_id, $template, $installment_calculation, $data['start_date'] ?? date('Y-m-d'));
        
        // إنشاء القيود المحاسبية
        $this->createAccountingEntries($plan_id, 'plan_created');
        
        return $plan_id;
    }
    
    /**
     * تعديل خطة تقسيط
     */
    public function editPlan($plan_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "installment_plan SET 
                notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                status = '" . $this->db->escape($data['status']) . "',
                date_modified = NOW()
            WHERE plan_id = '" . (int)$plan_id . "'
        ");
        
        // إذا تم تغيير الحالة، إنشاء قيد محاسبي
        if (isset($data['status'])) {
            $this->createAccountingEntries($plan_id, 'status_changed', $data['status']);
        }
    }
    
    /**
     * حذف خطة تقسيط
     */
    public function deletePlan($plan_id) {
        // التحقق من وجود مدفوعات
        $query = $this->db->query("
            SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "installment_payment 
            WHERE plan_id = '" . (int)$plan_id . "'
        ");
        
        if ($query->row['total'] > 0) {
            return false; // لا يمكن حذف خطة بها مدفوعات
        }
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "installment_plan WHERE plan_id = '" . (int)$plan_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "installment_schedule WHERE plan_id = '" . (int)$plan_id . "'");
        
        return true;
    }
    
    /**
     * الحصول على خطة تقسيط
     */
    public function getPlan($plan_id) {
        $query = $this->db->query("
            SELECT 
                p.*,
                c.firstname,
                c.lastname,
                c.email,
                c.telephone,
                CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                t.name as template_name,
                t.description as template_description,
                u.firstname as created_by_name
            FROM " . DB_PREFIX . "installment_plan p
            LEFT JOIN " . DB_PREFIX . "customer c ON (p.customer_id = c.customer_id)
            LEFT JOIN " . DB_PREFIX . "installment_template t ON (p.template_id = t.template_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (p.created_by = u.user_id)
            WHERE p.plan_id = '" . (int)$plan_id . "'
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * الحصول على خطط التقسيط
     */
    public function getPlans($data = []) {
        $sql = "
            SELECT 
                p.*,
                CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                c.email as customer_email,
                t.name as template_name,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "installment_schedule s 
                 WHERE s.plan_id = p.plan_id AND s.status = 'paid') as paid_installments,
                (SELECT SUM(amount) FROM " . DB_PREFIX . "installment_payment pay 
                 WHERE pay.plan_id = p.plan_id) as total_paid,
                (p.financed_amount - COALESCE((SELECT SUM(amount) FROM " . DB_PREFIX . "installment_payment pay 
                 WHERE pay.plan_id = p.plan_id), 0)) as remaining_balance,
                (SELECT MIN(due_date) FROM " . DB_PREFIX . "installment_schedule s 
                 WHERE s.plan_id = p.plan_id AND s.status = 'pending') as next_due_date,
                (SELECT SUM(s.amount) FROM " . DB_PREFIX . "installment_schedule s 
                 WHERE s.plan_id = p.plan_id AND s.status = 'overdue') as overdue_amount,
                (SELECT DATEDIFF(CURDATE(), MIN(due_date)) FROM " . DB_PREFIX . "installment_schedule s 
                 WHERE s.plan_id = p.plan_id AND s.status = 'overdue') as overdue_days
            FROM " . DB_PREFIX . "installment_plan p
            LEFT JOIN " . DB_PREFIX . "customer c ON (p.customer_id = c.customer_id)
            LEFT JOIN " . DB_PREFIX . "installment_template t ON (p.template_id = t.template_id)
            WHERE 1=1
        ";
        
        // تطبيق الفلاتر
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND p.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_template'])) {
            $sql .= " AND p.template_id = '" . (int)$data['filter_template'] . "'";
        }
        
        if (!empty($data['filter_overdue']) && $data['filter_overdue'] == '1') {
            $sql .= " AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "installment_schedule s 
                     WHERE s.plan_id = p.plan_id AND s.status = 'overdue')";
        }
        
        if (!empty($data['filter_amount_from'])) {
            $sql .= " AND p.total_amount >= '" . (float)$data['filter_amount_from'] . "'";
        }
        
        if (!empty($data['filter_amount_to'])) {
            $sql .= " AND p.total_amount <= '" . (float)$data['filter_amount_to'] . "'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(p.date_created) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(p.date_created) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        // ترتيب النتائج
        $sort_data = [
            'customer_name',
            'total_amount',
            'remaining_balance',
            'next_due_date',
            'status',
            'date_created'
        ];
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY p.date_created";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        // تحديد عدد النتائج
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
     * الحصول على إجمالي عدد الخطط
     */
    public function getTotalPlans($data = []) {
        $sql = "
            SELECT COUNT(DISTINCT p.plan_id) AS total
            FROM " . DB_PREFIX . "installment_plan p
            LEFT JOIN " . DB_PREFIX . "customer c ON (p.customer_id = c.customer_id)
            WHERE 1=1
        ";
        
        // تطبيق نفس الفلاتر
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND p.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_template'])) {
            $sql .= " AND p.template_id = '" . (int)$data['filter_template'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على جدول أقساط الخطة
     */
    public function getPlanInstallments($plan_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "installment_schedule 
            WHERE plan_id = '" . (int)$plan_id . "'
            ORDER BY installment_number ASC
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على مدفوعات الخطة
     */
    public function getPlanPayments($plan_id) {
        $query = $this->db->query("
            SELECT 
                p.*,
                u.firstname as received_by_name
            FROM " . DB_PREFIX . "installment_payment p
            LEFT JOIN " . DB_PREFIX . "user u ON (p.received_by = u.user_id)
            WHERE p.plan_id = '" . (int)$plan_id . "'
            ORDER BY p.payment_date DESC
        ");
        
        return $query->rows;
    }
    
    /**
     * حساب مبلغ القسط
     */
    private function calculateInstallmentAmount($template, $financed_amount) {
        $installments_count = $template['installments_count'];
        $interest_rate = $template['interest_rate'] / 100;
        
        switch ($template['interest_type']) {
            case 'fixed':
                $total_interest = $financed_amount * $interest_rate;
                $total_amount = $financed_amount + $total_interest;
                $installment_amount = $total_amount / $installments_count;
                break;
                
            case 'reducing':
                $monthly_rate = $interest_rate / 12;
                $installment_amount = $financed_amount * ($monthly_rate * pow(1 + $monthly_rate, $installments_count)) / (pow(1 + $monthly_rate, $installments_count) - 1);
                $total_interest = ($installment_amount * $installments_count) - $financed_amount;
                break;
                
            case 'simple':
                $total_interest = $financed_amount * $interest_rate * ($installments_count / 12);
                $total_amount = $financed_amount + $total_interest;
                $installment_amount = $total_amount / $installments_count;
                break;
                
            default:
                $installment_amount = $financed_amount / $installments_count;
                $total_interest = 0;
        }
        
        return [
            'installment_amount' => round($installment_amount, 2),
            'total_interest' => round($total_interest, 2)
        ];
    }
    
    /**
     * إنشاء جدول الأقساط
     */
    private function createInstallmentSchedule($plan_id, $template, $calculation, $start_date) {
        $remaining_balance = 0;
        $current_date = $start_date;
        
        for ($i = 1; $i <= $template['installments_count']; $i++) {
            $due_date = date('Y-m-d', strtotime($current_date . ' +1 month'));
            
            // حساب مكونات القسط (أصل + فائدة)
            if ($template['interest_type'] == 'reducing') {
                $interest_amount = $remaining_balance * ($template['interest_rate'] / 100) / 12;
                $principal_amount = $calculation['installment_amount'] - $interest_amount;
            } else {
                $interest_amount = $calculation['total_interest'] / $template['installments_count'];
                $principal_amount = $calculation['installment_amount'] - $interest_amount;
            }
            
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "installment_schedule SET
                    plan_id = '" . (int)$plan_id . "',
                    installment_number = '" . (int)$i . "',
                    due_date = '" . $this->db->escape($due_date) . "',
                    amount = '" . (float)$calculation['installment_amount'] . "',
                    principal_amount = '" . (float)$principal_amount . "',
                    interest_amount = '" . (float)$interest_amount . "',
                    status = 'pending',
                    date_created = NOW()
            ");
            
            $remaining_balance -= $principal_amount;
            $current_date = $due_date;
        }
    }
    
    /**
     * إنشاء القيود المحاسبية
     */
    private function createAccountingEntries($plan_id, $type, $additional_data = null) {
        $this->load->model('accounting/journal_entry');
        
        $plan = $this->getPlan($plan_id);
        
        if (!$plan) {
            return false;
        }
        
        switch ($type) {
            case 'plan_created':
                // قيد إنشاء خطة التقسيط
                $entries = [
                    [
                        'account_code' => '1131', // العملاء - تقسيط
                        'debit' => $plan['financed_amount'] + $plan['total_interest'],
                        'credit' => 0,
                        'description' => 'خطة تقسيط للعميل: ' . $plan['customer_name']
                    ],
                    [
                        'account_code' => '4111', // المبيعات
                        'debit' => 0,
                        'credit' => $plan['financed_amount'],
                        'description' => 'مبيعات تقسيط للعميل: ' . $plan['customer_name']
                    ],
                    [
                        'account_code' => '2411', // إيرادات فوائد مؤجلة
                        'debit' => 0,
                        'credit' => $plan['total_interest'],
                        'description' => 'إيرادات فوائد مؤجلة - خطة تقسيط'
                    ]
                ];
                break;
                
            default:
                return false;
        }
        
        // إنشاء القيد المحاسبي
        $journal_data = [
            'reference' => 'INST-PLAN-' . $plan_id,
            'description' => 'قيد خطة تقسيط رقم ' . $plan_id,
            'entries' => $entries
        ];
        
        return $this->model_accounting_journal_entry->addJournalEntry($journal_data);
    }
    
    /**
     * إحصائيات سريعة
     */
    public function getTotalFinancedAmount() {
        $query = $this->db->query("SELECT SUM(financed_amount) as total FROM " . DB_PREFIX . "installment_plan WHERE status != 'cancelled'");
        return $query->row['total'] ?? 0;
    }
    
    public function getTotalCollected() {
        $query = $this->db->query("SELECT SUM(amount) as total FROM " . DB_PREFIX . "installment_payment");
        return $query->row['total'] ?? 0;
    }
    
    public function getTotalOutstanding() {
        $query = $this->db->query("
            SELECT SUM(p.financed_amount - COALESCE(pay.total_paid, 0)) as total
            FROM " . DB_PREFIX . "installment_plan p
            LEFT JOIN (
                SELECT plan_id, SUM(amount) as total_paid 
                FROM " . DB_PREFIX . "installment_payment 
                GROUP BY plan_id
            ) pay ON p.plan_id = pay.plan_id
            WHERE p.status IN ('active', 'overdue')
        ");
        return $query->row['total'] ?? 0;
    }
    
    public function getTotalOverdueAmount() {
        $query = $this->db->query("
            SELECT SUM(amount) as total 
            FROM " . DB_PREFIX . "installment_schedule 
            WHERE status = 'overdue'
        ");
        return $query->row['total'] ?? 0;
    }
}
