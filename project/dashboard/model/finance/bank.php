<?php
class ModelFinanceBank extends Model {
    public function addBank($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "bank SET 
            name = '" . $this->db->escape($data['name']) . "',
            account_number = '" . $this->db->escape($data['account_number']) . "',
            branch = '" . $this->db->escape($data['branch']) . "',
            swift_code = '" . $this->db->escape($data['swift_code']) . "',
            address = '" . $this->db->escape($data['address']) . "',
            status = '" . (int)$data['status'] . "',
            date_added = NOW()");

        $bank_id = $this->db->getLastId();

        // Add related account
        $account_code = $this->generateAccountCode();
        $account_data = array(
            'account_code' => $account_code,
            'parent_id' => 1121, // Assuming 1121 is the parent account for banks
            'status' => 1,
            'account_description' => array(
                1 => array('name' => $data['name'])
            )
        );
        $this->load->model('accounts/chartaccount');
        $this->model_accounts_chartaccount->addAccount($account_data);

        $this->db->query("UPDATE " . DB_PREFIX . "bank SET account_code = '" . (int)$account_code . "' WHERE bank_id = '" . (int)$bank_id . "'");

        return $bank_id;
    }

    public function editBank($bank_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "bank SET 
            name = '" . $this->db->escape($data['name']) . "',
            account_number = '" . $this->db->escape($data['account_number']) . "',
            branch = '" . $this->db->escape($data['branch']) . "',
            swift_code = '" . $this->db->escape($data['swift_code']) . "',
            address = '" . $this->db->escape($data['address']) . "',
            status = '" . (int)$data['status'] . "'
            WHERE bank_id = '" . (int)$bank_id . "'");
    }

    public function deleteBank($bank_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "bank WHERE bank_id = '" . (int)$bank_id . "'");
    }

    public function getBank($bank_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "bank WHERE bank_id = '" . (int)$bank_id . "'");
        return $query->row;
    }

    public function getBanks($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "bank";

        if (!empty($data['filter_name'])) {
            $sql .= " WHERE name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        $sort_data = array(
            'name',
            'account_number',
            'status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
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

    public function getTotalBanks() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "bank");
        return $query->row['total'];
    }

    private function generateAccountCode() {
        $base_code = 1121; // Modify based on your account structure
        $last_account_code = $this->db->query("SELECT MAX(account_code) AS max_code FROM " . DB_PREFIX . "bank WHERE account_code LIKE '1121%'")->row['max_code'];
        $new_code = $last_account_code ? intval($last_account_code) + 1 : $base_code . '0000001';
        return $new_code;
    }
}