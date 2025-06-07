<?php
class ModelBranchBranch extends Model {
public function addBranch($data) {
    $this->db->query("INSERT INTO " . DB_PREFIX . "branch SET 
        name = '" . $this->db->escape($data['name']) . "', 
        type = '" . $this->db->escape($data['type']) . "', 
        eta_branch_id = '" . $this->db->escape($data['eta_branch_id']) . "', 
        available_online = '" . (int)$data['available_online'] . "',
        telephone = '" . $this->db->escape($data['telephone']) . "',
        email = '" . $this->db->escape($data['email']) . "',
        manager_id = '" . (int)$data['manager_id'] . "'");

    $branch_id = $this->db->getLastId();

    $this->db->query("INSERT INTO " . DB_PREFIX . "branch_address SET 
        branch_id = '" . (int)$branch_id . "', 
        firstname = '" . $this->db->escape($data['firstname']) . "', 
        lastname = '" . $this->db->escape($data['lastname']) . "', 
        company = '" . $this->db->escape($data['company']) . "', 
        address_1 = '" . $this->db->escape($data['address_1']) . "', 
        address_2 = '" . $this->db->escape($data['address_2']) . "', 
        city = '" . $this->db->escape($data['city']) . "', 
        postcode = '" . $this->db->escape($data['postcode']) . "', 
        country_id = '63', 
        zone_id = '" . (int)$data['zone_id'] . "'");

    $this->db->query("UPDATE " . DB_PREFIX . "branch SET address_id = '" . (int)$this->db->getLastId() . "' WHERE branch_id = '" . (int)$branch_id . "'");

    return $branch_id;
}

public function getMainBranch() {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "branch WHERE eta_branch_id = '0' ORDER BY branch_id ASC LIMIT 1");
    return $query->row;
}


public function editBranch($branch_id, $data) {
    $this->db->query("UPDATE " . DB_PREFIX . "branch SET 
        name = '" . $this->db->escape($data['name']) . "', 
        type = '" . $this->db->escape($data['type']) . "', 
        eta_branch_id = '" . $this->db->escape($data['eta_branch_id']) . "', 
        available_online = '" . (int)$data['available_online'] . "',
        telephone = '" . $this->db->escape($data['telephone']) . "',
        email = '" . $this->db->escape($data['email']) . "',
        manager_id = '" . (int)$data['manager_id'] . "' 
        WHERE branch_id = '" . (int)$branch_id . "'");

    $this->db->query("UPDATE " . DB_PREFIX . "branch_address SET 
        firstname = '" . $this->db->escape($data['firstname']) . "', 
        lastname = '" . $this->db->escape($data['lastname']) . "', 
        company = '" . $this->db->escape($data['company']) . "', 
        address_1 = '" . $this->db->escape($data['address_1']) . "', 
        address_2 = '" . $this->db->escape($data['address_2']) . "', 
        city = '" . $this->db->escape($data['city']) . "', 
        postcode = '" . $this->db->escape($data['postcode']) . "', 
        country_id = '63', 
        zone_id = '" . (int)$data['zone_id'] . "' 
        WHERE branch_id = '" . (int)$branch_id . "'");
}

    public function deleteBranch($branch_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "branch WHERE branch_id = '" . (int)$branch_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "branch_address WHERE branch_id = '" . (int)$branch_id . "'");
        $this->db->query("UPDATE " . DB_PREFIX . "user SET branch_id = '0' WHERE branch_id = '" . (int)$branch_id . "'");
    }

    public function getBranch($branch_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "branch b LEFT JOIN " . DB_PREFIX . "branch_address ba ON (b.branch_id = ba.branch_id) WHERE b.branch_id = '" . (int)$branch_id . "'");
        return $query->row;
    }

    public function getBranches($data = array()) {
        $sql = "SELECT b.*, ba.address_1, ba.address_2, ba.city, ba.postcode, c.name AS country, z.name AS zone FROM " . DB_PREFIX . "branch b LEFT JOIN " . DB_PREFIX . "branch_address ba ON (b.branch_id = ba.branch_id) LEFT JOIN " . DB_PREFIX . "country c ON (ba.country_id = c.country_id) LEFT JOIN " . DB_PREFIX . "zone z ON (ba.zone_id = z.zone_id) WHERE 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND b.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND b.type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        $sort_data = array(
            'name',
            'type',
            'address_1',
            'address_2',
            'telephone',
            'email',
            'city',
            'postcode',
            'country',
            'zone'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY b.name";
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

    public function getTotalBranches($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "branch WHERE 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
}
