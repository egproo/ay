<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 * 
 * Controller: Dashboard Reports (لوحة التقارير والتحليلات)
 * الهدف: عرض وإدارة التقارير والتحليلات المختلفة للنظام
 * المستخدمين: الإدارة العليا، مدراء الأقسام، المحللين، المدراء التنفيذيين
 */
class ControllerDashboardReports extends Controller {
    
    private $error = array();
    
    /**
     * Main Reports Dashboard page
     */
    public function index() {
        // Load language file
        $this->load->language('dashboard/reports');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title'));
        
        // Load model
        $this->load->model('dashboard/reports');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/reports')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Handle AJAX requests
        if (isset($this->request->get['ajax']) && $this->request->get['ajax'] == '1') {
            $this->getReportsData();
            return;
        }
        
        // Get filter parameters
        $filter_data = array(
            'filter_category' => isset($this->request->get['filter_category']) ? $this->request->get['filter_category'] : '',
            'filter_period' => isset($this->request->get['filter_period']) ? $this->request->get['filter_period'] : 'current_month',
            'filter_date_from' => isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : date('Y-m-01'),
            'filter_date_to' => isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : date('Y-m-t'),
            'filter_branch' => isset($this->request->get['filter_branch']) ? $this->request->get['filter_branch'] : ''
        );
        
        // Get reports data
        $data['reports_summary'] = $this->model_dashboard_reports->getReportsSummary($filter_data);
        $data['quick_reports'] = $this->model_dashboard_reports->getQuickReports($filter_data);
        $data['recent_reports'] = $this->model_dashboard_reports->getRecentReports();
        $data['favorite_reports'] = $this->model_dashboard_reports->getFavoriteReports($this->user->getId());
        
        // Get chart data
        $data['sales_chart'] = $this->model_dashboard_reports->getSalesChartData($filter_data);
        $data['profit_chart'] = $this->model_dashboard_reports->getProfitChartData($filter_data);
        $data['inventory_chart'] = $this->model_dashboard_reports->getInventoryChartData($filter_data);
        
        // Get branches for filter
        $this->load->model('setting/store');
        $data['branches'] = $this->model_setting_store->getStores();
        
        // Prepare data for view
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('dashboard/reports', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Set common template data
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        // URLs for actions
        $data['generate_url'] = $this->url->link('dashboard/reports/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_url'] = $this->url->link('dashboard/reports/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['schedule_url'] = $this->url->link('dashboard/reports/schedule', 'user_token=' . $this->session->data['user_token'], true);
        
        // Filter values
        $data['filter_category'] = $filter_data['filter_category'];
        $data['filter_period'] = $filter_data['filter_period'];
        $data['filter_date_from'] = $filter_data['filter_date_from'];
        $data['filter_date_to'] = $filter_data['filter_date_to'];
        $data['filter_branch'] = $filter_data['filter_branch'];
        
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
        $this->response->setOutput($this->load->view('dashboard/reports', $data));
    }
    
    /**
     * Generate specific report
     */
    public function generate() {
        // Load language file
        $this->load->language('dashboard/reports');
        
        // Load model
        $this->load->model('dashboard/reports');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/reports')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $report_type = isset($this->request->post['report_type']) ? $this->request->post['report_type'] : '';
            $filter_data = array(
                'date_from' => isset($this->request->post['date_from']) ? $this->request->post['date_from'] : date('Y-m-01'),
                'date_to' => isset($this->request->post['date_to']) ? $this->request->post['date_to'] : date('Y-m-t'),
                'branch_id' => isset($this->request->post['branch_id']) ? $this->request->post['branch_id'] : '',
                'category_id' => isset($this->request->post['category_id']) ? $this->request->post['category_id'] : '',
                'format' => isset($this->request->post['format']) ? $this->request->post['format'] : 'html'
            );
            
            if ($report_type) {
                $report_data = $this->model_dashboard_reports->generateReport($report_type, $filter_data);
                
                if ($report_data) {
                    // Save report to database
                    $report_id = $this->model_dashboard_reports->saveReport($report_type, $filter_data, $report_data, $this->user->getId());
                    
                    $json['success'] = $this->language->get('text_report_generated');
                    $json['report_id'] = $report_id;
                    $json['report_data'] = $report_data;
                } else {
                    $json['error'] = $this->language->get('error_report_generation');
                }
            } else {
                $json['error'] = $this->language->get('error_report_type_required');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Export report
     */
    public function export() {
        // Load language file
        $this->load->language('dashboard/reports');
        
        // Load model
        $this->load->model('dashboard/reports');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/reports')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('dashboard/reports', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $report_id = isset($this->request->get['report_id']) ? (int)$this->request->get['report_id'] : 0;
        $format = isset($this->request->get['format']) ? $this->request->get['format'] : 'pdf';
        
        if ($report_id) {
            $report = $this->model_dashboard_reports->getReport($report_id);
            
            if ($report) {
                switch ($format) {
                    case 'pdf':
                        $this->exportToPDF($report);
                        break;
                    case 'excel':
                        $this->exportToExcel($report);
                        break;
                    case 'csv':
                        $this->exportToCSV($report);
                        break;
                    default:
                        $this->session->data['error'] = $this->language->get('error_invalid_format');
                        $this->response->redirect($this->url->link('dashboard/reports', 'user_token=' . $this->session->data['user_token'], true));
                }
            } else {
                $this->session->data['error'] = $this->language->get('error_report_not_found');
                $this->response->redirect($this->url->link('dashboard/reports', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->session->data['error'] = $this->language->get('error_report_id_required');
            $this->response->redirect($this->url->link('dashboard/reports', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * Schedule report
     */
    public function schedule() {
        // Load language file
        $this->load->language('dashboard/reports');
        
        // Load model
        $this->load->model('dashboard/reports');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'dashboard/reports')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $schedule_data = array(
                'report_type' => isset($this->request->post['report_type']) ? $this->request->post['report_type'] : '',
                'schedule_type' => isset($this->request->post['schedule_type']) ? $this->request->post['schedule_type'] : 'daily',
                'schedule_time' => isset($this->request->post['schedule_time']) ? $this->request->post['schedule_time'] : '09:00',
                'recipients' => isset($this->request->post['recipients']) ? $this->request->post['recipients'] : array(),
                'format' => isset($this->request->post['format']) ? $this->request->post['format'] : 'pdf',
                'is_active' => isset($this->request->post['is_active']) ? (int)$this->request->post['is_active'] : 1
            );
            
            if ($this->validateSchedule($schedule_data)) {
                $schedule_id = $this->model_dashboard_reports->scheduleReport($schedule_data, $this->user->getId());
                $json['success'] = $this->language->get('text_report_scheduled');
                $json['schedule_id'] = $schedule_id;
            } else {
                $json['error'] = $this->language->get('error_validation_failed');
                $json['errors'] = $this->error;
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Add report to favorites
     */
    public function addToFavorites() {
        // Load language file
        $this->load->language('dashboard/reports');
        
        // Load model
        $this->load->model('dashboard/reports');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/reports')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $report_type = isset($this->request->post['report_type']) ? $this->request->post['report_type'] : '';
            
            if ($report_type) {
                $this->model_dashboard_reports->addToFavorites($report_type, $this->user->getId());
                $json['success'] = $this->language->get('text_added_to_favorites');
            } else {
                $json['error'] = $this->language->get('error_report_type_required');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Remove report from favorites
     */
    public function removeFromFavorites() {
        // Load language file
        $this->load->language('dashboard/reports');
        
        // Load model
        $this->load->model('dashboard/reports');
        
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/reports')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $report_type = isset($this->request->post['report_type']) ? $this->request->post['report_type'] : '';
            
            if ($report_type) {
                $this->model_dashboard_reports->removeFromFavorites($report_type, $this->user->getId());
                $json['success'] = $this->language->get('text_removed_from_favorites');
            } else {
                $json['error'] = $this->language->get('error_report_type_required');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * AJAX method to get reports data
     */
    public function getReportsData() {
        // Check permissions
        if (!$this->user->hasPermission('access', 'dashboard/reports')) {
            $json['error'] = 'Permission denied';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $this->load->model('dashboard/reports');
        
        // Get filter parameters
        $filter_data = array(
            'filter_category' => isset($this->request->get['filter_category']) ? $this->request->get['filter_category'] : '',
            'filter_period' => isset($this->request->get['filter_period']) ? $this->request->get['filter_period'] : 'current_month',
            'filter_date_from' => isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : date('Y-m-01'),
            'filter_date_to' => isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : date('Y-m-t'),
            'filter_branch' => isset($this->request->get['filter_branch']) ? $this->request->get['filter_branch'] : ''
        );
        
        $json['summary'] = $this->model_dashboard_reports->getReportsSummary($filter_data);
        $json['quick_reports'] = $this->model_dashboard_reports->getQuickReports($filter_data);
        $json['charts'] = array(
            'sales' => $this->model_dashboard_reports->getSalesChartData($filter_data),
            'profit' => $this->model_dashboard_reports->getProfitChartData($filter_data),
            'inventory' => $this->model_dashboard_reports->getInventoryChartData($filter_data)
        );
        $json['success'] = true;
        $json['timestamp'] = date('Y-m-d H:i:s');
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * Export report to PDF
     */
    private function exportToPDF($report) {
        // Implementation would depend on your PDF library (TCPDF, FPDF, etc.)
        // For now, we'll create a simple HTML to PDF conversion
        
        $html = $this->generateReportHTML($report);
        
        // Set headers for PDF download
        $this->response->addHeader('Content-Type: application/pdf');
        $this->response->addHeader('Content-Disposition: attachment; filename="report_' . $report['report_id'] . '.pdf"');
        
        // Here you would use a PDF library to convert HTML to PDF
        // For demonstration, we'll just output the HTML
        $this->response->setOutput($html);
    }
    
    /**
     * Export report to Excel
     */
    private function exportToExcel($report) {
        // Implementation would depend on your Excel library (PhpSpreadsheet, etc.)
        
        // Set headers for Excel download
        $this->response->addHeader('Content-Type: application/vnd.ms-excel');
        $this->response->addHeader('Content-Disposition: attachment; filename="report_' . $report['report_id'] . '.xls"');
        
        $html = $this->generateReportHTML($report);
        $this->response->setOutput($html);
    }
    
    /**
     * Export report to CSV
     */
    private function exportToCSV($report) {
        // Set headers for CSV download
        $this->response->addHeader('Content-Type: text/csv');
        $this->response->addHeader('Content-Disposition: attachment; filename="report_' . $report['report_id'] . '.csv"');
        
        $csv = $this->generateReportCSV($report);
        $this->response->setOutput($csv);
    }
    
    /**
     * Generate HTML for report
     */
    private function generateReportHTML($report) {
        $html = '<html><head><title>' . $report['report_name'] . '</title></head><body>';
        $html .= '<h1>' . $report['report_name'] . '</h1>';
        $html .= '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
        $html .= $report['report_data'];
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Generate CSV for report
     */
    private function generateReportCSV($report) {
        // This would need to be implemented based on the report data structure
        $csv = "Report Name,Generated Date\n";
        $csv .= '"' . $report['report_name'] . '","' . date('Y-m-d H:i:s') . '"' . "\n";
        
        return $csv;
    }
    
    /**
     * Validate schedule data
     */
    private function validateSchedule($data) {
        if (empty($data['report_type'])) {
            $this->error['report_type'] = $this->language->get('error_report_type_required');
        }
        
        if (empty($data['schedule_type'])) {
            $this->error['schedule_type'] = $this->language->get('error_schedule_type_required');
        }
        
        if (empty($data['recipients']) || !is_array($data['recipients'])) {
            $this->error['recipients'] = $this->language->get('error_recipients_required');
        }
        
        return !$this->error;
    }
}
