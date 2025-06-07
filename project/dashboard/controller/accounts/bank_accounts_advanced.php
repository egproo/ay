<?php
/**
 * تحكم إدارة الحسابات المصرفية المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsBankAccountsAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/bank_accounts_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/bank_accounts_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/bank_accounts.css');
        $this->document->addScript('view/javascript/accounts/bank_accounts.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');
        $this->document->addScript('view/javascript/jquery/chart.min.js');
        $this->document->addScript('view/javascript/jquery/datatables.min.js');
        $this->document->addStyle('view/javascript/jquery/datatables.min.css');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'bank_accounts',
            'record_id' => 0,
            'description' => 'عرض شاشة إدارة الحسابات المصرفية',
            'module' => 'bank_accounts'
        ]);

        $this->getList();
    }

    public function add() {
        $this->load->language('accounts/bank_accounts_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/bank_accounts_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $account_id = $this->model_accounts_bank_accounts_advanced->addBankAccount($this->request->post);

                // تسجيل إضافة الحساب
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'add_bank_account',
                    'table_name' => 'bank_accounts',
                    'record_id' => $account_id,
                    'description' => 'إضافة حساب مصرفي جديد: ' . $this->request->post['account_name'],
                    'module' => 'bank_accounts'
                ]);

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

                $this->response->redirect($this->url->link('accounts/bank_accounts_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إضافة الحساب المصرفي: ' . $e->getMessage();
            }
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('accounts/bank_accounts_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/bank_accounts_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $this->model_accounts_bank_accounts_advanced->editBankAccount($this->request->get['account_id'], $this->request->post);

                // تسجيل تعديل الحساب
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'edit_bank_account',
                    'table_name' => 'bank_accounts',
                    'record_id' => $this->request->get['account_id'],
                    'description' => 'تعديل الحساب المصرفي: ' . $this->request->post['account_name'],
                    'module' => 'bank_accounts'
                ]);

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

                $this->response->redirect($this->url->link('accounts/bank_accounts_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في تعديل الحساب المصرفي: ' . $e->getMessage();
            }
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('accounts/bank_accounts_advanced');
        $this->load->model('accounts/bank_accounts_advanced');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $account_id) {
                $account_info = $this->model_accounts_bank_accounts_advanced->getBankAccount($account_id);

                $this->model_accounts_bank_accounts_advanced->deleteBankAccount($account_id);

                // تسجيل حذف الحساب
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'delete_bank_account',
                    'table_name' => 'bank_accounts',
                    'record_id' => $account_id,
                    'description' => 'حذف الحساب المصرفي: ' . $account_info['account_name'],
                    'module' => 'bank_accounts'
                ]);
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

            $this->response->redirect($this->url->link('accounts/bank_accounts_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function reconcile() {
        $this->load->language('accounts/bank_accounts_advanced');
        $this->load->model('accounts/bank_accounts_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateReconciliation()) {
            try {
                $reconciliation_data = $this->prepareReconciliationData();

                $result = $this->model_accounts_bank_accounts_advanced->performReconciliation($reconciliation_data);

                // تسجيل التسوية البنكية
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'bank_reconciliation',
                    'table_name' => 'bank_reconciliation',
                    'record_id' => $result['reconciliation_id'],
                    'description' => 'تسوية بنكية للحساب: ' . $reconciliation_data['account_name'],
                    'module' => 'bank_accounts'
                ]);

                $this->session->data['reconciliation_result'] = $result;
                $this->session->data['success'] = 'تم إجراء التسوية البنكية بنجاح';

                $this->response->redirect($this->url->link('accounts/bank_accounts_advanced/viewReconciliation', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في التسوية البنكية: ' . $e->getMessage();
            }
        }

        $this->getReconciliationForm();
    }

    public function transfer() {
        $this->load->language('accounts/bank_accounts_advanced');
        $this->load->model('accounts/bank_accounts_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateTransfer()) {
            try {
                $transfer_data = $this->prepareTransferData();

                $result = $this->model_accounts_bank_accounts_advanced->processTransfer($transfer_data);

                // تسجيل التحويل البنكي
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'bank_transfer',
                    'table_name' => 'bank_transfers',
                    'record_id' => $result['transfer_id'],
                    'description' => 'تحويل بنكي من ' . $transfer_data['from_account_name'] . ' إلى ' . $transfer_data['to_account_name'],
                    'module' => 'bank_accounts'
                ]);

                $this->session->data['success'] = 'تم التحويل البنكي بنجاح';

                $this->response->redirect($this->url->link('accounts/bank_accounts_advanced', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في التحويل البنكي: ' . $e->getMessage();
            }
        }

        $this->getTransferForm();
    }

    public function getAccountAnalysis() {
        $this->load->model('accounts/bank_accounts_advanced');

        $json = array();

        if (isset($this->request->get['account_id'])) {
            try {
                $account_id = $this->request->get['account_id'];

                $analysis = $this->model_accounts_bank_accounts_advanced->analyzeAccount($account_id);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الحساب مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getCashFlow() {
        $this->load->model('accounts/bank_accounts_advanced');

        $json = array();

        if (isset($this->request->get['account_id'])) {
            try {
                $account_id = $this->request->get['account_id'];
                $period = $this->request->get['period'] ?? '30'; // آخر 30 يوم افتراضياً

                $cash_flow = $this->model_accounts_bank_accounts_advanced->calculateCashFlow($account_id, $period);

                $json['success'] = true;
                $json['cash_flow'] = $cash_flow;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الحساب مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getTransactionHistory() {
        $this->load->model('accounts/bank_accounts_advanced');

        $json = array();

        if (isset($this->request->get['account_id'])) {
            try {
                $account_id = $this->request->get['account_id'];
                $limit = $this->request->get['limit'] ?? 50;
                $offset = $this->request->get['offset'] ?? 0;

                $transactions = $this->model_accounts_bank_accounts_advanced->getTransactionHistory($account_id, $limit, $offset);

                $json['success'] = true;
                $json['transactions'] = $transactions;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الحساب مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getBalanceHistory() {
        $this->load->model('accounts/bank_accounts_advanced');

        $json = array();

        if (isset($this->request->get['account_id'])) {
            try {
                $account_id = $this->request->get['account_id'];
                $period = $this->request->get['period'] ?? '90'; // آخر 90 يوم افتراضياً

                $balance_history = $this->model_accounts_bank_accounts_advanced->getBalanceHistory($account_id, $period);

                $json['success'] = true;
                $json['balance_history'] = $balance_history;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الحساب مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function export() {
        $this->load->language('accounts/bank_accounts_advanced');
        $this->load->model('accounts/bank_accounts_advanced');

        $format = $this->request->get['format'] ?? 'excel';
        $filter_data = $this->prepareFilterData();

        $accounts_data = $this->model_accounts_bank_accounts_advanced->getAccountsForExport($filter_data);

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_bank_accounts',
            'table_name' => 'bank_accounts',
            'record_id' => 0,
            'description' => "تصدير الحسابات المصرفية بصيغة {$format}",
            'module' => 'bank_accounts'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($accounts_data);
                break;
            case 'pdf':
                $this->exportToPdf($accounts_data);
                break;
            case 'csv':
                $this->exportToCsv($accounts_data);
                break;
            default:
                $this->exportToExcel($accounts_data);
        }
    }

    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'account_name';
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
            'href' => $this->url->link('accounts/bank_accounts_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('accounts/bank_accounts_advanced/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('accounts/bank_accounts_advanced/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['reconcile'] = $this->url->link('accounts/bank_accounts_advanced/reconcile', 'user_token=' . $this->session->data['user_token'], true);
        $data['transfer'] = $this->url->link('accounts/bank_accounts_advanced/transfer', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['analysis_url'] = $this->url->link('accounts/bank_accounts_advanced/getAccountAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['cash_flow_url'] = $this->url->link('accounts/bank_accounts_advanced/getCashFlow', 'user_token=' . $this->session->data['user_token'], true);
        $data['transactions_url'] = $this->url->link('accounts/bank_accounts_advanced/getTransactionHistory', 'user_token=' . $this->session->data['user_token'], true);
        $data['balance_history_url'] = $this->url->link('accounts/bank_accounts_advanced/getBalanceHistory', 'user_token=' . $this->session->data['user_token'], true);

        $data['accounts'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $account_total = $this->model_accounts_bank_accounts_advanced->getTotalBankAccounts();

        $results = $this->model_accounts_bank_accounts_advanced->getBankAccounts($filter_data);

        foreach ($results as $result) {
            $data['accounts'][] = array(
                'account_id'        => $result['account_id'],
                'account_number'    => $result['account_number'],
                'account_name'      => $result['account_name'],
                'bank_name'         => $result['bank_name'],
                'branch_name'       => $result['branch_name'],
                'currency'          => $result['currency'],
                'current_balance'   => $this->currency->format($result['current_balance'], $result['currency']),
                'status'            => $result['status'],
                'last_reconciled'   => $result['last_reconciled'] ? date($this->language->get('date_format_short'), strtotime($result['last_reconciled'])) : 'لم يتم',
                'edit'              => $this->url->link('accounts/bank_accounts_advanced/edit', 'user_token=' . $this->session->data['user_token'] . '&account_id=' . $result['account_id'] . $url, true),
                'reconcile'         => $this->url->link('accounts/bank_accounts_advanced/reconcile', 'user_token=' . $this->session->data['user_token'] . '&account_id=' . $result['account_id'], true)
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

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

        $data['sort_account_name'] = $this->url->link('accounts/bank_accounts_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=account_name' . $url, true);
        $data['sort_bank_name'] = $this->url->link('accounts/bank_accounts_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=bank_name' . $url, true);
        $data['sort_current_balance'] = $this->url->link('accounts/bank_accounts_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=current_balance' . $url, true);
        $data['sort_status'] = $this->url->link('accounts/bank_accounts_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $account_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('accounts/bank_accounts_advanced', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($account_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($account_total - $this->config->get('config_limit_admin'))) ? $account_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $account_total, ceil($account_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/bank_accounts_advanced_list', $data));
    }