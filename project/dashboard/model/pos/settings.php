<?php
class ModelPosSettings extends Model {
    public function getSettings() {
        $settings = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = 'pos'");

        foreach ($query->rows as $result) {
            if (!$result['serialized']) {
                $settings[$result['key']] = $result['value'];
            } else {
                $settings[$result['key']] = json_decode($result['value'], true);
            }
        }

        return $settings;
    }

    public function getSetting($key, $store_id = 0) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");

        if ($query->num_rows) {
            if (!$query->row['serialized']) {
                return $query->row['value'];
            } else {
                return json_decode($query->row['value'], true);
            }
        } else {
            return null;
        }
    }

    public function editSetting($code, $data, $store_id = 0) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

        foreach ($data as $key => $value) {
            if (substr($key, 0, strlen($code)) == $code) {
                if (!is_array($value)) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value)) . "', serialized = '1'");
                }
            }
        }
    }

    public function deleteSetting($code, $store_id = 0) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");
    }
}