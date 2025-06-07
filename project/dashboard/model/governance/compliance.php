<?php
class ModelGovernanceCompliance extends Model {

    /**
     * جلب عدة سجلات مع فلاتر
     */
    public function getRecords($filters = []) {
        $sql = "SELECT c.*,
                       u.firstname AS responsible_fname,
                       u.lastname AS responsible_lname
                  FROM `" . DB_PREFIX . "compliance_record` c
             LEFT JOIN `" . DB_PREFIX . "user` u ON (c.responsible_user_id = u.user_id)
                 WHERE 1";

        $where = [];
        if(!empty($filters['status'])) {
            $where[] = "c.status = '".$this->db->escape($filters['status'])."'";
        }

        if($where) {
            $sql .= " AND " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY c.date_added DESC";

        $q = $this->db->query($sql);
        $rows = [];
        foreach($q->rows as $r) {
            $r['responsible_user_name'] = trim($r['responsible_fname'].' '.$r['responsible_lname']);
            $rows[] = $r;
        }
        return $rows;
    }

    /**
     * إضافة سجل
     */
    public function addRecord($data) {
        $sql = "INSERT INTO `" . DB_PREFIX . "compliance_record`
                SET compliance_type = '".$this->db->escape($data['compliance_type'])."',
                    reference_code  = '".$this->db->escape($data['reference_code'])."',
                    description     = '".$this->db->escape($data['description'])."',
                    due_date        = ".($data['due_date']?"'".$this->db->escape($data['due_date'])."'":"NULL").",
                    status          = '".$this->db->escape($data['status'])."',
                    responsible_user_id = '".(int)$data['responsible_user_id']."'";

        $this->db->query($sql);
        return $this->db->getLastId();
    }

    /**
     * تعديل سجل
     */
    public function updateRecord($compliance_id, $data) {
        $fields = [];
        if(isset($data['compliance_type'])) {
            $fields[] = "compliance_type = '".$this->db->escape($data['compliance_type'])."'";
        }
        if(isset($data['reference_code'])) {
            $fields[] = "reference_code  = '".$this->db->escape($data['reference_code'])."'";
        }
        if(isset($data['description'])) {
            $fields[] = "description = '".$this->db->escape($data['description'])."'";
        }
        if(array_key_exists('due_date',$data)) {
            if($data['due_date']) {
                $fields[] = "due_date = '".$this->db->escape($data['due_date'])."'";
            } else {
                $fields[] = "due_date = NULL";
            }
        }
        if(isset($data['status'])) {
            $fields[] = "status = '".$this->db->escape($data['status'])."'";
        }
        if(isset($data['responsible_user_id'])) {
            $fields[] = "responsible_user_id = '".(int)$data['responsible_user_id']."'";
        }

        if(!$fields) return false;

        $sql = "UPDATE `" . DB_PREFIX . "compliance_record`
                   SET " . implode(", ", $fields) . "
                 WHERE compliance_id='".(int)$compliance_id."' LIMIT 1";
        $this->db->query($sql);

        return ($this->db->countAffected() > 0);
    }

    /**
     * حذف سجل
     */
    public function deleteRecord($compliance_id) {
        $sql = "DELETE FROM `" . DB_PREFIX . "compliance_record`
                 WHERE compliance_id='".(int)$compliance_id."' LIMIT 1";
        $this->db->query($sql);
        return ($this->db->countAffected() > 0);
    }

    /**
     * جلب سجل واحد
     */
    public function getRecord($compliance_id) {
        $sql = "SELECT c.*,
                       u.firstname AS responsible_fname,
                       u.lastname  AS responsible_lname
                  FROM `" . DB_PREFIX . "compliance_record` c
             LEFT JOIN `" . DB_PREFIX . "user` u ON (c.responsible_user_id = u.user_id)
                 WHERE c.compliance_id='".(int)$compliance_id."' LIMIT 1";

        $q = $this->db->query($sql);
        if($q->num_rows) {
            $row = $q->row;
            $row['responsible_user_name'] = trim($row['responsible_fname'].' '.$row['responsible_lname']);
            return $row;
        }
        return null;
    }
}
