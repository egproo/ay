<?php
class ControllerCommonHeader extends Controller {
    public function index() {
        $this->load->language('common/header');

        $data['heading_title'] = $this->language->get('heading_title');

        $data['logged'] = $this->user->isLogged();
        $data['home'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']);

        if (!$data['logged']) {
            return new Action('common/login');
        }

        $this->load->model('tool/image');

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('user/user');
        $user_info = $this->model_user_user->getUser($this->user->getId());

        if ($user_info) {
            $data['firstname'] = $user_info['firstname'];
            $data['lastname'] = $user_info['lastname'];
            $data['username'] = $user_info['username'];
            $data['email'] = $user_info['email'];

            if (is_file(DIR_IMAGE . $user_info['image'])) {
                $data['image'] = $this->model_tool_image->resize($user_info['image'], 45, 45);
            } else {
                $data['image'] = $this->model_tool_image->resize('profile.png', 45, 45);
            }
        }

        // Simple notifications for OpenCart 3.0.3.7 compatibility
        $data['notifications'] = array();
        $data['unread_notifications'] = 0;

        // Check for basic system notifications
        $notifications = $this->getBasicNotifications();
        foreach ($notifications as $notification) {
            $data['notifications'][] = array(
                'notification_id' => $notification['id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'type' => $notification['type'],
                'priority' => $notification['priority'],
                'icon' => $this->getNotificationIcon($notification['type']),
                'color' => $this->getNotificationColor($notification['priority']),
                'url' => $notification['url'],
                'created_at' => $this->timeAgo($notification['created_at']),
                'is_read' => false
            );
        }

        $data['unread_notifications'] = count($data['notifications']);

        // Simple messages system
        $data['messages'] = array();
        $data['unread_messages'] = 0;

        // Simple tasks system
        $data['tasks'] = array();
        $data['pending_tasks'] = 0;

        // Simple approvals system
        $data['approvals'] = array();
        $data['pending_approvals'] = 0;

        // Basic system health
        $data['system_health'] = array(
            'cpu_usage' => $this->getBasicCPUUsage(),
            'memory_usage' => $this->getBasicMemoryUsage(),
            'response_time' => 0.5,
            'active_users' => $this->getActiveUsersCount(),
            'system_status' => 'healthy'
        );

        // AI assistant placeholder
        $data['ai_assistant'] = array(
            'enabled' => false,
            'status' => 'offline',
            'suggestions_count' => 0,
            'last_analysis' => date('Y-m-d H:i:s')
        );

        // User menu items
        $data['profile'] = $this->url->link('user/user/edit', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $this->user->getId());
        $data['settings'] = $this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token']);
        $data['logout'] = $this->url->link('common/logout', 'user_token=' . $this->session->data['user_token']);

        $this->load->model('setting/store');
        $data['stores'] = $this->model_setting_store->getStores();
        $data['current_store_id'] = 0;

        return $this->load->view('common/header', $data);
    }

    public function getHeaderData() {
        $json = array();

        if (!$this->user->isLogged()) {
            $json['error'] = 'Permission denied';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // Basic notifications for OpenCart 3.0.3.7
        $notifications = $this->getBasicNotifications();
        $json['notifications'] = array();
        foreach ($notifications as $notification) {
            $json['notifications'][] = array(
                'notification_id' => $notification['id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'icon' => $this->getNotificationIcon($notification['type']),
                'color' => $this->getNotificationColor($notification['priority']),
                'url' => $notification['url'],
                'created_at' => $this->timeAgo($notification['created_at']),
                'is_read' => false
            );
        }

        $json['unread_notifications'] = count($json['notifications']);

        // Basic messages
        $json['messages'] = array();
        $json['unread_messages'] = 0;

        // Basic tasks
        $json['tasks'] = array();
        $json['pending_tasks'] = 0;

        // Basic approvals
        $json['approvals'] = array();
        $json['pending_approvals'] = 0;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function markNotificationRead($notification_id) {
        // Placeholder for OpenCart 3.0.3.7 compatibility
        return true;
    }

    public function markAllNotificationsRead() {
        $json = array('success' => true, 'unread_notifications' => 0);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function markMessageRead($message_id) {
        // Placeholder for OpenCart 3.0.3.7 compatibility
        return true;
    }

    public function markAllMessagesRead() {
        $json = array('success' => true, 'unread_messages' => 0);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Get basic notifications for OpenCart 3.0.3.7
     */
    private function getBasicNotifications() {
        $notifications = array();

        // Check for low stock products
        $this->load->model('catalog/product');
        $low_stock_products = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "product WHERE quantity <= minimum AND status = 1");

        if ($low_stock_products->row['count'] > 0) {
            $notifications[] = array(
                'id' => 1,
                'title' => 'Low Stock Alert',
                'message' => $low_stock_products->row['count'] . ' products are running low on stock',
                'type' => 'inventory_low',
                'priority' => 'high',
                'url' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token']),
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        // Check for pending orders
        $pending_orders = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "order WHERE order_status_id = 1");

        if ($pending_orders->row['count'] > 0) {
            $notifications[] = array(
                'id' => 2,
                'title' => 'Pending Orders',
                'message' => $pending_orders->row['count'] . ' orders are pending processing',
                'type' => 'system',
                'priority' => 'medium',
                'url' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token']),
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        return $notifications;
    }

    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);

        $units = array(
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ($units as $unit => $val) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $val . ($numberOfUnits > 1 ? 's' : '') . ' ago';
        }

        return 'just now';
    }

    /**
     * Get notification icon based on type
     */
    private function getNotificationIcon($type) {
        $icons = array(
            'system' => 'fa-cog',
            'user_activity' => 'fa-user',
            'document_approval' => 'fa-file-text',
            'document_uploaded' => 'fa-upload',
            'document_generated' => 'fa-file',
            'workflow_completed' => 'fa-check-circle',
            'workflow_pending' => 'fa-clock-o',
            'inventory_low' => 'fa-exclamation-triangle',
            'inventory_update' => 'fa-cubes',
            'message_received' => 'fa-envelope',
            'task_assigned' => 'fa-tasks',
            'approval_request' => 'fa-gavel',
            'backup_completed' => 'fa-database',
            'ai_analysis' => 'fa-brain'
        );

        return isset($icons[$type]) ? $icons[$type] : 'fa-bell';
    }

    /**
     * Get notification color based on priority
     */
    private function getNotificationColor($priority) {
        $colors = array(
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'danger',
            'urgent' => 'danger'
        );

        return isset($colors[$priority]) ? $colors[$priority] : 'info';
    }

    /**
     * Calculate SLA remaining time
     */
    private function calculateSLARemaining($created_at, $sla_hours) {
        $created_time = strtotime($created_at);
        $sla_deadline = $created_time + ($sla_hours * 3600);
        $remaining = $sla_deadline - time();

        if ($remaining <= 0) {
            return 'Overdue';
        }

        $hours = floor($remaining / 3600);
        $minutes = floor(($remaining % 3600) / 60);

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } else {
            return $minutes . 'm';
        }
    }

    /**
     * Get basic CPU usage for OpenCart 3.0.3.7
     */
    private function getBasicCPUUsage() {
        // Simple CPU usage estimation
        $load = sys_getloadavg();
        return isset($load[0]) ? round($load[0] * 20, 1) : 25.0;
    }

    /**
     * Get basic memory usage for OpenCart 3.0.3.7
     */
    private function getBasicMemoryUsage() {
        $memory_used = memory_get_usage(true);
        $memory_limit = ini_get('memory_limit');

        if ($memory_limit == -1) {
            return 15.0; // Default if unlimited
        }

        $memory_limit_bytes = $this->convertToBytes($memory_limit);
        return round(($memory_used / $memory_limit_bytes) * 100, 1);
    }

    /**
     * Get active users count
     */
    private function getActiveUsersCount() {
        $result = $this->db->query("SELECT COUNT(DISTINCT user_id) as count FROM " . DB_PREFIX . "user_online WHERE date_added > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        return isset($result->row['count']) ? (int)$result->row['count'] : 1;
    }

    /**
     * Convert memory limit to bytes
     */
    private function convertToBytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;

        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}