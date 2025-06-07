<?php
/**
 * نموذج تحليل ABC للمخزون
 * يستخدم لاستخراج بيانات تحليل ABC للمنتجات
 */
class ModelInventoryAbcAnalysis extends Model {
    /**
     * الحصول على المنتجات مرتبة حسب القيمة للتحليل ABC
     */
    public function getProductsByValue($data = array()) {
        $sql = "SELECT pi.product_id, p.model, p.sku, pd.name, 
                    pi.branch_id, b.name AS branch_name, 
                    pi.unit_id, u.desc_en AS unit_name, 
                    pi.quantity, pi.average_cost, 
                    (pi.quantity * pi.average_cost) AS total_value,
                    (SELECT cd.name FROM " . DB_PREFIX . "category_description cd 
                        WHERE cd.category_id = p2c.category_id AND cd.language_id = '1' LIMIT 1) AS category
                FROM " . DB_PREFIX . "product_inventory pi
                LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
                WHERE pi.quantity > 0";

        if (!empty($data['filter_branch'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND p2c.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_date'])) {
            $sql .= " AND (SELECT COUNT(*) FROM " . DB_PREFIX . "inventory_movement m 
                        WHERE m.product_id = pi.product_id AND m.branch_id = pi.branch_id AND m.unit_id = pi.unit_id 
                        AND DATE(m.created_at) <= '" . $this->db->escape($data['filter_date']) . "') > 0";
        }

        $sql .= " GROUP BY pi.product_id, pi.branch_id, pi.unit_id";

        $sort_data = array(
            'p.name',
            'p.model',
            'p.sku',
            'category',
            'b.name',
            'pi.quantity',
            'pi.average_cost',
            'total_value'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY total_value";
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
     * الحصول على إجمالي عدد المنتجات للتحليل ABC حسب القيمة
     */
    public function getTotalProductsByValue($data = array()) {
        $sql = "SELECT COUNT(DISTINCT pi.product_id, pi.branch_id, pi.unit_id) AS total
                FROM " . DB_PREFIX . "product_inventory pi
                LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
                WHERE pi.quantity > 0";

        if (!empty($data['filter_branch'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND p2c.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_date'])) {
            $sql .= " AND (SELECT COUNT(*) FROM " . DB_PREFIX . "inventory_movement m 
                        WHERE m.product_id = pi.product_id AND m.branch_id = pi.branch_id AND m.unit_id = pi.unit_id 
                        AND DATE(m.created_at) <= '" . $this->db->escape($data['filter_date']) . "') > 0";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على المنتجات مرتبة حسب المبيعات للتحليل ABC
     */
    public function getProductsBySales($data = array()) {
        // Implementación pendiente
        return array();
    }

    /**
     * الحصول على إجمالي عدد المنتجات للتحليل ABC حسب المبيعات
     */
    public function getTotalProductsBySales($data = array()) {
        // Implementación pendiente
        return 0;
    }

    /**
     * الحصول على المنتجات مرتبة حسب الربحية للتحليل ABC
     */
    public function getProductsByProfit($data = array()) {
        // Implementación pendiente
        return array();
    }

    /**
     * الحصول على إجمالي عدد المنتجات للتحليل ABC حسب الربحية
     */
    public function getTotalProductsByProfit($data = array()) {
        // Implementación pendiente
        return 0;
    }
}
