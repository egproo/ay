<?php
/**
 * تحكم التقارير المالية المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsFinancialReportsAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/financial_reports');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/financial_reports_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/financial_reports.css');
        $this->document->addScript('view/javascript/accounts/financial_reports.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');
        $this->document->addScript('view/javascript/jquery/chart.min.js');
        $this->document->addScript('view/javascript/jquery/datatables.min.js');
        $this->document->addStyle('view/javascript/jquery/datatables.min.css');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'financial_reports',
            'record_id' => 0,
            'description' => 'عرض شاشة التقارير المالية المتقدمة',
            'module' => 'financial_reports'
        ]);

        $this->getDashboard();
    }

    public function generateReport() {
        $this->load->language('accounts/financial_reports');
        $this->load->model('accounts/financial_reports_advanced');
        $this->load->model('accounts/audit_trail');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $filter_data = $this->prepareFilterData();

                // تسجيل إنشاء التقرير في سجل المراجعة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_financial_report',
                    'table_name' => 'financial_reports',
                    'record_id' => 0,
                    'description' => 'إنشاء تقرير مالي: ' . $filter_data['report_type'] . ' للفترة: ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end'],
                    'module' => 'financial_reports',
                    'business_date' => $filter_data['date_end']
                ]);

                // إنشاء التقرير المالي
                $report_data = $this->model_accounts_financial_reports_advanced->generateFinancialReport($filter_data);

                // التحقق من وجود بيانات
                if (empty($report_data['data'])) {
                    $this->session->data['warning'] = 'لا توجد بيانات في الفترة المحددة';
                }

                $this->session->data['report_data'] = $report_data;
                $this->session->data['filter_data'] = $filter_data;

                $this->response->redirect($this->url->link('accounts/financial_reports_advanced/view', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إنشاء التقرير المالي: ' . $e->getMessage();

                // تسجيل الخطأ
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_financial_report_failed',
                    'table_name' => 'financial_reports',
                    'record_id' => 0,
                    'description' => 'فشل في إنشاء التقرير المالي: ' . $e->getMessage(),
                    'module' => 'financial_reports'
                ]);
            }
        }

        $this->getDashboard();
    }

    public function view() {
        $this->load->language('accounts/financial_reports');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_view'));

        if (!isset($this->session->data['report_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات تقرير مالي للعرض';
            $this->response->redirect($this->url->link('accounts/financial_reports_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();

        $this->response->setOutput($this->load->view('accounts/financial_reports_view', $data));
    }

    public function export() {
        $this->load->language('accounts/financial_reports');
        $this->load->model('accounts/financial_reports_advanced');
        $this->load->model('accounts/audit_trail');

        if (!isset($this->session->data['report_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للتصدير';
            $this->response->redirect($this->url->link('accounts/financial_reports_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $format = $this->request->get['format'] ?? 'excel';
        $report_data = $this->session->data['report_data'];
        $filter_data = $this->session->data['filter_data'];

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_financial_report',
            'table_name' => 'financial_reports',
            'record_id' => 0,
            'description' => "تصدير التقرير المالي بصيغة {$format}",
            'module' => 'financial_reports'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($report_data, $filter_data);
                break;
            case 'pdf':
                $this->exportToPdf($report_data, $filter_data);
                break;
            case 'csv':
                $this->exportToCsv($report_data, $filter_data);
                break;
            default:
                $this->exportToExcel($report_data, $filter_data);
        }
    }

    public function getFinancialRatios() {
        $this->load->model('accounts/financial_reports_advanced');

        $json = array();

        if (isset($this->session->data['filter_data'])) {
            try {
                $filter_data = $this->session->data['filter_data'];

                $ratios = $this->model_accounts_financial_reports_advanced->calculateFinancialRatios($filter_data);

                $json['success'] = true;
                $json['ratios'] = $ratios;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات لحساب النسب المالية';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getFinancialAnalysis() {
        $this->load->model('accounts/financial_reports_advanced');

        $json = array();

        if (isset($this->session->data['report_data']) && isset($this->session->data['filter_data'])) {
            try {
                $report_data = $this->session->data['report_data'];
                $filter_data = $this->session->data['filter_data'];

                $analysis = $this->model_accounts_financial_reports_advanced->analyzeFinancialData($report_data, $filter_data);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات للتحليل المالي';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getPerformanceIndicators() {
        $this->load->model('accounts/financial_reports_advanced');

        $json = array();

        if (isset($this->session->data['filter_data'])) {
            try {
                $filter_data = $this->session->data['filter_data'];

                $kpis = $this->model_accounts_financial_reports_advanced->calculateKPIs($filter_data);

                $json['success'] = true;
                $json['kpis'] = $kpis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات لحساب مؤشرات الأداء';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getTrendAnalysis() {
        $this->load->model('accounts/financial_reports_advanced');

        $json = array();

        if (isset($this->session->data['filter_data'])) {
            try {
                $filter_data = $this->session->data['filter_data'];

                $trends = $this->model_accounts_financial_reports_advanced->analyzeTrends($filter_data);

                $json['success'] = true;
                $json['trends'] = $trends;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات لتحليل الاتجاهات';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getBenchmarkAnalysis() {
        $this->load->model('accounts/financial_reports_advanced');

        $json = array();

        if (isset($this->session->data['filter_data'])) {
            try {
                $filter_data = $this->session->data['filter_data'];

                $benchmark = $this->model_accounts_financial_reports_advanced->benchmarkAnalysis($filter_data);

                $json['success'] = true;
                $json['benchmark'] = $benchmark;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات للمقارنة المرجعية';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getVarianceAnalysis() {
        $this->load->model('accounts/financial_reports_advanced');

        $json = array();

        if (isset($this->session->data['filter_data'])) {
            try {
                $filter_data = $this->session->data['filter_data'];

                $variance = $this->model_accounts_financial_reports_advanced->varianceAnalysis($filter_data);

                $json['success'] = true;
                $json['variance'] = $variance;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات لتحليل الانحرافات';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getSegmentAnalysis() {
        $this->load->model('accounts/financial_reports_advanced');

        $json = array();

        if (isset($this->session->data['filter_data'])) {
            try {
                $filter_data = $this->session->data['filter_data'];

                $segments = $this->model_accounts_financial_reports_advanced->segmentAnalysis($filter_data);

                $json['success'] = true;
                $json['segments'] = $segments;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات لتحليل القطاعات';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getDashboard() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/financial_reports_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للإجراءات
        $data['action'] = $this->url->link('accounts/financial_reports_advanced/generateReport', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['ratios_url'] = $this->url->link('accounts/financial_reports_advanced/getFinancialRatios', 'user_token=' . $this->session->data['user_token'], true);
        $data['analysis_url'] = $this->url->link('accounts/financial_reports_advanced/getFinancialAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['kpis_url'] = $this->url->link('accounts/financial_reports_advanced/getPerformanceIndicators', 'user_token=' . $this->session->data['user_token'], true);
        $data['trends_url'] = $this->url->link('accounts/financial_reports_advanced/getTrendAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['benchmark_url'] = $this->url->link('accounts/financial_reports_advanced/getBenchmarkAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['variance_url'] = $this->url->link('accounts/financial_reports_advanced/getVarianceAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['segments_url'] = $this->url->link('accounts/financial_reports_advanced/getSegmentAnalysis', 'user_token=' . $this->session->data['user_token'], true);

        // بيانات النموذج
        $fields = ['report_type', 'date_start', 'date_end', 'comparison_period', 'include_budget',
                   'currency', 'consolidation_level', 'segment_analysis', 'show_details'];

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } else {
                $default_values = [
                    'report_type' => 'comprehensive',
                    'date_start' => date('Y-01-01'),
                    'date_end' => date('Y-m-d'),
                    'comparison_period' => 'previous_year',
                    'include_budget' => 0,
                    'currency' => $this->config->get('config_currency'),
                    'consolidation_level' => 'company',
                    'segment_analysis' => 0,
                    'show_details' => 1
                ];
                $data[$field] = $default_values[$field] ?? '';
            }
        }

        // أنواع التقارير
        $data['report_types'] = array(
            'comprehensive' => 'تقرير مالي شامل',
            'income_statement' => 'قائمة الدخل',
            'balance_sheet' => 'الميزانية العمومية',
            'cash_flow' => 'قائمة التدفقات النقدية',
            'equity_changes' => 'قائمة التغير في حقوق الملكية',
            'financial_ratios' => 'النسب المالية',
            'performance_analysis' => 'تحليل الأداء'
        );

        // فترات المقارنة
        $data['comparison_periods'] = array(
            'none' => 'بدون مقارنة',
            'previous_month' => 'الشهر السابق',
            'previous_quarter' => 'الربع السابق',
            'previous_year' => 'السنة السابقة',
            'budget' => 'الموازنة التقديرية',
            'custom' => 'فترة مخصصة'
        );

        // مستويات التوحيد
        $data['consolidation_levels'] = array(
            'company' => 'مستوى الشركة',
            'division' => 'مستوى القسم',
            'department' => 'مستوى الإدارة',
            'cost_center' => 'مستوى مركز التكلفة'
        );

        // العملات المتاحة
        $this->load->model('localisation/currency');
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        // الرسائل
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

        if (isset($this->session->data['warning'])) {
            $data['warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        } else {
            $data['warning'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/financial_reports_advanced_dashboard', $data));
    }

    protected function prepareFilterData() {
        return array(
            'report_type' => $this->request->post['report_type'] ?? 'comprehensive',
            'date_start' => $this->request->post['date_start'],
            'date_end' => $this->request->post['date_end'],
            'comparison_period' => $this->request->post['comparison_period'] ?? 'none',
            'include_budget' => isset($this->request->post['include_budget']) ? 1 : 0,
            'currency' => $this->request->post['currency'] ?? $this->config->get('config_currency'),
            'consolidation_level' => $this->request->post['consolidation_level'] ?? 'company',
            'segment_analysis' => isset($this->request->post['segment_analysis']) ? 1 : 0,
            'show_details' => isset($this->request->post['show_details']) ? 1 : 0
        );
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'accounts/financial_reports')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['date_start'])) {
            $this->error['date_start'] = 'تاريخ البداية مطلوب';
        }

        if (empty($this->request->post['date_end'])) {
            $this->error['date_end'] = 'تاريخ النهاية مطلوب';
        }

        if (!empty($this->request->post['date_start']) && !empty($this->request->post['date_end'])) {
            if (strtotime($this->request->post['date_start']) > strtotime($this->request->post['date_end'])) {
                $this->error['date_range'] = 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية';
            }
        }

        return !$this->error;
    }

    protected function prepareViewData() {
        $data = array();

        $data['report_data'] = $this->session->data['report_data'];
        $data['filter_data'] = $this->session->data['filter_data'];

        // معلومات الشركة
        $data['company_name'] = $this->config->get('config_name');
        $data['company_address'] = $this->config->get('config_address');

        // معلومات التقرير
        $data['report_title'] = $this->language->get('heading_title');
        $data['report_date'] = date($this->language->get('date_format_long'));
        $data['generated_by'] = $this->user->getUserName();

        // URLs
        $data['export_excel'] = $this->url->link('accounts/financial_reports_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=excel', true);
        $data['export_pdf'] = $this->url->link('accounts/financial_reports_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=pdf', true);
        $data['print_url'] = $this->url->link('accounts/financial_reports_advanced/print', 'user_token=' . $this->session->data['user_token'], true);
        $data['back_url'] = $this->url->link('accounts/financial_reports_advanced', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['ratios_url'] = $this->url->link('accounts/financial_reports_advanced/getFinancialRatios', 'user_token=' . $this->session->data['user_token'], true);
        $data['analysis_url'] = $this->url->link('accounts/financial_reports_advanced/getFinancialAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['kpis_url'] = $this->url->link('accounts/financial_reports_advanced/getPerformanceIndicators', 'user_token=' . $this->session->data['user_token'], true);
        $data['trends_url'] = $this->url->link('accounts/financial_reports_advanced/getTrendAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['benchmark_url'] = $this->url->link('accounts/financial_reports_advanced/getBenchmarkAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['variance_url'] = $this->url->link('accounts/financial_reports_advanced/getVarianceAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['segments_url'] = $this->url->link('accounts/financial_reports_advanced/getSegmentAnalysis', 'user_token=' . $this->session->data['user_token'], true);

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/financial_reports_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $data;
    }
}