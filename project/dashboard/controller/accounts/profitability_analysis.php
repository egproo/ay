<?php
class ControllerAccountsProfitabilityAnalysis extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/profitability_analysis');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/profitability_analysis/print', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('accounts/profitability_analysis_form', $data));
    }

    public function print() {
        $this->load->language('accounts/profitability_analysis');
        $this->load->model('accounts/profitability_analysis');

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
            $results = $this->model_accounts_profitability_analysis->getProfitabilityData($date_start, $date_end);
            $data = array_merge($data, $results);
        } else {
            $data['revenue'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['cogs'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['operating_expenses'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['gross_profit'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['operating_profit'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['other_expenses'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['net_profit'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['gross_margin'] = '0.00%';
            $data['operating_margin'] = '0.00%';
            $data['net_margin'] = '0.00%';
            $this->error['warning'] = $this->language->get('error_no_data');
        }

        $data['text_profitability_analysis'] = $this->language->get('text_profitability_analysis');
        $data['text_period'] = $this->language->get('text_period');
        $data['text_from'] = $this->language->get('text_from');
        $data['text_to'] = $this->language->get('text_to');

        $data['text_revenue'] = $this->language->get('text_revenue');
        $data['text_cogs'] = $this->language->get('text_cogs');
        $data['text_operating_expenses'] = $this->language->get('text_operating_expenses');
        $data['text_gross_profit'] = $this->language->get('text_gross_profit');
        $data['text_operating_profit'] = $this->language->get('text_operating_profit');
        $data['text_other_expenses'] = $this->language->get('text_other_expenses');
        $data['text_net_profit'] = $this->language->get('text_net_profit');

        $data['text_gross_margin'] = $this->language->get('text_gross_margin');
        $data['text_operating_margin'] = $this->language->get('text_operating_margin');
        $data['text_net_margin'] = $this->language->get('text_net_margin');

        $this->response->setOutput($this->load->view('accounts/profitability_analysis_list', $data));
    }
}
