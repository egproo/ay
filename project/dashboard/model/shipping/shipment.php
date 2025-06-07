<?php
/**
 * Model: Shipment Management
 * نموذج إدارة الشحنات المتقدم
 *
 * يوفر جميع العمليات المتعلقة بإدارة الشحنات والتتبع
 *
 * @package    AYM ERP
 * @author     AYM ERP Development Team
 * @copyright  2024 AYM ERP
 * @license    Commercial License
 * @version    1.0.0
 * @link       https://aym-erp.com
 * @since      1.0.0
 */

class ModelShippingShipment extends Model {

    /**
     * إضافة شحنة جديدة
     */
    public function addShipment($data) {
        $shipment_number = $this->generateShipmentNumber();

        $this->db->query("INSERT INTO " . DB_PREFIX . "shipment SET
            shipment_number = '" . $this->db->escape($shipment_number) . "',
            order_id = '" . (int)$data['order_id'] . "',
            customer_id = '" . (int)$data['customer_id'] . "',
            carrier_id = '" . (int)$data['carrier_id'] . "',
            tracking_number = '" . $this->db->escape($data['tracking_number']) . "',
            shipping_method = '" . $this->db->escape($data['shipping_method']) . "',
            weight = '" . (float)$data['weight'] . "',
            dimensions = '" . $this->db->escape($data['dimensions']) . "',
            insurance_value = '" . (float)$data['insurance_value'] . "',
            delivery_instructions = '" . $this->db->escape($data['delivery_instructions']) . "',
            pickup_date = '" . $this->db->escape($data['pickup_date']) . "',
            estimated_delivery = '" . $this->db->escape($data['estimated_delivery']) . "',
            shipping_cost = '" . (float)$data['shipping_cost'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            date_added = NOW(),
            date_modified = NOW()");

        $shipment_id = $this->db->getLastId();

        // إضافة سجل في تاريخ الحالة
        $this->addStatusHistory($shipment_id, $data['status'], 'Shipment created');

        // إرسال إشعار
        $this->load->model('notification/notification');
        $this->model_notification_notification->addNotification(array(
            'type' => 'shipment_created',
            'title' => 'New Shipment Created',
            'message' => 'Shipment #' . $shipment_number . ' has been created',
            'user_id' => $this->user->getId(),
            'reference_id' => $shipment_id,
            'reference_type' => 'shipment'
        ));

        return $shipment_id;
    }

    /**
     * تعديل شحنة موجودة
     */
    public function editShipment($shipment_id, $data) {
        $old_shipment = $this->getShipment($shipment_id);

        $this->db->query("UPDATE " . DB_PREFIX . "shipment SET
            order_id = '" . (int)$data['order_id'] . "',
            carrier_id = '" . (int)$data['carrier_id'] . "',
            tracking_number = '" . $this->db->escape($data['tracking_number']) . "',
            shipping_method = '" . $this->db->escape($data['shipping_method']) . "',
            weight = '" . (float)$data['weight'] . "',
            dimensions = '" . $this->db->escape($data['dimensions']) . "',
            insurance_value = '" . (float)$data['insurance_value'] . "',
            delivery_instructions = '" . $this->db->escape($data['delivery_instructions']) . "',
            pickup_date = '" . $this->db->escape($data['pickup_date']) . "',
            estimated_delivery = '" . $this->db->escape($data['estimated_delivery']) . "',
            shipping_cost = '" . (float)$data['shipping_cost'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            date_modified = NOW()
            WHERE shipment_id = '" . (int)$shipment_id . "'");

        // إضافة سجل في تاريخ الحالة إذا تغيرت الحالة
        if ($old_shipment['status'] != $data['status']) {
            $this->addStatusHistory($shipment_id, $data['status'], 'Status updated');
        }

        return true;
    }

    /**
     * حذف شحنة
     */
    public function deleteShipment($shipment_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "shipment WHERE shipment_id = '" . (int)$shipment_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "shipment_status_history WHERE shipment_id = '" . (int)$shipment_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "shipment_tracking WHERE shipment_id = '" . (int)$shipment_id . "'");

        return true;
    }

    /**
     * الحصول على شحنة بالمعرف
     */
    public function getShipment($shipment_id) {
        $query = $this->db->query("SELECT s.*, o.order_id as order_number,
            CONCAT(c.firstname, ' ', c.lastname) as customer_name,
            car.name as carrier_name
            FROM " . DB_PREFIX . "shipment s
            LEFT JOIN " . DB_PREFIX . "order o ON s.order_id = o.order_id
            LEFT JOIN " . DB_PREFIX . "customer c ON s.customer_id = c.customer_id
            LEFT JOIN " . DB_PREFIX . "carrier car ON s.carrier_id = car.carrier_id
            WHERE s.shipment_id = '" . (int)$shipment_id . "'");

        return $query->row;
    }

    /**
     * الحصول على الشحنات مع الفلاتر
     */
    public function getShipments($data = array()) {
        $sql = "SELECT s.*, o.order_id as order_number,
            CONCAT(c.firstname, ' ', c.lastname) as customer_name,
            car.name as carrier_name
            FROM " . DB_PREFIX . "shipment s
            LEFT JOIN " . DB_PREFIX . "order o ON s.order_id = o.order_id
            LEFT JOIN " . DB_PREFIX . "customer c ON s.customer_id = c.customer_id
            LEFT JOIN " . DB_PREFIX . "carrier car ON s.carrier_id = car.carrier_id
            WHERE 1=1";

        if (!empty($data['filter_shipment_number'])) {
            $sql .= " AND s.shipment_number LIKE '%" . $this->db->escape($data['filter_shipment_number']) . "%'";
        }

        if (!empty($data['filter_order_number'])) {
            $sql .= " AND o.order_id LIKE '%" . $this->db->escape($data['filter_order_number']) . "%'";
        }

        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_carrier_id'])) {
            $sql .= " AND s.carrier_id = '" . (int)$data['filter_carrier_id'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND s.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(s.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(s.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sort_data = array(
            's.shipment_number',
            'o.order_id',
            'customer_name',
            'car.name',
            's.status',
            's.date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY s.date_added";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على إجمالي عدد الشحنات
     */
    public function getTotalShipments($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "shipment s
            LEFT JOIN " . DB_PREFIX . "order o ON s.order_id = o.order_id
            LEFT JOIN " . DB_PREFIX . "customer c ON s.customer_id = c.customer_id
            LEFT JOIN " . DB_PREFIX . "carrier car ON s.carrier_id = car.carrier_id
            WHERE 1=1";

        if (!empty($data['filter_shipment_number'])) {
            $sql .= " AND s.shipment_number LIKE '%" . $this->db->escape($data['filter_shipment_number']) . "%'";
        }

        if (!empty($data['filter_order_number'])) {
            $sql .= " AND o.order_id LIKE '%" . $this->db->escape($data['filter_order_number']) . "%'";
        }

        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_carrier_id'])) {
            $sql .= " AND s.carrier_id = '" . (int)$data['filter_carrier_id'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND s.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(s.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(s.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * إنشاء رقم شحنة جديد
     */
    private function generateShipmentNumber() {
        $prefix = 'SH';
        $year = date('Y');
        $month = date('m');

        // البحث عن آخر رقم في نفس الشهر
        $query = $this->db->query("SELECT shipment_number FROM " . DB_PREFIX . "shipment
            WHERE shipment_number LIKE '" . $prefix . $year . $month . "%'
            ORDER BY shipment_id DESC LIMIT 1");

        if ($query->num_rows) {
            $last_number = $query->row['shipment_number'];
            $sequence = (int)substr($last_number, -4) + 1;
        } else {
            $sequence = 1;
        }

        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * إضافة سجل في تاريخ الحالة
     */
    public function addStatusHistory($shipment_id, $status, $comment = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "shipment_status_history SET
            shipment_id = '" . (int)$shipment_id . "',
            status = '" . $this->db->escape($status) . "',
            comment = '" . $this->db->escape($comment) . "',
            user_id = '" . (int)$this->user->getId() . "',
            date_added = NOW()");

        return $this->db->getLastId();
    }

    /**
     * الحصول على حالات الشحن
     */
    public function getShipmentStatuses() {
        return array(
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'in_transit' => 'In Transit',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'returned' => 'Returned',
            'cancelled' => 'Cancelled'
        );
    }

    /**
     * الحصول على إحصائيات الشحنات
     */
    public function getShipmentStatistics() {
        $statistics = array();

        // إجمالي الشحنات
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "shipment");
        $statistics['total_shipments'] = $query->row['total'];

        // الشحنات المعلقة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "shipment WHERE status = 'pending'");
        $statistics['pending_shipments'] = $query->row['total'];

        // الشحنات قيد التنفيذ
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "shipment WHERE status IN ('processing', 'shipped', 'in_transit')");
        $statistics['active_shipments'] = $query->row['total'];

        // الشحنات المكتملة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "shipment WHERE status = 'delivered'");
        $statistics['completed_shipments'] = $query->row['total'];

        // إجمالي تكلفة الشحن
        $query = $this->db->query("SELECT SUM(shipping_cost) as total FROM " . DB_PREFIX . "shipment");
        $statistics['total_shipping_cost'] = $query->row['total'] ?: 0;

        // متوسط تكلفة الشحن
        $query = $this->db->query("SELECT AVG(shipping_cost) as average FROM " . DB_PREFIX . "shipment");
        $statistics['average_shipping_cost'] = $query->row['average'] ?: 0;

        return $statistics;
    }

    /**
     * تحديث حالة الشحنة
     */
    public function updateShipmentStatus($shipment_id, $status, $comment = '') {
        $this->db->query("UPDATE " . DB_PREFIX . "shipment SET
            status = '" . $this->db->escape($status) . "',
            date_modified = NOW()
            WHERE shipment_id = '" . (int)$shipment_id . "'");

        // إضافة سجل في تاريخ الحالة
        $this->addStatusHistory($shipment_id, $status, $comment);

        // إرسال إشعار للعميل إذا كانت الحالة مهمة
        if (in_array($status, array('shipped', 'delivered', 'returned'))) {
            $this->sendCustomerNotification($shipment_id, 'status_update');
        }

        return $this->db->countAffected() > 0;
    }

    /**
     * الحصول على معلومات التتبع
     */
    public function getTrackingInfo($shipment_id) {
        $shipment = $this->getShipment($shipment_id);

        if (!$shipment || empty($shipment['tracking_number'])) {
            return false;
        }

        // الحصول على تاريخ الحالة
        $status_history = $this->getStatusHistory($shipment_id);

        // محاولة الحصول على معلومات التتبع من شركة الشحن
        $carrier_tracking = $this->getCarrierTrackingInfo($shipment['carrier_id'], $shipment['tracking_number']);

        return array(
            'shipment_info' => $shipment,
            'status_history' => $status_history,
            'carrier_tracking' => $carrier_tracking,
            'current_status' => $shipment['status'],
            'tracking_number' => $shipment['tracking_number']
        );
    }

    /**
     * الحصول على تاريخ الحالة
     */
    public function getStatusHistory($shipment_id) {
        $query = $this->db->query("SELECT ssh.*, u.username
            FROM " . DB_PREFIX . "shipment_status_history ssh
            LEFT JOIN " . DB_PREFIX . "user u ON ssh.user_id = u.user_id
            WHERE ssh.shipment_id = '" . (int)$shipment_id . "'
            ORDER BY ssh.date_added ASC");

        return $query->rows;
    }

    /**
     * الحصول على معلومات التتبع من شركة الشحن
     */
    private function getCarrierTrackingInfo($carrier_id, $tracking_number) {
        // هذه دالة يمكن توسيعها للتكامل مع APIs شركات الشحن المختلفة
        $this->load->model('shipping/carrier');
        $carrier_info = $this->model_shipping_carrier->getCarrier($carrier_id);

        if (!$carrier_info) {
            return false;
        }

        // مثال على التكامل مع API شركة شحن
        switch ($carrier_info['code']) {
            case 'dhl':
                return $this->getDHLTracking($tracking_number);
            case 'fedex':
                return $this->getFedExTracking($tracking_number);
            case 'ups':
                return $this->getUPSTracking($tracking_number);
            default:
                return array(
                    'status' => 'tracking_not_available',
                    'message' => 'Tracking not available for this carrier'
                );
        }
    }

    /**
     * تحديث معلومات التتبع من شركة الشحن
     */
    public function updateTrackingFromCarrier($shipment_id) {
        $shipment = $this->getShipment($shipment_id);

        if (!$shipment || empty($shipment['tracking_number'])) {
            return false;
        }

        $tracking_info = $this->getCarrierTrackingInfo($shipment['carrier_id'], $shipment['tracking_number']);

        if ($tracking_info && isset($tracking_info['status'])) {
            // تحديث الحالة إذا تغيرت
            if ($tracking_info['status'] != $shipment['status']) {
                $this->updateShipmentStatus($shipment_id, $tracking_info['status'], 'Updated from carrier tracking');
            }

            // حفظ معلومات التتبع
            $this->saveTrackingData($shipment_id, $tracking_info);

            return $tracking_info;
        }

        return false;
    }

    /**
     * حفظ بيانات التتبع
     */
    private function saveTrackingData($shipment_id, $tracking_data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "shipment_tracking SET
            shipment_id = '" . (int)$shipment_id . "',
            tracking_data = '" . $this->db->escape(json_encode($tracking_data)) . "',
            date_added = NOW()
            ON DUPLICATE KEY UPDATE
            tracking_data = '" . $this->db->escape(json_encode($tracking_data)) . "',
            date_modified = NOW()");
    }

    /**
     * إرسال إشعار للعميل
     */
    public function sendCustomerNotification($shipment_id, $notification_type) {
        $shipment = $this->getShipment($shipment_id);

        if (!$shipment) {
            return false;
        }

        $this->load->model('customer/customer');
        $customer_info = $this->model_customer_customer->getCustomer($shipment['customer_id']);

        if (!$customer_info || empty($customer_info['email'])) {
            return false;
        }

        // إعداد محتوى الإشعار
        $subject = '';
        $message = '';

        switch ($notification_type) {
            case 'shipped':
                $subject = 'Your order has been shipped';
                $message = 'Your order #' . $shipment['order_number'] . ' has been shipped. Tracking number: ' . $shipment['tracking_number'];
                break;
            case 'delivered':
                $subject = 'Your order has been delivered';
                $message = 'Your order #' . $shipment['order_number'] . ' has been delivered successfully.';
                break;
            case 'status_update':
                $subject = 'Shipment status update';
                $message = 'Your shipment #' . $shipment['shipment_number'] . ' status has been updated to: ' . $shipment['status'];
                break;
        }

        if ($subject && $message) {
            $this->load->model('mail/mail');
            return $this->model_mail_mail->send($customer_info['email'], $subject, $message);
        }

        return false;
    }

    /**
     * إنشاء ملصق الشحن
     */
    public function generateShippingLabel($shipment_id) {
        $shipment = $this->getShipment($shipment_id);

        if (!$shipment) {
            return false;
        }

        // الحصول على معلومات الطلب
        $this->load->model('sale/order');
        $order_info = $this->model_sale_order->getOrder($shipment['order_id']);

        if (!$order_info) {
            return false;
        }

        // إعداد بيانات الملصق
        $label_data = array(
            'shipment_number' => $shipment['shipment_number'],
            'tracking_number' => $shipment['tracking_number'],
            'carrier_name' => $shipment['carrier_name'],
            'sender_address' => $this->config->get('config_address'),
            'recipient_address' => $order_info['shipping_address_1'] . "\n" .
                                 $order_info['shipping_city'] . ', ' . $order_info['shipping_zone'] . "\n" .
                                 $order_info['shipping_country'] . ' ' . $order_info['shipping_postcode'],
            'weight' => $shipment['weight'],
            'dimensions' => $shipment['dimensions'],
            'barcode' => true
        );

        return $label_data;
    }

    /**
     * الحصول على شحنة بمعرف الطلب
     */
    public function getShipmentByOrderId($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "shipment WHERE order_id = '" . (int)$order_id . "'");
        return $query->row;
    }

    /**
     * الحصول على إحصائيات مفصلة
     */
    public function getDetailedStatistics() {
        $statistics = array();

        // إحصائيات عامة
        $statistics['general'] = $this->getShipmentStatistics();

        // إحصائيات حسب الحالة
        $query = $this->db->query("SELECT status, COUNT(*) as count
            FROM " . DB_PREFIX . "shipment
            GROUP BY status");

        $statistics['by_status'] = array();
        foreach ($query->rows as $row) {
            $statistics['by_status'][$row['status']] = $row['count'];
        }

        // إحصائيات حسب شركة الشحن
        $query = $this->db->query("SELECT c.name, COUNT(*) as count, AVG(s.shipping_cost) as avg_cost
            FROM " . DB_PREFIX . "shipment s
            LEFT JOIN " . DB_PREFIX . "carrier c ON s.carrier_id = c.carrier_id
            GROUP BY s.carrier_id");

        $statistics['by_carrier'] = $query->rows;

        // إحصائيات شهرية
        $query = $this->db->query("SELECT
            YEAR(date_added) as year,
            MONTH(date_added) as month,
            COUNT(*) as count,
            SUM(shipping_cost) as total_cost
            FROM " . DB_PREFIX . "shipment
            WHERE date_added >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY YEAR(date_added), MONTH(date_added)
            ORDER BY year DESC, month DESC");

        $statistics['monthly'] = $query->rows;

        return $statistics;
    }

    /**
     * الحصول على بيانات الأداء
     */
    public function getPerformanceData() {
        $performance = array();

        // متوسط وقت التسليم
        $query = $this->db->query("SELECT AVG(DATEDIFF(
            (SELECT date_added FROM " . DB_PREFIX . "shipment_status_history
             WHERE shipment_id = s.shipment_id AND status = 'delivered' LIMIT 1),
            s.date_added
        )) as avg_delivery_time
        FROM " . DB_PREFIX . "shipment s
        WHERE s.status = 'delivered'");

        $performance['avg_delivery_time'] = $query->row['avg_delivery_time'] ?: 0;

        // معدل التسليم في الوقت المحدد
        $query = $this->db->query("SELECT
            COUNT(*) as total_delivered,
            SUM(CASE WHEN
                (SELECT date_added FROM " . DB_PREFIX . "shipment_status_history
                 WHERE shipment_id = s.shipment_id AND status = 'delivered' LIMIT 1) <= s.estimated_delivery
                THEN 1 ELSE 0 END) as on_time_deliveries
            FROM " . DB_PREFIX . "shipment s
            WHERE s.status = 'delivered' AND s.estimated_delivery IS NOT NULL");

        $total = $query->row['total_delivered'];
        $on_time = $query->row['on_time_deliveries'];
        $performance['on_time_delivery_rate'] = $total > 0 ? ($on_time / $total) * 100 : 0;

        // معدل الإرجاع
        $query = $this->db->query("SELECT
            COUNT(*) as total_shipments,
            SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_shipments
            FROM " . DB_PREFIX . "shipment");

        $total = $query->row['total_shipments'];
        $returned = $query->row['returned_shipments'];
        $performance['return_rate'] = $total > 0 ? ($returned / $total) * 100 : 0;

        return $performance;
    }

    /**
     * تحليل شركات الشحن
     */
    public function getCarrierAnalysis() {
        $query = $this->db->query("SELECT
            c.name as carrier_name,
            COUNT(*) as total_shipments,
            AVG(s.shipping_cost) as avg_cost,
            SUM(CASE WHEN s.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
            AVG(CASE WHEN s.status = 'delivered' THEN
                DATEDIFF((SELECT date_added FROM " . DB_PREFIX . "shipment_status_history
                         WHERE shipment_id = s.shipment_id AND status = 'delivered' LIMIT 1), s.date_added)
                ELSE NULL END) as avg_delivery_time,
            SUM(CASE WHEN s.status = 'returned' THEN 1 ELSE 0 END) as returned_count
            FROM " . DB_PREFIX . "shipment s
            LEFT JOIN " . DB_PREFIX . "carrier c ON s.carrier_id = c.carrier_id
            GROUP BY s.carrier_id
            ORDER BY total_shipments DESC");

        $analysis = array();
        foreach ($query->rows as $row) {
            $delivery_rate = $row['total_shipments'] > 0 ? ($row['delivered_count'] / $row['total_shipments']) * 100 : 0;
            $return_rate = $row['total_shipments'] > 0 ? ($row['returned_count'] / $row['total_shipments']) * 100 : 0;

            $analysis[] = array(
                'carrier_name' => $row['carrier_name'],
                'total_shipments' => $row['total_shipments'],
                'avg_cost' => $row['avg_cost'],
                'delivery_rate' => $delivery_rate,
                'return_rate' => $return_rate,
                'avg_delivery_time' => $row['avg_delivery_time']
            );
        }

        return $analysis;
    }

    /**
     * تحليل التكاليف
     */
    public function getCostAnalysis($date_from = '', $date_to = '', $carrier_id = 0) {
        $where_conditions = array();

        if ($date_from) {
            $where_conditions[] = "s.date_added >= '" . $this->db->escape($date_from) . "'";
        }

        if ($date_to) {
            $where_conditions[] = "s.date_added <= '" . $this->db->escape($date_to) . "'";
        }

        if ($carrier_id) {
            $where_conditions[] = "s.carrier_id = '" . (int)$carrier_id . "'";
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        // إجمالي التكاليف
        $query = $this->db->query("SELECT
            COUNT(*) as total_shipments,
            SUM(shipping_cost) as total_cost,
            AVG(shipping_cost) as avg_cost,
            MIN(shipping_cost) as min_cost,
            MAX(shipping_cost) as max_cost
            FROM " . DB_PREFIX . "shipment s " . $where_clause);

        $cost_summary = $query->row;

        // التكاليف حسب شركة الشحن
        $query = $this->db->query("SELECT
            c.name as carrier_name,
            COUNT(*) as shipment_count,
            SUM(s.shipping_cost) as total_cost,
            AVG(s.shipping_cost) as avg_cost
            FROM " . DB_PREFIX . "shipment s
            LEFT JOIN " . DB_PREFIX . "carrier c ON s.carrier_id = c.carrier_id
            " . $where_clause . "
            GROUP BY s.carrier_id
            ORDER BY total_cost DESC");

        $cost_by_carrier = $query->rows;

        // التكاليف الشهرية
        $query = $this->db->query("SELECT
            YEAR(s.date_added) as year,
            MONTH(s.date_added) as month,
            COUNT(*) as shipment_count,
            SUM(s.shipping_cost) as monthly_cost,
            AVG(s.shipping_cost) as avg_monthly_cost
            FROM " . DB_PREFIX . "shipment s
            " . $where_clause . "
            GROUP BY YEAR(s.date_added), MONTH(s.date_added)
            ORDER BY year DESC, month DESC");

        $monthly_costs = $query->rows;

        return array(
            'summary' => $cost_summary,
            'by_carrier' => $cost_by_carrier,
            'monthly' => $monthly_costs,
            'period' => array(
                'from' => $date_from,
                'to' => $date_to,
                'carrier_id' => $carrier_id
            )
        );
    }

    /**
     * APIs شركات الشحن - DHL
     */
    private function getDHLTracking($tracking_number) {
        // مثال على تكامل DHL API
        // يجب استبدال هذا بالتكامل الفعلي مع DHL API
        return array(
            'status' => 'in_transit',
            'message' => 'Package is in transit',
            'location' => 'Distribution Center',
            'estimated_delivery' => date('Y-m-d', strtotime('+2 days')),
            'events' => array(
                array(
                    'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'status' => 'shipped',
                    'location' => 'Origin Facility',
                    'description' => 'Package shipped from origin'
                ),
                array(
                    'date' => date('Y-m-d H:i:s'),
                    'status' => 'in_transit',
                    'location' => 'Distribution Center',
                    'description' => 'Package arrived at distribution center'
                )
            )
        );
    }

    /**
     * APIs شركات الشحن - FedEx
     */
    private function getFedExTracking($tracking_number) {
        // مثال على تكامل FedEx API
        return array(
            'status' => 'out_for_delivery',
            'message' => 'Out for delivery',
            'location' => 'Local Facility',
            'estimated_delivery' => date('Y-m-d'),
            'events' => array(
                array(
                    'date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    'status' => 'shipped',
                    'location' => 'Origin',
                    'description' => 'Shipment information sent to FedEx'
                ),
                array(
                    'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'status' => 'in_transit',
                    'location' => 'Sort Facility',
                    'description' => 'Arrived at FedEx location'
                ),
                array(
                    'date' => date('Y-m-d H:i:s'),
                    'status' => 'out_for_delivery',
                    'location' => 'Local Facility',
                    'description' => 'On FedEx vehicle for delivery'
                )
            )
        );
    }

    /**
     * APIs شركات الشحن - UPS
     */
    private function getUPSTracking($tracking_number) {
        // مثال على تكامل UPS API
        return array(
            'status' => 'delivered',
            'message' => 'Delivered',
            'location' => 'Front Door',
            'delivery_date' => date('Y-m-d H:i:s'),
            'events' => array(
                array(
                    'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
                    'status' => 'shipped',
                    'location' => 'Origin Scan',
                    'description' => 'Origin Scan'
                ),
                array(
                    'date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    'status' => 'in_transit',
                    'location' => 'Departure Scan',
                    'description' => 'Departure Scan'
                ),
                array(
                    'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'status' => 'in_transit',
                    'location' => 'Arrival Scan',
                    'description' => 'Arrival Scan'
                ),
                array(
                    'date' => date('Y-m-d H:i:s'),
                    'status' => 'delivered',
                    'location' => 'Front Door',
                    'description' => 'Delivered'
                )
            )
        );
    }

    /**
     * حساب تكلفة الشحن المقدرة
     */
    public function calculateShippingCost($carrier_id, $weight, $dimensions, $destination) {
        $this->load->model('shipping/carrier');
        $carrier_info = $this->model_shipping_carrier->getCarrier($carrier_id);

        if (!$carrier_info) {
            return false;
        }

        // حساب التكلفة بناءً على الوزن والأبعاد
        $base_cost = (float)$carrier_info['base_cost'];
        $weight_rate = (float)$carrier_info['weight_rate'];
        $dimension_rate = (float)$carrier_info['dimension_rate'];

        // حساب التكلفة الأساسية
        $cost = $base_cost;

        // إضافة تكلفة الوزن
        if ($weight > 0) {
            $cost += $weight * $weight_rate;
        }

        // إضافة تكلفة الأبعاد (إذا كانت متوفرة)
        if (!empty($dimensions)) {
            $dimension_parts = explode('x', $dimensions);
            if (count($dimension_parts) == 3) {
                $volume = (float)$dimension_parts[0] * (float)$dimension_parts[1] * (float)$dimension_parts[2];
                $cost += $volume * $dimension_rate;
            }
        }

        // تطبيق عوامل إضافية حسب الوجهة
        $destination_factor = $this->getDestinationFactor($destination);
        $cost *= $destination_factor;

        return round($cost, 2);
    }

    /**
     * الحصول على عامل الوجهة
     */
    private function getDestinationFactor($destination) {
        // يمكن تخصيص هذا حسب المناطق الجغرافية
        $factors = array(
            'local' => 1.0,
            'domestic' => 1.2,
            'international' => 1.8
        );

        return isset($factors[$destination]) ? $factors[$destination] : 1.0;
    }

    /**
     * البحث المتقدم في الشحنات
     */
    public function searchShipments($search_criteria) {
        $sql = "SELECT s.*, o.order_id as order_number,
            CONCAT(c.firstname, ' ', c.lastname) as customer_name,
            car.name as carrier_name
            FROM " . DB_PREFIX . "shipment s
            LEFT JOIN " . DB_PREFIX . "order o ON s.order_id = o.order_id
            LEFT JOIN " . DB_PREFIX . "customer c ON s.customer_id = c.customer_id
            LEFT JOIN " . DB_PREFIX . "carrier car ON s.carrier_id = car.carrier_id
            WHERE 1=1";

        $conditions = array();

        if (!empty($search_criteria['keyword'])) {
            $keyword = $this->db->escape($search_criteria['keyword']);
            $conditions[] = "(s.shipment_number LIKE '%{$keyword}%' OR
                           s.tracking_number LIKE '%{$keyword}%' OR
                           CONCAT(c.firstname, ' ', c.lastname) LIKE '%{$keyword}%')";
        }

        if (!empty($search_criteria['status_list'])) {
            $statuses = array_map(array($this->db, 'escape'), $search_criteria['status_list']);
            $conditions[] = "s.status IN ('" . implode("','", $statuses) . "')";
        }

        if (!empty($search_criteria['carrier_list'])) {
            $carriers = array_map('intval', $search_criteria['carrier_list']);
            $conditions[] = "s.carrier_id IN (" . implode(',', $carriers) . ")";
        }

        if (!empty($search_criteria['weight_min'])) {
            $conditions[] = "s.weight >= '" . (float)$search_criteria['weight_min'] . "'";
        }

        if (!empty($search_criteria['weight_max'])) {
            $conditions[] = "s.weight <= '" . (float)$search_criteria['weight_max'] . "'";
        }

        if (!empty($search_criteria['cost_min'])) {
            $conditions[] = "s.shipping_cost >= '" . (float)$search_criteria['cost_min'] . "'";
        }

        if (!empty($search_criteria['cost_max'])) {
            $conditions[] = "s.shipping_cost <= '" . (float)$search_criteria['cost_max'] . "'";
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY s.date_added DESC";

        if (isset($search_criteria['limit'])) {
            $sql .= " LIMIT " . (int)$search_criteria['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * تحديث مجمع للشحنات
     */
    public function bulkUpdateShipments($shipment_ids, $update_data) {
        if (empty($shipment_ids) || empty($update_data)) {
            return false;
        }

        $updates = array();
        $allowed_fields = array('status', 'carrier_id', 'shipping_cost', 'estimated_delivery');

        foreach ($update_data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                if ($field == 'carrier_id' || $field == 'shipping_cost') {
                    $updates[] = $field . " = '" . (float)$value . "'";
                } else {
                    $updates[] = $field . " = '" . $this->db->escape($value) . "'";
                }
            }
        }

        if (empty($updates)) {
            return false;
        }

        $shipment_ids_str = implode(',', array_map('intval', $shipment_ids));
        $updates[] = "date_modified = NOW()";

        $sql = "UPDATE " . DB_PREFIX . "shipment SET " . implode(', ', $updates) .
               " WHERE shipment_id IN (" . $shipment_ids_str . ")";

        $this->db->query($sql);

        // إضافة سجلات في تاريخ الحالة إذا تم تحديث الحالة
        if (isset($update_data['status'])) {
            foreach ($shipment_ids as $shipment_id) {
                $this->addStatusHistory($shipment_id, $update_data['status'], 'Bulk status update');
            }
        }

        return $this->db->countAffected();
    }

    /**
     * تقرير الشحنات المتأخرة
     */
    public function getDelayedShipments() {
        $query = $this->db->query("SELECT s.*, o.order_id as order_number,
            CONCAT(c.firstname, ' ', c.lastname) as customer_name,
            car.name as carrier_name,
            DATEDIFF(NOW(), s.estimated_delivery) as days_delayed
            FROM " . DB_PREFIX . "shipment s
            LEFT JOIN " . DB_PREFIX . "order o ON s.order_id = o.order_id
            LEFT JOIN " . DB_PREFIX . "customer c ON s.customer_id = c.customer_id
            LEFT JOIN " . DB_PREFIX . "carrier car ON s.carrier_id = car.carrier_id
            WHERE s.estimated_delivery < NOW()
            AND s.status NOT IN ('delivered', 'cancelled', 'returned')
            ORDER BY days_delayed DESC");

        return $query->rows;
    }

    /**
     * تقرير الشحنات عالية القيمة
     */
    public function getHighValueShipments($min_value = 1000) {
        $query = $this->db->query("SELECT s.*, o.order_id as order_number,
            CONCAT(c.firstname, ' ', c.lastname) as customer_name,
            car.name as carrier_name,
            o.total as order_value
            FROM " . DB_PREFIX . "shipment s
            LEFT JOIN " . DB_PREFIX . "order o ON s.order_id = o.order_id
            LEFT JOIN " . DB_PREFIX . "customer c ON s.customer_id = c.customer_id
            LEFT JOIN " . DB_PREFIX . "carrier car ON s.carrier_id = car.carrier_id
            WHERE (s.insurance_value >= '" . (float)$min_value . "' OR o.total >= '" . (float)$min_value . "')
            ORDER BY GREATEST(s.insurance_value, o.total) DESC");

        return $query->rows;
    }

    /**
     * إحصائيات الأداء اليومية
     */
    public function getDailyPerformanceStats($date = '') {
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $stats = array();

        // الشحنات المنشأة اليوم
        $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "shipment
            WHERE DATE(date_added) = '" . $this->db->escape($date) . "'");
        $stats['created_today'] = $query->row['count'];

        // الشحنات المشحونة اليوم
        $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "shipment_status_history
            WHERE DATE(date_added) = '" . $this->db->escape($date) . "' AND status = 'shipped'");
        $stats['shipped_today'] = $query->row['count'];

        // الشحنات المسلمة اليوم
        $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "shipment_status_history
            WHERE DATE(date_added) = '" . $this->db->escape($date) . "' AND status = 'delivered'");
        $stats['delivered_today'] = $query->row['count'];

        // إجمالي تكلفة الشحن اليوم
        $query = $this->db->query("SELECT SUM(shipping_cost) as total FROM " . DB_PREFIX . "shipment
            WHERE DATE(date_added) = '" . $this->db->escape($date) . "'");
        $stats['total_cost_today'] = $query->row['total'] ?: 0;

        return $stats;
    }
}
