<?php
class ModelPosTransaction extends Model {
    public function addTransaction($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "pos_transaction SET 
            shift_id = '" . (int)$data['shift_id'] . "', 
            order_id = '" . (isset($data['order_id']) ? (int)$data['order_id'] : 'NULL') . "', 
            type = '" . $this->db->escape($data['type']) . "', 
            payment_method = '" . $this->db->escape($data['payment_method']) . "', 
            amount = '" . (float)$data['amount'] . "', 
            reference = '" . $this->db->escape($data['reference']) . "', 
            notes = '" . $this->db->escape($data['notes']) . "', 
            created_at = NOW()");

        return $this->db->getLastId();
    }

    public function getTransaction($transaction_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "pos_transaction WHERE transaction_id = '" . (int)$transaction_id . "'");

        return $query->row;
    }

    public function getTransactionsByShift($shift_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "pos_transaction WHERE shift_id = '" . (int)$shift_id . "' ORDER BY created_at DESC");

        return $query->rows;
    }

    public function getTransactionsTotalByType($shift_id, $type) {
        $query = $this->db->query("SELECT SUM(amount) AS total FROM " . DB_PREFIX . "pos_transaction WHERE shift_id = '" . (int)$shift_id . "' AND type = '" . $this->db->escape($type) . "'");

        return $query->row['total'] ? $query->row['total'] : 0;
    }


    public function getPaymentMethodSummary($shift_id) {
        $query = $this->db->query("SELECT payment_method, SUM(amount) AS total, COUNT(*) AS count FROM " . DB_PREFIX . "pos_transaction WHERE shift_id = '" . (int)$shift_id . "' AND type = 'sale' GROUP BY payment_method");

        return $query->rows;
    }

    public function getTransactionsByDateRange($start_date, $end_date, $filter = array()) {
        $sql = "SELECT t.*, s.user_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name 
                FROM " . DB_PREFIX . "pos_transaction t 
                LEFT JOIN " . DB_PREFIX . "pos_shift s ON (t.shift_id = s.shift_id) 
                LEFT JOIN " . DB_PREFIX . "user u ON (s.user_id = u.user_id) 
                WHERE t.created_at BETWEEN '" . $this->db->escape($start_date) . " 00:00:00' AND '" . $this->db->escape($end_date) . " 23:59:59'";
        
        if (!empty($filter['type'])) {
            $sql .= " AND t.type = '" . $this->db->escape($filter['type']) . "'";
        }
        
        if (!empty($filter['payment_method'])) {
            $sql .= " AND t.payment_method = '" . $this->db->escape($filter['payment_method']) . "'";
        }
        
        if (!empty($filter['user_id'])) {
            $sql .= " AND s.user_id = '" . (int)$filter['user_id'] . "'";
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getCashSalesTotal($shift_id) {
        $query = $this->db->query("SELECT SUM(amount) AS total FROM " . DB_PREFIX . "pos_transaction WHERE shift_id = '" . (int)$shift_id . "' AND type = 'sale' AND payment_method = 'cash'");

        return $query->row['total'] ? $query->row['total'] : 0;
    }

    public function getTransactionsTotal($filter) {
        $sql = "SELECT SUM(amount) AS total FROM " . DB_PREFIX . "pos_transaction WHERE 1";
        
        if (!empty($filter['shift_id'])) {
            $sql .= " AND shift_id = '" . (int)$filter['shift_id'] . "'";
        }
        
        if (!empty($filter['type'])) {
            $sql .= " AND type = '" . $this->db->escape($filter['type']) . "'";
        }
        
        if (!empty($filter['payment_method'])) {
            $sql .= " AND payment_method = '" . $this->db->escape($filter['payment_method']) . "'";
        }
        
        $query = $this->db->query($sql);

        return $query->row['total'] ? $query->row['total'] : 0;
    }
    
}







