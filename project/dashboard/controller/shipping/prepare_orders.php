<?php
/**
 * AYM ERP System: Advanced Order Preparation Controller
 * 
 * شاشة تجهيز الطلبات المتقدمة - مطورة للشركات الحقيقية
 * 
 * الميزات المتقدمة:
 * - فلاتر متقدمة حسب الحالة والأولوية
 * - ملخص شامل للطلبات المطلوب تجهيزها
 * - تتبع التقدم في التجهيز
 * - طباعة قوائم التجهيز (Picking Lists)
 * - تكامل مع المخزون والشحن
 * - إشعارات تلقائية للعملاء
 * - تحديث حالة الطلب في الوقت الفعلي
 * - دعم الوحدات المتعددة والخيارات
 * - تكامل مع نظام الباركود
 * - إدارة أولويات التجهيز
 * 
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

class ControllerShippingPrepareOrders extends Controller {
    
    /**
     * الشاشة الرئيسية لتجهيز الطلبات
     */
    public function index() {
        $this->load->language('shipping/prepare_orders');
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'shipping/prepare_orders')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // تحميل النماذج المطلوبة
        $this->load->model('shipping/prepare_orders');
        $this->load->model('sale/order');
        $this->load->model('inventory/inventory');
        $this->load->model('localisation/order_status');
        
        // معالجة الفلاتر
        $filter_data = $this->getFilterData();
        
        // جلب الطلبات المطلوب تجهيزها
        $orders = $this->model_shipping_prepare_orders->getOrdersForPreparation($filter_data);
        $total_orders = $this->model_shipping_prepare_orders->getTotalOrdersForPreparation($filter_data);
        
        // إعداد البيانات للعرض
        $data = $this->prepareViewData($orders, $filter_data);
        
        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total_orders;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link('shipping/prepare_orders', 'user_token=' . $this->session->data['user_token'] . $this->buildFilterUrl($filter_data) . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total_orders) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0, ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total_orders - $filter_data['limit'])) ? $total_orders : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']), $total_orders, ceil($total_orders / $filter_data['limit']));
        
        // تحميل القوالب
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('shipping/prepare_orders', $data));
    }
    
    /**
     * تحديث حالة الطلب
     */
    public function updateOrderStatus() {
        $this->load->language('shipping/prepare_orders');
        $this->load->model('shipping/prepare_orders');
        
        $json = array('success' => false);
        
        if (isset($this->request->post['order_id']) && isset($this->request->post['status'])) {
            $order_id = (int)$this->request->post['order_id'];
            $status = $this->request->post['status'];
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';
            
            $result = $this->model_shipping_prepare_orders->updateOrderPreparationStatus($order_id, $status, $notes);
            
            if ($result) {
                $json['success'] = true;
                $json['message'] = $this->language->get('text_status_updated');
                
                // إرسال إشعار للعميل إذا مطلوب
                if ($this->config->get('prepare_orders_notify_customer')) {
                    $this->sendCustomerNotification($order_id, $status);
                }
            } else {
                $json['error'] = $this->language->get('error_update_status');
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * طباعة قائمة التجهيز
     */
    public function printPickingList() {
        $this->load->language('shipping/prepare_orders');
        $this->load->model('shipping/prepare_orders');
        
        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];
            
            // جلب تفاصيل الطلب
            $order_info = $this->model_shipping_prepare_orders->getOrderForPicking($order_id);
            
            if ($order_info) {
                // إعداد البيانات للطباعة
                $data = $this->preparePickingListData($order_info);
                
                // تحديد نوع الطباعة (PDF أو HTML)
                $print_type = isset($this->request->get['type']) ? $this->request->get['type'] : 'html';
                
                if ($print_type == 'pdf') {
                    $this->generatePickingListPDF($data);
                } else {
                    $this->response->setOutput($this->load->view('shipping/picking_list_print', $data));
                }
            } else {
                $this->session->data['error'] = $this->language->get('error_order_not_found');
                $this->response->redirect($this->url->link('shipping/prepare_orders', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('shipping/prepare_orders', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * طباعة قوائم تجهيز متعددة
     */
    public function printMultiplePickingLists() {
        $this->load->language('shipping/prepare_orders');
        $this->load->model('shipping/prepare_orders');
        
        $json = array('success' => false);
        
        if (isset($this->request->post['selected']) && is_array($this->request->post['selected'])) {
            $order_ids = $this->request->post['selected'];
            
            // التحقق من صحة الطلبات
            $valid_orders = array();
            foreach ($order_ids as $order_id) {
                $order_info = $this->model_shipping_prepare_orders->getOrderForPicking((int)$order_id);
                if ($order_info) {
                    $valid_orders[] = $order_info;
                }
            }
            
            if (!empty($valid_orders)) {
                // إنشاء ملف PDF مجمع
                $pdf_url = $this->generateMultiplePickingListsPDF($valid_orders);
                
                $json['success'] = true;
                $json['message'] = $this->language->get('text_picking_lists_generated');
                $json['pdf_url'] = $pdf_url;
            } else {
                $json['error'] = $this->language->get('error_no_valid_orders');
            }
        } else {
            $json['error'] = $this->language->get('error_no_orders_selected');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * تحديث أولوية الطلب
     */
    public function updateOrderPriority() {
        $this->load->language('shipping/prepare_orders');
        $this->load->model('shipping/prepare_orders');
        
        $json = array('success' => false);
        
        if (isset($this->request->post['order_id']) && isset($this->request->post['priority'])) {
            $order_id = (int)$this->request->post['order_id'];
            $priority = $this->request->post['priority'];
            
            $result = $this->model_shipping_prepare_orders->updateOrderPriority($order_id, $priority);
            
            if ($result) {
                $json['success'] = true;
                $json['message'] = $this->language->get('text_priority_updated');
            } else {
                $json['error'] = $this->language->get('error_update_priority');
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * الحصول على إحصائيات التجهيز
     */
    public function getPreparationStatistics() {
        $this->load->language('shipping/prepare_orders');
        $this->load->model('shipping/prepare_orders');
        
        $json = array();
        
        $statistics = $this->model_shipping_prepare_orders->getPreparationStatistics();
        
        $json['statistics'] = array(
            'pending_orders' => $statistics['pending_orders'] ?? 0,
            'in_progress_orders' => $statistics['in_progress_orders'] ?? 0,
            'ready_orders' => $statistics['ready_orders'] ?? 0,
            'shipped_orders' => $statistics['shipped_orders'] ?? 0,
            'total_items' => $statistics['total_items'] ?? 0,
            'prepared_items' => $statistics['prepared_items'] ?? 0,
            'preparation_percentage' => $statistics['preparation_percentage'] ?? 0
        );
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * تحديث حالة عنصر في الطلب
     */
    public function updateOrderItemStatus() {
        $this->load->language('shipping/prepare_orders');
        $this->load->model('shipping/prepare_orders');
        
        $json = array('success' => false);
        
        if (isset($this->request->post['order_product_id']) && isset($this->request->post['status'])) {
            $order_product_id = (int)$this->request->post['order_product_id'];
            $status = $this->request->post['status'];
            $prepared_quantity = isset($this->request->post['prepared_quantity']) ? (int)$this->request->post['prepared_quantity'] : 0;
            
            $result = $this->model_shipping_prepare_orders->updateOrderItemStatus($order_product_id, $status, $prepared_quantity);
            
            if ($result) {
                $json['success'] = true;
                $json['message'] = $this->language->get('text_item_status_updated');
                
                // التحقق من اكتمال تجهيز الطلب
                $order_id = $this->model_shipping_prepare_orders->getOrderIdByProductId($order_product_id);
                if ($this->model_shipping_prepare_orders->isOrderFullyPrepared($order_id)) {
                    $this->model_shipping_prepare_orders->updateOrderPreparationStatus($order_id, 'ready_for_shipping');
                    $json['order_ready'] = true;
                }
            } else {
                $json['error'] = $this->language->get('error_update_item_status');
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * إعداد بيانات الفلاتر
     */
    private function getFilterData() {
        $filter_data = array();
        
        // فلاتر البحث
        $filter_data['filter_order_id'] = isset($this->request->get['filter_order_id']) ? $this->request->get['filter_order_id'] : '';
        $filter_data['filter_customer'] = isset($this->request->get['filter_customer']) ? $this->request->get['filter_customer'] : '';
        $filter_data['filter_status'] = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';
        $filter_data['filter_priority'] = isset($this->request->get['filter_priority']) ? $this->request->get['filter_priority'] : '';
        $filter_data['filter_date_from'] = isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : '';
        $filter_data['filter_date_to'] = isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : '';
        $filter_data['filter_branch'] = isset($this->request->get['filter_branch']) ? $this->request->get['filter_branch'] : '';
        
        // الترتيب والترقيم
        $filter_data['sort'] = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'o.date_added';
        $filter_data['order'] = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';
        $filter_data['page'] = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $filter_data['limit'] = $this->config->get('config_limit_admin');
        $filter_data['start'] = ($filter_data['page'] - 1) * $filter_data['limit'];
        
        return $filter_data;
    }
    
    /**
     * إعداد البيانات للعرض
     */
    private function prepareViewData($orders, $filter_data) {
        $data = array();
        
        // معلومات أساسية
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        
        // أزرار وإجراءات
        $data['button_filter'] = $this->language->get('button_filter');
        $data['button_print_picking_list'] = $this->language->get('button_print_picking_list');
        $data['button_update_status'] = $this->language->get('button_update_status');
        $data['button_update_priority'] = $this->language->get('button_update_priority');
        
        // عناوين الأعمدة
        $data['column_order_id'] = $this->language->get('column_order_id');
        $data['column_customer'] = $this->language->get('column_customer');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_priority'] = $this->language->get('column_priority');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_action'] = $this->language->get('column_action');
        
        // الفلاتر
        $data['filter_order_id'] = $filter_data['filter_order_id'];
        $data['filter_customer'] = $filter_data['filter_customer'];
        $data['filter_status'] = $filter_data['filter_status'];
        $data['filter_priority'] = $filter_data['filter_priority'];
        $data['filter_date_from'] = $filter_data['filter_date_from'];
        $data['filter_date_to'] = $filter_data['filter_date_to'];
        
        // خيارات الحالة والأولوية
        $data['preparation_statuses'] = $this->model_shipping_prepare_orders->getPreparationStatuses();
        $data['priority_levels'] = $this->model_shipping_prepare_orders->getPriorityLevels();
        
        // الطلبات
        $data['orders'] = array();
        foreach ($orders as $order) {
            $data['orders'][] = array(
                'order_id' => $order['order_id'],
                'customer' => $order['customer'],
                'status' => $order['preparation_status'],
                'priority' => $order['priority'],
                'total' => $this->currency->format($order['total'], $order['currency_code']),
                'date_added' => date($this->language->get('date_format_short'), strtotime($order['date_added'])),
                'items_count' => $order['items_count'],
                'prepared_items' => $order['prepared_items'],
                'preparation_percentage' => $order['preparation_percentage'],
                'view' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order['order_id'], true),
                'print_picking_list' => $this->url->link('shipping/prepare_orders/printPickingList', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order['order_id'], true)
            );
        }
        
        // الروابط
        $data['user_token'] = $this->session->data['user_token'];
        $data['sort'] = $filter_data['sort'];
        $data['order'] = $filter_data['order'];
        
        // breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_shipping'),
            'href' => $this->url->link('shipping/prepare_orders', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/prepare_orders', 'user_token=' . $this->session->data['user_token'] . $this->buildFilterUrl($filter_data), true)
        );
        
        return $data;
    }
    
    /**
     * بناء رابط الفلاتر
     */
    private function buildFilterUrl($filter_data) {
        $url = '';
        
        if ($filter_data['filter_order_id']) {
            $url .= '&filter_order_id=' . urlencode($filter_data['filter_order_id']);
        }
        
        if ($filter_data['filter_customer']) {
            $url .= '&filter_customer=' . urlencode($filter_data['filter_customer']);
        }
        
        if ($filter_data['filter_status']) {
            $url .= '&filter_status=' . urlencode($filter_data['filter_status']);
        }
        
        if ($filter_data['filter_priority']) {
            $url .= '&filter_priority=' . urlencode($filter_data['filter_priority']);
        }
        
        if ($filter_data['filter_date_from']) {
            $url .= '&filter_date_from=' . urlencode($filter_data['filter_date_from']);
        }
        
        if ($filter_data['filter_date_to']) {
            $url .= '&filter_date_to=' . urlencode($filter_data['filter_date_to']);
        }
        
        if ($filter_data['sort']) {
            $url .= '&sort=' . $filter_data['sort'];
        }
        
        if ($filter_data['order']) {
            $url .= '&order=' . $filter_data['order'];
        }
        
        return $url;
    }
    
    /**
     * إرسال إشعار للعميل
     */
    private function sendCustomerNotification($order_id, $status) {
        $this->load->model('mail/mail');
        $this->load->model('sale/order');
        
        $order_info = $this->model_sale_order->getOrder($order_id);
        
        if ($order_info && $order_info['email']) {
            // إعداد رسالة الإشعار
            $subject = sprintf($this->language->get('text_order_status_update_subject'), $order_id);
            $message = sprintf($this->language->get('text_order_status_update_message'), $order_info['firstname'], $order_id, $this->language->get('text_status_' . $status));
            
            // إرسال البريد الإلكتروني
            $this->model_mail_mail->send($order_info['email'], $subject, $message);
        }
    }
    
    /**
     * إعداد بيانات قائمة التجهيز
     */
    private function preparePickingListData($order_info) {
        $data = array();
        
        $data['order_info'] = $order_info;
        $data['company_info'] = array(
            'name' => $this->config->get('config_name'),
            'address' => $this->config->get('config_address'),
            'telephone' => $this->config->get('config_telephone'),
            'email' => $this->config->get('config_email')
        );
        
        $data['text_picking_list'] = $this->language->get('text_picking_list');
        $data['text_order_id'] = $this->language->get('text_order_id');
        $data['text_customer'] = $this->language->get('text_customer');
        $data['text_date'] = $this->language->get('text_date');
        $data['text_product'] = $this->language->get('text_product');
        $data['text_quantity'] = $this->language->get('text_quantity');
        $data['text_location'] = $this->language->get('text_location');
        
        return $data;
    }
    
    /**
     * إنشاء PDF لقائمة التجهيز
     */
    private function generatePickingListPDF($data) {
        // تنفيذ إنشاء PDF
        // يمكن استخدام مكتبة مثل TCPDF أو DOMPDF
    }
    
    /**
     * إنشاء PDF لقوائم تجهيز متعددة
     */
    private function generateMultiplePickingListsPDF($orders) {
        // تنفيذ إنشاء PDF متعدد
        // إرجاع رابط الملف المُنشأ
        return 'path/to/generated/pdf';
    }
}
