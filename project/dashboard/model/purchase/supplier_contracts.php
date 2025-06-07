<?php
class ModelPurchaseSupplierContracts extends Model {

    /**
     * إضافة عقد مورد جديد
     */
    public function addContract($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_contract SET 
                         contract_number = '" . $this->db->escape($data['contract_number']) . "',
                         supplier_id = '" . (int)$data['supplier_id'] . "',
                         contract_type = '" . $this->db->escape($data['contract_type']) . "',
                         contract_date = '" . $this->db->escape($data['contract_date']) . "',
                         start_date = '" . $this->db->escape($data['start_date']) . "',
                         end_date = '" . $this->db->escape($data['end_date']) . "',
                         contract_value = '" . (float)$data['contract_value'] . "',
                         currency_id = '" . (int)$data['currency_id'] . "',
                         payment_terms = '" . $this->db->escape($data['payment_terms']) . "',
                         delivery_terms = '" . $this->db->escape($data['delivery_terms']) . "',
                         terms_conditions = '" . $this->db->escape($data['terms_conditions']) . "',
                         notes = '" . $this->db->escape($data['notes']) . "',
                         status = '" . $this->db->escape($data['status']) . "',
                         created_by = '" . (int)$this->user->getId() . "',
                         created_at = NOW(),
                         date_modified = NOW()");

        $contract_id = $this->db->getLastId();

        // إضافة سجل في تاريخ العقد
        $this->addContractHistory($contract_id, 'created', 'تم إنشاء العقد');

        return $contract_id;
    }

    /**
     * تعديل عقد مورد
     */
    public function editContract($contract_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_contract SET 
                         contract_number = '" . $this->db->escape($data['contract_number']) . "',
                         supplier_id = '" . (int)$data['supplier_id'] . "',
                         contract_type = '" . $this->db->escape($data['contract_type']) . "',
                         contract_date = '" . $this->db->escape($data['contract_date']) . "',
                         start_date = '" . $this->db->escape($data['start_date']) . "',
                         end_date = '" . $this->db->escape($data['end_date']) . "',
                         contract_value = '" . (float)$data['contract_value'] . "',
                         currency_id = '" . (int)$data['currency_id'] . "',
                         payment_terms = '" . $this->db->escape($data['payment_terms']) . "',
                         delivery_terms = '" . $this->db->escape($data['delivery_terms']) . "',
                         terms_conditions = '" . $this->db->escape($data['terms_conditions']) . "',
                         notes = '" . $this->db->escape($data['notes']) . "',
                         status = '" . $this->db->escape($data['status']) . "',
                         modified_by = '" . (int)$this->user->getId() . "',
                         date_modified = NOW()
                         WHERE contract_id = '" . (int)$contract_id . "'");

        // إضافة سجل في تاريخ العقد
        $this->addContractHistory($contract_id, 'modified', 'تم تعديل العقد');
    }

    /**
     * حذف عقد مورد
     */
    public function deleteContract($contract_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_contract WHERE contract_id = '" . (int)$contract_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_contract_history WHERE contract_id = '" . (int)$contract_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_contract_item WHERE contract_id = '" . (int)$contract_id . "'");
    }

    /**
     * الحصول على عقد مورد
     */
    public function getContract($contract_id) {
        $query = $this->db->query("SELECT sc.*, 
                                          s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          s.email AS supplier_email, s.telephone AS supplier_telephone,
                                          c.code AS currency_code, c.symbol_left, c.symbol_right,
                                          u1.firstname AS created_by_name, u1.lastname AS created_by_lastname,
                                          u2.firstname AS modified_by_name, u2.lastname AS modified_by_lastname
                                   FROM " . DB_PREFIX . "supplier_contract sc
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (sc.supplier_id = s.supplier_id)
                                   LEFT JOIN " . DB_PREFIX . "currency c ON (sc.currency_id = c.currency_id)
                                   LEFT JOIN " . DB_PREFIX . "user u1 ON (sc.created_by = u1.user_id)
                                   LEFT JOIN " . DB_PREFIX . "user u2 ON (sc.modified_by = u2.user_id)
                                   WHERE sc.contract_id = '" . (int)$contract_id . "'");

        return $query->row;
    }

    /**
     * الحصول على قائمة عقود الموردين
     */
    public function getContracts($data = array()) {
        $sql = "SELECT sc.*, 
                       s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                       c.code AS currency_code, c.symbol_left, c.symbol_right
                FROM " . DB_PREFIX . "supplier_contract sc
                LEFT JOIN " . DB_PREFIX . "supplier s ON (sc.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "currency c ON (sc.currency_id = c.currency_id)
                WHERE 1 = 1";

        if (!empty($data['filter_contract_number'])) {
            $sql .= " AND sc.contract_number LIKE '%" . $this->db->escape($data['filter_contract_number']) . "%'";
        }

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND sc.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND sc.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(sc.contract_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(sc.contract_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sort_data = array(
            'sc.contract_number',
            'supplier_name',
            'sc.contract_date',
            'sc.start_date',
            'sc.end_date',
            'sc.contract_value',
            'sc.status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sc.contract_date";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
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
     * الحصول على إجمالي عدد عقود الموردين
     */
    public function getTotalContracts($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier_contract sc
                LEFT JOIN " . DB_PREFIX . "supplier s ON (sc.supplier_id = s.supplier_id)
                WHERE 1 = 1";

        if (!empty($data['filter_contract_number'])) {
            $sql .= " AND sc.contract_number LIKE '%" . $this->db->escape($data['filter_contract_number']) . "%'";
        }

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND sc.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND sc.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(sc.contract_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(sc.contract_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * تجديد عقد مورد
     */
    public function renewContract($contract_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_contract SET 
                         end_date = '" . $this->db->escape($data['end_date']) . "',
                         status = '" . $this->db->escape($data['status']) . "',
                         renewed_by = '" . (int)$data['renewed_by'] . "',
                         renewed_at = '" . $this->db->escape($data['renewed_at']) . "',
                         renewal_notes = '" . $this->db->escape($data['renewal_notes']) . "',
                         date_modified = NOW()
                         WHERE contract_id = '" . (int)$contract_id . "'");

        // إضافة سجل في تاريخ العقد
        $this->addContractHistory($contract_id, 'renewed', 'تم تجديد العقد حتى ' . $data['end_date']);
    }

    /**
     * إنهاء عقد مورد
     */
    public function terminateContract($contract_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_contract SET 
                         status = '" . $this->db->escape($data['status']) . "',
                         terminated_by = '" . (int)$data['terminated_by'] . "',
                         terminated_at = '" . $this->db->escape($data['terminated_at']) . "',
                         termination_reason = '" . $this->db->escape($data['termination_reason']) . "',
                         termination_notes = '" . $this->db->escape($data['termination_notes']) . "',
                         date_modified = NOW()
                         WHERE contract_id = '" . (int)$contract_id . "'");

        // إضافة سجل في تاريخ العقد
        $this->addContractHistory($contract_id, 'terminated', 'تم إنهاء العقد - ' . $data['termination_reason']);
    }

    /**
     * إضافة سجل في تاريخ العقد
     */
    public function addContractHistory($contract_id, $action, $notes = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_contract_history SET 
                         contract_id = '" . (int)$contract_id . "',
                         action = '" . $this->db->escape($action) . "',
                         notes = '" . $this->db->escape($notes) . "',
                         user_id = '" . (int)$this->user->getId() . "',
                         date_added = NOW()");
    }

    /**
     * الحصول على تاريخ العقد
     */
    public function getContractHistory($contract_id) {
        $query = $this->db->query("SELECT sch.*, 
                                          u.firstname, u.lastname, CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS user_name
                                   FROM " . DB_PREFIX . "supplier_contract_history sch
                                   LEFT JOIN " . DB_PREFIX . "user u ON (sch.user_id = u.user_id)
                                   WHERE sch.contract_id = '" . (int)$contract_id . "'
                                   ORDER BY sch.date_added DESC");

        return $query->rows;
    }

    /**
     * الحصول على العقود المنتهية الصلاحية
     */
    public function getExpiredContracts() {
        $query = $this->db->query("SELECT sc.*, 
                                          s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          DATEDIFF(CURDATE(), sc.end_date) as days_expired
                                   FROM " . DB_PREFIX . "supplier_contract sc
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (sc.supplier_id = s.supplier_id)
                                   WHERE sc.end_date < CURDATE()
                                   AND sc.status IN ('active', 'pending_approval')
                                   ORDER BY sc.end_date ASC");

        return $query->rows;
    }

    /**
     * الحصول على العقود المتوقع انتهاؤها قريباً
     */
    public function getExpiringContracts($days = 30) {
        $query = $this->db->query("SELECT sc.*, 
                                          s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          DATEDIFF(sc.end_date, CURDATE()) as days_until_expiry
                                   FROM " . DB_PREFIX . "supplier_contract sc
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (sc.supplier_id = s.supplier_id)
                                   WHERE sc.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL " . (int)$days . " DAY)
                                   AND sc.status IN ('active', 'pending_approval')
                                   ORDER BY sc.end_date ASC");

        return $query->rows;
    }

    /**
     * الحصول على إحصائيات العقود
     */
    public function getContractStatistics() {
        $statistics = array();

        // إجمالي العقود
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_contract");
        $statistics['total_contracts'] = $query->row['total'];

        // العقود النشطة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_contract WHERE status = 'active'");
        $statistics['active_contracts'] = $query->row['total'];

        // العقود المنتهية
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_contract WHERE end_date < CURDATE() AND status IN ('active', 'pending_approval')");
        $statistics['expired_contracts'] = $query->row['total'];

        // العقود المتوقع انتهاؤها خلال 30 يوم
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_contract WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status IN ('active', 'pending_approval')");
        $statistics['expiring_contracts'] = $query->row['total'];

        // إجمالي قيمة العقود النشطة
        $query = $this->db->query("SELECT SUM(contract_value) as total_value FROM " . DB_PREFIX . "supplier_contract WHERE status = 'active'");
        $statistics['total_active_value'] = $query->row['total_value'] ? $query->row['total_value'] : 0;

        return $statistics;
    }

    /**
     * الحصول على عقود مورد معين
     */
    public function getContractsBySupplier($supplier_id) {
        $query = $this->db->query("SELECT sc.*, 
                                          c.code AS currency_code, c.symbol_left, c.symbol_right
                                   FROM " . DB_PREFIX . "supplier_contract sc
                                   LEFT JOIN " . DB_PREFIX . "currency c ON (sc.currency_id = c.currency_id)
                                   WHERE sc.supplier_id = '" . (int)$supplier_id . "'
                                   ORDER BY sc.contract_date DESC");

        return $query->rows;
    }

    /**
     * تحديث حالة العقود المنتهية تلقائياً
     */
    public function updateExpiredContracts() {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_contract 
                         SET status = 'expired', date_modified = NOW()
                         WHERE end_date < CURDATE() 
                         AND status IN ('active', 'pending_approval')");

        return $this->db->countAffected();
    }

    /**
     * البحث في العقود
     */
    public function searchContracts($search_term) {
        $query = $this->db->query("SELECT sc.*, 
                                          s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          c.code AS currency_code
                                   FROM " . DB_PREFIX . "supplier_contract sc
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (sc.supplier_id = s.supplier_id)
                                   LEFT JOIN " . DB_PREFIX . "currency c ON (sc.currency_id = c.currency_id)
                                   WHERE sc.contract_number LIKE '%" . $this->db->escape($search_term) . "%'
                                   OR CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) LIKE '%" . $this->db->escape($search_term) . "%'
                                   OR sc.notes LIKE '%" . $this->db->escape($search_term) . "%'
                                   ORDER BY sc.contract_date DESC
                                   LIMIT 10");

        return $query->rows;
    }
}
