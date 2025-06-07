<?php
class ModelToolMessaging extends Model {
    public function addMessage($data) {
        // Check if conversation exists or create a new one
        $conversation_id = 0;
        
        if (isset($data['parent_id']) && $data['parent_id'] > 0) {
            // Get parent message conversation
            $parent_message = $this->getMessage($data['parent_id']);
            if ($parent_message) {
                $conversation_id = $parent_message['conversation_id'];
            }
        } elseif (isset($data['conversation_id']) && $data['conversation_id'] > 0) {
            // Use provided conversation ID
            $conversation_id = (int)$data['conversation_id'];
        } else {
            // Create a new conversation
            $conversation_id = $this->createConversation([
                'title' => $data['subject'],
                'creator_id' => $this->user->getId(),
                'is_group' => isset($data['is_group']) ? (int)$data['is_group'] : 0
            ]);
            
            // Add sender as conversation member
            $this->addConversationMember([
                'conversation_id' => $conversation_id,
                'user_id' => $this->user->getId(),
                'is_admin' => 1
            ]);
            
            // Add recipient as conversation member
            if (isset($data['recipient_id']) && $data['recipient_id'] > 0) {
                $this->addConversationMember([
                    'conversation_id' => $conversation_id,
                    'user_id' => (int)$data['recipient_id'],
                    'is_admin' => 0
                ]);
            }
        }
        
        // Prepare sender data
        $sender_id = $this->user->getId();
        $sender_name = $this->user->getUserName();
        $sender_email = '';
        
        $user_info = $this->db->query("SELECT email FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$sender_id . "'")->row;
        if ($user_info) {
            $sender_email = $user_info['email'];
        }
        
        // Prepare recipient data
        $recipient_id = isset($data['recipient_id']) ? (int)$data['recipient_id'] : 0;
        $recipient_name = isset($data['recipient']) ? $this->db->escape($data['recipient']) : '';
        $recipient_email = '';
        
        if ($recipient_id > 0) {
            $recipient_info = $this->db->query("SELECT username, email FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$recipient_id . "'")->row;
            if ($recipient_info) {
                $recipient_name = $recipient_info['username'];
                $recipient_email = $recipient_info['email'];
            }
        }
        
        // Check if has attachments
        $has_attachment = (isset($data['attachment']) && is_array($data['attachment']) && count($data['attachment']) > 0) ? 1 : 0;
        
        // Set message status (0=draft, 1=sent)
        $status = isset($data['status']) ? (int)$data['status'] : 1;
        
        // Set is_draft flag
        $is_draft = ($status == 0) ? 1 : 0;
        
        // Insert message
        $this->db->query("INSERT INTO " . DB_PREFIX . "message SET 
            parent_id = '" . (isset($data['parent_id']) ? (int)$data['parent_id'] : 0) . "', 
            conversation_id = '" . (int)$conversation_id . "', 
            subject = '" . $this->db->escape($data['subject']) . "', 
            sender_id = '" . (int)$sender_id . "', 
            sender = '" . $this->db->escape($sender_name) . "', 
            sender_email = '" . $this->db->escape($sender_email) . "', 
            recipient_id = '" . (int)$recipient_id . "', 
            recipient = '" . $this->db->escape($recipient_name) . "', 
            recipient_email = '" . $this->db->escape($recipient_email) . "', 
            message = '" . $this->db->escape(isset($data['message']) ? $data['message'] : '') . "', 
            status = '" . (int)$status . "',
            priority = '" . (isset($data['priority']) ? (int)$data['priority'] : 0) . "',
            is_draft = '" . (int)$is_draft . "',
            has_attachment = '" . (int)$has_attachment . "',
            date_added = NOW(),
            date_modified = NOW()");
        
        $message_id = $this->db->getLastId();
        
        // Add to message history
        $this->db->query("INSERT INTO " . DB_PREFIX . "message_history SET 
            message_id = '" . (int)$message_id . "', 
            conversation_id = '" . (int)$conversation_id . "', 
            sender_id = '" . (int)$sender_id . "', 
            sender = '" . $this->db->escape($sender_name) . "', 
            sender_email = '" . $this->db->escape($sender_email) . "', 
            recipient_id = '" . (int)$recipient_id . "', 
            recipient = '" . $this->db->escape($recipient_name) . "', 
            recipient_email = '" . $this->db->escape($recipient_email) . "', 
            message = '" . $this->db->escape(isset($data['message']) ? $data['message'] : '') . "', 
            has_attachment = '" . (int)$has_attachment . "',
            date_added = NOW()");
        
        $message_history_id = $this->db->getLastId();
        
        // Process attachments if any
        if (isset($data['attachment']) && is_array($data['attachment'])) {
            foreach ($data['attachment'] as $attachment) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "message_attachment SET 
                    message_id = '" . (int)$message_id . "', 
                    message_history_id = '" . (int)$message_history_id . "', 
                    filename = '" . $this->db->escape($attachment['filename']) . "', 
                    mask = '" . $this->db->escape(isset($attachment['mask']) ? $attachment['mask'] : $attachment['filename']) . "', 
                    filepath = '" . $this->db->escape($attachment['filepath']) . "', 
                    filesize = '" . (isset($attachment['filesize']) ? (int)$attachment['filesize'] : 0) . "', 
                    filetype = '" . $this->db->escape(isset($attachment['filetype']) ? $attachment['filetype'] : '') . "', 
                    date_added = NOW()");
            }
        }
        
        // Send notifications if message is not a draft
        if ($status == 1) {
            $this->addNotification([
                'user_id' => $recipient_id,
                'message_id' => $message_id,
                'conversation_id' => $conversation_id,
                'type' => 'new_message',
                'title' => $this->db->escape($data['subject']),
                'message' => $this->db->escape(substr(strip_tags(isset($data['message']) ? $data['message'] : ''), 0, 100) . '...')
            ]);
        }
        
        // Delete any drafts related to this conversation if it's a new message
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_draft WHERE 
            user_id = '" . (int)$this->user->getId() . "' AND 
            message_id = '0'");
        
        return $message_id;
    }
    
    public function addReply($message_id, $data) {
        // Get original message info
        $original_message = $this->getMessage($message_id);
        
        if (!$original_message) {
            return false;
        }
        
        // Prepare sender data
        $sender_id = $this->user->getId();
        $sender_name = $this->user->getUserName();
        $sender_email = '';
        
        $user_info = $this->db->query("SELECT email FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$sender_id . "'")->row;
        if ($user_info) {
            $sender_email = $user_info['email'];
        }
        
        // Prepare recipient data (the original sender becomes the recipient)
        $recipient_id = $original_message['sender_id'];
        $recipient_name = $original_message['sender'];
        $recipient_email = $original_message['sender_email'];
        
        // Check if has attachments
        $has_attachment = (isset($data['attachment']) && is_array($data['attachment']) && count($data['attachment']) > 0) ? 1 : 0;
        
        // Get conversation ID
        $conversation_id = $original_message['conversation_id'];
        
        // Add to message history
        $this->db->query("INSERT INTO " . DB_PREFIX . "message_history SET 
            message_id = '" . (int)$message_id . "', 
            conversation_id = '" . (int)$conversation_id . "', 
            sender_id = '" . (int)$sender_id . "', 
            sender = '" . $this->db->escape($sender_name) . "', 
            sender_email = '" . $this->db->escape($sender_email) . "', 
            recipient_id = '" . (int)$recipient_id . "', 
            recipient = '" . $this->db->escape($recipient_name) . "', 
            recipient_email = '" . $this->db->escape($recipient_email) . "', 
            message = '" . $this->db->escape($data['reply']) . "', 
            has_attachment = '" . (int)$has_attachment . "',
            date_added = NOW()");
        
        $message_history_id = $this->db->getLastId();
        
        // Update the message status and date
        $this->db->query("UPDATE " . DB_PREFIX . "message SET 
            date_modified = NOW() 
            WHERE message_id = '" . (int)$message_id . "'");
        
        // Update conversation modified date
        $this->db->query("UPDATE " . DB_PREFIX . "message_conversation SET 
            date_modified = NOW() 
            WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        // Process attachments if any
        if (isset($data['attachment']) && is_array($data['attachment'])) {
            foreach ($data['attachment'] as $attachment) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "message_attachment SET 
                    message_id = '" . (int)$message_id . "', 
                    message_history_id = '" . (int)$message_history_id . "', 
                    filename = '" . $this->db->escape($attachment['filename']) . "', 
                    mask = '" . $this->db->escape(isset($attachment['mask']) ? $attachment['mask'] : $attachment['filename']) . "', 
                    filepath = '" . $this->db->escape($attachment['filepath']) . "', 
                    filesize = '" . (isset($attachment['filesize']) ? (int)$attachment['filesize'] : 0) . "', 
                    filetype = '" . $this->db->escape(isset($attachment['filetype']) ? $attachment['filetype'] : '') . "', 
                    date_added = NOW()");
            }
        }
        
        // Delete any drafts related to this conversation
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_draft WHERE 
            user_id = '" . (int)$this->user->getId() . "' AND 
            message_id = '" . (int)$message_id . "'");
        
        // Send notification to the recipient
        $this->addNotification([
            'user_id' => $recipient_id,
            'message_id' => $message_id,
            'conversation_id' => $conversation_id,
            'type' => 'reply',
            'title' => $original_message['subject'],
            'message' => substr(strip_tags($data['reply']), 0, 100) . '...'
        ]);
        
        // If this is a group conversation, notify all members except the sender
        if ($conversation_id > 0) {
            $members = $this->getConversationMembers($conversation_id);
            
            foreach ($members as $member) {
                if ($member['user_id'] != $sender_id && $member['user_id'] != $recipient_id) {
                    $this->addNotification([
                        'user_id' => $member['user_id'],
                        'message_id' => $message_id,
                        'conversation_id' => $conversation_id,
                        'type' => 'group_message',
                        'title' => $original_message['subject'],
                        'message' => substr(strip_tags($data['reply']), 0, 100) . '...'
                    ]);
                }
            }
        }
        
        return true;
    }
    
    public function saveDraft($message_id, $data) {
        // Check if a draft already exists
        $draft = $this->getDraft($message_id);
        
        if ($draft) {
            // Update existing draft
            $this->db->query("UPDATE " . DB_PREFIX . "message_draft SET 
                draft = '" . $this->db->escape($data['reply']) . "', 
                date_modified = NOW() 
                WHERE user_id = '" . (int)$this->user->getId() . "' AND 
                message_id = '" . (int)$message_id . "'");
        } else {
            // Create new draft
            $this->db->query("INSERT INTO " . DB_PREFIX . "message_draft SET 
                message_id = '" . (int)$message_id . "', 
                user_id = '" . (int)$this->user->getId() . "', 
                draft = '" . $this->db->escape($data['reply']) . "', 
                date_added = NOW(), 
                date_modified = NOW()");
        }
        
        return true;
    }
    
    public function getMessage($message_id) {
        $query = $this->db->query("SELECT m.*, 
                c.title as conversation_title, c.is_group,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "message_attachment ma WHERE ma.message_id = m.message_id) AS total_attachments 
            FROM " . DB_PREFIX . "message m 
            LEFT JOIN " . DB_PREFIX . "message_conversation c ON (m.conversation_id = c.conversation_id)
            WHERE m.message_id = '" . (int)$message_id . "'");
        
        if ($query->num_rows) {
            $message_data = $query->row;
            
            // Get attachments
            $attachment_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_attachment 
                WHERE message_id = '" . (int)$message_id . "' AND message_history_id = '0'
                ORDER BY date_added");
            
            $attachments = array();
            
            foreach ($attachment_query->rows as $attachment) {
                $attachments[] = array(
                    'attachment_id' => $attachment['attachment_id'],
                    'filename' => $attachment['filename'],
                    'mask' => $attachment['mask'],
                    'filepath' => $attachment['filepath'],
                    'filesize' => $attachment['filesize'],
                    'filetype' => $attachment['filetype'],
                    'date_added' => $attachment['date_added'],
                    'href' => 'index.php?route=tool/messaging/download&user_token=' . $this->session->data['user_token'] . '&attachment_id=' . $attachment['attachment_id']
                );
            }
            
            $message_data['attachments'] = $attachments;
            
            // Get message history with attachments
            $history_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_history 
                WHERE message_id = '" . (int)$message_id . "' 
                ORDER BY date_added DESC");
            
            $history = array();
            
            foreach ($history_query->rows as $history_item) {
                $history_attachments = array();
                
                // Get attachments for this history item
                $history_attachment_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_attachment 
                    WHERE message_history_id = '" . (int)$history_item['message_history_id'] . "'
                    ORDER BY date_added");
                
                foreach ($history_attachment_query->rows as $attachment) {
                    $history_attachments[] = array(
                        'attachment_id' => $attachment['attachment_id'],
                        'filename' => $attachment['filename'],
                        'mask' => $attachment['mask'],
                        'filepath' => $attachment['filepath'],
                        'filesize' => $attachment['filesize'],
                        'filetype' => $attachment['filetype'],
                        'date_added' => $attachment['date_added'],
                        'href' => 'index.php?route=tool/messaging/download&user_token=' . $this->session->data['user_token'] . '&attachment_id=' . $attachment['attachment_id']
                    );
                }
                
                $history[] = array(
                    'message_history_id' => $history_item['message_history_id'],
                    'sender_id' => $history_item['sender_id'],
                    'sender' => $history_item['sender'],
                    'sender_email' => $history_item['sender_email'],
                    'recipient_id' => $history_item['recipient_id'],
                    'recipient' => $history_item['recipient'],
                    'recipient_email' => $history_item['recipient_email'],
                    'message' => $history_item['message'],
                    'has_attachment' => $history_item['has_attachment'],
                    'attachments' => $history_attachments,
                    'date_added' => $history_item['date_added']
                );
            }
            
            $message_data['history'] = $history;
            
            // Get conversation members if this is part of a conversation
            if ($message_data['conversation_id'] > 0) {
                $message_data['conversation_members'] = $this->getConversationMembers($message_data['conversation_id']);
            } else {
                $message_data['conversation_members'] = array();
            }
            
            // Mark message as read if recipient is current user
            if ($message_data['recipient_id'] == $this->user->getId() && $message_data['is_read'] == 0) {
                $this->markMessageAsRead($message_id);
            }
            
            return $message_data;
        } else {
            return false;
        }
    }
    
    public function getMessageHistory($message_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_history 
            WHERE message_id = '" . (int)$message_id . "' 
            ORDER BY date_added");
        
        return $query->rows;
    }
    
    public function getDraft($message_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_draft 
            WHERE message_id = '" . (int)$message_id . "' AND 
            user_id = '" . (int)$this->user->getId() . "'");
        
        return $query->row;
    }
    
    public function deleteMessage($message_id) {
        // Delete message
        $this->db->query("DELETE FROM " . DB_PREFIX . "message WHERE message_id = '" . (int)$message_id . "'");
        
        // Delete message history
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_history WHERE message_id = '" . (int)$message_id . "'");
        
        // Delete attachments
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_attachment WHERE message_id = '" . (int)$message_id . "'");
        
        // Delete drafts
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_draft WHERE message_id = '" . (int)$message_id . "'");
        
        return true;
    }
    
    public function getMessages($data = array()) {
        $sql = "SELECT m.*, c.title as conversation_title, c.is_group, 
                (SELECT COUNT(*) FROM " . DB_PREFIX . "message_attachment ma WHERE ma.message_id = m.message_id) AS total_attachments 
                FROM " . DB_PREFIX . "message m 
                LEFT JOIN " . DB_PREFIX . "message_conversation c ON (m.conversation_id = c.conversation_id)";
        
        $where = array();
        
        // Filter by folder
        if (isset($data['filter_folder'])) {
            switch ($data['filter_folder']) {
                case 'inbox':
                    $where[] = "m.recipient_id = '" . (int)$this->user->getId() . "' AND m.is_draft = '0' AND m.is_archived = '0' AND m.is_deleted = '0'";
                    break;
                case 'sent':
                    $where[] = "m.sender_id = '" . (int)$this->user->getId() . "' AND m.is_draft = '0' AND m.is_archived = '0' AND m.is_deleted = '0'";
                    break;
                case 'draft':
                    $where[] = "m.sender_id = '" . (int)$this->user->getId() . "' AND m.is_draft = '1' AND m.is_deleted = '0'";
                    break;
                case 'archived':
                    $where[] = "(m.sender_id = '" . (int)$this->user->getId() . "' OR m.recipient_id = '" . (int)$this->user->getId() . "') AND m.is_archived = '1' AND m.is_deleted = '0'";
                    break;
                case 'all':
                    $where[] = "(m.sender_id = '" . (int)$this->user->getId() . "' OR m.recipient_id = '" . (int)$this->user->getId() . "') AND m.is_deleted = '0'";
                    break;
            }
        } else {
            // Default to inbox
            $where[] = "m.recipient_id = '" . (int)$this->user->getId() . "' AND m.is_draft = '0' AND m.is_archived = '0' AND m.is_deleted = '0'";
        }
        
        // Filter by conversation ID
        if (isset($data['filter_conversation_id'])) {
            $where[] = "m.conversation_id = '" . (int)$data['filter_conversation_id'] . "'";
        }
        
        // Filter by read status
        if (isset($data['filter_read'])) {
            $where[] = "m.is_read = '" . (int)$data['filter_read'] . "'";
        }
        
        // Filter by priority
        if (isset($data['filter_priority'])) {
            $where[] = "m.priority = '" . (int)$data['filter_priority'] . "'";
        }
        
        // Filter by sender
        if (isset($data['filter_sender'])) {
            $where[] = "m.sender LIKE '%" . $this->db->escape($data['filter_sender']) . "%'";
        }
        
        // Filter by recipient
        if (isset($data['filter_recipient'])) {
            $where[] = "m.recipient LIKE '%" . $this->db->escape($data['filter_recipient']) . "%'";
        }
        
        // Filter by subject
        if (isset($data['filter_subject'])) {
            $where[] = "m.subject LIKE '%" . $this->db->escape($data['filter_subject']) . "%'";
        }
        
        // Filter by message content
        if (isset($data['filter_message'])) {
            $where[] = "m.message LIKE '%" . $this->db->escape($data['filter_message']) . "%'";
        }
        
        // Filter by date range
        if (isset($data['filter_date_start']) && isset($data['filter_date_end'])) {
            $where[] = "DATE(m.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        } elseif (isset($data['filter_date_start'])) {
            $where[] = "DATE(m.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        } elseif (isset($data['filter_date_end'])) {
            $where[] = "DATE(m.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        
        // Filter by attachment
        if (isset($data['filter_has_attachment'])) {
            $where[] = "m.has_attachment = '" . (int)$data['filter_has_attachment'] . "'";
        }
        
        // Add where conditions to the query
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        // Group by message ID
        $sql .= " GROUP BY m.message_id";
        
        // Sorting
        $sort_data = array(
            'subject',
            'sender',
            'recipient',
            'date_added',
            'date_modified',
            'is_read',
            'priority'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY date_added";
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
        
        $messages = array();
        
        foreach ($query->rows as $row) {
            $messages[] = array(
                'message_id' => $row['message_id'],
                'parent_id' => $row['parent_id'],
                'conversation_id' => $row['conversation_id'],
                'conversation_title' => $row['conversation_title'],
                'is_group' => $row['is_group'],
                'subject' => $row['subject'],
                'sender_id' => $row['sender_id'],
                'sender' => $row['sender'],
                'sender_email' => $row['sender_email'],
                'recipient_id' => $row['recipient_id'],
                'recipient' => $row['recipient'],
                'recipient_email' => $row['recipient_email'],
                'message' => $row['message'],
                'status' => $row['status'],
                'priority' => $row['priority'],
                'is_read' => $row['is_read'],
                'is_draft' => $row['is_draft'],
                'is_archived' => $row['is_archived'],
                'is_deleted' => $row['is_deleted'],
                'has_attachment' => $row['has_attachment'],
                'total_attachments' => $row['total_attachments'],
                'date_added' => $row['date_added'],
                'date_modified' => $row['date_modified']
            );
        }
        
        return $messages;
    }
    
    public function getTotalMessages($data = array()) {
        $sql = "SELECT COUNT(DISTINCT m.message_id) AS total FROM " . DB_PREFIX . "message m 
                LEFT JOIN " . DB_PREFIX . "message_conversation c ON (m.conversation_id = c.conversation_id)";
        
        $where = array();
        
        // Filter by folder
        if (isset($data['filter_folder'])) {
            switch ($data['filter_folder']) {
                case 'inbox':
                    $where[] = "m.recipient_id = '" . (int)$this->user->getId() . "' AND m.is_draft = '0' AND m.is_archived = '0' AND m.is_deleted = '0'";
                    break;
                case 'sent':
                    $where[] = "m.sender_id = '" . (int)$this->user->getId() . "' AND m.is_draft = '0' AND m.is_archived = '0' AND m.is_deleted = '0'";
                    break;
                case 'draft':
                    $where[] = "m.sender_id = '" . (int)$this->user->getId() . "' AND m.is_draft = '1' AND m.is_deleted = '0'";
                    break;
                case 'archived':
                    $where[] = "(m.sender_id = '" . (int)$this->user->getId() . "' OR m.recipient_id = '" . (int)$this->user->getId() . "') AND m.is_archived = '1' AND m.is_deleted = '0'";
                    break;
                case 'all':
                    $where[] = "(m.sender_id = '" . (int)$this->user->getId() . "' OR m.recipient_id = '" . (int)$this->user->getId() . "') AND m.is_deleted = '0'";
                    break;
            }
        } else {
            // Default to inbox
            $where[] = "m.recipient_id = '" . (int)$this->user->getId() . "' AND m.is_draft = '0' AND m.is_archived = '0' AND m.is_deleted = '0'";
        }
        
        // Filter by conversation ID
        if (isset($data['filter_conversation_id'])) {
            $where[] = "m.conversation_id = '" . (int)$data['filter_conversation_id'] . "'";
        }
        
        // Filter by read status
        if (isset($data['filter_read'])) {
            $where[] = "m.is_read = '" . (int)$data['filter_read'] . "'";
        }
        
        // Filter by priority
        if (isset($data['filter_priority'])) {
            $where[] = "m.priority = '" . (int)$data['filter_priority'] . "'";
        }
        
        // Filter by sender
        if (isset($data['filter_sender'])) {
            $where[] = "m.sender LIKE '%" . $this->db->escape($data['filter_sender']) . "%'";
        }
        
        // Filter by recipient
        if (isset($data['filter_recipient'])) {
            $where[] = "m.recipient LIKE '%" . $this->db->escape($data['filter_recipient']) . "%'";
        }
        
        // Filter by subject
        if (isset($data['filter_subject'])) {
            $where[] = "m.subject LIKE '%" . $this->db->escape($data['filter_subject']) . "%'";
        }
        
        // Filter by message content
        if (isset($data['filter_message'])) {
            $where[] = "m.message LIKE '%" . $this->db->escape($data['filter_message']) . "%'";
        }
        
        // Filter by date range
        if (isset($data['filter_date_start']) && isset($data['filter_date_end'])) {
            $where[] = "DATE(m.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        } elseif (isset($data['filter_date_start'])) {
            $where[] = "DATE(m.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        } elseif (isset($data['filter_date_end'])) {
            $where[] = "DATE(m.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        
        // Filter by attachment
        if (isset($data['filter_has_attachment'])) {
            $where[] = "m.has_attachment = '" . (int)$data['filter_has_attachment'] . "'";
        }
        
        // Add where conditions to the query
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    public function install() {
        // Create message table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message` (
            `message_id` int(11) NOT NULL AUTO_INCREMENT,
            `parent_id` int(11) NOT NULL DEFAULT '0',
            `conversation_id` int(11) NOT NULL DEFAULT '0',
            `subject` varchar(255) NOT NULL,
            `sender_id` int(11) NOT NULL,
            `sender` varchar(255) NOT NULL,
            `sender_email` varchar(255) NOT NULL,
            `recipient_id` int(11) NOT NULL,
            `recipient` varchar(255) NOT NULL,
            `recipient_email` varchar(255) NOT NULL,
            `message` text NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT '0',
            `priority` tinyint(1) NOT NULL DEFAULT '0',
            `is_read` tinyint(1) NOT NULL DEFAULT '0',
            `is_draft` tinyint(1) NOT NULL DEFAULT '0',
            `is_archived` tinyint(1) NOT NULL DEFAULT '0',
            `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
            `has_attachment` tinyint(1) NOT NULL DEFAULT '0',
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`message_id`),
            KEY `parent_id` (`parent_id`),
            KEY `conversation_id` (`conversation_id`),
            KEY `sender_id` (`sender_id`),
            KEY `recipient_id` (`recipient_id`),
            KEY `is_read` (`is_read`),
            KEY `is_draft` (`is_draft`),
            KEY `is_archived` (`is_archived`),
            KEY `is_deleted` (`is_deleted`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
        
        // Create message history table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_history` (
            `message_history_id` int(11) NOT NULL AUTO_INCREMENT,
            `message_id` int(11) NOT NULL,
            `conversation_id` int(11) NOT NULL DEFAULT '0',
            `sender_id` int(11) NOT NULL,
            `sender` varchar(255) NOT NULL,
            `sender_email` varchar(255) NOT NULL,
            `recipient_id` int(11) NOT NULL,
            `recipient` varchar(255) NOT NULL,
            `recipient_email` varchar(255) NOT NULL,
            `message` text NOT NULL,
            `has_attachment` tinyint(1) NOT NULL DEFAULT '0',
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`message_history_id`),
            KEY `message_id` (`message_id`),
            KEY `sender_id` (`sender_id`),
            KEY `recipient_id` (`recipient_id`),
            KEY `conversation_id` (`conversation_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
        
        // Create message attachment table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_attachment` (
            `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
            `message_id` int(11) NOT NULL,
            `message_history_id` int(11) NOT NULL DEFAULT '0',
            `filename` varchar(255) NOT NULL,
            `mask` varchar(255) NOT NULL,
            `filepath` varchar(255) NOT NULL,
            `filesize` int(11) NOT NULL DEFAULT '0',
            `filetype` varchar(32) NOT NULL,
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`attachment_id`),
            KEY `message_id` (`message_id`),
            KEY `message_history_id` (`message_history_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
        
        // Create message draft table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_draft` (
            `draft_id` int(11) NOT NULL AUTO_INCREMENT,
            `message_id` int(11) NOT NULL,
            `conversation_id` int(11) NOT NULL DEFAULT '0',
            `user_id` int(11) NOT NULL,
            `draft` text NOT NULL,
            `has_attachment` tinyint(1) NOT NULL DEFAULT '0',
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`draft_id`),
            KEY `message_id` (`message_id`),
            KEY `user_id` (`user_id`),
            KEY `conversation_id` (`conversation_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
        
        // Create conversation table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_conversation` (
            `conversation_id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `creator_id` int(11) NOT NULL,
            `is_group` tinyint(1) NOT NULL DEFAULT '0',
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`conversation_id`),
            KEY `creator_id` (`creator_id`),
            KEY `is_group` (`is_group`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
        
        // Create conversation members table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_conversation_member` (
            `conversation_member_id` int(11) NOT NULL AUTO_INCREMENT,
            `conversation_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `is_admin` tinyint(1) NOT NULL DEFAULT '0',
            `status` tinyint(1) NOT NULL DEFAULT '1',
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`conversation_member_id`),
            KEY `conversation_id` (`conversation_id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
        
        // Create notification table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_notification` (
            `notification_id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `message_id` int(11) NOT NULL DEFAULT '0',
            `conversation_id` int(11) NOT NULL DEFAULT '0',
            `type` varchar(32) NOT NULL,
            `title` varchar(255) NOT NULL,
            `message` text NOT NULL,
            `is_read` tinyint(1) NOT NULL DEFAULT '0',
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`notification_id`),
            KEY `user_id` (`user_id`),
            KEY `message_id` (`message_id`),
            KEY `conversation_id` (`conversation_id`),
            KEY `is_read` (`is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
    }
    
    public function uninstall() {
        // Drop message table
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message`");
        
        // Drop message history table
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_history`");
        
        // Drop message attachment table
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_attachment`");
        
        // Drop message draft table
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_draft`");
        
        // Drop conversation tables
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_conversation`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_conversation_member`");
        
        // Drop notification table
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_notification`");
    }
    
    public function createConversation($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "message_conversation SET 
            title = '" . $this->db->escape($data['title']) . "', 
            creator_id = '" . (int)$data['creator_id'] . "', 
            is_group = '" . (isset($data['is_group']) ? (int)$data['is_group'] : 0) . "', 
            date_added = NOW(), 
            date_modified = NOW()");
        
        return $this->db->getLastId();
    }
    
    public function getConversation($conversation_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_conversation WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        return $query->row;
    }
    
    public function getConversations($user_id) {
        $query = $this->db->query("SELECT c.* FROM " . DB_PREFIX . "message_conversation c 
            INNER JOIN " . DB_PREFIX . "message_conversation_member cm ON (c.conversation_id = cm.conversation_id) 
            WHERE cm.user_id = '" . (int)$user_id . "' AND cm.status = '1' 
            ORDER BY c.date_modified DESC");
        
        return $query->rows;
    }
    
    public function updateConversation($conversation_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "message_conversation SET 
            title = '" . $this->db->escape($data['title']) . "', 
            is_group = '" . (isset($data['is_group']) ? (int)$data['is_group'] : 0) . "', 
            date_modified = NOW() 
            WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        return true;
    }
    
    public function deleteConversation($conversation_id) {
        // Delete conversation
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_conversation WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        // Delete conversation members
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_conversation_member WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        // Get all messages in this conversation
        $query = $this->db->query("SELECT message_id FROM " . DB_PREFIX . "message WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        foreach ($query->rows as $result) {
            $this->deleteMessage($result['message_id']);
        }
        
        // Delete notifications
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_notification WHERE conversation_id = '" . (int)$conversation_id . "'");
        
        return true;
    }
    
    public function addConversationMember($data) {
        // Check if member already exists
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_conversation_member 
            WHERE conversation_id = '" . (int)$data['conversation_id'] . "' AND user_id = '" . (int)$data['user_id'] . "'");
        
        if ($query->num_rows) {
            // Update existing member
            $this->db->query("UPDATE " . DB_PREFIX . "message_conversation_member SET 
                is_admin = '" . (isset($data['is_admin']) ? (int)$data['is_admin'] : 0) . "', 
                status = '1' 
                WHERE conversation_id = '" . (int)$data['conversation_id'] . "' AND user_id = '" . (int)$data['user_id'] . "'");
        } else {
            // Add new member
            $this->db->query("INSERT INTO " . DB_PREFIX . "message_conversation_member SET 
                conversation_id = '" . (int)$data['conversation_id'] . "', 
                user_id = '" . (int)$data['user_id'] . "', 
                is_admin = '" . (isset($data['is_admin']) ? (int)$data['is_admin'] : 0) . "', 
                status = '1', 
                date_added = NOW()");
        }
        
        return true;
    }
    
    public function removeConversationMember($conversation_id, $user_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_conversation_member 
            WHERE conversation_id = '" . (int)$conversation_id . "' AND user_id = '" . (int)$user_id . "'");
        
        return true;
    }
    
    public function getConversationMembers($conversation_id) {
        $query = $this->db->query("SELECT cm.*, u.username, u.firstname, u.lastname, u.email, u.image 
            FROM " . DB_PREFIX . "message_conversation_member cm 
            LEFT JOIN " . DB_PREFIX . "user u ON (cm.user_id = u.user_id) 
            WHERE cm.conversation_id = '" . (int)$conversation_id . "' AND cm.status = '1' 
            ORDER BY u.username");
        
        return $query->rows;
    }
    
    public function isConversationMember($conversation_id, $user_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_conversation_member 
            WHERE conversation_id = '" . (int)$conversation_id . "' AND user_id = '" . (int)$user_id . "' AND status = '1'");
        
        return $query->num_rows > 0;
    }
    
    public function isConversationAdmin($conversation_id, $user_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_conversation_member 
            WHERE conversation_id = '" . (int)$conversation_id . "' AND user_id = '" . (int)$user_id . "' AND is_admin = '1' AND status = '1'");
        
        return $query->num_rows > 0;
    }
    
    public function getConversationMessageCount($conversation_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "message 
            WHERE conversation_id = '" . (int)$conversation_id . "' AND is_draft = '0' AND is_deleted = '0'");
        
        return $query->row['total'];
    }
    
    public function getConversationMessages($conversation_id, $start = 0, $limit = 20) {
        $query = $this->db->query("SELECT m.*, 
                (SELECT COUNT(*) FROM " . DB_PREFIX . "message_attachment ma WHERE ma.message_id = m.message_id) AS total_attachments 
            FROM " . DB_PREFIX . "message m 
            WHERE m.conversation_id = '" . (int)$conversation_id . "' AND m.is_draft = '0' AND m.is_deleted = '0' 
            ORDER BY m.date_added DESC 
            LIMIT " . (int)$start . "," . (int)$limit);
        
        return $query->rows;
    }
    
    public function getAttachment($attachment_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_attachment 
            WHERE attachment_id = '" . (int)$attachment_id . "'");
        
        return $query->row;
    }
    
    public function addNotification($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "message_notification SET 
            user_id = '" . (int)$data['user_id'] . "', 
            message_id = '" . (isset($data['message_id']) ? (int)$data['message_id'] : 0) . "', 
            conversation_id = '" . (isset($data['conversation_id']) ? (int)$data['conversation_id'] : 0) . "', 
            type = '" . $this->db->escape($data['type']) . "', 
            title = '" . $this->db->escape($data['title']) . "', 
            message = '" . $this->db->escape($data['message']) . "', 
            is_read = '0', 
            date_added = NOW()");
        
        return $this->db->getLastId();
    }
    
    public function getNotifications($user_id, $start = 0, $limit = 20) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_notification 
            WHERE user_id = '" . (int)$user_id . "' 
            ORDER BY date_added DESC 
            LIMIT " . (int)$start . "," . (int)$limit);
        
        return $query->rows;
    }
    
    public function getUnreadNotificationCount($user_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "message_notification 
            WHERE user_id = '" . (int)$user_id . "' AND is_read = '0'");
        
        return $query->row['total'];
    }
    
    public function markNotificationRead($notification_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "message_notification SET 
            is_read = '1' 
            WHERE notification_id = '" . (int)$notification_id . "'");
        
        return true;
    }
    
    public function markAllNotificationsRead($user_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "message_notification SET 
            is_read = '1' 
            WHERE user_id = '" . (int)$user_id . "'");
        
        return true;
    }
    
    public function deleteNotification($notification_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_notification 
            WHERE notification_id = '" . (int)$notification_id . "'");
        
        return true;
    }
    
    public function markMessageAsRead($message_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "message SET 
            is_read = '1' 
            WHERE message_id = '" . (int)$message_id . "'");
        
        return true;
    }
    
    public function markMessageAsUnread($message_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "message SET 
            is_read = '0' 
            WHERE message_id = '" . (int)$message_id . "'");
        
        return true;
    }
    
    public function archiveMessage($message_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "message SET 
            is_archived = '1' 
            WHERE message_id = '" . (int)$message_id . "'");
        
        return true;
    }
    
    public function unarchiveMessage($message_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "message SET 
            is_archived = '0' 
            WHERE message_id = '" . (int)$message_id . "'");
        
        return true;
    }
    
    public function updateMessage($message_id, $data) {
        $has_attachment = (isset($data['attachment']) && is_array($data['attachment']) && count($data['attachment']) > 0) ? 1 : 0;
        
        // Update message
        $this->db->query("UPDATE " . DB_PREFIX . "message SET 
            subject = '" . $this->db->escape($data['subject']) . "', 
            recipient_id = '" . (isset($data['recipient_id']) ? (int)$data['recipient_id'] : 0) . "', 
            recipient = '" . $this->db->escape(isset($data['recipient']) ? $data['recipient'] : '') . "', 
            message = '" . $this->db->escape(isset($data['message']) ? $data['message'] : '') . "', 
            status = '" . (isset($data['status']) ? (int)$data['status'] : 0) . "',
            priority = '" . (isset($data['priority']) ? (int)$data['priority'] : 0) . "',
            is_draft = '" . (isset($data['is_draft']) ? (int)$data['is_draft'] : 0) . "',
            has_attachment = '" . ((int)$has_attachment | (int)$this->messageHasAttachments($message_id)) . "',
            date_modified = NOW()
            WHERE message_id = '" . (int)$message_id . "'");
        
        // Process attachments if any
        if (isset($data['attachment']) && is_array($data['attachment'])) {
            foreach ($data['attachment'] as $attachment) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "message_attachment SET 
                    message_id = '" . (int)$message_id . "', 
                    message_history_id = '0', 
                    filename = '" . $this->db->escape($attachment['filename']) . "', 
                    mask = '" . $this->db->escape(isset($attachment['mask']) ? $attachment['mask'] : $attachment['filename']) . "', 
                    filepath = '" . $this->db->escape($attachment['filepath']) . "', 
                    filesize = '" . (isset($attachment['filesize']) ? (int)$attachment['filesize'] : 0) . "', 
                    filetype = '" . $this->db->escape(isset($attachment['filetype']) ? $attachment['filetype'] : '') . "', 
                    date_added = NOW()");
            }
        }
        
        return true;
    }
    
    /**
     * Check if a message has attachments
     */
    public function messageHasAttachments($message_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "message_attachment 
            WHERE message_id = '" . (int)$message_id . "'");
        
        return $query->row['total'] > 0 ? 1 : 0;
    }
} 