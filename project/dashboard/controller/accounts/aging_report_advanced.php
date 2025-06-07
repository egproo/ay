<?php
/**
 * تحكم تقرير الأعمار المتقدم والمتكامل
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsAgingReportAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/aging_report');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/aging_report_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/aging_report.css');
        $this->document->addScript('view/javascript/accounts/aging_report.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');
        $this->document->addScript('view/javascript/jquery/chart.min.js');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'aging_report',
            'record_id' => 0,
            'description' => 'عرض شاشة تقرير الأعمار',
            'module' => 'aging_report'
        ]);

        $this->getForm();
    }

    public function generate() {
        $this->load->language('accounts/aging_report');
        $this->load->model('accounts/aging_report_advanced');
        $this->load->model('accounts/audit_trail');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $filter_data = $this->prepareFilterData();

                // تسجيل إنشاء تقرير الأعمار في سجل المراجعة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_aging_report',
                    'table_name' => 'aging_report',
                    'record_id' => 0,
                    'description' => 'إنشاء تقرير الأعمار كما في: ' . $filter_data['as_of_date'] . ' - النوع: ' . $filter_data['report_type'],
                    'module' => 'aging_report',
                    'business_date' => $filter_data['as_of_date']
                ]);

                // إنشاء تقرير الأعمار
                $aging_data = $this->model_accounts_aging_report_advanced->generateAgingReport($filter_data);

                // التحقق من وجود بيانات
                if (empty($aging_data['details'])) {
                    $this->session->data['warning'] = 'لا توجد بيانات في التاريخ المحدد';
                }

                $this->session->data['aging_data'] = $aging_data;
                $this->session->data['filter_data'] = $filter_data;

                $this->response->redirect($this->url->link('accounts/aging_report_advanced/view', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إنشاء تقرير الأعمار: ' . $e->getMessage();

                // تسجيل الخطأ
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_aging_report_failed',
                    'table_name' => 'aging_report',
                    'record_id' => 0,
                    'description' => 'فشل في إنشاء تقرير الأعمار: ' . $e->getMessage(),
                    'module' => 'aging_report'
                ]);
            }
        }

        $this->getForm();
    }

    public function view() {
        $this->load->language('accounts/aging_report');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_view'));

        if (!isset($this->session->data['aging_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات تقرير أعمار للعرض';
            $this->response->redirect($this->url->link('accounts/aging_report_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();

        $this->response->setOutput($this->load->view('accounts/aging_report_view', $data));
    }

    public function export() {
        $this->load->language('accounts/aging_report');
        $this->load->model('accounts/aging_report_advanced');
        $this->load->model('accounts/audit_trail');

        if (!isset($this->session->data['aging_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للتصدير';
            $this->response->redirect($this->url->link('accounts/aging_report_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $format = $this->request->get['format'] ?? 'excel';
        $aging_data = $this->session->data['aging_data'];
        $filter_data = $this->session->data['filter_data'];

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_aging_report',
            'table_name' => 'aging_report',
            'record_id' => 0,
            'description' => "تصدير تقرير الأعمار بصيغة {$format}",
            'module' => 'aging_report'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($aging_data, $filter_data);
                break;
            case 'pdf':
                $this->exportToPdf($aging_data, $filter_data);
                break;
            case 'csv':
                $this->exportToCsv($aging_data, $filter_data);
                break;
            default:
                $this->exportToExcel($aging_data, $filter_data);
        }
    }

    public function print() {
        $this->load->language('accounts/aging_report');

        if (!isset($this->session->data['aging_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للطباعة';
            $this->response->redirect($this->url->link('accounts/aging_report_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();
        $data['print_mode'] = true;

        $this->response->setOutput($this->load->view('accounts/aging_report_print', $data));
    }

    public function getAgingAnalysis() {
        $this->load->model('accounts/aging_report_advanced');

        $json = array();

        if (isset($this->session->data['aging_data'])) {
            try {
                $aging_data = $this->session->data['aging_data'];
                $filter_data = $this->session->data['filter_data'];

                $analysis = $this->model_accounts_aging_report_advanced->analyzeAging($aging_data, $filter_data);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات تقرير أعمار للتحليل';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getCollectionPriority() {
        $this->load->model('accounts/aging_report_advanced');

        $json = array();

        if (isset($this->session->data['aging_data'])) {
            try {
                $aging_data = $this->session->data['aging_data'];

                $priority = $this->model_accounts_aging_report_advanced->calculateCollectionPriority($aging_data);

                $json['success'] = true;
                $json['priority'] = $priority;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات تقرير أعمار لحساب الأولوية';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getRiskAssessment() {
        $this->load->model('accounts/aging_report_advanced');

        $json = array();

        if (isset($this->session->data['aging_data'])) {
            try {
                $aging_data = $this->session->data['aging_data'];
                $filter_data = $this->session->data['filter_data'];

                $risk_assessment = $this->model_accounts_aging_report_advanced->assessCollectionRisk($aging_data, $filter_data);

                $json['success'] = true;
                $json['risk_assessment'] = $risk_assessment;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات تقرير أعمار لتقييم المخاطر';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getAgingTrends() {
        $this->load->model('accounts/aging_report_advanced');

        $json = array();

        if (isset($this->session->data['filter_data'])) {
            try {
                $filter_data = $this->session->data['filter_data'];

                $trends = $this->model_accounts_aging_report_advanced->analyzeAgingTrends($filter_data);

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

    public function getCustomerDetails() {
        $this->load->model('accounts/aging_report_advanced');

        $json = array();

        if (isset($this->request->get['customer_id']) && isset($this->session->data['filter_data'])) {
            try {
                $customer_id = $this->request->get['customer_id'];
                $filter_data = $this->session->data['filter_data'];

                $customer_details = $this->model_accounts_aging_report_advanced->getCustomerAgingDetails($customer_id, $filter_data);

                $json['success'] = true;
                $json['details'] = $customer_details;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معاملات غير مكتملة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getForm() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/aging_report_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للإجراءات
        $data['action'] = $this->url->link('accounts/aging_report_advanced/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['analysis_url'] = $this->url->link('accounts/aging_report_advanced/getAgingAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['priority_url'] = $this->url->link('accounts/aging_report_advanced/getCollectionPriority', 'user_token=' . $this->session->data['user_token'], true);
        $data['risk_url'] = $this->url->link('accounts/aging_report_advanced/getRiskAssessment', 'user_token=' . $this->session->data['user_token'], true);
        $data['trends_url'] = $this->url->link('accounts/aging_report_advanced/getAgingTrends', 'user_token=' . $this->session->data['user_token'], true);
        $data['customer_details_url'] = $this->url->link('accounts/aging_report_advanced/getCustomerDetails', 'user_token=' . $this->session->data['user_token'], true);

        // بيانات النموذج
        $fields = ['as_of_date', 'report_type', 'aging_periods', 'include_zero_balances', 'customer_group',
                   'currency', 'sort_by', 'show_details'];

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } else {
                $default_values = [
                    'as_of_date' => date('Y-m-d'),
                    'report_type' => 'receivables',
                    'aging_periods' => '30,60,90',
                    'include_zero_balances' => 0,
                    'customer_group' => '',
                    'currency' => $this->config->get('config_currency'),
                    'sort_by' => 'amount_desc',
                    'show_details' => 1
                ];
                $data[$field] = $default_values[$field] ?? '';
            }
        }

        // أنواع التقارير
        $data['report_types'] = array(
            'receivables' => 'أعمار المدينين',
            'payables' => 'أعمار الدائنين',
            'both' => 'المدينين والدائنين'
        );

        // خيارات الترتيب
        $data['sort_options'] = array(
            'amount_desc' => 'المبلغ (تنازلي)',
            'amount_asc' => 'المبلغ (تصاعدي)',
            'name_asc' => 'الاسم (أ-ي)',
            'name_desc' => 'الاسم (ي-أ)',
            'days_desc' => 'الأيام (تنازلي)',
            'days_asc' => 'الأيام (تصاعدي)'
        );

        // الحصول على مجموعات العملاء
        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

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

        $this->response->setOutput($this->load->view('accounts/aging_report_advanced_form', $data));
    }

    protected function prepareFilterData() {
        return array(
            'as_of_date' => $this->request->post['as_of_date'],
            'report_type' => $this->request->post['report_type'] ?? 'receivables',
            'aging_periods' => $this->request->post['aging_periods'] ?? '30,60,90',
            'include_zero_balances' => isset($this->request->post['include_zero_balances']) ? 1 : 0,
            'customer_group' => $this->request->post['customer_group'] ?? '',
            'currency' => $this->request->post['currency'] ?? $this->config->get('config_currency'),
            'sort_by' => $this->request->post['sort_by'] ?? 'amount_desc',
            'show_details' => isset($this->request->post['show_details']) ? 1 : 0
        );
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'accounts/aging_report')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['as_of_date'])) {
            $this->error['as_of_date'] = 'تاريخ التقرير مطلوب';
        }

        if (!empty($this->request->post['aging_periods'])) {
            $periods = explode(',', $this->request->post['aging_periods']);
            foreach ($periods as $period) {
                if (!is_numeric(trim($period)) || trim($period) <= 0) {
                    $this->error['aging_periods'] = 'فترات الأعمار يجب أن تكون أرقام موجبة مفصولة بفواصل';
                    break;
                }
            }
        }

        return !$this->error;
    }

    protected function prepareViewData() {
        $data = array();

        $data['aging_data'] = $this->session->data['aging_data'];
        $data['filter_data'] = $this->session->data['filter_data'];

        // معلومات الشركة
        $data['company_name'] = $this->config->get('config_name');
        $data['company_address'] = $this->config->get('config_address');

        // معلومات التقرير
        $data['report_title'] = $this->language->get('heading_title');
        $data['report_date'] = date($this->language->get('date_format_long'));
        $data['generated_by'] = $this->user->getUserName();

        // URLs
        $data['export_excel'] = $this->url->link('accounts/aging_report_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=excel', true);
        $data['export_pdf'] = $this->url->link('accounts/aging_report_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=pdf', true);
        $data['print_url'] = $this->url->link('accounts/aging_report_advanced/print', 'user_token=' . $this->session->data['user_token'], true);
        $data['back_url'] = $this->url->link('accounts/aging_report_advanced', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['analysis_url'] = $this->url->link('accounts/aging_report_advanced/getAgingAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['priority_url'] = $this->url->link('accounts/aging_report_advanced/getCollectionPriority', 'user_token=' . $this->session->data['user_token'], true);
        $data['risk_url'] = $this->url->link('accounts/aging_report_advanced/getRiskAssessment', 'user_token=' . $this->session->data['user_token'], true);
        $data['trends_url'] = $this->url->link('accounts/aging_report_advanced/getAgingTrends', 'user_token=' . $this->session->data['user_token'], true);
        $data['customer_details_url'] = $this->url->link('accounts/aging_report_advanced/getCustomerDetails', 'user_token=' . $this->session->data['user_token'], true);

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/aging_report_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $data;
    }
}