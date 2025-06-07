<?php
class ModelSupplierSupplierGroup extends Model {

    /**
     * إضافة مجموعة مورد جديدة
     */
    public function addSupplierGroup($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_group SET approval = '" . (int)$data['approval'] . "', sort_order = '" . (int)$data['sort_order'] . "'");

        $supplier_group_id = $this->db->getLastId();

        foreach ($data['supplier_group_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_group_description SET supplier_group_id = '" . (int)$supplier_group_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "'");
        }

        return $supplier_group_id;
    }

    /**
     * تعديل مجموعة مورد
     */
    public function editSupplierGroup($supplier_group_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_group SET approval = '" . (int)$data['approval'] . "', sort_order = '" . (int)$data['sort_order'] . "' WHERE supplier_group_id = '" . (int)$supplier_group_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_group_description WHERE supplier_group_id = '" . (int)$supplier_group_id . "'");

        foreach ($data['supplier_group_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_group_description SET supplier_group_id = '" . (int)$supplier_group_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "'");
        }
    }

    /**
     * حذف مجموعة مورد
     */
    public function deleteSupplierGroup($supplier_group_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_group WHERE supplier_group_id = '" . (int)$supplier_group_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_group_description WHERE supplier_group_id = '" . (int)$supplier_group_id . "'");
    }

    /**
     * الحصول على مجموعة مورد
     */
    public function getSupplierGroup($supplier_group_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "supplier_group sg LEFT JOIN " . DB_PREFIX . "supplier_group_description sgd ON (sg.supplier_group_id = sgd.supplier_group_id) WHERE sg.supplier_group_id = '" . (int)$supplier_group_id . "' AND sgd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    /**
     * الحصول على قائمة مجموعات الموردين
     */
    public function getSupplierGroups($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "supplier_group sg LEFT JOIN " . DB_PREFIX . "supplier_group_description sgd ON (sg.supplier_group_id = sgd.supplier_group_id) WHERE sgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $sort_data = array(
            'sgd.name',
            'sg.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sgd.name";
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

    /**
     * الحصول على أوصاف مجموعة مورد
     */
    public function getSupplierGroupDescriptions($supplier_group_id) {
        $supplier_group_description_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "supplier_group_description WHERE supplier_group_id = '" . (int)$supplier_group_id . "'");

        foreach ($query->rows as $result) {
            $supplier_group_description_data[$result['language_id']] = array(
                'name'        => $result['name'],
                'description' => $result['description']
            );
        }

        return $supplier_group_description_data;
    }

    /**
     * الحصول على إجمالي عدد مجموعات الموردين
     */
    public function getTotalSupplierGroups() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier_group");

        return $query->row['total'];
    }

    /**
     * الحصول على مجموعات الموردين للقائمة المنسدلة
     */
    public function getSupplierGroupsForSelect() {
        $supplier_group_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "supplier_group sg LEFT JOIN " . DB_PREFIX . "supplier_group_description sgd ON (sg.supplier_group_id = sgd.supplier_group_id) WHERE sgd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY sgd.name ASC");

        foreach ($query->rows as $result) {
            $supplier_group_data[] = array(
                'supplier_group_id' => $result['supplier_group_id'],
                'name'              => $result['name'],
                'approval'          => $result['approval'],
                'sort_order'        => $result['sort_order']
            );
        }

        return $supplier_group_data;
    }

    /**
     * الحصول على مجموعة مورد بالاسم
     */
    public function getSupplierGroupByName($name) {
        $query = $this->db->query("SELECT sg.* FROM " . DB_PREFIX . "supplier_group sg LEFT JOIN " . DB_PREFIX . "supplier_group_description sgd ON (sg.supplier_group_id = sgd.supplier_group_id) WHERE sgd.name = '" . $this->db->escape($name) . "' AND sgd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    /**
     * تحديث ترتيب مجموعات الموردين
     */
    public function updateSupplierGroupSortOrder($supplier_group_id, $sort_order) {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_group SET sort_order = '" . (int)$sort_order . "' WHERE supplier_group_id = '" . (int)$supplier_group_id . "'");
    }

    /**
     * الحصول على إحصائيات مجموعات الموردين
     */
    public function getSupplierGroupStatistics() {
        $statistics = array();

        // إجمالي مجموعات الموردين
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_group");
        $statistics['total_groups'] = $query->row['total'];

        // مجموعات الموردين التي تتطلب موافقة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_group WHERE approval = 1");
        $statistics['approval_required'] = $query->row['total'];

        // مجموعات الموردين بدون موافقة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_group WHERE approval = 0");
        $statistics['no_approval'] = $query->row['total'];

        // إحصائيات الموردين لكل مجموعة
        $query = $this->db->query("SELECT sg.supplier_group_id, sgd.name, COUNT(s.supplier_id) as supplier_count 
                                   FROM " . DB_PREFIX . "supplier_group sg 
                                   LEFT JOIN " . DB_PREFIX . "supplier_group_description sgd ON (sg.supplier_group_id = sgd.supplier_group_id) 
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (sg.supplier_group_id = s.supplier_group_id) 
                                   WHERE sgd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                   GROUP BY sg.supplier_group_id 
                                   ORDER BY sgd.name ASC");

        $statistics['group_supplier_counts'] = $query->rows;

        return $statistics;
    }

    /**
     * البحث في مجموعات الموردين
     */
    public function searchSupplierGroups($search_term) {
        $query = $this->db->query("SELECT sg.*, sgd.name, sgd.description 
                                   FROM " . DB_PREFIX . "supplier_group sg 
                                   LEFT JOIN " . DB_PREFIX . "supplier_group_description sgd ON (sg.supplier_group_id = sgd.supplier_group_id) 
                                   WHERE sgd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                   AND (sgd.name LIKE '%" . $this->db->escape($search_term) . "%' 
                                   OR sgd.description LIKE '%" . $this->db->escape($search_term) . "%') 
                                   ORDER BY sgd.name ASC 
                                   LIMIT 10");

        return $query->rows;
    }

    /**
     * نسخ مجموعة مورد
     */
    public function copySupplierGroup($supplier_group_id) {
        $supplier_group = $this->getSupplierGroup($supplier_group_id);
        $descriptions = $this->getSupplierGroupDescriptions($supplier_group_id);

        if ($supplier_group) {
            $data = array(
                'approval' => $supplier_group['approval'],
                'sort_order' => $supplier_group['sort_order'],
                'supplier_group_description' => array()
            );

            foreach ($descriptions as $language_id => $description) {
                $data['supplier_group_description'][$language_id] = array(
                    'name' => $description['name'] . ' - نسخة',
                    'description' => $description['description']
                );
            }

            return $this->addSupplierGroup($data);
        }

        return false;
    }

    /**
     * تفعيل/إلغاء تفعيل مجموعة مورد
     */
    public function toggleSupplierGroupApproval($supplier_group_id) {
        $query = $this->db->query("SELECT approval FROM " . DB_PREFIX . "supplier_group WHERE supplier_group_id = '" . (int)$supplier_group_id . "'");

        if ($query->num_rows) {
            $new_approval = $query->row['approval'] ? 0 : 1;
            $this->db->query("UPDATE " . DB_PREFIX . "supplier_group SET approval = '" . (int)$new_approval . "' WHERE supplier_group_id = '" . (int)$supplier_group_id . "'");
            return $new_approval;
        }

        return false;
    }

    /**
     * الحصول على مجموعات الموردين النشطة فقط
     */
    public function getActiveSupplierGroups() {
        $query = $this->db->query("SELECT sg.*, sgd.name, sgd.description 
                                   FROM " . DB_PREFIX . "supplier_group sg 
                                   LEFT JOIN " . DB_PREFIX . "supplier_group_description sgd ON (sg.supplier_group_id = sgd.supplier_group_id) 
                                   WHERE sgd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                   ORDER BY sg.sort_order ASC, sgd.name ASC");

        return $query->rows;
    }

    /**
     * الحصول على مجموعة المورد الافتراضية
     */
    public function getDefaultSupplierGroup() {
        $query = $this->db->query("SELECT sg.*, sgd.name, sgd.description 
                                   FROM " . DB_PREFIX . "supplier_group sg 
                                   LEFT JOIN " . DB_PREFIX . "supplier_group_description sgd ON (sg.supplier_group_id = sgd.supplier_group_id) 
                                   WHERE sg.supplier_group_id = '" . (int)$this->config->get('config_supplier_group_id') . "' 
                                   AND sgd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    /**
     * تحديث مجموعة المورد الافتراضية
     */
    public function setDefaultSupplierGroup($supplier_group_id) {
        // تحديث الإعدادات
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('config', array('config_supplier_group_id' => $supplier_group_id));
        
        return true;
    }

    /**
     * الحصول على عدد الموردين في مجموعة معينة
     */
    public function getSupplierCountByGroup($supplier_group_id) {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier WHERE supplier_group_id = '" . (int)$supplier_group_id . "'");

        return $query->row['total'];
    }

    /**
     * نقل الموردين من مجموعة إلى أخرى
     */
    public function moveSuppliers($from_group_id, $to_group_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier SET supplier_group_id = '" . (int)$to_group_id . "' WHERE supplier_group_id = '" . (int)$from_group_id . "'");

        return $this->db->countAffected();
    }

    /**
     * تصدير مجموعات الموردين
     */
    public function exportSupplierGroups() {
        $query = $this->db->query("SELECT sg.supplier_group_id, sg.approval, sg.sort_order, sgd.name, sgd.description, sgd.language_id 
                                   FROM " . DB_PREFIX . "supplier_group sg 
                                   LEFT JOIN " . DB_PREFIX . "supplier_group_description sgd ON (sg.supplier_group_id = sgd.supplier_group_id) 
                                   ORDER BY sg.sort_order ASC, sgd.name ASC");

        return $query->rows;
    }
}
