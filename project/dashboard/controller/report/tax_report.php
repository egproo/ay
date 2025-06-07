<?php
/**
 * AYM ERP - Advanced Tax Reporting Controller
 * 
 * Professional tax reporting system with comprehensive analytics
 * Features:
 * - Real-time tax calculations and summaries
 * - ETA compliance reporting
 * - Multi-period comparisons
 * - Export capabilities (PDF, Excel, CSV)
 * - Interactive charts and graphs
 * - Drill-down capabilities
 * - Automated tax filing preparation
 * 
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerReportTaxReport extends Controller {
    
    private $error = array();
    
    public function index() {
        $this->load->language('report/tax_report');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_reports'),
            'href' => $this->url->link('report/report', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('report/tax_report', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $this->load->model('report/tax_report');
        
        // Get filter parameters
        $filter_date_start = $this->request->get['filter_date_start'] ?? date('Y-m-01');
        $filter_date_end = $this->request->get['filter_date_end'] ?? date('Y-m-t');
        $filter_tax_type = $this->request->get['filter_tax_type'] ?? '';
        $filter_customer_type = $this->request->get['filter_customer_type'] ?? '';
        $filter_eta_status = $this->request->get['filter_eta_status'] ?? '';
        
        // Get tax summary data
        $data['tax_summary'] = $this->model_report_tax_report->getTaxSummary($filter_date_start, $filter_date_end);
        
        // Get ETA compliance data
        $data['eta_compliance'] = $this->model_report_tax_report->getETACompliance($filter_date_start, $filter_date_end);
        
        // Get tax breakdown by type
        $data['tax_breakdown'] = $this->model_report_tax_report->getTaxBreakdown($filter_date_start, $filter_date_end);
        
        // Get monthly trends
        $data['monthly_trends'] = $this->model_report_tax_report->getMonthlyTrends($filter_date_start, $filter_date_end);
        
        // Get top customers by tax
        $data['top_customers'] = $this->model_report_tax_report->getTopCustomersByTax($filter_date_start, $filter_date_end, 10);
        
        // Get tax rate analysis
        $data['tax_rate_analysis'] = $this->model_report_tax_report->getTaxRateAnalysis($filter_date_start, $filter_date_end);
        
        // Get pending ETA submissions
        $data['pending_eta'] = $this->model_report_tax_report->getPendingETASubmissions();
        
        // Get tax filing preparation data
        $data['filing_preparation'] = $this->model_report_tax_report->getTaxFilingPreparation($filter_date_start, $filter_date_end);
        
        // Filter data
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['filter_tax_type'] = $filter_tax_type;
        $data['filter_customer_type'] = $filter_customer_type;
        $data['filter_eta_status'] = $filter_eta_status;
        
        // URLs
        $data['export_pdf'] = $this->url->link('report/tax_report/exportPDF', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_excel'] = $this->url->link('report/tax_report/exportExcel', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_csv'] = $this->url->link('report/tax_report/exportCSV', 'user_token=' . $this->session->data['user_token'], true);
        $data['generate_filing'] = $this->url->link('report/tax_report/generateFiling', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('report/tax_report', $data));
    }
    
    public function ajax_get_detailed_data() {
        $this->load->language('report/tax_report');
        $this->load->model('report/tax_report');
        
        $json = array('success' => false);
        
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $report_type = $this->request->post['report_type'] ?? '';
            $date_start = $this->request->post['date_start'] ?? '';
            $date_end = $this->request->post['date_end'] ?? '';
            $filters = $this->request->post['filters'] ?? array();
            
            try {
                switch ($report_type) {
                    case 'tax_details':
                        $data = $this->model_report_tax_report->getTaxDetails($date_start, $date_end, $filters);
                        break;
                    case 'eta_details':
                        $data = $this->model_report_tax_report->getETADetails($date_start, $date_end, $filters);
                        break;
                    case 'customer_tax_details':
                        $data = $this->model_report_tax_report->getCustomerTaxDetails($date_start, $date_end, $filters);
                        break;
                    case 'product_tax_details':
                        $data = $this->model_report_tax_report->getProductTaxDetails($date_start, $date_end, $filters);
                        break;
                    default:
                        throw new Exception('Invalid report type');
                }
                
                $json['success'] = true;
                $json['data'] = $data;
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function exportPDF() {
        $this->load->language('report/tax_report');
        $this->load->model('report/tax_report');
        
        // Get filter parameters
        $filter_date_start = $this->request->get['filter_date_start'] ?? date('Y-m-01');
        $filter_date_end = $this->request->get['filter_date_end'] ?? date('Y-m-t');
        
        // Get report data
        $data = array(
            'tax_summary' => $this->model_report_tax_report->getTaxSummary($filter_date_start, $filter_date_end),
            'eta_compliance' => $this->model_report_tax_report->getETACompliance($filter_date_start, $filter_date_end),
            'tax_breakdown' => $this->model_report_tax_report->getTaxBreakdown($filter_date_start, $filter_date_end),
            'filter_date_start' => $filter_date_start,
            'filter_date_end' => $filter_date_end,
            'generated_date' => date('Y-m-d H:i:s'),
            'company_name' => $this->config->get('config_name')
        );
        
        // Generate PDF
        $this->load->library('pdf');
        
        $html = $this->load->view('report/tax_report_pdf', $data);
        
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
        
        $filename = 'tax_report_' . $filter_date_start . '_to_' . $filter_date_end . '.pdf';
        
        $this->pdf->stream($filename);
    }
    
    public function exportExcel() {
        $this->load->language('report/tax_report');
        $this->load->model('report/tax_report');
        
        // Get filter parameters
        $filter_date_start = $this->request->get['filter_date_start'] ?? date('Y-m-01');
        $filter_date_end = $this->request->get['filter_date_end'] ?? date('Y-m-t');
        
        // Get detailed data for Excel export
        $tax_details = $this->model_report_tax_report->getTaxDetails($filter_date_start, $filter_date_end);
        $eta_details = $this->model_report_tax_report->getETADetails($filter_date_start, $filter_date_end);
        
        // Load Excel library
        $this->load->library('excel');
        
        // Create new Excel object
        $excel = new PHPExcel();
        
        // Set document properties
        $excel->getProperties()
            ->setCreator($this->config->get('config_name'))
            ->setLastModifiedBy($this->config->get('config_name'))
            ->setTitle('Tax Report')
            ->setSubject('Tax Report from ' . $filter_date_start . ' to ' . $filter_date_end)
            ->setDescription('Comprehensive tax report generated by AYM ERP');
        
        // Create Tax Summary sheet
        $this->createTaxSummarySheet($excel, $tax_details);
        
        // Create ETA Compliance sheet
        $this->createETAComplianceSheet($excel, $eta_details);
        
        // Set active sheet to first sheet
        $excel->setActiveSheetIndex(0);
        
        // Generate filename
        $filename = 'tax_report_' . $filter_date_start . '_to_' . $filter_date_end . '.xlsx';
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Write file
        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $writer->save('php://output');
        exit;
    }
    
    public function exportCSV() {
        $this->load->language('report/tax_report');
        $this->load->model('report/tax_report');
        
        // Get filter parameters
        $filter_date_start = $this->request->get['filter_date_start'] ?? date('Y-m-01');
        $filter_date_end = $this->request->get['filter_date_end'] ?? date('Y-m-t');
        
        // Get detailed data
        $tax_details = $this->model_report_tax_report->getTaxDetails($filter_date_start, $filter_date_end);
        
        // Generate filename
        $filename = 'tax_report_' . $filter_date_start . '_to_' . $filter_date_end . '.csv';
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write CSV headers
        fputcsv($output, array(
            'Order ID',
            'Date',
            'Customer',
            'Tax Type',
            'Tax Rate',
            'Taxable Amount',
            'Tax Amount',
            'Total Amount',
            'ETA Status',
            'ETA UUID'
        ));
        
        // Write data rows
        foreach ($tax_details as $row) {
            fputcsv($output, array(
                $row['order_id'],
                $row['date_added'],
                $row['customer_name'],
                $row['tax_type'],
                $row['tax_rate'] . '%',
                number_format($row['taxable_amount'], 2),
                number_format($row['tax_amount'], 2),
                number_format($row['total_amount'], 2),
                $row['eta_status'],
                $row['eta_uuid']
            ));
        }
        
        fclose($output);
        exit;
    }
    
    public function generateFiling() {
        $this->load->language('report/tax_report');
        $this->load->model('report/tax_report');
        
        $json = array('success' => false);
        
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $filing_period = $this->request->post['filing_period'] ?? '';
            $filing_type = $this->request->post['filing_type'] ?? 'monthly';
            
            try {
                // Generate tax filing data
                $filing_data = $this->model_report_tax_report->generateTaxFiling($filing_period, $filing_type);
                
                if ($filing_data) {
                    // Save filing record
                    $filing_id = $this->model_report_tax_report->saveTaxFiling($filing_data);
                    
                    $json['success'] = true;
                    $json['filing_id'] = $filing_id;
                    $json['message'] = $this->language->get('text_filing_generated_success');
                    $json['download_url'] = $this->url->link('report/tax_report/downloadFiling', 'user_token=' . $this->session->data['user_token'] . '&filing_id=' . $filing_id, true);
                } else {
                    $json['error'] = $this->language->get('error_filing_generation_failed');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function downloadFiling() {
        $this->load->model('report/tax_report');
        
        $filing_id = isset($this->request->get['filing_id']) ? (int)$this->request->get['filing_id'] : 0;
        
        if (!$filing_id) {
            $this->session->data['error'] = $this->language->get('error_filing_not_found');
            $this->response->redirect($this->url->link('report/tax_report', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $filing_info = $this->model_report_tax_report->getTaxFiling($filing_id);
        
        if (!$filing_info) {
            $this->session->data['error'] = $this->language->get('error_filing_not_found');
            $this->response->redirect($this->url->link('report/tax_report', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Generate filing document
        $this->load->library('pdf');
        
        $data = array(
            'filing_info' => $filing_info,
            'filing_data' => json_decode($filing_info['filing_data'], true),
            'company_name' => $this->config->get('config_name'),
            'generated_date' => date('Y-m-d H:i:s')
        );
        
        $html = $this->load->view('report/tax_filing_pdf', $data);
        
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
        
        $filename = 'tax_filing_' . $filing_info['filing_period'] . '.pdf';
        
        $this->pdf->stream($filename);
    }
    
    private function createTaxSummarySheet($excel, $data) {
        $sheet = $excel->getActiveSheet();
        $sheet->setTitle('Tax Summary');
        
        // Set headers
        $sheet->setCellValue('A1', 'Tax Summary Report');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        
        // Add data headers
        $row = 3;
        $headers = array('Order ID', 'Date', 'Customer', 'Tax Amount', 'ETA Status', 'Total');
        
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col, $row, $header);
            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
        }
        
        // Add data
        $row++;
        foreach ($data as $item) {
            $sheet->setCellValueByColumnAndRow(0, $row, $item['order_id']);
            $sheet->setCellValueByColumnAndRow(1, $row, $item['date_added']);
            $sheet->setCellValueByColumnAndRow(2, $row, $item['customer_name']);
            $sheet->setCellValueByColumnAndRow(3, $row, $item['tax_amount']);
            $sheet->setCellValueByColumnAndRow(4, $row, $item['eta_status']);
            $sheet->setCellValueByColumnAndRow(5, $row, $item['total_amount']);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    private function createETAComplianceSheet($excel, $data) {
        $sheet = $excel->createSheet();
        $sheet->setTitle('ETA Compliance');
        
        // Set headers
        $sheet->setCellValue('A1', 'ETA Compliance Report');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        
        // Add data headers
        $row = 3;
        $headers = array('Order ID', 'ETA UUID', 'Status', 'Sent Date', 'Response', 'Error', 'Retry Count');
        
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col, $row, $header);
            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
        }
        
        // Add data
        $row++;
        foreach ($data as $item) {
            $sheet->setCellValueByColumnAndRow(0, $row, $item['order_id']);
            $sheet->setCellValueByColumnAndRow(1, $row, $item['eta_uuid']);
            $sheet->setCellValueByColumnAndRow(2, $row, $item['status']);
            $sheet->setCellValueByColumnAndRow(3, $row, $item['sent_date']);
            $sheet->setCellValueByColumnAndRow(4, $row, $item['response_status']);
            $sheet->setCellValueByColumnAndRow(5, $row, $item['error_message']);
            $sheet->setCellValueByColumnAndRow(6, $row, $item['retry_count']);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
