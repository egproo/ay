<?php
/**
 * نموذج إدارة مستويات المخزون
 * يستخدم لإدارة ومراقبة مستويات المخزون للمنتجات في مختلف الفروع
 */
class ModelInventoryStockLevel extends Model {
    /**
     * الحصول على قائمة مستويات المخزون
     */
    public function getStockLevels($data = array()) {
        $sql = "SELECT sl.stock_level_id, sl.product_id, sl.branch_id, sl.unit_id, 
                    sl.minimum_stock, sl.reorder_point, sl.maximum_stock, sl.status, sl.notes,
                    pd.name AS product_name, b.name AS branch_name, u.desc_en AS unit_name,
                    (SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                     WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id) AS current_stock
                FROM " . DB_PREFIX . "product_stock_level sl
                LEFT JOIN " . DB_PREFIX . "product p ON (sl.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (sl.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (sl.unit_id = u.unit_id)
                WHERE 1=1";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND sl.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND sl.status = '" . (int)$data['filter_status'] . "'";
        }

        $sort_data = array(
            'pd.name',
            'b.name',
            'sl.minimum_stock',
            'sl.reorder_point',
            'sl.maximum_stock',
            'current_stock',
            'sl.status'
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

        return $query->rows;
    }

    /**
     * الحصول على إجمالي عدد مستويات المخزون
     */
    public function getTotalStockLevels($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "product_stock_level sl
                LEFT JOIN " . DB_PREFIX . "product p ON (sl.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (sl.branch_id = b.branch_id)
                WHERE 1=1";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND sl.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND sl.status = '" . (int)$data['filter_status'] . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على معلومات مستوى مخزون محدد
     */
    public function getStockLevel($stock_level_id) {
        $query = $this->db->query("SELECT sl.*, pd.name AS product_name, b.name AS branch_name, u.desc_en AS unit_name,
                (SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                 WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id) AS current_stock
                FROM " . DB_PREFIX . "product_stock_level sl
                LEFT JOIN " . DB_PREFIX . "product p ON (sl.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (sl.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (sl.unit_id = u.unit_id)
                WHERE sl.stock_level_id = '" . (int)$stock_level_id . "'");

        return $query->row;
    }

    /**
     * الحصول على مستوى المخزون حسب المنتج والفرع والوحدة
     */
    public function getStockLevelByProductBranchUnit($product_id, $branch_id, $unit_id) {
        $query = $this->db->query("SELECT sl.*, pd.name AS product_name, b.name AS branch_name, u.desc_en AS unit_name,
                (SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                 WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id) AS current_stock
                FROM " . DB_PREFIX . "product_stock_level sl
                LEFT JOIN " . DB_PREFIX . "product p ON (sl.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (sl.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (sl.unit_id = u.unit_id)
                WHERE sl.product_id = '" . (int)$product_id . "' 
                AND sl.branch_id = '" . (int)$branch_id . "' 
                AND sl.unit_id = '" . (int)$unit_id . "'");

        return $query->row;
    }

    /**
     * إضافة مستوى مخزون جديد
     */
    public function addStockLevel($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_stock_level SET 
                product_id = '" . (int)$data['product_id'] . "', 
                branch_id = '" . (int)$data['branch_id'] . "', 
                unit_id = '" . (int)$data['unit_id'] . "', 
                minimum_stock = '" . (float)$data['minimum_stock'] . "', 
                reorder_point = '" . (float)$data['reorder_point'] . "', 
                maximum_stock = '" . (float)$data['maximum_stock'] . "', 
                status = '" . (int)$data['status'] . "', 
                notes = '" . $this->db->escape($data['notes']) . "', 
                created_by = '" . (int)$this->user->getId() . "', 
                created_at = NOW()");

        return $this->db->getLastId();
    }

    /**
     * تعديل مستوى مخزون
     */
    public function editStockLevel($stock_level_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "product_stock_level SET 
                product_id = '" . (int)$data['product_id'] . "', 
                branch_id = '" . (int)$data['branch_id'] . "', 
                unit_id = '" . (int)$data['unit_id'] . "', 
                minimum_stock = '" . (float)$data['minimum_stock'] . "', 
                reorder_point = '" . (float)$data['reorder_point'] . "', 
                maximum_stock = '" . (float)$data['maximum_stock'] . "', 
                status = '" . (int)$data['status'] . "', 
                notes = '" . $this->db->escape($data['notes']) . "', 
                modified_by = '" . (int)$this->user->getId() . "', 
                modified_at = NOW() 
                WHERE stock_level_id = '" . (int)$stock_level_id . "'");
    }

    /**
     * حذف مستوى مخزون
     */
    public function deleteStockLevel($stock_level_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_stock_level WHERE stock_level_id = '" . (int)$stock_level_id . "'");
    }

    /**
     * الحصول على المنتجات التي تحتاج إلى إعادة طلب
     */
    public function getReorderProducts($data = array()) {
        $sql = "SELECT sl.stock_level_id, sl.product_id, sl.branch_id, sl.unit_id, 
                    sl.minimum_stock, sl.reorder_point, sl.maximum_stock, sl.status, sl.notes,
                    pd.name AS product_name, b.name AS branch_name, u.desc_en AS unit_name,
                    (SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                     WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id) AS current_stock,
                    (sl.reorder_point - (SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                     WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id)) AS quantity_to_order
                FROM " . DB_PREFIX . "product_stock_level sl
                LEFT JOIN " . DB_PREFIX . "product p ON (sl.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (sl.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (sl.unit_id = u.unit_id)
                WHERE sl.status = '1'
                HAVING current_stock <= sl.reorder_point";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND sl.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        $sort_data = array(
            'pd.name',
            'b.name',
            'current_stock',
            'quantity_to_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY quantity_to_order DESC";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
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
     * الحصول على إجمالي عدد المنتجات التي تحتاج إلى إعادة طلب
     */
    public function getTotalReorderProducts($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM (
                    SELECT sl.stock_level_id,
                        (SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                         WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id) AS current_stock
                    FROM " . DB_PREFIX . "product_stock_level sl
                    LEFT JOIN " . DB_PREFIX . "product p ON (sl.product_id = p.product_id)
                    LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                    LEFT JOIN " . DB_PREFIX . "branch b ON (sl.branch_id = b.branch_id)
                    WHERE sl.status = '1'";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND sl.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        $sql .= " HAVING current_stock <= sl.reorder_point
                ) AS subquery";

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على المنتجات ذات المخزون الزائد
     */
    public function getOverstockProducts($data = array()) {
        $sql = "SELECT sl.stock_level_id, sl.product_id, sl.branch_id, sl.unit_id, 
                    sl.minimum_stock, sl.reorder_point, sl.maximum_stock, sl.status, sl.notes,
                    pd.name AS product_name, b.name AS branch_name, u.desc_en AS unit_name,
                    (SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                     WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id) AS current_stock,
                    ((SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                     WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id) - sl.maximum_stock) AS excess_quantity
                FROM " . DB_PREFIX . "product_stock_level sl
                LEFT JOIN " . DB_PREFIX . "product p ON (sl.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (sl.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (sl.unit_id = u.unit_id)
                WHERE sl.status = '1'
                HAVING current_stock > sl.maximum_stock";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND sl.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        $sort_data = array(
            'pd.name',
            'b.name',
            'current_stock',
            'excess_quantity'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY excess_quantity DESC";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
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
     * الحصول على إجمالي عدد المنتجات ذات المخزون الزائد
     */
    public function getTotalOverstockProducts($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM (
                    SELECT sl.stock_level_id,
                        (SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                         WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id) AS current_stock
                    FROM " . DB_PREFIX . "product_stock_level sl
                    LEFT JOIN " . DB_PREFIX . "product p ON (sl.product_id = p.product_id)
                    LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                    LEFT JOIN " . DB_PREFIX . "branch b ON (sl.branch_id = b.branch_id)
                    WHERE sl.status = '1'";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND sl.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        $sql .= " HAVING current_stock > sl.maximum_stock
                ) AS subquery";

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
}
