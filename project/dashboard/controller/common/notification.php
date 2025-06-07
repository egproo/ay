<?php
class ControllerCommonNotification extends Controller {
    public function index() {
        $this->load->language('common/notification');
        
        $this->load->model('common/notification');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        // Get notifications
        $start = isset($this->request->get['start']) ? (int)$this->request->get['start'] : 0;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;
        $filter = isset($this->request->get['filter']) ? $this->request->get['filter'] : '';
        
        $notifications = $this->model_common_notification->getNotifications($this->user->getId(), $start, $limit, $filter);
        
        $json['notifications'] = array();
        foreach ($notifications as $notification) {
            $json['notifications'][] = array(
                'notification_id' => $notification['notification_id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'icon' => $notification['icon'],
                'color' => $notification['color'],
                'category' => $notification['category_name'],
                'reference_type' => $notification['reference_type'],
                'reference_id' => $notification['reference_id'],
                'is_read' => (bool)$notification['is_read'],
                'read_at' => $notification['read_at'] ? date($this->language->get('datetime_format'), strtotime($notification['read_at'])) : '',
                'created_at' => date($this->language->get('datetime_format'), strtotime($notification['created_at'])),
                'relative_time' => $this->getRelativeTime($notification['created_at'])
            );
        }
        
        $json['unread_count'] = $this->model_common_notification->getUnreadCount($this->user->getId());
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function markAsRead() {
        $this->load->language('common/notification');
        
        $this->load->model('common/notification');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['notification_id'])) {
            $this->model_common_notification->markAsRead($this->request->post['notification_id'], $this->user->getId());
            $json['success'] = $this->language->get('text_marked_read');
            $json['unread_count'] = $this->model_common_notification->getUnreadCount($this->user->getId());
        } else {
            $json['error'] = $this->language->get('error_notification');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function markAllAsRead() {
        $this->load->language('common/notification');
        
        $this->load->model('common/notification');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $this->model_common_notification->markAllAsRead($this->user->getId());
        $json['success'] = $this->language->get('text_all_marked_read');
        $json['unread_count'] = 0;
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function hide() {
        $this->load->language('common/notification');
        
        $this->load->model('common/notification');
        
        $json = array();
        
        if (!$this->user->isLogged()) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['notification_id'])) {
            $this->model_common_notification->hideNotification($this->request->post['notification_id'], $this->user->getId());
            $json['success'] = $this->language->get('text_notification_hidden');
        } else {
            $json['error'] = $this->language->get('error_notification');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function add() {
        $this->load->language('common/notification');
        
        $this->load->model('common/notification');
        
        $json = array();
        
        if (!$this->user->isLogged() || !$this->user->hasPermission('modify', 'common/notification')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['title']) && isset($this->request->post['message'])) {
            $data = array(
                'title' => $this->request->post['title'],
                'message' => $this->request->post['message'],
                'icon' => isset($this->request->post['icon']) ? $this->request->post['icon'] : '',
                'color' => isset($this->request->post['color']) ? $this->request->post['color'] : 'primary',
                'category_id' => isset($this->request->post['category_id']) ? (int)$this->request->post['category_id'] : null,
                'reference_type' => isset($this->request->post['reference_type']) ? $this->request->post['reference_type'] : null,
                'reference_id' => isset($this->request->post['reference_id']) ? (int)$this->request->post['reference_id'] : null,
                'user_ids' => isset($this->request->post['user_ids']) ? $this->request->post['user_ids'] : array(),
                'is_automated' => isset($this->request->post['is_automated']) ? (bool)$this->request->post['is_automated'] : false
            );
            
            $notification_id = $this->model_common_notification->addNotification($data);
            
            if ($notification_id) {
                $json['success'] = $this->language->get('text_notification_added');
                $json['notification_id'] = $notification_id;
            } else {
                $json['error'] = $this->language->get('error_adding_notification');
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function clean() {
        $this->load->language('common/notification');
        
        $this->load->model('common/notification');
        
        $json = array();
        
        if (!$this->user->isLogged() || !$this->user->hasPermission('modify', 'common/notification')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $days = isset($this->request->post['days']) ? (int)$this->request->post['days'] : 30;
        
        $cleaned = $this->model_common_notification->cleanOldNotifications($days);
        
        $json['success'] = sprintf($this->language->get('text_notifications_cleaned'), $days);
        $json['cleaned_count'] = $cleaned;
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    private function getRelativeTime($timestamp) {
        $now = time();
        $diff = $now - strtotime($timestamp);
        
        if ($diff < 60) {
            return $this->language->get('text_just_now');
        }
        
        if ($diff < 3600) {
            $mins = floor($diff / 60);
            return sprintf($this->language->get('text_minutes_ago'), $mins);
        }
        
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return sprintf($this->language->get('text_hours_ago'), $hours);
        }
        
        if ($diff < 172800) {
            return $this->language->get('text_yesterday');
        }
        
        $days = floor($diff / 86400);
        return sprintf($this->language->get('text_days_ago'), $days);
    }
}