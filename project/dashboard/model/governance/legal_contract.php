<?php
class ModelGovernanceLegalContract extends Model {

    /**
     * استرجاع عقود متعددة مع فلاتر
     */
    public function getContracts($filters=[]) {
        $sql = "SELECT lc.*
                  FROM `" . DB_PREFIX . "legal_contract` lc
                 WHERE 1";

        $where = [];
        if(!empty($filters['status'])) {
            $where[] = "lc.status = '".$this->db->escape($filters['status'])."'";
        }
        // لو هناك فلاتر إضافية مثل contract_type أو party_id

        if($where) {
            $sql .= " AND ".implode(" AND ", $where);
        }

        $sql .= " ORDER BY lc.date_added DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * إضافة عقد جديد
     */
    public function addContract($data) {
        $sql = "INSERT INTO `" . DB_PREFIX . "legal_contract`
                SET contract_type = '".$this->db->escape($data['contract_type'])."',
                    title         = '".$this->db->escape($data['title'])."',
                    party_id      = '".(int)$data['party_id']."',
                    start_date    = '".$this->db->escape($data['start_date'])."',
                    end_date      = ".($data['end_date']?"'".$this->db->escape($data['end_date'])."'":"NULL").",
                    status        = '".$this->db->escape($data['status'])."',
                    value         = '".(float)$data['value']."',
                    description   = '".$this->db->escape($data['description'])."'";
        $this->db->query($sql);

        return $this->db->getLastId();
    }

    /**
     * تعديل عقد
     */
    public function updateContract($contract_id, $data) {
        $fields = [];
        if(isset($data['contract_type'])) {
            $fields[] = "contract_type = '".$this->db->escape($data['contract_type'])."'";
        }
        if(isset($data['title'])) {
            $fields[] = "title = '".$this->db->escape($data['title'])."'";
        }
        if(isset($data['party_id'])) {
            $fields[] = "party_id = '".(int)$data['party_id']."'";
        }
        if(isset($data['start_date'])) {
            $fields[] = "start_date = '".$this->db->escape($data['start_date'])."'";
        }
        if(array_key_exists('end_date',$data)) {
            if($data['end_date']) {
                $fields[] = "end_date = '".$this->db->escape($data['end_date'])."'";
            } else {
                $fields[] = "end_date = NULL";
            }
        }
        if(isset($data['status'])) {
            $fields[] = "status = '".$this->db->escape($data['status'])."'";
        }
        if(isset($data['value'])) {
            $fields[] = "value = '".(float)$data['value']."'";
        }
        if(isset($data['description'])) {
            $fields[] = "description = '".$this->db->escape($data['description'])."'";
        }

        if(!$fields) return false;

        $sql = "UPDATE `" . DB_PREFIX . "legal_contract`
                   SET ".implode(", ", $fields)."
                 WHERE contract_id='".(int)$contract_id."' LIMIT 1";

        $this->db->query($sql);
        return ($this->db->countAffected() > 0);
    }

    /**
     * حذف عقد
     */
    public function deleteContract($contract_id) {
        $sql = "DELETE FROM `" . DB_PREFIX . "legal_contract`
                 WHERE contract_id='".(int)$contract_id."' LIMIT 1";
        $this->db->query($sql);
        return ($this->db->countAffected()>0);
    }

    /**
     * جلب عقد واحد
     */
    public function getContract($contract_id) {
        $sql = "SELECT lc.*
                  FROM `" . DB_PREFIX . "legal_contract` lc
                 WHERE lc.contract_id='".(int)$contract_id."' LIMIT 1";
        $query = $this->db->query($sql);
        if($query->num_rows) {
            return $query->row;
        }
        return null;
    }
}
