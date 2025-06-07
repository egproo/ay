<?php
class ModelGovernanceInternalControl extends Model {

    /**
     * جلب قائمة الضوابط/السياسات مع إمكانية الفلترة.
     */
    public function getControls($filters = []) {
        $sql = "SELECT ic.*,
                       ug.name AS responsible_group_name
                  FROM `" . DB_PREFIX . "internal_control` ic
             LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ic.responsible_group_id = ug.user_group_id)
                 WHERE 1";

        $where = [];

        // فلتر الحالة
        if (!empty($filters['status'])) {
            $where[] = "ic.status = '" . $this->db->escape($filters['status']) . "'";
        }

        // فلتر تاريخ السريان
        if (!empty($filters['effective_date_start'])) {
            $where[] = "ic.effective_date >= '" . $this->db->escape($filters['effective_date_start']) . "'";
        }
        if (!empty($filters['effective_date_end'])) {
            $where[] = "ic.effective_date <= '" . $this->db->escape($filters['effective_date_end']) . "'";
        }

        // فلتر اسم الضابط (بحث جزئي)
        if (!empty($filters['control_name'])) {
            $where[] = "ic.control_name LIKE '%" . $this->db->escape($filters['control_name']) . "%'";
        }

        // فلتر المجموعة المسؤولة
        if (!empty($filters['responsible_group_id'])) {
            $where[] = "ic.responsible_group_id = '" . (int)$filters['responsible_group_id'] . "'";
        }

        if ($where) {
            $sql .= " AND " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY ic.date_added DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * إضافة ضابط/سياسة جديدة.
     */
    public function addControl($data) {
        $sql = "INSERT INTO `" . DB_PREFIX . "internal_control`
                SET
                  control_name         = '" . $this->db->escape($data['control_name']) . "',
                  description          = '" . $this->db->escape($data['description']) . "',
                  responsible_group_id = '" . (int)$data['responsible_group_id'] . "',
                  effective_date       = '" . $this->db->escape($data['effective_date']) . "',
                  review_date          = " . (!empty($data['review_date']) 
                                               ? "'".$this->db->escape($data['review_date'])."'" 
                                               : "NULL") . ",
                  status               = '" . $this->db->escape($data['status']) . "',
                  date_added           = NOW()";

        $this->db->query($sql);
        return $this->db->getLastId();
    }

    /**
     * تحديث ضابط/سياسة.
     */
    public function updateControl($control_id, $data) {
        $sets = [];

        if (isset($data['control_name'])) {
            $sets[] = "control_name = '" . $this->db->escape($data['control_name']) . "'";
        }
        if (isset($data['description'])) {
            $sets[] = "description = '" . $this->db->escape($data['description']) . "'";
        }
        if (isset($data['responsible_group_id'])) {
            $sets[] = "responsible_group_id = '" . (int)$data['responsible_group_id'] . "'";
        }
        if (isset($data['effective_date'])) {
            $sets[] = "effective_date = '" . $this->db->escape($data['effective_date']) . "'";
        }
        if (array_key_exists('review_date', $data)) {
            if (!empty($data['review_date'])) {
                $sets[] = "review_date = '" . $this->db->escape($data['review_date']) . "'";
            } else {
                $sets[] = "review_date = NULL";
            }
        }
        if (isset($data['status'])) {
            $sets[] = "status = '" . $this->db->escape($data['status']) . "'";
        }

        if (!$sets) return false;

        $sets[] = "date_modified = NOW()";

        $sql = "UPDATE `" . DB_PREFIX . "internal_control`
                   SET " . implode(", ", $sets) . "
                 WHERE control_id = '" . (int)$control_id . "' 
                 LIMIT 1";

        $this->db->query($sql);
        return ($this->db->countAffected() > 0);
    }

    /**
     * حذف ضابط/سياسة.
     */
    public function deleteControl($control_id) {
        $sql = "DELETE FROM `" . DB_PREFIX . "internal_control`
                WHERE control_id = '" . (int)$control_id . "'
                LIMIT 1";
        $this->db->query($sql);
        return ($this->db->countAffected() > 0);
    }

    /**
     * جلب سجل واحد
     */
    public function getControl($control_id) {
        $sql = "SELECT ic.*,
                       ug.name AS responsible_group_name
                  FROM `" . DB_PREFIX . "internal_control` ic
             LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ic.responsible_group_id = ug.user_group_id)
                 WHERE ic.control_id = '" . (int)$control_id . "' LIMIT 1";
        $query = $this->db->query($sql);
        return ($query->num_rows ? $query->row : null);
    }
}
