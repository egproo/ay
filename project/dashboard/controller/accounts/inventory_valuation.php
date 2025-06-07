<?php
class ControllerAccountsInventoryValuation extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/inventory_valuation');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/inventory_valuation/print', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('accounts/inventory_valuation_form', $data));
    }

    public function print() {
        $this->load->language('accounts/inventory_valuation');
        $this->load->model('accounts/inventory_valuation');

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
            $results = $this->model_accounts_inventory_valuation->getInventoryValuationData($date_start, $date_end);
            $data['products'] = $results['products'];
            $data['total_value'] = $results['total_value'];
        } else {
            $data['products'] = [];
            $data['total_value'] = $this->currency->format(0, $this->config->get('config_currency'));
            $this->error['warning'] = $this->language->get('error_no_data');
        }

        $data['text_inventory_valuation'] = $this->language->get('text_inventory_valuation');
        $data['text_period'] = $this->language->get('text_period');
        $data['text_from'] = $this->language->get('text_from');
        $data['text_to'] = $this->language->get('text_to');
        $data['text_total_value'] = $this->language->get('text_total_value');

        $data['text_product_name'] = $this->language->get('text_product_name');
        $data['text_opening_qty'] = $this->language->get('text_opening_qty');
        $data['text_in_qty'] = $this->language->get('text_in_qty');
        $data['text_out_qty'] = $this->language->get('text_out_qty');
        $data['text_closing_qty'] = $this->language->get('text_closing_qty');
        $data['text_average_cost'] = $this->language->get('text_average_cost');
        $data['text_inventory_value'] = $this->language->get('text_inventory_value');

        $this->response->setOutput($this->load->view('accounts/inventory_valuation_list', $data));
    }
}

