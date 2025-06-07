<?php
/**
 * Inventory Analytics Dashboard Controller
 * 
 * Provides comprehensive inventory analytics and insights
 * including stock levels, movement trends, valuation analysis,
 * and performance metrics for inventory management
 */
class ControllerDashboardInventoryAnalytics extends Controller {
    private $error = array();

    /**
     * Main Inventory Analytics Dashboard page
     */
    public function index() {
        // Load language file
        $this->load->language('dashboard/inventory_analytics');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title'));
        
        // Load model
        $this->load->model('dashboard/inventory_analytics');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/inventory_analytics')) {
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
            'filter_date_from' => isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : date('Y-m-01'),
            'filter_date_to' => isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : date('Y-m-d'),
            'filter_period' => isset($this->request->get['filter_period']) ? $this->request->get['filter_period'] : 'month'
        );
        
        // Get analytics data
        $data['inventory_summary'] = $this->model_dashboard_inventory_analytics->getInventorySummary($filter_data);
        $data['stock_levels'] = $this->model_dashboard_inventory_analytics->getStockLevels($filter_data);
        $data['movement_trends'] = $this->model_dashboard_inventory_analytics->getMovementTrends($filter_data);
        $data['valuation_analysis'] = $this->model_dashboard_inventory_analytics->getValuationAnalysis($filter_data);
        $data['abc_analysis'] = $this->model_dashboard_inventory_analytics->getABCAnalysis($filter_data);
        $data['slow_moving_items'] = $this->model_dashboard_inventory_analytics->getSlowMovingItems($filter_data);
        $data['stock_alerts'] = $this->model_dashboard_inventory_analytics->getStockAlerts($filter_data);
        
        // Get filter options
        $this->load->model('branch/branch');
        $this->load->model('catalog/category');
        
        $data['branches'] = $this->model_branch_branch->getBranches();
        $data['categories'] = $this->model_catalog_category->getCategories();
        
        // Set filter values
        $data['filter_branch_id'] = $filter_data['filter_branch_id'];
        $data['filter_category_id'] = $filter_data['filter_category_id'];
        $data['filter_date_from'] = $filter_data['filter_date_from'];
        $data['filter_date_to'] = $filter_data['filter_date_to'];
        $data['filter_period'] = $filter_data['filter_period'];
        
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
            'href' => $this->url->link('dashboard/inventory_analytics', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Links
        $data['refresh'] = $this->url->link('dashboard/inventory_analytics', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('dashboard/inventory_analytics/export', 'user_token=' . $this->session->data['user_token'], true);
        
        // User token
        $data['user_token'] = $this->session->data['user_token'];
        
        // Load header, footer and render template
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('dashboard/inventory_analytics', $data));
    }
    
    /**
     * Get analytics data via AJAX
     */
    public function getAnalyticsData() {
        $this->load->language('dashboard/inventory_analytics');
        $this->load->model('dashboard/inventory_analytics');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/inventory_analytics')) {
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
                'filter_date_from' => isset($this->request->post['date_from']) ? $this->request->post['date_from'] : date('Y-m-01'),
                'filter_date_to' => isset($this->request->post['date_to']) ? $this->request->post['date_to'] : date('Y-m-d'),
                'filter_period' => isset($this->request->post['period']) ? $this->request->post['period'] : 'month'
            );
            
            // Get requested data type
            $data_type = isset($this->request->post['data_type']) ? $this->request->post['data_type'] : 'summary';
            
            switch ($data_type) {
                case 'summary':
                    $json['data'] = $this->model_dashboard_inventory_analytics->getInventorySummary($filter_data);
                    break;
                case 'movement_trends':
                    $json['data'] = $this->model_dashboard_inventory_analytics->getMovementTrends($filter_data);
                    break;
                case 'valuation':
                    $json['data'] = $this->model_dashboard_inventory_analytics->getValuationAnalysis($filter_data);
                    break;
                case 'abc_analysis':
                    $json['data'] = $this->model_dashboard_inventory_analytics->getABCAnalysis($filter_data);
                    break;
                case 'alerts':
                    $json['data'] = $this->model_dashboard_inventory_analytics->getStockAlerts($filter_data);
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
     * Export analytics data
     */
    public function export() {
        $this->load->language('dashboard/inventory_analytics');
        $this->load->model('dashboard/inventory_analytics');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/inventory_analytics')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('dashboard/inventory_analytics', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Get filter parameters
        $filter_data = array(
            'filter_branch_id' => isset($this->request->get['filter_branch_id']) ? (int)$this->request->get['filter_branch_id'] : 0,
            'filter_category_id' => isset($this->request->get['filter_category_id']) ? (int)$this->request->get['filter_category_id'] : 0,
            'filter_date_from' => isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : date('Y-m-01'),
            'filter_date_to' => isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : date('Y-m-d')
        );
        
        // Get export data
        $export_data = $this->model_dashboard_inventory_analytics->getExportData($filter_data);
        
        // Generate Excel file
        $this->load->library('excel');
        
        $filename = 'inventory_analytics_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $this->excel->generateInventoryAnalyticsReport($export_data, $filename);
    }
}
