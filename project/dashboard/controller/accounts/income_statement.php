<?php
/**
 * قائمة الدخل المتقدمة
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ControllerAccountsIncomeStatement extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/income_statement');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/income_statement');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/income_statement.css');
        $this->document->addScript('view/javascript/accounts/income_statement.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');
        $this->document->addScript('view/javascript/chart.min.js');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'income_statement',
            'record_id' => 0,
            'description' => 'عرض قائمة الدخل',
            'module' => 'income_statement'
        ]);

        $this->getReport();
    }

    public function generate() {
        $this->load->language('accounts/income_statement');
        $this->load->model('accounts/income_statement');

        $json = array();

        if ($this->validateForm()) {
            try {
                $filter_data = $this->prepareFilterData();

                $report_data = $this->model_accounts_income_statement->generateIncomeStatement($filter_data);

                $json['success'] = true;
                $json['report'] = $report_data;

                // تسجيل إنشاء التقرير
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_report',
                    'table_name' => 'income_statement',
                    'record_id' => 0,
                    'description' => 'إنشاء قائمة دخل للفترة: ' . $filter_data['date_from'] . ' - ' . $filter_data['date_to'],
                    'module' => 'income_statement'
                ]);

            } catch (Exception $e) {
                $json['error'] = 'خطأ في إنشاء قائمة الدخل: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'بيانات غير صحيحة';
            $json['errors'] = $this->error;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function compare() {
        $this->load->language('accounts/income_statement');
        $this->load->model('accounts/income_statement');

        $json = array();

        if ($this->validateCompareForm()) {
            try {
                $filter_data = $this->prepareCompareFilterData();

                $comparison_data = $this->model_accounts_income_statement->compareIncomeStatements($filter_data);

                $json['success'] = true;
                $json['comparison'] = $comparison_data;

                // تسجيل المقارنة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'compare_reports',
                    'table_name' => 'income_statement',
                    'record_id' => 0,
                    'description' => 'مقارنة قوائم الدخل بين الفترات',
                    'module' => 'income_statement'
                ]);

            } catch (Exception $e) {
                $json['error'] = 'خطأ في مقارنة قوائم الدخل: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'بيانات المقارنة غير صحيحة';
            $json['errors'] = $this->error;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function export() {
        $this->load->language('accounts/income_statement');
        $this->load->model('accounts/income_statement');

        $format = $this->request->get['format'] ?? 'excel';

        if ($this->validateForm()) {
            try {
                $filter_data = $this->prepareFilterData();

                $report_data = $this->model_accounts_income_statement->generateIncomeStatement($filter_data);

                // تسجيل التصدير
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'export_report',
                    'table_name' => 'income_statement',
                    'record_id' => 0,
                    'description' => "تصدير قائمة الدخل بصيغة {$format}",
                    'module' => 'income_statement'
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

            } catch (Exception $e) {
                $this->session->data['error'] = 'خطأ في تصدير قائمة الدخل: ' . $e->getMessage();
                $this->response->redirect($this->url->link('accounts/income_statement', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->session->data['error'] = 'بيانات غير صحيحة للتصدير';
            $this->response->redirect($this->url->link('accounts/income_statement', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function drill_down() {
        $this->load->model('accounts/income_statement');

        $json = array();

        if (isset($this->request->get['account_id']) && isset($this->request->get['date_from']) && isset($this->request->get['date_to'])) {
            $account_id = $this->request->get['account_id'];
            $date_from = $this->request->get['date_from'];
            $date_to = $this->request->get['date_to'];

            try {
                $drill_down_data = $this->model_accounts_income_statement->getDrillDownData($account_id, $date_from, $date_to);

                $json['success'] = true;
                $json['data'] = $drill_down_data;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معاملات مطلوبة مفقودة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getAnalytics() {
        $this->load->model('accounts/income_statement');

        $json = array();

        if ($this->validateForm()) {
            try {
                $filter_data = $this->prepareFilterData();

                $analytics = $this->model_accounts_income_statement->getIncomeStatementAnalytics($filter_data);

                $json['success'] = true;
                $json['analytics'] = $analytics;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'بيانات غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validateForm() {
        if (empty($this->request->post['date_from'])) {
            $this->error['date_from'] = 'تاريخ البداية مطلوب';
        }

        if (empty($this->request->post['date_to'])) {
            $this->error['date_to'] = 'تاريخ النهاية مطلوب';
        }

        if (!empty($this->request->post['date_from']) && !empty($this->request->post['date_to'])) {
            if (strtotime($this->request->post['date_from']) > strtotime($this->request->post['date_to'])) {
                $this->error['date_range'] = 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية';
            }
        }

        return !$this->error;
    }

    protected function validateCompareForm() {
        if (empty($this->request->post['period1_from']) || empty($this->request->post['period1_to'])) {
            $this->error['period1'] = 'الفترة الأولى مطلوبة';
        }

        if (empty($this->request->post['period2_from']) || empty($this->request->post['period2_to'])) {
            $this->error['period2'] = 'الفترة الثانية مطلوبة';
        }

        return !$this->error;
    }

    protected function prepareFilterData() {
        return array(
            'date_from' => $this->request->post['date_from'],
            'date_to' => $this->request->post['date_to'],
            'cost_center_id' => $this->request->post['cost_center_id'] ?? null,
            'branch_id' => $this->request->post['branch_id'] ?? null,
            'currency' => $this->request->post['currency'] ?? $this->config->get('config_currency'),
            'show_zero_balances' => $this->request->post['show_zero_balances'] ?? 0,
            'group_by_category' => $this->request->post['group_by_category'] ?? 1,
            'include_budget_comparison' => $this->request->post['include_budget_comparison'] ?? 0
        );
    }

    protected function prepareCompareFilterData() {
        return array(
            'period1_from' => $this->request->post['period1_from'],
            'period1_to' => $this->request->post['period1_to'],
            'period2_from' => $this->request->post['period2_from'],
            'period2_to' => $this->request->post['period2_to'],
            'cost_center_id' => $this->request->post['cost_center_id'] ?? null,
            'branch_id' => $this->request->post['branch_id'] ?? null,
            'currency' => $this->request->post['currency'] ?? $this->config->get('config_currency')
        );
    }

    public function print() {
        $this->load->language('accounts/income_statement');
        $this->load->model('accounts/income_statement');

        $data['title'] = $this->language->get('print_title');
        $data['printdate'] = date('Y-m-d H:i:s');
        $data['user_token'] = $this->session->data['user_token'];
        $data['lang'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');
        $data['whoprint'] = $this->user->getUserName();

        $date_start = $this->request->post['date_start'] ?: date('Y-01-01');
        $date_end = $this->request->post['date_end'] ?: date('Y-m-d');

        $data['start_date'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['end_date'] = date($this->language->get('date_format_short'), strtotime($date_end));

        if ($date_start && $date_end) {
            $results = $this->model_accounts_income_statement->getIncomeStatementData($date_start, $date_end);
            $data['revenues'] = $results['revenues'];
            $data['expenses'] = $results['expenses'];
            $data['total_revenues'] = $results['total_revenues'];
            $data['total_expenses'] = $results['total_expenses'];
            $data['net_income'] = $results['net_income'];
        } else {
            $data['revenues'] = [];
            $data['expenses'] = [];
            $data['total_revenues'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['total_expenses'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['net_income'] = $this->currency->format(0, $this->config->get('config_currency'));
            $this->error['warning'] = $this->language->get('error_no_data');
        }

        $data['text_income_statement'] = $this->language->get('text_income_statement');
        $data['text_period'] = $this->language->get('text_period');
        $data['text_from'] = $this->language->get('text_from');
        $data['text_to'] = $this->language->get('text_to');
        $data['text_revenues'] = $this->language->get('text_revenues');
        $data['text_expenses'] = $this->language->get('text_expenses');
        $data['text_total_revenues'] = $this->language->get('text_total_revenues');
        $data['text_total_expenses'] = $this->language->get('text_total_expenses');
        $data['text_net_income'] = $this->language->get('text_net_income');

        $this->response->setOutput($this->load->view('accounts/income_statement_list', $data));
    }

    protected function getReport() {
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/income_statement', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للـ AJAX
        $data['generate_url'] = $this->url->link('accounts/income_statement/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['compare_url'] = $this->url->link('accounts/income_statement/compare', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_url'] = $this->url->link('accounts/income_statement/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['drill_down_url'] = $this->url->link('accounts/income_statement/drill_down', 'user_token=' . $this->session->data['user_token'], true);
        $data['analytics_url'] = $this->url->link('accounts/income_statement/getAnalytics', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        // تحميل قوائم البيانات
        $this->load->model('accounts/cost_center');
        $data['cost_centers'] = $this->model_accounts_cost_center->getCostCenters();

        $this->load->model('setting/branch');
        $data['branches'] = $this->model_setting_branch->getBranches();

        // التواريخ الافتراضية
        $data['default_date_from'] = date('Y-01-01');
        $data['default_date_to'] = date('Y-m-d');

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

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/income_statement', $data));
    }

    private function exportToExcel($report_data, $filter_data) {
        // تنفيذ تصدير Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="income_statement_' . date('Y-m-d') . '.xlsx"');
        // كود تصدير Excel هنا
    }

    private function exportToPdf($report_data, $filter_data) {
        // تنفيذ تصدير PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="income_statement_' . date('Y-m-d') . '.pdf"');
        // كود تصدير PDF هنا
    }

    private function exportToCsv($report_data, $filter_data) {
        // تنفيذ تصدير CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="income_statement_' . date('Y-m-d') . '.csv"');
        // كود تصدير CSV هنا
    }
}
