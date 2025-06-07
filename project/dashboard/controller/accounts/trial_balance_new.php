<?php
class ControllerAccountsTrialBalanceNew extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/trial_balance_new');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/trial_balance_new/print', 'user_token=' . $this->session->data['user_token'], true);
        
        // يمكن تحميل نموذج الحسابات لاختيار الحسابات مثلا
        $this->load->model('accounts/chartaccount');
        $data['accounts_list'] = $this->model_accounts_chartaccount->getAccountsToEntry(); // نفترض أنها تعيد قائمة الحسابات

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_form'] = $this->language->get('text_form');
        $data['entry_date_start'] = $this->language->get('entry_date_start');
        $data['entry_date_end'] = $this->language->get('entry_date_end');
        $data['entry_account_start'] = $this->language->get('entry_account_start');
        $data['entry_account_end'] = $this->language->get('entry_account_end');
        $data['button_filter'] = $this->language->get('button_filter');

        $data['user_token'] = $this->session->data['user_token'];
        
        $data['error_warning'] = isset($this->error['warning'])?$this->error['warning']:'';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/trial_balance_new_form', $data));
    }

    public function print() {
        $this->load->language('accounts/trial_balance_new');
        $this->load->model('accounts/trial_balance_new');
    
        $data['title'] = $this->language->get('print_title');
        $data['printdate'] = date('Y-m-d H:i:s');
        $data['user_token'] = $this->session->data['user_token'];
        $data['lang'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');  
        $data['whoprint'] = $this->user->getUserName();

        $date_start = $this->request->post['date_start'] ?: date('Y-01-01');
        $date_end = $this->request->post['date_end'] ?: date('Y-m-d');
        $account_start = $this->request->post['account_start'] ?: $this->model_accounts_trial_balance_new->getMinAccountCode();
        $account_end = $this->request->post['account_end'] ?: $this->model_accounts_trial_balance_new->getMaxAccountCode();

        $data['start_date'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['end_date'] = date($this->language->get('date_format_short'), strtotime($date_end));

        if ($account_start && $account_end && $date_start && $date_end) {
            $results = $this->model_accounts_trial_balance_new->getAccountRangeData($date_start, $date_end, $account_start, $account_end);
            $data['accounts'] = $results['accounts'];
            $data['sums'] = $results['sums'];
        } else {
            $data['accounts'] = [];
            $data['sums'] = [];
            $this->error['warning'] = $this->language->get('error_no_data');
        }

        $data['text_total'] = $this->language->get('text_total');
        $data['text_account_code'] = $this->language->get('text_account_code');
        $data['text_account_name'] = $this->language->get('text_account_name');
        $data['text_opening_balance_debit'] = $this->language->get('text_opening_balance_debit');
        $data['text_opening_balance_credit'] = $this->language->get('text_opening_balance_credit');
        $data['text_period_debit'] = $this->language->get('text_period_debit');
        $data['text_period_credit'] = $this->language->get('text_period_credit');
        $data['text_closing_balance_debit'] = $this->language->get('text_closing_balance_debit');
        $data['text_closing_balance_credit'] = $this->language->get('text_closing_balance_credit');
        $data['text_from'] = $this->language->get('text_from');
        $data['text_to'] = $this->language->get('text_to');
        $data['text_trial_balance_new'] = $this->language->get('text_trial_balance_new');
        $data['text_period'] = $this->language->get('text_period');

        $this->response->setOutput($this->load->view('accounts/trial_balance_new_list', $data));
    }
}
