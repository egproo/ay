<?php
class ControllerAccountsCashFlow extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/cash_flow');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('accounts/cash_flow/print', 'user_token=' . $this->session->data['user_token'], true);

        $data['heading_title'] = $this->language->get('heading_title');
        // بقية النصوص...

        $data['user_token'] = $this->session->data['user_token'];
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        // اجلب التاريخ الافتراضي أو من GET/POST
        if (isset($this->request->post['date_start'])) {
            $data['date_start'] = $this->request->post['date_start'];
        } else {
            $data['date_start'] = date('Y-01-01');
        }
        if (isset($this->request->post['date_end'])) {
            $data['date_end'] = $this->request->post['date_end'];
        } else {
            $data['date_end'] = date('Y-m-d');
        }

        // تحميل أجزاء التصميم
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // حمل الفيو
        $this->response->setOutput($this->load->view('accounts/cash_flow_form', $data));
    }

    public function print() {
        $this->load->language('accounts/cash_flow');
        $this->load->model('accounts/cash_flow');

        $date_start = $this->request->post['date_start'] ?: date('Y-01-01');
        $date_end   = $this->request->post['date_end'] ?: date('Y-m-d');

        $data['start_date'] = $date_start;
        $data['end_date']   = $date_end;

        // 1) أحضر بيانات التدفقات
        $results = $this->model_accounts_cash_flow->getCashFlowData($date_start, $date_end);

        $data['operating'] = $results['operating'];
        $data['investing'] = $results['investing'];
        $data['financing'] = $results['financing'];

        // 2) رصيد النقدية أول الفترة
        $openingBalance = $this->model_accounts_cash_flow->getOpeningCashBalance($date_start);

        // 3) صافي التغير
        $netChange = $results['net_change'];

        // 4) الرصيد الختامي
        $closingBalance = $openingBalance + $netChange;

        $data['total_operating'] = $results['total_operating'];
        $data['total_investing'] = $results['total_investing'];
        $data['total_financing'] = $results['total_financing'];
        $data['net_change'] = $netChange;
        $data['opening_balance'] = $openingBalance;
        $data['closing_balance'] = $closingBalance;

        // اعداد عرض
        $this->response->setOutput($this->load->view('accounts/cash_flow_list', $data));
    }
}
