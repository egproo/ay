<?php
/**
 * نظام صلاحيات القيود المحاسبية المتقدم
 * مستوى احترافي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsJournalPermissions extends Controller {
    
    /**
     * التحقق من صلاحية تعديل القيد
     * @param int $journal_id
     * @param array $journal_data
     * @return array
     */
    public function canEditJournal($journal_id, $journal_data = null) {
        $this->load->model('accounts/journal_entry');
        $this->load->model('user/user_group');
        
        if (!$journal_data) {
            $journal_data = $this->model_accounts_journal_entry->getJournalEntry($journal_id);
        }
        
        $result = [
            'allowed' => false,
            'reason' => '',
            'restrictions' => []
        ];
        
        // 1. التحقق من الصلاحية الأساسية
        if (!$this->user->hasPermission('modify', 'accounts/journal_entry')) {
            $result['reason'] = 'لا توجد صلاحية تعديل القيود المحاسبية';
            return $result;
        }
        
        // 2. منع تعديل القيود المرحلة (Posted)
        if ($journal_data['status'] === 'posted') {
            // فقط المدير المالي أو مدير النظام يمكنه تعديل القيود المرحلة
            if (!$this->hasRole(['financial_manager', 'system_admin', 'cfo'])) {
                $result['reason'] = 'لا يمكن تعديل القيود المرحلة إلا من قبل المدير المالي';
                return $result;
            }
            
            // حتى المدير المالي يحتاج موافقة خاصة للقيود المرحلة منذ أكثر من 30 يوم
            $posted_date = strtotime($journal_data['posted_date']);
            $days_since_posted = (time() - $posted_date) / (24 * 60 * 60);
            
            if ($days_since_posted > 30) {
                if (!$this->hasSpecialPermission('edit_old_posted_journals')) {
                    $result['reason'] = 'القيود المرحلة منذ أكثر من 30 يوم تحتاج موافقة خاصة';
                    return $result;
                }
            }
            
            $result['restrictions'][] = 'تعديل قيد مرحل - سيتم تسجيل جميع التغييرات في سجل المراجعة';
        }
        
        // 3. منع تعديل القيود المعتمدة (Approved)
        if ($journal_data['status'] === 'approved') {
            if (!$this->hasRole(['cfo', 'system_admin'])) {
                $result['reason'] = 'لا يمكن تعديل القيود المعتمدة إلا من قبل المدير المالي التنفيذي';
                return $result;
            }
            
            $result['restrictions'][] = 'تعديل قيد معتمد - يتطلب إعادة اعتماد';
        }
        
        // 4. منع تعديل القيود المغلقة (Closed Period)
        if ($this->isInClosedPeriod($journal_data['journal_date'])) {
            if (!$this->hasSpecialPermission('edit_closed_period_journals')) {
                $result['reason'] = 'لا يمكن تعديل القيود في الفترات المغلقة';
                return $result;
            }
            
            $result['restrictions'][] = 'تعديل في فترة مغلقة - يتطلب موافقة المدير المالي';
        }
        
        // 5. التحقق من صلاحية تعديل الحسابات المستخدمة
        foreach ($journal_data['lines'] as $line) {
            if (!$this->canAccessAccount($line['account_id'])) {
                $result['reason'] = 'لا توجد صلاحية للوصول لأحد الحسابات المستخدمة';
                return $result;
            }
        }
        
        // 6. التحقق من حد المبلغ المسموح
        $total_amount = max($journal_data['total_debit'], $journal_data['total_credit']);
        $max_amount = $this->getUserMaxJournalAmount();
        
        if ($total_amount > $max_amount) {
            if (!$this->hasApprovalForAmount($total_amount)) {
                $result['reason'] = "المبلغ يتجاوز الحد المسموح ({$max_amount})";
                return $result;
            }
            
            $result['restrictions'][] = 'مبلغ كبير - يتطلب موافقة إضافية';
        }
        
        // 7. التحقق من قيود الوقت (Business Hours)
        if (!$this->isInBusinessHours() && !$this->hasRole(['system_admin'])) {
            $result['restrictions'][] = 'تعديل خارج ساعات العمل - سيتم إشعار المدير';
        }
        
        $result['allowed'] = true;
        return $result;
    }
    
    /**
     * التحقق من صلاحية حذف القيد
     * @param int $journal_id
     * @param array $journal_data
     * @return array
     */
    public function canDeleteJournal($journal_id, $journal_data = null) {
        $this->load->model('accounts/journal_entry');
        
        if (!$journal_data) {
            $journal_data = $this->model_accounts_journal_entry->getJournalEntry($journal_id);
        }
        
        $result = [
            'allowed' => false,
            'reason' => '',
            'requires_approval' => false
        ];
        
        // 1. فقط أدوار محددة يمكنها حذف القيود
        $allowed_roles = ['financial_manager', 'cfo', 'system_admin'];
        if (!$this->hasRole($allowed_roles)) {
            $result['reason'] = 'لا توجد صلاحية حذف القيود المحاسبية';
            return $result;
        }
        
        // 2. منع حذف القيود المرحلة نهائياً
        if ($journal_data['status'] === 'posted') {
            $result['reason'] = 'لا يمكن حذف القيود المرحلة - يمكن إلغاء الترحيل أولاً';
            return $result;
        }
        
        // 3. منع حذف القيود المعتمدة
        if ($journal_data['status'] === 'approved') {
            $result['reason'] = 'لا يمكن حذف القيود المعتمدة';
            return $result;
        }
        
        // 4. منع حذف القيود التلقائية
        if ($journal_data['auto_generated']) {
            if (!$this->hasSpecialPermission('delete_auto_journals')) {
                $result['reason'] = 'لا يمكن حذف القيود التلقائية';
                return $result;
            }
        }
        
        // 5. القيود الكبيرة تحتاج موافقة
        $total_amount = max($journal_data['total_debit'], $journal_data['total_credit']);
        if ($total_amount > 100000) { // مبلغ كبير
            $result['requires_approval'] = true;
        }
        
        // 6. القيود القديمة تحتاج موافقة خاصة
        $journal_date = strtotime($journal_data['journal_date']);
        $days_old = (time() - $journal_date) / (24 * 60 * 60);
        
        if ($days_old > 7) { // أكثر من أسبوع
            $result['requires_approval'] = true;
        }
        
        $result['allowed'] = true;
        return $result;
    }
    
    /**
     * التحقق من صلاحية ترحيل القيد
     */
    public function canPostJournal($journal_id, $journal_data = null) {
        if (!$journal_data) {
            $this->load->model('accounts/journal_entry');
            $journal_data = $this->model_accounts_journal_entry->getJournalEntry($journal_id);
        }
        
        $result = [
            'allowed' => false,
            'reason' => '',
            'warnings' => []
        ];
        
        // 1. التحقق من الصلاحية الأساسية
        if (!$this->user->hasPermission('modify', 'accounts/journal_entry')) {
            $result['reason'] = 'لا توجد صلاحية ترحيل القيود';
            return $result;
        }
        
        // 2. التحقق من حالة القيد
        if ($journal_data['status'] !== 'draft') {
            $result['reason'] = 'يمكن ترحيل المسودات فقط';
            return $result;
        }
        
        // 3. التحقق من التوازن
        if (abs($journal_data['total_debit'] - $journal_data['total_credit']) > 0.01) {
            $result['reason'] = 'القيد غير متوازن';
            return $result;
        }
        
        // 4. التحقق من الفترة المحاسبية
        if ($this->isInClosedPeriod($journal_data['journal_date'])) {
            $result['reason'] = 'لا يمكن الترحيل في فترة مغلقة';
            return $result;
        }
        
        // 5. التحقق من حد المبلغ
        $total_amount = max($journal_data['total_debit'], $journal_data['total_credit']);
        $max_amount = $this->getUserMaxPostingAmount();
        
        if ($total_amount > $max_amount) {
            $result['reason'] = "المبلغ يتجاوز حد الترحيل المسموح ({$max_amount})";
            return $result;
        }
        
        // 6. تحذيرات
        if ($total_amount > 50000) {
            $result['warnings'][] = 'مبلغ كبير - تأكد من صحة البيانات';
        }
        
        if ($this->isWeekend()) {
            $result['warnings'][] = 'ترحيل في عطلة نهاية الأسبوع';
        }
        
        $result['allowed'] = true;
        return $result;
    }
    
    /**
     * التحقق من الأدوار
     */
    private function hasRole($roles) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        $user_groups = $this->user->getGroups();
        foreach ($user_groups as $group) {
            if (in_array($group['name'], $roles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * التحقق من الصلاحيات الخاصة
     */
    private function hasSpecialPermission($permission) {
        return $this->user->hasPermission('special', $permission);
    }
    
    /**
     * التحقق من الفترة المغلقة
     */
    private function isInClosedPeriod($date) {
        $this->load->model('accounts/fiscal_period');
        return $this->model_accounts_fiscal_period->isPeriodClosed($date);
    }
    
    /**
     * التحقق من صلاحية الوصول للحساب
     */
    private function canAccessAccount($account_id) {
        $this->load->model('accounts/chartaccount');
        $account = $this->model_accounts_chartaccount->getAccount($account_id);
        
        // التحقق من قيود الوصول للحساب
        if (isset($account['access_restriction']) && $account['access_restriction']) {
            return $this->user->hasPermission('access', 'account_' . $account_id);
        }
        
        return true;
    }
    
    /**
     * الحصول على الحد الأقصى للمبلغ
     */
    private function getUserMaxJournalAmount() {
        $user_id = $this->user->getId();
        $this->load->model('user/user');
        $user_limits = $this->model_user_user->getUserLimits($user_id);
        
        return $user_limits['max_journal_amount'] ?? 10000; // افتراضي 10,000
    }
    
    /**
     * الحصول على حد الترحيل
     */
    private function getUserMaxPostingAmount() {
        $user_id = $this->user->getId();
        $this->load->model('user/user');
        $user_limits = $this->model_user_user->getUserLimits($user_id);
        
        return $user_limits['max_posting_amount'] ?? 50000; // افتراضي 50,000
    }
    
    /**
     * التحقق من ساعات العمل
     */
    private function isInBusinessHours() {
        $current_hour = date('H');
        $current_day = date('N'); // 1 = Monday, 7 = Sunday
        
        // ساعات العمل: 8 صباحاً - 6 مساءً، الأحد - الخميس
        return ($current_day >= 1 && $current_day <= 5) && 
               ($current_hour >= 8 && $current_hour <= 18);
    }
    
    /**
     * التحقق من عطلة نهاية الأسبوع
     */
    private function isWeekend() {
        $current_day = date('N');
        return $current_day >= 6; // السبت والأحد
    }
    
    /**
     * التحقق من موافقة المبلغ
     */
    private function hasApprovalForAmount($amount) {
        // التحقق من وجود موافقة مسبقة للمبلغ
        $this->load->model('accounts/approval');
        return $this->model_accounts_approval->hasAmountApproval($this->user->getId(), $amount);
    }
}
