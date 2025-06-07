<?php
/**
 * تحكم نظام تجهيز الطلبات المتقدم
 * 
 * يوفر واجهة شاملة لتجهيز الطلبات مع:
 * - عرض الطلبات الجاهزة للتجهيز
 * - تجهيز الطلبات (Picking & Packing)
 * - إنشاء أوامر الشحن
 * - التكامل مع المخزون والمبيعات
 * - لوحة تحكم تجهيز الطلبات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerShippingOrderFulfillment extends Controller {
    
    private $error = [];
    
    /**
     * الصفحة الرئيسية لتجهيز الطلبات
     */
    public function index() {
        $this->load->language('shipping/order_fulfillment');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('shipping/order_fulfillment');
        
        $this->getList();
    }
    
    /**
     * تجهيز طلب محدد
     */
    public function fulfill() {
        $this->load->language('shipping/order_fulfillment');
        
        $this->document->setTitle($this->language->get('text_fulfill_order'));
        
        $this->load->model('shipping/order_fulfillment');
        $this->load->model('shipping/shipping_integration');
        
        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];
            
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateFulfillment()) {
                try {
                    $fulfillment_id = $this->model_shipping_order_fulfillment->fulfillOrder($order_id, $this->request->post);
                    
                    $this->session->data['success'] = $this->language->get('text_order_fulfilled');
                    
                    $this->response->redirect($this->url->link('shipping/order_fulfillment', 'user_token=' . $this->session->data['user_token'], true));
                } catch (Exception $e) {
                    $this->error['warning'] = $e->getMessage();
                }
            }
            
            $this->getFulfillmentForm($order_id);
        } else {
            $this->response->redirect($this->url->link('shipping/order_fulfillment', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * لوحة تحكم تجهيز الطلبات
     */
    public function dashboard() {
        $this->load->language('shipping/order_fulfillment');
        
        $this->document->setTitle($this->language->get('text_fulfillment_dashboard'));
        
        $this->load->model('shipping/order_fulfillment');
        $this->load->model('shipping/shipping_integration');
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/order_fulfillment', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_fulfillment_dashboard'),
            'href' => $this->url->link('shipping/order_fulfillment/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        // إحصائيات تجهيز الطلبات
        $data['statistics'] = $this->getFulfillmentStatistics();
        
        // الرسوم البيانية
        $data['charts_data'] = $this->getChartsData();
        
        // التنبيهات
        $data['alerts'] = $this->getFulfillmentAlerts();
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('shipping/order_fulfillment_dashboard', $data));
    }
    
    /**
     * تحديث حالة الطلب عبر AJAX
     */
    public function updateStatus() {
        $this->load->language('shipping/order_fulfillment');
        
        $this->load->model('shipping/order_fulfillment');
        
        $json = [];
        
        if (isset($this->request->post['order_id']) && isset($this->request->post['status'])) {
            $order_id = (int)$this->request->post['order_id'];
            $status = $this->request->post['status'];
            
            try {
                $this->model_shipping_order_fulfillment->updateOrderStatus($order_id, $status);
                
                $json['success'] = $this->language->get('text_status_updated');
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * طباعة قائمة الانتقاء (Picking List)
     */
    public function printPickingList() {
        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];
            
            $this->load->model('shipping/order_fulfillment');
            
            $order = $this->model_shipping_order_fulfillment->getOrderFulfillmentDetails($order_id);
            
            if ($order) {
                $data['order'] = $order;
                
                // إعداد الطباعة
                $this->response->addHeader('Content-Type: text/html; charset=utf-8');
                $this->response->setOutput($this->load->view('shipping/picking_list_print', $data));
            }
        }
    }
    
    /**
     * طباعة ملصق الشحن
     */
    public function printShippingLabel() {
        if (isset($this->request->get['shipping_order_id'])) {
            $shipping_order_id = (int)$this->request->get['shipping_order_id'];
            
            $this->load->model('shipping/shipping_integration');
            
            $shipping_order = $this->model_shipping_shipping_integration->getShippingOrder($shipping_order_id);
            
            if ($shipping_order && $shipping_order['shipping_label_url']) {
                // إعادة توجيه لرابط الملصق
                $this->response->redirect($shipping_order['shipping_label_url']);
            } else {
                $this->session->data['error'] = $this->language->get('error_label_not_available');
                $this->response->redirect($this->url->link('shipping/order_fulfillment', 'user_token=' . $this->session->data['user_token'], true));
            }
        }
    }
    
    /**
     * عرض قائمة الطلبات الجاهزة للتجهيز
     */
    protected function getList() {
        if (isset($this->request->get['filter_order_id'])) {
            $filter_order_id = $this->request->get['filter_order_id'];
        } else {
            $filter_order_id = '';
        }
        
        if (isset($this->request->get['filter_customer'])) {
            $filter_customer = $this->request->get['filter_customer'];
        } else {
            $filter_customer = '';
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
        
        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/order_fulfillment', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['dashboard'] = $this->url->link('shipping/order_fulfillment/dashboard', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['orders'] = [];
        
        $filter_data = [
            'filter_order_id' => $filter_order_id,
            'filter_customer' => $filter_customer,
            'filter_date_start' => $filter_date_start,
            'filter_date_end' => $filter_date_end,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        ];
        
        $orders = $this->model_shipping_order_fulfillment->getOrdersReadyForFulfillment($filter_data);
        
        foreach ($orders as $order) {
            $data['orders'][] = [
                'order_id' => $order['order_id'],
                'customer_name' => $order['customer_name'],
                'email' => $order['email'],
                'telephone' => $order['telephone'],
                'total' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']),
                'order_status' => $order['order_status_name'],
                'product_count' => $order['product_count'],
                'total_quantity' => $order['total_quantity'],
                'date_added' => date($this->language->get('datetime_format'), strtotime($order['date_added'])),
                'fulfill' => $this->url->link('shipping/order_fulfillment/fulfill', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order['order_id'], true),
                'view' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order['order_id'], true),
                'picking_list' => $this->url->link('shipping/order_fulfillment/printPickingList', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order['order_id'], true)
            ];
        }
        
        // إحصائيات سريعة
        $data['statistics'] = $this->getQuickStatistics();
        
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
        
        $data['filter_order_id'] = $filter_order_id;
        $data['filter_customer'] = $filter_customer;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('shipping/order_fulfillment_list', $data));
    }
    
    /**
     * نموذج تجهيز الطلب
     */
    protected function getFulfillmentForm($order_id) {
        $order = $this->model_shipping_order_fulfillment->getOrderFulfillmentDetails($order_id);
        
        if (!$order) {
            $this->session->data['error'] = $this->language->get('error_order_not_found');
            $this->response->redirect($this->url->link('shipping/order_fulfillment', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['order'] = $order;
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/order_fulfillment', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_fulfill_order') . ' #' . $order_id,
            'href' => $this->url->link('shipping/order_fulfillment/fulfill', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true)
        ];
        
        $data['action'] = $this->url->link('shipping/order_fulfillment/fulfill', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true);
        $data['cancel'] = $this->url->link('shipping/order_fulfillment', 'user_token=' . $this->session->data['user_token'], true);
        
        // الحصول على شركات الشحن المتاحة
        $this->load->model('shipping/shipping_company');
        $data['shipping_companies'] = $this->model_shipping_shipping_company->getActiveCompanies();
        
        $data['user_token'] = $this->session->data['user_token'];
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('shipping/order_fulfillment_form', $data));
    }
    
    /**
     * التحقق من صحة بيانات التجهيز
     */
    protected function validateFulfillment() {
        if (!$this->user->hasPermission('modify', 'shipping/order_fulfillment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['package_weight']) || $this->request->post['package_weight'] <= 0) {
            $this->error['package_weight'] = $this->language->get('error_package_weight');
        }
        
        if (empty($this->request->post['package_dimensions'])) {
            $this->error['package_dimensions'] = $this->language->get('error_package_dimensions');
        }
        
        return !$this->error;
    }
    
    /**
     * الحصول على إحصائيات سريعة
     */
    private function getQuickStatistics() {
        $statistics = [];
        
        // الطلبات الجاهزة للتجهيز
        $ready_orders = $this->model_shipping_order_fulfillment->getOrdersReadyForFulfillment(['limit' => 1000]);
        $statistics['ready_orders'] = count($ready_orders);
        
        // الطلبات المجهزة اليوم
        $today_fulfilled = $this->model_shipping_order_fulfillment->getTodayFulfilledOrders();
        $statistics['today_fulfilled'] = count($today_fulfilled);
        
        // متوسط وقت التجهيز
        $avg_fulfillment_time = $this->model_shipping_order_fulfillment->getAverageFulfillmentTime();
        $statistics['avg_fulfillment_time'] = round($avg_fulfillment_time, 1);
        
        return $statistics;
    }
    
    /**
     * الحصول على إحصائيات تجهيز الطلبات
     */
    private function getFulfillmentStatistics() {
        // يمكن تطوير هذه الدالة لاحقاً
        return [];
    }
    
    /**
     * الحصول على بيانات الرسوم البيانية
     */
    private function getChartsData() {
        // يمكن تطوير هذه الدالة لاحقاً
        return [];
    }
    
    /**
     * الحصول على تنبيهات التجهيز
     */
    private function getFulfillmentAlerts() {
        // يمكن تطوير هذه الدالة لاحقاً
        return [];
    }
}
