<?php
class ModelUserPermission extends Model {
    public function addPermission($data) {
        $name = $this->db->escape($data['name']);
        $type = $this->db->escape($data['type']);

        $this->db->query("INSERT INTO " . DB_PREFIX . "permission SET name='$name', `key`='', type='$type', date_added=NOW(), date_modified=NOW()");
        $permission_id = $this->db->getLastId();

        $key = trim($data['key']);
        if ($key == '') {
            $key = 'permission_' . $permission_id;
        } else {
            $key = $this->generateUniqueKey($key, $permission_id);
        }

        $this->db->query("UPDATE " . DB_PREFIX . "permission SET `key`='".$this->db->escape($key)."' WHERE permission_id=".(int)$permission_id);

        return $permission_id;
    }

    public function editPermission($permission_id, $data) {
        $name = $this->db->escape($data['name']);
        $type = $this->db->escape($data['type']);
        $key = trim($data['key']);
        if ($key == '') {
            $key = 'permission_' . $permission_id;
        } else {
            $key = $this->generateUniqueKey($key, $permission_id);
        }

        $this->db->query("UPDATE " . DB_PREFIX . "permission SET name='$name', `key`='".$this->db->escape($key)."', type='$type', date_modified=NOW() WHERE permission_id=".(int)$permission_id);
    }

    public function deletePermission($permission_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "permission WHERE permission_id=".(int)$permission_id);
        $this->db->query("DELETE FROM " . DB_PREFIX . "user_group_permission WHERE permission_id=".(int)$permission_id);
        $this->db->query("DELETE FROM " . DB_PREFIX . "user_permission WHERE permission_id=".(int)$permission_id);
    }

    public function getPermissions($filter_name='') {
        $sql = "SELECT * FROM " . DB_PREFIX . "permission WHERE 1";
        if ($filter_name) {
            $sql .= " AND name LIKE '%".$this->db->escape($filter_name)."%'";
        }
        $sql .= " ORDER BY name";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getPermission($permission_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "permission WHERE permission_id=".(int)$permission_id);
        return $query->row;
    }

    public function setUserGroupPermissions($permission_id, $user_group_ids) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "user_group_permission WHERE permission_id=".(int)$permission_id);
        foreach ($user_group_ids as $ugid) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "user_group_permission SET user_group_id=".(int)$ugid.", permission_id=".(int)$permission_id);
        }
    }

    public function setUserPermissions($permission_id, $user_ids) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "user_permission WHERE permission_id=".(int)$permission_id);
        foreach ($user_ids as $uid) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "user_permission SET user_id=".(int)$uid.", permission_id=".(int)$permission_id);
        }
    }

    public function getUserGroupPermissions($permission_id) {
        $query = $this->db->query("SELECT user_group_id FROM " . DB_PREFIX . "user_group_permission WHERE permission_id=".(int)$permission_id);
        return array_column($query->rows, 'user_group_id');
    }

    public function getUserPermissions($permission_id) {
        $query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user_permission WHERE permission_id=".(int)$permission_id);
        return array_column($query->rows, 'user_id');
    }

    private function generateUniqueKey($key, $permission_id) {
        $key = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $key));
        $check = $this->db->query("SELECT permission_id FROM " . DB_PREFIX . "permission WHERE `key`='".$this->db->escape($key)."' AND permission_id!=".(int)$permission_id);
        if ($check->num_rows) {
            $key .= '_'.rand(100,999);
        }
        return $key;
    }
}
