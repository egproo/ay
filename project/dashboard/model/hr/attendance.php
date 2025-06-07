<?php
class ModelHrAttendance extends Model {

    public function getTotalAttendance($filter_data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM `cod_attendance` a LEFT JOIN `cod_user` u ON (a.user_id = u.user_id) WHERE 1";

        if (!empty($filter_data['filter_user'])) {
            $sql .= " AND a.user_id = '" . (int)$filter_data['filter_user'] . "'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND a.date >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND a.date <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getAttendance($filter_data = array()) {
        $sql = "SELECT a.*, CONCAT(u.firstname, ' ', u.lastname) AS employee_name FROM `cod_attendance` a LEFT JOIN `cod_user` u ON (a.user_id = u.user_id) WHERE 1";

        if (!empty($filter_data['filter_user'])) {
            $sql .= " AND a.user_id = '" . (int)$filter_data['filter_user'] . "'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND a.date >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND a.date <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        $sort_data = array('employee_name','date','checkin_time','checkout_time','status');
        $sort = (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) ? $filter_data['sort'] : 'date';
        $order = (isset($filter_data['order']) && ($filter_data['order'] == 'desc')) ? 'DESC' : 'ASC';

        $sql .= " ORDER BY " . $sort . " " . $order;

        $start = isset($filter_data['start']) ? (int)$filter_data['start'] : 0;
        $limit = isset($filter_data['limit']) ? (int)$filter_data['limit'] : 10;

        if ($start < 0) { $start = 0; }
        if ($limit < 1) { $limit = 10; }

        $sql .= " LIMIT " . $start . "," . $limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getAttendanceById($attendance_id) {
        $query = $this->db->query("SELECT * FROM `cod_attendance` WHERE attendance_id = '" . (int)$attendance_id . "'");

        return $query->row;
    }

    public function addAttendance($data) {
        $this->db->query("INSERT INTO `cod_attendance` SET user_id = '" . (int)$data['user_id'] . "', 
            `date` = '" . $this->db->escape($data['date']) . "',
            checkin_time = '" . $this->db->escape($data['checkin_time']) . "',
            checkout_time = '" . $this->db->escape($data['checkout_time']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "'");

        return $this->db->getLastId();
    }

    public function editAttendance($attendance_id, $data) {
        $this->db->query("UPDATE `cod_attendance` SET user_id = '" . (int)$data['user_id'] . "', 
            `date` = '" . $this->db->escape($data['date']) . "',
            checkin_time = '" . $this->db->escape($data['checkin_time']) . "',
            checkout_time = '" . $this->db->escape($data['checkout_time']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "'
            WHERE attendance_id = '" . (int)$attendance_id . "'");
    }

    public function deleteAttendance($attendance_id) {
        $this->db->query("DELETE FROM `cod_attendance` WHERE attendance_id = '" . (int)$attendance_id . "'");
    }

}
