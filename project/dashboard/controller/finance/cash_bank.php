<?php
class ControllerFinanceCashBank extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('finance/cash_bank');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/cash_bank');

        $data['user_token'] = $this->session->data['user_token'];

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('finance/cash_bank', 'user_token=' . $this->session->data['user_token'], true)
        );

        // الخزن/الصناديق
        $data['cash_accounts'] = array();
        $cash_results = $this->model_finance_cash_bank->getCashAccounts();
        foreach ($cash_results as $result) {
            $data['cash_accounts'][] = array(
                'cash_id' => $result['cash_id'],
                'name' => $result['name'],
                'code' => $result['code'],
                'account_code' => $result['account_code'],
                'responsible_user' => $result['responsible_user'],
                'balance' => $this->currency->format($result['balance'], $this->config->get('config_currency')),
                'status' => $result['status'],
                'action' => array(
                    'view' => $this->url->link('finance/cash_bank/viewCash', 'user_token=' . $this->session->data['user_token'] . '&cash_id=' . $result['cash_id'], true),
                    'edit' => $this->url->link('finance/cash_bank/editCash', 'user_token=' . $this->session->data['user_token'] . '&cash_id=' . $result['cash_id'], true)
                )
            );
        }

        // الحسابات البنكية
        $data['bank_accounts'] = array();
        $bank_results = $this->model_finance_cash_bank->getBankAccounts();
        foreach ($bank_results as $result) {
            $data['bank_accounts'][] = array(
                'bank_account_id' => $result['account_id'],
                'account_name' => $result['account_name'],
                'bank_name' => $result['bank_name'],
                'account_number' => $result['account_number'],
                'currency' => $result['currency'],
                'current_balance' => $this->currency->format($result['current_balance'], $result['currency']),
                'account_type' => $result['account_type'],
                'action' => array(
                    'view' => $this->url->link('finance/cash_bank/viewBank', 'user_token=' . $this->session->data['user_token'] . '&bank_account_id=' . $result['account_id'], true),
                    'edit' => $this->url->link('finance/cash_bank/editBank', 'user_token=' . $this->session->data['user_token'] . '&bank_account_id=' . $result['account_id'], true),
                    'reconcile' => $this->url->link('finance/cash_bank/reconcileBank', 'user_token=' . $this->session->data['user_token'] . '&bank_account_id=' . $result['account_id'], true)
                )
            );
        }

        // حركات النقدية
        $data['cash_transactions'] = array();
        $cash_transaction_results = $this->model_finance_cash_bank->getCashTransactions();
        foreach ($cash_transaction_results as $result) {
            $data['cash_transactions'][] = array(
                'transaction_id' => $result['cash_transaction_id'],
                'cash_name' => $result['cash_name'],
                'type' => $result['transaction_type'],
                'amount' => $this->currency->format($result['amount'], $this->config->get('config_currency')),
                'reference' => $result['reference'],
                'note' => $result['note'],
                'created_by' => $result['created_by'],
                'created_at' => date($this->language->get('datetime_format'), strtotime($result['created_at']))
            );
        }

        // حركات البنوك
        $data['bank_transactions'] = array();
        $bank_transaction_results = $this->model_finance_cash_bank->getBankTransactions();
        foreach ($bank_transaction_results as $result) {
            $data['bank_transactions'][] = array(
                'transaction_id' => $result['bank_transaction_id'],
                'bank_name' => $result['bank_name'],
                'type' => $result['transaction_type'],
                'amount' => $this->currency->format($result['amount'], $result['currency']),
                'reference' => $result['reference'],
                'description' => $result['description'],
                'created_by' => $result['created_by'],
                'created_at' => date($this->language->get('datetime_format'), strtotime($result['created_at']))
            );
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/cash_bank', $data));
    }

    public function viewCash() {
        $this->load->language('finance/cash_bank');
        $this->load->model('finance/cash_bank');

        if (isset($this->request->get['cash_id'])) {
            $cash_id = $this->request->get['cash_id'];
        } else {
            $this->response->redirect($this->url->link('finance/cash_bank', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['cash_info'] = $this->model_finance_cash_bank->getCashInfo($cash_id);
        $data['cash_transactions'] = $this->model_finance_cash_bank->getCashTransactionsByCash($cash_id);

        $this->response->setOutput($this->load->view('finance/cash_view', $data));
    }

    public function viewBank() {
        $this->load->language('finance/cash_bank');
        $this->load->model('finance/cash_bank');

        if (isset($this->request->get['bank_account_id'])) {
            $bank_account_id = $this->request->get['bank_account_id'];
        } else {
            $this->response->redirect($this->url->link('finance/cash_bank', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['bank_info'] = $this->model_finance_cash_bank->getBankInfo($bank_account_id);
        $data['bank_transactions'] = $this->model_finance_cash_bank->getBankTransactionsByAccount($bank_account_id);
        $data['reconciliations'] = $this->model_finance_cash_bank->getBankReconciliations($bank_account_id);

        $this->response->setOutput($this->load->view('finance/bank_view', $data));
    }

    public function getCashTransactions() {
        $this->load->model('finance/cash_bank');

        $filter_data = array(
            'filter_cash_id' => isset($this->request->post['filter_cash']) ? $this->request->post['filter_cash'] : null,
            'filter_type' => isset($this->request->post['filter_type']) ? $this->request->post['filter_type'] : null,
            'filter_date_from' => isset($this->request->post['filter_date_from']) ? $this->request->post['filter_date_from'] : null,
            'filter_date_to' => isset($this->request->post['filter_date_to']) ? $this->request->post['filter_date_to'] : null
        );

        $results = $this->model_finance_cash_bank->getCashTransactions($filter_data);

        $data = array();
        foreach ($results as $result) {
            $data[] = array(
                'date' => date($this->language->get('datetime_format'), strtotime($result['created_at'])),
                'cash' => $result['cash_name'],
                'type' => $result['transaction_type'],
                'amount' => $this->currency->format($result['amount'], $this->config->get('config_currency')),
                'reference' => $result['reference'],
                'note' => $result['note'],
                'created_by' => $result['created_by']
            );
        }

        $json = array('data' => $data);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getBankTransactions() {
        $this->load->model('finance/cash_bank');

        $filter_data = array(
            'filter_bank_account_id' => isset($this->request->post['filter_bank']) ? $this->request->post['filter_bank'] : null,
            'filter_type' => isset($this->request->post['filter_type']) ? $this->request->post['filter_type'] : null,
            'filter_date_from' => isset($this->request->post['filter_date_from']) ? $this->request->post['filter_date_from'] : null,
            'filter_date_to' => isset($this->request->post['filter_date_to']) ? $this->request->post['filter_date_to'] : null
        );

        $results = $this->model_finance_cash_bank->getBankTransactions($filter_data);

        $data = array();
        foreach ($results as $result) {
            $data[] = array(
                'date' => date($this->language->get('datetime_format'), strtotime($result['transaction_date'])),
                'bank' => $result['bank_name'],
                'type' => $result['transaction_type'],
                'amount' => $this->currency->format($result['amount'], $result['currency']),
                'reference' => $result['reference'],
                'description' => $result['description'],
                'created_by' => $result['created_by']
            );
        }

        $json = array('data' => $data);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'finance/cash_bank')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}