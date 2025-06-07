<?php
/**
 * Notification Center Model for OpenCart 3.0.3.7
 * نموذج مركز الإشعارات لـ OpenCart 3.0.3.7
 */

class ModelNotificationCenter extends Model {
    
    public function getNotifications($data = array()) {
        $sql = "SELECT n.*, u.firstname, u.lastname, nt.name as type_name, nt.icon, nt.color
                FROM " . DB_PREFIX . "notification n
                LEFT JOIN " . DB_PREFIX . "user u ON (n.sender_id = u.user_id)
                LEFT JOIN " . DB_PREFIX . "notification_type nt ON (n.type = nt.code)
                WHERE n.recipient_id = '" . (int)$this->user->getId() . "'";
        
        if (!empty($data['type'])) {
            $sql .= " AND n.type = '" . $this->db->escape($data['type']) . "'";
        }
        
        if (!empty($data['status'])) {
            $sql .= " AND n.status = '" . $this->db->escape($data['status']) . "'";
        }
        
        if (!empty($data['priority'])) {
            $sql .= " AND n.priority = '" . $this->db->escape($data['priority']) . "'";
        }
        
        if (!empty($data['date_from'])) {
            $sql .= " AND DATE(n.date_added) >= '" . $this->db->escape($data['date_from']) . "'";
        }
        
        if (!empty($data['date_to'])) {
            $sql .= " AND DATE(n.date_added) <= '" . $this->db->escape($data['date_to']) . "'";
        }
        
        $sql .= " ORDER BY n.priority DESC, n.date_added DESC";
        
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
    
    public function getTotalNotifications($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "notification n
                WHERE n.recipient_id = '" . (int)$this->user->getId() . "'";
        
        if (!empty($data['type'])) {
            $sql .= " AND n.type = '" . $this->db->escape($data['type']) . "'";
        }
        
        if (!empty($data['status'])) {
            $sql .= " AND n.status = '" . $this->db->escape($data['status']) . "'";
        }
        
        if (!empty($data['priority'])) {
            $sql .= " AND n.priority = '" . $this->db->escape($data['priority']) . "'";
        }
        
        if (!empty($data['date_from'])) {
            $sql .= " AND DATE(n.date_added) >= '" . $this->db->escape($data['date_from']) . "'";
        }
        
        if (!empty($data['date_to'])) {
            $sql .= " AND DATE(n.date_added) <= '" . $this->db->escape($data['date_to']) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    public function getRecentNotifications($limit = 10) {
        $query = $this->db->query("SELECT n.*, u.firstname, u.lastname, nt.name as type_name, nt.icon, nt.color
                                   FROM " . DB_PREFIX . "notification n
                                   LEFT JOIN " . DB_PREFIX . "user u ON (n.sender_id = u.user_id)
                                   LEFT JOIN " . DB_PREFIX . "notification_type nt ON (n.type = nt.code)
                                   WHERE n.recipient_id = '" . (int)$this->user->getId() . "'
                                   ORDER BY n.date_added DESC
                                   LIMIT " . (int)$limit);
        
        return $query->rows;
    }
    
    public function getNotificationStats() {
        $stats = array();
        
        // Total notifications
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "notification 
                                   WHERE recipient_id = '" . (int)$this->user->getId() . "'");
        $stats['total'] = $query->row['total'];
        
        // Unread notifications
        $query = $this->db->query("SELECT COUNT(*) as unread FROM " . DB_PREFIX . "notification 
                                   WHERE recipient_id = '" . (int)$this->user->getId() . "' AND status = 'unread'");
        $stats['unread'] = $query->row['unread'];
        
        // High priority notifications
        $query = $this->db->query("SELECT COUNT(*) as high_priority FROM " . DB_PREFIX . "notification 
                                   WHERE recipient_id = '" . (int)$this->user->getId() . "' AND priority = 'high' AND status = 'unread'");
        $stats['high_priority'] = $query->row['high_priority'];
        
        // Today's notifications
        $query = $this->db->query("SELECT COUNT(*) as today FROM " . DB_PREFIX . "notification 
                                   WHERE recipient_id = '" . (int)$this->user->getId() . "' AND DATE(date_added) = CURDATE()");
        $stats['today'] = $query->row['today'];
        
        return $stats;
    }
    
    public function getNotificationTypes() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "notification_type ORDER BY sort_order, name");
        
        return $query->rows;
    }
    
    public function getUserPreferences($user_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "notification_preference 
                                   WHERE user_id = '" . (int)$user_id . "'");
        
        if ($query->num_rows) {
            $preferences = $query->row;
            $preferences['notification_types'] = json_decode($preferences['notification_types'], true) ? json_decode($preferences['notification_types'], true) : array();
            return $preferences;
        }
        
        // Return default preferences
        return array(
            'email_notifications' => 1,
            'sms_notifications' => 0,
            'desktop_notifications' => 1,
            'sound_notifications' => 1,
            'notification_types' => array()
        );
    }
    
    public function markAsRead($notification_id, $user_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "notification 
                          SET status = 'read', read_at = NOW() 
                          WHERE notification_id = '" . (int)$notification_id . "' 
                          AND recipient_id = '" . (int)$user_id . "'");
        
        return $this->db->countAffected() > 0;
    }
    
    public function markAllAsRead($user_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "notification 
                          SET status = 'read', read_at = NOW() 
                          WHERE recipient_id = '" . (int)$user_id . "' AND status = 'unread'");
        
        return $this->db->countAffected();
    }
    
    public function deleteNotification($notification_id, $user_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "notification 
                          WHERE notification_id = '" . (int)$notification_id . "' 
                          AND recipient_id = '" . (int)$user_id . "'");
        
        return $this->db->countAffected() > 0;
    }
    
    public function updateUserPreferences($user_id, $preferences) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "notification_preference WHERE user_id = '" . (int)$user_id . "'");
        
        $this->db->query("INSERT INTO " . DB_PREFIX . "notification_preference SET 
                          user_id = '" . (int)$user_id . "',
                          email_notifications = '" . (int)$preferences['email_notifications'] . "',
                          sms_notifications = '" . (int)$preferences['sms_notifications'] . "',
                          desktop_notifications = '" . (int)$preferences['desktop_notifications'] . "',
                          sound_notifications = '" . (int)$preferences['sound_notifications'] . "',
                          notification_types = '" . $this->db->escape(json_encode($preferences['notification_types'])) . "',
                          date_modified = NOW()");
        
        return true;
    }
    
    public function sendNotification($data, $sender_id) {
        $notification_id = 0;
        
        foreach ($data['recipients'] as $recipient_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "notification SET 
                              sender_id = '" . (int)$sender_id . "',
                              recipient_id = '" . (int)$recipient_id . "',
                              title = '" . $this->db->escape($data['title']) . "',
                              message = '" . $this->db->escape($data['message']) . "',
                              type = '" . $this->db->escape($data['type']) . "',
                              priority = '" . $this->db->escape($data['priority']) . "',
                              status = 'unread',
                              date_added = NOW()");
            
            if (!$notification_id) {
                $notification_id = $this->db->getLastId();
            }
            
            // Send email if requested
            if ($data['send_email']) {
                $this->sendEmailNotification($recipient_id, $data);
            }
            
            // Send SMS if requested
            if ($data['send_sms']) {
                $this->sendSMSNotification($recipient_id, $data);
            }
        }
        
        return $notification_id;
    }
    
    public function getUnreadCount($user_id) {
        $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "notification
                                   WHERE recipient_id = '" . (int)$user_id . "' AND status = 'unread'");

        return $query->row['count'];
    }

    /**
     * Get user notifications - compatibility method for header.php
     * الحصول على إشعارات المستخدم - طريقة التوافق لـ header.php
     */
    public function getUserNotifications($user_id, $limit = 10) {
        // Simple notification system for OpenCart 3.0.3.7 compatibility
        $notifications = array();

        // Check for low stock products
        $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "product WHERE quantity <= minimum AND status = 1");
        if ($query->row['count'] > 0) {
            $notifications[] = array(
                'notification_id' => 1,
                'title' => 'Low Stock Alert',
                'message' => $query->row['count'] . ' products are running low on stock',
                'type' => 'inventory_low',
                'priority' => 'high',
                'link' => 'catalog/product',
                'created_at' => date('Y-m-d H:i:s'),
                'is_read' => 0
            );
        }

        // Check for pending orders
        $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "order WHERE order_status_id = 1");
        if ($query->row['count'] > 0) {
            $notifications[] = array(
                'notification_id' => 2,
                'title' => 'Pending Orders',
                'message' => $query->row['count'] . ' orders are pending processing',
                'type' => 'system',
                'priority' => 'medium',
                'link' => 'sale/order',
                'created_at' => date('Y-m-d H:i:s'),
                'is_read' => 0
            );
        }

        // Check for new customers today
        $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "customer WHERE DATE(date_added) = CURDATE()");
        if ($query->row['count'] > 0) {
            $notifications[] = array(
                'notification_id' => 3,
                'title' => 'New Customers',
                'message' => $query->row['count'] . ' new customers registered today',
                'type' => 'customer',
                'priority' => 'low',
                'link' => 'customer/customer',
                'created_at' => date('Y-m-d H:i:s'),
                'is_read' => 0
            );
        }

        return array_slice($notifications, 0, $limit);
    }
    
    public function archiveNotification($notification_id, $user_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "notification 
                          SET status = 'archived', archived_at = NOW() 
                          WHERE notification_id = '" . (int)$notification_id . "' 
                          AND recipient_id = '" . (int)$user_id . "'");
        
        return $this->db->countAffected() > 0;
    }
    
    public function getNotificationTemplate($template_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "notification_template 
                                   WHERE template_id = '" . (int)$template_id . "'");
        
        return $query->num_rows ? $query->row : false;
    }
    
    private function sendEmailNotification($recipient_id, $data) {
        // Get recipient email
        $query = $this->db->query("SELECT email FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$recipient_id . "'");
        
        if ($query->num_rows) {
            $email = $query->row['email'];
            
            // Load mail library and send email
            $this->load->library('mail');
            
            $mail = new Mail();
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
            
            $mail->setTo($email);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($this->config->get('config_name'));
            $mail->setSubject($data['title']);
            $mail->setText($data['message']);
            $mail->send();
        }
    }
    
    private function sendSMSNotification($recipient_id, $data) {
        // Get recipient phone
        $query = $this->db->query("SELECT phone FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$recipient_id . "'");
        
        if ($query->num_rows && !empty($query->row['phone'])) {
            $phone = $query->row['phone'];
            
            // Implement SMS sending logic here
            // This would depend on your SMS provider (Twilio, etc.)
        }
    }
}
