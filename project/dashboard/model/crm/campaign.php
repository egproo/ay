<?php
class ModelCrmCampaign extends Model {
    public function getTotalCampaigns($filter_data = array()) {
        $sql = "SELECT COUNT(*) as total FROM `cod_crm_campaign` WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND name LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'";
        }

        if (!empty($filter_data['filter_date_start']) && !empty($filter_data['filter_date_end'])) {
            $sql .= " AND ((start_date BETWEEN '".$this->db->escape($filter_data['filter_date_start'])."' AND '".$this->db->escape($filter_data['filter_date_end'])."')
                     OR (end_date BETWEEN '".$this->db->escape($filter_data['filter_date_start'])."' AND '".$this->db->escape($filter_data['filter_date_end'])."'))";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getCampaigns($filter_data = array()) {
        $sql = "SELECT * FROM `cod_crm_campaign` WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND name LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'";
        }

        if (!empty($filter_data['filter_date_start']) && !empty($filter_data['filter_date_end'])) {
            // إذا محدد فترة، نعرض الحملات التي تقع تواريخها ضمن هذه الفترة
            $sql .= " AND ((start_date BETWEEN '".$this->db->escape($filter_data['filter_date_start'])."' AND '".$this->db->escape($filter_data['filter_date_end'])."')
                     OR (end_date BETWEEN '".$this->db->escape($filter_data['filter_date_start'])."' AND '".$this->db->escape($filter_data['filter_date_end'])."'))";
        }

        $sort_data = array('name','type','start_date','end_date','budget','status');
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

    public function getCampaign($campaign_id) {
        $query = $this->db->query("SELECT * FROM `cod_crm_campaign` WHERE campaign_id = '" . (int)$campaign_id . "'");
        return $query->row;
    }

    public function addCampaign($data) {
        $this->db->query("INSERT INTO `cod_crm_campaign` SET
          name = '".$this->db->escape($data['name'])."',
          type = '".$this->db->escape($data['type'])."',
          start_date = '".$this->db->escape($data['start_date'])."',
          end_date = '".$this->db->escape($data['end_date'])."',
          budget = '".(float)$data['budget']."',
          code = '".$this->db->escape($data['code'])."',
          status = '".$this->db->escape($data['status'])."',
          assigned_to_user_id = ".(int)$data['assigned_to_user_id'].",
          actual_spend = '".(float)$data['actual_spend']."',
          invoice_reference = '".$this->db->escape($data['invoice_reference'])."',
          add_expense = '".(int)$data['add_expense']."',
          notes = '".$this->db->escape($data['notes'])."'");
        
        return $this->db->getLastId();
    }

    public function editCampaign($campaign_id, $data) {
        $this->db->query("UPDATE `cod_crm_campaign` SET
          name = '".$this->db->escape($data['name'])."',
          type = '".$this->db->escape($data['type'])."',
          start_date = '".$this->db->escape($data['start_date'])."',
          end_date = '".$this->db->escape($data['end_date'])."',
          budget = '".(float)$data['budget']."',
          code = '".$this->db->escape($data['code'])."',
          status = '".$this->db->escape($data['status'])."',
          assigned_to_user_id = ".(int)$data['assigned_to_user_id'].",
          actual_spend = '".(float)$data['actual_spend']."',
          invoice_reference = '".$this->db->escape($data['invoice_reference'])."',
          add_expense = '".(int)$data['add_expense']."',
          notes = '".$this->db->escape($data['notes'])."',
          date_modified = NOW()
          WHERE campaign_id = '".(int)$campaign_id."'");
    }

    public function deleteCampaign($campaign_id) {
        $this->db->query("DELETE FROM `cod_crm_campaign` WHERE campaign_id = '".(int)$campaign_id."'");
    }
}
