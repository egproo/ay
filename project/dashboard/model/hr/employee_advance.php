<?php
/**
 * نموذج إدارة السلف والقروض للموظفين مع التكامل المحاسبي
 * 
 * يوفر إدارة شاملة للسلف والقروض مع:
 * - طلبات السلف والموافقة عليها
 * - جدولة الاستقطاعات
 * - التكامل مع المحاسبة
 * - قيود اليومية التلقائية
 * - تقارير السلف المتقدمة
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelHrEmployeeAdvance extends Model {
    
    /**
     * إضافة طلب سلفة جديد
     */
    public function addAdvanceRequest($data) {
        $this->db->query("
            INSERT INTO cod_employee_advance SET 
            employee_id = '" . (int)$data['employee_id'] . "',
            advance_type = '" . $this->db->escape($data['advance_type']) . "',
            amount = '" . (float)$data['amount'] . "',
            reason = '" . $this->db->escape($data['reason']) . "',
            installments = '" . (int)$data['installments'] . "',
            installment_amount = '" . (float)($data['amount'] / $data['installments']) . "',
            start_deduction_date = '" . $this->db->escape($data['start_deduction_date']) . "',
            status = 'pending',
            requested_by = '" . (int)$this->user->getId() . "',
            date_requested = NOW()
        ");
        
        $advance_id = $this->db->getLastId();
        
        // إنشاء جدول الاستقطاعات
        $this->generateInstallmentSchedule($advance_id, $data);
        
        return $advance_id;
    }
    
    /**
     * إنشاء جدول الاستقطاعات
     */
    private function generateInstallmentSchedule($advance_id, $data) {
        $installment_amount = $data['amount'] / $data['installments'];
        $start_date = new DateTime($data['start_deduction_date']);
        
        for ($i = 1; $i <= $data['installments']; $i++) {
            $due_date = clone $start_date;
            $due_date->add(new DateInterval('P' . ($i - 1) . 'M')); // إضافة شهر لكل قسط
            
            $this->db->query("
                INSERT INTO cod_employee_advance_installment SET 
                advance_id = '" . (int)$advance_id . "',
                installment_number = '" . (int)$i . "',
                installment_amount = '" . (float)$installment_amount . "',
                due_date = '" . $due_date->format('Y-m-d') . "',
                status = 'pending'
            ");
        }
    }
    
    /**
     * الموافقة على طلب السلفة
     */
    public function approveAdvance($advance_id, $approval_notes = '') {
        // التحقق من حالة الطلب
        $advance = $this->getAdvance($advance_id);
        if (!$advance || $advance['status'] != 'pending') {
            throw new Exception('لا يمكن الموافقة على هذا الطلب');
        }
        
        // تحديث حالة الطلب
        $this->db->query("
            UPDATE cod_employee_advance SET 
            status = 'approved',
            approved_by = '" . (int)$this->user->getId() . "',
            approval_notes = '" . $this->db->escape($approval_notes) . "',
            date_approved = NOW()
            WHERE advance_id = '" . (int)$advance_id . "'
        ");
        
        return true;
    }
    
    /**
     * صرف السلفة وإنشاء القيد المحاسبي
     */
    public function disburseAdvance($advance_id, $payment_method = 'cash') {
        // التحقق من حالة الطلب
        $advance = $this->getAdvance($advance_id);
        if (!$advance || $advance['status'] != 'approved') {
            throw new Exception('يجب الموافقة على الطلب أولاً');
        }
        
        // إنشاء القيد المحاسبي
        $this->load->model('accounts/journal');
        
        $journal_data = [
            'reference' => 'ADV-' . str_pad($advance_id, 6, '0', STR_PAD_LEFT),
            'description' => 'صرف سلفة للموظف: ' . $advance['employee_name'],
            'date' => date('Y-m-d'),
            'entries' => []
        ];
        
        // مدين: سلف الموظفين
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId('employee_advances'),
            'debit' => $advance['amount'],
            'credit' => 0,
            'description' => 'سلفة موظف - ' . $advance['employee_name']
        ];
        
        // دائن: النقدية أو البنك
        $account_key = ($payment_method == 'cash') ? 'cash' : 'bank';
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId($account_key),
            'debit' => 0,
            'credit' => $advance['amount'],
            'description' => 'صرف سلفة - ' . ($payment_method == 'cash' ? 'نقداً' : 'تحويل بنكي')
        ];
        
        // إنشاء القيد
        $journal_id = $this->model_accounts_journal->addJournalEntry($journal_data);
        
        // تحديث حالة السلفة
        $this->db->query("
            UPDATE cod_employee_advance SET 
            status = 'disbursed',
            payment_method = '" . $this->db->escape($payment_method) . "',
            journal_id = '" . (int)$journal_id . "',
            disbursed_by = '" . (int)$this->user->getId() . "',
            date_disbursed = NOW()
            WHERE advance_id = '" . (int)$advance_id . "'
        ");
        
        return $journal_id;
    }
    
    /**
     * استقطاع قسط من الراتب
     */
    public function deductInstallment($installment_id, $payroll_record_id) {
        // الحصول على بيانات القسط
        $installment = $this->getInstallment($installment_id);
        if (!$installment || $installment['status'] != 'pending') {
            throw new Exception('القسط غير متاح للاستقطاع');
        }
        
        // تحديث حالة القسط
        $this->db->query("
            UPDATE cod_employee_advance_installment SET 
            status = 'deducted',
            payroll_record_id = '" . (int)$payroll_record_id . "',
            date_deducted = NOW()
            WHERE installment_id = '" . (int)$installment_id . "'
        ");
        
        // التحقق من اكتمال السداد
        $this->checkAdvanceCompletion($installment['advance_id']);
        
        return true;
    }
    
    /**
     * التحقق من اكتمال سداد السلفة
     */
    private function checkAdvanceCompletion($advance_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as pending_count 
            FROM cod_employee_advance_installment 
            WHERE advance_id = '" . (int)$advance_id . "' 
            AND status = 'pending'
        ");
        
        if ($query->row['pending_count'] == 0) {
            // تحديث حالة السلفة إلى مكتملة
            $this->db->query("
                UPDATE cod_employee_advance SET 
                status = 'completed',
                date_completed = NOW()
                WHERE advance_id = '" . (int)$advance_id . "'
            ");
            
            // إنشاء قيد إقفال السلفة
            $this->createAdvanceClosureEntry($advance_id);
        }
    }
    
    /**
     * إنشاء قيد إقفال السلفة
     */
    private function createAdvanceClosureEntry($advance_id) {
        $advance = $this->getAdvance($advance_id);
        
        $this->load->model('accounts/journal');
        
        $journal_data = [
            'reference' => 'ADV-CLOSE-' . str_pad($advance_id, 6, '0', STR_PAD_LEFT),
            'description' => 'إقفال سلفة مكتملة للموظف: ' . $advance['employee_name'],
            'date' => date('Y-m-d'),
            'entries' => []
        ];
        
        // مدين: الرواتب المستحقة (إجمالي الاستقطاعات)
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId('salary_payable'),
            'debit' => $advance['amount'],
            'credit' => 0,
            'description' => 'إقفال سلفة مكتملة'
        ];
        
        // دائن: سلف الموظفين
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId('employee_advances'),
            'debit' => 0,
            'credit' => $advance['amount'],
            'description' => 'إقفال سلفة مكتملة'
        ];
        
        // إنشاء القيد
        $journal_id = $this->model_accounts_journal->addJournalEntry($journal_data);
        
        // تحديث السلفة بمعرف قيد الإقفال
        $this->db->query("
            UPDATE cod_employee_advance SET 
            closure_journal_id = '" . (int)$journal_id . "'
            WHERE advance_id = '" . (int)$advance_id . "'
        ");
        
        return $journal_id;
    }
    
    /**
     * رفض طلب السلفة
     */
    public function rejectAdvance($advance_id, $rejection_reason) {
        $this->db->query("
            UPDATE cod_employee_advance SET 
            status = 'rejected',
            rejected_by = '" . (int)$this->user->getId() . "',
            rejection_reason = '" . $this->db->escape($rejection_reason) . "',
            date_rejected = NOW()
            WHERE advance_id = '" . (int)$advance_id . "'
        ");
        
        return true;
    }
    
    /**
     * الحصول على بيانات السلفة
     */
    public function getAdvance($advance_id) {
        $query = $this->db->query("
            SELECT ea.*, 
                ep.job_title,
                CONCAT(u.firstname, ' ', u.lastname) as employee_name,
                u.email as employee_email,
                CONCAT(u1.firstname, ' ', u1.lastname) as requested_by_name,
                CONCAT(u2.firstname, ' ', u2.lastname) as approved_by_name,
                CONCAT(u3.firstname, ' ', u3.lastname) as disbursed_by_name,
                CONCAT(u4.firstname, ' ', u4.lastname) as rejected_by_name
            FROM cod_employee_advance ea
            LEFT JOIN cod_employee_profile ep ON (ea.employee_id = ep.employee_id)
            LEFT JOIN cod_user u ON (ep.user_id = u.user_id)
            LEFT JOIN cod_user u1 ON (ea.requested_by = u1.user_id)
            LEFT JOIN cod_user u2 ON (ea.approved_by = u2.user_id)
            LEFT JOIN cod_user u3 ON (ea.disbursed_by = u3.user_id)
            LEFT JOIN cod_user u4 ON (ea.rejected_by = u4.user_id)
            WHERE ea.advance_id = '" . (int)$advance_id . "'
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * الحصول على بيانات القسط
     */
    public function getInstallment($installment_id) {
        $query = $this->db->query("
            SELECT eai.*, ea.employee_id, ea.amount as total_amount
            FROM cod_employee_advance_installment eai
            LEFT JOIN cod_employee_advance ea ON (eai.advance_id = ea.advance_id)
            WHERE eai.installment_id = '" . (int)$installment_id . "'
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * الحصول على قائمة السلف
     */
    public function getAdvances($filter_data = []) {
        $sql = "SELECT ea.*, 
                ep.job_title,
                CONCAT(u.firstname, ' ', u.lastname) as employee_name,
                CONCAT(u1.firstname, ' ', u1.lastname) as requested_by_name,
                (SELECT COUNT(*) FROM cod_employee_advance_installment WHERE advance_id = ea.advance_id AND status = 'deducted') as paid_installments,
                (SELECT COUNT(*) FROM cod_employee_advance_installment WHERE advance_id = ea.advance_id) as total_installments
                FROM cod_employee_advance ea
                LEFT JOIN cod_employee_profile ep ON (ea.employee_id = ep.employee_id)
                LEFT JOIN cod_user u ON (ep.user_id = u.user_id)
                LEFT JOIN cod_user u1 ON (ea.requested_by = u1.user_id)
                WHERE 1";
        
        if (!empty($filter_data['filter_employee'])) {
            $sql .= " AND ea.employee_id = '" . (int)$filter_data['filter_employee'] . "'";
        }
        
        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND ea.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }
        
        if (!empty($filter_data['filter_type'])) {
            $sql .= " AND ea.advance_type = '" . $this->db->escape($filter_data['filter_type']) . "'";
        }
        
        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND DATE(ea.date_requested) >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }
        
        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND DATE(ea.date_requested) <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }
        
        $sql .= " ORDER BY ea.date_requested DESC";
        
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
     * الحصول على أقساط السلفة
     */
    public function getAdvanceInstallments($advance_id) {
        $query = $this->db->query("
            SELECT eai.*, 
                pr.cycle_id,
                pc.cycle_name
            FROM cod_employee_advance_installment eai
            LEFT JOIN cod_payroll_record pr ON (eai.payroll_record_id = pr.record_id)
            LEFT JOIN cod_payroll_cycle pc ON (pr.cycle_id = pc.cycle_id)
            WHERE eai.advance_id = '" . (int)$advance_id . "'
            ORDER BY eai.installment_number
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على الأقساط المستحقة للموظف
     */
    public function getEmployeePendingInstallments($employee_id, $due_date = null) {
        if (!$due_date) {
            $due_date = date('Y-m-d');
        }
        
        $query = $this->db->query("
            SELECT eai.*, ea.advance_type, ea.reason
            FROM cod_employee_advance_installment eai
            LEFT JOIN cod_employee_advance ea ON (eai.advance_id = ea.advance_id)
            WHERE ea.employee_id = '" . (int)$employee_id . "'
            AND eai.status = 'pending'
            AND eai.due_date <= '" . $this->db->escape($due_date) . "'
            AND ea.status = 'disbursed'
            ORDER BY eai.due_date
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على معرف الحساب المحاسبي
     */
    private function getAccountId($account_key) {
        $account_mapping = [
            'employee_advances' => 1301, // سلف الموظفين
            'salary_payable' => 2001,    // الرواتب المستحقة
            'bank' => 1002,              // البنك
            'cash' => 1001               // النقدية
        ];
        
        return isset($account_mapping[$account_key]) ? $account_mapping[$account_key] : 0;
    }
}
