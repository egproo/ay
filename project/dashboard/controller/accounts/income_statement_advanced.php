<?php
/**
 * تحكم قائمة الدخل المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsIncomeStatementAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/income_statement');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/income_statement_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/income_statement.css');
        $this->document->addScript('view/javascript/accounts/income_statement.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');
        $this->document->addScript('view/javascript/jquery/chart.min.js');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'income_statement',
            'record_id' => 0,
            'description' => 'عرض شاشة قائمة الدخل',
            'module' => 'income_statement'
        ]);

        $this->getForm();
    }

    public function generate() {
        $this->load->language('accounts/income_statement');
        $this->load->model('accounts/income_statement_advanced');
        $this->load->model('accounts/audit_trail');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $filter_data = $this->prepareFilterData();

                // تسجيل إنشاء قائمة الدخل في سجل المراجعة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_income_statement',
                    'table_name' => 'income_statement',
                    'record_id' => 0,
                    'description' => 'إنشاء قائمة الدخل للفترة: ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end'],
                    'module' => 'income_statement',
                    'business_date' => $filter_data['date_end']
                ]);

                // إنشاء قائمة الدخل
                $income_statement_data = $this->model_accounts_income_statement_advanced->generateIncomeStatement($filter_data);

                // التحقق من وجود بيانات
                if (empty($income_statement_data['revenues']) && empty($income_statement_data['expenses'])) {
                    $this->session->data['warning'] = 'لا توجد إيرادات أو مصروفات في الفترة المحددة';
                }

                $this->session->data['income_statement_data'] = $income_statement_data;
                $this->session->data['filter_data'] = $filter_data;

                $this->response->redirect($this->url->link('accounts/income_statement_advanced/view', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إنشاء قائمة الدخل: ' . $e->getMessage();

                // تسجيل الخطأ
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_income_statement_failed',
                    'table_name' => 'income_statement',
                    'record_id' => 0,
                    'description' => 'فشل في إنشاء قائمة الدخل: ' . $e->getMessage(),
                    'module' => 'income_statement'
                ]);
            }
        }

        $this->getForm();
    }

    public function view() {
        $this->load->language('accounts/income_statement');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_view'));

        if (!isset($this->session->data['income_statement_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات قائمة دخل للعرض';
            $this->response->redirect($this->url->link('accounts/income_statement_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();

        $this->response->setOutput($this->load->view('accounts/income_statement_view', $data));
    }

    public function export() {
        $this->load->language('accounts/income_statement');
        $this->load->model('accounts/income_statement_advanced');
        $this->load->model('accounts/audit_trail');

        if (!isset($this->session->data['income_statement_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للتصدير';
            $this->response->redirect($this->url->link('accounts/income_statement_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $format = $this->request->get['format'] ?? 'excel';
        $income_statement_data = $this->session->data['income_statement_data'];
        $filter_data = $this->session->data['filter_data'];

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_income_statement',
            'table_name' => 'income_statement',
            'record_id' => 0,
            'description' => "تصدير قائمة الدخل بصيغة {$format}",
            'module' => 'income_statement'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($income_statement_data, $filter_data);
                break;
            case 'pdf':
                $this->exportToPdf($income_statement_data, $filter_data);
                break;
            case 'csv':
                $this->exportToCsv($income_statement_data, $filter_data);
                break;
            default:
                $this->exportToExcel($income_statement_data, $filter_data);
        }
    }

    public function print() {
        $this->load->language('accounts/income_statement');

        if (!isset($this->session->data['income_statement_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للطباعة';
            $this->response->redirect($this->url->link('accounts/income_statement_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();
        $data['print_mode'] = true;

        $this->response->setOutput($this->load->view('accounts/income_statement_print', $data));
    }

    public function compareIncomeStatements() {
        $this->load->language('accounts/income_statement');
        $this->load->model('accounts/income_statement_advanced');

        $json = array();

        if (isset($this->request->post['period1']) && isset($this->request->post['period2'])) {
            try {
                $period1 = $this->request->post['period1'];
                $period2 = $this->request->post['period2'];

                $comparison_data = $this->model_accounts_income_statement_advanced->compareIncomeStatements($period1, $period2);

                $json['success'] = true;
                $json['data'] = $comparison_data;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'بيانات المقارنة غير مكتملة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getProfitabilityRatios() {
        $this->load->model('accounts/income_statement_advanced');

        $json = array();

        if (isset($this->session->data['income_statement_data'])) {
            try {
                $income_statement_data = $this->session->data['income_statement_data'];

                $ratios = $this->model_accounts_income_statement_advanced->calculateProfitabilityRatios($income_statement_data);

                $json['success'] = true;
                $json['ratios'] = $ratios;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات قائمة دخل لحساب النسب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getRevenueAnalysis() {
        $this->load->model('accounts/income_statement_advanced');

        $json = array();

        if (isset($this->session->data['income_statement_data'])) {
            try {
                $income_statement_data = $this->session->data['income_statement_data'];
                $filter_data = $this->session->data['filter_data'];

                $analysis = $this->model_accounts_income_statement_advanced->analyzeRevenues($income_statement_data, $filter_data);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات قائمة دخل للتحليل';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getExpenseAnalysis() {
        $this->load->model('accounts/income_statement_advanced');

        $json = array();

        if (isset($this->session->data['income_statement_data'])) {
            try {
                $income_statement_data = $this->session->data['income_statement_data'];
                $filter_data = $this->session->data['filter_data'];

                $analysis = $this->model_accounts_income_statement_advanced->analyzeExpenses($income_statement_data, $filter_data);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات قائمة دخل للتحليل';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getIncomeStatementAnalysis() {
        $this->load->model('accounts/income_statement_advanced');

        $json = array();

        if (isset($this->session->data['income_statement_data'])) {
            try {
                $income_statement_data = $this->session->data['income_statement_data'];
                $filter_data = $this->session->data['filter_data'];

                $analysis = $this->model_accounts_income_statement_advanced->analyzeIncomeStatement($income_statement_data, $filter_data);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات قائمة دخل للتحليل';
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
            'href' => $this->url->link('accounts/income_statement_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للإجراءات
        $data['action'] = $this->url->link('accounts/income_statement_advanced/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['compare_url'] = $this->url->link('accounts/income_statement_advanced/compareIncomeStatements', 'user_token=' . $this->session->data['user_token'], true);
        $data['ratios_url'] = $this->url->link('accounts/income_statement_advanced/getProfitabilityRatios', 'user_token=' . $this->session->data['user_token'], true);
        $data['revenue_analysis_url'] = $this->url->link('accounts/income_statement_advanced/getRevenueAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['expense_analysis_url'] = $this->url->link('accounts/income_statement_advanced/getExpenseAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['analysis_url'] = $this->url->link('accounts/income_statement_advanced/getIncomeStatementAnalysis', 'user_token=' . $this->session->data['user_token'], true);

        // بيانات النموذج
        $fields = ['date_start', 'date_end', 'include_zero_balances', 'show_comparative', 'comparative_date_start',
                   'comparative_date_end', 'group_by_type', 'show_percentages', 'currency'];

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } else {
                $default_values = [
                    'date_start' => date('Y-m-01'),
                    'date_end' => date('Y-m-t'),
                    'include_zero_balances' => 0,
                    'show_comparative' => 0,
                    'comparative_date_start' => date('Y-m-01', strtotime('-1 year')),
                    'comparative_date_end' => date('Y-m-t', strtotime('-1 year')),
                    'group_by_type' => 1,
                    'show_percentages' => 1,
                    'currency' => $this->config->get('config_currency')
                ];
                $data[$field] = $default_values[$field] ?? '';
            }
        }

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

        $this->response->setOutput($this->load->view('accounts/income_statement_advanced_form', $data));
    }

    protected function prepareFilterData() {
        return array(
            'date_start' => $this->request->post['date_start'],
            'date_end' => $this->request->post['date_end'],
            'include_zero_balances' => isset($this->request->post['include_zero_balances']) ? 1 : 0,
            'show_comparative' => isset($this->request->post['show_comparative']) ? 1 : 0,
            'comparative_date_start' => $this->request->post['comparative_date_start'] ?? '',
            'comparative_date_end' => $this->request->post['comparative_date_end'] ?? '',
            'group_by_type' => isset($this->request->post['group_by_type']) ? 1 : 0,
            'show_percentages' => isset($this->request->post['show_percentages']) ? 1 : 0,
            'currency' => $this->request->post['currency'] ?? $this->config->get('config_currency')
        );
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'accounts/income_statement')) {
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

        if (isset($this->request->post['show_comparative']) && $this->request->post['show_comparative']) {
            if (empty($this->request->post['comparative_date_start'])) {
                $this->error['comparative_date_start'] = 'تاريخ بداية المقارنة مطلوب';
            }
            if (empty($this->request->post['comparative_date_end'])) {
                $this->error['comparative_date_end'] = 'تاريخ نهاية المقارنة مطلوب';
            }
        }

        return !$this->error;
    }

    protected function prepareViewData() {
        $data = array();

        $data['income_statement_data'] = $this->session->data['income_statement_data'];
        $data['filter_data'] = $this->session->data['filter_data'];

        // معلومات الشركة
        $data['company_name'] = $this->config->get('config_name');
        $data['company_address'] = $this->config->get('config_address');

        // معلومات التقرير
        $data['report_title'] = $this->language->get('heading_title');
        $data['report_date'] = date($this->language->get('date_format_long'));
        $data['generated_by'] = $this->user->getUserName();

        // URLs
        $data['export_excel'] = $this->url->link('accounts/income_statement_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=excel', true);
        $data['export_pdf'] = $this->url->link('accounts/income_statement_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=pdf', true);
        $data['print_url'] = $this->url->link('accounts/income_statement_advanced/print', 'user_token=' . $this->session->data['user_token'], true);
        $data['back_url'] = $this->url->link('accounts/income_statement_advanced', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['ratios_url'] = $this->url->link('accounts/income_statement_advanced/getProfitabilityRatios', 'user_token=' . $this->session->data['user_token'], true);
        $data['analysis_url'] = $this->url->link('accounts/income_statement_advanced/getIncomeStatementAnalysis', 'user_token=' . $this->session->data['user_token'], true);

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/income_statement_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $data;
    }
}