<?php
/**
 * تحكم إدارة البنوك والحسابات البنكية المحسن
 * يدعم متابعة الأرصدة والحركات البنكية والتسوية والتكامل المحاسبي
 */
class ControllerFinanceBank extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('finance/bank');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('bank/bank');
        $this->getList();
    }

    public function add() {
        $this->load->language('finance/bank');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('bank/bank');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $bank_account_id = $this->model_bank_bank->addBankAccount($this->request->post);
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

            $this->response->redirect($this->url->link('finance/bank', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('finance/bank');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('bank/bank');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_bank_bank->editBankAccount($this->request->get['bank_account_id'], $this->request->post);
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

            $this->response->redirect($this->url->link('finance/bank', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function transactions() {
        $this->load->language('finance/bank');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('bank/bank');

        $this->getTransactions();
    }

    public function addTransaction() {
        $this->load->language('finance/bank');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('bank/bank');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateTransaction()) {
            $transaction_id = $this->model_bank_bank->addBankTransaction($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success_transaction');

            $this->response->redirect($this->url->link('finance/bank/transactions', 'user_token=' . $this->session->data['user_token'] . '&bank_account_id=' . $this->request->post['bank_account_id'], true));
        }

        $this->getTransactionForm();
    }

    public function reconciliation() {
        $this->load->language('finance/bank');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('bank/bank');

        $this->getReconciliation();
    }

    public function statement() {
        $this->load->language('finance/bank');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('bank/bank');

        $this->getStatement();
    }

    public function delete() {
        $this->load->language('finance/bank');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('finance/bank');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $bank_id) {
                $this->model_finance_bank->deleteBank($bank_id);
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

            $this->response->redirect($this->url->link('finance/bank', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    protected function getList() {
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
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

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

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('finance/bank', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('finance/bank/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('finance/bank/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['banks'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $bank_total = $this->model_finance_bank->getTotalBanks();

        $results = $this->model_finance_bank->getBanks($filter_data);

        foreach ($results as $result) {
            $data['banks'][] = array(
                'bank_id'    => $result['bank_id'],
                'name'       => $result['name'],
                'account_number' => $result['account_number'],
                'status'     => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'edit'       => $this->url->link('finance/bank/edit', 'user_token=' . $this->session->data['user_token'] . '&bank_id=' . $result['bank_id'] . $url, true)
            );
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');

        $data['column_name'] = $this->language->get('column_name');
        $data['column_account_number'] = $this->language->get('column_account_number');
        $data['column_status'] = $this->language->get('column_status');
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

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link('finance/bank', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
        $data['sort_account_number'] = $this->url->link('finance/bank', 'user_token=' . $this->session->data['user_token'] . '&sort=account_number' . $url, true);
        $data['sort_status'] = $this->url->link('finance/bank', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $bank_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('finance/bank', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($bank_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($bank_total - $this->config->get('config_limit_admin'))) ? $bank_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $bank_total, ceil($bank_total / $this->config->get('config_limit_admin')));
    $data['sort'] = $sort;
    $data['order'] = $order;

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('finance/bank_list', $data));
}

protected function getForm() {
    $data['text_form'] = !isset($this->request->get['bank_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

    if (isset($this->error['account_number'])) {
        $data['error_account_number'] = $this->error['account_number'];
    } else {
        $data['error_account_number'] = '';
    }

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

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_home'),
        'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    );

    $data['breadcrumbs'][] = array(
        'text' => $this->language->get('heading_title'),
        'href' => $this->url->link('finance/bank', 'user_token=' . $this->session->data['user_token'] . $url, true)
    );

    if (!isset($this->request->get['bank_id'])) {
        $data['action'] = $this->url->link('finance/bank/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
    } else {
        $data['action'] = $this->url->link('finance/bank/edit', 'user_token=' . $this->session->data['user_token'] . '&bank_id=' . $this->request->get['bank_id'] . $url, true);
    }

    $data['cancel'] = $this->url->link('finance/bank', 'user_token=' . $this->session->data['user_token'] . $url, true);

    if (isset($this->request->get['bank_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
        $bank_info = $this->model_finance_bank->getBank($this->request->get['bank_id']);
    }

    $data['user_token'] = $this->session->data['user_token'];

    if (isset($this->request->post['name'])) {
        $data['name'] = $this->request->post['name'];
    } elseif (!empty($bank_info)) {
        $data['name'] = $bank_info['name'];
    } else {
        $data['name'] = '';
    }

    if (isset($this->request->post['account_number'])) {
        $data['account_number'] = $this->request->post['account_number'];
    } elseif (!empty($bank_info)) {
        $data['account_number'] = $bank_info['account_number'];
    } else {
        $data['account_number'] = '';
    }

    if (isset($this->request->post['branch'])) {
        $data['branch'] = $this->request->post['branch'];
    } elseif (!empty($bank_info)) {
        $data['branch'] = $bank_info['branch'];
    } else {
        $data['branch'] = '';
    }

    if (isset($this->request->post['swift_code'])) {
        $data['swift_code'] = $this->request->post['swift_code'];
    } elseif (!empty($bank_info)) {
        $data['swift_code'] = $bank_info['swift_code'];
    } else {
        $data['swift_code'] = '';
    }

    if (isset($this->request->post['address'])) {
        $data['address'] = $this->request->post['address'];
    } elseif (!empty($bank_info)) {
        $data['address'] = $bank_info['address'];
    } else {
        $data['address'] = '';
    }

    if (isset($this->request->post['status'])) {
        $data['status'] = $this->request->post['status'];
    } elseif (!empty($bank_info)) {
        $data['status'] = $bank_info['status'];
    } else {
        $data['status'] = true;
    }

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('finance/bank_form', $data));
}

protected function validateForm() {
    if (!$this->user->hasPermission('modify', 'finance/bank')) {
        $this->error['warning'] = $this->language->get('error_permission');
    }

    if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
        $this->error['name'] = $this->language->get('error_name');
    }

    if ((utf8_strlen($this->request->post['account_number']) < 3) || (utf8_strlen($this->request->post['account_number']) > 64)) {
        $this->error['account_number'] = $this->language->get('error_account_number');
    }

    return !$this->error;
}

protected function validateDelete() {
    if (!$this->user->hasPermission('modify', 'finance/bank')) {
        $this->error['warning'] = $this->language->get('error_permission');
    }

    return !$this->error;
}
}
