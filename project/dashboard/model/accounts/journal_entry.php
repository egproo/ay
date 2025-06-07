<?php
/**
 * نموذج القيود المحاسبية المتقدم والمتكامل
 * يدعم القيود التلقائية والمراجعة والاعتماد والتكامل الكامل مع النظام
 */
class ModelAccountsJournalEntry extends Model {

    /**
     * إضافة قيد محاسبي جديد مع التحقق المتقدم
     */
    public function addJournalEntry($data) {
        // التحقق المتقدم من صحة البيانات
        $validation = $this->validateJournalEntryAdvanced($data);
        if (!$validation['valid']) {
            throw new Exception($validation['error']);
        }

        // تحديد رقم القيد التلقائي إذا لم يتم تحديده
        if (empty($data['journal_number'])) {
            $data['journal_number'] = $this->generateJournalNumber($data['journal_date']);
        }

        $this->db->query("START TRANSACTION");

        try {
            // إدراج القيد الرئيسي
            $this->db->query("INSERT INTO " . DB_PREFIX . "journal_entry SET
                journal_number = '" . $this->db->escape($data['journal_number']) . "',
                journal_date = '" . $this->db->escape($data['journal_date']) . "',
                description = '" . $this->db->escape($data['description']) . "',
                reference_type = '" . $this->db->escape($data['reference_type'] ?? '') . "',
                reference_id = '" . (int)($data['reference_id'] ?? 0) . "',
                reference_number = '" . $this->db->escape($data['reference_number'] ?? '') . "',
                status = '" . $this->db->escape($data['status'] ?? 'draft') . "',
                total_debit = '" . (float)$this->calculateTotalDebit($data['lines']) . "',
                total_credit = '" . (float)$this->calculateTotalCredit($data['lines']) . "',
                currency_code = '" . $this->db->escape($data['currency_code'] ?? $this->config->get('config_currency')) . "',
                exchange_rate = '" . (float)($data['exchange_rate'] ?? 1) . "',
                cost_center_id = '" . (int)($data['cost_center_id'] ?? 0) . "',
                project_id = '" . (int)($data['project_id'] ?? 0) . "',
                department_id = '" . (int)($data['department_id'] ?? 0) . "',
                auto_generated = '" . (int)($data['auto_generated'] ?? 0) . "',
                requires_approval = '" . (int)($data['requires_approval'] ?? 0) . "',
                approved_by = '" . (int)($data['approved_by'] ?? 0) . "',
                approval_date = " . (!empty($data['approval_date']) ? "'" . $this->db->escape($data['approval_date']) . "'" : "NULL") . ",
                created_by = '" . (int)$this->user->getId() . "',
                date_added = NOW(),
                date_modified = NOW()");

            $journal_id = $this->db->getLastId();

            // إدراج بنود القيد
            $line_number = 1;
            foreach ($data['lines'] as $line) {
                $this->addJournalEntryLine($journal_id, $line, $line_number);
                $line_number++;
            }

            // تحديث أرصدة الحسابات إذا كان القيد مرحل
            if (($data['status'] ?? 'draft') == 'posted') {
                $this->updateAccountBalances($journal_id);
            }

            // إنشاء سجل تدقيق
            $this->createAuditLog('journal_entry_created', $journal_id, $data);

            // إرسال إشعارات إذا كان القيد يتطلب موافقة
            if (!empty($data['requires_approval'])) {
                $this->sendApprovalNotifications($journal_id);
            }

            $this->db->query("COMMIT");
            $this->cache->delete('journal_entry');

            return $journal_id;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * تعديل قيد محاسبي موجود
     */
    public function editJournalEntry($journal_id, $data) {
        // التحقق من إمكانية التعديل
        $journal = $this->getJournalEntry($journal_id);
        if (!$journal) {
            throw new Exception('القيد غير موجود');
        }

        if ($journal['status'] == 'posted' && !$this->user->hasPermission('modify', 'accounts/posted_entries')) {
            throw new Exception('لا يمكن تعديل قيد مرحل');
        }

        // التحقق المتقدم من صحة البيانات
        $validation = $this->validateJournalEntryAdvanced($data);
        if (!$validation['valid']) {
            throw new Exception($validation['error']);
        }

        $this->db->query("START TRANSACTION");

        try {
            // حفظ القيم القديمة للتدقيق
            $old_values = $journal;

            // تحديث القيد الرئيسي
            $this->db->query("UPDATE " . DB_PREFIX . "journal_entry SET
                journal_date = '" . $this->db->escape($data['journal_date']) . "',
                description = '" . $this->db->escape($data['description']) . "',
                reference_type = '" . $this->db->escape($data['reference_type'] ?? '') . "',
                reference_id = '" . (int)($data['reference_id'] ?? 0) . "',
                reference_number = '" . $this->db->escape($data['reference_number'] ?? '') . "',
                status = '" . $this->db->escape($data['status'] ?? 'draft') . "',
                total_debit = '" . (float)$this->calculateTotalDebit($data['lines']) . "',
                total_credit = '" . (float)$this->calculateTotalCredit($data['lines']) . "',
                currency_code = '" . $this->db->escape($data['currency_code'] ?? $this->config->get('config_currency')) . "',
                exchange_rate = '" . (float)($data['exchange_rate'] ?? 1) . "',
                cost_center_id = '" . (int)($data['cost_center_id'] ?? 0) . "',
                project_id = '" . (int)($data['project_id'] ?? 0) . "',
                department_id = '" . (int)($data['department_id'] ?? 0) . "',
                date_modified = NOW()
                WHERE journal_id = '" . (int)$journal_id . "'");

            // حذف البنود القديمة
            $this->db->query("DELETE FROM " . DB_PREFIX . "journal_entry_line WHERE journal_id = '" . (int)$journal_id . "'");

            // إدراج البنود الجديدة
            $line_number = 1;
            foreach ($data['lines'] as $line) {
                $this->addJournalEntryLine($journal_id, $line, $line_number);
                $line_number++;
            }

            // إعادة حساب أرصدة الحسابات
            if ($journal['status'] == 'posted') {
                $this->reverseAccountBalances($journal_id, $old_values);
            }

            if (($data['status'] ?? 'draft') == 'posted') {
                $this->updateAccountBalances($journal_id);
            }

            // إنشاء سجل تدقيق
            $this->createAuditLog('journal_entry_modified', $journal_id, $data, $old_values);

            $this->db->query("COMMIT");
            $this->cache->delete('journal_entry');

            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * حذف قيد محاسبي
     */
    public function deleteJournalEntry($journal_id) {
        $journal = $this->getJournalEntry($journal_id);
        if (!$journal) {
            throw new Exception('القيد غير موجود');
        }

        if ($journal['status'] == 'posted' && !$this->user->hasPermission('modify', 'accounts/posted_entries')) {
            throw new Exception('لا يمكن حذف قيد مرحل');
        }

        $this->db->query("START TRANSACTION");

        try {
            // عكس تأثير القيد على أرصدة الحسابات إذا كان مرحل
            if ($journal['status'] == 'posted') {
                $this->reverseAccountBalances($journal_id, $journal);
            }

            // حذف بنود القيد
            $this->db->query("DELETE FROM " . DB_PREFIX . "journal_entry_line WHERE journal_id = '" . (int)$journal_id . "'");

            // حذف القيد الرئيسي
            $this->db->query("DELETE FROM " . DB_PREFIX . "journal_entry WHERE journal_id = '" . (int)$journal_id . "'");

            // إنشاء سجل تدقيق
            $this->createAuditLog('journal_entry_deleted', $journal_id, array(), $journal);

            $this->db->query("COMMIT");
            $this->cache->delete('journal_entry');

            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * ترحيل قيد محاسبي
     */
    public function postJournalEntry($journal_id) {
        $journal = $this->getJournalEntry($journal_id);
        if (!$journal) {
            throw new Exception('القيد غير موجود');
        }

        if ($journal['status'] == 'posted') {
            throw new Exception('القيد مرحل مسبقاً');
        }

        // التحقق من صحة القيد قبل الترحيل
        $validation = $this->validateJournalForPosting($journal_id);
        if (!$validation['valid']) {
            throw new Exception($validation['error']);
        }

        $this->db->query("START TRANSACTION");

        try {
            // تحديث حالة القيد
            $this->db->query("UPDATE " . DB_PREFIX . "journal_entry SET
                status = 'posted',
                posted_by = '" . (int)$this->user->getId() . "',
                posting_date = NOW(),
                date_modified = NOW()
                WHERE journal_id = '" . (int)$journal_id . "'");

            // تحديث أرصدة الحسابات
            $this->updateAccountBalances($journal_id);

            // إنشاء سجل تدقيق
            $this->createAuditLog('journal_entry_posted', $journal_id, array('status' => 'posted'));

            $this->db->query("COMMIT");
            $this->cache->delete('journal_entry');

            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * إلغاء ترحيل قيد محاسبي
     */
    public function unpostJournalEntry($journal_id) {
        if (!$this->user->hasPermission('modify', 'accounts/posted_entries')) {
            throw new Exception('ليس لديك صلاحية لإلغاء ترحيل القيود');
        }

        $journal = $this->getJournalEntry($journal_id);
        if (!$journal) {
            throw new Exception('القيد غير موجود');
        }

        if ($journal['status'] != 'posted') {
            throw new Exception('القيد غير مرحل');
        }

        $this->db->query("START TRANSACTION");

        try {
            // عكس تأثير القيد على أرصدة الحسابات
            $this->reverseAccountBalances($journal_id, $journal);

            // تحديث حالة القيد
            $this->db->query("UPDATE " . DB_PREFIX . "journal_entry SET
                status = 'draft',
                posted_by = 0,
                posting_date = NULL,
                date_modified = NOW()
                WHERE journal_id = '" . (int)$journal_id . "'");

            // إنشاء سجل تدقيق
            $this->createAuditLog('journal_entry_unposted', $journal_id, array('status' => 'draft'));

            $this->db->query("COMMIT");
            $this->cache->delete('journal_entry');

            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * الحصول على قيد محاسبي واحد
     */
    public function getJournalEntry($journal_id) {
        $query = $this->db->query("SELECT je.*,
                                          u1.firstname as created_by_name, u1.lastname as created_by_lastname,
                                          u2.firstname as posted_by_name, u2.lastname as posted_by_lastname,
                                          u3.firstname as approved_by_name, u3.lastname as approved_by_lastname
                                  FROM " . DB_PREFIX . "journal_entry je
                                  LEFT JOIN " . DB_PREFIX . "user u1 ON je.created_by = u1.user_id
                                  LEFT JOIN " . DB_PREFIX . "user u2 ON je.posted_by = u2.user_id
                                  LEFT JOIN " . DB_PREFIX . "user u3 ON je.approved_by = u3.user_id
                                  WHERE je.journal_id = '" . (int)$journal_id . "'");

        if ($query->num_rows) {
            $journal = $query->row;
            $journal['lines'] = $this->getJournalEntryLines($journal_id);
            return $journal;
        }

        return false;
    }

    /**
     * الحصول على بنود القيد المحاسبي
     */
    public function getJournalEntryLines($journal_id) {
        $query = $this->db->query("SELECT jel.*, a.account_code, ad.name as account_name, a.account_type, a.account_nature
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                  LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  WHERE jel.journal_id = '" . (int)$journal_id . "'
                                  ORDER BY jel.line_number");

        return $query->rows;
    }

    /**
     * الحصول على قائمة القيود المحاسبية
     */
    public function getJournalEntries($data = array()) {
        $sql = "SELECT je.*,
                       u1.firstname as created_by_name, u1.lastname as created_by_lastname,
                       u2.firstname as posted_by_name, u2.lastname as posted_by_lastname
                FROM " . DB_PREFIX . "journal_entry je
                LEFT JOIN " . DB_PREFIX . "user u1 ON je.created_by = u1.user_id
                LEFT JOIN " . DB_PREFIX . "user u2 ON je.posted_by = u2.user_id
                WHERE 1";

        if (!empty($data['filter_journal_number'])) {
            $sql .= " AND je.journal_number LIKE '%" . $this->db->escape($data['filter_journal_number']) . "%'";
        }

        if (!empty($data['filter_description'])) {
            $sql .= " AND je.description LIKE '%" . $this->db->escape($data['filter_description']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND je.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_reference_type'])) {
            $sql .= " AND je.reference_type = '" . $this->db->escape($data['filter_reference_type']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND je.journal_date >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND je.journal_date <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        if (!empty($data['filter_created_by'])) {
            $sql .= " AND je.created_by = '" . (int)$data['filter_created_by'] . "'";
        }

        if (isset($data['filter_auto_generated'])) {
            $sql .= " AND je.auto_generated = '" . (int)$data['filter_auto_generated'] . "'";
        }

        $sql .= " ORDER BY je.journal_date DESC, je.journal_id DESC";

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
     * الحصول على إجمالي عدد القيود
     */
    public function getTotalJournalEntries($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "journal_entry je WHERE 1";

        if (!empty($data['filter_journal_number'])) {
            $sql .= " AND je.journal_number LIKE '%" . $this->db->escape($data['filter_journal_number']) . "%'";
        }

        if (!empty($data['filter_description'])) {
            $sql .= " AND je.description LIKE '%" . $this->db->escape($data['filter_description']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND je.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_reference_type'])) {
            $sql .= " AND je.reference_type = '" . $this->db->escape($data['filter_reference_type']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND je.journal_date >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND je.journal_date <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        if (!empty($data['filter_created_by'])) {
            $sql .= " AND je.created_by = '" . (int)$data['filter_created_by'] . "'";
        }

        if (isset($data['filter_auto_generated'])) {
            $sql .= " AND je.auto_generated = '" . (int)$data['filter_auto_generated'] . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * إضافة بند قيد محاسبي
     */
    private function addJournalEntryLine($journal_id, $line_data, $line_number) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "journal_entry_line SET
            journal_id = '" . (int)$journal_id . "',
            line_number = '" . (int)$line_number . "',
            account_id = '" . (int)$line_data['account_id'] . "',
            debit_amount = '" . (float)($line_data['debit_amount'] ?? 0) . "',
            credit_amount = '" . (float)($line_data['credit_amount'] ?? 0) . "',
            description = '" . $this->db->escape($line_data['description'] ?? '') . "',
            cost_center_id = '" . (int)($line_data['cost_center_id'] ?? 0) . "',
            project_id = '" . (int)($line_data['project_id'] ?? 0) . "',
            department_id = '" . (int)($line_data['department_id'] ?? 0) . "',
            reference_type = '" . $this->db->escape($line_data['reference_type'] ?? '') . "',
            reference_id = '" . (int)($line_data['reference_id'] ?? 0) . "',
            tax_amount = '" . (float)($line_data['tax_amount'] ?? 0) . "',
            tax_rate = '" . (float)($line_data['tax_rate'] ?? 0) . "'");

        return $this->db->getLastId();
    }

    /**
     * التحقق المتقدم من صحة بيانات القيد
     */
    private function validateJournalEntryAdvanced($data) {
        $errors = array();

        // التحقق من وجود التاريخ
        if (empty($data['journal_date'])) {
            $errors[] = 'تاريخ القيد مطلوب';
        } else {
            // التحقق من صحة التاريخ
            if (!strtotime($data['journal_date'])) {
                $errors[] = 'تاريخ القيد غير صحيح';
            }
        }

        // التحقق من وجود الوصف
        if (empty($data['description'])) {
            $errors[] = 'وصف القيد مطلوب';
        }

        // التحقق من وجود البنود
        if (empty($data['lines']) || !is_array($data['lines'])) {
            $errors[] = 'بنود القيد مطلوبة';
        } else {
            // التحقق من صحة البنود
            $total_debit = 0;
            $total_credit = 0;
            $line_number = 1;

            foreach ($data['lines'] as $line) {
                $line_errors = $this->validateJournalEntryLine($line, $line_number);
                if (!empty($line_errors)) {
                    $errors = array_merge($errors, $line_errors);
                }

                $total_debit += (float)($line['debit_amount'] ?? 0);
                $total_credit += (float)($line['credit_amount'] ?? 0);
                $line_number++;
            }

            // التحقق من توازن القيد
            if (abs($total_debit - $total_credit) > 0.01) {
                $errors[] = 'القيد غير متوازن - إجمالي المدين: ' . $total_debit . ' إجمالي الدائن: ' . $total_credit;
            }

            // التحقق من وجود بندين على الأقل
            if (count($data['lines']) < 2) {
                $errors[] = 'القيد يجب أن يحتوي على بندين على الأقل';
            }
        }

        // التحقق من صحة رقم القيد إذا تم تحديده
        if (!empty($data['journal_number'])) {
            if (!$this->isJournalNumberUnique($data['journal_number'], $data['journal_id'] ?? null)) {
                $errors[] = 'رقم القيد موجود مسبقاً';
            }
        }

        return array(
            'valid' => empty($errors),
            'error' => implode(', ', $errors)
        );
    }

    /**
     * التحقق من صحة بند القيد
     */
    private function validateJournalEntryLine($line, $line_number) {
        $errors = array();

        // التحقق من وجود الحساب
        if (empty($line['account_id'])) {
            $errors[] = "البند {$line_number}: الحساب مطلوب";
        } else {
            // التحقق من وجود الحساب في قاعدة البيانات
            $query = $this->db->query("SELECT account_id, allow_posting, is_active FROM " . DB_PREFIX . "accounts WHERE account_id = '" . (int)$line['account_id'] . "'");
            if ($query->num_rows == 0) {
                $errors[] = "البند {$line_number}: الحساب غير موجود";
            } else {
                if (!$query->row['is_active']) {
                    $errors[] = "البند {$line_number}: الحساب غير نشط";
                }
                if (!$query->row['allow_posting']) {
                    $errors[] = "البند {$line_number}: الحساب لا يسمح بالترحيل";
                }
            }
        }

        // التحقق من وجود مبلغ
        $debit = (float)($line['debit_amount'] ?? 0);
        $credit = (float)($line['credit_amount'] ?? 0);

        if ($debit == 0 && $credit == 0) {
            $errors[] = "البند {$line_number}: يجب إدخال مبلغ مدين أو دائن";
        }

        if ($debit > 0 && $credit > 0) {
            $errors[] = "البند {$line_number}: لا يمكن إدخال مبلغ مدين ودائن في نفس البند";
        }

        if ($debit < 0 || $credit < 0) {
            $errors[] = "البند {$line_number}: المبالغ يجب أن تكون موجبة";
        }

        return $errors;
    }

    /**
     * حساب إجمالي المدين
     */
    private function calculateTotalDebit($lines) {
        $total = 0;
        foreach ($lines as $line) {
            $total += (float)($line['debit_amount'] ?? 0);
        }
        return $total;
    }

    /**
     * حساب إجمالي الدائن
     */
    private function calculateTotalCredit($lines) {
        $total = 0;
        foreach ($lines as $line) {
            $total += (float)($line['credit_amount'] ?? 0);
        }
        return $total;
    }

    /**
     * توليد رقم قيد تلقائي
     */
    private function generateJournalNumber($journal_date) {
        $year = date('Y', strtotime($journal_date));
        $month = date('m', strtotime($journal_date));

        $prefix = 'JE-' . $year . $month . '-';

        $query = $this->db->query("SELECT MAX(CAST(SUBSTRING(journal_number, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as max_number
                                  FROM " . DB_PREFIX . "journal_entry
                                  WHERE journal_number LIKE '" . $prefix . "%'");

        $next_number = 1;
        if ($query->num_rows > 0 && $query->row['max_number']) {
            $next_number = $query->row['max_number'] + 1;
        }

        return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * التحقق من تفرد رقم القيد
     */
    private function isJournalNumberUnique($journal_number, $journal_id = null) {
        $sql = "SELECT journal_id FROM " . DB_PREFIX . "journal_entry WHERE journal_number = '" . $this->db->escape($journal_number) . "'";
        if ($journal_id) {
            $sql .= " AND journal_id != '" . (int)$journal_id . "'";
        }

        $query = $this->db->query($sql);
        return $query->num_rows == 0;
    }

    /**
     * تحديث أرصدة الحسابات
     */
    private function updateAccountBalances($journal_id) {
        $lines = $this->getJournalEntryLines($journal_id);

        foreach ($lines as $line) {
            $this->load->model('accounts/chartaccount');
            $this->model_accounts_chartaccount->updateAccountBalance($line['account_id']);
        }
    }

    /**
     * عكس تأثير القيد على أرصدة الحسابات
     */
    private function reverseAccountBalances($journal_id, $journal_data) {
        $lines = $this->getJournalEntryLines($journal_id);

        foreach ($lines as $line) {
            $this->load->model('accounts/chartaccount');
            $this->model_accounts_chartaccount->updateAccountBalance($line['account_id']);
        }
    }

    /**
     * التحقق من صحة القيد قبل الترحيل
     */
    private function validateJournalForPosting($journal_id) {
        $journal = $this->getJournalEntry($journal_id);
        $errors = array();

        // التحقق من توازن القيد
        if (abs($journal['total_debit'] - $journal['total_credit']) > 0.01) {
            $errors[] = 'القيد غير متوازن';
        }

        // التحقق من وجود بنود
        if (empty($journal['lines'])) {
            $errors[] = 'القيد لا يحتوي على بنود';
        }

        // التحقق من صحة الحسابات
        foreach ($journal['lines'] as $line) {
            if (!$line['account_id']) {
                $errors[] = 'يوجد بند بدون حساب';
            }
        }

        return array(
            'valid' => empty($errors),
            'error' => implode(', ', $errors)
        );
    }

    /**
     * إنشاء سجل تدقيق
     */
    private function createAuditLog($action, $journal_id, $new_values = array(), $old_values = array()) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "audit_log SET
            table_name = 'journal_entry',
            record_id = '" . (int)$journal_id . "',
            action = '" . $this->db->escape($action) . "',
            old_values = '" . $this->db->escape(json_encode($old_values)) . "',
            new_values = '" . $this->db->escape(json_encode($new_values)) . "',
            user_id = '" . (int)$this->user->getId() . "',
            ip_address = '" . $this->db->escape($this->request->server['REMOTE_ADDR'] ?? '') . "',
            user_agent = '" . $this->db->escape($this->request->server['HTTP_USER_AGENT'] ?? '') . "',
            date_added = NOW()");
    }

    /**
     * إرسال إشعارات الموافقة
     */
    private function sendApprovalNotifications($journal_id) {
        // تنفيذ إرسال الإشعارات للمسؤولين عن الموافقة
        $this->load->model('common/notification');

        $journal = $this->getJournalEntry($journal_id);

        $notification_data = array(
            'type' => 'journal_approval_required',
            'title' => 'قيد محاسبي يتطلب موافقة',
            'message' => 'القيد رقم ' . $journal['journal_number'] . ' يتطلب موافقة',
            'reference_type' => 'journal_entry',
            'reference_id' => $journal_id,
            'priority' => 'high'
        );

        // إرسال للمسؤولين عن الموافقة
        $approvers = $this->getApprovers();
        foreach ($approvers as $approver) {
            $notification_data['user_id'] = $approver['user_id'];
            $this->model_common_notification->addNotification($notification_data);
        }
    }

    /**
     * الحصول على المسؤولين عن الموافقة
     */
    private function getApprovers() {
        $query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user_group_permission
                                  WHERE permission = 'accounts/journal_approval'
                                  AND type = 'access'");
        return $query->rows;
    }

    /**
     * إنشاء قيد تلقائي من معاملة
     */
    public function createAutoJournalEntry($reference_type, $reference_id, $journal_data) {
        $journal_data['auto_generated'] = 1;
        $journal_data['reference_type'] = $reference_type;
        $journal_data['reference_id'] = $reference_id;
        $journal_data['status'] = 'posted'; // القيود التلقائية ترحل مباشرة

        return $this->addJournalEntry($journal_data);
    }

    /**
     * البحث في القيود المحاسبية
     */
    public function searchJournalEntries($search_term, $limit = 10) {
        $sql = "SELECT je.journal_id, je.journal_number, je.journal_date, je.description, je.total_debit
                FROM " . DB_PREFIX . "journal_entry je
                WHERE (je.journal_number LIKE '%" . $this->db->escape($search_term) . "%'
                OR je.description LIKE '%" . $this->db->escape($search_term) . "%')
                ORDER BY je.journal_date DESC, je.journal_id DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }
}
