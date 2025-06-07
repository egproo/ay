<?php
class ModelPosShift extends Model {
    public function addShift($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "pos_shift SET 
            user_id = '" . (int)$data['user_id'] . "', 
            branch_id = '" . (int)$data['branch_id'] . "', 
            terminal_id = '" . (int)$data['terminal_id'] . "', 
            start_time = NOW(), 
            starting_cash = '" . (float)$data['starting_cash'] . "', 
            notes = '" . $this->db->escape($data['notes']) . "', 
            status = 'active'");

        return $this->db->getLastId();
    }

    public function endShift($shift_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "pos_shift SET 
            end_time = NOW(), 
            ending_cash = '" . (float)$data['ending_cash'] . "', 
            expected_cash = '" . (float)$data['expected_cash'] . "', 
            cash_difference = '" . (float)$data['cash_difference'] . "', 
            notes = CONCAT(notes, '\n', '" . $this->db->escape($data['notes']) . "'), 
            status = '" . (($data['cash_difference'] == 0) ? 'balanced' : 'closed') . "' 
            WHERE shift_id = '" . (int)$shift_id . "'");
    }

    public function getShift($shift_id) {
        $query = $this->db->query("SELECT s.*, 
            CONCAT(u.firstname, ' ', u.lastname) AS user_name, 
            b.name AS branch_name, 
            t.name AS terminal_name 
            FROM " . DB_PREFIX . "pos_shift s 
            LEFT JOIN " . DB_PREFIX . "user u ON (s.user_id = u.user_id) 
            LEFT JOIN " . DB_PREFIX . "branch b ON (s.branch_id = b.branch_id) 
            LEFT JOIN " . DB_PREFIX . "pos_terminal t ON (s.terminal_id = t.terminal_id) 
            WHERE s.shift_id = '" . (int)$shift_id . "'");

        return $query->row;
    }

    public function getShifts($data = array()) {
        $sql = "SELECT s.*, 
            CONCAT(u.firstname, ' ', u.lastname) AS user_name, 
            b.name AS branch_name, 
            t.name AS terminal_name 
            FROM " . DB_PREFIX . "pos_shift s 
            LEFT JOIN " . DB_PREFIX . "user u ON (s.user_id = u.user_id) 
            LEFT JOIN " . DB_PREFIX . "branch b ON (s.branch_id = b.branch_id) 
            LEFT JOIN " . DB_PREFIX . "pos_terminal t ON (s.terminal_id = t.terminal_id)";

        $sql .= " ORDER BY s.start_time DESC";

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

    public function getTotalShifts() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "pos_shift");

        return $query->row['total'];
    }

    public function getActiveShiftByUser($user_id) {
        $query = $this->db->query("SELECT s.*, 
            CONCAT(u.firstname, ' ', u.lastname) AS user_name, 
            b.name AS branch_name, 
            t.name AS terminal_name 
            FROM " . DB_PREFIX . "pos_shift s 
            LEFT JOIN " . DB_PREFIX . "user u ON (s.user_id = u.user_id) 
            LEFT JOIN " . DB_PREFIX . "branch b ON (s.branch_id = b.branch_id) 
            LEFT JOIN " . DB_PREFIX . "pos_terminal t ON (s.terminal_id = t.terminal_id) 
            WHERE s.user_id = '" . (int)$user_id . "' AND s.status = 'active'");

        return $query->row;
    }

    public function isTerminalInUse($terminal_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "pos_shift WHERE terminal_id = '" . (int)$terminal_id . "' AND status = 'active'");

        return $query->row['total'] > 0;
    }

    public function getShiftById($shift_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "pos_shift WHERE shift_id = '" . (int)$shift_id . "'");

        return $query->row;
    }

    public function getShiftsByDateRange($start_date, $end_date) {
        $query = $this->db->query("SELECT s.*, 
            CONCAT(u.firstname, ' ', u.lastname) AS user_name, 
            b.name AS branch_name, 
            t.name AS terminal_name 
            FROM " . DB_PREFIX . "pos_shift s 
            LEFT JOIN " . DB_PREFIX . "user u ON (s.user_id = u.user_id) 
            LEFT JOIN " . DB_PREFIX . "branch b ON (s.branch_id = b.branch_id) 
            LEFT JOIN " . DB_PREFIX . "pos_terminal t ON (s.terminal_id = t.terminal_id) 
            WHERE s.start_time BETWEEN '" . $this->db->escape($start_date) . " 00:00:00' AND '" . $this->db->escape($end_date) . " 23:59:59'
            ORDER BY s.start_time DESC");

        return $query->rows;
    }
}