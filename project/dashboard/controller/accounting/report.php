<?php
class ControllerAccountingReport extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounting/report');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->getList();
    }

    public function trialBalance() {
        $this->load->language('accounting/report');

        $this->document->setTitle($this->language->get('heading_trial_balance'));

        $this->load->model('accounting/financial_report');

        if (isset($this->request->get['filter_date_from'])) {
            $filter_date_from = $this->request->get['filter_date_from'];
        } else {
            $filter_date_from = date('Y-m-01');
        }

        if (isset($this->request->get['filter_date_to'])) {
            $filter_date_to = $this->request->get['filter_date_to'];
        } else {
            $filter_date_to = date('Y-m-d');
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounting/report', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_trial_balance'),
            'href' => $this->url->link('accounting/report/trialBalance', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['back'] = $this->url->link('accounting/report', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('accounting/report/export', 'user_token=' . $this->session->data['user_token'] . '&report_type=trial_balance&date_from=' . $filter_date_from . '&date_to=' . $filter_date_to, true);

        $filter_data = array(
            'date_from' => $filter_date_from,
            'date_to' => $filter_date_to
        );

        $data['trial_balance'] = $this->model_accounting_financial_report->getTrialBalance($filter_data);

        // Calculate totals
        $data['total_debit'] = 0;
        $data['total_credit'] = 0;

        foreach ($data['trial_balance'] as $account) {
            $data['total_debit'] += $account['debit'];
            $data['total_credit'] += $account['credit'];
        }

        $data['filter_date_from'] = $filter_date_from;
        $data['filter_date_to'] = $filter_date_to;
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounting/trial_balance', $data));
    }

    public function incomeStatement() {
        $this->load->language('accounting/report');

        $this->document->setTitle($this->language->get('heading_income_statement'));

        $this->load->model('accounting/financial_report');

        if (isset($this->request->get['filter_date_from'])) {
            $filter_date_from = $this->request->get['filter_date_from'];
        } else {
            $filter_date_from = date('Y-m-01');
        }

        if (isset($this->request->get['filter_date_to'])) {
            $filter_date_to = $this->request->get['filter_date_to'];
        } else {
            $filter_date_to = date('Y-m-d');
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounting/report', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_income_statement'),
            'href' => $this->url->link('accounting/report/incomeStatement', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['back'] = $this->url->link('accounting/report', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('accounting/report/export', 'user_token=' . $this->session->data['user_token'] . '&report_type=income_statement&date_from=' . $filter_date_from . '&date_to=' . $filter_date_to, true);

        $filter_data = array(
            'date_from' => $filter_date_from,
            'date_to' => $filter_date_to
        );

        $income_statement = $this->model_accounting_financial_report->getIncomeStatement($filter_data);

        $data['revenue'] = $income_statement['revenue'];
        $data['total_revenue'] = $income_statement['total_revenue'];
        $data['expense'] = $income_statement['expense'];
        $data['total_expense'] = $income_statement['total_expense'];
        $data['net_income'] = $income_statement['net_income'];

        $data['filter_date_from'] = $filter_date_from;
        $data['filter_date_to'] = $filter_date_to;
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounting/income_statement', $data));
    }

    public function balanceSheet() {
        $this->load->language('accounting/report');

        $this->document->setTitle($this->language->get('heading_balance_sheet'));

        $this->load->model('accounting/financial_report');

        if (isset($this->request->get['filter_date_to'])) {
            $filter_date_to = $this->request->get['filter_date_to'];
        } else {
            $filter_date_to = date('Y-m-d');
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounting/report', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_balance_sheet'),
            'href' => $this->url->link('accounting/report/balanceSheet', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['back'] = $this->url->link('accounting/report', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('accounting/report/export', 'user_token=' . $this->session->data['user_token'] . '&report_type=balance_sheet&date_to=' . $filter_date_to, true);

        $filter_data = array(
            'date_to' => $filter_date_to
        );

        $balance_sheet = $this->model_accounting_financial_report->getBalanceSheet($filter_data);

        $data['assets'] = $balance_sheet['assets'];
        $data['total_assets'] = $balance_sheet['total_assets'];
        $data['liabilities'] = $balance_sheet['liabilities'];
        $data['total_liabilities'] = $balance_sheet['total_liabilities'];
        $data['equity'] = $balance_sheet['equity'];
        $data['total_equity'] = $balance_sheet['total_equity'];
        $data['retained_earnings'] = $balance_sheet['retained_earnings'];
        $data['total_liabilities_equity'] = $balance_sheet['total_liabilities_equity'];

        $data['filter_date_to'] = $filter_date_to;
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounting/balance_sheet', $data));
    }

    protected function getList() {
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounting/report', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['trial_balance'] = $this->url->link('accounting/report/trialBalance', 'user_token=' . $this->session->data['user_token'], true);
        $data['income_statement'] = $this->url->link('accounting/report/incomeStatement', 'user_token=' . $this->session->data['user_token'], true);
        $data['balance_sheet'] = $this->url->link('accounting/report/balanceSheet', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounting/report_list', $data));
    }

    public function export() {
        $this->load->language('accounting/report');

        $this->load->model('accounting/financial_report');

        $report_type = isset($this->request->get['report_type']) ? $this->request->get['report_type'] : '';
        $date_from = isset($this->request->get['date_from']) ? $this->request->get['date_from'] : date('Y-m-01');
        $date_to = isset($this->request->get['date_to']) ? $this->request->get['date_to'] : date('Y-m-d');

        if ($report_type == 'trial_balance') {
            $filter_data = array(
                'date_from' => $date_from,
                'date_to' => $date_to
            );

            $trial_balance = $this->model_accounting_financial_report->getTrialBalance($filter_data);

            // Create Excel file
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set title
            $sheet->setCellValue('A1', $this->language->get('heading_trial_balance'));
            $sheet->mergeCells('A1:E1');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getFont()->setSize(14);

            // Set date range
            $sheet->setCellValue('A2', $this->language->get('text_date_range') . ': ' . $date_from . ' - ' . $date_to);
            $sheet->mergeCells('A2:E2');

            // Set headers
            $sheet->setCellValue('A4', $this->language->get('column_account_code'));
            $sheet->setCellValue('B4', $this->language->get('column_account_name'));
            $sheet->setCellValue('C4', $this->language->get('column_account_type'));
            $sheet->setCellValue('D4', $this->language->get('column_debit'));
            $sheet->setCellValue('E4', $this->language->get('column_credit'));

            $sheet->getStyle('A4:E4')->getFont()->setBold(true);

            // Fill data
            $row = 5;
            $total_debit = 0;
            $total_credit = 0;

            foreach ($trial_balance as $account) {
                $sheet->setCellValue('A' . $row, $account['code']);
                $sheet->setCellValue('B' . $row, $account['name']);
                $sheet->setCellValue('C' . $row, $account['type']);
                $sheet->setCellValue('D' . $row, $account['debit']);
                $sheet->setCellValue('E' . $row, $account['credit']);

                $total_debit += $account['debit'];
                $total_credit += $account['credit'];

                $row++;
            }

            // Add totals
            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, '');
            $sheet->setCellValue('C' . $row, $this->language->get('text_total'));
            $sheet->setCellValue('D' . $row, $total_debit);
            $sheet->setCellValue('E' . $row, $total_credit);

            $sheet->getStyle('C' . $row . ':E' . $row)->getFont()->setBold(true);

            // Auto size columns
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'trial_balance_' . date('Y-m-d') . '.xlsx';
        } elseif ($report_type == 'income_statement') {
            $filter_data = array(
                'date_from' => $date_from,
                'date_to' => $date_to
            );

            $income_statement = $this->model_accounting_financial_report->getIncomeStatement($filter_data);

            // Create Excel file
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set title
            $sheet->setCellValue('A1', $this->language->get('heading_income_statement'));
            $sheet->mergeCells('A1:C1');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getFont()->setSize(14);

            // Set date range
            $sheet->setCellValue('A2', $this->language->get('text_date_range') . ': ' . $date_from . ' - ' . $date_to);
            $sheet->mergeCells('A2:C2');

            // Revenue section
            $sheet->setCellValue('A4', $this->language->get('text_revenue'));
            $sheet->getStyle('A4')->getFont()->setBold(true);
            $sheet->mergeCells('A4:C4');

            $row = 5;
            foreach ($income_statement['revenue'] as $account) {
                $sheet->setCellValue('A' . $row, $account['code']);
                $sheet->setCellValue('B' . $row, $account['name']);
                $sheet->setCellValue('C' . $row, $account['balance']);
                $row++;
            }

            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, $this->language->get('text_total_revenue'));
            $sheet->setCellValue('C' . $row, $income_statement['total_revenue']);
            $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
            $row += 2;

            // Expense section
            $sheet->setCellValue('A' . $row, $this->language->get('text_expense'));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $row++;

            foreach ($income_statement['expense'] as $account) {
                $sheet->setCellValue('A' . $row, $account['code']);
                $sheet->setCellValue('B' . $row, $account['name']);
                $sheet->setCellValue('C' . $row, $account['balance']);
                $row++;
            }

            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, $this->language->get('text_total_expense'));
            $sheet->setCellValue('C' . $row, $income_statement['total_expense']);
            $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
            $row += 2;

            // Net income
            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, $this->language->get('text_net_income'));
            $sheet->setCellValue('C' . $row, $income_statement['net_income']);
            $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);

            // Auto size columns
            foreach (range('A', 'C') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'income_statement_' . date('Y-m-d') . '.xlsx';
        } elseif ($report_type == 'balance_sheet') {
            $filter_data = array(
                'date_to' => $date_to
            );

            $balance_sheet = $this->model_accounting_financial_report->getBalanceSheet($filter_data);

            // Create Excel file
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set title
            $sheet->setCellValue('A1', $this->language->get('heading_balance_sheet'));
            $sheet->mergeCells('A1:C1');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getFont()->setSize(14);

            // Set date
            $sheet->setCellValue('A2', $this->language->get('text_as_of') . ': ' . $date_to);
            $sheet->mergeCells('A2:C2');

            // Assets section
            $sheet->setCellValue('A4', $this->language->get('text_assets'));
            $sheet->getStyle('A4')->getFont()->setBold(true);
            $sheet->mergeCells('A4:C4');

            $row = 5;
            foreach ($balance_sheet['assets'] as $account) {
                $sheet->setCellValue('A' . $row, $account['code']);
                $sheet->setCellValue('B' . $row, $account['name']);
                $sheet->setCellValue('C' . $row, $account['balance']);
                $row++;
            }

            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, $this->language->get('text_total_assets'));
            $sheet->setCellValue('C' . $row, $balance_sheet['total_assets']);
            $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
            $row += 2;

            // Liabilities section
            $sheet->setCellValue('A' . $row, $this->language->get('text_liabilities'));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $row++;

            foreach ($balance_sheet['liabilities'] as $account) {
                $sheet->setCellValue('A' . $row, $account['code']);
                $sheet->setCellValue('B' . $row, $account['name']);
                $sheet->setCellValue('C' . $row, $account['balance']);
                $row++;
            }

            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, $this->language->get('text_total_liabilities'));
            $sheet->setCellValue('C' . $row, $balance_sheet['total_liabilities']);
            $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
            $row += 2;

            // Equity section
            $sheet->setCellValue('A' . $row, $this->language->get('text_equity'));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $row++;

            foreach ($balance_sheet['equity'] as $account) {
                $sheet->setCellValue('A' . $row, $account['code']);
                $sheet->setCellValue('B' . $row, $account['name']);
                $sheet->setCellValue('C' . $row, $account['balance']);
                $row++;
            }

            // Retained earnings
            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, $this->language->get('text_retained_earnings'));
            $sheet->setCellValue('C' . $row, $balance_sheet['retained_earnings']);
            $row++;

            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, $this->language->get('text_total_equity'));
            $sheet->setCellValue('C' . $row, $balance_sheet['total_equity']);
            $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
            $row += 2;

            // Total liabilities and equity
            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, $this->language->get('text_total_liabilities_equity'));
            $sheet->setCellValue('C' . $row, $balance_sheet['total_liabilities_equity']);
            $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);

            // Auto size columns
            foreach (range('A', 'C') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'balance_sheet_' . date('Y-m-d') . '.xlsx';
        } else {
            $this->response->redirect($this->url->link('accounting/report', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        // Set headers and output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
