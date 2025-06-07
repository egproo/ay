<?php
class ModelPurchaseReturn extends Model {

    /**
     * Add a new purchase return
     * @param array $data Return data including items
     * @return array|false New return ID and number or false on failure
     * @throws Exception
     */
    public function addReturn($data) {
        $this->load->language('purchase/return'); // Load language for potential errors/history

        // Basic validation (should ideally be more robust in controller)
        if (empty($data['supplier_id']) || empty($data['return_date']) || empty($data['return_reason_id']) || empty($data['return_action_id']) || empty($data['items'])) {
            throw new Exception($this->language->get('error_missing_data'));
        }

        // TODO: Determine the branch_id for inventory updates. Assuming it's linked or a default.
        // For now, let's assume a default branch or it needs to be passed in $data.
        $branch_id = $data['branch_id'] ?? $this->config->get('config_branch_id'); // Example: Get from data or config
        if (!$branch_id) {
             throw new Exception('Branch ID is required for inventory update.');
        }

        // TODO: Get necessary account codes
        $ap_account_code = $this->config->get('config_accounts_payable_account') ?: '210100'; // Example
        $inventory_account_code = $this->config->get('config_inventory_account') ?: '120100'; // Example

        if (!$ap_account_code || !$inventory_account_code) {
             throw new Exception('Accounting codes for purchase return posting are not configured.');
        }

        try {
            $this->db->query("START TRANSACTION");

            // 1. Generate Return Number
            $return_number = $this->generateReturnNumber();

            // 2. Insert Return Header
            $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_return SET
                return_number = '" . $this->db->escape($return_number) . "',
                supplier_id = '" . (int)$data['supplier_id'] . "',
                po_id = '" . (isset($data['po_id']) ? (int)$data['po_id'] : 0) . "',
                goods_receipt_id = '" . (isset($data['goods_receipt_id']) ? (int)$data['goods_receipt_id'] : 0) . "',
                return_date = '" . $this->db->escape($data['return_date']) . "',
                return_reason_id = '" . (int)$data['return_reason_id'] . "',
                return_action_id = '" . (int)$data['return_action_id'] . "',
                return_status_id = '" . (int)($data['return_status_id'] ?: $this->config->get('config_return_status_id')) . "', /* Default pending status */
                comment = '" . $this->db->escape($data['comment'] ?? '') . "',
                user_id = '" . (int)$data['user_id'] . "',
                date_added = NOW(),
                date_modified = NOW()");

            $return_id = $this->db->getLastId();

            if (!$return_id) {
                throw new Exception('Failed to create purchase return header.');
            }

            $total_return_value = 0;
            $journal_entries = [];

            // 3. Insert Return Items & Update Inventory
            foreach ($data['items'] as $item) {
                $quantity_returned = (float)$item['quantity'];
                if ($quantity_returned <= 0) continue; // Skip zero quantity items

                // Get current inventory info (cost and quantity) for the specific branch
                $inventory_info = $this->getInventoryInfo($item['product_id'], $item['unit_id'], $branch_id);
                $current_wac = $inventory_info ? (float)$inventory_info['average_cost'] : 0;
                $quantity_before = $inventory_info ? (float)$inventory_info['quantity'] : 0;

                // Calculate value of returned items based on current WAC
                $item_return_value = $quantity_returned * $current_wac;
                $total_return_value += $item_return_value;

                // Insert return item
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_return_item SET
                    return_id = '" . (int)$return_id . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . $quantity_returned . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    price = '" . $current_wac . "', /* Store the cost at time of return */
                    total = '" . $item_return_value . "',
                    reason = '" . $this->db->escape($item['reason'] ?? '') . "'");

                $return_item_id = $this->db->getLastId();

                // Update Inventory Quantity (Decrease)
                $new_quantity = $quantity_before - $quantity_returned;
                // Note: WAC generally doesn't change on returns, only quantity and total value.
                // However, if new_quantity becomes 0, the cost might be reset or handled differently.
                // For simplicity, we just update quantity here. Cost recalculation happens on next receipt.
                if ($inventory_info) {
                     $this->db->query("UPDATE " . DB_PREFIX . "product_inventory SET
                        quantity = '" . $new_quantity . "',
                        quantity_available = quantity_available - '" . $quantity_returned . "' /* Adjust available too */
                        WHERE product_inventory_id = '" . (int)$inventory_info['product_inventory_id'] . "'");
                } else {
                    // This case (returning item not in inventory) should ideally be prevented or handled carefully.
                    // Log a warning or throw an error? For now, log potential issue.
                    error_log("Warning: Purchase Return ID {$return_id} - Item ID {$return_item_id} (Product {$item['product_id']}) not found in inventory for branch {$branch_id}.");
                    // Optionally insert with negative quantity if allowed:
                    // $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory SET product_id=..., quantity = -" . $quantity_returned . ", ...");
                }

                // Add Product Movement Record
                $this->addInventoryMovement(array(
                    'product_id' => $item['product_id'],
                    'type' => 'return', /* Use 'return' or 'purchase_return' */
                    'movement_reference_type' => 'purchase_return',
                    'movement_reference_id' => $return_id,
                    'quantity' => -$quantity_returned, /* Negative quantity for return */
                    'unit_cost' => $current_wac, /* Cost at time of return */
                    'unit_id' => $item['unit_id'],
                    'branch_id' => $branch_id,
                    'reference' => $return_number,
                    'old_average_cost' => $current_wac, /* WAC before movement */
                    'new_average_cost' => $current_wac, /* WAC typically unchanged by return */
                    'user_id' => $data['user_id'],
                    'cost_before_movement' => $current_wac,
                    'cost_after_movement' => $current_wac,
                    'movement_value' => -$item_return_value, /* Negative value */
                    'effect_on_cost' => 'no_change'
                ));

                 // Add to journal entries (Credit Inventory)
                 if ($item_return_value > 0) {
                     $journal_entries[] = [
                         'account_code' => $inventory_account_code,
                         'is_debit' => 0,
                         'amount' => $item_return_value
                     ];
                 }
            } // End foreach item

            // 4. Create Journal Entry (if total value > 0)
            $journal_id = null;
            if ($total_return_value > 0) {
                 // Add Debit entry for Accounts Payable
                 $journal_entries[] = [
                     'account_code' => $ap_account_code, // Use supplier's specific AP if available
                     'is_debit' => 1,
                     'amount' => $total_return_value
                 ];

                 $journal_data = [
                    'refnum' => $return_number,
                    'thedate' => $data['return_date'],
                    'description' => sprintf($this->language->get('text_journal_purchase_return'), $return_number, $data['supplier_id']), // Improve description
                    'entrytype' => 2, // Automatic
                    'added_by' => $data['user_id'],
                    'entries' => $journal_entries
                 ];

                 // Validate balance
                 $debit_total = 0; $credit_total = 0;
                 foreach($journal_data['entries'] as $entry) { $entry['is_debit'] ? $debit_total += $entry['amount'] : $credit_total += $entry['amount']; }
                 if (abs(round($debit_total, 4) - round($credit_total, 4)) > 0.0001) {
                     throw new Exception('Journal entry for return does not balance.');
                 }

                 $journal_id = $this->addJournal($journal_data); // Use helper
                 if (!$journal_id) {
                     throw new Exception('Failed to create journal entry for purchase return.');
                 }

                 // Link journal entry to the return
                 $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET journal_id = '" . (int)$journal_id . "' WHERE return_id = '" . (int)$return_id . "'");
            }

            // 5. Add History
            $history_desc = 'Purchase return created.';
            if ($journal_id) {
                $history_desc .= ' Journal ID: ' . $journal_id;
            }
            $this->addReturnHistory($return_id, $data['user_id'], 'create', $history_desc);

            $this->db->query("COMMIT");

            return ['return_id' => $return_id, 'return_number' => $return_number]; // Return ID and Number

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            // Log error $e->getMessage();
            throw $e; // Re-throw for controller
        }
    }

    /**
     * Edit an existing purchase return
     * @param int $return_id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function editReturn($return_id, $data) {
        $this->load->language('purchase/return');
        $return_info = $this->getReturn($return_id);

        if (!$return_info) {
            throw new Exception($this->language->get('error_return_not_found'));
        }

        // Check status before allowing edit (e.g., only allow editing in pending status)
        $pending_status_id = $this->config->get('config_return_status_id') ?: 1; // Default to 1 if not set
        if ($return_info['return_status_id'] != $pending_status_id) {
            throw new Exception($this->language->get('error_edit_status'));
        }

        // Basic validation
        if (empty($data['supplier_id']) || empty($data['return_date']) || empty($data['return_reason_id']) || empty($data['return_action_id']) || empty($data['items'])) {
            throw new Exception($this->language->get('error_missing_data'));
        }

        // IMPORTANT: Editing a return that has already affected inventory/accounting is complex.
        // This implementation assumes editing only happens before completion/processing.
        // Reversing previous inventory/journal entries is NOT handled here.
        // TODO: Add more robust logic if editing processed returns is required.

        try {
            $this->db->query("START TRANSACTION");

            // Update Return Header
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET
                supplier_id = '" . (int)$data['supplier_id'] . "',
                po_id = '" . (isset($data['po_id']) ? (int)$data['po_id'] : 0) . "',
                goods_receipt_id = '" . (isset($data['goods_receipt_id']) ? (int)$data['goods_receipt_id'] : 0) . "',
                return_date = '" . $this->db->escape($data['return_date']) . "',
                return_reason_id = '" . (int)$data['return_reason_id'] . "',
                return_action_id = '" . (int)$data['return_action_id'] . "',
                comment = '" . $this->db->escape($data['comment'] ?? '') . "',
                date_modified = NOW()
                WHERE return_id = '" . (int)$return_id . "'");

            // Get existing items to compare quantities (needed if inventory reversal were implemented)
            // $existing_items = $this->getReturnItems($return_id);

            // Delete existing items
            $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_return_item WHERE return_id = '" . (int)$return_id . "'");

            // Re-insert items (without re-affecting inventory/accounting in this simplified version)
            $total_return_value = 0; // Recalculate value based on potentially changed items/prices
            foreach ($data['items'] as $item) {
                $quantity_returned = (float)$item['quantity'];
                if ($quantity_returned <= 0) continue;

                // Use the price stored during the initial return creation or fetch current WAC again?
                // For simplicity, let's assume price doesn't change on edit, or it needs recalculation logic.
                // Using a placeholder price for now.
                $item_price = (float)($item['price'] ?? 0); // Get price if available in data, else 0
                $item_return_value = $quantity_returned * $item_price;
                $total_return_value += $item_return_value;

                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_return_item SET
                    return_id = '" . (int)$return_id . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . $quantity_returned . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    price = '" . $item_price . "',
                    total = '" . $item_return_value . "',
                    reason = '" . $this->db->escape($item['reason'] ?? '') . "'");
            }

            // Update total amount on header (optional, could be calculated on view)
            // $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET total_amount = '" . $total_return_value . "' WHERE return_id = '" . (int)$return_id . "'");


            // Add history
            $this->addReturnHistory($return_id, $data['user_id'], 'edit', 'Purchase return updated.');

            $this->db->query("COMMIT");
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Get details of a specific purchase return
     * @param int $return_id
     * @return array|false
     */
    public function getReturn($return_id) {
        $query = $this->db->query("SELECT pr.*,
                                          s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          po.po_number,
                                          gr.receipt_number,
                                          rs.name as status_name,
                                          rr.name as reason_name,
                                          ra.name as action_name
                                   FROM `" . DB_PREFIX . "purchase_return` pr
                                   LEFT JOIN `" . DB_PREFIX . "supplier` s ON (pr.supplier_id = s.supplier_id)
                                   LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (pr.po_id = po.po_id)
                                   LEFT JOIN `" . DB_PREFIX . "goods_receipt` gr ON (pr.goods_receipt_id = gr.goods_receipt_id)
                                   LEFT JOIN `" . DB_PREFIX . "return_status` rs ON (pr.return_status_id = rs.return_status_id AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                   LEFT JOIN `" . DB_PREFIX . "return_reason` rr ON (pr.return_reason_id = rr.return_reason_id AND rr.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                   LEFT JOIN `" . DB_PREFIX . "return_action` ra ON (pr.return_action_id = ra.return_action_id AND ra.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                   WHERE pr.return_id = '" . (int)$return_id . "'");
        return $query->row;
    }

    /**
     * Get items for a specific purchase return
     * @param int $return_id
     * @return array
     */
    public function getReturnItems($return_id) {
        $query = $this->db->query("SELECT pri.*, pd.name as product_name, u.desc_en as unit_name
                                   FROM " . DB_PREFIX . "purchase_return_item pri
                                   LEFT JOIN " . DB_PREFIX . "product_description pd ON (pri.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                   LEFT JOIN " . DB_PREFIX . "unit u ON (pri.unit_id = u.unit_id)
                                   WHERE pri.return_id = '" . (int)$return_id . "' ORDER BY pri.return_item_id ASC");
        return $query->rows;
    }

    /**
     * جلب قائمة مرتجعات المشتريات
     * @param array $data بيانات الفلترة والترتيب والصفحات
     * @return array
     */
    public function getReturns($data = array()) {
        $sql = "SELECT pr.*, s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                       po.po_number, rs.name as status_name
                FROM `" . DB_PREFIX . "purchase_return` pr
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (pr.supplier_id = s.supplier_id)
                LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (pr.po_id = po.po_id)
                LEFT JOIN `" . DB_PREFIX . "return_status` rs ON (pr.return_status_id = rs.return_status_id AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "')
                WHERE 1=1";

        // Apply filters
        if (!empty($data['filter_return_id'])) {
            $sql .= " AND pr.return_id = '" . (int)$data['filter_return_id'] . "'";
        }
        if (!empty($data['filter_po_id'])) {
            $sql .= " AND pr.po_id = '" . (int)$data['filter_po_id'] . "'";
        }
        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND pr.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }
        if (!empty($data['filter_return_status_id'])) { // Filter by status ID
            $sql .= " AND pr.return_status_id = '" . (int)$data['filter_return_status_id'] . "'";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pr.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pr.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sort_data = array(
            'pr.return_id',
            'po.po_number',
            'supplier_name',
            'rs.name', // Status name
            'pr.date_added',
            'pr.date_modified'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pr.date_added"; // Default sort
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
     * جلب العدد الإجمالي لمرتجعات المشتريات
     * @param array $data بيانات الفلترة
     * @return int
     */
    public function getTotalReturns($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM `" . DB_PREFIX . "purchase_return` pr
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (pr.supplier_id = s.supplier_id)
                LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (pr.po_id = po.po_id)
                WHERE 1=1";

        // Apply filters
        if (!empty($data['filter_return_id'])) {
            $sql .= " AND pr.return_id = '" . (int)$data['filter_return_id'] . "'";
        }
        if (!empty($data['filter_po_id'])) {
            $sql .= " AND pr.po_id = '" . (int)$data['filter_po_id'] . "'";
        }
        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND pr.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }
        if (!empty($data['filter_return_status_id'])) { // Filter by status ID
            $sql .= " AND pr.return_status_id = '" . (int)$data['filter_return_status_id'] . "'";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pr.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pr.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * Delete a purchase return
     * @param int $return_id
     * @return bool
     * @throws Exception
     */
    public function deleteReturn($return_id) {
        $this->load->language('purchase/return');
        $return_info = $this->getReturn($return_id);

        if (!$return_info) {
            throw new Exception($this->language->get('error_return_not_found'));
        }

        // Prevent deletion of completed or processed returns (adjust statuses as needed)
        // Assuming config_return_status_id holds the ID for the initial/pending status
        $pending_status_id = $this->config->get('config_return_status_id') ?: 1; // Default to 1 if not set
        if ($return_info['return_status_id'] != $pending_status_id) {
             throw new Exception(sprintf($this->language->get('error_delete_status'), $return_info['status_name']));
        }

        // IMPORTANT: Deleting a return SHOULD ideally reverse the inventory and journal entries created by addReturn.
        // This is complex and requires careful handling of costs and quantities.
        // For now, this delete function only removes the records, it DOES NOT reverse inventory/accounting.
        // TODO: Implement reversal logic if required (e.g., reverse inventory movement, reverse journal entry).

        try {
            $this->db->query("START TRANSACTION");

            // Delete return items
            $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_return_item WHERE return_id = '" . (int)$return_id . "'");

            // Delete return history
            $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_return_history WHERE return_id = '" . (int)$return_id . "'");

            // Delete related documents
            // TODO: Implement document deletion if document handling is added

            // Delete the main return record
            $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_return WHERE return_id = '" . (int)$return_id . "'");

            $this->db->query("COMMIT");
            // History is deleted above, so no need to add a delete history record here.
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Approve a purchase return
     * @param int $return_id
     * @param int $user_id
     * @return bool
     * @throws Exception
     */
    public function approveReturn($return_id, $user_id) {
        $this->load->language('purchase/return');
        $return_info = $this->getReturn($return_id);

        if (!$return_info) {
            throw new Exception($this->language->get('error_return_not_found'));
        }

        // Check if return can be approved (e.g., only pending returns)
        $pending_status_id = $this->config->get('config_return_status_id') ?: 1;
        if ($return_info['return_status_id'] != $pending_status_id) {
            throw new Exception($this->language->get('error_already_processed'));
        }

        try {
            $this->db->query("START TRANSACTION");

            // Update status to approved (assuming status ID 2 is approved)
            $approved_status_id = 2; // Adjust based on your status configuration
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET
                return_status_id = '" . (int)$approved_status_id . "',
                date_modified = NOW()
                WHERE return_id = '" . (int)$return_id . "'");

            // Add history
            $this->addReturnHistory($return_id, $user_id, 'approve', $this->language->get('text_approve_success'));

            $this->db->query("COMMIT");
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Reject a purchase return
     * @param int $return_id
     * @param string $reason
     * @param int $user_id
     * @return bool
     * @throws Exception
     */
    public function rejectReturn($return_id, $reason, $user_id) {
        $this->load->language('purchase/return');
        $return_info = $this->getReturn($return_id);

        if (!$return_info) {
            throw new Exception($this->language->get('error_return_not_found'));
        }

        // Check if return can be rejected (e.g., only pending returns)
        $pending_status_id = $this->config->get('config_return_status_id') ?: 1;
        if ($return_info['return_status_id'] != $pending_status_id) {
            throw new Exception($this->language->get('error_already_processed'));
        }

        try {
            $this->db->query("START TRANSACTION");

            // Update status to rejected (assuming status ID 3 is rejected)
            $rejected_status_id = 3; // Adjust based on your status configuration
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET
                return_status_id = '" . (int)$rejected_status_id . "',
                comment = CONCAT(COALESCE(comment, ''), '\nRejection Reason: ', '" . $this->db->escape($reason) . "'),
                date_modified = NOW()
                WHERE return_id = '" . (int)$return_id . "'");

            // Add history
            $history_desc = $this->language->get('text_reject_success');
            if ($reason) {
                $history_desc .= ': ' . $reason;
            }
            $this->addReturnHistory($return_id, $user_id, 'reject', $history_desc);

            $this->db->query("COMMIT");
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Create credit note for a purchase return
     * @param int $return_id
     * @param int $user_id
     * @return array|false
     * @throws Exception
     */
    public function createCreditNote($return_id, $user_id) {
        $this->load->language('purchase/return');
        $return_info = $this->getReturn($return_id);

        if (!$return_info) {
            throw new Exception($this->language->get('error_return_not_found'));
        }

        // Check if return is approved
        $approved_status_id = 2; // Adjust based on your status configuration
        if ($return_info['return_status_id'] != $approved_status_id) {
            throw new Exception($this->language->get('error_not_approved'));
        }

        // Check if credit note already exists
        $existing_credit = $this->db->query("SELECT credit_note_id FROM " . DB_PREFIX . "supplier_credit_note
            WHERE reference_type = 'purchase_return' AND reference_id = '" . (int)$return_id . "'");
        if ($existing_credit->num_rows) {
            throw new Exception($this->language->get('error_credit_note_exists'));
        }

        try {
            $this->db->query("START TRANSACTION");

            // Get return items for credit note
            $return_items = $this->getReturnItems($return_id);
            $total_amount = 0;

            foreach ($return_items as $item) {
                $total_amount += $item['total'];
            }

            // Generate credit note number
            $credit_note_number = $this->generateCreditNoteNumber();

            // Create credit note
            $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_credit_note SET
                credit_note_number = '" . $this->db->escape($credit_note_number) . "',
                supplier_id = '" . (int)$return_info['supplier_id'] . "',
                reference_type = 'purchase_return',
                reference_id = '" . (int)$return_id . "',
                credit_date = NOW(),
                total_amount = '" . (float)$total_amount . "',
                status = 'pending',
                created_by = '" . (int)$user_id . "',
                date_added = NOW()");

            $credit_note_id = $this->db->getLastId();

            // Create credit note items
            foreach ($return_items as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_credit_note_item SET
                    credit_note_id = '" . (int)$credit_note_id . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . (float)$item['quantity'] . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    unit_price = '" . (float)$item['price'] . "',
                    total = '" . (float)$item['total'] . "'");
            }

            // Add history to return
            $this->addReturnHistory($return_id, $user_id, 'credit_note_created',
                'Credit note created: ' . $credit_note_number);

            $this->db->query("COMMIT");

            return array(
                'credit_note_id' => $credit_note_id,
                'credit_note_number' => $credit_note_number,
                'total_amount' => $total_amount
            );

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Complete a return (e.g., after items shipped back)
     * @param int $return_id
     * @param int $user_id
     * @return bool
     * @throws Exception
     */
    public function completeReturn($return_id, $user_id) {
        $this->load->language('purchase/return');
        $return_info = $this->getReturn($return_id);

        if (!$return_info) {
            throw new Exception($this->language->get('error_return_not_found'));
        }

        try {
            $this->db->query("START TRANSACTION");

            // Update status to completed (assuming status ID 4 is completed)
            $completed_status_id = 4; // Adjust based on your status configuration
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_return SET
                return_status_id = '" . (int)$completed_status_id . "',
                date_modified = NOW()
                WHERE return_id = '" . (int)$return_id . "'");

            // Add history
            $this->addReturnHistory($return_id, $user_id, 'complete', $this->language->get('text_complete_success'));

            $this->db->query("COMMIT");
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Generate a unique credit note number
     * @return string
     */
    protected function generateCreditNoteNumber() {
        $prefix = 'CN-P-'; // Purchase Credit Note Prefix
        $date_prefix = date('Ym');
        $query = $this->db->query("SELECT MAX(CAST(SUBSTRING(credit_note_number, " . (strlen($prefix . $date_prefix) + 1) . ") AS UNSIGNED)) AS max_number
                                  FROM `" . DB_PREFIX . "supplier_credit_note`
                                  WHERE credit_note_number LIKE '" . $prefix . $date_prefix . "%'");
        $max_number = $query->row['max_number'] ?? 0;
        $next_number = $max_number + 1;
        return $prefix . $date_prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Add a history record for a purchase return
     * @param int $return_id
     * @param int $user_id
     * @param string $action
     * @param string $description
     * @return int History ID
     */
    public function addReturnHistory($return_id, $user_id, $action, $description = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_return_history SET
            return_id = '" . (int)$return_id . "',
            user_id = '" . (int)$user_id . "',
            action = '" . $this->db->escape($action) . "',
            description = '" . $this->db->escape($description) . "',
            created_at = NOW()");
        return $this->db->getLastId();
    }

    /**
     * Get history for a purchase return
     * @param int $return_id
     * @return array
     */
    public function getReturnHistory($return_id) {
        $query = $this->db->query("SELECT prh.*, CONCAT(u.firstname, ' ', u.lastname) AS user_name
                                   FROM " . DB_PREFIX . "purchase_return_history prh
                                   LEFT JOIN " . DB_PREFIX . "user u ON (prh.user_id = u.user_id)
                                   WHERE prh.return_id = '" . (int)$return_id . "'
                                   ORDER BY prh.created_at DESC");
        return $query->rows;
    }

    /**
     * Get available return reasons
     * @return array
     */
    public function getReturnReasons() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "return_reason WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name ASC");
        return $query->rows;
    }

    /**
     * Get available return actions
     * @return array
     */
    public function getReturnActions() {
         $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "return_action WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name ASC");
        return $query->rows;
    }

    /**
     * Get available return statuses
     * @return array
     */
    public function getReturnStatuses() {
         $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "return_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name ASC");
        return $query->rows;
    }

    /**
     * Get status text based on status ID
     * @param int $status_id
     * @return string
     */
    public function getStatusText($status_id) {
        $this->load->language('purchase/return');
        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "return_status WHERE return_status_id = '" . (int)$status_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->row ? $query->row['name'] : $this->language->get('text_unknown_status');
    }

    /**
     * Get CSS class based on status ID (example implementation)
     * @param int $status_id
     * @return string
     */
    public function getStatusClass($status_id) {
        // Example mapping - adjust based on actual statuses used
        $status_classes = [
            $this->config->get('config_return_status_id') => 'warning', // Pending
            // Add other statuses like 'completed', 'cancelled'
            // e.g., 5 => 'success', // Completed
            // e.g., 6 => 'danger', // Cancelled
        ];
        return $status_classes[$status_id] ?? 'default';
    }

    /**
     * Generate a unique return number
     * @return string
     */
    protected function generateReturnNumber() {
        $prefix = 'RTN-P-'; // Purchase Return Prefix
        $date_prefix = date('Ym');
        $query = $this->db->query("SELECT MAX(CAST(SUBSTRING(return_number, " . (strlen($prefix . $date_prefix) + 1) . ") AS UNSIGNED)) AS max_number
                                  FROM `" . DB_PREFIX . "purchase_return`
                                  WHERE return_number LIKE '" . $prefix . $date_prefix . "%'");
        $max_number = $query->row['max_number'] ?? 0;
        $next_number = $max_number + 1;
        return $prefix . $date_prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    // Helper function to get products received on a specific GRN or PO (for return selection)
    public function getReceivableProducts($filter = []) {
        // TODO: Implement logic to find products eligible for return based on GRN or PO
        // This needs to check goods_receipt_item and potentially subtract quantities already returned.
        return []; // Placeholder
    }

    /**
     * Get inventory info for a product/unit/branch
     * @param int $product_id
     * @param int $unit_id
     * @param int $branch_id
     * @return array|false
     */
    protected function getInventoryInfo($product_id, $unit_id, $branch_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_inventory
            WHERE product_id = '" . (int)$product_id . "'
            AND unit_id = '" . (int)$unit_id . "'
            AND branch_id = '" . (int)$branch_id . "'");

        return $query->num_rows ? $query->row : false;
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

    /**
     * Add Inventory Movement Record (Helper)
     * @param array $movement_data
     * @return int Movement ID
     */
     protected function addInventoryMovement($movement_data) {
         $this->db->query("INSERT INTO " . DB_PREFIX . "product_movement SET
            product_id = '" . (int)$movement_data['product_id'] . "',
            type = '" . $this->db->escape($movement_data['type']) . "',
            movement_reference_type = '" . $this->db->escape($movement_data['movement_reference_type'] ?? $movement_data['type']) . "',
            movement_reference_id = '" . (int)($movement_data['movement_reference_id'] ?? 0) . "',
            date_added = NOW(),
            quantity = '" . (float)$movement_data['quantity'] . "',
            unit_cost = '" . (float)($movement_data['unit_cost'] ?? 0) . "',
            unit_id = '" . (int)$movement_data['unit_id'] . "',
            branch_id = '" . (int)($movement_data['branch_id'] ?? $this->config->get('config_branch_id')) . "',
            reference = '" . $this->db->escape($movement_data['reference'] ?? '') . "',
            old_average_cost = '" . (float)($movement_data['old_average_cost'] ?? 0) . "',
            new_average_cost = '" . (float)($movement_data['new_average_cost'] ?? 0) . "',
            user_id = '" . (int)($movement_data['user_id'] ?? $this->user->getId()) . "',
            cost_before_movement = '" . (float)($movement_data['cost_before_movement'] ?? 0) . "',
            cost_after_movement = '" . (float)($movement_data['cost_after_movement'] ?? 0) . "',
            movement_value = '" . (float)($movement_data['movement_value'] ?? 0) . "',
            effect_on_cost = '" . $this->db->escape($movement_data['effect_on_cost'] ?? 'no_change') . "'");

         return $this->db->getLastId();
     }

}
