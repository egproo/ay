<?php
class ControllerAccountsTaxReturn extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/tax_return');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/tax_return/print', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('accounts/tax_return_form', $data));
    }

    public function print() {
        $this->load->language('accounts/tax_return');
        $this->load->model('accounts/tax_return');

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
            $results = $this->model_accounts_tax_return->getTaxReturnData($date_start, $date_end);
            $data['accounting_profit'] = $results['accounting_profit'];
            $data['non_deductible'] = $results['non_deductible'];
            $data['exempt_income'] = $results['exempt_income'];
            $data['taxable_profit'] = $results['taxable_profit'];
            $data['tax_rate'] = $results['tax_rate'];
            $data['tax_due'] = $results['tax_due'];
        } else {
            $data['accounting_profit'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['non_deductible'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['exempt_income'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['taxable_profit'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['tax_rate'] = 0;
            $data['tax_due'] = $this->currency->format(0, $this->config->get('config_currency'));
            $this->error['warning'] = $this->language->get('error_no_data');
        }

        $data['text_tax_return'] = $this->language->get('text_tax_return');
        $data['text_period'] = $this->language->get('text_period');
        $data['text_from'] = $this->language->get('text_from');
        $data['text_to'] = $this->language->get('text_to');
        $data['text_accounting_profit'] = $this->language->get('text_accounting_profit');
        $data['text_non_deductible'] = $this->language->get('text_non_deductible');
        $data['text_exempt_income'] = $this->language->get('text_exempt_income');
        $data['text_taxable_profit'] = $this->language->get('text_taxable_profit');
        $data['text_tax_rate'] = $this->language->get('text_tax_rate');
        $data['text_tax_due'] = $this->language->get('text_tax_due');

        $this->response->setOutput($this->load->view('accounts/tax_return_list', $data));
    }
}
