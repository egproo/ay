<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 * 
 * Model: Internal Messaging System
 */
class ModelToolMessage extends Model {
    
    /**
     * Create a new conversation
     * 
     * @param array $data
     * @return int
     */
    public function createConversation($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "internal_conversation SET 
            title = '" . $this->db->escape(isset($data['title']) ? $data['title'] : '') . "', 
            type = '" . $this->db->escape(isset($data['type']) ? $data['type'] : 'private') . "', 
            creator_id = '" . (int)$data['creator_id'] . "', 
            created_at = NOW(), 
            updated_at = NOW(), 
            status = 'active'" . 
            (isset($data['associated_module']) ? ", associated_module = '" . $this->db->escape($data['associated_module']) . "'" : "") . 
            (isset($data['reference_id']) ? ", reference_id = '" . (int)$data['reference_id'] . "'" : ""));
        
        $conversation_id = $this->db->getLastId();
        
        // Add participants
        if (isset($data['participants']) && is_array($data['participants'])) {
            // Always add the creator as a participant
            $this->addParticipant($conversation_id, $data['creator_id'], 'admin');
            
            // Add other participants
            foreach ($data['participants'] as $participant) {
                if ($participant != $data['creator_id']) {
                    $this->addParticipant($conversation_id, $participant);
                }
            }
        }
        
        return $conversation_id;
    }
    
    /**
     * Add participant to conversation
     * 
     * @param int $conversation_id
     * @param int $user_id
     * @param string $role
     * @return bool
     */
    public function addParticipant($conversation_id, $user_id, $role = 'member') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "internal_participant SET 
            conversation_id = '" . (int)$conversation_id . "', 
            user_id = '" . (int)$user_id . "', 
            joined_at = NOW(), 
            role = '" . $this->db->escape($role) . "', 
            notification_settings = 'all'");
        
        return true;
    }
    
    /**
     * Send a message
     * 
     * @param array $data
     * @return int
     */
    public function sendMessage($data) {
        // Check if conversation exists
        if (!isset($data['conversation_id']) || !$this->isParticipant($data['conversation_id'], $data['sender_id'])) {
            return false;
        }
        
        $this->db->query("INSERT INTO " . DB_PREFIX . "internal_message SET 
            conversation_id = '" . (int)$data['conversation_id'] . "', 
            sender_id = '" . (int)$data['sender_id'] . "', 
            message_text = '" . $this->db->escape($data['message']) . "', 
            sent_at = NOW(), 
            message_type = '" . $this->db->escape(isset($data['message_type']) ? $data['message_type'] : 'text') . "'" . 
            (isset($data['reference_module']) ? ", reference_module = '" . $this->db->escape($data['reference_module']) . "'" : "") . 
            (isset($data['reference_id']) ? ", reference_id = '" . (int)$data['reference_id'] . "'" : "") . 
            (isset($data['parent_message_id']) ? ", parent_message_id = '" . (int)$data['parent_message_id'] . "'" : "") . 
            (isset($data['mentions']) ? ", mentions = '" . $this->db->escape(json_encode($data['mentions'])) . "'" : ""));
        
        $message_id = $this->db->getLastId();
        
        // Update conversation last update time
        $this->db->query("UPDATE " . DB_PREFIX . "internal_conversation SET 
            updated_at = NOW() 
            WHERE conversation_id = '" . (int)$data['conversation_id'] . "'");
        
        // Process attachments if any
        if (isset($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                $this->addAttachment($message_id, $attachment);
            }
        }
        
        return $message_id;
    }
    
    /**
     * Add attachment to message
     * 
     * @param int $message_id
     * @param array $attachment
     * @return bool
     */
    public function addAttachment($message_id, $attachment) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "internal_attachment SET 
            message_id = '" . (int)$message_id . "', 
            file_name = '" . $this->db->escape($attachment['file_name']) . "', 
            file_path = '" . $this->db->escape($attachment['file_path']) . "', 
            file_size = '" . (int)$attachment['file_size'] . "', 
            file_type = '" . $this->db->escape($attachment['file_type']) . "', 
            uploaded_at = NOW()" . 
            (isset($attachment['thumbnail_path']) ? ", thumbnail_path = '" . $this->db->escape($attachment['thumbnail_path']) . "'" : ""));
        
        return true;
    }
    
    /**
     * Check if user is participant in conversation
     * 
     * @param int $conversation_id
     * @param int $user_id
     * @return bool
     */
    public function isParticipant($conversation_id, $user_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_participant 
            WHERE conversation_id = '" . (int)$conversation_id . "' 
            AND user_id = '" . (int)$user_id . "' 
            AND left_at IS NULL");
        
        return $query->num_rows > 0;
    }
    
    /**
     * Get conversations for user
     * 
     * @param int $user_id
     * @param int $limit
     * @param int $start
     * @return array
     */
    public function getConversations($user_id, $limit = 10, $start = 0) {
        $query = $this->db->query("SELECT c.conversation_id, c.title, c.type, 
            c.creator_id, c.created_at, c.updated_at, c.status, 
            p.role, p.last_read_message_id, p.notification_settings, 
            (SELECT COUNT(*) FROM " . DB_PREFIX . "internal_message m 
                WHERE m.conversation_id = c.conversation_id) as message_count,
            (SELECT COUNT(*) FROM " . DB_PREFIX . "internal_message m 
                JOIN " . DB_PREFIX . "internal_participant p2 ON (m.conversation_id = p2.conversation_id) 
                WHERE m.conversation_id = c.conversation_id 
                AND p2.user_id = '" . (int)$user_id . "' 
                AND (p2.last_read_message_id IS NULL OR m.message_id > p2.last_read_message_id)) as unread_count,
            (SELECT m2.message_text FROM " . DB_PREFIX . "internal_message m2 
                WHERE m2.conversation_id = c.conversation_id 
                ORDER BY m2.sent_at DESC LIMIT 1) as last_message,
            (SELECT u.username FROM " . DB_PREFIX . "user u 
                WHERE u.user_id = (SELECT m3.sender_id FROM " . DB_PREFIX . "internal_message m3 
                    WHERE m3.conversation_id = c.conversation_id 
                    ORDER BY m3.sent_at DESC LIMIT 1)) as last_sender,
            (SELECT m4.sent_at FROM " . DB_PREFIX . "internal_message m4 
                WHERE m4.conversation_id = c.conversation_id 
                ORDER BY m4.sent_at DESC LIMIT 1) as last_activity
            FROM " . DB_PREFIX . "internal_conversation c 
            JOIN " . DB_PREFIX . "internal_participant p ON (c.conversation_id = p.conversation_id) 
            WHERE p.user_id = '" . (int)$user_id . "' 
            AND p.left_at IS NULL 
            AND c.status = 'active' 
            ORDER BY c.updated_at DESC 
            LIMIT " . (int)$start . "," . (int)$limit);
        
        $conversations = [];
        
        foreach ($query->rows as $row) {
            $conversations[] = [
                'conversation_id' => $row['conversation_id'],
                'title' => $row['title'],
                'type' => $row['type'],
                'creator_id' => $row['creator_id'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'status' => $row['status'],
                'role' => $row['role'],
                'notification_settings' => $row['notification_settings'],
                'message_count' => $row['message_count'],
                'unread_count' => $row['unread_count'],
                'last_message' => $row['last_message'],
                'last_sender' => $row['last_sender'],
                'last_activity' => $row['last_activity'],
                'time_ago' => $this->timeAgo($row['last_activity']),
                'participants' => $this->getConversationParticipants($row['conversation_id'])
            ];
        }
        
        return $conversations;
    }
    
    /**
     * Get conversation participants
     * 
     * @param int $conversation_id
     * @return array
     */
    public function getConversationParticipants($conversation_id) {
        $query = $this->db->query("SELECT p.user_id, p.role, p.joined_at, 
            u.username, u.firstname, u.lastname, u.image 
            FROM " . DB_PREFIX . "internal_participant p 
            JOIN " . DB_PREFIX . "user u ON (p.user_id = u.user_id) 
            WHERE p.conversation_id = '" . (int)$conversation_id . "' 
            AND p.left_at IS NULL");
        
        $participants = [];
        
        foreach ($query->rows as $row) {
            $participants[] = [
                'user_id' => $row['user_id'],
                'role' => $row['role'],
                'joined_at' => $row['joined_at'],
                'username' => $row['username'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'image' => $row['image'],
                'full_name' => $row['firstname'] . ' ' . $row['lastname']
            ];
        }
        
        return $participants;
    }
    
    /**
     * Get conversation details
     * 
     * @param int $conversation_id
     * @return array
     */
    public function getConversation($conversation_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_conversation 
            WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        if ($query->num_rows) {
            $conversation = $query->row;
            $conversation['participants'] = $this->getConversationParticipants($conversation_id);
            
            return $conversation;
        }
        
        return [];
    }
    
    /**
     * Get conversation messages
     * 
     * @param int $conversation_id
     * @param int $limit
     * @param int $start
     * @return array
     */
    public function getConversationMessages($conversation_id, $limit = 20, $start = 0) {
        $query = $this->db->query("SELECT m.message_id, m.sender_id, m.message_text, 
            m.sent_at, m.edited_at, m.is_system_message, m.message_type, 
            m.reference_module, m.reference_id, m.parent_message_id, m.mentions, 
            u.username, u.firstname, u.lastname, u.image 
            FROM " . DB_PREFIX . "internal_message m 
            LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id) 
            WHERE m.conversation_id = '" . (int)$conversation_id . "' 
            ORDER BY m.sent_at DESC 
            LIMIT " . (int)$start . "," . (int)$limit);
        
        $messages = [];
        
        foreach ($query->rows as $row) {
            $messages[] = [
                'message_id' => $row['message_id'],
                'sender_id' => $row['sender_id'],
                'message_text' => $row['message_text'],
                'sent_at' => $row['sent_at'],
                'edited_at' => $row['edited_at'],
                'is_system_message' => $row['is_system_message'],
                'message_type' => $row['message_type'],
                'reference_module' => $row['reference_module'],
                'reference_id' => $row['reference_id'],
                'parent_message_id' => $row['parent_message_id'],
                'mentions' => $row['mentions'] ? json_decode($row['mentions'], true) : [],
                'username' => $row['username'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'image' => $row['image'],
                'time_ago' => $this->timeAgo($row['sent_at']),
                'attachments' => $this->getMessageAttachments($row['message_id'])
            ];
        }
        
        return array_reverse($messages); // Return in chronological order
    }
    
    /**
     * Get message attachments
     * 
     * @param int $message_id
     * @return array
     */
    public function getMessageAttachments($message_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_attachment 
            WHERE message_id = '" . (int)$message_id . "'");
        
        return $query->rows;
    }
    
    /**
     * Mark conversation as read
     * 
     * @param int $conversation_id
     * @param int $user_id
     * @return bool
     */
    public function markConversationAsRead($conversation_id, $user_id) {
        // Get the latest message ID in the conversation
        $query = $this->db->query("SELECT MAX(message_id) as last_message_id 
            FROM " . DB_PREFIX . "internal_message 
            WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        if ($query->num_rows && $query->row['last_message_id']) {
            $this->db->query("UPDATE " . DB_PREFIX . "internal_participant SET 
                last_read_message_id = '" . (int)$query->row['last_message_id'] . "' 
                WHERE conversation_id = '" . (int)$conversation_id . "' 
                AND user_id = '" . (int)$user_id . "'");
        }
        
        return true;
    }
    
    /**
     * Get unread message count for a user
     * 
     * @param int $user_id
     * @return int
     */
    public function getUnreadMessageCount($user_id) {
        $query = $this->db->query("SELECT COUNT(*) as total FROM (
            SELECT c.conversation_id, 
            (SELECT COUNT(*) FROM " . DB_PREFIX . "internal_message m 
                JOIN " . DB_PREFIX . "internal_participant p2 ON (m.conversation_id = p2.conversation_id) 
                WHERE m.conversation_id = c.conversation_id 
                AND p2.user_id = '" . (int)$user_id . "' 
                AND (p2.last_read_message_id IS NULL OR m.message_id > p2.last_read_message_id)) as unread_count
            FROM " . DB_PREFIX . "internal_conversation c 
            JOIN " . DB_PREFIX . "internal_participant p ON (c.conversation_id = p.conversation_id) 
            WHERE p.user_id = '" . (int)$user_id . "' 
            AND p.left_at IS NULL 
            AND c.status = 'active' 
            HAVING unread_count > 0
        ) as unread");
        
        return $query->row['total'];
    }
    
    /**
     * Get recent messages for a user
     * 
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function getMessages($user_id, $limit = 10) {
        $recent_conversations = $this->getConversations($user_id, 5);
        $messages = [];
        
        foreach ($recent_conversations as $conversation) {
            if ($conversation['unread_count'] > 0) {
                // Get the most recent unread message
                $query = $this->db->query("SELECT m.message_id, m.sender_id, m.message_text, 
                    m.sent_at, m.message_type, c.conversation_id, c.title, 
                    u.username, u.firstname, u.lastname, u.image 
                    FROM " . DB_PREFIX . "internal_message m 
                    JOIN " . DB_PREFIX . "internal_conversation c ON (m.conversation_id = c.conversation_id) 
                    JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id) 
                    JOIN " . DB_PREFIX . "internal_participant p ON (m.conversation_id = p.conversation_id) 
                    WHERE m.conversation_id = '" . (int)$conversation['conversation_id'] . "' 
                    AND p.user_id = '" . (int)$user_id . "' 
                    AND (p.last_read_message_id IS NULL OR m.message_id > p.last_read_message_id) 
                    ORDER BY m.sent_at DESC 
                    LIMIT 1");
                
                if ($query->num_rows) {
                    $row = $query->row;
                    $messages[] = [
                        'message_id' => $row['message_id'],
                        'conversation_id' => $row['conversation_id'],
                        'conversation_title' => $row['title'],
                        'sender_id' => $row['sender_id'],
                        'sender_name' => $row['firstname'] . ' ' . $row['lastname'],
                        'sender_image' => $row['image'],
                        'message' => $this->truncateMessage($row['message_text']),
                        'is_read' => false,
                        'sent_at' => $row['sent_at'],
                        'time_ago' => $this->timeAgo($row['sent_at'])
                    ];
                }
            }
        }
        
        return $messages;
    }
    
    /**
     * Mark a message as read
     * 
     * @param int $message_id
     * @param int $user_id
     * @return bool
     */
    public function markAsRead($message_id, $user_id) {
        // Get the conversation ID for this message
        $query = $this->db->query("SELECT conversation_id FROM " . DB_PREFIX . "internal_message 
            WHERE message_id = '" . (int)$message_id . "'");
        
        if ($query->num_rows) {
            $conversation_id = $query->row['conversation_id'];
            
            // Update the participant's last_read_message_id
            $this->db->query("UPDATE " . DB_PREFIX . "internal_participant SET 
                last_read_message_id = '" . (int)$message_id . "' 
                WHERE conversation_id = '" . (int)$conversation_id . "' 
                AND user_id = '" . (int)$user_id . "' 
                AND (last_read_message_id IS NULL OR last_read_message_id < '" . (int)$message_id . "')");
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Leave a conversation
     * 
     * @param int $conversation_id
     * @param int $user_id
     * @return bool
     */
    public function leaveConversation($conversation_id, $user_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "internal_participant SET 
            left_at = NOW() 
            WHERE conversation_id = '" . (int)$conversation_id . "' 
            AND user_id = '" . (int)$user_id . "' 
            AND left_at IS NULL");
        
        return $this->db->countAffected() > 0;
    }
    
    /**
     * Update conversation settings
     * 
     * @param int $conversation_id
     * @param int $user_id
     * @param array $settings
     * @return bool
     */
    public function updateConversationSettings($conversation_id, $user_id, $settings) {
        if (isset($settings['notification_settings'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "internal_participant SET 
                notification_settings = '" . $this->db->escape($settings['notification_settings']) . "' 
                WHERE conversation_id = '" . (int)$conversation_id . "' 
                AND user_id = '" . (int)$user_id . "'");
        }
        
        return true;
    }
    
    /**
     * Archive a conversation
     * 
     * @param int $conversation_id
     * @return bool
     */
    public function archiveConversation($conversation_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "internal_conversation SET 
            status = 'archived' 
            WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        return $this->db->countAffected() > 0;
    }
    
    /**
     * Helper function to truncate message text
     * 
     * @param string $message
     * @param int $length
     * @return string
     */
    private function truncateMessage($message, $length = 50) {
        if (strlen($message) > $length) {
            return substr($message, 0, $length) . '...';
        }
        
        return $message;
    }
    
    /**
     * Helper function to get time ago string
     * 
     * @param string $datetime
     * @return string
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        $units = [
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];
        
        foreach ($units as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text . ($numberOfUnits > 1 ? 's' : '') . ' ago';
        }
        
        return 'just now';
    }
} 