<?php
/**
 * نموذج سندات القبض المتقدمة
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ModelFinanceReceiptVoucher extends Model {

    /**
     * إضافة سند قبض جديد
     */
    public function addReceiptVoucher($data) {
        // إنشاء رقم السند
        $voucher_number = $this->generateVoucherNumber();

        // إدراج السند الرئيسي
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "receipt_vouchers SET
            voucher_number = '" . $this->db->escape($voucher_number) . "',
            voucher_date = '" . $this->db->escape($data['voucher_date']) . "',
            customer_id = '" . (int)$data['customer_id'] . "',
            amount = '" . (float)$data['amount'] . "',
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
        if (!empty($data['invoice_allocations'])) {
            $this->addInvoiceAllocations($voucher_id, $data['invoice_allocations']);
        }

        return $voucher_id;
    }

    /**
     * تعديل سند قبض
     */
    public function editReceiptVoucher($voucher_id, $data) {
        // تحديث السند الرئيسي
        $this->db->query("
            UPDATE " . DB_PREFIX . "receipt_vouchers SET
            voucher_date = '" . $this->db->escape($data['voucher_date']) . "',
            customer_id = '" . (int)$data['customer_id'] . "',
            amount = '" . (float)$data['amount'] . "',
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

        // حذف التخصيصات القديمة
        $this->db->query("DELETE FROM " . DB_PREFIX . "receipt_voucher_allocations WHERE voucher_id = '" . (int)$voucher_id . "'");

        // إدراج التخصيصات الجديدة
        if (!empty($data['invoice_allocations'])) {
            $this->addInvoiceAllocations($voucher_id, $data['invoice_allocations']);
        }

        return true;
    }

    /**
     * حذف سند قبض
     */
    public function deleteReceiptVoucher($voucher_id) {
        // حذف التخصيصات
        $this->db->query("DELETE FROM " . DB_PREFIX . "receipt_voucher_allocations WHERE voucher_id = '" . (int)$voucher_id . "'");

        // حذف السند
        $this->db->query("DELETE FROM " . DB_PREFIX . "receipt_vouchers WHERE voucher_id = '" . (int)$voucher_id . "'");

        return true;
    }

    /**
     * اعتماد سند قبض
     */
    public function approveReceiptVoucher($voucher_id) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "receipt_vouchers SET
            is_approved = 1,
            approved_by = '" . (int)$this->user->getId() . "',
            approved_date = NOW(),
            status = 'approved'
            WHERE voucher_id = '" . (int)$voucher_id . "'
        ");

        return $this->db->countAffected() > 0;
    }

    /**
     * ترحيل سند قبض
     */
    public function postReceiptVoucher($voucher_id) {
        $voucher = $this->getReceiptVoucher($voucher_id);

        if (!$voucher) {
            throw new Exception('سند القبض غير موجود');
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
            UPDATE " . DB_PREFIX . "receipt_vouchers SET
            is_posted = 1,
            posted_by = '" . (int)$this->user->getId() . "',
            posted_date = NOW(),
            journal_id = '" . (int)$journal_id . "',
            status = 'posted'
            WHERE voucher_id = '" . (int)$voucher_id . "'
        ");

        // تحديث أرصدة العملاء
        $this->updateCustomerBalance($voucher['customer_id'], $voucher['amount']);

        return array(
            'journal_id' => $journal_id,
            'voucher_id' => $voucher_id
        );
    }

    /**
     * إنشاء القيد المحاسبي
     */
    private function createJournalEntry($voucher) {
        // تحديد الحسابات
        $cash_bank_account = $voucher['cash_account_id'] ?: $voucher['bank_account_id'];
        $customer_account = $this->getCustomerAccount($voucher['customer_id']);

        // إنشاء القيد الرئيسي
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "journal_entries SET
            journal_date = '" . $this->db->escape($voucher['voucher_date']) . "',
            description = 'سند قبض رقم: " . $voucher['voucher_number'] . " - " . $voucher['customer_name'] . "',
            reference_type = 'receipt_voucher',
            reference_id = '" . (int)$voucher['voucher_id'] . "',
            total_amount = '" . (float)$voucher['amount'] . "',
            created_by = '" . (int)$this->user->getId() . "',
            is_auto_generated = 1,
            created_date = NOW()
        ");

        $journal_id = $this->db->getLastId();

        // إدراج تفاصيل القيد
        // مدين: النقدية/البنك
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "journal_entry_details SET
            journal_id = '" . (int)$journal_id . "',
            account_id = '" . (int)$cash_bank_account . "',
            debit_amount = '" . (float)$voucher['amount'] . "',
            credit_amount = 0,
            description = 'استلام نقدي من العميل'
        ");

        // دائن: حساب العميل
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "journal_entry_details SET
            journal_id = '" . (int)$journal_id . "',
            account_id = '" . (int)$customer_account . "',
            debit_amount = 0,
            credit_amount = '" . (float)$voucher['amount'] . "',
            description = 'تحصيل من العميل: " . $voucher['customer_name'] . "'
        ");

        return $journal_id;
    }

    /**
     * تحديث رصيد العميل
     */
    private function updateCustomerBalance($customer_id, $amount) {
        // تقليل رصيد العميل المدين
        $this->db->query("
            UPDATE " . DB_PREFIX . "customer SET
            balance = balance - '" . (float)$amount . "'
            WHERE customer_id = '" . (int)$customer_id . "'
        ");
    }

    /**
     * الحصول على حساب العميل
     */
    private function getCustomerAccount($customer_id) {
        $query = $this->db->query("
            SELECT account_id
            FROM " . DB_PREFIX . "customer_accounts
            WHERE customer_id = '" . (int)$customer_id . "'
        ");

        if ($query->num_rows) {
            return $query->row['account_id'];
        }

        // حساب افتراضي للعملاء
        return $this->getDefaultCustomerAccount();
    }

    /**
     * الحصول على الحساب الافتراضي للعملاء
     */
    private function getDefaultCustomerAccount() {
        $query = $this->db->query("
            SELECT account_id
            FROM " . DB_PREFIX . "chart_accounts
            WHERE account_code = '1210'
            OR account_name LIKE '%عملاء%'
            OR account_name LIKE '%customers%'
            LIMIT 1
        ");

        return $query->num_rows ? $query->row['account_id'] : 1;
    }

    /**
     * إضافة تخصيص الفواتير
     */
    private function addInvoiceAllocations($voucher_id, $allocations) {
        foreach ($allocations as $allocation) {
            if ($allocation['amount'] > 0) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "receipt_voucher_allocations SET
                    voucher_id = '" . (int)$voucher_id . "',
                    invoice_id = '" . (int)$allocation['invoice_id'] . "',
                    allocated_amount = '" . (float)$allocation['amount'] . "'
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
            FROM " . DB_PREFIX . "receipt_vouchers
            WHERE voucher_number LIKE 'RV-{$year}{$month}-%'
        ");

        $next_number = ($query->row['max_number'] ?? 0) + 1;

        return 'RV-' . $year . $month . '-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * الحصول على سند قبض
     */
    public function getReceiptVoucher($voucher_id) {
        $query = $this->db->query("
            SELECT rv.*, c.firstname, c.lastname,
                   CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                   ca.account_name as cash_account_name,
                   ba.account_name as bank_account_name,
                   u1.firstname as created_by_name,
                   u2.firstname as approved_by_name,
                   u3.firstname as posted_by_name
            FROM " . DB_PREFIX . "receipt_vouchers rv
            LEFT JOIN " . DB_PREFIX . "customer c ON rv.customer_id = c.customer_id
            LEFT JOIN " . DB_PREFIX . "chart_accounts ca ON rv.cash_account_id = ca.account_id
            LEFT JOIN " . DB_PREFIX . "chart_accounts ba ON rv.bank_account_id = ba.account_id
            LEFT JOIN " . DB_PREFIX . "user u1 ON rv.created_by = u1.user_id
            LEFT JOIN " . DB_PREFIX . "user u2 ON rv.approved_by = u2.user_id
            LEFT JOIN " . DB_PREFIX . "user u3 ON rv.posted_by = u3.user_id
            WHERE rv.voucher_id = '" . (int)$voucher_id . "'
        ");

        if ($query->num_rows) {
            $voucher = $query->row;

            // الحصول على تخصيص الفواتير
            $voucher['allocations'] = $this->getVoucherAllocations($voucher_id);

            return $voucher;
        }

        return false;
    }

    /**
     * الحصول على تخصيص الفواتير للسند
     */
    public function getVoucherAllocations($voucher_id) {
        $query = $this->db->query("
            SELECT rva.*, i.invoice_number, i.total_amount as invoice_total
            FROM " . DB_PREFIX . "receipt_voucher_allocations rva
            LEFT JOIN " . DB_PREFIX . "invoices i ON rva.invoice_id = i.invoice_id
            WHERE rva.voucher_id = '" . (int)$voucher_id . "'
        ");

        return $query->rows;
    }

    /**
     * الحصول على قائمة سندات القبض
     */
    public function getReceiptVouchers($data = array()) {
        $sql = "
            SELECT rv.*, c.firstname, c.lastname,
                   CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                   CASE
                       WHEN rv.status = 'draft' THEN 'مسودة'
                       WHEN rv.status = 'approved' THEN 'معتمد'
                       WHEN rv.status = 'posted' THEN 'مرحل'
                       ELSE 'غير محدد'
                   END as status_name
            FROM " . DB_PREFIX . "receipt_vouchers rv
            LEFT JOIN " . DB_PREFIX . "customer c ON rv.customer_id = c.customer_id
        ";

        $sort_data = array(
            'voucher_number',
            'voucher_date',
            'customer_name',
            'amount',
            'status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY voucher_date";
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
     * الحصول على إجمالي سندات القبض
     */
    public function getTotalReceiptVouchers() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "receipt_vouchers");

        return $query->row['total'];
    }

    /**
     * الحصول على رصيد العميل
     */
    public function getCustomerBalance($customer_id) {
        $query = $this->db->query("
            SELECT balance
            FROM " . DB_PREFIX . "customer
            WHERE customer_id = '" . (int)$customer_id . "'
        ");

        return $query->num_rows ? (float)$query->row['balance'] : 0;
    }

    /**
     * الحصول على فواتير العميل غير المدفوعة
     */
    public function getCustomerUnpaidInvoices($customer_id) {
        $query = $this->db->query("
            SELECT i.invoice_id, i.invoice_number, i.invoice_date, i.total_amount,
                   COALESCE(SUM(rva.allocated_amount), 0) as paid_amount,
                   (i.total_amount - COALESCE(SUM(rva.allocated_amount), 0)) as remaining_amount
            FROM " . DB_PREFIX . "invoices i
            LEFT JOIN " . DB_PREFIX . "receipt_voucher_allocations rva ON i.invoice_id = rva.invoice_id
            WHERE i.customer_id = '" . (int)$customer_id . "'
            AND i.status = 'confirmed'
            GROUP BY i.invoice_id
            HAVING remaining_amount > 0
            ORDER BY i.invoice_date
        ");

        return $query->rows;
    }

    /**
     * البحث المتقدم في سندات القبض
     */
    public function searchReceiptVouchers($filter_data) {
        $sql = "
            SELECT rv.*, c.firstname, c.lastname,
                   CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                   ca.account_name as cash_account_name,
                   ba.account_name as bank_account_name
            FROM " . DB_PREFIX . "receipt_vouchers rv
            LEFT JOIN " . DB_PREFIX . "customer c ON rv.customer_id = c.customer_id
            LEFT JOIN " . DB_PREFIX . "chart_accounts ca ON rv.cash_account_id = ca.account_id
            LEFT JOIN " . DB_PREFIX . "chart_accounts ba ON rv.bank_account_id = ba.account_id
            WHERE 1=1
        ";

        // تطبيق الفلاتر
        if (!empty($filter_data['voucher_number'])) {
            $sql .= " AND rv.voucher_number LIKE '%" . $this->db->escape($filter_data['voucher_number']) . "%'";
        }

        if (!empty($filter_data['customer_id'])) {
            $sql .= " AND rv.customer_id = '" . (int)$filter_data['customer_id'] . "'";
        }

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND rv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND rv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        if (!empty($filter_data['amount_from'])) {
            $sql .= " AND rv.amount >= '" . (float)$filter_data['amount_from'] . "'";
        }

        if (!empty($filter_data['amount_to'])) {
            $sql .= " AND rv.amount <= '" . (float)$filter_data['amount_to'] . "'";
        }

        if (!empty($filter_data['status'])) {
            $sql .= " AND rv.status = '" . $this->db->escape($filter_data['status']) . "'";
        }

        if (!empty($filter_data['payment_method'])) {
            $sql .= " AND rv.payment_method = '" . $this->db->escape($filter_data['payment_method']) . "'";
        }

        if (!empty($filter_data['is_approved'])) {
            $sql .= " AND rv.is_approved = '" . (int)$filter_data['is_approved'] . "'";
        }

        if (!empty($filter_data['is_posted'])) {
            $sql .= " AND rv.is_posted = '" . (int)$filter_data['is_posted'] . "'";
        }

        // الترتيب
        $sort_data = array(
            'voucher_number',
            'voucher_date',
            'customer_name',
            'amount',
            'status',
            'payment_method'
        );

        if (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $filter_data['sort'];
        } else {
            $sql .= " ORDER BY rv.voucher_date";
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
            FROM " . DB_PREFIX . "receipt_vouchers rv
            LEFT JOIN " . DB_PREFIX . "customer c ON rv.customer_id = c.customer_id
            WHERE 1=1
        ";

        // تطبيق نفس الفلاتر
        if (!empty($filter_data['voucher_number'])) {
            $sql .= " AND rv.voucher_number LIKE '%" . $this->db->escape($filter_data['voucher_number']) . "%'";
        }

        if (!empty($filter_data['customer_id'])) {
            $sql .= " AND rv.customer_id = '" . (int)$filter_data['customer_id'] . "'";
        }

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND rv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND rv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        if (!empty($filter_data['amount_from'])) {
            $sql .= " AND rv.amount >= '" . (float)$filter_data['amount_from'] . "'";
        }

        if (!empty($filter_data['amount_to'])) {
            $sql .= " AND rv.amount <= '" . (float)$filter_data['amount_to'] . "'";
        }

        if (!empty($filter_data['status'])) {
            $sql .= " AND rv.status = '" . $this->db->escape($filter_data['status']) . "'";
        }

        if (!empty($filter_data['payment_method'])) {
            $sql .= " AND rv.payment_method = '" . $this->db->escape($filter_data['payment_method']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * تقارير سندات القبض
     */
    public function getReceiptVoucherReports($filter_data) {
        $reports = array();

        // تقرير إجمالي المقبوضات
        $sql = "
            SELECT
                COUNT(*) as total_vouchers,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount
            FROM " . DB_PREFIX . "receipt_vouchers rv
            WHERE rv.is_posted = 1
        ";

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND rv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND rv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        $query = $this->db->query($sql);
        $reports['summary'] = $query->row;

        // تقرير حسب طريقة الدفع
        $sql = "
            SELECT
                payment_method,
                COUNT(*) as voucher_count,
                SUM(amount) as total_amount
            FROM " . DB_PREFIX . "receipt_vouchers rv
            WHERE rv.is_posted = 1
        ";

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND rv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND rv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        $sql .= " GROUP BY payment_method ORDER BY total_amount DESC";

        $query = $this->db->query($sql);
        $reports['by_payment_method'] = $query->rows;

        // تقرير حسب العملاء
        $sql = "
            SELECT
                c.customer_id,
                CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                COUNT(*) as voucher_count,
                SUM(rv.amount) as total_amount
            FROM " . DB_PREFIX . "receipt_vouchers rv
            LEFT JOIN " . DB_PREFIX . "customer c ON rv.customer_id = c.customer_id
            WHERE rv.is_posted = 1
        ";

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND rv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND rv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        $sql .= " GROUP BY c.customer_id ORDER BY total_amount DESC LIMIT 10";

        $query = $this->db->query($sql);
        $reports['top_customers'] = $query->rows;

        // تقرير يومي
        $sql = "
            SELECT
                DATE(voucher_date) as date,
                COUNT(*) as voucher_count,
                SUM(amount) as total_amount
            FROM " . DB_PREFIX . "receipt_vouchers rv
            WHERE rv.is_posted = 1
        ";

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND rv.voucher_date >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND rv.voucher_date <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        $sql .= " GROUP BY DATE(voucher_date) ORDER BY date DESC";

        $query = $this->db->query($sql);
        $reports['daily'] = $query->rows;

        return $reports;
    }

    /**
     * نسخ سند قبض
     */
    public function duplicateReceiptVoucher($voucher_id) {
        $voucher = $this->getReceiptVoucher($voucher_id);

        if (!$voucher) {
            throw new Exception('السند غير موجود');
        }

        // إعداد بيانات السند الجديد
        $new_data = array(
            'voucher_date' => date('Y-m-d'),
            'customer_id' => $voucher['customer_id'],
            'amount' => $voucher['amount'],
            'payment_method' => $voucher['payment_method'],
            'cash_account_id' => $voucher['cash_account_id'],
            'bank_account_id' => $voucher['bank_account_id'],
            'check_number' => '',
            'check_date' => null,
            'bank_name' => $voucher['bank_name'],
            'reference_number' => '',
            'notes' => 'نسخة من السند رقم: ' . $voucher['voucher_number'],
            'invoice_allocations' => array()
        );

        return $this->addReceiptVoucher($new_data);
    }

    /**
     * عكس سند قبض
     */
    public function reverseReceiptVoucher($voucher_id, $reason = '') {
        $voucher = $this->getReceiptVoucher($voucher_id);

        if (!$voucher) {
            throw new Exception('السند غير موجود');
        }

        if (!$voucher['is_posted']) {
            throw new Exception('لا يمكن عكس سند غير مرحل');
        }

        // إنشاء سند عكسي
        $reverse_data = array(
            'voucher_date' => date('Y-m-d'),
            'customer_id' => $voucher['customer_id'],
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
            'invoice_allocations' => array()
        );

        $reverse_voucher_id = $this->addReceiptVoucher($reverse_data);

        // اعتماد وترحيل السند العكسي تلقائياً
        $this->approveReceiptVoucher($reverse_voucher_id);
        $this->postReceiptVoucher($reverse_voucher_id);

        // تحديث السند الأصلي
        $this->db->query("
            UPDATE " . DB_PREFIX . "receipt_vouchers SET
            is_reversed = 1,
            reversed_by = '" . (int)$this->user->getId() . "',
            reversed_date = NOW(),
            reverse_voucher_id = '" . (int)$reverse_voucher_id . "'
            WHERE voucher_id = '" . (int)$voucher_id . "'
        ");

        return $reverse_voucher_id;
    }

    /**
     * تصدير سندات القبض
     */
    public function exportReceiptVouchers($filter_data, $format = 'csv') {
        $vouchers = $this->searchReceiptVouchers($filter_data);

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
        $csv_data = "رقم السند,التاريخ,العميل,المبلغ,طريقة الدفع,الحالة\n";

        foreach ($vouchers as $voucher) {
            $csv_data .= '"' . $voucher['voucher_number'] . '",';
            $csv_data .= '"' . $voucher['voucher_date'] . '",';
            $csv_data .= '"' . $voucher['customer_name'] . '",';
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

        // مقبوضات اليوم
        $query = $this->db->query("
            SELECT COUNT(*) as count, SUM(amount) as total
            FROM " . DB_PREFIX . "receipt_vouchers
            WHERE DATE(voucher_date) = CURDATE()
            AND is_posted = 1
        ");
        $stats['today'] = $query->row;

        // مقبوضات الشهر
        $query = $this->db->query("
            SELECT COUNT(*) as count, SUM(amount) as total
            FROM " . DB_PREFIX . "receipt_vouchers
            WHERE YEAR(voucher_date) = YEAR(CURDATE())
            AND MONTH(voucher_date) = MONTH(CURDATE())
            AND is_posted = 1
        ");
        $stats['month'] = $query->row;

        // في انتظار الاعتماد
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "receipt_vouchers
            WHERE is_approved = 0
            AND status = 'draft'
        ");
        $stats['pending_approval'] = $query->row['count'];

        // في انتظار الترحيل
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "receipt_vouchers
            WHERE is_approved = 1
            AND is_posted = 0
        ");
        $stats['pending_posting'] = $query->row['count'];

        return $stats;
    }

    /**
     * الحصول على العملاء
     */
    public function getCustomers() {
        $query = $this->db->query("
            SELECT customer_id, CONCAT(firstname, ' ', lastname) as name
            FROM " . DB_PREFIX . "customer
            WHERE status = 1
            ORDER BY firstname, lastname
        ");

        return $query->rows;
    }

    /**
     * الحصول على حسابات النقدية
     */
    public function getCashAccounts() {
        $query = $this->db->query("
            SELECT cash_id, name
            FROM " . DB_PREFIX . "cash
            WHERE status = 1
            ORDER BY name
        ");

        return $query->rows;
    }

    /**
     * الحصول على حسابات البنوك
     */
    public function getBankAccounts() {
        $query = $this->db->query("
            SELECT account_id, account_name
            FROM " . DB_PREFIX . "bank_accounts
            WHERE status = 1
            ORDER BY account_name
        ");

        return $query->rows;
    }

    /**
     * الحصول على العملات
     */
    public function getCurrencies() {
        $query = $this->db->query("
            SELECT currency_id, title, code
            FROM " . DB_PREFIX . "currency
            WHERE status = 1
            ORDER BY title
        ");

        return $query->rows;
    }
}
