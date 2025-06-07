<?php
/**
 * نموذج قوالب خطط التقسيط (Installment Templates Model)
 * 
 * الهدف: إدارة قوالب خطط التقسيط في قاعدة البيانات
 * الميزات: CRUD operations، حسابات الفوائد، التكامل المحاسبي
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelSaleInstallmentTemplate extends Model {
    
    /**
     * إضافة قالب تقسيط جديد
     */
    public function addTemplate($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "installment_template SET 
                name = '" . $this->db->escape($data['name']) . "',
                description = '" . $this->db->escape($data['description'] ?? '') . "',
                installments_count = '" . (int)$data['installments_count'] . "',
                interest_rate = '" . (float)$data['interest_rate'] . "',
                interest_type = '" . $this->db->escape($data['interest_type']) . "',
                min_amount = '" . (float)$data['min_amount'] . "',
                max_amount = '" . (float)$data['max_amount'] . "',
                down_payment_percentage = '" . (float)$data['down_payment_percentage'] . "',
                grace_period_days = '" . (int)($data['grace_period_days'] ?? 0) . "',
                late_fee_percentage = '" . (float)($data['late_fee_percentage'] ?? 0) . "',
                early_payment_discount = '" . (float)($data['early_payment_discount'] ?? 0) . "',
                customer_group_restriction = '" . $this->db->escape($data['customer_group_restriction'] ?? '') . "',
                product_category_restriction = '" . $this->db->escape($data['product_category_restriction'] ?? '') . "',
                terms_conditions = '" . $this->db->escape($data['terms_conditions'] ?? '') . "',
                accounting_settings = '" . $this->db->escape(json_encode($data['accounting_settings'] ?? [])) . "',
                status = '" . (int)$data['status'] . "',
                sort_order = '" . (int)($data['sort_order'] ?? 0) . "',
                date_added = NOW(),
                date_modified = NOW()
        ");
        
        $template_id = $this->db->getLastId();
        
        // إضافة إعدادات الحسابات المحاسبية
        if (isset($data['accounting_settings']) && is_array($data['accounting_settings'])) {
            $this->addAccountingSettings($template_id, $data['accounting_settings']);
        }
        
        // إضافة شروط الأهلية
        if (isset($data['eligibility_criteria']) && is_array($data['eligibility_criteria'])) {
            $this->addEligibilityCriteria($template_id, $data['eligibility_criteria']);
        }
        
        return $template_id;
    }
    
    /**
     * تعديل قالب تقسيط
     */
    public function editTemplate($template_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "installment_template SET 
                name = '" . $this->db->escape($data['name']) . "',
                description = '" . $this->db->escape($data['description'] ?? '') . "',
                installments_count = '" . (int)$data['installments_count'] . "',
                interest_rate = '" . (float)$data['interest_rate'] . "',
                interest_type = '" . $this->db->escape($data['interest_type']) . "',
                min_amount = '" . (float)$data['min_amount'] . "',
                max_amount = '" . (float)$data['max_amount'] . "',
                down_payment_percentage = '" . (float)$data['down_payment_percentage'] . "',
                grace_period_days = '" . (int)($data['grace_period_days'] ?? 0) . "',
                late_fee_percentage = '" . (float)($data['late_fee_percentage'] ?? 0) . "',
                early_payment_discount = '" . (float)($data['early_payment_discount'] ?? 0) . "',
                customer_group_restriction = '" . $this->db->escape($data['customer_group_restriction'] ?? '') . "',
                product_category_restriction = '" . $this->db->escape($data['product_category_restriction'] ?? '') . "',
                terms_conditions = '" . $this->db->escape($data['terms_conditions'] ?? '') . "',
                accounting_settings = '" . $this->db->escape(json_encode($data['accounting_settings'] ?? [])) . "',
                status = '" . (int)$data['status'] . "',
                sort_order = '" . (int)($data['sort_order'] ?? 0) . "',
                date_modified = NOW()
            WHERE template_id = '" . (int)$template_id . "'
        ");
        
        // تحديث إعدادات الحسابات المحاسبية
        $this->deleteAccountingSettings($template_id);
        if (isset($data['accounting_settings']) && is_array($data['accounting_settings'])) {
            $this->addAccountingSettings($template_id, $data['accounting_settings']);
        }
        
        // تحديث شروط الأهلية
        $this->deleteEligibilityCriteria($template_id);
        if (isset($data['eligibility_criteria']) && is_array($data['eligibility_criteria'])) {
            $this->addEligibilityCriteria($template_id, $data['eligibility_criteria']);
        }
    }
    
    /**
     * حذف قالب تقسيط
     */
    public function deleteTemplate($template_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "installment_template WHERE template_id = '" . (int)$template_id . "'");
        $this->deleteAccountingSettings($template_id);
        $this->deleteEligibilityCriteria($template_id);
    }
    
    /**
     * الحصول على قالب تقسيط
     */
    public function getTemplate($template_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "installment_template 
            WHERE template_id = '" . (int)$template_id . "'
        ");
        
        if ($query->num_rows) {
            $template = $query->row;
            $template['accounting_settings'] = json_decode($template['accounting_settings'], true);
            $template['eligibility_criteria'] = $this->getEligibilityCriteria($template_id);
            return $template;
        }
        
        return false;
    }
    
    /**
     * الحصول على قوالب التقسيط
     */
    public function getTemplates($data = []) {
        $sql = "SELECT * FROM " . DB_PREFIX . "installment_template WHERE 1=1";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND status = '" . (int)$data['filter_status'] . "'";
        }
        
        if (!empty($data['filter_interest_type'])) {
            $sql .= " AND interest_type = '" . $this->db->escape($data['filter_interest_type']) . "'";
        }
        
        $sort_data = [
            'name',
            'installments_count',
            'interest_rate',
            'min_amount',
            'max_amount',
            'status',
            'date_added'
        ];
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
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
     * الحصول على إجمالي عدد القوالب
     */
    public function getTotalTemplates($data = []) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "installment_template WHERE 1=1";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND status = '" . (int)$data['filter_status'] . "'";
        }
        
        if (!empty($data['filter_interest_type'])) {
            $sql .= " AND interest_type = '" . $this->db->escape($data['filter_interest_type']) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * التحقق من وجود خطط تقسيط مرتبطة
     */
    public function hasInstallmentPlans($template_id) {
        $query = $this->db->query("
            SELECT COUNT(*) AS total 
            FROM " . DB_PREFIX . "installment_plan 
            WHERE template_id = '" . (int)$template_id . "'
        ");
        
        return $query->row['total'] > 0;
    }
    
    /**
     * الحصول على القالب الأكثر استخداماً
     */
    public function getMostUsedTemplate() {
        $query = $this->db->query("
            SELECT t.name, COUNT(p.plan_id) as usage_count
            FROM " . DB_PREFIX . "installment_template t
            LEFT JOIN " . DB_PREFIX . "installment_plan p ON t.template_id = p.template_id
            WHERE t.status = 1
            GROUP BY t.template_id
            ORDER BY usage_count DESC
            LIMIT 1
        ");
        
        return $query->num_rows ? $query->row : ['name' => 'لا يوجد', 'usage_count' => 0];
    }
    
    /**
     * الحصول على متوسط معدل الفائدة
     */
    public function getAverageInterestRate() {
        $query = $this->db->query("
            SELECT AVG(interest_rate) as avg_rate 
            FROM " . DB_PREFIX . "installment_template 
            WHERE status = 1 AND interest_rate > 0
        ");
        
        return $query->num_rows ? number_format($query->row['avg_rate'], 2) : '0.00';
    }
    
    /**
     * الحصول على القوالب المتاحة للعميل
     */
    public function getAvailableTemplatesForCustomer($customer_id, $amount = 0) {
        $this->load->model('customer/customer');
        $customer_info = $this->model_customer_customer->getCustomer($customer_id);
        
        $sql = "
            SELECT * FROM " . DB_PREFIX . "installment_template 
            WHERE status = 1
        ";
        
        if ($amount > 0) {
            $sql .= " AND min_amount <= '" . (float)$amount . "' AND max_amount >= '" . (float)$amount . "'";
        }
        
        if ($customer_info && $customer_info['customer_group_id']) {
            $sql .= " AND (customer_group_restriction = '' OR FIND_IN_SET('" . (int)$customer_info['customer_group_id'] . "', customer_group_restriction))";
        }
        
        $sql .= " ORDER BY sort_order ASC, name ASC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * حساب جدول الأقساط
     */
    public function calculateInstallmentSchedule($template_id, $amount, $start_date = null) {
        $template = $this->getTemplate($template_id);
        
        if (!$template) {
            return false;
        }
        
        if (!$start_date) {
            $start_date = date('Y-m-d');
        }
        
        $schedule = [];
        $down_payment = $amount * ($template['down_payment_percentage'] / 100);
        $financed_amount = $amount - $down_payment;
        
        // حساب الفائدة والأقساط حسب نوع الفائدة
        switch ($template['interest_type']) {
            case 'fixed':
                $total_interest = $financed_amount * ($template['interest_rate'] / 100);
                $total_amount = $financed_amount + $total_interest;
                $installment_amount = $total_amount / $template['installments_count'];
                break;
                
            case 'reducing':
                $monthly_rate = ($template['interest_rate'] / 100) / 12;
                $installment_amount = $financed_amount * ($monthly_rate * pow(1 + $monthly_rate, $template['installments_count'])) / (pow(1 + $monthly_rate, $template['installments_count']) - 1);
                break;
                
            case 'simple':
                $total_interest = $financed_amount * ($template['interest_rate'] / 100) * ($template['installments_count'] / 12);
                $total_amount = $financed_amount + $total_interest;
                $installment_amount = $total_amount / $template['installments_count'];
                break;
                
            default:
                $installment_amount = $financed_amount / $template['installments_count'];
                $total_interest = 0;
        }
        
        // إنشاء جدول الأقساط
        $remaining_balance = $financed_amount;
        $current_date = $start_date;
        
        for ($i = 1; $i <= $template['installments_count']; $i++) {
            $due_date = date('Y-m-d', strtotime($current_date . ' +1 month'));
            
            if ($template['interest_type'] == 'reducing') {
                $interest_amount = $remaining_balance * ($template['interest_rate'] / 100) / 12;
                $principal_amount = $installment_amount - $interest_amount;
            } else {
                $interest_amount = ($total_interest ?? 0) / $template['installments_count'];
                $principal_amount = $installment_amount - $interest_amount;
            }
            
            $schedule[] = [
                'installment_number' => $i,
                'due_date' => $due_date,
                'installment_amount' => round($installment_amount, 2),
                'principal_amount' => round($principal_amount, 2),
                'interest_amount' => round($interest_amount, 2),
                'remaining_balance' => round($remaining_balance - $principal_amount, 2)
            ];
            
            $remaining_balance -= $principal_amount;
            $current_date = $due_date;
        }
        
        return [
            'template' => $template,
            'total_amount' => $amount,
            'down_payment' => $down_payment,
            'financed_amount' => $financed_amount,
            'total_interest' => $total_interest ?? array_sum(array_column($schedule, 'interest_amount')),
            'installment_amount' => $installment_amount,
            'schedule' => $schedule
        ];
    }
    
    /**
     * إضافة إعدادات الحسابات المحاسبية
     */
    private function addAccountingSettings($template_id, $settings) {
        foreach ($settings as $setting_key => $setting_value) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "installment_template_accounting SET
                    template_id = '" . (int)$template_id . "',
                    setting_key = '" . $this->db->escape($setting_key) . "',
                    setting_value = '" . $this->db->escape($setting_value) . "'
            ");
        }
    }
    
    /**
     * حذف إعدادات الحسابات المحاسبية
     */
    private function deleteAccountingSettings($template_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "installment_template_accounting WHERE template_id = '" . (int)$template_id . "'");
    }
    
    /**
     * إضافة شروط الأهلية
     */
    private function addEligibilityCriteria($template_id, $criteria) {
        foreach ($criteria as $criterion) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "installment_template_criteria SET
                    template_id = '" . (int)$template_id . "',
                    criteria_type = '" . $this->db->escape($criterion['type']) . "',
                    criteria_value = '" . $this->db->escape($criterion['value']) . "',
                    operator = '" . $this->db->escape($criterion['operator']) . "'
            ");
        }
    }
    
    /**
     * حذف شروط الأهلية
     */
    private function deleteEligibilityCriteria($template_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "installment_template_criteria WHERE template_id = '" . (int)$template_id . "'");
    }
    
    /**
     * الحصول على شروط الأهلية
     */
    private function getEligibilityCriteria($template_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "installment_template_criteria 
            WHERE template_id = '" . (int)$template_id . "'
        ");
        
        return $query->rows;
    }
}
