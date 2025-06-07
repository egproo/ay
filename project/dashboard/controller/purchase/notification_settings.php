<?php
/**
 * AYM ERP - Purchase Notification Settings Controller
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerPurchaseNotificationSettings extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('purchase/notification_settings');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('purchase/notification_settings');

            $this->model_purchase_notification_settings->editSettings($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('purchase/notification_settings', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/notification_settings', 'user_token=' . $this->session->data['user_token'], true)
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

        $data['action'] = $this->url->link('purchase/notification_settings', 'user_token=' . $this->session->data['user_token'], true);

        $this->load->model('purchase/notification_settings');

        // Get current settings
        $settings = $this->model_purchase_notification_settings->getSettings();

        // General notification settings
        if (isset($this->request->post['notification_enabled'])) {
            $data['notification_enabled'] = $this->request->post['notification_enabled'];
        } else {
            $data['notification_enabled'] = $settings['notification_enabled'] ?? '1';
        }

        if (isset($this->request->post['email_enabled'])) {
            $data['email_enabled'] = $this->request->post['email_enabled'];
        } else {
            $data['email_enabled'] = $settings['email_enabled'] ?? '1';
        }

        if (isset($this->request->post['sms_enabled'])) {
            $data['sms_enabled'] = $this->request->post['sms_enabled'];
        } else {
            $data['sms_enabled'] = $settings['sms_enabled'] ?? '0';
        }

        if (isset($this->request->post['push_enabled'])) {
            $data['push_enabled'] = $this->request->post['push_enabled'];
        } else {
            $data['push_enabled'] = $settings['push_enabled'] ?? '1';
        }

        if (isset($this->request->post['internal_enabled'])) {
            $data['internal_enabled'] = $this->request->post['internal_enabled'];
        } else {
            $data['internal_enabled'] = $settings['internal_enabled'] ?? '1';
        }

        // Email settings
        if (isset($this->request->post['email_from_name'])) {
            $data['email_from_name'] = $this->request->post['email_from_name'];
        } else {
            $data['email_from_name'] = $settings['email_from_name'] ?? '';
        }

        if (isset($this->request->post['email_from_address'])) {
            $data['email_from_address'] = $this->request->post['email_from_address'];
        } else {
            $data['email_from_address'] = $settings['email_from_address'] ?? '';
        }

        if (isset($this->request->post['email_reply_to'])) {
            $data['email_reply_to'] = $this->request->post['email_reply_to'];
        } else {
            $data['email_reply_to'] = $settings['email_reply_to'] ?? '';
        }

        // SMS settings
        if (isset($this->request->post['sms_provider'])) {
            $data['sms_provider'] = $this->request->post['sms_provider'];
        } else {
            $data['sms_provider'] = $settings['sms_provider'] ?? 'twilio';
        }

        if (isset($this->request->post['sms_api_key'])) {
            $data['sms_api_key'] = $this->request->post['sms_api_key'];
        } else {
            $data['sms_api_key'] = $settings['sms_api_key'] ?? '';
        }

        if (isset($this->request->post['sms_api_secret'])) {
            $data['sms_api_secret'] = $this->request->post['sms_api_secret'];
        } else {
            $data['sms_api_secret'] = $settings['sms_api_secret'] ?? '';
        }

        if (isset($this->request->post['sms_from_number'])) {
            $data['sms_from_number'] = $this->request->post['sms_from_number'];
        } else {
            $data['sms_from_number'] = $settings['sms_from_number'] ?? '';
        }

        // Push notification settings
        if (isset($this->request->post['push_provider'])) {
            $data['push_provider'] = $this->request->post['push_provider'];
        } else {
            $data['push_provider'] = $settings['push_provider'] ?? 'firebase';
        }

        if (isset($this->request->post['push_api_key'])) {
            $data['push_api_key'] = $this->request->post['push_api_key'];
        } else {
            $data['push_api_key'] = $settings['push_api_key'] ?? '';
        }

        if (isset($this->request->post['push_app_id'])) {
            $data['push_app_id'] = $this->request->post['push_app_id'];
        } else {
            $data['push_app_id'] = $settings['push_app_id'] ?? '';
        }

        // Notification events
        if (isset($this->request->post['notification_events'])) {
            $data['notification_events'] = $this->request->post['notification_events'];
        } else {
            $data['notification_events'] = $this->model_purchase_notification_settings->getNotificationEvents();
        }

        // Notification templates
        if (isset($this->request->post['notification_templates'])) {
            $data['notification_templates'] = $this->request->post['notification_templates'];
        } else {
            $data['notification_templates'] = $this->model_purchase_notification_settings->getNotificationTemplates();
        }

        // Notification rules
        if (isset($this->request->post['notification_rules'])) {
            $data['notification_rules'] = $this->request->post['notification_rules'];
        } else {
            $data['notification_rules'] = $this->model_purchase_notification_settings->getNotificationRules();
        }

        // Escalation settings
        if (isset($this->request->post['escalation_enabled'])) {
            $data['escalation_enabled'] = $this->request->post['escalation_enabled'];
        } else {
            $data['escalation_enabled'] = $settings['escalation_enabled'] ?? '1';
        }

        if (isset($this->request->post['escalation_levels'])) {
            $data['escalation_levels'] = $this->request->post['escalation_levels'];
        } else {
            $data['escalation_levels'] = $this->model_purchase_notification_settings->getEscalationLevels();
        }

        // Frequency settings
        if (isset($this->request->post['digest_enabled'])) {
            $data['digest_enabled'] = $this->request->post['digest_enabled'];
        } else {
            $data['digest_enabled'] = $settings['digest_enabled'] ?? '1';
        }

        if (isset($this->request->post['digest_frequency'])) {
            $data['digest_frequency'] = $this->request->post['digest_frequency'];
        } else {
            $data['digest_frequency'] = $settings['digest_frequency'] ?? 'daily';
        }

        if (isset($this->request->post['digest_time'])) {
            $data['digest_time'] = $this->request->post['digest_time'];
        } else {
            $data['digest_time'] = $settings['digest_time'] ?? '09:00';
        }

        // Load helper data
        $this->load->model('user/user_group');
        $data['user_groups'] = $this->model_user_user_group->getUserGroups();

        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();

        $this->load->model('setting/setting');
        $departments = $this->model_setting_setting->getSettingValue('config_departments');
        $data['departments'] = $departments ? json_decode($departments, true) : array();

        // Available notification providers
        $data['sms_providers'] = array(
            'twilio' => 'Twilio',
            'nexmo' => 'Nexmo/Vonage',
            'aws_sns' => 'AWS SNS',
            'clickatell' => 'Clickatell',
            'custom' => 'Custom API'
        );

        $data['push_providers'] = array(
            'firebase' => 'Firebase Cloud Messaging',
            'onesignal' => 'OneSignal',
            'pusher' => 'Pusher',
            'aws_sns' => 'AWS SNS',
            'custom' => 'Custom API'
        );

        $data['digest_frequencies'] = array(
            'hourly' => $this->language->get('text_hourly'),
            'daily' => $this->language->get('text_daily'),
            'weekly' => $this->language->get('text_weekly'),
            'monthly' => $this->language->get('text_monthly')
        );

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/notification_settings', $data));
    }

    public function templates() {
        $this->load->language('purchase/notification_settings');

        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_templates'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateTemplates()) {
            $this->load->model('purchase/notification_settings');

            $this->model_purchase_notification_settings->saveTemplates($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_templates');

            $this->response->redirect($this->url->link('purchase/notification_settings/templates', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/notification_settings', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_templates'),
            'href' => $this->url->link('purchase/notification_settings/templates', 'user_token=' . $this->session->data['user_token'], true)
        );

        $this->load->model('purchase/notification_settings');

        // Get notification templates
        $data['templates'] = $this->model_purchase_notification_settings->getNotificationTemplates();

        // Get available variables for templates
        $data['template_variables'] = $this->model_purchase_notification_settings->getTemplateVariables();

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

        $data['action'] = $this->url->link('purchase/notification_settings/templates', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('purchase/notification_settings', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/notification_templates', $data));
    }

    public function test() {
        $this->load->language('purchase/notification_settings');
        $this->load->model('purchase/notification_settings');

        $json = array();

        if (isset($this->request->post['notification_type']) && isset($this->request->post['delivery_method'])) {
            $test_data = array(
                'notification_type' => $this->request->post['notification_type'],
                'delivery_method' => $this->request->post['delivery_method'],
                'recipient' => $this->request->post['recipient'] ?? '',
                'test_message' => $this->request->post['test_message'] ?? 'Test notification from AYM ERP'
            );

            try {
                $result = $this->model_purchase_notification_settings->sendTestNotification($test_data);

                if ($result['success']) {
                    $json['success'] = true;
                    $json['message'] = $this->language->get('text_test_success');
                    $json['details'] = $result['details'];
                } else {
                    $json['success'] = false;
                    $json['error'] = $result['error'];
                }
            } catch (Exception $e) {
                $json['success'] = false;
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['success'] = false;
            $json['error'] = $this->language->get('error_test_data');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function preview() {
        $this->load->language('purchase/notification_settings');
        $this->load->model('purchase/notification_settings');

        $json = array();

        if (isset($this->request->post['template_id']) && isset($this->request->post['sample_data'])) {
            $template_id = $this->request->post['template_id'];
            $sample_data = $this->request->post['sample_data'];

            try {
                $preview = $this->model_purchase_notification_settings->previewTemplate($template_id, $sample_data);

                $json['success'] = true;
                $json['preview'] = $preview;
            } catch (Exception $e) {
                $json['success'] = false;
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['success'] = false;
            $json['error'] = $this->language->get('error_preview_data');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function logs() {
        $this->load->language('purchase/notification_settings');

        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_logs'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/notification_settings', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_logs'),
            'href' => $this->url->link('purchase/notification_settings/logs', 'user_token=' . $this->session->data['user_token'], true)
        );

        $this->getLogsList($data);
    }

    public function analytics() {
        $this->load->language('purchase/notification_settings');
        $this->load->model('purchase/notification_settings');

        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_analytics'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/notification_settings', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_analytics'),
            'href' => $this->url->link('purchase/notification_settings/analytics', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Get notification analytics
        $data['notification_stats'] = $this->model_purchase_notification_settings->getNotificationStatistics();

        // Get delivery analytics
        $data['delivery_stats'] = $this->model_purchase_notification_settings->getDeliveryStatistics();

        // Get performance metrics
        $data['performance_metrics'] = $this->model_purchase_notification_settings->getPerformanceMetrics();

        // Get trend data
        $data['trend_data'] = $this->model_purchase_notification_settings->getTrendData();

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/notification_analytics', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'purchase/notification_settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['email_enabled']) && $this->request->post['email_enabled']) {
            if (empty($this->request->post['email_from_address']) || !filter_var($this->request->post['email_from_address'], FILTER_VALIDATE_EMAIL)) {
                $this->error['email_from_address'] = $this->language->get('error_email_from_address');
            }

            if (empty($this->request->post['email_from_name'])) {
                $this->error['email_from_name'] = $this->language->get('error_email_from_name');
            }
        }

        if (isset($this->request->post['sms_enabled']) && $this->request->post['sms_enabled']) {
            if (empty($this->request->post['sms_provider'])) {
                $this->error['sms_provider'] = $this->language->get('error_sms_provider');
            }

            if (empty($this->request->post['sms_api_key'])) {
                $this->error['sms_api_key'] = $this->language->get('error_sms_api_key');
            }
        }

        if (isset($this->request->post['push_enabled']) && $this->request->post['push_enabled']) {
            if (empty($this->request->post['push_provider'])) {
                $this->error['push_provider'] = $this->language->get('error_push_provider');
            }

            if (empty($this->request->post['push_api_key'])) {
                $this->error['push_api_key'] = $this->language->get('error_push_api_key');
            }
        }

        return !$this->error;
    }

    protected function validateTemplates() {
        if (!$this->user->hasPermission('modify', 'purchase/notification_settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['templates'])) {
            foreach ($this->request->post['templates'] as $key => $template) {
                if (empty($template['name'])) {
                    $this->error['template_name_' . $key] = $this->language->get('error_template_name');
                }

                if (empty($template['subject'])) {
                    $this->error['template_subject_' . $key] = $this->language->get('error_template_subject');
                }

                if (empty($template['content'])) {
                    $this->error['template_content_' . $key] = $this->language->get('error_template_content');
                }
            }
        }

        return !$this->error;
    }

    protected function getLogsList(&$data = array()) {
        if (isset($this->request->get['filter_type'])) {
            $filter_type = $this->request->get['filter_type'];
        } else {
            $filter_type = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = '';
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'pn.date_added';
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

        $data['logs'] = array();

        $filter_data = array(
            'filter_type'       => $filter_type,
            'filter_status'     => $filter_status,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'sort'              => $sort,
            'order'             => $order,
            'start'             => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'             => $this->config->get('config_limit_admin')
        );

        $this->load->model('purchase/notification_settings');

        $log_total = $this->model_purchase_notification_settings->getTotalNotificationLogs($filter_data);

        $results = $this->model_purchase_notification_settings->getNotificationLogs($filter_data);

        foreach ($results as $result) {
            $data['logs'][] = array(
                'log_id'         => $result['log_id'],
                'notification_type' => $result['notification_type'],
                'delivery_method' => $result['delivery_method'],
                'recipient'      => $result['recipient'],
                'subject'        => $result['subject'],
                'status'         => $result['status'],
                'error_message'  => $result['error_message'],
                'date_added'     => date($this->language->get('date_format_short'), strtotime($result['date_added']))
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

        $data['filter_type'] = $filter_type;
        $data['filter_status'] = $filter_status;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $pagination = new Pagination();
        $pagination->total = $log_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('purchase/notification_settings/logs', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($log_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($log_total - $this->config->get('config_limit_admin'))) ? $log_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $log_total, ceil($log_total / $this->config->get('config_limit_admin')));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/notification_logs', $data));
    }
}
