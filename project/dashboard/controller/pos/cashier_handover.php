<?php
class ControllerPosCashierHandover extends Controller {
    public function index() {
        $this->load->language('pos/cashier_handover');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('pos/cashier_handover');

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
            'href' => $this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('pos/cashier_handover/add', 'user_token=' . $this->session->data['user_token'], true);

        $filter_data = array(
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $results = $this->model_pos_cashier_handover->getHandovers($filter_data);
        $handovers_total = $this->model_pos_cashier_handover->getTotalHandovers();

        $data['handovers'] = array();
        
        foreach ($results as $result) {
            $data['handovers'][] = array(
                'handover_id'    => $result['handover_id'],
                'shift_id'       => $result['shift_id'],
                'from_user'      => $result['from_user'],
                'to_user'        => $result['to_user'],
                'amount'         => $this->currency->format($result['amount'], $this->config->get('config_currency')),
                'handover_time'  => date($this->language->get('datetime_format'), strtotime($result['handover_time'])),
                'view'           => $this->url->link('pos/cashier_handover/view', 'user_token=' . $this->session->data['user_token'] . '&handover_id=' . $result['handover_id'], true)
            );
        }

        $pagination = new Pagination();
        $pagination->total = $handovers_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($handovers_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($handovers_total - $this->config->get('config_limit_admin'))) ? $handovers_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $handovers_total, ceil($handovers_total / $this->config->get('config_limit_admin')));

        // Check if user has active shift for handover
        $this->load->model('pos/shift');
        $data['active_shift'] = $this->model_pos_shift->getActiveShiftByUser($this->user->getId());

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/cashier_handover_list', $data));
    }

    public function add() {
        $this->load->language('pos/cashier_handover');
        $this->document->setTitle($this->language->get('heading_title_add'));
        $this->load->model('pos/cashier_handover');
        $this->load->model('pos/shift');

        // Check if user has active shift
        $active_shift = $this->model_pos_shift->getActiveShiftByUser($this->user->getId());
        
        if (!$active_shift) {
            $this->session->data['error'] = $this->language->get('error_no_active_shift');
            $this->response->redirect($this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $handover_id = $this->model_pos_cashier_handover->addHandover(array(
                'shift_id'      => $active_shift['shift_id'],
                'from_user_id'  => $this->user->getId(),
                'to_user_id'    => $this->request->post['to_user_id'],
                'amount'        => $this->request->post['amount'],
                'notes'         => $this->request->post['notes']
            ));

            // Create transaction record
            $this->load->model('pos/transaction');
            $transaction_data = array(
                'shift_id'       => $active_shift['shift_id'],
                'type'           => 'cash_out',
                'payment_method' => 'cash',
                'amount'         => $this->request->post['amount'],
                'reference'      => 'Handover #' . $handover_id,
                'notes'          => $this->request->post['notes']
            );
            $this->model_pos_transaction->addTransaction($transaction_data);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_add'),
            'href' => $this->url->link('pos/cashier_handover/add', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('pos/cashier_handover/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'], true);

        // Get all users who could receive handover
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();

        // Remove current user from list
        foreach ($data['users'] as $key => $user) {
            if ($user['user_id'] == $this->user->getId()) {
                unset($data['users'][$key]);
            }
        }

        // Cash available in current shift
        $data['current_cash'] = $active_shift['starting_cash'];

        // Get sales for this shift to calculate available cash
        $this->load->model('pos/transaction');
        $cash_in = $this->model_pos_transaction->getTransactionsTotalByType($active_shift['shift_id'], 'cash_in');
        $cash_out = $this->model_pos_transaction->getTransactionsTotalByType($active_shift['shift_id'], 'cash_out');
        $cash_sales = $this->model_pos_transaction->getCashSalesTotal($active_shift['shift_id']);
        
        $data['available_cash'] = $active_shift['starting_cash'] + $cash_in + $cash_sales - $cash_out;
        $data['formatted_available_cash'] = $this->currency->format($data['available_cash'], $this->config->get('config_currency'));

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['to_user_id'])) {
            $data['error_to_user'] = $this->error['to_user_id'];
        } else {
            $data['error_to_user'] = '';
        }

        if (isset($this->error['amount'])) {
            $data['error_amount'] = $this->error['amount'];
        } else {
            $data['error_amount'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/cashier_handover_form', $data));
    }

    public function view() {
        $this->load->language('pos/cashier_handover');
        $this->document->setTitle($this->language->get('heading_title_view'));
        $this->load->model('pos/cashier_handover');

        if (isset($this->request->get['handover_id'])) {
            $handover_id = $this->request->get['handover_id'];
        } else {
            $this->response->redirect($this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'], true));
        }

        $handover_info = $this->model_pos_cashier_handover->getHandover($handover_id);
        
        if (!$handover_info) {
            $this->response->redirect($this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_view'),
            'href' => $this->url->link('pos/cashier_handover/view', 'user_token=' . $this->session->data['user_token'] . '&handover_id=' . $handover_id, true)
        );

        $data['back'] = $this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'], true);

        $data['handover'] = array(
            'handover_id'    => $handover_info['handover_id'],
            'shift_id'       => $handover_info['shift_id'],
            'from_user'      => $handover_info['from_user'],
            'to_user'        => $handover_info['to_user'],
            'amount'         => $this->currency->format($handover_info['amount'], $this->config->get('config_currency')),
            'handover_time'  => date($this->language->get('datetime_format'), strtotime($handover_info['handover_time'])),
            'notes'          => $handover_info['notes']
        );

        // Get shift details
        $this->load->model('pos/shift');
        $shift_info = $this->model_pos_shift->getShift($handover_info['shift_id']);
        
        if ($shift_info) {
            $data['shift'] = array(
                'shift_id'      => $shift_info['shift_id'],
                'terminal_name' => $shift_info['terminal_name'],
                'branch_name'   => $shift_info['branch_name'],
                'start_time'    => date($this->language->get('datetime_format'), strtotime($shift_info['start_time'])),
                'status'        => $this->language->get('text_status_' . $shift_info['status'])
            );
        } else {
            $data['shift'] = array();
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/cashier_handover_view', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'pos/cashier_handover')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['to_user_id'])) {
            $this->error['to_user_id'] = $this->language->get('error_to_user');
        }

        if (!isset($this->request->post['amount']) || $this->request->post['amount'] <= 0) {
            $this->error['amount'] = $this->language->get('error_amount');
        } else {
            // Check if amount is available in current shift
            $this->load->model('pos/shift');
            $active_shift = $this->model_pos_shift->getActiveShiftByUser($this->user->getId());
            
            if ($active_shift) {
                $this->load->model('pos/transaction');
                $cash_in = $this->model_pos_transaction->getTransactionsTotalByType($active_shift['shift_id'], 'cash_in');
                $cash_out = $this->model_pos_transaction->getTransactionsTotalByType($active_shift['shift_id'], 'cash_out');
                $cash_sales = $this->model_pos_transaction->getCashSalesTotal($active_shift['shift_id']);
                
                $available_cash = $active_shift['starting_cash'] + $cash_in + $cash_sales - $cash_out;
                
                if ($this->request->post['amount'] > $available_cash) {
                    $this->error['amount'] = $this->language->get('error_insufficient_cash');
                }
            }
        }

        return !$this->error;
    }
}