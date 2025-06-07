<?php
class ModelGovernanceInternalAudit extends Model {

    /**
     * جلب قائمة التدقيق مع إمكانية الفلترة.
     */
    public function getAudits($filters = []) {
        $sql = "SELECT ia.*,
                       u.firstname AS auditor_fname,
                       u.lastname  AS auditor_lname
                  FROM `" . DB_PREFIX . "internal_audit` ia
             LEFT JOIN `" . DB_PREFIX . "user` u ON (ia.auditor_user_id = u.user_id)
                 WHERE 1";

        $where = [];

        // فلتر الحالة
        if (!empty($filters['status'])) {
            $where[] = "ia.status = '" . $this->db->escape($filters['status']) . "'";
        }
        // فلتر التاريخ (مثلاً حسب scheduled_date)
        if (!empty($filters['date_start'])) {
            $where[] = "ia.scheduled_date >= '" . $this->db->escape($filters['date_start']) . "'";
        }
        if (!empty($filters['date_end'])) {
            $where[] = "ia.scheduled_date <= '" . $this->db->escape($filters['date_end']) . "'";
        }
        // لو أردت فلتر نوع التدقيق
        if (!empty($filters['audit_type'])) {
            $where[] = "ia.audit_type = '" . $this->db->escape($filters['audit_type']) . "'";
        }

        if ($where) {
            $sql .= " AND " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY ia.date_added DESC";

        $query = $this->db->query($sql);

        $data = [];
        foreach ($query->rows as $row) {
            $row['auditor_name'] = trim($row['auditor_fname'] . ' ' . $row['auditor_lname']);
            $data[] = $row;
        }
        return $data;
    }

    /**
     * إضافة تدقيق.
     * يتم تعيين auditor_user_id من خارج (الكونترولر) تلقائياً.
     */
    public function addAudit($data) {
        $sql = "INSERT INTO `" . DB_PREFIX . "internal_audit`
                SET
                  audit_subject   = '" . $this->db->escape($data['audit_subject']) . "',
                  audit_type      = '" . $this->db->escape($data['audit_type']) . "',
                  description     = '" . $this->db->escape($data['description']) . "',
                  auditor_user_id = '" . (int)$data['auditor_user_id'] . "',
                  scheduled_date  = '" . $this->db->escape($data['scheduled_date']) . "',
                  completion_date = " . (!empty($data['completion_date'])
                                           ? "'".$this->db->escape($data['completion_date'])."'"
                                           : "NULL") . ",
                  findings        = '" . $this->db->escape($data['findings']) . "',
                  recommendations = '" . $this->db->escape($data['recommendations']) . "',
                  status          = '" . $this->db->escape($data['status']) . "',
                  date_added      = NOW()";

        $this->db->query($sql);
        return $this->db->getLastId();
    }

    /**
     * تعديل تدقيق.
     */
    public function updateAudit($audit_id, $data) {
        $sets = [];
        if (isset($data['audit_subject'])) {
            $sets[] = "audit_subject = '" . $this->db->escape($data['audit_subject']) . "'";
        }
        if (isset($data['audit_type'])) {
            $sets[] = "audit_type = '" . $this->db->escape($data['audit_type']) . "'";
        }
        if (isset($data['description'])) {
            $sets[] = "description = '" . $this->db->escape($data['description']) . "'";
        }
        // المدقق يُعيّن تلقائيًا أيضًا عند التعديل (لو أردت ذلك)
        if (isset($data['auditor_user_id'])) {
            $sets[] = "auditor_user_id = '" . (int)$data['auditor_user_id'] . "'";
        }
        if (isset($data['scheduled_date'])) {
            $sets[] = "scheduled_date = '" . $this->db->escape($data['scheduled_date']) . "'";
        }
        if (array_key_exists('completion_date', $data)) {
            if (!empty($data['completion_date'])) {
                $sets[] = "completion_date = '" . $this->db->escape($data['completion_date']) . "'";
            } else {
                $sets[] = "completion_date = NULL";
            }
        }
        if (isset($data['findings'])) {
            $sets[] = "findings = '" . $this->db->escape($data['findings']) . "'";
        }
        if (isset($data['recommendations'])) {
            $sets[] = "recommendations = '" . $this->db->escape($data['recommendations']) . "'";
        }
        if (isset($data['status'])) {
            $sets[] = "status = '" . $this->db->escape($data['status']) . "'";
        }

        if (!$sets) return false;

        $sets[] = "date_modified = NOW()";

        $sql = "UPDATE `" . DB_PREFIX . "internal_audit`
                SET " . implode(", ", $sets) . "
                WHERE audit_id = '" . (int)$audit_id . "'
                LIMIT 1";

        $this->db->query($sql);
        return ($this->db->countAffected() > 0);
    }

    /**
     * حذف
     */
    public function deleteAudit($audit_id) {
        $sql = "DELETE FROM `" . DB_PREFIX . "internal_audit`
                WHERE audit_id = '".(int)$audit_id."' LIMIT 1";
        $this->db->query($sql);
        return ($this->db->countAffected() > 0);
    }

    /**
     * جلب تدقيق واحد
     */
    public function getAudit($audit_id) {
        $sql = "SELECT ia.*,
                       u.firstname AS auditor_fname,
                       u.lastname  AS auditor_lname
                  FROM `" . DB_PREFIX . "internal_audit` ia
             LEFT JOIN `" . DB_PREFIX . "user` u ON (ia.auditor_user_id = u.user_id)
                 WHERE ia.audit_id='".(int)$audit_id."' LIMIT 1";
        
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            $row = $q->row;
            $row['auditor_name'] = trim($row['auditor_fname'].' '.$row['auditor_lname']);
            return $row;
        }
        return null;
    }
}
