<?php
class ModelCrmOpportunity extends Model {
    public function getTotalOpportunities($filter_data = array()) {
        $sql = "SELECT COUNT(*) as total FROM `cod_crm_opportunity` WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND name LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'";
        }

        if (!empty($filter_data['filter_stage'])) {
            $sql .= " AND stage = '" . $this->db->escape($filter_data['filter_stage']) . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getOpportunities($filter_data = array()) {
        $sql = "SELECT * FROM `cod_crm_opportunity` WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND name LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'";
        }

        if (!empty($filter_data['filter_stage'])) {
            $sql .= " AND stage = '" . $this->db->escape($filter_data['filter_stage']) . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $sort_data = array('name','stage','amount','probability','status');
        $sort = (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) ? $filter_data['sort'] : 'name';
        $order = (isset($filter_data['order']) && $filter_data['order'] == 'desc') ? 'DESC' : 'ASC';

        $sql .= " ORDER BY " . $sort . " " . $order;

        $start = isset($filter_data['start']) ? (int)$filter_data['start'] : 0;
        $limit = isset($filter_data['limit']) ? (int)$filter_data['limit'] : 10;

        if ($start < 0) $start = 0;
        if ($limit < 1) $limit = 10;

        $sql .= " LIMIT " . $start . "," . $limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getOpportunity($opportunity_id) {
        $query = $this->db->query("SELECT * FROM `cod_crm_opportunity` WHERE opportunity_id = '" . (int)$opportunity_id . "'");
        return $query->row;
    }

    public function addOpportunity($data) {
        $this->db->query("INSERT INTO `cod_crm_opportunity` SET
          name = '" . $this->db->escape($data['name']) . "',
          stage = '" . $this->db->escape($data['stage']) . "',
          probability = '" . (float)$data['probability'] . "',
          amount = '" . (float)$data['amount'] . "',
          close_date = " . ($data['close_date'] ? "'".$this->db->escape($data['close_date'])."'" : "NULL") . ",
          assigned_to_user_id = " . (int)$data['assigned_to_user_id'] . ",
          status = '" . $this->db->escape($data['status']) . "',
          notes = '" . $this->db->escape($data['notes']) . "'");

        return $this->db->getLastId();
    }

    public function editOpportunity($opportunity_id, $data) {
        $this->db->query("UPDATE `cod_crm_opportunity` SET
          name = '" . $this->db->escape($data['name']) . "',
          stage = '" . $this->db->escape($data['stage']) . "',
          probability = '" . (float)$data['probability'] . "',
          amount = '" . (float)$data['amount'] . "',
          close_date = " . ($data['close_date'] ? "'".$this->db->escape($data['close_date'])."'" : "NULL") . ",
          assigned_to_user_id = " . (int)$data['assigned_to_user_id'] . ",
          status = '" . $this->db->escape($data['status']) . "',
          notes = '" . $this->db->escape($data['notes']) . "',
          date_modified = NOW()
          WHERE opportunity_id = '" . (int)$opportunity_id . "'");
    }

    public function deleteOpportunity($opportunity_id) {
        $this->db->query("DELETE FROM `cod_crm_opportunity` WHERE opportunity_id = '" . (int)$opportunity_id . "'");
    }
}
