<?php
/**
 * نموذج مراجعة واعتماد القيود المحاسبية
 * يدعم دورة عمل الاعتماد والمراجعة
 */
class ModelAccountsJournalReview extends Model {
    
    /**
     * الحصول على القيود المحاسبية للمراجعة
     */
    public function getJournals($data = array()) {
        $sql = "SELECT je.*, u.username as created_by_name,
                       COALESCE(SUM(jel.debit_amount), 0) as total_debit,
                       COALESCE(SUM(jel.credit_amount), 0) as total_credit
                FROM " . DB_PREFIX . "journal_entry je
                LEFT JOIN " . DB_PREFIX . "journal_entry_line jel ON je.journal_id = jel.journal_id
                LEFT JOIN " . DB_PREFIX . "user u ON je.created_by = u.user_id
                WHERE 1";
        
        if (!empty($data['filter_journal_number'])) {
            $sql .= " AND je.journal_number LIKE '%" . $this->db->escape($data['filter_journal_number']) . "%'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND je.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(je.journal_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(je.journal_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        $sql .= " GROUP BY je.journal_id";
        
        $sort_data = array(
            'je.journal_number',
            'je.journal_date',
            'je.description',
            'je.status',
            'je.date_added'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY je.journal_date";
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
     * الحصول على إجمالي عدد القيود للمراجعة
     */
    public function getTotalJournals($data = array()) {
        $sql = "SELECT COUNT(DISTINCT je.journal_id) AS total
                FROM " . DB_PREFIX . "journal_entry je
                WHERE 1";
        
        if (!empty($data['filter_journal_number'])) {
            $sql .= " AND je.journal_number LIKE '%" . $this->db->escape($data['filter_journal_number']) . "%'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND je.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(je.journal_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(je.journal_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على قيد محاسبي واحد للمراجعة
     */
    public function getJournal($journal_id) {
        $query = $this->db->query("SELECT je.*, u.username as created_by_name,
                                          au.username as approved_by_name,
                                          ru.username as rejected_by_name
                                  FROM " . DB_PREFIX . "journal_entry je
                                  LEFT JOIN " . DB_PREFIX . "user u ON je.created_by = u.user_id
                                  LEFT JOIN " . DB_PREFIX . "user au ON je.approved_by = au.user_id
                                  LEFT JOIN " . DB_PREFIX . "user ru ON je.rejected_by = ru.user_id
                                  WHERE je.journal_id = '" . (int)$journal_id . "'");
        
        return $query->row;
    }
    
    /**
     * الحصول على تفاصيل القيد المحاسبي
     */
    public function getJournalLines($journal_id) {
        $query = $this->db->query("SELECT jel.*, a.account_code, ad.name as account_name
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  LEFT JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                  LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  WHERE jel.journal_id = '" . (int)$journal_id . "'
                                  ORDER BY jel.line_id");
        
        return $query->rows;
    }
    
    /**
     * اعتماد قيد محاسبي
     */
    public function approveJournal($journal_id, $approval_notes = '') {
        // التحقق من حالة القيد
        $journal = $this->getJournal($journal_id);
        if (!$journal || $journal['status'] != 'pending') {
            return false;
        }
        
        // التحقق من توازن القيد
        if (!$this->validateJournalBalance($journal_id)) {
            return false;
        }
        
        // تحديث حالة القيد إلى معتمد
        $this->db->query("UPDATE " . DB_PREFIX . "journal_entry SET 
                         status = 'approved',
                         approved_by = '" . (int)$this->user->getId() . "',
                         approved_date = NOW(),
                         approval_notes = '" . $this->db->escape($approval_notes) . "'
                         WHERE journal_id = '" . (int)$journal_id . "'");
        
        // تحديث أرصدة الحسابات
        $this->updateAccountBalances($journal_id);
        
        // تسجيل في سجل المراجعة
        $this->addReviewLog($journal_id, 'approved', $approval_notes);
        
        return true;
    }
    
    /**
     * رفض قيد محاسبي
     */
    public function rejectJournal($journal_id, $rejection_reason) {
        // التحقق من حالة القيد
        $journal = $this->getJournal($journal_id);
        if (!$journal || $journal['status'] != 'pending') {
            return false;
        }
        
        // تحديث حالة القيد إلى مرفوض
        $this->db->query("UPDATE " . DB_PREFIX . "journal_entry SET 
                         status = 'rejected',
                         rejected_by = '" . (int)$this->user->getId() . "',
                         rejected_date = NOW(),
                         rejection_reason = '" . $this->db->escape($rejection_reason) . "'
                         WHERE journal_id = '" . (int)$journal_id . "'");
        
        // تسجيل في سجل المراجعة
        $this->addReviewLog($journal_id, 'rejected', $rejection_reason);
        
        return true;
    }
    
    /**
     * إعادة إرسال قيد للمراجعة
     */
    public function resubmitJournal($journal_id) {
        // التحقق من حالة القيد
        $journal = $this->getJournal($journal_id);
        if (!$journal || $journal['status'] != 'rejected') {
            return false;
        }
        
        // تحديث حالة القيد إلى في انتظار المراجعة
        $this->db->query("UPDATE " . DB_PREFIX . "journal_entry SET 
                         status = 'pending',
                         rejected_by = NULL,
                         rejected_date = NULL,
                         rejection_reason = NULL,
                         date_modified = NOW()
                         WHERE journal_id = '" . (int)$journal_id . "'");
        
        // تسجيل في سجل المراجعة
        $this->addReviewLog($journal_id, 'resubmitted', 'إعادة إرسال للمراجعة');
        
        return true;
    }
    
    /**
     * التحقق من توازن القيد المحاسبي
     */
    private function validateJournalBalance($journal_id) {
        $query = $this->db->query("SELECT 
                                    SUM(debit_amount) as total_debit,
                                    SUM(credit_amount) as total_credit
                                  FROM " . DB_PREFIX . "journal_entry_line 
                                  WHERE journal_id = '" . (int)$journal_id . "'");
        
        $total_debit = (float)$query->row['total_debit'];
        $total_credit = (float)$query->row['total_credit'];
        
        return abs($total_debit - $total_credit) < 0.01;
    }
    
    /**
     * تحديث أرصدة الحسابات بعد الاعتماد
     */
    private function updateAccountBalances($journal_id) {
        $lines = $this->getJournalLines($journal_id);
        
        foreach ($lines as $line) {
            $balance_change = (float)$line['debit_amount'] - (float)$line['credit_amount'];
            
            $this->db->query("UPDATE " . DB_PREFIX . "accounts 
                             SET current_balance = current_balance + '" . (float)$balance_change . "'
                             WHERE account_id = '" . (int)$line['account_id'] . "'");
        }
    }
    
    /**
     * إضافة سجل مراجعة
     */
    private function addReviewLog($journal_id, $action, $notes) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "journal_review_log SET 
                         journal_id = '" . (int)$journal_id . "',
                         action = '" . $this->db->escape($action) . "',
                         notes = '" . $this->db->escape($notes) . "',
                         user_id = '" . (int)$this->user->getId() . "',
                         date_added = NOW()");
    }
    
    /**
     * الحصول على سجل مراجعة القيد
     */
    public function getReviewLog($journal_id) {
        $query = $this->db->query("SELECT jrl.*, u.username
                                  FROM " . DB_PREFIX . "journal_review_log jrl
                                  LEFT JOIN " . DB_PREFIX . "user u ON jrl.user_id = u.user_id
                                  WHERE jrl.journal_id = '" . (int)$journal_id . "'
                                  ORDER BY jrl.date_added DESC");
        
        return $query->rows;
    }
    
    /**
     * الحصول على إحصائيات المراجعة
     */
    public function getReviewStatistics($date_start = null, $date_end = null) {
        $sql = "SELECT 
                    COUNT(*) as total_journals,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                FROM " . DB_PREFIX . "journal_entry
                WHERE 1";
        
        if ($date_start) {
            $sql .= " AND DATE(journal_date) >= '" . $this->db->escape($date_start) . "'";
        }
        
        if ($date_end) {
            $sql .= " AND DATE(journal_date) <= '" . $this->db->escape($date_end) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    /**
     * اعتماد متعدد للقيود
     */
    public function bulkApprove($journal_ids, $approval_notes = '') {
        $approved_count = 0;
        
        foreach ($journal_ids as $journal_id) {
            if ($this->approveJournal($journal_id, $approval_notes)) {
                $approved_count++;
            }
        }
        
        return $approved_count;
    }
    
    /**
     * رفض متعدد للقيود
     */
    public function bulkReject($journal_ids, $rejection_reason) {
        $rejected_count = 0;
        
        foreach ($journal_ids as $journal_id) {
            if ($this->rejectJournal($journal_id, $rejection_reason)) {
                $rejected_count++;
            }
        }
        
        return $rejected_count;
    }
}
?>
