<?php
/**
 * نموذج إدارة المناديب الداخليين
 * 
 * يوفر إدارة شاملة للمناديب الداخليين مع:
 * - تعريف المناديب وربطهم بالمستخدمين
 * - إدارة مناطق التغطية
 * - تتبع الأداء والإحصائيات
 * - جدولة المهام والتوصيل
 * - التكامل مع نظام الشحن
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelShippingInternalCourier extends Model {
    
    /**
     * إضافة مندوب داخلي جديد
     */
    public function addInternalCourier($data) {
        $this->db->query("
            INSERT INTO cod_internal_courier SET 
            name = '" . $this->db->escape($data['name']) . "',
            user_id = '" . (int)$data['user_id'] . "',
            phone = '" . $this->db->escape($data['phone']) . "',
            email = '" . $this->db->escape($data['email']) . "',
            vehicle_type = '" . $this->db->escape($data['vehicle_type']) . "',
            vehicle_number = '" . $this->db->escape($data['vehicle_number']) . "',
            max_weight_capacity = '" . (float)$data['max_weight_capacity'] . "',
            max_orders_per_day = '" . (int)$data['max_orders_per_day'] . "',
            base_salary = '" . (float)$data['base_salary'] . "',
            commission_per_delivery = '" . (float)$data['commission_per_delivery'] . "',
            commission_percentage = '" . (float)$data['commission_percentage'] . "',
            working_hours_start = '" . $this->db->escape($data['working_hours_start']) . "',
            working_hours_end = '" . $this->db->escape($data['working_hours_end']) . "',
            working_days = '" . $this->db->escape(json_encode($data['working_days'])) . "',
            status = '" . $this->db->escape($data['status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()
        ");
        
        $courier_id = $this->db->getLastId();
        
        // إضافة مناطق التغطية
        if (!empty($data['coverage_areas'])) {
            foreach ($data['coverage_areas'] as $area) {
                $this->db->query("
                    INSERT INTO cod_courier_coverage SET 
                    courier_id = '" . (int)$courier_id . "',
                    area_type = '" . $this->db->escape($area['type']) . "',
                    area_value = '" . $this->db->escape($area['value']) . "',
                    delivery_fee = '" . (float)$area['delivery_fee'] . "',
                    estimated_time = '" . (int)$area['estimated_time'] . "'
                ");
            }
        }
        
        // إرسال إشعار للمندوب
        $this->sendCourierNotification($courier_id, 'welcome', 'مرحباً بك كمندوب في فريقنا');
        
        return $courier_id;
    }
    
    /**
     * تحديث بيانات المندوب
     */
    public function editInternalCourier($courier_id, $data) {
        $this->db->query("
            UPDATE cod_internal_courier SET 
            name = '" . $this->db->escape($data['name']) . "',
            user_id = '" . (int)$data['user_id'] . "',
            phone = '" . $this->db->escape($data['phone']) . "',
            email = '" . $this->db->escape($data['email']) . "',
            vehicle_type = '" . $this->db->escape($data['vehicle_type']) . "',
            vehicle_number = '" . $this->db->escape($data['vehicle_number']) . "',
            max_weight_capacity = '" . (float)$data['max_weight_capacity'] . "',
            max_orders_per_day = '" . (int)$data['max_orders_per_day'] . "',
            base_salary = '" . (float)$data['base_salary'] . "',
            commission_per_delivery = '" . (float)$data['commission_per_delivery'] . "',
            commission_percentage = '" . (float)$data['commission_percentage'] . "',
            working_hours_start = '" . $this->db->escape($data['working_hours_start']) . "',
            working_hours_end = '" . $this->db->escape($data['working_hours_end']) . "',
            working_days = '" . $this->db->escape(json_encode($data['working_days'])) . "',
            status = '" . $this->db->escape($data['status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            updated_by = '" . (int)$this->user->getId() . "',
            updated_at = NOW()
            WHERE courier_id = '" . (int)$courier_id . "'
        ");
        
        // تحديث مناطق التغطية
        $this->db->query("DELETE FROM cod_courier_coverage WHERE courier_id = '" . (int)$courier_id . "'");
        
        if (!empty($data['coverage_areas'])) {
            foreach ($data['coverage_areas'] as $area) {
                $this->db->query("
                    INSERT INTO cod_courier_coverage SET 
                    courier_id = '" . (int)$courier_id . "',
                    area_type = '" . $this->db->escape($area['type']) . "',
                    area_value = '" . $this->db->escape($area['value']) . "',
                    delivery_fee = '" . (float)$area['delivery_fee'] . "',
                    estimated_time = '" . (int)$area['estimated_time'] . "'
                ");
            }
        }
    }
    
    /**
     * حذف مندوب
     */
    public function deleteInternalCourier($courier_id) {
        // التحقق من عدم وجود طلبات نشطة
        $active_orders = $this->db->query("
            SELECT COUNT(*) as count FROM cod_courier_assignment 
            WHERE courier_id = '" . (int)$courier_id . "' 
            AND status IN ('assigned', 'picked_up', 'in_transit')
        ");
        
        if ($active_orders->row['count'] > 0) {
            throw new Exception('لا يمكن حذف المندوب لوجود طلبات نشطة');
        }
        
        $this->db->query("DELETE FROM cod_internal_courier WHERE courier_id = '" . (int)$courier_id . "'");
        $this->db->query("DELETE FROM cod_courier_coverage WHERE courier_id = '" . (int)$courier_id . "'");
    }
    
    /**
     * الحصول على بيانات مندوب
     */
    public function getInternalCourier($courier_id) {
        $query = $this->db->query("
            SELECT ic.*, u.firstname, u.lastname, u.username,
                CONCAT(u.firstname, ' ', u.lastname) as user_name
            FROM cod_internal_courier ic
            LEFT JOIN cod_user u ON (ic.user_id = u.user_id)
            WHERE ic.courier_id = '" . (int)$courier_id . "'
        ");
        
        if ($query->num_rows) {
            $courier = $query->row;
            
            // الحصول على مناطق التغطية
            $coverage_query = $this->db->query("
                SELECT * FROM cod_courier_coverage 
                WHERE courier_id = '" . (int)$courier_id . "'
                ORDER BY area_type, area_value
            ");
            
            $courier['coverage_areas'] = $coverage_query->rows;
            $courier['working_days'] = json_decode($courier['working_days'], true);
            
            return $courier;
        }
        
        return false;
    }
    
    /**
     * الحصول على قائمة المناديب
     */
    public function getInternalCouriers($filter_data = []) {
        $sql = "SELECT ic.*, u.firstname, u.lastname,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                (SELECT COUNT(*) FROM cod_courier_assignment ca 
                 WHERE ca.courier_id = ic.courier_id 
                 AND DATE(ca.assigned_date) = CURDATE()) as today_assignments,
                (SELECT COUNT(*) FROM cod_courier_assignment ca 
                 WHERE ca.courier_id = ic.courier_id 
                 AND ca.status = 'delivered' 
                 AND DATE(ca.delivered_date) = CURDATE()) as today_deliveries
                FROM cod_internal_courier ic
                LEFT JOIN cod_user u ON (ic.user_id = u.user_id)
                WHERE 1";
        
        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND ic.name LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'";
        }
        
        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND ic.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }
        
        if (!empty($filter_data['filter_vehicle_type'])) {
            $sql .= " AND ic.vehicle_type = '" . $this->db->escape($filter_data['filter_vehicle_type']) . "'";
        }
        
        $sql .= " ORDER BY ic.name";
        
        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
            
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * تعيين طلب لمندوب
     */
    public function assignOrderToCourier($order_id, $courier_id, $assignment_data = []) {
        // التحقق من توفر المندوب
        if (!$this->isCourierAvailable($courier_id)) {
            throw new Exception('المندوب غير متاح حالياً');
        }
        
        // التحقق من قدرة المندوب على حمل الطلب
        if (!$this->canCourierHandleOrder($courier_id, $order_id)) {
            throw new Exception('المندوب لا يستطيع حمل هذا الطلب (تجاوز الحد الأقصى)');
        }
        
        $this->db->query("
            INSERT INTO cod_courier_assignment SET 
            courier_id = '" . (int)$courier_id . "',
            order_id = '" . (int)$order_id . "',
            assigned_date = NOW(),
            assigned_by = '" . (int)$this->user->getId() . "',
            status = 'assigned',
            priority = '" . $this->db->escape($assignment_data['priority'] ?? 'normal') . "',
            estimated_delivery_time = '" . $this->db->escape($assignment_data['estimated_delivery_time'] ?? '') . "',
            special_instructions = '" . $this->db->escape($assignment_data['special_instructions'] ?? '') . "',
            delivery_fee = '" . (float)($assignment_data['delivery_fee'] ?? 0) . "'
        ");
        
        $assignment_id = $this->db->getLastId();
        
        // تحديث حالة الطلب
        $this->db->query("
            UPDATE cod_order SET 
            order_status_id = 16 -- Assigned to Courier
            WHERE order_id = '" . (int)$order_id . "'
        ");
        
        // إرسال إشعار للمندوب
        $this->sendCourierNotification($courier_id, 'new_assignment', 'تم تعيين طلب جديد لك رقم ' . $order_id);
        
        return $assignment_id;
    }
    
    /**
     * التحقق من توفر المندوب
     */
    private function isCourierAvailable($courier_id) {
        $courier = $this->getInternalCourier($courier_id);
        
        if (!$courier || $courier['status'] != 'active') {
            return false;
        }
        
        // التحقق من أوقات العمل
        $current_time = date('H:i:s');
        $current_day = date('w'); // 0 = Sunday, 6 = Saturday
        
        if ($current_time < $courier['working_hours_start'] || $current_time > $courier['working_hours_end']) {
            return false;
        }
        
        if (!in_array($current_day, $courier['working_days'])) {
            return false;
        }
        
        // التحقق من الحد الأقصى للطلبات اليومية
        $today_assignments = $this->db->query("
            SELECT COUNT(*) as count FROM cod_courier_assignment 
            WHERE courier_id = '" . (int)$courier_id . "' 
            AND DATE(assigned_date) = CURDATE()
        ");
        
        if ($today_assignments->row['count'] >= $courier['max_orders_per_day']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * التحقق من قدرة المندوب على حمل الطلب
     */
    private function canCourierHandleOrder($courier_id, $order_id) {
        $courier = $this->getInternalCourier($courier_id);
        
        // حساب وزن الطلب
        $order_weight_query = $this->db->query("
            SELECT SUM(op.quantity * p.weight) as total_weight
            FROM cod_order_product op
            LEFT JOIN cod_product p ON (op.product_id = p.product_id)
            WHERE op.order_id = '" . (int)$order_id . "'
        ");
        
        $order_weight = $order_weight_query->row['total_weight'] ?? 0;
        
        // حساب الوزن الحالي للمندوب
        $current_weight_query = $this->db->query("
            SELECT SUM(op.quantity * p.weight) as current_weight
            FROM cod_courier_assignment ca
            LEFT JOIN cod_order_product op ON (ca.order_id = op.order_id)
            LEFT JOIN cod_product p ON (op.product_id = p.product_id)
            WHERE ca.courier_id = '" . (int)$courier_id . "'
            AND ca.status IN ('assigned', 'picked_up', 'in_transit')
        ");
        
        $current_weight = $current_weight_query->row['current_weight'] ?? 0;
        
        return ($current_weight + $order_weight) <= $courier['max_weight_capacity'];
    }
    
    /**
     * إرسال إشعار للمندوب
     */
    private function sendCourierNotification($courier_id, $type, $message) {
        $courier = $this->getInternalCourier($courier_id);
        
        if ($courier && $courier['user_id']) {
            $this->db->query("
                INSERT INTO cod_unified_notification SET 
                user_id = '" . (int)$courier['user_id'] . "',
                title = 'إشعار مندوب',
                message = '" . $this->db->escape($message) . "',
                type = 'courier_" . $this->db->escape($type) . "',
                priority = 'medium',
                reference_type = 'courier',
                reference_id = '" . (int)$courier_id . "',
                created_by = '" . (int)$this->user->getId() . "',
                created_at = NOW()
            ");
        }
    }
    
    /**
     * الحصول على إحصائيات المندوب
     */
    public function getCourierStatistics($courier_id, $period = 'month') {
        $date_condition = '';
        
        switch ($period) {
            case 'today':
                $date_condition = "AND DATE(ca.assigned_date) = CURDATE()";
                break;
            case 'week':
                $date_condition = "AND WEEK(ca.assigned_date) = WEEK(CURDATE())";
                break;
            case 'month':
                $date_condition = "AND MONTH(ca.assigned_date) = MONTH(CURDATE()) AND YEAR(ca.assigned_date) = YEAR(CURDATE())";
                break;
        }
        
        $query = $this->db->query("
            SELECT 
                COUNT(*) as total_assignments,
                COUNT(CASE WHEN ca.status = 'delivered' THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN ca.status = 'failed' THEN 1 END) as failed_orders,
                COUNT(CASE WHEN ca.status = 'returned' THEN 1 END) as returned_orders,
                AVG(TIMESTAMPDIFF(MINUTE, ca.assigned_date, ca.delivered_date)) as avg_delivery_time,
                SUM(ca.delivery_fee) as total_earnings
            FROM cod_courier_assignment ca
            WHERE ca.courier_id = '" . (int)$courier_id . "'
            " . $date_condition . "
        ");
        
        $stats = $query->row;
        
        // حساب معدل النجاح
        $stats['success_rate'] = $stats['total_assignments'] > 0 ? 
            ($stats['delivered_orders'] / $stats['total_assignments']) * 100 : 0;
        
        return $stats;
    }
    
    /**
     * الحصول على المناديب المتاحين لمنطقة معينة
     */
    public function getAvailableCouriersForArea($area_type, $area_value) {
        $query = $this->db->query("
            SELECT DISTINCT ic.*, u.firstname, u.lastname,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                cc.delivery_fee, cc.estimated_time
            FROM cod_internal_courier ic
            LEFT JOIN cod_user u ON (ic.user_id = u.user_id)
            LEFT JOIN cod_courier_coverage cc ON (ic.courier_id = cc.courier_id)
            WHERE ic.status = 'active'
            AND cc.area_type = '" . $this->db->escape($area_type) . "'
            AND cc.area_value = '" . $this->db->escape($area_value) . "'
            ORDER BY cc.delivery_fee, cc.estimated_time
        ");
        
        $available_couriers = [];
        
        foreach ($query->rows as $courier) {
            if ($this->isCourierAvailable($courier['courier_id'])) {
                $available_couriers[] = $courier;
            }
        }
        
        return $available_couriers;
    }
}
