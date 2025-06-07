<?php
class ControllerSupplierAccounts extends Controller {
    private $error = array();

    /**
     * عرض صفحة حسابات الموردين
     */
    public function index() {
        $this->load->language('supplier/accounts');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/accounts');

        $this->getList();
    }

    /**
     * عرض تفاصيل حساب مورد
     */
    public function view() {
        $this->load->language('supplier/accounts');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/accounts');

        if (isset($this->request->get['supplier_id'])) {
            $supplier_id = $this->request->get['supplier_id'];
        } else {
            $supplier_id = 0;
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/accounts', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account_details'),
            'href' => $this->url->link('supplier/accounts/view', 'user_token=' . $this->session->data['user_token'] . '&supplier_id=' . $supplier_id, true)
        );

        if ($supplier_id) {
            $data['supplier_account'] = $this->model_supplier_accounts->getSupplierAccount($supplier_id);
            $data['account_transactions'] = $this->model_supplier_accounts->getSupplierTransactions($supplier_id);
            $data['account_summary'] = $this->model_supplier_accounts->getSupplierAccountSummary($supplier_id);
            $data['payment_history'] = $this->model_supplier_accounts->getSupplierPaymentHistory($supplier_id);
        } else {
            $data['supplier_account'] = array();
            $data['account_transactions'] = array();
            $data['account_summary'] = array();
            $data['payment_history'] = array();
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['supplier_id'] = $supplier_id;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/accounts_view', $data));
    }

    /**
     * إضافة معاملة جديدة
     */
    public function addTransaction() {
        $this->load->language('supplier/accounts');
        $json = array();

        if (!$this->user->hasPermission('modify', 'supplier/accounts')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('supplier/accounts');

            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateTransaction()) {
                $transaction_id = $this->model_supplier_accounts->addTransaction($this->request->post);
                $json['success'] = $this->language->get('text_transaction_success');
                $json['transaction_id'] = $transaction_id;
            } else {
                $json['error'] = $this->error;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إضافة دفعة جديدة
     */
    public function addPayment() {
        $this->load->language('supplier/accounts');
        $json = array();

        if (!$this->user->hasPermission('modify', 'supplier/accounts')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('supplier/accounts');

            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validatePayment()) {
                $payment_id = $this->model_supplier_accounts->addPayment($this->request->post);
                $json['success'] = $this->language->get('text_payment_success');
                $json['payment_id'] = $payment_id;
            } else {
                $json['error'] = $this->error;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * عرض قائمة حسابات الموردين
     */
    protected function getList() {
        if (isset($this->request->get['filter_supplier_name'])) {
            $filter_supplier_name = $this->request->get['filter_supplier_name'];
        } else {
            $filter_supplier_name = '';
        }

        if (isset($this->request->get['filter_account_status'])) {
            $filter_account_status = $this->request->get['filter_account_status'];
        } else {
            $filter_account_status = '';
        }

        if (isset($this->request->get['filter_balance_min'])) {
            $filter_balance_min = $this->request->get['filter_balance_min'];
        } else {
            $filter_balance_min = '';
        }

        if (isset($this->request->get['filter_balance_max'])) {
            $filter_balance_max = $this->request->get['filter_balance_max'];
        } else {
            $filter_balance_max = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'supplier_name';
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

        $url = '';

        if (isset($this->request->get['filter_supplier_name'])) {
            $url .= '&filter_supplier_name=' . urlencode(html_entity_decode($this->request->get['filter_supplier_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_account_status'])) {
            $url .= '&filter_account_status=' . $this->request->get['filter_account_status'];
        }

        if (isset($this->request->get['filter_balance_min'])) {
            $url .= '&filter_balance_min=' . $this->request->get['filter_balance_min'];
        }

        if (isset($this->request->get['filter_balance_max'])) {
            $url .= '&filter_balance_max=' . $this->request->get['filter_balance_max'];
        }

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
            'href' => $this->url->link('supplier/accounts', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['accounts'] = array();

        $filter_data = array(
            'filter_supplier_name'  => $filter_supplier_name,
            'filter_account_status' => $filter_account_status,
            'filter_balance_min'    => $filter_balance_min,
            'filter_balance_max'    => $filter_balance_max,
            'sort'                  => $sort,
            'order'                 => $order,
            'start'                 => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'                 => $this->config->get('config_limit_admin')
        );

        $account_total = $this->model_supplier_accounts->getTotalSupplierAccounts($filter_data);

        $results = $this->model_supplier_accounts->getSupplierAccounts($filter_data);

        foreach ($results as $result) {
            $data['accounts'][] = array(
                'supplier_id'      => $result['supplier_id'],
                'supplier_name'    => $result['supplier_name'],
                'account_number'   => $result['account_number'],
                'current_balance'  => $this->currency->format($result['current_balance'], $this->config->get('config_currency')),
                'credit_limit'     => $this->currency->format($result['credit_limit'], $this->config->get('config_currency')),
                'payment_terms'    => $result['payment_terms'],
                'account_status'   => $result['account_status'],
                'last_transaction' => $result['last_transaction'] ? date($this->language->get('date_format_short'), strtotime($result['last_transaction'])) : '',
                'balance_class'    => $this->getBalanceClass($result['current_balance']),
                'view'             => $this->url->link('supplier/accounts/view', 'user_token=' . $this->session->data['user_token'] . '&supplier_id=' . $result['supplier_id'] . $url, true)
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

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_supplier'] = $this->url->link('supplier/accounts', 'user_token=' . $this->session->data['user_token'] . '&sort=supplier_name' . $url, true);
        $data['sort_balance'] = $this->url->link('supplier/accounts', 'user_token=' . $this->session->data['user_token'] . '&sort=current_balance' . $url, true);
        $data['sort_status'] = $this->url->link('supplier/accounts', 'user_token=' . $this->session->data['user_token'] . '&sort=account_status' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_supplier_name'])) {
            $url .= '&filter_supplier_name=' . urlencode(html_entity_decode($this->request->get['filter_supplier_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_account_status'])) {
            $url .= '&filter_account_status=' . $this->request->get['filter_account_status'];
        }

        if (isset($this->request->get['filter_balance_min'])) {
            $url .= '&filter_balance_min=' . $this->request->get['filter_balance_min'];
        }

        if (isset($this->request->get['filter_balance_max'])) {
            $url .= '&filter_balance_max=' . $this->request->get['filter_balance_max'];
        }

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
        $pagination->url = $this->url->link('supplier/accounts', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($account_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($account_total - $this->config->get('config_limit_admin'))) ? $account_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $account_total, ceil($account_total / $this->config->get('config_limit_admin')));

        $data['filter_supplier_name'] = $filter_supplier_name;
        $data['filter_account_status'] = $filter_account_status;
        $data['filter_balance_min'] = $filter_balance_min;
        $data['filter_balance_max'] = $filter_balance_max;

        $data['sort'] = $sort;
        $data['order'] = $order;

        // إحصائيات الحسابات
        $data['account_statistics'] = $this->model_supplier_accounts->getAccountStatistics();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/accounts_list', $data));
    }

    /**
     * التحقق من صحة بيانات المعاملة
     */
    protected function validateTransaction() {
        if (empty($this->request->post['supplier_id'])) {
            $this->error['supplier_id'] = $this->language->get('error_supplier');
        }

        if (empty($this->request->post['transaction_type'])) {
            $this->error['transaction_type'] = $this->language->get('error_transaction_type');
        }

        if (empty($this->request->post['amount']) || $this->request->post['amount'] <= 0) {
            $this->error['amount'] = $this->language->get('error_amount');
        }

        if (empty($this->request->post['transaction_date'])) {
            $this->error['transaction_date'] = $this->language->get('error_transaction_date');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة بيانات الدفعة
     */
    protected function validatePayment() {
        if (empty($this->request->post['supplier_id'])) {
            $this->error['supplier_id'] = $this->language->get('error_supplier');
        }

        if (empty($this->request->post['payment_amount']) || $this->request->post['payment_amount'] <= 0) {
            $this->error['payment_amount'] = $this->language->get('error_payment_amount');
        }

        if (empty($this->request->post['payment_method'])) {
            $this->error['payment_method'] = $this->language->get('error_payment_method');
        }

        if (empty($this->request->post['payment_date'])) {
            $this->error['payment_date'] = $this->language->get('error_payment_date');
        }

        return !$this->error;
    }

    /**
     * الحصول على فئة CSS للرصيد
     */
    private function getBalanceClass($balance) {
        if ($balance > 0) {
            return 'success'; // رصيد موجب
        } elseif ($balance < 0) {
            return 'danger'; // رصيد سالب
        } else {
            return 'default'; // رصيد صفر
        }
    }

    /**
     * تصدير حسابات الموردين
     */
    public function export() {
        $this->load->language('supplier/accounts');

        if (!$this->user->hasPermission('access', 'supplier/accounts')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('supplier/accounts', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->load->model('supplier/accounts');

        $filter_data = array();

        if (isset($this->request->get['filter_supplier_name'])) {
            $filter_data['filter_supplier_name'] = $this->request->get['filter_supplier_name'];
        }

        if (isset($this->request->get['filter_account_status'])) {
            $filter_data['filter_account_status'] = $this->request->get['filter_account_status'];
        }

        $results = $this->model_supplier_accounts->getSupplierAccounts($filter_data);

        $filename = 'supplier_accounts_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // رؤوس الأعمدة
        fputcsv($output, array(
            $this->language->get('column_supplier'),
            $this->language->get('column_account_number'),
            $this->language->get('column_current_balance'),
            $this->language->get('column_credit_limit'),
            $this->language->get('column_payment_terms'),
            $this->language->get('column_account_status'),
            $this->language->get('column_last_transaction')
        ));

        // البيانات
        foreach ($results as $result) {
            fputcsv($output, array(
                $result['supplier_name'],
                $result['account_number'],
                $result['current_balance'],
                $result['credit_limit'],
                $result['payment_terms'],
                $result['account_status'],
                $result['last_transaction']
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * تقرير أعمار الديون
     */
    public function agingReport() {
        $this->load->language('supplier/accounts');

        $this->document->setTitle($this->language->get('text_aging_report'));

        $this->load->model('supplier/accounts');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/accounts', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_aging_report'),
            'href' => $this->url->link('supplier/accounts/agingReport', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['aging_report'] = $this->model_supplier_accounts->getAgingReport();
        $data['aging_summary'] = $this->model_supplier_accounts->getAgingSummary();

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/accounts_aging', $data));
    }

    /**
     * تقرير كشف حساب مورد
     */
    public function statement() {
        $this->load->language('supplier/accounts');

        $this->document->setTitle($this->language->get('text_statement'));

        $this->load->model('supplier/accounts');

        if (isset($this->request->get['supplier_id'])) {
            $supplier_id = $this->request->get['supplier_id'];
        } else {
            $supplier_id = 0;
        }

        if (isset($this->request->get['date_start'])) {
            $date_start = $this->request->get['date_start'];
        } else {
            $date_start = date('Y-m-01'); // بداية الشهر الحالي
        }

        if (isset($this->request->get['date_end'])) {
            $date_end = $this->request->get['date_end'];
        } else {
            $date_end = date('Y-m-d'); // اليوم
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/accounts', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_statement'),
            'href' => $this->url->link('supplier/accounts/statement', 'user_token=' . $this->session->data['user_token'] . '&supplier_id=' . $supplier_id, true)
        );

        if ($supplier_id) {
            $data['supplier_info'] = $this->model_supplier_accounts->getSupplierAccount($supplier_id);
            $data['statement_transactions'] = $this->model_supplier_accounts->getStatementTransactions($supplier_id, $date_start, $date_end);
            $data['opening_balance'] = $this->model_supplier_accounts->getOpeningBalance($supplier_id, $date_start);
            $data['closing_balance'] = $this->model_supplier_accounts->getClosingBalance($supplier_id, $date_end);
        } else {
            $data['supplier_info'] = array();
            $data['statement_transactions'] = array();
            $data['opening_balance'] = 0;
            $data['closing_balance'] = 0;
        }

        // قائمة الموردين
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        $data['supplier_id'] = $supplier_id;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/accounts_statement', $data));
    }

    /**
     * تحديث حد الائتمان
     */
    public function updateCreditLimit() {
        $this->load->language('supplier/accounts');
        $json = array();

        if (!$this->user->hasPermission('modify', 'supplier/accounts')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('supplier/accounts');

            if (isset($this->request->post['supplier_id']) && isset($this->request->post['credit_limit'])) {
                $supplier_id = $this->request->post['supplier_id'];
                $credit_limit = $this->request->post['credit_limit'];

                if ($credit_limit >= 0) {
                    $this->model_supplier_accounts->updateCreditLimit($supplier_id, $credit_limit);
                    $json['success'] = $this->language->get('text_credit_limit_updated');
                } else {
                    $json['error'] = $this->language->get('error_credit_limit');
                }
            } else {
                $json['error'] = $this->language->get('error_missing_data');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تجميد/إلغاء تجميد حساب
     */
    public function toggleAccountStatus() {
        $this->load->language('supplier/accounts');
        $json = array();

        if (!$this->user->hasPermission('modify', 'supplier/accounts')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('supplier/accounts');

            if (isset($this->request->post['supplier_id'])) {
                $supplier_id = $this->request->post['supplier_id'];
                $new_status = $this->model_supplier_accounts->toggleAccountStatus($supplier_id);

                if ($new_status !== false) {
                    $json['success'] = $this->language->get('text_status_updated');
                    $json['new_status'] = $new_status;
                } else {
                    $json['error'] = $this->language->get('error_update_status');
                }
            } else {
                $json['error'] = $this->language->get('error_missing_data');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
