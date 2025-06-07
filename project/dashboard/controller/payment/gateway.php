<?php
/**
 * تحكم إدارة بوابات الدفع الإلكتروني المحسن
 * يدعم إدارة جميع بوابات الدفع المحلية والعالمية مع التكامل المحاسبي الكامل
 */
class ControllerPaymentGateway extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('payment/gateway');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/gateway');
        $this->getList();
    }

    public function add() {
        $this->load->language('payment/gateway');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/gateway');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $gateway_id = $this->model_payment_gateway->addGateway($this->request->post);
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

            $this->response->redirect($this->url->link('payment/gateway', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('payment/gateway');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/gateway');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_payment_gateway->editGateway($this->request->get['gateway_id'], $this->request->post);
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

            $this->response->redirect($this->url->link('payment/gateway', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('payment/gateway');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/gateway');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $gateway_id) {
                $this->model_payment_gateway->deleteGateway($gateway_id);
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

            $this->response->redirect($this->url->link('payment/gateway', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function test() {
        $this->load->language('payment/gateway');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/gateway');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateTest()) {
            $result = $this->model_payment_gateway->testGatewayConnection($this->request->post);
            
            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_test_success');
            } else {
                $this->session->data['error'] = $result['error'];
            }

            $this->response->redirect($this->url->link('payment/gateway', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getTestForm();
    }

    public function sync() {
        $this->load->language('payment/gateway');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/gateway');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateSync()) {
            $result = $this->model_payment_gateway->syncGatewayTransactions($this->request->post);
            
            if ($result['success']) {
                $this->session->data['success'] = sprintf($this->language->get('text_sync_success'), $result['synced_count']);
            } else {
                $this->session->data['error'] = $result['error'];
            }

            $this->response->redirect($this->url->link('payment/gateway', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getSyncForm();
    }

    protected function getList() {
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        if (isset($this->request->get['filter_provider'])) {
            $filter_provider = $this->request->get['filter_provider'];
        } else {
            $filter_provider = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
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
            'href' => $this->url->link('payment/gateway', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('payment/gateway/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('payment/gateway/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['sync_all'] = $this->url->link('payment/gateway/sync', 'user_token=' . $this->session->data['user_token'], true);

        $data['gateways'] = array();

        $filter_data = array(
            'filter_name' => $filter_name,
            'filter_provider' => $filter_provider,
            'filter_status' => $filter_status,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $gateway_total = $this->model_payment_gateway->getTotalGateways($filter_data);
        $results = $this->model_payment_gateway->getGateways($filter_data);

        foreach ($results as $result) {
            $data['gateways'][] = array(
                'gateway_id' => $result['gateway_id'],
                'name' => $result['name'],
                'provider_name' => $result['provider_name'],
                'gateway_type' => $result['gateway_type'],
                'commission_rate' => $result['commission_rate'] . '%',
                'monthly_volume' => $this->currency->format($result['monthly_volume'], $this->config->get('config_currency')),
                'is_active' => ($result['is_active'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'connection_status' => $result['connection_status'],
                'last_sync' => $result['last_sync'] ? date($this->language->get('datetime_format'), strtotime($result['last_sync'])) : $this->language->get('text_never'),
                'edit' => $this->url->link('payment/gateway/edit', 'user_token=' . $this->session->data['user_token'] . '&gateway_id=' . $result['gateway_id'], true),
                'test' => $this->url->link('payment/gateway/test', 'user_token=' . $this->session->data['user_token'] . '&gateway_id=' . $result['gateway_id'], true),
                'sync' => $this->url->link('payment/gateway/sync', 'user_token=' . $this->session->data['user_token'] . '&gateway_id=' . $result['gateway_id'], true),
                'transactions' => $this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'] . '&filter_gateway_id=' . $result['gateway_id'], true)
            );
        }

        // إضافة باقي بيانات العرض
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

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/gateway_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['gateway_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/gateway', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (!isset($this->request->get['gateway_id'])) {
            $data['action'] = $this->url->link('payment/gateway/add', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('payment/gateway/edit', 'user_token=' . $this->session->data['user_token'] . '&gateway_id=' . $this->request->get['gateway_id'], true);
        }

        $data['cancel'] = $this->url->link('payment/gateway', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->get['gateway_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $gateway_info = $this->model_payment_gateway->getGateway($this->request->get['gateway_id']);
        }

        // الحصول على مقدمي الخدمة
        $data['providers'] = $this->model_payment_gateway->getGatewayProviders();

        // الحصول على الحسابات المحاسبية
        $this->load->model('accounts/chartaccount');
        $data['accounts'] = $this->model_accounts_chartaccount->getAccounts(array('filter_type' => 'asset'));

        // أنواع البوابات
        $data['gateway_types'] = array(
            'credit_card' => $this->language->get('text_credit_card'),
            'debit_card' => $this->language->get('text_debit_card'),
            'bank_transfer' => $this->language->get('text_bank_transfer'),
            'digital_wallet' => $this->language->get('text_digital_wallet'),
            'mobile_payment' => $this->language->get('text_mobile_payment'),
            'cryptocurrency' => $this->language->get('text_cryptocurrency'),
            'installment' => $this->language->get('text_installment'),
            'bnpl' => $this->language->get('text_buy_now_pay_later')
        );

        // بيانات النموذج
        $fields = ['name', 'provider_id', 'gateway_type', 'api_endpoint', 'merchant_id', 'api_key', 'secret_key', 
                   'account_id', 'commission_account_id', 'commission_rate', 'fixed_fee', 'currency_code', 
                   'settlement_period', 'is_active', 'is_test_mode', 'webhook_url', 'return_url', 'cancel_url'];
        
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($gateway_info)) {
                $data[$field] = $gateway_info[$field];
            } else {
                $data[$field] = ($field == 'currency_code') ? $this->config->get('config_currency') : 
                               (($field == 'is_active' || $field == 'is_test_mode') ? 1 : '');
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/gateway_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'payment/gateway')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['provider_id'])) {
            $this->error['provider_id'] = $this->language->get('error_provider');
        }

        if (empty($this->request->post['gateway_type'])) {
            $this->error['gateway_type'] = $this->language->get('error_gateway_type');
        }

        if (empty($this->request->post['api_endpoint'])) {
            $this->error['api_endpoint'] = $this->language->get('error_api_endpoint');
        }

        if (empty($this->request->post['merchant_id'])) {
            $this->error['merchant_id'] = $this->language->get('error_merchant_id');
        }

        if (empty($this->request->post['api_key'])) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        if (empty($this->request->post['account_id'])) {
            $this->error['account_id'] = $this->language->get('error_account');
        }

        if (empty($this->request->post['commission_account_id'])) {
            $this->error['commission_account_id'] = $this->language->get('error_commission_account');
        }

        if (!empty($this->request->post['commission_rate']) && (!is_numeric($this->request->post['commission_rate']) || $this->request->post['commission_rate'] < 0)) {
            $this->error['commission_rate'] = $this->language->get('error_commission_rate');
        }

        if (!empty($this->request->post['fixed_fee']) && (!is_numeric($this->request->post['fixed_fee']) || $this->request->post['fixed_fee'] < 0)) {
            $this->error['fixed_fee'] = $this->language->get('error_fixed_fee');
        }

        return !$this->error;
    }

    protected function validateTest() {
        if (!$this->user->hasPermission('modify', 'payment/gateway')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['gateway_id'])) {
            $this->error['gateway_id'] = $this->language->get('error_gateway');
        }

        return !$this->error;
    }

    protected function validateSync() {
        if (!$this->user->hasPermission('modify', 'payment/gateway')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'payment/gateway')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
