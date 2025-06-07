<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class ControllerAccountsStatementaccount extends Controller {
    public function index() {
        $this->load->language('accounts/statementaccount');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/statementaccount/print', 'user_token=' . $this->session->data['user_token'], true);
    $this->load->model('accounts/chartaccount'); // تأكد من تحميل النموذج المناسب
    $data['accounts'] = $this->model_accounts_chartaccount->getAccountsToEntry();
	    // Additional template data
    $data['heading_title'] = $this->language->get('heading_title');
    $data['text_no_results'] = $this->language->get('text_no_results');
    $data['text_account_start'] = $this->language->get('text_account_start');
    $data['text_account_end'] = $this->language->get('text_account_end');
    $data['text_date_start'] = $this->language->get('text_date_start');
    $data['text_date_end'] = $this->language->get('text_date_end');
    $data['button_submit'] = $this->language->get('button_submit');
    $data['text_no_results'] = $this->language->get('text_no_results');
    
  	$data['header'] = $this->load->controller('common/header');
  	$data['user_token'] =  $this->session->data['user_token'];
	$data['column_left'] = $this->load->controller('common/column_left');
	$data['footer'] = $this->load->controller('common/footer');
        // Load view template
        $this->response->setOutput($this->load->view('accounts/statement_print_form', $data));
    }

public function print() {
    $this->load->language('accounts/statementaccount');
    $this->load->model('accounts/statementaccount');

    $data['whoprint'] = $this->user->getUserName();
    $data['printdate'] = date('Y-m-d h:i');
    $data['lang'] = $this->language->get('code');
    $data['direction'] = $this->language->get('direction');   
    $data['title'] = $this->language->get('print_title');
    // Date formatting and account processing
    $date_start = $this->request->post['date_start'];
    $date_end = $this->request->post['date_end'];
    if(empty($date_start) && empty($date_end)){
       $data['start_date'] = '';
       $data['end_date'] = '';     
    }else{
       $data['start_date'] = date($this->language->get('date_format_short'), strtotime($date_start));
       $data['end_date'] = date($this->language->get('date_format_short'), strtotime($date_end));      
    }
    
    $accounts = array();
    // Logic to fetch account or accounts based on the mode (single or range)
    $statement_mode = $this->request->post['statement_mode'] ?? 'single';
    if ($statement_mode == 'single' && !empty($this->request->post['account'])) {
        // Single account logic
        $account_code = $this->request->post['account'];
        $accounts = $this->model_accounts_statementaccount->getAccountsRange($account_code, $account_code,$date_start,$date_end);
    } elseif ($statement_mode == 'range' && !empty($this->request->post['account_start']) && !empty($this->request->post['account_end'])) {
        // Range logic
        $account_start = $this->request->post['account_start'];
        $account_end = $this->request->post['account_end'];
        $accounts = $this->model_accounts_statementaccount->getAccountsRange($account_start, $account_end,$date_start,$date_end);
        $data['title'] = $this->language->get('direction') == 'rtl' ? 'طباعة كشف حساب النطاق' : 'Print Range Account Statement';
    } else {
        // Handle errors or redirect
        $this->response->redirect($this->url->link('accounts/statementaccount', '', true));
        return;
    }



    $data['accounts'] = $accounts;
    $this->response->setOutput($this->load->view('accounts/statement_print', $data));
}



}
