<?php
/**
 * نموذج مدفوعات الأقساط (Installment Payments Model)
 *
 * الهدف: إدارة مدفوعات أقساط العملاء في قاعدة البيانات
 * الميزات: CRUD operations، معالجة المدفوعات، التكامل المحاسبي، تتبع المتأخرات
 *
 * @author ERP Team
 * @version 2.0
 * @since 2024
 */

class ModelSaleInstallmentPayment extends Model {

    /**
     * إضافة مدفوعة جديدة
     */
    public function addPayment($data) {
        // التحقق من صحة خطة التقسيط
        $this->load->model('sale/installment_plan');
        $plan = $this->model_sale_installment_plan->getPlan($data['plan_id']);

        if (!$plan) {
            return false;
        }

        // حساب المبلغ الصافي
        $amount = (float)$data['amount'];
        $late_fee = (float)($data['late_fee'] ?? 0);
        $discount = (float)($data['discount'] ?? 0);
        $net_amount = $amount + $late_fee - $discount;

        // إنشاء رقم مرجعي
        $reference_number = $this->generateReferenceNumber();

        // إدراج المدفوعة
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "installment_payment SET
                plan_id = '" . (int)$data['plan_id'] . "',
                amount = '" . (float)$amount . "',
                late_fee = '" . (float)$late_fee . "',
                discount = '" . (float)$discount . "',
                net_amount = '" . (float)$net_amount . "',
                payment_method = '" . $this->db->escape($data['payment_method']) . "',
                payment_date = '" . $this->db->escape($data['payment_date']) . "',
                reference_number = '" . $this->db->escape($reference_number) . "',
                bank_reference = '" . $this->db->escape($data['bank_reference'] ?? '') . "',
                notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                received_by = '" . (int)$this->user->getId() . "',
                status = '" . $this->db->escape($data['status'] ?? 'confirmed') . "',
                date_created = NOW(),
                date_modified = NOW()
        ");

        $payment_id = $this->db->getLastId();

        // تحديث جدول الأقساط
        $this->updateInstallmentSchedule($data['plan_id'], $net_amount);

        // إنشاء القيود المحاسبية
        $this->createAccountingEntries($payment_id);

        return $payment_id;
    }

    /**
     * تعديل مدفوعة
     */
    public function editPayment($payment_id, $data) {
        // الحصول على المدفوعة الحالية
        $current_payment = $this->getPayment($payment_id);

        if (!$current_payment) {
            return false;
        }

        // حساب المبلغ الصافي الجديد
        $amount = (float)$data['amount'];
        $late_fee = (float)($data['late_fee'] ?? 0);
        $discount = (float)($data['discount'] ?? 0);
        $net_amount = $amount + $late_fee - $discount;

        // تحديث المدفوعة
        $this->db->query("
            UPDATE " . DB_PREFIX . "installment_payment SET
                amount = '" . (float)$amount . "',
                late_fee = '" . (float)$late_fee . "',
                discount = '" . (float)$discount . "',
                net_amount = '" . (float)$net_amount . "',
                payment_method = '" . $this->db->escape($data['payment_method']) . "',
                payment_date = '" . $this->db->escape($data['payment_date']) . "',
                bank_reference = '" . $this->db->escape($data['bank_reference'] ?? '') . "',
                notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                status = '" . $this->db->escape($data['status'] ?? 'confirmed') . "',
                date_modified = NOW()
            WHERE payment_id = '" . (int)$payment_id . "'
        ");

        // إذا تغير المبلغ، تحديث جدول الأقساط
        if ($net_amount != $current_payment['net_amount']) {
            $difference = $net_amount - $current_payment['net_amount'];
            $this->updateInstallmentSchedule($current_payment['plan_id'], $difference);
        }

        return true;
    }

    /**
     * حذف مدفوعة
     */
    public function deletePayment($payment_id) {
        $payment = $this->getPayment($payment_id);

        if (!$payment) {
            return false;
        }

        // عكس تأثير المدفوعة على جدول الأقساط
        $this->updateInstallmentSchedule($payment['plan_id'], -$payment['net_amount']);

        // حذف المدفوعة
        $this->db->query("DELETE FROM " . DB_PREFIX . "installment_payment WHERE payment_id = '" . (int)$payment_id . "'");

        return true;
    }

    /**
     * الحصول على مدفوعة
     */
    public function getPayment($payment_id) {
        $query = $this->db->query("
            SELECT
                p.*,
                pl.customer_id,
                CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                c.email as customer_email,
                c.telephone as customer_phone,
                u.firstname as received_by_name
            FROM " . DB_PREFIX . "installment_payment p
            LEFT JOIN " . DB_PREFIX . "installment_plan pl ON (p.plan_id = pl.plan_id)
            LEFT JOIN " . DB_PREFIX . "customer c ON (pl.customer_id = c.customer_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (p.received_by = u.user_id)
            WHERE p.payment_id = '" . (int)$payment_id . "'
        ");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على المدفوعات
     */
    public function getPayments($data = []) {
        $sql = "
            SELECT
                p.*,
                pl.customer_id,
                CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                c.email as customer_email,
                u.firstname as received_by_name
            FROM " . DB_PREFIX . "installment_payment p
            LEFT JOIN " . DB_PREFIX . "installment_plan pl ON (p.plan_id = pl.plan_id)
            LEFT JOIN " . DB_PREFIX . "customer c ON (pl.customer_id = c.customer_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (p.received_by = u.user_id)
            WHERE 1=1
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_payment_method'])) {
            $sql .= " AND p.payment_method = '" . $this->db->escape($data['filter_payment_method']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND p.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_amount_from'])) {
            $sql .= " AND p.net_amount >= '" . (float)$data['filter_amount_from'] . "'";
        }

        if (!empty($data['filter_amount_to'])) {
            $sql .= " AND p.net_amount <= '" . (float)$data['filter_amount_to'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(p.payment_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(p.payment_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // ترتيب النتائج
        $sort_data = [
            'customer_name',
            'amount',
            'net_amount',
            'payment_method',
            'payment_date',
            'status',
            'date_created'
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY p.payment_date";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        // تحديد عدد النتائج
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
     * الحصول على إجمالي عدد المدفوعات
     */
    public function getTotalPayments($data = []) {
        $sql = "
            SELECT COUNT(DISTINCT p.payment_id) AS total
            FROM " . DB_PREFIX . "installment_payment p
            LEFT JOIN " . DB_PREFIX . "installment_plan pl ON (p.plan_id = pl.plan_id)
            LEFT JOIN " . DB_PREFIX . "customer c ON (pl.customer_id = c.customer_id)
            WHERE 1=1
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_payment_method'])) {
            $sql .= " AND p.payment_method = '" . $this->db->escape($data['filter_payment_method']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND p.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * تحديث جدول الأقساط
     */
    private function updateInstallmentSchedule($plan_id, $payment_amount) {
        // الحصول على الأقساط المعلقة مرتبة حسب تاريخ الاستحقاق
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "installment_schedule
            WHERE plan_id = '" . (int)$plan_id . "' AND status = 'pending'
            ORDER BY due_date ASC
        ");

        $remaining_payment = $payment_amount;

        foreach ($query->rows as $installment) {
            if ($remaining_payment <= 0) {
                break;
            }

            if ($remaining_payment >= $installment['amount']) {
                // دفع القسط كاملاً
                $this->db->query("
                    UPDATE " . DB_PREFIX . "installment_schedule SET
                        status = 'paid',
                        paid_amount = '" . (float)$installment['amount'] . "',
                        payment_date = NOW()
                    WHERE schedule_id = '" . (int)$installment['schedule_id'] . "'
                ");

                $remaining_payment -= $installment['amount'];
            } else {
                // دفع جزئي
                $this->db->query("
                    UPDATE " . DB_PREFIX . "installment_schedule SET
                        status = 'partial',
                        paid_amount = '" . (float)$remaining_payment . "'
                    WHERE schedule_id = '" . (int)$installment['schedule_id'] . "'
                ");

                $remaining_payment = 0;
            }
        }
    }

    /**
     * إنشاء القيود المحاسبية
     */
    private function createAccountingEntries($payment_id) {
        $this->load->model('accounting/journal_entry');

        $payment = $this->getPayment($payment_id);

        if (!$payment) {
            return false;
        }

        $entries = [];

        // قيد تحصيل القسط الأساسي
        if ($payment['amount'] > 0) {
            $entries[] = [
                'account_code' => $this->getPaymentAccountCode($payment['payment_method']),
                'debit' => $payment['amount'],
                'credit' => 0,
                'description' => 'تحصيل قسط من العميل: ' . $payment['customer_name']
            ];

            $entries[] = [
                'account_code' => '1131', // العملاء - تقسيط
                'debit' => 0,
                'credit' => $payment['amount'],
                'description' => 'تحصيل قسط من العميل: ' . $payment['customer_name']
            ];
        }

        // قيد غرامة التأخير
        if ($payment['late_fee'] > 0) {
            $entries[] = [
                'account_code' => $this->getPaymentAccountCode($payment['payment_method']),
                'debit' => $payment['late_fee'],
                'credit' => 0,
                'description' => 'غرامة تأخير من العميل: ' . $payment['customer_name']
            ];

            $entries[] = [
                'account_code' => '4211', // إيرادات غرامات تأخير
                'debit' => 0,
                'credit' => $payment['late_fee'],
                'description' => 'غرامة تأخير من العميل: ' . $payment['customer_name']
            ];
        }

        // قيد خصم السداد المبكر
        if ($payment['discount'] > 0) {
            $entries[] = [
                'account_code' => '5211', // خصومات سداد مبكر
                'debit' => $payment['discount'],
                'credit' => 0,
                'description' => 'خصم سداد مبكر للعميل: ' . $payment['customer_name']
            ];

            $entries[] = [
                'account_code' => '1131', // العملاء - تقسيط
                'debit' => 0,
                'credit' => $payment['discount'],
                'description' => 'خصم سداد مبكر للعميل: ' . $payment['customer_name']
            ];
        }

        // إنشاء القيد المحاسبي
        if (!empty($entries)) {
            $journal_data = [
                'reference' => 'INST-PAY-' . $payment_id,
                'description' => 'قيد مدفوعة قسط رقم ' . $payment['reference_number'],
                'entries' => $entries
            ];

            return $this->model_accounting_journal_entry->addJournalEntry($journal_data);
        }

        return false;
    }

    /**
     * الحصول على رمز الحساب حسب طريقة الدفع
     */
    private function getPaymentAccountCode($payment_method) {
        switch ($payment_method) {
            case 'cash':
                return '1111'; // النقدية
            case 'bank_transfer':
                return '1121'; // البنوك
            case 'check':
                return '1141'; // الشيكات تحت التحصيل
            case 'credit_card':
                return '1151'; // بطاقات ائتمان
            case 'mobile_wallet':
                return '1161'; // محافظ إلكترونية
            default:
                return '1111'; // النقدية (افتراضي)
        }
    }

    /**
     * إنشاء رقم مرجعي
     */
    private function generateReferenceNumber() {
        $prefix = 'PAY';
        $date = date('Ymd');

        // الحصول على آخر رقم لليوم
        $query = $this->db->query("
            SELECT reference_number
            FROM " . DB_PREFIX . "installment_payment
            WHERE reference_number LIKE '" . $prefix . $date . "%'
            ORDER BY payment_id DESC
            LIMIT 1
        ");

        if ($query->num_rows) {
            $last_number = substr($query->row['reference_number'], -4);
            $new_number = str_pad((int)$last_number + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $new_number = '0001';
        }

        return $prefix . $date . $new_number;
    }

    /**
     * إحصائيات سريعة
     */
    public function getTodayPayments() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "installment_payment
            WHERE DATE(payment_date) = CURDATE() AND status = 'confirmed'
        ");
        return $query->row['total'];
    }

    public function getTodayAmount() {
        $query = $this->db->query("
            SELECT SUM(net_amount) as total
            FROM " . DB_PREFIX . "installment_payment
            WHERE DATE(payment_date) = CURDATE() AND status = 'confirmed'
        ");
        return $query->row['total'] ?? 0;
    }

    public function getMonthPayments() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "installment_payment
            WHERE MONTH(payment_date) = MONTH(CURDATE())
            AND YEAR(payment_date) = YEAR(CURDATE())
            AND status = 'confirmed'
        ");
        return $query->row['total'];
    }

    public function getMonthAmount() {
        $query = $this->db->query("
            SELECT SUM(net_amount) as total
            FROM " . DB_PREFIX . "installment_payment
            WHERE MONTH(payment_date) = MONTH(CURDATE())
            AND YEAR(payment_date) = YEAR(CURDATE())
            AND status = 'confirmed'
        ");
        return $query->row['total'] ?? 0;
    }

    public function getPendingPayments() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "installment_payment
            WHERE status = 'pending'
        ");
        return $query->row['total'];
    }

    public function getOverdueAmount() {
        $query = $this->db->query("
            SELECT SUM(s.amount) as total
            FROM " . DB_PREFIX . "installment_schedule s
            WHERE s.status = 'pending' AND s.due_date < CURDATE()
        ");
        return $query->row['total'] ?? 0;
    }

    /**
     * دوال للتوافق مع النظام القديم
     */
    public function getPaymentsByOrder($order_id) {
        $query = $this->db->query("
            SELECT p.*, pl.customer_id
            FROM " . DB_PREFIX . "installment_payment p
            LEFT JOIN " . DB_PREFIX . "installment_plan pl ON (p.plan_id = pl.plan_id)
            WHERE pl.order_id = '" . (int)$order_id . "'
            ORDER BY p.payment_date ASC
        ");
        return $query->rows;
    }

    public function editPaymentStatus($payment_id, $status) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "installment_payment
            SET status = '" . $this->db->escape($status) . "', date_modified = NOW()
            WHERE payment_id = '" . (int)$payment_id . "'
        ");
    }
}
