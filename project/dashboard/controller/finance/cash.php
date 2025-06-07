<?php
/**
 * تحكم إدارة النقدية والصناديق المحسن
 * يدعم متابعة الأرصدة والحركات النقدية والتكامل المحاسبي
 */
class ControllerFinanceCash extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('finance/cash');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('cash/cash');
        $this->getList();
    }

    public function add() {
        $this->load->language('finance/cash');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('cash/cash');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $cash_box_id = $this->model_cash_cash->addCashBox($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('finance/cash');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('cash/cash');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_cash_cash->editCashBox($this->request->get['cash_box_id'], $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('finance/cash');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('cash/cash');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $cash_box_id) {
                $this->model_cash_cash->deleteCashBox($cash_box_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function transactions() {
        $this->load->language('finance/cash');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('cash/cash');

        $this->getTransactions();
    }

    public function addTransaction() {
        $this->load->language('finance/cash');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('cash/cash');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateTransaction()) {
            $transaction_id = $this->model_cash_cash->addCashTransaction($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success_transaction');

            $this->response->redirect($this->url->link('finance/cash/transactions', 'user_token=' . $this->session->data['user_token'] . '&cash_box_id=' . $this->request->post['cash_box_id'], true));
        }

        $this->getTransactionForm();
    }

    protected function getTransactions() {
        if (isset($this->request->get['cash_box_id'])) {
            $cash_box_id = (int)$this->request->get['cash_box_id'];
        } else {
            $this->response->redirect($this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'], true)
        );

        // الحصول على بيانات الصندوق
        $cash_box = $this->model_cash_cash->getCashBox($cash_box_id);
        if ($cash_box) {
            $data['cash_box'] = $cash_box;

            $data['breadcrumbs'][] = array(
                'text' => $cash_box['name'] . ' - ' . $this->language->get('text_transactions'),
                'href' => $this->url->link('finance/cash/transactions', 'user_token=' . $this->session->data['user_token'] . '&cash_box_id=' . $cash_box_id, true)
            );

            $data['add_transaction'] = $this->url->link('finance/cash/addTransaction', 'user_token=' . $this->session->data['user_token'] . '&cash_box_id=' . $cash_box_id, true);
            $data['back'] = $this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'], true);

            // الحصول على الحركات
            $filter_data = array(
                'start' => 0,
                'limit' => 20
            );

            $data['transactions'] = $this->model_cash_cash->getCashTransactions($cash_box_id, $filter_data);

            foreach ($data['transactions'] as &$transaction) {
                $transaction['amount_formatted'] = $this->currency->format($transaction['amount'], $cash_box['currency_code']);
                $transaction['transaction_date_formatted'] = date($this->language->get('date_format_short'), strtotime($transaction['transaction_date']));
            }
        } else {
            $this->response->redirect($this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/cash_transactions', $data));
    }

    protected function getTransactionForm() {
        if (isset($this->request->get['cash_box_id'])) {
            $cash_box_id = (int)$this->request->get['cash_box_id'];
        } else {
            $this->response->redirect($this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'], true)
        );

        // الحصول على بيانات الصندوق
        $cash_box = $this->model_cash_cash->getCashBox($cash_box_id);
        if ($cash_box) {
            $data['cash_box'] = $cash_box;

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_add_transaction'),
                'href' => $this->url->link('finance/cash/addTransaction', 'user_token=' . $this->session->data['user_token'] . '&cash_box_id=' . $cash_box_id, true)
            );

            $data['action'] = $this->url->link('finance/cash/addTransaction', 'user_token=' . $this->session->data['user_token'] . '&cash_box_id=' . $cash_box_id, true);
            $data['cancel'] = $this->url->link('finance/cash/transactions', 'user_token=' . $this->session->data['user_token'] . '&cash_box_id=' . $cash_box_id, true);

            // بيانات النموذج
            if (isset($this->request->post['transaction_type'])) {
                $data['transaction_type'] = $this->request->post['transaction_type'];
            } else {
                $data['transaction_type'] = 'in';
            }

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

            if (isset($this->request->post['reference_number'])) {
                $data['reference_number'] = $this->request->post['reference_number'];
            } else {
                $data['reference_number'] = '';
            }

            if (isset($this->request->post['transaction_date'])) {
                $data['transaction_date'] = $this->request->post['transaction_date'];
            } else {
                $data['transaction_date'] = date('Y-m-d');
            }

            $data['cash_box_id'] = $cash_box_id;
        } else {
            $this->response->redirect($this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/cash_transaction_form', $data));
    }

    protected function getList() {
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        if (isset($this->request->get['filter_active'])) {
            $filter_active = $this->request->get['filter_active'];
        } else {
            $filter_active = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'name';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
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
            'href' => $this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('finance/cash/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('finance/cash/delete', 'user_token=' . $this->session->data['user_token'], true);

        $data['cash_boxes'] = array();

        $filter_data = array(
            'filter_name' => $filter_name,
            'filter_active' => $filter_active,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $cash_box_total = $this->model_cash_cash->getTotalCashBoxes($filter_data);
        $results = $this->model_cash_cash->getCashBoxes($filter_data);

        foreach ($results as $result) {
            $data['cash_boxes'][] = array(
                'cash_box_id' => $result['cash_box_id'],
                'name' => $result['name'],
                'account_code' => $result['account_code'],
                'account_name' => $result['account_name'],
                'current_balance' => $this->currency->format($result['current_balance'], $this->config->get('config_currency')),
                'currency_code' => $result['currency_code'],
                'is_active' => ($result['is_active'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'edit' => $this->url->link('finance/cash/edit', 'user_token=' . $this->session->data['user_token'] . '&cash_box_id=' . $result['cash_box_id'], true),
                'transactions' => $this->url->link('finance/cash/transactions', 'user_token=' . $this->session->data['user_token'] . '&cash_box_id=' . $result['cash_box_id'], true)
            );
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');

        $data['column_name'] = $this->language->get('column_name');
        $data['column_code'] = $this->language->get('column_code');
        $data['column_balance'] = $this->language->get('column_balance');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_add'] = $this->language->get('button_add');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/cash_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['cash_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

        if (isset($this->error['code'])) {
            $data['error_code'] = $this->error['code'];
        } else {
            $data['error_code'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (!isset($this->request->get['cash_id'])) {
            $data['action'] = $this->url->link('finance/cash/add', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('finance/cash/edit', 'user_token=' . $this->session->data['user_token'] . '&cash_id=' . $this->request->get['cash_id'], true);
        }

        $data['cancel'] = $this->url->link('finance/cash', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->get['cash_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $cash_info = $this->model_finance_cash->getCash($this->request->get['cash_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($cash_info)) {
            $data['name'] = $cash_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['code'])) {
        $data['code'] = $this->request->post['code'];
        } elseif (!empty($cash_info)) {
        $data['code'] = $cash_info['code'];
        } else {
        $data['code'] = '';
        }
        if (isset($this->request->post['responsible_user_id'])) {
                $data['responsible_user_id'] = $this->request->post['responsible_user_id'];
            } elseif (!empty($cash_info)) {
                $data['responsible_user_id'] = $cash_info['responsible_user_id'];
            } else {
                $data['responsible_user_id'] = 0;
            }

            $this->load->model('user/user');
            $data['users'] = $this->model_user_user->getUsers();

            if (isset($this->request->post['status'])) {
                $data['status'] = $this->request->post['status'];
            } elseif (!empty($cash_info)) {
                $data['status'] = $cash_info['status'];
            } else {
                $data['status'] = true;
            }

            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('finance/cash_form', $data));
        }

        protected function validateForm() {
            if (!$this->user->hasPermission('modify', 'finance/cash')) {
                $this->error['warning'] = $this->language->get('error_permission');
            }

            if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
                $this->error['name'] = $this->language->get('error_name');
            }

            if (empty($this->request->post['account_id'])) {
                $this->error['account_id'] = $this->language->get('error_account');
            }

            if (!empty($this->request->post['opening_balance']) && !is_numeric($this->request->post['opening_balance'])) {
                $this->error['opening_balance'] = $this->language->get('error_opening_balance');
            }

            return !$this->error;
        }

        protected function validateTransaction() {
            if (!$this->user->hasPermission('modify', 'finance/cash')) {
                $this->error['warning'] = $this->language->get('error_permission');
            }

            if (empty($this->request->post['cash_box_id'])) {
                $this->error['cash_box_id'] = $this->language->get('error_cash_box');
            }

            if (empty($this->request->post['transaction_type']) || !in_array($this->request->post['transaction_type'], ['in', 'out'])) {
                $this->error['transaction_type'] = $this->language->get('error_transaction_type');
            }

            if (empty($this->request->post['amount']) || !is_numeric($this->request->post['amount']) || $this->request->post['amount'] <= 0) {
                $this->error['amount'] = $this->language->get('error_amount');
            }

            if (empty($this->request->post['description'])) {
                $this->error['description'] = $this->language->get('error_description');
            }

            if (empty($this->request->post['transaction_date'])) {
                $this->error['transaction_date'] = $this->language->get('error_transaction_date');
            }

            return !$this->error;
        }

        protected function validateDelete() {
            if (!$this->user->hasPermission('modify', 'finance/cash')) {
                $this->error['warning'] = $this->language->get('error_permission');
            }

            return !$this->error;
        }
}