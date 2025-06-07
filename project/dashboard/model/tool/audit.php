<?php
class ModelToolAudit extends Model {
    public function log($action, $reference_type, $reference_id = null, $before_data = null, $after_data = null) {
        $user_id = (int)$this->user->getId();
        $action = $this->db->escape($action);
        $reference_type = $this->db->escape($reference_type);
        $reference_id = $reference_id ? (int)$reference_id : 'NULL';
        $before = $before_data ? $this->db->escape(json_encode($before_data)) : null;
        $after = $after_data ? $this->db->escape(json_encode($after_data)) : null;

        $this->db->query("INSERT INTO " . DB_PREFIX . "audit_log SET 
                          user_id={$user_id}, 
                          action='{$action}', 
                          reference_type='{$reference_type}', 
                          reference_id={$reference_id}, 
                          before_data=" . ($before ? "'$before'" : 'NULL') . ",
                          after_data=" . ($after ? "'$after'" : 'NULL') . ",
                          timestamp=NOW()");
    }

    public function getLogs($filter = array()) {
        $sql = "SELECT al.*, u.username FROM " . DB_PREFIX . "audit_log al 
                LEFT JOIN " . DB_PREFIX . "user u ON (al.user_id = u.user_id)
                WHERE 1";

        if (!empty($filter['filter_user_id'])) {
            $sql .= " AND al.user_id=".(int)$filter['filter_user_id'];
        }
        if (!empty($filter['filter_action'])) {
            $sql .= " AND al.action LIKE '%".$this->db->escape($filter['filter_action'])."%'";
        }
        if (!empty($filter['filter_reference_type'])) {
            $sql .= " AND al.reference_type='".$this->db->escape($filter['filter_reference_type'])."'";
        }
        if (!empty($filter['filter_date_start'])) {
            $sql .= " AND DATE(al.timestamp)>='".$this->db->escape($filter['filter_date_start'])."'";
        }
        if (!empty($filter['filter_date_end'])) {
            $sql .= " AND DATE(al.timestamp)<='".$this->db->escape($filter['filter_date_end'])."'";
        }

        $sql .= " ORDER BY al.timestamp DESC";

        if (isset($filter['start']) && isset($filter['limit'])) {
            $start = (int)$filter['start'];
            $limit = (int)$filter['limit'];
            $sql .= " LIMIT $start,$limit";
        }

        return $this->db->query($sql)->rows;
    }

    public function getTotalLogs($filter = array()) {
        $sql = "SELECT COUNT(*) as total FROM " . DB_PREFIX . "audit_log al WHERE 1";
        if (!empty($filter['filter_user_id'])) {
            $sql .= " AND al.user_id=".(int)$filter['filter_user_id'];
        }
        if (!empty($filter['filter_action'])) {
            $sql .= " AND al.action LIKE '%".$this->db->escape($filter['filter_action'])."%'";
        }
        if (!empty($filter['filter_reference_type'])) {
            $sql .= " AND al.reference_type='".$this->db->escape($filter['filter_reference_type'])."'";
        }
        if (!empty($filter['filter_date_start'])) {
            $sql .= " AND DATE(al.timestamp)>='".$this->db->escape($filter['filter_date_start'])."'";
        }
        if (!empty($filter['filter_date_end'])) {
            $sql .= " AND DATE(al.timestamp)<='".$this->db->escape($filter['filter_date_end'])."'";
        }

        $res = $this->db->query($sql);
        return $res->row['total'];
    }

    public function deleteLog($log_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "audit_log WHERE log_id=".(int)$log_id);
    }
}
