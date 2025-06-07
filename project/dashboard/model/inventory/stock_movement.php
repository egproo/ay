<?php
/**
 * نموذج سجل حركة المخزون المتطور (Advanced Stock Movement Ledger Model)
 * 
 * الهدف: توفير تتبع شامل لجميع حركات المخزون مع حسابات WAC وتتبع الدفعات
 * الميزات: كارت صنف تفصيلي، حسابات تراكمية، ربط بالمستندات، تتبع الدفعات
 * التكامل: مع المحاسبة والمشتريات والمبيعات والتحويلات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryStockMovement extends Model {
    
    /**
     * الحصول على حركات المخزون مع فلاتر متقدمة
     */
    public function getStockMovements($data = array()) {
        $sql = "
            SELECT 
                pm.movement_id,
                pm.product_id,
                pd.name as product_name,
                p.model,
                p.sku,
                pm.branch_id,
                b.name as branch_name,
                b.type as branch_type,
                pm.movement_type,
                pm.reference_type,
                pm.reference_id,
                pm.reference_number,
                pm.lot_number,
                pm.expiry_date,
                pm.unit_id,
                ud.name as unit_name,
                u.symbol as unit_symbol,
                pm.quantity_in,
                pm.quantity_out,
                pm.quantity_balance,
                pm.unit_cost,
                pm.total_cost,
                pm.average_cost_before,
                pm.average_cost_after,
                pm.notes,
                pm.user_id,
                CONCAT(us.firstname, ' ', us.lastname) as user_name,
                pm.date_added,
                pm.date_modified,
                c.category_id,
                cd.name as category_name,
                m.manufacturer_id,
                md.name as manufacturer_name,
                CASE pm.movement_type
                    WHEN 'purchase' THEN 'وارد شراء'
                    WHEN 'sale' THEN 'صادر بيع'
                    WHEN 'transfer_in' THEN 'وارد تحويل'
                    WHEN 'transfer_out' THEN 'صادر تحويل'
                    WHEN 'adjustment_in' THEN 'تسوية زيادة'
                    WHEN 'adjustment_out' THEN 'تسوية نقص'
                    WHEN 'production_in' THEN 'وارد إنتاج'
                    WHEN 'production_out' THEN 'صادر إنتاج'
                    WHEN 'return_in' THEN 'وارد مرتجع'
                    WHEN 'return_out' THEN 'صادر مرتجع'
                    WHEN 'opening_balance' THEN 'رصيد افتتاحي'
                    WHEN 'physical_count' THEN 'جرد فعلي'
                    ELSE pm.movement_type
                END as movement_type_text,
                CASE pm.reference_type
                    WHEN 'purchase_order' THEN 'أمر شراء'
                    WHEN 'purchase_invoice' THEN 'فاتورة شراء'
                    WHEN 'sale_order' THEN 'أمر بيع'
                    WHEN 'sale_invoice' THEN 'فاتورة بيع'
                    WHEN 'stock_transfer' THEN 'تحويل مخزني'
                    WHEN 'stock_adjustment' THEN 'تسوية مخزنية'
                    WHEN 'production_order' THEN 'أمر إنتاج'
                    WHEN 'physical_inventory' THEN 'جرد فعلي'
                    ELSE pm.reference_type
                END as reference_type_text,
                (pm.quantity_in - pm.quantity_out) as net_quantity,
                (SELECT SUM(pm2.quantity_in - pm2.quantity_out) 
                 FROM " . DB_PREFIX . "cod_product_movement pm2 
                 WHERE pm2.product_id = pm.product_id 
                 AND pm2.branch_id = pm.branch_id 
                 AND pm2.movement_id <= pm.movement_id) as running_balance
            FROM " . DB_PREFIX . "cod_product_movement pm
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pm.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (pm.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pm.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "user us ON (pm.user_id = us.user_id)
            LEFT JOIN " . DB_PREFIX . "category c ON (p.category_id = c.category_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
            LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND md.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        // تطبيق الفلاتر
        if (!empty($data['filter_product_id'])) {
            $sql .= " AND pm.product_id = '" . (int)$data['filter_product_id'] . "'";
        }
        
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
            $sql .= " AND pm.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_branch_type'])) {
            $sql .= " AND b.type = '" . $this->db->escape($data['filter_branch_type']) . "'";
        }
        
        if (!empty($data['filter_movement_type'])) {
            $sql .= " AND pm.movement_type = '" . $this->db->escape($data['filter_movement_type']) . "'";
        }
        
        if (!empty($data['filter_reference_type'])) {
            $sql .= " AND pm.reference_type = '" . $this->db->escape($data['filter_reference_type']) . "'";
        }
        
        if (!empty($data['filter_reference_number'])) {
            $sql .= " AND pm.reference_number LIKE '%" . $this->db->escape($data['filter_reference_number']) . "%'";
        }
        
        if (!empty($data['filter_lot_number'])) {
            $sql .= " AND pm.lot_number LIKE '%" . $this->db->escape($data['filter_lot_number']) . "%'";
        }
        
        if (!empty($data['filter_user_id'])) {
            $sql .= " AND pm.user_id = '" . (int)$data['filter_user_id'] . "'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pm.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pm.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        if (isset($data['filter_has_expiry']) && $data['filter_has_expiry'] !== '') {
            if ($data['filter_has_expiry']) {
                $sql .= " AND pm.expiry_date IS NOT NULL";
            } else {
                $sql .= " AND pm.expiry_date IS NULL";
            }
        }
        
        if (!empty($data['filter_expiry_from'])) {
            $sql .= " AND DATE(pm.expiry_date) >= '" . $this->db->escape($data['filter_expiry_from']) . "'";
        }
        
        if (!empty($data['filter_expiry_to'])) {
            $sql .= " AND DATE(pm.expiry_date) <= '" . $this->db->escape($data['filter_expiry_to']) . "'";
        }
        
        // ترتيب النتائج
        $sort_data = array(
            'pm.date_added',
            'pd.name',
            'p.model',
            'b.name',
            'pm.movement_type',
            'pm.reference_number',
            'pm.quantity_in',
            'pm.quantity_out',
            'pm.unit_cost',
            'pm.total_cost',
            'pm.average_cost_after',
            'running_balance'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pm.date_added DESC, pm.movement_id DESC";
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
     * الحصول على إجمالي عدد الحركات
     */
    public function getTotalStockMovements($data = array()) {
        $sql = "
            SELECT COUNT(DISTINCT pm.movement_id) AS total
            FROM " . DB_PREFIX . "cod_product_movement pm
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pm.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "user us ON (pm.user_id = us.user_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        // تطبيق نفس الفلاتر
        if (!empty($data['filter_product_id'])) {
            $sql .= " AND pm.product_id = '" . (int)$data['filter_product_id'] . "'";
        }
        
        if (!empty($data['filter_product_name'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product_name']) . "%' 
                     OR p.model LIKE '%" . $this->db->escape($data['filter_product_name']) . "%'
                     OR p.sku LIKE '%" . $this->db->escape($data['filter_product_name']) . "%')";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pm.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_movement_type'])) {
            $sql .= " AND pm.movement_type = '" . $this->db->escape($data['filter_movement_type']) . "'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pm.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pm.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على ملخص الحركات
     */
    public function getMovementSummary($data = array()) {
        $sql = "
            SELECT 
                COUNT(DISTINCT pm.movement_id) as total_movements,
                COUNT(DISTINCT pm.product_id) as total_products,
                COUNT(DISTINCT pm.branch_id) as total_branches,
                SUM(pm.quantity_in) as total_quantity_in,
                SUM(pm.quantity_out) as total_quantity_out,
                SUM(pm.total_cost) as total_value,
                AVG(pm.unit_cost) as avg_unit_cost,
                COUNT(DISTINCT pm.lot_number) as total_lots,
                COUNT(CASE WHEN pm.expiry_date IS NOT NULL THEN 1 END) as movements_with_expiry
            FROM " . DB_PREFIX . "cod_product_movement pm
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pm.branch_id = b.branch_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        // تطبيق الفلاتر
        if (!empty($data['filter_product_id'])) {
            $sql .= " AND pm.product_id = '" . (int)$data['filter_product_id'] . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pm.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_movement_type'])) {
            $sql .= " AND pm.movement_type = '" . $this->db->escape($data['filter_movement_type']) . "'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pm.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pm.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    /**
     * الحصول على حركات حسب النوع
     */
    public function getMovementsByType($data = array()) {
        $sql = "
            SELECT 
                pm.movement_type,
                COUNT(*) as movement_count,
                SUM(pm.quantity_in) as total_quantity_in,
                SUM(pm.quantity_out) as total_quantity_out,
                SUM(pm.total_cost) as total_value,
                AVG(pm.unit_cost) as avg_unit_cost
            FROM " . DB_PREFIX . "cod_product_movement pm
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        // تطبيق الفلاتر
        if (!empty($data['filter_product_id'])) {
            $sql .= " AND pm.product_id = '" . (int)$data['filter_product_id'] . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pm.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pm.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pm.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        $sql .= "
            GROUP BY pm.movement_type
            ORDER BY total_value DESC
        ";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على كارت الصنف لمنتج محدد
     */
    public function getProductCard($product_id, $branch_id = 0, $data = array()) {
        $sql = "
            SELECT 
                pm.*,
                pd.name as product_name,
                p.model,
                p.sku,
                b.name as branch_name,
                ud.name as unit_name,
                u.symbol as unit_symbol,
                CONCAT(us.firstname, ' ', us.lastname) as user_name,
                @running_balance := @running_balance + (pm.quantity_in - pm.quantity_out) as running_balance
            FROM " . DB_PREFIX . "cod_product_movement pm
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pm.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (pm.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pm.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "user us ON (pm.user_id = us.user_id)
            CROSS JOIN (SELECT @running_balance := 0) r
            WHERE pm.product_id = '" . (int)$product_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if ($branch_id > 0) {
            $sql .= " AND pm.branch_id = '" . (int)$branch_id . "'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pm.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pm.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        $sql .= " ORDER BY pm.date_added ASC, pm.movement_id ASC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على الدفعات المنتهية الصلاحية قريباً
     */
    public function getExpiringLots($days = 30, $data = array()) {
        $sql = "
            SELECT 
                pm.product_id,
                pd.name as product_name,
                p.model,
                pm.branch_id,
                b.name as branch_name,
                pm.lot_number,
                pm.expiry_date,
                SUM(pm.quantity_in - pm.quantity_out) as remaining_quantity,
                DATEDIFF(pm.expiry_date, NOW()) as days_to_expiry
            FROM " . DB_PREFIX . "cod_product_movement pm
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pm.branch_id = b.branch_id)
            WHERE pm.expiry_date IS NOT NULL
            AND pm.expiry_date <= DATE_ADD(NOW(), INTERVAL " . (int)$days . " DAY)
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pm.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $sql .= "
            GROUP BY pm.product_id, pm.branch_id, pm.lot_number, pm.expiry_date
            HAVING remaining_quantity > 0
            ORDER BY pm.expiry_date ASC
        ";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على تقرير الدفعات
     */
    public function getLotReport($data = array()) {
        $sql = "
            SELECT 
                pm.lot_number,
                pm.product_id,
                pd.name as product_name,
                p.model,
                pm.branch_id,
                b.name as branch_name,
                pm.expiry_date,
                SUM(pm.quantity_in) as total_in,
                SUM(pm.quantity_out) as total_out,
                SUM(pm.quantity_in - pm.quantity_out) as balance,
                MIN(pm.date_added) as first_movement,
                MAX(pm.date_added) as last_movement,
                COUNT(*) as movement_count
            FROM " . DB_PREFIX . "cod_product_movement pm
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pm.branch_id = b.branch_id)
            WHERE pm.lot_number IS NOT NULL
            AND pm.lot_number != ''
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if (!empty($data['filter_product_id'])) {
            $sql .= " AND pm.product_id = '" . (int)$data['filter_product_id'] . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pm.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_lot_number'])) {
            $sql .= " AND pm.lot_number LIKE '%" . $this->db->escape($data['filter_lot_number']) . "%'";
        }
        
        $sql .= "
            GROUP BY pm.lot_number, pm.product_id, pm.branch_id
            ORDER BY pm.expiry_date ASC, pd.name ASC
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
        
        return $this->getStockMovements($data);
    }
    
    /**
     * الحصول على أنواع الحركات
     */
    public function getMovementTypes() {
        return array(
            'purchase' => 'وارد شراء',
            'sale' => 'صادر بيع',
            'transfer_in' => 'وارد تحويل',
            'transfer_out' => 'صادر تحويل',
            'adjustment_in' => 'تسوية زيادة',
            'adjustment_out' => 'تسوية نقص',
            'production_in' => 'وارد إنتاج',
            'production_out' => 'صادر إنتاج',
            'return_in' => 'وارد مرتجع',
            'return_out' => 'صادر مرتجع',
            'opening_balance' => 'رصيد افتتاحي',
            'physical_count' => 'جرد فعلي'
        );
    }
    
    /**
     * الحصول على أنواع المراجع
     */
    public function getReferenceTypes() {
        return array(
            'purchase_order' => 'أمر شراء',
            'purchase_invoice' => 'فاتورة شراء',
            'sale_order' => 'أمر بيع',
            'sale_invoice' => 'فاتورة بيع',
            'stock_transfer' => 'تحويل مخزني',
            'stock_adjustment' => 'تسوية مخزنية',
            'production_order' => 'أمر إنتاج',
            'physical_inventory' => 'جرد فعلي'
        );
    }
}
