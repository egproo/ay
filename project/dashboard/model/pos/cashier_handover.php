<?php
class ModelPosCashierHandover extends Model {
    public function addHandover($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "pos_cash_handover SET 
            shift_id = '" . (int)$data['shift_id'] . "', 
            from_user_id = '" . (int)$data['from_user_id'] . "', 
            to_user_id = '" . (int)$data['to_user_id'] . "', 
            amount = '" . (float)$data['amount'] . "', 
            handover_time = NOW(), 
            notes = '" . $this->db->escape($data['notes']) . "'");

        return $this->db->getLastId();
    }

    public function getHandover($handover_id) {
        $query = $this->db->query("SELECT h.*, 
            CONCAT(from_user.firstname, ' ', from_user.lastname) AS from_user, 
            CONCAT(to_user.firstname, ' ', to_user.lastname) AS to_user 
            FROM " . DB_PREFIX . "pos_cash_handover h 
            LEFT JOIN " . DB_PREFIX . "user from_user ON (h.from_user_id = from_user.user_id) 
            LEFT JOIN " . DB_PREFIX . "user to_user ON (h.to_user_id = to_user.user_id) 
            WHERE h.handover_id = '" . (int)$handover_id . "'");

        return $query->row;
    }

    public function getHandovers($data = array()) {
        $sql = "SELECT h.*, 
            CONCAT(from_user.firstname, ' ', from_user.lastname) AS from_user, 
            CONCAT(to_user.firstname, ' ', to_user.lastname) AS to_user 
            FROM " . DB_PREFIX . "pos_cash_handover h 
            LEFT JOIN " . DB_PREFIX . "user from_user ON (h.from_user_id = from_user.user_id) 
            LEFT JOIN " . DB_PREFIX . "user to_user ON (h.to_user_id = to_user.user_id)";

        $sql .= " ORDER BY h.handover_time DESC";

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

    public function getTotalHandovers() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "pos_cash_handover");

        return $query->row['total'];
    }

    public function getHandoversByUser($user_id) {
        $query = $this->db->query("SELECT h.*, 
            CONCAT(from_user.firstname, ' ', from_user.lastname) AS from_user, 
            CONCAT(to_user.firstname, ' ', to_user.lastname) AS to_user 
            FROM " . DB_PREFIX . "pos_cash_handover h 
            LEFT JOIN " . DB_PREFIX . "user from_user ON (h.from_user_id = from_user.user_id) 
            LEFT JOIN " . DB_PREFIX . "user to_user ON (h.to_user_id = to_user.user_id) 
            WHERE h.from_user_id = '" . (int)$user_id . "' OR h.to_user_id = '" . (int)$user_id . "' 
            ORDER BY h.handover_time DESC");

        return $query->rows;
    }

public function getHandoversByShift($shift_id) {
        $query = $this->db->query("SELECT h.*, 
            CONCAT(from_user.firstname, ' ', from_user.lastname) AS from_user, 
            CONCAT(to_user.firstname, ' ', to_user.lastname) AS to_user 
            FROM " . DB_PREFIX . "pos_cash_handover h 
            LEFT JOIN " . DB_PREFIX . "user from_user ON (h.from_user_id = from_user.user_id) 
            LEFT JOIN " . DB_PREFIX . "user to_user ON (h.to_user_id = to_user.user_id) 
            WHERE h.shift_id = '" . (int)$shift_id . "' 
            ORDER BY h.handover_time DESC");

        return $query->rows;
    }

    public function getHandoverTotal($shift_id) {
        $query = $this->db->query("SELECT SUM(amount) AS total FROM " . DB_PREFIX . "pos_cash_handover WHERE shift_id = '" . (int)$shift_id . "'");

        return $query->row['total'];
    }
}