<?php
/**
 * نموذج دليل الحسابات المحسن والمتكامل
 * يدعم الهيكل الشجري وتجميع الأرصدة والتكامل مع النظام
 */
class ModelAccountsChartaccount extends Model {

    /**
     * إضافة حساب جديد مع التحقق المتقدم والتكامل الكامل
     */
    public function addAccount($data) {
        // التحقق المتقدم من صحة البيانات
        $validation = $this->validateAccountDataAdvanced($data);
        if (!$validation['valid']) {
            throw new Exception($validation['error']);
        }

        // تحديد المستوى والطبيعة تلقائياً
        $level = $this->calculateAccountLevel($data['parent_id'] ?? null);
        $account_nature = $this->determineAccountNature($data['account_type']);

        // تحديد المسار الهرمي للحساب
        $account_path = $this->generateAccountPath($data['parent_id'] ?? null, $data['account_code']);

        // تحديد رقم الحساب التلقائي إذا لم يتم تحديده
        if (empty($data['account_code'])) {
            $data['account_code'] = $this->generateAccountCode($data['account_type'], $data['parent_id'] ?? null);
        }

        $this->db->query("START TRANSACTION");

        try {
            // إدراج الحساب مع البيانات المتقدمة
            $this->db->query("INSERT INTO " . DB_PREFIX . "accounts SET
                account_code = '" . $this->db->escape($data['account_code']) . "',
                parent_id = " . (isset($data['parent_id']) && $data['parent_id'] ? "'" . (int)$data['parent_id'] . "'" : "NULL") . ",
                account_type = '" . $this->db->escape($data['account_type']) . "',
                account_nature = '" . $this->db->escape($account_nature) . "',
                account_path = '" . $this->db->escape($account_path) . "',
                level = '" . (int)$level . "',
                is_parent = '" . (int)(isset($data['is_parent']) ? $data['is_parent'] : 0) . "',
                is_active = '" . (int)(isset($data['status']) ? $data['status'] : 1) . "',
                allow_posting = '" . (int)(isset($data['allow_posting']) ? $data['allow_posting'] : 1) . "',
                opening_balance = '" . (float)(isset($data['opening_balance']) ? $data['opening_balance'] : 0) . "',
                current_balance = '" . (float)(isset($data['opening_balance']) ? $data['opening_balance'] : 0) . "',
                sort_order = '" . (int)(isset($data['sort_order']) ? $data['sort_order'] : 0) . "',
                tax_rate = '" . (float)(isset($data['tax_rate']) ? $data['tax_rate'] : 0) . "',
                cost_center_id = '" . (int)(isset($data['cost_center_id']) ? $data['cost_center_id'] : 0) . "',
                currency_code = '" . $this->db->escape(isset($data['currency_code']) ? $data['currency_code'] : $this->config->get('config_currency')) . "',
                reconciliation_required = '" . (int)(isset($data['reconciliation_required']) ? $data['reconciliation_required'] : 0) . "',
                auto_posting = '" . (int)(isset($data['auto_posting']) ? $data['auto_posting'] : 1) . "',
                is_system = '" . (int)(isset($data['is_system']) ? $data['is_system'] : 0) . "',
                created_by = '" . (int)$this->user->getId() . "',
                date_added = NOW(),
                date_modified = NOW()");

            $account_id = $this->db->getLastId();

            // إدراج أوصاف الحساب متعددة اللغات
            if (isset($data['account_description'])) {
                foreach ($data['account_description'] as $language_id => $value) {
                    if (!empty($value['name'])) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "account_description SET
                            account_id = '" . (int)$account_id . "',
                            language_id = '" . (int)$language_id . "',
                            name = '" . $this->db->escape($value['name']) . "',
                            description = '" . $this->db->escape(isset($value['description']) ? $value['description'] : '') . "'");
                    }
                }
            }

            // إضافة إعدادات الحساب المتقدمة
            $this->addAccountSettings($account_id, $data);

            // تحديث الحساب الأب ليصبح حساب أب
            if (isset($data['parent_id']) && $data['parent_id']) {
                $this->db->query("UPDATE " . DB_PREFIX . "accounts SET is_parent = 1 WHERE account_id = '" . (int)$data['parent_id'] . "'");
            }

            // إنشاء قيد الرصيد الافتتاحي إذا كان موجود
            if (!empty($data['opening_balance']) && $data['opening_balance'] != 0) {
                $this->createOpeningBalanceEntry($account_id, $data);
            }

            // إنشاء سجل تدقيق
            $this->createAuditLog('account_created', $account_id, $data);

            // تحديث مسارات الحسابات الفرعية إذا كان هذا حساب أب
            if (isset($data['is_parent']) && $data['is_parent']) {
                $this->updateChildAccountsPaths($account_id);
            }

            $this->db->query("COMMIT");
            $this->cache->delete('account');

            return $account_id;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * تعديل حساب موجود
     */
    public function editAccount($account_id, $data) {
        // التحقق من صحة البيانات
        if (!$this->validateAccountData($data, $account_id)) {
            return false;
        }

        // تحديد المستوى والطبيعة تلقائياً
        $level = $this->calculateAccountLevel($data['parent_id']);
        $account_nature = $this->determineAccountNature($data['account_type']);

        // تحديث الحساب
        $this->db->query("UPDATE " . DB_PREFIX . "accounts SET
            account_code = '" . $this->db->escape($data['account_code']) . "',
            parent_id = " . (isset($data['parent_id']) && $data['parent_id'] ? "'" . (int)$data['parent_id'] . "'" : "NULL") . ",
            account_type = '" . $this->db->escape($data['account_type']) . "',
            account_nature = '" . $this->db->escape($account_nature) . "',
            level = '" . (int)$level . "',
            is_parent = '" . (int)(isset($data['is_parent']) ? $data['is_parent'] : 0) . "',
            is_active = '" . (int)(isset($data['status']) ? $data['status'] : 1) . "',
            allow_posting = '" . (int)(isset($data['allow_posting']) ? $data['allow_posting'] : 1) . "',
            sort_order = '" . (int)(isset($data['sort_order']) ? $data['sort_order'] : 0) . "',
            date_modified = NOW()
            WHERE account_id = '" . (int)$account_id . "'");

        // حذف الأوصاف القديمة
        $this->db->query("DELETE FROM " . DB_PREFIX . "account_description WHERE account_id = '" . (int)$account_id . "'");

        // إدراج الأوصاف الجديدة
        if (isset($data['account_description'])) {
            foreach ($data['account_description'] as $language_id => $value) {
                if (!empty($value['name'])) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "account_description SET
                        account_id = '" . (int)$account_id . "',
                        language_id = '" . (int)$language_id . "',
                        name = '" . $this->db->escape($value['name']) . "',
                        description = '" . $this->db->escape(isset($value['description']) ? $value['description'] : '') . "'");
                }
            }
        }

        $this->cache->delete('account');
        return true;
    }

    /**
     * حذف حساب
     */
    public function deleteAccount($account_id) {
        // التحقق من إمكانية الحذف
        if (!$this->canDeleteAccount($account_id)) {
            return false;
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "accounts WHERE account_id = '" . (int)$account_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "account_description WHERE account_id = '" . (int)$account_id . "'");

        $this->cache->delete('account');
        return true;
    }

    /**
     * الحصول على حساب واحد
     */
    public function getAccount($account_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounts WHERE account_id = '" . (int)$account_id . "'");
        return $query->row;
    }

    /**
     * الحصول على قائمة الحسابات مع التصفية والترتيب
     */
    public function getAccounts($data = array()) {
        $sql = "SELECT a.*, ad.name, ad.description,
                       CASE WHEN a.parent_id IS NULL THEN a.account_code
                            ELSE CONCAT(pa.account_code, '-', a.account_code) END as full_code
                FROM " . DB_PREFIX . "accounts a
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id)
                LEFT JOIN " . DB_PREFIX . "accounts pa ON (a.parent_id = pa.account_id)
                WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND (ad.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'
                     OR a.account_code LIKE '%" . $this->db->escape($data['filter_name']) . "%')";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND a.account_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (isset($data['filter_active'])) {
            $sql .= " AND a.is_active = '" . (int)$data['filter_active'] . "'";
        }

        $sql .= " ORDER BY a.account_code ASC";

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
     * الحصول على قائمة الحسابات للقيود المحاسبية
     */
    public function getAccountsForJournal($data = array()) {
        $sql = "SELECT a.account_id, a.account_code, a.account_type, a.account_nature,
                       a.current_balance, ad.name, a.allow_posting, a.is_active
                FROM " . DB_PREFIX . "accounts a
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id)
                WHERE a.is_active = 1 AND a.allow_posting = 1
                AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND (ad.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'
                     OR a.account_code LIKE '%" . $this->db->escape($data['filter_name']) . "%')";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND a.account_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        $sql .= " ORDER BY a.account_code ASC";

        if (isset($data['limit'])) {
            $sql .= " LIMIT " . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * الحصول على الهيكل الشجري للحسابات
     */
    public function getAccountsTree($parent_id = null, $level = 0) {
        $sql = "SELECT a.*, ad.name, ad.description
                FROM " . DB_PREFIX . "accounts a
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id)
                WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if ($parent_id === null) {
            $sql .= " AND a.parent_id IS NULL";
        } else {
            $sql .= " AND a.parent_id = '" . (int)$parent_id . "'";
        }

        $sql .= " ORDER BY a.sort_order, a.account_code";

        $query = $this->db->query($sql);
        $accounts = array();

        foreach ($query->rows as $account) {
            $account['level'] = $level;
            $account['children'] = $this->getAccountsTree($account['account_id'], $level + 1);
            $accounts[] = $account;
        }

        return $accounts;
    }

    /**
     * الحصول على أوصاف الحساب
     */
    public function getAccountDescriptions($account_id) {
        $account_description_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "account_description WHERE account_id = '" . (int)$account_id . "'");

        foreach ($query->rows as $result) {
            $account_description_data[$result['language_id']] = array(
                'name' => $result['name'],
                'description' => $result['description']
            );
        }

        return $account_description_data;
    }

    /**
     * الحصول على إجمالي عدد الحسابات
     */
    public function getTotalAccounts($data = array()) {
        $sql = "SELECT COUNT(DISTINCT a.account_id) AS total
                FROM " . DB_PREFIX . "accounts a
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id)
                WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND (ad.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'
                     OR a.account_code LIKE '%" . $this->db->escape($data['filter_name']) . "%')";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND a.account_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (isset($data['filter_active'])) {
            $sql .= " AND a.is_active = '" . (int)$data['filter_active'] . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * التحقق من صحة بيانات الحساب
     */
    private function validateAccountData($data, $account_id = null) {
        // التحقق من وجود رقم الحساب
        if (empty($data['account_code'])) {
            return false;
        }

        // التحقق من تفرد رقم الحساب
        $sql = "SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_code = '" . $this->db->escape($data['account_code']) . "'";
        if ($account_id) {
            $sql .= " AND account_id != '" . (int)$account_id . "'";
        }
        $query = $this->db->query($sql);
        if ($query->num_rows > 0) {
            return false;
        }

        // التحقق من صحة نوع الحساب
        $valid_types = array('asset', 'liability', 'equity', 'revenue', 'expense');
        if (!in_array($data['account_type'], $valid_types)) {
            return false;
        }

        // التحقق من وجود الحساب الأب
        if (isset($data['parent_id']) && $data['parent_id']) {
            $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_id = '" . (int)$data['parent_id'] . "'");
            if ($query->num_rows == 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * حساب مستوى الحساب في الشجرة
     */
    private function calculateAccountLevel($parent_id) {
        if (!$parent_id) {
            return 1;
        }

        $query = $this->db->query("SELECT level FROM " . DB_PREFIX . "accounts WHERE account_id = '" . (int)$parent_id . "'");
        if ($query->num_rows > 0) {
            return $query->row['level'] + 1;
        }

        return 1;
    }

    /**
     * تحديد طبيعة الحساب (مدين أو دائن)
     */
    private function determineAccountNature($account_type) {
        switch ($account_type) {
            case 'asset':
            case 'expense':
                return 'debit';
            case 'liability':
            case 'equity':
            case 'revenue':
                return 'credit';
            default:
                return 'debit';
        }
    }

    /**
     * التحقق من إمكانية حذف الحساب
     */
    private function canDeleteAccount($account_id) {
        // التحقق من وجود حسابات فرعية
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "accounts WHERE parent_id = '" . (int)$account_id . "'");
        if ($query->row['total'] > 0) {
            return false;
        }

        // التحقق من وجود قيود محاسبية
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "journal_entry_line WHERE account_id = '" . (int)$account_id . "'");
        if ($query->row['total'] > 0) {
            return false;
        }

        return true;
    }

    /**
     * الحصول على رصيد الحساب
     */
    public function getAccountBalance($account_id, $date = null) {
        $sql = "SELECT
                    COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as balance
                FROM " . DB_PREFIX . "journal_entry_line jel
                JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                WHERE jel.account_id = '" . (int)$account_id . "'
                AND je.status = 'posted'";

        if ($date) {
            $sql .= " AND je.journal_date <= '" . $this->db->escape($date) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['balance'];
    }

    /**
     * تحديث رصيد الحساب
     */
    public function updateAccountBalance($account_id) {
        $balance = $this->getAccountBalance($account_id);

        $this->db->query("UPDATE " . DB_PREFIX . "accounts
                         SET current_balance = '" . (float)$balance . "'
                         WHERE account_id = '" . (int)$account_id . "'");

        return $balance;
    }

    /**
     * الحصول على كشف حساب
     */
    public function getAccountStatement($account_id, $start_date, $end_date) {
        $sql = "SELECT
                    je.journal_date,
                    je.journal_number,
                    je.description,
                    jel.description as line_description,
                    jel.debit_amount,
                    jel.credit_amount
                FROM " . DB_PREFIX . "journal_entry_line jel
                JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                WHERE jel.account_id = '" . (int)$account_id . "'
                AND je.status = 'posted'
                AND je.journal_date BETWEEN '" . $this->db->escape($start_date) . "'
                AND '" . $this->db->escape($end_date) . "'
                ORDER BY je.journal_date, je.journal_id, jel.line_id";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * البحث التلقائي في الحسابات
     */
    public function autocomplete($filter_name) {
        $json = array();

        if ($filter_name) {
            $sql = "SELECT a.account_id, a.account_code, ad.name
                    FROM " . DB_PREFIX . "accounts a
                    LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id)
                    WHERE a.is_active = 1 AND a.allow_posting = 1
                    AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'
                    AND (ad.name LIKE '%" . $this->db->escape($filter_name) . "%'
                    OR a.account_code LIKE '%" . $this->db->escape($filter_name) . "%')
                    ORDER BY a.account_code ASC
                    LIMIT 10";

            $query = $this->db->query($sql);

            foreach ($query->rows as $result) {
                $json[] = array(
                    'account_id' => $result['account_id'],
                    'account_code' => $result['account_code'],
                    'name' => $result['name'],
                    'display_name' => $result['account_code'] . ' - ' . $result['name']
                );
            }
        }

        return $json;
    }

    /**
     * الحصول على الحسابات حسب النوع
     */
    public function getAccountsByType($account_type) {
        $sql = "SELECT a.*, ad.name
                FROM " . DB_PREFIX . "accounts a
                LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id)
                WHERE a.account_type = '" . $this->db->escape($account_type) . "'
                AND a.is_active = 1
                AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'
                ORDER BY a.account_code";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * التحقق المتقدم من صحة بيانات الحساب
     */
    private function validateAccountDataAdvanced($data, $account_id = null) {
        $errors = array();

        // التحقق من وجود رقم الحساب
        if (empty($data['account_code'])) {
            $errors[] = 'رقم الحساب مطلوب';
        } else {
            // التحقق من تفرد رقم الحساب
            $sql = "SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_code = '" . $this->db->escape($data['account_code']) . "'";
            if ($account_id) {
                $sql .= " AND account_id != '" . (int)$account_id . "'";
            }
            $query = $this->db->query($sql);
            if ($query->num_rows > 0) {
                $errors[] = 'رقم الحساب موجود مسبقاً';
            }

            // التحقق من صحة تنسيق رقم الحساب
            if (!preg_match('/^[0-9]{4,10}$/', $data['account_code'])) {
                $errors[] = 'رقم الحساب يجب أن يكون من 4 إلى 10 أرقام';
            }
        }

        // التحقق من صحة نوع الحساب
        $valid_types = array('asset', 'liability', 'equity', 'revenue', 'expense');
        if (empty($data['account_type']) || !in_array($data['account_type'], $valid_types)) {
            $errors[] = 'نوع الحساب غير صحيح';
        }

        // التحقق من وجود الحساب الأب
        if (isset($data['parent_id']) && $data['parent_id']) {
            $query = $this->db->query("SELECT account_id, account_type FROM " . DB_PREFIX . "accounts WHERE account_id = '" . (int)$data['parent_id'] . "'");
            if ($query->num_rows == 0) {
                $errors[] = 'الحساب الأب غير موجود';
            } else {
                // التحقق من تطابق نوع الحساب مع الحساب الأب
                if ($query->row['account_type'] != $data['account_type']) {
                    $errors[] = 'نوع الحساب يجب أن يطابق نوع الحساب الأب';
                }
            }
        }

        // التحقق من صحة الرصيد الافتتاحي
        if (isset($data['opening_balance']) && !is_numeric($data['opening_balance'])) {
            $errors[] = 'الرصيد الافتتاحي يجب أن يكون رقماً';
        }

        // التحقق من صحة معدل الضريبة
        if (isset($data['tax_rate']) && (!is_numeric($data['tax_rate']) || $data['tax_rate'] < 0 || $data['tax_rate'] > 100)) {
            $errors[] = 'معدل الضريبة يجب أن يكون بين 0 و 100';
        }

        return array(
            'valid' => empty($errors),
            'error' => implode(', ', $errors)
        );
    }

    /**
     * إنشاء المسار الهرمي للحساب
     */
    private function generateAccountPath($parent_id, $account_code) {
        if (!$parent_id) {
            return $account_code;
        }

        $query = $this->db->query("SELECT account_path FROM " . DB_PREFIX . "accounts WHERE account_id = '" . (int)$parent_id . "'");
        if ($query->num_rows > 0) {
            return $query->row['account_path'] . '/' . $account_code;
        }

        return $account_code;
    }

    /**
     * توليد رقم حساب تلقائي
     */
    private function generateAccountCode($account_type, $parent_id = null) {
        $type_prefixes = array(
            'asset' => '1',
            'liability' => '2',
            'equity' => '3',
            'revenue' => '4',
            'expense' => '5'
        );

        $prefix = $type_prefixes[$account_type] ?? '9';

        if ($parent_id) {
            $query = $this->db->query("SELECT account_code FROM " . DB_PREFIX . "accounts WHERE account_id = '" . (int)$parent_id . "'");
            if ($query->num_rows > 0) {
                $parent_code = $query->row['account_code'];
                $prefix = $parent_code;
            }
        }

        // البحث عن أعلى رقم متاح
        $query = $this->db->query("SELECT MAX(CAST(account_code AS UNSIGNED)) as max_code
                                  FROM " . DB_PREFIX . "accounts
                                  WHERE account_code LIKE '" . $prefix . "%'
                                  AND LENGTH(account_code) = " . (strlen($prefix) + 3));

        $next_number = 1;
        if ($query->num_rows > 0 && $query->row['max_code']) {
            $last_digits = substr($query->row['max_code'], -3);
            $next_number = intval($last_digits) + 1;
        }

        return $prefix . str_pad($next_number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * إضافة إعدادات الحساب المتقدمة
     */
    private function addAccountSettings($account_id, $data) {
        $settings = array(
            'auto_reconcile' => isset($data['auto_reconcile']) ? $data['auto_reconcile'] : 0,
            'require_approval' => isset($data['require_approval']) ? $data['require_approval'] : 0,
            'budget_control' => isset($data['budget_control']) ? $data['budget_control'] : 0,
            'cost_center_required' => isset($data['cost_center_required']) ? $data['cost_center_required'] : 0,
            'project_required' => isset($data['project_required']) ? $data['project_required'] : 0,
            'department_required' => isset($data['department_required']) ? $data['department_required'] : 0
        );

        foreach ($settings as $key => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "account_settings SET
                account_id = '" . (int)$account_id . "',
                setting_key = '" . $this->db->escape($key) . "',
                setting_value = '" . $this->db->escape($value) . "'");
        }
    }

    /**
     * إنشاء قيد الرصيد الافتتاحي
     */
    private function createOpeningBalanceEntry($account_id, $data) {
        $this->load->model('accounts/journal_entry');

        $journal_data = array(
            'journal_date' => date('Y-01-01'),
            'journal_number' => 'OB-' . $account_id,
            'description' => 'رصيد افتتاحي للحساب ' . $data['account_code'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'opening_balance',
            'reference_id' => $account_id,
            'lines' => array(
                array(
                    'account_id' => $account_id,
                    'debit_amount' => $data['opening_balance_type'] == 'debit' ? $data['opening_balance'] : 0,
                    'credit_amount' => $data['opening_balance_type'] == 'credit' ? $data['opening_balance'] : 0,
                    'description' => 'رصيد افتتاحي'
                ),
                array(
                    'account_id' => $this->getOpeningBalanceAccount(),
                    'debit_amount' => $data['opening_balance_type'] == 'credit' ? $data['opening_balance'] : 0,
                    'credit_amount' => $data['opening_balance_type'] == 'debit' ? $data['opening_balance'] : 0,
                    'description' => 'رصيد افتتاحي مقابل'
                )
            )
        );

        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * إنشاء سجل تدقيق
     */
    private function createAuditLog($action, $account_id, $data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "audit_log SET
            table_name = 'accounts',
            record_id = '" . (int)$account_id . "',
            action = '" . $this->db->escape($action) . "',
            old_values = '',
            new_values = '" . $this->db->escape(json_encode($data)) . "',
            user_id = '" . (int)$this->user->getId() . "',
            ip_address = '" . $this->db->escape($this->request->server['REMOTE_ADDR'] ?? '') . "',
            user_agent = '" . $this->db->escape($this->request->server['HTTP_USER_AGENT'] ?? '') . "',
            date_added = NOW()");
    }

    /**
     * تحديث مسارات الحسابات الفرعية
     */
    private function updateChildAccountsPaths($parent_id) {
        $query = $this->db->query("SELECT account_id, account_code FROM " . DB_PREFIX . "accounts WHERE parent_id = '" . (int)$parent_id . "'");

        foreach ($query->rows as $child) {
            $new_path = $this->generateAccountPath($parent_id, $child['account_code']);
            $this->db->query("UPDATE " . DB_PREFIX . "accounts SET account_path = '" . $this->db->escape($new_path) . "' WHERE account_id = '" . (int)$child['account_id'] . "'");

            // تحديث الحسابات الفرعية للحساب الفرعي
            $this->updateChildAccountsPaths($child['account_id']);
        }
    }

    /**
     * الحصول على حساب الأرصدة الافتتاحية
     */
    private function getOpeningBalanceAccount() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_code = '3900' AND account_type = 'equity' LIMIT 1");

        if ($query->num_rows > 0) {
            return $query->row['account_id'];
        }

        // إنشاء حساب الأرصدة الافتتاحية إذا لم يكن موجود
        return $this->createOpeningBalanceAccount();
    }

    /**
     * إنشاء حساب الأرصدة الافتتاحية
     */
    private function createOpeningBalanceAccount() {
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounts SET
            account_code = '3900',
            account_type = 'equity',
            account_nature = 'credit',
            level = 1,
            is_active = 1,
            allow_posting = 1,
            is_system = 1,
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");

        $account_id = $this->db->getLastId();

        // إضافة الوصف
        $this->db->query("INSERT INTO " . DB_PREFIX . "account_description SET
            account_id = '" . (int)$account_id . "',
            language_id = '" . (int)$this->config->get('config_language_id') . "',
            name = 'الأرصدة الافتتاحية',
            description = 'حساب نظام للأرصدة الافتتاحية'");

        return $account_id;
    }
}
