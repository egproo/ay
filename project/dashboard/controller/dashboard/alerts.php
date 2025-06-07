<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 * 
 * Controller: Dashboard Alerts (لوحة التنبيهات والإنذارات)
 * الهدف: إدارة ومتابعة التنبيهات والإنذارات المختلفة في النظام
 * المستخدمين: جميع المستخدمين، الإدارة، مدراء الأقسام
 */
class ControllerDashboardAlerts extends Controller {
    
    private $error = array();
    
    /**
     * Main Alerts Dashboard page
     */
    public function index() {
        // Load language file
        $this->load->language('dashboard/alerts');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title'));
        
        // Load model
        $this->load->model('dashboard/alerts');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/alerts')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Handle AJAX requests
        if (isset($this->request->get['ajax']) && $this->request->get['ajax'] == '1') {
            $this->getAlertsData();
            return;
        }
        
        // Get filter parameters
        $filter_data = array(
            'filter_type' => isset($this->request->get['filter_type']) ? $this->request->get['filter_type'] : '',
            'filter_priority' => isset($this->request->get['filter_priority']) ? $this->request->get['filter_priority'] : '',
            'filter_status' => isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : 'unread',
            'filter_date_from' => isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : '',
            'filter_date_to' => isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : '',
            'start' => 0,
            'limit' => 50
        );
        
        // Get alerts data
        $data['alerts'] = $this->model_dashboard_alerts->getAlerts($filter_data);
        $data['alerts_summary'] = $this->model_dashboard_alerts->getAlertsSummary();
        
        // Get system alerts (real-time)
        $data['system_alerts'] = $this->model_dashboard_alerts->getSystemAlerts();
        
        // Prepare data for view
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('dashboard/alerts', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Set common template data
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        // URLs for actions
        $data['refresh_url'] = $this->url->link('dashboard/alerts/refresh', 'user_token=' . $this->session->data['user_token'], true);
        $data['settings_url'] = $this->url->link('dashboard/alerts/settings', 'user_token=' . $this->session->data['user_token'], true);
        
        // Filter values
        $data['filter_type'] = $filter_data['filter_type'];
        $data['filter_priority'] = $filter_data['filter_priority'];
        $data['filter_status'] = $filter_data['filter_status'];
        $data['filter_date_from'] = $filter_data['filter_date_from'];
        $data['filter_date_to'] = $filter_data['filter_date_to'];
        
        // User token for AJAX requests
        $data['user_token'] = $this->session->data['user_token'];
        
        // Success/Error messages
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        
        if (isset($this->session->data['error'])) {
            $data['error'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }
        
        // Load view
        $this->response->setOutput($this->load->view('dashboard/alerts', $data));
    }
    
    /**
     * Mark alert as read
     */
    public function markAsRead() {
        // Load language file
        $this->load->language('dashboard/alerts');
        
        // Load model
        $this->load->model('dashboard/alerts');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/alerts')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $alert_id = isset($this->request->post['alert_id']) ? (int)$this->request->post['alert_id'] : 0;
            
            if ($alert_id) {
                $this->model_dashboard_alerts->markAsRead($alert_id, $this->user->getId());
                $json['success'] = $this->language->get('text_marked_as_read');
            } else {
                $json['error'] = $this->language->get('error_alert_not_found');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Mark all alerts as read
     */
    public function markAllAsRead() {
        // Load language file
        $this->load->language('dashboard/alerts');
        
        // Load model
        $this->load->model('dashboard/alerts');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/alerts')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->model_dashboard_alerts->markAllAsRead($this->user->getId());
            $json['success'] = $this->language->get('text_all_marked_as_read');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Dismiss alert
     */
    public function dismiss() {
        // Load language file
        $this->load->language('dashboard/alerts');
        
        // Load model
        $this->load->model('dashboard/alerts');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/alerts')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $alert_id = isset($this->request->post['alert_id']) ? (int)$this->request->post['alert_id'] : 0;
            
            if ($alert_id) {
                $this->model_dashboard_alerts->dismissAlert($alert_id, $this->user->getId());
                $json['success'] = $this->language->get('text_alert_dismissed');
            } else {
                $json['error'] = $this->language->get('error_alert_not_found');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Create custom alert
     */
    public function create() {
        // Load language file
        $this->load->language('dashboard/alerts');
        
        // Load model
        $this->load->model('dashboard/alerts');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/alerts')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $alert_data = array(
                'alert_type' => isset($this->request->post['alert_type']) ? $this->request->post['alert_type'] : 'custom',
                'title' => isset($this->request->post['title']) ? $this->request->post['title'] : '',
                'message' => isset($this->request->post['message']) ? $this->request->post['message'] : '',
                'priority' => isset($this->request->post['priority']) ? $this->request->post['priority'] : 'medium',
                'target_users' => isset($this->request->post['target_users']) ? $this->request->post['target_users'] : array(),
                'expires_at' => isset($this->request->post['expires_at']) ? $this->request->post['expires_at'] : null
            );
            
            if ($this->validateAlert($alert_data)) {
                $alert_id = $this->model_dashboard_alerts->createAlert($alert_data, $this->user->getId());
                $json['success'] = $this->language->get('text_alert_created');
                $json['alert_id'] = $alert_id;
            } else {
                $json['error'] = $this->language->get('error_validation_failed');
                $json['errors'] = $this->error;
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Refresh system alerts
     */
    public function refresh() {
        // Load language file
        $this->load->language('dashboard/alerts');
        
        // Load model
        $this->load->model('dashboard/alerts');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/alerts')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('dashboard/alerts', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        try {
            // Generate system alerts
            $this->model_dashboard_alerts->generateSystemAlerts();
            
            $this->session->data['success'] = $this->language->get('text_refresh_success');
        } catch (Exception $e) {
            $this->session->data['error'] = $this->language->get('error_refresh_failed') . ': ' . $e->getMessage();
        }
        
        $this->response->redirect($this->url->link('dashboard/alerts', 'user_token=' . $this->session->data['user_token'], true));
    }
    
    /**
     * AJAX method to get alerts data
     */
    public function getAlertsData() {
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/alerts')) {
            $json['error'] = 'Permission denied';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $this->load->model('dashboard/alerts');
        
        // Get filter parameters
        $filter_data = array(
            'filter_type' => isset($this->request->get['filter_type']) ? $this->request->get['filter_type'] : '',
            'filter_priority' => isset($this->request->get['filter_priority']) ? $this->request->get['filter_priority'] : '',
            'filter_status' => isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '',
            'user_id' => $this->user->getId()
        );
        
        $json['alerts'] = $this->model_dashboard_alerts->getAlerts($filter_data);
        $json['summary'] = $this->model_dashboard_alerts->getAlertsSummary();
        $json['system_alerts'] = $this->model_dashboard_alerts->getSystemAlerts();
        $json['success'] = true;
        $json['timestamp'] = date('Y-m-d H:i:s');
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Get unread alerts count for header notification
     */
    public function getUnreadCount() {
        $this->load->model('dashboard/alerts');
        
        $count = $this->model_dashboard_alerts->getUnreadCount($this->user->getId());
        
        $json['count'] = $count;
        $json['success'] = true;
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Alert settings page
     */
    public function settings() {
        // Load language file
        $this->load->language('dashboard/alerts');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title_settings'));
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/alerts')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('dashboard/alerts', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Handle form submission
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateSettings()) {
            $this->load->model('setting/setting');
            
            // Save alert settings
            $this->model_setting_setting->editSetting('dashboard_alerts', $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_settings_success');
            $this->response->redirect($this->url->link('dashboard/alerts', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Prepare data for view
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('dashboard/alerts', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_settings'),
            'href' => $this->url->link('dashboard/alerts/settings', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Load current settings
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('dashboard_alerts');
        
        // Set default values
        $data['dashboard_alerts_enabled'] = isset($settings['dashboard_alerts_enabled']) ? $settings['dashboard_alerts_enabled'] : 1;
        $data['dashboard_alerts_auto_refresh'] = isset($settings['dashboard_alerts_auto_refresh']) ? $settings['dashboard_alerts_auto_refresh'] : 1;
        $data['dashboard_alerts_refresh_interval'] = isset($settings['dashboard_alerts_refresh_interval']) ? $settings['dashboard_alerts_refresh_interval'] : 60;
        $data['dashboard_alerts_sound_enabled'] = isset($settings['dashboard_alerts_sound_enabled']) ? $settings['dashboard_alerts_sound_enabled'] : 1;
        $data['dashboard_alerts_email_enabled'] = isset($settings['dashboard_alerts_email_enabled']) ? $settings['dashboard_alerts_email_enabled'] : 0;
        
        // Error handling
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        
        // Set common template data
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        // Form action URL
        $data['action'] = $this->url->link('dashboard/alerts/settings', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('dashboard/alerts', 'user_token=' . $this->session->data['user_token'], true);
        
        // Load view
        $this->response->setOutput($this->load->view('dashboard/alerts_settings', $data));
    }
    
    /**
     * Validate alert data
     */
    private function validateAlert($data) {
        if (empty($data['title']) || utf8_strlen($data['title']) < 3) {
            $this->error['title'] = $this->language->get('error_title');
        }
        
        if (empty($data['message']) || utf8_strlen($data['message']) < 10) {
            $this->error['message'] = $this->language->get('error_message');
        }
        
        return !$this->error;
    }
    
    /**
     * Validate settings form
     */
    private function validateSettings() {
        if (!$this->user->hasPermission('modify', 'dashboard/alerts')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        // Validate refresh interval
        if (isset($this->request->post['dashboard_alerts_refresh_interval'])) {
            $interval = (int)$this->request->post['dashboard_alerts_refresh_interval'];
            if ($interval < 30 || $interval > 3600) {
                $this->error['refresh_interval'] = $this->language->get('error_refresh_interval');
            }
        }
        
        return !$this->error;
    }
}
