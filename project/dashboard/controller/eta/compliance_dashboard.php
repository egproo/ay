<?php
class ControllerEtaComplianceDashboard extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('eta/compliance_dashboard');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('eta/compliance_dashboard');

        $this->getList();
    }

    public function getStats() {
        $this->load->language('eta/compliance_dashboard');
        $this->load->model('eta/compliance_dashboard');

        $json = array();

        try {
            // إحصائيات الامتثال
            $json['compliance_stats'] = array(
                'total_invoices' => $this->model_eta_compliance_dashboard->getTotalInvoices(),
                'submitted_invoices' => $this->model_eta_compliance_dashboard->getSubmittedInvoices(),
                'pending_invoices' => $this->model_eta_compliance_dashboard->getPendingInvoices(),
                'rejected_invoices' => $this->model_eta_compliance_dashboard->getRejectedInvoices(),
                'compliance_rate' => $this->model_eta_compliance_dashboard->getComplianceRate(),
                'total_tax_amount' => $this->model_eta_compliance_dashboard->getTotalTaxAmount()
            );

            // إحصائيات الأداء
            $json['performance_stats'] = array(
                'avg_submission_time' => $this->model_eta_compliance_dashboard->getAverageSubmissionTime(),
                'success_rate' => $this->model_eta_compliance_dashboard->getSuccessRate(),
                'error_rate' => $this->model_eta_compliance_dashboard->getErrorRate(),
                'monthly_growth' => $this->model_eta_compliance_dashboard->getMonthlyGrowth()
            );

            // بيانات الرسوم البيانية
            $json['charts'] = array(
                'submission_trend' => $this->model_eta_compliance_dashboard->getSubmissionTrendData(),
                'status_distribution' => $this->model_eta_compliance_dashboard->getStatusDistributionData(),
                'tax_breakdown' => $this->model_eta_compliance_dashboard->getTaxBreakdownData(),
                'compliance_timeline' => $this->model_eta_compliance_dashboard->getComplianceTimelineData()
            );

            // التنبيهات والتحذيرات
            $json['alerts'] = $this->model_eta_compliance_dashboard->getComplianceAlerts();

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getInvoiceDetails() {
        $this->load->language('eta/compliance_dashboard');
        $this->load->model('eta/compliance_dashboard');

        $json = array();

        if (isset($this->request->get['invoice_id'])) {
            $invoice_id = (int)$this->request->get['invoice_id'];
            
            try {
                $invoice_details = $this->model_eta_compliance_dashboard->getInvoiceDetails($invoice_id);
                
                if ($invoice_details) {
                    $json['invoice'] = $invoice_details;
                    $json['submission_history'] = $this->model_eta_compliance_dashboard->getSubmissionHistory($invoice_id);
                    $json['validation_errors'] = $this->model_eta_compliance_dashboard->getValidationErrors($invoice_id);
                } else {
                    $json['error'] = $this->language->get('error_invoice_not_found');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invoice_id_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function resubmitInvoice() {
        $this->load->language('eta/compliance_dashboard');
        $this->load->model('eta/compliance_dashboard');

        $json = array();

        if (isset($this->request->post['invoice_id'])) {
            $invoice_id = (int)$this->request->post['invoice_id'];
            
            try {
                $result = $this->model_eta_compliance_dashboard->resubmitInvoice($invoice_id);
                
                if ($result) {
                    $json['success'] = $this->language->get('text_invoice_resubmitted');
                } else {
                    $json['error'] = $this->language->get('error_resubmission_failed');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invoice_id_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function exportComplianceReport() {
        $this->load->language('eta/compliance_dashboard');
        $this->load->model('eta/compliance_dashboard');

        if (isset($this->request->post['report_type'])) {
            $report_type = $this->request->post['report_type'];
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';

            try {
                $report_data = $this->model_eta_compliance_dashboard->generateComplianceReport($report_type, $date_from, $date_to);
                
                // إنشاء ملف Excel
                $this->load->library('excel');
                $filename = 'eta_compliance_' . $report_type . '_' . date('Y-m-d') . '.xlsx';
                
                $excel_file = $this->excel->createReport($report_data, $filename);
                
                if ($excel_file) {
                    $json['success'] = $this->language->get('text_report_generated');
                    $json['download_url'] = $this->url->link('eta/compliance_dashboard/download', 'file=' . $filename . '&user_token=' . $this->session->data['user_token'], true);
                } else {
                    $json['error'] = $this->language->get('error_report_generation');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_report_type_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function testConnection() {
        $this->load->language('eta/compliance_dashboard');
        $this->load->model('eta/compliance_dashboard');

        $json = array();

        try {
            $connection_status = $this->model_eta_compliance_dashboard->testETAConnection();
            
            if ($connection_status['success']) {
                $json['success'] = $this->language->get('text_connection_successful');
                $json['connection_info'] = $connection_status['info'];
            } else {
                $json['error'] = $connection_status['error'];
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getList() {
        if (isset($this->request->get['filter_date_from'])) {
            $filter_date_from = $this->request->get['filter_date_from'];
        } else {
            $filter_date_from = date('Y-m-01'); // بداية الشهر الحالي
        }

        if (isset($this->request->get['filter_date_to'])) {
            $filter_date_to = $this->request->get['filter_date_to'];
        } else {
            $filter_date_to = date('Y-m-d'); // اليوم
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('eta/compliance_dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        // إحصائيات الامتثال
        $data['compliance_stats'] = array(
            'total_invoices' => $this->model_eta_compliance_dashboard->getTotalInvoices(),
            'submitted_invoices' => $this->model_eta_compliance_dashboard->getSubmittedInvoices(),
            'pending_invoices' => $this->model_eta_compliance_dashboard->getPendingInvoices(),
            'rejected_invoices' => $this->model_eta_compliance_dashboard->getRejectedInvoices(),
            'compliance_rate' => $this->model_eta_compliance_dashboard->getComplianceRate(),
            'total_tax_amount' => $this->model_eta_compliance_dashboard->getTotalTaxAmount()
        );

        // حالات الفواتير للفلترة
        $data['invoice_statuses'] = array(
            'pending' => $this->language->get('text_pending'),
            'submitted' => $this->language->get('text_submitted'),
            'accepted' => $this->language->get('text_accepted'),
            'rejected' => $this->language->get('text_rejected'),
            'cancelled' => $this->language->get('text_cancelled')
        );

        // فلاتر البحث
        $data['filter_date_from'] = $filter_date_from;
        $data['filter_date_to'] = $filter_date_to;
        $data['filter_status'] = $filter_status;

        // روابط مهمة
        $data['dashboard'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
        $data['stats_url'] = $this->url->link('eta/compliance_dashboard/getStats', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_url'] = $this->url->link('eta/compliance_dashboard/exportComplianceReport', 'user_token=' . $this->session->data['user_token'], true);
        $data['test_connection_url'] = $this->url->link('eta/compliance_dashboard/testConnection', 'user_token=' . $this->session->data['user_token'], true);
        $data['resubmit_url'] = $this->url->link('eta/compliance_dashboard/resubmitInvoice', 'user_token=' . $this->session->data['user_token'], true);

        // رسائل النجاح والخطأ
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('eta/compliance_dashboard', $data));
    }

    public function download() {
        if (isset($this->request->get['file'])) {
            $file = basename($this->request->get['file']);
            $filepath = DIR_DOWNLOAD . $file;

            if (file_exists($filepath)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $file . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                
                // حذف الملف بعد التحميل
                unlink($filepath);
                exit;
            }
        }

        $this->response->redirect($this->url->link('eta/compliance_dashboard', 'user_token=' . $this->session->data['user_token'], true));
    }
}
