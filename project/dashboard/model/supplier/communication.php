<?php
/**
 * Model: Supplier Communication
 * نموذج التواصل مع الموردين المتقدم
 * يدير جميع أنواع التواصل مع الموردين (إيميل، رسائل، مكالمات، اجتماعات)
 */

class ModelSupplierCommunication extends Model {

    /**
     * إضافة تواصل جديد
     */
    public function addCommunication($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "supplier_communications SET
            supplier_id = '" . (int)$data['supplier_id'] . "',
            communication_type = '" . $this->db->escape($data['communication_type']) . "',
            subject = '" . $this->db->escape($data['subject']) . "',
            content = '" . $this->db->escape($data['content']) . "',
            communication_date = '" . $this->db->escape($data['communication_date']) . "',
            communication_time = '" . $this->db->escape($data['communication_time']) . "',
            direction = '" . $this->db->escape($data['direction']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            priority = '" . $this->db->escape($data['priority']) . "',
            contact_person = '" . $this->db->escape($data['contact_person']) . "',
            contact_email = '" . $this->db->escape($data['contact_email']) . "',
            contact_phone = '" . $this->db->escape($data['contact_phone']) . "',
            follow_up_date = " . ($data['follow_up_date'] ? "'" . $this->db->escape($data['follow_up_date']) . "'" : "NULL") . ",
            follow_up_notes = '" . $this->db->escape($data['follow_up_notes']) . "',
            tags = '" . $this->db->escape($data['tags']) . "',
            is_confidential = '" . (int)$data['is_confidential'] . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_date = NOW()
        ");

        $communication_id = $this->db->getLastId();

        // إضافة المرفقات
        if (!empty($data['attachments'])) {
            $this->addAttachments($communication_id, $data['attachments']);
        }

        // إضافة المشاركين
        if (!empty($data['participants'])) {
            $this->addParticipants($communication_id, $data['participants']);
        }

        // إرسال الإشعارات
        $this->sendNotifications($communication_id, $data);

        return $communication_id;
    }

    /**
     * تعديل تواصل
     */
    public function editCommunication($communication_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "supplier_communications SET
            supplier_id = '" . (int)$data['supplier_id'] . "',
            communication_type = '" . $this->db->escape($data['communication_type']) . "',
            subject = '" . $this->db->escape($data['subject']) . "',
            content = '" . $this->db->escape($data['content']) . "',
            communication_date = '" . $this->db->escape($data['communication_date']) . "',
            communication_time = '" . $this->db->escape($data['communication_time']) . "',
            direction = '" . $this->db->escape($data['direction']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            priority = '" . $this->db->escape($data['priority']) . "',
            contact_person = '" . $this->db->escape($data['contact_person']) . "',
            contact_email = '" . $this->db->escape($data['contact_email']) . "',
            contact_phone = '" . $this->db->escape($data['contact_phone']) . "',
            follow_up_date = " . ($data['follow_up_date'] ? "'" . $this->db->escape($data['follow_up_date']) . "'" : "NULL") . ",
            follow_up_notes = '" . $this->db->escape($data['follow_up_notes']) . "',
            tags = '" . $this->db->escape($data['tags']) . "',
            is_confidential = '" . (int)$data['is_confidential'] . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_date = NOW()
            WHERE communication_id = '" . (int)$communication_id . "'
        ");

        // حذف المرفقات والمشاركين القدامى
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_communication_attachments WHERE communication_id = '" . (int)$communication_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_communication_participants WHERE communication_id = '" . (int)$communication_id . "'");

        // إضافة المرفقات والمشاركين الجدد
        if (!empty($data['attachments'])) {
            $this->addAttachments($communication_id, $data['attachments']);
        }

        if (!empty($data['participants'])) {
            $this->addParticipants($communication_id, $data['participants']);
        }
    }

    /**
     * حذف تواصل
     */
    public function deleteCommunication($communication_id) {
        // حذف المرفقات والمشاركين
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_communication_attachments WHERE communication_id = '" . (int)$communication_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_communication_participants WHERE communication_id = '" . (int)$communication_id . "'");

        // حذف التواصل
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_communications WHERE communication_id = '" . (int)$communication_id . "'");

        return true;
    }

    /**
     * الحصول على تواصل
     */
    public function getCommunication($communication_id) {
        $query = $this->db->query("
            SELECT sc.*, s.name as supplier_name, s.email as supplier_email,
                   u1.firstname as created_by_name,
                   u2.firstname as modified_by_name
            FROM " . DB_PREFIX . "supplier_communications sc
            LEFT JOIN " . DB_PREFIX . "supplier s ON sc.supplier_id = s.supplier_id
            LEFT JOIN " . DB_PREFIX . "user u1 ON sc.created_by = u1.user_id
            LEFT JOIN " . DB_PREFIX . "user u2 ON sc.modified_by = u2.user_id
            WHERE sc.communication_id = '" . (int)$communication_id . "'
        ");

        if ($query->num_rows) {
            $communication = $query->row;

            // الحصول على المرفقات والمشاركين
            $communication['attachments'] = $this->getCommunicationAttachments($communication_id);
            $communication['participants'] = $this->getCommunicationParticipants($communication_id);

            return $communication;
        }

        return false;
    }

    /**
     * الحصول على قائمة التواصلات
     */
    public function getCommunications($data = array()) {
        $sql = "
            SELECT sc.*, s.name as supplier_name,
                   CASE
                       WHEN sc.communication_type = 'email' THEN 'بريد إلكتروني'
                       WHEN sc.communication_type = 'phone' THEN 'مكالمة هاتفية'
                       WHEN sc.communication_type = 'meeting' THEN 'اجتماع'
                       WHEN sc.communication_type = 'message' THEN 'رسالة'
                       WHEN sc.communication_type = 'video_call' THEN 'مكالمة فيديو'
                       ELSE 'غير محدد'
                   END as communication_type_name,
                   CASE
                       WHEN sc.direction = 'incoming' THEN 'واردة'
                       WHEN sc.direction = 'outgoing' THEN 'صادرة'
                       ELSE 'غير محدد'
                   END as direction_name,
                   CASE
                       WHEN sc.status = 'pending' THEN 'معلقة'
                       WHEN sc.status = 'completed' THEN 'مكتملة'
                       WHEN sc.status = 'cancelled' THEN 'ملغية'
                       WHEN sc.status = 'follow_up' THEN 'متابعة'
                       ELSE 'غير محدد'
                   END as status_name,
                   CASE
                       WHEN sc.priority = 'low' THEN 'منخفضة'
                       WHEN sc.priority = 'medium' THEN 'متوسطة'
                       WHEN sc.priority = 'high' THEN 'عالية'
                       WHEN sc.priority = 'urgent' THEN 'عاجلة'
                       ELSE 'غير محدد'
                   END as priority_name
            FROM " . DB_PREFIX . "supplier_communications sc
            LEFT JOIN " . DB_PREFIX . "supplier s ON sc.supplier_id = s.supplier_id
            WHERE 1=1
        ";

        // فلاتر البحث
        if (!empty($data['filter_supplier'])) {
            $sql .= " AND s.name LIKE '%" . $this->db->escape($data['filter_supplier']) . "%'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND sc.communication_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND sc.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_priority'])) {
            $sql .= " AND sc.priority = '" . $this->db->escape($data['filter_priority']) . "'";
        }

        if (!empty($data['filter_direction'])) {
            $sql .= " AND sc.direction = '" . $this->db->escape($data['filter_direction']) . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(sc.communication_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(sc.communication_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        if (!empty($data['filter_subject'])) {
            $sql .= " AND sc.subject LIKE '%" . $this->db->escape($data['filter_subject']) . "%'";
        }

        if (!empty($data['filter_tags'])) {
            $sql .= " AND sc.tags LIKE '%" . $this->db->escape($data['filter_tags']) . "%'";
        }

        // الترتيب
        $sort_data = array(
            'sc.communication_date',
            'sc.subject',
            'supplier_name',
            'sc.communication_type',
            'sc.status',
            'sc.priority',
            'sc.created_date'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sc.communication_date";
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
     * الحصول على إجمالي التواصلات
     */
    public function getTotalCommunications($data = array()) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "supplier_communications sc
            LEFT JOIN " . DB_PREFIX . "supplier s ON sc.supplier_id = s.supplier_id
            WHERE 1=1
        ";

        // نفس فلاتر البحث
        if (!empty($data['filter_supplier'])) {
            $sql .= " AND s.name LIKE '%" . $this->db->escape($data['filter_supplier']) . "%'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND sc.communication_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND sc.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_priority'])) {
            $sql .= " AND sc.priority = '" . $this->db->escape($data['filter_priority']) . "'";
        }

        if (!empty($data['filter_direction'])) {
            $sql .= " AND sc.direction = '" . $this->db->escape($data['filter_direction']) . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(sc.communication_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(sc.communication_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        if (!empty($data['filter_subject'])) {
            $sql .= " AND sc.subject LIKE '%" . $this->db->escape($data['filter_subject']) . "%'";
        }

        if (!empty($data['filter_tags'])) {
            $sql .= " AND sc.tags LIKE '%" . $this->db->escape($data['filter_tags']) . "%'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * إضافة المرفقات
     */
    private function addAttachments($communication_id, $attachments) {
        foreach ($attachments as $attachment) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "supplier_communication_attachments SET
                communication_id = '" . (int)$communication_id . "',
                filename = '" . $this->db->escape($attachment['filename']) . "',
                original_name = '" . $this->db->escape($attachment['original_name']) . "',
                file_size = '" . (int)$attachment['file_size'] . "',
                file_type = '" . $this->db->escape($attachment['file_type']) . "',
                file_path = '" . $this->db->escape($attachment['file_path']) . "'
            ");
        }
    }

    /**
     * إضافة المشاركين
     */
    private function addParticipants($communication_id, $participants) {
        foreach ($participants as $participant) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "supplier_communication_participants SET
                communication_id = '" . (int)$communication_id . "',
                user_id = '" . (int)$participant['user_id'] . "',
                role = '" . $this->db->escape($participant['role']) . "',
                is_required = '" . (int)$participant['is_required'] . "'
            ");
        }
    }

    /**
     * الحصول على مرفقات التواصل
     */
    private function getCommunicationAttachments($communication_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "supplier_communication_attachments
            WHERE communication_id = '" . (int)$communication_id . "'
        ");

        return $query->rows;
    }

    /**
     * الحصول على مشاركي التواصل
     */
    private function getCommunicationParticipants($communication_id) {
        $query = $this->db->query("
            SELECT scp.*, u.firstname, u.lastname, u.email
            FROM " . DB_PREFIX . "supplier_communication_participants scp
            LEFT JOIN " . DB_PREFIX . "user u ON scp.user_id = u.user_id
            WHERE scp.communication_id = '" . (int)$communication_id . "'
        ");

        return $query->rows;
    }

    /**
     * إرسال الإشعارات
     */
    private function sendNotifications($communication_id, $data) {
        // إرسال إشعار للمشاركين
        if (!empty($data['participants'])) {
            foreach ($data['participants'] as $participant) {
                $this->sendNotificationToUser($participant['user_id'], $communication_id, $data);
            }
        }

        // إرسال إيميل للمورد إذا كان التواصل صادر
        if ($data['direction'] == 'outgoing' && $data['communication_type'] == 'email') {
            $this->sendEmailToSupplier($data['supplier_id'], $data);
        }
    }

    /**
     * إرسال إشعار للمستخدم
     */
    private function sendNotificationToUser($user_id, $communication_id, $data) {
        $this->load->model('notification/notification');

        $notification_data = array(
            'user_id' => $user_id,
            'title' => 'تواصل جديد مع المورد',
            'message' => 'تم إضافة تواصل جديد: ' . $data['subject'],
            'type' => 'supplier_communication',
            'reference_id' => $communication_id,
            'url' => 'supplier/communication&communication_id=' . $communication_id
        );

        $this->model_notification_notification->addNotification($notification_data);
    }

    /**
     * إرسال إيميل للمورد
     */
    private function sendEmailToSupplier($supplier_id, $data) {
        $supplier = $this->getSupplier($supplier_id);

        if ($supplier && $supplier['email']) {
            $this->load->model('mail/mail');

            $mail_data = array(
                'to' => $supplier['email'],
                'subject' => $data['subject'],
                'message' => $data['content'],
                'from_name' => $this->config->get('config_name'),
                'from_email' => $this->config->get('config_email')
            );

            $this->model_mail_mail->send($mail_data);
        }
    }

    /**
     * الحصول على قائمة الموردين
     */
    public function getSuppliers() {
        $query = $this->db->query("
            SELECT supplier_id, name, email, telephone
            FROM " . DB_PREFIX . "supplier
            WHERE status = '1'
            ORDER BY name
        ");

        return $query->rows;
    }

    /**
     * الحصول على مورد
     */
    public function getSupplier($supplier_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "supplier
            WHERE supplier_id = '" . (int)$supplier_id . "'
        ");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على قائمة المستخدمين
     */
    public function getUsers() {
        $query = $this->db->query("
            SELECT user_id, CONCAT(firstname, ' ', lastname) as name, email
            FROM " . DB_PREFIX . "user
            WHERE status = '1'
            ORDER BY firstname, lastname
        ");

        return $query->rows;
    }

    /**
     * تحديث حالة التواصل
     */
    public function updateStatus($communication_id, $status) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "supplier_communications SET
            status = '" . $this->db->escape($status) . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_date = NOW()
            WHERE communication_id = '" . (int)$communication_id . "'
        ");
    }

    /**
     * إضافة متابعة
     */
    public function addFollowUp($communication_id, $notes, $follow_up_date = null) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "supplier_communications SET
            follow_up_notes = '" . $this->db->escape($notes) . "',
            follow_up_date = " . ($follow_up_date ? "'" . $this->db->escape($follow_up_date) . "'" : "NULL") . ",
            status = 'follow_up',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_date = NOW()
            WHERE communication_id = '" . (int)$communication_id . "'
        ");
    }

    /**
     * الحصول على إحصائيات التواصل
     */
    public function getCommunicationStats($supplier_id = null) {
        $where = $supplier_id ? " WHERE supplier_id = '" . (int)$supplier_id . "'" : "";

        $query = $this->db->query("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'follow_up' THEN 1 ELSE 0 END) as follow_up,
                SUM(CASE WHEN communication_type = 'email' THEN 1 ELSE 0 END) as emails,
                SUM(CASE WHEN communication_type = 'phone' THEN 1 ELSE 0 END) as calls,
                SUM(CASE WHEN communication_type = 'meeting' THEN 1 ELSE 0 END) as meetings,
                SUM(CASE WHEN communication_type = 'message' THEN 1 ELSE 0 END) as messages,
                SUM(CASE WHEN communication_type = 'video_call' THEN 1 ELSE 0 END) as video_calls,
                SUM(CASE WHEN direction = 'incoming' THEN 1 ELSE 0 END) as incoming,
                SUM(CASE WHEN direction = 'outgoing' THEN 1 ELSE 0 END) as outgoing,
                SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority,
                SUM(CASE WHEN DATE(communication_date) = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN DATE(communication_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week,
                SUM(CASE WHEN DATE(communication_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as this_month
            FROM " . DB_PREFIX . "supplier_communications" . $where . "
        ");

        return $query->num_rows ? $query->row : array();
    }

    /**
     * البحث المتقدم في التواصلات
     */
    public function searchCommunications($data = array()) {
        $sql = "
            SELECT sc.*, s.name as supplier_name, s.email as supplier_email,
                   u1.firstname as created_by_name,
                   CASE
                       WHEN sc.communication_type = 'email' THEN 'بريد إلكتروني'
                       WHEN sc.communication_type = 'phone' THEN 'مكالمة هاتفية'
                       WHEN sc.communication_type = 'meeting' THEN 'اجتماع'
                       WHEN sc.communication_type = 'message' THEN 'رسالة'
                       WHEN sc.communication_type = 'video_call' THEN 'مكالمة فيديو'
                       ELSE 'غير محدد'
                   END as communication_type_name,
                   CASE
                       WHEN sc.direction = 'incoming' THEN 'واردة'
                       WHEN sc.direction = 'outgoing' THEN 'صادرة'
                       ELSE 'غير محدد'
                   END as direction_name,
                   CASE
                       WHEN sc.status = 'pending' THEN 'معلقة'
                       WHEN sc.status = 'completed' THEN 'مكتملة'
                       WHEN sc.status = 'cancelled' THEN 'ملغية'
                       WHEN sc.status = 'follow_up' THEN 'متابعة'
                       ELSE 'غير محدد'
                   END as status_name,
                   CASE
                       WHEN sc.priority = 'low' THEN 'منخفضة'
                       WHEN sc.priority = 'medium' THEN 'متوسطة'
                       WHEN sc.priority = 'high' THEN 'عالية'
                       WHEN sc.priority = 'urgent' THEN 'عاجلة'
                       ELSE 'غير محدد'
                   END as priority_name,
                   (SELECT COUNT(*) FROM " . DB_PREFIX . "supplier_communication_attachments sca WHERE sca.communication_id = sc.communication_id) as attachment_count,
                   (SELECT COUNT(*) FROM " . DB_PREFIX . "supplier_communication_participants scp WHERE scp.communication_id = sc.communication_id) as participant_count
            FROM " . DB_PREFIX . "supplier_communications sc
            LEFT JOIN " . DB_PREFIX . "supplier s ON sc.supplier_id = s.supplier_id
            LEFT JOIN " . DB_PREFIX . "user u1 ON sc.created_by = u1.user_id
            WHERE 1=1
        ";

        // البحث النصي الشامل
        if (!empty($data['search_text'])) {
            $search_text = $this->db->escape($data['search_text']);
            $sql .= " AND (
                sc.subject LIKE '%" . $search_text . "%' OR
                sc.content LIKE '%" . $search_text . "%' OR
                sc.tags LIKE '%" . $search_text . "%' OR
                sc.contact_person LIKE '%" . $search_text . "%' OR
                s.name LIKE '%" . $search_text . "%'
            )";
        }

        // فلاتر متقدمة
        if (!empty($data['supplier_ids']) && is_array($data['supplier_ids'])) {
            $sql .= " AND sc.supplier_id IN (" . implode(',', array_map('intval', $data['supplier_ids'])) . ")";
        }

        if (!empty($data['communication_types']) && is_array($data['communication_types'])) {
            $types = array_map(array($this->db, 'escape'), $data['communication_types']);
            $sql .= " AND sc.communication_type IN ('" . implode("','", $types) . "')";
        }

        if (!empty($data['statuses']) && is_array($data['statuses'])) {
            $statuses = array_map(array($this->db, 'escape'), $data['statuses']);
            $sql .= " AND sc.status IN ('" . implode("','", $statuses) . "')";
        }

        if (!empty($data['priorities']) && is_array($data['priorities'])) {
            $priorities = array_map(array($this->db, 'escape'), $data['priorities']);
            $sql .= " AND sc.priority IN ('" . implode("','", $priorities) . "')";
        }

        if (!empty($data['directions']) && is_array($data['directions'])) {
            $directions = array_map(array($this->db, 'escape'), $data['directions']);
            $sql .= " AND sc.direction IN ('" . implode("','", $directions) . "')";
        }

        // فلاتر التاريخ المتقدمة
        if (!empty($data['date_range'])) {
            switch ($data['date_range']) {
                case 'today':
                    $sql .= " AND DATE(sc.communication_date) = CURDATE()";
                    break;
                case 'yesterday':
                    $sql .= " AND DATE(sc.communication_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                    break;
                case 'this_week':
                    $sql .= " AND YEARWEEK(sc.communication_date) = YEARWEEK(CURDATE())";
                    break;
                case 'last_week':
                    $sql .= " AND YEARWEEK(sc.communication_date) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))";
                    break;
                case 'this_month':
                    $sql .= " AND YEAR(sc.communication_date) = YEAR(CURDATE()) AND MONTH(sc.communication_date) = MONTH(CURDATE())";
                    break;
                case 'last_month':
                    $sql .= " AND YEAR(sc.communication_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(sc.communication_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
                    break;
                case 'this_year':
                    $sql .= " AND YEAR(sc.communication_date) = YEAR(CURDATE())";
                    break;
            }
        }

        if (!empty($data['date_from'])) {
            $sql .= " AND DATE(sc.communication_date) >= '" . $this->db->escape($data['date_from']) . "'";
        }

        if (!empty($data['date_to'])) {
            $sql .= " AND DATE(sc.communication_date) <= '" . $this->db->escape($data['date_to']) . "'";
        }

        // فلاتر إضافية
        if (isset($data['has_attachments']) && $data['has_attachments'] !== '') {
            if ($data['has_attachments']) {
                $sql .= " AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "supplier_communication_attachments sca WHERE sca.communication_id = sc.communication_id)";
            } else {
                $sql .= " AND NOT EXISTS (SELECT 1 FROM " . DB_PREFIX . "supplier_communication_attachments sca WHERE sca.communication_id = sc.communication_id)";
            }
        }

        if (isset($data['has_follow_up']) && $data['has_follow_up'] !== '') {
            if ($data['has_follow_up']) {
                $sql .= " AND sc.follow_up_date IS NOT NULL";
            } else {
                $sql .= " AND sc.follow_up_date IS NULL";
            }
        }

        if (isset($data['is_overdue']) && $data['is_overdue']) {
            $sql .= " AND sc.follow_up_date < CURDATE() AND sc.status != 'completed'";
        }

        if (isset($data['is_confidential']) && $data['is_confidential'] !== '') {
            $sql .= " AND sc.is_confidential = '" . (int)$data['is_confidential'] . "'";
        }

        // الترتيب
        $sort_data = array(
            'sc.communication_date',
            'sc.subject',
            'supplier_name',
            'sc.communication_type',
            'sc.status',
            'sc.priority',
            'sc.created_date',
            'sc.follow_up_date'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sc.communication_date";
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
     * الحصول على إجمالي نتائج البحث المتقدم
     */
    public function getTotalSearchResults($data = array()) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "supplier_communications sc
            LEFT JOIN " . DB_PREFIX . "supplier s ON sc.supplier_id = s.supplier_id
            WHERE 1=1
        ";

        // نفس فلاتر البحث المتقدم
        if (!empty($data['search_text'])) {
            $search_text = $this->db->escape($data['search_text']);
            $sql .= " AND (
                sc.subject LIKE '%" . $search_text . "%' OR
                sc.content LIKE '%" . $search_text . "%' OR
                sc.tags LIKE '%" . $search_text . "%' OR
                sc.contact_person LIKE '%" . $search_text . "%' OR
                s.name LIKE '%" . $search_text . "%'
            )";
        }

        // باقي الفلاتر...
        if (!empty($data['supplier_ids']) && is_array($data['supplier_ids'])) {
            $sql .= " AND sc.supplier_id IN (" . implode(',', array_map('intval', $data['supplier_ids'])) . ")";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * تصدير التواصلات
     */
    public function exportCommunications($data = array(), $format = 'csv') {
        $communications = $this->searchCommunications($data);

        if ($format == 'csv') {
            $output = "رقم التواصل,المورد,الموضوع,النوع,الاتجاه,الأولوية,الحالة,التاريخ,الوقت,أنشأ بواسطة\n";

            foreach ($communications as $communication) {
                $output .= '"' . $communication['communication_id'] . '",';
                $output .= '"' . $communication['supplier_name'] . '",';
                $output .= '"' . $communication['subject'] . '",';
                $output .= '"' . $communication['communication_type_name'] . '",';
                $output .= '"' . $communication['direction_name'] . '",';
                $output .= '"' . $communication['priority_name'] . '",';
                $output .= '"' . $communication['status_name'] . '",';
                $output .= '"' . date('Y-m-d', strtotime($communication['communication_date'])) . '",';
                $output .= '"' . $communication['communication_time'] . '",';
                $output .= '"' . $communication['created_by_name'] . '"';
                $output .= "\n";
            }

            return $output;
        }

        return false;
    }

    /**
     * الحصول على تقارير التواصل
     */
    public function getCommunicationReports($data = array()) {
        $reports = array();

        // تقرير الملخص العام
        $reports['summary'] = $this->getCommunicationStats();

        // تقرير التواصل حسب النوع
        $query = $this->db->query("
            SELECT
                communication_type,
                CASE
                    WHEN communication_type = 'email' THEN 'بريد إلكتروني'
                    WHEN communication_type = 'phone' THEN 'مكالمة هاتفية'
                    WHEN communication_type = 'meeting' THEN 'اجتماع'
                    WHEN communication_type = 'message' THEN 'رسالة'
                    WHEN communication_type = 'video_call' THEN 'مكالمة فيديو'
                    ELSE 'غير محدد'
                END as type_name,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM " . DB_PREFIX . "supplier_communications)), 2) as percentage
            FROM " . DB_PREFIX . "supplier_communications
            GROUP BY communication_type
            ORDER BY count DESC
        ");
        $reports['by_type'] = $query->rows;

        // تقرير التواصل حسب المورد
        $query = $this->db->query("
            SELECT
                s.name as supplier_name,
                COUNT(*) as count,
                SUM(CASE WHEN sc.status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN sc.status = 'pending' THEN 1 ELSE 0 END) as pending,
                MAX(sc.communication_date) as last_communication
            FROM " . DB_PREFIX . "supplier_communications sc
            LEFT JOIN " . DB_PREFIX . "supplier s ON sc.supplier_id = s.supplier_id
            GROUP BY sc.supplier_id, s.name
            ORDER BY count DESC
            LIMIT 10
        ");
        $reports['by_supplier'] = $query->rows;

        // تقرير التواصل حسب الشهر
        $query = $this->db->query("
            SELECT
                DATE_FORMAT(communication_date, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(CASE WHEN communication_type = 'email' THEN 1 ELSE 0 END) as emails,
                SUM(CASE WHEN communication_type = 'phone' THEN 1 ELSE 0 END) as calls,
                SUM(CASE WHEN communication_type = 'meeting' THEN 1 ELSE 0 END) as meetings
            FROM " . DB_PREFIX . "supplier_communications
            WHERE communication_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(communication_date, '%Y-%m')
            ORDER BY month DESC
        ");
        $reports['by_month'] = $query->rows;

        // تقرير الأولوية والحالة
        $query = $this->db->query("
            SELECT
                priority,
                CASE
                    WHEN priority = 'low' THEN 'منخفضة'
                    WHEN priority = 'medium' THEN 'متوسطة'
                    WHEN priority = 'high' THEN 'عالية'
                    WHEN priority = 'urgent' THEN 'عاجلة'
                    ELSE 'غير محدد'
                END as priority_name,
                status,
                CASE
                    WHEN status = 'pending' THEN 'معلقة'
                    WHEN status = 'completed' THEN 'مكتملة'
                    WHEN status = 'cancelled' THEN 'ملغية'
                    WHEN status = 'follow_up' THEN 'متابعة'
                    ELSE 'غير محدد'
                END as status_name,
                COUNT(*) as count
            FROM " . DB_PREFIX . "supplier_communications
            GROUP BY priority, status
            ORDER BY
                FIELD(priority, 'urgent', 'high', 'medium', 'low'),
                FIELD(status, 'pending', 'follow_up', 'completed', 'cancelled')
        ");
        $reports['by_priority_status'] = $query->rows;

        return $reports;
    }

    /**
     * الحصول على إحصائيات لوحة التحكم
     */
    public function getDashboardStatistics() {
        $stats = array();

        // الإحصائيات الأساسية
        $stats['basic'] = $this->getCommunicationStats();

        // التواصلات الحديثة (آخر 5)
        $query = $this->db->query("
            SELECT sc.*, s.name as supplier_name
            FROM " . DB_PREFIX . "supplier_communications sc
            LEFT JOIN " . DB_PREFIX . "supplier s ON sc.supplier_id = s.supplier_id
            ORDER BY sc.created_date DESC
            LIMIT 5
        ");
        $stats['recent'] = $query->rows;

        // التواصلات المتأخرة في المتابعة
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "supplier_communications
            WHERE follow_up_date < CURDATE() AND status != 'completed'
        ");
        $stats['overdue_follow_ups'] = $query->row['count'];

        // التواصلات المجدولة لليوم
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "supplier_communications
            WHERE DATE(follow_up_date) = CURDATE()
        ");
        $stats['today_follow_ups'] = $query->row['count'];

        // متوسط وقت الاستجابة (بالساعات)
        $query = $this->db->query("
            SELECT AVG(TIMESTAMPDIFF(HOUR, created_date, modified_date)) as avg_response_time
            FROM " . DB_PREFIX . "supplier_communications
            WHERE modified_date IS NOT NULL AND status = 'completed'
        ");
        $stats['avg_response_time'] = round($query->row['avg_response_time'], 2);

        // أكثر الموردين تواصلاً (آخر 30 يوم)
        $query = $this->db->query("
            SELECT s.name as supplier_name, COUNT(*) as count
            FROM " . DB_PREFIX . "supplier_communications sc
            LEFT JOIN " . DB_PREFIX . "supplier s ON sc.supplier_id = s.supplier_id
            WHERE sc.communication_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY sc.supplier_id, s.name
            ORDER BY count DESC
            LIMIT 5
        ");
        $stats['top_suppliers'] = $query->rows;

        return $stats;
    }
                SUM(CASE WHEN communication_type = 'meeting' THEN 1 ELSE 0 END) as meetings
            FROM " . DB_PREFIX . "supplier_communications" . $where
        );

        return $query->row;
    }

    /**
     * البحث في التواصلات
     */
    public function searchCommunications($keyword) {
        $query = $this->db->query("
            SELECT sc.*, s.name as supplier_name
            FROM " . DB_PREFIX . "supplier_communications sc
            LEFT JOIN " . DB_PREFIX . "supplier s ON sc.supplier_id = s.supplier_id
            WHERE sc.subject LIKE '%" . $this->db->escape($keyword) . "%'
            OR sc.content LIKE '%" . $this->db->escape($keyword) . "%'
            OR sc.tags LIKE '%" . $this->db->escape($keyword) . "%'
            OR s.name LIKE '%" . $this->db->escape($keyword) . "%'
            ORDER BY sc.communication_date DESC
            LIMIT 50
        ");

        return $query->rows;
    }

    /**
     * الحصول على التواصلات المطلوب متابعتها
     */
    public function getFollowUpCommunications() {
        $query = $this->db->query("
            SELECT sc.*, s.name as supplier_name
            FROM " . DB_PREFIX . "supplier_communications sc
            LEFT JOIN " . DB_PREFIX . "supplier s ON sc.supplier_id = s.supplier_id
            WHERE sc.follow_up_date IS NOT NULL
            AND sc.follow_up_date <= CURDATE()
            AND sc.status != 'completed'
            ORDER BY sc.follow_up_date ASC
        ");

        return $query->rows;
    }
}
