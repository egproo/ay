<?php
/**
 * التسوية البنكية المتقدمة
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ControllerFinanceBankReconciliation extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('finance/bank_reconciliation');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/bank_reconciliation');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/finance/bank_reconciliation.css');
        $this->document->addScript('view/javascript/finance/bank_reconciliation.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');
        $this->document->addScript('view/javascript/jquery/datatables.min.js');
        $this->document->addStyle('view/javascript/jquery/datatables.min.css');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'bank_reconciliation',
            'record_id' => 0,
            'description' => 'عرض شاشة التسوية البنكية',
            'module' => 'bank_reconciliation'
        ]);

        $this->getList();
    }

    public function add() {
        $this->load->language('finance/bank_reconciliation');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/bank_reconciliation');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $reconciliation_data = $this->prepareReconciliationData();

                $reconciliation_id = $this->model_finance_bank_reconciliation->addBankReconciliation($reconciliation_data);

                // تسجيل إنشاء التسوية
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'create',
                    'table_name' => 'bank_reconciliation',
                    'record_id' => $reconciliation_id,
                    'description' => 'إنشاء تسوية بنكية جديدة للفترة: ' . $reconciliation_data['period_from'] . ' - ' . $reconciliation_data['period_to'],
                    'module' => 'bank_reconciliation'
                ]);

                $this->session->data['success'] = 'تم إنشاء التسوية البنكية بنجاح';

                $this->response->redirect($this->url->link('finance/bank_reconciliation/edit', 'user_token=' . $this->session->data['user_token'] . '&reconciliation_id=' . $reconciliation_id, true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إنشاء التسوية البنكية: ' . $e->getMessage();
            }
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('finance/bank_reconciliation');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('finance/bank_reconciliation');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $reconciliation_id = $this->request->get['reconciliation_id'];

            try {
                $reconciliation_data = $this->prepareReconciliationData();

                $this->model_finance_bank_reconciliation->editBankReconciliation($reconciliation_id, $reconciliation_data);

                // تسجيل التعديل
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'edit',
                    'table_name' => 'bank_reconciliation',
                    'record_id' => $reconciliation_id,
                    'description' => 'تعديل تسوية بنكية رقم: ' . $reconciliation_id,
                    'module' => 'bank_reconciliation'
                ]);

                $this->session->data['success'] = 'تم تعديل التسوية البنكية بنجاح';

                $this->response->redirect($this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في تعديل التسوية البنكية: ' . $e->getMessage();
            }
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('finance/bank_reconciliation');
        $this->load->model('finance/bank_reconciliation');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $reconciliation_id) {
                $this->model_finance_bank_reconciliation->deleteBankReconciliation($reconciliation_id);

                // تسجيل الحذف
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'delete',
                    'table_name' => 'bank_reconciliation',
                    'record_id' => $reconciliation_id,
                    'description' => 'حذف تسوية بنكية رقم: ' . $reconciliation_id,
                    'module' => 'bank_reconciliation'
                ]);
            }

            $this->session->data['success'] = 'تم حذف التسويات البنكية المحددة بنجاح';
        }

        $this->response->redirect($this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function autoReconcile() {
        $this->load->language('finance/bank_reconciliation');
        $this->load->model('finance/bank_reconciliation');

        $json = array();

        if (isset($this->request->post['reconciliation_id'])) {
            $reconciliation_id = $this->request->post['reconciliation_id'];

            try {
                $result = $this->model_finance_bank_reconciliation->performAutoReconciliation($reconciliation_id);

                $json['success'] = true;
                $json['result'] = $result;
                $json['message'] = "تم تطبيق التسوية التلقائية - تم تطابق {$result['matched_items']} عنصر";

                // تسجيل التسوية التلقائية
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'auto_reconcile',
                    'table_name' => 'bank_reconciliation',
                    'record_id' => $reconciliation_id,
                    'description' => "تسوية تلقائية - تطابق {$result['matched_items']} عنصر",
                    'module' => 'bank_reconciliation'
                ]);

            } catch (Exception $e) {
                $json['error'] = 'خطأ في التسوية التلقائية: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف التسوية مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function smartMatch() {
        $this->load->language('finance/bank_reconciliation');
        $this->load->model('finance/bank_reconciliation');

        $json = array();

        if (isset($this->request->post['reconciliation_id'])) {
            $reconciliation_id = $this->request->post['reconciliation_id'];
            $tolerance = $this->request->post['tolerance'] ?? 0.01;

            try {
                $result = $this->model_finance_bank_reconciliation->performSmartMatching($reconciliation_id, $tolerance);

                $json['success'] = true;
                $json['result'] = $result;
                $json['message'] = "تم التطابق الذكي - {$result['suggested_matches']} اقتراح تطابق";

                // تسجيل التطابق الذكي
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'smart_match',
                    'table_name' => 'bank_reconciliation',
                    'record_id' => $reconciliation_id,
                    'description' => "تطابق ذكي - {$result['suggested_matches']} اقتراح",
                    'module' => 'bank_reconciliation'
                ]);

            } catch (Exception $e) {
                $json['error'] = 'خطأ في التطابق الذكي: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف التسوية مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function importBankStatement() {
        $this->load->language('finance/bank_reconciliation');
        $this->load->model('finance/bank_reconciliation');

        $json = array();

        if (isset($this->files['bank_statement']) && $this->validateImport()) {
            try {
                $bank_account_id = $this->request->post['bank_account_id'];
                $file_format = $this->request->post['file_format']; // csv, excel, ofx, qif

                $result = $this->model_finance_bank_reconciliation->importBankStatement(
                    $this->files['bank_statement'],
                    $bank_account_id,
                    $file_format
                );

                $json['success'] = true;
                $json['result'] = $result;
                $json['message'] = "تم استيراد {$result['imported_transactions']} معاملة من كشف البنك";

                // تسجيل الاستيراد
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'import_statement',
                    'table_name' => 'bank_statements',
                    'record_id' => $result['statement_id'],
                    'description' => "استيراد كشف بنك - {$result['imported_transactions']} معاملة",
                    'module' => 'bank_reconciliation'
                ]);

            } catch (Exception $e) {
                $json['error'] = 'خطأ في استيراد كشف البنك: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'ملف كشف البنك مطلوب';
            $json['errors'] = $this->error;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function finalize() {
        $this->load->language('finance/bank_reconciliation');
        $this->load->model('finance/bank_reconciliation');

        $json = array();

        if (isset($this->request->post['reconciliation_id'])) {
            $reconciliation_id = $this->request->post['reconciliation_id'];

            try {
                $result = $this->model_finance_bank_reconciliation->finalizeReconciliation($reconciliation_id);

                if ($result) {
                    $json['success'] = 'تم إنهاء التسوية البنكية بنجاح';

                    // تسجيل الإنهاء
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'finalize',
                        'table_name' => 'bank_reconciliation',
                        'record_id' => $reconciliation_id,
                        'description' => 'إنهاء تسوية بنكية رقم: ' . $reconciliation_id,
                        'module' => 'bank_reconciliation'
                    ]);
                } else {
                    $json['error'] = 'فشل في إنهاء التسوية البنكية';
                }

            } catch (Exception $e) {
                $json['error'] = 'خطأ في إنهاء التسوية البنكية: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف التسوية مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getUnreconciledItems() {
        $this->load->model('finance/bank_reconciliation');

        $json = array();

        if (isset($this->request->get['reconciliation_id'])) {
            $reconciliation_id = $this->request->get['reconciliation_id'];

            try {
                $items = $this->model_finance_bank_reconciliation->getUnreconciledItems($reconciliation_id);

                $json['success'] = true;
                $json['items'] = $items;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف التسوية مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getReconciliationSummary() {
        $this->load->model('finance/bank_reconciliation');

        $json = array();

        if (isset($this->request->get['reconciliation_id'])) {
            $reconciliation_id = $this->request->get['reconciliation_id'];

            try {
                $summary = $this->model_finance_bank_reconciliation->getReconciliationSummary($reconciliation_id);

                $json['success'] = true;
                $json['summary'] = $summary;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف التسوية مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function markAsReconciled() {
        $this->load->model('finance/bank_reconciliation');

        $json = array();

        if (isset($this->request->post['items']) && isset($this->request->post['reconciliation_id'])) {
            $items = $this->request->post['items'];
            $reconciliation_id = $this->request->post['reconciliation_id'];

            try {
                $result = $this->model_finance_bank_reconciliation->markItemsAsReconciled($reconciliation_id, $items);

                $json['success'] = true;
                $json['marked_items'] = $result;
                $json['message'] = "تم تطبيق التسوية على {$result} عنصر";

            } catch (Exception $e) {
                $json['error'] = 'خطأ في تطبيق التسوية: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'العناصر ومعرف التسوية مطلوبان';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'finance/bank_reconciliation')) {
            $this->error['warning'] = 'ليس لديك صلاحية تعديل التسوية البنكية';
        }

        if (empty($this->request->post['bank_account_id'])) {
            $this->error['bank_account_id'] = 'الحساب البنكي مطلوب';
        }

        if (empty($this->request->post['period_from'])) {
            $this->error['period_from'] = 'تاريخ بداية الفترة مطلوب';
        }

        if (empty($this->request->post['period_to'])) {
            $this->error['period_to'] = 'تاريخ نهاية الفترة مطلوب';
        }

        if (!empty($this->request->post['period_from']) && !empty($this->request->post['period_to'])) {
            if (strtotime($this->request->post['period_from']) > strtotime($this->request->post['period_to'])) {
                $this->error['period'] = 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية';
            }
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'finance/bank_reconciliation')) {
            $this->error['warning'] = 'ليس لديك صلاحية حذف التسوية البنكية';
        }

        return !$this->error;
    }

    protected function validateImport() {
        if (empty($this->request->post['bank_account_id'])) {
            $this->error['bank_account_id'] = 'الحساب البنكي مطلوب';
        }

        if (empty($this->request->post['file_format'])) {
            $this->error['file_format'] = 'صيغة الملف مطلوبة';
        }

        return !$this->error;
    }

    protected function prepareReconciliationData() {
        return array(
            'bank_account_id' => $this->request->post['bank_account_id'],
            'period_from' => $this->request->post['period_from'],
            'period_to' => $this->request->post['period_to'],
            'statement_balance' => $this->request->post['statement_balance'] ?? 0,
            'notes' => $this->request->post['notes'] ?? ''
        );
    }

    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'reconciliation_date';
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
            'href' => $this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('finance/bank_reconciliation/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('finance/bank_reconciliation/delete', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['auto_reconcile_url'] = $this->url->link('finance/bank_reconciliation/autoReconcile', 'user_token=' . $this->session->data['user_token'], true);
        $data['smart_match_url'] = $this->url->link('finance/bank_reconciliation/smartMatch', 'user_token=' . $this->session->data['user_token'], true);
        $data['import_statement_url'] = $this->url->link('finance/bank_reconciliation/importBankStatement', 'user_token=' . $this->session->data['user_token'], true);
        $data['finalize_url'] = $this->url->link('finance/bank_reconciliation/finalize', 'user_token=' . $this->session->data['user_token'], true);
        $data['unreconciled_items_url'] = $this->url->link('finance/bank_reconciliation/getUnreconciledItems', 'user_token=' . $this->session->data['user_token'], true);
        $data['summary_url'] = $this->url->link('finance/bank_reconciliation/getReconciliationSummary', 'user_token=' . $this->session->data['user_token'], true);
        $data['mark_reconciled_url'] = $this->url->link('finance/bank_reconciliation/markAsReconciled', 'user_token=' . $this->session->data['user_token'], true);

        $data['reconciliations'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $reconciliation_total = $this->model_finance_bank_reconciliation->getTotalBankReconciliations();

        $results = $this->model_finance_bank_reconciliation->getBankReconciliations($filter_data);

        foreach ($results as $result) {
            $data['reconciliations'][] = array(
                'reconciliation_id'     => $result['reconciliation_id'],
                'bank_account_name'     => $result['bank_account_name'],
                'period_from'           => date($this->language->get('date_format_short'), strtotime($result['period_from'])),
                'period_to'             => date($this->language->get('date_format_short'), strtotime($result['period_to'])),
                'statement_balance'     => $this->currency->format($result['statement_balance'], $this->config->get('config_currency')),
                'book_balance'          => $this->currency->format($result['book_balance'], $this->config->get('config_currency')),
                'difference'            => $this->currency->format($result['difference'], $this->config->get('config_currency')),
                'status'                => $result['status'],
                'reconciled_items'      => $result['reconciled_items'],
                'unreconciled_items'    => $result['unreconciled_items'],
                'reconciliation_date'   => $result['reconciliation_date'] ? date($this->language->get('date_format_short'), strtotime($result['reconciliation_date'])) : 'غير مكتمل',
                'edit'                  => $this->url->link('finance/bank_reconciliation/edit', 'user_token=' . $this->session->data['user_token'] . '&reconciliation_id=' . $result['reconciliation_id'], true),
                'view'                  => $this->url->link('finance/bank_reconciliation/view', 'user_token=' . $this->session->data['user_token'] . '&reconciliation_id=' . $result['reconciliation_id'], true)
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

        $data['sort_bank_account'] = $this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'] . '&sort=bank_account_name' . $url, true);
        $data['sort_period'] = $this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'] . '&sort=period_from' . $url, true);
        $data['sort_difference'] = $this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'] . '&sort=difference' . $url, true);
        $data['sort_status'] = $this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $reconciliation_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($reconciliation_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($reconciliation_total - $this->config->get('config_limit_admin'))) ? $reconciliation_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $reconciliation_total, ceil($reconciliation_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/bank_reconciliation_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['reconciliation_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['bank_account_id'])) {
            $data['error_bank_account_id'] = $this->error['bank_account_id'];
        } else {
            $data['error_bank_account_id'] = '';
        }

        if (isset($this->error['period_from'])) {
            $data['error_period_from'] = $this->error['period_from'];
        } else {
            $data['error_period_from'] = '';
        }

        if (isset($this->error['period_to'])) {
            $data['error_period_to'] = $this->error['period_to'];
        } else {
            $data['error_period_to'] = '';
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
            'href' => $this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['reconciliation_id'])) {
            $data['action'] = $this->url->link('finance/bank_reconciliation/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('finance/bank_reconciliation/edit', 'user_token=' . $this->session->data['user_token'] . '&reconciliation_id=' . $this->request->get['reconciliation_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['reconciliation_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $reconciliation_info = $this->model_finance_bank_reconciliation->getBankReconciliation($this->request->get['reconciliation_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        // تحضير البيانات للنموذج
        if (isset($this->request->post['period_from'])) {
            $data['period_from'] = $this->request->post['period_from'];
        } elseif (!empty($reconciliation_info)) {
            $data['period_from'] = $reconciliation_info['period_from'];
        } else {
            $data['period_from'] = date('Y-m-01');
        }

        if (isset($this->request->post['period_to'])) {
            $data['period_to'] = $this->request->post['period_to'];
        } elseif (!empty($reconciliation_info)) {
            $data['period_to'] = $reconciliation_info['period_to'];
        } else {
            $data['period_to'] = date('Y-m-t');
        }

        // تحميل قوائم البيانات
        $this->load->model('accounts/chartaccount');
        $data['bank_accounts'] = $this->model_accounts_chartaccount->getAccountsByType('bank');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('finance/bank_reconciliation_form', $data));
    }
}
