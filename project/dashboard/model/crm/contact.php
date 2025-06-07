<?php
class ModelCrmContact extends Model {
    public function getTotalContacts($filter_data = array()) {
        $sql = "SELECT COUNT(*) as total FROM `cod_crm_contact` WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND (firstname LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%' OR lastname LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%')";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getContacts($filter_data = array()) {
        $sql = "SELECT * FROM `cod_crm_contact` WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND (firstname LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%' OR lastname LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%')";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $sort_data = array('firstname','email','phone','position','status');
        $sort = (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) ? $filter_data['sort'] : 'firstname';
        $order = (isset($filter_data['order']) && $filter_data['order'] == 'desc') ? 'DESC' : 'ASC';

        if ($sort == 'firstname') {
            $sql .= " ORDER BY firstname " . $order . ", lastname " . $order;
        } else {
            $sql .= " ORDER BY " . $sort . " " . $order;
        }

        $start = isset($filter_data['start']) ? (int)$filter_data['start'] : 0;
        $limit = isset($filter_data['limit']) ? (int)$filter_data['limit'] : 10;

        if ($start < 0) $start = 0;
        if ($limit < 1) $limit = 10;

        $sql .= " LIMIT " . $start . "," . $limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getContact($contact_id) {
        $query = $this->db->query("SELECT * FROM `cod_crm_contact` WHERE contact_id = '" . (int)$contact_id . "'");
        return $query->row;
    }

    public function addContact($data) {
        $this->db->query("INSERT INTO `cod_crm_contact` SET
          firstname = '" . $this->db->escape($data['firstname']) . "',
          lastname = '" . $this->db->escape($data['lastname']) . "',
          email = '" . $this->db->escape($data['email']) . "',
          phone = '" . $this->db->escape($data['phone']) . "',
          position = '" . $this->db->escape($data['position']) . "',
          assigned_to_user_id = " . (int)$data['assigned_to_user_id'] . ",
          status = '" . $this->db->escape($data['status']) . "',
          notes = '" . $this->db->escape($data['notes']) . "'");

        return $this->db->getLastId();
    }

    public function editContact($contact_id, $data) {
        $this->db->query("UPDATE `cod_crm_contact` SET
          firstname = '" . $this->db->escape($data['firstname']) . "',
          lastname = '" . $this->db->escape($data['lastname']) . "',
          email = '" . $this->db->escape($data['email']) . "',
          phone = '" . $this->db->escape($data['phone']) . "',
          position = '" . $this->db->escape($data['position']) . "',
          assigned_to_user_id = " . (int)$data['assigned_to_user_id'] . ",
          status = '" . $this->db->escape($data['status']) . "',
          notes = '" . $this->db->escape($data['notes']) . "',
          date_modified = NOW()
          WHERE contact_id = '" . (int)$contact_id . "'");
    }

    public function deleteContact($contact_id) {
        $this->db->query("DELETE FROM `cod_crm_contact` WHERE contact_id = '" . (int)$contact_id . "'");
    }
}
