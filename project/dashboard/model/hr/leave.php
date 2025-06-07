<?php
class ModelHrLeave extends Model {

    public function getTotalLeaveRequests($filter_data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM `cod_leave_request` lr 
                LEFT JOIN `cod_user` u ON (lr.user_id = u.user_id)
                LEFT JOIN `cod_leave_type` lt ON (lr.leave_type_id = lt.leave_type_id)
                WHERE 1";

        if (!empty($filter_data['filter_user'])) {
            $sql .= " AND lr.user_id = '" . (int)$filter_data['filter_user'] . "'";
        }

        if (!empty($filter_data['filter_leave_type'])) {
            $sql .= " AND lr.leave_type_id = '" . (int)$filter_data['filter_leave_type'] . "'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND lr.start_date >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND lr.end_date <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND lr.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getLeaveRequests($filter_data = array()) {
        $sql = "SELECT lr.*, CONCAT(u.firstname,' ',u.lastname) as employee_name, lt.name as leave_type_name 
                FROM `cod_leave_request` lr
                LEFT JOIN `cod_user` u ON (lr.user_id = u.user_id)
                LEFT JOIN `cod_leave_type` lt ON (lr.leave_type_id = lt.leave_type_id)
                WHERE 1";

        if (!empty($filter_data['filter_user'])) {
            $sql .= " AND lr.user_id = '" . (int)$filter_data['filter_user'] . "'";
        }

        if (!empty($filter_data['filter_leave_type'])) {
            $sql .= " AND lr.leave_type_id = '" . (int)$filter_data['filter_leave_type'] . "'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND lr.start_date >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND lr.end_date <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND lr.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $sort_data = array('employee_name','leave_type_name','start_date','end_date','status');
        $sort = (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) ? $filter_data['sort'] : 'start_date';
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

    public function getLeaveRequestById($leave_request_id) {
        $query = $this->db->query("SELECT * FROM `cod_leave_request` WHERE leave_request_id = '" . (int)$leave_request_id . "'");
        return $query->row;
    }

    public function addLeaveRequest($data) {
        $this->db->query("INSERT INTO `cod_leave_request` SET 
            user_id = '" . (int)$data['user_id'] . "',
            leave_type_id = '" . (int)$data['leave_type_id'] . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            reason = '" . $this->db->escape($data['reason']) . "',
            approved_by = '" . (int)$data['approved_by'] . "'");

        return $this->db->getLastId();
    }

    public function editLeaveRequest($leave_request_id, $data) {
        $this->db->query("UPDATE `cod_leave_request` SET 
            user_id = '" . (int)$data['user_id'] . "',
            leave_type_id = '" . (int)$data['leave_type_id'] . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            reason = '" . $this->db->escape($data['reason']) . "',
            approved_by = '" . (int)$data['approved_by'] . "'
            WHERE leave_request_id = '" . (int)$leave_request_id . "'");
    }

    public function deleteLeaveRequest($leave_request_id) {
        $this->db->query("DELETE FROM `cod_leave_request` WHERE leave_request_id = '" . (int)$leave_request_id . "'");
    }

    public function getLeaveTypes() {
        $query = $this->db->query("SELECT * FROM `cod_leave_type` WHERE status = '1' ORDER BY name");
        return $query->rows;
    }

}
