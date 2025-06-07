<?php
class ControllerAccountsAgingReport extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/aging_report');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/aging_report/print', 'user_token=' . $this->session->data['user_token'], true);

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_form'] = $this->language->get('text_form');
        $data['entry_date_end'] = $this->language->get('entry_date_end');
        $data['button_filter'] = $this->language->get('button_filter');

        $data['user_token'] = $this->session->data['user_token'];
        $data['error_warning'] = isset($this->error['warning'])?$this->error['warning']:'';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/aging_report_form', $data));
    }

    public function print() {
        $this->load->language('accounts/aging_report');
        $this->load->model('accounts/aging_report');

        $data['title'] = $this->language->get('print_title');
        $data['printdate'] = date('Y-m-d H:i:s');
        $data['user_token'] = $this->session->data['user_token'];
        $data['lang'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');  
        $data['whoprint'] = $this->user->getUserName();

        $date_end = $this->request->post['date_end'] ?: date('Y-m-d');
        $data['end_date'] = date($this->language->get('date_format_short'), strtotime($date_end));

        if ($date_end) {
            $results = $this->model_accounts_aging_report->getAgingReportData($date_end);
            $data['buckets'] = $results['buckets'];
            $data['customers_data'] = $results['customers_data'];
        } else {
            $data['buckets'] = [
                '0-30' => $this->currency->format(0, $this->config->get('config_currency')),
                '31-60' => $this->currency->format(0, $this->config->get('config_currency')),
                '61-90' => $this->currency->format(0, $this->config->get('config_currency')),
                '>90' => $this->currency->format(0, $this->config->get('config_currency'))
            ];
            $data['customers_data'] = [];
            $this->error['warning'] = $this->language->get('error_no_data');
        }

        $data['text_aging_report'] = $this->language->get('text_aging_report');
        $data['text_period_end'] = $this->language->get('text_period_end');
        $data['text_buckets'] = $this->language->get('text_buckets');
        $data['text_customer_details'] = $this->language->get('text_customer_details');

        $data['text_0_30'] = $this->language->get('text_0_30');
        $data['text_31_60'] = $this->language->get('text_31_60');
        $data['text_61_90'] = $this->language->get('text_61_90');
        $data['text_over_90'] = $this->language->get('text_over_90');

        $data['text_customer_name'] = $this->language->get('text_customer_name');

        $this->response->setOutput($this->load->view('accounts/aging_report_list', $data));
    }
}
