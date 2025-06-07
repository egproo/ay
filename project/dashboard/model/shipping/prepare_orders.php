<?php
/**
 * AYM ERP System: Advanced Order Preparation Model
 * 
 * نموذج تجهيز الطلبات المتقدم - مطور للشركات الحقيقية
 * 
 * الميزات المتقدمة:
 * - إدارة حالات التجهيز المتقدمة
 * - تتبع تقدم التجهيز لكل عنصر
 * - حساب الإحصائيات في الوقت الفعلي
 * - إدارة أولويات التجهيز
 * - تكامل مع المخزون والمواقع
 * - دعم الوحدات المتعددة والخيارات
 * - تتبع الموظف المسؤول عن التجهيز
 * - سجل تاريخ التجهيز المفصل
 * 
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

class ModelShippingPrepareOrders extends Model {
    
    /**
     * الحصول على الطلبات المطلوب تجهيزها
     */
    public function getOrdersForPreparation($data = array()) {
        $sql = "SELECT 
                    o.order_id,
                    o.customer_id,
                    CONCAT(o.firstname, ' ', o.lastname) as customer,
                    o.email,
                    o.telephone,
                    o.total,
                    o.currency_code,
                    o.date_added,
                    o.date_modified,
                    os.name as order_status,
                    COALESCE(op.preparation_status, 'pending') as preparation_status,
                    COALESCE(op.priority, 'normal') as priority,
                    COALESCE(op.assigned_user_id, 0) as assigned_user_id,
                    COALESCE(op.preparation_started, '') as preparation_started,
                    COALESCE(op.preparation_completed, '') as preparation_completed,
                    COUNT(orp.order_product_id) as items_count,
                    SUM(CASE WHEN orp.preparation_status = 'prepared' THEN 1 ELSE 0 END) as prepared_items,
                    ROUND((SUM(CASE WHEN orp.preparation_status = 'prepared' THEN 1 ELSE 0 END) / COUNT(orp.order_product_id)) * 100, 2) as preparation_percentage
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id)
                LEFT JOIN " . DB_PREFIX . "order_preparation op ON (o.order_id = op.order_id)
                LEFT JOIN " . DB_PREFIX . "order_product orp ON (o.order_id = orp.order_id)
                WHERE o.order_status_id IN (1, 2, 3, 15) "; // حالات تحتاج تجهيز
        
        // تطبيق الفلاتر
        if (!empty($data['filter_order_id'])) {
            $sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
        }
        
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND COALESCE(op.preparation_status, 'pending') = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_priority'])) {
            $sql .= " AND COALESCE(op.priority, 'normal') = '" . $this->db->escape($data['filter_priority']) . "'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        if (!empty($data['filter_branch'])) {
            $sql .= " AND o.store_id = '" . (int)$data['filter_branch'] . "'";
        }
        
        $sql .= " GROUP BY o.order_id";
        
        // الترتيب
        $sort_data = array(
            'o.order_id',
            'customer',
            'o.total',
            'o.date_added',
            'preparation_status',
            'priority',
            'preparation_percentage'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY o.date_added";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        // الحد والبداية
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
     * الحصول على إجمالي عدد الطلبات المطلوب تجهيزها
     */
    public function getTotalOrdersForPreparation($data = array()) {
        $sql = "SELECT COUNT(DISTINCT o.order_id) as total
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_preparation op ON (o.order_id = op.order_id)
                WHERE o.order_status_id IN (1, 2, 3, 15)";
        
        // تطبيق نفس الفلاتر
        if (!empty($data['filter_order_id'])) {
            $sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
        }
        
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND COALESCE(op.preparation_status, 'pending') = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_priority'])) {
            $sql .= " AND COALESCE(op.priority, 'normal') = '" . $this->db->escape($data['filter_priority']) . "'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        if (!empty($data['filter_branch'])) {
            $sql .= " AND o.store_id = '" . (int)$data['filter_branch'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * تحديث حالة تجهيز الطلب
     */
    public function updateOrderPreparationStatus($order_id, $status, $notes = '') {
        // التحقق من وجود سجل التجهيز
        $existing = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_preparation WHERE order_id = '" . (int)$order_id . "'");
        
        if ($existing->num_rows) {
            // تحديث السجل الموجود
            $sql = "UPDATE " . DB_PREFIX . "order_preparation SET 
                        preparation_status = '" . $this->db->escape($status) . "',
                        notes = '" . $this->db->escape($notes) . "',
                        last_updated = NOW(),
                        updated_by = '" . (int)$this->user->getId() . "'";
            
            if ($status == 'in_progress' && empty($existing->row['preparation_started'])) {
                $sql .= ", preparation_started = NOW()";
            }
            
            if ($status == 'ready_for_shipping' || $status == 'completed') {
                $sql .= ", preparation_completed = NOW()";
            }
            
            $sql .= " WHERE order_id = '" . (int)$order_id . "'";
        } else {
            // إنشاء سجل جديد
            $sql = "INSERT INTO " . DB_PREFIX . "order_preparation SET 
                        order_id = '" . (int)$order_id . "',
                        preparation_status = '" . $this->db->escape($status) . "',
                        priority = 'normal',
                        notes = '" . $this->db->escape($notes) . "',
                        assigned_user_id = '" . (int)$this->user->getId() . "',
                        created_date = NOW(),
                        last_updated = NOW(),
                        updated_by = '" . (int)$this->user->getId() . "'";
            
            if ($status == 'in_progress') {
                $sql .= ", preparation_started = NOW()";
            }
        }
        
        $this->db->query($sql);
        
        // تسجيل في سجل التاريخ
        $this->addPreparationHistory($order_id, $status, $notes);
        
        return true;
    }
    
    /**
     * تحديث أولوية الطلب
     */
    public function updateOrderPriority($order_id, $priority) {
        // التحقق من وجود سجل التجهيز
        $existing = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_preparation WHERE order_id = '" . (int)$order_id . "'");
        
        if ($existing->num_rows) {
            $this->db->query("UPDATE " . DB_PREFIX . "order_preparation SET 
                                priority = '" . $this->db->escape($priority) . "',
                                last_updated = NOW(),
                                updated_by = '" . (int)$this->user->getId() . "'
                              WHERE order_id = '" . (int)$order_id . "'");
        } else {
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_preparation SET 
                                order_id = '" . (int)$order_id . "',
                                preparation_status = 'pending',
                                priority = '" . $this->db->escape($priority) . "',
                                assigned_user_id = '" . (int)$this->user->getId() . "',
                                created_date = NOW(),
                                last_updated = NOW(),
                                updated_by = '" . (int)$this->user->getId() . "'");
        }
        
        return true;
    }
    
    /**
     * تحديث حالة عنصر في الطلب
     */
    public function updateOrderItemStatus($order_product_id, $status, $prepared_quantity = 0) {
        $this->db->query("UPDATE " . DB_PREFIX . "order_product SET 
                            preparation_status = '" . $this->db->escape($status) . "',
                            prepared_quantity = '" . (int)$prepared_quantity . "',
                            preparation_date = NOW(),
                            prepared_by = '" . (int)$this->user->getId() . "'
                          WHERE order_product_id = '" . (int)$order_product_id . "'");
        
        return $this->db->countAffected() > 0;
    }
    
    /**
     * الحصول على معرف الطلب من معرف المنتج
     */
    public function getOrderIdByProductId($order_product_id) {
        $query = $this->db->query("SELECT order_id FROM " . DB_PREFIX . "order_product WHERE order_product_id = '" . (int)$order_product_id . "'");
        
        return $query->row ? $query->row['order_id'] : 0;
    }
    
    /**
     * التحقق من اكتمال تجهيز الطلب
     */
    public function isOrderFullyPrepared($order_id) {
        $query = $this->db->query("SELECT 
                                    COUNT(*) as total_items,
                                    SUM(CASE WHEN preparation_status = 'prepared' THEN 1 ELSE 0 END) as prepared_items
                                  FROM " . DB_PREFIX . "order_product 
                                  WHERE order_id = '" . (int)$order_id . "'");
        
        if ($query->row && $query->row['total_items'] > 0) {
            return $query->row['total_items'] == $query->row['prepared_items'];
        }
        
        return false;
    }
    
    /**
     * الحصول على إحصائيات التجهيز
     */
    public function getPreparationStatistics() {
        $statistics = array();
        
        // عدد الطلبات حسب الحالة
        $query = $this->db->query("SELECT 
                                    COALESCE(op.preparation_status, 'pending') as status,
                                    COUNT(*) as count
                                  FROM " . DB_PREFIX . "order o
                                  LEFT JOIN " . DB_PREFIX . "order_preparation op ON (o.order_id = op.order_id)
                                  WHERE o.order_status_id IN (1, 2, 3, 15)
                                  GROUP BY COALESCE(op.preparation_status, 'pending')");
        
        foreach ($query->rows as $row) {
            $statistics[$row['status'] . '_orders'] = $row['count'];
        }
        
        // إجمالي العناصر والمُجهزة
        $query = $this->db->query("SELECT 
                                    COUNT(*) as total_items,
                                    SUM(CASE WHEN preparation_status = 'prepared' THEN 1 ELSE 0 END) as prepared_items
                                  FROM " . DB_PREFIX . "order_product op
                                  INNER JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                                  WHERE o.order_status_id IN (1, 2, 3, 15)");
        
        if ($query->row) {
            $statistics['total_items'] = $query->row['total_items'];
            $statistics['prepared_items'] = $query->row['prepared_items'];
            $statistics['preparation_percentage'] = $query->row['total_items'] > 0 ? 
                round(($query->row['prepared_items'] / $query->row['total_items']) * 100, 2) : 0;
        }
        
        return $statistics;
    }
    
    /**
     * الحصول على حالات التجهيز المتاحة
     */
    public function getPreparationStatuses() {
        return array(
            'pending' => 'في الانتظار',
            'in_progress' => 'قيد التجهيز',
            'ready_for_shipping' => 'جاهز للشحن',
            'shipped' => 'تم الشحن',
            'completed' => 'مكتمل'
        );
    }
    
    /**
     * الحصول على مستويات الأولوية
     */
    public function getPriorityLevels() {
        return array(
            'low' => 'منخفضة',
            'normal' => 'عادية',
            'high' => 'عالية',
            'urgent' => 'عاجلة'
        );
    }
    
    /**
     * الحصول على تفاصيل الطلب للتجهيز
     */
    public function getOrderForPicking($order_id) {
        $query = $this->db->query("SELECT o.*, 
                                    CONCAT(o.firstname, ' ', o.lastname) as customer_name,
                                    os.name as order_status_name,
                                    COALESCE(op.preparation_status, 'pending') as preparation_status,
                                    COALESCE(op.priority, 'normal') as priority
                                  FROM " . DB_PREFIX . "order o
                                  LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id)
                                  LEFT JOIN " . DB_PREFIX . "order_preparation op ON (o.order_id = op.order_id)
                                  WHERE o.order_id = '" . (int)$order_id . "'");
        
        if ($query->row) {
            $order = $query->row;
            
            // جلب منتجات الطلب
            $product_query = $this->db->query("SELECT op.*, p.name, p.model, p.sku,
                                                u.desc_ar as unit_name,
                                                COALESCE(op.preparation_status, 'pending') as item_preparation_status,
                                                COALESCE(op.prepared_quantity, 0) as prepared_quantity,
                                                il.location_name, il.zone_name
                                              FROM " . DB_PREFIX . "order_product op
                                              LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                                              LEFT JOIN " . DB_PREFIX . "unit u ON (op.unit_id = u.unit_id)
                                              LEFT JOIN " . DB_PREFIX . "inventory_location il ON (p.product_id = il.product_id)
                                              WHERE op.order_id = '" . (int)$order_id . "'
                                              ORDER BY op.order_product_id");
            
            $order['products'] = $product_query->rows;
            
            return $order;
        }
        
        return false;
    }
    
    /**
     * إضافة سجل في تاريخ التجهيز
     */
    private function addPreparationHistory($order_id, $status, $notes) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_preparation_history SET 
                            order_id = '" . (int)$order_id . "',
                            status = '" . $this->db->escape($status) . "',
                            notes = '" . $this->db->escape($notes) . "',
                            user_id = '" . (int)$this->user->getId() . "',
                            date_added = NOW()");
    }
}
