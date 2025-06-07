<?php
class ControllerAccountsTrialBalance extends Controller {
    public function index() {
        $this->load->language('accounts/trial_balance');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/trial_balance/print', 'user_token=' . $this->session->data['user_token'], true);
        $this->load->model('accounts/chartaccount');
        $data['accounts'] = $this->model_accounts_chartaccount->getAccountsToEntry();

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_no_results'] = $this->language->get('text_no_results');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/trial_balance_form', $data));
    }

    public function print() {
        $this->load->language('accounts/trial_balance');
        $this->load->model('accounts/trial_balance');
    
        $data['title'] = $this->language->get('print_title');
        $data['printdate'] = date('Y-m-d H:i:s');
        $data['user_token'] = $this->session->data['user_token'];
        $data['lang'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');  
        $data['whoprint'] = $this->user->getUserName();
    
        // Handling dates with defaults
        $date_start = $this->request->post['date_start'] ?: date('Y-01-01'); // Default to start of current year
        $date_end = $this->request->post['date_end'] ?: date('Y-m-d'); // Default to today
    
        $data['start_date'] = date($this->language->get('date_format_short'), strtotime($date_start));
        $data['end_date'] = date($this->language->get('date_format_short'), strtotime($date_end));
    
        // Handling accounts with defaults
        $account_start = $this->request->post['account_start'] ?: $this->model_accounts_trial_balance->getMinAccountCode();
        $account_end = $this->request->post['account_end'] ?: $this->model_accounts_trial_balance->getMaxAccountCode();
    
        if ($account_start && $account_end && $date_start && $date_end) {
            $data['accounts'] = $this->model_accounts_trial_balance->getAccountRangeData($date_start, $date_end, $account_start, $account_end);
            
          /*
              echo "<pre>";
        print_r($data['accounts']);
        echo "</pre>";
        exit; 
        */
        } else {
            $data['accounts'] = [];
            $this->session->data['error'] = $this->language->get('error_no_data');
            $this->response->redirect($this->url->link('accounts/trial_balance', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }
    
        $this->response->setOutput($this->load->view('accounts/trial_balance_list', $data));
    }
    
}
