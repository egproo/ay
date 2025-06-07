<?php
/**
 * AYM ERP - Purchase Approval Settings Controller
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerPurchaseApprovalSettings extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('purchase/approval_settings');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('purchase/approval_settings');

            $this->model_purchase_approval_settings->editSettings($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('purchase/approval_settings', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/approval_settings', 'user_token=' . $this->session->data['user_token'], true)
        );

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

        $data['action'] = $this->url->link('purchase/approval_settings', 'user_token=' . $this->session->data['user_token'], true);

        $this->load->model('purchase/approval_settings');

        // Get current settings
        $settings = $this->model_purchase_approval_settings->getSettings();

        // General approval settings
        if (isset($this->request->post['approval_enabled'])) {
            $data['approval_enabled'] = $this->request->post['approval_enabled'];
        } else {
            $data['approval_enabled'] = $settings['approval_enabled'] ?? '1';
        }

        if (isset($this->request->post['auto_approval_enabled'])) {
            $data['auto_approval_enabled'] = $this->request->post['auto_approval_enabled'];
        } else {
            $data['auto_approval_enabled'] = $settings['auto_approval_enabled'] ?? '0';
        }

        if (isset($this->request->post['approval_timeout_days'])) {
            $data['approval_timeout_days'] = $this->request->post['approval_timeout_days'];
        } else {
            $data['approval_timeout_days'] = $settings['approval_timeout_days'] ?? '7';
        }

        if (isset($this->request->post['escalation_enabled'])) {
            $data['escalation_enabled'] = $this->request->post['escalation_enabled'];
        } else {
            $data['escalation_enabled'] = $settings['escalation_enabled'] ?? '1';
        }

        if (isset($this->request->post['escalation_days'])) {
            $data['escalation_days'] = $this->request->post['escalation_days'];
        } else {
            $data['escalation_days'] = $settings['escalation_days'] ?? '3';
        }

        // Amount-based approval thresholds
        if (isset($this->request->post['amount_thresholds'])) {
            $data['amount_thresholds'] = $this->request->post['amount_thresholds'];
        } else {
            $data['amount_thresholds'] = $this->model_purchase_approval_settings->getAmountThresholds();
        }

        // Department-based approval rules
        if (isset($this->request->post['department_rules'])) {
            $data['department_rules'] = $this->request->post['department_rules'];
        } else {
            $data['department_rules'] = $this->model_purchase_approval_settings->getDepartmentRules();
        }

        // Category-based approval rules
        if (isset($this->request->post['category_rules'])) {
            $data['category_rules'] = $this->request->post['category_rules'];
        } else {
            $data['category_rules'] = $this->model_purchase_approval_settings->getCategoryRules();
        }

        // Approval workflow settings
        if (isset($this->request->post['workflow_type'])) {
            $data['workflow_type'] = $this->request->post['workflow_type'];
        } else {
            $data['workflow_type'] = $settings['workflow_type'] ?? 'sequential';
        }

        if (isset($this->request->post['parallel_approval_percentage'])) {
            $data['parallel_approval_percentage'] = $this->request->post['parallel_approval_percentage'];
        } else {
            $data['parallel_approval_percentage'] = $settings['parallel_approval_percentage'] ?? '100';
        }

        // Emergency approval settings
        if (isset($this->request->post['emergency_approval_enabled'])) {
            $data['emergency_approval_enabled'] = $this->request->post['emergency_approval_enabled'];
        } else {
            $data['emergency_approval_enabled'] = $settings['emergency_approval_enabled'] ?? '1';
        }

        if (isset($this->request->post['emergency_approval_roles'])) {
            $data['emergency_approval_roles'] = $this->request->post['emergency_approval_roles'];
        } else {
            $data['emergency_approval_roles'] = $settings['emergency_approval_roles'] ?? array();
        }

        // Notification settings
        if (isset($this->request->post['notification_enabled'])) {
            $data['notification_enabled'] = $this->request->post['notification_enabled'];
        } else {
            $data['notification_enabled'] = $settings['notification_enabled'] ?? '1';
        }

        if (isset($this->request->post['email_notifications'])) {
            $data['email_notifications'] = $this->request->post['email_notifications'];
        } else {
            $data['email_notifications'] = $settings['email_notifications'] ?? '1';
        }

        if (isset($this->request->post['sms_notifications'])) {
            $data['sms_notifications'] = $this->request->post['sms_notifications'];
        } else {
            $data['sms_notifications'] = $settings['sms_notifications'] ?? '0';
        }

        // Load helper data
        $this->load->model('user/user_group');
        $data['user_groups'] = $this->model_user_user_group->getUserGroups();

        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();

        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories();

        $this->load->model('setting/setting');
        $departments = $this->model_setting_setting->getSettingValue('config_departments');
        $data['departments'] = $departments ? json_decode($departments, true) : array();

        $data['currencies'] = array();
        $this->load->model('localisation/currency');
        $currencies = $this->model_localisation_currency->getCurrencies();
        foreach ($currencies as $currency) {
            if ($currency['status']) {
                $data['currencies'][] = $currency;
            }
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/approval_settings', $data));
    }

    public function workflow() {
        $this->load->language('purchase/approval_settings');

        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_workflow'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateWorkflow()) {
            $this->load->model('purchase/approval_settings');

            $this->model_purchase_approval_settings->saveWorkflow($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_workflow');

            $this->response->redirect($this->url->link('purchase/approval_settings/workflow', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/approval_settings', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_workflow'),
            'href' => $this->url->link('purchase/approval_settings/workflow', 'user_token=' . $this->session->data['user_token'], true)
        );

        $this->load->model('purchase/approval_settings');

        // Get workflow steps
        $data['workflow_steps'] = $this->model_purchase_approval_settings->getWorkflowSteps();

        // Get available approvers
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();

        $this->load->model('user/user_group');
        $data['user_groups'] = $this->model_user_user_group->getUserGroups();

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

        $data['action'] = $this->url->link('purchase/approval_settings/workflow', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('purchase/approval_settings', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/approval_workflow', $data));
    }

    public function test() {
        $this->load->language('purchase/approval_settings');
        $this->load->model('purchase/approval_settings');

        $json = array();

        if (isset($this->request->post['amount']) && isset($this->request->post['department_id']) && isset($this->request->post['category_id'])) {
            $test_data = array(
                'amount' => (float)$this->request->post['amount'],
                'department_id' => (int)$this->request->post['department_id'],
                'category_id' => (int)$this->request->post['category_id'],
                'user_id' => $this->user->getId()
            );

            $approval_flow = $this->model_purchase_approval_settings->getApprovalFlow($test_data);

            $json['success'] = true;
            $json['approval_flow'] = $approval_flow;
            $json['message'] = $this->language->get('text_test_success');
        } else {
            $json['success'] = false;
            $json['error'] = $this->language->get('error_test_data');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'purchase/approval_settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['approval_timeout_days'])) {
            $timeout_days = (int)$this->request->post['approval_timeout_days'];
            if ($timeout_days < 1 || $timeout_days > 365) {
                $this->error['approval_timeout_days'] = $this->language->get('error_timeout_days');
            }
        }

        if (isset($this->request->post['escalation_days'])) {
            $escalation_days = (int)$this->request->post['escalation_days'];
            if ($escalation_days < 1 || $escalation_days > 30) {
                $this->error['escalation_days'] = $this->language->get('error_escalation_days');
            }
        }

        if (isset($this->request->post['parallel_approval_percentage'])) {
            $percentage = (int)$this->request->post['parallel_approval_percentage'];
            if ($percentage < 1 || $percentage > 100) {
                $this->error['parallel_approval_percentage'] = $this->language->get('error_approval_percentage');
            }
        }

        // Validate amount thresholds
        if (isset($this->request->post['amount_thresholds'])) {
            foreach ($this->request->post['amount_thresholds'] as $key => $threshold) {
                if (isset($threshold['amount']) && $threshold['amount'] !== '') {
                    if (!is_numeric($threshold['amount']) || (float)$threshold['amount'] < 0) {
                        $this->error['amount_threshold_' . $key] = $this->language->get('error_threshold_amount');
                    }
                }

                if (empty($threshold['approver_type']) || empty($threshold['approver_id'])) {
                    $this->error['amount_threshold_approver_' . $key] = $this->language->get('error_threshold_approver');
                }
            }
        }

        return !$this->error;
    }

    protected function validateWorkflow() {
        if (!$this->user->hasPermission('modify', 'purchase/approval_settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['workflow_steps'])) {
            foreach ($this->request->post['workflow_steps'] as $key => $step) {
                if (empty($step['step_name'])) {
                    $this->error['step_name_' . $key] = $this->language->get('error_step_name');
                }

                if (empty($step['approver_type']) || empty($step['approver_id'])) {
                    $this->error['step_approver_' . $key] = $this->language->get('error_step_approver');
                }

                if (isset($step['sort_order']) && (!is_numeric($step['sort_order']) || (int)$step['sort_order'] < 0)) {
                    $this->error['step_sort_order_' . $key] = $this->language->get('error_step_sort_order');
                }
            }
        }

        return !$this->error;
    }

    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('user/user');

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start'       => 0,
                'limit'       => 5
            );

            $users = $this->model_user_user->getUsers($filter_data);

            foreach ($users as $user) {
                $json[] = array(
                    'user_id'  => $user['user_id'],
                    'name'     => strip_tags(html_entity_decode($user['firstname'] . ' ' . $user['lastname'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function export() {
        $this->load->language('purchase/approval_settings');
        $this->load->model('purchase/approval_settings');

        if (!$this->user->hasPermission('access', 'purchase/approval_settings')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $settings = $this->model_purchase_approval_settings->getAllSettings();

        $filename = 'purchase_approval_settings_' . date('Y-m-d_H-i-s') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        echo json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }

    public function import() {
        $this->load->language('purchase/approval_settings');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/approval_settings')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->files['import_file']) && $this->request->files['import_file']['error'] == 0) {
                $file_content = file_get_contents($this->request->files['import_file']['tmp_name']);
                $import_data = json_decode($file_content, true);

                if ($import_data && is_array($import_data)) {
                    $this->load->model('purchase/approval_settings');

                    try {
                        $this->model_purchase_approval_settings->importSettings($import_data);
                        $json['success'] = $this->language->get('text_import_success');
                    } catch (Exception $e) {
                        $json['error'] = $this->language->get('error_import_failed') . ': ' . $e->getMessage();
                    }
                } else {
                    $json['error'] = $this->language->get('error_import_invalid');
                }
            } else {
                $json['error'] = $this->language->get('error_import_file');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
