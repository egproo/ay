<?php
class ModelGovernanceMeetings extends Model {

    // =================== [ Meetings CRUD ] ===================

    /**
     * جلب قائمة الاجتماعات بفلتر اختياري
     */
    public function getMeetings($filters=[]) {
        $sql = "SELECT gm.*,
                       u.firstname AS added_fname,
                       u.lastname  AS added_lname
                  FROM " . DB_PREFIX . "governance_meeting gm
             LEFT JOIN " . DB_PREFIX . "user u ON (gm.added_by = u.user_id)
                 WHERE 1";

        $where = [];

        if(!empty($filters['meeting_type'])) {
            $where[] = "gm.meeting_type = '".$this->db->escape($filters['meeting_type'])."'";
        }

        if($where) {
            $sql .= " AND ".implode(" AND ", $where);
        }
        $sql .= " ORDER BY gm.meeting_date DESC";

        $q = $this->db->query($sql);
        $rows = [];
        foreach($q->rows as $r) {
            $r['added_by_name'] = trim($r['added_fname'].' '.$r['added_lname']);
            $rows[] = $r;
        }
        return $rows;
    }

    /**
     * إضافة اجتماع
     */
    public function addMeeting($data) {
        $sql = "INSERT INTO `" . DB_PREFIX . "governance_meeting`
                SET
                  `meeting_type` = '".$this->db->escape($data['meeting_type'])."',
                  `title`        = '".$this->db->escape($data['title'])."',
                  `meeting_date` = '".$this->db->escape($data['meeting_date'])."',
                  `location`     = '".$this->db->escape($data['location'])."',
                  `agenda`       = '".$this->db->escape($data['agenda'])."',
                  `decisions`    = '".$this->db->escape($data['decisions'])."',
                  `added_by`     = '".(int)$data['added_by']."'";
        $this->db->query($sql);
        return $this->db->getLastId();
    }

    /**
     * تعديل اجتماع
     */
    public function updateMeeting($meeting_id, $data) {
        $fields = [];
        if(isset($data['meeting_type'])) {
            $fields[] = "meeting_type = '".$this->db->escape($data['meeting_type'])."'";
        }
        if(isset($data['title'])) {
            $fields[] = "title = '".$this->db->escape($data['title'])."'";
        }
        if(isset($data['meeting_date'])) {
            $fields[] = "meeting_date = '".$this->db->escape($data['meeting_date'])."'";
        }
        if(isset($data['location'])) {
            $fields[] = "location = '".$this->db->escape($data['location'])."'";
        }
        if(isset($data['agenda'])) {
            $fields[] = "agenda = '".$this->db->escape($data['agenda'])."'";
        }
        if(isset($data['decisions'])) {
            $fields[] = "decisions = '".$this->db->escape($data['decisions'])."'";
        }
        if(!$fields) return false;

        $sql = "UPDATE `" . DB_PREFIX . "governance_meeting`
                SET " . implode(", ", $fields) . "
                WHERE `meeting_id`='".(int)$meeting_id."' LIMIT 1";
        $this->db->query($sql);
        return ($this->db->countAffected() > 0);
    }

    /**
     * حذف اجتماع
     */
    public function deleteMeeting($meeting_id) {
        // أولًا يمكن حذف الحضور المرتبط (أو ON DELETE CASCADE لو كنت ضابط Foreign Key)
        $sql_del_att = "DELETE FROM `" . DB_PREFIX . "meeting_attendees`
                        WHERE meeting_id='".(int)$meeting_id."'";
        $this->db->query($sql_del_att);

        // ثم حذف الاجتماع
        $sql = "DELETE FROM `" . DB_PREFIX . "governance_meeting`
                WHERE meeting_id='".(int)$meeting_id."' LIMIT 1";
        $this->db->query($sql);
        return ($this->db->countAffected() > 0);
    }

    /**
     * جلب اجتماع واحد
     */
    public function getMeeting($meeting_id) {
        $sql = "SELECT gm.*, u.firstname AS added_fname, u.lastname AS added_lname
                  FROM `" . DB_PREFIX . "governance_meeting` gm
             LEFT JOIN `" . DB_PREFIX . "user` u ON (gm.added_by = u.user_id)
                 WHERE gm.meeting_id='".(int)$meeting_id."' LIMIT 1";
        $q = $this->db->query($sql);
        if($q->num_rows) {
            $r = $q->row;
            $r['added_by_name'] = trim($r['added_fname'].' '.$r['added_lname']);
            return $r;
        }
        return null;
    }

    // =================== [ Attendees CRUD ] ===================

    /**
     * جلب قائمة الحضور لاجتماع محدد
     */
    public function getAttendees($meeting_id) {
        $sql = "SELECT ma.*,
                       u.firstname AS user_fname,
                       u.lastname  AS user_lname
                  FROM `" . DB_PREFIX . "meeting_attendees` ma
             LEFT JOIN `" . DB_PREFIX . "user` u ON (ma.user_id = u.user_id)
                 WHERE ma.meeting_id='".(int)$meeting_id."'
               ORDER BY ma.attendee_id ASC";
        $q = $this->db->query($sql);
        $rows = [];
        foreach($q->rows as $row) {
            // دمج اسم المستخدم في حقل واحد
            $row['user_name'] = trim($row['user_fname'].' '.$row['user_lname']);
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * إضافة حاضر (موظف أو خارجي)
     */
    public function addAttendee($data) {
        $sql = "INSERT INTO `" . DB_PREFIX . "meeting_attendees`
                SET
                  meeting_id      = '".(int)$data['meeting_id']."',
                  user_id         = '".(int)$data['user_id']."',
                  external_name   = '".$this->db->escape($data['external_name'])."',
                  role_in_meeting = '".$this->db->escape($data['role_in_meeting'])."',
                  presence_status = '".$this->db->escape($data['presence_status'])."'";
        $this->db->query($sql);
        return $this->db->getLastId();
    }

    /**
     * تعديل حاضر
     */
    public function updateAttendee($attendee_id, $data) {
        $fields = [];
        if(isset($data['user_id'])) {
            $fields[] = "user_id='".(int)$data['user_id']."'";
        }
        if(isset($data['external_name'])) {
            $fields[] = "external_name='".$this->db->escape($data['external_name'])."'";
        }
        if(isset($data['role_in_meeting'])) {
            $fields[] = "role_in_meeting='".$this->db->escape($data['role_in_meeting'])."'";
        }
        if(isset($data['presence_status'])) {
            $fields[] = "presence_status='".$this->db->escape($data['presence_status'])."'";
        }
        if(!$fields) return false;

        $sql = "UPDATE `" . DB_PREFIX . "meeting_attendees`
                SET ".implode(", ",$fields)."
                WHERE attendee_id='".(int)$attendee_id."' LIMIT 1";
        $this->db->query($sql);
        return ($this->db->countAffected()>0);
    }

    /**
     * حذف حاضر
     */
    public function deleteAttendee($attendee_id) {
        $sql = "DELETE FROM `" . DB_PREFIX . "meeting_attendees`
                WHERE attendee_id='".(int)$attendee_id."' LIMIT 1";
        $this->db->query($sql);
        return ($this->db->countAffected()>0);
    }

    /**
     * جلب حاضر واحد
     */
    public function getAttendee($attendee_id) {
        $sql = "SELECT ma.*,
                       u.firstname AS user_fname,
                       u.lastname  AS user_lname
                  FROM `" . DB_PREFIX . "meeting_attendees` ma
             LEFT JOIN `" . DB_PREFIX . "user` u ON (ma.user_id = u.user_id)
                 WHERE ma.attendee_id='".(int)$attendee_id."' LIMIT 1";
        $q = $this->db->query($sql);
        if($q->num_rows) {
            $row = $q->row;
            $row['user_name'] = trim($row['user_fname'].' '.$row['user_lname']);
            return $row;
        }
        return null;
    }
}
