<?php
/**
 * نموذج تقارير المخزون
 * يستخدم لاستخراج بيانات تقارير وتحليلات المخزون
 */
class ModelReportInventory extends Model {
    /**
     * الحصول على بيانات تقييم المخزون
     */
    public function getInventoryValuation($data = array()) {
        $sql = "SELECT pi.product_id, p.model, p.sku, pd.name, 
                    pi.branch_id, b.name AS branch_name, 
                    pi.unit_id, u.desc_en AS unit_name, 
                    pi.quantity, pi.average_cost, 
                    (pi.quantity * pi.average_cost) AS total_value,
                    (SELECT cd.name FROM " . DB_PREFIX . "category_description cd 
                        WHERE cd.category_id = p2c.category_id AND cd.language_id = '1' LIMIT 1) AS category,
                    (SELECT m.created_at FROM " . DB_PREFIX . "inventory_movement m 
                        WHERE m.product_id = pi.product_id AND m.branch_id = pi.branch_id AND m.unit_id = pi.unit_id 
                        ORDER BY m.created_at DESC LIMIT 1) AS last_movement_date
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

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
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

        return $query->rows;
    }

    /**
     * الحصول على إجمالي عدد عناصر تقييم المخزون
     */
    public function getTotalInventoryValuation($data = array()) {
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

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
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
     * الحصول على بيانات دوران المخزون
     */
    public function getInventoryTurnover($data = array()) {
        $sql = "SELECT pi.product_id, p.model, p.sku, pd.name, 
                    pi.branch_id, b.name AS branch_name, 
                    pi.unit_id, u.desc_en AS unit_name, 
                    pi.quantity, pi.average_cost, 
                    (pi.quantity * pi.average_cost) AS current_value,
                    (SELECT SUM(CASE WHEN m.movement_type = 'out' THEN m.quantity ELSE 0 END) 
                        FROM " . DB_PREFIX . "inventory_movement m 
                        WHERE m.product_id = pi.product_id AND m.branch_id = pi.branch_id AND m.unit_id = pi.unit_id 
                        AND DATE(m.created_at) BETWEEN '" . $this->db->escape($data['filter_date_start']) . "' AND '" . $this->db->escape($data['filter_date_end']) . "') AS total_out,
                    (SELECT AVG(quantity) 
                        FROM " . DB_PREFIX . "product_inventory_history 
                        WHERE product_id = pi.product_id AND branch_id = pi.branch_id AND unit_id = pi.unit_id 
                        AND DATE(date) BETWEEN '" . $this->db->escape($data['filter_date_start']) . "' AND '" . $this->db->escape($data['filter_date_end']) . "') AS average_inventory,
                    (SELECT cd.name FROM " . DB_PREFIX . "category_description cd 
                        WHERE cd.category_id = p2c.category_id AND cd.language_id = '1' LIMIT 1) AS category
                FROM " . DB_PREFIX . "product_inventory pi
                LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
                WHERE 1=1";

        if (!empty($data['filter_branch'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND p2c.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        $sql .= " GROUP BY pi.product_id, pi.branch_id, pi.unit_id";

        $sort_data = array(
            'p.name',
            'p.model',
            'category',
            'b.name',
            'total_out',
            'average_inventory',
            'turnover_ratio'
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
        $results = $query->rows;
        
        // حساب معدل دوران المخزون
        foreach ($results as &$result) {
            $result['turnover_ratio'] = ($result['average_inventory'] > 0) ? ($result['total_out'] / $result['average_inventory']) : 0;
            $result['days_on_hand'] = ($result['turnover_ratio'] > 0) ? (365 / $result['turnover_ratio']) : 0;
        }

        return $results;
    }

    /**
     * الحصول على بيانات المخزون الراكد
     */
    public function getSlowMovingInventory($data = array()) {
        $sql = "SELECT pi.product_id, p.model, p.sku, pd.name, 
                    pi.branch_id, b.name AS branch_name, 
                    pi.unit_id, u.desc_en AS unit_name, 
                    pi.quantity, pi.average_cost, 
                    (pi.quantity * pi.average_cost) AS total_value,
                    (SELECT MAX(m.created_at) FROM " . DB_PREFIX . "inventory_movement m 
                        WHERE m.product_id = pi.product_id AND m.branch_id = pi.branch_id AND m.unit_id = pi.unit_id 
                        AND m.movement_type = 'out') AS last_out_date,
                    DATEDIFF(NOW(), (SELECT MAX(m.created_at) FROM " . DB_PREFIX . "inventory_movement m 
                        WHERE m.product_id = pi.product_id AND m.branch_id = pi.branch_id AND m.unit_id = pi.unit_id 
                        AND m.movement_type = 'out')) AS days_since_last_movement,
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

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_days'])) {
            $sql .= " HAVING days_since_last_movement >= '" . (int)$data['filter_days'] . "' OR days_since_last_movement IS NULL";
        }

        $sql .= " GROUP BY pi.product_id, pi.branch_id, pi.unit_id";

        $sort_data = array(
            'p.name',
            'p.model',
            'category',
            'b.name',
            'pi.quantity',
            'total_value',
            'days_since_last_movement'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY days_since_last_movement DESC";
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
     * الحصول على تنبيهات المخزون
     */
    public function getInventoryAlerts($data = array()) {
        $sql = "SELECT pi.product_id, p.model, p.sku, pd.name, 
                    pi.branch_id, b.name AS branch_name, 
                    pi.unit_id, u.desc_en AS unit_name, 
                    pi.quantity, p.minimum_quantity,
                    (pi.quantity * pi.average_cost) AS total_value,
                    (SELECT cd.name FROM " . DB_PREFIX . "category_description cd 
                        WHERE cd.category_id = p2c.category_id AND cd.language_id = '1' LIMIT 1) AS category
                FROM " . DB_PREFIX . "product_inventory pi
                LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
                WHERE pi.quantity <= p.minimum_quantity";

        if (!empty($data['filter_branch'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND p2c.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        $sql .= " GROUP BY pi.product_id, pi.branch_id, pi.unit_id";

        $sort_data = array(
            'p.name',
            'p.model',
            'category',
            'b.name',
            'pi.quantity',
            'p.minimum_quantity'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pi.quantity";
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
}
