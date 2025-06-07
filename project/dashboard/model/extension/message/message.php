<?php
/**
 * @package     AYM CMS
 * @author      Team AYM <info@aymcms.com>
 * @copyright   Copyright (c) 2021 AYM. (https://www.aymcms.com)
 * @license     https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 */

class ModelExtensionMessageMessage extends Model {
    public function addMessage($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "message SET 
            sender_id = '" . (int)$this->user->getId() . "', 
            subject = '" . $this->db->escape($data['subject']) . "', 
            message = '" . $this->db->escape($data['message']) . "', 
            date_added = NOW()");
        
        $message_id = $this->db->getLastId();
        
        // Add recipients
        foreach ($data['to'] as $user_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "message_recipient SET 
                message_id = '" . (int)$message_id . "', 
                user_id = '" . (int)$user_id . "',
                is_read = '0', 
                date_added = NOW()");
        }
        
        // Add attachments
        if (isset($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "message_attachment SET 
                    message_id = '" . (int)$message_id . "', 
                    filename = '" . $this->db->escape($attachment['filename']) . "', 
                    filepath = '" . $this->db->escape($attachment['filepath']) . "', 
                    filesize = '" . (int)$attachment['filesize'] . "'");
            }
        }
        
        return $message_id;
    }
    
    public function editMessage($message_id, $data) {
        $this->db->query("UPDATE `" . DB_PREFIX . "message` SET 
            `subject` = '" . $this->db->escape($data['subject']) . "', 
            `message` = '" . $this->db->escape($data['message']) . "', 
            `date_modified` = NOW() 
            WHERE `message_id` = '" . (int)$message_id . "'");
        
        // Delete old recipients
        $this->db->query("DELETE FROM `" . DB_PREFIX . "message_recipient` WHERE `message_id` = '" . (int)$message_id . "'");
        
        // Add new recipients
        if (isset($data['to']) && is_array($data['to'])) {
            foreach ($data['to'] as $user_id) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "message_recipient` SET 
                    `message_id` = '" . (int)$message_id . "', 
                    `user_id` = '" . (int)$user_id . "'");
            }
        }
    }
    
    public function deleteMessage($message_id) {
        // First check if user is the sender or recipient of this message
        $user_id = (int)$this->user->getId();
        
        $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "message m 
                                  LEFT JOIN " . DB_PREFIX . "message_recipient mr ON (m.message_id = mr.message_id) 
                                  WHERE m.message_id = '" . (int)$message_id . "' 
                                  AND (m.sender_id = '" . $user_id . "' OR mr.user_id = '" . $user_id . "')");
        
        if ($query->row['count'] > 0) {
            // Delete the recipient record for this user
            $this->db->query("DELETE FROM " . DB_PREFIX . "message_recipient 
                              WHERE message_id = '" . (int)$message_id . "' 
                              AND user_id = '" . $user_id . "'");
            
            // Check if there are any recipients left
            $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "message_recipient 
                                      WHERE message_id = '" . (int)$message_id . "'");
            
            // If no recipients and sender wants to delete, delete the message and attachments
            if ($query->row['count'] == 0) {
                // Delete attachments
                $this->deleteMessageAttachments($message_id);
                
                // Delete the message
                $this->db->query("DELETE FROM " . DB_PREFIX . "message 
                                 WHERE message_id = '" . (int)$message_id . "'");
            }
            
            return true;
        }
        
        return false;
    }
    
    private function deleteMessageAttachments($message_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_attachment 
                                  WHERE message_id = '" . (int)$message_id . "'");
        
        foreach ($query->rows as $attachment) {
            // Delete the physical file
            if (file_exists(DIR_UPLOAD . $attachment['filepath'])) {
                unlink(DIR_UPLOAD . $attachment['filepath']);
            }
        }
        
        // Delete the attachment records
        $this->db->query("DELETE FROM " . DB_PREFIX . "message_attachment 
                         WHERE message_id = '" . (int)$message_id . "'");
    }
    
    public function getMessage($message_id) {
        $query = $this->db->query("SELECT m.*, u.username as sender FROM " . DB_PREFIX . "message m 
                                  LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id) 
                                  WHERE m.message_id = '" . (int)$message_id . "'");
        
        return $query->row;
    }
    
    public function getMessages($data = array()) {
        $user_id = (int)$this->user->getId();
        
        $sql = "SELECT DISTINCT m.message_id, m.subject, m.date_added, 
                m.sender_id, u.username as sender, 
                mr.is_read, mr.date_read 
                FROM " . DB_PREFIX . "message m 
                LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id)
                LEFT JOIN " . DB_PREFIX . "message_recipient mr ON (m.message_id = mr.message_id)";
        
        if (!empty($data['filter_folder']) && $data['filter_folder'] == 'inbox') {
            $sql .= " WHERE mr.user_id = '" . $user_id . "'";
        } elseif (!empty($data['filter_folder']) && $data['filter_folder'] == 'sent') {
            $sql .= " WHERE m.sender_id = '" . $user_id . "'";
        } else {
            $sql .= " WHERE (mr.user_id = '" . $user_id . "' OR m.sender_id = '" . $user_id . "')";
        }
        
        if (!empty($data['filter_read'])) {
            if ($data['filter_read'] == 'read') {
                $sql .= " AND mr.is_read = '1'";
            } elseif ($data['filter_read'] == 'unread') {
                $sql .= " AND mr.is_read = '0'";
            }
        }
        
        if (!empty($data['filter_search'])) {
            $sql .= " AND (m.subject LIKE '%" . $this->db->escape($data['filter_search']) . "%' OR m.message LIKE '%" . $this->db->escape($data['filter_search']) . "%')";
        }
        
        $sort_data = array(
            'm.date_added',
            'm.subject',
            'u.username',
            'mr.is_read'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY m.date_added";
        }
        
        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
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
    
    public function getTotalMessages($data = array()) {
        $user_id = (int)$this->user->getId();
        
        $sql = "SELECT COUNT(DISTINCT m.message_id) AS total 
                FROM " . DB_PREFIX . "message m 
                LEFT JOIN " . DB_PREFIX . "message_recipient mr ON (m.message_id = mr.message_id)";
        
        if (!empty($data['filter_folder']) && $data['filter_folder'] == 'inbox') {
            $sql .= " WHERE mr.user_id = '" . $user_id . "'";
        } elseif (!empty($data['filter_folder']) && $data['filter_folder'] == 'sent') {
            $sql .= " WHERE m.sender_id = '" . $user_id . "'";
        } else {
            $sql .= " WHERE (mr.user_id = '" . $user_id . "' OR m.sender_id = '" . $user_id . "')";
        }
        
        if (!empty($data['filter_read'])) {
            if ($data['filter_read'] == 'read') {
                $sql .= " AND mr.is_read = '1'";
            } elseif ($data['filter_read'] == 'unread') {
                $sql .= " AND mr.is_read = '0'";
            }
        }
        
        if (!empty($data['filter_search'])) {
            $sql .= " AND (m.subject LIKE '%" . $this->db->escape($data['filter_search']) . "%' OR m.message LIKE '%" . $this->db->escape($data['filter_search']) . "%')";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    public function markAsRead($message_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "message_recipient 
                          SET is_read = '1', date_read = NOW() 
                          WHERE message_id = '" . (int)$message_id . "' 
                          AND user_id = '" . (int)$this->user->getId() . "'");
    }
    
    public function markAsUnread($message_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "message_recipient 
                          SET is_read = '0', date_read = NULL 
                          WHERE message_id = '" . (int)$message_id . "' 
                          AND user_id = '" . (int)$this->user->getId() . "'");
    }
    
    public function getMessageRecipients($message_id) {
        $query = $this->db->query("SELECT mr.*, u.username FROM " . DB_PREFIX . "message_recipient mr 
                                  LEFT JOIN " . DB_PREFIX . "user u ON (mr.user_id = u.user_id) 
                                  WHERE mr.message_id = '" . (int)$message_id . "'");
        
        return $query->rows;
    }
    
    public function getUser($user_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "user` WHERE `user_id` = '" . (int)$user_id . "'");
        
        if ($query->num_rows) {
            return $query->row;
        } else {
            return false;
        }
    }
    
    // Attachment methods
    public function addAttachment($message_id, $tmp_name, $filename, $size) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        $unique_filename = md5(mt_rand()) . '.' . $extension;
        
        $file_path = DIR_UPLOAD . $unique_filename;
        
        move_uploaded_file($tmp_name, $file_path);
        
        $mime_type = $this->getMimeType($file_path);
        
        $this->db->query("INSERT INTO `" . DB_PREFIX . "message_attachment` SET 
            `message_id` = '" . (int)$message_id . "', 
            `filename` = '" . $this->db->escape($unique_filename) . "', 
            `mask` = '" . $this->db->escape($filename) . "', 
            `size` = '" . (int)$size . "', 
            `mime_type` = '" . $this->db->escape($mime_type) . "', 
            `date_added` = NOW()");
        
        return $this->db->getLastId();
    }
    
    public function copyAttachment($message_id, $attachment_id) {
        $attachment_info = $this->getAttachment($attachment_id);
        
        if ($attachment_info) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "message_attachment` SET 
                `message_id` = '" . (int)$message_id . "', 
                `filename` = '" . $this->db->escape($attachment_info['filename']) . "', 
                `mask` = '" . $this->db->escape($attachment_info['mask']) . "', 
                `size` = '" . (int)$attachment_info['size'] . "', 
                `mime_type` = '" . $this->db->escape($attachment_info['mime_type']) . "', 
                `date_added` = NOW()");
            
            return $this->db->getLastId();
        }
        
        return false;
    }
    
    public function deleteAttachment($attachment_id) {
        $attachment_info = $this->getAttachment($attachment_id);
        
        if ($attachment_info) {
            // Delete file from disk
            $file = DIR_UPLOAD . $attachment_info['filename'];
            
            if (file_exists($file)) {
                unlink($file);
            }
            
            // Delete from database
            $this->db->query("DELETE FROM `" . DB_PREFIX . "message_attachment` WHERE `attachment_id` = '" . (int)$attachment_id . "'");
        }
    }
    
    public function getAttachment($attachment_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "message_attachment` WHERE `attachment_id` = '" . (int)$attachment_id . "'");
        
        if ($query->num_rows) {
            return $query->row;
        } else {
            return false;
        }
    }
    
    public function getMessageAttachments($message_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "message_attachment` WHERE `message_id` = '" . (int)$message_id . "' ORDER BY `date_added` ASC");
        
        return $query->rows;
    }
    
    private function getMimeType($file) {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file);
            finfo_close($finfo);
            return $mime_type;
        } else {
            return 'application/octet-stream';
        }
    }
    
    // Unread message count - for display in dashboard
    public function getTotalUnreadMessages() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "message_recipient` WHERE `user_id` = '" . (int)$this->user->getId() . "' AND `is_read` = '0'");
        
        return $query->row['total'];
    }
    
    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message` (
                `message_id` int(11) NOT NULL AUTO_INCREMENT,
                `sender_id` int(11) NOT NULL,
                `subject` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `date_added` datetime NOT NULL,
                PRIMARY KEY (`message_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
        
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_recipient` (
                `message_recipient_id` int(11) NOT NULL AUTO_INCREMENT,
                `message_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `is_read` tinyint(1) NOT NULL DEFAULT '0',
                `date_added` datetime NOT NULL,
                `date_read` datetime DEFAULT NULL,
                PRIMARY KEY (`message_recipient_id`),
                KEY `message_id` (`message_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
        
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_attachment` (
                `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
                `message_id` int(11) NOT NULL,
                `filename` varchar(255) NOT NULL,
                `filepath` varchar(255) NOT NULL,
                `filesize` int(11) NOT NULL,
                PRIMARY KEY (`attachment_id`),
                KEY `message_id` (`message_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
    }
    
    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_recipient`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_attachment`");
    }
} 