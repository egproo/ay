<?php
/**
 * إعدادات نظام الإشعارات المتقدم
 * Notification Settings Controller
 * 
 * نظام إدارة إعدادات الإشعارات مع تكامل مع catalog/inventory
 * مطور بمستوى عالمي لتفوق على Odoo
 * 
 * @package    AYM ERP
 * @author     AYM ERP Development Team
 * @copyright  2024 AYM ERP
 * @license    Proprietary
 * @version    1.0.0
 * @link       https://aym-erp.com
 * @since      2024-12-19
 */

class ControllerNotificationSettings extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة إعدادات الإشعارات
     */
    public function index() {
        $this->load->language('notification/settings');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'notification/settings')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('notification/settings', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // معالجة حفظ الإعدادات
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('notification/settings');
            
            $this->model_notification_settings->editSettings($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect($this->url->link('notification/settings', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // تحميل الإعدادات الحالية
        $this->load->model('notification/settings');
        $settings = $this->model_notification_settings->getSettings();
        
        // إعدادات عامة
        $data['notification_enabled'] = isset($settings['notification_enabled']) ? $settings['notification_enabled'] : 1;
        $data['email_notifications'] = isset($settings['email_notifications']) ? $settings['email_notifications'] : 1;
        $data['sms_notifications'] = isset($settings['sms_notifications']) ? $settings['sms_notifications'] : 0;
        $data['push_notifications'] = isset($settings['push_notifications']) ? $settings['push_notifications'] : 1;
        
        // إعدادات catalog/inventory
        $data['catalog_notifications'] = array(
            'new_product' => isset($settings['catalog_new_product']) ? $settings['catalog_new_product'] : 1,
            'price_change' => isset($settings['catalog_price_change']) ? $settings['catalog_price_change'] : 1,
            'product_expiry' => isset($settings['catalog_product_expiry']) ? $settings['catalog_product_expiry'] : 1,
            'low_stock' => isset($settings['inventory_low_stock']) ? $settings['inventory_low_stock'] : 1,
            'stock_out' => isset($settings['inventory_stock_out']) ? $settings['inventory_stock_out'] : 1,
            'stock_movement' => isset($settings['inventory_stock_movement']) ? $settings['inventory_stock_movement'] : 0,
            'batch_expiry' => isset($settings['inventory_batch_expiry']) ? $settings['inventory_batch_expiry'] : 1,
            'reorder_point' => isset($settings['inventory_reorder_point']) ? $settings['inventory_reorder_point'] : 1
        );
        
        // إعدادات الأولوية
        $data['priority_settings'] = array(
            'critical' => isset($settings['priority_critical']) ? $settings['priority_critical'] : 1,
            'high' => isset($settings['priority_high']) ? $settings['priority_high'] : 1,
            'medium' => isset($settings['priority_medium']) ? $settings['priority_medium'] : 1,
            'low' => isset($settings['priority_low']) ? $settings['priority_low'] : 0
        );
        
        // إعدادات التوقيت
        $data['timing_settings'] = array(
            'real_time' => isset($settings['timing_real_time']) ? $settings['timing_real_time'] : 1,
            'batch_interval' => isset($settings['timing_batch_interval']) ? $settings['timing_batch_interval'] : 15,
            'quiet_hours_start' => isset($settings['timing_quiet_hours_start']) ? $settings['timing_quiet_hours_start'] : '22:00',
            'quiet_hours_end' => isset($settings['timing_quiet_hours_end']) ? $settings['timing_quiet_hours_end'] : '08:00'
        );
        
        // إعدادات المستخدمين والأدوار
        $this->load->model('user/user_group');
        $data['user_groups'] = $this->model_user_user_group->getUserGroups();
        
        // إعدادات القوالب
        $this->load->model('notification/templates');
        $data['templates'] = $this->model_notification_templates->getTemplates();
        
        // الروابط
        $data['action'] = $this->url->link('notification/settings', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('notification/center', 'user_token=' . $this->session->data['user_token'], true);
        
        // الرسائل
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
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('notification/settings', $data));
    }
    
    /**
     * اختبار إعدادات الإشعارات
     */
    public function test() {
        $this->load->language('notification/settings');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'notification/settings')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('notification/settings');
            
            $test_type = isset($this->request->post['test_type']) ? $this->request->post['test_type'] : 'email';
            
            try {
                switch ($test_type) {
                    case 'email':
                        $result = $this->model_notification_settings->testEmailNotification();
                        break;
                    case 'sms':
                        $result = $this->model_notification_settings->testSmsNotification();
                        break;
                    case 'push':
                        $result = $this->model_notification_settings->testPushNotification();
                        break;
                    default:
                        $result = false;
                }
                
                if ($result) {
                    $json['success'] = $this->language->get('text_test_success');
                } else {
                    $json['error'] = $this->language->get('text_test_failed');
                }
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * تصدير إعدادات الإشعارات
     */
    public function export() {
        $this->load->language('notification/settings');
        
        if (!$this->user->hasPermission('access', 'notification/settings')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->load->model('notification/settings');
        $settings = $this->model_notification_settings->getSettings();
        
        $filename = 'notification_settings_' . date('Y-m-d_H-i-s') . '.json';
        
        $this->response->addheader('Pragma: public');
        $this->response->addheader('Expires: 0');
        $this->response->addheader('Content-Description: File Transfer');
        $this->response->addheader('Content-Type: application/json');
        $this->response->addheader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->addheader('Content-Transfer-Encoding: binary');
        
        $this->response->setOutput(json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * استيراد إعدادات الإشعارات
     */
    public function import() {
        $this->load->language('notification/settings');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'notification/settings')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->files['import_file']) && is_uploaded_file($this->request->files['import_file']['tmp_name'])) {
                $file_content = file_get_contents($this->request->files['import_file']['tmp_name']);
                $settings = json_decode($file_content, true);
                
                if ($settings && is_array($settings)) {
                    $this->load->model('notification/settings');
                    $this->model_notification_settings->editSettings($settings);
                    
                    $json['success'] = $this->language->get('text_import_success');
                } else {
                    $json['error'] = $this->language->get('error_invalid_file');
                }
            } else {
                $json['error'] = $this->language->get('error_no_file');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'notification/settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        // التحقق من إعدادات التوقيت
        if (isset($this->request->post['timing_batch_interval'])) {
            $interval = (int)$this->request->post['timing_batch_interval'];
            if ($interval < 1 || $interval > 1440) {
                $this->error['batch_interval'] = $this->language->get('error_batch_interval');
            }
        }
        
        return !$this->error;
    }
}
