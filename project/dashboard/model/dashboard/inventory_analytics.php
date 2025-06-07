<?php
/**
 * Inventory Analytics Model
 *
 * Provides data analysis and calculations for inventory management
 * including stock levels, movement trends, valuation, and performance metrics
 */
class ModelDashboardInventoryAnalytics extends Model {

    /**
     * Get inventory summary statistics
     *
     * @param array $data Filter parameters
     * @return array Summary statistics
     */
    public function getInventorySummary($data = array()) {
        $sql = "SELECT
            COUNT(DISTINCT pi.product_id) as total_products,
            COUNT(DISTINCT pi.branch_id) as total_branches,
            SUM(pi.quantity) as total_quantity,
            SUM(pi.quantity * pi.average_cost) as total_value,
            AVG(pi.average_cost) as avg_cost,
            SUM(CASE WHEN pi.quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock_count,
            SUM(CASE WHEN pi.quantity > 0 AND pi.quantity <= psl.minimum_stock THEN 1 ELSE 0 END) as low_stock_count,
            SUM(CASE WHEN pi.quantity >= psl.maximum_stock THEN 1 ELSE 0 END) as overstock_count
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_stock_level psl ON (pi.product_id = psl.product_id AND pi.branch_id = psl.branch_id AND pi.unit_id = psl.unit_id)
            WHERE 1=1";

        // Apply filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category_id'] . "'";
        }

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            $result = $query->row;
            $result['avg_cost'] = round($result['avg_cost'], 4);
            $result['total_value'] = round($result['total_value'], 4);
            $result['stock_health_percentage'] = $result['total_products'] > 0 ?
                round((($result['total_products'] - $result['out_of_stock_count'] - $result['low_stock_count']) / $result['total_products']) * 100, 2) : 0;

            return $result;
        }

        return array(
            'total_products' => 0,
            'total_branches' => 0,
            'total_quantity' => 0,
            'total_value' => 0,
            'avg_cost' => 0,
            'out_of_stock_count' => 0,
            'low_stock_count' => 0,
            'overstock_count' => 0,
            'stock_health_percentage' => 0
        );
    }

    /**
     * Get current stock levels with alerts
     *
     * @param array $data Filter parameters
     * @return array Stock levels data
     */
    public function getStockLevels($data = array()) {
        $sql = "SELECT
            pi.product_id,
            pd.name as product_name,
            p.model,
            p.sku,
            b.name as branch_name,
            u.name as unit_name,
            pi.quantity,
            pi.average_cost,
            (pi.quantity * pi.average_cost) as total_value,
            psl.minimum_stock,
            psl.reorder_point,
            psl.maximum_stock,
            CASE
                WHEN pi.quantity <= 0 THEN 'out_of_stock'
                WHEN pi.quantity <= psl.minimum_stock THEN 'low_stock'
                WHEN pi.quantity >= psl.maximum_stock THEN 'overstock'
                ELSE 'normal'
            END as stock_status
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "product_stock_level psl ON (pi.product_id = psl.product_id AND pi.branch_id = psl.branch_id AND pi.unit_id = psl.unit_id)
            WHERE p.status = '1'";

        // Apply filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category_id'] . "'";
        }

        // Filter by stock status if specified
        if (!empty($data['filter_stock_status'])) {
            switch ($data['filter_stock_status']) {
                case 'out_of_stock':
                    $sql .= " AND pi.quantity <= 0";
                    break;
                case 'low_stock':
                    $sql .= " AND pi.quantity > 0 AND pi.quantity <= psl.minimum_stock";
                    break;
                case 'overstock':
                    $sql .= " AND pi.quantity >= psl.maximum_stock";
                    break;
            }
        }

        $sql .= " ORDER BY pi.quantity ASC, pd.name ASC";

        // Apply pagination
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
     * Get inventory movement trends
     *
     * @param array $data Filter parameters
     * @return array Movement trends data
     */
    public function getMovementTrends($data = array()) {
        $period = isset($data['filter_period']) ? $data['filter_period'] : 'month';

        // Determine date grouping based on period
        switch ($period) {
            case 'day':
                $date_format = '%Y-%m-%d';
                $date_group = 'DATE(im.created_at)';
                break;
            case 'week':
                $date_format = '%Y-%u';
                $date_group = 'YEARWEEK(im.created_at)';
                break;
            case 'month':
                $date_format = '%Y-%m';
                $date_group = 'DATE_FORMAT(im.created_at, "%Y-%m")';
                break;
            case 'quarter':
                $date_format = '%Y-Q%q';
                $date_group = 'CONCAT(YEAR(im.created_at), "-Q", QUARTER(im.created_at))';
                break;
            case 'year':
                $date_format = '%Y';
                $date_group = 'YEAR(im.created_at)';
                break;
            default:
                $date_format = '%Y-%m';
                $date_group = 'DATE_FORMAT(im.created_at, "%Y-%m")';
        }

        $sql = "SELECT
            " . $date_group . " as period,
            SUM(CASE WHEN im.movement_type = 'in' THEN im.quantity ELSE 0 END) as inbound_quantity,
            SUM(CASE WHEN im.movement_type = 'out' THEN im.quantity ELSE 0 END) as outbound_quantity,
            SUM(CASE WHEN im.movement_type = 'in' THEN (im.quantity * im.cost) ELSE 0 END) as inbound_value,
            SUM(CASE WHEN im.movement_type = 'out' THEN (im.quantity * im.cost) ELSE 0 END) as outbound_value,
            COUNT(*) as total_movements
            FROM " . DB_PREFIX . "inventory_movement im
            LEFT JOIN " . DB_PREFIX . "product p ON (im.product_id = p.product_id)
            WHERE 1=1";

        // Apply date filter
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(im.created_at) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(im.created_at) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // Apply other filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND im.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category_id'] . "'";
        }

        $sql .= " GROUP BY " . $date_group . " ORDER BY period ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Get inventory valuation analysis
     *
     * @param array $data Filter parameters
     * @return array Valuation analysis data
     */
    public function getValuationAnalysis($data = array()) {
        $sql = "SELECT
            p.category_id,
            cd.name as category_name,
            COUNT(DISTINCT pi.product_id) as product_count,
            SUM(pi.quantity) as total_quantity,
            SUM(pi.quantity * pi.average_cost) as total_value,
            AVG(pi.average_cost) as avg_cost,
            MIN(pi.average_cost) as min_cost,
            MAX(pi.average_cost) as max_cost
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (p.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE p.status = '1' AND pi.quantity > 0";

        // Apply filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category_id'] . "'";
        }

        $sql .= " GROUP BY p.category_id, cd.name ORDER BY total_value DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Get ABC Analysis (Pareto Analysis) for inventory
     *
     * @param array $data Filter parameters
     * @return array ABC analysis data
     */
    public function getABCAnalysis($data = array()) {
        // First, get all products with their value and movement data
        $sql = "SELECT
            pi.product_id,
            pd.name as product_name,
            p.model,
            p.sku,
            pi.quantity,
            pi.average_cost,
            (pi.quantity * pi.average_cost) as inventory_value,
            COALESCE(movement_data.total_movements, 0) as total_movements,
            COALESCE(movement_data.total_value_moved, 0) as total_value_moved
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN (
                SELECT
                    product_id,
                    COUNT(*) as total_movements,
                    SUM(quantity * cost) as total_value_moved
                FROM " . DB_PREFIX . "inventory_movement
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY product_id
            ) movement_data ON (pi.product_id = movement_data.product_id)
            WHERE p.status = '1' AND pi.quantity > 0";

        // Apply filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category_id'] . "'";
        }

        $sql .= " ORDER BY total_value_moved DESC, inventory_value DESC";

        $query = $this->db->query($sql);
        $products = $query->rows;

        // Calculate ABC classification
        $total_value = array_sum(array_column($products, 'total_value_moved'));
        $cumulative_value = 0;
        $abc_data = array();

        foreach ($products as $product) {
            $cumulative_value += $product['total_value_moved'];
            $cumulative_percentage = $total_value > 0 ? ($cumulative_value / $total_value) * 100 : 0;

            if ($cumulative_percentage <= 80) {
                $classification = 'A';
            } elseif ($cumulative_percentage <= 95) {
                $classification = 'B';
            } else {
                $classification = 'C';
            }

            $abc_data[] = array_merge($product, array(
                'classification' => $classification,
                'cumulative_percentage' => round($cumulative_percentage, 2),
                'value_percentage' => $total_value > 0 ? round(($product['total_value_moved'] / $total_value) * 100, 2) : 0
            ));
        }

        return $abc_data;
    }

    /**
     * Get slow moving items analysis
     *
     * @param array $data Filter parameters
     * @return array Slow moving items data
     */
    public function getSlowMovingItems($data = array()) {
        $days_threshold = isset($data['days_threshold']) ? (int)$data['days_threshold'] : 90;

        $sql = "SELECT
            pi.product_id,
            pd.name as product_name,
            p.model,
            p.sku,
            pi.quantity,
            pi.average_cost,
            (pi.quantity * pi.average_cost) as inventory_value,
            COALESCE(last_movement.last_movement_date, p.date_added) as last_movement_date,
            DATEDIFF(NOW(), COALESCE(last_movement.last_movement_date, p.date_added)) as days_since_movement,
            COALESCE(movement_count.movement_count, 0) as movement_count_90_days
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN (
                SELECT
                    product_id,
                    MAX(created_at) as last_movement_date
                FROM " . DB_PREFIX . "inventory_movement
                GROUP BY product_id
            ) last_movement ON (pi.product_id = last_movement.product_id)
            LEFT JOIN (
                SELECT
                    product_id,
                    COUNT(*) as movement_count
                FROM " . DB_PREFIX . "inventory_movement
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                GROUP BY product_id
            ) movement_count ON (pi.product_id = movement_count.product_id)
            WHERE p.status = '1'
            AND pi.quantity > 0
            AND DATEDIFF(NOW(), COALESCE(last_movement.last_movement_date, p.date_added)) >= " . (int)$days_threshold;

        // Apply filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category_id'] . "'";
        }

        $sql .= " ORDER BY days_since_movement DESC, inventory_value DESC";

        // Apply pagination
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
     * Get stock alerts (low stock, out of stock, overstock)
     *
     * @param array $data Filter parameters
     * @return array Stock alerts data
     */
    public function getStockAlerts($data = array()) {
        $sql = "SELECT
            pi.product_id,
            pd.name as product_name,
            p.model,
            p.sku,
            b.name as branch_name,
            u.name as unit_name,
            pi.quantity,
            psl.minimum_stock,
            psl.reorder_point,
            psl.maximum_stock,
            CASE
                WHEN pi.quantity <= 0 THEN 'out_of_stock'
                WHEN pi.quantity <= psl.minimum_stock THEN 'low_stock'
                WHEN pi.quantity >= psl.maximum_stock THEN 'overstock'
                ELSE 'normal'
            END as alert_type,
            CASE
                WHEN pi.quantity <= 0 THEN 'critical'
                WHEN pi.quantity <= psl.minimum_stock THEN 'warning'
                WHEN pi.quantity >= psl.maximum_stock THEN 'info'
                ELSE 'normal'
            END as alert_level
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "product_stock_level psl ON (pi.product_id = psl.product_id AND pi.branch_id = psl.branch_id AND pi.unit_id = psl.unit_id)
            WHERE p.status = '1'
            AND (pi.quantity <= 0 OR pi.quantity <= psl.minimum_stock OR pi.quantity >= psl.maximum_stock)";

        // Apply filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category_id'] . "'";
        }

        $sql .= " ORDER BY
            CASE alert_level
                WHEN 'critical' THEN 1
                WHEN 'warning' THEN 2
                WHEN 'info' THEN 3
                ELSE 4
            END, pd.name ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Get export data for analytics
     *
     * @param array $data Filter parameters
     * @return array Export data
     */
    public function getExportData($data = array()) {
        return array(
            'summary' => $this->getInventorySummary($data),
            'stock_levels' => $this->getStockLevels($data),
            'movement_trends' => $this->getMovementTrends($data),
            'valuation_analysis' => $this->getValuationAnalysis($data),
            'abc_analysis' => $this->getABCAnalysis($data),
            'slow_moving_items' => $this->getSlowMovingItems($data),
            'stock_alerts' => $this->getStockAlerts($data)
        );
    }
}
