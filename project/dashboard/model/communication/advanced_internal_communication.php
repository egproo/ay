<?php
/**
 * نموذج التواصل الداخلي المتقدم
 * 
 * يوفر نظام تواصل داخلي شامل مع:
 * - دردشة مباشرة في الوقت الفعلي
 * - مجموعات وقنوات متخصصة
 * - مشاركة الملفات والمستندات
 * - إشعارات ذكية
 * - تكامل مع سير العمل
 * - أرشفة وبحث متقدم
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelCommunicationAdvancedInternalCommunication extends Model {
    
    /**
     * إرسال رسالة جديدة
     */
    public function sendMessage($data) {
        // التحقق من صحة البيانات
        if (!$this->validateMessageData($data)) {
            throw new Exception('بيانات الرسالة غير صحيحة');
        }
        
        // إنشاء الرسالة
        $this->db->query("
            INSERT INTO cod_internal_message SET 
            conversation_id = '" . (int)$data['conversation_id'] . "',
            sender_id = '" . (int)$this->user->getId() . "',
            message_type = '" . $this->db->escape($data['message_type']) . "',
            content = '" . $this->db->escape($data['content']) . "',
            formatted_content = '" . $this->db->escape($this->formatMessage($data['content'])) . "',
            priority = '" . $this->db->escape($data['priority'] ?? 'normal') . "',
            is_system_message = '" . (int)($data['is_system_message'] ?? 0) . "',
            reply_to_message_id = '" . (int)($data['reply_to_message_id'] ?? 0) . "',
            metadata = '" . $this->db->escape(json_encode($data['metadata'] ?? [])) . "',
            created_at = NOW()
        ");
        
        $message_id = $this->db->getLastId();
        
        // معالجة المرفقات
        if (!empty($data['attachments'])) {
            $this->processMessageAttachments($message_id, $data['attachments']);
        }
        
        // معالجة الإشارات (@mentions)
        $mentions = $this->extractMentions($data['content']);
        if (!empty($mentions)) {
            $this->processMentions($message_id, $mentions);
        }
        
        // تحديث آخر نشاط في المحادثة
        $this->updateConversationActivity($data['conversation_id']);
        
        // إرسال إشعارات للمشاركين
        $this->sendMessageNotifications($message_id, $data['conversation_id']);
        
        // تسجيل النشاط
        $this->logCommunicationActivity($data['conversation_id'], 'message_sent', 'تم إرسال رسالة جديدة');
        
        return $message_id;
    }
    
    /**
     * إنشاء محادثة جديدة
     */
    public function createConversation($data) {
        $this->db->query("
            INSERT INTO cod_internal_conversation SET 
            title = '" . $this->db->escape($data['title']) . "',
            description = '" . $this->db->escape($data['description'] ?? '') . "',
            conversation_type = '" . $this->db->escape($data['conversation_type']) . "',
            is_group = '" . (int)($data['is_group'] ?? 0) . "',
            is_channel = '" . (int)($data['is_channel'] ?? 0) . "',
            is_private = '" . (int)($data['is_private'] ?? 0) . "',
            department_id = '" . (int)($data['department_id'] ?? 0) . "',
            project_id = '" . (int)($data['project_id'] ?? 0) . "',
            workflow_id = '" . (int)($data['workflow_id'] ?? 0) . "',
            creator_id = '" . (int)$this->user->getId() . "',
            settings = '" . $this->db->escape(json_encode($data['settings'] ?? [])) . "',
            created_at = NOW()
        ");
        
        $conversation_id = $this->db->getLastId();
        
        // إضافة المشاركين
        if (!empty($data['participants'])) {
            $this->addConversationParticipants($conversation_id, $data['participants']);
        }
        
        // إضافة المنشئ كمشارك ومدير
        $this->addConversationParticipant($conversation_id, $this->user->getId(), 'admin');
        
        // إرسال رسالة ترحيب
        if ($data['conversation_type'] == 'group' || $data['conversation_type'] == 'channel') {
            $this->sendWelcomeMessage($conversation_id, $data);
        }
        
        return $conversation_id;
    }
    
    /**
     * إضافة مشاركين للمحادثة
     */
    public function addConversationParticipants($conversation_id, $participants) {
        foreach ($participants as $participant) {
            $user_id = is_array($participant) ? $participant['user_id'] : $participant;
            $role = is_array($participant) ? ($participant['role'] ?? 'member') : 'member';
            
            $this->addConversationParticipant($conversation_id, $user_id, $role);
        }
    }
    
    /**
     * إضافة مشارك واحد للمحادثة
     */
    private function addConversationParticipant($conversation_id, $user_id, $role = 'member') {
        // التحقق من عدم وجود المشارك مسبقاً
        $existing_query = $this->db->query("
            SELECT participant_id FROM cod_conversation_participant 
            WHERE conversation_id = '" . (int)$conversation_id . "' 
            AND user_id = '" . (int)$user_id . "'
        ");
        
        if ($existing_query->num_rows == 0) {
            $this->db->query("
                INSERT INTO cod_conversation_participant SET 
                conversation_id = '" . (int)$conversation_id . "',
                user_id = '" . (int)$user_id . "',
                role = '" . $this->db->escape($role) . "',
                joined_at = NOW(),
                added_by = '" . (int)$this->user->getId() . "'
            ");
            
            // إرسال إشعار للمشارك الجديد
            $this->sendParticipantAddedNotification($conversation_id, $user_id);
        }
    }
    
    /**
     * الحصول على المحادثات
     */
    public function getConversations($filter_data = []) {
        $user_id = $this->user->getId();
        
        $sql = "SELECT ic.*, 
                COUNT(DISTINCT cp.user_id) as participant_count,
                COUNT(DISTINCT im.message_id) as message_count,
                MAX(im.created_at) as last_message_time,
                (SELECT content FROM cod_internal_message 
                 WHERE conversation_id = ic.conversation_id 
                 ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT COUNT(*) FROM cod_internal_message 
                 WHERE conversation_id = ic.conversation_id 
                 AND created_at > COALESCE(cp_user.last_read_at, '1970-01-01')
                 AND sender_id != '" . (int)$user_id . "') as unread_count
                FROM cod_internal_conversation ic
                LEFT JOIN cod_conversation_participant cp ON (ic.conversation_id = cp.conversation_id)
                LEFT JOIN cod_conversation_participant cp_user ON (ic.conversation_id = cp_user.conversation_id AND cp_user.user_id = '" . (int)$user_id . "')
                LEFT JOIN cod_internal_message im ON (ic.conversation_id = im.conversation_id)
                WHERE cp_user.user_id = '" . (int)$user_id . "'
                AND cp_user.status = 'active'";
        
        if (!empty($filter_data['filter_type'])) {
            $sql .= " AND ic.conversation_type = '" . $this->db->escape($filter_data['filter_type']) . "'";
        }
        
        if (!empty($filter_data['filter_search'])) {
            $sql .= " AND (ic.title LIKE '%" . $this->db->escape($filter_data['filter_search']) . "%' 
                      OR ic.description LIKE '%" . $this->db->escape($filter_data['filter_search']) . "%')";
        }
        
        $sql .= " GROUP BY ic.conversation_id";
        $sql .= " ORDER BY last_message_time DESC";
        
        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
            
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على رسائل المحادثة
     */
    public function getConversationMessages($conversation_id, $filter_data = []) {
        // التحقق من صلاحية الوصول للمحادثة
        if (!$this->hasConversationAccess($conversation_id)) {
            throw new Exception('ليس لديك صلاحية للوصول لهذه المحادثة');
        }
        
        $sql = "SELECT im.*, 
                CONCAT(u.firstname, ' ', u.lastname) as sender_name,
                u.image as sender_avatar,
                (SELECT COUNT(*) FROM cod_message_attachment 
                 WHERE message_id = im.message_id) as attachment_count,
                (SELECT COUNT(*) FROM cod_message_reaction 
                 WHERE message_id = im.message_id) as reaction_count
                FROM cod_internal_message im
                LEFT JOIN cod_user u ON (im.sender_id = u.user_id)
                WHERE im.conversation_id = '" . (int)$conversation_id . "'";
        
        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND DATE(im.created_at) >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }
        
        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND DATE(im.created_at) <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }
        
        if (!empty($filter_data['filter_search'])) {
            $sql .= " AND im.content LIKE '%" . $this->db->escape($filter_data['filter_search']) . "%'";
        }
        
        $sql .= " ORDER BY im.created_at DESC";
        
        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
            
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 50;
            }
            
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        $messages = [];
        foreach ($query->rows as $message) {
            // الحصول على المرفقات
            $message['attachments'] = $this->getMessageAttachments($message['message_id']);
            
            // الحصول على التفاعلات
            $message['reactions'] = $this->getMessageReactions($message['message_id']);
            
            $messages[] = $message;
        }
        
        // تحديث وقت آخر قراءة
        $this->updateLastReadTime($conversation_id);
        
        return array_reverse($messages); // ترتيب تصاعدي للعرض
    }
    
    /**
     * البحث في الرسائل
     */
    public function searchMessages($search_term, $filter_data = []) {
        $user_id = $this->user->getId();
        
        $sql = "SELECT im.*, ic.title as conversation_title,
                CONCAT(u.firstname, ' ', u.lastname) as sender_name,
                u.image as sender_avatar
                FROM cod_internal_message im
                LEFT JOIN cod_internal_conversation ic ON (im.conversation_id = ic.conversation_id)
                LEFT JOIN cod_conversation_participant cp ON (ic.conversation_id = cp.conversation_id AND cp.user_id = '" . (int)$user_id . "')
                LEFT JOIN cod_user u ON (im.sender_id = u.user_id)
                WHERE cp.user_id = '" . (int)$user_id . "'
                AND cp.status = 'active'
                AND (im.content LIKE '%" . $this->db->escape($search_term) . "%'
                     OR ic.title LIKE '%" . $this->db->escape($search_term) . "%')";
        
        if (!empty($filter_data['conversation_id'])) {
            $sql .= " AND im.conversation_id = '" . (int)$filter_data['conversation_id'] . "'";
        }
        
        if (!empty($filter_data['sender_id'])) {
            $sql .= " AND im.sender_id = '" . (int)$filter_data['sender_id'] . "'";
        }
        
        if (!empty($filter_data['date_start'])) {
            $sql .= " AND DATE(im.created_at) >= '" . $this->db->escape($filter_data['date_start']) . "'";
        }
        
        if (!empty($filter_data['date_end'])) {
            $sql .= " AND DATE(im.created_at) <= '" . $this->db->escape($filter_data['date_end']) . "'";
        }
        
        $sql .= " ORDER BY im.created_at DESC";
        $sql .= " LIMIT 100"; // حد أقصى للنتائج
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * إضافة تفاعل على رسالة
     */
    public function addMessageReaction($message_id, $reaction_type) {
        $user_id = $this->user->getId();
        
        // التحقق من وجود تفاعل سابق
        $existing_query = $this->db->query("
            SELECT reaction_id FROM cod_message_reaction 
            WHERE message_id = '" . (int)$message_id . "' 
            AND user_id = '" . (int)$user_id . "'
        ");
        
        if ($existing_query->num_rows) {
            // تحديث التفاعل الموجود
            $this->db->query("
                UPDATE cod_message_reaction SET 
                reaction_type = '" . $this->db->escape($reaction_type) . "',
                created_at = NOW()
                WHERE reaction_id = '" . (int)$existing_query->row['reaction_id'] . "'
            ");
        } else {
            // إضافة تفاعل جديد
            $this->db->query("
                INSERT INTO cod_message_reaction SET 
                message_id = '" . (int)$message_id . "',
                user_id = '" . (int)$user_id . "',
                reaction_type = '" . $this->db->escape($reaction_type) . "',
                created_at = NOW()
            ");
        }
        
        return true;
    }
    
    /**
     * تنسيق الرسالة (Markdown, mentions, etc.)
     */
    private function formatMessage($content) {
        // تحويل Markdown البسيط
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
        $content = preg_replace('/`(.*?)`/', '<code>$1</code>', $content);
        
        // تحويل الروابط
        $content = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank">$1</a>', $content);
        
        // تحويل الإشارات
        $content = preg_replace('/@(\w+)/', '<span class="mention">@$1</span>', $content);
        
        return $content;
    }
    
    /**
     * استخراج الإشارات من النص
     */
    private function extractMentions($content) {
        preg_match_all('/@(\w+)/', $content, $matches);
        return $matches[1] ?? [];
    }
    
    /**
     * معالجة الإشارات
     */
    private function processMentions($message_id, $mentions) {
        foreach ($mentions as $username) {
            // البحث عن المستخدم
            $user_query = $this->db->query("
                SELECT user_id FROM cod_user 
                WHERE username = '" . $this->db->escape($username) . "'
            ");
            
            if ($user_query->num_rows) {
                $mentioned_user_id = $user_query->row['user_id'];
                
                // إنشاء إشعار
                $this->db->query("
                    INSERT INTO cod_unified_notification SET 
                    user_id = '" . (int)$mentioned_user_id . "',
                    title = 'تم ذكرك في رسالة',
                    message = 'تم ذكرك في رسالة جديدة',
                    type = 'mention',
                    priority = 'medium',
                    reference_type = 'message',
                    reference_id = '" . (int)$message_id . "',
                    created_by = '" . (int)$this->user->getId() . "',
                    created_at = NOW()
                ");
            }
        }
    }
    
    /**
     * التحقق من صلاحية الوصول للمحادثة
     */
    private function hasConversationAccess($conversation_id) {
        $query = $this->db->query("
            SELECT participant_id FROM cod_conversation_participant 
            WHERE conversation_id = '" . (int)$conversation_id . "' 
            AND user_id = '" . (int)$this->user->getId() . "'
            AND status = 'active'
        ");
        
        return $query->num_rows > 0;
    }
    
    /**
     * تحديث وقت آخر قراءة
     */
    private function updateLastReadTime($conversation_id) {
        $this->db->query("
            UPDATE cod_conversation_participant SET 
            last_read_at = NOW()
            WHERE conversation_id = '" . (int)$conversation_id . "' 
            AND user_id = '" . (int)$this->user->getId() . "'
        ");
    }
    
    /**
     * التحقق من صحة بيانات الرسالة
     */
    private function validateMessageData($data) {
        if (empty($data['conversation_id']) || empty($data['content'])) {
            return false;
        }
        
        if (!$this->hasConversationAccess($data['conversation_id'])) {
            return false;
        }
        
        return true;
    }
    
    // دوال مساعدة أخرى...
    private function processMessageAttachments($message_id, $attachments) { /* تطوير لاحق */ }
    private function updateConversationActivity($conversation_id) { /* تطوير لاحق */ }
    private function sendMessageNotifications($message_id, $conversation_id) { /* تطوير لاحق */ }
    private function logCommunicationActivity($conversation_id, $action, $description) { /* تطوير لاحق */ }
    private function sendWelcomeMessage($conversation_id, $data) { /* تطوير لاحق */ }
    private function sendParticipantAddedNotification($conversation_id, $user_id) { /* تطوير لاحق */ }
    private function getMessageAttachments($message_id) { return []; }
    private function getMessageReactions($message_id) { return []; }
}
