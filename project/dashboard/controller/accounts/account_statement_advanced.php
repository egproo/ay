<?php
/**
 * تحكم كشف الحساب المتقدم والمتكامل
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsAccountStatementAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/account_statement');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/account_statement_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/account_statement.css');
        $this->document->addScript('view/javascript/accounts/account_statement.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'account_statement',
            'record_id' => 0,
            'description' => 'عرض شاشة كشف الحساب',
            'module' => 'account_statement'
        ]);

        $this->getForm();
    }

    public function generate() {
        $this->load->language('accounts/account_statement');
        $this->load->model('accounts/account_statement_advanced');
        $this->load->model('accounts/audit_trail');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $filter_data = $this->prepareFilterData();

                // تسجيل إنشاء كشف الحساب في سجل المراجعة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_statement',
                    'table_name' => 'account_statement',
                    'record_id' => $filter_data['account_id'],
                    'description' => 'إنشاء كشف حساب للفترة: ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end'],
                    'module' => 'account_statement',
                    'business_date' => $filter_data['date_end']
                ]);

                // إنشاء كشف الحساب
                $statement_data = $this->model_accounts_account_statement_advanced->generateAccountStatement($filter_data);

                // التحقق من وجود بيانات
                if (empty($statement_data['transactions']) && $statement_data['opening_balance'] == 0) {
                    $this->session->data['warning'] = 'لا توجد حركات للحساب في الفترة المحددة';
                }

                $this->session->data['statement_data'] = $statement_data;
                $this->session->data['filter_data'] = $filter_data;

                $this->response->redirect($this->url->link('accounts/account_statement_advanced/view', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إنشاء كشف الحساب: ' . $e->getMessage();

                // تسجيل الخطأ
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'generate_statement_failed',
                    'table_name' => 'account_statement',
                    'record_id' => $filter_data['account_id'] ?? 0,
                    'description' => 'فشل في إنشاء كشف الحساب: ' . $e->getMessage(),
                    'module' => 'account_statement'
                ]);
            }
        }

        $this->getForm();
    }

    public function view() {
        $this->load->language('accounts/account_statement');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_view'));

        if (!isset($this->session->data['statement_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات كشف حساب للعرض';
            $this->response->redirect($this->url->link('accounts/account_statement_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();

        $this->response->setOutput($this->load->view('accounts/account_statement_view', $data));
    }

    public function export() {
        $this->load->language('accounts/account_statement');
        $this->load->model('accounts/account_statement_advanced');
        $this->load->model('accounts/audit_trail');

        if (!isset($this->session->data['statement_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للتصدير';
            $this->response->redirect($this->url->link('accounts/account_statement_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $format = $this->request->get['format'] ?? 'excel';
        $statement_data = $this->session->data['statement_data'];
        $filter_data = $this->session->data['filter_data'];

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_statement',
            'table_name' => 'account_statement',
            'record_id' => $filter_data['account_id'],
            'description' => "تصدير كشف الحساب بصيغة {$format}",
            'module' => 'account_statement'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($statement_data, $filter_data);
                break;
            case 'pdf':
                $this->exportToPdf($statement_data, $filter_data);
                break;
            case 'csv':
                $this->exportToCsv($statement_data, $filter_data);
                break;
            default:
                $this->exportToExcel($statement_data, $filter_data);
        }
    }

    public function print() {
        $this->load->language('accounts/account_statement');

        if (!isset($this->session->data['statement_data'])) {
            $this->session->data['error'] = 'لا توجد بيانات للطباعة';
            $this->response->redirect($this->url->link('accounts/account_statement_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareViewData();
        $data['print_mode'] = true;

        $this->response->setOutput($this->load->view('accounts/account_statement_print', $data));
    }

    public function getAccountInfo() {
        $this->load->model('accounts/chartaccount');
        $this->load->model('accounts/account_statement_advanced');

        $json = array();

        if (isset($this->request->get['account_id'])) {
            $account_id = $this->request->get['account_id'];
            $account = $this->model_accounts_chartaccount->getAccount($account_id);

            if ($account) {
                // الحصول على الرصيد الحالي
                $current_balance = $this->model_accounts_account_statement_advanced->getCurrentBalance($account_id);

                // الحصول على آخر حركة
                $last_transaction = $this->model_accounts_account_statement_advanced->getLastTransaction($account_id);

                $json = array(
                    'account_id' => $account['account_id'],
                    'account_code' => $account['account_code'],
                    'account_name' => $account['name'],
                    'account_type' => $account['account_type'],
                    'account_nature' => $account['account_nature'],
                    'current_balance' => $current_balance,
                    'current_balance_formatted' => $this->currency->format($current_balance, $this->config->get('config_currency')),
                    'last_transaction_date' => $last_transaction['transaction_date'] ?? '',
                    'allow_posting' => $account['allow_posting'] ?? 1,
                    'is_active' => $account['is_active'] ?? 1
                );
            } else {
                $json['error'] = $this->language->get('error_account_not_found');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getAccountSummary() {
        $this->load->model('accounts/account_statement_advanced');

        $json = array();

        if (isset($this->request->get['account_id'])) {
            $account_id = $this->request->get['account_id'];
            $date_start = $this->request->get['date_start'] ?? '';
            $date_end = $this->request->get['date_end'] ?? '';

            try {
                $summary = $this->model_accounts_account_statement_advanced->getAccountSummary($account_id, $date_start, $date_end);

                $json['success'] = true;
                $json['summary'] = $summary;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الحساب مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getTransactionDetails() {
        $this->load->model('accounts/account_statement_advanced');

        $json = array();

        if (isset($this->request->get['journal_id'])) {
            $journal_id = $this->request->get['journal_id'];

            try {
                $transaction_details = $this->model_accounts_account_statement_advanced->getTransactionDetails($journal_id);

                $json['success'] = true;
                $json['transaction'] = $transaction_details;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف القيد مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function compareStatements() {
        $this->load->model('accounts/account_statement_advanced');

        $json = array();

        if (isset($this->request->post['account_id']) && isset($this->request->post['period1']) && isset($this->request->post['period2'])) {
            try {
                $account_id = $this->request->post['account_id'];
                $period1 = $this->request->post['period1'];
                $period2 = $this->request->post['period2'];

                $comparison_data = $this->model_accounts_account_statement_advanced->compareStatements($account_id, $period1, $period2);

                $json['success'] = true;
                $json['comparison'] = $comparison_data;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'بيانات المقارنة غير مكتملة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getAccountAnalysis() {
        $this->load->model('accounts/account_statement_advanced');

        $json = array();

        if (isset($this->session->data['statement_data'])) {
            try {
                $statement_data = $this->session->data['statement_data'];
                $filter_data = $this->session->data['filter_data'];

                $analysis = $this->model_accounts_account_statement_advanced->analyzeAccountStatement($statement_data, $filter_data);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'لا توجد بيانات كشف حساب للتحليل';
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
            'href' => $this->url->link('accounts/account_statement_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للإجراءات
        $data['action'] = $this->url->link('accounts/account_statement_advanced/generate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['account_info_url'] = $this->url->link('accounts/account_statement_advanced/getAccountInfo', 'user_token=' . $this->session->data['user_token'], true);
        $data['account_summary_url'] = $this->url->link('accounts/account_statement_advanced/getAccountSummary', 'user_token=' . $this->session->data['user_token'], true);

        // بيانات النموذج
        $fields = ['account_id', 'date_start', 'date_end', 'include_opening_balance', 'include_closing_balance',
                   'show_running_balance', 'group_by_month', 'currency'];

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } else {
                $default_values = [
                    'date_start' => date('Y-m-01'),
                    'date_end' => date('Y-m-t'),
                    'include_opening_balance' => 1,
                    'include_closing_balance' => 1,
                    'show_running_balance' => 1,
                    'group_by_month' => 0,
                    'currency' => $this->config->get('config_currency')
                ];
                $data[$field] = $default_values[$field] ?? '';
            }
        }

        // الحصول على قوائم البيانات
        $this->load->model('accounts/chartaccount');
        $data['accounts'] = $this->model_accounts_chartaccount->getAccountsForSelection();

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

        $this->response->setOutput($this->load->view('accounts/account_statement_advanced_form', $data));
    }

    protected function prepareFilterData() {
        return array(
            'account_id' => $this->request->post['account_id'],
            'date_start' => $this->request->post['date_start'],
            'date_end' => $this->request->post['date_end'],
            'include_opening_balance' => isset($this->request->post['include_opening_balance']) ? 1 : 0,
            'include_closing_balance' => isset($this->request->post['include_closing_balance']) ? 1 : 0,
            'show_running_balance' => isset($this->request->post['show_running_balance']) ? 1 : 0,
            'group_by_month' => isset($this->request->post['group_by_month']) ? 1 : 0,
            'currency' => $this->request->post['currency'] ?? $this->config->get('config_currency')
        );
    }

    protected function prepareViewData() {
        $data = array();

        $data['statement_data'] = $this->session->data['statement_data'];
        $data['filter_data'] = $this->session->data['filter_data'];

        // معلومات الشركة
        $data['company_name'] = $this->config->get('config_name');
        $data['company_address'] = $this->config->get('config_address');

        // معلومات التقرير
        $data['report_title'] = $this->language->get('heading_title');
        $data['report_date'] = date($this->language->get('date_format_long'));
        $data['generated_by'] = $this->user->getUserName();

        // URLs
        $data['export_excel'] = $this->url->link('accounts/account_statement_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=excel', true);
        $data['export_pdf'] = $this->url->link('accounts/account_statement_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&format=pdf', true);
        $data['print_url'] = $this->url->link('accounts/account_statement_advanced/print', 'user_token=' . $this->session->data['user_token'], true);
        $data['back_url'] = $this->url->link('accounts/account_statement_advanced', 'user_token=' . $this->session->data['user_token'], true);

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/account_statement_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $data;
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('access', 'accounts/account_statement')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['account_id'])) {
            $this->error['account_id'] = 'يجب اختيار حساب';
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

    private function exportToExcel($statement_data, $filter_data) {
        require_once(DIR_SYSTEM . 'library/phpspreadsheet/vendor/autoload.php');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // إعداد الرأس
        $sheet->setTitle('كشف الحساب');
        $sheet->setCellValue('A1', $this->config->get('config_name'));
        $sheet->setCellValue('A2', 'كشف حساب: ' . $statement_data['account']['account_name']);
        $sheet->setCellValue('A3', 'من ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end']);

        // رؤوس الأعمدة
        $headers = ['التاريخ', 'رقم القيد', 'البيان', 'مدين', 'دائن', 'الرصيد'];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }

        // البيانات
        $row = 6;

        // الرصيد الافتتاحي
        if ($filter_data['include_opening_balance'] && $statement_data['opening_balance'] != 0) {
            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, '');
            $sheet->setCellValue('C' . $row, 'الرصيد الافتتاحي');
            $sheet->setCellValue('D' . $row, $statement_data['opening_balance'] > 0 ? $statement_data['opening_balance'] : 0);
            $sheet->setCellValue('E' . $row, $statement_data['opening_balance'] < 0 ? abs($statement_data['opening_balance']) : 0);
            $sheet->setCellValue('F' . $row, $statement_data['opening_balance']);
            $row++;
        }

        // المعاملات
        foreach ($statement_data['transactions'] as $transaction) {
            $sheet->setCellValue('A' . $row, $transaction['transaction_date']);
            $sheet->setCellValue('B' . $row, $transaction['journal_number']);
            $sheet->setCellValue('C' . $row, $transaction['description']);
            $sheet->setCellValue('D' . $row, $transaction['debit_amount']);
            $sheet->setCellValue('E' . $row, $transaction['credit_amount']);
            $sheet->setCellValue('F' . $row, $transaction['running_balance']);
            $row++;
        }

        // تنسيق
        $sheet->getStyle('A1:F' . $row)->getFont()->setName('Arial');
        $sheet->getStyle('A5:F5')->getFont()->setBold(true);

        // تصدير
        $filename = 'account_statement_' . $statement_data['account']['account_code'] . '_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    private function exportToPdf($statement_data, $filter_data) {
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('ERP System');
        $pdf->SetAuthor($this->config->get('config_name'));
        $pdf->SetTitle('كشف الحساب');

        $pdf->SetFont('aealarabiya', '', 12);
        $pdf->AddPage();

        // الرأس
        $pdf->Cell(0, 10, $this->config->get('config_name'), 0, 1, 'C');
        $pdf->Cell(0, 10, 'كشف حساب: ' . $statement_data['account']['account_name'], 0, 1, 'C');
        $pdf->Cell(0, 10, 'من ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end'], 0, 1, 'C');
        $pdf->Ln(10);

        // رؤوس الأعمدة
        $pdf->SetFont('aealarabiya', 'B', 8);
        $pdf->Cell(30, 8, 'التاريخ', 1, 0, 'C');
        $pdf->Cell(30, 8, 'رقم القيد', 1, 0, 'C');
        $pdf->Cell(60, 8, 'البيان', 1, 0, 'C');
        $pdf->Cell(25, 8, 'مدين', 1, 0, 'C');
        $pdf->Cell(25, 8, 'دائن', 1, 0, 'C');
        $pdf->Cell(25, 8, 'الرصيد', 1, 1, 'C');

        // البيانات
        $pdf->SetFont('aealarabiya', '', 7);

        // الرصيد الافتتاحي
        if ($filter_data['include_opening_balance'] && $statement_data['opening_balance'] != 0) {
            $pdf->Cell(30, 6, '', 1, 0, 'C');
            $pdf->Cell(30, 6, '', 1, 0, 'C');
            $pdf->Cell(60, 6, 'الرصيد الافتتاحي', 1, 0, 'R');
            $pdf->Cell(25, 6, $statement_data['opening_balance'] > 0 ? number_format($statement_data['opening_balance'], 2) : '', 1, 0, 'R');
            $pdf->Cell(25, 6, $statement_data['opening_balance'] < 0 ? number_format(abs($statement_data['opening_balance']), 2) : '', 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($statement_data['opening_balance'], 2), 1, 1, 'R');
        }

        // المعاملات
        foreach ($statement_data['transactions'] as $transaction) {
            $pdf->Cell(30, 6, $transaction['transaction_date'], 1, 0, 'C');
            $pdf->Cell(30, 6, $transaction['journal_number'], 1, 0, 'C');
            $pdf->Cell(60, 6, $transaction['description'], 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($transaction['debit_amount'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($transaction['credit_amount'], 2), 1, 0, 'R');
            $pdf->Cell(25, 6, number_format($transaction['running_balance'], 2), 1, 1, 'R');
        }

        $pdf->Output('account_statement_' . $statement_data['account']['account_code'] . '_' . date('Y-m-d') . '.pdf', 'D');
    }

    private function exportToCsv($statement_data, $filter_data) {
        $filename = 'account_statement_' . $statement_data['account']['account_code'] . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // الرأس
        fputcsv($output, [$this->config->get('config_name')]);
        fputcsv($output, ['كشف حساب: ' . $statement_data['account']['account_name']]);
        fputcsv($output, ['من ' . $filter_data['date_start'] . ' إلى ' . $filter_data['date_end']]);
        fputcsv($output, []);

        // رؤوس الأعمدة
        $headers = ['التاريخ', 'رقم القيد', 'البيان', 'مدين', 'دائن', 'الرصيد'];
        fputcsv($output, $headers);

        // الرصيد الافتتاحي
        if ($filter_data['include_opening_balance'] && $statement_data['opening_balance'] != 0) {
            $row = [
                '',
                '',
                'الرصيد الافتتاحي',
                $statement_data['opening_balance'] > 0 ? number_format($statement_data['opening_balance'], 2) : '',
                $statement_data['opening_balance'] < 0 ? number_format(abs($statement_data['opening_balance']), 2) : '',
                number_format($statement_data['opening_balance'], 2)
            ];
            fputcsv($output, $row);
        }

        // المعاملات
        foreach ($statement_data['transactions'] as $transaction) {
            $row = [
                $transaction['transaction_date'],
                $transaction['journal_number'],
                $transaction['description'],
                number_format($transaction['debit_amount'], 2),
                number_format($transaction['credit_amount'], 2),
                number_format($transaction['running_balance'], 2)
            ];

            fputcsv($output, $row);
        }

        fclose($output);
    }
}