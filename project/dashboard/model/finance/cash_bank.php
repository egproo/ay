<?php
class ModelFinanceCashBank extends Model {
    public function getCashAccounts() {
        $sql = "SELECT c.*, CONCAT(u.firstname, ' ', u.lastname) as responsible_user 
                FROM " . DB_PREFIX . "cash c 
                LEFT JOIN " . DB_PREFIX . "user u ON (c.responsible_user_id = u.user_id)";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getBankAccounts() {
        $sql = "SELECT * FROM " . DB_PREFIX . "bank_account";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getCashTransactions($data = array()) {
        $sql = "SELECT ct.*, c.name as cash_name, CONCAT(u.firstname, ' ', u.lastname) as created_by 
                FROM " . DB_PREFIX . "cash_transaction ct 
                LEFT JOIN " . DB_PREFIX . "cash c ON (ct.cash_id = c.cash_id) 
                LEFT JOIN " . DB_PREFIX . "user u ON (ct.created_by = u.user_id) 
                WHERE 1=1";

        if (!empty($data['filter_cash_id'])) {
            $sql .= " AND ct.cash_id = '" . (int)$data['filter_cash_id'] . "'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND ct.transaction_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(ct.created_at) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(ct.created_at) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " ORDER BY ct.created_at DESC";

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

    public function getBankTransactions($data = array()) {
        $sql = "SELECT bt.*, ba.bank_name, ba.currency, CONCAT(u.firstname, ' ', u.lastname) as created_by 
                FROM " . DB_PREFIX . "bank_transaction bt 
                LEFT JOIN " . DB_PREFIX . "bank_account ba ON (bt.bank_account_id = ba.account_id) 
                LEFT JOIN " . DB_PREFIX . "user u ON (bt.created_by = u.user_id) 
                WHERE 1=1";

        if (!empty($data['filter_bank_account_id'])) {
            $sql .= " AND bt.bank_account_id = '" . (int)$data['filter_bank_account_id'] . "'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND bt.transaction_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(bt.transaction_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(bt.transaction_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " ORDER BY bt.transaction_date DESC";

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

    public function getCashInfo($cash_id) {
        $sql = "SELECT c.*, CONCAT(u.firstname, ' ', u.lastname) as responsible_user 
                FROM " . DB_PREFIX . "cash c 
                LEFT JOIN " . DB_PREFIX . "user u ON (c.responsible_user_id = u.user_id) 
                WHERE c.cash_id = '" . (int)$cash_id . "'";
        $query = $this->db->query($sql);
        return $query->row;
    }

    public function getBankInfo($bank_account_id) {
        $sql = "SELECT * FROM " . DB_PREFIX . "bank_account 
                WHERE account_id = '" . (int)$bank_account_id . "'";
        $query = $this->db->query($sql);
        return $query->row;
    }

    public function getCashTransactionsByCash($cash_id) {
        $sql = "SELECT ct.*, CONCAT(u.firstname, ' ', u.lastname) as created_by 
                FROM " . DB_PREFIX . "cash_transaction ct 
                LEFT JOIN " . DB_PREFIX . "user u ON (ct.created_by = u.user_id) 
                WHERE ct.cash_id = '" . (int)$cash_id . "' 
                ORDER BY ct.created_at DESC";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getBankTransactionsByAccount($bank_account_id) {
        $sql = "SELECT bt.*, CONCAT(u.firstname, ' ', u.lastname) as created_by 
                FROM " . DB_PREFIX . "bank_transaction bt 
                LEFT JOIN " . DB_PREFIX . "user u ON (bt.created_by = u.user_id) 
                WHERE bt.bank_account_id = '" . (int)$bank_account_id . "' 
                ORDER BY bt.transaction_date DESC";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getBankReconciliations($bank_account_id) {
        $sql = "SELECT br.*, CONCAT(u.firstname, ' ', u.lastname) as created_by 
                FROM " . DB_PREFIX . "bank_reconciliation br 
                LEFT JOIN " . DB_PREFIX . "user u ON (br.created_by = u.user_id) 
                WHERE br.bank_account_id = '" . (int)$bank_account_id . "' 
                ORDER BY br.statement_date DESC";
        $query = $this->db->query($sql);
        return $query->rows;
    }
}