<?php
class ModelCatalogUnit extends Model {
    public function addUnit($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "unit SET code = '" . $this->db->escape($data['code']) . "', desc_en = '" . $this->db->escape($data['desc_en']) . "', desc_ar = '" . $this->db->escape($data['desc_ar']) . "'");
        
        return $this->db->getLastId();
    }

    public function editUnit($unit_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "unit SET code = '" . $this->db->escape($data['code']) . "', desc_en = '" . $this->db->escape($data['desc_en']) . "', desc_ar = '" . $this->db->escape($data['desc_ar']) . "' WHERE unit_id = '" . (int)$unit_id . "'");
    }

    public function deleteUnit($unit_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "unit WHERE unit_id = '" . (int)$unit_id . "'");
    }

    public function getUnit($unit_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "unit WHERE unit_id = '" . (int)$unit_id . "'");

        return $query->row;
    }

    public function getUnitByCode($code) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "unit WHERE code = '" . $this->db->escape($code) . "'");

        return $query->row;
    }

    public function getUnits($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "unit WHERE 1=1";

        // تطبيق الفلاتر
        if (!empty($data['filter_code'])) {
            $sql .= " AND code LIKE '%" . $this->db->escape($data['filter_code']) . "%'";
        }

        if (!empty($data['filter_name_en'])) {
            $sql .= " AND desc_en LIKE '%" . $this->db->escape($data['filter_name_en']) . "%'";
        }

        if (!empty($data['filter_name_ar'])) {
            $sql .= " AND desc_ar LIKE '%" . $this->db->escape($data['filter_name_ar']) . "%'";
        }

        // تطبيق الترتيب
        $sort_data = array(
            'code',
            'desc_en',
            'desc_ar'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY code";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        // تطبيق الترقيم
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

    public function getTotalUnits($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "unit WHERE 1=1";

        // تطبيق الفلاتر
        if (!empty($data['filter_code'])) {
            $sql .= " AND code LIKE '%" . $this->db->escape($data['filter_code']) . "%'";
        }

        if (!empty($data['filter_name_en'])) {
            $sql .= " AND desc_en LIKE '%" . $this->db->escape($data['filter_name_en']) . "%'";
        }

        if (!empty($data['filter_name_ar'])) {
            $sql .= " AND desc_ar LIKE '%" . $this->db->escape($data['filter_name_ar']) . "%'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getProductCountByUnit($unit_id) {
        // التحقق من استخدام الوحدة في جدول product_unit
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_unit WHERE unit_id = '" . (int)$unit_id . "'");
        
        return $query->row['total'];
    }
}