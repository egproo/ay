<?php
class ControllerAccountsBalanceSheet extends Controller {
    public function index() {
        $this->load->language('accounts/balance_sheet');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/balance_sheet/print', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/balance_sheet_print_form', $data));
    }

    public function print() {
        $this->load->language('accounts/balance_sheet');
        $this->load->model('accounts/trial_balance');
    
        $data['title'] = $this->language->get('print_title');
        $data['printdate'] = date('Y-m-d H:i:s');
        $data['user_token'] = $this->session->data['user_token'];
        $data['lang'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');  
        $data['whoprint'] = $this->user->getUserName();

        // الحصول على التواريخ ونطاق الحسابات
        $date_start = $this->request->post['start_date'] ?: date('Y-01-01');
        $date_end = $this->request->post['end_date'] ?: date('Y-m-d');
        $account_start = $this->request->post['account_start'] ?: $this->model_accounts_trial_balance->getMinAccountCode();
        $account_end = $this->request->post['account_end'] ?: $this->model_accounts_trial_balance->getMaxAccountCode();
    
        $data['start_date'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['end_date'] = date($this->language->get('date_format_short'), strtotime($date_end));
    
        // استرجاع بيانات ميزان المراجعة
        $trial_balance = $this->model_accounts_trial_balance->getAccountRangeData($date_start, $date_end, $account_start, $account_end);
        $accounts = $this->formatAccounts($trial_balance['accounts']);
        $data['accounts'] = $accounts;
        
        // تسجيل بيانات ميزان المراجعة
        //error_log("Trial Balance Data: " . print_r($trial_balance, true));

        // تصفية الحسابات حسب النوع
        $data['assets_non_current'] = $this->filterAccounts($trial_balance['accounts'], 'non_current_assets');
        $data['assets_current'] = $this->filterAccounts($trial_balance['accounts'], 'current_assets');
        $data['liabilities_non_current'] = $this->filterAccounts($trial_balance['accounts'], 'non_current_liabilities');
        $data['liabilities_current'] = $this->filterAccounts($trial_balance['accounts'], 'current_liabilities');
        $data['equity'] = $this->filterAccounts($trial_balance['accounts'], 'equity');

        // التحقق من إقفال حساب 25 (صافي أرباح (خسائر) العام)
        $profit_loss_balance = $this->calculateProfitLossAccount($trial_balance['accounts']);
        
        $profit_loss_account_closed = false;
        foreach ($trial_balance['accounts'] as $account) {
            if ($account['account_code'] == 25 && $account['closing_balance'] != 0) {
                $profit_loss_account_closed = true;
                $data['equity'][] = $account; // تضمين حساب 25 إذا كان مغلقًا
                break;
            }
        }

        if (!$profit_loss_account_closed) {
            // حساب صافي الربح أو الخسارة يدويًا وإضافته إلى الملكية
            $profit_loss_account = [
                'account_code' => 25,
                'name' => 'صافي أرباح (خسائر) العام',
                'closing_balance' => $profit_loss_balance,
                'closing_balance_formatted' => $this->currency->format($profit_loss_balance, $this->config->get('config_currency'))
            ];
            
            $data['equity'][] = $profit_loss_account;

            if ($profit_loss_balance < 0) {
                $data['total_equity'] -= abs($profit_loss_balance); // الخسارة تُطرح
            } else {
                $data['total_equity'] += $profit_loss_balance; // الربح يُضاف
            }

            // تسجيل حساب صافي الربح أو الخسارة
            //error_log("Profit/Loss Account (Manually Added): " . print_r($profit_loss_account, true));
        }

        // حساب الإجماليات
        $data['total_assets_non_current'] = $this->calculateTotal($data['assets_non_current']);
        $data['total_assets_current'] = $this->calculateTotal($data['assets_current']);
        $data['total_liabilities_non_current'] = $this->calculateTotal($data['liabilities_non_current']);
        $data['total_liabilities_current'] = $this->calculateTotal($data['liabilities_current']);
        $data['total_equity'] = $this->calculateTotal($data['equity']);

        $data['total_assets'] = $data['total_assets_non_current'] + $data['total_assets_current'];
        $data['total_liabilities'] = $data['total_liabilities_non_current'] + $data['total_liabilities_current'];
        $data['total_equity_liabilities'] = $data['total_liabilities'] + $data['total_equity'];

        // تنسيق الإجماليات
        $data['total_assets_formatted'] = $this->currency->format($data['total_assets'], $this->config->get('config_currency'));
        $data['total_liabilities_formatted'] = $this->currency->format($data['total_liabilities'], $this->config->get('config_currency'));
        $data['total_equity_formatted'] = $this->currency->format($data['total_equity'], $this->config->get('config_currency'));
        $data['total_equity_liabilities_formatted'] = $this->currency->format($data['total_equity_liabilities'], $this->config->get('config_currency'));

        // تسجيل الإجماليات
        //error_log("Total Assets: " . print_r($data['total_assets'], true));
        //error_log("Total Liabilities: " . print_r($data['total_liabilities'], true));
        //error_log("Total Equity: " . print_r($data['total_equity'], true));
        //error_log("Total Equity and Liabilities: " . print_r($data['total_equity_liabilities'], true));

        $this->response->setOutput($this->load->view('accounts/balance_sheet_print', $data));
    }

    /**
     * استثناء الحساب 25 من حسابات الملكية الأساسية
     * والتعامل معه بشكل منفصل بناءً على ما إذا كان مغلقًا أم لا
     */
    private function getAccountCodesByType($type) {
        $account_codes = [];

        switch ($type) {
            case 'non_current_assets':
                $account_codes = [111, 112, 113, 114, 115, 116, 117, 118, 119]; // إضافة المزيد حسب الحاجة
                break;
            case 'current_assets':
                $account_codes = [121, 122, 123, 124, 125, 126, 127, 128];
                break;
            case 'non_current_liabilities':
                $account_codes = [311, 312, 313, 314, 315, 316, 317, 318, 319];
                break;
            case 'current_liabilities':
                $account_codes = [321, 322, 323, 324, 325, 326, 327, 328, 329];
                break;
            case 'equity':
                $account_codes = [21, 22, 23, 24, 26, 27]; // تم إزالة الحساب 25
                break;
        }

        return $account_codes;
    }

    /**
     * تصفية الحسابات بناءً على النوع
     */
    private function filterAccounts($accounts, $type) {
        $filtered_accounts = [];
        $account_codes = $this->getAccountCodesByType($type);

        foreach ($accounts as $account) {
            if (in_array($account['account_code'], $account_codes)) {
                // حساب الأرصدة بشكل صحيح بناءً على نوع الحساب
                if ($type == 'non_current_assets' || $type == 'current_assets') {
                    $account['closing_balance'] = $account['closing_balance_debit'] - $account['closing_balance_credit'];
                } else if ($type == 'non_current_liabilities' || $type == 'current_liabilities' || $type == 'equity') {
                    $account['closing_balance'] = $account['closing_balance_credit'] - $account['closing_balance_debit'];
                }
                $account['closing_balance_formatted'] = $this->currency->format($account['closing_balance'], $this->config->get('config_currency'));
                $filtered_accounts[] = $account;
            }
        }

        // تسجيل الحسابات المفلترة
        //error_log("Filtered Accounts for type {$type}: " . print_r($filtered_accounts, true));

        return $filtered_accounts;
    }

    /**
     * تنسيق الحسابات
     */
    private function formatAccounts($accounts) {
        $formatted_accounts = [];
        foreach ($accounts as $account) {
            $account['closing_balance'] = $account['closing_balance_debit'] - $account['closing_balance_credit'];
            $account['closing_balance_formatted'] = $this->currency->format($account['closing_balance'], $this->config->get('config_currency'));
            $formatted_accounts[] = $account;
        }
        return $formatted_accounts;
    }
    
    /**
     * حساب إجمالي الحسابات
     */
    private function calculateTotal($accounts) {
        $total = 0;
        foreach ($accounts as $account) {
            $total += $account['closing_balance'];
        }

        // تسجيل إجمالي الحسابات
        //error_log("Calculated Total: " . print_r($total, true));

        return $total;
    }

    /**
     * حساب صافي الربح أو الخسارة بناءً على حسابات معينة
     */
    private function calculateProfitLossAccount($accounts) {
        $profit_loss_balance = 0;
        $balance_code_4 = 0;
        $balance_code_5 = 0;
    
        foreach ($accounts as $account) {
            if ($account['account_code'] == 4) {
                $balance_code_4 = $account['closing_balance'];
            } elseif ($account['account_code'] == 5) {
                $balance_code_5 = $account['closing_balance'];
            }
        }

        // قارن بين رصيد حساب 4 ورصيد حساب 5 لتحديد إذا كان ينبغي جمعهما أو خصم أحدهما
        if ($balance_code_5 > $balance_code_4) {
            $profit_loss_balance = ($balance_code_5 + $balance_code_4);
        } elseif ($balance_code_5 < $balance_code_4) {
            $profit_loss_balance = -1 * ($balance_code_5 + $balance_code_4);
        } else { // عندما يكونا متساويين
            $profit_loss_balance = 0;
        }
        // error_log("Profit/Loss Balance: " . print_r($profit_loss_balance, true));

        return $profit_loss_balance * 1;
    }
}
