<?php
class ModelCommonMessage extends Model {
    public function getMessages($user_id, $start = 0, $limit = 10) {
        $query = $this->db->query("SELECT m.*, 
            CONCAT(u.firstname, ' ', u.lastname) as sender_name,
            u.image as sender_image,
            mr.is_read,
            mr.read_at,
            mr.starred,
            (SELECT GROUP_CONCAT(CONCAT(u2.firstname, ' ', u2.lastname))
             FROM " . DB_PREFIX . "message_recipient mr2
             LEFT JOIN " . DB_PREFIX . "user u2 ON (mr2.user_id = u2.user_id)
             WHERE mr2.message_id = m.message_id AND mr2.user_id != '" . (int)$user_id . "'
            ) as other_recipients
            FROM " . DB_PREFIX . "internal_message m
            LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "message_recipient mr ON (m.message_id = mr.message_id AND mr.user_id = '" . (int)$user_id . "')
            WHERE mr.user_id = '" . (int)$user_id . "'
            ORDER BY m.created_at DESC
            LIMIT " . (int)$start . "," . (int)$limit);
            
        return $query->rows;
    }
    
    public function getMessage($message_id, $user_id) {
        $query = $this->db->query("SELECT m.*, 
            CONCAT(u.firstname, ' ', u.lastname) as sender_name,
            u.image as sender_image,
            mr.is_read,
            mr.read_at,
            mr.starred,
            mr.notification_settings,
            m.attachments,
            m.mentions,
            (SELECT GROUP_CONCAT(CONCAT(u2.firstname, ' ', u2.lastname))
             FROM " . DB_PREFIX . "message_recipient mr2
             LEFT JOIN " . DB_PREFIX . "user u2 ON (mr2.user_id = u2.user_id)
             WHERE mr2.message_id = m.message_id AND mr2.user_id != '" . (int)$user_id . "'
            ) as other_recipients
            FROM " . DB_PREFIX . "internal_message m
            LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "message_recipient mr ON (m.message_id = mr.message_id AND mr.user_id = '" . (int)$user_id . "')
            WHERE m.message_id = '" . (int)$message_id . "'
            AND mr.user_id = '" . (int)$user_id . "'");
            
        return $query->row;
    }
    
    public function getUnreadCount($user_id) {
        $query = $this->db->query("SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "message_recipient
            WHERE user_id = '" . (int)$user_id . "'
            AND is_read = 0");
            
        return $query->row['total'];
    }
    
    public function markAsRead($message_id, $user_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "message_recipient
            SET is_read = 1,
                read_at = NOW()
            WHERE message_id = '" . (int)$message_id . "'
            AND user_id = '" . (int)$user_id . "'");
    }
    
    public function sendMessage($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "internal_message SET
            sender_id = '" . (int)$data['sender_id'] . "',
            subject = '" . $this->db->escape($data['subject']) . "',
            message = '" . $this->db->escape($data['message']) . "',
            is_private = '" . (int)$data['is_private'] . "',
            parent_id = '" . (int)$data['parent_id'] . "',
            attachments = '" . $this->db->escape(json_encode($data['attachments'])) . "',
            mentions = '" . $this->db->escape(json_encode($data['mentions'])) . "',
            created_at = NOW()");
            
        $message_id = $this->db->getLastId();
        
        // Add recipients
        if (isset($data['recipients']) && is_array($data['recipients'])) {
            foreach ($data['recipients'] as $recipient_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "message_recipient SET
                    message_id = '" . (int)$message_id . "',
                    user_id = '" . (int)$recipient_id . "',
                    notification_settings = 'all'");
            }
        }
        
        return $message_id;
    }
    
    public function replyToMessage($data) {
        $parent_message = $this->getMessage($data['parent_id'], $data['sender_id']);
        
        if ($parent_message) {
            // Get all participants from the thread
            $query = $this->db->query("SELECT DISTINCT mr.user_id
                FROM " . DB_PREFIX . "message_recipient mr
                WHERE mr.message_id = '" . (int)$data['parent_id'] . "'
                AND mr.user_id != '" . (int)$data['sender_id'] . "'");
                
            $recipients = array();
            foreach ($query->rows as $recipient) {
                $recipients[] = $recipient['user_id'];
            }
            
            $data['recipients'] = $recipients;
            return $this->sendMessage($data);
        }
        
        return false;
    }
    
    public function starMessage($message_id, $user_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "message_recipient
            SET starred = NOT starred
            WHERE message_id = '" . (int)$message_id . "'
            AND user_id = '" . (int)$user_id . "'");
    }
    
    public function updateNotificationSettings($message_id, $user_id, $settings) {
        $this->db->query("UPDATE " . DB_PREFIX . "message_recipient
            SET notification_settings = '" . $this->db->escape($settings) . "'
            WHERE message_id = '" . (int)$message_id . "'
            AND user_id = '" . (int)$user_id . "'");
    }
    
    public function deleteMessage($message_id, $user_id) {
        // Soft delete - just hide from the user
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_recipient
            WHERE message_id = '" . (int)$message_id . "'
            AND user_id = '" . (int)$user_id . "'");
            
        // Check if anyone else still has access to this message
        $query = $this->db->query("SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "message_recipient
            WHERE message_id = '" . (int)$message_id . "'");
            
        // If no one has access, delete the message and its replies
        if ($query->row['total'] == 0) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "internal_message
                WHERE message_id = '" . (int)$message_id . "'
                OR parent_id = '" . (int)$message_id . "'");
        }
    }
    
    public function getThread($message_id, $user_id) {
        // Get the root message if this is a reply
        $query = $this->db->query("SELECT parent_id
            FROM " . DB_PREFIX . "internal_message
            WHERE message_id = '" . (int)$message_id . "'");
            
        if ($query->row && $query->row['parent_id']) {
            $message_id = $query->row['parent_id'];
        }
        
        // Get all messages in the thread
        $query = $this->db->query("SELECT m.*, 
            CONCAT(u.firstname, ' ', u.lastname) as sender_name,
            u.image as sender_image,
            mr.is_read,
            mr.read_at,
            mr.starred
            FROM " . DB_PREFIX . "internal_message m
            LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "message_recipient mr ON (m.message_id = mr.message_id AND mr.user_id = '" . (int)$user_id . "')
            WHERE m.message_id = '" . (int)$message_id . "'
            OR m.parent_id = '" . (int)$message_id . "'
            ORDER BY m.created_at ASC");
            
        return $query->rows;
    }
    
    public function getParticipants($message_id) {
        $query = $this->db->query("SELECT DISTINCT u.user_id, 
            CONCAT(u.firstname, ' ', u.lastname) as name,
            u.image,
            mr.notification_settings
            FROM " . DB_PREFIX . "message_recipient mr
            LEFT JOIN " . DB_PREFIX . "user u ON (mr.user_id = u.user_id)
            WHERE mr.message_id = '" . (int)$message_id . "'");
            
        return $query->rows;
    }
    
    public function addParticipant($message_id, $user_id) {
        // Check if already a participant
        $query = $this->db->query("SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "message_recipient
            WHERE message_id = '" . (int)$message_id . "'
            AND user_id = '" . (int)$user_id . "'");
            
        if ($query->row['total'] == 0) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "message_recipient SET
                message_id = '" . (int)$message_id . "',
                user_id = '" . (int)$user_id . "',
                notification_settings = 'all'");
                
            // Add to any replies in the thread
            $this->db->query("INSERT INTO " . DB_PREFIX . "message_recipient (message_id, user_id, notification_settings)
                SELECT message_id, '" . (int)$user_id . "', 'all'
                FROM " . DB_PREFIX . "internal_message
                WHERE parent_id = '" . (int)$message_id . "'");
        }
    }
}