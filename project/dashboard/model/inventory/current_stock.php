<?php
/**
 * AYM ERP - Inventory Current Stock Model
 * 
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelInventoryCurrentStock extends Model {
    
    public function getCurrentStock($data = array()) {
        $sql = "SELECT 
            p.product_id,
            pd.name as product_name,
            p.model,
            p.sku,
            p.price,
            p.cost,
            cd.name as category_name,
            w.name as warehouse_name,
            COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0) as current_stock,
            COALESCE(SUM(CASE WHEN sr.status = 'active' THEN sr.quantity ELSE 0 END), 0) as reserved_stock,
            (COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
             COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0) - 
             COALESCE(SUM(CASE WHEN sr.status = 'active' THEN sr.quantity ELSE 0 END), 0)) as available_stock,
            p.cost as unit_cost,
            ((COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
              COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0)) * p.cost) as total_value,
            p.minimum as reorder_level,
            p.maximum as max_level,
            MAX(sm.date_added) as last_movement_date,
            CASE 
                WHEN (COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
                      COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0)) <= 0 THEN 'out_of_stock'
                WHEN (COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
                      COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0)) <= p.minimum THEN 'low_stock'
                WHEN (COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
                      COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0)) >= p.maximum THEN 'overstock'
                ELSE 'in_stock'
            END as status
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "stock_movement sm ON (p.product_id = sm.product_id)
            LEFT JOIN " . DB_PREFIX . "warehouse w ON (sm.warehouse_id = w.warehouse_id)
            LEFT JOIN " . DB_PREFIX . "stock_reservation sr ON (p.product_id = sr.product_id)
            WHERE p.status = '1'";
        
        if (!empty($data['filter_product_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_product_name']) . "%'";
        }
        
        if (!empty($data['filter_sku'])) {
            $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
        }
        
        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
        }
        
        if (!empty($data['filter_warehouse_id'])) {
            $sql .= " AND sm.warehouse_id = '" . (int)$data['filter_warehouse_id'] . "'";
        }
        
        $sql .= " GROUP BY p.product_id, w.warehouse_id";
        
        if (!empty($data['filter_stock_status'])) {
            $sql .= " HAVING status = '" . $this->db->escape($data['filter_stock_status']) . "'";
        }
        
        $sort_data = array(
            'pd.name',
            'p.model',
            'p.sku',
            'current_stock',
            'available_stock',
            'total_value',
            'last_movement_date'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name";
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
        
        $results = array();
        
        foreach ($query->rows as $row) {
            $row['status_text'] = $this->getStatusText($row['status']);
            $row['status_class'] = $this->getStatusClass($row['status']);
            $results[] = $row;
        }
        
        return $results;
    }
    
    public function getTotalCurrentStock($data = array()) {
        $sql = "SELECT COUNT(DISTINCT p.product_id) AS total
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
            LEFT JOIN " . DB_PREFIX . "stock_movement sm ON (p.product_id = sm.product_id)
            WHERE p.status = '1'";
        
        if (!empty($data['filter_product_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_product_name']) . "%'";
        }
        
        if (!empty($data['filter_sku'])) {
            $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
        }
        
        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
        }
        
        if (!empty($data['filter_warehouse_id'])) {
            $sql .= " AND sm.warehouse_id = '" . (int)$data['filter_warehouse_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    public function getStockSummary() {
        $sql = "SELECT 
            COUNT(DISTINCT p.product_id) as total_products,
            SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END) as out_of_stock_count,
            SUM(CASE WHEN current_stock <= p.minimum AND current_stock > 0 THEN 1 ELSE 0 END) as low_stock_count,
            SUM(CASE WHEN current_stock >= p.maximum THEN 1 ELSE 0 END) as overstock_count,
            SUM(current_stock * p.cost) as total_inventory_value,
            AVG(current_stock) as avg_stock_level
            FROM (
                SELECT 
                    p.product_id,
                    p.minimum,
                    p.maximum,
                    p.cost,
                    COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
                    COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0) as current_stock
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "stock_movement sm ON (p.product_id = sm.product_id)
                WHERE p.status = '1'
                GROUP BY p.product_id
            ) as stock_data
            JOIN " . DB_PREFIX . "product p ON (stock_data.product_id = p.product_id)";
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    public function getCategoryAnalysis() {
        $sql = "SELECT 
            cd.name as category_name,
            COUNT(DISTINCT p.product_id) as product_count,
            SUM(current_stock) as total_stock,
            SUM(current_stock * p.cost) as total_value,
            AVG(current_stock) as avg_stock
            FROM (
                SELECT 
                    p.product_id,
                    p.cost,
                    COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
                    COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0) as current_stock
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "stock_movement sm ON (p.product_id = sm.product_id)
                WHERE p.status = '1'
                GROUP BY p.product_id
            ) as stock_data
            JOIN " . DB_PREFIX . "product p ON (stock_data.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            GROUP BY p2c.category_id
            ORDER BY total_value DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getWarehouseAnalysis() {
        $sql = "SELECT 
            w.name as warehouse_name,
            COUNT(DISTINCT sm.product_id) as product_count,
            SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END) - 
            SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END) as total_stock,
            SUM((CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END) - 
                (CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END)) * p.cost as total_value
            FROM " . DB_PREFIX . "warehouse w
            LEFT JOIN " . DB_PREFIX . "stock_movement sm ON (w.warehouse_id = sm.warehouse_id)
            LEFT JOIN " . DB_PREFIX . "product p ON (sm.product_id = p.product_id)
            WHERE w.status = '1'
            GROUP BY w.warehouse_id
            ORDER BY total_value DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getValuationAnalysis() {
        $sql = "SELECT 
            SUM(CASE WHEN current_stock > 0 THEN current_stock * p.cost ELSE 0 END) as positive_value,
            SUM(CASE WHEN current_stock < 0 THEN ABS(current_stock) * p.cost ELSE 0 END) as negative_value,
            COUNT(CASE WHEN current_stock > 0 THEN 1 END) as positive_count,
            COUNT(CASE WHEN current_stock < 0 THEN 1 END) as negative_count,
            COUNT(CASE WHEN current_stock = 0 THEN 1 END) as zero_count
            FROM (
                SELECT 
                    p.product_id,
                    p.cost,
                    COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
                    COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0) as current_stock
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "stock_movement sm ON (p.product_id = sm.product_id)
                WHERE p.status = '1'
                GROUP BY p.product_id
            ) as stock_data
            JOIN " . DB_PREFIX . "product p ON (stock_data.product_id = p.product_id)";
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    public function getMovementTrends() {
        $sql = "SELECT 
            DATE(sm.date_added) as movement_date,
            SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END) as total_in,
            SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END) as total_out,
            COUNT(DISTINCT sm.product_id) as products_moved
            FROM " . DB_PREFIX . "stock_movement sm
            WHERE sm.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(sm.date_added)
            ORDER BY movement_date DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getLowStockAlerts() {
        $sql = "SELECT 
            p.product_id,
            pd.name as product_name,
            p.sku,
            current_stock,
            p.minimum as reorder_level,
            (p.minimum - current_stock) as shortage_quantity
            FROM (
                SELECT 
                    p.product_id,
                    COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
                    COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0) as current_stock
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "stock_movement sm ON (p.product_id = sm.product_id)
                WHERE p.status = '1'
                GROUP BY p.product_id
            ) as stock_data
            JOIN " . DB_PREFIX . "product p ON (stock_data.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE current_stock <= p.minimum AND p.minimum > 0
            ORDER BY (p.minimum - current_stock) DESC
            LIMIT 20";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getOverstockAlerts() {
        $sql = "SELECT 
            p.product_id,
            pd.name as product_name,
            p.sku,
            current_stock,
            p.maximum as max_level,
            (current_stock - p.maximum) as excess_quantity
            FROM (
                SELECT 
                    p.product_id,
                    COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
                    COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0) as current_stock
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "stock_movement sm ON (p.product_id = sm.product_id)
                WHERE p.status = '1'
                GROUP BY p.product_id
            ) as stock_data
            JOIN " . DB_PREFIX . "product p ON (stock_data.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE current_stock >= p.maximum AND p.maximum > 0
            ORDER BY (current_stock - p.maximum) DESC
            LIMIT 20";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getAgingAnalysis() {
        $sql = "SELECT 
            CASE 
                WHEN DATEDIFF(NOW(), last_movement) <= 30 THEN '0-30 days'
                WHEN DATEDIFF(NOW(), last_movement) <= 60 THEN '31-60 days'
                WHEN DATEDIFF(NOW(), last_movement) <= 90 THEN '61-90 days'
                WHEN DATEDIFF(NOW(), last_movement) <= 180 THEN '91-180 days'
                ELSE '180+ days'
            END as age_group,
            COUNT(*) as product_count,
            SUM(current_stock * p.cost) as total_value
            FROM (
                SELECT 
                    p.product_id,
                    p.cost,
                    COALESCE(SUM(CASE WHEN sm.type IN ('in', 'adjustment_in', 'transfer_in') THEN sm.quantity ELSE 0 END), 0) - 
                    COALESCE(SUM(CASE WHEN sm.type IN ('out', 'adjustment_out', 'transfer_out') THEN sm.quantity ELSE 0 END), 0) as current_stock,
                    MAX(sm.date_added) as last_movement
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "stock_movement sm ON (p.product_id = sm.product_id)
                WHERE p.status = '1'
                GROUP BY p.product_id
                HAVING current_stock > 0
            ) as stock_data
            JOIN " . DB_PREFIX . "product p ON (stock_data.product_id = p.product_id)
            GROUP BY age_group
            ORDER BY FIELD(age_group, '0-30 days', '31-60 days', '61-90 days', '91-180 days', '180+ days')";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function updateReorderLevels($reorder_levels) {
        foreach ($reorder_levels as $product_id => $levels) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET 
                minimum = '" . (float)$levels['minimum'] . "', 
                maximum = '" . (float)$levels['maximum'] . "' 
                WHERE product_id = '" . (int)$product_id . "'");
        }
    }
    
    private function getStatusText($status) {
        switch ($status) {
            case 'out_of_stock':
                return 'نفد المخزون';
            case 'low_stock':
                return 'مخزون منخفض';
            case 'overstock':
                return 'مخزون زائد';
            case 'in_stock':
            default:
                return 'متوفر';
        }
    }
    
    private function getStatusClass($status) {
        switch ($status) {
            case 'out_of_stock':
                return 'danger';
            case 'low_stock':
                return 'warning';
            case 'overstock':
                return 'info';
            case 'in_stock':
            default:
                return 'success';
        }
    }
}
