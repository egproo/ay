<?php
/**
 * نموذج سجل المراجعة المتقدم
 * مستوى احترافي مثل الأنظمة العالمية
 */
class ModelAccountsAuditTrail extends Model {
    
    /**
     * تسجيل عملية في سجل المراجعة
     */
    public function logAction($data) {
        $log_data = [
            'user_id' => $this->user->getId(),
            'user_name' => $this->user->getUserName(),
            'action_type' => $data['action_type'], // create, update, delete, post, unpost
            'table_name' => $data['table_name'],
            'record_id' => $data['record_id'],
            'old_values' => isset($data['old_values']) ? json_encode($data['old_values']) : null,
            'new_values' => isset($data['new_values']) ? json_encode($data['new_values']) : null,
            'ip_address' => $this->request->server['REMOTE_ADDR'],
            'user_agent' => $this->request->server['HTTP_USER_AGENT'],
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s'),
            'description' => $data['description'] ?? '',
            'risk_level' => $this->calculateRiskLevel($data),
            'business_date' => $data['business_date'] ?? date('Y-m-d'),
            'module' => $data['module'] ?? 'accounts',
            'transaction_amount' => $data['transaction_amount'] ?? 0,
            'approval_required' => $data['approval_required'] ?? 0,
            'approved_by' => $data['approved_by'] ?? null,
            'approval_date' => $data['approval_date'] ?? null
        ];
        
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "audit_trail SET
            user_id = '" . (int)$log_data['user_id'] . "',
            user_name = '" . $this->db->escape($log_data['user_name']) . "',
            action_type = '" . $this->db->escape($log_data['action_type']) . "',
            table_name = '" . $this->db->escape($log_data['table_name']) . "',
            record_id = '" . (int)$log_data['record_id'] . "',
            old_values = '" . $this->db->escape($log_data['old_values']) . "',
            new_values = '" . $this->db->escape($log_data['new_values']) . "',
            ip_address = '" . $this->db->escape($log_data['ip_address']) . "',
            user_agent = '" . $this->db->escape($log_data['user_agent']) . "',
            session_id = '" . $this->db->escape($log_data['session_id']) . "',
            timestamp = '" . $this->db->escape($log_data['timestamp']) . "',
            description = '" . $this->db->escape($log_data['description']) . "',
            risk_level = '" . $this->db->escape($log_data['risk_level']) . "',
            business_date = '" . $this->db->escape($log_data['business_date']) . "',
            module = '" . $this->db->escape($log_data['module']) . "',
            transaction_amount = '" . (float)$log_data['transaction_amount'] . "',
            approval_required = '" . (int)$log_data['approval_required'] . "',
            approved_by = " . ($log_data['approved_by'] ? "'" . (int)$log_data['approved_by'] . "'" : "NULL") . ",
            approval_date = " . ($log_data['approval_date'] ? "'" . $this->db->escape($log_data['approval_date']) . "'" : "NULL") . "
        ");
        
        $audit_id = $this->db->getLastId();
        
        // إرسال تنبيهات للعمليات عالية المخاطر
        if ($log_data['risk_level'] === 'HIGH') {
            $this->sendHighRiskAlert($audit_id, $log_data);
        }
        
        return $audit_id;
    }
    
    /**
     * تسجيل تغيير القيد المحاسبي
     */
    public function logJournalChange($journal_id, $action_type, $old_data = null, $new_data = null) {
        $description = $this->getJournalActionDescription($action_type, $journal_id);
        
        $log_data = [
            'action_type' => $action_type,
            'table_name' => 'journal_entry',
            'record_id' => $journal_id,
            'old_values' => $old_data,
            'new_values' => $new_data,
            'description' => $description,
            'module' => 'accounts',
            'business_date' => $new_data['journal_date'] ?? ($old_data['journal_date'] ?? date('Y-m-d')),
            'transaction_amount' => $new_data['total_debit'] ?? ($old_data['total_debit'] ?? 0)
        ];
        
        // تحديد إذا كانت العملية تحتاج موافقة
        if ($action_type === 'update' && $old_data['status'] === 'posted') {
            $log_data['approval_required'] = 1;
        }
        
        return $this->logAction($log_data);
    }
    
    /**
     * حساب مستوى المخاطر
     */
    private function calculateRiskLevel($data) {
        $risk_score = 0;
        
        // العمليات عالية المخاطر
        $high_risk_actions = ['delete', 'unpost', 'update_posted'];
        if (in_array($data['action_type'], $high_risk_actions)) {
            $risk_score += 30;
        }
        
        // المبالغ الكبيرة
        $amount = $data['transaction_amount'] ?? 0;
        if ($amount > 100000) {
            $risk_score += 25;
        } elseif ($amount > 50000) {
            $risk_score += 15;
        } elseif ($amount > 10000) {
            $risk_score += 10;
        }
        
        // العمليات خارج ساعات العمل
        $current_hour = date('H');
        if ($current_hour < 8 || $current_hour > 18) {
            $risk_score += 10;
        }
        
        // العمليات في عطلة نهاية الأسبوع
        $current_day = date('N');
        if ($current_day >= 6) {
            $risk_score += 15;
        }
        
        // المستخدمين الجدد
        $user_creation_date = $this->getUserCreationDate($this->user->getId());
        $days_since_creation = (time() - strtotime($user_creation_date)) / (24 * 60 * 60);
        if ($days_since_creation < 30) {
            $risk_score += 10;
        }
        
        // تحديد مستوى المخاطر
        if ($risk_score >= 50) {
            return 'HIGH';
        } elseif ($risk_score >= 25) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }
    
    /**
     * الحصول على وصف العملية
     */
    private function getJournalActionDescription($action_type, $journal_id) {
        $descriptions = [
            'create' => "إنشاء قيد محاسبي جديد رقم {$journal_id}",
            'update' => "تعديل القيد المحاسبي رقم {$journal_id}",
            'delete' => "حذف القيد المحاسبي رقم {$journal_id}",
            'post' => "ترحيل القيد المحاسبي رقم {$journal_id}",
            'unpost' => "إلغاء ترحيل القيد المحاسبي رقم {$journal_id}",
            'approve' => "اعتماد القيد المحاسبي رقم {$journal_id}",
            'reject' => "رفض القيد المحاسبي رقم {$journal_id}"
        ];
        
        return $descriptions[$action_type] ?? "عملية غير معروفة على القيد {$journal_id}";
    }
    
    /**
     * إرسال تنبيه للعمليات عالية المخاطر
     */
    private function sendHighRiskAlert($audit_id, $log_data) {
        $this->load->model('notification/notification');
        
        $message = "تنبيه أمني: عملية عالية المخاطر\n";
        $message .= "المستخدم: {$log_data['user_name']}\n";
        $message .= "العملية: {$log_data['action_type']}\n";
        $message .= "الوصف: {$log_data['description']}\n";
        $message .= "المبلغ: {$log_data['transaction_amount']}\n";
        $message .= "الوقت: {$log_data['timestamp']}\n";
        $message .= "عنوان IP: {$log_data['ip_address']}";
        
        // إرسال للمدير المالي ومدير النظام
        $recipients = $this->getSecurityNotificationRecipients();
        
        foreach ($recipients as $recipient) {
            $this->model_notification_notification->send([
                'user_id' => $recipient['user_id'],
                'title' => 'تنبيه أمني - عملية عالية المخاطر',
                'message' => $message,
                'type' => 'security_alert',
                'priority' => 'high',
                'module' => 'audit_trail',
                'reference_id' => $audit_id
            ]);
        }
    }
    
    /**
     * الحصول على مستقبلي التنبيهات الأمنية
     */
    private function getSecurityNotificationRecipients() {
        $query = $this->db->query("
            SELECT DISTINCT u.user_id, u.username, u.email
            FROM " . DB_PREFIX . "user u
            JOIN " . DB_PREFIX . "user_group ug ON u.user_group_id = ug.user_group_id
            WHERE ug.name IN ('financial_manager', 'system_admin', 'cfo', 'security_officer')
            AND u.status = 1
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على تاريخ إنشاء المستخدم
     */
    private function getUserCreationDate($user_id) {
        $query = $this->db->query("
            SELECT date_added 
            FROM " . DB_PREFIX . "user 
            WHERE user_id = '" . (int)$user_id . "'
        ");
        
        return $query->row['date_added'] ?? date('Y-m-d H:i:s');
    }
    
    /**
     * الحصول على سجل المراجعة للقيد
     */
    public function getJournalAuditTrail($journal_id) {
        $query = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "audit_trail
            WHERE table_name = 'journal_entry' 
            AND record_id = '" . (int)$journal_id . "'
            ORDER BY timestamp DESC
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على إحصائيات المراجعة
     */
    public function getAuditStatistics($date_start = null, $date_end = null) {
        $where = "";
        if ($date_start && $date_end) {
            $where = "WHERE DATE(timestamp) BETWEEN '" . $this->db->escape($date_start) . "' 
                     AND '" . $this->db->escape($date_end) . "'";
        }
        
        $query = $this->db->query("
            SELECT 
                action_type,
                risk_level,
                COUNT(*) as count,
                SUM(transaction_amount) as total_amount
            FROM " . DB_PREFIX . "audit_trail
            {$where}
            GROUP BY action_type, risk_level
            ORDER BY count DESC
        ");
        
        return $query->rows;
    }
    
    /**
     * البحث في سجل المراجعة
     */
    public function searchAuditTrail($filters = []) {
        $where_conditions = [];
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = "user_id = '" . (int)$filters['user_id'] . "'";
        }
        
        if (!empty($filters['action_type'])) {
            $where_conditions[] = "action_type = '" . $this->db->escape($filters['action_type']) . "'";
        }
        
        if (!empty($filters['table_name'])) {
            $where_conditions[] = "table_name = '" . $this->db->escape($filters['table_name']) . "'";
        }
        
        if (!empty($filters['risk_level'])) {
            $where_conditions[] = "risk_level = '" . $this->db->escape($filters['risk_level']) . "'";
        }
        
        if (!empty($filters['date_start'])) {
            $where_conditions[] = "DATE(timestamp) >= '" . $this->db->escape($filters['date_start']) . "'";
        }
        
        if (!empty($filters['date_end'])) {
            $where_conditions[] = "DATE(timestamp) <= '" . $this->db->escape($filters['date_end']) . "'";
        }
        
        if (!empty($filters['ip_address'])) {
            $where_conditions[] = "ip_address = '" . $this->db->escape($filters['ip_address']) . "'";
        }
        
        if (!empty($filters['amount_min'])) {
            $where_conditions[] = "transaction_amount >= '" . (float)$filters['amount_min'] . "'";
        }
        
        if (!empty($filters['amount_max'])) {
            $where_conditions[] = "transaction_amount <= '" . (float)$filters['amount_max'] . "'";
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $query = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "audit_trail
            {$where_clause}
            ORDER BY timestamp DESC
            LIMIT " . (int)($filters['limit'] ?? 100)
        );
        
        return $query->rows;
    }
    
    /**
     * تصدير سجل المراجعة
     */
    public function exportAuditTrail($filters = [], $format = 'csv') {
        $data = $this->searchAuditTrail($filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($data);
            case 'excel':
                return $this->exportToExcel($data);
            case 'pdf':
                return $this->exportToPdf($data);
            default:
                return $data;
        }
    }
    
    /**
     * تنظيف سجل المراجعة القديم
     */
    public function cleanupOldAuditLogs($days_to_keep = 2555) { // 7 سنوات افتراضي
        $cutoff_date = date('Y-m-d', strtotime("-{$days_to_keep} days"));
        
        // نسخ احتياطي قبل الحذف
        $this->createAuditBackup($cutoff_date);
        
        // حذف السجلات القديمة
        $this->db->query("
            DELETE FROM " . DB_PREFIX . "audit_trail
            WHERE DATE(timestamp) < '" . $this->db->escape($cutoff_date) . "'
            AND risk_level = 'LOW'
        ");
        
        return $this->db->countAffected();
    }
    
    /**
     * إنشاء نسخة احتياطية من سجل المراجعة
     */
    private function createAuditBackup($cutoff_date) {
        $backup_table = DB_PREFIX . "audit_trail_archive_" . date('Y_m_d');
        
        $this->db->query("
            CREATE TABLE {$backup_table} AS
            SELECT * FROM " . DB_PREFIX . "audit_trail
            WHERE DATE(timestamp) < '" . $this->db->escape($cutoff_date) . "'
        ");
    }
}
