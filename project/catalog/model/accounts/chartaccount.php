<?php
class ModelAccountsChartaccount extends Model {
	public function addAccount($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "accounts SET account_code = '" . $this->db->escape($data['account_code']) . "', parent_id = '" . (int)$data['parent_id'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW(), date_added = NOW()");

		$account_id = $this->db->getLastId();

		foreach ($data['account_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "account_description SET account_id = '" . (int)$account_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}


		$this->cache->delete('account');

		return $account_id;
	} 
	

	public function editAccount($account_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "accounts SET account_code = '" . $this->db->escape($data['account_code']) . "', parent_id = '" . (int)$data['parent_id'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE account_id = '" . (int)$account_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "account_description WHERE account_id = '" . (int)$account_id . "'");

		foreach ($data['account_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "account_description SET account_id = '" . (int)$account_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}


		$this->cache->delete('account');
	}

	public function deleteAccount($account_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "accounts WHERE account_id = '" . (int)$account_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "account_description WHERE account_id = '" . (int)$account_id . "'");
		$this->cache->delete('account');
	}

	public function getAccount($account_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "accounts a LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id) WHERE a.account_id = '" . (int)$account_id . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		
		return $query->row;
	}
	public function getAccountsToEntry($data = array()) {
		$sql = "SELECT a.account_id, ad.name, a.account_code,a.status, a.parent_id FROM " . DB_PREFIX . "accounts a LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id) WHERE LENGTH(a.account_code) > '3' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND ad.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sql .= " GROUP BY a.account_id";

		$sort_data = array(
			'name',
			'account_code'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY account_code";
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

				$data['limit'] = 20000;

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}
	public function getAccounts($data = array()) {
		$sql = "SELECT a.account_id, ad.name, a.account_code,a.status, a.parent_id FROM " . DB_PREFIX . "accounts a LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id) WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND ad.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sql .= " GROUP BY a.account_id";

		$sort_data = array(
			'name',
			'account_code'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY account_code";
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

				$data['limit'] = 20000;

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}
	
    public function getAllAccounts($data = array()) {
        $sql = "SELECT a.account_id, a.account_code,a.status,a.parent_id, GROUP_CONCAT(ad.name SEPARATOR '|') AS names, GROUP_CONCAT(ad.language_id SEPARATOR '|') AS language_ids FROM " . DB_PREFIX . "accounts a LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id) GROUP BY a.account_id";
    
        $query = $this->db->query($sql);
        $accounts = array();
        foreach ($query->rows as $row) {
            $names = explode('|', $row['names']);
            $language_ids = explode('|', $row['language_ids']);
            $name_data = array_combine($language_ids, $names);
            $accounts[] = array(
                'account_id' => $row['account_id'],
                'parent_id' => $row['parent_id'],
                'status' => $row['status'],
                'account_code' => $row['account_code'],
                'names' => $name_data
            );
        }
        return $accounts;
    }


	public function getAccountDescriptions($account_id) {
		$account_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "account_description WHERE account_id = '" . (int)$account_id . "'");

		foreach ($query->rows as $result) {
			$account_description_data[$result['language_id']] = array(
				'name'             => $result['name']
			);
		}

		return $account_description_data;
	}

	public function getTotalAccounts() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "accounts");

		return $query->row['total'];
	}
	
	
}