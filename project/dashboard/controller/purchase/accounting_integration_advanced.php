<?php
/**
 * نظام التكامل المحاسبي المتقدم للمشتريات
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ControllerPurchaseAccountingIntegrationAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('purchase/accounting_integration_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('purchase/accounting_integration_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/purchase/accounting_integration.css');
        $this->document->addScript('view/javascript/purchase/accounting_integration.js');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'purchase_accounting_integration',
            'record_id' => 0,
            'description' => 'عرض نظام التكامل المحاسبي المتقدم للمشتريات',
            'module' => 'purchase_accounting_integration'
        ]);

        $this->getList();
    }

    public function getIntegrationStatus() {
        $this->load->model('purchase/accounting_integration_advanced');

        $json = array();

        try {
            $status = $this->model_purchase_accounting_integration_advanced->getIntegrationStatus();
            
            $json['success'] = true;
            $json['status'] = $status;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function generateJournalEntries() {
        $this->load->model('purchase/accounting_integration_advanced');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'purchase/accounting_integration_advanced')) {
                    throw new Exception('ليس لديك صلاحية لإنشاء القيود المحاسبية');
                }

                $filter_data = $this->prepareFilterData();
                
                $result = $this->model_purchase_accounting_integration_advanced->generateJournalEntries($filter_data);
                
                if ($result['success']) {
                    $json['success'] = true;
                    $json['message'] = 'تم إنشاء القيود المحاسبية بنجاح';
                    $json['details'] = $result['details'];
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'generate_journal_entries',
                        'table_name' => 'purchase_accounting_integration',
                        'record_id' => 0,
                        'description' => 'إنشاء قيود محاسبية للمشتريات - معالجة ' . $result['details']['processed_records'] . ' سجل',
                        'module' => 'purchase_accounting_integration'
                    ]);
                } else {
                    $json['error'] = 'فشل في إنشاء القيود المحاسبية';
                    $json['details'] = $result['details'];
                }
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function syncInventoryAccounting() {
        $this->load->model('purchase/accounting_integration_advanced');
        $this->load->model('accounts/audit_trail');

        $json = array();

        try {
            // التحقق من الصلاحيات
            if (!$this->user->hasPermission('modify', 'purchase/accounting_integration_advanced')) {
                throw new Exception('ليس لديك صلاحية لمزامنة البيانات');
            }

            $result = $this->model_purchase_accounting_integration_advanced->syncInventoryAccounting();
            
            if ($result['success']) {
                $json['success'] = true;
                $json['message'] = 'تم مزامنة المخزون والمحاسبة بنجاح';
                $json['details'] = $result['details'];
                
                // تسجيل في سجل المراجعة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'sync_inventory_accounting',
                    'table_name' => 'purchase_accounting_integration',
                    'record_id' => 0,
                    'description' => 'مزامنة المخزون والمحاسبة للمشتريات - معالجة ' . $result['details']['synced_items'] . ' صنف',
                    'module' => 'purchase_accounting_integration'
                ]);
            } else {
                $json['error'] = 'فشل في مزامنة المخزون والمحاسبة';
                $json['details'] = $result['details'];
            }
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function updateWAC() {
        $this->load->model('purchase/accounting_integration_advanced');
        $this->load->model('accounts/audit_trail');

        $json = array();

        try {
            // التحقق من الصلاحيات
            if (!$this->user->hasPermission('modify', 'purchase/accounting_integration_advanced')) {
                throw new Exception('ليس لديك صلاحية لتحديث التكلفة المرجحة');
            }

            $result = $this->model_purchase_accounting_integration_advanced->updateWeightedAverageCost();
            
            if ($result['success']) {
                $json['success'] = true;
                $json['message'] = 'تم تحديث التكلفة المرجحة بنجاح';
                $json['details'] = $result['details'];
                
                // تسجيل في سجل المراجعة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'update_wac',
                    'table_name' => 'purchase_accounting_integration',
                    'record_id' => 0,
                    'description' => 'تحديث التكلفة المرجحة - معالجة ' . $result['details']['updated_products'] . ' منتج',
                    'module' => 'purchase_accounting_integration'
                ]);
            } else {
                $json['error'] = 'فشل في تحديث التكلفة المرجحة';
                $json['details'] = $result['details'];
            }
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function validateIntegration() {
        $this->load->model('purchase/accounting_integration_advanced');

        $json = array();

        try {
            $validation = $this->model_purchase_accounting_integration_advanced->validateIntegration();
            
            $json['success'] = true;
            $json['validation'] = $validation;
            
            if (!$validation['is_valid']) {
                $json['warning'] = 'توجد مشاكل في التكامل المحاسبي للمشتريات';
            }
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getIntegrationReport() {
        $this->load->model('purchase/accounting_integration_advanced');

        $json = array();

        try {
            $filter_data = array(
                'date_from' => $this->request->get['date_from'] ?? date('Y-m-01'),
                'date_to' => $this->request->get['date_to'] ?? date('Y-m-d'),
                'supplier_id' => $this->request->get['supplier_id'] ?? null,
                'product_id' => $this->request->get['product_id'] ?? null
            );

            $report = $this->model_purchase_accounting_integration_advanced->generateIntegrationReport($filter_data);
            
            $json['success'] = true;
            $json['report'] = $report;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getAccountingSettings() {
        $this->load->model('purchase/accounting_integration_advanced');

        $json = array();

        try {
            $settings = $this->model_purchase_accounting_integration_advanced->getAccountingSettings();
            
            $json['success'] = true;
            $json['settings'] = $settings;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function updateAccountingSettings() {
        $this->load->model('purchase/accounting_integration_advanced');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات العليا
                if (!$this->user->hasPermission('modify', 'purchase/accounting_settings')) {
                    throw new Exception('ليس لديك صلاحية لتعديل إعدادات التكامل المحاسبي');
                }

                $settings = $this->request->post;
                
                $result = $this->model_purchase_accounting_integration_advanced->updateAccountingSettings($settings);
                
                if ($result) {
                    $json['success'] = true;
                    $json['message'] = 'تم تحديث إعدادات التكامل المحاسبي بنجاح';
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'update_accounting_settings',
                        'table_name' => 'purchase_accounting_settings',
                        'record_id' => 0,
                        'description' => 'تحديث إعدادات التكامل المحاسبي للمشتريات',
                        'module' => 'purchase_accounting_integration'
                    ]);
                } else {
                    $json['error'] = 'فشل في تحديث إعدادات التكامل المحاسبي';
                }
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function prepareFilterData() {
        return array(
            'date_from' => $this->request->post['date_from'] ?? date('Y-m-01'),
            'date_to' => $this->request->post['date_to'] ?? date('Y-m-d'),
            'supplier_id' => $this->request->post['supplier_id'] ?? null,
            'product_id' => $this->request->post['product_id'] ?? null,
            'transaction_type' => $this->request->post['transaction_type'] ?? 'all',
            'include_pending' => $this->request->post['include_pending'] ?? 0,
            'auto_post' => $this->request->post['auto_post'] ?? 1
        );
    }

    protected function getList() {
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/accounting_integration_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للـ AJAX
        $data['status_url'] = $this->url->link('purchase/accounting_integration_advanced/getIntegrationStatus', 'user_token=' . $this->session->data['user_token'], true);
        $data['generate_entries_url'] = $this->url->link('purchase/accounting_integration_advanced/generateJournalEntries', 'user_token=' . $this->session->data['user_token'], true);
        $data['sync_inventory_url'] = $this->url->link('purchase/accounting_integration_advanced/syncInventoryAccounting', 'user_token=' . $this->session->data['user_token'], true);
        $data['update_wac_url'] = $this->url->link('purchase/accounting_integration_advanced/updateWAC', 'user_token=' . $this->session->data['user_token'], true);
        $data['validate_url'] = $this->url->link('purchase/accounting_integration_advanced/validateIntegration', 'user_token=' . $this->session->data['user_token'], true);
        $data['report_url'] = $this->url->link('purchase/accounting_integration_advanced/getIntegrationReport', 'user_token=' . $this->session->data['user_token'], true);
        $data['settings_url'] = $this->url->link('purchase/accounting_integration_advanced/getAccountingSettings', 'user_token=' . $this->session->data['user_token'], true);
        $data['update_settings_url'] = $this->url->link('purchase/accounting_integration_advanced/updateAccountingSettings', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        // تحميل قوائم البيانات
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        $this->load->model('catalog/product');
        $data['products'] = $this->model_catalog_product->getProducts();

        // الصلاحيات
        $data['can_generate_entries'] = $this->user->hasPermission('modify', 'purchase/accounting_integration_advanced');
        $data['can_sync'] = $this->user->hasPermission('modify', 'purchase/accounting_integration_advanced');
        $data['can_update_settings'] = $this->user->hasPermission('modify', 'purchase/accounting_settings');

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

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/accounting_integration_advanced', $data));
    }
}
