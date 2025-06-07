<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 * 
 * Model: Notification System
 */
class ModelToolNotification extends Model {
    
    /**
     * Add a new notification
     * 
     * @param array $data
     * @return int
     */
    public function addNotification($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "system_notifications SET 
            title = '" . $this->db->escape($data['title']) . "', 
            content = '" . $this->db->escape($data['content']) . "', 
            type = '" . $this->db->escape($data['type']) . "', 
            priority = '" . $this->db->escape(isset($data['priority']) ? $data['priority'] : 'normal') . "', 
            link = '" . $this->db->escape(isset($data['link']) ? $data['link'] : '') . "', 
            created_at = NOW()" . 
            (isset($data['expires_at']) ? ", expires_at = '" . $this->db->escape($data['expires_at']) . "'" : ""));
        
        $notification_id = $this->db->getLastId();
        
        // Add notification to users
        if (isset($data['user_ids']) && is_array($data['user_ids'])) {
            foreach ($data['user_ids'] as $user_id) {
                $this->addNotificationToUser($notification_id, $user_id);
            }
        } elseif (isset($data['user_groups']) && is_array($data['user_groups'])) {
            // Add to all users in specified user groups
            foreach ($data['user_groups'] as $user_group_id) {
                $query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user 
                    WHERE user_group_id = '" . (int)$user_group_id . "'");
                
                foreach ($query->rows as $user) {
                    $this->addNotificationToUser($notification_id, $user['user_id']);
                }
            }
        } elseif (isset($data['all_users']) && $data['all_users']) {
            // Add to all users
            $query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user");
            
            foreach ($query->rows as $user) {
                $this->addNotificationToUser($notification_id, $user['user_id']);
            }
        }
        
        return $notification_id;
    }
    
    /**
     * Add notification to specific user
     * 
     * @param int $notification_id
     * @param int $user_id
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
     * Get notifications for a user
     * 
     * @param int $user_id
     * @param int $limit
     * @param int $start
     * @return array
     */
    public function getNotifications($user_id, $limit = 10, $start = 0) {
        $query = $this->db->query("SELECT n.notification_id, n.title, n.content, n.type, 
            n.priority, n.link, n.created_at, nu.is_read, nu.read_at 
            FROM " . DB_PREFIX . "system_notifications n 
            JOIN " . DB_PREFIX . "notification_user nu ON (n.notification_id = nu.notification_id) 
            WHERE nu.user_id = '" . (int)$user_id . "' 
            AND (n.expires_at IS NULL OR n.expires_at > NOW()) 
            ORDER BY n.created_at DESC 
            LIMIT " . (int)$start . "," . (int)$limit);
        
        $notifications = [];
        
        foreach ($query->rows as $row) {
            $notifications[] = [
                'notification_id' => $row['notification_id'],
                'title' => $row['title'],
                'content' => $row['content'],
                'type' => $row['type'],
                'priority' => $row['priority'],
                'link' => $row['link'],
                'is_read' => (bool)$row['is_read'],
                'read_at' => $row['read_at'],
                'created_at' => $row['created_at'],
                'time_ago' => $this->timeAgo($row['created_at']),
                'icon' => $this->getNotificationIcon($row['type'])
            ];
        }
        
        return $notifications;
    }
    
    /**
     * Get total notifications count for a user
     * 
     * @param int $user_id
     * @return int
     */
    public function getTotalNotifications($user_id) {
        $query = $this->db->query("SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "notification_user nu 
            JOIN " . DB_PREFIX . "system_notifications n ON (nu.notification_id = n.notification_id) 
            WHERE nu.user_id = '" . (int)$user_id . "' 
            AND (n.expires_at IS NULL OR n.expires_at > NOW())");
        
        return $query->row['total'];
    }
    
    /**
     * Get unread notification count for a user
     * 
     * @param int $user_id
     * @return int
     */
    public function getUnreadNotificationCount($user_id) {
        $query = $this->db->query("SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "notification_user nu 
            JOIN " . DB_PREFIX . "system_notifications n ON (nu.notification_id = n.notification_id) 
            WHERE nu.user_id = '" . (int)$user_id . "' 
            AND nu.is_read = 0 
            AND (n.expires_at IS NULL OR n.expires_at > NOW())");
        
        return $query->row['total'];
    }
    
    /**
     * Mark a notification as read
     * 
     * @param int $notification_id
     * @param int $user_id
     * @return bool
     */
    public function markAsRead($notification_id, $user_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "notification_user SET 
            is_read = 1, 
            read_at = NOW() 
            WHERE notification_id = '" . (int)$notification_id . "' 
            AND user_id = '" . (int)$user_id . "'");
        
        return $this->db->countAffected() > 0;
    }
    
    /**
     * Mark all notifications as read for a user
     * 
     * @param int $user_id
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
     * Delete a notification
     * 
     * @param int $notification_id
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
     * Delete expired notifications
     * 
     * @return bool
     */
    public function deleteExpiredNotifications() {
        // Get expired notification IDs
        $query = $this->db->query("SELECT notification_id FROM " . DB_PREFIX . "system_notifications 
            WHERE expires_at < NOW()");
        
        foreach ($query->rows as $row) {
            $this->deleteNotification($row['notification_id']);
        }
        
        return true;
    }
    
    /**
     * Create system notification types
     * 
     * @return bool
     */
    public function createSystemNotification($type, $data) {
        switch ($type) {
            case 'new_order':
                return $this->createNewOrderNotification($data);
                
            case 'low_stock':
                return $this->createLowStockNotification($data);
                
            case 'payment_received':
                return $this->createPaymentReceivedNotification($data);
                
            case 'order_status_change':
                return $this->createOrderStatusChangeNotification($data);
                
            case 'system_alert':
                return $this->createSystemAlertNotification($data);
                
            default:
                return false;
        }
    }
    
    /**
     * Create new order notification
     * 
     * @param array $data
     * @return int
     */
    private function createNewOrderNotification($data) {
        $order_id = isset($data['order_id']) ? $data['order_id'] : 0;
        
        if (!$order_id) {
            return false;
        }
        
        // Load order model
        $this->load->model('sale/order');
        $order_info = $this->model_sale_order->getOrder($order_id);
        
        if (!$order_info) {
            return false;
        }
        
        $notification_data = [
            'title' => 'New Order #' . $order_id,
            'content' => 'A new order has been placed by ' . $order_info['firstname'] . ' ' . $order_info['lastname'] . ' for ' . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']),
            'type' => 'order',
            'priority' => 'normal',
            'link' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true),
            'user_groups' => [1, 2] // Assuming 1=Admin, 2=Sales
        ];
        
        return $this->addNotification($notification_data);
    }
    
    /**
     * Create low stock notification
     * 
     * @param array $data
     * @return int
     */
    private function createLowStockNotification($data) {
        $product_id = isset($data['product_id']) ? $data['product_id'] : 0;
        
        if (!$product_id) {
            return false;
        }
        
        // Load product model
        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);
        
        if (!$product_info) {
            return false;
        }
        
        $notification_data = [
            'title' => 'Low Stock Alert',
            'content' => 'Product "' . $product_info['name'] . '" is running low on stock. Current quantity: ' . $product_info['quantity'],
            'type' => 'inventory',
            'priority' => 'high',
            'link' => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product_id, true),
            'user_groups' => [1, 3] // Assuming 1=Admin, 3=Inventory
        ];
        
        return $this->addNotification($notification_data);
    }
    
    /**
     * Create payment received notification
     * 
     * @param array $data
     * @return int
     */
    private function createPaymentReceivedNotification($data) {
        $order_id = isset($data['order_id']) ? $data['order_id'] : 0;
        $amount = isset($data['amount']) ? $data['amount'] : 0;
        
        if (!$order_id || !$amount) {
            return false;
        }
        
        // Load order model
        $this->load->model('sale/order');
        $order_info = $this->model_sale_order->getOrder($order_id);
        
        if (!$order_info) {
            return false;
        }
        
        $notification_data = [
            'title' => 'Payment Received',
            'content' => 'Payment of ' . $this->currency->format($amount, $order_info['currency_code'], $order_info['currency_value']) . ' has been received for Order #' . $order_id,
            'type' => 'payment',
            'priority' => 'normal',
            'link' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true),
            'user_groups' => [1, 4] // Assuming 1=Admin, 4=Finance
        ];
        
        return $this->addNotification($notification_data);
    }
    
    /**
     * Create order status change notification
     * 
     * @param array $data
     * @return int
     */
    private function createOrderStatusChangeNotification($data) {
        $order_id = isset($data['order_id']) ? $data['order_id'] : 0;
        $status = isset($data['status']) ? $data['status'] : '';
        
        if (!$order_id || !$status) {
            return false;
        }
        
        $notification_data = [
            'title' => 'Order Status Updated',
            'content' => 'Order #' . $order_id . ' status has been changed to "' . $status . '"',
            'type' => 'order',
            'priority' => 'normal',
            'link' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true),
            'user_groups' => [1, 2] // Assuming 1=Admin, 2=Sales
        ];
        
        return $this->addNotification($notification_data);
    }
    
    /**
     * Create system alert notification
     * 
     * @param array $data
     * @return int
     */
    private function createSystemAlertNotification($data) {
        $title = isset($data['title']) ? $data['title'] : 'System Alert';
        $content = isset($data['content']) ? $data['content'] : '';
        $priority = isset($data['priority']) ? $data['priority'] : 'high';
        
        if (!$content) {
            return false;
        }
        
        $notification_data = [
            'title' => $title,
            'content' => $content,
            'type' => 'system',
            'priority' => $priority,
            'all_users' => true
        ];
        
        return $this->addNotification($notification_data);
    }
    
    /**
     * Get icon for notification type
     * 
     * @param string $type
     * @return string
     */
    private function getNotificationIcon($type) {
        switch ($type) {
            case 'order':
                return 'fa fa-shopping-cart bg-blue';
                
            case 'inventory':
                return 'fa fa-cubes bg-yellow';
                
            case 'payment':
                return 'fa fa-money bg-green';
                
            case 'system':
                return 'fa fa-cog bg-red';
                
            case 'message':
                return 'fa fa-envelope bg-aqua';
                
            case 'user':
                return 'fa fa-user bg-purple';
                
            default:
                return 'fa fa-bell-o bg-gray';
        }
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