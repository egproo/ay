<?php
/**
 * تحكم إدارة الموازنات التقديرية المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsBudgetManagementAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/budget_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/budget_management_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/budget_management.css');
        $this->document->addScript('view/javascript/accounts/budget_management.js');
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
            'table_name' => 'budget_management',
            'record_id' => 0,
            'description' => 'عرض شاشة إدارة الموازنات التقديرية',
            'module' => 'budget_management'
        ]);

        $this->getList();
    }

    public function add() {
        $this->load->language('accounts/budget_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/budget_management_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $budget_id = $this->model_accounts_budget_management_advanced->addBudget($this->request->post);

                // تسجيل إضافة الموازنة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'add_budget',
                    'table_name' => 'budget',
                    'record_id' => $budget_id,
                    'description' => 'إضافة موازنة جديدة: ' . $this->request->post['budget_name'],
                    'module' => 'budget_management'
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

                $this->response->redirect($this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إضافة الموازنة: ' . $e->getMessage();
            }
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('accounts/budget_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/budget_management_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $this->model_accounts_budget_management_advanced->editBudget($this->request->get['budget_id'], $this->request->post);

                // تسجيل تعديل الموازنة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'edit_budget',
                    'table_name' => 'budget',
                    'record_id' => $this->request->get['budget_id'],
                    'description' => 'تعديل الموازنة: ' . $this->request->post['budget_name'],
                    'module' => 'budget_management'
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

                $this->response->redirect($this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في تعديل الموازنة: ' . $e->getMessage();
            }
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('accounts/budget_management');
        $this->load->model('accounts/budget_management_advanced');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $budget_id) {
                $budget_info = $this->model_accounts_budget_management_advanced->getBudget($budget_id);

                $this->model_accounts_budget_management_advanced->deleteBudget($budget_id);

                // تسجيل حذف الموازنة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'delete_budget',
                    'table_name' => 'budget',
                    'record_id' => $budget_id,
                    'description' => 'حذف الموازنة: ' . $budget_info['budget_name'],
                    'module' => 'budget_management'
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

            $this->response->redirect($this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function copy() {
        $this->load->language('accounts/budget_management');
        $this->load->model('accounts/budget_management_advanced');

        if (isset($this->request->post['selected']) && $this->validateCopy()) {
            foreach ($this->request->post['selected'] as $budget_id) {
                $budget_info = $this->model_accounts_budget_management_advanced->getBudget($budget_id);

                $new_budget_id = $this->model_accounts_budget_management_advanced->copyBudget($budget_id);

                // تسجيل نسخ الموازنة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'copy_budget',
                    'table_name' => 'budget',
                    'record_id' => $new_budget_id,
                    'description' => 'نسخ الموازنة: ' . $budget_info['budget_name'],
                    'module' => 'budget_management'
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

            $this->response->redirect($this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function approve() {
        $this->load->language('accounts/budget_management');
        $this->load->model('accounts/budget_management_advanced');

        if (isset($this->request->get['budget_id']) && $this->validateApprove()) {
            $budget_id = $this->request->get['budget_id'];
            $budget_info = $this->model_accounts_budget_management_advanced->getBudget($budget_id);

            $this->model_accounts_budget_management_advanced->approveBudget($budget_id);

            // تسجيل اعتماد الموازنة
            $this->model_accounts_audit_trail->logAction([
                'action_type' => 'approve_budget',
                'table_name' => 'budget',
                'record_id' => $budget_id,
                'description' => 'اعتماد الموازنة: ' . $budget_info['budget_name'],
                'module' => 'budget_management'
            ]);

            $this->session->data['success'] = 'تم اعتماد الموازنة بنجاح';

            $this->response->redirect($this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getList();
    }

    public function getBudgetAnalysis() {
        $this->load->model('accounts/budget_management_advanced');

        $json = array();

        if (isset($this->request->get['budget_id'])) {
            try {
                $budget_id = $this->request->get['budget_id'];

                $analysis = $this->model_accounts_budget_management_advanced->analyzeBudget($budget_id);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الموازنة مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getVarianceAnalysis() {
        $this->load->model('accounts/budget_management_advanced');

        $json = array();

        if (isset($this->request->get['budget_id'])) {
            try {
                $budget_id = $this->request->get['budget_id'];

                $variance = $this->model_accounts_budget_management_advanced->calculateVarianceAnalysis($budget_id);

                $json['success'] = true;
                $json['variance'] = $variance;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الموازنة مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getBudgetPerformance() {
        $this->load->model('accounts/budget_management_advanced');

        $json = array();

        if (isset($this->request->get['budget_id'])) {
            try {
                $budget_id = $this->request->get['budget_id'];

                $performance = $this->model_accounts_budget_management_advanced->calculateBudgetPerformance($budget_id);

                $json['success'] = true;
                $json['performance'] = $performance;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الموازنة مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getForecast() {
        $this->load->model('accounts/budget_management_advanced');

        $json = array();

        if (isset($this->request->get['budget_id'])) {
            try {
                $budget_id = $this->request->get['budget_id'];

                $forecast = $this->model_accounts_budget_management_advanced->generateBudgetForecast($budget_id);

                $json['success'] = true;
                $json['forecast'] = $forecast;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الموازنة مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getScenarioAnalysis() {
        $this->load->model('accounts/budget_management_advanced');

        $json = array();

        if (isset($this->request->get['budget_id'])) {
            try {
                $budget_id = $this->request->get['budget_id'];
                $scenarios = $this->request->get['scenarios'] ?? 'optimistic,realistic,pessimistic';

                $scenario_analysis = $this->model_accounts_budget_management_advanced->performScenarioAnalysis($budget_id, $scenarios);

                $json['success'] = true;
                $json['scenarios'] = $scenario_analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الموازنة مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function export() {
        $this->load->language('accounts/budget_management');
        $this->load->model('accounts/budget_management_advanced');

        if (!isset($this->request->get['budget_id'])) {
            $this->session->data['error'] = 'معرف الموازنة مطلوب';
            $this->response->redirect($this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $budget_id = $this->request->get['budget_id'];
        $format = $this->request->get['format'] ?? 'excel';

        $budget_data = $this->model_accounts_budget_management_advanced->getBudgetForExport($budget_id);

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_budget',
            'table_name' => 'budget',
            'record_id' => $budget_id,
            'description' => "تصدير الموازنة بصيغة {$format}",
            'module' => 'budget_management'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($budget_data);
                break;
            case 'pdf':
                $this->exportToPdf($budget_data);
                break;
            case 'csv':
                $this->exportToCsv($budget_data);
                break;
            default:
                $this->exportToExcel($budget_data);
        }
    }

    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'budget_name';
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
            'href' => $this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('accounts/budget_management_advanced/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['copy'] = $this->url->link('accounts/budget_management_advanced/copy', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('accounts/budget_management_advanced/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        // URLs للـ AJAX
        $data['analysis_url'] = $this->url->link('accounts/budget_management_advanced/getBudgetAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['variance_url'] = $this->url->link('accounts/budget_management_advanced/getVarianceAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['performance_url'] = $this->url->link('accounts/budget_management_advanced/getBudgetPerformance', 'user_token=' . $this->session->data['user_token'], true);
        $data['forecast_url'] = $this->url->link('accounts/budget_management_advanced/getForecast', 'user_token=' . $this->session->data['user_token'], true);
        $data['scenario_url'] = $this->url->link('accounts/budget_management_advanced/getScenarioAnalysis', 'user_token=' . $this->session->data['user_token'], true);

        $data['budgets'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $budget_total = $this->model_accounts_budget_management_advanced->getTotalBudgets();

        $results = $this->model_accounts_budget_management_advanced->getBudgets($filter_data);

        foreach ($results as $result) {
            $data['budgets'][] = array(
                'budget_id'     => $result['budget_id'],
                'budget_name'   => $result['budget_name'],
                'budget_year'   => $result['budget_year'],
                'budget_type'   => $result['budget_type'],
                'status'        => $result['status'],
                'total_amount'  => $this->currency->format($result['total_amount'], $this->config->get('config_currency')),
                'created_date'  => date($this->language->get('date_format_short'), strtotime($result['created_date'])),
                'edit'          => $this->url->link('accounts/budget_management_advanced/edit', 'user_token=' . $this->session->data['user_token'] . '&budget_id=' . $result['budget_id'] . $url, true),
                'approve'       => $this->url->link('accounts/budget_management_advanced/approve', 'user_token=' . $this->session->data['user_token'] . '&budget_id=' . $result['budget_id'] . $url, true),
                'export'        => $this->url->link('accounts/budget_management_advanced/export', 'user_token=' . $this->session->data['user_token'] . '&budget_id=' . $result['budget_id'], true)
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

        $data['sort_budget_name'] = $this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=budget_name' . $url, true);
        $data['sort_budget_year'] = $this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=budget_year' . $url, true);
        $data['sort_status'] = $this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $budget_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($budget_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($budget_total - $this->config->get('config_limit_admin'))) ? $budget_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $budget_total, ceil($budget_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/budget_management_advanced_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['budget_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['budget_name'])) {
            $data['error_budget_name'] = $this->error['budget_name'];
        } else {
            $data['error_budget_name'] = '';
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
            'href' => $this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['budget_id'])) {
            $data['action'] = $this->url->link('accounts/budget_management_advanced/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('accounts/budget_management_advanced/edit', 'user_token=' . $this->session->data['user_token'] . '&budget_id=' . $this->request->get['budget_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('accounts/budget_management_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['budget_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $budget_info = $this->model_accounts_budget_management_advanced->getBudget($this->request->get['budget_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        // بيانات النموذج
        $fields = ['budget_name', 'budget_description', 'budget_year', 'budget_type', 'start_date', 'end_date', 'currency', 'status'];

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($budget_info)) {
                $data[$field] = $budget_info[$field];
            } else {
                $default_values = [
                    'budget_year' => date('Y'),
                    'budget_type' => 'annual',
                    'start_date' => date('Y-01-01'),
                    'end_date' => date('Y-12-31'),
                    'currency' => $this->config->get('config_currency'),
                    'status' => 'draft'
                ];
                $data[$field] = $default_values[$field] ?? '';
            }
        }

        // أنواع الموازنات
        $data['budget_types'] = array(
            'annual' => 'موازنة سنوية',
            'quarterly' => 'موازنة ربع سنوية',
            'monthly' => 'موازنة شهرية',
            'project' => 'موازنة مشروع',
            'department' => 'موازنة إدارة',
            'capital' => 'موازنة رأسمالية',
            'operational' => 'موازنة تشغيلية'
        );

        // حالات الموازنة
        $data['budget_statuses'] = array(
            'draft' => 'مسودة',
            'submitted' => 'مقدمة للاعتماد',
            'approved' => 'معتمدة',
            'active' => 'نشطة',
            'closed' => 'مغلقة',
            'cancelled' => 'ملغاة'
        );

        // العملات المتاحة
        $this->load->model('localisation/currency');
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        // الحصول على بنود الموازنة إذا كانت موجودة
        if (isset($this->request->get['budget_id'])) {
            $data['budget_lines'] = $this->model_accounts_budget_management_advanced->getBudgetLines($this->request->get['budget_id']);
        } else {
            $data['budget_lines'] = array();
        }

        // الحصول على دليل الحسابات
        $this->load->model('accounts/chart_of_accounts');
        $data['accounts'] = $this->model_accounts_chart_of_accounts->getAccounts();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/budget_management_advanced_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'accounts/budget_management_advanced')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['budget_name']) < 3) || (utf8_strlen($this->request->post['budget_name']) > 64)) {
            $this->error['budget_name'] = $this->language->get('error_budget_name');
        }

        if (empty($this->request->post['budget_year'])) {
            $this->error['budget_year'] = 'سنة الموازنة مطلوبة';
        }

        if (empty($this->request->post['start_date'])) {
            $this->error['start_date'] = 'تاريخ البداية مطلوب';
        }

        if (empty($this->request->post['end_date'])) {
            $this->error['end_date'] = 'تاريخ النهاية مطلوب';
        }

        if (!empty($this->request->post['start_date']) && !empty($this->request->post['end_date'])) {
            if (strtotime($this->request->post['start_date']) >= strtotime($this->request->post['end_date'])) {
                $this->error['date_range'] = 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية';
            }
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'accounts/budget_management_advanced')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateCopy() {
        if (!$this->user->hasPermission('modify', 'accounts/budget_management_advanced')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateApprove() {
        if (!$this->user->hasPermission('approve', 'accounts/budget_management_advanced')) {
            $this->error['warning'] = 'ليس لديك صلاحية اعتماد الموازنات';
        }

        return !$this->error;
    }

    private function exportToExcel($budget_data) {
        require_once(DIR_SYSTEM . 'library/phpspreadsheet/vendor/autoload.php');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // إعداد الرأس
        $sheet->setTitle('الموازنة التقديرية');
        $sheet->setCellValue('A1', $this->config->get('config_name'));
        $sheet->setCellValue('A2', 'الموازنة التقديرية: ' . $budget_data['budget_name']);
        $sheet->setCellValue('A3', 'السنة المالية: ' . $budget_data['budget_year']);

        $row = 5;

        // رؤوس الأعمدة
        $headers = ['رمز الحساب', 'اسم الحساب', 'المبلغ المخطط', 'المبلغ الفعلي', 'الانحراف', 'نسبة الانحراف'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;

        // البيانات
        foreach ($budget_data['lines'] as $line) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $line['account_code']);
            $sheet->setCellValue($col++ . $row, $line['account_name']);
            $sheet->setCellValue($col++ . $row, $line['budgeted_amount']);
            $sheet->setCellValue($col++ . $row, $line['actual_amount']);
            $sheet->setCellValue($col++ . $row, $line['variance']);
            $sheet->setCellValue($col++ . $row, $line['variance_percentage'] . '%');
            $row++;
        }

        // تنسيق
        $sheet->getStyle('A1:F' . $row)->getFont()->setName('Arial');

        // تصدير
        $filename = 'budget_' . $budget_data['budget_name'] . '_' . $budget_data['budget_year'] . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    private function exportToPdf($budget_data) {
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('ERP System');
        $pdf->SetAuthor($this->config->get('config_name'));
        $pdf->SetTitle('الموازنة التقديرية');

        $pdf->SetFont('aealarabiya', '', 12);
        $pdf->AddPage();

        // الرأس
        $pdf->Cell(0, 10, $this->config->get('config_name'), 0, 1, 'C');
        $pdf->Cell(0, 10, 'الموازنة التقديرية: ' . $budget_data['budget_name'], 0, 1, 'C');
        $pdf->Cell(0, 10, 'السنة المالية: ' . $budget_data['budget_year'], 0, 1, 'C');
        $pdf->Ln(10);

        // رؤوس الأعمدة
        $pdf->SetFont('aealarabiya', 'B', 8);
        $pdf->Cell(30, 6, 'رمز الحساب', 1, 0, 'C');
        $pdf->Cell(50, 6, 'اسم الحساب', 1, 0, 'C');
        $pdf->Cell(30, 6, 'المبلغ المخطط', 1, 0, 'C');
        $pdf->Cell(30, 6, 'المبلغ الفعلي', 1, 0, 'C');
        $pdf->Cell(25, 6, 'الانحراف', 1, 0, 'C');
        $pdf->Cell(25, 6, 'نسبة الانحراف', 1, 1, 'C');

        // البيانات
        $pdf->SetFont('aealarabiya', '', 7);
        foreach ($budget_data['lines'] as $line) {
            $pdf->Cell(30, 5, $line['account_code'], 1, 0, 'C');
            $pdf->Cell(50, 5, $line['account_name'], 1, 0, 'R');
            $pdf->Cell(30, 5, number_format($line['budgeted_amount'], 2), 1, 0, 'R');
            $pdf->Cell(30, 5, number_format($line['actual_amount'], 2), 1, 0, 'R');
            $pdf->Cell(25, 5, number_format($line['variance'], 2), 1, 0, 'R');
            $pdf->Cell(25, 5, $line['variance_percentage'] . '%', 1, 1, 'C');
        }

        $pdf->Output('budget_' . $budget_data['budget_name'] . '_' . $budget_data['budget_year'] . '.pdf', 'D');
    }

    private function exportToCsv($budget_data) {
        $filename = 'budget_' . $budget_data['budget_name'] . '_' . $budget_data['budget_year'] . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // الرأس
        fputcsv($output, [$this->config->get('config_name')]);
        fputcsv($output, ['الموازنة التقديرية: ' . $budget_data['budget_name']]);
        fputcsv($output, ['السنة المالية: ' . $budget_data['budget_year']]);
        fputcsv($output, []);

        // رؤوس الأعمدة
        fputcsv($output, ['رمز الحساب', 'اسم الحساب', 'المبلغ المخطط', 'المبلغ الفعلي', 'الانحراف', 'نسبة الانحراف']);

        // البيانات
        foreach ($budget_data['lines'] as $line) {
            fputcsv($output, [
                $line['account_code'],
                $line['account_name'],
                $line['budgeted_amount'],
                $line['actual_amount'],
                $line['variance'],
                $line['variance_percentage'] . '%'
            ]);
        }

        fclose($output);
    }
}