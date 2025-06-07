<?php
class ControllerAccountsFixedAssetsReport extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/fixed_assets_report');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/fixed_assets_report/print', 'user_token=' . $this->session->data['user_token'], true);

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

        $this->response->setOutput($this->load->view('accounts/fixed_assets_report_form', $data));
    }

    public function print() {
        $this->load->language('accounts/fixed_assets_report');
        $this->load->model('accounts/fixed_assets_report');

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
            $results = $this->model_accounts_fixed_assets_report->getFixedAssetsReportData($date_start, $date_end);
            $data['assets'] = $results['assets'];
            $data['total_depreciation'] = $results['total_depreciation'];
        } else {
            $data['assets'] = [];
            $data['total_depreciation'] = $this->currency->format(0, $this->config->get('config_currency'));
            $this->error['warning'] = $this->language->get('error_no_data');
        }

        $data['text_fixed_assets_report'] = $this->language->get('text_fixed_assets_report');
        $data['text_period'] = $this->language->get('text_period');
        $data['text_from'] = $this->language->get('text_from');
        $data['text_to'] = $this->language->get('text_to');
        $data['text_total_depreciation'] = $this->language->get('text_total_depreciation');

        $data['text_asset_code'] = $this->language->get('text_asset_code');
        $data['text_asset_name'] = $this->language->get('text_asset_name');
        $data['text_purchase_date'] = $this->language->get('text_purchase_date');
        $data['text_purchase_value'] = $this->language->get('text_purchase_value');
        $data['text_current_value'] = $this->language->get('text_current_value');
        $data['text_method'] = $this->language->get('text_method');
        $data['text_useful_life'] = $this->language->get('text_useful_life');
        $data['text_salvage_value'] = $this->language->get('text_salvage_value');
        $data['text_period_depreciation'] = $this->language->get('text_period_depreciation');
        $data['text_new_current_value'] = $this->language->get('text_new_current_value');

        $this->response->setOutput($this->load->view('accounts/fixed_assets_report_list', $data));
    }
}
