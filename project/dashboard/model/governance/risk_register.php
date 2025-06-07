<?php
class ModelGovernanceRiskRegister extends Model {

    /**
     * استرجاع قائمة المخاطر مع فلترة اختيارية
     */
    public function getRisks($filters = []) {
        $sql = "SELECT 
                    r.*,
                    ug.name AS owner_group_name,
                    CONCAT(u.firstname, ' ', u.lastname) AS owner_user_name
                FROM `" . DB_PREFIX . "risk_register` r
                LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (r.owner_group_id = ug.user_group_id)
                LEFT JOIN `" . DB_PREFIX . "user` u ON (r.owner_user_id = u.user_id)
                WHERE 1";

        $where = [];

        // فلتر التصنيف
        if (!empty($filters['risk_category'])) {
            $where[] = "r.risk_category = '" . $this->db->escape($filters['risk_category']) . "'";
        }

        // فلتر الحالة
        if (!empty($filters['status'])) {
            $where[] = "r.status = '" . $this->db->escape($filters['status']) . "'";
        }

        // فلتر طبيعة الخطر
        if (!empty($filters['nature_of_risk'])) {
            $where[] = "r.nature_of_risk = '" . $this->db->escape($filters['nature_of_risk']) . "'";
        }

        // فلتر المجموعة
        if (!empty($filters['owner_group_id'])) {
            $where[] = "r.owner_group_id = '" . (int)$filters['owner_group_id'] . "'";
        }

        // فلتر المستخدم
        if (!empty($filters['owner_user_id'])) {
            $where[] = "r.owner_user_id = '" . (int)$filters['owner_user_id'] . "'";
        }

        // فلتر التاريخ
        if (!empty($filters['date_start'])) {
            $where[] = "DATE(r.date_added) >= '" . $this->db->escape($filters['date_start']) . "'";
        }
        if (!empty($filters['date_end'])) {
            $where[] = "DATE(r.date_added) <= '" . $this->db->escape($filters['date_end']) . "'";
        }

        // دمج الشروط
        if ($where) {
            $sql .= " AND " . implode(" AND ", $where);
        }

        // ترتيب
        $sql .= " ORDER BY r.date_added DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * استرجاع سجل واحد
     */
    public function getRisk($risk_id) {
        $sql = "SELECT 
                    r.*,
                    ug.name AS owner_group_name,
                    CONCAT(u.firstname, ' ', u.lastname) AS owner_user_name
                FROM `" . DB_PREFIX . "risk_register` r
                LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (r.owner_group_id = ug.user_group_id)
                LEFT JOIN `" . DB_PREFIX . "user` u ON (r.owner_user_id = u.user_id)
                WHERE r.risk_id = '" . (int)$risk_id . "'";

        $query = $this->db->query($sql);
        return ($query->num_rows) ? $query->row : null;
    }

    /**
     * إضافة خطر جديد
     */
    public function addRisk($data) {
        $fields = [];
        $fields[] = "`title`           = '" . $this->db->escape($data['title']) . "'";
        $fields[] = "`description`     = '" . $this->db->escape($data['description']) . "'";
        $fields[] = "`risk_category`   = '" . $this->db->escape($data['risk_category']) . "'";
        $fields[] = "`likelihood`      = '" . $this->db->escape($data['likelihood']) . "'";
        $fields[] = "`impact`          = '" . $this->db->escape($data['impact']) . "'";
        $fields[] = "`owner_group_id`  = '" . (int)$data['owner_group_id'] . "'";
        
        // الحقل owner_user_id إن وُجد (يقبل NULL)
        if (isset($data['owner_user_id']) && !empty($data['owner_user_id'])) {
            $fields[] = "`owner_user_id` = '" . (int)$data['owner_user_id'] . "'";
        } else {
            $fields[] = "`owner_user_id` = NULL";
        }

        $fields[] = "`nature_of_risk`  = '" . $this->db->escape($data['nature_of_risk']) . "'";
        $fields[] = "`status`          = '" . $this->db->escape($data['status']) . "'";
        $fields[] = "`mitigation_plan` = '" . $this->db->escape($data['mitigation_plan']) . "'";

        // تاريخ بدء وانتهاء
        if (!empty($data['risk_start_date'])) {
            $fields[] = "`risk_start_date` = '" . $this->db->escape($data['risk_start_date']) . "'";
        }
        if (!empty($data['risk_end_date'])) {
            $fields[] = "`risk_end_date` = '" . $this->db->escape($data['risk_end_date']) . "'";
        }

        $fields[] = "`date_added` = NOW()";

        $sql = "INSERT INTO `" . DB_PREFIX . "risk_register`
                SET " . implode(", ", $fields);

        $this->db->query($sql);
        return $this->db->getLastId();
    }

    /**
     * تحديث سجل خطر
     */
    public function updateRisk($risk_id, $data) {
        $updates = [];

        if (isset($data['title'])) {
            $updates[] = "`title` = '" . $this->db->escape($data['title']) . "'";
        }
        if (isset($data['description'])) {
            $updates[] = "`description` = '" . $this->db->escape($data['description']) . "'";
        }
        if (isset($data['risk_category'])) {
            $updates[] = "`risk_category` = '" . $this->db->escape($data['risk_category']) . "'";
        }
        if (isset($data['likelihood'])) {
            $updates[] = "`likelihood` = '" . $this->db->escape($data['likelihood']) . "'";
        }
        if (isset($data['impact'])) {
            $updates[] = "`impact` = '" . $this->db->escape($data['impact']) . "'";
        }
        if (isset($data['owner_group_id'])) {
            $updates[] = "`owner_group_id` = '" . (int)$data['owner_group_id'] . "'";
        }
        if (array_key_exists('owner_user_id', $data)) {
            if (!empty($data['owner_user_id'])) {
                $updates[] = "`owner_user_id` = '" . (int)$data['owner_user_id'] . "'";
            } else {
                $updates[] = "`owner_user_id` = NULL";
            }
        }
        if (isset($data['nature_of_risk'])) {
            $updates[] = "`nature_of_risk` = '" . $this->db->escape($data['nature_of_risk']) . "'";
        }
        if (isset($data['status'])) {
            $updates[] = "`status` = '" . $this->db->escape($data['status']) . "'";
        }
        if (isset($data['mitigation_plan'])) {
            $updates[] = "`mitigation_plan` = '" . $this->db->escape($data['mitigation_plan']) . "'";
        }
        if (array_key_exists('risk_start_date', $data)) {
            if (!empty($data['risk_start_date'])) {
                $updates[] = "`risk_start_date` = '" . $this->db->escape($data['risk_start_date']) . "'";
            } else {
                $updates[] = "`risk_start_date` = NULL";
            }
        }
        if (array_key_exists('risk_end_date', $data)) {
            if (!empty($data['risk_end_date'])) {
                $updates[] = "`risk_end_date` = '" . $this->db->escape($data['risk_end_date']) . "'";
            } else {
                $updates[] = "`risk_end_date` = NULL";
            }
        }

        if (!$updates) return false; // لا شيء للتحديث

        $updates[] = "`date_modified` = NOW()";

        $sql = "UPDATE `" . DB_PREFIX . "risk_register`
                SET " . implode(", ", $updates) . "
                WHERE `risk_id` = '" . (int)$risk_id . "'
                LIMIT 1";
        $this->db->query($sql);
        return ($this->db->countAffected() > 0);
    }

    /**
     * حذف سجل خطر
     */
    public function deleteRisk($risk_id) {
        $sql = "DELETE FROM `" . DB_PREFIX . "risk_register`
                WHERE `risk_id` = '" . (int)$risk_id . "'
                LIMIT 1";
        $this->db->query($sql);

        return ($this->db->countAffected() > 0);
    }
}
