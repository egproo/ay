<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 * 
 * Controller: Dashboard Goals (متابعة الأهداف)
 * الهدف: إدارة ومتابعة أهداف الشركة والأقسام والموظفين
 * المستخدمين: الإدارة العليا، مدراء الأقسام، المدراء التنفيذيين
 */
class ControllerDashboardGoals extends Controller {
    
    private $error = array();
    
    /**
     * Main Goals Dashboard page
     */
    public function index() {
        // Load language file
        $this->load->language('dashboard/goals');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title'));
        
        // Load model
        $this->load->model('dashboard/goals');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/goals')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Handle AJAX requests
        if (isset($this->request->get['ajax']) && $this->request->get['ajax'] == '1') {
            $this->getGoalsData();
            return;
        }
        
        // Get filter parameters
        $filter_data = array(
            'filter_period' => isset($this->request->get['filter_period']) ? $this->request->get['filter_period'] : 'current_month',
            'filter_department' => isset($this->request->get['filter_department']) ? $this->request->get['filter_department'] : '',
            'filter_status' => isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '',
            'start' => 0,
            'limit' => 20
        );
        
        // Get goals data
        $data['goals'] = $this->model_dashboard_goals->getGoals($filter_data);
        $data['goals_summary'] = $this->model_dashboard_goals->getGoalsSummary($filter_data);
        
        // Get departments for filter
        $this->load->model('user/user_group');
        $data['departments'] = $this->model_user_user_group->getUserGroups();
        
        // Prepare data for view
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('dashboard/goals', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Set common template data
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        // URLs for actions
        $data['add_url'] = $this->url->link('dashboard/goals/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['refresh_url'] = $this->url->link('dashboard/goals/refresh', 'user_token=' . $this->session->data['user_token'], true);
        
        // Filter URLs
        $data['filter_period'] = $filter_data['filter_period'];
        $data['filter_department'] = $filter_data['filter_department'];
        $data['filter_status'] = $filter_data['filter_status'];
        
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
        $this->response->setOutput($this->load->view('dashboard/goals', $data));
    }
    
    /**
     * Add new goal
     */
    public function add() {
        // Load language file
        $this->load->language('dashboard/goals');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title_add'));
        
        // Load model
        $this->load->model('dashboard/goals');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/goals')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('dashboard/goals', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Handle form submission
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $goal_id = $this->model_dashboard_goals->addGoal($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success_add');
            $this->response->redirect($this->url->link('dashboard/goals', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Prepare form data
        $this->getForm();
    }
    
    /**
     * Edit existing goal
     */
    public function edit() {
        // Load language file
        $this->load->language('dashboard/goals');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title_edit'));
        
        // Load model
        $this->load->model('dashboard/goals');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/goals')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('dashboard/goals', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Get goal ID
        $goal_id = isset($this->request->get['goal_id']) ? (int)$this->request->get['goal_id'] : 0;
        
        // Handle form submission
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_dashboard_goals->editGoal($goal_id, $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success_edit');
            $this->response->redirect($this->url->link('dashboard/goals', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Prepare form data
        $this->getForm();
    }
    
    /**
     * Delete goal
     */
    public function delete() {
        // Load language file
        $this->load->language('dashboard/goals');
        
        // Load model
        $this->load->model('dashboard/goals');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/goals')) {
            $this->session->data['error'] = $this->language->get('error_permission');
        } else {
            $goal_id = isset($this->request->get['goal_id']) ? (int)$this->request->get['goal_id'] : 0;
            
            if ($goal_id) {
                $this->model_dashboard_goals->deleteGoal($goal_id);
                $this->session->data['success'] = $this->language->get('text_success_delete');
            }
        }
        
        $this->response->redirect($this->url->link('dashboard/goals', 'user_token=' . $this->session->data['user_token'], true));
    }
    
    /**
     * Update goal progress
     */
    public function updateProgress() {
        // Load language file
        $this->load->language('dashboard/goals');
        
        // Load model
        $this->load->model('dashboard/goals');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/goals')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $goal_id = isset($this->request->post['goal_id']) ? (int)$this->request->post['goal_id'] : 0;
            $current_value = isset($this->request->post['current_value']) ? (float)$this->request->post['current_value'] : 0;
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';
            
            if ($goal_id) {
                $this->model_dashboard_goals->updateGoalProgress($goal_id, $current_value, $notes);
                $json['success'] = $this->language->get('text_progress_updated');
            } else {
                $json['error'] = $this->language->get('error_goal_not_found');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * AJAX method to get goals data
     */
    public function getGoalsData() {
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/goals')) {
            $json['error'] = 'Permission denied';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $this->load->model('dashboard/goals');
        
        // Get filter parameters
        $filter_data = array(
            'filter_period' => isset($this->request->get['filter_period']) ? $this->request->get['filter_period'] : 'current_month',
            'filter_department' => isset($this->request->get['filter_department']) ? $this->request->get['filter_department'] : '',
            'filter_status' => isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : ''
        );
        
        $json['goals'] = $this->model_dashboard_goals->getGoals($filter_data);
        $json['summary'] = $this->model_dashboard_goals->getGoalsSummary($filter_data);
        $json['success'] = true;
        $json['timestamp'] = date('Y-m-d H:i:s');
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Prepare form data for add/edit
     */
    private function getForm() {
        // Get goal data if editing
        $goal_id = isset($this->request->get['goal_id']) ? (int)$this->request->get['goal_id'] : 0;
        
        if ($goal_id && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $goal_info = $this->model_dashboard_goals->getGoal($goal_id);
            
            if ($goal_info) {
                $data = array_merge($data ?? [], $goal_info);
            } else {
                $this->session->data['error'] = $this->language->get('error_goal_not_found');
                $this->response->redirect($this->url->link('dashboard/goals', 'user_token=' . $this->session->data['user_token'], true));
            }
        }
        
        // Set default values
        $data['goal_title'] = isset($this->request->post['goal_title']) ? $this->request->post['goal_title'] : (isset($data['goal_title']) ? $data['goal_title'] : '');
        $data['goal_description'] = isset($this->request->post['goal_description']) ? $this->request->post['goal_description'] : (isset($data['goal_description']) ? $data['goal_description'] : '');
        $data['goal_type'] = isset($this->request->post['goal_type']) ? $this->request->post['goal_type'] : (isset($data['goal_type']) ? $data['goal_type'] : 'sales');
        $data['target_value'] = isset($this->request->post['target_value']) ? $this->request->post['target_value'] : (isset($data['target_value']) ? $data['target_value'] : '');
        $data['current_value'] = isset($this->request->post['current_value']) ? $this->request->post['current_value'] : (isset($data['current_value']) ? $data['current_value'] : '0');
        $data['start_date'] = isset($this->request->post['start_date']) ? $this->request->post['start_date'] : (isset($data['start_date']) ? $data['start_date'] : date('Y-m-d'));
        $data['end_date'] = isset($this->request->post['end_date']) ? $this->request->post['end_date'] : (isset($data['end_date']) ? $data['end_date'] : date('Y-m-d', strtotime('+1 month')));
        $data['assigned_to'] = isset($this->request->post['assigned_to']) ? $this->request->post['assigned_to'] : (isset($data['assigned_to']) ? $data['assigned_to'] : $this->user->getId());
        $data['department_id'] = isset($this->request->post['department_id']) ? $this->request->post['department_id'] : (isset($data['department_id']) ? $data['department_id'] : '');
        $data['priority'] = isset($this->request->post['priority']) ? $this->request->post['priority'] : (isset($data['priority']) ? $data['priority'] : 'medium');
        $data['status'] = isset($this->request->post['status']) ? $this->request->post['status'] : (isset($data['status']) ? $data['status'] : 'active');
        
        // Error handling
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_title'] = isset($this->error['title']) ? $this->error['title'] : '';
        $data['error_target_value'] = isset($this->error['target_value']) ? $this->error['target_value'] : '';
        $data['error_end_date'] = isset($this->error['end_date']) ? $this->error['end_date'] : '';
        
        // Breadcrumbs
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('dashboard/goals', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        if ($goal_id) {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title_edit'),
                'href' => $this->url->link('dashboard/goals/edit', 'user_token=' . $this->session->data['user_token'] . '&goal_id=' . $goal_id, true)
            );
        } else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title_add'),
                'href' => $this->url->link('dashboard/goals/add', 'user_token=' . $this->session->data['user_token'], true)
            );
        }
        
        // Get users for assignment
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();
        
        // Get departments
        $this->load->model('user/user_group');
        $data['departments'] = $this->model_user_user_group->getUserGroups();
        
        // Set common template data
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        // Form action URL
        if ($goal_id) {
            $data['action'] = $this->url->link('dashboard/goals/edit', 'user_token=' . $this->session->data['user_token'] . '&goal_id=' . $goal_id, true);
        } else {
            $data['action'] = $this->url->link('dashboard/goals/add', 'user_token=' . $this->session->data['user_token'], true);
        }
        
        $data['cancel'] = $this->url->link('dashboard/goals', 'user_token=' . $this->session->data['user_token'], true);
        
        // Load view
        $this->response->setOutput($this->load->view('dashboard/goals_form', $data));
    }
    
    /**
     * Validate form data
     */
    private function validateForm() {
        if (!$this->user->hasPermission('modify', 'dashboard/goals')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen($this->request->post['goal_title']) < 3) || (utf8_strlen($this->request->post['goal_title']) > 255)) {
            $this->error['title'] = $this->language->get('error_title');
        }
        
        if (!$this->request->post['target_value'] || !is_numeric($this->request->post['target_value'])) {
            $this->error['target_value'] = $this->language->get('error_target_value');
        }
        
        if (strtotime($this->request->post['end_date']) <= strtotime($this->request->post['start_date'])) {
            $this->error['end_date'] = $this->language->get('error_end_date');
        }
        
        return !$this->error;
    }
}
