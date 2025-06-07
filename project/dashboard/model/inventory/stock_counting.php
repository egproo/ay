<?php
/**
 * نموذج الجرد المخزني المتطور (Advanced Stock Counting Model)
 * 
 * الهدف: توفير نظام جرد شامل مع تتبع الفروقات وإنشاء التسويات التلقائية
 * الميزات: جرد دوري/مستمر، تتبع الحالة، تحليل الفروقات، workflow متقدم
 * التكامل: مع المحاسبة والتسويات والتقارير والتنبيهات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryStockCounting extends Model {
    
    /**
     * الحصول على عمليات الجرد مع فلاتر متقدمة
     */
    public function getStockCountings($data = array()) {
        $sql = "
            SELECT 
                sc.counting_id,
                sc.counting_number,
                sc.counting_name,
                sc.counting_type,
                sc.status,
                sc.branch_id,
                b.name as branch_name,
                b.type as branch_type,
                sc.category_id,
                cd.name as category_name,
                sc.user_id,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                sc.start_date,
                sc.end_date,
                sc.counting_date,
                sc.notes,
                sc.date_added,
                sc.date_modified,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_stock_counting_item sci 
                 WHERE sci.counting_id = sc.counting_id) as total_items,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_stock_counting_item sci 
                 WHERE sci.counting_id = sc.counting_id 
                 AND sci.actual_quantity IS NOT NULL) as counted_items,
                (SELECT SUM(ABS(sci.system_quantity - COALESCE(sci.actual_quantity, 0))) 
                 FROM " . DB_PREFIX . "cod_stock_counting_item sci 
                 WHERE sci.counting_id = sc.counting_id 
                 AND sci.actual_quantity IS NOT NULL) as total_variance_quantity,
                (SELECT SUM(ABS((sci.system_quantity - COALESCE(sci.actual_quantity, 0)) * sci.unit_cost)) 
                 FROM " . DB_PREFIX . "cod_stock_counting_item sci 
                 WHERE sci.counting_id = sc.counting_id 
                 AND sci.actual_quantity IS NOT NULL) as total_variance_value,
                CASE sc.status
                    WHEN 'draft' THEN 'مسودة'
                    WHEN 'in_progress' THEN 'قيد التنفيذ'
                    WHEN 'completed' THEN 'مكتمل'
                    WHEN 'posted' THEN 'مرحل'
                    WHEN 'cancelled' THEN 'ملغي'
                    ELSE sc.status
                END as status_text,
                CASE sc.counting_type
                    WHEN 'full' THEN 'جرد شامل'
                    WHEN 'partial' THEN 'جرد جزئي'
                    WHEN 'cycle' THEN 'جرد دوري'
                    WHEN 'spot' THEN 'جرد عشوائي'
                    ELSE sc.counting_type
                END as counting_type_text
            FROM " . DB_PREFIX . "cod_stock_counting sc
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (sc.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (sc.category_id = cd.category_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (sc.user_id = u.user_id)
            WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            OR sc.category_id IS NULL
        ";
        
        // تطبيق الفلاتر
        if (!empty($data['filter_counting_number'])) {
            $sql .= " AND sc.counting_number LIKE '%" . $this->db->escape($data['filter_counting_number']) . "%'";
        }
        
        if (!empty($data['filter_counting_name'])) {
            $sql .= " AND sc.counting_name LIKE '%" . $this->db->escape($data['filter_counting_name']) . "%'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND sc.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_counting_type'])) {
            $sql .= " AND sc.counting_type = '" . $this->db->escape($data['filter_counting_type']) . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND sc.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_category_id'])) {
            $sql .= " AND sc.category_id = '" . (int)$data['filter_category_id'] . "'";
        }
        
        if (!empty($data['filter_user_id'])) {
            $sql .= " AND sc.user_id = '" . (int)$data['filter_user_id'] . "'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(sc.counting_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(sc.counting_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        // ترتيب النتائج
        $sort_data = array(
            'sc.counting_number',
            'sc.counting_name',
            'sc.status',
            'sc.counting_type',
            'b.name',
            'sc.counting_date',
            'sc.date_added',
            'total_items',
            'total_variance_value'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sc.date_added DESC";
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
     * الحصول على إجمالي عدد عمليات الجرد
     */
    public function getTotalStockCountings($data = array()) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "cod_stock_counting sc
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (sc.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (sc.category_id = cd.category_id)
            WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            OR sc.category_id IS NULL
        ";
        
        // تطبيق نفس الفلاتر
        if (!empty($data['filter_counting_number'])) {
            $sql .= " AND sc.counting_number LIKE '%" . $this->db->escape($data['filter_counting_number']) . "%'";
        }
        
        if (!empty($data['filter_status'])) {
            $sql .= " AND sc.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND sc.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على عملية جرد محددة
     */
    public function getStockCounting($counting_id) {
        $query = $this->db->query("
            SELECT 
                sc.*,
                b.name as branch_name,
                cd.name as category_name,
                CONCAT(u.firstname, ' ', u.lastname) as user_name
            FROM " . DB_PREFIX . "cod_stock_counting sc
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (sc.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (sc.category_id = cd.category_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (sc.user_id = u.user_id)
            WHERE sc.counting_id = '" . (int)$counting_id . "'
            AND (cd.language_id = '" . (int)$this->config->get('config_language_id') . "' OR sc.category_id IS NULL)
        ");
        
        return $query->row;
    }
    
    /**
     * إضافة عملية جرد جديدة
     */
    public function addStockCounting($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_stock_counting 
            SET counting_number = '" . $this->db->escape($data['counting_number']) . "',
                counting_name = '" . $this->db->escape($data['counting_name']) . "',
                counting_type = '" . $this->db->escape($data['counting_type']) . "',
                status = 'draft',
                branch_id = '" . (int)$data['branch_id'] . "',
                category_id = " . (!empty($data['category_id']) ? "'" . (int)$data['category_id'] . "'" : "NULL") . ",
                user_id = '" . (int)$this->user->getId() . "',
                start_date = '" . $this->db->escape($data['start_date']) . "',
                end_date = '" . $this->db->escape($data['end_date']) . "',
                counting_date = '" . $this->db->escape($data['counting_date']) . "',
                notes = '" . $this->db->escape($data['notes']) . "',
                date_added = NOW(),
                date_modified = NOW()
        ");
        
        $counting_id = $this->db->getLastId();
        
        // إضافة المنتجات للجرد
        $this->generateCountingItems($counting_id, $data);
        
        return $counting_id;
    }
    
    /**
     * تحديث عملية جرد
     */
    public function editStockCounting($counting_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_stock_counting 
            SET counting_name = '" . $this->db->escape($data['counting_name']) . "',
                counting_type = '" . $this->db->escape($data['counting_type']) . "',
                branch_id = '" . (int)$data['branch_id'] . "',
                category_id = " . (!empty($data['category_id']) ? "'" . (int)$data['category_id'] . "'" : "NULL") . ",
                start_date = '" . $this->db->escape($data['start_date']) . "',
                end_date = '" . $this->db->escape($data['end_date']) . "',
                counting_date = '" . $this->db->escape($data['counting_date']) . "',
                notes = '" . $this->db->escape($data['notes']) . "',
                date_modified = NOW()
            WHERE counting_id = '" . (int)$counting_id . "'
            AND status = 'draft'
        ");
    }
    
    /**
     * حذف عملية جرد
     */
    public function deleteStockCounting($counting_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_stock_counting WHERE counting_id = '" . (int)$counting_id . "' AND status = 'draft'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_stock_counting_item WHERE counting_id = '" . (int)$counting_id . "'");
    }
    
    /**
     * توليد عناصر الجرد
     */
    public function generateCountingItems($counting_id, $data) {
        // حذف العناصر الموجودة
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_stock_counting_item WHERE counting_id = '" . (int)$counting_id . "'");
        
        // بناء استعلام المنتجات
        $sql = "
            SELECT 
                p.product_id,
                pd.name as product_name,
                p.model,
                p.sku,
                pi.quantity as system_quantity,
                p.average_cost as unit_cost,
                pi.unit_id,
                ud.name as unit_name
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (pi.unit_id = ud.unit_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND pi.branch_id = '" . (int)$data['branch_id'] . "'
            AND p.status = 1
        ";
        
        // تطبيق فلاتر التصنيف
        if (!empty($data['category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['category_id'] . "'";
        }
        
        // فلتر نوع الجرد
        switch ($data['counting_type']) {
            case 'partial':
                if (!empty($data['filter_products'])) {
                    $product_ids = implode(',', array_map('intval', $data['filter_products']));
                    $sql .= " AND p.product_id IN (" . $product_ids . ")";
                }
                break;
            case 'cycle':
                // جرد المنتجات التي لم يتم جردها مؤخراً
                $sql .= " AND p.product_id NOT IN (
                    SELECT DISTINCT sci.product_id 
                    FROM " . DB_PREFIX . "cod_stock_counting_item sci
                    INNER JOIN " . DB_PREFIX . "cod_stock_counting sc ON (sci.counting_id = sc.counting_id)
                    WHERE sc.status = 'posted' 
                    AND sc.branch_id = '" . (int)$data['branch_id'] . "'
                    AND sc.counting_date >= DATE_SUB('" . $this->db->escape($data['counting_date']) . "', INTERVAL 3 MONTH)
                )";
                break;
            case 'spot':
                // جرد عشوائي - اختيار عدد محدود من المنتجات
                $sql .= " ORDER BY RAND() LIMIT 50";
                break;
        }
        
        $query = $this->db->query($sql);
        
        // إدراج العناصر
        foreach ($query->rows as $product) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cod_stock_counting_item 
                SET counting_id = '" . (int)$counting_id . "',
                    product_id = '" . (int)$product['product_id'] . "',
                    system_quantity = '" . (float)$product['system_quantity'] . "',
                    unit_cost = '" . (float)$product['unit_cost'] . "',
                    unit_id = '" . (int)$product['unit_id'] . "',
                    date_added = NOW()
            ");
        }
    }
    
    /**
     * الحصول على عناصر الجرد
     */
    public function getCountingItems($counting_id, $data = array()) {
        $sql = "
            SELECT 
                sci.*,
                p.product_id,
                pd.name as product_name,
                p.model,
                p.sku,
                ud.name as unit_name,
                u.symbol as unit_symbol,
                (sci.system_quantity - COALESCE(sci.actual_quantity, 0)) as variance_quantity,
                ((sci.system_quantity - COALESCE(sci.actual_quantity, 0)) * sci.unit_cost) as variance_value,
                CASE 
                    WHEN sci.actual_quantity IS NULL THEN 'pending'
                    WHEN sci.system_quantity = sci.actual_quantity THEN 'match'
                    WHEN sci.system_quantity > sci.actual_quantity THEN 'shortage'
                    WHEN sci.system_quantity < sci.actual_quantity THEN 'surplus'
                END as variance_status
            FROM " . DB_PREFIX . "cod_stock_counting_item sci
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (sci.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (sci.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (sci.unit_id = u.unit_id)
            WHERE sci.counting_id = '" . (int)$counting_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        // تطبيق الفلاتر
        if (!empty($data['filter_product_name'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product_name']) . "%' 
                     OR p.model LIKE '%" . $this->db->escape($data['filter_product_name']) . "%'
                     OR p.sku LIKE '%" . $this->db->escape($data['filter_product_name']) . "%')";
        }
        
        if (isset($data['filter_variance_status']) && $data['filter_variance_status'] !== '') {
            switch ($data['filter_variance_status']) {
                case 'pending':
                    $sql .= " AND sci.actual_quantity IS NULL";
                    break;
                case 'match':
                    $sql .= " AND sci.actual_quantity IS NOT NULL AND sci.system_quantity = sci.actual_quantity";
                    break;
                case 'shortage':
                    $sql .= " AND sci.actual_quantity IS NOT NULL AND sci.system_quantity > sci.actual_quantity";
                    break;
                case 'surplus':
                    $sql .= " AND sci.actual_quantity IS NOT NULL AND sci.system_quantity < sci.actual_quantity";
                    break;
            }
        }
        
        $sql .= " ORDER BY pd.name ASC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * تحديث كمية فعلية لعنصر جرد
     */
    public function updateCountingItem($item_id, $actual_quantity, $notes = '') {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_stock_counting_item 
            SET actual_quantity = '" . (float)$actual_quantity . "',
                notes = '" . $this->db->escape($notes) . "',
                date_modified = NOW()
            WHERE item_id = '" . (int)$item_id . "'
        ");
    }
    
    /**
     * تغيير حالة الجرد
     */
    public function changeStatus($counting_id, $status) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_stock_counting 
            SET status = '" . $this->db->escape($status) . "',
                date_modified = NOW()
            WHERE counting_id = '" . (int)$counting_id . "'
        ");
        
        // إذا تم ترحيل الجرد، إنشاء التسويات
        if ($status == 'posted') {
            $this->createAdjustments($counting_id);
        }
    }
    
    /**
     * إنشاء تسويات من الجرد
     */
    public function createAdjustments($counting_id) {
        // الحصول على معلومات الجرد
        $counting = $this->getStockCounting($counting_id);
        
        // الحصول على العناصر التي بها فروقات
        $items = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cod_stock_counting_item 
            WHERE counting_id = '" . (int)$counting_id . "'
            AND actual_quantity IS NOT NULL
            AND system_quantity != actual_quantity
        ");
        
        if ($items->num_rows > 0) {
            // إنشاء تسوية جديدة
            $adjustment_number = 'ADJ-' . date('Y') . '-' . str_pad($counting_id, 6, '0', STR_PAD_LEFT);
            
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cod_stock_adjustment 
                SET adjustment_number = '" . $this->db->escape($adjustment_number) . "',
                    adjustment_name = 'تسوية من الجرد: " . $this->db->escape($counting['counting_name']) . "',
                    adjustment_type = 'counting',
                    branch_id = '" . (int)$counting['branch_id'] . "',
                    reference_type = 'stock_counting',
                    reference_id = '" . (int)$counting_id . "',
                    user_id = '" . (int)$this->user->getId() . "',
                    adjustment_date = '" . $this->db->escape($counting['counting_date']) . "',
                    status = 'posted',
                    notes = 'تسوية تلقائية من الجرد المخزني',
                    date_added = NOW(),
                    date_modified = NOW()
            ");
            
            $adjustment_id = $this->db->getLastId();
            
            // إضافة عناصر التسوية
            foreach ($items->rows as $item) {
                $variance_quantity = $item['actual_quantity'] - $item['system_quantity'];
                
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "cod_stock_adjustment_item 
                    SET adjustment_id = '" . (int)$adjustment_id . "',
                        product_id = '" . (int)$item['product_id'] . "',
                        quantity = '" . (float)$variance_quantity . "',
                        unit_cost = '" . (float)$item['unit_cost'] . "',
                        unit_id = '" . (int)$item['unit_id'] . "',
                        reason = 'جرد مخزني',
                        notes = 'فرق الجرد: النظام " . $item['system_quantity'] . " - الفعلي " . $item['actual_quantity'] . "',
                        date_added = NOW()
                ");
                
                // تحديث المخزون
                $this->updateInventory($item['product_id'], $counting['branch_id'], $variance_quantity, $item['unit_cost']);
            }
        }
    }
    
    /**
     * تحديث المخزون
     */
    private function updateInventory($product_id, $branch_id, $quantity, $unit_cost) {
        // تحديث كمية المخزون
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_product_inventory 
            SET quantity = quantity + '" . (float)$quantity . "'
            WHERE product_id = '" . (int)$product_id . "'
            AND branch_id = '" . (int)$branch_id . "'
        ");
        
        // إضافة حركة مخزنية
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_movement 
            SET product_id = '" . (int)$product_id . "',
                branch_id = '" . (int)$branch_id . "',
                movement_type = '" . ($quantity > 0 ? 'adjustment_in' : 'adjustment_out') . "',
                reference_type = 'stock_counting',
                reference_id = '" . (int)$counting_id . "',
                quantity_in = '" . ($quantity > 0 ? (float)$quantity : 0) . "',
                quantity_out = '" . ($quantity < 0 ? (float)abs($quantity) : 0) . "',
                unit_cost = '" . (float)$unit_cost . "',
                total_cost = '" . (float)($quantity * $unit_cost) . "',
                user_id = '" . (int)$this->user->getId() . "',
                notes = 'تسوية من الجرد المخزني',
                date_added = NOW()
        ");
    }
    
    /**
     * الحصول على ملخص الجرد
     */
    public function getCountingSummary($data = array()) {
        $sql = "
            SELECT 
                COUNT(*) as total_countings,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count,
                AVG((SELECT COUNT(*) FROM " . DB_PREFIX . "cod_stock_counting_item sci WHERE sci.counting_id = sc.counting_id)) as avg_items_per_counting
            FROM " . DB_PREFIX . "cod_stock_counting sc
            WHERE 1=1
        ";
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND sc.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(sc.counting_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }
        
        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(sc.counting_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    /**
     * توليد رقم جرد جديد
     */
    public function generateCountingNumber() {
        $query = $this->db->query("
            SELECT counting_number 
            FROM " . DB_PREFIX . "cod_stock_counting 
            WHERE counting_number LIKE 'CNT-" . date('Y') . "-%' 
            ORDER BY counting_id DESC 
            LIMIT 1
        ");
        
        if ($query->num_rows > 0) {
            $last_number = $query->row['counting_number'];
            $number_part = (int)substr($last_number, -6);
            $new_number = $number_part + 1;
        } else {
            $new_number = 1;
        }
        
        return 'CNT-' . date('Y') . '-' . str_pad($new_number, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * تصدير البيانات للإكسل
     */
    public function exportToExcel($data = array()) {
        // إزالة حدود الصفحات للتصدير
        unset($data['start']);
        unset($data['limit']);
        
        return $this->getStockCountings($data);
    }
}
