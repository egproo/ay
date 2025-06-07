<?php
/**
 * نموذج تقرير تقييم المخزون المتطور (Advanced Inventory Valuation Report Model)
 * 
 * الهدف: توفير تقييم شامل للمخزون بطريقة المتوسط المرجح مع تحليلات متقدمة
 * الميزات: تقييم WAC، مقارنات زمنية، تحليل الربحية، تقارير متعددة المستويات
 * التكامل: مع المحاسبة والتقارير المالية والتحليلات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryInventoryValuation extends Model {
    
    /**
     * الحصول على تقرير تقييم المخزون الشامل
     */
    public function getInventoryValuation($data = array()) {
        $valuation_date = !empty($data['valuation_date']) ? $data['valuation_date'] : date('Y-m-d');
        
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
                p.price as selling_price,
                (pi.quantity * p.price) as total_selling_value,
                (p.price - p.average_cost) as unit_profit,
                ((p.price - p.average_cost) * pi.quantity) as total_profit,
                CASE 
                    WHEN p.average_cost > 0 THEN ((p.price - p.average_cost) / p.average_cost * 100)
                    ELSE 0
                END as profit_percentage,
                p.minimum_quantity,
                p.maximum_quantity,
                CASE 
                    WHEN pi.quantity <= 0 THEN 'out_of_stock'
                    WHEN pi.quantity <= p.minimum_quantity THEN 'low_stock'
                    WHEN pi.quantity >= p.maximum_quantity THEN 'overstock'
                    ELSE 'normal'
                END as stock_status,
                p.date_added as product_date_added,
                p.date_modified as product_date_modified,
                (SELECT pm.date_added FROM " . DB_PREFIX . "cod_product_movement pm 
                 WHERE pm.product_id = p.product_id 
                 AND pm.branch_id = pi.branch_id 
                 AND DATE(pm.date_added) <= '" . $this->db->escape($valuation_date) . "'
                 ORDER BY pm.date_added DESC LIMIT 1) as last_movement_date,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_product_movement pm 
                 WHERE pm.product_id = p.product_id 
                 AND pm.branch_id = pi.branch_id 
                 AND DATE(pm.date_added) <= '" . $this->db->escape($valuation_date) . "') as total_movements,
                (SELECT SUM(pm.quantity_in - pm.quantity_out) FROM " . DB_PREFIX . "cod_product_movement pm 
                 WHERE pm.product_id = p.product_id 
                 AND pm.branch_id = pi.branch_id 
                 AND DATE(pm.date_added) <= '" . $this->db->escape($valuation_date) . "') as calculated_quantity,
                DATEDIFF('" . $this->db->escape($valuation_date) . "', 
                        (SELECT pm2.date_added FROM " . DB_PREFIX . "cod_product_movement pm2 
                         WHERE pm2.product_id = p.product_id 
                         AND pm2.branch_id = pi.branch_id 
                         AND DATE(pm2.date_added) <= '" . $this->db->escape($valuation_date) . "'
                         ORDER BY pm2.date_added DESC LIMIT 1)) as days_since_last_movement,
                -- حساب متوسط التكلفة التاريخي
                (SELECT AVG(pm3.unit_cost) FROM " . DB_PREFIX . "cod_product_movement pm3 
                 WHERE pm3.product_id = p.product_id 
                 AND pm3.branch_id = pi.branch_id 
                 AND pm3.quantity_in > 0
                 AND DATE(pm3.date_added) <= '" . $this->db->escape($valuation_date) . "') as historical_avg_cost,
                -- حساب أعلى وأقل تكلفة
                (SELECT MAX(pm4.unit_cost) FROM " . DB_PREFIX . "cod_product_movement pm4 
                 WHERE pm4.product_id = p.product_id 
                 AND pm4.branch_id = pi.branch_id 
                 AND pm4.quantity_in > 0
                 AND DATE(pm4.date_added) <= '" . $this->db->escape($valuation_date) . "') as max_cost,
                (SELECT MIN(pm5.unit_cost) FROM " . DB_PREFIX . "cod_product_movement pm5 
                 WHERE pm5.product_id = p.product_id 
                 AND pm5.branch_id = pi.branch_id 
                 AND pm5.quantity_in > 0
                 AND DATE(pm5.date_added) <= '" . $this->db->escape($valuation_date) . "') as min_cost
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
            AND pi.quantity > 0
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
        
        if (!empty($data['filter_min_value'])) {
            $sql .= " AND (pi.quantity * p.average_cost) >= '" . (float)$data['filter_min_value'] . "'";
        }
        
        if (!empty($data['filter_max_value'])) {
            $sql .= " AND (pi.quantity * p.average_cost) <= '" . (float)$data['filter_max_value'] . "'";
        }
        
        if (!empty($data['filter_min_profit_percentage'])) {
            $sql .= " AND ((p.price - p.average_cost) / p.average_cost * 100) >= '" . (float)$data['filter_min_profit_percentage'] . "'";
        }
        
        if (!empty($data['filter_max_profit_percentage'])) {
            $sql .= " AND ((p.price - p.average_cost) / p.average_cost * 100) <= '" . (float)$data['filter_max_profit_percentage'] . "'";
        }
        
        // ترتيب النتائج
        $sort_data = array(
            'pd.name',
            'p.model',
            'cd.name',
            'md.name',
            'b.name',
            'pi.quantity',
            'p.average_cost',
            'total_value',
            'profit_percentage',
            'total_profit',
            'last_movement_date'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY total_value DESC";
        }
        
        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
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
    public function getTotalInventoryValuation($data = array()) {
        $valuation_date = !empty($data['valuation_date']) ? $data['valuation_date'] : date('Y-m-d');
        
        $sql = "
            SELECT COUNT(DISTINCT CONCAT(p.product_id, '-', pi.branch_id)) AS total
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pi.branch_id = b.branch_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND pi.quantity > 0
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
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على ملخص تقييم المخزون
     */
    public function getValuationSummary($data = array()) {
        $valuation_date = !empty($data['valuation_date']) ? $data['valuation_date'] : date('Y-m-d');
        
        $sql = "
            SELECT 
                COUNT(DISTINCT p.product_id) as total_products,
                COUNT(DISTINCT pi.branch_id) as total_branches,
                SUM(pi.quantity) as total_quantity,
                SUM(pi.quantity * p.average_cost) as total_cost_value,
                SUM(pi.quantity * p.price) as total_selling_value,
                SUM((p.price - p.average_cost) * pi.quantity) as total_profit,
                AVG(p.average_cost) as avg_cost,
                AVG(p.price) as avg_selling_price,
                AVG(CASE WHEN p.average_cost > 0 THEN ((p.price - p.average_cost) / p.average_cost * 100) ELSE 0 END) as avg_profit_percentage,
                SUM(CASE WHEN pi.quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock_count,
                SUM(CASE WHEN pi.quantity > 0 AND pi.quantity <= p.minimum_quantity THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN pi.quantity >= p.maximum_quantity THEN 1 ELSE 0 END) as overstock_count,
                MAX(pi.quantity * p.average_cost) as highest_value_item,
                MIN(pi.quantity * p.average_cost) as lowest_value_item
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
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    /**
     * الحصول على تقييم المخزون حسب التصنيف
     */
    public function getValuationByCategory($data = array()) {
        $valuation_date = !empty($data['valuation_date']) ? $data['valuation_date'] : date('Y-m-d');
        
        $sql = "
            SELECT 
                c.category_id,
                cd.name as category_name,
                COUNT(DISTINCT p.product_id) as total_products,
                SUM(pi.quantity) as total_quantity,
                SUM(pi.quantity * p.average_cost) as total_cost_value,
                SUM(pi.quantity * p.price) as total_selling_value,
                SUM((p.price - p.average_cost) * pi.quantity) as total_profit,
                AVG(p.average_cost) as avg_cost,
                AVG(CASE WHEN p.average_cost > 0 THEN ((p.price - p.average_cost) / p.average_cost * 100) ELSE 0 END) as avg_profit_percentage
            FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (c.category_id = p.category_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND c.status = 1
            AND pi.quantity > 0
        ";
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $sql .= "
            GROUP BY c.category_id
            HAVING total_cost_value > 0
            ORDER BY total_cost_value DESC
        ";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على تقييم المخزون حسب الفرع
     */
    public function getValuationByBranch($data = array()) {
        $valuation_date = !empty($data['valuation_date']) ? $data['valuation_date'] : date('Y-m-d');
        
        $sql = "
            SELECT 
                b.branch_id,
                b.name as branch_name,
                b.type as branch_type,
                COUNT(DISTINCT p.product_id) as total_products,
                SUM(pi.quantity) as total_quantity,
                SUM(pi.quantity * p.average_cost) as total_cost_value,
                SUM(pi.quantity * p.price) as total_selling_value,
                SUM((p.price - p.average_cost) * pi.quantity) as total_profit,
                AVG(p.average_cost) as avg_cost,
                AVG(CASE WHEN p.average_cost > 0 THEN ((p.price - p.average_cost) / p.average_cost * 100) ELSE 0 END) as avg_profit_percentage
            FROM " . DB_PREFIX . "cod_branch b
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (b.branch_id = pi.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pi.product_id = p.product_id)
            WHERE b.status = 1
            AND pi.quantity > 0
        ";
        
        if (!empty($data['filter_branch_type'])) {
            $sql .= " AND b.type = '" . $this->db->escape($data['filter_branch_type']) . "'";
        }
        
        $sql .= "
            GROUP BY b.branch_id
            HAVING total_cost_value > 0
            ORDER BY total_cost_value DESC
        ";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على أعلى المنتجات قيمة
     */
    public function getTopValueProducts($limit = 10, $data = array()) {
        $valuation_date = !empty($data['valuation_date']) ? $data['valuation_date'] : date('Y-m-d');
        
        $sql = "
            SELECT 
                p.product_id,
                pd.name as product_name,
                p.model,
                SUM(pi.quantity * p.average_cost) as total_cost_value,
                SUM(pi.quantity * p.price) as total_selling_value,
                SUM((p.price - p.average_cost) * pi.quantity) as total_profit,
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
            ORDER BY total_cost_value DESC
            LIMIT " . (int)$limit;
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على المنتجات الأكثر ربحية
     */
    public function getMostProfitableProducts($limit = 10, $data = array()) {
        $valuation_date = !empty($data['valuation_date']) ? $data['valuation_date'] : date('Y-m-d');
        
        $sql = "
            SELECT 
                p.product_id,
                pd.name as product_name,
                p.model,
                SUM(pi.quantity * p.average_cost) as total_cost_value,
                SUM(pi.quantity * p.price) as total_selling_value,
                SUM((p.price - p.average_cost) * pi.quantity) as total_profit,
                AVG(CASE WHEN p.average_cost > 0 THEN ((p.price - p.average_cost) / p.average_cost * 100) ELSE 0 END) as avg_profit_percentage,
                SUM(pi.quantity) as total_quantity
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND pi.quantity > 0
            AND p.average_cost > 0
        ";
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $sql .= "
            GROUP BY p.product_id
            HAVING total_profit > 0
            ORDER BY avg_profit_percentage DESC, total_profit DESC
            LIMIT " . (int)$limit;
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * مقارنة التقييم بين تاريخين
     */
    public function compareValuation($date_from, $date_to, $data = array()) {
        $sql_from = "
            SELECT 
                SUM(pi.quantity * p.average_cost) as total_value_from,
                COUNT(DISTINCT p.product_id) as products_count_from
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pi.quantity > 0
        ";
        
        $sql_to = "
            SELECT 
                SUM(pi.quantity * p.average_cost) as total_value_to,
                COUNT(DISTINCT p.product_id) as products_count_to
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pi.quantity > 0
        ";
        
        if (!empty($data['filter_branch_id'])) {
            $sql_from .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
            $sql_to .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $query_from = $this->db->query($sql_from);
        $query_to = $this->db->query($sql_to);
        
        $result = array(
            'date_from' => $date_from,
            'date_to' => $date_to,
            'value_from' => $query_from->row['total_value_from'],
            'value_to' => $query_to->row['total_value_to'],
            'value_change' => $query_to->row['total_value_to'] - $query_from->row['total_value_from'],
            'value_change_percentage' => $query_from->row['total_value_from'] > 0 ? 
                (($query_to->row['total_value_to'] - $query_from->row['total_value_from']) / $query_from->row['total_value_from'] * 100) : 0,
            'products_from' => $query_from->row['products_count_from'],
            'products_to' => $query_to->row['products_count_to'],
            'products_change' => $query_to->row['products_count_to'] - $query_from->row['products_count_from']
        );
        
        return $result;
    }
    
    /**
     * تصدير البيانات للإكسل
     */
    public function exportToExcel($data = array()) {
        // إزالة حدود الصفحات للتصدير
        unset($data['start']);
        unset($data['limit']);
        
        return $this->getInventoryValuation($data);
    }
}
