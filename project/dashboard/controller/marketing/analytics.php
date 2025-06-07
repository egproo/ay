<?php
class ControllerMarketingAnalytics extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('marketing/analytics');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('marketing/analytics');

        $this->getList();
    }

    public function getStats() {
        $this->load->language('marketing/analytics');
        $this->load->model('marketing/analytics');

        $json = array();

        try {
            // إحصائيات عامة
            $json['stats'] = array(
                'total_campaigns' => $this->model_marketing_analytics->getTotalCampaigns(),
                'active_campaigns' => $this->model_marketing_analytics->getActiveCampaigns(),
                'total_leads' => $this->model_marketing_analytics->getTotalLeads(),
                'conversion_rate' => $this->model_marketing_analytics->getConversionRate(),
                'total_revenue' => $this->model_marketing_analytics->getTotalRevenue(),
                'roi' => $this->model_marketing_analytics->getROI()
            );

            // بيانات الرسوم البيانية
            $json['charts'] = array(
                'campaign_performance' => $this->model_marketing_analytics->getCampaignPerformanceData(),
                'lead_sources' => $this->model_marketing_analytics->getLeadSourcesData(),
                'conversion_funnel' => $this->model_marketing_analytics->getConversionFunnelData(),
                'revenue_trend' => $this->model_marketing_analytics->getRevenueTrendData()
            );

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getCampaignDetails() {
        $this->load->language('marketing/analytics');
        $this->load->model('marketing/analytics');

        $json = array();

        if (isset($this->request->get['campaign_id'])) {
            $campaign_id = (int)$this->request->get['campaign_id'];
            
            try {
                $campaign_details = $this->model_marketing_analytics->getCampaignDetails($campaign_id);
                
                if ($campaign_details) {
                    $json['campaign'] = $campaign_details;
                    $json['metrics'] = $this->model_marketing_analytics->getCampaignMetrics($campaign_id);
                    $json['timeline'] = $this->model_marketing_analytics->getCampaignTimeline($campaign_id);
                } else {
                    $json['error'] = $this->language->get('error_campaign_not_found');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_campaign_id_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function exportReport() {
        $this->load->language('marketing/analytics');
        $this->load->model('marketing/analytics');

        if (isset($this->request->post['report_type'])) {
            $report_type = $this->request->post['report_type'];
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';

            try {
                $report_data = $this->model_marketing_analytics->generateReport($report_type, $date_from, $date_to);
                
                // إنشاء ملف Excel
                $this->load->library('excel');
                $filename = 'marketing_analytics_' . $report_type . '_' . date('Y-m-d') . '.xlsx';
                
                $excel_file = $this->excel->createReport($report_data, $filename);
                
                if ($excel_file) {
                    $json['success'] = $this->language->get('text_report_generated');
                    $json['download_url'] = $this->url->link('marketing/analytics/download', 'file=' . $filename . '&user_token=' . $this->session->data['user_token'], true);
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

        if (isset($this->request->get['filter_campaign'])) {
            $filter_campaign = $this->request->get['filter_campaign'];
        } else {
            $filter_campaign = '';
        }

        if (isset($this->request->get['filter_source'])) {
            $filter_source = $this->request->get['filter_source'];
        } else {
            $filter_source = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('marketing/analytics', 'user_token=' . $this->session->data['user_token'], true)
        );

        // إحصائيات عامة
        $data['statistics'] = array(
            'total_campaigns' => $this->model_marketing_analytics->getTotalCampaigns(),
            'active_campaigns' => $this->model_marketing_analytics->getActiveCampaigns(),
            'total_leads' => $this->model_marketing_analytics->getTotalLeads(),
            'conversion_rate' => $this->model_marketing_analytics->getConversionRate(),
            'total_revenue' => $this->model_marketing_analytics->getTotalRevenue(),
            'roi' => $this->model_marketing_analytics->getROI()
        );

        // قائمة الحملات للفلترة
        $data['campaigns'] = $this->model_marketing_analytics->getCampaigns();
        
        // قائمة مصادر العملاء المحتملين
        $data['lead_sources'] = $this->model_marketing_analytics->getLeadSources();

        // فلاتر البحث
        $data['filter_date_from'] = $filter_date_from;
        $data['filter_date_to'] = $filter_date_to;
        $data['filter_campaign'] = $filter_campaign;
        $data['filter_source'] = $filter_source;

        // روابط مهمة
        $data['dashboard'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
        $data['stats_url'] = $this->url->link('marketing/analytics/getStats', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_url'] = $this->url->link('marketing/analytics/exportReport', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('marketing/analytics', $data));
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

        $this->response->redirect($this->url->link('marketing/analytics', 'user_token=' . $this->session->data['user_token'], true));
    }
}
