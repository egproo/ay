<?php
class ControllerPurchaseSettings extends Controller {
    private $error = array();

    /**
     * عرض صفحة إعدادات المشتريات
     */
    public function index() {
        $this->load->language('purchase/settings');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('purchase', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true));
        }

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

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        // إعدادات عامة
        if (isset($this->request->post['purchase_auto_approve_limit'])) {
            $data['purchase_auto_approve_limit'] = $this->request->post['purchase_auto_approve_limit'];
        } else {
            $data['purchase_auto_approve_limit'] = $this->config->get('purchase_auto_approve_limit');
        }

        if (isset($this->request->post['purchase_require_approval'])) {
            $data['purchase_require_approval'] = $this->request->post['purchase_require_approval'];
        } else {
            $data['purchase_require_approval'] = $this->config->get('purchase_require_approval');
        }

        if (isset($this->request->post['purchase_default_payment_terms'])) {
            $data['purchase_default_payment_terms'] = $this->request->post['purchase_default_payment_terms'];
        } else {
            $data['purchase_default_payment_terms'] = $this->config->get('purchase_default_payment_terms');
        }

        if (isset($this->request->post['purchase_default_currency'])) {
            $data['purchase_default_currency'] = $this->request->post['purchase_default_currency'];
        } else {
            $data['purchase_default_currency'] = $this->config->get('purchase_default_currency');
        }

        // إعدادات الترقيم
        if (isset($this->request->post['purchase_order_prefix'])) {
            $data['purchase_order_prefix'] = $this->request->post['purchase_order_prefix'];
        } else {
            $data['purchase_order_prefix'] = $this->config->get('purchase_order_prefix');
        }

        if (isset($this->request->post['purchase_order_start_number'])) {
            $data['purchase_order_start_number'] = $this->request->post['purchase_order_start_number'];
        } else {
            $data['purchase_order_start_number'] = $this->config->get('purchase_order_start_number');
        }

        if (isset($this->request->post['purchase_requisition_prefix'])) {
            $data['purchase_requisition_prefix'] = $this->request->post['purchase_requisition_prefix'];
        } else {
            $data['purchase_requisition_prefix'] = $this->config->get('purchase_requisition_prefix');
        }

        if (isset($this->request->post['purchase_quotation_prefix'])) {
            $data['purchase_quotation_prefix'] = $this->request->post['purchase_quotation_prefix'];
        } else {
            $data['purchase_quotation_prefix'] = $this->config->get('purchase_quotation_prefix');
        }

        // إعدادات الإشعارات
        if (isset($this->request->post['purchase_email_notifications'])) {
            $data['purchase_email_notifications'] = $this->request->post['purchase_email_notifications'];
        } else {
            $data['purchase_email_notifications'] = $this->config->get('purchase_email_notifications');
        }

        if (isset($this->request->post['purchase_notification_emails'])) {
            $data['purchase_notification_emails'] = $this->request->post['purchase_notification_emails'];
        } else {
            $data['purchase_notification_emails'] = $this->config->get('purchase_notification_emails');
        }

        if (isset($this->request->post['purchase_low_stock_notification'])) {
            $data['purchase_low_stock_notification'] = $this->request->post['purchase_low_stock_notification'];
        } else {
            $data['purchase_low_stock_notification'] = $this->config->get('purchase_low_stock_notification');
        }

        // إعدادات المخزون
        if (isset($this->request->post['purchase_auto_update_inventory'])) {
            $data['purchase_auto_update_inventory'] = $this->request->post['purchase_auto_update_inventory'];
        } else {
            $data['purchase_auto_update_inventory'] = $this->config->get('purchase_auto_update_inventory');
        }

        if (isset($this->request->post['purchase_inventory_method'])) {
            $data['purchase_inventory_method'] = $this->request->post['purchase_inventory_method'];
        } else {
            $data['purchase_inventory_method'] = $this->config->get('purchase_inventory_method');
        }

        if (isset($this->request->post['purchase_reorder_level_days'])) {
            $data['purchase_reorder_level_days'] = $this->request->post['purchase_reorder_level_days'];
        } else {
            $data['purchase_reorder_level_days'] = $this->config->get('purchase_reorder_level_days');
        }

        // إعدادات التكامل
        if (isset($this->request->post['purchase_accounting_integration'])) {
            $data['purchase_accounting_integration'] = $this->request->post['purchase_accounting_integration'];
        } else {
            $data['purchase_accounting_integration'] = $this->config->get('purchase_accounting_integration');
        }

        if (isset($this->request->post['purchase_expense_account'])) {
            $data['purchase_expense_account'] = $this->request->post['purchase_expense_account'];
        } else {
            $data['purchase_expense_account'] = $this->config->get('purchase_expense_account');
        }

        if (isset($this->request->post['purchase_payable_account'])) {
            $data['purchase_payable_account'] = $this->request->post['purchase_payable_account'];
        } else {
            $data['purchase_payable_account'] = $this->config->get('purchase_payable_account');
        }

        // إعدادات الموافقة
        if (isset($this->request->post['purchase_approval_workflow'])) {
            $data['purchase_approval_workflow'] = $this->request->post['purchase_approval_workflow'];
        } else {
            $data['purchase_approval_workflow'] = $this->config->get('purchase_approval_workflow');
        }

        if (isset($this->request->post['purchase_approval_levels'])) {
            $data['purchase_approval_levels'] = $this->request->post['purchase_approval_levels'];
        } else {
            $data['purchase_approval_levels'] = $this->config->get('purchase_approval_levels');
        }

        // إعدادات التقارير
        if (isset($this->request->post['purchase_default_report_period'])) {
            $data['purchase_default_report_period'] = $this->request->post['purchase_default_report_period'];
        } else {
            $data['purchase_default_report_period'] = $this->config->get('purchase_default_report_period');
        }

        if (isset($this->request->post['purchase_report_auto_email'])) {
            $data['purchase_report_auto_email'] = $this->request->post['purchase_report_auto_email'];
        } else {
            $data['purchase_report_auto_email'] = $this->config->get('purchase_report_auto_email');
        }

        // قوائم الخيارات
        $data['payment_terms'] = array(
            array('value' => 'net_30', 'text' => $this->language->get('text_net_30')),
            array('value' => 'net_60', 'text' => $this->language->get('text_net_60')),
            array('value' => 'net_90', 'text' => $this->language->get('text_net_90')),
            array('value' => 'cod', 'text' => $this->language->get('text_cod')),
            array('value' => 'prepaid', 'text' => $this->language->get('text_prepaid'))
        );

        $this->load->model('localisation/currency');
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        $data['inventory_methods'] = array(
            array('value' => 'fifo', 'text' => $this->language->get('text_fifo')),
            array('value' => 'lifo', 'text' => $this->language->get('text_lifo')),
            array('value' => 'weighted_average', 'text' => $this->language->get('text_weighted_average'))
        );

        $data['approval_workflows'] = array(
            array('value' => 'none', 'text' => $this->language->get('text_no_approval')),
            array('value' => 'single', 'text' => $this->language->get('text_single_approval')),
            array('value' => 'multi', 'text' => $this->language->get('text_multi_approval'))
        );

        $data['report_periods'] = array(
            array('value' => 'daily', 'text' => $this->language->get('text_daily')),
            array('value' => 'weekly', 'text' => $this->language->get('text_weekly')),
            array('value' => 'monthly', 'text' => $this->language->get('text_monthly')),
            array('value' => 'quarterly', 'text' => $this->language->get('text_quarterly')),
            array('value' => 'yearly', 'text' => $this->language->get('text_yearly'))
        );

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/settings', $data));
    }

    /**
     * التحقق من صحة البيانات
     */
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!empty($this->request->post['purchase_auto_approve_limit']) && !is_numeric($this->request->post['purchase_auto_approve_limit'])) {
            $this->error['warning'] = $this->language->get('error_auto_approve_limit');
        }

        if (!empty($this->request->post['purchase_order_start_number']) && !is_numeric($this->request->post['purchase_order_start_number'])) {
            $this->error['warning'] = $this->language->get('error_start_number');
        }

        if (!empty($this->request->post['purchase_reorder_level_days']) && !is_numeric($this->request->post['purchase_reorder_level_days'])) {
            $this->error['warning'] = $this->language->get('error_reorder_level_days');
        }

        return !$this->error;
    }

    /**
     * إعادة تعيين الإعدادات للقيم الافتراضية
     */
    public function reset() {
        $this->load->language('purchase/settings');

        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $this->session->data['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('setting/setting');

            // القيم الافتراضية
            $default_settings = array(
                'purchase_auto_approve_limit' => '1000',
                'purchase_require_approval' => '1',
                'purchase_default_payment_terms' => 'net_30',
                'purchase_default_currency' => $this->config->get('config_currency'),
                'purchase_order_prefix' => 'PO',
                'purchase_order_start_number' => '1',
                'purchase_requisition_prefix' => 'PR',
                'purchase_quotation_prefix' => 'PQ',
                'purchase_email_notifications' => '1',
                'purchase_notification_emails' => $this->config->get('config_email'),
                'purchase_low_stock_notification' => '1',
                'purchase_auto_update_inventory' => '1',
                'purchase_inventory_method' => 'weighted_average',
                'purchase_reorder_level_days' => '30',
                'purchase_accounting_integration' => '1',
                'purchase_expense_account' => '',
                'purchase_payable_account' => '',
                'purchase_approval_workflow' => 'single',
                'purchase_approval_levels' => '2',
                'purchase_default_report_period' => 'monthly',
                'purchase_report_auto_email' => '0'
            );

            $this->model_setting_setting->editSetting('purchase', $default_settings);

            $this->session->data['success'] = $this->language->get('text_reset_success');
        }

        $this->response->redirect($this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true));
    }

    /**
     * تصدير الإعدادات
     */
    public function export() {
        $this->load->language('purchase/settings');

        if (!$this->user->hasPermission('access', 'purchase/settings')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->load->model('setting/setting');

        $settings = $this->model_setting_setting->getSetting('purchase');

        $filename = 'purchase_settings_' . date('Y-m-d_H-i-s') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * استيراد الإعدادات
     */
    public function import() {
        $this->load->language('purchase/settings');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->files['import_file']) && $this->request->files['import_file']['error'] == 0) {
                $file_content = file_get_contents($this->request->files['import_file']['tmp_name']);
                $settings = json_decode($file_content, true);

                if ($settings && is_array($settings)) {
                    $this->load->model('setting/setting');
                    $this->model_setting_setting->editSetting('purchase', $settings);
                    $json['success'] = $this->language->get('text_import_success');
                } else {
                    $json['error'] = $this->language->get('error_invalid_file');
                }
            } else {
                $json['error'] = $this->language->get('error_upload');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إعدادات الأمان
     */
    public function security() {
        $this->load->language('purchase/settings');
        $this->load->model('purchase/settings');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateSecurityForm()) {
            $this->model_purchase_settings->saveSecuritySettings($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('purchase/settings/security', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_security_settings'),
            'href' => $this->url->link('purchase/settings/security', 'user_token=' . $this->session->data['user_token'], true)
        );

        $security_settings = $this->model_purchase_settings->getSecuritySettings();
        foreach ($security_settings as $key => $value) {
            $data[$key] = $value;
        }

        $data['action'] = $this->url->link('purchase/settings/security', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/settings_security', $data));
    }

    /**
     * إعدادات الأداء
     */
    public function performance() {
        $this->load->language('purchase/settings');
        $this->load->model('purchase/settings');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validatePerformanceForm()) {
            $this->model_purchase_settings->savePerformanceSettings($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('purchase/settings/performance', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_performance_settings'),
            'href' => $this->url->link('purchase/settings/performance', 'user_token=' . $this->session->data['user_token'], true)
        );

        $performance_settings = $this->model_purchase_settings->getPerformanceSettings();
        foreach ($performance_settings as $key => $value) {
            $data[$key] = $value;
        }

        $data['action'] = $this->url->link('purchase/settings/performance', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/settings_performance', $data));
    }

    /**
     * إعدادات النسخ الاحتياطي
     */
    public function backup() {
        $this->load->language('purchase/settings');
        $this->load->model('purchase/settings');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateBackupForm()) {
            $this->model_purchase_settings->saveBackupSettings($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('purchase/settings/backup', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_backup_settings'),
            'href' => $this->url->link('purchase/settings/backup', 'user_token=' . $this->session->data['user_token'], true)
        );

        $backup_settings = $this->model_purchase_settings->getBackupSettings();
        foreach ($backup_settings as $key => $value) {
            $data[$key] = $value;
        }

        $data['backups'] = $this->model_purchase_settings->getBackupList();
        $data['action'] = $this->url->link('purchase/settings/backup', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/settings_backup', $data));
    }

    /**
     * إنشاء نسخة احتياطية
     */
    public function createBackup() {
        $this->load->language('purchase/settings');
        $this->load->model('purchase/settings');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $result = $this->model_purchase_settings->createBackup();
            if (isset($result['success'])) {
                $json['success'] = $result['success'];
                $json['filename'] = $result['filename'];
            } else {
                $json['error'] = $result['error'];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * استعادة نسخة احتياطية
     */
    public function restoreBackup() {
        $this->load->language('purchase/settings');
        $this->load->model('purchase/settings');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $filename = isset($this->request->post['filename']) ? $this->request->post['filename'] : '';
            if (!$filename) {
                $json['error'] = $this->language->get('error_filename_required');
            } else {
                $result = $this->model_purchase_settings->restoreBackup($filename);
                if (isset($result['success'])) {
                    $json['success'] = $result['success'];
                } else {
                    $json['error'] = $result['error'];
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * اختبار إعدادات البريد الإلكتروني
     */
    public function testEmail() {
        $this->load->language('purchase/settings');
        $this->load->model('purchase/settings');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $test_email = isset($this->request->post['test_email']) ? $this->request->post['test_email'] : '';
            if (!$test_email || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
                $json['error'] = $this->language->get('error_invalid_email');
            } else {
                $settings = array('test_email' => $test_email);
                $result = $this->model_purchase_settings->testEmailSettings($settings);
                if (isset($result['success'])) {
                    $json['success'] = $result['success'];
                } else {
                    $json['error'] = $result['error'];
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على إحصائيات النظام
     */
    public function getStatistics() {
        $this->load->language('purchase/settings');
        $this->load->model('purchase/settings');

        $json = array();

        if (!$this->user->hasPermission('access', 'purchase/settings')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $json['statistics'] = $this->model_purchase_settings->getSystemStatistics();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحسين قاعدة البيانات
     */
    public function optimizeDatabase() {
        $this->load->language('purchase/settings');
        $this->load->model('purchase/settings');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if ($this->model_purchase_settings->optimizeDatabase()) {
                $json['success'] = $this->language->get('text_database_optimized');
            } else {
                $json['error'] = $this->language->get('error_database_optimization');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * مسح التخزين المؤقت
     */
    public function clearCache() {
        $this->load->language('purchase/settings');
        $this->load->model('purchase/settings');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if ($this->model_purchase_settings->clearCache()) {
                $json['success'] = $this->language->get('text_cache_cleared');
            } else {
                $json['error'] = $this->language->get('error_cache_clear');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * التحقق من صحة نموذج الأمان
     */
    protected function validateSecurityForm() {
        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة نموذج الأداء
     */
    protected function validatePerformanceForm() {
        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['purchase_page_size']) && ((int)$this->request->post['purchase_page_size'] < 1 || (int)$this->request->post['purchase_page_size'] > 1000)) {
            $this->error['page_size'] = $this->language->get('error_page_size');
        }

        if (isset($this->request->post['purchase_query_timeout']) && ((int)$this->request->post['purchase_query_timeout'] < 1 || (int)$this->request->post['purchase_query_timeout'] > 300)) {
            $this->error['query_timeout'] = $this->language->get('error_query_timeout');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة نموذج النسخ الاحتياطي
     */
    protected function validateBackupForm() {
        if (!$this->user->hasPermission('modify', 'purchase/settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
