<?php
namespace Opencart\Admin\Controller\Notification;

class Center extends \Opencart\System\Engine\Controller {
    
    public function index() {
        $this->load->language('notification/center');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('notification/center', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $this->load->model('notification/center');
        
        // Get notification statistics
        $data['stats'] = $this->model_notification_center->getNotificationStats();
        
        // Get recent notifications
        $data['recent_notifications'] = $this->model_notification_center->getRecentNotifications(10);
        
        // Get notification types
        $data['notification_types'] = $this->model_notification_center->getNotificationTypes();
        
        // Get user notification preferences
        $data['preferences'] = $this->model_notification_center->getUserPreferences($this->user->getId());
        
        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('notification/center', $data));
    }
    
    public function getNotifications() {
        $this->load->language('notification/center');
        $this->load->model('notification/center');
        
        $filter_data = [
            'start' => $this->request->get['start'] ?? 0,
            'limit' => $this->request->get['limit'] ?? 20,
            'type' => $this->request->get['type'] ?? '',
            'status' => $this->request->get['status'] ?? '',
            'priority' => $this->request->get['priority'] ?? '',
            'date_from' => $this->request->get['date_from'] ?? '',
            'date_to' => $this->request->get['date_to'] ?? ''
        ];
        
        $notifications = $this->model_notification_center->getNotifications($filter_data);
        $total = $this->model_notification_center->getTotalNotifications($filter_data);
        
        $json = [
            'success' => true,
            'notifications' => $notifications,
            'total' => $total
        ];
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function markAsRead() {
        $this->load->language('notification/center');
        $this->load->model('notification/center');
        
        $json = [];
        
        if (isset($this->request->post['notification_id'])) {
            $notification_id = (int)$this->request->post['notification_id'];
            
            if ($this->model_notification_center->markAsRead($notification_id, $this->user->getId())) {
                $json['success'] = $this->language->get('text_notification_marked_read');
            } else {
                $json['error'] = $this->language->get('error_notification_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_notification_id_required');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function markAllAsRead() {
        $this->load->language('notification/center');
        $this->load->model('notification/center');
        
        $count = $this->model_notification_center->markAllAsRead($this->user->getId());
        
        $json = [
            'success' => true,
            'message' => sprintf($this->language->get('text_notifications_marked_read'), $count)
        ];
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function delete() {
        $this->load->language('notification/center');
        $this->load->model('notification/center');
        
        $json = [];
        
        if (isset($this->request->post['notification_id'])) {
            $notification_id = (int)$this->request->post['notification_id'];
            
            if ($this->model_notification_center->deleteNotification($notification_id, $this->user->getId())) {
                $json['success'] = $this->language->get('text_notification_deleted');
            } else {
                $json['error'] = $this->language->get('error_notification_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_notification_id_required');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function updatePreferences() {
        $this->load->language('notification/center');
        $this->load->model('notification/center');
        
        $json = [];
        
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $preferences = [
                'email_notifications' => isset($this->request->post['email_notifications']) ? 1 : 0,
                'sms_notifications' => isset($this->request->post['sms_notifications']) ? 1 : 0,
                'desktop_notifications' => isset($this->request->post['desktop_notifications']) ? 1 : 0,
                'sound_notifications' => isset($this->request->post['sound_notifications']) ? 1 : 0,
                'notification_types' => $this->request->post['notification_types'] ?? []
            ];
            
            if ($this->model_notification_center->updateUserPreferences($this->user->getId(), $preferences)) {
                $json['success'] = $this->language->get('text_preferences_updated');
            } else {
                $json['error'] = $this->language->get('error_preferences_update');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function send() {
        $this->load->language('notification/center');
        $this->load->model('notification/center');
        
        $json = [];
        
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $notification_data = [
                'title' => $this->request->post['title'] ?? '',
                'message' => $this->request->post['message'] ?? '',
                'type' => $this->request->post['type'] ?? 'info',
                'priority' => $this->request->post['priority'] ?? 'normal',
                'recipients' => $this->request->post['recipients'] ?? [],
                'send_email' => isset($this->request->post['send_email']) ? 1 : 0,
                'send_sms' => isset($this->request->post['send_sms']) ? 1 : 0,
                'scheduled_at' => $this->request->post['scheduled_at'] ?? null
            ];
            
            // Validate required fields
            if (empty($notification_data['title']) || empty($notification_data['message'])) {
                $json['error'] = $this->language->get('error_required_fields');
            } elseif (empty($notification_data['recipients'])) {
                $json['error'] = $this->language->get('error_no_recipients');
            } else {
                $notification_id = $this->model_notification_center->sendNotification($notification_data, $this->user->getId());
                
                if ($notification_id) {
                    $json['success'] = $this->language->get('text_notification_sent');
                    $json['notification_id'] = $notification_id;
                } else {
                    $json['error'] = $this->language->get('error_notification_send');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function getUnreadCount() {
        $this->load->model('notification/center');
        
        $count = $this->model_notification_center->getUnreadCount($this->user->getId());
        
        $json = [
            'success' => true,
            'count' => $count
        ];
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function archive() {
        $this->load->language('notification/center');
        $this->load->model('notification/center');
        
        $json = [];
        
        if (isset($this->request->post['notification_id'])) {
            $notification_id = (int)$this->request->post['notification_id'];
            
            if ($this->model_notification_center->archiveNotification($notification_id, $this->user->getId())) {
                $json['success'] = $this->language->get('text_notification_archived');
            } else {
                $json['error'] = $this->language->get('error_notification_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_notification_id_required');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function getTemplate() {
        $this->load->language('notification/center');
        $this->load->model('notification/center');
        
        $json = [];
        
        if (isset($this->request->get['template_id'])) {
            $template_id = (int)$this->request->get['template_id'];
            $template = $this->model_notification_center->getNotificationTemplate($template_id);
            
            if ($template) {
                $json['success'] = true;
                $json['template'] = $template;
            } else {
                $json['error'] = $this->language->get('error_template_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_template_id_required');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
