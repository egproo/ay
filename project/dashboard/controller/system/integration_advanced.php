<?php
/**
 * نظام الترابط المتقدم بين الوحدات
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 * ضمان الترابط الكامل والدقة المحاسبية
 */
class ControllerSystemIntegrationAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('system/integration_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('system/integration_advanced');
        $this->load->model('accounts/audit_trail');

        // تسجيل الوصول
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'system_integration',
            'record_id' => 0,
            'description' => 'عرض شاشة الترابط المتقدم بين الوحدات',
            'module' => 'system_integration'
        ]);

        $this->getList();
    }

    /**
     * فحص تكامل النظام الشامل
     */
    public function checkSystemIntegrity() {
        $this->load->model('system/integration_advanced');

        $json = array();

        try {
            $integrity_report = $this->model_system_integration_advanced->performIntegrityCheck();

            $json['success'] = true;
            $json['report'] = $integrity_report;

            // تسجيل فحص التكامل
            $this->model_accounts_audit_trail->logAction([
                'action_type' => 'integrity_check',
                'table_name' => 'system',
                'record_id' => 0,
                'description' => 'فحص تكامل النظام الشامل',
                'module' => 'system_integration'
            ]);

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * مزامنة البيانات بين الوحدات
     */
    public function synchronizeModules() {
        $this->load->model('system/integration_advanced');

        $json = array();

        if (isset($this->request->post['modules'])) {
            $modules = $this->request->post['modules'];

            try {
                $sync_result = $this->model_system_integration_advanced->synchronizeModules($modules);

                $json['success'] = true;
                $json['result'] = $sync_result;

                // تسجيل المزامنة
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'module_sync',
                    'table_name' => 'system',
                    'record_id' => 0,
                    'description' => 'مزامنة الوحدات: ' . implode(', ', $modules),
                    'module' => 'system_integration'
                ]);

            } catch (Exception $e) {
                $json['error'] = 'خطأ في المزامنة: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'يجب تحديد الوحدات للمزامنة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * التحقق من الترابط المحاسبي
     */
    public function checkAccountingIntegration() {
        $this->load->model('system/integration_advanced');

        $json = array();

        try {
            $accounting_check = $this->model_system_integration_advanced->checkAccountingIntegration();

            $json['success'] = true;
            $json['check'] = $accounting_check;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إصلاح مشاكل الترابط
     */
    public function fixIntegrationIssues() {
        $this->load->model('system/integration_advanced');

        $json = array();

        if (isset($this->request->post['issues'])) {
            $issues = $this->request->post['issues'];

            try {
                $fix_result = $this->model_system_integration_advanced->fixIntegrationIssues($issues);

                $json['success'] = true;
                $json['result'] = $fix_result;

                // تسجيل الإصلاح
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'integration_fix',
                    'table_name' => 'system',
                    'record_id' => 0,
                    'description' => 'إصلاح مشاكل الترابط: ' . count($issues) . ' مشكلة',
                    'module' => 'system_integration',
                    'severity' => 'high'
                ]);

            } catch (Exception $e) {
                $json['error'] = 'خطأ في الإصلاح: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'يجب تحديد المشاكل للإصلاح';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تقرير حالة الترابط
     */
    public function getIntegrationStatus() {
        $this->load->model('system/integration_advanced');

        $json = array();

        try {
            $status_report = $this->model_system_integration_advanced->getIntegrationStatus();

            $json['success'] = true;
            $json['status'] = $status_report;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحديث إعدادات الترابط
     */
    public function updateIntegrationSettings() {
        $this->load->model('system/integration_advanced');

        $json = array();

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateSettings()) {
            try {
                $settings_data = $this->prepareSettingsData();

                $result = $this->model_system_integration_advanced->updateIntegrationSettings($settings_data);

                if ($result) {
                    $json['success'] = 'تم تحديث إعدادات الترابط بنجاح';

                    // تسجيل التحديث
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'settings_update',
                        'table_name' => 'integration_settings',
                        'record_id' => 0,
                        'description' => 'تحديث إعدادات الترابط بين الوحدات',
                        'module' => 'system_integration'
                    ]);
                } else {
                    $json['error'] = 'فشل في تحديث الإعدادات';
                }

            } catch (Exception $e) {
                $json['error'] = 'خطأ في التحديث: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'بيانات غير صحيحة';
            $json['errors'] = $this->error;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * اختبار الاتصال بين الوحدات
     */
    public function testModuleConnections() {
        $this->load->model('system/integration_advanced');

        $json = array();

        try {
            $connection_tests = $this->model_system_integration_advanced->testModuleConnections();

            $json['success'] = true;
            $json['tests'] = $connection_tests;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تصدير تقرير الترابط
     */
    public function exportIntegrationReport() {
        $this->load->model('system/integration_advanced');

        $format = $this->request->get['format'] ?? 'excel';

        try {
            $report_data = $this->model_system_integration_advanced->getComprehensiveIntegrationReport();

            // تسجيل التصدير
            $this->model_accounts_audit_trail->logAction([
                'action_type' => 'export_integration_report',
                'table_name' => 'system',
                'record_id' => 0,
                'description' => "تصدير تقرير الترابط بصيغة {$format}",
                'module' => 'system_integration'
            ]);

            switch ($format) {
                case 'excel':
                    $this->exportToExcel($report_data);
                    break;
                case 'pdf':
                    $this->exportToPdf($report_data);
                    break;
                case 'csv':
                    $this->exportToCsv($report_data);
                    break;
                default:
                    $this->exportToExcel($report_data);
            }

        } catch (Exception $e) {
            $this->session->data['error'] = 'خطأ في تصدير التقرير: ' . $e->getMessage();
            $this->response->redirect($this->url->link('system/integration_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * التحقق من صحة الإعدادات
     */
    private function validateSettings() {
        if (empty($this->request->post['auto_sync_enabled'])) {
            $this->error['auto_sync'] = 'يجب تحديد حالة المزامنة التلقائية';
        }

        if (empty($this->request->post['integrity_check_frequency'])) {
            $this->error['frequency'] = 'يجب تحديد تكرار فحص التكامل';
        }

        return !$this->error;
    }

    /**
     * تحضير بيانات الإعدادات
     */
    private function prepareSettingsData() {
        return array(
            'auto_sync_enabled' => $this->request->post['auto_sync_enabled'],
            'sync_frequency' => $this->request->post['sync_frequency'] ?? 'hourly',
            'integrity_check_frequency' => $this->request->post['integrity_check_frequency'],
            'auto_fix_enabled' => $this->request->post['auto_fix_enabled'] ?? 0,
            'notification_enabled' => $this->request->post['notification_enabled'] ?? 1,
            'log_level' => $this->request->post['log_level'] ?? 'info',
            'backup_before_fix' => $this->request->post['backup_before_fix'] ?? 1
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
            'href' => $this->url->link('system/integration_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للـ AJAX
        $data['check_integrity_url'] = $this->url->link('system/integration_advanced/checkSystemIntegrity', 'user_token=' . $this->session->data['user_token'], true);
        $data['sync_modules_url'] = $this->url->link('system/integration_advanced/synchronizeModules', 'user_token=' . $this->session->data['user_token'], true);
        $data['check_accounting_url'] = $this->url->link('system/integration_advanced/checkAccountingIntegration', 'user_token=' . $this->session->data['user_token'], true);
        $data['fix_issues_url'] = $this->url->link('system/integration_advanced/fixIntegrationIssues', 'user_token=' . $this->session->data['user_token'], true);
        $data['status_url'] = $this->url->link('system/integration_advanced/getIntegrationStatus', 'user_token=' . $this->session->data['user_token'], true);
        $data['test_connections_url'] = $this->url->link('system/integration_advanced/testModuleConnections', 'user_token=' . $this->session->data['user_token'], true);
        $data['update_settings_url'] = $this->url->link('system/integration_advanced/updateIntegrationSettings', 'user_token=' . $this->session->data['user_token'], true);

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

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('system/integration_advanced_list', $data));
    }

    /**
     * تصدير إلى Excel
     */
    private function exportToExcel($data) {
        // تنفيذ تصدير Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="integration_report_' . date('Y-m-d') . '.xlsx"');
        // كود تصدير Excel هنا
    }

    /**
     * تصدير إلى PDF
     */
    private function exportToPdf($data) {
        // تنفيذ تصدير PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="integration_report_' . date('Y-m-d') . '.pdf"');
        // كود تصدير PDF هنا
    }

    /**
     * تصدير إلى CSV
     */
    private function exportToCsv($data) {
        // تنفيذ تصدير CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="integration_report_' . date('Y-m-d') . '.csv"');
        // كود تصدير CSV هنا
    }
}
