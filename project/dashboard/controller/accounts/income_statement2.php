<?php
class ControllerAccountsIncomeStatement2 extends Controller {
    public function index() {
        $this->load->language('accounts/income_statement');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/income_statement/print', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/income_statement_print_form', $data));
    }

    public function print() {
        $this->load->language('accounts/income_statement');
        $this->load->model('accounts/trial_balance');

        $data['title'] = $this->language->get('print_title');
        $data['printdate'] = date('Y-m-d H:i:s');
        $data['user_token'] = $this->session->data['user_token'];
        $data['lang'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');  
        $data['whoprint'] = $this->user->getUserName();

        $date_start = $this->request->post['start_date'] ?: date('Y-01-01');
        $date_end = $this->request->post['end_date'] ?: date('Y-m-d');
        $account_start = $this->request->post['account_start'] ?: $this->model_accounts_trial_balance->getMinAccountCode();
        $account_end = $this->request->post['account_end'] ?: $this->model_accounts_trial_balance->getMaxAccountCode();

        $data['start_date'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['end_date'] = date($this->language->get('date_format_short'), strtotime($date_end));

        // استرجاع بيانات ميزان المراجعة
        $trial_balance = $this->model_accounts_trial_balance->getAccountRangeData($date_start, $date_end, $account_start, $account_end);

        // تسجيل بيانات ميزان المراجعة
        error_log("Trial Balance Data: " . print_r($trial_balance, true));

        // جمع البيانات وتصنيفها
        $data['revenues'] = $this->filterAccounts($trial_balance['accounts'], 'revenues');
        $data['costs'] = $this->filterAccounts($trial_balance['accounts'], 'costs');
        $data['expenses'] = $this->filterAccounts($trial_balance['accounts'], 'expenses');
        $data['other_income'] = $this->filterAccounts($trial_balance['accounts'], 'other_income');
        $data['other_expenses'] = $this->filterAccounts($trial_balance['accounts'], 'other_expenses');
        $data['financial_income'] = $this->filterAccounts($trial_balance['accounts'], 'financial_income');
        $data['financial_expenses'] = $this->filterAccounts($trial_balance['accounts'], 'financial_expenses');

        // حساب الإجماليات
        $data['total_revenues'] = $this->calculateTotal($data['revenues']);
        $data['total_costs'] = $this->calculateTotal($data['costs']);
        $data['total_expenses'] = $this->calculateTotal($data['expenses']);
        $data['total_other_income'] = $this->calculateTotal($data['other_income']);
        $data['total_other_expenses'] = $this->calculateTotal($data['other_expenses']);
        $data['total_financial_income'] = $this->calculateTotal($data['financial_income']);
        $data['total_financial_expenses'] = $this->calculateTotal($data['financial_expenses']);

        // حساب صافي الربح أو الخسارة
        $data['net_profit_loss'] = $data['total_revenues'] - $data['total_costs'] - $data['total_expenses'] + $data['total_other_income'] - $data['total_other_expenses'] + $data['total_financial_income'] - $data['total_financial_expenses'];

        // تنسيق الإجماليات
        $data['total_revenues_formatted'] = $this->currency->format($data['total_revenues'], $this->config->get('config_currency'));
        $data['total_costs_formatted'] = $this->currency->format($data['total_costs'], $this->config->get('config_currency'));
        $data['total_expenses_formatted'] = $this->currency->format($data['total_expenses'], $this->config->get('config_currency'));
        $data['total_other_income_formatted'] = $this->currency->format($data['total_other_income'], $this->config->get('config_currency'));
        $data['total_other_expenses_formatted'] = $this->currency->format($data['total_other_expenses'], $this->config->get('config_currency'));
        $data['total_financial_income_formatted'] = $this->currency->format($data['total_financial_income'], $this->config->get('config_currency'));
        $data['total_financial_expenses_formatted'] = $this->currency->format($data['total_financial_expenses'], $this->config->get('config_currency'));
        $data['net_profit_loss_formatted'] = $this->currency->format($data['net_profit_loss'], $this->config->get('config_currency'));

        // تسجيل الإجماليات
        error_log("Total Revenues: " . print_r($data['total_revenues'], true));
        error_log("Total Costs: " . print_r($data['total_costs'], true));
        error_log("Total Expenses: " . print_r($data['total_expenses'], true));
        error_log("Net Profit/Loss: " . print_r($data['net_profit_loss'], true));

        $this->response->setOutput($this->load->view('accounts/income_statement_print', $data));
    }

    private function filterAccounts($accounts, $type) {
        $filtered_accounts = [];
        $account_codes = $this->getAccountCodesByType($type);

        foreach ($accounts as $account) {
            if (in_array($account['account_code'], $account_codes)) {
                // حساب الأرصدة بشكل صحيح بناءً على نوع الحساب
                if (in_array($type, ['revenues', 'other_income', 'financial_income'])) {
                    $account['closing_balance'] = $account['closing_balance_credit'] - $account['closing_balance_debit'];
                } else {
                    $account['closing_balance'] = $account['closing_balance_debit'] - $account['closing_balance_credit'];
                }
                $account['closing_balance_formatted'] = $this->currency->format($account['closing_balance'], $this->config->get('config_currency'));
                $filtered_accounts[] = $account;
            }
        }

        // تسجيل الحسابات المفلترة
        error_log("Filtered Accounts for type {$type}: " . print_r($filtered_accounts, true));

        return $filtered_accounts;
    }

    private function getAccountCodesByType($type) {
        $account_codes = [];

        switch ($type) {
            case 'revenues':
                $account_codes = [511, 412, 413, 414, 415, 416, 417, 418, 419]; // Add more as needed
                break;
            case 'costs':
                $account_codes = [421, 422, 423, 424, 425, 426, 427, 428];
                break;
            case 'expenses':
                $account_codes = [431, 432, 433, 434, 435, 436, 437, 438];
                break;
            case 'other_income':
                $account_codes = [441, 442, 443, 444, 445, 446, 447, 448, 449];
                break;
            case 'other_expenses':
                $account_codes = [451, 452, 453, 454, 455, 456, 457, 458, 459];
                break;
            case 'financial_income':
                $account_codes = [461, 462, 463, 464, 465, 466, 467, 468, 469];
                break;
            case 'financial_expenses':
                $account_codes = [471, 472, 473, 474, 475, 476, 477, 478, 479];
                break;
        }

        return $account_codes;
    }

    private function calculateTotal($accounts) {
        $total = 0;
        foreach ($accounts as $account) {
            $total += $account['closing_balance'];
        }

        // تسجيل إجمالي الحسابات
        error_log("Calculated Total: " . print_r($total, true));

        return $total;
    }
}