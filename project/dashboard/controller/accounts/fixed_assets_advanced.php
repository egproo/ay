<?php
/**
 * تحكم إدارة الأصول الثابتة المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerAccountsFixedAssetsAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/fixed_assets_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/fixed_assets_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/accounts/fixed_assets.css');
        $this->document->addScript('view/javascript/accounts/fixed_assets.js');
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
            'table_name' => 'fixed_assets',
            'record_id' => 0,
            'description' => 'عرض شاشة إدارة الأصول الثابتة',
            'module' => 'fixed_assets'
        ]);

        $this->getList();
    }

    public function add() {
        $this->load->language('accounts/fixed_assets_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/fixed_assets_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $asset_id = $this->model_accounts_fixed_assets_advanced->addAsset($this->request->post);

                // تسجيل إضافة الأصل
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'add_asset',
                    'table_name' => 'fixed_assets',
                    'record_id' => $asset_id,
                    'description' => 'إضافة أصل ثابت جديد: ' . $this->request->post['asset_name'],
                    'module' => 'fixed_assets'
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

                $this->response->redirect($this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إضافة الأصل الثابت: ' . $e->getMessage();
            }
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('accounts/fixed_assets_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/fixed_assets_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $this->model_accounts_fixed_assets_advanced->editAsset($this->request->get['asset_id'], $this->request->post);

                // تسجيل تعديل الأصل
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'edit_asset',
                    'table_name' => 'fixed_assets',
                    'record_id' => $this->request->get['asset_id'],
                    'description' => 'تعديل الأصل الثابت: ' . $this->request->post['asset_name'],
                    'module' => 'fixed_assets'
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

                $this->response->redirect($this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في تعديل الأصل الثابت: ' . $e->getMessage();
            }
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('accounts/fixed_assets_advanced');
        $this->load->model('accounts/fixed_assets_advanced');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $asset_id) {
                $asset_info = $this->model_accounts_fixed_assets_advanced->getAsset($asset_id);

                $this->model_accounts_fixed_assets_advanced->deleteAsset($asset_id);

                // تسجيل حذف الأصل
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'delete_asset',
                    'table_name' => 'fixed_assets',
                    'record_id' => $asset_id,
                    'description' => 'حذف الأصل الثابت: ' . $asset_info['asset_name'],
                    'module' => 'fixed_assets'
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

            $this->response->redirect($this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function calculateDepreciation() {
        $this->load->language('accounts/fixed_assets_advanced');
        $this->load->model('accounts/fixed_assets_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateDepreciation()) {
            try {
                $depreciation_data = $this->prepareDepreciationData();

                $result = $this->model_accounts_fixed_assets_advanced->calculateDepreciation($depreciation_data);

                // تسجيل حساب الاستهلاك
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'calculate_depreciation',
                    'table_name' => 'fixed_assets_depreciation',
                    'record_id' => 0,
                    'description' => 'حساب استهلاك الأصول للفترة: ' . $depreciation_data['period_start'] . ' إلى ' . $depreciation_data['period_end'],
                    'module' => 'fixed_assets'
                ]);

                $this->session->data['depreciation_result'] = $result;
                $this->session->data['success'] = 'تم حساب الاستهلاك بنجاح';

                $this->response->redirect($this->url->link('accounts/fixed_assets_advanced/viewDepreciation', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في حساب الاستهلاك: ' . $e->getMessage();
            }
        }

        $this->getDepreciationForm();
    }

    public function postDepreciation() {
        $this->load->language('accounts/fixed_assets_advanced');
        $this->load->model('accounts/fixed_assets_advanced');

        if (isset($this->session->data['depreciation_result']) && $this->validatePostDepreciation()) {
            try {
                $depreciation_result = $this->session->data['depreciation_result'];

                $journal_entry_id = $this->model_accounts_fixed_assets_advanced->postDepreciation($depreciation_result);

                // تسجيل ترحيل الاستهلاك
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'post_depreciation',
                    'table_name' => 'journal_entry',
                    'record_id' => $journal_entry_id,
                    'description' => 'ترحيل قيد استهلاك الأصول الثابتة',
                    'module' => 'fixed_assets'
                ]);

                unset($this->session->data['depreciation_result']);
                $this->session->data['success'] = 'تم ترحيل قيد الاستهلاك بنجاح';

                $this->response->redirect($this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في ترحيل قيد الاستهلاك: ' . $e->getMessage();
            }
        }

        $this->viewDepreciation();
    }

    public function dispose() {
        $this->load->language('accounts/fixed_assets_advanced');
        $this->load->model('accounts/fixed_assets_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateDisposal()) {
            try {
                $disposal_data = $this->prepareDisposalData();

                $result = $this->model_accounts_fixed_assets_advanced->disposeAsset($disposal_data);

                // تسجيل التخلص من الأصل
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'dispose_asset',
                    'table_name' => 'fixed_assets_disposal',
                    'record_id' => $result['disposal_id'],
                    'description' => 'التخلص من الأصل الثابت: ' . $disposal_data['asset_name'],
                    'module' => 'fixed_assets'
                ]);

                $this->session->data['success'] = 'تم التخلص من الأصل بنجاح';

                $this->response->redirect($this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في التخلص من الأصل: ' . $e->getMessage();
            }
        }

        $this->getDisposalForm();
    }

    public function getAssetAnalysis() {
        $this->load->model('accounts/fixed_assets_advanced');

        $json = array();

        if (isset($this->request->get['asset_id'])) {
            try {
                $asset_id = $this->request->get['asset_id'];

                $analysis = $this->model_accounts_fixed_assets_advanced->analyzeAsset($asset_id);

                $json['success'] = true;
                $json['analysis'] = $analysis;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الأصل مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getDepreciationSchedule() {
        $this->load->model('accounts/fixed_assets_advanced');

        $json = array();

        if (isset($this->request->get['asset_id'])) {
            try {
                $asset_id = $this->request->get['asset_id'];

                $schedule = $this->model_accounts_fixed_assets_advanced->generateDepreciationSchedule($asset_id);

                $json['success'] = true;
                $json['schedule'] = $schedule;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الأصل مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getAssetValuation() {
        $this->load->model('accounts/fixed_assets_advanced');

        $json = array();

        if (isset($this->request->get['asset_id'])) {
            try {
                $asset_id = $this->request->get['asset_id'];

                $valuation = $this->model_accounts_fixed_assets_advanced->calculateAssetValuation($asset_id);

                $json['success'] = true;
                $json['valuation'] = $valuation;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الأصل مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getMaintenanceHistory() {
        $this->load->model('accounts/fixed_assets_advanced');

        $json = array();

        if (isset($this->request->get['asset_id'])) {
            try {
                $asset_id = $this->request->get['asset_id'];

                $maintenance = $this->model_accounts_fixed_assets_advanced->getMaintenanceHistory($asset_id);

                $json['success'] = true;
                $json['maintenance'] = $maintenance;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف الأصل مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function export() {
        $this->load->language('accounts/fixed_assets_advanced');
        $this->load->model('accounts/fixed_assets_advanced');

        $format = $this->request->get['format'] ?? 'excel';
        $filter_data = $this->prepareFilterData();

        $assets_data = $this->model_accounts_fixed_assets_advanced->getAssetsForExport($filter_data);

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_assets',
            'table_name' => 'fixed_assets',
            'record_id' => 0,
            'description' => "تصدير الأصول الثابتة بصيغة {$format}",
            'module' => 'fixed_assets'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($assets_data);
                break;
            case 'pdf':
                $this->exportToPdf($assets_data);
                break;
            case 'csv':
                $this->exportToCsv($assets_data);
                break;
            default:
                $this->exportToExcel($assets_data);
        }
    }

    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'asset_name';
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
            'href' => $this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('accounts/fixed_assets_advanced/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('accounts/fixed_assets_advanced/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['calculate_depreciation'] = $this->url->link('accounts/fixed_assets_advanced/calculateDepreciation', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['analysis_url'] = $this->url->link('accounts/fixed_assets_advanced/getAssetAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['schedule_url'] = $this->url->link('accounts/fixed_assets_advanced/getDepreciationSchedule', 'user_token=' . $this->session->data['user_token'], true);
        $data['valuation_url'] = $this->url->link('accounts/fixed_assets_advanced/getAssetValuation', 'user_token=' . $this->session->data['user_token'], true);
        $data['maintenance_url'] = $this->url->link('accounts/fixed_assets_advanced/getMaintenanceHistory', 'user_token=' . $this->session->data['user_token'], true);

        $data['assets'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $asset_total = $this->model_accounts_fixed_assets_advanced->getTotalAssets();

        $results = $this->model_accounts_fixed_assets_advanced->getAssets($filter_data);

        foreach ($results as $result) {
            $data['assets'][] = array(
                'asset_id'          => $result['asset_id'],
                'asset_code'        => $result['asset_code'],
                'asset_name'        => $result['asset_name'],
                'category_name'     => $result['category_name'],
                'purchase_date'     => date($this->language->get('date_format_short'), strtotime($result['purchase_date'])),
                'purchase_cost'     => $this->currency->format($result['purchase_cost'], $this->config->get('config_currency')),
                'current_value'     => $this->currency->format($result['current_value'], $this->config->get('config_currency')),
                'status'            => $result['status'],
                'location'          => $result['location'],
                'edit'              => $this->url->link('accounts/fixed_assets_advanced/edit', 'user_token=' . $this->session->data['user_token'] . '&asset_id=' . $result['asset_id'] . $url, true),
                'dispose'           => $this->url->link('accounts/fixed_assets_advanced/dispose', 'user_token=' . $this->session->data['user_token'] . '&asset_id=' . $result['asset_id'], true)
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

        $data['sort_asset_name'] = $this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=asset_name' . $url, true);
        $data['sort_category'] = $this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=category_name' . $url, true);
        $data['sort_purchase_date'] = $this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=purchase_date' . $url, true);
        $data['sort_status'] = $this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $asset_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('accounts/fixed_assets_advanced', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($asset_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($asset_total - $this->config->get('config_limit_admin'))) ? $asset_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $asset_total, ceil($asset_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/fixed_assets_advanced_list', $data));
    }