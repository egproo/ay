<?php
/**
 * تحكم الميزانية العمومية المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsBalanceSheetAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/balance_sheet');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/balance_sheet_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/balance_sheet.css');
        $this->document->addScript('view/javascript/accounts/balance_sheet.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');
        $this->document->addScript('view/javascript/jquery/chart.min.js');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'balance_sheet',
            'record_id' => 0,
            'description' => 'عرض شاشة الميزانية العمومية',
            'module' => 'balance_sheet'
        ]);

        $this->getForm();
    }

    public function generate() {
        $this->load->language('accounts/balance_sheet');
        $this->load->model('accounts/balance_sheet_advanced');
        $this->load->model('accounts/audit_trail');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $filter_data = $this->prepareFilterData();

                // تسجيل إنشاء الميزانية في سجل المراجعة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_balance_sheet',
                    'table_name' => 'balance_sheet',
                    'record_id' => 0,
                    'description' => 'إنشاء الميزانية العمومية كما في: ' . $filter_data['date_end'],
                    'module' => 'balance_sheet',
                    'business_date' => $filter_data['date_end']
                ]);

                // إنشاء الميزانية العمومية
                $balance_sheet_data = $this->model_accounts_balance_sheet_advanced->generateBalanceSheet($filter_data);

                // التحقق من التوازن
                $balance_check = $this->validateBalanceSheet($balance_sheet_data);

                if (!$balance_check['is_balanced']) {
                    $this->session->data['warning'] = 'تحذير: الميزانية العمومية غير متوازنة! الفرق: ' . $balance_check['difference_formatted'];

                    // تسجيل عدم التوازن كتنبيه أمني
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'unbalanced_balance_sheet',
                        'table_name' => 'balance_sheet',
                        'record_id' => 0,
                        'description' => 'ميزانية عمومية غير متوازنة - الفرق: ' . $balance_check['difference'],
                        'module' => 'balance_sheet',
                        'transaction_amount' => $balance_check['difference']
                    ]);
                }

                $this->session->data['balance_sheet_data'] = $balance_sheet_data;
                $this->session->data['filter_data'] = $filter_data;
                $this->session->data['balance_check'] = $balance_check;

                $this->response->redirect($this->url->link('accounts/balance_sheet_advanced/view', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إنشاء الميزانية العمومية: ' . $e->getMessage();

                // تسجيل الخطأ
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_balance_sheet_failed',
                    'table_name' => 'balance_sheet',
                    'record_id' => 0,
                    'description' => 'فشل في إنشاء الميزانية العمومية: ' . $e->getMessage(),
                    'module' => 'balance_sheet'
                ]);
            }
        }

        $this->getForm();
    }

    public function view() {
        $this->load->language('accounts/balance_sheet');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_view'));

        if (!isset($this->session->data['balance_sheet_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات ميزانية عمومية للعرض';
            $this->response->redirect($this->url->link('accounts/balance_sheet_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();

        $this->response->setOutput($this->load->view('accounts/balance_sheet_view', $data));
    }

    public function export() {
        $this->load->language('accounts/balance_sheet');
        $this->load->model('accounts/balance_sheet_advanced');
        $this->load->model('accounts/audit_trail');

        if (!isset($this->session->data['balance_sheet_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للتصدير';
            $this->response->redirect($this->url->link('accounts/balance_sheet_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $format = $this->request->get['format'] ?? 'excel';
        $balance_sheet_data = $this->session->data['balance_sheet_data'];
        $filter_data = $this->session->data['filter_data'];

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_balance_sheet',
            'table_name' => 'balance_sheet',
            'record_id' => 0,
            'description' => "تصدير الميزانية العمومية بصيغة {$format}",
            'module' => 'balance_sheet'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($balance_sheet_data, $filter_data);
                break;
            case 'pdf':
                $this->exportToPdf($balance_sheet_data, $filter_data);
                break;
            case 'csv':
                $this->exportToCsv($balance_sheet_data, $filter_data);
                break;
            default:
                $this->exportToExcel($balance_sheet_data, $filter_data);
        }
    }

    public function print() {
        $this->load->language('accounts/balance_sheet');

        if (!isset($this->session->data['balance_sheet_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للطباعة';
            $this->response->redirect($this->url->link('accounts/balance_sheet_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();
        $data['print_mode'] = true;

        $this->response->setOutput($this->load->view('accounts/balance_sheet_print', $data));
    }

    public function compareBalanceSheets() {
        $this->load->language('accounts/balance_sheet');
        $this->load->model('accounts/balance_sheet_advanced');

        $json = array();

        if (isset($this->request->post['period1']) && isset($this->request->post['period2'])) {
            try {
                $period1 = $this->request->post['period1'];
                $period2 = $this->request->post['period2'];

                $comparison_data = $this->model_accounts_balance_sheet_advanced->compareBalanceSheets($period1, $period2);

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

    public function getFinancialRatios() {
        $this->load->model('accounts/balance_sheet_advanced');

        $json = array();

        if (isset($this->session->data['balance_sheet_data'])) {
            try {
                $balance_sheet_data = $this->session->data['balance_sheet_data'];
                $filter_data = $this->session->data['filter_data'];

                $ratios = $this->model_accounts_balance_sheet_advanced->calculateFinancialRatios($balance_sheet_data);

                $json['success'] = true;
                $json['ratios'] = $ratios;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات ميزانية عمومية لحساب النسب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getAccountDetails() {
        $this->load->model('accounts/balance_sheet_advanced');

        $json = array();

        if (isset($this->request->get['account_id']) && isset($this->request->get['date_end'])) {
            try {
                $account_id = $this->request->get['account_id'];
                $date_end = $this->request->get['date_end'];

                $account_details = $this->model_accounts_balance_sheet_advanced->getAccountDetails($account_id, $date_end);

                $json['success'] = true;
                $json['details'] = $account_details;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معاملات غير مكتملة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function validateBalanceSheetIntegrity() {
        $this->load->model('accounts/balance_sheet_advanced');
        $this->load->model('accounts/trial_balance_advanced');

        $json = array();

        try {
            if (isset($this->session->data['filter_data'])) {
                $filter_data = $this->session->data['filter_data'];

                // التحقق من تكامل الميزانية العمومية
                $integrity_check = $this->model_accounts_balance_sheet_advanced->validateIntegrity($filter_data);

                // التحقق من تطابق مع ميزان المراجعة
                $trial_balance_check = $this->model_accounts_trial_balance_advanced->validateIntegrity();

                $json['balance_sheet_integrity'] = $integrity_check;
                $json['trial_balance_check'] = $trial_balance_check;

                if ($integrity_check['is_valid'] && $trial_balance_check['is_valid']) {
                    $json['success'] = 'التكامل المحاسبي سليم';
                } else {
                    $json['warning'] = 'توجد مشاكل في التكامل المحاسبي';
                    $json['errors'] = array_merge(
                        $integrity_check['errors'] ?? [],
                        $trial_balance_check['errors'] ?? []
                    );
                }
            } else {
                $json['error'] = 'لا توجد بيانات ميزانية للفحص';
            }

        } catch (Exception $e) {
            $json['error'] = 'خطأ في التحقق من التكامل: ' . $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getBalanceSheetAnalysis() {
        $this->load->model('accounts/balance_sheet_advanced');

        $json = array();

        if (isset($this->session->data['balance_sheet_data'])) {
            try {
                $balance_sheet_data = $this->session->data['balance_sheet_data'];
                $filter_data = $this->session->data['filter_data'];

                $analysis = $this->model_accounts_balance_sheet_advanced->analyzeBalanceSheet($balance_sheet_data, $filter_data);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات ميزانية عمومية للتحليل';
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
            'href' => $this->url->link('accounts/balance_sheet_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للإجراءات
        $data['action'] = $this->url->link('accounts/balance_sheet_advanced/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['compare_url'] = $this->url->link('accounts/balance_sheet_advanced/compareBalanceSheets', 'user_token=' . $this->session->data['user_token'], true);
        $data['ratios_url'] = $this->url->link('accounts/balance_sheet_advanced/getFinancialRatios', 'user_token=' . $this->session->data['user_token'], true);
        $data['integrity_check_url'] = $this->url->link('accounts/balance_sheet_advanced/validateBalanceSheetIntegrity', 'user_token=' . $this->session->data['user_token'], true);
        $data['analysis_url'] = $this->url->link('accounts/balance_sheet_advanced/getBalanceSheetAnalysis', 'user_token=' . $this->session->data['user_token'], true);

        // بيانات النموذج
        $fields = ['date_end', 'include_zero_balances', 'show_comparative', 'comparative_date',
                   'group_by_type', 'show_percentages', 'currency'];

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } else {
                $default_values = [
                    'date_end' => date('Y-m-t'),
                    'include_zero_balances' => 0,
                    'show_comparative' => 0,
                    'comparative_date' => date('Y-m-t', strtotime('-1 year')),
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

        $this->response->setOutput($this->load->view('accounts/balance_sheet_advanced_form', $data));
    }

    protected function prepareFilterData() {
        return array(
            'date_end' => $this->request->post['date_end'],
            'include_zero_balances' => isset($this->request->post['include_zero_balances']) ? 1 : 0,
            'show_comparative' => isset($this->request->post['show_comparative']) ? 1 : 0,
            'comparative_date' => $this->request->post['comparative_date'] ?? '',
            'group_by_type' => isset($this->request->post['group_by_type']) ? 1 : 0,
            'show_percentages' => isset($this->request->post['show_percentages']) ? 1 : 0,
            'currency' => $this->request->post['currency'] ?? $this->config->get('config_currency')
        );
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'accounts/balance_sheet')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['date_end'])) {
            $this->error['date_end'] = 'تاريخ الميزانية مطلوب';
        }

        if (isset($this->request->post['show_comparative']) && $this->request->post['show_comparative'] && empty($this->request->post['comparative_date'])) {
            $this->error['comparative_date'] = 'تاريخ المقارنة مطلوب عند تفعيل المقارنة';
        }

        return !$this->error;
    }

    protected function validateBalanceSheet($balance_sheet_data) {
        $total_assets = $balance_sheet_data['totals']['total_assets'];
        $total_liabilities_equity = $balance_sheet_data['totals']['total_liabilities'] + $balance_sheet_data['totals']['total_equity'];

        $difference = abs($total_assets - $total_liabilities_equity);
        $is_balanced = $difference < 0.01;

        return array(
            'total_assets' => $total_assets,
            'total_liabilities_equity' => $total_liabilities_equity,
            'difference' => $difference,
            'is_balanced' => $is_balanced,
            'total_assets_formatted' => $this->currency->format($total_assets, $this->config->get('config_currency')),
            'total_liabilities_equity_formatted' => $this->currency->format($total_liabilities_equity, $this->config->get('config_currency')),
            'difference_formatted' => $this->currency->format($difference, $this->config->get('config_currency'))
        );
    }

    protected function prepareViewData() {
        $data = array();

        $data['balance_sheet_data'] = $this->session->data['balance_sheet_data'];
        $data['filter_data'] = $this->session->data['filter_data'];
        $data['balance_check'] = $this->session->data['balance_check'];

        // معلومات الشركة
        $data['company_name'] = $this->config->get('config_name');
        $data['company_address'] = $this->config->get('config_address');

        // معلومات التقرير
        $data['report_title'] = $this->language->get('heading_title');
        $data['report_date'] = date($this->language->get('date_format_long'));
        $data['generated_by'] = $this->user->getUserName();

        // URLs
        $data['export_excel'] = $this->url->link('accounts/balance_sheet_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=excel', true);
        $data['export_pdf'] = $this->url->link('accounts/balance_sheet_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=pdf', true);
        $data['print_url'] = $this->url->link('accounts/balance_sheet_advanced/print', 'user_token=' . $this->session->data['user_token'], true);
        $data['back_url'] = $this->url->link('accounts/balance_sheet_advanced', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['ratios_url'] = $this->url->link('accounts/balance_sheet_advanced/getFinancialRatios', 'user_token=' . $this->session->data['user_token'], true);
        $data['analysis_url'] = $this->url->link('accounts/balance_sheet_advanced/getBalanceSheetAnalysis', 'user_token=' . $this->session->data['user_token'], true);

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/balance_sheet_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $data;
    }

    private function exportToExcel($balance_sheet_data, $filter_data) {
        require_once(DIR_SYSTEM . 'library/phpspreadsheet/vendor/autoload.php');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // إعداد الرأس
        $sheet->setTitle('الميزانية العمومية');
        $sheet->setCellValue('A1', $this->config->get('config_name'));
        $sheet->setCellValue('A2', 'الميزانية العمومية');
        $sheet->setCellValue('A3', 'كما في: ' . $filter_data['date_end']);

        $row = 5;

        // الأصول
        $sheet->setCellValue('A' . $row, 'الأصول');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($balance_sheet_data['assets'] as $asset_group => $assets) {
            $sheet->setCellValue('A' . $row, $this->getAssetGroupName($asset_group));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;

            foreach ($assets as $asset) {
                $sheet->setCellValue('A' . $row, $asset['account_name']);
                $sheet->setCellValue('B' . $row, $asset['balance']);
                $row++;
            }
            $row++;
        }

        // إجمالي الأصول
        $sheet->setCellValue('A' . $row, 'إجمالي الأصول');
        $sheet->setCellValue('B' . $row, $balance_sheet_data['totals']['total_assets']);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
        $row += 2;

        // الخصوم وحقوق الملكية
        $sheet->setCellValue('A' . $row, 'الخصوم وحقوق الملكية');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        // الخصوم
        foreach ($balance_sheet_data['liabilities'] as $liability_group => $liabilities) {
            $sheet->setCellValue('A' . $row, $this->getLiabilityGroupName($liability_group));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;

            foreach ($liabilities as $liability) {
                $sheet->setCellValue('A' . $row, $liability['account_name']);
                $sheet->setCellValue('B' . $row, $liability['balance']);
                $row++;
            }
            $row++;
        }

        // حقوق الملكية
        foreach ($balance_sheet_data['equity'] as $equity) {
            $sheet->setCellValue('A' . $row, $equity['account_name']);
            $sheet->setCellValue('B' . $row, $equity['balance']);
            $row++;
        }

        // إجمالي الخصوم وحقوق الملكية
        $sheet->setCellValue('A' . $row, 'إجمالي الخصوم وحقوق الملكية');
        $sheet->setCellValue('B' . $row, $balance_sheet_data['totals']['total_liabilities'] + $balance_sheet_data['totals']['total_equity']);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);

        // تنسيق
        $sheet->getStyle('A1:B' . $row)->getFont()->setName('Arial');

        // تصدير
        $filename = 'balance_sheet_' . $filter_data['date_end'] . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    private function exportToPdf($balance_sheet_data, $filter_data) {
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('ERP System');
        $pdf->SetAuthor($this->config->get('config_name'));
        $pdf->SetTitle('الميزانية العمومية');

        $pdf->SetFont('aealarabiya', '', 12);
        $pdf->AddPage();

        // الرأس
        $pdf->Cell(0, 10, $this->config->get('config_name'), 0, 1, 'C');
        $pdf->Cell(0, 10, 'الميزانية العمومية', 0, 1, 'C');
        $pdf->Cell(0, 10, 'كما في: ' . $filter_data['date_end'], 0, 1, 'C');
        $pdf->Ln(10);

        // الأصول
        $pdf->SetFont('aealarabiya', 'B', 10);
        $pdf->Cell(0, 8, 'الأصول', 0, 1, 'R');

        $pdf->SetFont('aealarabiya', '', 8);
        foreach ($balance_sheet_data['assets'] as $asset_group => $assets) {
            $pdf->SetFont('aealarabiya', 'B', 8);
            $pdf->Cell(120, 6, $this->getAssetGroupName($asset_group), 0, 0, 'R');
            $pdf->Cell(40, 6, '', 0, 1, 'R');

            $pdf->SetFont('aealarabiya', '', 7);
            foreach ($assets as $asset) {
                $pdf->Cell(120, 5, '    ' . $asset['account_name'], 0, 0, 'R');
                $pdf->Cell(40, 5, number_format($asset['balance'], 2), 0, 1, 'R');
            }
        }

        // إجمالي الأصول
        $pdf->SetFont('aealarabiya', 'B', 8);
        $pdf->Cell(120, 8, 'إجمالي الأصول', 1, 0, 'R');
        $pdf->Cell(40, 8, number_format($balance_sheet_data['totals']['total_assets'], 2), 1, 1, 'R');

        $pdf->Ln(5);

        // الخصوم وحقوق الملكية
        $pdf->SetFont('aealarabiya', 'B', 10);
        $pdf->Cell(0, 8, 'الخصوم وحقوق الملكية', 0, 1, 'R');

        // الخصوم
        $pdf->SetFont('aealarabiya', '', 8);
        foreach ($balance_sheet_data['liabilities'] as $liability_group => $liabilities) {
            $pdf->SetFont('aealarabiya', 'B', 8);
            $pdf->Cell(120, 6, $this->getLiabilityGroupName($liability_group), 0, 0, 'R');
            $pdf->Cell(40, 6, '', 0, 1, 'R');

            $pdf->SetFont('aealarabiya', '', 7);
            foreach ($liabilities as $liability) {
                $pdf->Cell(120, 5, '    ' . $liability['account_name'], 0, 0, 'R');
                $pdf->Cell(40, 5, number_format($liability['balance'], 2), 0, 1, 'R');
            }
        }

        // حقوق الملكية
        $pdf->SetFont('aealarabiya', 'B', 8);
        $pdf->Cell(120, 6, 'حقوق الملكية', 0, 0, 'R');
        $pdf->Cell(40, 6, '', 0, 1, 'R');

        $pdf->SetFont('aealarabiya', '', 7);
        foreach ($balance_sheet_data['equity'] as $equity) {
            $pdf->Cell(120, 5, '    ' . $equity['account_name'], 0, 0, 'R');
            $pdf->Cell(40, 5, number_format($equity['balance'], 2), 0, 1, 'R');
        }

        // إجمالي الخصوم وحقوق الملكية
        $pdf->SetFont('aealarabiya', 'B', 8);
        $pdf->Cell(120, 8, 'إجمالي الخصوم وحقوق الملكية', 1, 0, 'R');
        $pdf->Cell(40, 8, number_format($balance_sheet_data['totals']['total_liabilities'] + $balance_sheet_data['totals']['total_equity'], 2), 1, 1, 'R');

        $pdf->Output('balance_sheet_' . $filter_data['date_end'] . '.pdf', 'D');
    }

    private function exportToCsv($balance_sheet_data, $filter_data) {
        $filename = 'balance_sheet_' . $filter_data['date_end'] . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // الرأس
        fputcsv($output, [$this->config->get('config_name')]);
        fputcsv($output, ['الميزانية العمومية']);
        fputcsv($output, ['كما في: ' . $filter_data['date_end']]);
        fputcsv($output, []);

        // الأصول
        fputcsv($output, ['الأصول']);
        foreach ($balance_sheet_data['assets'] as $asset_group => $assets) {
            fputcsv($output, [$this->getAssetGroupName($asset_group)]);
            foreach ($assets as $asset) {
                fputcsv($output, ['    ' . $asset['account_name'], number_format($asset['balance'], 2)]);
            }
        }
        fputcsv($output, ['إجمالي الأصول', number_format($balance_sheet_data['totals']['total_assets'], 2)]);
        fputcsv($output, []);

        // الخصوم وحقوق الملكية
        fputcsv($output, ['الخصوم وحقوق الملكية']);
        foreach ($balance_sheet_data['liabilities'] as $liability_group => $liabilities) {
            fputcsv($output, [$this->getLiabilityGroupName($liability_group)]);
            foreach ($liabilities as $liability) {
                fputcsv($output, ['    ' . $liability['account_name'], number_format($liability['balance'], 2)]);
            }
        }

        fputcsv($output, ['حقوق الملكية']);
        foreach ($balance_sheet_data['equity'] as $equity) {
            fputcsv($output, ['    ' . $equity['account_name'], number_format($equity['balance'], 2)]);
        }

        fputcsv($output, ['إجمالي الخصوم وحقوق الملكية', number_format($balance_sheet_data['totals']['total_liabilities'] + $balance_sheet_data['totals']['total_equity'], 2)]);

        fclose($output);
    }

    private function getAssetGroupName($group) {
        $groups = array(
            'current_assets' => 'الأصول المتداولة',
            'non_current_assets' => 'الأصول غير المتداولة',
            'fixed_assets' => 'الأصول الثابتة',
            'intangible_assets' => 'الأصول غير الملموسة'
        );
        return $groups[$group] ?? $group;
    }

    private function getLiabilityGroupName($group) {
        $groups = array(
            'current_liabilities' => 'الخصوم المتداولة',
            'non_current_liabilities' => 'الخصوم غير المتداولة',
            'long_term_liabilities' => 'الخصوم طويلة الأجل'
        );
        return $groups[$group] ?? $group;
    }
}