<?php
class ControllerPosShift extends Controller {
    public function index() {
        $this->load->language('pos/shift');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('pos/shift');

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['start_shift'] = $this->url->link('pos/shift/start', 'user_token=' . $this->session->data['user_token'], true);
        $data['end_shift'] = $this->url->link('pos/shift/end', 'user_token=' . $this->session->data['user_token'], true);

        $filter_data = array(
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $results = $this->model_pos_shift->getShifts($filter_data);
        $shifts_total = $this->model_pos_shift->getTotalShifts();

        $data['shifts'] = array();
        
        foreach ($results as $result) {
            $data['shifts'][] = array(
                'shift_id'       => $result['shift_id'],
                'user_name'      => $result['user_name'],
                'branch_name'    => $result['branch_name'],
                'terminal_name'  => $result['terminal_name'],
                'start_time'     => date($this->language->get('datetime_format'), strtotime($result['start_time'])),
                'end_time'       => $result['end_time'] ? date($this->language->get('datetime_format'), strtotime($result['end_time'])) : '',
                'starting_cash'  => $this->currency->format($result['starting_cash'], $this->config->get('config_currency')),
                'ending_cash'    => $result['ending_cash'] ? $this->currency->format($result['ending_cash'], $this->config->get('config_currency')) : '',
                'status'         => $this->language->get('text_status_' . $result['status']),
                'view'           => $this->url->link('pos/shift/view', 'user_token=' . $this->session->data['user_token'] . '&shift_id=' . $result['shift_id'], true),
                'end'            => $this->url->link('pos/shift/end', 'user_token=' . $this->session->data['user_token'] . '&shift_id=' . $result['shift_id'], true)
            );
        }

        $pagination = new Pagination();
        $pagination->total = $shifts_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($shifts_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($shifts_total - $this->config->get('config_limit_admin'))) ? $shifts_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $shifts_total, ceil($shifts_total / $this->config->get('config_limit_admin')));

        $data['active_shift'] = $this->model_pos_shift->getActiveShiftByUser($this->user->getId());

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/shift_list', $data));
    }

    public function start() {
        $this->load->language('pos/shift');
        $this->document->setTitle($this->language->get('heading_title_start'));
        $this->load->model('pos/shift');

        if ($this->model_pos_shift->getActiveShiftByUser($this->user->getId())) {
            $this->session->data['error'] = $this->language->get('error_active_shift_exists');
            $this->response->redirect($this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateShiftForm()) {
            $shift_id = $this->model_pos_shift->addShift(array(
                'user_id'       => $this->user->getId(),
                'branch_id'     => $this->request->post['branch_id'],
                'terminal_id'   => $this->request->post['terminal_id'],
                'starting_cash' => $this->request->post['starting_cash'],
                'notes'         => $this->request->post['notes']
            ));

            $this->session->data['success'] = $this->language->get('text_success_start');
            $this->response->redirect($this->url->link('pos/pos', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_start'),
            'href' => $this->url->link('pos/shift/start', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('pos/shift/start', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true);

        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        $this->load->model('pos/terminal');
        $data['terminals'] = $this->model_pos_terminal->getTerminals();

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['branch'])) {
            $data['error_branch'] = $this->error['branch'];
        } else {
            $data['error_branch'] = '';
        }

        if (isset($this->error['terminal'])) {
            $data['error_terminal'] = $this->error['terminal'];
        } else {
            $data['error_terminal'] = '';
        }

        if (isset($this->error['starting_cash'])) {
            $data['error_starting_cash'] = $this->error['starting_cash'];
        } else {
            $data['error_starting_cash'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/shift_form', $data));
    }

    public function end() {
        $this->load->language('pos/shift');
        $this->document->setTitle($this->language->get('heading_title_end'));
        $this->load->model('pos/shift');

        $shift_id = 0;
        
        if (isset($this->request->get['shift_id'])) {
            $shift_id = $this->request->get['shift_id'];
        } else {
            $active_shift = $this->model_pos_shift->getActiveShiftByUser($this->user->getId());
            if ($active_shift) {
                $shift_id = $active_shift['shift_id'];
            }
        }

        if (!$shift_id) {
            $this->session->data['error'] = $this->language->get('error_no_active_shift');
            $this->response->redirect($this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true));
        }

        $shift_info = $this->model_pos_shift->getShift($shift_id);
        
        if (!$shift_info || $shift_info['status'] != 'active') {
            $this->session->data['error'] = $this->language->get('error_invalid_shift');
            $this->response->redirect($this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateEndForm()) {
            // Calcular el monto total de ventas y otras transacciones
            $this->load->model('pos/transaction');
            $filter = array(
                'shift_id' => $shift_id,
                'type'     => 'sale'
            );
            $sales_total = $this->model_pos_transaction->getTransactionsTotal($filter);
            
            $expected_cash = $shift_info['starting_cash'] + $sales_total;
            $ending_cash = $this->request->post['ending_cash'];
            $cash_difference = $ending_cash - $expected_cash;

            $this->model_pos_shift->endShift($shift_id, array(
                'ending_cash'     => $ending_cash,
                'notes'           => $this->request->post['notes'],
                'expected_cash'   => $expected_cash,
                'cash_difference' => $cash_difference
            ));

            $this->session->data['success'] = $this->language->get('text_success_end');
            $this->response->redirect($this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_end'),
            'href' => $this->url->link('pos/shift/end', 'user_token=' . $this->session->data['user_token'] . '&shift_id=' . $shift_id, true)
        );

        $data['action'] = $this->url->link('pos/shift/end', 'user_token=' . $this->session->data['user_token'] . '&shift_id=' . $shift_id, true);
        $data['cancel'] = $this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true);

        // Obtener información financiera para el cierre
        $this->load->model('pos/transaction');
        $filter = array(
            'shift_id' => $shift_id,
            'type'     => 'sale'
        );
        $sales_total = $this->model_pos_transaction->getTransactionsTotal($filter);
        
        $data['shift'] = $shift_info;
        $data['sales_total'] = $this->currency->format($sales_total, $this->config->get('config_currency'));
        $data['expected_cash'] = $this->currency->format($shift_info['starting_cash'] + $sales_total, $this->config->get('config_currency'));
        $data['starting_cash'] = $this->currency->format($shift_info['starting_cash'], $this->config->get('config_currency'));
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['ending_cash'])) {
            $data['error_ending_cash'] = $this->error['ending_cash'];
        } else {
            $data['error_ending_cash'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/shift_end_form', $data));
    }

    public function view() {
        $this->load->language('pos/shift');
        $this->document->setTitle($this->language->get('heading_title_view'));
        $this->load->model('pos/shift');

        if (isset($this->request->get['shift_id'])) {
            $shift_id = $this->request->get['shift_id'];
        } else {
            $this->response->redirect($this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true));
        }

        $shift_info = $this->model_pos_shift->getShift($shift_id);
        
        if (!$shift_info) {
            $this->response->redirect($this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_view'),
            'href' => $this->url->link('pos/shift/view', 'user_token=' . $this->session->data['user_token'] . '&shift_id=' . $shift_id, true)
        );

        $data['back'] = $this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true);

        $data['shift'] = array(
            'shift_id'       => $shift_info['shift_id'],
            'user_name'      => $shift_info['user_name'],
            'branch_name'    => $shift_info['branch_name'],
            'terminal_name'  => $shift_info['terminal_name'],
            'start_time'     => date($this->language->get('datetime_format'), strtotime($shift_info['start_time'])),
            'end_time'       => $shift_info['end_time'] ? date($this->language->get('datetime_format'), strtotime($shift_info['end_time'])) : '',
            'starting_cash'  => $this->currency->format($shift_info['starting_cash'], $this->config->get('config_currency')),
            'ending_cash'    => $shift_info['ending_cash'] ? $this->currency->format($shift_info['ending_cash'], $this->config->get('config_currency')) : '',
            'expected_cash'  => $shift_info['expected_cash'] ? $this->currency->format($shift_info['expected_cash'], $this->config->get('config_currency')) : '',
            'cash_difference' => $shift_info['cash_difference'] ? $this->currency->format($shift_info['cash_difference'], $this->config->get('config_currency')) : '',
            'status'         => $this->language->get('text_status_' . $shift_info['status']),
            'notes'          => $shift_info['notes']
        );

        // Get transactions for this shift
        $this->load->model('pos/transaction');
        $transactions = $this->model_pos_transaction->getTransactionsByShift($shift_id);

        $data['transactions'] = array();
        
        foreach ($transactions as $transaction) {
            $data['transactions'][] = array(
                'transaction_id' => $transaction['transaction_id'],
                'type'           => $this->language->get('text_transaction_type_' . $transaction['type']),
                'payment_method' => $transaction['payment_method'],
                'amount'         => $this->currency->format($transaction['amount'], $this->config->get('config_currency')),
                'reference'      => $transaction['reference'],
                'created_at'     => date($this->language->get('datetime_format'), strtotime($transaction['created_at'])),
                'notes'          => $transaction['notes']
            );
        }

        // Get sales summary by payment method
        $data['payment_summary'] = array();
        $payment_methods = $this->model_pos_transaction->getPaymentMethodSummary($shift_id);
        
        foreach ($payment_methods as $method) {
            $data['payment_summary'][] = array(
                'payment_method' => $method['payment_method'],
                'total'          => $this->currency->format($method['total'], $this->config->get('config_currency')),
                'count'          => $method['count']
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/shift_view', $data));
    }

    protected function validateShiftForm() {
        if (!$this->user->hasPermission('modify', 'pos/shift')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['branch_id'])) {
            $this->error['branch'] = $this->language->get('error_branch');
        }

        if (empty($this->request->post['terminal_id'])) {
            $this->error['terminal'] = $this->language->get('error_terminal');
        }

        if (!isset($this->request->post['starting_cash']) || $this->request->post['starting_cash'] < 0) {
            $this->error['starting_cash'] = $this->language->get('error_starting_cash');
        }

        return !$this->error;
    }

    protected function validateEndForm() {
        if (!$this->user->hasPermission('modify', 'pos/shift')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!isset($this->request->post['ending_cash']) || $this->request->post['ending_cash'] < 0) {
            $this->error['ending_cash'] = $this->language->get('error_ending_cash');
        }

        return !$this->error;
    }
}