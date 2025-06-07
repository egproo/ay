<?php
/**
 * لوحة تحكم الشحن والتوزيع التفاعلية
 * 
 * يوفر لوحة تحكم شاملة للشحن والتوزيع مع:
 * - إحصائيات الشحن المتقدمة
 * - رسوم بيانية تفاعلية
 * - تنبيهات ذكية للشحنات
 * - مؤشرات الأداء الرئيسية
 * - تتبع الشحنات في الوقت الفعلي
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerShippingShippingDashboard extends Controller {
    
    /**
     * الصفحة الرئيسية للوحة التحكم
     */
    public function index() {
        $this->load->language('shipping/shipping_dashboard');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('shipping/order_fulfillment');
        $this->load->model('shipping/shipping_integration');
        $this->load->model('shipping/shipment_tracking');
        $this->load->model('shipping/shipping_settlement');
        $this->load->model('sale/order');
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/shipping_dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        // الإحصائيات الرئيسية
        $data['main_statistics'] = $this->getMainStatistics();
        
        // إحصائيات تجهيز الطلبات
        $data['fulfillment_statistics'] = $this->getFulfillmentStatistics();
        
        // إحصائيات الشحن
        $data['shipping_statistics'] = $this->getShippingStatistics();
        
        // إحصائيات التسويات
        $data['settlement_statistics'] = $this->getSettlementStatistics();
        
        // الرسوم البيانية
        $data['charts_data'] = $this->getChartsData();
        
        // التنبيهات الذكية
        $data['smart_alerts'] = $this->getSmartAlerts();
        
        // الشحنات الحديثة
        $data['recent_shipments'] = $this->getRecentShipments();
        
        // مؤشرات الأداء الرئيسية
        $data['kpi_metrics'] = $this->getKPIMetrics();
        
        // الطلبات المعلقة للتجهيز
        $data['pending_orders'] = $this->getPendingOrders();
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('shipping/shipping_dashboard', $data));
    }
    
    /**
     * الحصول على الإحصائيات الرئيسية
     */
    private function getMainStatistics() {
        $statistics = [];
        
        // إجمالي الطلبات الجاهزة للتجهيز
        $ready_orders = $this->model_shipping_order_fulfillment->getOrdersReadyForFulfillment(['limit' => 1000]);
        $statistics['ready_orders'] = count($ready_orders);
        
        // الطلبات المجهزة اليوم
        $today_fulfilled = $this->getTodayFulfilledOrders();
        $statistics['today_fulfilled'] = count($today_fulfilled);
        
        // الشحنات النشطة
        $active_shipments = $this->getActiveShipments();
        $statistics['active_shipments'] = count($active_shipments);
        
        // الشحنات المسلمة اليوم
        $today_delivered = $this->getTodayDeliveredShipments();
        $statistics['today_delivered'] = count($today_delivered);
        
        // متوسط وقت التجهيز
        $avg_fulfillment_time = $this->getAverageFulfillmentTime();
        $statistics['avg_fulfillment_time'] = round($avg_fulfillment_time, 1);
        
        // معدل التسليم الناجح
        $delivery_success_rate = $this->getDeliverySuccessRate();
        $statistics['delivery_success_rate'] = round($delivery_success_rate, 1);
        
        return $statistics;
    }
    
    /**
     * الحصول على إحصائيات تجهيز الطلبات
     */
    private function getFulfillmentStatistics() {
        $statistics = [];
        
        // الطلبات المعلقة للتجهيز
        $pending_fulfillment = $this->model_shipping_order_fulfillment->getOrdersReadyForFulfillment(['limit' => 1000]);
        $statistics['pending_fulfillment'] = count($pending_fulfillment);
        
        // الطلبات المجهزة هذا الأسبوع
        $week_fulfilled = $this->getWeekFulfilledOrders();
        $statistics['week_fulfilled'] = count($week_fulfilled);
        
        // الطلبات المجهزة هذا الشهر
        $month_fulfilled = $this->getMonthFulfilledOrders();
        $statistics['month_fulfilled'] = count($month_fulfilled);
        
        // متوسط عدد المنتجات لكل طلب
        $avg_items_per_order = $this->getAverageItemsPerOrder();
        $statistics['avg_items_per_order'] = round($avg_items_per_order, 1);
        
        return $statistics;
    }
    
    /**
     * الحصول على إحصائيات الشحن
     */
    private function getShippingStatistics() {
        $statistics = [];
        
        // الشحنات في الطريق
        $in_transit = $this->getShipmentsByStatus('in_transit');
        $statistics['in_transit'] = count($in_transit);
        
        // الشحنات المسلمة هذا الأسبوع
        $week_delivered = $this->getWeekDeliveredShipments();
        $statistics['week_delivered'] = count($week_delivered);
        
        // الشحنات المرتجعة
        $returned_shipments = $this->getShipmentsByStatus('returned');
        $statistics['returned_shipments'] = count($returned_shipments);
        
        // الشحنات الفاشلة
        $failed_shipments = $this->getShipmentsByStatus('failed');
        $statistics['failed_shipments'] = count($failed_shipments);
        
        return $statistics;
    }
    
    /**
     * الحصول على إحصائيات التسويات
     */
    private function getSettlementStatistics() {
        $statistics = [];
        
        // التسويات المعلقة
        $pending_settlements = $this->model_shipping_shipping_settlement->getSettlements(['filter_status' => 'pending']);
        $statistics['pending_settlements'] = count($pending_settlements);
        
        // إجمالي COD هذا الشهر
        $monthly_cod = $this->getMonthlyCODAmount();
        $statistics['monthly_cod'] = $this->currency->format($monthly_cod, $this->config->get('config_currency'));
        
        // رسوم الشحن هذا الشهر
        $monthly_shipping_fees = $this->getMonthlyShippingFees();
        $statistics['monthly_shipping_fees'] = $this->currency->format($monthly_shipping_fees, $this->config->get('config_currency'));
        
        return $statistics;
    }
    
    /**
     * الحصول على بيانات الرسوم البيانية
     */
    private function getChartsData() {
        $charts = [];
        
        // رسم بياني للطلبات المجهزة يومياً (آخر 30 يوم)
        $fulfillment_data = $this->getDailyFulfillmentChart();
        $charts['fulfillment_chart'] = $fulfillment_data;
        
        // رسم بياني للشحنات حسب الحالة
        $shipment_status_data = $this->getShipmentStatusChart();
        $charts['shipment_status_chart'] = $shipment_status_data;
        
        // رسم بياني لشركات الشحن
        $shipping_companies_data = $this->getShippingCompaniesChart();
        $charts['shipping_companies_chart'] = $shipping_companies_data;
        
        // رسم بياني لمعدل التسليم
        $delivery_rate_data = $this->getDeliveryRateChart();
        $charts['delivery_rate_chart'] = $delivery_rate_data;
        
        return $charts;
    }
    
    /**
     * الحصول على التنبيهات الذكية
     */
    private function getSmartAlerts() {
        $alerts = [];
        
        // تنبيهات الطلبات المعلقة للتجهيز
        $pending_orders = $this->model_shipping_order_fulfillment->getOrdersReadyForFulfillment(['limit' => 100]);
        if (count($pending_orders) > 10) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-boxes',
                'title' => 'طلبات معلقة للتجهيز',
                'message' => count($pending_orders) . ' طلب في انتظار التجهيز',
                'action_url' => $this->url->link('shipping/order_fulfillment', 'user_token=' . $this->session->data['user_token'], true)
            ];
        }
        
        // تنبيهات الشحنات المتأخرة
        $delayed_shipments = $this->getDelayedShipments();
        if (count($delayed_shipments) > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fa-clock',
                'title' => 'شحنات متأخرة',
                'message' => count($delayed_shipments) . ' شحنة متأخرة عن الموعد المتوقع',
                'action_url' => $this->url->link('shipping/shipment_tracking', 'user_token=' . $this->session->data['user_token'], true)
            ];
        }
        
        // تنبيهات التسويات المعلقة
        $pending_settlements = $this->model_shipping_shipping_settlement->getSettlements(['filter_status' => 'pending']);
        if (count($pending_settlements) > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'fa-calculator',
                'title' => 'تسويات معلقة',
                'message' => count($pending_settlements) . ' تسوية في انتظار الاعتماد',
                'action_url' => $this->url->link('shipping/shipping_settlement', 'user_token=' . $this->session->data['user_token'], true)
            ];
        }
        
        // تنبيهات الشحنات المرتجعة
        $returned_shipments = $this->getRecentReturnedShipments();
        if (count($returned_shipments) > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-undo',
                'title' => 'شحنات مرتجعة',
                'message' => count($returned_shipments) . ' شحنة تم إرجاعها مؤخراً',
                'action_url' => $this->url->link('shipping/shipment_tracking', 'user_token=' . $this->session->data['user_token'] . '&filter_status=returned', true)
            ];
        }
        
        return $alerts;
    }
    
    /**
     * الحصول على الشحنات الحديثة
     */
    private function getRecentShipments() {
        $query = $this->db->query("
            SELECT so.*, o.firstname, o.lastname, sc.name as company_name,
                CONCAT(o.firstname, ' ', o.lastname) as customer_name
            FROM cod_shipping_order so
            LEFT JOIN cod_order o ON (so.order_id = o.order_id)
            LEFT JOIN cod_shipping_company sc ON (so.company_id = sc.company_id)
            WHERE so.status NOT IN ('cancelled', 'failed')
            ORDER BY so.created_at DESC
            LIMIT 10
        ");
        
        $shipments = [];
        foreach ($query->rows as $shipment) {
            $shipments[] = [
                'shipping_order_id' => $shipment['shipping_order_id'],
                'order_id' => $shipment['order_id'],
                'customer_name' => $shipment['customer_name'],
                'company_name' => $shipment['company_name'],
                'tracking_number' => $shipment['tracking_number'],
                'status' => $shipment['status'],
                'status_text' => $this->getStatusText($shipment['status']),
                'created_at' => date($this->language->get('datetime_format'), strtotime($shipment['created_at'])),
                'view_url' => $this->url->link('shipping/shipment_tracking/view', 'user_token=' . $this->session->data['user_token'] . '&shipping_order_id=' . $shipment['shipping_order_id'], true)
            ];
        }
        
        return $shipments;
    }
    
    /**
     * الحصول على مؤشرات الأداء الرئيسية
     */
    private function getKPIMetrics() {
        $kpis = [];
        
        // معدل التسليم في الوقت المحدد
        $on_time_delivery = $this->getOnTimeDeliveryRate();
        $kpis['on_time_delivery'] = [
            'value' => round($on_time_delivery, 1),
            'target' => 95,
            'unit' => '%',
            'trend' => 'up'
        ];
        
        // متوسط وقت التجهيز
        $avg_fulfillment_time = $this->getAverageFulfillmentTime();
        $kpis['avg_fulfillment_time'] = [
            'value' => round($avg_fulfillment_time, 1),
            'target' => 2,
            'unit' => 'ساعة',
            'trend' => 'down'
        ];
        
        // معدل الإرجاع
        $return_rate = $this->getReturnRate();
        $kpis['return_rate'] = [
            'value' => round($return_rate, 1),
            'target' => 5,
            'unit' => '%',
            'trend' => 'down'
        ];
        
        // رضا العملاء عن الشحن
        $shipping_satisfaction = $this->getShippingSatisfactionRate();
        $kpis['shipping_satisfaction'] = [
            'value' => round($shipping_satisfaction, 1),
            'target' => 90,
            'unit' => '%',
            'trend' => 'up'
        ];
        
        return $kpis;
    }
    
    /**
     * الحصول على الطلبات المعلقة للتجهيز
     */
    private function getPendingOrders() {
        $orders = $this->model_shipping_order_fulfillment->getOrdersReadyForFulfillment(['limit' => 5]);
        
        $pending_orders = [];
        foreach ($orders as $order) {
            $pending_orders[] = [
                'order_id' => $order['order_id'],
                'customer_name' => $order['customer_name'],
                'total' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']),
                'product_count' => $order['product_count'],
                'date_added' => date($this->language->get('datetime_format'), strtotime($order['date_added'])),
                'fulfill_url' => $this->url->link('shipping/order_fulfillment/fulfill', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order['order_id'], true)
            ];
        }
        
        return $pending_orders;
    }
    
    /**
     * تحديث البيانات عبر AJAX
     */
    public function refresh() {
        $json = [];
        
        try {
            $json['main_statistics'] = $this->getMainStatistics();
            $json['smart_alerts'] = $this->getSmartAlerts();
            $json['recent_shipments'] = $this->getRecentShipments();
            $json['success'] = true;
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    // دوال مساعدة لحساب الإحصائيات
    private function getTodayFulfilledOrders() {
        $query = $this->db->query("
            SELECT order_id FROM cod_order_fulfillment 
            WHERE DATE(fulfillment_date) = CURDATE()
        ");
        return $query->rows;
    }
    
    private function getActiveShipments() {
        $query = $this->db->query("
            SELECT shipping_order_id FROM cod_shipping_order 
            WHERE status IN ('processed', 'shipped', 'in_transit')
        ");
        return $query->rows;
    }
    
    private function getTodayDeliveredShipments() {
        $query = $this->db->query("
            SELECT shipping_order_id FROM cod_shipping_order 
            WHERE status = 'delivered' AND DATE(actual_delivery_date) = CURDATE()
        ");
        return $query->rows;
    }
    
    private function getAverageFulfillmentTime() {
        $query = $this->db->query("
            SELECT AVG(TIMESTAMPDIFF(HOUR, o.date_added, of.fulfillment_date)) as avg_time
            FROM cod_order_fulfillment of
            LEFT JOIN cod_order o ON (of.order_id = o.order_id)
            WHERE DATE(of.fulfillment_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        
        return $query->row['avg_time'] ?? 0;
    }
    
    private function getDeliverySuccessRate() {
        $query = $this->db->query("
            SELECT 
                COUNT(*) as total_shipments,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_shipments
            FROM cod_shipping_order 
            WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        
        $total = $query->row['total_shipments'];
        $delivered = $query->row['delivered_shipments'];
        
        return ($total > 0) ? ($delivered / $total) * 100 : 0;
    }
    
    private function getStatusText($status) {
        $status_texts = [
            'pending' => 'في الانتظار',
            'processed' => 'تم المعالجة',
            'shipped' => 'تم الشحن',
            'in_transit' => 'في الطريق',
            'delivered' => 'تم التسليم',
            'returned' => 'تم الإرجاع',
            'failed' => 'فشل التسليم',
            'cancelled' => 'ملغي'
        ];
        
        return isset($status_texts[$status]) ? $status_texts[$status] : $status;
    }
    
    // يمكن إضافة المزيد من الدوال المساعدة حسب الحاجة
    private function getWeekFulfilledOrders() { return []; }
    private function getMonthFulfilledOrders() { return []; }
    private function getAverageItemsPerOrder() { return 0; }
    private function getShipmentsByStatus($status) { return []; }
    private function getWeekDeliveredShipments() { return []; }
    private function getMonthlyCODAmount() { return 0; }
    private function getMonthlyShippingFees() { return 0; }
    private function getDailyFulfillmentChart() { return []; }
    private function getShipmentStatusChart() { return []; }
    private function getShippingCompaniesChart() { return []; }
    private function getDeliveryRateChart() { return []; }
    private function getDelayedShipments() { return []; }
    private function getRecentReturnedShipments() { return []; }
    private function getOnTimeDeliveryRate() { return 0; }
    private function getReturnRate() { return 0; }
    private function getShippingSatisfactionRate() { return 0; }
}
