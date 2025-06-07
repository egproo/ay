<?php
/**
 * نموذج تحليل اتجاهات المخزون
 * يستخدم لتحليل حركة المخزون وتوقع الاتجاهات المستقبلية
 */
class ModelReportInventoryTrends extends Model {
    /**
     * الحصول على بيانات حركة المخزون
     */
    public function getMovements($data = array()) {
        $sql = "SELECT p.product_id, pd.name AS product_name, b.name AS branch_name, 
                    u.desc_en AS unit_name, ";
        
        switch ($data['filter_group']) {
            case 'day':
                $sql .= " DATE_FORMAT(m.created_at, '%Y-%m-%d') AS date,";
                break;
            case 'week':
                $sql .= " CONCAT(YEAR(m.created_at), '-', LPAD(WEEK(m.created_at, 1), 2, '0')) AS date,";
                break;
            case 'month':
                $sql .= " DATE_FORMAT(m.created_at, '%Y-%m') AS date,";
                break;
            case 'quarter':
                $sql .= " CONCAT(YEAR(m.created_at), '-', CEIL(MONTH(m.created_at)/3)) AS date,";
                break;
            case 'year':
                $sql .= " DATE_FORMAT(m.created_at, '%Y') AS date,";
                break;
        }
        
        $sql .= " SUM(CASE WHEN m.movement_type = 'in' THEN m.quantity ELSE 0 END) AS quantity_in,
                  SUM(CASE WHEN m.movement_type = 'out' THEN m.quantity ELSE 0 END) AS quantity_out,
                  SUM(CASE WHEN m.movement_type = 'in' THEN m.quantity ELSE -m.quantity END) AS balance
                FROM " . DB_PREFIX . "inventory_movement m
                LEFT JOIN " . DB_PREFIX . "product p ON (m.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "branch b ON (m.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (m.unit_id = u.unit_id)
                WHERE m.created_at BETWEEN '" . $this->db->escape($data['filter_date_start']) . " 00:00:00' AND '" . $this->db->escape($data['filter_date_end']) . " 23:59:59'";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND m.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_movement_type'])) {
            $sql .= " AND m.movement_type = '" . $this->db->escape($data['filter_movement_type']) . "'";
        }

        $sql .= " GROUP BY date, p.product_id, m.branch_id, m.unit_id";
        $sql .= " ORDER BY date ASC, pd.name ASC";

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
     * الحصول على إجمالي عدد حركات المخزون
     */
    public function getTotalMovements($data = array()) {
        $sql = "SELECT COUNT(DISTINCT ";
        
        switch ($data['filter_group']) {
            case 'day':
                $sql .= "CONCAT(DATE_FORMAT(m.created_at, '%Y-%m-%d'), p.product_id, m.branch_id, m.unit_id)";
                break;
            case 'week':
                $sql .= "CONCAT(YEAR(m.created_at), WEEK(m.created_at, 1), p.product_id, m.branch_id, m.unit_id)";
                break;
            case 'month':
                $sql .= "CONCAT(DATE_FORMAT(m.created_at, '%Y-%m'), p.product_id, m.branch_id, m.unit_id)";
                break;
            case 'quarter':
                $sql .= "CONCAT(YEAR(m.created_at), CEIL(MONTH(m.created_at)/3), p.product_id, m.branch_id, m.unit_id)";
                break;
            case 'year':
                $sql .= "CONCAT(DATE_FORMAT(m.created_at, '%Y'), p.product_id, m.branch_id, m.unit_id)";
                break;
        }
        
        $sql .= ") AS total FROM " . DB_PREFIX . "inventory_movement m";
        $sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (m.product_id = p.product_id)";
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')";
        $sql .= " LEFT JOIN " . DB_PREFIX . "branch b ON (m.branch_id = b.branch_id)";
        $sql .= " WHERE m.created_at BETWEEN '" . $this->db->escape($data['filter_date_start']) . " 00:00:00' AND '" . $this->db->escape($data['filter_date_end']) . " 23:59:59'";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND m.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_movement_type'])) {
            $sql .= " AND m.movement_type = '" . $this->db->escape($data['filter_movement_type']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على بيانات معدل دوران المخزون
     */
    public function getTurnoverRates($data = array()) {
        $sql = "SELECT p.product_id, pd.name AS product_name, p.model, p.sku, 
                    b.branch_id, b.name AS branch_name, 
                    u.unit_id, u.desc_en AS unit_name,
                    (SELECT COALESCE(SUM(CASE WHEN m.movement_type = 'out' THEN m.quantity ELSE 0 END)), 0) 
                     FROM " . DB_PREFIX . "inventory_movement m 
                     WHERE m.product_id = p.product_id 
                     AND m.branch_id = b.branch_id 
                     AND m.unit_id = u.unit_id
                     AND m.created_at BETWEEN '" . $this->db->escape($data['filter_date_start']) . " 00:00:00' AND '" . $this->db->escape($data['filter_date_end']) . " 23:59:59'
                    ) AS total_out,
                    (SELECT AVG(pi_hist.quantity) 
                     FROM " . DB_PREFIX . "product_inventory_history pi_hist 
                     WHERE pi_hist.product_id = p.product_id 
                     AND pi_hist.branch_id = b.branch_id 
                     AND pi_hist.unit_id = u.unit_id
                     AND pi_hist.date BETWEEN '" . $this->db->escape($data['filter_date_start']) . "' AND '" . $this->db->escape($data['filter_date_end']) . "'
                    ) AS average_inventory,
                    pi.quantity AS current_stock,
                    pi.average_cost,
                    (pi.quantity * pi.average_cost) AS current_value
                FROM " . DB_PREFIX . "product p
                INNER JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                INNER JOIN " . DB_PREFIX . "branch b ON (1=1)
                INNER JOIN " . DB_PREFIX . "unit u ON (u.unit_id IN (SELECT DISTINCT unit_id FROM " . DB_PREFIX . "product_inventory WHERE product_id = p.product_id))
                LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (p.product_id = pi.product_id AND b.branch_id = pi.branch_id AND u.unit_id = pi.unit_id)
                WHERE pi.quantity > 0";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND b.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND p.product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$data['filter_category'] . "')";
        }

        $sql .= " GROUP BY p.product_id, b.branch_id, u.unit_id";

        if (isset($data['sort']) && in_array($data['sort'], array('pd.name', 'p.model', 'b.name', 'total_out', 'average_inventory', 'turnover_rate', 'days_on_hand'))) {
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
        
        // حساب معدل الدوران وأيام التخزين
        foreach ($results as &$result) {
            $result['total_out'] = (float)$result['total_out'];
            $result['average_inventory'] = (float)$result['average_inventory'];
            $result['current_stock'] = (float)$result['current_stock'];
            $result['average_cost'] = (float)$result['average_cost'];
            $result['current_value'] = (float)$result['current_value'];
            
            // حساب معدل الدوران
            $result['turnover_rate'] = ($result['average_inventory'] > 0) ? 
                                      ($result['total_out'] / $result['average_inventory']) : 0;
            
            // حساب أيام التخزين
            $days_in_period = (strtotime($data['filter_date_end']) - strtotime($data['filter_date_start'])) / (60 * 60 * 24);
            $result['days_on_hand'] = ($result['turnover_rate'] > 0) ? 
                                     ($days_in_period / $result['turnover_rate']) : 0;
        }

        return $results;
    }

    /**
     * الحصول على إجمالي عدد سجلات معدل الدوران
     */
    public function getTotalTurnoverRates($data = array()) {
        $sql = "SELECT COUNT(DISTINCT p.product_id, b.branch_id, u.unit_id) AS total
                FROM " . DB_PREFIX . "product p
                INNER JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                INNER JOIN " . DB_PREFIX . "branch b ON (1=1)
                INNER JOIN " . DB_PREFIX . "unit u ON (u.unit_id IN (SELECT DISTINCT unit_id FROM " . DB_PREFIX . "product_inventory WHERE product_id = p.product_id))
                LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (p.product_id = pi.product_id AND b.branch_id = pi.branch_id AND u.unit_id = pi.unit_id)
                WHERE pi.quantity > 0";

        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND b.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND p.product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$data['filter_category'] . "')";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على بيانات موسمية المخزون
     */
    public function getSeasonalityData($data = array()) {
        // سيتم تنفيذه لاحقًا
        return array();
    }

    /**
     * الحصول على بيانات التنبؤ بالمخزون
     */
    public function getForecastData($data = array()) {
        // سيتم تنفيذه لاحقًا
        return array();
    }
}
