<?php
/**
 * تحكم ميزان المراجعة المتقدم والمتكامل
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsTrialBalanceAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/trial_balance');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/trial_balance_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/trial_balance.css');
        $this->document->addScript('view/javascript/accounts/trial_balance.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'trial_balance',
            'record_id' => 0,
            'description' => 'عرض ميزان المراجعة',
            'module' => 'trial_balance'
        ]);

        $this->getForm();
    }

    public function generate() {
        $this->load->language('accounts/trial_balance');
        $this->load->model('accounts/trial_balance_advanced');
        $this->load->model('accounts/audit_trail');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $filter_data = $this->prepareFilterData();

                // تسجيل إنشاء التقرير في سجل المراجعة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_report',
                    'table_name' => 'trial_balance',
                    'record_id' => 0,
                    'description' => 'إنشاء ميزان المراجعة للفترة: ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end'],
                    'module' => 'trial_balance',
                    'business_date' => $filter_data['date_end']
                ]);

                // إنشاء التقرير
                $trial_balance_data = $this->model_accounts_trial_balance_advanced->generateTrialBalance($filter_data);

                // التحقق من التوازن
                $balance_check = $this->validateTrialBalance($trial_balance_data);

                if (!$balance_check['is_balanced']) {
                    $this->session->data['warning'] = 'تحذير: ميزان المراجعة غير متوازن! الفرق: ' . $balance_check['difference_formatted'];

                    // تسجيل عدم التوازن كتنبيه أمني
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'unbalanced_trial_balance',
                        'table_name' => 'trial_balance',
                        'record_id' => 0,
                        'description' => 'ميزان مراجعة غير متوازن - الفرق: ' . $balance_check['difference'],
                        'module' => 'trial_balance',
                        'transaction_amount' => $balance_check['difference']
                    ]);
                }

                $this->session->data['trial_balance_data'] = $trial_balance_data;
                $this->session->data['filter_data'] = $filter_data;
                $this->session->data['balance_check'] = $balance_check;

                $this->response->redirect($this->url->link('accounts/trial_balance_advanced/view', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إنشاء ميزان المراجعة: ' . $e->getMessage();

                // تسجيل الخطأ
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_report_failed',
                    'table_name' => 'trial_balance',
                    'record_id' => 0,
                    'description' => 'فشل في إنشاء ميزان المراجعة: ' . $e->getMessage(),
                    'module' => 'trial_balance'
                ]);
            }
        }

        $this->getForm();
    }

    public function view() {
        $this->load->language('accounts/trial_balance');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_view'));

        if (!isset($this->session->data['trial_balance_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات ميزان مراجعة للعرض';
            $this->response->redirect($this->url->link('accounts/trial_balance_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();

        $this->response->setOutput($this->load->view('accounts/trial_balance_view', $data));
    }

    public function export() {
        $this->load->language('accounts/trial_balance');
        $this->load->model('accounts/trial_balance_advanced');
        $this->load->model('accounts/audit_trail');

        if (!isset($this->session->data['trial_balance_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للتصدير';
            $this->response->redirect($this->url->link('accounts/trial_balance_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $format = $this->request->get['format'] ?? 'excel';
        $trial_balance_data = $this->session->data['trial_balance_data'];
        $filter_data = $this->session->data['filter_data'];

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_report',
            'table_name' => 'trial_balance',
            'record_id' => 0,
            'description' => "تصدير ميزان المراجعة بصيغة {$format}",
            'module' => 'trial_balance'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($trial_balance_data, $filter_data);
                break;
            case 'pdf':
                $this->exportToPdf($trial_balance_data, $filter_data);
                break;
            case 'csv':
                $this->exportToCsv($trial_balance_data, $filter_data);
                break;
            default:
                $this->exportToExcel($trial_balance_data, $filter_data);
        }
    }

    public function print() {
        $this->load->language('accounts/trial_balance');

        if (!isset($this->session->data['trial_balance_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للطباعة';
            $this->response->redirect($this->url->link('accounts/trial_balance_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();
        $data['print_mode'] = true;

        $this->response->setOutput($this->load->view('accounts/trial_balance_print', $data));
    }

    public function compareTrialBalance() {
        $this->load->language('accounts/trial_balance');
        $this->load->model('accounts/trial_balance_advanced');

        $json = array();

        if (isset($this->request->post['period1']) && isset($this->request->post['period2'])) {
            try {
                $period1 = $this->request->post['period1'];
                $period2 = $this->request->post['period2'];

                $comparison_data = $this->model_accounts_trial_balance_advanced->compareTrialBalances($period1, $period2);

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

    public function getDrillDownData() {
        $this->load->model('accounts/trial_balance_advanced');

        $json = array();

        if (isset($this->request->get['account_id']) && isset($this->request->get['date_start']) && isset($this->request->get['date_end'])) {
            try {
                $account_id = $this->request->get['account_id'];
                $date_start = $this->request->get['date_start'];
                $date_end = $this->request->get['date_end'];

                $drill_down_data = $this->model_accounts_trial_balance_advanced->getAccountDrillDown($account_id, $date_start, $date_end);

                $json['success'] = true;
                $json['data'] = $drill_down_data;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معاملات غير مكتملة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getAccountMovements() {
        $this->load->model('accounts/trial_balance_advanced');

        $json = array();

        if (isset($this->request->get['account_id'])) {
            try {
                $account_id = $this->request->get['account_id'];
                $date_start = $this->request->get['date_start'] ?? '';
                $date_end = $this->request->get['date_end'] ?? '';

                $movements = $this->model_accounts_trial_balance_advanced->getAccountMovements($account_id, $date_start, $date_end);

                $json['success'] = true;
                $json['movements'] = $movements;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الحساب مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function validateTrialBalanceIntegrity() {
        $this->load->model('accounts/trial_balance_advanced');
        $this->load->model('accounts/journal_entry');

        $json = array();

        try {
            // التحقق من تكامل ميزان المراجعة
            $integrity_check = $this->model_accounts_trial_balance_advanced->validateIntegrity();

            // التحقق من توازن القيود
            $journal_balance_check = $this->model_accounts_journal_entry->validateAllJournalsBalance();

            $json['trial_balance_integrity'] = $integrity_check;
            $json['journal_balance_check'] = $journal_balance_check;

            if ($integrity_check['is_valid'] && $journal_balance_check['is_valid']) {
                $json['success'] = 'التكامل المحاسبي سليم';
            } else {
                $json['warning'] = 'توجد مشاكل في التكامل المحاسبي';
                $json['errors'] = array_merge(
                    $integrity_check['errors'] ?? [],
                    $journal_balance_check['errors'] ?? []
                );
            }

        } catch (Exception $e) {
            $json['error'] = 'خطأ في التحقق من التكامل: ' . $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getTrialBalanceAnalysis() {
        $this->load->model('accounts/trial_balance_advanced');

        $json = array();

        if (isset($this->session->data['trial_balance_data'])) {
            try {
                $trial_balance_data = $this->session->data['trial_balance_data'];
                $analysis = $this->model_accounts_trial_balance_advanced->analyzeTrialBalance($trial_balance_data);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات ميزان مراجعة للتحليل';
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
            'href' => $this->url->link('accounts/trial_balance_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للإجراءات
        $data['action'] = $this->url->link('accounts/trial_balance_advanced/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_excel'] = $this->url->link('accounts/trial_balance_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=excel', true);
        $data['export_pdf'] = $this->url->link('accounts/trial_balance_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=pdf', true);
        $data['export_csv'] = $this->url->link('accounts/trial_balance_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=csv', true);
        $data['print_url'] = $this->url->link('accounts/trial_balance_advanced/print', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['compare_url'] = $this->url->link('accounts/trial_balance_advanced/compareTrialBalance', 'user_token=' . $this->session->data['user_token'], true);
        $data['drill_down_url'] = $this->url->link('accounts/trial_balance_advanced/getDrillDownData', 'user_token=' . $this->session->data['user_token'], true);
        $data['movements_url'] = $this->url->link('accounts/trial_balance_advanced/getAccountMovements', 'user_token=' . $this->session->data['user_token'], true);
        $data['integrity_check_url'] = $this->url->link('accounts/trial_balance_advanced/validateTrialBalanceIntegrity', 'user_token=' . $this->session->data['user_token'], true);
        $data['analysis_url'] = $this->url->link('accounts/trial_balance_advanced/getTrialBalanceAnalysis', 'user_token=' . $this->session->data['user_token'], true);

        // بيانات النموذج
        $fields = ['date_start', 'date_end', 'account_start', 'account_end', 'include_zero_balances',
                   'group_by_type', 'show_opening_balances', 'show_period_movements', 'show_closing_balances',
                   'currency', 'cost_center_id', 'project_id', 'department_id'];

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } else {
                $default_values = [
                    'date_start' => date('Y-m-01'), // بداية الشهر الحالي
                    'date_end' => date('Y-m-t'),    // نهاية الشهر الحالي
                    'include_zero_balances' => 0,
                    'group_by_type' => 1,
                    'show_opening_balances' => 1,
                    'show_period_movements' => 1,
                    'show_closing_balances' => 1,
                    'currency' => $this->config->get('config_currency')
                ];
                $data[$field] = $default_values[$field] ?? '';
            }
        }

        // الحصول على قوائم البيانات
        $this->load->model('accounts/chartaccount');
        $this->load->model('accounts/cost_center');
        $this->load->model('accounts/project');
        $this->load->model('accounts/department');

        $data['accounts'] = $this->model_accounts_chartaccount->getAccountsForSelection();
        $data['cost_centers'] = $this->model_accounts_cost_center->getCostCenters();
        $data['projects'] = $this->model_accounts_project->getProjects();
        $data['departments'] = $this->model_accounts_department->getDepartments();

        // العملات المتاحة
        $this->load->model('localisation/currency');
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        // أنواع الحسابات
        $data['account_types'] = array(
            'asset' => $this->language->get('text_asset'),
            'liability' => $this->language->get('text_liability'),
            'equity' => $this->language->get('text_equity'),
            'revenue' => $this->language->get('text_revenue'),
            'expense' => $this->language->get('text_expense')
        );

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

        $this->response->setOutput($this->load->view('accounts/trial_balance_form', $data));
    }

    protected function prepareFilterData() {
        return array(
            'date_start' => $this->request->post['date_start'],
            'date_end' => $this->request->post['date_end'],
            'account_start' => $this->request->post['account_start'] ?? '',
            'account_end' => $this->request->post['account_end'] ?? '',
            'include_zero_balances' => isset($this->request->post['include_zero_balances']) ? 1 : 0,
            'group_by_type' => isset($this->request->post['group_by_type']) ? 1 : 0,
            'show_opening_balances' => isset($this->request->post['show_opening_balances']) ? 1 : 0,
            'show_period_movements' => isset($this->request->post['show_period_movements']) ? 1 : 0,
            'show_closing_balances' => isset($this->request->post['show_closing_balances']) ? 1 : 0,
            'currency' => $this->request->post['currency'] ?? $this->config->get('config_currency'),
            'cost_center_id' => $this->request->post['cost_center_id'] ?? '',
            'project_id' => $this->request->post['project_id'] ?? '',
            'department_id' => $this->request->post['department_id'] ?? ''
        );
    }

    protected function prepareViewData() {
        $data = array();

        $data['trial_balance_data'] = $this->session->data['trial_balance_data'];
        $data['filter_data'] = $this->session->data['filter_data'];
        $data['balance_check'] = $this->session->data['balance_check'];

        // معلومات الشركة
        $data['company_name'] = $this->config->get('config_name');
        $data['company_address'] = $this->config->get('config_address');
        $data['company_telephone'] = $this->config->get('config_telephone');
        $data['company_email'] = $this->config->get('config_email');

        // معلومات التقرير
        $data['report_title'] = $this->language->get('heading_title');
        $data['report_date'] = date($this->language->get('date_format_long'));
        $data['generated_by'] = $this->user->getUserName();

        // URLs
        $data['export_excel'] = $this->url->link('accounts/trial_balance_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=excel', true);
        $data['export_pdf'] = $this->url->link('accounts/trial_balance_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=pdf', true);
        $data['export_csv'] = $this->url->link('accounts/trial_balance_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=csv', true);
        $data['print_url'] = $this->url->link('accounts/trial_balance_advanced/print', 'user_token=' . $this->session->data['user_token'], true);
        $data['back_url'] = $this->url->link('accounts/trial_balance_advanced', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['drill_down_url'] = $this->url->link('accounts/trial_balance_advanced/getDrillDownData', 'user_token=' . $this->session->data['user_token'], true);
        $data['movements_url'] = $this->url->link('accounts/trial_balance_advanced/getAccountMovements', 'user_token=' . $this->session->data['user_token'], true);
        $data['analysis_url'] = $this->url->link('accounts/trial_balance_advanced/getTrialBalanceAnalysis', 'user_token=' . $this->session->data['user_token'], true);

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/trial_balance_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_view'),
            'href' => $this->url->link('accounts/trial_balance_advanced/view', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $data;
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'accounts/trial_balance')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['date_start'])) {
            $this->error['date_start'] = $this->language->get('error_date_start');
        }

        if (empty($this->request->post['date_end'])) {
            $this->error['date_end'] = $this->language->get('error_date_end');
        }

        if (!empty($this->request->post['date_start']) && !empty($this->request->post['date_end'])) {
            if (strtotime($this->request->post['date_start']) > strtotime($this->request->post['date_end'])) {
                $this->error['date_range'] = $this->language->get('error_date_range');
            }
        }

        return !$this->error;
    }

    protected function validateTrialBalance($trial_balance_data) {
        $total_debit = 0;
        $total_credit = 0;

        foreach ($trial_balance_data['accounts'] as $account) {
            $total_debit += $account['closing_balance_debit'];
            $total_credit += $account['closing_balance_credit'];
        }

        $difference = abs($total_debit - $total_credit);
        $is_balanced = $difference < 0.01;

        return array(
            'total_debit' => $total_debit,
            'total_credit' => $total_credit,
            'difference' => $difference,
            'is_balanced' => $is_balanced,
            'total_debit_formatted' => $this->currency->format($total_debit, $this->config->get('config_currency')),
            'total_credit_formatted' => $this->currency->format($total_credit, $this->config->get('config_currency')),
            'difference_formatted' => $this->currency->format($difference, $this->config->get('config_currency'))
        );
    }

    private function exportToExcel($trial_balance_data, $filter_data) {
        require_once(DIR_SYSTEM . 'library/phpspreadsheet/vendor/autoload.php');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // إعداد الرأس
        $sheet->setTitle('ميزان المراجعة');
        $sheet->setCellValue('A1', $this->config->get('config_name'));
        $sheet->setCellValue('A2', 'ميزان المراجعة');
        $sheet->setCellValue('A3', 'من ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end']);

        // رؤوس الأعمدة
        $headers = ['كود الحساب', 'اسم الحساب', 'نوع الحساب', 'رصيد افتتاحي مدين', 'رصيد افتتاحي دائن',
                   'حركة الفترة مدين', 'حركة الفترة دائن', 'رصيد ختامي مدين', 'رصيد ختامي دائن'];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }

        // البيانات
        $row = 6;
        foreach ($trial_balance_data['accounts'] as $account) {
            $sheet->setCellValue('A' . $row, $account['account_code']);
            $sheet->setCellValue('B' . $row, $account['account_name']);
            $sheet->setCellValue('C' . $row, $account['account_type']);
            $sheet->setCellValue('D' . $row, $account['opening_balance_debit']);
            $sheet->setCellValue('E' . $row, $account['opening_balance_credit']);
            $sheet->setCellValue('F' . $row, $account['period_debit']);
            $sheet->setCellValue('G' . $row, $account['period_credit']);
            $sheet->setCellValue('H' . $row, $account['closing_balance_debit']);
            $sheet->setCellValue('I' . $row, $account['closing_balance_credit']);
            $row++;
        }

        // الإجماليات
        $sheet->setCellValue('A' . $row, 'الإجماليات');
        $sheet->setCellValue('D' . $row, $trial_balance_data['totals']['opening_balance_debit']);
        $sheet->setCellValue('E' . $row, $trial_balance_data['totals']['opening_balance_credit']);
        $sheet->setCellValue('F' . $row, $trial_balance_data['totals']['period_debit']);
        $sheet->setCellValue('G' . $row, $trial_balance_data['totals']['period_credit']);
        $sheet->setCellValue('H' . $row, $trial_balance_data['totals']['closing_balance_debit']);
        $sheet->setCellValue('I' . $row, $trial_balance_data['totals']['closing_balance_credit']);

        // تنسيق
        $sheet->getStyle('A1:I' . $row)->getFont()->setName('Arial');
        $sheet->getStyle('A5:I5')->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);

        // تصدير
        $filename = 'trial_balance_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    private function exportToPdf($trial_balance_data, $filter_data) {
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('ERP System');
        $pdf->SetAuthor($this->config->get('config_name'));
        $pdf->SetTitle('ميزان المراجعة');

        $pdf->SetFont('aealarabiya', '', 12);
        $pdf->AddPage();

        // الرأس
        $pdf->Cell(0, 10, $this->config->get('config_name'), 0, 1, 'C');
        $pdf->Cell(0, 10, 'ميزان المراجعة', 0, 1, 'C');
        $pdf->Cell(0, 10, 'من ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end'], 0, 1, 'C');
        $pdf->Ln(10);

        // رؤوس الأعمدة
        $pdf->SetFont('aealarabiya', 'B', 8);
        $pdf->Cell(25, 8, 'كود الحساب', 1, 0, 'C');
        $pdf->Cell(50, 8, 'اسم الحساب', 1, 0, 'C');
        $pdf->Cell(20, 8, 'النوع', 1, 0, 'C');
        $pdf->Cell(25, 8, 'افتتاحي مدين', 1, 0, 'C');
        $pdf->Cell(25, 8, 'افتتاحي دائن', 1, 0, 'C');
        $pdf->Cell(25, 8, 'حركة مدين', 1, 0, 'C');
        $pdf->Cell(25, 8, 'حركة دائن', 1, 0, 'C');
        $pdf->Cell(25, 8, 'ختامي مدين', 1, 0, 'C');
        $pdf->Cell(25, 8, 'ختامي دائن', 1, 1, 'C');

        // البيانات
        $pdf->SetFont('aealarabiya', '', 7);
        foreach ($trial_balance_data['accounts'] as $account) {
            $pdf->Cell(25, 6, $account['account_code'], 1, 0, 'C');
            $pdf->Cell(50, 6, $account['account_name'], 1, 0, 'R');
            $pdf->Cell(20, 6, $account['account_type'], 1, 0, 'C');
            $pdf->Cell(25, 6, number_format($account['opening_balance_debit'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($account['opening_balance_credit'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($account['period_debit'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($account['period_credit'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($account['closing_balance_debit'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($account['closing_balance_credit'], 2), 1, 1, 'R');
        }

        // الإجماليات
        $pdf->SetFont('aealarabiya', 'B', 8);
        $pdf->Cell(95, 8, 'الإجماليات', 1, 0, 'C');
        $pdf->Cell(25, 8, number_format($trial_balance_data['totals']['opening_balance_debit'], 2), 1, 0, 'R');
        $pdf->Cell(25, 8, number_format($trial_balance_data['totals']['opening_balance_credit'], 2), 1, 0, 'R');
        $pdf->Cell(25, 8, number_format($trial_balance_data['totals']['period_debit'], 2), 1, 0, 'R');
        $pdf->Cell(25, 8, number_format($trial_balance_data['totals']['period_credit'], 2), 1, 0, 'R');
        $pdf->Cell(25, 8, number_format($trial_balance_data['totals']['closing_balance_debit'], 2), 1, 0, 'R');
        $pdf->Cell(25, 8, number_format($trial_balance_data['totals']['closing_balance_credit'], 2), 1, 1, 'R');

        $pdf->Output('trial_balance_' . date('Y-m-d') . '.pdf', 'D');
    }

    private function exportToCsv($trial_balance_data, $filter_data) {
        $filename = 'trial_balance_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // الرأس
        fputcsv($output, [$this->config->get('config_name')]);
        fputcsv($output, ['ميزان المراجعة']);
        fputcsv($output, ['من ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end']]);
        fputcsv($output, []);

        // رؤوس الأعمدة
        $headers = ['كود الحساب', 'اسم الحساب', 'نوع الحساب', 'رصيد افتتاحي مدين', 'رصيد افتتاحي دائن',
                   'حركة الفترة مدين', 'حركة الفترة دائن', 'رصيد ختامي مدين', 'رصيد ختامي دائن'];
        fputcsv($output, $headers);

        // البيانات
        foreach ($trial_balance_data['accounts'] as $account) {
            $row = [
                $account['account_code'],
                $account['account_name'],
                $account['account_type'],
                number_format($account['opening_balance_debit'], 2),
                number_format($account['opening_balance_credit'], 2),
                number_format($account['period_debit'], 2),
                number_format($account['period_credit'], 2),
                number_format($account['closing_balance_debit'], 2),
                number_format($account['closing_balance_credit'], 2)
            ];

            fputcsv($output, $row);
        }

        // الإجماليات
        $totals_row = [
            'الإجماليات',
            '',
            '',
            number_format($trial_balance_data['totals']['opening_balance_debit'], 2),
            number_format($trial_balance_data['totals']['opening_balance_credit'], 2),
            number_format($trial_balance_data['totals']['period_debit'], 2),
            number_format($trial_balance_data['totals']['period_credit'], 2),
            number_format($trial_balance_data['totals']['closing_balance_debit'], 2),
            number_format($trial_balance_data['totals']['closing_balance_credit'], 2)
        ];

        fputcsv($output, $totals_row);

        fclose($output);
    }
}