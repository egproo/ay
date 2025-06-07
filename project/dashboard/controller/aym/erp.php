<?php
class ControllerAymErp extends Controller {
    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('aym/erp');
    }
    
    // دالة الاستدعاء الافتراضية
    public function index() {

        return $this;
    }

    /**
     * Verificar si el usuario ha iniciado sesión
     * Este método debe ser llamado al principio de cada método que requiera autenticación
     * @return bool
     */
    private function checkUserLogged() {
        if ((int)$this->user->getId() <= 0) {
            return false;
        }else{
            return true;
        }

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
     * @return int معرف سجل النشاط
     */
    public function logActivity($action_type, $module, $description, $reference_type = null, $reference_id = null) {
        return $this->model_aym_erp->logActivity($action_type, $module, $description, $reference_type, $reference_id);
    }
    
    /**
     * الحصول على سجل الأنشطة مع خيارات تصفية
     */
    public function getActivities() {
        if (!$this->user->isLogged() || !$this->user->hasKey('activity_log/view')) {
            return [];
        }
        
        $filters = [];
        
        // استخراج المعلمات من الطلب
        if (isset($this->request->get['filters'])) {
            $filters = $this->request->get['filters'];
        }
        
        $start = isset($this->request->get['start']) ? (int)$this->request->get['start'] : 0;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;
        
        return $this->model_aym_erp->getActivities($filters, $start, $limit);
    }
    
    /**
     * الحصول على عدد سجلات الأنشطة
     */
    public function getTotalActivities() {
        if (!$this->user->isLogged() || !$this->user->hasKey('activity_log/view')) {
            return 0;
        }
        
        $filters = [];
        
        if (isset($this->request->get['filters'])) {
            $filters = $this->request->get['filters'];
        }
        
        return $this->model_aym_erp->getTotalActivities($filters);
    }
    
    // ################################
    // # 2. DOCUMENT MANAGER METHODS
    // ################################
    
    /**
     * رفع وتخزين مستند جديد
     */
    public function uploadDocument() {
        if (!$this->user->isLogged()) {
            return ['success' => false, 'error' => 'غير مصرح بالوصول'];
        }
        
        if (!isset($this->request->files['file'])) {
            return ['success' => false, 'error' => 'لم يتم تحديد ملف للرفع'];
        }
        
        $file = $this->request->files['file'];
        $data = [
            'title' => isset($this->request->post['title']) ? $this->request->post['title'] : $file['name'],
            'description' => isset($this->request->post['description']) ? $this->request->post['description'] : '',
            'document_type' => isset($this->request->post['document_type']) ? $this->request->post['document_type'] : 'other',
            'status' => isset($this->request->post['status']) ? $this->request->post['status'] : 'draft',
            'tags' => isset($this->request->post['tags']) ? $this->request->post['tags'] : null
        ];
        
        $reference_module = isset($this->request->post['reference_module']) ? $this->request->post['reference_module'] : null;
        $reference_id = isset($this->request->post['reference_id']) ? (int)$this->request->post['reference_id'] : null;
        
        $document_id = $this->model_aym_erp->uploadDocument($file, $data, $reference_module, $reference_id);
        
        if ($document_id) {
            return ['success' => true, 'document_id' => $document_id];
        } else {
            return ['success' => false, 'error' => 'فشل رفع المستند'];
        }
    }
    
    /**
     * الحصول على معلومات مستند
     */
    public function getDocument() {
        if (!$this->user->isLogged()) {
            return null;
        }
        
        $document_id = isset($this->request->get['document_id']) ? (int)$this->request->get['document_id'] : 0;
        
        if (!$document_id) {
            return null;
        }
        
        $document = $this->model_aym_erp->getDocument($document_id);
        
        // التحقق من صلاحية الوصول للمستند
        if ($document && $this->model_aym_erp->checkDocumentPermission($document_id, $this->user->getId(), 'view')) {
            return $document;
        }
        
        return null;
    }
    
    /**
     * البحث عن المستندات
     */
    public function getDocuments() {
        if (!$this->user->isLogged()) {
            return ['items' => [], 'total' => 0];
        }
        
        $filters = [];
        
        if (isset($this->request->get['filters'])) {
            $filters = $this->request->get['filters'];
        }
        
        $start = isset($this->request->get['start']) ? (int)$this->request->get['start'] : 0;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;
        
        $documents = $this->model_aym_erp->getDocuments($filters, $start, $limit);
        $total = $this->model_aym_erp->getTotalDocuments($filters);
        
        return ['items' => $documents, 'total' => $total];
    }
    
    /**
     * تحديث بيانات مستند
     */
    public function updateDocument() {
        if (!$this->user->isLogged()) {
            return ['success' => false, 'error' => 'غير مصرح بالوصول'];
        }
        
        $document_id = isset($this->request->post['document_id']) ? (int)$this->request->post['document_id'] : 0;
        
        if (!$document_id) {
            return ['success' => false, 'error' => 'معرف المستند مطلوب'];
        }
        
        // التحقق من صلاحية تعديل المستند
        if (!$this->model_aym_erp->checkDocumentPermission($document_id, $this->user->getId(), 'edit')) {
            return ['success' => false, 'error' => 'غير مصرح لك بتعديل هذا المستند'];
        }
        
        $data = [];
        
        if (isset($this->request->post['title'])) {
            $data['title'] = $this->request->post['title'];
        }
        
        if (isset($this->request->post['description'])) {
            $data['description'] = $this->request->post['description'];
        }
        
        if (isset($this->request->post['document_type'])) {
            $data['document_type'] = $this->request->post['document_type'];
        }
        
        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        }
        
        if (isset($this->request->post['tags'])) {
            $data['tags'] = $this->request->post['tags'];
        }
        
        $success = $this->model_aym_erp->updateDocument($document_id, $data);
        
        return ['success' => $success];
    }
    
    /**
     * حذف مستند
     */
    public function deleteDocument() {
        if (!$this->user->isLogged()) {
            return ['success' => false, 'error' => 'غير مصرح بالوصول'];
        }
        
        $document_id = isset($this->request->post['document_id']) ? (int)$this->request->post['document_id'] : 0;
        
        if (!$document_id) {
            return ['success' => false, 'error' => 'معرف المستند مطلوب'];
        }
        
        // التحقق من صلاحية حذف المستند
        if (!$this->model_aym_erp->checkDocumentPermission($document_id, $this->user->getId(), 'delete')) {
            return ['success' => false, 'error' => 'غير مصرح لك بحذف هذا المستند'];
        }
        
        $success = $this->model_aym_erp->deleteDocument($document_id);
        
        return ['success' => $success];
    }
    
    // ################################
    // # 3. NOTIFICATION SYSTEM METHODS
    // ################################
    
    /**
     * إرسال إشعار لمستخدم
     */
    public function sendNotification($user_id, $title, $content, $module, $reference_type = null, $reference_id = null, $priority = 'normal', $action_url = null) {
        if (!$this->user->isLogged()) {
            return false;
        }
        
        return $this->model_aym_erp->sendNotification($user_id, $title, $content, $module, $reference_type, $reference_id, $priority, $action_url);
    }
    
    /**
     * إرسال إشعار لمجموعة مستخدمين
     */
    public function sendGroupNotification($user_group_id, $title, $content, $module, $reference_type = null, $reference_id = null, $priority = 'normal', $action_url = null) {
        if (!$this->user->isLogged()) {
            return [];
        }
        
        return $this->model_aym_erp->sendGroupNotification($user_group_id, $title, $content, $module, $reference_type, $reference_id, $priority, $action_url);
    }
    
    /**
     * الحصول على الإشعارات غير المقروءة للمستخدم
     */
    public function getUnreadNotifications($user_id = null, $limit = 5) {
        if (!$this->user->isLogged()) {
            return [];
        }
        
        // إذا لم يتم تحديد معرف المستخدم، استخدم المستخدم الحالي
        if ($user_id === null) {
            $user_id = $this->user->getId();
        }
        
        // التحقق من الصلاحيات إذا كان المستخدم يطلب إشعارات مستخدم آخر
        if ($user_id != $this->user->getId() && !$this->user->hasKey('notifications/manage')) {
            return [];
        }
        
        return $this->model_aym_erp->getUnreadNotifications($user_id, $limit);
    }
    
    /**
     * تحديث حالة قراءة الإشعار
     */
    public function markNotificationAsRead($notification_id) {
        if (!$this->user->isLogged()) {
            return false;
        }
        
        // التحقق من ملكية الإشعار
        $notification = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "unified_notification WHERE notification_id = '" . (int)$notification_id . "'");
        
        if (!$notification->num_rows || $notification->row['user_id'] != $this->user->getId()) {
            // لا يمكن للمستخدم وضع علامة قراءة على إشعارات المستخدمين الآخرين، إلا إذا كان لديه صلاحية الإدارة
            if (!$this->user->hasKey('notifications/manage')) {
                return false;
            }
        }
        
        return $this->model_aym_erp->markNotificationAsRead($notification_id);
    }
    
    /**
     * تحديث حالة قراءة جميع إشعارات المستخدم
     */
    public function markAllNotificationsAsRead($user_id = null) {
        if (!$this->user->isLogged()) {
            return false;
        }
        
        // إذا لم يتم تحديد معرف المستخدم، استخدم المستخدم الحالي
        if ($user_id === null) {
            $user_id = $this->user->getId();
        }
        
        // التحقق من الصلاحيات إذا كان المستخدم يضع علامة قراءة على إشعارات مستخدم آخر
        if ($user_id != $this->user->getId() && !$this->user->hasKey('notifications/manage')) {
            return false;
        }
        
        return $this->model_aym_erp->markAllNotificationsAsRead($user_id);
    }
    
    // ################################
    // # 4. MESSAGING SYSTEM METHODS
    // ################################
    
    /**
     * إنشاء محادثة جديدة
     */
    public function createConversation() {
        if (!$this->user->isLogged()) {
            return ['success' => false, 'error' => 'غير مصرح بالوصول'];
        }
        
        // استخراج المعلمات من الطلب
        $title = isset($this->request->post['title']) ? $this->request->post['title'] : null;
        $type = isset($this->request->post['type']) ? $this->request->post['type'] : 'private';
        $participants = isset($this->request->post['participants']) ? json_decode($this->request->post['participants'], true) : [];
        $associated_module = isset($this->request->post['associated_module']) ? $this->request->post['associated_module'] : null;
        $reference_id = isset($this->request->post['reference_id']) ? (int)$this->request->post['reference_id'] : null;
        
        $conversation_id = $this->model_aym_erp->createConversation($title, $type, $participants, $associated_module, $reference_id);
        
        if ($conversation_id) {
            return ['success' => true, 'conversation_id' => $conversation_id];
        } else {
            return ['success' => false, 'error' => 'فشل إنشاء المحادثة'];
        }
    }
    
    /**
     * إرسال رسالة في محادثة
     */
    public function sendMessage() {
        if (!$this->user->isLogged()) {
            return ['success' => false, 'error' => 'غير مصرح بالوصول'];
        }
        
        // استخراج المعلمات من الطلب
        $conversation_id = isset($this->request->post['conversation_id']) ? (int)$this->request->post['conversation_id'] : 0;
        $message_text = isset($this->request->post['message_text']) ? $this->request->post['message_text'] : '';
        $message_type = isset($this->request->post['message_type']) ? $this->request->post['message_type'] : 'text';
        $parent_message_id = isset($this->request->post['parent_message_id']) ? (int)$this->request->post['parent_message_id'] : null;
        $reference_module = isset($this->request->post['reference_module']) ? $this->request->post['reference_module'] : null;
        $reference_id = isset($this->request->post['reference_id']) ? (int)$this->request->post['reference_id'] : null;
        $mentions = isset($this->request->post['mentions']) ? $this->request->post['mentions'] : null;
        
        if (!$conversation_id || !$message_text) {
            return ['success' => false, 'error' => 'معرف المحادثة ونص الرسالة مطلوبان'];
        }
        
        $message_id = $this->model_aym_erp->sendMessage($conversation_id, $message_text, $message_type, $parent_message_id, $reference_module, $reference_id, $mentions);
        
        if ($message_id) {
            // في حالة الرسالة النصية، نعيد فقط المعرف
            if ($message_type == 'text') {
                return ['success' => true, 'message_id' => $message_id];
            }
            
            // في حالة الملفات، نحتاج لمعالجة الملفات المرفقة
            if ($message_type == 'file' && isset($this->request->files['attachment'])) {
                $file = $this->request->files['attachment'];
                $attachment_id = $this->model_aym_erp->uploadMessageAttachment($message_id, $file);
                
                return [
                    'success' => true,
                    'message_id' => $message_id,
                    'attachment_id' => $attachment_id
                ];
            }
            
            return ['success' => true, 'message_id' => $message_id];
        } else {
            return ['success' => false, 'error' => 'فشل إرسال الرسالة'];
        }
    }
    
    /**
     * الحصول على محادثات المستخدم
     */
    public function getUserConversations($user_id = null, $start = 0, $limit = 10) {
        if (!$this->user->isLogged()) {
            return [];
        }
        
        // إذا لم يتم تحديد معرف المستخدم، استخدم المستخدم الحالي
        if ($user_id === null) {
            $user_id = $this->user->getId();
        }
        
        // التحقق من الصلاحيات إذا كان المستخدم يطلب محادثات مستخدم آخر
        if ($user_id != $this->user->getId() && !$this->user->hasKey('messaging/manage')) {
            return [];
        }
        
        // استخدام معلمات الطلب إذا كانت موجودة
        if (isset($this->request->get['start'])) {
            $start = (int)$this->request->get['start'];
        }
        
        if (isset($this->request->get['limit'])) {
            $limit = (int)$this->request->get['limit'];
        }
        
        return $this->model_aym_erp->getUserConversations($user_id, $start, $limit);
    }
    
    /**
     * الحصول على رسائل محادثة معينة
     */
    public function getConversationMessages($conversation_id = null, $start = 0, $limit = 20) {
        if (!$this->user->isLogged()) {
            return [];
        }
        
        // استخدام معلمات الطلب إذا كانت موجودة
        if ($conversation_id === null && isset($this->request->get['conversation_id'])) {
            $conversation_id = (int)$this->request->get['conversation_id'];
        }
        
        if ($conversation_id === null) {
            return [];
        }
        
        if (isset($this->request->get['start'])) {
            $start = (int)$this->request->get['start'];
        }
        
        if (isset($this->request->get['limit'])) {
            $limit = (int)$this->request->get['limit'];
        }
        
        // التحقق من مشاركة المستخدم في المحادثة
        $participant_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_participant 
            WHERE conversation_id = '" . (int)$conversation_id . "' 
            AND user_id = '" . (int)$this->user->getId() . "'
            AND left_at IS NULL");
        
        if (!$participant_query->num_rows && !$this->user->hasKey('messaging/manage')) {
            return [];
        }
        
        $messages = $this->model_aym_erp->getConversationMessages($conversation_id, $start, $limit);
        
        // تحديث آخر رسالة مقروءة للمستخدم إذا كانت هناك رسائل
        if (!empty($messages)) {
            // الرسائل تكون مرتبة تنازليًا (الأحدث أولاً)، لذا نأخذ آخر رسالة من القائمة
            $last_message_id = $messages[0]['message_id'];
            $this->model_aym_erp->updateLastReadMessage($conversation_id, $this->user->getId(), $last_message_id);
        }
        
        return $messages;
    }
    
    /**
     * إضافة مشارك جديد للمحادثة
     */
    public function addParticipantToConversation() {
        if (!$this->user->isLogged()) {
            return ['success' => false, 'error' => 'غير مصرح بالوصول'];
        }
        
        $conversation_id = isset($this->request->post['conversation_id']) ? (int)$this->request->post['conversation_id'] : 0;
        $user_id = isset($this->request->post['user_id']) ? (int)$this->request->post['user_id'] : 0;
        $role = isset($this->request->post['role']) ? $this->request->post['role'] : 'member';
        
        if (!$conversation_id || !$user_id) {
            return ['success' => false, 'error' => 'معرف المحادثة ومعرف المستخدم مطلوبان'];
        }
        
        // التحقق من صلاحية المستخدم لإضافة مشاركين
        $participant_query = $this->db->query("SELECT role FROM " . DB_PREFIX . "internal_participant 
            WHERE conversation_id = '" . (int)$conversation_id . "' 
            AND user_id = '" . (int)$this->user->getId() . "'
            AND left_at IS NULL");
        
        if (!$participant_query->num_rows) {
            return ['success' => false, 'error' => 'غير مصرح لك بإدارة هذه المحادثة'];
        }
        
        $user_role = $participant_query->row['role'];
        
        if ($user_role != 'admin' && !$this->user->hasKey('messaging/manage')) {
            return ['success' => false, 'error' => 'لا تملك صلاحية إضافة مشاركين'];
        }
        
        $success = $this->model_aym_erp->addParticipantToConversation($conversation_id, $user_id, $role);
        
        return ['success' => $success];
    }
    
    /**
     * إزالة مشارك من المحادثة
     */
    public function removeParticipantFromConversation() {
        if (!$this->user->isLogged()) {
            return ['success' => false, 'error' => 'غير مصرح بالوصول'];
        }
        
        $conversation_id = isset($this->request->post['conversation_id']) ? (int)$this->request->post['conversation_id'] : 0;
        $user_id = isset($this->request->post['user_id']) ? (int)$this->request->post['user_id'] : 0;
        
        if (!$conversation_id || !$user_id) {
            return ['success' => false, 'error' => 'معرف المحادثة ومعرف المستخدم مطلوبان'];
        }
        
        // التحقق من صلاحية المستخدم لإزالة مشاركين
        $participant_query = $this->db->query("SELECT role FROM " . DB_PREFIX . "internal_participant 
            WHERE conversation_id = '" . (int)$conversation_id . "' 
            AND user_id = '" . (int)$this->user->getId() . "'
            AND left_at IS NULL");
        
        if (!$participant_query->num_rows) {
            return ['success' => false, 'error' => 'غير مصرح لك بإدارة هذه المحادثة'];
        }
        
        $user_role = $participant_query->row['role'];
        
        // المستخدم يمكنه مغادرة المحادثة بنفسه
        if ($user_id == $this->user->getId()) {
            $success = $this->model_aym_erp->removeParticipantFromConversation($conversation_id, $user_id);
            return ['success' => $success];
        }
        
        // لإزالة مستخدم آخر، يجب أن يكون مشرفًا أو لديه صلاحية الإدارة
        if ($user_role != 'admin' && !$this->user->hasKey('messaging/manage')) {
            return ['success' => false, 'error' => 'لا تملك صلاحية إزالة مشاركين'];
        }
        
        $success = $this->model_aym_erp->removeParticipantFromConversation($conversation_id, $user_id);
        
        return ['success' => $success];
    }
    
    // ################################
    // # 5. WORKFLOW ENGINE METHODS
    // ################################
    
    /**
     * إنشاء سير عمل جديد
     */
    public function createWorkflow() {
        if (!$this->user->isLogged() || !$this->user->hasKey('workflow/manage')) {
            return ['success' => false, 'error' => 'غير مصرح بالوصول'];
        }
        
        // استخراج بيانات سير العمل
        $workflow_data = [];
        
        if (isset($this->request->post['workflow'])) {
            $workflow_data = json_decode($this->request->post['workflow'], true);
        } else {
            // استخراج البيانات الأساسية
            $workflow_data = [
                'name' => isset($this->request->post['name']) ? $this->request->post['name'] : '',
                'description' => isset($this->request->post['description']) ? $this->request->post['description'] : '',
                'workflow_type' => isset($this->request->post['workflow_type']) ? $this->request->post['workflow_type'] : 'approval',
                'status' => isset($this->request->post['status']) ? $this->request->post['status'] : 'active',
                'is_template' => isset($this->request->post['is_template']) ? (int)$this->request->post['is_template'] : 0,
                'department_id' => isset($this->request->post['department_id']) ? (int)$this->request->post['department_id'] : null,
                'escalation_enabled' => isset($this->request->post['escalation_enabled']) ? (int)$this->request->post['escalation_enabled'] : 0,
                'escalation_after_days' => isset($this->request->post['escalation_after_days']) ? (int)$this->request->post['escalation_after_days'] : null,
                'notify_creator' => isset($this->request->post['notify_creator']) ? (int)$this->request->post['notify_creator'] : 1,
            ];
            
            // استخراج خطوات سير العمل
            $workflow_data['steps'] = [];
            
            if (isset($this->request->post['steps']) && is_array($this->request->post['steps'])) {
                $workflow_data['steps'] = $this->request->post['steps'];
            }
        }
        
        if (empty($workflow_data['name']) || empty($workflow_data['workflow_type'])) {
            return ['success' => false, 'error' => 'اسم ونوع سير العمل مطلوبان'];
        }
        
        $workflow_id = $this->model_aym_erp->createWorkflow($workflow_data);
        
        if ($workflow_id) {
            return ['success' => true, 'workflow_id' => $workflow_id];
        } else {
            return ['success' => false, 'error' => 'فشل إنشاء سير العمل'];
        }
    }
    
    /**
     * إنشاء طلب موافقة جديد
     */
    public function createWorkflowRequest() {
        if (!$this->user->isLogged()) {
            return ['success' => false, 'error' => 'غير مصرح بالوصول'];
        }
        
        // استخراج البيانات
        $workflow_id = isset($this->request->post['workflow_id']) ? (int)$this->request->post['workflow_id'] : 0;
        
        if (!$workflow_id) {
            return ['success' => false, 'error' => 'معرف سير العمل مطلوب'];
        }
        
        $request_data = [
            'title' => isset($this->request->post['title']) ? $this->request->post['title'] : '',
            'description' => isset($this->request->post['description']) ? $this->request->post['description'] : '',
            'priority' => isset($this->request->post['priority']) ? $this->request->post['priority'] : 'normal',
'reference_module' => isset($this->request->post['reference_module']) ? $this->request->post['reference_module'] : 'general',
            'reference_id' => isset($this->request->post['reference_id']) ? (int)$this->request->post['reference_id'] : 0,
            'due_date' => isset($this->request->post['due_date']) ? $this->request->post['due_date'] : null
        ];
        
        if (empty($request_data['title'])) {
            return ['success' => false, 'error' => 'عنوان الطلب مطلوب'];
        }
        
        $request_id = $this->model_aym_erp->createWorkflowRequest($workflow_id, $request_data);
        
        if ($request_id) {
            return ['success' => true, 'request_id' => $request_id];
        } else {
            return ['success' => false, 'error' => 'فشل إنشاء طلب الموافقة'];
        }
    }
    
    /**
     * تسجيل موافقة على طلب
     */
    public function approveWorkflowRequest($request_id = null, $approval_data = null) {
        if (!$this->user->isLogged()) {
            return ['success' => false, 'error' => 'غير مصرح بالوصول'];
        }
        
        // استخدام معلمات الطلب إذا لم يتم تمريرها
        if ($request_id === null) {
            $request_id = isset($this->request->post['request_id']) ? (int)$this->request->post['request_id'] : 0;
        }
        
        if (!$request_id) {
            return ['success' => false, 'error' => 'معرف الطلب مطلوب'];
        }
        
        if ($approval_data === null) {
            $approval_data = [
                'action' => isset($this->request->post['action']) ? $this->request->post['action'] : '',
                'comment' => isset($this->request->post['comment']) ? $this->request->post['comment'] : null,
                'delegated_to' => isset($this->request->post['delegated_to']) ? (int)$this->request->post['delegated_to'] : null
            ];
        }
        
        if (empty($approval_data['action'])) {
            return ['success' => false, 'error' => 'إجراء الموافقة مطلوب'];
        }
        
        $success = $this->model_aym_erp->approveWorkflowRequest($request_id, $approval_data);
        
        return ['success' => $success];
    }
    
    /**
     * الحصول على طلبات الموافقة المعلقة للمستخدم
     */
    public function getPendingApprovals($user_id = null, $filters = [], $start = 0, $limit = 10) {
        if (!$this->user->isLogged()) {
            return [];
        }
        
        // إذا لم يتم تحديد معرف المستخدم، استخدم المستخدم الحالي
        if ($user_id === null) {
            $user_id = $this->user->getId();
        }
        
        // التحقق من الصلاحيات إذا كان المستخدم يطلب موافقات مستخدم آخر
        if ($user_id != $this->user->getId() && !$this->user->hasKey('workflow/manage')) {
            return [];
        }
        
        // استخدام معلمات الطلب إذا كانت موجودة
        if (isset($this->request->get['filters'])) {
            $filters = $this->request->get['filters'];
        }
        
        if (isset($this->request->get['start'])) {
            $start = (int)$this->request->get['start'];
        }
        
        if (isset($this->request->get['limit'])) {
            $limit = (int)$this->request->get['limit'];
        }
        
        return $this->model_aym_erp->getPendingApprovals($user_id, $filters, $start, $limit);
    }
    
    /**
     * الحصول على تفاصيل طلب موافقة
     */
    public function getWorkflowRequest() {
        if (!$this->user->isLogged()) {
            return null;
        }
        
        $request_id = isset($this->request->get['request_id']) ? (int)$this->request->get['request_id'] : 0;
        
        if (!$request_id) {
            return null;
        }
        
        $request = $this->model_aym_erp->getWorkflowRequest($request_id);
        
        // التحقق إذا كان المستخدم هو الطالب أو له علاقة بالطلب
        if (empty($request)) {
            return null;
        }
        
        $has_access = false;
        
        // المستخدم هو الطالب
        if ($request['requester_id'] == $this->user->getId()) {
            $has_access = true;
        }
        
        // المستخدم هو موافق في الخطوة الحالية
        if (!empty($request['current_step_id'])) {
            $user_group_id = $this->user->getGroupId();
            
            if (($request['approver_user_id'] == $this->user->getId()) || 
                ($request['approver_group_id'] == $user_group_id)) {
                $has_access = true;
            }
        }
        
        // المستخدم لديه صلاحية إدارة سير العمل
        if ($this->user->hasKey('workflow/manage')) {
            $has_access = true;
        }
        
        if (!$has_access) {
            return null;
        }
        
        return $request;
    }
    
    /**
     * الحصول على قائمة سير العمل
     */
    public function getWorkflows() {
        if (!$this->user->isLogged()) {
            return ['items' => [], 'total' => 0];
        }
        
        $filters = [];
        
        if (isset($this->request->get['filters'])) {
            $filters = $this->request->get['filters'];
        }
        
        $start = isset($this->request->get['start']) ? (int)$this->request->get['start'] : 0;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;
        
        $workflows = $this->model_aym_erp->getWorkflows($filters, $start, $limit);
        
        // الحصول على العدد الإجمالي
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "unified_workflow WHERE 1=1");
        $total = $query->row['total'];
        
        return ['items' => $workflows, 'total' => $total];
    }
    
    // ################################
    // # 6. AUDIT TRAIL METHODS
    // ################################
    
    /**
     * تسجيل حدث في سجل التدقيق
     */
    public function addAuditLog($action, $reference_type, $reference_id, $before_data = null, $after_data = null) {
        if (!$this->user->isLogged()) {
            return false;
        }
        
        return $this->model_aym_erp->addAuditLog($action, $reference_type, $reference_id, $before_data, $after_data);
    }
    
    /**
     * الحصول على سجل التدقيق للعنصر
     */
    public function getAuditLog() {
        if (!$this->user->isLogged() || !$this->user->hasKey('audit/view')) {
            return ['items' => [], 'total' => 0];
        }
        
        $reference_type = isset($this->request->get['reference_type']) ? $this->request->get['reference_type'] : '';
        $reference_id = isset($this->request->get['reference_id']) ? (int)$this->request->get['reference_id'] : 0;
        
        if (!$reference_type || !$reference_id) {
            return ['items' => [], 'total' => 0];
        }
        
        $start = isset($this->request->get['start']) ? (int)$this->request->get['start'] : 0;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;
        
        $logs = $this->model_aym_erp->getAuditLog($reference_type, $reference_id, $start, $limit);
        $total = $this->model_aym_erp->getTotalAuditLog($reference_type, $reference_id);
        
        return ['items' => $logs, 'total' => $total];
    }
    
    /**
     * تسجيل تدقيق للتغييرات مع مراعاة الحقول الحساسة
     */
    public function logDataChanges($action, $reference_type, $reference_id, $before_data, $after_data) {
        if (!$this->user->isLogged()) {
            return false;
        }
        
        return $this->model_aym_erp->logDataChanges($action, $reference_type, $reference_id, $before_data, $after_data);
    }
    
    /**
     * استخراج التغييرات بين نسختين من البيانات
     */
    public function getDataChanges($before_data, $after_data) {
        return $this->model_aym_erp->getDataChanges($before_data, $after_data);
    }
    
    // ################################
    // # UTILITY METHODS
    // ################################
    
    /**
     * تنسيق التاريخ والوقت بالشكل المحلي
     */
    public function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
        return $this->model_aym_erp->formatDateTime($datetime, $format);
    }
    
    /**
     * إنشاء مسار فريد للملف
     */
    public function generateUniqueFilePath($base_dir, $filename) {
        return $this->model_aym_erp->generateUniqueFilePath($base_dir, $filename);
    }
    
    /**
     * إنشاء رمز فريد
     */
    public function generateRandomCode($length = 8, $include_letters = true, $include_numbers = true, $include_symbols = false) {
        return $this->model_aym_erp->generateRandomCode($length, $include_letters, $include_numbers, $include_symbols);
    }
    
    /**
     * الحصول على عنوان IP الحقيقي للمستخدم
     */
    public function getRealIpAddr() {
        return $this->model_aym_erp->getRealIpAddr();
    }
    
    /**
     * إنشاء معرف تتبع فريد للطلب الحالي
     */
    public function generateTraceId() {
        return $this->model_aym_erp->generateTraceId();
    }
    
    /**
     * تشفير النص باستخدام مفتاح النظام
     */
    public function encrypt($data) {
        return $this->model_aym_erp->encrypt($data);
    }
    
    /**
     * فك تشفير النص باستخدام مفتاح النظام
     */
    public function decrypt($data) {
        return $this->model_aym_erp->decrypt($data);
    }
}