<?php
namespace Cart;

class Aym {
    /*
     * AYM - All Your Management
     * الوحدة المركزية لإدارة العمليات المشتركة في النظام
     * 
     * تتضمن وظائف:
     * - ActivityLogger: تسجيل الأنشطة
     * - DocumentManager: إدارة المستندات
     * - NotificationSystem: إدارة الإشعارات
     * - MessagingSystem: نظام المراسلات الداخلية
     * - WorkflowEngine: محرك سير العمل
     * - AuditTrail: سجل التدقيق
     */
    
    private $db;
    private $user;
    private $request;

    public function __construct($registry) {
        $this->db = $registry->get('db');
        $this->request = $registry->get('request');
        $this->user = $registry->get('user');
    }
    
    // #############################
    // # 1. ACTIVITY LOGGER METHODS
    // #############################
    
    /**
     * تسجيل نشاط في النظام
     * 
     * @param string $action_type نوع الإجراء (create, update, delete, view, login, etc)
     * @param string $module الوحدة المرتبطة (product, order, customer, etc)
     * @param string $description وصف النشاط
     * @param string $reference_type نوع المرجع (اختياري)
     * @param int $reference_id معرف المرجع (اختياري)
     * @param int $override_user_id معرف المستخدم للتجاوز (اختياري)
     * @return int معرف سجل النشاط
     */
    public function logActivity($action_type, $module, $description, $reference_type = null, $reference_id = null, $override_user_id = null) {
        // استخدام معرف المستخدم المتجاوز إذا تم تقديمه، أو الحصول عليه من كائن المستخدم
        $user_id = (int)$this->user->getId() ?? 0;
        $user_agent = isset($this->request->server['HTTP_USER_AGENT']) ? $this->request->server['HTTP_USER_AGENT'] : '';
        $user_ip = $this->getRealIpAddr();                   
        $data = [
            'user_id' => $user_id,
            'action_type' => $action_type,
            'module' => $module,
            'description' => $description,
            'ip_address' => $user_ip,
            'user_agent' => $user_agent,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->query("INSERT INTO " . DB_PREFIX . "activity_log SET 
            user_id = '" . (int)$data['user_id'] . "', 
            action_type = '" . $this->db->escape($data['action_type']) . "', 
            module = '" . $this->db->escape($data['module']) . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            ip_address = '" . $this->db->escape($data['ip_address']) . "', 
            user_agent = '" . $this->db->escape($data['user_agent']) . "', 
            reference_type = " . ($data['reference_type'] ? "'" . $this->db->escape($data['reference_type']) . "'" : "NULL") . ", 
            reference_id = " . ($data['reference_id'] ? (int)$data['reference_id'] : "NULL") . ", 
            created_at = '" . $this->db->escape($data['created_at']) . "'");
        
        return $this->db->getLastId();
    }
    
    /**
     * الحصول على سجل الأنشطة مع خيارات تصفية
     * 
     * @param array $filters خيارات التصفية
     * @param int $start بداية النتائج
     * @param int $limit عدد النتائج
     * @return array سجلات الأنشطة
     */
    public function getActivities($filters = [], $start = 0, $limit = 10) {
        $sql = "SELECT al.*, u.username, u.firstname, u.lastname 
                FROM " . DB_PREFIX . "activity_log al 
                LEFT JOIN " . DB_PREFIX . "user u ON (al.user_id = u.user_id)
                WHERE 1=1";
        
        // تطبيق الفلاتر
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = '" . (int)$filters['user_id'] . "'";
        }
        
        if (!empty($filters['action_type'])) {
            $sql .= " AND al.action_type = '" . $this->db->escape($filters['action_type']) . "'";
        }
        
        if (!empty($filters['module'])) {
            $sql .= " AND al.module = '" . $this->db->escape($filters['module']) . "'";
        }
        
        if (!empty($filters['reference_type'])) {
            $sql .= " AND al.reference_type = '" . $this->db->escape($filters['reference_type']) . "'";
        }
        
        if (!empty($filters['reference_id'])) {
            $sql .= " AND al.reference_id = '" . (int)$filters['reference_id'] . "'";
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND al.created_at >= '" . $this->db->escape($filters['date_from']) . "'";
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND al.created_at <= '" . $this->db->escape($filters['date_to']) . "'";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (al.description LIKE '%" . $this->db->escape($filters['search']) . "%'
                    OR u.username LIKE '%" . $this->db->escape($filters['search']) . "%'
                    OR u.firstname LIKE '%" . $this->db->escape($filters['search']) . "%'
                    OR u.lastname LIKE '%" . $this->db->escape($filters['search']) . "%')";
        }
        
        $sql .= " ORDER BY al.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * عدد سجلات الأنشطة مع التصفية
     * 
     * @param array $filters خيارات التصفية
     * @return int عدد السجلات
     */
    public function getTotalActivities($filters = []) {
        $sql = "SELECT COUNT(*) AS total 
                FROM " . DB_PREFIX . "activity_log al 
                LEFT JOIN " . DB_PREFIX . "user u ON (al.user_id = u.user_id)
                WHERE 1=1";
        
        // تطبيق الفلاتر (نفس كود الطريقة السابقة)
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = '" . (int)$filters['user_id'] . "'";
        }
        
        if (!empty($filters['action_type'])) {
            $sql .= " AND al.action_type = '" . $this->db->escape($filters['action_type']) . "'";
        }
        
        if (!empty($filters['module'])) {
            $sql .= " AND al.module = '" . $this->db->escape($filters['module']) . "'";
        }
        
        if (!empty($filters['reference_type'])) {
            $sql .= " AND al.reference_type = '" . $this->db->escape($filters['reference_type']) . "'";
        }
        
        if (!empty($filters['reference_id'])) {
            $sql .= " AND al.reference_id = '" . (int)$filters['reference_id'] . "'";
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND al.created_at >= '" . $this->db->escape($filters['date_from']) . "'";
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND al.created_at <= '" . $this->db->escape($filters['date_to']) . "'";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (al.description LIKE '%" . $this->db->escape($filters['search']) . "%'
                    OR u.username LIKE '%" . $this->db->escape($filters['search']) . "%'
                    OR u.firstname LIKE '%" . $this->db->escape($filters['search']) . "%'
                    OR u.lastname LIKE '%" . $this->db->escape($filters['search']) . "%')";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    // ################################
    // # 2. DOCUMENT MANAGER METHODS
    // ################################
    
    /**
     * رفع وتخزين مستند جديد
     * 
     * @param array $file معلومات الملف المرفوع ($_FILES)
     * @param array $data بيانات المستند (العنوان، الوصف، إلخ)
     * @param string $reference_module الوحدة المرتبطة
     * @param int $reference_id معرف المرجع
     * @return int|bool معرف المستند أو false في حالة الفشل
     */
    public function uploadDocument($file, $data, $reference_module = null, $reference_id = null) {
        // التحقق من الملف
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        // إنشاء مسار حفظ الملفات
        $upload_dir = DIR_UPLOAD . 'documents/' . date('Y/m/d/');
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // إنشاء اسم فريد للملف
        $filename = date('YmdHis') . '_' . uniqid() . '_' . $this->cleanFilename($file['name']);
        $file_path = $upload_dir . $filename;
        
        // نقل الملف إلى المسار
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return false;
        }
        
        // حفظ بيانات المستند في قاعدة البيانات
            $user_id = (int)$this->user->getId() ?? 0;

        
        $document_data = [
            'title' => $data['title'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'document_type' => isset($data['document_type']) ? $data['document_type'] : 'other',
            'file_path' => str_replace(DIR_UPLOAD, '', $file_path),
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'file_type' => $file['type'],
            'creator_id' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'status' => isset($data['status']) ? $data['status'] : 'draft',
            'reference_module' => $reference_module,
            'reference_id' => $reference_id,
            'tags' => isset($data['tags']) ? $data['tags'] : null,
        ];
        
        $this->db->query("INSERT INTO " . DB_PREFIX . "unified_document SET
            title = '" . $this->db->escape($document_data['title']) . "',
            description = '" . $this->db->escape($document_data['description']) . "',
            document_type = '" . $this->db->escape($document_data['document_type']) . "',
            file_path = '" . $this->db->escape($document_data['file_path']) . "',
            file_name = '" . $this->db->escape($document_data['file_name']) . "',
            file_size = '" . (int)$document_data['file_size'] . "',
            file_type = '" . $this->db->escape($document_data['file_type']) . "',
            creator_id = '" . (int)$document_data['creator_id'] . "',
            created_at = '" . $this->db->escape($document_data['created_at']) . "',
            updated_at = '" . $this->db->escape($document_data['updated_at']) . "',
            status = '" . $this->db->escape($document_data['status']) . "',
            reference_module = " . ($document_data['reference_module'] ? "'" . $this->db->escape($document_data['reference_module']) . "'" : "NULL") . ",
            reference_id = " . ($document_data['reference_id'] ? (int)$document_data['reference_id'] : "NULL") . ",
            tags = " . ($document_data['tags'] ? "'" . $this->db->escape($document_data['tags']) . "'" : "NULL"));
        
        $document_id = $this->db->getLastId();
        
        // تسجيل النشاط
        $this->logActivity('create', 'document', 'تم إنشاء مستند جديد: ' . $document_data['title'], 'document', $document_id);
        
        return $document_id;
    }
    
    /**
     * تنظيف اسم الملف
     * 
     * @param string $filename اسم الملف
     * @return string اسم الملف بعد التنظيف
     */
    private function cleanFilename($filename) {
        // إزالة الأحرف الخاصة والمسافات
        $filename = preg_replace('/[^\w\-\.\,\(\)]/u', '_', $filename);
        return $filename;
    }
    
    /**
     * الحصول على معلومات مستند
     * 
     * @param int $document_id معرف المستند
     * @return array بيانات المستند
     */
    public function getDocument($document_id) {
        $query = $this->db->query("SELECT d.*, u.username, u.firstname, u.lastname 
            FROM " . DB_PREFIX . "unified_document d
            LEFT JOIN " . DB_PREFIX . "user u ON (d.creator_id = u.user_id)
            WHERE d.document_id = '" . (int)$document_id . "'");
        
        return $query->row;
    }
    
    /**
     * البحث عن المستندات
     * 
     * @param array $filters خيارات التصفية
     * @param int $start بداية النتائج
     * @param int $limit عدد النتائج
     * @return array المستندات المطابقة
     */
    public function getDocuments($filters = [], $start = 0, $limit = 10) {
        $sql = "SELECT d.*, u.username, u.firstname, u.lastname 
                FROM " . DB_PREFIX . "unified_document d
                LEFT JOIN " . DB_PREFIX . "user u ON (d.creator_id = u.user_id)
                WHERE 1=1";
        
        // تطبيق الفلاتر
        if (!empty($filters['title'])) {
            $sql .= " AND d.title LIKE '%" . $this->db->escape($filters['title']) . "%'";
        }
        
        if (!empty($filters['document_type'])) {
            $sql .= " AND d.document_type = '" . $this->db->escape($filters['document_type']) . "'";
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND d.status = '" . $this->db->escape($filters['status']) . "'";
        }
        
        if (!empty($filters['creator_id'])) {
            $sql .= " AND d.creator_id = '" . (int)$filters['creator_id'] . "'";
        }
        
        if (!empty($filters['reference_module'])) {
            $sql .= " AND d.reference_module = '" . $this->db->escape($filters['reference_module']) . "'";
        }
        
        if (!empty($filters['reference_id'])) {
            $sql .= " AND d.reference_id = '" . (int)$filters['reference_id'] . "'";
        }
        
        if (!empty($filters['tags'])) {
            $sql .= " AND d.tags LIKE '%" . $this->db->escape($filters['tags']) . "%'";
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND d.created_at >= '" . $this->db->escape($filters['date_from']) . "'";
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND d.created_at <= '" . $this->db->escape($filters['date_to']) . "'";
        }
        
        $sql .= " ORDER BY d.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * عدد المستندات المطابقة للفلاتر
     * 
     * @param array $filters خيارات التصفية
     * @return int عدد المستندات
     */
    public function getTotalDocuments($filters = []) {
        $sql = "SELECT COUNT(*) AS total 
                FROM " . DB_PREFIX . "unified_document d
                LEFT JOIN " . DB_PREFIX . "user u ON (d.creator_id = u.user_id)
                WHERE 1=1";
        
        // تطبيق الفلاتر (نفس كود getDocuments)
        if (!empty($filters['title'])) {
            $sql .= " AND d.title LIKE '%" . $this->db->escape($filters['title']) . "%'";
        }
        
        if (!empty($filters['document_type'])) {
            $sql .= " AND d.document_type = '" . $this->db->escape($filters['document_type']) . "'";
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND d.status = '" . $this->db->escape($filters['status']) . "'";
        }
        
        if (!empty($filters['creator_id'])) {
            $sql .= " AND d.creator_id = '" . (int)$filters['creator_id'] . "'";
        }
        
        if (!empty($filters['reference_module'])) {
            $sql .= " AND d.reference_module = '" . $this->db->escape($filters['reference_module']) . "'";
        }
        
        if (!empty($filters['reference_id'])) {
            $sql .= " AND d.reference_id = '" . (int)$filters['reference_id'] . "'";
        }
        
        if (!empty($filters['tags'])) {
            $sql .= " AND d.tags LIKE '%" . $this->db->escape($filters['tags']) . "%'";
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND d.created_at >= '" . $this->db->escape($filters['date_from']) . "'";
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND d.created_at <= '" . $this->db->escape($filters['date_to']) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * تحديث بيانات مستند
     * 
     * @param int $document_id معرف المستند
     * @param array $data البيانات الجديدة
     * @return bool نجاح العملية
     */
    public function updateDocument($document_id, $data) {
        $set_array = [];
        
        // إعداد البيانات للتحديث
        if (isset($data['title'])) {
            $set_array[] = "title = '" . $this->db->escape($data['title']) . "'";
        }
        
        if (isset($data['description'])) {
            $set_array[] = "description = '" . $this->db->escape($data['description']) . "'";
        }
        
        if (isset($data['document_type'])) {
            $set_array[] = "document_type = '" . $this->db->escape($data['document_type']) . "'";
        }
        
        if (isset($data['status'])) {
            $set_array[] = "status = '" . $this->db->escape($data['status']) . "'";
        }
        
        if (isset($data['tags'])) {
            $set_array[] = "tags = '" . $this->db->escape($data['tags']) . "'";
        }
        
        // دائمًا تحديث وقت التعديل
        $set_array[] = "updated_at = '" . date('Y-m-d H:i:s') . "'";
        
        if (!empty($set_array)) {
            $this->db->query("UPDATE " . DB_PREFIX . "unified_document 
                SET " . implode(', ', $set_array) . " 
                WHERE document_id = '" . (int)$document_id . "'");
            
            // تسجيل النشاط
            $this->logActivity('update', 'document', 'تم تحديث المستند: ' . $data['title'], 'document', $document_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * حذف مستند
     * 
     * @param int $document_id معرف المستند
     * @return bool نجاح العملية
     */
    public function deleteDocument($document_id) {
        // الحصول على معلومات المستند قبل الحذف
        $document = $this->getDocument($document_id);
        
        if (!$document) {
            return false;
        }
        
        // حذف الملف الفعلي
        $file_path = DIR_UPLOAD . $document['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // حذف السجل من قاعدة البيانات
        $this->db->query("DELETE FROM " . DB_PREFIX . "unified_document WHERE document_id = '" . (int)$document_id . "'");
        
        // حذف جميع أذونات المستند
        $this->db->query("DELETE FROM " . DB_PREFIX . "document_permission WHERE document_id = '" . (int)$document_id . "'");
        
        // تسجيل النشاط
        $this->logActivity('delete', 'document', 'تم حذف المستند: ' . $document['title'], 'document', $document_id);
        
        return true;
    }
    
    /**
     * إضافة أذونات للمستند
     * 
     * @param int $document_id معرف المستند
     * @param array $permission بيانات الإذن
     * @return int|bool معرف الإذن أو false في حالة الفشل
     */
    public function addDocumentPermission($document_id, $permission) {
            $user_id = (int)$this->user->getId() ?? 0;

        $this->db->query("INSERT INTO " . DB_PREFIX . "document_permission SET
            document_id = '" . (int)$document_id . "',
            user_id = " . (isset($permission['user_id']) ? (int)$permission['user_id'] : "NULL") . ",
            user_group_id = " . (isset($permission['user_group_id']) ? (int)$permission['user_group_id'] : "NULL") . ",
            permission_type = '" . $this->db->escape($permission['permission_type']) . "',
            granted_by = '" . $user_id . "',
            granted_at = '" . date('Y-m-d H:i:s') . "',
            expires_at = " . (!empty($permission['expires_at']) ? "'" . $this->db->escape($permission['expires_at']) . "'" : "NULL"));
        
        return $this->db->getLastId();
    }
    
    /**
     * التحقق من صلاحيات المستخدم للمستند
     * 
     * @param int $document_id معرف المستند
     * @param int $user_id معرف المستخدم
     * @param string $permission_type نوع الإذن (view, edit, delete, approve, share)
     * @return bool هل المستخدم لديه الصلاحية
     */
    public function checkDocumentPermission($document_id, $user_id, $permission_type) {
        // الحصول على مجموعة المستخدم
        $query = $this->db->query("SELECT user_group_id FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$user_id . "'");
        
        if (!$query->num_rows) {
            return false;
        }
        
        $user_group_id = $query->row['user_group_id'];
        
        // التحقق من صلاحيات المستخدم
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "document_permission 
            WHERE document_id = '" . (int)$document_id . "' 
            AND (
                (user_id = '" . (int)$user_id . "' AND (permission_type = '" . $this->db->escape($permission_type) . "' OR permission_type = 'approve' OR permission_type = 'share'))
                OR 
                (user_group_id = '" . (int)$user_group_id . "' AND (permission_type = '" . $this->db->escape($permission_type) . "' OR permission_type = 'approve' OR permission_type = 'share'))
            )
            AND (expires_at IS NULL OR expires_at > NOW())");
        
        // التحقق من منشئ المستند
        $document_query = $this->db->query("SELECT creator_id FROM " . DB_PREFIX . "unified_document WHERE document_id = '" . (int)$document_id . "'");
        
        if ($document_query->num_rows && $document_query->row['creator_id'] == $user_id) {
            return true; // منشئ المستند لديه جميع الصلاحيات
        }
        
        return ($query->num_rows > 0);
    }
    
    // ################################
    // # 3. NOTIFICATION SYSTEM METHODS
    // ################################
    
    /**
     * إرسال إشعار لمستخدم
     * 
     * @param int $user_id معرف المستخدم المستهدف
     * @param string $title عنوان الإشعار
     * @param string $content محتوى الإشعار
     * @param string $module الوحدة المرتبطة
     * @param string $reference_type نوع المرجع (اختياري)
     * @param int $reference_id معرف المرجع (اختياري)
     * @param string $priority أولوية الإشعار (اختياري)
     * @param string $action_url رابط الإجراء (اختياري)
     * @return int معرف الإشعار
     */
    public function sendNotification($user_id, $title, $content, $module, $reference_type = null, $reference_id = null, $priority = 'normal', $action_url = null) {
            $creator_id = (int)$this->user->getId() ?? 0;

        
        $this->db->query("INSERT INTO " . DB_PREFIX . "unified_notification SET
            user_id = '" . (int)$user_id . "',
            title = '" . $this->db->escape($title) . "',
            content = '" . $this->db->escape($content) . "',
            module = '" . $this->db->escape($module) . "',
            reference_type = " . ($reference_type ? "'" . $this->db->escape($reference_type) . "'" : "NULL") . ",
            reference_id = " . ($reference_id ? (int)$reference_id : "NULL") . ",
            created_at = '" . date('Y-m-d H:i:s') . "',
            read_at = NULL,
            expiry_at = '" . date('Y-m-d H:i:s', strtotime('+30 days')) . "',
            priority = '" . $this->db->escape($priority) . "',
            action_url = " . ($action_url ? "'" . $this->db->escape($action_url) . "'" : "NULL") . ",
            created_by = '" . $creator_id . "'");
        
        return $this->db->getLastId();
    }
    
    /**
     * إرسال إشعار لمجموعة مستخدمين
     * 
     * @param int $user_group_id معرف مجموعة المستخدمين
     * @param string $title عنوان الإشعار
     * @param string $content محتوى الإشعار
     * @param string $module الوحدة المرتبطة
     * @param string $reference_type نوع المرجع (اختياري)
     * @param int $reference_id معرف المرجع (اختياري)
     * @param string $priority أولوية الإشعار
     * @param string $action_url رابط الإجراء (اختياري)
     * @return array معرفات الإشعارات المرسلة
     */
    public function sendGroupNotification($user_group_id, $title, $content, $module, $reference_type = null, $reference_id = null, $priority = 'normal', $action_url = null) {
        $notification_ids = [];
        
        // الحصول على قائمة المستخدمين في المجموعة
        $query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user WHERE user_group_id = '" . (int)$user_group_id . "'");
        
        foreach ($query->rows as $user) {
            $notification_ids[] = $this->sendNotification(
                $user['user_id'],
                $title,
                $content,
                $module,
                $reference_type,
                $reference_id,
                $priority,
                $action_url
            );
        }
        
        return $notification_ids;
    }
    
    /**
     * إرسال إشعار للنظام (عام)
     * 
     * @param string $title عنوان الإشعار
     * @param string $content محتوى الإشعار
     * @param string $type نوع الإشعار (info, warning, error, success, alert)
     * @param string $reference_type نوع المرجع (اختياري)
     * @param int $reference_id معرف المرجع (اختياري)
     * @return int معرف الإشعار
     */
    public function sendSystemNotification($title, $content, $type = 'info', $reference_type = null, $reference_id = null) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "system_notifications SET
            user_id = NULL,
            user_group_id = NULL,
            type = '" . $this->db->escape($type) . "',
            title = '" . $this->db->escape($title) . "',
            message = '" . $this->db->escape($content) . "',
            reference_type = " . ($reference_type ? "'" . $this->db->escape($reference_type) . "'" : "NULL") . ",
            reference_id = " . ($reference_id ? (int)$reference_id : "NULL") . ",
            is_read = 0,
            created_at = '" . date('Y-m-d H:i:s') . "',
            expiry_date = '" . date('Y-m-d H:i:s', strtotime('+30 days')) . "'");
        
        return $this->db->getLastId();
    }
    
    /**
     * الحصول على الإشعارات غير المقروءة للمستخدم
     * 
     * @param int $user_id معرف المستخدم
     * @param int $limit عدد الإشعارات
     * @return array الإشعارات غير المقروءة
     */
    public function getUnreadNotifications($user_id, $limit = 10) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "unified_notification 
            WHERE user_id = '" . (int)$user_id . "' 
            AND read_at IS NULL 
            AND (expiry_at IS NULL OR expiry_at > NOW())
            ORDER BY created_at DESC
            LIMIT " . (int)$limit);
        
        return $query->rows;
    }
    
    /**
     * تحديث حالة قراءة الإشعار
     * 
     * @param int $notification_id معرف الإشعار
     * @param bool $is_read حالة القراءة
     * @return bool نجاح العملية
     */
    public function markNotificationAsRead($notification_id, $is_read = true) {
        $this->db->query("UPDATE " . DB_PREFIX . "unified_notification SET
            read_at = " . ($is_read ? "'" . date('Y-m-d H:i:s') . "'" : "NULL") . "
            WHERE notification_id = '" . (int)$notification_id . "'");
        
        return true;
    }
    
    /**
     * تحديث حالة قراءة جميع إشعارات المستخدم
     * 
     * @param int $user_id معرف المستخدم
     * @param bool $is_read حالة القراءة
     * @return bool نجاح العملية
     */
    public function markAllNotificationsAsRead($user_id, $is_read = true) {
        $this->db->query("UPDATE " . DB_PREFIX . "unified_notification SET
            read_at = " . ($is_read ? "'" . date('Y-m-d H:i:s') . "'" : "NULL") . "
            WHERE user_id = '" . (int)$user_id . "'
            AND read_at IS NULL");
        
        return true;
    }
    
    /**
     * حذف الإشعارات القديمة
     * 
     * @param int $days عدد الأيام للاحتفاظ بالإشعارات (الافتراضي 30 يوم)
     * @return int عدد الإشعارات المحذوفة
     */
    public function cleanupOldNotifications($days = 30) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "unified_notification 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
            AND read_at IS NOT NULL");
        
        $affected_rows = $this->db->countAffected();
        
        // أيضًا حذف الإشعارات المنتهية
        $this->db->query("DELETE FROM " . DB_PREFIX . "unified_notification 
            WHERE expiry_at < NOW()");
        
        $affected_rows += $this->db->countAffected();
        
        return $affected_rows;
    }
    
    // ################################
    // # 4. MESSAGING SYSTEM METHODS
    // ################################
    
    /**
     * إنشاء محادثة جديدة
     * 
     * @param string $title عنوان المحادثة (اختياري للمحادثات الخاصة)
     * @param string $type نوع المحادثة (private, group, department)
     * @param array $participants قائمة بمعرفات المشاركين
     * @param string $associated_module الوحدة المرتبطة (اختياري)
     * @param int $reference_id معرف المرجع (اختياري)
     * @return int معرف المحادثة
     */
    public function createConversation($title = null, $type = 'private', $participants = [], $associated_module = null, $reference_id = null) {
        // التحقق من توفر كائن المستخدم
            $user_id = (int)$this->user->getId() ?? 0;

        
        if ($user_id <= 0) {
            // لا يمكن إنشاء محادثة بدون مستخدم
            return false;
        }
        
        // إنشاء المحادثة
        $this->db->query("INSERT INTO " . DB_PREFIX . "internal_conversation SET
            title = " . ($title ? "'" . $this->db->escape($title) . "'" : "NULL") . ",
            type = '" . $this->db->escape($type) . "',
            creator_id = '" . $user_id . "',
            created_at = '" . date('Y-m-d H:i:s') . "',
            updated_at = '" . date('Y-m-d H:i:s') . "',
            status = 'active',
            associated_module = " . ($associated_module ? "'" . $this->db->escape($associated_module) . "'" : "NULL") . ",
            reference_id = " . ($reference_id ? (int)$reference_id : "NULL"));
        
        $conversation_id = $this->db->getLastId();
        
        // إضافة المنشئ كمشارك
        $this->db->query("INSERT INTO " . DB_PREFIX . "internal_participant SET
            conversation_id = '" . (int)$conversation_id . "',
            user_id = '" . $user_id . "',
            joined_at = '" . date('Y-m-d H:i:s') . "',
            role = 'admin',
            notification_settings = 'all'");
        
        // إضافة باقي المشاركين
        foreach ($participants as $participant_id) {
            if ($participant_id != $user_id) { // تجنب إضافة المنشئ مرة أخرى
                $this->db->query("INSERT INTO " . DB_PREFIX . "internal_participant SET
                    conversation_id = '" . (int)$conversation_id . "',
                    user_id = '" . (int)$participant_id . "',
                    joined_at = '" . date('Y-m-d H:i:s') . "',
                    role = 'member',
                    notification_settings = 'all'");
            }
        }
        
        // تسجيل النشاط
        $this->logActivity('create', 'messaging', 'تم إنشاء محادثة جديدة', 'conversation', $conversation_id);
        
        return $conversation_id;
    }
    
    /**
     * إرسال رسالة في محادثة
     * 
     * @param int $conversation_id معرف المحادثة
     * @param string $message_text نص الرسالة
     * @param string $message_type نوع الرسالة (text, file, link, task, workflow, approval)
     * @param int $parent_message_id معرف الرسالة الأم في حالة الرد (اختياري)
     * @param string $reference_module الوحدة المرتبطة (اختياري)
     * @param int $reference_id معرف المرجع (اختياري)
     * @param string $mentions قائمة المستخدمين المشار إليهم (اختياري)
     * @return int معرف الرسالة
     */
    public function sendMessage($conversation_id, $message_text, $message_type = 'text', $parent_message_id = null, $reference_module = null, $reference_id = null, $mentions = null) {
        // التحقق من توفر كائن المستخدم
            $user_id = (int)$this->user->getId() ?? 0;

        
        if ($user_id <= 0) {
            // لا يمكن إرسال رسالة بدون مستخدم
            return false;
        }
        
        // التحقق من وجود المحادثة ومشاركة المستخدم فيها
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_participant 
            WHERE conversation_id = '" . (int)$conversation_id . "' 
            AND user_id = '" . $user_id . "'
            AND left_at IS NULL");
        
        if (!$query->num_rows) {
            return false; // المستخدم ليس مشارك في المحادثة
        }
        
        // إرسال الرسالة
        $this->db->query("INSERT INTO " . DB_PREFIX . "internal_message SET
            conversation_id = '" . (int)$conversation_id . "',
            sender_id = '" . $user_id . "',
            message_text = '" . $this->db->escape($message_text) . "',
            sent_at = '" . date('Y-m-d H:i:s') . "',
            message_type = '" . $this->db->escape($message_type) . "',
            reference_module = " . ($reference_module ? "'" . $this->db->escape($reference_module) . "'" : "NULL") . ",
            reference_id = " . ($reference_id ? (int)$reference_id : "NULL") . ",
            parent_message_id = " . ($parent_message_id ? (int)$parent_message_id : "NULL") . ",
            mentions = " . ($mentions ? "'" . $this->db->escape($mentions) . "'" : "NULL"));
        
        $message_id = $this->db->getLastId();
        
        // تحديث وقت آخر تعديل للمحادثة
        $this->db->query("UPDATE " . DB_PREFIX . "internal_conversation SET
            updated_at = '" . date('Y-m-d H:i:s') . "'
            WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        // تحديث آخر رسالة مقروءة للمرسل
        $this->db->query("UPDATE " . DB_PREFIX . "internal_participant SET
            last_read_message_id = '" . (int)$message_id . "'
            WHERE conversation_id = '" . (int)$conversation_id . "'
            AND user_id = '" . $user_id . "'");
        
        // إرسال إشعارات للمشاركين الآخرين
        $this->sendMessageNotifications($conversation_id, $message_id);
        
        return $message_id;
    }
    
    /**
     * إرسال إشعارات عن رسالة جديدة للمشاركين في المحادثة
     * 
     * @param int $conversation_id معرف المحادثة
     * @param int $message_id معرف الرسالة
     * @return void
     */
    private function sendMessageNotifications($conversation_id, $message_id) {
        // الحصول على معلومات الرسالة
        $message_query = $this->db->query("SELECT m.*, u.firstname, u.lastname, c.title 
            FROM " . DB_PREFIX . "internal_message m
            LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "internal_conversation c ON (m.conversation_id = c.conversation_id)
            WHERE m.message_id = '" . (int)$message_id . "'");
        
        if (!$message_query->num_rows) {
            return;
        }
        
        $message = $message_query->row;
        $sender_name = $message['firstname'] . ' ' . $message['lastname'];
        $conversation_title = $message['title'] ? $message['title'] : 'محادثة خاصة';
        
        // الحصول على المشاركين في المحادثة (باستثناء المرسل)
        $participants_query = $this->db->query("SELECT p.user_id, p.notification_settings 
            FROM " . DB_PREFIX . "internal_participant p
            WHERE p.conversation_id = '" . (int)$conversation_id . "'
            AND p.user_id != '" . (int)$message['sender_id'] . "'
            AND p.left_at IS NULL");
        
        foreach ($participants_query->rows as $participant) {
            // التحقق من إعدادات الإشعارات
            if ($participant['notification_settings'] == 'none') {
                continue;
            }
            
            // التحقق من الإشارات إذا كانت إعدادات الإشعارات هي 'mentions' فقط
            if ($participant['notification_settings'] == 'mentions' && (!$message['mentions'] || strpos($message['mentions'], '"' . $participant['user_id'] . '"') === false)) {
                continue;
            }
            
            // إنشاء نص للإشعار
            $notification_title = 'رسالة جديدة من ' . $sender_name;
            $notification_content = 'لديك رسالة جديدة في "' . $conversation_title . '"';
            
            // إرسال الإشعار
            $this->sendNotification(
                $participant['user_id'],
                $notification_title,
                $notification_content,
                'messaging',
                'message',
                $message_id,
                'normal',
                'index.php?route=messaging/conversation&conversation_id=' . $conversation_id
            );
        }
    }
    
    /**
     * الحصول على محادثات المستخدم
     * 
     * @param int $user_id معرف المستخدم
     * @param int $start بداية النتائج
     * @param int $limit عدد النتائج
     * @return array المحادثات
     */
    public function getUserConversations($user_id, $start = 0, $limit = 10) {
        $sql = "SELECT c.*, p.role, p.last_read_message_id,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "internal_message m 
                 WHERE m.conversation_id = c.conversation_id) AS total_messages,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "internal_message m 
                 WHERE m.conversation_id = c.conversation_id 
                 AND m.message_id > IFNULL(p.last_read_message_id, 0)
                 AND m.sender_id != '" . (int)$user_id . "') AS unread_messages,
                (SELECT m.sent_at FROM " . DB_PREFIX . "internal_message m 
                 WHERE m.conversation_id = c.conversation_id 
                 ORDER BY m.sent_at DESC LIMIT 1) AS last_message_date,
                (SELECT u.firstname FROM " . DB_PREFIX . "internal_message m
                 LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id)
                 WHERE m.conversation_id = c.conversation_id 
                 ORDER BY m.sent_at DESC LIMIT 1) AS last_sender_firstname,
                (SELECT u.lastname FROM " . DB_PREFIX . "internal_message m
                 LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id)
                 WHERE m.conversation_id = c.conversation_id 
                 ORDER BY m.sent_at DESC LIMIT 1) AS last_sender_lastname,
                (SELECT m.message_text FROM " . DB_PREFIX . "internal_message m
                 WHERE m.conversation_id = c.conversation_id 
                 ORDER BY m.sent_at DESC LIMIT 1) AS last_message_text
                FROM " . DB_PREFIX . "internal_conversation c
                JOIN " . DB_PREFIX . "internal_participant p ON (c.conversation_id = p.conversation_id)
                WHERE p.user_id = '" . (int)$user_id . "'
                AND p.left_at IS NULL
                AND c.status = 'active'
                ORDER BY c.updated_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    // ################################
    // # 5. WORKFLOW ENGINE METHODS
    // ################################
    
    /**
     * إنشاء طلب موافقة جديد
     * 
     * @param int $workflow_id معرف سير العمل
     * @param array $request_data بيانات الطلب
     * @return int معرف الطلب
     */
    public function createWorkflowRequest($workflow_id, $request_data) {
        // التحقق من توفر كائن المستخدم
            $user_id = (int)$this->user->getId() ?? 0;

        
        if ($user_id <= 0) {
            // لا يمكن إنشاء طلب بدون مستخدم
            return false;
        }
        
        // الحصول على أول خطوة في سير العمل
        $step_query = $this->db->query("SELECT step_id FROM " . DB_PREFIX . "workflow_step 
            WHERE workflow_id = '" . (int)$workflow_id . "' 
            ORDER BY step_order ASC LIMIT 1");
        
        $first_step_id = $step_query->num_rows ? $step_query->row['step_id'] : null;
        
        // إنشاء الطلب
        $this->db->query("INSERT INTO " . DB_PREFIX . "workflow_request SET
            workflow_id = '" . (int)$workflow_id . "',
            current_step_id = " . ($first_step_id ? "'" . (int)$first_step_id . "'" : "NULL") . ",
            requester_id = '" . $user_id . "',
            title = '" . $this->db->escape($request_data['title']) . "',
            description = " . (isset($request_data['description']) ? "'" . $this->db->escape($request_data['description']) . "'" : "NULL") . ",
            status = 'pending',
            priority = '" . $this->db->escape(isset($request_data['priority']) ? $request_data['priority'] : 'normal') . "',
            reference_module = '" . $this->db->escape($request_data['reference_module']) . "',
            reference_id = '" . (int)$request_data['reference_id'] . "',
            created_at = '" . date('Y-m-d H:i:s') . "',
            updated_at = '" . date('Y-m-d H:i:s') . "',
            due_date = " . (isset($request_data['due_date']) ? "'" . $this->db->escape($request_data['due_date']) . "'" : "NULL"));
        
        $request_id = $this->db->getLastId();
        
        // تسجيل النشاط
        $this->logActivity('create', 'workflow', 'تم إنشاء طلب موافقة جديد: ' . $request_data['title'], 'workflow_request', $request_id);
        
        // إرسال إشعارات للموافقين في الخطوة الأولى
        if ($first_step_id) {
            $this->sendWorkflowStepNotifications($request_id, $first_step_id);
        }
        
        return $request_id;
    }
    
    /**
     * إرسال إشعارات لموافقي خطوة معينة
     * 
     * @param int $request_id معرف الطلب
     * @param int $step_id معرف الخطوة
     * @return void
     */
    private function sendWorkflowStepNotifications($request_id, $step_id) {
        // الحصول على معلومات الطلب
        $request_query = $this->db->query("SELECT r.*, u.firstname, u.lastname, w.name AS workflow_name 
            FROM " . DB_PREFIX . "workflow_request r
            LEFT JOIN " . DB_PREFIX . "user u ON (r.requester_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "unified_workflow w ON (r.workflow_id = w.workflow_id)
            WHERE r.request_id = '" . (int)$request_id . "'");
        
        if (!$request_query->num_rows) {
            return;
        }
        
        $request = $request_query->row;
        $requester_name = $request['firstname'] . ' ' . $request['lastname'];
        
        // الحصول على معلومات الخطوة
        $step_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "workflow_step 
            WHERE step_id = '" . (int)$step_id . "'");
        
        if (!$step_query->num_rows) {
            return;
        }
        
        $step = $step_query->row;
        
        // إرسال إشعارات للمستخدمين أو المجموعة
        if ($step['approver_user_id']) {
            // إرسال إشعار للمستخدم المعين
            $this->sendNotification(
                $step['approver_user_id'],
                'طلب موافقة جديد: ' . $request['title'],
                'تم إرسال طلب موافقة جديد من ' . $requester_name . ' يحتاج موافقتك.',
                'workflow',
                'workflow_request',
                $request_id,
                'normal',
                'index.php?route=workflow/approval&request_id=' . $request_id
            );
        } elseif ($step['approver_group_id']) {
            // الحصول على المستخدمين في المجموعة
            $users_query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user 
                WHERE user_group_id = '" . (int)$step['approver_group_id'] . "'");
            
            foreach ($users_query->rows as $user) {
                $this->sendNotification(
                    $user['user_id'],
                    'طلب موافقة جديد: ' . $request['title'],
                    'تم إرسال طلب موافقة جديد من ' . $requester_name . ' يحتاج موافقتك.',
                    'workflow',
                    'workflow_request',
                    $request_id,
                    'normal',
                    'index.php?route=workflow/approval&request_id=' . $request_id
                );
            }
        }
    }
    
    /**
     * تسجيل موافقة على طلب
     * 
     * @param int $request_id معرف الطلب
     * @param array $approval_data بيانات الموافقة
     * @return bool نجاح العملية
     */
    public function approveWorkflowRequest($request_id, $approval_data) {
        // التحقق من توفر كائن المستخدم
            $user_id = (int)$this->user->getId() ?? 0;

        if ($user_id <= 0) {
            // لا يمكن الموافقة بدون مستخدم
            return false;
        }
        
        // الحصول على معلومات الطلب والخطوة الحالية
        $request_query = $this->db->query("SELECT r.*, s.step_order, s.is_final_step, s.approval_type, s.approval_percentage 
            FROM " . DB_PREFIX . "workflow_request r
            LEFT JOIN " . DB_PREFIX . "workflow_step s ON (r.current_step_id = s.step_id)
            WHERE r.request_id = '" . (int)$request_id . "'");
        
        if (!$request_query->num_rows || $request_query->row['status'] != 'pending') {
            return false;
        }
        
        $request = $request_query->row;
        $current_step_id = $request['current_step_id'];
        
        // تسجيل الموافقة
        $this->db->query("INSERT INTO " . DB_PREFIX . "workflow_approval SET
            request_id = '" . (int)$request_id . "',
            step_id = '" . (int)$current_step_id . "',
            user_id = '" . $user_id . "',
            action = '" . $this->db->escape($approval_data['action']) . "',
            comment = " . (isset($approval_data['comment']) ? "'" . $this->db->escape($approval_data['comment']) . "'" : "NULL") . ",
            created_at = '" . date('Y-m-d H:i:s') . "',
            delegated_to = " . (isset($approval_data['delegated_to']) ? (int)$approval_data['delegated_to'] : "NULL"));
        
        // إذا كان التفويض
        if ($approval_data['action'] == 'delegated' && isset($approval_data['delegated_to'])) {
            // إرسال إشعار للمفوض إليه
            $this->sendNotification(
                $approval_data['delegated_to'],
                'تم تفويض طلب موافقة إليك',
                'تم تفويض طلب موافقة "' . $request['title'] . '" إليك للمراجعة والموافقة.',
                'workflow',
                'workflow_request',
                $request_id,
                'normal',
                'index.php?route=workflow/approval&request_id=' . $request_id
            );
            
            return true;
        }
        
        // إذا كان الرفض
        if ($approval_data['action'] == 'rejected') {
            // تحديث حالة الطلب
            $this->db->query("UPDATE " . DB_PREFIX . "workflow_request SET
                status = 'rejected',
                updated_at = '" . date('Y-m-d H:i:s') . "',
                completed_at = '" . date('Y-m-d H:i:s') . "'
                WHERE request_id = '" . (int)$request_id . "'");
            
            // تسجيل النشاط
            $this->logActivity('update', 'workflow', 'تم رفض طلب الموافقة: ' . $request['title'], 'workflow_request', $request_id);
            
            // إرسال إشعار للطالب
            $this->sendNotification(
                $request['requester_id'],
                'تم رفض طلب الموافقة',
                'تم رفض طلب الموافقة "' . $request['title'] . '".' . (isset($approval_data['comment']) ? ' السبب: ' . $approval_data['comment'] : ''),
                'workflow',
                'workflow_request',
                $request_id
            );
            
            return true;
        }
        
        // إذا كانت الموافقة
        if ($approval_data['action'] == 'approved') {
            // التحقق من نوع الموافقة المطلوبة للخطوة
            if ($request['approval_type'] == 'any_one') {
                // الموافقة من أي شخص كافية
                $this->moveToNextStep($request_id, $current_step_id);
            } else {
                // الموافقة من الجميع أو نسبة معينة
                $this->checkStepCompletion($request_id, $current_step_id);
            }
            
            return true;
        }
        
        // إذا كان تعليق فقط
        if ($approval_data['action'] == 'commented') {
            // إرسال إشعار للطالب
            $this->sendNotification(
                $request['requester_id'],
                'تعليق جديد على طلب الموافقة',
                'تم إضافة تعليق جديد على طلب الموافقة "' . $request['title'] . '": ' . $approval_data['comment'],
                'workflow',
                'workflow_request',
                $request_id
            );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * التحقق من اكتمال الموافقات في خطوة معينة
     * 
     * @param int $request_id معرف الطلب
     * @param int $step_id معرف الخطوة
     * @return void
     */
    private function checkStepCompletion($request_id, $step_id) {
        // الحصول على معلومات الخطوة
        $step_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "workflow_step 
            WHERE step_id = '" . (int)$step_id . "'");
        
        if (!$step_query->num_rows) {
            return;
        }
        
        $step = $step_query->row;
        
        // الحصول على عدد الموافقات المسجلة
        $approvals_query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "workflow_approval 
            WHERE request_id = '" . (int)$request_id . "' 
            AND step_id = '" . (int)$step_id . "'
            AND action = 'approved'");
        
        $approvals_count = $approvals_query->row['total'];
        
        // الحصول على عدد الموافقين المطلوبين
        $approvers_count = 0;
        
        if ($step['approver_user_id']) {
            $approvers_count = 1;
        } elseif ($step['approver_group_id']) {
            $users_query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "user 
                WHERE user_group_id = '" . (int)$step['approver_group_id'] . "'");
            $approvers_count = $users_query->row['total'];
        }
        
        // التحقق من اكتمال الموافقات حسب نوع الموافقة
        $step_completed = false;
        
        if ($step['approval_type'] == 'all' && $approvals_count >= $approvers_count) {
            $step_completed = true;
        } elseif ($step['approval_type'] == 'percentage' && $approvers_count > 0) {
            $percentage = ($approvals_count / $approvers_count) * 100;
            if ($percentage >= $step['approval_percentage']) {
                $step_completed = true;
            }
        }
        
        // إذا اكتملت الخطوة، الانتقال للخطوة التالية
        if ($step_completed) {
            $this->moveToNextStep($request_id, $step_id);
        }
    }
    
    /**
     * الانتقال للخطوة التالية في سير العمل
     * 
     * @param int $request_id معرف الطلب
     * @param int $current_step_id معرف الخطوة الحالية
     * @return void
     */
    private function moveToNextStep($request_id, $current_step_id) {
        // الحصول على معلومات الخطوة الحالية والطلب
        $request_query = $this->db->query("SELECT r.*, s.step_order, s.is_final_step, w.workflow_id 
            FROM " . DB_PREFIX . "workflow_request r
            LEFT JOIN " . DB_PREFIX . "workflow_step s ON (r.current_step_id = s.step_id)
            LEFT JOIN " . DB_PREFIX . "unified_workflow w ON (r.workflow_id = w.workflow_id)
            WHERE r.request_id = '" . (int)$request_id . "'");
        
        if (!$request_query->num_rows) {
            return;
        }
        
        $request = $request_query->row;
        $current_step_order = $request['step_order'];
        $is_final_step = $request['is_final_step'];
        
        // إذا كانت الخطوة النهائية
        if ($is_final_step) {
            // تحديث حالة الطلب إلى موافق
            $this->db->query("UPDATE " . DB_PREFIX . "workflow_request SET
                status = 'approved',
                updated_at = '" . date('Y-m-d H:i:s') . "',
                completed_at = '" . date('Y-m-d H:i:s') . "',
                current_step_id = NULL
                WHERE request_id = '" . (int)$request_id . "'");
            
            // تسجيل النشاط
            $this->logActivity('update', 'workflow', 'تمت الموافقة على طلب: ' . $request['title'], 'workflow_request', $request_id);
            
            // إرسال إشعار للطالب
            $this->sendNotification(
                $request['requester_id'],
                'تمت الموافقة على طلبك',
                'تمت الموافقة النهائية على طلب "' . $request['title'] . '".',
                'workflow',
                'workflow_request',
                $request_id
            );
            
            return;
        }
        
        // البحث عن الخطوة التالية
        $next_step_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "workflow_step 
            WHERE workflow_id = '" . (int)$request['workflow_id'] . "' 
            AND step_order = '" . ((int)$current_step_order + 1) . "'");
        
        if (!$next_step_query->num_rows) {
            // لا توجد خطوة تالية (هذا غير متوقع إذا كانت الخطوة الحالية ليست نهائية)
            return;
        }
        
        $next_step = $next_step_query->row;
        
        // تحديث الطلب بالخطوة التالية
        $this->db->query("UPDATE " . DB_PREFIX . "workflow_request SET
            current_step_id = '" . (int)$next_step['step_id'] . "',
            updated_at = '" . date('Y-m-d H:i:s') . "'
            WHERE request_id = '" . (int)$request_id . "'");
        
        // إرسال إشعارات للموافقين في الخطوة التالية
        $this->sendWorkflowStepNotifications($request_id, $next_step['step_id']);
    }
    
    /**
     * الحصول على طلبات الموافقة المعلقة للمستخدم
     * 
     * @param int $user_id معرف المستخدم
     * @param array $filters خيارات التصفية
     * @param int $start بداية النتائج
     * @param int $limit عدد النتائج
     * @return array طلبات الموافقة
     */
    public function getPendingApprovals($user_id, $filters = [], $start = 0, $limit = 10) {
        // الحصول على مجموعة المستخدم
        $user_query = $this->db->query("SELECT user_group_id FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$user_id . "'");
        
        if (!$user_query->num_rows) {
            return [];
        }
        
        $user_group_id = $user_query->row['user_group_id'];
        
        // بناء استعلام للحصول على الطلبات المعلقة للمستخدم
        $sql = "SELECT r.*, s.step_name, w.name AS workflow_name, 
                u_req.firstname AS requester_firstname, u_req.lastname AS requester_lastname,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "workflow_approval a 
                 WHERE a.request_id = r.request_id 
                 AND a.step_id = r.current_step_id 
                 AND a.user_id = '" . (int)$user_id . "') AS has_approve FROM " . DB_PREFIX . "workflow_request r
                LEFT JOIN " . DB_PREFIX . "workflow_step s ON (r.current_step_id = s.step_id)
                LEFT JOIN " . DB_PREFIX . "unified_workflow w ON (r.workflow_id = w.workflow_id)
                LEFT JOIN " . DB_PREFIX . "user u_req ON (r.requester_id = u_req.user_id)
                WHERE r.status = 'pending'
                AND s.step_id IS NOT NULL
                AND (
                    (s.approver_user_id = '" . (int)$user_id . "')
                    OR 
                    (s.approver_group_id = '" . (int)$user_group_id . "')
                )";
        
        // تطبيق الفلاتر
        if (!empty($filters['title'])) {
            $sql .= " AND r.title LIKE '%" . $this->db->escape($filters['title']) . "%'";
        }
        
        if (!empty($filters['priority'])) {
            $sql .= " AND r.priority = '" . $this->db->escape($filters['priority']) . "'";
        }
        
        if (!empty($filters['reference_module'])) {
            $sql .= " AND r.reference_module = '" . $this->db->escape($filters['reference_module']) . "'";
        }
        
        if (!empty($filters['due_date_from'])) {
            $sql .= " AND r.due_date >= '" . $this->db->escape($filters['due_date_from']) . "'";
        }
        
        if (!empty($filters['due_date_to'])) {
            $sql .= " AND r.due_date <= '" . $this->db->escape($filters['due_date_to']) . "'";
        }
        
        $sql .= " ORDER BY r.priority DESC, r.created_at ASC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    // ################################
    // # 6. AUDIT TRAIL METHODS
    // ################################
    
    /**
     * تسجيل حدث في سجل التدقيق
     * 
     * @param string $action الإجراء المتخذ
     * @param string $reference_type نوع المرجع
     * @param int $reference_id معرف المرجع
     * @param array $before_data البيانات قبل التغيير (اختياري)
     * @param array $after_data البيانات بعد التغيير (اختياري)
     * @return int معرف سجل التدقيق
     */
    public function addAuditLog($action, $reference_type, $reference_id, $before_data = null, $after_data = null) {

            $user_id = (int)$this->user->getId() ?? 0;
        
        
        $this->db->query("INSERT INTO " . DB_PREFIX . "audit_log SET
            user_id = '" . $user_id . "',
            action = '" . $this->db->escape($action) . "',
            reference_type = '" . $this->db->escape($reference_type) . "',
            reference_id = " . ($reference_id ? (int)$reference_id : "NULL") . ",
            before_data = " . ($before_data ? "'" . $this->db->escape(json_encode($before_data)) . "'" : "NULL") . ",
            after_data = " . ($after_data ? "'" . $this->db->escape(json_encode($after_data)) . "'" : "NULL") . ",
            timestamp = '" . date('Y-m-d H:i:s') . "'");
        
        return $this->db->getLastId();
    }
    
    /**
     * الحصول على سجل التدقيق للعنصر
     * 
     * @param string $reference_type نوع المرجع
     * @param int $reference_id معرف المرجع
     * @param int $start بداية النتائج
     * @param int $limit عدد النتائج
     * @return array سجلات التدقيق
     */
    public function getAuditLog($reference_type, $reference_id, $start = 0, $limit = 10) {
        $sql = "SELECT al.*, u.username, u.firstname, u.lastname
                FROM " . DB_PREFIX . "audit_log al
                LEFT JOIN " . DB_PREFIX . "user u ON (al.user_id = u.user_id)
                WHERE al.reference_type = '" . $this->db->escape($reference_type) . "'
                AND al.reference_id = '" . (int)$reference_id . "'
                ORDER BY al.timestamp DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        }
        
        $query = $this->db->query($sql);
        
        $audit_logs = $query->rows;
        
        // تحويل البيانات من JSON إلى مصفوفة
        foreach ($audit_logs as &$log) {
            if ($log['before_data']) {
                $log['before_data'] = json_decode($log['before_data'], true);
            }
            
            if ($log['after_data']) {
                $log['after_data'] = json_decode($log['after_data'], true);
            }
        }
        
        return $audit_logs;
    }
    
    /**
     * تسجيل تدقيق للتغييرات مع مراعاة الحقول الحساسة
     * 
     * @param string $action الإجراء المتخذ
     * @param string $reference_type نوع المرجع
     * @param int $reference_id معرف المرجع
     * @param array $before_data البيانات قبل التغيير
     * @param array $after_data البيانات بعد التغيير
     * @return int معرف سجل التدقيق
     */
    public function logDataChanges($action, $reference_type, $reference_id, $before_data, $after_data) {
        // الحصول على الحقول الحساسة
        $sensitive_fields = $this->getSensitiveFields($reference_type);
        
        // تنظيف البيانات
        $before_data_sanitized = $this->sanitizeData($before_data, $sensitive_fields);
        $after_data_sanitized = $this->sanitizeData($after_data, $sensitive_fields);
        
        // تسجيل التدقيق
        return $this->addAuditLog($action, $reference_type, $reference_id, $before_data_sanitized, $after_data_sanitized);
    }
    
    /**
     * جلب الحقول الحساسة لنوع كائن معين
     * 
     * @param string $reference_type نوع المرجع
     * @return array قائمة الحقول الحساسة
     */
    public function getSensitiveFields($reference_type) {
        // تحديد الحقول الحساسة لكل نوع كائن
        $sensitive_fields = [
            'user' => ['password', 'salt', 'token', 'code', 'ip'],
            'customer' => ['password', 'salt', 'token', 'card_number', 'card_cvv'],
            'supplier' => ['password', 'salt', 'token', 'bank_account'],
            'document' => ['file_path'],
            'payment' => ['card_number', 'card_cvv', 'authorization_code'],
            'api' => ['key', 'secret']
        ];
        
        return isset($sensitive_fields[$reference_type]) ? $sensitive_fields[$reference_type] : [];
    }
    
    /**
     * إخفاء القيم الحساسة من البيانات
     * 
     * @param array $data البيانات
     * @param array $sensitive_fields الحقول الحساسة
     * @return array البيانات بعد إخفاء القيم الحساسة
     */
    public function sanitizeData($data, $sensitive_fields) {
        if (!is_array($data) || !is_array($sensitive_fields)) {
            return $data;
        }
        
        foreach ($sensitive_fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '********';
            }
        }
        
        return $data;
    }
    
    // ################################
    // # UTILITY METHODS
    // ################################
    
    /**
     * الحصول على عنوان IP الحقيقي للمستخدم
     * 
     * @return string عنوان IP
     */
    public function getRealIpAddr() {
        if (!empty($this->request->server['HTTP_CLIENT_IP'])) {
            // خادم وكيل شفاف
            $ip = $this->request->server['HTTP_CLIENT_IP'];
        } elseif (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            // وكيل ويب
            $ip = $this->request->server['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $this->request->server['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    /**
     * تنسيق التاريخ والوقت بالشكل المحلي
     * 
     * @param string $datetime التاريخ والوقت بصيغة MySQL
     * @param string $format صيغة التنسيق المطلوبة
     * @return string التاريخ والوقت المنسق
     */
    public function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
        if (!$datetime) {
            return '';
        }
        
        $date = new DateTime($datetime);
        return $date->format($format);
    }
    
    /**
     * إنشاء رمز فريد
     * 
     * @param int $length طول الرمز
     * @param bool $include_letters تضمين الحروف
     * @param bool $include_numbers تضمين الأرقام
     * @param bool $include_symbols تضمين الرموز
     * @return string الرمز المولد
     */
    public function generateRandomCode($length = 8, $include_letters = true, $include_numbers = true, $include_symbols = false) {
        $chars = '';
        
        if ($include_letters) {
            $chars .= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        
        if ($include_numbers) {
            $chars .= '0123456789';
        }
        
        if ($include_symbols) {
            $chars .= '!@#$%^&*()_-+={}[]|:;<>,.?/';
        }
        
        if (empty($chars)) {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        }
        
        $code = '';
        $chars_length = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[rand(0, $chars_length - 1)];
        }
        
        return $code;
    }
    
    /**
     * تشفير النص باستخدام مفتاح النظام
     * 
     * @param string $data النص المراد تشفيره
     * @return string النص المشفر
     */
    public function encrypt($data) {
        return $this->encryptDecrypt($data, 'encrypt');
    }
    
    /**
     * فك تشفير النص باستخدام مفتاح النظام
     * 
     * @param string $data النص المشفر
     * @return string النص الأصلي
     */
    public function decrypt($data) {
        return $this->encryptDecrypt($data, 'decrypt');
    }
    
    /**
     * تشفير/فك تشفير البيانات
     * 
     * @param string $data البيانات
     * @param string $action الإجراء (encrypt/decrypt)
     * @return string البيانات المعالجة
     */
    private function encryptDecrypt($data, $action) {
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $secret_key = defined('DB_PREFIX') ? DB_PREFIX : 'cod_';
        $secret_iv = defined('HTTP_SERVER') ? HTTP_SERVER : 'my_secret_iv';
        
        // استخراج key و iv
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        
        if ($action == 'encrypt') {
            $output = openssl_encrypt($data, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($data), $encrypt_method, $key, 0, $iv);
        }
        
        return $output;
    }
}