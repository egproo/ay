<?php
/**
 * Profitability Analysis Dashboard Controller
 * 
 * Provides comprehensive profitability analysis including
 * product profitability, customer profitability, branch performance,
 * and trend analysis for strategic decision making
 */
class ControllerDashboardProfitability extends Controller {
    private $error = array();

    /**
     * Main Profitability Analysis Dashboard page
     */
    public function index() {
        // Load language file
        $this->load->language('dashboard/profitability');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title'));
        
        // Load models
        $this->load->model('dashboard/profitability');
        $this->load->model('reports/profitability_analysis');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/profitability')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Handle AJAX requests
        if (isset($this->request->get['ajax']) && $this->request->get['ajax'] == '1') {
            $this->getAnalyticsData();
            return;
        }
        
        // Get filter parameters
        $filter_data = array(
            'filter_branch_id' => isset($this->request->get['filter_branch_id']) ? (int)$this->request->get['filter_branch_id'] : 0,
            'filter_category_id' => isset($this->request->get['filter_category_id']) ? (int)$this->request->get['filter_category_id'] : 0,
            'filter_customer_group_id' => isset($this->request->get['filter_customer_group_id']) ? (int)$this->request->get['filter_customer_group_id'] : 0,
            'filter_date_from' => isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : date('Y-m-01'),
            'filter_date_to' => isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : date('Y-m-d'),
            'filter_period' => isset($this->request->get['filter_period']) ? $this->request->get['filter_period'] : 'month',
            'filter_analysis_type' => isset($this->request->get['filter_analysis_type']) ? $this->request->get['filter_analysis_type'] : 'overview'
        );
        
        // Get profitability data
        $data['profitability_summary'] = $this->model_dashboard_profitability->getProfitabilitySummary($filter_data);
        $data['product_profitability'] = $this->model_reports_profitability_analysis->getProductProfitability($filter_data['filter_date_from'], $filter_data['filter_date_to']);
        $data['customer_profitability'] = $this->model_reports_profitability_analysis->getCustomerProfitability($filter_data['filter_date_from'], $filter_data['filter_date_to']);
        $data['category_profitability'] = $this->model_reports_profitability_analysis->getCategoryProfitability($filter_data['filter_date_from'], $filter_data['filter_date_to']);
        $data['branch_profitability'] = $this->model_reports_profitability_analysis->getLocationProfitability($filter_data['filter_date_from'], $filter_data['filter_date_to']);
        $data['profitability_trends'] = $this->model_dashboard_profitability->getProfitabilityTrends($filter_data);
        $data['margin_analysis'] = $this->model_dashboard_profitability->getMarginAnalysis($filter_data);
        
        // Get filter options
        $this->load->model('branch/branch');
        $this->load->model('catalog/category');
        $this->load->model('customer/customer_group');
        
        $data['branches'] = $this->model_branch_branch->getBranches();
        $data['categories'] = $this->model_catalog_category->getCategories();
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
        
        // Set filter values
        $data['filter_branch_id'] = $filter_data['filter_branch_id'];
        $data['filter_category_id'] = $filter_data['filter_category_id'];
        $data['filter_customer_group_id'] = $filter_data['filter_customer_group_id'];
        $data['filter_date_from'] = $filter_data['filter_date_from'];
        $data['filter_date_to'] = $filter_data['filter_date_to'];
        $data['filter_period'] = $filter_data['filter_period'];
        $data['filter_analysis_type'] = $filter_data['filter_analysis_type'];
        
        // Error handling
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        // Success message
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        // Breadcrumbs
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_dashboards'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('dashboard/profitability', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Links
        $data['refresh'] = $this->url->link('dashboard/profitability', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('dashboard/profitability/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['detailed_report'] = $this->url->link('reports/profitability_analysis', 'user_token=' . $this->session->data['user_token'], true);
        
        // User token
        $data['user_token'] = $this->session->data['user_token'];
        
        // Load header, footer and render template
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('dashboard/profitability', $data));
    }
    
    /**
     * Get analytics data via AJAX
     */
    public function getAnalyticsData() {
        $this->load->language('dashboard/profitability');
        $this->load->model('dashboard/profitability');
        $this->load->model('reports/profitability_analysis');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/profitability')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $json = array();
        
        try {
            // Get filter parameters
            $filter_data = array(
                'filter_branch_id' => isset($this->request->post['branch_id']) ? (int)$this->request->post['branch_id'] : 0,
                'filter_category_id' => isset($this->request->post['category_id']) ? (int)$this->request->post['category_id'] : 0,
                'filter_customer_group_id' => isset($this->request->post['customer_group_id']) ? (int)$this->request->post['customer_group_id'] : 0,
                'filter_date_from' => isset($this->request->post['date_from']) ? $this->request->post['date_from'] : date('Y-m-01'),
                'filter_date_to' => isset($this->request->post['date_to']) ? $this->request->post['date_to'] : date('Y-m-d'),
                'filter_period' => isset($this->request->post['period']) ? $this->request->post['period'] : 'month',
                'filter_analysis_type' => isset($this->request->post['analysis_type']) ? $this->request->post['analysis_type'] : 'overview'
            );
            
            // Get requested data type
            $data_type = isset($this->request->post['data_type']) ? $this->request->post['data_type'] : 'summary';
            
            switch ($data_type) {
                case 'summary':
                    $json['data'] = $this->model_dashboard_profitability->getProfitabilitySummary($filter_data);
                    break;
                case 'product_profitability':
                    $json['data'] = $this->model_reports_profitability_analysis->getProductProfitability($filter_data['filter_date_from'], $filter_data['filter_date_to']);
                    break;
                case 'customer_profitability':
                    $json['data'] = $this->model_reports_profitability_analysis->getCustomerProfitability($filter_data['filter_date_from'], $filter_data['filter_date_to']);
                    break;
                case 'category_profitability':
                    $json['data'] = $this->model_reports_profitability_analysis->getCategoryProfitability($filter_data['filter_date_from'], $filter_data['filter_date_to']);
                    break;
                case 'branch_profitability':
                    $json['data'] = $this->model_reports_profitability_analysis->getLocationProfitability($filter_data['filter_date_from'], $filter_data['filter_date_to']);
                    break;
                case 'trends':
                    $json['data'] = $this->model_dashboard_profitability->getProfitabilityTrends($filter_data);
                    break;
                case 'margin_analysis':
                    $json['data'] = $this->model_dashboard_profitability->getMarginAnalysis($filter_data);
                    break;
                default:
                    $json['error'] = $this->language->get('error_invalid_request');
            }
            
            $json['success'] = true;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Export profitability data
     */
    public function export() {
        $this->load->language('dashboard/profitability');
        $this->load->model('dashboard/profitability');
        $this->load->model('reports/profitability_analysis');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/profitability')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('dashboard/profitability', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Get filter parameters
        $filter_data = array(
            'filter_branch_id' => isset($this->request->get['filter_branch_id']) ? (int)$this->request->get['filter_branch_id'] : 0,
            'filter_category_id' => isset($this->request->get['filter_category_id']) ? (int)$this->request->get['filter_category_id'] : 0,
            'filter_customer_group_id' => isset($this->request->get['filter_customer_group_id']) ? (int)$this->request->get['filter_customer_group_id'] : 0,
            'filter_date_from' => isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : date('Y-m-01'),
            'filter_date_to' => isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : date('Y-m-d')
        );
        
        // Get export data
        $export_data = $this->model_dashboard_profitability->getExportData($filter_data);
        
        // Generate Excel file
        $this->load->library('excel');
        
        $filename = 'profitability_analysis_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $this->excel->generateProfitabilityReport($export_data, $filename);
    }
    
    /**
     * Get top performing products
     */
    public function getTopProducts() {
        $this->load->language('dashboard/profitability');
        $this->load->model('dashboard/profitability');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/profitability')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $json = array();
        
        try {
            $filter_data = array(
                'filter_date_from' => isset($this->request->get['date_from']) ? $this->request->get['date_from'] : date('Y-m-01'),
                'filter_date_to' => isset($this->request->get['date_to']) ? $this->request->get['date_to'] : date('Y-m-d'),
                'limit' => isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10
            );
            
            $json['data'] = $this->model_dashboard_profitability->getTopPerformingProducts($filter_data);
            $json['success'] = true;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Get profitability insights and recommendations
     */
    public function getInsights() {
        $this->load->language('dashboard/profitability');
        $this->load->model('dashboard/profitability');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/profitability')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $json = array();
        
        try {
            $filter_data = array(
                'filter_date_from' => isset($this->request->get['date_from']) ? $this->request->get['date_from'] : date('Y-m-01'),
                'filter_date_to' => isset($this->request->get['date_to']) ? $this->request->get['date_to'] : date('Y-m-d')
            );
            
            $json['data'] = $this->model_dashboard_profitability->getProfitabilityInsights($filter_data);
            $json['success'] = true;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
