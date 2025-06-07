<?php
/**
 * نموذج نظام حماية القيود المحاسبية المتقدم
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ModelAccountsJournalSecurityAdvanced extends Model {

    /**
     * الحصول على حالة القيد المحاسبي
     */
    public function getJournalStatus($journal_id) {
        $query = $this->db->query("
            SELECT 
                journal_id,
                is_reviewed,
                is_posted,
                is_secured,
                reviewed_by,
                reviewed_date,
                posted_by,
                posted_date,
                secured_by,
                secured_date,
                security_level,
                modification_count,
                last_modified_by,
                last_modified_date
            FROM " . DB_PREFIX . "journal_entries 
            WHERE journal_id = '" . (int)$journal_id . "'
        ");

        if ($query->num_rows) {
            return $query->row;
        }

        return array(
            'is_reviewed' => false,
            'is_posted' => false,
            'is_secured' => false,
            'security_level' => 'none'
        );
    }

    /**
     * تأمين القيد بعد المراجعة
     */
    public function secureJournalEntry($journal_id) {
        // التحقق من حالة القيد
        $status = $this->getJournalStatus($journal_id);
        
        if (!$status['is_reviewed']) {
            throw new Exception('لا يمكن تأمين القيد قبل مراجعته');
        }

        // تأمين القيد
        $this->db->query("
            UPDATE " . DB_PREFIX . "journal_entries SET
            is_secured = 1,
            secured_by = '" . (int)$this->user->getId() . "',
            secured_date = NOW(),
            security_level = 'high',
            modification_allowed = 0
            WHERE journal_id = '" . (int)$journal_id . "'
        ");

        // إنشاء سجل الحماية
        $this->createSecurityRecord($journal_id, 'secured', 'تأمين القيد بعد المراجعة');

        return $this->db->countAffected() > 0;
    }

    /**
     * إلغاء تأمين القيد - صلاحيات خاصة
     */
    public function unsecureJournalEntry($journal_id, $reason) {
        // التحقق من الصلاحية
        $this->load->model('user/user_permission');
        $can_unsecure = $this->model_user_user_permission->hasAdvancedPermission($this->user->getId(), 'journal_unsecure_super_admin');
        
        if (!$can_unsecure) {
            throw new Exception('ليس لديك صلاحية إلغاء تأمين القيود');
        }

        // إلغاء التأمين
        $this->db->query("
            UPDATE " . DB_PREFIX . "journal_entries SET
            is_secured = 0,
            unsecured_by = '" . (int)$this->user->getId() . "',
            unsecured_date = NOW(),
            unsecure_reason = '" . $this->db->escape($reason) . "',
            security_level = 'medium',
            modification_allowed = 1
            WHERE journal_id = '" . (int)$journal_id . "'
        ");

        // إنشاء سجل إلغاء الحماية
        $this->createSecurityRecord($journal_id, 'unsecured', 'إلغاء تأمين القيد - السبب: ' . $reason);

        return $this->db->countAffected() > 0;
    }

    /**
     * التحقق من إمكانية التعديل
     */
    public function canModifyEntry($journal_id) {
        $status = $this->getJournalStatus($journal_id);
        
        // لا يمكن التعديل إذا كان القيد مراجع أو مرحل أو مؤمن
        if ($status['is_reviewed'] || $status['is_posted'] || $status['is_secured']) {
            return array(
                'allowed' => false,
                'reason' => 'القيد محمي - تم مراجعته أو ترحيله أو تأمينه'
            );
        }

        return array(
            'allowed' => true,
            'reason' => 'يمكن التعديل'
        );
    }

    /**
     * التحقق من إمكانية الحذف
     */
    public function canDeleteEntry($journal_id) {
        // التحقق من الصلاحية
        $this->load->model('user/user_permission');
        $can_delete = $this->model_user_user_permission->hasAdvancedPermission($this->user->getId(), 'journal_delete_advanced');
        
        if (!$can_delete) {
            return array(
                'allowed' => false,
                'reason' => 'ليس لديك صلاحية حذف القيود'
            );
        }

        $status = $this->getJournalStatus($journal_id);
        
        // لا يمكن الحذف إذا كان القيد مراجع أو مرحل
        if ($status['is_reviewed'] || $status['is_posted']) {
            return array(
                'allowed' => false,
                'reason' => 'لا يمكن حذف القيد بعد المراجعة أو الترحيل'
            );
        }

        // التحقق من وجود قيود مرتبطة
        $related_entries = $this->getRelatedEntries($journal_id);
        if (!empty($related_entries)) {
            return array(
                'allowed' => false,
                'reason' => 'يوجد قيود مرتبطة بهذا القيد',
                'related_entries' => $related_entries
            );
        }

        return array(
            'allowed' => true,
            'reason' => 'يمكن الحذف'
        );
    }

    /**
     * الحصول على القيود المرتبطة
     */
    public function getRelatedEntries($journal_id) {
        $query = $this->db->query("
            SELECT 
                je.journal_id,
                je.journal_number,
                je.journal_date,
                je.description,
                je.total_amount
            FROM " . DB_PREFIX . "journal_entries je
            WHERE je.parent_journal_id = '" . (int)$journal_id . "'
            OR je.reference_journal_id = '" . (int)$journal_id . "'
            OR je.journal_id IN (
                SELECT reference_journal_id 
                FROM " . DB_PREFIX . "journal_entries 
                WHERE journal_id = '" . (int)$journal_id . "'
            )
        ");

        return $query->rows;
    }

    /**
     * إنشاء سجل الحماية
     */
    private function createSecurityRecord($journal_id, $action, $description) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "journal_security_log SET
            journal_id = '" . (int)$journal_id . "',
            action_type = '" . $this->db->escape($action) . "',
            description = '" . $this->db->escape($description) . "',
            user_id = '" . (int)$this->user->getId() . "',
            user_name = '" . $this->db->escape($this->user->getUserName()) . "',
            ip_address = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "',
            user_agent = '" . $this->db->escape($this->request->server['HTTP_USER_AGENT']) . "',
            created_date = NOW()
        ");
    }

    /**
     * تقرير الحماية
     */
    public function getSecurityReport($filter_data) {
        $sql = "
            SELECT 
                je.journal_id,
                je.journal_number,
                je.journal_date,
                je.description,
                je.total_amount,
                je.is_reviewed,
                je.is_posted,
                je.is_secured,
                je.security_level,
                je.modification_count,
                CASE 
                    WHEN je.is_secured = 1 THEN 'مؤمن'
                    WHEN je.is_posted = 1 THEN 'مرحل'
                    WHEN je.is_reviewed = 1 THEN 'مراجع'
                    ELSE 'غير محمي'
                END as protection_status,
                u1.firstname as reviewed_by_name,
                u2.firstname as posted_by_name,
                u3.firstname as secured_by_name
            FROM " . DB_PREFIX . "journal_entries je
            LEFT JOIN " . DB_PREFIX . "user u1 ON je.reviewed_by = u1.user_id
            LEFT JOIN " . DB_PREFIX . "user u2 ON je.posted_by = u2.user_id
            LEFT JOIN " . DB_PREFIX . "user u3 ON je.secured_by = u3.user_id
            WHERE je.journal_date BETWEEN '" . $this->db->escape($filter_data['start_date']) . "' 
            AND '" . $this->db->escape($filter_data['end_date']) . "'
        ";

        if ($filter_data['security_level'] != 'all') {
            switch ($filter_data['security_level']) {
                case 'secured':
                    $sql .= " AND je.is_secured = 1";
                    break;
                case 'posted':
                    $sql .= " AND je.is_posted = 1 AND je.is_secured = 0";
                    break;
                case 'reviewed':
                    $sql .= " AND je.is_reviewed = 1 AND je.is_posted = 0";
                    break;
                case 'unprotected':
                    $sql .= " AND je.is_reviewed = 0 AND je.is_posted = 0 AND je.is_secured = 0";
                    break;
            }
        }

        $sql .= " ORDER BY je.journal_date DESC, je.journal_id DESC";

        $query = $this->db->query($sql);

        $report = array(
            'entries' => $query->rows,
            'summary' => $this->getSecuritySummary($filter_data)
        );

        return $report;
    }

    /**
     * ملخص الحماية
     */
    private function getSecuritySummary($filter_data) {
        $query = $this->db->query("
            SELECT 
                COUNT(*) as total_entries,
                SUM(CASE WHEN is_secured = 1 THEN 1 ELSE 0 END) as secured_entries,
                SUM(CASE WHEN is_posted = 1 AND is_secured = 0 THEN 1 ELSE 0 END) as posted_entries,
                SUM(CASE WHEN is_reviewed = 1 AND is_posted = 0 THEN 1 ELSE 0 END) as reviewed_entries,
                SUM(CASE WHEN is_reviewed = 0 AND is_posted = 0 AND is_secured = 0 THEN 1 ELSE 0 END) as unprotected_entries,
                SUM(modification_count) as total_modifications
            FROM " . DB_PREFIX . "journal_entries
            WHERE journal_date BETWEEN '" . $this->db->escape($filter_data['start_date']) . "' 
            AND '" . $this->db->escape($filter_data['end_date']) . "'
        ");

        $summary = $query->row;
        
        // حساب النسب المئوية
        if ($summary['total_entries'] > 0) {
            $summary['secured_percentage'] = round(($summary['secured_entries'] / $summary['total_entries']) * 100, 2);
            $summary['posted_percentage'] = round(($summary['posted_entries'] / $summary['total_entries']) * 100, 2);
            $summary['reviewed_percentage'] = round(($summary['reviewed_entries'] / $summary['total_entries']) * 100, 2);
            $summary['unprotected_percentage'] = round(($summary['unprotected_entries'] / $summary['total_entries']) * 100, 2);
        } else {
            $summary['secured_percentage'] = 0;
            $summary['posted_percentage'] = 0;
            $summary['reviewed_percentage'] = 0;
            $summary['unprotected_percentage'] = 0;
        }

        return $summary;
    }

    /**
     * تسجيل محاولة التعديل
     */
    public function logModificationAttempt($journal_id, $action, $result) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "journal_modification_log SET
            journal_id = '" . (int)$journal_id . "',
            action_attempted = '" . $this->db->escape($action) . "',
            result = '" . $this->db->escape($result) . "',
            user_id = '" . (int)$this->user->getId() . "',
            user_name = '" . $this->db->escape($this->user->getUserName()) . "',
            ip_address = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "',
            user_agent = '" . $this->db->escape($this->request->server['HTTP_USER_AGENT']) . "',
            attempt_date = NOW()
        ");
    }

    /**
     * تحديث عداد التعديلات
     */
    public function incrementModificationCount($journal_id) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "journal_entries SET
            modification_count = modification_count + 1,
            last_modified_by = '" . (int)$this->user->getId() . "',
            last_modified_date = NOW()
            WHERE journal_id = '" . (int)$journal_id . "'
        ");
    }

    /**
     * التحقق من تكامل النظام
     */
    public function checkSystemIntegrity() {
        $integrity_checks = array();

        // التحقق من القيود المرحلة بدون مراجعة
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "journal_entries
            WHERE is_posted = 1 AND is_reviewed = 0
        ");
        $integrity_checks['posted_without_review'] = $query->row['count'];

        // التحقق من القيود المؤمنة بدون ترحيل
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "journal_entries
            WHERE is_secured = 1 AND is_posted = 0
        ");
        $integrity_checks['secured_without_posting'] = $query->row['count'];

        // التحقق من القيود المعدلة بعد المراجعة
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "journal_entries
            WHERE is_reviewed = 1 AND last_modified_date > reviewed_date
        ");
        $integrity_checks['modified_after_review'] = $query->row['count'];

        return $integrity_checks;
    }
}
