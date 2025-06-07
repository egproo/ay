<?php
/**
 * نموذج تحليل الربحية المتقدم
 * يدعم تحليل الربحية حسب المنتج، العميل، الفرع، الفترة الزمنية، وغيرها
 */
class ModelReportsProfitabilityAnalysis extends Model {
    
    /**
     * إنشاء تحليل الربحية الشامل
     */
    public function generateProfitabilityAnalysis($date_start, $date_end, $analysis_type = 'overview', $group_by = 'product') {
        $currency_code = $this->config->get('config_currency');
        
        switch ($group_by) {
            case 'product':
                $items = $this->getProductProfitability($date_start, $date_end);
                break;
            case 'customer':
                $items = $this->getCustomerProfitability($date_start, $date_end);
                break;
            case 'category':
                $items = $this->getCategoryProfitability($date_start, $date_end);
                break;
            case 'period':
                $items = $this->getPeriodProfitability($date_start, $date_end);
                break;
            case 'salesperson':
                $items = $this->getSalespersonProfitability($date_start, $date_end);
                break;
            case 'location':
                $items = $this->getLocationProfitability($date_start, $date_end);
                break;
            default:
                $items = $this->getProductProfitability($date_start, $date_end);
        }
        
        // حساب الإجماليات
        $totals = $this->calculateTotals($items);
        
        return array(
            'period' => array(
                'date_start' => $date_start,
                'date_end' => $date_end,
                'date_start_formatted' => date($this->language->get('date_format_short'), strtotime($date_start)),
                'date_end_formatted' => date($this->language->get('date_format_short'), strtotime($date_end))
            ),
            'analysis_type' => $analysis_type,
            'group_by' => $group_by,
            'items' => $this->formatProfitabilityItems($items, $currency_code),
            'totals' => $this->formatTotals($totals, $currency_code),
            'summary' => $this->generateSummary($items, $totals),
            'trends' => $this->getTrends($date_start, $date_end, $group_by),
            'top_performers' => $this->getTopPerformers($items, 10),
            'bottom_performers' => $this->getBottomPerformers($items, 10)
        );
    }
    
    /**
     * تحليل الربحية حسب المنتج
     */
    public function getProductProfitability($date_start, $date_end) {
        $sql = "SELECT 
                    p.product_id,
                    pd.name,
                    p.model,
                    p.sku,
                    SUM(op.quantity) as quantity_sold,
                    SUM(op.total) as revenue,
                    SUM(op.quantity * COALESCE(ph.cost, p.cost, 0)) as cost_of_goods,
                    SUM(op.total) - SUM(op.quantity * COALESCE(ph.cost, p.cost, 0)) as gross_profit,
                    CASE 
                        WHEN SUM(op.total) > 0 THEN 
                            ((SUM(op.total) - SUM(op.quantity * COALESCE(ph.cost, p.cost, 0))) / SUM(op.total)) * 100
                        ELSE 0 
                    END as margin_percentage,
                    COUNT(DISTINCT o.order_id) as order_count,
                    COUNT(DISTINCT o.customer_id) as customer_count,
                    AVG(op.price) as avg_selling_price,
                    AVG(COALESCE(ph.cost, p.cost, 0)) as avg_cost_price
                FROM " . DB_PREFIX . "order_product op
                JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                JOIN " . DB_PREFIX . "product p ON op.product_id = p.product_id
                JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN " . DB_PREFIX . "product_cost_history ph ON (p.product_id = ph.product_id AND ph.date_added <= o.date_added)
                WHERE o.order_status_id > 0
                AND DATE(o.date_added) BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                GROUP BY p.product_id
                ORDER BY gross_profit DESC";
        
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    /**
     * تحليل الربحية حسب العميل
     */
    public function getCustomerProfitability($date_start, $date_end) {
        $sql = "SELECT 
                    o.customer_id,
                    CONCAT(o.firstname, ' ', o.lastname) as customer_name,
                    o.email,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(o.total) as revenue,
                    SUM(
                        (SELECT SUM(op2.quantity * COALESCE(ph2.cost, p2.cost, 0))
                         FROM " . DB_PREFIX . "order_product op2
                         JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                         LEFT JOIN " . DB_PREFIX . "product_cost_history ph2 ON (p2.product_id = ph2.product_id AND ph2.date_added <= o.date_added)
                         WHERE op2.order_id = o.order_id)
                    ) as cost_of_goods,
                    SUM(o.total) - SUM(
                        (SELECT SUM(op2.quantity * COALESCE(ph2.cost, p2.cost, 0))
                         FROM " . DB_PREFIX . "order_product op2
                         JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                         LEFT JOIN " . DB_PREFIX . "product_cost_history ph2 ON (p2.product_id = ph2.product_id AND ph2.date_added <= o.date_added)
                         WHERE op2.order_id = o.order_id)
                    ) as gross_profit,
                    AVG(o.total) as avg_order_value,
                    MIN(o.date_added) as first_order_date,
                    MAX(o.date_added) as last_order_date,
                    DATEDIFF(MAX(o.date_added), MIN(o.date_added)) as customer_lifetime_days
                FROM " . DB_PREFIX . "order o
                WHERE o.order_status_id > 0
                AND o.customer_id > 0
                AND DATE(o.date_added) BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                GROUP BY o.customer_id
                ORDER BY gross_profit DESC";
        
        $query = $this->db->query($sql);
        
        // حساب هامش الربح لكل عميل
        foreach ($query->rows as &$row) {
            $row['margin_percentage'] = $row['revenue'] > 0 ? ($row['gross_profit'] / $row['revenue']) * 100 : 0;
            $row['quantity_sold'] = $this->getCustomerQuantitySold($row['customer_id'], $date_start, $date_end);
        }
        
        return $query->rows;
    }
    
    /**
     * تحليل الربحية حسب الفئة
     */
    public function getCategoryProfitability($date_start, $date_end) {
        $sql = "SELECT 
                    c.category_id,
                    cd.name,
                    COUNT(DISTINCT p.product_id) as product_count,
                    SUM(op.quantity) as quantity_sold,
                    SUM(op.total) as revenue,
                    SUM(op.quantity * COALESCE(ph.cost, p.cost, 0)) as cost_of_goods,
                    SUM(op.total) - SUM(op.quantity * COALESCE(ph.cost, p.cost, 0)) as gross_profit,
                    COUNT(DISTINCT o.order_id) as order_count,
                    COUNT(DISTINCT o.customer_id) as customer_count
                FROM " . DB_PREFIX . "category c
                JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                JOIN " . DB_PREFIX . "product_to_category pc ON c.category_id = pc.category_id
                JOIN " . DB_PREFIX . "product p ON pc.product_id = p.product_id
                JOIN " . DB_PREFIX . "order_product op ON p.product_id = op.product_id
                JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                LEFT JOIN " . DB_PREFIX . "product_cost_history ph ON (p.product_id = ph.product_id AND ph.date_added <= o.date_added)
                WHERE o.order_status_id > 0
                AND DATE(o.date_added) BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                GROUP BY c.category_id
                ORDER BY gross_profit DESC";
        
        $query = $this->db->query($sql);
        
        // حساب هامش الربح لكل فئة
        foreach ($query->rows as &$row) {
            $row['margin_percentage'] = $row['revenue'] > 0 ? ($row['gross_profit'] / $row['revenue']) * 100 : 0;
        }
        
        return $query->rows;
    }
    
    /**
     * تحليل الربحية حسب الفترة الزمنية
     */
    public function getPeriodProfitability($date_start, $date_end) {
        $sql = "SELECT 
                    DATE(o.date_added) as period_date,
                    YEAR(o.date_added) as year,
                    MONTH(o.date_added) as month,
                    DAY(o.date_added) as day,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(o.total) as revenue,
                    SUM(
                        (SELECT SUM(op2.quantity * COALESCE(ph2.cost, p2.cost, 0))
                         FROM " . DB_PREFIX . "order_product op2
                         JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                         LEFT JOIN " . DB_PREFIX . "product_cost_history ph2 ON (p2.product_id = ph2.product_id AND ph2.date_added <= o.date_added)
                         WHERE op2.order_id = o.order_id)
                    ) as cost_of_goods,
                    COUNT(DISTINCT o.customer_id) as customer_count,
                    SUM(
                        (SELECT SUM(op3.quantity)
                         FROM " . DB_PREFIX . "order_product op3
                         WHERE op3.order_id = o.order_id)
                    ) as quantity_sold
                FROM " . DB_PREFIX . "order o
                WHERE o.order_status_id > 0
                AND DATE(o.date_added) BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                GROUP BY DATE(o.date_added)
                ORDER BY period_date ASC";
        
        $query = $this->db->query($sql);
        
        // حساب الربح وهامش الربح لكل فترة
        foreach ($query->rows as &$row) {
            $row['gross_profit'] = $row['revenue'] - $row['cost_of_goods'];
            $row['margin_percentage'] = $row['revenue'] > 0 ? ($row['gross_profit'] / $row['revenue']) * 100 : 0;
            $row['name'] = date('Y-m-d', strtotime($row['period_date']));
        }
        
        return $query->rows;
    }
    
    /**
     * تحليل الربحية حسب مندوب المبيعات
     */
    public function getSalespersonProfitability($date_start, $date_end) {
        $sql = "SELECT 
                    u.user_id,
                    CONCAT(u.firstname, ' ', u.lastname) as name,
                    u.email,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(o.total) as revenue,
                    SUM(
                        (SELECT SUM(op2.quantity * COALESCE(ph2.cost, p2.cost, 0))
                         FROM " . DB_PREFIX . "order_product op2
                         JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                         LEFT JOIN " . DB_PREFIX . "product_cost_history ph2 ON (p2.product_id = ph2.product_id AND ph2.date_added <= o.date_added)
                         WHERE op2.order_id = o.order_id)
                    ) as cost_of_goods,
                    COUNT(DISTINCT o.customer_id) as customer_count,
                    AVG(o.total) as avg_order_value
                FROM " . DB_PREFIX . "user u
                JOIN " . DB_PREFIX . "order o ON u.user_id = o.salesperson_id
                WHERE o.order_status_id > 0
                AND DATE(o.date_added) BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                GROUP BY u.user_id
                ORDER BY revenue DESC";
        
        $query = $this->db->query($sql);
        
        // حساب الربح وهامش الربح لكل مندوب
        foreach ($query->rows as &$row) {
            $row['gross_profit'] = $row['revenue'] - $row['cost_of_goods'];
            $row['margin_percentage'] = $row['revenue'] > 0 ? ($row['gross_profit'] / $row['revenue']) * 100 : 0;
            $row['quantity_sold'] = $this->getSalespersonQuantitySold($row['user_id'], $date_start, $date_end);
        }
        
        return $query->rows;
    }
    
    /**
     * تحليل الربحية حسب الموقع/الفرع
     */
    public function getLocationProfitability($date_start, $date_end) {
        $sql = "SELECT 
                    b.branch_id,
                    b.name,
                    b.location,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(o.total) as revenue,
                    SUM(
                        (SELECT SUM(op2.quantity * COALESCE(ph2.cost, p2.cost, 0))
                         FROM " . DB_PREFIX . "order_product op2
                         JOIN " . DB_PREFIX . "product p2 ON op2.product_id = p2.product_id
                         LEFT JOIN " . DB_PREFIX . "product_cost_history ph2 ON (p2.product_id = ph2.product_id AND ph2.date_added <= o.date_added)
                         WHERE op2.order_id = o.order_id)
                    ) as cost_of_goods,
                    COUNT(DISTINCT o.customer_id) as customer_count
                FROM " . DB_PREFIX . "branch b
                JOIN " . DB_PREFIX . "order o ON b.branch_id = o.branch_id
                WHERE o.order_status_id > 0
                AND DATE(o.date_added) BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                GROUP BY b.branch_id
                ORDER BY revenue DESC";
        
        $query = $this->db->query($sql);
        
        // حساب الربح وهامش الربح لكل موقع
        foreach ($query->rows as &$row) {
            $row['gross_profit'] = $row['revenue'] - $row['cost_of_goods'];
            $row['margin_percentage'] = $row['revenue'] > 0 ? ($row['gross_profit'] / $row['revenue']) * 100 : 0;
            $row['quantity_sold'] = $this->getLocationQuantitySold($row['branch_id'], $date_start, $date_end);
        }
        
        return $query->rows;
    }
    
    /**
     * حساب الإجماليات
     */
    private function calculateTotals($items) {
        $totals = array(
            'revenue' => 0,
            'cost' => 0,
            'profit' => 0,
            'quantity' => 0,
            'order_count' => 0,
            'customer_count' => 0
        );
        
        foreach ($items as $item) {
            $totals['revenue'] += (float)$item['revenue'];
            $totals['cost'] += (float)($item['cost_of_goods'] ?? $item['cost'] ?? 0);
            $totals['profit'] += (float)($item['gross_profit'] ?? $item['profit'] ?? 0);
            $totals['quantity'] += (float)($item['quantity_sold'] ?? $item['quantity'] ?? 0);
            $totals['order_count'] += (int)($item['order_count'] ?? 0);
            
            if (isset($item['customer_count'])) {
                $totals['customer_count'] += (int)$item['customer_count'];
            }
        }
        
        $totals['margin'] = $totals['revenue'] > 0 ? ($totals['profit'] / $totals['revenue']) * 100 : 0;
        $totals['avg_order_value'] = $totals['order_count'] > 0 ? $totals['revenue'] / $totals['order_count'] : 0;
        
        return $totals;
    }
    
    /**
     * تنسيق عناصر الربحية
     */
    private function formatProfitabilityItems($items, $currency_code) {
        $formatted_items = array();
        
        foreach ($items as $item) {
            $revenue = (float)$item['revenue'];
            $cost = (float)($item['cost_of_goods'] ?? $item['cost'] ?? 0);
            $profit = (float)($item['gross_profit'] ?? $item['profit'] ?? ($revenue - $cost));
            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
            
            $formatted_items[] = array(
                'id' => $item['product_id'] ?? $item['customer_id'] ?? $item['category_id'] ?? $item['user_id'] ?? $item['branch_id'] ?? 0,
                'name' => $item['name'],
                'revenue' => $revenue,
                'revenue_formatted' => $this->currency->format($revenue, $currency_code),
                'cost' => $cost,
                'cost_formatted' => $this->currency->format($cost, $currency_code),
                'profit' => $profit,
                'profit_formatted' => $this->currency->format($profit, $currency_code),
                'margin' => $margin,
                'margin_formatted' => number_format($margin, 2) . '%',
                'quantity' => (float)($item['quantity_sold'] ?? $item['quantity'] ?? 0),
                'quantity_formatted' => number_format((float)($item['quantity_sold'] ?? $item['quantity'] ?? 0), 0),
                'order_count' => (int)($item['order_count'] ?? 0),
                'customer_count' => (int)($item['customer_count'] ?? 0),
                'additional_data' => $this->getAdditionalItemData($item)
            );
        }
        
        return $formatted_items;
    }
    
    /**
     * تنسيق الإجماليات
     */
    private function formatTotals($totals, $currency_code) {
        return array(
            'revenue' => $totals['revenue'],
            'revenue_formatted' => $this->currency->format($totals['revenue'], $currency_code),
            'cost' => $totals['cost'],
            'cost_formatted' => $this->currency->format($totals['cost'], $currency_code),
            'profit' => $totals['profit'],
            'profit_formatted' => $this->currency->format($totals['profit'], $currency_code),
            'margin' => $totals['margin'],
            'margin_formatted' => number_format($totals['margin'], 2) . '%',
            'quantity' => $totals['quantity'],
            'quantity_formatted' => number_format($totals['quantity'], 0),
            'order_count' => $totals['order_count'],
            'customer_count' => $totals['customer_count'],
            'avg_order_value' => $totals['avg_order_value'],
            'avg_order_value_formatted' => $this->currency->format($totals['avg_order_value'], $currency_code)
        );
    }
    
    /**
     * إنشاء ملخص التحليل
     */
    private function generateSummary($items, $totals) {
        $item_count = count($items);
        
        return array(
            'total_items' => $item_count,
            'profitable_items' => count(array_filter($items, function($item) {
                return ($item['gross_profit'] ?? $item['profit'] ?? 0) > 0;
            })),
            'loss_making_items' => count(array_filter($items, function($item) {
                return ($item['gross_profit'] ?? $item['profit'] ?? 0) < 0;
            })),
            'avg_margin' => $totals['margin'],
            'top_revenue_item' => $item_count > 0 ? $items[0]['name'] : '',
            'performance_rating' => $this->calculatePerformanceRating($totals['margin'])
        );
    }
    
    /**
     * الحصول على الاتجاهات
     */
    private function getTrends($date_start, $date_end, $group_by) {
        // تنفيذ مبسط للاتجاهات - يمكن تطويره أكثر
        return array(
            'revenue_trend' => 'stable',
            'margin_trend' => 'improving',
            'volume_trend' => 'increasing'
        );
    }
    
    /**
     * الحصول على أفضل الأداءات
     */
    private function getTopPerformers($items, $limit = 10) {
        usort($items, function($a, $b) {
            return ($b['gross_profit'] ?? $b['profit'] ?? 0) <=> ($a['gross_profit'] ?? $a['profit'] ?? 0);
        });
        
        return array_slice($items, 0, $limit);
    }
    
    /**
     * الحصول على أسوأ الأداءات
     */
    private function getBottomPerformers($items, $limit = 10) {
        usort($items, function($a, $b) {
            return ($a['gross_profit'] ?? $a['profit'] ?? 0) <=> ($b['gross_profit'] ?? $b['profit'] ?? 0);
        });
        
        return array_slice($items, 0, $limit);
    }
    
    /**
     * دوال مساعدة
     */
    private function getCustomerQuantitySold($customer_id, $date_start, $date_end) {
        $query = $this->db->query("SELECT SUM(op.quantity) as total
                                  FROM " . DB_PREFIX . "order_product op
                                  JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                                  WHERE o.customer_id = '" . (int)$customer_id . "'
                                  AND o.order_status_id > 0
                                  AND DATE(o.date_added) BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        return (float)$query->row['total'];
    }
    
    private function getSalespersonQuantitySold($user_id, $date_start, $date_end) {
        $query = $this->db->query("SELECT SUM(op.quantity) as total
                                  FROM " . DB_PREFIX . "order_product op
                                  JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                                  WHERE o.salesperson_id = '" . (int)$user_id . "'
                                  AND o.order_status_id > 0
                                  AND DATE(o.date_added) BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        return (float)$query->row['total'];
    }
    
    private function getLocationQuantitySold($branch_id, $date_start, $date_end) {
        $query = $this->db->query("SELECT SUM(op.quantity) as total
                                  FROM " . DB_PREFIX . "order_product op
                                  JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                                  WHERE o.branch_id = '" . (int)$branch_id . "'
                                  AND o.order_status_id > 0
                                  AND DATE(o.date_added) BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        return (float)$query->row['total'];
    }
    
    private function getAdditionalItemData($item) {
        return array(
            'sku' => $item['sku'] ?? '',
            'model' => $item['model'] ?? '',
            'email' => $item['email'] ?? '',
            'location' => $item['location'] ?? ''
        );
    }
    
    private function calculatePerformanceRating($margin) {
        if ($margin >= 30) return 'excellent';
        if ($margin >= 20) return 'good';
        if ($margin >= 10) return 'average';
        if ($margin >= 0) return 'poor';
        return 'loss';
    }
}
