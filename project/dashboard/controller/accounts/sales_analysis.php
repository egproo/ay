<?php
class ControllerAccountsSalesAnalysis extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/sales_analysis');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/sales_analysis/print', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('accounts/sales_analysis_form', $data));
    }

    public function print() {
        $this->load->language('accounts/sales_analysis');
        $this->load->model('accounts/sales_analysis');

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
            $results = $this->model_accounts_sales_analysis->getSalesAnalysisData($date_start, $date_end);
            $data['products'] = $results['products'];
            $data['total_sales'] = $results['total_sales'];
        } else {
            $data['products'] = [];
            $data['total_sales'] = $this->currency->format(0, $this->config->get('config_currency'));
            $this->error['warning'] = $this->language->get('error_no_data');
        }
        $data['text_sales_analysis'] = $this->language->get('text_sales_analysis');
        $data['text_period'] = $this->language->get('text_period');
        $data['text_from'] = $this->language->get('text_from');
        $data['text_to'] = $this->language->get('text_to');
        $data['text_total_sales'] = $this->language->get('text_total_sales');

        $data['text_product_name'] = $this->language->get('text_product_name');
        $data['text_total_quantity'] = $this->language->get('text_total_quantity');
        $data['text_total_sales_col'] = $this->language->get('text_total_sales_col');
        $data['text_avg_price'] = $this->language->get('text_avg_price');

        $this->response->setOutput($this->load->view('accounts/sales_analysis_list', $data));
    }
}
