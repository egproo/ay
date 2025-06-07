<?php
class ControllerAccountsChangesInEquity extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/changes_in_equity');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/changes_in_equity/print', 'user_token=' . $this->session->data['user_token'], true);

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_form'] = $this->language->get('text_form');
        $data['entry_date_start'] = $this->language->get('entry_date_start');
        $data['entry_date_end'] = $this->language->get('entry_date_end');
        $data['button_filter'] = $this->language->get('button_filter');

        $data['user_token'] = $this->session->data['user_token'];
        $data['error_warning'] = isset($this->error['warning'])?$this->error['warning']:'';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/changes_in_equity_form', $data));
    }

    public function print() {
        $this->load->language('accounts/changes_in_equity');
        $this->load->model('accounts/changes_in_equity');

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
            $results = $this->model_accounts_changes_in_equity->getChangesInEquityData($date_start, $date_end);
            $data['accounts'] = $results['accounts'];
            $data['total_opening'] = $results['total_opening'];
            $data['total_movement'] = $results['total_movement'];
            $data['total_closing'] = $results['total_closing'];
        } else {
            $data['accounts'] = [];
            $data['total_opening'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['total_movement'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['total_closing'] = $this->currency->format(0, $this->config->get('config_currency'));
            $this->error['warning'] = $this->language->get('error_no_data');
        }

        $data['text_changes_in_equity'] = $this->language->get('text_changes_in_equity');
        $data['text_period'] = $this->language->get('text_period');
        $data['text_from'] = $this->language->get('text_from');
        $data['text_to'] = $this->language->get('text_to');
        $data['text_opening_balance'] = $this->language->get('text_opening_balance');
        $data['text_movement'] = $this->language->get('text_movement');
        $data['text_closing_balance'] = $this->language->get('text_closing_balance');
        $data['text_account_name'] = $this->language->get('text_account_name');
        $data['text_total'] = $this->language->get('text_total');

        $this->response->setOutput($this->load->view('accounts/changes_in_equity_list', $data));
    }
}
