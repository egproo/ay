<?php
class ModelPurchaseSupplierInvoice extends Model {

    /**
     * Get invoice statistics
     * @param array $filter_data Filter data
     * @return array Statistics
     */
    public function getInvoiceStats($filter_data = array()) {
        $stats = array(
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'partially_paid' => 0,
            'paid' => 0,
            'cancelled' => 0,
            'total_amount' => 0
        );

        // Build WHERE clause based on filters
        $where = " WHERE 1 ";
        $join = " LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (si.po_id = po.po_id) ";
        $join .= " LEFT JOIN `" . DB_PREFIX . "supplier` s ON (si.supplier_id = s.supplier_id) ";

        if (!empty($filter_data['filter_invoice_number'])) {
            $where .= " AND si.invoice_number LIKE '%" . $this->db->escape($filter_data['filter_invoice_number']) . "%' ";
        }
        if (!empty($filter_data['filter_po_number'])) {
            $where .= " AND po.po_number LIKE '%" . $this->db->escape($filter_data['filter_po_number']) . "%' ";
        }
        if (!empty($filter_data['filter_supplier_id'])) {
            $where .= " AND si.supplier_id = '" . (int)$filter_data['filter_supplier_id'] . "' ";
        }
        if (!empty($filter_data['filter_status'])) {
            $where .= " AND si.status = '" . $this->db->escape($filter_data['filter_status']) . "' ";
        }
        if (!empty($filter_data['filter_date_start'])) {
            $where .= " AND DATE(si.invoice_date) >= '" . $this->db->escape($filter_data['filter_date_start']) . "' ";
        }
        if (!empty($filter_data['filter_date_end'])) {
            $where .= " AND DATE(si.invoice_date) <= '" . $this->db->escape($filter_data['filter_date_end']) . "' ";
        }

        // Use subqueries to calculate statistics
        $sql = "SELECT
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "supplier_invoice` si $join $where) AS total_invoices,
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "supplier_invoice` si $join $where AND si.status = 'pending_approval') AS pending_invoices,
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "supplier_invoice` si $join $where AND si.status = 'approved') AS approved_invoices,
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "supplier_invoice` si $join $where AND si.status = 'rejected') AS rejected_invoices,
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "supplier_invoice` si $join $where AND si.status = 'partially_paid') AS partially_paid_invoices,
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "supplier_invoice` si $join $where AND si.status = 'paid') AS paid_invoices,
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "supplier_invoice` si $join $where AND si.status = 'cancelled') AS cancelled_invoices,
                (SELECT SUM(si.total_amount) FROM `" . DB_PREFIX . "supplier_invoice` si $join $where) AS total_amount";

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            $stats['total'] = (int)$query->row['total_invoices'];
            $stats['pending'] = (int)$query->row['pending_invoices'];
            $stats['approved'] = (int)$query->row['approved_invoices'];
            $stats['rejected'] = (int)$query->row['rejected_invoices'];
            $stats['partially_paid'] = (int)$query->row['partially_paid_invoices'];
            $stats['paid'] = (int)$query->row['paid_invoices'];
            $stats['cancelled'] = (int)$query->row['cancelled_invoices'];
            $stats['total_amount'] = $query->row['total_amount'] ? $this->currency->format($query->row['total_amount'], $this->config->get('config_currency')) : $this->currency->format(0, $this->config->get('config_currency'));
        }

        return $stats;
    }

    /**
     * Get the amount due for an invoice
     * @param int $invoice_id Invoice ID
     * @return float Amount still due on the invoice
     */
    public function getInvoiceAmountDue($invoice_id) {
        $invoice_info = $this->getInvoice($invoice_id);
        if (!$invoice_info) {
            return 0;
        }

        // If amount_due field exists and is set, use it directly
        if (isset($invoice_info['amount_due'])) {
            return (float)$invoice_info['amount_due'];
        }

        // Otherwise calculate: total_amount - amount_paid
        $total = (float)$invoice_info['total_amount'];
        $paid = (float)($invoice_info['amount_paid'] ?? 0);

        return max(0, round($total - $paid, 4)); // Ensure we don't return negative values
    }

    /**
     * Get the payment history for an invoice
     * @param int $invoice_id Invoice ID
     * @return array Payment records
     */
    public function getInvoicePayments($invoice_id) {
        // Get direct payments from supplier_invoice_payment table
        $query_direct = $this->db->query("SELECT
                                   p.payment_id,
                                   p.payment_date,
                                   p.reference,
                                   p.amount,
                                   p.amount as amount_applied,
                                   p.status,
                                   pm.name as payment_method,
                                   CONCAT(u.firstname, ' ', u.lastname) as user_name,
                                   'direct' as payment_type
                                   FROM " . DB_PREFIX . "supplier_invoice_payment p
                                   LEFT JOIN " . DB_PREFIX . "payment_method pm ON (p.payment_method_id = pm.payment_method_id)
                                   LEFT JOIN " . DB_PREFIX . "user u ON (p.user_id = u.user_id)
                                   WHERE p.invoice_id = '" . (int)$invoice_id . "'");

        // Get vendor payments from vendor_payment_invoice table
        $query_vendor = $this->db->query("SELECT
                                   vp.payment_id,
                                   vp.payment_date,
                                   vp.reference,
                                   vp.amount,
                                   vpi.amount_applied,
                                   vp.status,
                                   pm.name as payment_method,
                                   CONCAT(u.firstname, ' ', u.lastname) as user_name,
                                   'vendor' as payment_type
                                   FROM " . DB_PREFIX . "vendor_payment vp
                                   JOIN " . DB_PREFIX . "vendor_payment_invoice vpi ON (vp.payment_id = vpi.payment_id)
                                   LEFT JOIN " . DB_PREFIX . "payment_method pm ON (vp.payment_method_id = pm.payment_method_id)
                                   LEFT JOIN " . DB_PREFIX . "user u ON (vp.user_id = u.user_id)
                                   WHERE vpi.invoice_id = '" . (int)$invoice_id . "'");

        // Combine results
        $payments = array_merge($query_direct->rows, $query_vendor->rows);

        // Sort by payment date (newest first)
        usort($payments, function($a, $b) {
            return strtotime($b['payment_date']) - strtotime($a['payment_date']);
        });

        return $payments;
    }

    /**
     * Update invoice payment status based on payment amount
     * @param int $invoice_id Invoice ID
     * @return bool Success
     */
    public function updateInvoicePaymentStatus($invoice_id) {
        $invoice_info = $this->getInvoice($invoice_id);
        if (!$invoice_info) {
            return false;
        }

        // Get total payments applied to this invoice from vendor_payment_invoice table
        $query_vendor = $this->db->query("SELECT SUM(amount_applied) AS total_paid
                                   FROM " . DB_PREFIX . "vendor_payment_invoice
                                   WHERE invoice_id = '" . (int)$invoice_id . "'");
        $vendor_paid = (float)($query_vendor->row['total_paid'] ?? 0);

        // Also get payments from supplier_invoice_payment table that are not voided
        $query_direct = $this->db->query("SELECT SUM(amount) AS total_paid
                                   FROM " . DB_PREFIX . "supplier_invoice_payment
                                   WHERE invoice_id = '" . (int)$invoice_id . "'
                                   AND status = 'completed'");
        $direct_paid = (float)($query_direct->row['total_paid'] ?? 0);

        // Calculate total payments from both sources
        $total_paid = $vendor_paid + $direct_paid;

        // Calculate amount due
        $total_amount = (float)$invoice_info['total_amount'];
        $amount_due = max(0, $total_amount - $total_paid);

        // Determine new status based on payment amount
        $new_status = $invoice_info['status']; // Default: keep current status

        // Only update status if invoice is approved (don't change pending/rejected status)
        if ($invoice_info['status'] == 'approved' || $invoice_info['status'] == 'partially_paid' || $invoice_info['status'] == 'paid') {
            if ($total_paid <= 0) {
                $new_status = 'approved'; // No payments yet
            } elseif (round($total_paid, 4) >= round($total_amount, 4)) {
                $new_status = 'paid'; // Fully paid
            } else {
                $new_status = 'partially_paid'; // Partially paid
            }
        }

        // Update invoice with payment information
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_invoice SET
                          amount_paid = '" . (float)$total_paid . "',
                          amount_due = '" . (float)$amount_due . "',
                          status = '" . $this->db->escape($new_status) . "',
                          updated_at = NOW()
                          WHERE invoice_id = '" . (int)$invoice_id . "'");

        return true;
    }

    /**
     * Get supplier invoice aging report data
     * @param array $data Filter parameters
     * @return array Aging report data
     */
    public function getInvoiceAgingReport($data = array()) {
        $sql = "SELECT si.invoice_id, si.invoice_number, si.invoice_date, si.due_date,
                       si.total_amount, si.amount_paid, si.amount_due,
                       DATEDIFF(CURDATE(), si.due_date) as days_overdue,
                       s.supplier_id, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name
                FROM " . DB_PREFIX . "supplier_invoice si
                LEFT JOIN " . DB_PREFIX . "supplier s ON (si.supplier_id = s.supplier_id)
                WHERE si.status IN ('approved', 'partially_paid')
                AND si.amount_due > 0";

        // Apply filters
        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND si.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND si.due_date >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND si.due_date <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        // Add sorting
        $sql .= " ORDER BY days_overdue DESC, si.due_date ASC";

        // Add pagination
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
     * Get supplier payment summary
     * @param int $supplier_id Supplier ID
     * @param array $data Filter parameters
     * @return array Payment summary data
     */
    public function getSupplierPaymentSummary($supplier_id, $data = array()) {
        // Get total invoiced amount
        $sql_total = "SELECT SUM(total_amount) AS total_invoiced,
                             SUM(amount_paid) AS total_paid,
                             SUM(amount_due) AS total_due
                      FROM " . DB_PREFIX . "supplier_invoice
                      WHERE supplier_id = '" . (int)$supplier_id . "'
                      AND status IN ('approved', 'partially_paid', 'paid')";

        // Apply date filters if provided
        if (!empty($data['filter_date_start'])) {
            $sql_total .= " AND invoice_date >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql_total .= " AND invoice_date <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query_total = $this->db->query($sql_total);
        $summary = $query_total->row;

        // Get aging buckets
        $sql_aging = "SELECT
            SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) <= 0 THEN amount_due ELSE 0 END) as current_due,
            SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 1 AND 30 THEN amount_due ELSE 0 END) as days_1_30,
            SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 31 AND 60 THEN amount_due ELSE 0 END) as days_31_60,
            SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 61 AND 90 THEN amount_due ELSE 0 END) as days_61_90,
            SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) > 90 THEN amount_due ELSE 0 END) as days_over_90
            FROM " . DB_PREFIX . "supplier_invoice
            WHERE supplier_id = '" . (int)$supplier_id . "'
            AND status IN ('approved', 'partially_paid')
            AND amount_due > 0";

        $query_aging = $this->db->query($sql_aging);
        $aging = $query_aging->row;

        // Get recent payment history
        $sql_payments = "SELECT p.payment_date, p.amount, p.reference, pm.name as payment_method
                         FROM " . DB_PREFIX . "supplier_invoice_payment p
                         LEFT JOIN " . DB_PREFIX . "payment_method pm ON (p.payment_method_id = pm.payment_method_id)
                         LEFT JOIN " . DB_PREFIX . "supplier_invoice si ON (p.invoice_id = si.invoice_id)
                         WHERE si.supplier_id = '" . (int)$supplier_id . "'
                         ORDER BY p.payment_date DESC
                         LIMIT 5";

        $query_payments = $this->db->query($sql_payments);
        $recent_payments = $query_payments->rows;

        return array(
            'summary' => $summary,
            'aging' => $aging,
            'recent_payments' => $recent_payments
        );
    }

    /**
     * Get total count for invoice aging report
     * @param array $data Filter parameters
     * @return int Total count
     */
    public function getTotalInvoiceAgingReport($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "supplier_invoice si
                LEFT JOIN " . DB_PREFIX . "supplier s ON (si.supplier_id = s.supplier_id)
                WHERE si.status IN ('approved', 'partially_paid')
                AND si.amount_due > 0";

        // Apply filters
        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND si.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND si.due_date >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND si.due_date <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * إضافة فاتورة مورد جديدة
     * @param array $data بيانات الفاتورة
     * @return int|false معرف الفاتورة الجديدة أو false عند الفشل
     */
    public function addInvoice($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_invoice SET
            supplier_id = '" . (int)$data['supplier_id'] . "',
            po_id = '" . (isset($data['po_id']) ? (int)$data['po_id'] : 0) . "',
            invoice_number = '" . $this->db->escape($data['invoice_number']) . "',
            invoice_date = '" . $this->db->escape($data['invoice_date']) . "',
            due_date = '" . $this->db->escape($data['due_date']) . "',
            currency_id = '" . (int)$data['currency_id'] . "',
            exchange_rate = '" . (float)$data['exchange_rate'] . "',
            subtotal = '" . (float)$data['subtotal'] . "',
            tax_amount = '" . (float)$data['tax_amount'] . "',
            total_amount = '" . (float)$data['total_amount'] . "',
            status = 'pending_approval', // Default status
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)$data['user_id'] . "',
            created_at = NOW(),
            updated_at = NOW(),
            updated_by = '" . (int)$data['user_id'] . "'");

        $invoice_id = $this->db->getLastId();

        if ($invoice_id && isset($data['item'])) { // Changed from $data['items'] to $data['item'] to match controller
            foreach ($data['item'] as $item) {
                 // Ensure required fields exist
                 if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['unit_price'])) continue;

                 $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_invoice_item SET
                    invoice_id = '" . (int)$invoice_id . "',
                    po_item_id = '" . (isset($item['po_item_id']) ? (int)$item['po_item_id'] : 0) . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . (float)$item['quantity'] . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    unit_price = '" . (float)$item['unit_price'] . "',
                    line_total = '" . (float)($item['quantity'] * $item['unit_price']) . "'"); // Calculate line total here
            }
        }

        // Add history
        if ($invoice_id) {
            $this->addInvoiceHistory($invoice_id, $data['user_id'], 'create', $this->language->get('text_history_created'));
            return $invoice_id;
        } else {
            return false;
        }
    }

    /**
     * تعديل فاتورة مورد
     * @param int $invoice_id معرف الفاتورة
     * @param array $data بيانات الفاتورة
     * @return bool
     */
    public function editInvoice($invoice_id, $data) {
        $invoice_info = $this->getInvoice($invoice_id);
        // Allow editing only if pending approval
        if (!$invoice_info || !in_array($invoice_info['status'], ['pending_approval'])) {
             return false;
        }

        $this->db->query("UPDATE " . DB_PREFIX . "supplier_invoice SET
            supplier_id = '" . (int)$data['supplier_id'] . "',
            po_id = '" . (isset($data['po_id']) ? (int)$data['po_id'] : 0) . "',
            invoice_number = '" . $this->db->escape($data['invoice_number']) . "',
            invoice_date = '" . $this->db->escape($data['invoice_date']) . "',
            due_date = '" . $this->db->escape($data['due_date']) . "',
            currency_id = '" . (int)$data['currency_id'] . "',
            exchange_rate = '" . (float)$data['exchange_rate'] . "',
            subtotal = '" . (float)$data['subtotal'] . "',
            tax_amount = '" . (float)$data['tax_amount'] . "',
            total_amount = '" . (float)$data['total_amount'] . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            updated_at = NOW(),
            updated_by = '" . (int)$data['user_id'] . "'
            WHERE invoice_id = '" . (int)$invoice_id . "'");

        // Delete existing items before re-inserting (simple approach)
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_invoice_item WHERE invoice_id = '" . (int)$invoice_id . "'");

        if (isset($data['item'])) { // Changed from $data['items']
            foreach ($data['item'] as $item) {
                 // Ensure required fields exist
                 if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['unit_price'])) continue;

                 $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_invoice_item SET
                    invoice_id = '" . (int)$invoice_id . "',
                    po_item_id = '" . (isset($item['po_item_id']) ? (int)$item['po_item_id'] : 0) . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . (float)$item['quantity'] . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    unit_price = '" . (float)$item['unit_price'] . "',
                    line_total = '" . (float)($item['quantity'] * $item['unit_price']) . "'"); // Calculate line total here
            }
        }

        // Add history
        $this->addInvoiceHistory($invoice_id, $data['user_id'], 'edit', $this->language->get('text_history_updated'));

        return true;
    }

    /**
     * جلب فاتورة مورد واحدة
     * @param int $invoice_id معرف الفاتورة
     * @return array|false
     */
    public function getInvoice($invoice_id) {
        $query = $this->db->query("SELECT si.*, s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                   po.po_number, c.code AS currency_code, c.symbol_left, c.symbol_right, c.decimal_place
                                   FROM " . DB_PREFIX . "supplier_invoice si
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (si.supplier_id = s.supplier_id)
                                   LEFT JOIN " . DB_PREFIX . "purchase_order po ON (si.po_id = po.po_id)
                                   LEFT JOIN " . DB_PREFIX . "currency c ON (si.currency_id = c.currency_id)
                                   WHERE si.invoice_id = '" . (int)$invoice_id . "'");
        return $query->row;
    }

    /**
     * جلب بنود فاتورة مورد
     * @param int $invoice_id معرف الفاتورة
     * @return array
     */
    public function getInvoiceItems($invoice_id) {
         $query = $this->db->query("SELECT sii.*, pd.name as product_name, u.desc_en as unit_name
                                   FROM " . DB_PREFIX . "supplier_invoice_item sii
                                   LEFT JOIN " . DB_PREFIX . "product_description pd ON (sii.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                   LEFT JOIN " . DB_PREFIX . "unit u ON (sii.unit_id = u.unit_id)
                                   WHERE sii.invoice_id = '" . (int)$invoice_id . "' ORDER BY sii.invoice_item_id ASC");
        return $query->rows;
    }

    /**
     * جلب قائمة فواتير الموردين
     * @param array $data بيانات الفلترة والترتيب والصفحات
     * @return array
     */
    public function getInvoices($data = array()) {
        $sql = "SELECT si.*, s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                       po.po_number, c.code AS currency_code
                FROM " . DB_PREFIX . "supplier_invoice si
                LEFT JOIN " . DB_PREFIX . "supplier s ON (si.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "purchase_order po ON (si.po_id = po.po_id)
                LEFT JOIN " . DB_PREFIX . "currency c ON (si.currency_id = c.currency_id)
                WHERE 1=1";

        // Apply filters
        if (!empty($data['filter_invoice_number'])) {
            $sql .= " AND si.invoice_number LIKE '%" . $this->db->escape($data['filter_invoice_number']) . "%'";
        }
        if (!empty($data['filter_po_id'])) { // Filter by PO ID if provided
            $sql .= " AND si.po_id = '" . (int)$data['filter_po_id'] . "'";
        }
         if (!empty($data['filter_po_number'])) { // Filter by PO Number if provided (requires join)
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%'";
        }
        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND si.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND si.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(si.invoice_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(si.invoice_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sort_data = array(
            'si.invoice_number',
            'po.po_number',
            'supplier_name',
            'si.total_amount',
            'si.status',
            'si.invoice_date',
            'si.due_date',
            'si.created_at'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY si.invoice_date"; // Default sort
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
     * جلب العدد الإجمالي لفواتير الموردين
     * @param array $data بيانات الفلترة
     * @return int
     */
    public function getTotalInvoices($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "supplier_invoice si
                LEFT JOIN " . DB_PREFIX . "supplier s ON (si.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "purchase_order po ON (si.po_id = po.po_id)
                WHERE 1=1";

        // Apply filters
        if (!empty($data['filter_invoice_number'])) {
            $sql .= " AND si.invoice_number LIKE '%" . $this->db->escape($data['filter_invoice_number']) . "%'";
        }
         if (!empty($data['filter_po_id'])) { // Filter by PO ID if provided
            $sql .= " AND si.po_id = '" . (int)$data['filter_po_id'] . "'";
        }
         if (!empty($data['filter_po_number'])) { // Filter by PO Number if provided (requires join)
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%'";
        }
        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND si.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND si.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(si.invoice_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(si.invoice_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * حذف فاتورة مورد
     * @param int $invoice_id معرف الفاتورة
     * @return bool
     * @throws Exception
     */
    public function deleteInvoice($invoice_id) {
        $this->load->language('purchase/supplier_invoice'); // Load language file for error messages

        // Check status before allowing delete (e.g., only pending, rejected, cancelled)
        $invoice_info = $this->getInvoice($invoice_id);
        if (!$invoice_info) {
            throw new Exception($this->language->get('error_invoice_not_found'));
        }
        // Adjust allowed statuses as needed. Cannot easily delete approved/paid invoices.
        if (!in_array($invoice_info['status'], ['pending_approval', 'rejected', 'cancelled'])) {
             // Use sprintf to include the status in the error message if the language string supports it
             throw new Exception(sprintf($this->language->get('error_delete_status'), $invoice_info['status']));
        }

        // Check for related payments before allowing deletion (important!)
        $payment_check = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "vendor_payment_invoice WHERE invoice_id = '" . (int)$invoice_id . "'");
        if ($payment_check->row['total'] > 0) {
            throw new Exception($this->language->get('error_delete_paid_invoice'));
        }

        // Check if associated journal entry exists and prevent deletion if it does (or handle reversal)
        if (!empty($invoice_info['journal_id'])) {
             throw new Exception($this->language->get('error_delete_journal_linked'));
        }


        try {
            $this->db->query("START TRANSACTION");

            // Delete invoice items
            $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_invoice_item WHERE invoice_id = '" . (int)$invoice_id . "'");

            // Delete invoice history
            $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_invoice_history WHERE invoice_id = '" . (int)$invoice_id . "'");

            // Delete related documents (assuming a similar structure to PO/Quotation)
            // TODO: Implement document deletion logic if needed
            // $documents = $this->getInvoiceDocuments($invoice_id); // Assuming getInvoiceDocuments exists
            // foreach ($documents as $document) {
            //     $this->deletePhysicalDocument($document['file_path']); // Assuming deletePhysicalDocument exists
            // }
            // $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_document WHERE reference_type = 'supplier_invoice' AND reference_id = '" . (int)$invoice_id . "'");


            // Delete the main invoice record
            $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_invoice WHERE invoice_id = '" . (int)$invoice_id . "'");

            $this->db->query("COMMIT");
            // Add history after successful commit
            $this->addInvoiceHistory($invoice_id, $this->user->getId(), 'delete', 'Supplier invoice deleted.');
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            // Log error $e->getMessage();
            throw $e; // Re-throw for controller
        }
    }

    /**
     * Approve a supplier invoice
     * @param int $invoice_id
     * @param int $user_id
     * @return bool
     * @throws Exception
     */
    public function approveInvoice($invoice_id, $user_id) {
        $this->load->language('purchase/supplier_invoice');
        // Get necessary account codes from settings or mapping
        // This is simplified - a real implementation needs robust account fetching
        // TODO: Replace config gets with proper account mapping lookup based on supplier/branch/etc.
        $ap_account_code = $this->config->get('config_accounts_payable_account') ?: '210100'; // Example fallback
        $grir_account_code = $this->config->get('config_grir_suspense_account') ?: '210900'; // Example fallback for GR/IR
        $vat_input_account_code = $this->config->get('config_vat_input_account') ?: '130500'; // Example fallback
        $expense_account_code = $this->config->get('config_default_expense_account') ?: '510100'; // Example fallback expense

        $invoice_info = $this->getInvoice($invoice_id);
        if (!$invoice_info || $invoice_info['status'] != 'pending_approval') {
            throw new Exception($this->language->get('error_invalid_status_approval'));
        }

        if (!$ap_account_code || !$grir_account_code || !$vat_input_account_code || !$expense_account_code) {
             throw new Exception('Accounting codes for supplier invoice posting are not configured.');
        }

        try {
            $this->db->query("START TRANSACTION");

            // Update invoice status
            $this->db->query("UPDATE " . DB_PREFIX . "supplier_invoice SET
                status = 'approved',
                amount_paid = '0.00', /* Initialize payment tracking */
                amount_due = '" . (float)$invoice_info['total_amount'] . "', /* Set initial amount due */
                updated_at = NOW(),
                updated_by = '" . (int)$user_id . "'
                WHERE invoice_id = '" . (int)$invoice_id . "'");

            // --- Create Journal Entry ---
            $journal_data = [
                'refnum' => $invoice_info['invoice_number'],
                'thedate' => $invoice_info['invoice_date'],
                'description' => sprintf($this->language->get('text_journal_supplier_invoice'), $invoice_info['invoice_number'], $invoice_info['supplier_name']),
                'entrytype' => 2, // Automatic
                'added_by' => $user_id,
                'entries' => []
            ];

            // Debit: GR/IR Suspense (or Expense if no PO/Receipt link) - Use Subtotal
            // TODO: Need logic to determine if it hits GR/IR or Expense based on PO/Receipt link and item types
            $debit_account = $grir_account_code; // Assume GR/IR for now
            if ((float)$invoice_info['subtotal'] > 0) { // Only add if subtotal is positive
                $journal_data['entries'][] = [
                    'account_code' => $debit_account,
                    'is_debit' => 1,
                    'amount' => (float)$invoice_info['subtotal']
                ];
            }

            // Debit: VAT Input (if applicable)
            if ((float)$invoice_info['tax_amount'] > 0) {
                $journal_data['entries'][] = [
                    'account_code' => $vat_input_account_code,
                    'is_debit' => 1,
                    'amount' => (float)$invoice_info['tax_amount']
                ];
            }

            // Credit: Accounts Payable
            if ((float)$invoice_info['total_amount'] > 0) { // Only add if total is positive
                $journal_data['entries'][] = [
                    'account_code' => $ap_account_code, // Use supplier's specific AP account if available, else default
                    'is_debit' => 0,
                    'amount' => (float)$invoice_info['total_amount']
                ];
            }

            // TODO: Handle discounts if they need separate posting

            // Validate journal balance before adding
            $debit_total = 0;
            $credit_total = 0;
            foreach ($journal_data['entries'] as $entry) {
                if ($entry['is_debit']) {
                    $debit_total += $entry['amount'];
                } else {
                    $credit_total += $entry['amount'];
                }
            }

            // Allow for small rounding differences
            if (abs(round($debit_total, 4) - round($credit_total, 4)) > 0.0001) {
                 throw new Exception('Journal entry does not balance. Debit: ' . $debit_total . ', Credit: ' . $credit_total);
            }

            // Add the journal entry only if there are entries
            $journal_id = null;
            if (!empty($journal_data['entries'])) {
                $this->load->model('accounts/journal'); // Ensure journal model is loaded
                $journal_id = $this->model_accounts_journal->addJournal($journal_data);

                if (!$journal_id) {
                    throw new Exception('Failed to create journal entry for supplier invoice.');
                }

                // Link journal entry to the invoice
                $this->db->query("UPDATE " . DB_PREFIX . "supplier_invoice SET journal_id = '" . (int)$journal_id . "' WHERE invoice_id = '" . (int)$invoice_id . "'");
            }

            // TODO: Update matching status if applicable
            // Example: $this->updateMatchingStatusForInvoice($invoice_id);

            // Add history
            $history_desc = 'Supplier invoice approved.';
            if ($journal_id) {
                $history_desc .= ' Journal ID: ' . $journal_id;
            }
            $this->addInvoiceHistory($invoice_id, $user_id, 'approve', $history_desc);

            $this->db->query("COMMIT");
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            // Log error $e->getMessage();
            throw $e; // Re-throw for controller
        }
    }

    /**
     * Reject a supplier invoice
     * @param int $invoice_id
     * @param string $reason
     * @param int $user_id
     * @return bool
     * @throws Exception
     */
    public function rejectInvoice($invoice_id, $reason, $user_id) {
        $invoice_info = $this->getInvoice($invoice_id);
        if (!$invoice_info || !in_array($invoice_info['status'], ['pending_approval'])) {
             throw new Exception($this->language->get('error_invalid_status_rejection'));
        }

        if (empty($reason)) {
            // Allow rejection without reason for now, maybe enforce later via controller
            // throw new Exception($this->language->get('error_rejection_reason_required'));
            $reason = 'Rejected without specific reason.'; // Default reason if empty
        }

        try {
            $this->db->query("START TRANSACTION");

            $this->db->query("UPDATE " . DB_PREFIX . "supplier_invoice SET
                status = 'rejected',
                notes = CONCAT(COALESCE(notes, ''), '\nRejected Reason: ', '" . $this->db->escape($reason) . "'),
                updated_at = NOW(),
                updated_by = '" . (int)$user_id . "'
                WHERE invoice_id = '" . (int)$invoice_id . "'");

            // Add history
            $this->addInvoiceHistory($invoice_id, $user_id, 'reject', 'Supplier invoice rejected. Reason: ' . $reason);

            $this->db->query("COMMIT");
            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * إضافة سجل تاريخ للفاتورة
     * @param int $invoice_id معرف الفاتورة
     * @param int $user_id معرف المستخدم
     * @param string $action الإجراء
     * @param string $description الوصف
     * @return bool
     */
    public function addInvoiceHistory($invoice_id, $user_id, $action, $description = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_invoice_history SET
            invoice_id = '" . (int)$invoice_id . "',
            user_id = '" . (int)$user_id . "',
            action = '" . $this->db->escape($action) . "',
            description = '" . $this->db->escape($description) . "',
            created_at = NOW()");
        return $this->db->getLastId();
    }

    /**
     * تحديث حالة الفاتورة
     * @param int $invoice_id معرف الفاتورة
     * @param string $status الحالة الجديدة
     * @param int $user_id معرف المستخدم
     * @param string $comment التعليق
     * @return bool
     */
    public function updateInvoiceStatus($invoice_id, $status, $user_id, $comment = '') {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_invoice SET
            status = '" . $this->db->escape($status) . "',
            updated_at = NOW(),
            updated_by = '" . (int)$user_id . "'
            WHERE invoice_id = '" . (int)$invoice_id . "'");

        // Add history
        $action = 'status_change';
        $history_comment = $comment;

        if (empty($history_comment)) {
            if ($status == 'approved') {
                $history_comment = $this->language->get('text_history_approved');
            } elseif ($status == 'rejected') {
                $history_comment = $this->language->get('text_history_rejected');
            } elseif ($status == 'cancelled') {
                $history_comment = $this->language->get('text_history_cancelled');
            } elseif ($status == 'partially_paid') {
                $history_comment = $this->language->get('text_history_partially_paid');
            } elseif ($status == 'paid') {
                $history_comment = $this->language->get('text_history_paid');
            } else {
                $history_comment = sprintf($this->language->get('text_history_status_changed'), $status);
            }
        }

        $this->addInvoiceHistory($invoice_id, $user_id, $action, $history_comment);

        return true;
    }

    /**
     * الحصول على سجل تاريخ الفاتورة
     * @param int $invoice_id معرف الفاتورة
     * @return array
     */
    public function getInvoiceHistory($invoice_id) {
        $query = $this->db->query("SELECT h.*, CONCAT(u.firstname, ' ', u.lastname) AS user_name
            FROM " . DB_PREFIX . "supplier_invoice_history h
            LEFT JOIN " . DB_PREFIX . "user u ON (h.user_id = u.user_id)
            WHERE h.invoice_id = '" . (int)$invoice_id . "'
            ORDER BY h.created_at DESC");

        return $query->rows;
    }

    /**
     * الحصول على مدفوعات الفاتورة
     * @param int $invoice_id معرف الفاتورة
     * @return array
     */
    public function getInvoicePayments($invoice_id) {
        $query = $this->db->query("SELECT p.*, CONCAT(u.firstname, ' ', u.lastname) AS created_by
            FROM " . DB_PREFIX . "supplier_invoice_payment p
            LEFT JOIN " . DB_PREFIX . "user u ON (p.user_id = u.user_id)
            WHERE p.invoice_id = '" . (int)$invoice_id . "'
            ORDER BY p.payment_date DESC");

        return $query->rows;
    }

    /**
     * الحصول على معلومات المورد
     * @param int $supplier_id معرف المورد
     * @return array
     */
    public function getSupplierInfo($supplier_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "supplier WHERE supplier_id = '" . (int)$supplier_id . "'");

        return $query->row;
    }

    /**
     * Get invoice history
     * @param int $invoice_id
     * @return array
     */
    public function getInvoiceHistory($invoice_id) {
        $query = $this->db->query("SELECT h.*, CONCAT(u.firstname, ' ', u.lastname) as user_name
                                   FROM " . DB_PREFIX . "supplier_invoice_history h
                                   LEFT JOIN " . DB_PREFIX . "user u ON (h.user_id = u.user_id)
                                   WHERE h.invoice_id = '" . (int)$invoice_id . "'
                                   ORDER BY h.date_added DESC");
        return $query->rows;
    }

    /**
     * Record a payment against an invoice
     * @param int $invoice_id Invoice ID
     * @param array $payment_data Payment data
     * @param int $user_id User making the payment
     * @return bool Success
     */
    public function recordInvoicePayment($invoice_id, $payment_data, $user_id) {
        $this->load->language('purchase/supplier_invoice');

        $invoice_info = $this->getInvoice($invoice_id);
        if (!$invoice_info) {
            return false;
        }

        // Validate payment amount
        $amount_due = $this->getInvoiceAmountDue($invoice_id);
        $payment_amount = (float)$payment_data['amount'];

        if ($payment_amount <= 0) {
            return false;
        }

        if ($payment_amount > $amount_due) {
            // Payment exceeds amount due - could handle overpayment or return error
            return false;
        }

        try {
            $this->db->query("START TRANSACTION");

            // Record payment in payment table
            $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_invoice_payment SET
                invoice_id = '" . (int)$invoice_id . "',
                payment_date = '" . $this->db->escape($payment_data['payment_date']) . "',
                payment_method_id = '" . (int)$payment_data['payment_method_id'] . "',
                amount = '" . (float)$payment_amount . "',
                reference = '" . $this->db->escape($payment_data['reference'] ?? '') . "',
                notes = '" . $this->db->escape($payment_data['notes'] ?? '') . "',
                status = 'completed',
                user_id = '" . (int)$user_id . "',
                date_added = NOW()");

            $payment_id = $this->db->getLastId();

            // Update invoice payment status
            $this->updateInvoicePaymentStatus($invoice_id);

            // Add history record
            $payment_method = '';
            if (!empty($payment_data['payment_method_id'])) {
                $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "payment_method WHERE payment_method_id = '" . (int)$payment_data['payment_method_id'] . "'");
                if ($query->num_rows) {
                    $payment_method = $query->row['name'];
                }
            }

            $history_comment = sprintf($this->language->get('text_payment_recorded'),
                                      $payment_amount,
                                      $payment_method,
                                      $payment_data['reference'] ?? '');

            $this->addInvoiceHistory($invoice_id, $user_id, 'payment', $history_comment);

            $this->db->query("COMMIT");
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return false;
        }
    }

    /**
     * Void a payment for an invoice
     * @param int $payment_id Payment ID
     * @param int $user_id User voiding the payment
     * @param string $reason Reason for voiding
     * @return bool Success
     */
    public function voidInvoicePayment($payment_id, $user_id, $reason = '') {
        $this->load->language('purchase/supplier_invoice');

        // Get payment info
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "supplier_invoice_payment
                                   WHERE payment_id = '" . (int)$payment_id . "'");
        if (!$query->num_rows) {
            return false;
        }

        $payment_info = $query->row;
        $invoice_id = $payment_info['invoice_id'];

        // Check if payment can be voided (e.g., not already voided)
        if ($payment_info['status'] != 'completed') {
            return false;
        }

        try {
            $this->db->query("START TRANSACTION");

            // Update payment status to voided
            $this->db->query("UPDATE " . DB_PREFIX . "supplier_invoice_payment SET
                status = 'voided',
                void_reason = '" . $this->db->escape($reason) . "',
                void_date = NOW(),
                void_user_id = '" . (int)$user_id . "'
                WHERE payment_id = '" . (int)$payment_id . "'");

            // Update invoice payment status
            $this->updateInvoicePaymentStatus($invoice_id);

            // Add history record
            $history_comment = sprintf($this->language->get('text_payment_voided'),
                                      $payment_info['amount'],
                                      $payment_info['reference'] ?? '',
                                      $reason);

            $this->addInvoiceHistory($invoice_id, $user_id, 'payment_void', $history_comment);

            $this->db->query("COMMIT");
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return false;
        }
    }

    /**
     * جلب نص الحالة
     * @param string $status رمز الحالة
     * @return string
     */
    public function getStatusText($status) {
        $this->load->language('purchase/supplier_invoice'); // Load language file
        $statuses = [
            'pending_approval' => $this->language->get('text_status_pending_approval'),
            'approved' => $this->language->get('text_status_approved'),
            'rejected' => $this->language->get('text_status_rejected'),
            'partially_paid' => $this->language->get('text_status_partially_paid'),
            'paid' => $this->language->get('text_status_paid'),
            'cancelled' => $this->language->get('text_status_cancelled')
        ];
        return $statuses[$status] ?? $status;
    }

    /**
     * جلب صنف CSS للحالة
     * @param string $status رمز الحالة
     * @return string
     */
    public function getStatusClass($status) {
        $classes = [
            'pending_approval' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'partially_paid' => 'info',
            'paid' => 'primary', // Or 'success' depending on preference
            'cancelled' => 'default'
        ];
        return $classes[$status] ?? 'default';
    }

    // Helper function to get suppliers (can be moved to a common model later)
    public function getSuppliers() {
        $query = $this->db->query("SELECT supplier_id, CONCAT(firstname, ' ', COALESCE(lastname, '')) AS name FROM " . DB_PREFIX . "supplier ORDER BY name ASC");
        return $query->rows;
    }

    // Helper function to get POs (can be moved to a common model later)
    public function getPurchaseOrdersForSupplier($supplier_id) {
         $query = $this->db->query("SELECT po_id, po_number FROM " . DB_PREFIX . "purchase_order WHERE supplier_id = '" . (int)$supplier_id . "' AND status IN ('approved', 'partially_received', 'fully_received') ORDER BY order_date DESC");
         return $query->rows;
    }

    // Helper function to get PO items for invoice creation
    public function getPurchaseOrderItemsForInvoice($po_id) {
        // Fetch PO items and potentially received/invoiced quantities to suggest remaining
        $query = $this->db->query("SELECT poi.*, pd.name as product_name, u.desc_en as unit_name,
                                  (poi.quantity - COALESCE((SELECT SUM(sii.quantity) FROM " . DB_PREFIX . "supplier_invoice_item sii WHERE sii.po_item_id = poi.po_item_id), 0)) as remaining_quantity
                                  FROM " . DB_PREFIX . "purchase_order_item poi
                                  LEFT JOIN " . DB_PREFIX . "product_description pd ON (poi.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  LEFT JOIN " . DB_PREFIX . "unit u ON (poi.unit_id = u.unit_id)
                                  WHERE poi.po_id = '" . (int)$po_id . "'");
        return $query->rows;
    }

    /**
     * Get documents attached to an invoice
     * @param int $invoice_id
     * @return array
     */
    public function getDocuments($invoice_id) {
        $query = $this->db->query("SELECT pd.*, CONCAT(u.firstname, ' ', u.lastname) AS uploaded_by_name
                                   FROM " . DB_PREFIX . "purchase_document pd
                                   LEFT JOIN " . DB_PREFIX . "user u ON (pd.uploaded_by = u.user_id)
                                   WHERE pd.reference_type = 'supplier_invoice'
                                   AND pd.reference_id = '" . (int)$invoice_id . "'
                                   ORDER BY pd.upload_date DESC");
        return $query->rows;
    }

    /**
     * Get a specific document record
     * @param int $document_id
     * @return array|false
     */
    public function getDocument($document_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "purchase_document
                                   WHERE document_id = '" . (int)$document_id . "'");
        return $query->row;
    }

    /**
     * Upload a document for a supplier invoice
     * @param int $invoice_id
     * @param array $file
     * @param string $document_type
     * @param int $user_id
     * @return array Uploaded document info
     * @throws Exception
     */
    public function uploadDocument($invoice_id, $file, $document_type, $user_id) {
        $invoice_info = $this->getInvoice($invoice_id);
        if (!$invoice_info) {
            throw new Exception($this->language->get('error_invoice_not_found'));
        }

        // Validate file (reuse logic from other models or create a helper)
        $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'txt'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception("Invalid file type.");
        }
        if ($file['size'] > 10485760) { // 10MB limit
            throw new Exception("File size exceeds limit.");
        }

        // Create directory structure
        $upload_dir = DIR_UPLOAD . 'purchase/invoices/' . $invoice_id . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename and path
        $filename = 'invoice_' . $invoice_id . '_' . uniqid() . '.' . $file_extension;
        $file_path = 'purchase/invoices/' . $invoice_id . '/' . $filename;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], DIR_UPLOAD . $file_path)) {
            throw new Exception("Failed to move uploaded file.");
        }

        // Save to database
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_document SET
            reference_type = 'supplier_invoice',
            reference_id = '" . (int)$invoice_id . "',
            document_name = '" . $this->db->escape($file['name']) . "',
            file_path = '" . $this->db->escape($file_path) . "',
            document_type = '" . $this->db->escape($document_type) . "',
            uploaded_by = '" . (int)$user_id . "',
            upload_date = NOW()");

        $document_id = $this->db->getLastId();

        // Add history
        $this->addInvoiceHistory($invoice_id, $user_id, 'document_upload', 'Document uploaded: ' . $file['name']);

        return [
            'document_id' => $document_id,
            'name' => $file['name'],
            'type' => $document_type,
            'path' => $file_path,
            'size' => $file['size'],
            'upload_date' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Delete a document record and its physical file
     * @param int $document_id
     * @return bool
     * @throws Exception
     */
    public function deleteDocument($document_id) {
        $document_info = $this->getDocument($document_id);
        if (!$document_info) {
            throw new Exception("Document not found.");
        }

        // Delete physical file first
        $this->deletePhysicalDocument($document_info['file_path']);

        // Delete database record
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_document WHERE document_id = '" . (int)$document_id . "'");

        // Add history (assuming reference_id is the invoice_id)
        if ($document_info['reference_type'] == 'supplier_invoice') {
             $this->addInvoiceHistory($document_info['reference_id'], $this->user->getId(), 'document_delete', 'Document deleted: ' . $document_info['document_name']);
        }

        return true;
    }

    /**
     * Delete a physical document file
     * @param string $file_path Relative path from DIR_UPLOAD
     * @return bool
     */
    protected function deletePhysicalDocument($file_path) {
        $full_path = DIR_UPLOAD . $file_path;
        if (file_exists($full_path) && is_file($full_path)) {
            return unlink($full_path);
        }
        return true; // Return true even if file doesn't exist
    }

    /**
     * Preview a document (image or PDF)
     * @param int $document_id
     * @param bool $thumbnail
     * @return bool Returns false if file not found or not previewable, otherwise outputs file content and returns true.
     */
    public function previewDocument($document_id, $thumbnail = false) {
        $document_info = $this->getDocument($document_id);

        if (!$document_info || !file_exists(DIR_UPLOAD . $document_info['file_path'])) {
            return false;
        }

        $file = DIR_UPLOAD . $document_info['file_path'];
        $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        $is_image = in_array($file_ext, $image_extensions);

        if ($is_image) {
            if ($thumbnail) {
                // Generate and output thumbnail using GD functions
                $thumbnail_width = 200; // Define desired thumbnail width
                $thumbnail_height = 200; // Define desired thumbnail height

                list($width, $height, $type) = getimagesize($file);

                if (!$width || !$height) {
                    return false; // Cannot get image dimensions
                }

                // Calculate new dimensions while maintaining aspect ratio
                $ratio = min($thumbnail_width / $width, $thumbnail_height / $height);
                $new_width = round($width * $ratio);
                $new_height = round($height * $ratio);

                // Create new image resource
                $thumb = imagecreatetruecolor($new_width, $new_height);
                if (!$thumb) return false;

                // Load the source image based on type
                $source = null;
                switch ($type) {
                    case IMAGETYPE_JPEG:
                        $source = imagecreatefromjpeg($file);
                        break;
                    case IMAGETYPE_PNG:
                        $source = imagecreatefrompng($file);
                        // Preserve transparency for PNG
                        imagealphablending($thumb, false);
                        imagesavealpha($thumb, true);
                        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                        imagefilledrectangle($thumb, 0, 0, $new_width, $new_height, $transparent);
                        break;
                    case IMAGETYPE_GIF:
                        $source = imagecreatefromgif($file);
                        break;
                    case IMAGETYPE_BMP:
                         // Need imagecreatefrombmp() if available or a custom function
                         // For simplicity, let's skip BMP thumbnail for now or return false
                         // $source = imagecreatefrombmp($file); // Requires PHP >= 7.2 or GD extension compiled with BMP support
                         return false; // Or handle BMP differently
                         break;
                    default:
                        imagedestroy($thumb);
                        return false; // Unsupported image type for thumbnail
                }

                if (!$source) {
                     imagedestroy($thumb);
                     return false; // Failed to load source image
                }

                // Resize and copy image
                imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                // Output the thumbnail as JPEG
                header('Content-Type: image/jpeg');
                imagejpeg($thumb, null, 90); // Output directly to browser

                // Free memory
                imagedestroy($thumb);
                imagedestroy($source);

                return true;

            } else {
                // Output original image
                header('Content-Type: ' . mime_content_type($file));
                readfile($file);
                return true;
            }
        } elseif ($file_ext === 'pdf') {
            // Output PDF inline
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($document_info['document_name']) . '"');
            readfile($file);
            return true;
        }

        // Cannot preview other file types directly
        return false;
    }

    /**
     * Get documents with permissions check
     * @param int $invoice_id
     * @return array
     */
    public function getDocumentsWithPermissions($invoice_id) {
        $documents = $this->getDocuments($invoice_id);
        $result = [
            'documents' => [],
            'can_delete' => $this->user->hasPermission('modify', 'purchase/supplier_invoice'), // Or a more specific permission
            'can_upload' => $this->user->hasPermission('modify', 'purchase/supplier_invoice'),
            'can_download' => $this->user->hasPermission('access', 'purchase/supplier_invoice'),
            'can_preview' => $this->user->hasPermission('access', 'purchase/supplier_invoice')
        ];

        foreach ($documents as $doc) {
            $fileExt = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
            $doc['icon_class'] = $this->getFileIconClass($fileExt); // Assuming getFileIconClass exists
            $doc['preview_possible'] = in_array($fileExt, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp']);
            $result['documents'][] = $doc;
        }
        return $result;
    }

    /**
     * Get appropriate FontAwesome icon class for a file extension
     * @param string $fileExt
     * @return string
     */
    private function getFileIconClass($fileExt) {
        $fileExt = strtolower($fileExt);
        switch ($fileExt) {
            case 'pdf': return 'fa fa-file-pdf-o text-danger';
            case 'doc': case 'docx': return 'fa fa-file-word-o text-primary';
            case 'xls': case 'xlsx': return 'fa fa-file-excel-o text-success';
            case 'ppt': case 'pptx': return 'fa fa-file-powerpoint-o text-warning';
            case 'jpg': case 'jpeg': case 'png': case 'gif': case 'bmp': return 'fa fa-file-image-o text-info';
            case 'zip': case 'rar': return 'fa fa-file-archive-o text-muted';
            case 'txt': return 'fa fa-file-text-o';
            default: return 'fa fa-file-o';
        }
    }

}
