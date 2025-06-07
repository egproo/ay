<?php
/**
 * Accounting Manager Model
 *
 * This model handles all accounting operations related to inventory movements
 * and other financial transactions in the system.
 */
class ModelAccountingAccountingManager extends Model {
    /**
     * Create a journal entry
     *
     * @param array $data Journal entry data
     * @return int Journal ID
     */
    public function createJournalEntry($data) {
        try {
            // Check if date is in an open period
            $date = isset($data['date_added']) ? $data['date_added'] : date('Y-m-d H:i:s');
            $date_only = date('Y-m-d', strtotime($date));

            $this->load->model('accounting/period');
            $period_info = $this->model_accounting_period->getPeriodForDate($date_only);

            if (!$period_info) {
                // No period found for this date
                $this->log->write("Error in createJournalEntry: No accounting period found for date " . $date_only);
                return false;
            }

            if ($period_info['status'] != 0) {
                // Period is closed or locked
                $this->log->write("Error in createJournalEntry: Accounting period is closed or locked for date " . $date_only);
                return false;
            }

            $this->db->query("START TRANSACTION");

            // Create journal header
            $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_journal SET
                reference_type = '" . $this->db->escape($data['reference_type']) . "',
                reference_id = '" . (int)$data['reference_id'] . "',
                period_id = '" . (int)$period_info['period_id'] . "',
                description = '" . $this->db->escape($data['description']) . "',
                date_added = '" . $this->db->escape($date) . "',
                user_id = '" . (int)($data['user_id'] ?? $this->user->getId()) . "',
                status = '" . (int)($data['status'] ?? 1) . "'");

            $journal_id = $this->db->getLastId();

            // Create journal details (debits and credits)
            if (isset($data['entries']) && is_array($data['entries'])) {
                foreach ($data['entries'] as $entry) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_journal_entry SET
                        journal_id = '" . (int)$journal_id . "',
                        account_id = '" . (int)$entry['account_id'] . "',
                        debit = '" . (float)($entry['debit'] ?? 0) . "',
                        credit = '" . (float)($entry['credit'] ?? 0) . "',
                        description = '" . $this->db->escape($entry['description'] ?? '') . "'");
                }
            }

            $this->db->query("COMMIT");

            return $journal_id;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $this->log->write("Error in createJournalEntry: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get journal entries by reference
     *
     * @param string $reference_type Reference type
     * @param int $reference_id Reference ID
     * @return array Journal entries
     */
    public function getJournalEntriesByReference($reference_type, $reference_id) {
        $query = $this->db->query("SELECT j.*, u.username
            FROM " . DB_PREFIX . "accounting_journal j
            LEFT JOIN " . DB_PREFIX . "user u ON (j.user_id = u.user_id)
            WHERE j.reference_type = '" . $this->db->escape($reference_type) . "'
            AND j.reference_id = '" . (int)$reference_id . "'
            ORDER BY j.date_added DESC");

        $journals = $query->rows;

        // Get journal details
        foreach ($journals as &$journal) {
            $query = $this->db->query("SELECT je.*, a.name as account_name, a.code as account_code
                FROM " . DB_PREFIX . "accounting_journal_entry je
                LEFT JOIN " . DB_PREFIX . "accounting_account a ON (je.account_id = a.account_id)
                WHERE je.journal_id = '" . (int)$journal['journal_id'] . "'
                ORDER BY je.journal_entry_id");

            $journal['entries'] = $query->rows;
        }

        return $journals;
    }

    /**
     * Get journal entries for a specific journal
     *
     * @param int $journal_id Journal ID
     * @return array Journal entries
     */
    public function getJournalEntries($journal_id) {
        $query = $this->db->query("SELECT je.*, a.name as account_name, a.code as account_code
            FROM " . DB_PREFIX . "accounting_journal_entry je
            LEFT JOIN " . DB_PREFIX . "accounting_account a ON (je.account_id = a.account_id)
            WHERE je.journal_id = '" . (int)$journal_id . "'
            ORDER BY je.journal_entry_id");

        return $query->rows;
    }

    /**
     * Get accounting accounts
     *
     * @param array $data Filter data
     * @return array Accounting accounts
     */
    public function getAccounts($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "accounting_account";

        $sort_data = array(
            'code',
            'name',
            'type',
            'parent_id'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY code";
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
     * Get account by ID
     *
     * @param int $account_id Account ID
     * @return array Account data
     */
    public function getAccount($account_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_account
            WHERE account_id = '" . (int)$account_id . "'");

        return $query->row;
    }

    /**
     * Get account by code
     *
     * @param string $code Account code
     * @return array Account data
     */
    public function getAccountByCode($code) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_account
            WHERE code = '" . $this->db->escape($code) . "'");

        return $query->row;
    }

    /**
     * Get accounting settings
     *
     * @return array Accounting settings
     */
    public function getAccountingSettings() {
        $settings = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting
            WHERE `code` = 'accounting'");

        foreach ($query->rows as $result) {
            if (!$result['serialized']) {
                $settings[$result['key']] = $result['value'];
            } else {
                $settings[$result['key']] = json_decode($result['value'], true);
            }
        }

        return $settings;
    }

    /**
     * Get inventory account mappings
     *
     * @return array Account mappings
     */
    public function getInventoryAccountMappings() {
        $mappings = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "accounting_inventory_mapping");

        foreach ($query->rows as $row) {
            $mappings[$row['transaction_type']] = array(
                'inventory_account_id' => $row['inventory_account_id'],
                'contra_account_id' => $row['contra_account_id'],
                'description' => $row['description']
            );
        }

        return $mappings;
    }

    /**
     * Create inventory journal entry
     *
     * @param array $data Movement data
     * @return int|bool Journal ID or false on failure
     */
    public function createInventoryJournalEntry($data) {
        try {
            // Get account mappings
            $mappings = $this->getInventoryAccountMappings();

            // Determine transaction type
            $transaction_type = $data['movement_type'];

            // Check if mapping exists
            if (!isset($mappings[$transaction_type])) {
                $this->log->write("No account mapping found for transaction type: " . $transaction_type);
                return false;
            }

            $mapping = $mappings[$transaction_type];

            // Calculate amount
            $amount = abs($data['value_change']);

            if ($amount <= 0) {
                return false; // No financial impact
            }

            // Create journal entries
            $journal_data = array(
                'reference_type' => 'inventory_movement',
                'reference_id' => $data['movement_id'],
                'description' => $mapping['description'] . ' - ' . $data['product_name'],
                'date_added' => $data['date_added'],
                'user_id' => $data['user_id'],
                'entries' => array()
            );

            // Determine debit and credit accounts based on movement type
            if (in_array($transaction_type, array('purchase', 'adjustment_increase', 'transfer_in', 'initial', 'return_in', 'production'))) {
                // Increase inventory: Debit inventory, Credit contra account
                $journal_data['entries'][] = array(
                    'account_id' => $mapping['inventory_account_id'],
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Inventory increase: ' . $data['product_name']
                );

                $journal_data['entries'][] = array(
                    'account_id' => $mapping['contra_account_id'],
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Contra entry for inventory increase: ' . $data['product_name']
                );
            } else {
                // Decrease inventory: Debit contra account, Credit inventory
                $journal_data['entries'][] = array(
                    'account_id' => $mapping['contra_account_id'],
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Contra entry for inventory decrease: ' . $data['product_name']
                );

                $journal_data['entries'][] = array(
                    'account_id' => $mapping['inventory_account_id'],
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Inventory decrease: ' . $data['product_name']
                );
            }

            // Create journal entry
            return $this->createJournalEntry($journal_data);
        } catch (Exception $e) {
            $this->log->write("Error in createInventoryJournalEntry: " . $e->getMessage());
            return false;
        }
    }
}
