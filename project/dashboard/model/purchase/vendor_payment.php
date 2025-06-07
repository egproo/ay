<?php
class ModelPurchaseVendorPayment extends Model {

    /**
     * Add a new vendor payment
     * @param array $data Payment data including invoice allocations
     * @return int|false New payment ID or false on failure
     * @throws Exception
     */
    public function addPayment($data) {
        $this->load->language('purchase/vendor_payment');

        // Basic validation
        if (empty($data['supplier_id']) || empty($data['payment_date']) || empty($data['payment_method_id']) || !isset($data['amount']) || (float)$data['amount'] <= 0) {
            throw new Exception($this->language->get('error_missing_data'));
        }

        // TODO: Get necessary account codes
        $ap_account_code = $this->config->get('config_accounts_payable_account') ?: '210100'; // Example
        // Get the cash/bank account linked to the payment method
        $payment_method_account_code = $this->getPaymentMethodAccountCode($data['payment_method_id']); 
        if (!$payment_method_account_code) {
             throw new Exception($this->language->get('error_payment_method_account_missing'));
        }
        if (!$ap_account_code) {
             throw new Exception('Accounts Payable account code is not configured.');
        }

        try {
            $this->db->query("START TRANSACTION");

            // 1. Insert Payment Header
            $this->db->query("INSERT INTO " . DB_PREFIX . "vendor_payment SET 
                supplier_id = '" . (int)$data['supplier_id'] . "',
                payment_date = '" . $this->db->escape($data['payment_date']) . "',
                payment_method_id = '" . (int)$data['payment_method_id'] . "',
                amount = '" . (float)$data['amount'] . "',
                currency_id = '" . (int)$data['currency_id'] . "',
                exchange_rate = '" . (float)($data['exchange_rate'] ?? 1.0) . "',
                reference = '" . $this->db->escape($data['reference'] ?? '') . "',
                notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                status = 'posted', /* Assuming payments are posted directly for now */
                user_id = '" . (int)$data['user_id'] . "',
                date_added = NOW()");
                
            $payment_id = $this->db->getLastId();

            if (!$payment_id) {
                throw new Exception('Failed to create vendor payment header.');
            }

            $total_applied_amount = 0;
            $journal_entries = [];

            // 2. Link to Invoices & Update Invoice Statuses
            if (!empty($data['invoice_payments']) && is_array($data['invoice_payments'])) {
                foreach ($data['invoice_payments'] as $invoice_payment) {
                    $invoice_id = (int)$invoice_payment['invoice_id'];
                    $amount_applied = (float)$invoice_payment['amount'];

                    if ($invoice_id <= 0 || $amount_applied <= 0) continue;

                    // Fetch invoice details (amount due)
                    // TODO: Need a function in supplier_invoice model: getInvoiceAmountDue($invoice_id)
                    $invoice_info = $this->model_purchase_supplier_invoice->getInvoice($invoice_id); // Assuming this exists
                    if (!$invoice_info) {
                         throw new Exception(sprintf($this->language->get('error_invoice_not_found_for_payment'), $invoice_id));
                    }
                    
                    // Calculate amount due (simplified - needs proper calculation)
                    $amount_due = (float)$invoice_info['total_amount'] - (float)$invoice_info['amount_paid']; // Assuming amount_paid field exists

                    if ($amount_applied > round($amount_due, 4) + 0.0001) { // Allow for small rounding diff
                         throw new Exception(sprintf($this->language->get('error_payment_exceeds_due_for_invoice'), $amount_applied, $amount_due, $invoice_info['invoice_number']));
                    }

                    // Insert into linking table
                    $this->db->query("INSERT INTO " . DB_PREFIX . "vendor_payment_invoice SET 
                        payment_id = '" . (int)$payment_id . "',
                        invoice_id = '" . (int)$invoice_id . "',
                        amount_applied = '" . $amount_applied . "'");

                    // Update invoice amount paid and status
                    $new_amount_paid = (float)$invoice_info['amount_paid'] + $amount_applied;
                    $new_status = (round($new_amount_paid, 4) >= round((float)$invoice_info['total_amount'], 4)) ? 'paid' : 'partially_paid';

                    $this->db->query("UPDATE " . DB_PREFIX . "supplier_invoice SET 
                        amount_paid = '" . $new_amount_paid . "',
                        status = '" . $this->db->escape($new_status) . "' 
                        WHERE invoice_id = '" . (int)$invoice_id . "'");
                        
                    // Add to total applied amount for journal entry validation
                    $total_applied_amount += $amount_applied;
                }
            }
            
            // Validate total applied amount against payment amount (allow for unapplied amounts if needed)
            // For now, assume total payment amount must be applied or handled as prepayment/credit
            if (round($total_applied_amount, 4) > round((float)$data['amount'], 4)) {
                 throw new Exception($this->language->get('error_applied_exceeds_payment'));
            }
            // TODO: Handle unapplied amounts if necessary (e.g., create supplier credit)

            // 3. Create Journal Entry
            $journal_data = [
                'refnum' => $data['reference'] ?? 'PAY-' . $payment_id,
                'thedate' => $data['payment_date'],
                'description' => sprintf($this->language->get('text_journal_vendor_payment'), $payment_id, $data['supplier_id']), // Improve description
                'entrytype' => 2, // Automatic
                'added_by' => $data['user_id'],
                'entries' => [
                    [ // Debit Accounts Payable
                        'account_code' => $ap_account_code, // Use supplier's specific AP if available
                        'is_debit' => 1,
                        'amount' => (float)$data['amount'] // Debit the full payment amount from AP
                    ],
                    [ // Credit Cash/Bank Account
                        'account_code' => $payment_method_account_code,
                        'is_debit' => 0,
                        'amount' => (float)$data['amount']
                    ]
                ]
             ];

             // Validate balance (should always balance here)
             $debit_total = 0; $credit_total = 0;
             foreach($journal_data['entries'] as $entry) { $entry['is_debit'] ? $debit_total += $entry['amount'] : $credit_total += $entry['amount']; }
             if (abs(round($debit_total, 4) - round($credit_total, 4)) > 0.0001) {
                 throw new Exception('Journal entry for payment does not balance.');
             }

             $journal_id = $this->addJournal($journal_data); // Use helper
             if (!$journal_id) {
                 throw new Exception('Failed to create journal entry for vendor payment.');
             }

             // Link journal entry to the payment
             $this->db->query("UPDATE " . DB_PREFIX . "vendor_payment SET journal_id = '" . (int)$journal_id . "' WHERE payment_id = '" . (int)$payment_id . "'");

            // 4. Add History
            $history_desc = 'Vendor payment created and posted.';
            if ($journal_id) {
                $history_desc .= ' Journal ID: ' . $journal_id;
            }
            $this->addPaymentHistory($payment_id, $data['user_id'], 'create_post', $history_desc);

            $this->db->query("COMMIT");
            
            return $payment_id;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            // Log error $e->getMessage();
            throw $e; // Re-throw for controller
        }
    }

    // Placeholder for editing a vendor payment (might be restricted)
    public function editPayment($payment_id, $data) {
        // TODO: Implement logic if editing is allowed (e.g., changing payment method, reference)
        // Check status before allowing edit
        // Potentially reverse/update journal entries (complex)
        // Add history
        return true; // Placeholder
    }

    // Placeholder for getting a single payment
    public function getPayment($payment_id) {
        // TODO: Implement query to fetch payment details, linked invoices
        return false; // Placeholder
    }

    // Placeholder for getting payment items (linked invoices)
    public function getPaymentItems($payment_id) {
        // TODO: Implement query to fetch linked invoice details for a payment
        return []; // Placeholder
    }

    /**
     * جلب قائمة مدفوعات الموردين
     * @param array $data بيانات الفلترة والترتيب والصفحات
     * @return array
     */
    public function getPayments($data = array()) {
        // Assuming a table structure like 'vendor_payment'
        // Adjust joins and fields based on actual schema
        $sql = "SELECT vp.*, s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name, 
                       pm.name as payment_method_name, c.code as currency_code
                FROM `" . DB_PREFIX . "vendor_payment` vp 
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (vp.supplier_id = s.supplier_id) 
                LEFT JOIN `" . DB_PREFIX . "payment_method` pm ON (vp.payment_method_id = pm.payment_method_id) 
                LEFT JOIN `" . DB_PREFIX . "currency` c ON (vp.currency_id = c.currency_id) 
                WHERE 1=1";

        // Apply filters
        if (!empty($data['filter_payment_id'])) {
            $sql .= " AND vp.payment_id = '" . (int)$data['filter_payment_id'] . "'";
        }
        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND vp.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }
        if (!empty($data['filter_payment_method_id'])) { 
            $sql .= " AND vp.payment_method_id = '" . (int)$data['filter_payment_method_id'] . "'";
        }
        if (!empty($data['filter_status'])) { // Assuming a status field exists
            $sql .= " AND vp.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(vp.payment_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(vp.payment_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sort_data = array(
            'vp.payment_id',
            'vp.payment_date',
            'supplier_name',
            'pm.name',
            'vp.amount',
            'vp.reference',
            'vp.status' 
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY vp.payment_date"; // Default sort
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
     * جلب العدد الإجمالي لمدفوعات الموردين
     * @param array $data بيانات الفلترة
     * @return int
     */
    public function getTotalPayments($data = array()) {
        $sql = "SELECT COUNT(*) AS total 
                FROM `" . DB_PREFIX . "vendor_payment` vp 
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (vp.supplier_id = s.supplier_id) 
                LEFT JOIN `" . DB_PREFIX . "payment_method` pm ON (vp.payment_method_id = pm.payment_method_id) 
                WHERE 1=1";

        // Apply filters
        if (!empty($data['filter_payment_id'])) {
            $sql .= " AND vp.payment_id = '" . (int)$data['filter_payment_id'] . "'";
        }
        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND vp.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }
         if (!empty($data['filter_payment_method_id'])) { 
            $sql .= " AND vp.payment_method_id = '" . (int)$data['filter_payment_method_id'] . "'";
        }
        if (!empty($data['filter_status'])) { // Assuming a status field exists
            $sql .= " AND vp.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(vp.payment_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(vp.payment_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * Delete/Cancel a vendor payment
     * @param int $payment_id
     * @return bool
     * @throws Exception
     */
    public function deletePayment($payment_id) {
        $this->load->language('purchase/vendor_payment');
        $payment_info = $this->getPayment($payment_id); // Assuming getPayment is implemented

        if (!$payment_info) {
            throw new Exception($this->language->get('error_payment_not_found'));
        }

        // Prevent deletion of already processed/posted payments (adjust statuses as needed)
        // For now, assume only 'draft' status can be deleted.
        if (($payment_info['status'] ?? 'draft') != 'draft') { 
             throw new Exception(sprintf($this->language->get('error_delete_status'), $payment_info['status'] ?? 'unknown'));
        }
        
        // IMPORTANT: Deleting a payment SHOULD ideally reverse the journal entry and invoice updates.
        // This is complex. This function currently only removes records.
        // TODO: Implement reversal logic:
        // 1. Get linked invoice payments (vendor_payment_invoice).
        // 2. For each linked invoice, decrease 'amount_paid' and update 'status' back to 'approved' or 'partially_paid'.
        // 3. Reverse the journal entry associated with this payment (if it exists).
        // 4. Delete records from vendor_payment_invoice, vendor_payment_history, vendor_payment.

        try {
            $this->db->query("START TRANSACTION");

            // Delete linked invoice payments (if table exists)
            $this->db->query("DELETE FROM " . DB_PREFIX . "vendor_payment_invoice WHERE payment_id = '" . (int)$payment_id . "'");

            // Delete payment history (if table exists)
            $this->db->query("DELETE FROM " . DB_PREFIX . "vendor_payment_history WHERE payment_id = '" . (int)$payment_id . "'");
            
            // Delete related documents
            // TODO: Implement document deletion if document handling is added

            // Delete the main payment record
            $this->db->query("DELETE FROM " . DB_PREFIX . "vendor_payment WHERE payment_id = '" . (int)$payment_id . "'");

            $this->db->query("COMMIT");
            // History is deleted above.
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }
    
    // Placeholder for posting/approving a payment (if workflow exists)
    public function postPayment($payment_id, $user_id) {
        // TODO: Implement logic to finalize payment, create journal entry if not done on save
        // Add history
        return true; // Placeholder
    }

    /**
     * Add a history record for a vendor payment
     * @param int $payment_id
     * @param int $user_id
     * @param string $action
     * @param string $description
     * @return int History ID
     */
    public function addPaymentHistory($payment_id, $user_id, $action, $description = '') {
        // Assuming a table like vendor_payment_history
        $this->db->query("INSERT INTO " . DB_PREFIX . "vendor_payment_history SET 
            payment_id = '" . (int)$payment_id . "',
            user_id = '" . (int)$user_id . "',
            action = '" . $this->db->escape($action) . "',
            description = '" . $this->db->escape($description) . "',
            created_at = NOW()");
        return $this->db->getLastId();
    }

    /**
     * Get history for a vendor payment
     * @param int $payment_id
     * @return array
     */
    public function getPaymentHistory($payment_id) {
        // TODO: Implement query to fetch history
        return []; // Placeholder
    }
    
    /**
     * Get status text based on status code
     * @param string $status
     * @return string
     */
    public function getStatusText($status) {
        $this->load->language('purchase/vendor_payment');
        $statuses = [
            'draft'     => $this->language->get('text_status_draft'),
            'posted'    => $this->language->get('text_status_posted'),
            'cancelled' => $this->language->get('text_status_cancelled')
        ];
        return $statuses[$status] ?? $status;
    }

    /**
     * Get CSS class based on status code
     * @param string $status
     * @return string
     */
    public function getStatusClass($status) {
        $classes = [
            'draft'     => 'default',
            'posted'    => 'success',
            'cancelled' => 'danger'
        ];
        return $classes[$status] ?? 'default';
    }
    
    /**
     * Get unpaid/partially paid invoices for a specific supplier
     * @param int $supplier_id
     * @return array
     */
    public function getUnpaidInvoicesBySupplier($supplier_id) {
        // TODO: Implement query to fetch approved/partially_paid supplier invoices
        return []; // Placeholder
    }
    
    /**
     * Get available payment methods
     * @return array
     */
    public function getPaymentMethods() {
        // Assuming a simple payment_method table or fetch from finance module
        $query = $this->db->query("SELECT payment_method_id, name FROM " . DB_PREFIX . "payment_method ORDER BY name ASC"); // Adjust table/fields if needed
        return $query->rows;
    }
    
    /**
     * Get the GL account code linked to a payment method
     * @param int $payment_method_id
     * @return string|false Account code or false if not found/linked
     */
    protected function getPaymentMethodAccountCode($payment_method_id) {
         // Assuming payment_method table has an 'account_code' column
         $query = $this->db->query("SELECT account_code FROM " . DB_PREFIX . "payment_method WHERE payment_method_id = '" . (int)$payment_method_id . "'");
         return $query->row ? $query->row['account_code'] : false;
    }
    
    /**
     * Add Journal Entry (Helper - Requires accounts/journal model)
     * @param array $journal_data
     * @return int|false Journal ID or false
     */
    protected function addJournal($journal_data) {
         if (!isset($this->model_accounts_journal)) { // Load if not already loaded
             $this->load->model('accounts/journal');
         }
         return $this->model_accounts_journal->addJournal($journal_data);
    }

}
