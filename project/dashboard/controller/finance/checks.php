<?php
/**
 * تحكم إدارة الشيكات المحسن
 * يدعم إدارة الشيكات الواردة والصادرة وتتبع حالاتها والتكامل المحاسبي
 */
class ControllerFinanceChecks extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('finance/checks');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/checks');
        $this->getList();
    }

    public function add() {
        $this->load->language('finance/checks');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/checks');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $check_id = $this->model_finance_checks->addCheck($this->request->post);
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

            $this->response->redirect($this->url->link('finance/checks', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('finance/checks');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/checks');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_finance_checks->editCheck($this->request->get['check_id'], $this->request->post);
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

            $this->response->redirect($this->url->link('finance/checks', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('finance/checks');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/checks');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $check_id) {
                $this->model_finance_checks->deleteCheck($check_id);
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

            $this->response->redirect($this->url->link('finance/checks', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function collect() {
        $this->load->language('finance/checks');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/checks');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateCollect()) {
            $result = $this->model_finance_checks->collectCheck($this->request->post);
            
            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_success_collected');
            } else {
                $this->session->data['error'] = $result['error'];
            }

            $this->response->redirect($this->url->link('finance/checks', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getCollectForm();
    }

    public function bounce() {
        $this->load->language('finance/checks');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/checks');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateBounce()) {
            $result = $this->model_finance_checks->bounceCheck($this->request->post);
            
            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_success_bounced');
            } else {
                $this->session->data['error'] = $result['error'];
            }

            $this->response->redirect($this->url->link('finance/checks', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getBounceForm();
    }

    public function deposit() {
        $this->load->language('finance/checks');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/checks');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateDeposit()) {
            $result = $this->model_finance_checks->depositChecks($this->request->post);
            
            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_success_deposited');
            } else {
                $this->session->data['error'] = $result['error'];
            }

            $this->response->redirect($this->url->link('finance/checks', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getDepositForm();
    }

    protected function getList() {
        if (isset($this->request->get['filter_check_number'])) {
            $filter_check_number = $this->request->get['filter_check_number'];
        } else {
            $filter_check_number = '';
        }

        if (isset($this->request->get['filter_type'])) {
            $filter_type = $this->request->get['filter_type'];
        } else {
            $filter_type = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'check_date';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
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
            'href' => $this->url->link('finance/checks', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('finance/checks/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('finance/checks/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['deposit'] = $this->url->link('finance/checks/deposit', 'user_token=' . $this->session->data['user_token'], true);

        $data['checks'] = array();

        $filter_data = array(
            'filter_check_number' => $filter_check_number,
            'filter_type' => $filter_type,
            'filter_status' => $filter_status,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $check_total = $this->model_finance_checks->getTotalChecks($filter_data);
        $results = $this->model_finance_checks->getChecks($filter_data);

        foreach ($results as $result) {
            $data['checks'][] = array(
                'check_id' => $result['check_id'],
                'check_number' => $result['check_number'],
                'check_type' => $result['check_type'],
                'amount' => $this->currency->format($result['amount'], $this->config->get('config_currency')),
                'check_date' => date($this->language->get('date_format_short'), strtotime($result['check_date'])),
                'due_date' => date($this->language->get('date_format_short'), strtotime($result['due_date'])),
                'bank_name' => $result['bank_name'],
                'drawer_name' => $result['drawer_name'],
                'status' => $result['status'],
                'status_text' => $this->language->get('text_status_' . $result['status']),
                'edit' => $this->url->link('finance/checks/edit', 'user_token=' . $this->session->data['user_token'] . '&check_id=' . $result['check_id'], true),
                'collect' => $this->url->link('finance/checks/collect', 'user_token=' . $this->session->data['user_token'] . '&check_id=' . $result['check_id'], true),
                'bounce' => $this->url->link('finance/checks/bounce', 'user_token=' . $this->session->data['user_token'] . '&check_id=' . $result['check_id'], true)
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

        $this->response->setOutput($this->load->view('finance/checks_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['check_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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
            'href' => $this->url->link('finance/checks', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (!isset($this->request->get['check_id'])) {
            $data['action'] = $this->url->link('finance/checks/add', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('finance/checks/edit', 'user_token=' . $this->session->data['user_token'] . '&check_id=' . $this->request->get['check_id'], true);
        }

        $data['cancel'] = $this->url->link('finance/checks', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->get['check_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $check_info = $this->model_finance_checks->getCheck($this->request->get['check_id']);
        }

        // الحصول على البنوك
        $this->load->model('bank/bank');
        $data['banks'] = $this->model_bank_bank->getBankAccounts();

        // الحصول على العملاء والموردين
        $this->load->model('customer/customer');
        $this->load->model('supplier/supplier');
        $data['customers'] = $this->model_customer_customer->getCustomers();
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        // بيانات النموذج
        $fields = ['check_type', 'check_number', 'amount', 'check_date', 'due_date', 'bank_id', 'drawer_name', 'drawer_id', 'drawer_type', 'notes'];
        
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($check_info)) {
                $data[$field] = $check_info[$field];
            } else {
                $data[$field] = ($field == 'check_date' || $field == 'due_date') ? date('Y-m-d') : '';
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/checks_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'finance/checks')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['check_number'])) {
            $this->error['check_number'] = $this->language->get('error_check_number');
        }

        if (empty($this->request->post['amount']) || !is_numeric($this->request->post['amount']) || $this->request->post['amount'] <= 0) {
            $this->error['amount'] = $this->language->get('error_amount');
        }

        if (empty($this->request->post['check_date'])) {
            $this->error['check_date'] = $this->language->get('error_check_date');
        }

        if (empty($this->request->post['due_date'])) {
            $this->error['due_date'] = $this->language->get('error_due_date');
        }

        if (empty($this->request->post['bank_id'])) {
            $this->error['bank_id'] = $this->language->get('error_bank');
        }

        if (empty($this->request->post['drawer_name'])) {
            $this->error['drawer_name'] = $this->language->get('error_drawer_name');
        }

        return !$this->error;
    }

    protected function validateCollect() {
        if (!$this->user->hasPermission('modify', 'finance/checks')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['check_id'])) {
            $this->error['check_id'] = $this->language->get('error_check');
        }

        if (empty($this->request->post['collection_date'])) {
            $this->error['collection_date'] = $this->language->get('error_collection_date');
        }

        return !$this->error;
    }

    protected function validateBounce() {
        if (!$this->user->hasPermission('modify', 'finance/checks')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['check_id'])) {
            $this->error['check_id'] = $this->language->get('error_check');
        }

        if (empty($this->request->post['bounce_date'])) {
            $this->error['bounce_date'] = $this->language->get('error_bounce_date');
        }

        if (empty($this->request->post['bounce_reason'])) {
            $this->error['bounce_reason'] = $this->language->get('error_bounce_reason');
        }

        return !$this->error;
    }

    protected function validateDeposit() {
        if (!$this->user->hasPermission('modify', 'finance/checks')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['selected_checks'])) {
            $this->error['selected_checks'] = $this->language->get('error_selected_checks');
        }

        if (empty($this->request->post['deposit_date'])) {
            $this->error['deposit_date'] = $this->language->get('error_deposit_date');
        }

        if (empty($this->request->post['bank_account_id'])) {
            $this->error['bank_account_id'] = $this->language->get('error_bank_account');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'finance/checks')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
