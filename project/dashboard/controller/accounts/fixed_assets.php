<?php
class ControllerAccountsFixedAssets extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/fixed_assets');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/fixed_assets/print', 'user_token=' . $this->session->data['user_token'], true);

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_form'] = $this->language->get('text_form');
        $data['entry_date_end'] = $this->language->get('entry_date_end');
        $data['button_filter'] = $this->language->get('button_filter');

        $data['user_token'] = $this->session->data['user_token'];
        $data['error_warning'] = isset($this->error['warning'])?$this->error['warning']:'';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('accounts/fixed_assets_form', $data));
    }

    public function print() {
        $this->load->language('accounts/fixed_assets');
        $this->load->model('accounts/fixed_assets');

        $data['title'] = $this->language->get('print_title');
        $data['printdate'] = date('Y-m-d H:i:s');
        $data['user_token'] = $this->session->data['user_token'];
        $data['lang'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');
        $data['whoprint'] = $this->user->getUserName();

        $date_end = $this->request->post['date_end'] ?: date('Y-m-d');
        $data['end_date'] = date($this->language->get('date_format_short'), strtotime($date_end));

        $results = $this->model_accounts_fixed_assets->getFixedAssetsData($date_end);

        $data = array_merge($data, $results);

        $data['text_fixed_assets_report'] = $this->language->get('text_fixed_assets_report');
        $data['text_end_date'] = $this->language->get('text_end_date');
        $data['text_assets'] = $this->language->get('text_assets');
        $data['text_accum_depr'] = $this->language->get('text_accum_depr');
        $data['text_net_value'] = $this->language->get('text_net_value');

        $this->response->setOutput($this->load->view('accounts/fixed_assets_list', $data));
    }
}
