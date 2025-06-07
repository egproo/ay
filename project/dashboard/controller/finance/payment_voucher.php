<?php
/**
 * سندات الصرف المتقدمة
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ControllerFinancePaymentVoucher extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('finance/payment_voucher');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/payment_voucher');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/finance/payment_voucher.css');
        $this->document->addScript('view/javascript/finance/payment_voucher.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'payment_vouchers',
            'record_id' => 0,
            'description' => 'عرض شاشة سندات الصرف',
            'module' => 'payment_voucher'
        ]);

        $this->getList();
    }

    public function add() {
        $this->load->language('finance/payment_voucher');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/payment_voucher');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $voucher_data = $this->prepareVoucherData();

                $voucher_id = $this->model_finance_payment_voucher->addPaymentVoucher($voucher_data);

                // تسجيل إنشاء السند
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'create',
                    'table_name' => 'payment_vouchers',
                    'record_id' => $voucher_id,
                    'description' => 'إنشاء سند صرف رقم: ' . $voucher_data['voucher_number'],
                    'module' => 'payment_voucher'
                ]);

                $this->session->data['success'] = 'تم إنشاء سند الصرف بنجاح';

                $this->response->redirect($this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إنشاء سند الصرف: ' . $e->getMessage();
            }
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('finance/payment_voucher');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/payment_voucher');
        $this->load->model('accounts/journal_security_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $voucher_id = $this->request->get['voucher_id'];

            // التحقق من إمكانية التعديل
            $can_modify = $this->model_accounts_journal_security_advanced->canModifyEntry($voucher_id);

            if (!$can_modify['allowed']) {
                $this->error['warning'] = $can_modify['reason'];
            } else {
                try {
                    $voucher_data = $this->prepareVoucherData();

                    $this->model_finance_payment_voucher->editPaymentVoucher($voucher_id, $voucher_data);

                    // تسجيل التعديل
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'edit',
                        'table_name' => 'payment_vouchers',
                        'record_id' => $voucher_id,
                        'description' => 'تعديل سند صرف رقم: ' . $voucher_data['voucher_number'],
                        'module' => 'payment_voucher'
                    ]);

                    $this->session->data['success'] = 'تم تعديل سند الصرف بنجاح';

                    $this->response->redirect($this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'], true));

                } catch (Exception $e) {
                    $this->error['warning'] = 'خطأ في تعديل سند الصرف: ' . $e->getMessage();
                }
            }
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');
        $this->load->model('accounts/journal_security_advanced');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $voucher_id) {
                // التحقق من إمكانية الحذف
                $can_delete = $this->model_accounts_journal_security_advanced->canDeleteEntry($voucher_id);

                if ($can_delete['allowed']) {
                    $this->model_finance_payment_voucher->deletePaymentVoucher($voucher_id);

                    // تسجيل الحذف
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'delete',
                        'table_name' => 'payment_vouchers',
                        'record_id' => $voucher_id,
                        'description' => 'حذف سند صرف رقم: ' . $voucher_id,
                        'module' => 'payment_voucher'
                    ]);
                }
            }

            $this->session->data['success'] = 'تم حذف سندات الصرف المحددة بنجاح';
        }

        $this->response->redirect($this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function approve() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        $json = array();

        if (isset($this->request->post['voucher_id'])) {
            $voucher_id = $this->request->post['voucher_id'];

            try {
                $result = $this->model_finance_payment_voucher->approvePaymentVoucher($voucher_id);

                if ($result) {
                    $json['success'] = 'تم اعتماد سند الصرف بنجاح';

                    // تسجيل الاعتماد
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'approve',
                        'table_name' => 'payment_vouchers',
                        'record_id' => $voucher_id,
                        'description' => 'اعتماد سند صرف رقم: ' . $voucher_id,
                        'module' => 'payment_voucher'
                    ]);
                } else {
                    $json['error'] = 'فشل في اعتماد سند الصرف';
                }

            } catch (Exception $e) {
                $json['error'] = 'خطأ في اعتماد سند الصرف: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف سند الصرف مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function post() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        $json = array();

        if (isset($this->request->post['voucher_id'])) {
            $voucher_id = $this->request->post['voucher_id'];

            try {
                $result = $this->model_finance_payment_voucher->postPaymentVoucher($voucher_id);

                if ($result) {
                    $json['success'] = 'تم ترحيل سند الصرف بنجاح';
                    $json['journal_id'] = $result['journal_id'];

                    // تسجيل الترحيل
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'post',
                        'table_name' => 'payment_vouchers',
                        'record_id' => $voucher_id,
                        'description' => 'ترحيل سند صرف رقم: ' . $voucher_id . ' - قيد رقم: ' . $result['journal_id'],
                        'module' => 'payment_voucher'
                    ]);
                } else {
                    $json['error'] = 'فشل في ترحيل سند الصرف';
                }

            } catch (Exception $e) {
                $json['error'] = 'خطأ في ترحيل سند الصرف: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف سند الصرف مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function print() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        if (isset($this->request->get['voucher_id'])) {
            $voucher_id = $this->request->get['voucher_id'];

            $voucher_info = $this->model_finance_payment_voucher->getPaymentVoucher($voucher_id);

            if ($voucher_info) {
                // تسجيل الطباعة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'print',
                    'table_name' => 'payment_vouchers',
                    'record_id' => $voucher_id,
                    'description' => 'طباعة سند صرف رقم: ' . $voucher_info['voucher_number'],
                    'module' => 'payment_voucher'
                ]);

                $this->getPrintView($voucher_info);
            } else {
                $this->session->data['error'] = 'سند الصرف غير موجود';
                $this->response->redirect($this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'], true));
            }
        }
    }

    public function getSupplierBalance() {
        $this->load->model('finance/payment_voucher');

        $json = array();

        if (isset($this->request->get['supplier_id'])) {
            $supplier_id = $this->request->get['supplier_id'];

            try {
                $balance = $this->model_finance_payment_voucher->getSupplierBalance($supplier_id);

                $json['success'] = true;
                $json['balance'] = $balance;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف المورد مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getSupplierInvoices() {
        $this->load->model('finance/payment_voucher');

        $json = array();

        if (isset($this->request->get['supplier_id'])) {
            $supplier_id = $this->request->get['supplier_id'];

            try {
                $invoices = $this->model_finance_payment_voucher->getSupplierUnpaidInvoices($supplier_id);

                $json['success'] = true;
                $json['invoices'] = $invoices;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف المورد مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'finance/payment_voucher')) {
            $this->error['warning'] = 'ليس لديك صلاحية تعديل سندات الصرف';
        }

        if (empty($this->request->post['voucher_date'])) {
            $this->error['voucher_date'] = 'تاريخ السند مطلوب';
        }

        if (empty($this->request->post['payee_type'])) {
            $this->error['payee_type'] = 'نوع المستفيد مطلوب';
        }

        if (empty($this->request->post['payee_id'])) {
            $this->error['payee_id'] = 'المستفيد مطلوب';
        }

        if (empty($this->request->post['amount']) || $this->request->post['amount'] <= 0) {
            $this->error['amount'] = 'المبلغ مطلوب ويجب أن يكون أكبر من صفر';
        }

        if (empty($this->request->post['payment_method'])) {
            $this->error['payment_method'] = 'طريقة الدفع مطلوبة';
        }

        if (empty($this->request->post['cash_account_id']) && empty($this->request->post['bank_account_id'])) {
            $this->error['account'] = 'حساب النقدية أو البنك مطلوب';
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'finance/payment_voucher')) {
            $this->error['warning'] = 'ليس لديك صلاحية حذف سندات الصرف';
        }

        return !$this->error;
    }

    protected function prepareVoucherData() {
        return array(
            'voucher_date' => $this->request->post['voucher_date'],
            'payee_type' => $this->request->post['payee_type'], // supplier, employee, other
            'payee_id' => $this->request->post['payee_id'],
            'payee_name' => $this->request->post['payee_name'] ?? '',
            'amount' => $this->request->post['amount'],
            'payment_method' => $this->request->post['payment_method'],
            'cash_account_id' => $this->request->post['cash_account_id'] ?? null,
            'bank_account_id' => $this->request->post['bank_account_id'] ?? null,
            'check_number' => $this->request->post['check_number'] ?? '',
            'check_date' => $this->request->post['check_date'] ?? null,
            'bank_name' => $this->request->post['bank_name'] ?? '',
            'reference_number' => $this->request->post['reference_number'] ?? '',
            'purpose' => $this->request->post['purpose'] ?? '',
            'notes' => $this->request->post['notes'] ?? '',
            'expense_account_id' => $this->request->post['expense_account_id'] ?? null,
            'invoice_allocations' => $this->request->post['invoice_allocations'] ?? array()
        );
    }

    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'voucher_date';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
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
            'href' => $this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('finance/payment_voucher/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('finance/payment_voucher/delete', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['approve_url'] = $this->url->link('finance/payment_voucher/approve', 'user_token=' . $this->session->data['user_token'], true);
        $data['post_url'] = $this->url->link('finance/payment_voucher/post', 'user_token=' . $this->session->data['user_token'], true);
        $data['supplier_balance_url'] = $this->url->link('finance/payment_voucher/getSupplierBalance', 'user_token=' . $this->session->data['user_token'], true);
        $data['supplier_invoices_url'] = $this->url->link('finance/payment_voucher/getSupplierInvoices', 'user_token=' . $this->session->data['user_token'], true);

        $data['vouchers'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $voucher_total = $this->model_finance_payment_voucher->getTotalPaymentVouchers();

        $results = $this->model_finance_payment_voucher->getPaymentVouchers($filter_data);

        foreach ($results as $result) {
            $data['vouchers'][] = array(
                'voucher_id'        => $result['voucher_id'],
                'voucher_number'    => $result['voucher_number'],
                'voucher_date'      => date($this->language->get('date_format_short'), strtotime($result['voucher_date'])),
                'payee_name'        => $result['payee_name'],
                'payee_type'        => $result['payee_type'],
                'amount'            => $this->currency->format($result['amount'], $this->config->get('config_currency')),
                'payment_method'    => $result['payment_method'],
                'purpose'           => $result['purpose'],
                'status'            => $result['status'],
                'is_approved'       => $result['is_approved'],
                'is_posted'         => $result['is_posted'],
                'edit'              => $this->url->link('finance/payment_voucher/edit', 'user_token=' . $this->session->data['user_token'] . '&voucher_id=' . $result['voucher_id'], true),
                'print'             => $this->url->link('finance/payment_voucher/print', 'user_token=' . $this->session->data['user_token'] . '&voucher_id=' . $result['voucher_id'], true)
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

        $data['sort_voucher_number'] = $this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'] . '&sort=voucher_number' . $url, true);
        $data['sort_voucher_date'] = $this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'] . '&sort=voucher_date' . $url, true);
        $data['sort_payee'] = $this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'] . '&sort=payee_name' . $url, true);
        $data['sort_amount'] = $this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'] . '&sort=amount' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $voucher_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($voucher_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($voucher_total - $this->config->get('config_limit_admin'))) ? $voucher_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $voucher_total, ceil($voucher_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/payment_voucher_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['voucher_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['voucher_date'])) {
            $data['error_voucher_date'] = $this->error['voucher_date'];
        } else {
            $data['error_voucher_date'] = '';
        }

        if (isset($this->error['payee_type'])) {
            $data['error_payee_type'] = $this->error['payee_type'];
        } else {
            $data['error_payee_type'] = '';
        }

        if (isset($this->error['amount'])) {
            $data['error_amount'] = $this->error['amount'];
        } else {
            $data['error_amount'] = '';
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
            'href' => $this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['voucher_id'])) {
            $data['action'] = $this->url->link('finance/payment_voucher/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('finance/payment_voucher/edit', 'user_token=' . $this->session->data['user_token'] . '&voucher_id=' . $this->request->get['voucher_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['voucher_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $voucher_info = $this->model_finance_payment_voucher->getPaymentVoucher($this->request->get['voucher_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        // تحضير البيانات للنموذج
        if (isset($this->request->post['voucher_date'])) {
            $data['voucher_date'] = $this->request->post['voucher_date'];
        } elseif (!empty($voucher_info)) {
            $data['voucher_date'] = $voucher_info['voucher_date'];
        } else {
            $data['voucher_date'] = date('Y-m-d');
        }

        // تحميل قوائم البيانات
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        $this->load->model('hr/employee');
        $data['employees'] = $this->model_hr_employee->getEmployees();

        $this->load->model('accounts/chartaccount');
        $data['cash_accounts'] = $this->model_accounts_chartaccount->getAccountsByType('cash');
        $data['bank_accounts'] = $this->model_accounts_chartaccount->getAccountsByType('bank');
        $data['expense_accounts'] = $this->model_accounts_chartaccount->getAccountsByType('expense');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/payment_voucher_form', $data));
    }

    protected function getPrintView($voucher_info) {
        $data['voucher'] = $voucher_info;

        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/payment_voucher_print', $data));
    }

    public function search() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $filter_data = array(
                'voucher_number' => $this->request->post['voucher_number'] ?? '',
                'supplier_id' => $this->request->post['supplier_id'] ?? '',
                'date_from' => $this->request->post['date_from'] ?? '',
                'date_to' => $this->request->post['date_to'] ?? '',
                'amount_from' => $this->request->post['amount_from'] ?? '',
                'amount_to' => $this->request->post['amount_to'] ?? '',
                'status' => $this->request->post['status'] ?? '',
                'payment_method' => $this->request->post['payment_method'] ?? '',
                'is_approved' => $this->request->post['is_approved'] ?? '',
                'is_posted' => $this->request->post['is_posted'] ?? '',
                'sort' => $this->request->post['sort'] ?? 'voucher_date',
                'order' => $this->request->post['order'] ?? 'DESC',
                'start' => $this->request->post['start'] ?? 0,
                'limit' => $this->request->post['limit'] ?? 25
            );

            try {
                $results = $this->model_finance_payment_voucher->searchPaymentVouchers($filter_data);
                $total = $this->model_finance_payment_voucher->getTotalSearchResults($filter_data);

                $data = array();
                foreach ($results as $result) {
                    $data[] = array(
                        'voucher_id' => $result['voucher_id'],
                        'voucher_number' => $result['voucher_number'],
                        'voucher_date' => date('Y-m-d', strtotime($result['voucher_date'])),
                        'supplier_name' => $result['supplier_name'],
                        'amount' => number_format($result['amount'], 2),
                        'payment_method' => $result['payment_method'],
                        'status' => $result['status'],
                        'is_approved' => $result['is_approved'],
                        'is_posted' => $result['is_posted'],
                        'edit' => $this->url->link('finance/payment_voucher/edit', 'user_token=' . $this->session->data['user_token'] . '&voucher_id=' . $result['voucher_id'], true),
                        'print' => $this->url->link('finance/payment_voucher/print', 'user_token=' . $this->session->data['user_token'] . '&voucher_id=' . $result['voucher_id'], true)
                    );
                }

                $json['success'] = true;
                $json['data'] = $data;
                $json['total'] = $total;

            } catch (Exception $e) {
                $json['error'] = 'خطأ في البحث: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function reports() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $filter_data = array(
                'date_from' => $this->request->post['date_from'] ?? '',
                'date_to' => $this->request->post['date_to'] ?? ''
            );

            try {
                $reports = $this->model_finance_payment_voucher->getPaymentVoucherReports($filter_data);
                $json['success'] = true;
                $json['reports'] = $reports;

            } catch (Exception $e) {
                $json['error'] = 'خطأ في إنشاء التقرير: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function duplicate() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        $json = array();

        if (isset($this->request->post['voucher_id'])) {
            $voucher_id = $this->request->post['voucher_id'];

            try {
                $new_voucher_id = $this->model_finance_payment_voucher->duplicatePaymentVoucher($voucher_id);

                // تسجيل النسخ
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'duplicate',
                    'table_name' => 'payment_vouchers',
                    'record_id' => $new_voucher_id,
                    'description' => 'نسخ سند صرف من السند رقم: ' . $voucher_id,
                    'module' => 'payment_voucher'
                ]);

                $json['success'] = 'تم نسخ سند الصرف بنجاح';
                $json['new_voucher_id'] = $new_voucher_id;

            } catch (Exception $e) {
                $json['error'] = 'خطأ في نسخ سند الصرف: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف سند الصرف مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function reverse() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        $json = array();

        if (isset($this->request->post['voucher_id'])) {
            $voucher_id = $this->request->post['voucher_id'];
            $reason = $this->request->post['reason'] ?? '';

            try {
                $reverse_voucher_id = $this->model_finance_payment_voucher->reversePaymentVoucher($voucher_id, $reason);

                // تسجيل العكس
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'reverse',
                    'table_name' => 'payment_vouchers',
                    'record_id' => $voucher_id,
                    'description' => 'عكس سند صرف - السند العكسي رقم: ' . $reverse_voucher_id . ($reason ? ' - السبب: ' . $reason : ''),
                    'module' => 'payment_voucher'
                ]);

                $json['success'] = 'تم عكس سند الصرف بنجاح';
                $json['reverse_voucher_id'] = $reverse_voucher_id;

            } catch (Exception $e) {
                $json['error'] = 'خطأ في عكس سند الصرف: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف سند الصرف مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function export() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        if (!$this->user->hasPermission('access', 'finance/payment_voucher')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $filter_data = array(
            'voucher_number' => $this->request->get['voucher_number'] ?? '',
            'supplier_id' => $this->request->get['supplier_id'] ?? '',
            'date_from' => $this->request->get['date_from'] ?? '',
            'date_to' => $this->request->get['date_to'] ?? '',
            'status' => $this->request->get['status'] ?? '',
            'payment_method' => $this->request->get['payment_method'] ?? ''
        );

        $format = $this->request->get['format'] ?? 'csv';

        try {
            $export_data = $this->model_finance_payment_voucher->exportPaymentVouchers($filter_data, $format);

            $filename = 'payment_vouchers_' . date('Y-m-d_H-i-s') . '.' . $format;

            if ($format == 'csv') {
                $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
                $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
                $this->response->setOutput("\xEF\xBB\xBF" . $export_data); // UTF-8 BOM
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array('error' => 'تنسيق التصدير غير مدعوم')));
            }

        } catch (Exception $e) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => 'خطأ في التصدير: ' . $e->getMessage())));
        }
    }

    public function dashboard() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        $json = array();

        try {
            $stats = $this->model_finance_payment_voucher->getDashboardStatistics();
            $json['success'] = true;
            $json['statistics'] = $stats;

        } catch (Exception $e) {
            $json['error'] = 'خطأ في تحميل الإحصائيات: ' . $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function bulkApprove() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        $json = array();

        if (isset($this->request->post['voucher_ids']) && is_array($this->request->post['voucher_ids'])) {
            $voucher_ids = $this->request->post['voucher_ids'];
            $approved_count = 0;
            $errors = array();

            foreach ($voucher_ids as $voucher_id) {
                try {
                    $result = $this->model_finance_payment_voucher->approvePaymentVoucher($voucher_id);
                    if ($result) {
                        $approved_count++;

                        // تسجيل الاعتماد
                        $this->model_accounts_audit_trail->logAction([
                            'action_type' => 'bulk_approve',
                            'table_name' => 'payment_vouchers',
                            'record_id' => $voucher_id,
                            'description' => 'اعتماد جماعي لسند صرف رقم: ' . $voucher_id,
                            'module' => 'payment_voucher'
                        ]);
                    }
                } catch (Exception $e) {
                    $errors[] = 'السند رقم ' . $voucher_id . ': ' . $e->getMessage();
                }
            }

            if ($approved_count > 0) {
                $json['success'] = 'تم اعتماد ' . $approved_count . ' سند بنجاح';
            }

            if (!empty($errors)) {
                $json['warnings'] = $errors;
            }

            if ($approved_count == 0 && !empty($errors)) {
                $json['error'] = 'فشل في اعتماد جميع السندات';
            }

        } else {
            $json['error'] = 'لم يتم تحديد أي سندات';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function bulkPost() {
        $this->load->language('finance/payment_voucher');
        $this->load->model('finance/payment_voucher');

        $json = array();

        if (isset($this->request->post['voucher_ids']) && is_array($this->request->post['voucher_ids'])) {
            $voucher_ids = $this->request->post['voucher_ids'];
            $posted_count = 0;
            $errors = array();

            foreach ($voucher_ids as $voucher_id) {
                try {
                    $result = $this->model_finance_payment_voucher->postPaymentVoucher($voucher_id);
                    if ($result) {
                        $posted_count++;

                        // تسجيل الترحيل
                        $this->model_accounts_audit_trail->logAction([
                            'action_type' => 'bulk_post',
                            'table_name' => 'payment_vouchers',
                            'record_id' => $voucher_id,
                            'description' => 'ترحيل جماعي لسند صرف رقم: ' . $voucher_id . ' - قيد رقم: ' . $result['journal_id'],
                            'module' => 'payment_voucher'
                        ]);
                    }
                } catch (Exception $e) {
                    $errors[] = 'السند رقم ' . $voucher_id . ': ' . $e->getMessage();
                }
            }

            if ($posted_count > 0) {
                $json['success'] = 'تم ترحيل ' . $posted_count . ' سند بنجاح';
            }

            if (!empty($errors)) {
                $json['warnings'] = $errors;
            }

            if ($posted_count == 0 && !empty($errors)) {
                $json['error'] = 'فشل في ترحيل جميع السندات';
            }

        } else {
            $json['error'] = 'لم يتم تحديد أي سندات';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
