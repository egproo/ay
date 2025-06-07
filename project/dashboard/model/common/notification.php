<?php
class ModelCommonNotification extends Model {
    public function getNotifications($user_id, $start = 0, $limit = 10) {
        $query = $this->db->query("SELECT n.*, nc.name as category_name, nc.icon as category_icon, nc.color as category_color 
            FROM " . DB_PREFIX . "system_notifications n 
            LEFT JOIN " . DB_PREFIX . "notification_category nc ON (n.category_id = nc.category_id)
            LEFT JOIN " . DB_PREFIX . "notification_user nu ON (n.notification_id = nu.notification_id AND nu.user_id = '" . (int)$user_id . "')
            WHERE (n.user_id = '" . (int)$user_id . "' OR n.user_id = 0)
            AND (nu.is_hidden IS NULL OR nu.is_hidden = 0)
            ORDER BY n.created_at DESC
            LIMIT " . (int)$start . "," . (int)$limit);
        
        return $query->rows;
    }
    
    public function getUnreadCount($user_id) {
        $query = $this->db->query("SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "system_notifications n 
            LEFT JOIN " . DB_PREFIX . "notification_user nu ON (n.notification_id = nu.notification_id AND nu.user_id = '" . (int)$user_id . "')
            WHERE (n.user_id = '" . (int)$user_id . "' OR n.user_id = 0)
            AND (nu.is_read = 0 OR nu.is_read IS NULL)
            AND (nu.is_hidden IS NULL OR nu.is_hidden = 0)");
            
        return $query->row['total'];
    }
    
    public function markAsRead($notification_id, $user_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "notification_user SET 
            notification_id = '" . (int)$notification_id . "',
            user_id = '" . (int)$user_id . "',
            is_read = 1,
            read_at = NOW()
            ON DUPLICATE KEY UPDATE 
            is_read = 1,
            read_at = NOW()");
    }
    
    public function markAllAsRead($user_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "notification_user (notification_id, user_id, is_read, read_at)
            SELECT n.notification_id, '" . (int)$user_id . "', 1, NOW()
            FROM " . DB_PREFIX . "system_notifications n
            LEFT JOIN " . DB_PREFIX . "notification_user nu ON (n.notification_id = nu.notification_id AND nu.user_id = '" . (int)$user_id . "')
            WHERE (n.user_id = '" . (int)$user_id . "' OR n.user_id = 0)
            AND (nu.notification_id IS NULL)");
            
        $this->db->query("UPDATE " . DB_PREFIX . "notification_user 
            SET is_read = 1, read_at = NOW()
            WHERE user_id = '" . (int)$user_id . "'
            AND is_read = 0");
    }
    
    public function hideNotification($notification_id, $user_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "notification_user SET 
            notification_id = '" . (int)$notification_id . "',
            user_id = '" . (int)$user_id . "',
            is_hidden = 1
            ON DUPLICATE KEY UPDATE 
            is_hidden = 1");
    }
    
    public function addNotification($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "system_notifications SET 
            title = '" . $this->db->escape($data['title']) . "',
            message = '" . $this->db->escape($data['message']) . "',
            icon = '" . $this->db->escape($data['icon']) . "',
            color = '" . $this->db->escape($data['color']) . "',
            url = '" . $this->db->escape($data['url']) . "',
            user_id = '" . (int)$data['user_id'] . "',
            category_id = '" . (int)$data['category_id'] . "',
            reference_type = '" . $this->db->escape($data['reference_type']) . "',
            reference_id = '" . (int)$data['reference_id'] . "',
            trigger_type = '" . $this->db->escape($data['trigger_type']) . "',
            is_automated = '" . (int)$data['is_automated'] . "',
            created_at = NOW()");
            
        return $this->db->getLastId();
    }
    
    public function getCategories() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "notification_category ORDER BY sort_order ASC");
        return $query->rows;
    }
    
    public function deleteOldNotifications($days = 30) {
        $this->db->query("DELETE n, nu FROM " . DB_PREFIX . "system_notifications n
            LEFT JOIN " . DB_PREFIX . "notification_user nu ON (n.notification_id = nu.notification_id)
            WHERE n.created_at < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)");
    }
}