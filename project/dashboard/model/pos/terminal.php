<?php
class ModelPosTerminal extends Model {
    public function addTerminal($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "pos_terminal SET 
            name = '" . $this->db->escape($data['name']) . "', 
            branch_id = '" . (int)$data['branch_id'] . "', 
            status = '" . (int)$data['status'] . "', 
            printer_type = '" . $this->db->escape($data['printer_type']) . "', 
            printer_name = '" . $this->db->escape($data['printer_name']) . "'");

        return $this->db->getLastId();
    }

    public function editTerminal($terminal_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "pos_terminal SET 
            name = '" . $this->db->escape($data['name']) . "', 
            branch_id = '" . (int)$data['branch_id'] . "', 
            status = '" . (int)$data['status'] . "', 
            printer_type = '" . $this->db->escape($data['printer_type']) . "', 
            printer_name = '" . $this->db->escape($data['printer_name']) . "' 
            WHERE terminal_id = '" . (int)$terminal_id . "'");
    }

    public function deleteTerminal($terminal_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "pos_terminal WHERE terminal_id = '" . (int)$terminal_id . "'");
    }

    public function getTerminal($terminal_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "pos_terminal WHERE terminal_id = '" . (int)$terminal_id . "'");

        return $query->row;
    }

    public function getTerminals($data = array()) {
        $sql = "SELECT t.*, b.name AS branch_name 
                FROM " . DB_PREFIX . "pos_terminal t 
                LEFT JOIN " . DB_PREFIX . "branch b ON (t.branch_id = b.branch_id)";

        $sort_data = array(
            'name',
            'branch_name',
            'status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY t.name";
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

    public function getTerminalsByBranch($branch_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "pos_terminal WHERE branch_id = '" . (int)$branch_id . "' AND status = '1'");

        return $query->rows;
    }

    public function getTotalTerminals() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "pos_terminal");

        return $query->row['total'];
    }
}