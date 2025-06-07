<?php
/**
 * تحكم إدارة المحافظ الإلكترونية المحسن
 * يدعم إدارة المحافظ الإلكترونية الشائعة في مصر والعالم العربي
 * مثل فودافون كاش، أورانج موني، إتصالات كاش، فوري، أمان، PayPal، إلخ
 */
class ControllerFinanceEwallet extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('finance/ewallet');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/ewallet');
        $this->getList();
    }

    public function add() {
        $this->load->language('finance/ewallet');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/ewallet');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $ewallet_id = $this->model_finance_ewallet->addEwallet($this->request->post);
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

            $this->response->redirect($this->url->link('finance/ewallet', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('finance/ewallet');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/ewallet');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_finance_ewallet->editEwallet($this->request->get['ewallet_id'], $this->request->post);
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

            $this->response->redirect($this->url->link('finance/ewallet', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('finance/ewallet');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/ewallet');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $ewallet_id) {
                $this->model_finance_ewallet->deleteEwallet($ewallet_id);
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

            $this->response->redirect($this->url->link('finance/ewallet', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function transactions() {
        $this->load->language('finance/ewallet');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/ewallet');
        
        $this->getTransactions();
    }

    public function addTransaction() {
        $this->load->language('finance/ewallet');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/ewallet');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateTransaction()) {
            $transaction_id = $this->model_finance_ewallet->addEwalletTransaction($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success_transaction');
            
            $this->response->redirect($this->url->link('finance/ewallet/transactions', 'user_token=' . $this->session->data['user_token'] . '&ewallet_id=' . $this->request->post['ewallet_id'], true));
        }

        $this->getTransactionForm();
    }

    public function reconciliation() {
        $this->load->language('finance/ewallet');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/ewallet');
        
        $this->getReconciliation();
    }

    public function settlement() {
        $this->load->language('finance/ewallet');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/ewallet');
        
        $this->getSettlement();
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
            'href' => $this->url->link('finance/ewallet', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('finance/ewallet/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('finance/ewallet/delete', 'user_token=' . $this->session->data['user_token'], true);

        $data['ewallets'] = array();

        $filter_data = array(
            'filter_name' => $filter_name,
            'filter_provider' => $filter_provider,
            'filter_active' => $filter_active,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $ewallet_total = $this->model_finance_ewallet->getTotalEwallets($filter_data);
        $results = $this->model_finance_ewallet->getEwallets($filter_data);

        foreach ($results as $result) {
            $data['ewallets'][] = array(
                'ewallet_id' => $result['ewallet_id'],
                'name' => $result['name'],
                'provider_name' => $result['provider_name'],
                'account_number' => $result['account_number'],
                'current_balance' => $this->currency->format($result['current_balance'], $this->config->get('config_currency')),
                'currency_code' => $result['currency_code'],
                'is_active' => ($result['is_active'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'edit' => $this->url->link('finance/ewallet/edit', 'user_token=' . $this->session->data['user_token'] . '&ewallet_id=' . $result['ewallet_id'], true),
                'transactions' => $this->url->link('finance/ewallet/transactions', 'user_token=' . $this->session->data['user_token'] . '&ewallet_id=' . $result['ewallet_id'], true)
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

        $this->response->setOutput($this->load->view('finance/ewallet_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['ewallet_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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
            'href' => $this->url->link('finance/ewallet', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (!isset($this->request->get['ewallet_id'])) {
            $data['action'] = $this->url->link('finance/ewallet/add', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('finance/ewallet/edit', 'user_token=' . $this->session->data['user_token'] . '&ewallet_id=' . $this->request->get['ewallet_id'], true);
        }

        $data['cancel'] = $this->url->link('finance/ewallet', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->get['ewallet_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $ewallet_info = $this->model_finance_ewallet->getEwallet($this->request->get['ewallet_id']);
        }

        // الحصول على مقدمي الخدمة
        $data['providers'] = $this->model_finance_ewallet->getEwalletProviders();

        // الحصول على الحسابات المحاسبية
        $this->load->model('accounts/chartaccount');
        $data['accounts'] = $this->model_accounts_chartaccount->getAccounts(array('filter_type' => 'asset'));

        // بيانات النموذج
        $fields = ['name', 'provider_id', 'account_number', 'account_id', 'currency_code', 'opening_balance', 'commission_rate', 'is_active'];
        
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($ewallet_info)) {
                $data[$field] = $ewallet_info[$field];
            } else {
                $data[$field] = ($field == 'currency_code') ? $this->config->get('config_currency') : 
                               (($field == 'is_active') ? 1 : '');
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/ewallet_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'finance/ewallet')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['provider_id'])) {
            $this->error['provider_id'] = $this->language->get('error_provider');
        }

        if (empty($this->request->post['account_number'])) {
            $this->error['account_number'] = $this->language->get('error_account_number');
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
        if (!$this->user->hasPermission('modify', 'finance/ewallet')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['ewallet_id'])) {
            $this->error['ewallet_id'] = $this->language->get('error_ewallet');
        }

        if (empty($this->request->post['transaction_type']) || !in_array($this->request->post['transaction_type'], ['deposit', 'withdrawal', 'transfer'])) {
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
        if (!$this->user->hasPermission('modify', 'finance/ewallet')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
