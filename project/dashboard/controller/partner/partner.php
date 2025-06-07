<?php
class ControllerPartnerPartner extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('partner/partner');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('partner/partner');
        $this->getList();
    }

    public function add() {
        $this->load->language('partner/partner');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('partner/partner');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $partner_id = $this->model_partner_partner->addPartner($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('partner/partner', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('partner/partner');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('partner/partner');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_partner_partner->editPartner($this->request->get['partner_id'], $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('partner/partner', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('partner/partner');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('partner/partner');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $partner_id) {
                $this->model_partner_partner->deletePartner($partner_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('partner/partner', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getList();
    }

    protected function getList() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('partner/partner', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('partner/partner/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('partner/partner/delete', 'user_token=' . $this->session->data['user_token'], true);

        $data['partners'] = array();

        $results = $this->model_partner_partner->getPartners();

        foreach ($results as $result) {
            $data['partners'][] = array(
                'partner_id'     => $result['partner_id'],
                'name'           => $result['name'],
                'type'           => $this->language->get('type_' . $result['type']),
                'percentage'     => $result['percentage'],
                'account_number' => $result['account_number'],
                'current_balance' => $this->currency->format($result['current_balance'], $this->config->get('config_currency')),
                'status'         => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'date_added'     => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'edit'           => $this->url->link('partner/partner/edit', 'user_token=' . $this->session->data['user_token'] . '&partner_id=' . $result['partner_id'], true)
            );
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');

        $data['column_name'] = $this->language->get('column_name');
        $data['column_type'] = $this->language->get('column_type');
        $data['column_percentage'] = $this->language->get('column_percentage');
        $data['column_account_number'] = $this->language->get('column_account_number');
        $data['column_current_balance'] = $this->language->get('column_current_balance');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_add'] = $this->language->get('button_add');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('partner/partner_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['partner_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('partner/partner', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (!isset($this->request->get['partner_id'])) {
            $data['action'] = $this->url->link('partner/partner/add', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('partner/partner/edit', 'user_token=' . $this->session->data['user_token'] . '&partner_id=' . $this->request->get['partner_id'], true);
        }

        $data['cancel'] = $this->url->link('partner/partner', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->get['partner_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $partner_info = $this->model_partner_partner->getPartner($this->request->get['partner_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($partner_info)) {
            $data['name'] = $partner_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['type'])) {
            $data['type'] = $this->request->post['type'];
        } elseif (!empty($partner_info)) {
            $data['type'] = $partner_info['type'];
        } else {
            $data['type'] = '';
        }

        $data['partnership_types'] = array(
            'mudarabah' => $this->language->get('type_mudarabah'),
            'musharakah' => $this->language->get('type_musharakah'),
            'wakala' => $this->language->get('type_wakala')
        );

        if (isset($this->request->post['percentage'])) {
            $data['percentage'] = $this->request->post['percentage'];
        } elseif (!empty($partner_info)) {
            $data['percentage'] = $partner_info['percentage'];
        } else {
            $data['percentage'] = '';
        }

        if (isset($this->request->post['profit_percentage'])) {
            $data['profit_percentage'] = $this->request->post['profit_percentage'];
        } elseif (!empty($partner_info)) {
            $data['profit_percentage'] = $partner_info['profit_percentage'];
        } else {
            $data['profit_percentage'] = '';
        }

        if (isset($this->request->post['initial_investment'])) {
            $data['initial_investment'] = $this->request->post['initial_investment'];
        } elseif (!empty($partner_info)) {
            $data['initial_investment'] = $partner_info['initial_investment'];
        } else {
            $data['initial_investment'] = '';
        }

        if (isset($this->request->post['current_balance'])) {
            $data['current_balance'] = $this->request->post['current_balance'];
        } elseif (!empty($partner_info)) {
            $data['current_balance'] = $partner_info['current_balance'];
        } else {
            $data['current_balance'] = '';
        }

        if (isset($this->request->post['account_number'])) {
            $data['account_number'] = $this->request->post['account_number'];
        } elseif (!empty($partner_info)) {
            $data['account_number'] = $partner_info['account_number'];
        } else {
            $data['account_number'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($partner_info)) {
            $data['status'] = $partner_info['status'];
        } else {
            $data['status'] = true;
        }

        // Load transactions if editing
        if (isset($this->request->get['partner_id'])) {
            $data['transactions'] = $this->model_partner_partner->getPartnerTransactions($this->request->get['partner_id']);
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('partner/partner_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'partner/partner')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (!isset($this->request->post['type']) || !in_array($this->request->post['type'], array('mudarabah', 'musharakah', 'wakala'))) {
            $this->error['type'] = $this->language->get('error_type');
        }

        if (!is_numeric($this->request->post['percentage']) || $this->request->post['percentage'] < 0 || $this->request->post['percentage'] > 100) {
            $this->error['percentage'] = $this->language->get('error_percentage');
        }

        if (!is_numeric($this->request->post['profit_percentage']) || $this->request->post['profit_percentage'] < 0 || $this->request->post['profit_percentage'] > 100) {
            $this->error['profit_percentage'] = $this->language->get('error_profit_percentage');
        }

        if (!is_numeric($this->request->post['initial_investment']) || $this->request->post['initial_investment'] < 0) {
            $this->error['initial_investment'] = $this->language->get('error_initial_investment');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'partner/partner')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function transaction() {
        $this->load->language('partner/partner');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('partner/partner');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateTransaction()) {
            $this->model_partner_partner->addTransaction($this->request->get['partner_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_transaction_success');

            $this->response->redirect($this->url->link('partner/partner/edit', 'user_token=' . $this->session->data['user_token'] . '&partner_id=' . $this->request->get['partner_id'], true));
        }

        $this->getTransactionForm();
    }

    protected function getTransactionForm() {
        $data['text_form'] = $this->language->get('text_transaction_add');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['amount'])) {
            $data['error_amount'] = $this->error['amount'];
        } else {
            $data['error_amount'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('partner/partner', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('partner/partner/transaction', 'user_token=' . $this->session->data['user_token'] . '&partner_id=' . $this->request->get['partner_id'], true);

        $data['cancel'] = $this->url->link('partner/partner/edit', 'user_token=' . $this->session->data['user_token'] . '&partner_id=' . $this->request->get['partner_id'], true);

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['amount'])) {
            $data['amount'] = $this->request->post['amount'];
        } else {
            $data['amount'] = '';
        }

        if (isset($this->request->post['description'])) {
            $data['description'] = $this->request->post['description'];
        } else {
            $data['description'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('partner/partner_transaction_form', $data));
    }

    protected function validateTransaction() {
        if (!$this->user->hasPermission('modify', 'partner/partner')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!is_numeric($this->request->post['amount']) || $this->request->post['amount'] <= 0) {
            $this->error['amount'] = $this->language->get('error_amount');
        }

        return !$this->error;
    }

    public function report() {
        $this->load->language('partner/partner');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('partner/partner');

        if (isset($this->request->get['partner_id'])) {
            $partner_id = $this->request->get['partner_id'];
        } else {
            $partner_id = 0;
        }

        $partner_info = $this->model_partner_partner->getPartner($partner_id);

        if ($partner_info) {
            $data['partner_name'] = $partner_info['name'];
            $data['partner_type'] = $this->language->get('type_' . $partner_info['type']);
            $data['partner_percentage'] = $partner_info['percentage'];
            $data['partner_profit_percentage'] = $partner_info['profit_percentage'];
            $data['partner_initial_investment'] = $this->currency->format($partner_info['initial_investment'], $this->config->get('config_currency'));
            $data['partner_current_balance'] = $this->currency->format($partner_info['current_balance'], $this->config->get('config_currency'));

            $data['transactions'] = array();

            $results = $this->model_partner_partner->getPartnerTransactions($partner_id);

            foreach ($results as $result) {
                $data['transactions'][] = array(
                    'date'        => date($this->language->get('date_format_short'), strtotime($result['date'])),
                    'description' => $result['description'],
                    'amount'      => $this->currency->format($result['amount'], $this->config->get('config_currency')),
                    'balance'     => $this->currency->format($result['balance'], $this->config->get('config_currency'))
                );
            }

            $data['heading_title'] = $this->language->get('text_financial_report');

            $data['text_partner'] = $this->language->get('text_partner');
            $data['text_type'] = $this->language->get('column_type');
            $data['text_percentage'] = $this->language->get('column_percentage');
            $data['text_profit_percentage'] = $this->language->get('entry_profit_percentage');
            $data['text_initial_investment'] = $this->language->get('entry_initial_investment');
            $data['text_current_balance'] = $this->language->get('entry_current_balance');

            $data['column_date'] = $this->language->get('column_date');
            $data['column_description'] = $this->language->get('column_description');
            $data['column_amount'] = $this->language->get('column_amount');
            $data['column_balance'] = $this->language->get('column_balance');

            $data['button_cancel'] = $this->language->get('button_cancel');

            $data['cancel'] = $this->url->link('partner/partner', 'user_token=' . $this->session->data['user_token'], true);

            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('partner/partner_report', $data));
        } else {
            return new Action('error/not_found');
        }
    }
}            