<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 * 
 * Controller: Dashboard KPIs (Key Performance Indicators)
 * الهدف: عرض وإدارة مؤشرات الأداء الرئيسية للشركة
 * المستخدمين: الإدارة العليا، مدراء الأقسام، المحللين
 */
class ControllerDashboardKpi extends Controller {
    
    private $error = array();
    
    /**
     * Main KPI Dashboard page
     */
    public function index() {
        // Load language file
        $this->load->language('dashboard/kpi');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title'));
        
        // Load model
        $this->load->model('dashboard/kpi');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/kpi')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Handle AJAX requests for real-time updates
        if (isset($this->request->get['ajax']) && $this->request->get['ajax'] == '1') {
            $this->getKpiData();
            return;
        }
        
        // Get KPI data
        $data['kpis'] = $this->model_dashboard_kpi->getKpiValues();
        
        // Prepare data for view
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('dashboard/kpi', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Set common template data
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        // URLs for actions
        $data['refresh_url'] = $this->url->link('dashboard/kpi/refresh', 'user_token=' . $this->session->data['user_token'], true);
        $data['settings_url'] = $this->url->link('dashboard/kpi/settings', 'user_token=' . $this->session->data['user_token'], true);
        
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
        $this->response->setOutput($this->load->view('dashboard/kpi', $data));
    }
    
    /**
     * AJAX method to get real-time KPI data
     */
    public function getKpiData() {
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/kpi')) {
            $json['error'] = 'Permission denied';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $this->load->model('dashboard/kpi');
        
        // Get specific KPIs if requested
        $kpi_codes = array();
        if (isset($this->request->get['kpis'])) {
            $kpi_codes = explode(',', $this->request->get['kpis']);
        }
        
        $json['kpis'] = $this->model_dashboard_kpi->getKpiValues($kpi_codes);
        $json['success'] = true;
        $json['timestamp'] = date('Y-m-d H:i:s');
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Refresh all KPIs
     */
    public function refresh() {
        // Load language file
        $this->load->language('dashboard/kpi');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/kpi')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('dashboard/kpi', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->load->model('dashboard/kpi');
        
        try {
            // Process all KPIs
            $this->model_dashboard_kpi->processAllKpis();
            
            $this->session->data['success'] = $this->language->get('text_refresh_success');
        } catch (Exception $e) {
            $this->session->data['error'] = $this->language->get('error_refresh_failed') . ': ' . $e->getMessage();
        }
        
        $this->response->redirect($this->url->link('dashboard/kpi', 'user_token=' . $this->session->data['user_token'], true));
    }
    
    /**
     * KPI Settings page
     */
    public function settings() {
        // Load language file
        $this->load->language('dashboard/kpi');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title_settings'));
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/kpi')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('dashboard/kpi', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Handle form submission
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateSettings()) {
            $this->load->model('setting/setting');
            
            // Save KPI settings
            $this->model_setting_setting->editSetting('dashboard_kpi', $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_settings_success');
            $this->response->redirect($this->url->link('dashboard/kpi', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Prepare data for view
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('dashboard/kpi', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_settings'),
            'href' => $this->url->link('dashboard/kpi/settings', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Load current settings
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('dashboard_kpi');
        
        // Set default values
        $data['dashboard_kpi_auto_refresh'] = isset($settings['dashboard_kpi_auto_refresh']) ? $settings['dashboard_kpi_auto_refresh'] : 1;
        $data['dashboard_kpi_refresh_interval'] = isset($settings['dashboard_kpi_refresh_interval']) ? $settings['dashboard_kpi_refresh_interval'] : 300; // 5 minutes
        $data['dashboard_kpi_cache_enabled'] = isset($settings['dashboard_kpi_cache_enabled']) ? $settings['dashboard_kpi_cache_enabled'] : 1;
        
        // Error handling
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        
        // Set common template data
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        // Form action URL
        $data['action'] = $this->url->link('dashboard/kpi/settings', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('dashboard/kpi', 'user_token=' . $this->session->data['user_token'], true);
        
        // Load view
        $this->response->setOutput($this->load->view('dashboard/kpi_settings', $data));
    }
    
    /**
     * Validate settings form
     */
    private function validateSettings() {
        if (!$this->user->hasPermission('modify', 'dashboard/kpi')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        // Validate refresh interval
        if (isset($this->request->post['dashboard_kpi_refresh_interval'])) {
            $interval = (int)$this->request->post['dashboard_kpi_refresh_interval'];
            if ($interval < 60 || $interval > 3600) {
                $this->error['refresh_interval'] = $this->language->get('error_refresh_interval');
            }
        }
        
        return !$this->error;
    }
    
    /**
     * Export KPI data
     */
    public function export() {
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/kpi')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('dashboard/kpi', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->load->model('dashboard/kpi');
        
        // Get all KPI data
        $kpis = $this->model_dashboard_kpi->getKpiValues();
        
        // Prepare CSV data
        $csv_data = array();
        $csv_data[] = array('KPI Name', 'Current Value', 'Previous Value', 'Trend %', 'Date Range', 'Last Updated');
        
        foreach ($kpis as $kpi) {
            $csv_data[] = array(
                $kpi['name'],
                $kpi['value'],
                $kpi['previous_value'],
                $kpi['trend'],
                $kpi['date_range'],
                date('Y-m-d H:i:s')
            );
        }
        
        // Set headers for CSV download
        $filename = 'kpi_export_' . date('Y-m-d_H-i-s') . '.csv';
        $this->response->addheader('Content-Type: application/csv');
        $this->response->addheader('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Output CSV
        $output = '';
        foreach ($csv_data as $row) {
            $output .= '"' . implode('","', $row) . '"' . "\n";
        }
        
        $this->response->setOutput($output);
    }
}
