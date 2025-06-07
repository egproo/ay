<?php
/**
 * قوالب نظام الإشعارات المتقدم
 * Notification Templates Controller
 * 
 * نظام إدارة قوالب الإشعارات مع تكامل مع catalog/inventory
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

class ControllerNotificationTemplates extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة قوالب الإشعارات
     */
    public function index() {
        $this->load->language('notification/templates');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'notification/templates')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('notification/templates', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل القوالب
        $this->load->model('notification/templates');
        
        $filter_data = array(
            'start' => 0,
            'limit' => 20
        );
        
        if (isset($this->request->get['filter_name'])) {
            $filter_data['filter_name'] = $this->request->get['filter_name'];
        }
        
        if (isset($this->request->get['filter_type'])) {
            $filter_data['filter_type'] = $this->request->get['filter_type'];
        }
        
        if (isset($this->request->get['filter_status'])) {
            $filter_data['filter_status'] = $this->request->get['filter_status'];
        }
        
        $data['templates'] = $this->model_notification_templates->getTemplates($filter_data);
        $data['total'] = $this->model_notification_templates->getTotalTemplates($filter_data);
        
        // أنواع القوالب
        $data['template_types'] = array(
            'email' => $this->language->get('text_type_email'),
            'sms' => $this->language->get('text_type_sms'),
            'push' => $this->language->get('text_type_push'),
            'system' => $this->language->get('text_type_system')
        );
        
        // فئات القوالب للـ catalog/inventory
        $data['template_categories'] = array(
            'catalog' => array(
                'catalog_new_product' => $this->language->get('text_catalog_new_product'),
                'catalog_price_change' => $this->language->get('text_catalog_price_change'),
                'catalog_product_expiry' => $this->language->get('text_catalog_product_expiry'),
                'catalog_low_stock_alert' => $this->language->get('text_catalog_low_stock_alert')
            ),
            'inventory' => array(
                'inventory_low_stock' => $this->language->get('text_inventory_low_stock'),
                'inventory_stock_out' => $this->language->get('text_inventory_stock_out'),
                'inventory_reorder_point' => $this->language->get('text_inventory_reorder_point'),
                'inventory_batch_expiry' => $this->language->get('text_inventory_batch_expiry'),
                'inventory_movement' => $this->language->get('text_inventory_movement')
            ),
            'purchase' => array(
                'purchase_order_approved' => $this->language->get('text_purchase_order_approved'),
                'purchase_goods_received' => $this->language->get('text_purchase_goods_received'),
                'purchase_invoice_received' => $this->language->get('text_purchase_invoice_received')
            ),
            'sales' => array(
                'sales_order_received' => $this->language->get('text_sales_order_received'),
                'sales_order_shipped' => $this->language->get('text_sales_order_shipped'),
                'sales_payment_received' => $this->language->get('text_sales_payment_received')
            )
        );
        
        // المتغيرات المتاحة لكل فئة
        $data['available_variables'] = array(
            'catalog' => array(
                '{product_name}', '{product_id}', '{category_name}', '{price}', 
                '{old_price}', '{new_price}', '{expiry_date}', '{stock_quantity}'
            ),
            'inventory' => array(
                '{product_name}', '{product_id}', '{current_quantity}', '{minimum_quantity}',
                '{warehouse_name}', '{batch_number}', '{expiry_date}', '{movement_type}'
            ),
            'purchase' => array(
                '{order_number}', '{supplier_name}', '{total_amount}', '{order_date}',
                '{delivery_date}', '{items_count}', '{invoice_number}'
            ),
            'sales' => array(
                '{order_number}', '{customer_name}', '{total_amount}', '{order_date}',
                '{shipping_date}', '{payment_amount}', '{payment_method}'
            )
        );
        
        // الروابط
        $data['add'] = $this->url->link('notification/templates/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('notification/templates/delete', 'user_token=' . $this->session->data['user_token'], true);
        
        // الرسائل
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('notification/templates', $data));
    }
    
    /**
     * إضافة قالب جديد
     */
    public function add() {
        $this->load->language('notification/templates');
        
        $this->document->setTitle($this->language->get('text_add'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'notification/templates')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // معالجة حفظ القالب
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('notification/templates');
            
            $template_id = $this->model_notification_templates->addTemplate($this->request->post);
            
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
            
            $this->response->redirect($this->url->link('notification/templates', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    /**
     * تعديل قالب موجود
     */
    public function edit() {
        $this->load->language('notification/templates');
        
        $this->document->setTitle($this->language->get('text_edit'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'notification/templates')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // معالجة حفظ التعديلات
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('notification/templates');
            
            $this->model_notification_templates->editTemplate($this->request->get['template_id'], $this->request->post);
            
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
            
            $this->response->redirect($this->url->link('notification/templates', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    /**
     * حذف قالب
     */
    public function delete() {
        $this->load->language('notification/templates');
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'notification/templates')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            $this->load->model('notification/templates');
            
            foreach ($this->request->post['selected'] as $template_id) {
                $this->model_notification_templates->deleteTemplate($template_id);
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
            
            $this->response->redirect($this->url->link('notification/templates', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->index();
    }
    
    /**
     * معاينة قالب
     */
    public function preview() {
        $this->load->language('notification/templates');
        
        $json = array();
        
        if (!$this->user->hasPermission('access', 'notification/templates')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('notification/templates');
            
            if (isset($this->request->get['template_id'])) {
                $template_info = $this->model_notification_templates->getTemplate($this->request->get['template_id']);
                
                if ($template_info) {
                    // استبدال المتغيرات بقيم تجريبية
                    $sample_data = $this->getSampleData($template_info['template_key']);
                    
                    $content = $template_info['content'];
                    $subject = $template_info['subject'];
                    
                    foreach ($sample_data as $variable => $value) {
                        $content = str_replace($variable, $value, $content);
                        $subject = str_replace($variable, $value, $subject);
                    }
                    
                    $json['success'] = true;
                    $json['subject'] = $subject;
                    $json['content'] = $content;
                } else {
                    $json['error'] = $this->language->get('error_template_not_found');
                }
            } else {
                $json['error'] = $this->language->get('error_template_id_required');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * نموذج إضافة/تعديل القالب
     */
    protected function getForm() {
        // تحميل البيانات للنموذج
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('notification/templates', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحديد العملية (إضافة أم تعديل)
        if (!isset($this->request->get['template_id'])) {
            $data['action'] = $this->url->link('notification/templates/add', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('notification/templates/edit', 'template_id=' . $this->request->get['template_id'] . '&user_token=' . $this->session->data['user_token'], true);
        }
        
        $data['cancel'] = $this->url->link('notification/templates', 'user_token=' . $this->session->data['user_token'], true);
        
        // تحميل بيانات القالب للتعديل
        if (isset($this->request->get['template_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $this->load->model('notification/templates');
            $template_info = $this->model_notification_templates->getTemplate($this->request->get['template_id']);
        }
        
        // بيانات النموذج
        if (isset($this->request->post['template_name'])) {
            $data['template_name'] = $this->request->post['template_name'];
        } elseif (!empty($template_info)) {
            $data['template_name'] = $template_info['template_name'];
        } else {
            $data['template_name'] = '';
        }
        
        // المزيد من الحقول...
        
        // الرسائل
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('notification/template_form', $data));
    }
    
    /**
     * الحصول على بيانات تجريبية للمعاينة
     */
    private function getSampleData($template_key) {
        $sample_data = array();
        
        switch ($template_key) {
            case 'catalog_new_product':
                $sample_data = array(
                    '{product_name}' => 'لابتوب Dell Inspiron 15',
                    '{product_id}' => '12345',
                    '{category_name}' => 'أجهزة الكمبيوتر',
                    '{price}' => '15,000 جنيه'
                );
                break;
            case 'inventory_low_stock':
                $sample_data = array(
                    '{product_name}' => 'لابتوب Dell Inspiron 15',
                    '{current_quantity}' => '5',
                    '{minimum_quantity}' => '10',
                    '{warehouse_name}' => 'المستودع الرئيسي'
                );
                break;
            default:
                $sample_data = array(
                    '{product_name}' => 'منتج تجريبي',
                    '{quantity}' => '100',
                    '{date}' => date('Y-m-d')
                );
        }
        
        return $sample_data;
    }
    
    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'notification/templates')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen($this->request->post['template_name']) < 1) || (utf8_strlen($this->request->post['template_name']) > 255)) {
            $this->error['template_name'] = $this->language->get('error_template_name');
        }
        
        if (utf8_strlen($this->request->post['content']) < 1) {
            $this->error['content'] = $this->language->get('error_content');
        }
        
        return !$this->error;
    }
    
    /**
     * التحقق من صحة الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'notification/templates')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
}
