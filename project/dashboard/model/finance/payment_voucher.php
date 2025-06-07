<?php
/**
 * Model: Payment Voucher
 * نموذج سندات الصرف المتقدم
 * يدير جميع عمليات سندات الصرف مع التكامل المحاسبي
 */

class ModelFinancePaymentVoucher extends Model {

    /**
     * إضافة سند صرف جديد
     */
    public function addPaymentVoucher($data) {
        $voucher_number = $this->generateVoucherNumber();

        $this->db->query("
            INSERT INTO " . DB_PREFIX . "payment_vouchers SET
            voucher_number = '" . $this->db->escape($voucher_number) . "',
            voucher_date = '" . $this->db->escape($data['voucher_date']) . "',
            supplier_id = '" . (int)$data['supplier_id'] . "',
            amount = '" . (float)$data['amount'] . "',
            currency_id = '" . (int)$data['currency_id'] . "',
            exchange_rate = '" . (float)$data['exchange_rate'] . "',
            payment_method = '" . $this->db->escape($data['payment_method']) . "',
            cash_account_id = " . ($data['cash_account_id'] ? "'" . (int)$data['cash_account_id'] . "'" : "NULL") . ",
            bank_account_id = " . ($data['bank_account_id'] ? "'" . (int)$data['bank_account_id'] . "'" : "NULL") . ",
            check_number = '" . $this->db->escape($data['check_number']) . "',
            check_date = " . ($data['check_date'] ? "'" . $this->db->escape($data['check_date']) . "'" : "NULL") . ",
            bank_name = '" . $this->db->escape($data['bank_name']) . "',
            reference_number = '" . $this->db->escape($data['reference_number']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            status = 'draft',
            is_approved = 0,
            is_posted = 0,
            created_by = '" . (int)$this->user->getId() . "',
            created_date = NOW()
        ");

        $voucher_id = $this->db->getLastId();

        // إدراج تخصيص الفواتير إن وجد
        if (!empty($data['bill_allocations'])) {
            $this->addBillAllocations($voucher_id, $data['bill_allocations']);
        }

        // إدراج بنود الصرف
        if (!empty($data['expense_items'])) {
            $this->addExpenseItems($voucher_id, $data['expense_items']);
        }

        return $voucher_id;
    }

    /**
     * تعديل سند صرف
     */
    public function editPaymentVoucher($voucher_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "payment_vouchers SET
            voucher_date = '" . $this->db->escape($data['voucher_date']) . "',
            supplier_id = '" . (int)$data['supplier_id'] . "',
            amount = '" . (float)$data['amount'] . "',
            currency_id = '" . (int)$data['currency_id'] . "',
            exchange_rate = '" . (float)$data['exchange_rate'] . "',
            payment_method = '" . $this->db->escape($data['payment_method']) . "',
            cash_account_id = " . ($data['cash_account_id'] ? "'" . (int)$data['cash_account_id'] . "'" : "NULL") . ",
            bank_account_id = " . ($data['bank_account_id'] ? "'" . (int)$data['bank_account_id'] . "'" : "NULL") . ",
            check_number = '" . $this->db->escape($data['check_number']) . "',
            check_date = " . ($data['check_date'] ? "'" . $this->db->escape($data['check_date']) . "'" : "NULL") . ",
            bank_name = '" . $this->db->escape($data['bank_name']) . "',
            reference_number = '" . $this->db->escape($data['reference_number']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_date = NOW()
            WHERE voucher_id = '" . (int)$voucher_id . "'
        ");

        // حذف التخصيصات والبنود القديمة
        $this->db->query("DELETE FROM " . DB_PREFIX . "payment_voucher_allocations WHERE voucher_id = '" . (int)$voucher_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "payment_voucher_items WHERE voucher_id = '" . (int)$voucher_id . "'");

        // إدراج التخصيصات والبنود الجديدة
        if (!empty($data['bill_allocations'])) {
            $this->addBillAllocations($voucher_id, $data['bill_allocations']);
        }

        if (!empty($data['expense_items'])) {
            $this->addExpenseItems($voucher_id, $data['expense_items']);
        }

        return true;
    }

    /**
     * حذف سند صرف
     */
    public function deletePaymentVoucher($voucher_id) {
        // التحقق من إمكانية الحذف
        $voucher = $this->getPaymentVoucher($voucher_id);
        if ($voucher['is_posted']) {
            return false; // لا يمكن حذف سند مرحل
        }

        // حذف التخصيصات والبنود
        $this->db->query("DELETE FROM " . DB_PREFIX . "payment_voucher_allocations WHERE voucher_id = '" . (int)$voucher_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "payment_voucher_items WHERE voucher_id = '" . (int)$voucher_id . "'");

        // حذف السند
        $this->db->query("DELETE FROM " . DB_PREFIX . "payment_vouchers WHERE voucher_id = '" . (int)$voucher_id . "'");

        return true;
    }

    /**
     * اعتماد سند صرف
     */
    public function approvePaymentVoucher($voucher_id) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "payment_vouchers SET
            is_approved = 1,
            approved_by = '" . (int)$this->user->getId() . "',
            approved_date = NOW(),
            status = 'approved'
            WHERE voucher_id = '" . (int)$voucher_id . "'
        ");

        return $this->db->countAffected() > 0;
    }

    /**
     * ترحيل سند صرف
     */
    public function postPaymentVoucher($voucher_id) {
        $voucher = $this->getPaymentVoucher($voucher_id);

        if (!$voucher) {
            throw new Exception('سند الصرف غير موجود');
        }

        if (!$voucher['is_approved']) {
            throw new Exception('يجب اعتماد السند قبل الترحيل');
        }

        if ($voucher['is_posted']) {
            throw new Exception('السند مرحل مسبقاً');
        }

        // إنشاء القيد المحاسبي
        $journal_id = $this->createJournalEntry($voucher);

        // تحديث حالة السند
        $this->db->query("
            UPDATE " . DB_PREFIX . "payment_vouchers SET
            is_posted = 1,
            posted_by = '" . (int)$this->user->getId() . "',
            posted_date = NOW(),
            journal_id = '" . (int)$journal_id . "',
            status = 'posted'
            WHERE voucher_id = '" . (int)$voucher_id . "'
        ");

        // تحديث أرصدة الموردين
        if ($voucher['supplier_id']) {
            $this->updateSupplierBalance($voucher['supplier_id'], $voucher['amount']);
        }

        return array(
            'journal_id' => $journal_id,
            'voucher_id' => $voucher_id
        );
    }

    /**
     * إنشاء القيد المحاسبي
     */
    private function createJournalEntry($voucher) {
        // إنشاء القيد الرئيسي
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "journal_entries SET
            journal_date = '" . $this->db->escape($voucher['voucher_date']) . "',
            description = 'سند صرف رقم: " . $voucher['voucher_number'] . " - " . $voucher['supplier_name'] . "',
            reference_type = 'payment_voucher',
            reference_id = '" . (int)$voucher['voucher_id'] . "',
            total_amount = '" . (float)$voucher['amount'] . "',
            created_by = '" . (int)$this->user->getId() . "',
            is_auto_generated = 1,
            created_date = NOW()
        ");

        $journal_id = $this->db->getLastId();

        // تحديد الحساب النقدي/البنكي
        $cash_bank_account = $voucher['cash_account_id'] ?: $voucher['bank_account_id'];

        // دائن: النقدية/البنك
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "journal_entry_details SET
            journal_id = '" . (int)$journal_id . "',
            account_id = '" . (int)$cash_bank_account . "',
            debit_amount = 0,
            credit_amount = '" . (float)$voucher['amount'] . "',
            description = 'دفع نقدي للمورد'
        ");

        // مدين: حساب المورد أو حسابات المصروفات
        if ($voucher['supplier_id']) {
            $supplier_account = $this->getSupplierAccount($voucher['supplier_id']);
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "journal_entry_details SET
                journal_id = '" . (int)$journal_id . "',
                account_id = '" . (int)$supplier_account . "',
                debit_amount = '" . (float)$voucher['amount'] . "',
                credit_amount = 0,
                description = 'دفع للمورد: " . $voucher['supplier_name'] . "'
            ");
        } else {
            // إدراج بنود المصروفات
            $expense_items = $this->getVoucherExpenseItems($voucher['voucher_id']);
            foreach ($expense_items as $item) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "journal_entry_details SET
                    journal_id = '" . (int)$journal_id . "',
                    account_id = '" . (int)$item['account_id'] . "',
                    debit_amount = '" . (float)$item['amount'] . "',
                    credit_amount = 0,
                    description = '" . $this->db->escape($item['description']) . "'
                ");
            }
        }

        return $journal_id;
    }

    /**
     * تحديث رصيد المورد
     */
    private function updateSupplierBalance($supplier_id, $amount) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "supplier SET
            balance = balance - '" . (float)$amount . "'
            WHERE supplier_id = '" . (int)$supplier_id . "'
        ");
    }

    /**
     * الحصول على حساب المورد
     */
    private function getSupplierAccount($supplier_id) {
        $query = $this->db->query("
            SELECT account_id
            FROM " . DB_PREFIX . "supplier_accounts
            WHERE supplier_id = '" . (int)$supplier_id . "'
        ");

        if ($query->num_rows) {
            return $query->row['account_id'];
        }

        return $this->getDefaultSupplierAccount();
    }

    /**
     * الحصول على الحساب الافتراضي للموردين
     */
    private function getDefaultSupplierAccount() {
        $query = $this->db->query("
            SELECT account_id
            FROM " . DB_PREFIX . "chart_accounts
            WHERE account_code = '2110'
            OR account_name LIKE '%موردين%'
            OR account_name LIKE '%suppliers%'
            LIMIT 1
        ");

        return $query->num_rows ? $query->row['account_id'] : 1;
    }

    /**
     * إضافة تخصيص الفواتير
     */
    private function addBillAllocations($voucher_id, $allocations) {
        foreach ($allocations as $allocation) {
            if ($allocation['amount'] > 0) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "payment_voucher_allocations SET
                    voucher_id = '" . (int)$voucher_id . "',
                    bill_id = '" . (int)$allocation['bill_id'] . "',
                    allocated_amount = '" . (float)$allocation['amount'] . "'
                ");
            }
        }
    }

    /**
     * إضافة بنود المصروفات
     */
    private function addExpenseItems($voucher_id, $items) {
        foreach ($items as $item) {
            if ($item['amount'] > 0) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "payment_voucher_items SET
                    voucher_id = '" . (int)$voucher_id . "',
                    account_id = '" . (int)$item['account_id'] . "',
                    description = '" . $this->db->escape($item['description']) . "',
                    amount = '" . (float)$item['amount'] . "',
                    sort_order = '" . (int)$item['sort_order'] . "'
                ");
            }
        }
    }

    /**
     * توليد رقم السند
     */
    private function generateVoucherNumber() {
        $year = date('Y');
        $month = date('m');

        $query = $this->db->query("
            SELECT MAX(CAST(SUBSTRING(voucher_number, -6) AS UNSIGNED)) as max_number
            FROM " . DB_PREFIX . "payment_vouchers
            WHERE voucher_number LIKE 'PV-{$year}{$month}-%'
        ");

        $next_number = ($query->row['max_number'] ?? 0) + 1;

        return 'PV-' . $year . $month . '-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * الحصول على سند صرف
     */
    public function getPaymentVoucher($voucher_id) {
        $query = $this->db->query("
            SELECT pv.*, s.name as supplier_name, s.email as supplier_email,
                   curr.title as currency_title, curr.code as currency_code,
                   ca.account_name as cash_account_name,
                   ba.account_name as bank_account_name,
                   u1.firstname as created_by_name,
                   u2.firstname as approved_by_name,
                   u3.firstname as posted_by_name
            FROM " . DB_PREFIX . "payment_vouchers pv
            LEFT JOIN " . DB_PREFIX . "supplier s ON pv.supplier_id = s.supplier_id
            LEFT JOIN " . DB_PREFIX . "currency curr ON pv.currency_id = curr.currency_id
            LEFT JOIN " . DB_PREFIX . "chart_accounts ca ON pv.cash_account_id = ca.account_id
            LEFT JOIN " . DB_PREFIX . "chart_accounts ba ON pv.bank_account_id = ba.account_id
            LEFT JOIN " . DB_PREFIX . "user u1 ON pv.created_by = u1.user_id
            LEFT JOIN " . DB_PREFIX . "user u2 ON pv.approved_by = u2.user_id
            LEFT JOIN " . DB_PREFIX . "user u3 ON pv.posted_by = u3.user_id
            WHERE pv.voucher_id = '" . (int)$voucher_id . "'
        ");

        if ($query->num_rows) {
            $voucher = $query->row;

            // الحصول على التخصيصات والبنود
            $voucher['allocations'] = $this->getVoucherAllocations($voucher_id);
            $voucher['expense_items'] = $this->getVoucherExpenseItems($voucher_id);

            return $voucher;
        }

        return false;
    }

    /**
     * الحصول على تخصيص الفواتير للسند
     */
    public function getVoucherAllocations($voucher_id) {
        $query = $this->db->query("
            SELECT pva.*, b.bill_number, b.total_amount as bill_total
            FROM " . DB_PREFIX . "payment_voucher_allocations pva
            LEFT JOIN " . DB_PREFIX . "bills b ON pva.bill_id = b.bill_id
            WHERE pva.voucher_id = '" . (int)$voucher_id . "'
        ");

        return $query->rows;
    }

    /**
     * الحصول على بنود المصروفات للسند
     */
    public function getVoucherExpenseItems($voucher_id) {
        $query = $this->db->query("
            SELECT pvi.*, a.account_code, ad.name as account_name
            FROM " . DB_PREFIX . "payment_voucher_items pvi
            LEFT JOIN " . DB_PREFIX . "chart_accounts a ON pvi.account_id = a.account_id
            LEFT JOIN " . DB_PREFIX . "account_description ad ON a.account_id = ad.account_id
            WHERE pvi.voucher_id = '" . (int)$voucher_id . "'
            AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY pvi.sort_order
        ");

        return $query->rows;
    }

    /**
     * الحصول على قائمة سندات الصرف
     */
    public function getPaymentVouchers($data = array()) {
        $sql = "
            SELECT pv.*, s.name as supplier_name,
                   curr.code as currency_code,
                   CASE
                       WHEN pv.status = 'draft' THEN 'مسودة'
                       WHEN pv.status = 'approved' THEN 'معتمد'
                       WHEN pv.status = 'posted' THEN 'مرحل'
                       ELSE 'غير محدد'
                   END as status_name
            FROM " . DB_PREFIX . "payment_vouchers pv
            LEFT JOIN " . DB_PREFIX . "supplier s ON pv.supplier_id = s.supplier_id
            LEFT JOIN " . DB_PREFIX . "currency curr ON pv.currency_id = curr.currency_id
            WHERE 1=1
        ";

        // فلاتر البحث
        if (!empty($data['filter_voucher_number'])) {
            $sql .= " AND pv.voucher_number LIKE '%" . $this->db->escape($data['filter_voucher_number']) . "%'";
        }

        if (!empty($data['filter_supplier'])) {
            $sql .= " AND s.name LIKE '%" . $this->db->escape($data['filter_supplier']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND pv.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pv.voucher_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pv.voucher_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // الترتيب
        $sort_data = array(
            'pv.voucher_number',
            'supplier_name',
            'pv.amount',
            'pv.voucher_date',
            'pv.status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pv.voucher_date";
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
     * الحصول على إجمالي سندات الصرف
     */
    public function getTotalPaymentVouchers($data = array()) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "payment_vouchers pv
            LEFT JOIN " . DB_PREFIX . "supplier s ON pv.supplier_id = s.supplier_id
            WHERE 1=1
        ";

        // نفس فلاتر البحث
        if (!empty($data['filter_voucher_number'])) {
            $sql .= " AND pv.voucher_number LIKE '%" . $this->db->escape($data['filter_voucher_number']) . "%'";
        }

        if (!empty($data['filter_supplier'])) {
            $sql .= " AND s.name LIKE '%" . $this->db->escape($data['filter_supplier']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND pv.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pv.voucher_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pv.voucher_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على قائمة الموردين
     */
    public function getSuppliers() {
        $query = $this->db->query("
            SELECT supplier_id, name, email
            FROM " . DB_PREFIX . "supplier
            WHERE status = '1'
            ORDER BY name
        ");

        return $query->rows;
    }

    /**
     * الحصول على قائمة العملات
     */
    public function getCurrencies() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency WHERE status = '1' ORDER BY title");
        return $query->rows;
    }

    /**
     * الحصول على قائمة الحسابات البنكية
     */
    public function getBankAccounts() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "bank_account WHERE status = '1' ORDER BY account_name");
        return $query->rows;
    }

    /**
     * الحصول على قائمة الصناديق
     */
    public function getCashAccounts() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cash WHERE status = '1' ORDER BY name");
        return $query->rows;
    }

    /**
     * الحصول على قائمة حسابات المصروفات
     */
    public function getExpenseAccounts() {
        $query = $this->db->query("
            SELECT a.account_id, a.account_code, ad.name
            FROM " . DB_PREFIX . "chart_accounts a
            LEFT JOIN " . DB_PREFIX . "account_description ad ON a.account_id = ad.account_id
            WHERE a.account_type = 'expense' AND a.status = '1'
            AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY a.account_code
        ");
        return $query->rows;
    }

    /**
     * الحصول على فواتير المورد غير المدفوعة
     */
    public function getSupplierUnpaidBills($supplier_id) {
        $query = $this->db->query("
            SELECT b.bill_id, b.bill_number, b.bill_date, b.total_amount,
                   COALESCE(SUM(pva.allocated_amount), 0) as paid_amount,
                   (b.total_amount - COALESCE(SUM(pva.allocated_amount), 0)) as remaining_amount
            FROM " . DB_PREFIX . "bills b
            LEFT JOIN " . DB_PREFIX . "payment_voucher_allocations pva ON b.bill_id = pva.bill_id
            WHERE b.supplier_id = '" . (int)$supplier_id . "'
            AND b.status = 'confirmed'
            GROUP BY b.bill_id
            HAVING remaining_amount > 0
            ORDER BY b.bill_date
        ");

        return $query->rows;
    }

    /**
     * البحث المتقدم في سندات الصرف
     */
    public function searchPaymentVouchers($filter_data) {
        $sql = "
            SELECT pv.*, s.name as supplier_name, s.email as supplier_email,
                   curr.title as currency_title, curr.code as currency_code,
                   ca.account_name as cash_account_name,
                   ba.account_name as bank_account_name
            FROM " . DB_PREFIX . "payment_vouchers pv
            LEFT JOIN " . DB_PREFIX . "supplier s ON pv.supplier_id = s.supplier_id
            LEFT JOIN " . DB_PREFIX . "currency curr ON pv.currency_id = curr.currency_id
            LEFT JOIN " . DB_PREFIX . "chart_accounts ca ON pv.cash_account_id = ca.account_id
            LEFT JOIN " . DB_PREFIX . "chart_accounts ba ON pv.bank_account_id = ba.account_id
            WHERE 1=1
        ";

        // تطبيق الفلاتر
        if (!empty($filter_data['voucher_number'])) {
            $sql .= " AND pv.voucher_number LIKE '%" . $this->db->escape($filter_data['voucher_number']) . "%'";
        }

        if (!empty($filter_data['supplier_id'])) {
            $sql .= " AND pv.supplier_id = '" . (int)$filter_data['supplier_id'] . "'";
        }

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND pv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND pv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        if (!empty($filter_data['amount_from'])) {
            $sql .= " AND pv.amount >= '" . (float)$filter_data['amount_from'] . "'";
        }

        if (!empty($filter_data['amount_to'])) {
            $sql .= " AND pv.amount <= '" . (float)$filter_data['amount_to'] . "'";
        }

        if (!empty($filter_data['status'])) {
            $sql .= " AND pv.status = '" . $this->db->escape($filter_data['status']) . "'";
        }

        if (!empty($filter_data['payment_method'])) {
            $sql .= " AND pv.payment_method = '" . $this->db->escape($filter_data['payment_method']) . "'";
        }

        if (!empty($filter_data['is_approved'])) {
            $sql .= " AND pv.is_approved = '" . (int)$filter_data['is_approved'] . "'";
        }

        if (!empty($filter_data['is_posted'])) {
            $sql .= " AND pv.is_posted = '" . (int)$filter_data['is_posted'] . "'";
        }

        // الترتيب
        $sort_data = array(
            'voucher_number',
            'voucher_date',
            'supplier_name',
            'amount',
            'status',
            'payment_method'
        );

        if (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $filter_data['sort'];
        } else {
            $sql .= " ORDER BY pv.voucher_date";
        }

        if (isset($filter_data['order']) && ($filter_data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        // التصفح
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
     * عدد نتائج البحث المتقدم
     */
    public function getTotalSearchResults($filter_data) {
        $sql = "
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "payment_vouchers pv
            LEFT JOIN " . DB_PREFIX . "supplier s ON pv.supplier_id = s.supplier_id
            WHERE 1=1
        ";

        // تطبيق نفس الفلاتر
        if (!empty($filter_data['voucher_number'])) {
            $sql .= " AND pv.voucher_number LIKE '%" . $this->db->escape($filter_data['voucher_number']) . "%'";
        }

        if (!empty($filter_data['supplier_id'])) {
            $sql .= " AND pv.supplier_id = '" . (int)$filter_data['supplier_id'] . "'";
        }

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND pv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND pv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        if (!empty($filter_data['amount_from'])) {
            $sql .= " AND pv.amount >= '" . (float)$filter_data['amount_from'] . "'";
        }

        if (!empty($filter_data['amount_to'])) {
            $sql .= " AND pv.amount <= '" . (float)$filter_data['amount_to'] . "'";
        }

        if (!empty($filter_data['status'])) {
            $sql .= " AND pv.status = '" . $this->db->escape($filter_data['status']) . "'";
        }

        if (!empty($filter_data['payment_method'])) {
            $sql .= " AND pv.payment_method = '" . $this->db->escape($filter_data['payment_method']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * تقارير سندات الصرف
     */
    public function getPaymentVoucherReports($filter_data) {
        $reports = array();

        // تقرير إجمالي المدفوعات
        $sql = "
            SELECT
                COUNT(*) as total_vouchers,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount
            FROM " . DB_PREFIX . "payment_vouchers pv
            WHERE pv.is_posted = 1
        ";

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND pv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND pv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        $query = $this->db->query($sql);
        $reports['summary'] = $query->row;

        // تقرير حسب طريقة الدفع
        $sql = "
            SELECT
                payment_method,
                COUNT(*) as voucher_count,
                SUM(amount) as total_amount
            FROM " . DB_PREFIX . "payment_vouchers pv
            WHERE pv.is_posted = 1
        ";

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND pv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND pv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        $sql .= " GROUP BY payment_method ORDER BY total_amount DESC";

        $query = $this->db->query($sql);
        $reports['by_payment_method'] = $query->rows;

        // تقرير حسب الموردين
        $sql = "
            SELECT
                s.supplier_id,
                s.name as supplier_name,
                COUNT(*) as voucher_count,
                SUM(pv.amount) as total_amount
            FROM " . DB_PREFIX . "payment_vouchers pv
            LEFT JOIN " . DB_PREFIX . "supplier s ON pv.supplier_id = s.supplier_id
            WHERE pv.is_posted = 1
        ";

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND pv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND pv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        $sql .= " GROUP BY s.supplier_id ORDER BY total_amount DESC LIMIT 10";

        $query = $this->db->query($sql);
        $reports['top_suppliers'] = $query->rows;

        // تقرير يومي
        $sql = "
            SELECT
                DATE(voucher_date) as date,
                COUNT(*) as voucher_count,
                SUM(amount) as total_amount
            FROM " . DB_PREFIX . "payment_vouchers pv
            WHERE pv.is_posted = 1
        ";

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND pv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND pv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        $sql .= " GROUP BY DATE(voucher_date) ORDER BY date DESC";

        $query = $this->db->query($sql);
        $reports['daily'] = $query->rows;

        return $reports;
    }

    /**
     * نسخ سند صرف
     */
    public function duplicatePaymentVoucher($voucher_id) {
        $voucher = $this->getPaymentVoucher($voucher_id);

        if (!$voucher) {
            throw new Exception('السند غير موجود');
        }

        // إعداد بيانات السند الجديد
        $new_data = array(
            'voucher_date' => date('Y-m-d'),
            'supplier_id' => $voucher['supplier_id'],
            'amount' => $voucher['amount'],
            'payment_method' => $voucher['payment_method'],
            'cash_account_id' => $voucher['cash_account_id'],
            'bank_account_id' => $voucher['bank_account_id'],
            'check_number' => '',
            'check_date' => null,
            'bank_name' => $voucher['bank_name'],
            'reference_number' => '',
            'notes' => 'نسخة من السند رقم: ' . $voucher['voucher_number'],
            'bill_allocations' => array(),
            'expense_items' => array()
        );

        return $this->addPaymentVoucher($new_data);
    }

    /**
     * عكس سند صرف
     */
    public function reversePaymentVoucher($voucher_id, $reason = '') {
        $voucher = $this->getPaymentVoucher($voucher_id);

        if (!$voucher) {
            throw new Exception('السند غير موجود');
        }

        if (!$voucher['is_posted']) {
            throw new Exception('لا يمكن عكس سند غير مرحل');
        }

        // إنشاء سند عكسي
        $reverse_data = array(
            'voucher_date' => date('Y-m-d'),
            'supplier_id' => $voucher['supplier_id'],
            'amount' => -$voucher['amount'], // مبلغ سالب
            'payment_method' => $voucher['payment_method'],
            'cash_account_id' => $voucher['cash_account_id'],
            'bank_account_id' => $voucher['bank_account_id'],
            'check_number' => '',
            'check_date' => null,
            'bank_name' => $voucher['bank_name'],
            'reference_number' => 'REV-' . $voucher['voucher_number'],
            'notes' => 'عكس السند رقم: ' . $voucher['voucher_number'] .
                      ($reason ? ' - السبب: ' . $reason : ''),
            'bill_allocations' => array(),
            'expense_items' => array()
        );

        $reverse_voucher_id = $this->addPaymentVoucher($reverse_data);

        // اعتماد وترحيل السند العكسي تلقائياً
        $this->approvePaymentVoucher($reverse_voucher_id);
        $this->postPaymentVoucher($reverse_voucher_id);

        // تحديث السند الأصلي
        $this->db->query("
            UPDATE " . DB_PREFIX . "payment_vouchers SET
            is_reversed = 1,
            reversed_by = '" . (int)$this->user->getId() . "',
            reversed_date = NOW(),
            reverse_voucher_id = '" . (int)$reverse_voucher_id . "'
            WHERE voucher_id = '" . (int)$voucher_id . "'
        ");

        return $reverse_voucher_id;
    }

    /**
     * تصدير سندات الصرف
     */
    public function exportPaymentVouchers($filter_data, $format = 'csv') {
        $vouchers = $this->searchPaymentVouchers($filter_data);

        if ($format == 'csv') {
            return $this->exportToCSV($vouchers);
        } elseif ($format == 'excel') {
            return $this->exportToExcel($vouchers);
        } elseif ($format == 'pdf') {
            return $this->exportToPDF($vouchers);
        }

        throw new Exception('تنسيق التصدير غير مدعوم');
    }

    /**
     * تصدير إلى CSV
     */
    private function exportToCSV($vouchers) {
        $csv_data = "رقم السند,التاريخ,المورد,المبلغ,طريقة الدفع,الحالة\n";

        foreach ($vouchers as $voucher) {
            $csv_data .= '"' . $voucher['voucher_number'] . '",';
            $csv_data .= '"' . $voucher['voucher_date'] . '",';
            $csv_data .= '"' . $voucher['supplier_name'] . '",';
            $csv_data .= '"' . $voucher['amount'] . '",';
            $csv_data .= '"' . $voucher['payment_method'] . '",';
            $csv_data .= '"' . $voucher['status'] . '"' . "\n";
        }

        return $csv_data;
    }

    /**
     * الحصول على إحصائيات لوحة التحكم
     */
    public function getDashboardStatistics() {
        $stats = array();

        // مدفوعات اليوم
        $query = $this->db->query("
            SELECT COUNT(*) as count, SUM(amount) as total
            FROM " . DB_PREFIX . "payment_vouchers
            WHERE DATE(voucher_date) = CURDATE()
            AND is_posted = 1
        ");
        $stats['today'] = $query->row;

        // مدفوعات الشهر
        $query = $this->db->query("
            SELECT COUNT(*) as count, SUM(amount) as total
            FROM " . DB_PREFIX . "payment_vouchers
            WHERE YEAR(voucher_date) = YEAR(CURDATE())
            AND MONTH(voucher_date) = MONTH(CURDATE())
            AND is_posted = 1
        ");
        $stats['month'] = $query->row;

        // في انتظار الاعتماد
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "payment_vouchers
            WHERE is_approved = 0
            AND status = 'draft'
        ");
        $stats['pending_approval'] = $query->row['count'];

        // في انتظار الترحيل
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "payment_vouchers
            WHERE is_approved = 1
            AND is_posted = 0
        ");
        $stats['pending_posting'] = $query->row['count'];

        return $stats;
    }

    /**
     * الحصول على الموظفين
     */
    public function getEmployees() {
        $query = $this->db->query("
            SELECT employee_id, CONCAT(firstname, ' ', lastname) as name
            FROM " . DB_PREFIX . "employee
            WHERE status = 1
            ORDER BY firstname, lastname
        ");

        return $query->rows;
    }

    /**
     * الحصول على رصيد المورد
     */
    public function getSupplierBalance($supplier_id) {
        $query = $this->db->query("
            SELECT balance
            FROM " . DB_PREFIX . "supplier
            WHERE supplier_id = '" . (int)$supplier_id . "'
        ");

        return $query->num_rows ? (float)$query->row['balance'] : 0;
    }
}
