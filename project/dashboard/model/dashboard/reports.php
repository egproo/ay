<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 * 
 * Model: Dashboard Reports (لوحة التقارير والتحليلات)
 */
class ModelDashboardReports extends Model {
    
    /**
     * Get reports summary statistics
     * 
     * @param array $data
     * @return array
     */
    public function getReportsSummary($data = array()) {
        $date_from = isset($data['filter_date_from']) ? $data['filter_date_from'] : date('Y-m-01');
        $date_to = isset($data['filter_date_to']) ? $data['filter_date_to'] : date('Y-m-t');
        $branch_filter = isset($data['filter_branch']) && $data['filter_branch'] ? " AND store_id = '" . (int)$data['filter_branch'] . "'" : "";
        
        // Sales summary
        $sales_query = $this->db->query("SELECT 
            COUNT(*) as total_orders,
            SUM(total) as total_sales,
            AVG(total) as avg_order_value,
            SUM(CASE WHEN order_status_id IN (5, 3) THEN total ELSE 0 END) as completed_sales
            FROM " . DB_PREFIX . "order 
            WHERE DATE(date_added) BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'" . $branch_filter);
        
        // Products summary
        $products_query = $this->db->query("SELECT 
            COUNT(*) as total_products,
            SUM(quantity) as total_stock,
            SUM(CASE WHEN quantity <= minimum THEN 1 ELSE 0 END) as low_stock_items,
            AVG(price) as avg_product_price
            FROM " . DB_PREFIX . "product 
            WHERE status = 1");
        
        // Customers summary
        $customers_query = $this->db->query("SELECT 
            COUNT(*) as total_customers,
            COUNT(CASE WHEN DATE(date_added) BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "' THEN 1 END) as new_customers
            FROM " . DB_PREFIX . "customer 
            WHERE status = 1");
        
        // Revenue by period comparison
        $prev_date_from = date('Y-m-d', strtotime($date_from . ' -1 month'));
        $prev_date_to = date('Y-m-d', strtotime($date_to . ' -1 month'));
        
        $prev_sales_query = $this->db->query("SELECT 
            SUM(total) as prev_total_sales
            FROM " . DB_PREFIX . "order 
            WHERE DATE(date_added) BETWEEN '" . $this->db->escape($prev_date_from) . "' AND '" . $this->db->escape($prev_date_to) . "'" . $branch_filter);
        
        $sales_data = $sales_query->row;
        $products_data = $products_query->row;
        $customers_data = $customers_query->row;
        $prev_sales_data = $prev_sales_query->row;
        
        // Calculate growth percentage
        $sales_growth = 0;
        if ($prev_sales_data['prev_total_sales'] > 0) {
            $sales_growth = (($sales_data['total_sales'] - $prev_sales_data['prev_total_sales']) / $prev_sales_data['prev_total_sales']) * 100;
        }
        
        return array(
            'total_sales' => (float)$sales_data['total_sales'],
            'total_orders' => (int)$sales_data['total_orders'],
            'avg_order_value' => (float)$sales_data['avg_order_value'],
            'completed_sales' => (float)$sales_data['completed_sales'],
            'total_products' => (int)$products_data['total_products'],
            'total_stock' => (int)$products_data['total_stock'],
            'low_stock_items' => (int)$products_data['low_stock_items'],
            'avg_product_price' => (float)$products_data['avg_product_price'],
            'total_customers' => (int)$customers_data['total_customers'],
            'new_customers' => (int)$customers_data['new_customers'],
            'sales_growth' => round($sales_growth, 2)
        );
    }
    
    /**
     * Get quick reports data
     * 
     * @param array $data
     * @return array
     */
    public function getQuickReports($data = array()) {
        $reports = array();
        
        // Top selling products
        $reports['top_products'] = $this->getTopSellingProducts($data);
        
        // Recent orders
        $reports['recent_orders'] = $this->getRecentOrders($data);
        
        // Low stock alerts
        $reports['low_stock'] = $this->getLowStockProducts($data);
        
        // Top customers
        $reports['top_customers'] = $this->getTopCustomers($data);
        
        // Sales by category
        $reports['sales_by_category'] = $this->getSalesByCategory($data);
        
        return $reports;
    }
    
    /**
     * Get recent reports
     * 
     * @return array
     */
    public function getRecentReports() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dashboard_reports 
            WHERE created_by = '" . (int)$this->user->getId() . "' 
            ORDER BY created_at DESC 
            LIMIT 10");
        
        return $query->rows;
    }
    
    /**
     * Get favorite reports for user
     * 
     * @param int $user_id
     * @return array
     */
    public function getFavoriteReports($user_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dashboard_report_favorites 
            WHERE user_id = '" . (int)$user_id . "' 
            ORDER BY created_at DESC");
        
        return $query->rows;
    }
    
    /**
     * Get sales chart data
     * 
     * @param array $data
     * @return array
     */
    public function getSalesChartData($data = array()) {
        $date_from = isset($data['filter_date_from']) ? $data['filter_date_from'] : date('Y-m-01');
        $date_to = isset($data['filter_date_to']) ? $data['filter_date_to'] : date('Y-m-t');
        $branch_filter = isset($data['filter_branch']) && $data['filter_branch'] ? " AND store_id = '" . (int)$data['filter_branch'] . "'" : "";
        
        $query = $this->db->query("SELECT 
            DATE(date_added) as date,
            SUM(total) as daily_sales,
            COUNT(*) as daily_orders
            FROM " . DB_PREFIX . "order 
            WHERE DATE(date_added) BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'" . $branch_filter . "
            GROUP BY DATE(date_added)
            ORDER BY DATE(date_added)");
        
        $chart_data = array(
            'labels' => array(),
            'sales' => array(),
            'orders' => array()
        );
        
        foreach ($query->rows as $row) {
            $chart_data['labels'][] = date('M d', strtotime($row['date']));
            $chart_data['sales'][] = (float)$row['daily_sales'];
            $chart_data['orders'][] = (int)$row['daily_orders'];
        }
        
        return $chart_data;
    }
    
    /**
     * Get profit chart data
     * 
     * @param array $data
     * @return array
     */
    public function getProfitChartData($data = array()) {
        $date_from = isset($data['filter_date_from']) ? $data['filter_date_from'] : date('Y-m-01');
        $date_to = isset($data['filter_date_to']) ? $data['filter_date_to'] : date('Y-m-t');
        
        // This is a simplified profit calculation
        // In a real system, you'd calculate based on cost of goods sold
        $query = $this->db->query("SELECT 
            DATE(o.date_added) as date,
            SUM(o.total) as revenue,
            SUM(o.total * 0.3) as estimated_profit
            FROM " . DB_PREFIX . "order o
            WHERE DATE(o.date_added) BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'
            AND o.order_status_id IN (5, 3)
            GROUP BY DATE(o.date_added)
            ORDER BY DATE(o.date_added)");
        
        $chart_data = array(
            'labels' => array(),
            'revenue' => array(),
            'profit' => array()
        );
        
        foreach ($query->rows as $row) {
            $chart_data['labels'][] = date('M d', strtotime($row['date']));
            $chart_data['revenue'][] = (float)$row['revenue'];
            $chart_data['profit'][] = (float)$row['estimated_profit'];
        }
        
        return $chart_data;
    }
    
    /**
     * Get inventory chart data
     * 
     * @param array $data
     * @return array
     */
    public function getInventoryChartData($data = array()) {
        $query = $this->db->query("SELECT 
            c.name as category,
            COUNT(p.product_id) as product_count,
            SUM(p.quantity) as total_stock,
            SUM(p.quantity * p.price) as stock_value
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
            LEFT JOIN " . DB_PREFIX . "category_description c ON (p2c.category_id = c.category_id)
            WHERE p.status = 1 AND c.language_id = '" . (int)$this->config->get('config_language_id') . "'
            GROUP BY c.category_id, c.name
            ORDER BY stock_value DESC
            LIMIT 10");
        
        $chart_data = array(
            'labels' => array(),
            'stock_counts' => array(),
            'stock_values' => array()
        );
        
        foreach ($query->rows as $row) {
            $chart_data['labels'][] = $row['category'] ?: 'غير مصنف';
            $chart_data['stock_counts'][] = (int)$row['total_stock'];
            $chart_data['stock_values'][] = (float)$row['stock_value'];
        }
        
        return $chart_data;
    }
    
    /**
     * Generate specific report
     * 
     * @param string $report_type
     * @param array $filter_data
     * @return array|false
     */
    public function generateReport($report_type, $filter_data) {
        switch ($report_type) {
            case 'sales_summary':
                return $this->generateSalesSummaryReport($filter_data);
            case 'inventory_report':
                return $this->generateInventoryReport($filter_data);
            case 'customer_report':
                return $this->generateCustomerReport($filter_data);
            case 'profit_loss':
                return $this->generateProfitLossReport($filter_data);
            case 'top_products':
                return $this->generateTopProductsReport($filter_data);
            default:
                return false;
        }
    }
    
    /**
     * Save generated report
     * 
     * @param string $report_type
     * @param array $filter_data
     * @param array $report_data
     * @param int $user_id
     * @return int
     */
    public function saveReport($report_type, $filter_data, $report_data, $user_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "dashboard_reports SET 
            report_type = '" . $this->db->escape($report_type) . "',
            report_name = '" . $this->db->escape($this->getReportName($report_type)) . "',
            filter_data = '" . $this->db->escape(json_encode($filter_data)) . "',
            report_data = '" . $this->db->escape(json_encode($report_data)) . "',
            created_by = '" . (int)$user_id . "',
            created_at = NOW()");
        
        return $this->db->getLastId();
    }
    
    /**
     * Get report by ID
     * 
     * @param int $report_id
     * @return array
     */
    public function getReport($report_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dashboard_reports 
            WHERE report_id = '" . (int)$report_id . "'");
        
        if ($query->num_rows) {
            $report = $query->row;
            $report['filter_data'] = json_decode($report['filter_data'], true);
            $report['report_data'] = json_decode($report['report_data'], true);
            return $report;
        }
        
        return array();
    }
    
    /**
     * Schedule report
     * 
     * @param array $data
     * @param int $user_id
     * @return int
     */
    public function scheduleReport($data, $user_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "dashboard_report_schedules SET 
            report_type = '" . $this->db->escape($data['report_type']) . "',
            schedule_type = '" . $this->db->escape($data['schedule_type']) . "',
            schedule_time = '" . $this->db->escape($data['schedule_time']) . "',
            recipients = '" . $this->db->escape(json_encode($data['recipients'])) . "',
            format = '" . $this->db->escape($data['format']) . "',
            is_active = '" . (int)$data['is_active'] . "',
            created_by = '" . (int)$user_id . "',
            created_at = NOW()");
        
        return $this->db->getLastId();
    }
    
    /**
     * Add report to favorites
     * 
     * @param string $report_type
     * @param int $user_id
     * @return void
     */
    public function addToFavorites($report_type, $user_id) {
        // Check if already exists
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dashboard_report_favorites 
            WHERE report_type = '" . $this->db->escape($report_type) . "' AND user_id = '" . (int)$user_id . "'");
        
        if (!$query->num_rows) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "dashboard_report_favorites SET 
                report_type = '" . $this->db->escape($report_type) . "',
                report_name = '" . $this->db->escape($this->getReportName($report_type)) . "',
                user_id = '" . (int)$user_id . "',
                created_at = NOW()");
        }
    }
    
    /**
     * Remove report from favorites
     * 
     * @param string $report_type
     * @param int $user_id
     * @return void
     */
    public function removeFromFavorites($report_type, $user_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "dashboard_report_favorites 
            WHERE report_type = '" . $this->db->escape($report_type) . "' AND user_id = '" . (int)$user_id . "'");
    }
    
    /**
     * Get top selling products
     * 
     * @param array $data
     * @return array
     */
    private function getTopSellingProducts($data) {
        $date_from = isset($data['filter_date_from']) ? $data['filter_date_from'] : date('Y-m-01');
        $date_to = isset($data['filter_date_to']) ? $data['filter_date_to'] : date('Y-m-t');
        
        $query = $this->db->query("SELECT 
            p.product_id,
            pd.name,
            SUM(op.quantity) as total_sold,
            SUM(op.total) as total_revenue
            FROM " . DB_PREFIX . "order_product op
            LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
            WHERE DATE(o.date_added) BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND o.order_status_id IN (5, 3)
            GROUP BY p.product_id, pd.name
            ORDER BY total_sold DESC
            LIMIT 10");
        
        return $query->rows;
    }
    
    /**
     * Get recent orders
     * 
     * @param array $data
     * @return array
     */
    private function getRecentOrders($data) {
        $query = $this->db->query("SELECT 
            o.order_id,
            o.total,
            o.date_added,
            CONCAT(o.firstname, ' ', o.lastname) as customer_name,
            os.name as status_name
            FROM " . DB_PREFIX . "order o
            LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id)
            WHERE os.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY o.date_added DESC
            LIMIT 10");
        
        return $query->rows;
    }
    
    /**
     * Get low stock products
     * 
     * @param array $data
     * @return array
     */
    private function getLowStockProducts($data) {
        $query = $this->db->query("SELECT 
            p.product_id,
            pd.name,
            p.quantity,
            p.minimum,
            p.price
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            WHERE p.quantity <= p.minimum 
            AND p.status = 1
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY (p.quantity - p.minimum) ASC
            LIMIT 20");
        
        return $query->rows;
    }
    
    /**
     * Get top customers
     * 
     * @param array $data
     * @return array
     */
    private function getTopCustomers($data) {
        $date_from = isset($data['filter_date_from']) ? $data['filter_date_from'] : date('Y-m-01');
        $date_to = isset($data['filter_date_to']) ? $data['filter_date_to'] : date('Y-m-t');
        
        $query = $this->db->query("SELECT 
            c.customer_id,
            CONCAT(c.firstname, ' ', c.lastname) as customer_name,
            c.email,
            COUNT(o.order_id) as total_orders,
            SUM(o.total) as total_spent
            FROM " . DB_PREFIX . "customer c
            LEFT JOIN " . DB_PREFIX . "order o ON (c.customer_id = o.customer_id)
            WHERE DATE(o.date_added) BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'
            AND o.order_status_id IN (5, 3)
            GROUP BY c.customer_id
            ORDER BY total_spent DESC
            LIMIT 10");
        
        return $query->rows;
    }
    
    /**
     * Get sales by category
     * 
     * @param array $data
     * @return array
     */
    private function getSalesByCategory($data) {
        $date_from = isset($data['filter_date_from']) ? $data['filter_date_from'] : date('Y-m-01');
        $date_to = isset($data['filter_date_to']) ? $data['filter_date_to'] : date('Y-m-t');
        
        $query = $this->db->query("SELECT 
            cd.name as category_name,
            SUM(op.quantity) as total_quantity,
            SUM(op.total) as total_sales
            FROM " . DB_PREFIX . "order_product op
            LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id)
            LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
            WHERE DATE(o.date_added) BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'
            AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND o.order_status_id IN (5, 3)
            GROUP BY cd.category_id, cd.name
            ORDER BY total_sales DESC
            LIMIT 10");
        
        return $query->rows;
    }
    
    /**
     * Generate sales summary report
     * 
     * @param array $filter_data
     * @return array
     */
    private function generateSalesSummaryReport($filter_data) {
        // Implementation for sales summary report
        return $this->getReportsSummary($filter_data);
    }
    
    /**
     * Generate inventory report
     * 
     * @param array $filter_data
     * @return array
     */
    private function generateInventoryReport($filter_data) {
        // Implementation for inventory report
        return array(
            'low_stock' => $this->getLowStockProducts($filter_data),
            'inventory_chart' => $this->getInventoryChartData($filter_data)
        );
    }
    
    /**
     * Generate customer report
     * 
     * @param array $filter_data
     * @return array
     */
    private function generateCustomerReport($filter_data) {
        // Implementation for customer report
        return array(
            'top_customers' => $this->getTopCustomers($filter_data)
        );
    }
    
    /**
     * Generate profit & loss report
     * 
     * @param array $filter_data
     * @return array
     */
    private function generateProfitLossReport($filter_data) {
        // Implementation for P&L report
        return $this->getProfitChartData($filter_data);
    }
    
    /**
     * Generate top products report
     * 
     * @param array $filter_data
     * @return array
     */
    private function generateTopProductsReport($filter_data) {
        // Implementation for top products report
        return $this->getTopSellingProducts($filter_data);
    }
    
    /**
     * Get report name by type
     * 
     * @param string $report_type
     * @return string
     */
    private function getReportName($report_type) {
        $names = array(
            'sales_summary' => 'تقرير ملخص المبيعات',
            'inventory_report' => 'تقرير المخزون',
            'customer_report' => 'تقرير العملاء',
            'profit_loss' => 'تقرير الأرباح والخسائر',
            'top_products' => 'تقرير أفضل المنتجات'
        );
        
        return isset($names[$report_type]) ? $names[$report_type] : $report_type;
    }
}
