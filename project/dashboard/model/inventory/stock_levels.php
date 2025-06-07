<?php
/**
 * نموذج استعلام الأرصدة الحالية المتطور (Advanced Current Stock Levels Model)
 * 
 * الهدف: توفير استعلامات متطورة للأرصدة الحالية مع تحليلات متقدمة
 * الميزات: فلاتر متعددة، تجميع ذكي، حسابات WAC، تنبيهات المخزون
 * التكامل: مع المحاسبة والتقارير والتنبيهات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryStockLevels extends Model {
    
    /**
     * الحصول على الأرصدة الحالية مع فلاتر متقدمة
     */
    public function getCurrentStockLevels($data = array()) {
        $sql = "
            SELECT 
                p.product_id,
                pd.name as product_name,
                p.model,
                p.sku,
                p.status as product_status,
                c.category_id,
                cd.name as category_name,
                m.manufacturer_id,
                md.name as manufacturer_name,
                pi.branch_id,
                b.name as branch_name,
                b.type as branch_type,
                pi.unit_id,
                ud.name as unit_name,
                u.symbol as unit_symbol,
                pi.quantity,
                p.average_cost,
                (pi.quantity * p.average_cost) as total_value,
                p.minimum_quantity,
                p.maximum_quantity,
                CASE 
                    WHEN pi.quantity <= 0 THEN 'out_of_stock'
                    WHEN pi.quantity <= p.minimum_quantity THEN 'low_stock'
                    WHEN pi.quantity >= p.maximum_quantity THEN 'overstock'
                    ELSE 'normal'
                END as stock_status,
                p.price as selling_price,
                (p.price - p.average_cost) as profit_margin,
                CASE 
                    WHEN p.average_cost > 0 THEN ((p.price - p.average_cost) / p.average_cost * 100)
                    ELSE 0
                END as profit_percentage,
                p.date_available,
                p.date_added,
                p.date_modified,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_product_movement pm 
                 WHERE pm.product_id = p.product_id 
                 AND pm.branch_id = pi.branch_id 
                 AND pm.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as movements_last_30_days,
                (SELECT pm2.date_added FROM " . DB_PREFIX . "cod_product_movement pm2 
                 WHERE pm2.product_id = p.product_id 
                 AND pm2.branch_id = pi.branch_id 
                 ORDER BY pm2.date_added DESC LIMIT 1) as last_movement_date,
                DATEDIFF(NOW(), (SELECT pm3.date_added FROM " . DB_PREFIX . "cod_product_movement pm3 
                                WHERE pm3.product_id = p.product_id 
                                AND pm3.branch_id = pi.branch_id 
                                ORDER BY pm3.date_added DESC LIMIT 1)) as days_since_last_movement
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pi.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "category c ON (p.category_id = c.category_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
            LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (pi.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pi.unit_id = u.unit_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND md.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        // تطبيق الفلاتر
        if (!empty($data['filter_product_name'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product_name']) . "%' 
                     OR p.model LIKE '%" . $this->db->escape($data['filter_product_name']) . "%'
                     OR p.sku LIKE '%" . $this->db->escape($data['filter_product_name']) . "%')";
        }
        
        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category_id'] . "'";
        }
        
        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_branch_type'])) {
            $sql .= " AND b.type = '" . $this->db->escape($data['filter_branch_type']) . "'";
        }
        
        if (isset($data['filter_stock_status']) && $data['filter_stock_status'] !== '') {
            switch ($data['filter_stock_status']) {
                case 'out_of_stock':
                    $sql .= " AND pi.quantity <= 0";
                    break;
                case 'low_stock':
                    $sql .= " AND pi.quantity > 0 AND pi.quantity <= p.minimum_quantity";
                    break;
                case 'overstock':
                    $sql .= " AND pi.quantity >= p.maximum_quantity";
                    break;
                case 'normal':
                    $sql .= " AND pi.quantity > p.minimum_quantity AND pi.quantity < p.maximum_quantity";
                    break;
            }
        }
        
        if (isset($data['filter_product_status']) && $data['filter_product_status'] !== '') {
            $sql .= " AND p.status = '" . (int)$data['filter_product_status'] . "'";
        }
        
        if (!empty($data['filter_min_quantity'])) {
            $sql .= " AND pi.quantity >= '" . (float)$data['filter_min_quantity'] . "'";
        }
        
        if (!empty($data['filter_max_quantity'])) {
            $sql .= " AND pi.quantity <= '" . (float)$data['filter_max_quantity'] . "'";
        }
        
        if (!empty($data['filter_min_value'])) {
            $sql .= " AND (pi.quantity * p.average_cost) >= '" . (float)$data['filter_min_value'] . "'";
        }
        
        if (!empty($data['filter_max_value'])) {
            $sql .= " AND (pi.quantity * p.average_cost) <= '" . (float)$data['filter_max_value'] . "'";
        }
        
        if (!empty($data['filter_slow_moving_days'])) {
            $sql .= " AND DATEDIFF(NOW(), (SELECT pm.date_added FROM " . DB_PREFIX . "cod_product_movement pm 
                     WHERE pm.product_id = p.product_id AND pm.branch_id = pi.branch_id 
                     ORDER BY pm.date_added DESC LIMIT 1)) >= '" . (int)$data['filter_slow_moving_days'] . "'";
        }
        
        // ترتيب النتائج
        $sort_data = array(
            'pd.name',
            'p.model',
            'p.sku',
            'cd.name',
            'md.name',
            'b.name',
            'pi.quantity',
            'p.average_cost',
            'total_value',
            'profit_margin',
            'profit_percentage',
            'stock_status',
            'last_movement_date',
            'days_since_last_movement'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name, b.name";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        // تحديد عدد النتائج
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
     * الحصول على إجمالي عدد السجلات
     */
    public function getTotalStockLevels($data = array()) {
        $sql = "
            SELECT COUNT(DISTINCT CONCAT(p.product_id, '-', pi.branch_id)) AS total
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pi.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "category c ON (p.category_id = c.category_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
            LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND md.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        // تطبيق نفس الفلاتر
        if (!empty($data['filter_product_name'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product_name']) . "%' 
                     OR p.model LIKE '%" . $this->db->escape($data['filter_product_name']) . "%'
                     OR p.sku LIKE '%" . $this->db->escape($data['filter_product_name']) . "%')";
        }
        
        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category_id'] . "'";
        }
        
        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_branch_type'])) {
            $sql .= " AND b.type = '" . $this->db->escape($data['filter_branch_type']) . "'";
        }
        
        if (isset($data['filter_stock_status']) && $data['filter_stock_status'] !== '') {
            switch ($data['filter_stock_status']) {
                case 'out_of_stock':
                    $sql .= " AND pi.quantity <= 0";
                    break;
                case 'low_stock':
                    $sql .= " AND pi.quantity > 0 AND pi.quantity <= p.minimum_quantity";
                    break;
                case 'overstock':
                    $sql .= " AND pi.quantity >= p.maximum_quantity";
                    break;
                case 'normal':
                    $sql .= " AND pi.quantity > p.minimum_quantity AND pi.quantity < p.maximum_quantity";
                    break;
            }
        }
        
        if (isset($data['filter_product_status']) && $data['filter_product_status'] !== '') {
            $sql .= " AND p.status = '" . (int)$data['filter_product_status'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على ملخص الأرصدة
     */
    public function getStockSummary($data = array()) {
        $sql = "
            SELECT 
                COUNT(DISTINCT p.product_id) as total_products,
                COUNT(DISTINCT pi.branch_id) as total_branches,
                SUM(pi.quantity) as total_quantity,
                SUM(pi.quantity * p.average_cost) as total_value,
                SUM(CASE WHEN pi.quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock_count,
                SUM(CASE WHEN pi.quantity > 0 AND pi.quantity <= p.minimum_quantity THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN pi.quantity >= p.maximum_quantity THEN 1 ELSE 0 END) as overstock_count,
                AVG(p.average_cost) as avg_cost,
                AVG(pi.quantity) as avg_quantity
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pi.branch_id = b.branch_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        // تطبيق الفلاتر
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category_id'] . "'";
        }
        
        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    /**
     * الحصول على أعلى المنتجات قيمة
     */
    public function getTopValueProducts($limit = 10, $data = array()) {
        $sql = "
            SELECT 
                p.product_id,
                pd.name as product_name,
                p.model,
                SUM(pi.quantity * p.average_cost) as total_value,
                SUM(pi.quantity) as total_quantity
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND pi.quantity > 0
        ";
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $sql .= "
            GROUP BY p.product_id
            ORDER BY total_value DESC
            LIMIT " . (int)$limit;
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على المنتجات بطيئة الحركة
     */
    public function getSlowMovingProducts($days = 90, $limit = 10, $data = array()) {
        $sql = "
            SELECT 
                p.product_id,
                pd.name as product_name,
                p.model,
                pi.quantity,
                (pi.quantity * p.average_cost) as total_value,
                DATEDIFF(NOW(), (SELECT pm.date_added FROM " . DB_PREFIX . "cod_product_movement pm 
                                WHERE pm.product_id = p.product_id 
                                AND pm.branch_id = pi.branch_id 
                                ORDER BY pm.date_added DESC LIMIT 1)) as days_since_last_movement
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND pi.quantity > 0
            AND DATEDIFF(NOW(), (SELECT pm.date_added FROM " . DB_PREFIX . "cod_product_movement pm 
                                WHERE pm.product_id = p.product_id 
                                AND pm.branch_id = pi.branch_id 
                                ORDER BY pm.date_added DESC LIMIT 1)) >= " . (int)$days . "
        ";
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $sql .= "
            ORDER BY days_since_last_movement DESC, total_value DESC
            LIMIT " . (int)$limit;
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على تقرير الأرصدة حسب الفرع
     */
    public function getStockByBranch($data = array()) {
        $sql = "
            SELECT 
                b.branch_id,
                b.name as branch_name,
                b.type as branch_type,
                COUNT(DISTINCT p.product_id) as total_products,
                SUM(pi.quantity) as total_quantity,
                SUM(pi.quantity * p.average_cost) as total_value,
                SUM(CASE WHEN pi.quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock_count,
                SUM(CASE WHEN pi.quantity > 0 AND pi.quantity <= p.minimum_quantity THEN 1 ELSE 0 END) as low_stock_count
            FROM " . DB_PREFIX . "cod_branch b
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (b.branch_id = pi.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pi.product_id = p.product_id)
            WHERE b.status = 1
        ";
        
        if (!empty($data['filter_branch_type'])) {
            $sql .= " AND b.type = '" . $this->db->escape($data['filter_branch_type']) . "'";
        }
        
        $sql .= "
            GROUP BY b.branch_id
            ORDER BY total_value DESC
        ";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على تقرير الأرصدة حسب التصنيف
     */
    public function getStockByCategory($data = array()) {
        $sql = "
            SELECT 
                c.category_id,
                cd.name as category_name,
                COUNT(DISTINCT p.product_id) as total_products,
                SUM(pi.quantity) as total_quantity,
                SUM(pi.quantity * p.average_cost) as total_value,
                AVG(p.average_cost) as avg_cost
            FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (c.category_id = p.category_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND c.status = 1
        ";
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $sql .= "
            GROUP BY c.category_id
            HAVING total_quantity > 0
            ORDER BY total_value DESC
        ";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * تصدير البيانات للإكسل
     */
    public function exportToExcel($data = array()) {
        // إزالة حدود الصفحات للتصدير
        unset($data['start']);
        unset($data['limit']);
        
        return $this->getCurrentStockLevels($data);
    }
}
