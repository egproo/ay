<?php
/**
 * نظام إدارة الإشعارات
 * يستخدم لإرسال وإدارة الإشعارات في النظام
 */
class ModelNotificationNotification extends Model {
    /**
     * إضافة إشعار جديد
     * 
     * @param array $data بيانات الإشعار
     * @return int معرف الإشعار الجديد
     */
    public function addNotification($data) {
        // التحقق من وجود الجدول
        $this->createNotificationTables();
        
        // تعيين القيم الافتراضية
        $data['priority'] = isset($data['priority']) ? $data['priority'] : 'normal';
        $data['icon'] = isset($data['icon']) ? $data['icon'] : 'bell';
        $data['url'] = isset($data['url']) ? $data['url'] : '';
        $data['user_id'] = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        $data['reference_id'] = isset($data['reference_id']) ? (int)$data['reference_id'] : 0;
        $data['reference_type'] = isset($data['reference_type']) ? $data['reference_type'] : '';
        
        // إضافة الإشعار
        $this->db->query("INSERT INTO " . DB_PREFIX . "system_notifications SET 
            title = '" . $this->db->escape($data['title']) . "', 
            message = '" . $this->db->escape($data['message']) . "', 
            icon = '" . $this->db->escape($data['icon']) . "', 
            priority = '" . $this->db->escape($data['priority']) . "', 
            url = '" . $this->db->escape($data['url']) . "', 
            type = '" . $this->db->escape($data['type']) . "', 
            reference_id = '" . (int)$data['reference_id'] . "', 
            reference_type = '" . $this->db->escape($data['reference_type']) . "', 
            created_at = NOW()");
        
        $notification_id = $this->db->getLastId();
        
        // إضافة الإشعار للمستخدمين
        if (isset($data['user_ids']) && is_array($data['user_ids'])) {
            foreach ($data['user_ids'] as $user_id) {
                $this->addNotificationToUser($notification_id, $user_id);
            }
        } elseif (isset($data['user_groups']) && is_array($data['user_groups'])) {
            // إضافة لجميع المستخدمين في مجموعات محددة
            foreach ($data['user_groups'] as $user_group_id) {
                $query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user 
                    WHERE user_group_id = '" . (int)$user_group_id . "'");
                
                foreach ($query->rows as $user) {
                    $this->addNotificationToUser($notification_id, $user['user_id']);
                }
            }
        } elseif (isset($data['all_users']) && $data['all_users']) {
            // إضافة لجميع المستخدمين
            $query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user");
            
            foreach ($query->rows as $user) {
                $this->addNotificationToUser($notification_id, $user['user_id']);
            }
        } else {
            // إذا لم يتم تحديد مستخدمين، نضيف الإشعار لجميع المستخدمين
            $query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user");
            
            foreach ($query->rows as $user) {
                $this->addNotificationToUser($notification_id, $user['user_id']);
            }
        }
        
        return $notification_id;
    }
    
    /**
     * إضافة إشعار لمستخدم محدد
     * 
     * @param int $notification_id معرف الإشعار
     * @param int $user_id معرف المستخدم
     * @return bool
     */
    public function addNotificationToUser($notification_id, $user_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "notification_user SET 
            notification_id = '" . (int)$notification_id . "', 
            user_id = '" . (int)$user_id . "', 
            is_read = 0, 
            created_at = NOW()");
        
        return true;
    }
    
    /**
     * الحصول على إشعارات المستخدم
     * 
     * @param int $user_id معرف المستخدم
     * @param int $limit عدد الإشعارات
     * @param int $start بداية الإشعارات
     * @return array
     */
    public function getNotifications($user_id, $limit = 10, $start = 0) {
        $query = $this->db->query("SELECT n.notification_id, n.title, n.message, n.type, 
            n.priority, n.icon, n.url, n.reference_id, n.reference_type, n.created_at, 
            nu.is_read, nu.read_at 
            FROM " . DB_PREFIX . "system_notifications n 
            JOIN " . DB_PREFIX . "notification_user nu ON (n.notification_id = nu.notification_id) 
            WHERE nu.user_id = '" . (int)$user_id . "' 
            ORDER BY n.created_at DESC 
            LIMIT " . (int)$start . "," . (int)$limit);
        
        return $query->rows;
    }
    
    /**
     * الحصول على عدد الإشعارات غير المقروءة للمستخدم
     * 
     * @param int $user_id معرف المستخدم
     * @return int
     */
    public function getUnreadCount($user_id) {
        $query = $this->db->query("SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "notification_user 
            WHERE user_id = '" . (int)$user_id . "' 
            AND is_read = 0");
        
        return $query->row['total'];
    }
    
    /**
     * تعليم إشعار كمقروء
     * 
     * @param int $notification_id معرف الإشعار
     * @param int $user_id معرف المستخدم
     * @return bool
     */
    public function markAsRead($notification_id, $user_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "notification_user SET 
            is_read = 1, 
            read_at = NOW() 
            WHERE notification_id = '" . (int)$notification_id . "' 
            AND user_id = '" . (int)$user_id . "'");
        
        return true;
    }
    
    /**
     * تعليم جميع الإشعارات كمقروءة للمستخدم
     * 
     * @param int $user_id معرف المستخدم
     * @return bool
     */
    public function markAllAsRead($user_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "notification_user SET 
            is_read = 1, 
            read_at = NOW() 
            WHERE user_id = '" . (int)$user_id . "' 
            AND is_read = 0");
        
        return true;
    }
    
    /**
     * حذف إشعار
     * 
     * @param int $notification_id معرف الإشعار
     * @return bool
     */
    public function deleteNotification($notification_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "notification_user 
            WHERE notification_id = '" . (int)$notification_id . "'");
            
        $this->db->query("DELETE FROM " . DB_PREFIX . "system_notifications 
            WHERE notification_id = '" . (int)$notification_id . "'");
        
        return true;
    }
    
    /**
     * إنشاء جداول الإشعارات إذا لم تكن موجودة
     */
    private function createNotificationTables() {
        // جدول الإشعارات
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "system_notifications` (
            `notification_id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `message` text NOT NULL,
            `icon` varchar(50) NOT NULL DEFAULT 'bell',
            `priority` varchar(20) NOT NULL DEFAULT 'normal',
            `url` varchar(255) NOT NULL DEFAULT '',
            `type` varchar(50) NOT NULL,
            `reference_id` int(11) NOT NULL DEFAULT '0',
            `reference_type` varchar(50) NOT NULL DEFAULT '',
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`notification_id`),
            KEY `type` (`type`),
            KEY `reference_id` (`reference_id`),
            KEY `reference_type` (`reference_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
        
        // جدول علاقة الإشعارات بالمستخدمين
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "notification_user` (
            `notification_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `is_read` tinyint(1) NOT NULL DEFAULT '0',
            `read_at` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`notification_id`,`user_id`),
            KEY `user_id` (`user_id`),
            KEY `is_read` (`is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
    }
}
