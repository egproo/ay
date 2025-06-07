<?php
class ControllerAccountsVatReport extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/vat_report');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/vat_report/print', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('accounts/vat_report_form', $data));
    }

    public function print() {
        $this->load->language('accounts/vat_report');
        $this->load->model('accounts/vat_report');

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
            $results = $this->model_accounts_vat_report->getVatReportData($date_start, $date_end);
            $data['vat_sales'] = $results['vat_sales'];
            $data['vat_purchases'] = $results['vat_purchases'];
            $data['net_vat'] = $results['net_vat'];
        } else {
            $data['vat_sales'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['vat_purchases'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['net_vat'] = $this->currency->format(0, $this->config->get('config_currency'));
            $this->error['warning'] = $this->language->get('error_no_data');
        }

        $data['text_vat_report'] = $this->language->get('text_vat_report');
        $data['text_period'] = $this->language->get('text_period');
        $data['text_from'] = $this->language->get('text_from');
        $data['text_to'] = $this->language->get('text_to');
        $data['text_vat_sales'] = $this->language->get('text_vat_sales');
        $data['text_vat_purchases'] = $this->language->get('text_vat_purchases');
        $data['text_net_vat'] = $this->language->get('text_net_vat');

        $this->response->setOutput($this->load->view('accounts/vat_report_list', $data));
    }
}
