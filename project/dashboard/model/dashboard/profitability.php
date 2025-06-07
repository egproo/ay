<?php
/**
 * Profitability Dashboard Model
 *
 * Provides data analysis and calculations for profitability dashboard
 * including summary statistics, trends, and performance metrics
 */
class ModelDashboardProfitability extends Model {

    /**
     * Get profitability summary statistics
     *
     * @param array $data Filter parameters
     * @return array Summary statistics
     */
    public function getProfitabilitySummary($data = array()) {
        $sql = "SELECT
            COUNT(DISTINCT o.order_id) as total_orders,
            COUNT(DISTINCT o.customer_id) as total_customers,
            COUNT(DISTINCT op.product_id) as total_products,
            SUM(o.total) as total_revenue,
            SUM(
                (SELECT SUM(op2.quantity * COALESCE(p2.average_cost, 0))
                 FROM " . DB_PREFIX . "order_product op2
                 JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                 WHERE op2.order_id = o.order_id)
            ) as total_cost,
            SUM(o.total) - SUM(
                (SELECT SUM(op2.quantity * COALESCE(p2.average_cost, 0))
                 FROM " . DB_PREFIX . "order_product op2
                 JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                 WHERE op2.order_id = o.order_id)
            ) as total_profit,
            AVG(o.total) as avg_order_value,
            SUM(op.quantity) as total_quantity_sold
            FROM " . DB_PREFIX . "order o
            JOIN " . DB_PREFIX . "order_product op ON o.order_id = op.order_id
            WHERE o.order_status_id > 0";

        // Apply date filter
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // Apply other filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND o.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_customer_group_id'])) {
            $sql .= " AND o.customer_group_id = '" . (int)$data['filter_customer_group_id'] . "'";
        }

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            $result = $query->row;

            // Calculate additional metrics
            $result['profit_margin'] = $result['total_revenue'] > 0 ?
                round(($result['total_profit'] / $result['total_revenue']) * 100, 2) : 0;

            $result['avg_profit_per_order'] = $result['total_orders'] > 0 ?
                round($result['total_profit'] / $result['total_orders'], 2) : 0;

            $result['avg_profit_per_customer'] = $result['total_customers'] > 0 ?
                round($result['total_profit'] / $result['total_customers'], 2) : 0;

            $result['cost_ratio'] = $result['total_revenue'] > 0 ?
                round(($result['total_cost'] / $result['total_revenue']) * 100, 2) : 0;

            // Format numbers
            $result['total_revenue'] = round($result['total_revenue'], 2);
            $result['total_cost'] = round($result['total_cost'], 2);
            $result['total_profit'] = round($result['total_profit'], 2);
            $result['avg_order_value'] = round($result['avg_order_value'], 2);

            return $result;
        }

        return array(
            'total_orders' => 0,
            'total_customers' => 0,
            'total_products' => 0,
            'total_revenue' => 0,
            'total_cost' => 0,
            'total_profit' => 0,
            'profit_margin' => 0,
            'avg_order_value' => 0,
            'avg_profit_per_order' => 0,
            'avg_profit_per_customer' => 0,
            'cost_ratio' => 0,
            'total_quantity_sold' => 0
        );
    }

    /**
     * Get profitability trends over time
     *
     * @param array $data Filter parameters
     * @return array Trends data
     */
    public function getProfitabilityTrends($data = array()) {
        $period = isset($data['filter_period']) ? $data['filter_period'] : 'month';

        // Determine date grouping based on period
        switch ($period) {
            case 'day':
                $date_format = '%Y-%m-%d';
                $date_group = 'DATE(o.date_added)';
                break;
            case 'week':
                $date_format = '%Y-%u';
                $date_group = 'YEARWEEK(o.date_added)';
                break;
            case 'month':
                $date_format = '%Y-%m';
                $date_group = 'DATE_FORMAT(o.date_added, "%Y-%m")';
                break;
            case 'quarter':
                $date_format = '%Y-Q%q';
                $date_group = 'CONCAT(YEAR(o.date_added), "-Q", QUARTER(o.date_added))';
                break;
            case 'year':
                $date_format = '%Y';
                $date_group = 'YEAR(o.date_added)';
                break;
            default:
                $date_format = '%Y-%m';
                $date_group = 'DATE_FORMAT(o.date_added, "%Y-%m")';
        }

        $sql = "SELECT
            " . $date_group . " as period,
            COUNT(DISTINCT o.order_id) as order_count,
            SUM(o.total) as revenue,
            SUM(
                (SELECT SUM(op2.quantity * COALESCE(p2.average_cost, 0))
                 FROM " . DB_PREFIX . "order_product op2
                 JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                 WHERE op2.order_id = o.order_id)
            ) as cost,
            SUM(o.total) - SUM(
                (SELECT SUM(op2.quantity * COALESCE(p2.average_cost, 0))
                 FROM " . DB_PREFIX . "order_product op2
                 JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                 WHERE op2.order_id = o.order_id)
            ) as profit,
            AVG(o.total) as avg_order_value,
            COUNT(DISTINCT o.customer_id) as customer_count
            FROM " . DB_PREFIX . "order o
            WHERE o.order_status_id > 0";

        // Apply date filter
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // Apply other filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND o.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_customer_group_id'])) {
            $sql .= " AND o.customer_group_id = '" . (int)$data['filter_customer_group_id'] . "'";
        }

        $sql .= " GROUP BY " . $date_group . " ORDER BY period ASC";

        $query = $this->db->query($sql);

        $trends = array();
        foreach ($query->rows as $row) {
            $row['profit_margin'] = $row['revenue'] > 0 ? round(($row['profit'] / $row['revenue']) * 100, 2) : 0;
            $row['cost_ratio'] = $row['revenue'] > 0 ? round(($row['cost'] / $row['revenue']) * 100, 2) : 0;
            $row['revenue'] = round($row['revenue'], 2);
            $row['cost'] = round($row['cost'], 2);
            $row['profit'] = round($row['profit'], 2);
            $row['avg_order_value'] = round($row['avg_order_value'], 2);

            $trends[] = $row;
        }

        return $trends;
    }

    /**
     * Get margin analysis by different dimensions
     *
     * @param array $data Filter parameters
     * @return array Margin analysis data
     */
    public function getMarginAnalysis($data = array()) {
        $analysis = array();

        // Margin by product category
        $sql = "SELECT
            c.category_id,
            cd.name as category_name,
            COUNT(DISTINCT op.product_id) as product_count,
            SUM(op.total) as revenue,
            SUM(op.quantity * COALESCE(p.average_cost, 0)) as cost,
            SUM(op.total) - SUM(op.quantity * COALESCE(p.average_cost, 0)) as profit,
            CASE
                WHEN SUM(op.total) > 0 THEN
                    ((SUM(op.total) - SUM(op.quantity * COALESCE(p.average_cost, 0))) / SUM(op.total)) * 100
                ELSE 0
            END as margin_percentage
            FROM " . DB_PREFIX . "order_product op
            JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
            JOIN " . DB_PREFIX . "product p ON op.product_id = p.product_id
            JOIN " . DB_PREFIX . "product_to_category pc ON p.product_id = pc.product_id
            JOIN " . DB_PREFIX . "category c ON pc.category_id = c.category_id
            JOIN " . DB_PREFIX . "category_description cd ON c.category_id = cd.category_id
                AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            WHERE o.order_status_id > 0";

        // Apply date filter
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // Apply other filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND o.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        $sql .= " GROUP BY c.category_id, cd.name ORDER BY margin_percentage DESC";

        $query = $this->db->query($sql);
        $analysis['by_category'] = $query->rows;

        // Margin by customer group
        $sql = "SELECT
            cg.customer_group_id,
            cgd.name as customer_group_name,
            COUNT(DISTINCT o.customer_id) as customer_count,
            COUNT(DISTINCT o.order_id) as order_count,
            SUM(o.total) as revenue,
            SUM(
                (SELECT SUM(op2.quantity * COALESCE(p2.average_cost, 0))
                 FROM " . DB_PREFIX . "order_product op2
                 JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                 WHERE op2.order_id = o.order_id)
            ) as cost,
            SUM(o.total) - SUM(
                (SELECT SUM(op2.quantity * COALESCE(p2.average_cost, 0))
                 FROM " . DB_PREFIX . "order_product op2
                 JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                 WHERE op2.order_id = o.order_id)
            ) as profit,
            CASE
                WHEN SUM(o.total) > 0 THEN
                    ((SUM(o.total) - SUM(
                        (SELECT SUM(op2.quantity * COALESCE(p2.average_cost, 0))
                         FROM " . DB_PREFIX . "order_product op2
                         JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                         WHERE op2.order_id = o.order_id)
                    )) / SUM(o.total)) * 100
                ELSE 0
            END as margin_percentage
            FROM " . DB_PREFIX . "order o
            JOIN " . DB_PREFIX . "customer_group cg ON o.customer_group_id = cg.customer_group_id
            JOIN " . DB_PREFIX . "customer_group_description cgd ON cg.customer_group_id = cgd.customer_group_id
                AND cgd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            WHERE o.order_status_id > 0";

        // Apply date filter
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // Apply other filters
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND o.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        $sql .= " GROUP BY cg.customer_group_id, cgd.name ORDER BY margin_percentage DESC";

        $query = $this->db->query($sql);
        $analysis['by_customer_group'] = $query->rows;

        return $analysis;
    }

    /**
     * Get top performing products by profitability
     *
     * @param array $data Filter parameters
     * @return array Top products data
     */
    public function getTopPerformingProducts($data = array()) {
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;

        $sql = "SELECT
            p.product_id,
            pd.name as product_name,
            p.model,
            p.sku,
            SUM(op.quantity) as quantity_sold,
            SUM(op.total) as revenue,
            SUM(op.quantity * COALESCE(p.average_cost, 0)) as cost,
            SUM(op.total) - SUM(op.quantity * COALESCE(p.average_cost, 0)) as profit,
            CASE
                WHEN SUM(op.total) > 0 THEN
                    ((SUM(op.total) - SUM(op.quantity * COALESCE(p.average_cost, 0))) / SUM(op.total)) * 100
                ELSE 0
            END as margin_percentage,
            COUNT(DISTINCT o.order_id) as order_count,
            AVG(op.price) as avg_selling_price
            FROM " . DB_PREFIX . "order_product op
            JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
            JOIN " . DB_PREFIX . "product p ON op.product_id = p.product_id
            JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            WHERE o.order_status_id > 0";

        // Apply date filter
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " GROUP BY p.product_id, pd.name, p.model, p.sku
                  HAVING profit > 0
                  ORDER BY profit DESC, margin_percentage DESC
                  LIMIT " . $limit;

        $query = $this->db->query($sql);

        $products = array();
        foreach ($query->rows as $row) {
            $row['revenue'] = round($row['revenue'], 2);
            $row['cost'] = round($row['cost'], 2);
            $row['profit'] = round($row['profit'], 2);
            $row['margin_percentage'] = round($row['margin_percentage'], 2);
            $row['avg_selling_price'] = round($row['avg_selling_price'], 2);

            $products[] = $row;
        }

        return $products;
    }

    /**
     * Get profitability insights and recommendations
     *
     * @param array $data Filter parameters
     * @return array Insights and recommendations
     */
    public function getProfitabilityInsights($data = array()) {
        $insights = array();

        // Get summary data
        $summary = $this->getProfitabilitySummary($data);

        // Performance insights
        $insights['performance'] = array();

        if ($summary['profit_margin'] > 25) {
            $insights['performance'][] = array(
                'type' => 'success',
                'title' => 'هامش ربح ممتاز',
                'message' => 'هامش الربح الحالي ' . $summary['profit_margin'] . '% يعتبر ممتازاً',
                'recommendation' => 'حافظ على الاستراتيجية الحالية وركز على زيادة الحجم'
            );
        } elseif ($summary['profit_margin'] > 15) {
            $insights['performance'][] = array(
                'type' => 'info',
                'title' => 'هامش ربح جيد',
                'message' => 'هامش الربح الحالي ' . $summary['profit_margin'] . '% جيد',
                'recommendation' => 'ابحث عن فرص لتحسين الكفاءة وتقليل التكاليف'
            );
        } elseif ($summary['profit_margin'] > 5) {
            $insights['performance'][] = array(
                'type' => 'warning',
                'title' => 'هامش ربح متوسط',
                'message' => 'هامش الربح الحالي ' . $summary['profit_margin'] . '% يحتاج تحسين',
                'recommendation' => 'راجع استراتيجية التسعير وتكاليف المنتجات'
            );
        } else {
            $insights['performance'][] = array(
                'type' => 'danger',
                'title' => 'هامش ربح منخفض',
                'message' => 'هامش الربح الحالي ' . $summary['profit_margin'] . '% منخفض جداً',
                'recommendation' => 'مراجعة عاجلة للتسعير والتكاليف مطلوبة'
            );
        }

        // Cost analysis insights
        if ($summary['cost_ratio'] > 80) {
            $insights['performance'][] = array(
                'type' => 'warning',
                'title' => 'نسبة تكلفة عالية',
                'message' => 'نسبة التكلفة ' . $summary['cost_ratio'] . '% من الإيرادات عالية',
                'recommendation' => 'ابحث عن موردين أفضل أو حسن عمليات الإنتاج'
            );
        }

        // Get top and bottom performers
        $top_products = $this->getTopPerformingProducts(array_merge($data, array('limit' => 5)));
        $insights['top_products'] = $top_products;

        // Get low margin products
        $low_margin_products = $this->getLowMarginProducts($data);
        $insights['low_margin_products'] = $low_margin_products;

        // Trend analysis
        $trends = $this->getProfitabilityTrends($data);
        if (count($trends) >= 2) {
            $latest = end($trends);
            $previous = prev($trends);

            $profit_change = $latest['profit'] - $previous['profit'];
            $margin_change = $latest['profit_margin'] - $previous['profit_margin'];

            if ($profit_change > 0) {
                $insights['trends'][] = array(
                    'type' => 'success',
                    'title' => 'نمو في الأرباح',
                    'message' => 'الأرباح زادت بمقدار ' . number_format($profit_change, 2) . ' مقارنة بالفترة السابقة'
                );
            } else {
                $insights['trends'][] = array(
                    'type' => 'warning',
                    'title' => 'انخفاض في الأرباح',
                    'message' => 'الأرباح انخفضت بمقدار ' . number_format(abs($profit_change), 2) . ' مقارنة بالفترة السابقة'
                );
            }

            if ($margin_change > 0) {
                $insights['trends'][] = array(
                    'type' => 'success',
                    'title' => 'تحسن في هامش الربح',
                    'message' => 'هامش الربح تحسن بـ ' . number_format($margin_change, 2) . '% مقارنة بالفترة السابقة'
                );
            } elseif ($margin_change < 0) {
                $insights['trends'][] = array(
                    'type' => 'warning',
                    'title' => 'انخفاض في هامش الربح',
                    'message' => 'هامش الربح انخفض بـ ' . number_format(abs($margin_change), 2) . '% مقارنة بالفترة السابقة'
                );
            }
        }

        return $insights;
    }

    /**
     * Get products with low profit margins
     *
     * @param array $data Filter parameters
     * @return array Low margin products
     */
    public function getLowMarginProducts($data = array()) {
        $sql = "SELECT
            p.product_id,
            pd.name as product_name,
            p.model,
            p.sku,
            SUM(op.quantity) as quantity_sold,
            SUM(op.total) as revenue,
            SUM(op.quantity * COALESCE(p.average_cost, 0)) as cost,
            SUM(op.total) - SUM(op.quantity * COALESCE(p.average_cost, 0)) as profit,
            CASE
                WHEN SUM(op.total) > 0 THEN
                    ((SUM(op.total) - SUM(op.quantity * COALESCE(p.average_cost, 0))) / SUM(op.total)) * 100
                ELSE 0
            END as margin_percentage
            FROM " . DB_PREFIX . "order_product op
            JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
            JOIN " . DB_PREFIX . "product p ON op.product_id = p.product_id
            JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            WHERE o.order_status_id > 0";

        // Apply date filter
        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " GROUP BY p.product_id, pd.name, p.model, p.sku
                  HAVING margin_percentage < 10 AND revenue > 100
                  ORDER BY margin_percentage ASC, revenue DESC
                  LIMIT 10";

        $query = $this->db->query($sql);

        $products = array();
        foreach ($query->rows as $row) {
            $row['revenue'] = round($row['revenue'], 2);
            $row['cost'] = round($row['cost'], 2);
            $row['profit'] = round($row['profit'], 2);
            $row['margin_percentage'] = round($row['margin_percentage'], 2);

            $products[] = $row;
        }

        return $products;
    }

    /**
     * Get export data for profitability analysis
     *
     * @param array $data Filter parameters
     * @return array Export data
     */
    public function getExportData($data = array()) {
        return array(
            'summary' => $this->getProfitabilitySummary($data),
            'trends' => $this->getProfitabilityTrends($data),
            'margin_analysis' => $this->getMarginAnalysis($data),
            'top_products' => $this->getTopPerformingProducts(array_merge($data, array('limit' => 50))),
            'low_margin_products' => $this->getLowMarginProducts($data),
            'insights' => $this->getProfitabilityInsights($data)
        );
    }
}
