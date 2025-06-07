<?php
/**
 * نموذج لوحة تحكم المخزون التفاعلية (Interactive Inventory Dashboard Model)
 * 
 * الهدف: توفير dashboard شامل مع إحصائيات متقدمة وتحليلات في الوقت الفعلي
 * الميزات: رسوم بيانية تفاعلية، تنبيهات ذكية، تحليلات متقدمة، مؤشرات الأداء
 * التكامل: مع جميع شاشات المخزون والمنتجات والمبيعات والمشتريات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryInteractiveDashboard extends Model {
    
    /**
     * الحصول على الإحصائيات العامة للمخزون
     */
    public function getGeneralStatistics($data = array()) {
        $sql = "
            SELECT 
                COUNT(DISTINCT p.product_id) as total_products,
                COUNT(DISTINCT CASE WHEN p.status = 1 THEN p.product_id END) as active_products,
                COUNT(DISTINCT CASE WHEN p.status = 0 THEN p.product_id END) as inactive_products,
                COUNT(DISTINCT CASE WHEN pi.available_quantity <= 0 THEN p.product_id END) as out_of_stock_products,
                COUNT(DISTINCT CASE WHEN pi.available_quantity <= pi.reorder_level AND pi.available_quantity > 0 THEN p.product_id END) as low_stock_products,
                COUNT(DISTINCT CASE WHEN pi.available_quantity >= pi.max_stock_level THEN p.product_id END) as overstock_products,
                SUM(pi.available_quantity) as total_quantity,
                SUM(pi.available_quantity * pi.avg_cost) as total_inventory_value,
                AVG(pi.avg_cost) as avg_cost_price,
                AVG(p.price) as avg_selling_price,
                COUNT(DISTINCT p.manufacturer_id) as total_manufacturers,
                COUNT(DISTINCT ptc.category_id) as total_categories,
                
                -- إحصائيات الباركود
                COUNT(DISTINCT pb.barcode_id) as total_barcodes,
                COUNT(DISTINCT CASE WHEN pb.is_active = 1 THEN pb.barcode_id END) as active_barcodes,
                
                -- إحصائيات الوحدات
                COUNT(DISTINCT pu.unit_id) as total_units,
                
                -- إحصائيات الحركات (آخر 30 يوم)
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_inventory_movement im 
                 WHERE DATE(im.date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as movements_30_days,
                
                -- إحصائيات المبيعات (آخر 30 يوم)
                (SELECT COALESCE(SUM(op.quantity), 0) FROM " . DB_PREFIX . "order_product op 
                 LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                 WHERE o.order_status_id > 0 
                 AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as sales_quantity_30_days,
                
                (SELECT COALESCE(SUM(op.quantity * op.price), 0) FROM " . DB_PREFIX . "order_product op 
                 LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                 WHERE o.order_status_id > 0 
                 AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as sales_value_30_days
                
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_barcode pb ON (p.product_id = pb.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_unit pu ON (p.product_id = pu.product_id)
        ";
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " WHERE DATE(p.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= (!empty($data['filter_date_from']) ? " AND" : " WHERE") . " DATE(p.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    /**
     * الحصول على إحصائيات المخزون حسب الفئات
     */
    public function getInventoryByCategories($limit = 10) {
        $query = $this->db->query("
            SELECT 
                c.category_id,
                cd.name as category_name,
                COUNT(DISTINCT p.product_id) as product_count,
                SUM(pi.available_quantity) as total_quantity,
                SUM(pi.available_quantity * pi.avg_cost) as total_value,
                AVG(pi.avg_cost) as avg_cost,
                AVG(p.price) as avg_price,
                COUNT(DISTINCT CASE WHEN pi.available_quantity <= 0 THEN p.product_id END) as out_of_stock_count,
                COUNT(DISTINCT CASE WHEN pi.available_quantity <= pi.reorder_level AND pi.available_quantity > 0 THEN p.product_id END) as low_stock_count
            FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON (c.category_id = ptc.category_id)
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (ptc.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND c.status = 1
            GROUP BY c.category_id
            HAVING product_count > 0
            ORDER BY total_value DESC
            LIMIT " . (int)$limit
        );
        
        return $query->rows;
    }
    
    /**
     * الحصول على تحليل حركة المخزون (آخر 30 يوم)
     */
    public function getInventoryMovementAnalysis($days = 30) {
        $query = $this->db->query("
            SELECT 
                DATE(im.date_added) as movement_date,
                im.movement_type,
                COUNT(*) as movement_count,
                SUM(CASE WHEN im.movement_type IN ('in', 'adjustment_in', 'transfer_in') THEN im.quantity ELSE 0 END) as total_in,
                SUM(CASE WHEN im.movement_type IN ('out', 'adjustment_out', 'transfer_out') THEN im.quantity ELSE 0 END) as total_out,
                SUM(CASE WHEN im.movement_type IN ('in', 'adjustment_in', 'transfer_in') THEN im.quantity * im.cost_price ELSE 0 END) as value_in,
                SUM(CASE WHEN im.movement_type IN ('out', 'adjustment_out', 'transfer_out') THEN im.quantity * im.cost_price ELSE 0 END) as value_out
            FROM " . DB_PREFIX . "cod_inventory_movement im
            WHERE DATE(im.date_added) >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
            GROUP BY DATE(im.date_added), im.movement_type
            ORDER BY movement_date DESC, im.movement_type
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على أفضل المنتجات مبيعاً
     */
    public function getTopSellingProducts($limit = 10, $days = 30) {
        $query = $this->db->query("
            SELECT 
                p.product_id,
                pd.name,
                p.model,
                p.sku,
                p.price,
                pi.available_quantity,
                SUM(op.quantity) as total_sold,
                SUM(op.quantity * op.price) as total_revenue,
                AVG(op.price) as avg_selling_price,
                (pi.available_quantity * pi.avg_cost) as inventory_value,
                CASE 
                    WHEN pi.available_quantity <= 0 THEN 'out_of_stock'
                    WHEN pi.available_quantity <= pi.reorder_level THEN 'low_stock'
                    WHEN pi.available_quantity >= pi.max_stock_level THEN 'overstock'
                    ELSE 'in_stock'
                END as stock_status
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "order_product op ON (p.product_id = op.product_id)
            LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND o.order_status_id > 0
            AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT " . (int)$limit
        );
        
        return $query->rows;
    }
    
    /**
     * الحصول على المنتجات منخفضة المخزون
     */
    public function getLowStockProducts($limit = 20) {
        $query = $this->db->query("
            SELECT 
                p.product_id,
                pd.name,
                p.model,
                p.sku,
                pi.available_quantity,
                pi.reorder_level,
                pi.max_stock_level,
                (pi.reorder_level - pi.available_quantity) as shortage_quantity,
                (pi.available_quantity * pi.avg_cost) as current_value,
                (pi.reorder_level * pi.avg_cost) as recommended_value,
                ROUND((pi.available_quantity / NULLIF(pi.reorder_level, 0)) * 100, 2) as stock_percentage
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND p.status = 1
            AND pi.available_quantity <= pi.reorder_level
            AND pi.available_quantity >= 0
            ORDER BY stock_percentage ASC, shortage_quantity DESC
            LIMIT " . (int)$limit
        );
        
        return $query->rows;
    }
    
    /**
     * الحصول على تحليل الربحية
     */
    public function getProfitabilityAnalysis($days = 30) {
        $query = $this->db->query("
            SELECT 
                p.product_id,
                pd.name,
                p.model,
                p.sku,
                SUM(op.quantity) as total_sold,
                SUM(op.quantity * op.price) as total_revenue,
                SUM(op.quantity * pi.avg_cost) as total_cost,
                (SUM(op.quantity * op.price) - SUM(op.quantity * pi.avg_cost)) as total_profit,
                ROUND(((SUM(op.quantity * op.price) - SUM(op.quantity * pi.avg_cost)) / NULLIF(SUM(op.quantity * op.price), 0)) * 100, 2) as profit_margin,
                AVG(op.price) as avg_selling_price,
                AVG(pi.avg_cost) as avg_cost_price,
                pi.available_quantity,
                (pi.available_quantity * pi.avg_cost) as inventory_value
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "order_product op ON (p.product_id = op.product_id)
            LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND o.order_status_id > 0
            AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
            GROUP BY p.product_id
            HAVING total_sold > 0
            ORDER BY total_profit DESC
            LIMIT 20
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على التنبيهات الذكية
     */
    public function getSmartAlerts() {
        $alerts = array();
        
        // تنبيهات المخزون المنخفض
        $low_stock_query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE p.status = 1 
            AND pi.available_quantity <= pi.reorder_level 
            AND pi.available_quantity > 0
        ");
        
        if ($low_stock_query->row['count'] > 0) {
            $alerts[] = array(
                'type' => 'warning',
                'icon' => 'fa-exclamation-triangle',
                'title' => 'تنبيه مخزون منخفض',
                'message' => $low_stock_query->row['count'] . ' منتج يحتاج إعادة طلب',
                'action_url' => 'inventory/interactive_dashboard/lowStock',
                'priority' => 'high'
            );
        }
        
        // تنبيهات المخزون المنتهي
        $out_of_stock_query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE p.status = 1 AND pi.available_quantity <= 0
        ");
        
        if ($out_of_stock_query->row['count'] > 0) {
            $alerts[] = array(
                'type' => 'danger',
                'icon' => 'fa-times-circle',
                'title' => 'تنبيه مخزون منتهي',
                'message' => $out_of_stock_query->row['count'] . ' منتج غير متوفر',
                'action_url' => 'inventory/interactive_dashboard/outOfStock',
                'priority' => 'critical'
            );
        }
        
        // تنبيهات المخزون الزائد
        $overstock_query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE p.status = 1 AND pi.available_quantity > pi.max_stock_level
        ");
        
        if ($overstock_query->row['count'] > 0) {
            $alerts[] = array(
                'type' => 'info',
                'icon' => 'fa-arrow-up',
                'title' => 'تنبيه مخزون زائد',
                'message' => $overstock_query->row['count'] . ' منتج يحتاج مراجعة',
                'action_url' => 'inventory/interactive_dashboard/overStock',
                'priority' => 'medium'
            );
        }
        
        return $alerts;
    }
    
    /**
     * الحصول على مؤشرات الأداء الرئيسية (KPIs)
     */
    public function getKPIs($days = 30) {
        $kpis = array();
        
        // معدل دوران المخزون
        $turnover_query = $this->db->query("
            SELECT 
                AVG(turnover_rate) as avg_turnover
            FROM (
                SELECT 
                    (SUM(op.quantity * pi.avg_cost) / NULLIF((pi.available_quantity * pi.avg_cost), 0)) as turnover_rate
                FROM " . DB_PREFIX . "cod_product p
                LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
                LEFT JOIN " . DB_PREFIX . "order_product op ON (p.product_id = op.product_id)
                LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                WHERE o.order_status_id > 0
                AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
                AND pi.available_quantity > 0
                AND pi.avg_cost > 0
                GROUP BY p.product_id
            ) as turnover_stats
        ");
        
        $kpis['inventory_turnover'] = round($turnover_query->row['avg_turnover'] ?: 0, 2);
        
        // نسبة دقة المخزون
        $accuracy_query = $this->db->query("
            SELECT 
                COUNT(CASE WHEN ABS(pi.available_quantity - COALESCE(sc.counted_quantity, pi.available_quantity)) <= 1 THEN 1 END) as accurate_count,
                COUNT(*) as total_count
            FROM " . DB_PREFIX . "cod_product_inventory pi
            LEFT JOIN " . DB_PREFIX . "cod_stock_count sc ON (pi.product_id = sc.product_id)
            WHERE pi.available_quantity >= 0
        ");
        
        $accuracy_data = $accuracy_query->row;
        $kpis['inventory_accuracy'] = $accuracy_data['total_count'] > 0 ? 
            round(($accuracy_data['accurate_count'] / $accuracy_data['total_count']) * 100, 2) : 100;
        
        // نسبة توفر المخزون
        $availability_query = $this->db->query("
            SELECT 
                COUNT(CASE WHEN pi.available_quantity > 0 THEN 1 END) as available_count,
                COUNT(*) as total_count
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE p.status = 1
        ");
        
        $availability_data = $availability_query->row;
        $kpis['stock_availability'] = $availability_data['total_count'] > 0 ? 
            round(($availability_data['available_count'] / $availability_data['total_count']) * 100, 2) : 0;
        
        return $kpis;
    }
}
