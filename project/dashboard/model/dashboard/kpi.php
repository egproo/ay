<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 *
 * Model: Dashboard KPIs (Key Performance Indicators)
 */
class ModelDashboardKpi extends Model {

    /**
     * Get KPI values for dashboard
     *
     * @param array $kpi_codes
     * @return array
     */
    public function getKpiValues($kpi_codes = []) {
        $result = [];

        // If no specific KPIs requested, get all active ones
        if (empty($kpi_codes)) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dashboard_kpi
                WHERE date_range = 'today' ORDER BY category");

            return $query->rows;
        }

        // Process each requested KPI
        foreach ($kpi_codes as $kpi_code) {
            switch ($kpi_code) {
                case 'sales_today':
                    $result[] = $this->getSalesToday();
                    break;

                case 'orders_today':
                    $result[] = $this->getOrdersToday();
                    break;

                case 'customers_online':
                    $result[] = $this->getCustomersOnline();
                    break;

                case 'low_stock_items':
                    $result[] = $this->getLowStockItems();
                    break;

                case 'total_revenue_month':
                    $result[] = $this->getTotalRevenueMonth();
                    break;

                case 'pending_orders':
                    $result[] = $this->getPendingOrders();
                    break;

                case 'avg_order_value':
                    $result[] = $this->getAvgOrderValue();
                    break;

                case 'inventory_value':
                    $result[] = $this->getInventoryValue();
                    break;

                case 'overdue_payments':
                    $result[] = $this->getOverduePayments();
                    break;

                // Add more KPIs as needed
            }
        }

        return $result;
    }

    /**
     * Calculate sales today KPI
     *
     * @return array
     */
    private function getSalesToday() {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Get today's sales
        $query = $this->db->query("SELECT SUM(total) as total_sales
            FROM `" . DB_PREFIX . "order`
            WHERE DATE(date_added) = '" . $this->db->escape($today) . "'
            AND order_status_id > 0");

        $sales_today = $query->row['total_sales'] ? $query->row['total_sales'] : 0;

        // Get yesterday's sales for comparison
        $query = $this->db->query("SELECT SUM(total) as total_sales
            FROM `" . DB_PREFIX . "order`
            WHERE DATE(date_added) = '" . $this->db->escape($yesterday) . "'
            AND order_status_id > 0");

        $sales_yesterday = $query->row['total_sales'] ? $query->row['total_sales'] : 0;

        // Calculate trend
        $trend = 0;
        if ($sales_yesterday > 0) {
            $trend = (($sales_today - $sales_yesterday) / $sales_yesterday) * 100;
        }

        return [
            'name' => 'sales_today',
            'value' => $sales_today,
            'previous_value' => $sales_yesterday,
            'trend' => round($trend, 2),
            'date_range' => 'today'
        ];
    }

    /**
     * Calculate orders today KPI
     *
     * @return array
     */
    private function getOrdersToday() {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Get today's orders
        $query = $this->db->query("SELECT COUNT(*) as total_orders
            FROM `" . DB_PREFIX . "order`
            WHERE DATE(date_added) = '" . $this->db->escape($today) . "'
            AND order_status_id > 0");

        $orders_today = $query->row['total_orders'] ? $query->row['total_orders'] : 0;

        // Get yesterday's orders for comparison
        $query = $this->db->query("SELECT COUNT(*) as total_orders
            FROM `" . DB_PREFIX . "order`
            WHERE DATE(date_added) = '" . $this->db->escape($yesterday) . "'
            AND order_status_id > 0");

        $orders_yesterday = $query->row['total_orders'] ? $query->row['total_orders'] : 0;

        // Calculate trend
        $trend = 0;
        if ($orders_yesterday > 0) {
            $trend = (($orders_today - $orders_yesterday) / $orders_yesterday) * 100;
        }

        return [
            'name' => 'orders_today',
            'value' => $orders_today,
            'previous_value' => $orders_yesterday,
            'trend' => round($trend, 2),
            'date_range' => 'today'
        ];
    }

    /**
     * Calculate customers online KPI
     *
     * @return array
     */
    private function getCustomersOnline() {
        // Time frame for "online" customers (last 15 minutes)
        $time = date('Y-m-d H:i:s', strtotime('-15 minutes'));

        // Get customers online now
        $query = $this->db->query("SELECT COUNT(DISTINCT ip) as total
            FROM " . DB_PREFIX . "customer_online
            WHERE date_added > '" . $this->db->escape($time) . "'");

        $customers_online = $query->row['total'] ? $query->row['total'] : 0;

        // Get historical average for this time of day over last 7 days
        $query = $this->db->query("SELECT AVG(online_count) as average
            FROM (
                SELECT COUNT(DISTINCT ip) as online_count,
                DATE_FORMAT(date_added, '%H:%i') as time_slot
                FROM " . DB_PREFIX . "customer_online
                WHERE date_added > DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND DATE_FORMAT(date_added, '%H:%i') BETWEEN
                    DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 30 MINUTE), '%H:%i')
                    AND DATE_FORMAT(NOW(), '%H:%i')
                GROUP BY DATE(date_added), time_slot
            ) as counts");

        $average_online = $query->row['average'] ? $query->row['average'] : 0;

        // Calculate trend
        $trend = 0;
        if ($average_online > 0) {
            $trend = (($customers_online - $average_online) / $average_online) * 100;
        }

        return [
            'name' => 'customers_online',
            'value' => $customers_online,
            'previous_value' => round($average_online, 0),
            'trend' => round($trend, 2),
            'date_range' => 'current'
        ];
    }

    /**
     * Calculate low stock items KPI
     *
     * @return array
     */
    private function getLowStockItems() {
        // Get current low stock items count using proper inventory table
        $query = $this->db->query("SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pi.quantity <= p.minimum AND pi.quantity >= 0 AND p.minimum > 0");

        $low_stock = $query->row['total'] ? $query->row['total'] : 0;

        // Get count from 7 days ago for comparison
        $query = $this->db->query("SELECT value as previous_count
            FROM " . DB_PREFIX . "dashboard_kpi
            WHERE name = 'low_stock_items'
            AND date_range = 'week'
            ORDER BY last_calculated DESC
            LIMIT 1");

        $previous_count = $query->num_rows ? $query->row['previous_count'] : $low_stock;

        // Calculate trend (negative trend is good for low stock items)
        $trend = 0;
        if ($previous_count > 0) {
            $trend = (($low_stock - $previous_count) / $previous_count) * 100;
        }

        return [
            'name' => 'low_stock_items',
            'value' => $low_stock,
            'previous_value' => $previous_count,
            'trend' => round($trend, 2),
            'date_range' => 'today'
        ];
    }

    /**
     * Calculate total revenue for current month KPI
     *
     * @return array
     */
    private function getTotalRevenueMonth() {
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');

        // Get previous month date range
        $previous_month_start = date('Y-m-01', strtotime('-1 month'));
        $previous_month_end = date('Y-m-t', strtotime('-1 month'));

        // Get current month's revenue
        $query = $this->db->query("SELECT SUM(total) as total_revenue
            FROM `" . DB_PREFIX . "order`
            WHERE date_added BETWEEN '" . $this->db->escape($current_month_start) . "' AND '" . $this->db->escape($current_month_end) . " 23:59:59'
            AND order_status_id > 0");

        $revenue_current = $query->row['total_revenue'] ? $query->row['total_revenue'] : 0;

        // Get previous month's revenue for comparison
        $query = $this->db->query("SELECT SUM(total) as total_revenue
            FROM `" . DB_PREFIX . "order`
            WHERE date_added BETWEEN '" . $this->db->escape($previous_month_start) . "' AND '" . $this->db->escape($previous_month_end) . " 23:59:59'
            AND order_status_id > 0");

        $revenue_previous = $query->row['total_revenue'] ? $query->row['total_revenue'] : 0;

        // Calculate trend
        $trend = 0;
        if ($revenue_previous > 0) {
            $trend = (($revenue_current - $revenue_previous) / $revenue_previous) * 100;
        }

        // Calculate projected month-end value based on current progress
        $days_in_month = date('t');
        $days_passed = date('j');
        $projected_value = ($revenue_current / $days_passed) * $days_in_month;

        return [
            'name' => 'total_revenue_month',
            'value' => $revenue_current,
            'previous_value' => $revenue_previous,
            'trend' => round($trend, 2),
            'date_range' => 'month',
            'projected_value' => round($projected_value, 2)
        ];
    }

    /**
     * Calculate pending orders KPI
     *
     * @return array
     */
    private function getPendingOrders() {
        // Define the status IDs that represent "pending" orders
        // Adjust these based on your specific order status configuration
        $pending_status_ids = [1, 2]; // Example: 1=Pending, 2=Processing

        // Get current pending orders count
        $query = $this->db->query("SELECT COUNT(*) as total
            FROM `" . DB_PREFIX . "order`
            WHERE order_status_id IN (" . implode(',', $pending_status_ids) . ")");

        $pending_orders = $query->row['total'] ? $query->row['total'] : 0;

        // Get average processing time for recent orders
        $query = $this->db->query("SELECT AVG(TIMESTAMPDIFF(HOUR, date_added, date_modified)) as avg_processing_time
            FROM `" . DB_PREFIX . "order`
            WHERE order_status_id > '" . max($pending_status_ids) . "'
            AND date_modified > DATE_SUB(NOW(), INTERVAL 30 DAY)");

        $avg_processing_time = $query->row['avg_processing_time'] ? round($query->row['avg_processing_time'], 1) : 0;

        // Get yesterday's pending count for comparison
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $query = $this->db->query("SELECT COUNT(*) as total
            FROM `" . DB_PREFIX . "order`
            WHERE order_status_id IN (" . implode(',', $pending_status_ids) . ")
            AND DATE(date_added) = '" . $this->db->escape($yesterday) . "'");

        $yesterday_pending = $query->row['total'] ? $query->row['total'] : 0;

        // Calculate trend (negative trend is good for pending orders)
        $trend = 0;
        if ($yesterday_pending > 0) {
            $trend = (($pending_orders - $yesterday_pending) / $yesterday_pending) * 100;
        }

        return [
            'name' => 'pending_orders',
            'value' => $pending_orders,
            'previous_value' => $yesterday_pending,
            'trend' => round($trend, 2),
            'date_range' => 'today',
            'avg_processing_time' => $avg_processing_time
        ];
    }

    /**
     * Calculate average order value KPI
     *
     * @return array
     */
    private function getAvgOrderValue() {
        // Current period (last 30 days)
        $current_period_start = date('Y-m-d', strtotime('-30 days'));
        $current_period_end = date('Y-m-d');

        // Previous period (30 days before current period)
        $previous_period_start = date('Y-m-d', strtotime('-60 days'));
        $previous_period_end = date('Y-m-d', strtotime('-31 days'));

        // Get current period's average order value
        $query = $this->db->query("SELECT AVG(total) as avg_order_value
            FROM `" . DB_PREFIX . "order`
            WHERE date_added BETWEEN '" . $this->db->escape($current_period_start) . "' AND '" . $this->db->escape($current_period_end) . " 23:59:59'
            AND order_status_id > 0");

        $current_avg = $query->row['avg_order_value'] ? $query->row['avg_order_value'] : 0;

        // Get previous period's average order value
        $query = $this->db->query("SELECT AVG(total) as avg_order_value
            FROM `" . DB_PREFIX . "order`
            WHERE date_added BETWEEN '" . $this->db->escape($previous_period_start) . "' AND '" . $this->db->escape($previous_period_end) . " 23:59:59'
            AND order_status_id > 0");

        $previous_avg = $query->row['avg_order_value'] ? $query->row['avg_order_value'] : 0;

        // Calculate trend
        $trend = 0;
        if ($previous_avg > 0) {
            $trend = (($current_avg - $previous_avg) / $previous_avg) * 100;
        }

        return [
            'name' => 'avg_order_value',
            'value' => round($current_avg, 2),
            'previous_value' => round($previous_avg, 2),
            'trend' => round($trend, 2),
            'date_range' => 'month'
        ];
    }

    /**
     * Calculate inventory value KPI
     *
     * @return array
     */
    private function getInventoryValue() {
        // Get current inventory value using proper inventory table
        $query = $this->db->query("SELECT SUM(pi.quantity * p.price) as total_value
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pi.quantity > 0");

        $inventory_value = $query->row['total_value'] ? $query->row['total_value'] : 0;

        // Get previous inventory value (from 30 days ago)
        $query = $this->db->query("SELECT value as previous_value
            FROM " . DB_PREFIX . "dashboard_kpi
            WHERE name = 'inventory_value'
            AND date_range = 'month'
            ORDER BY last_calculated DESC
            LIMIT 1");

        $previous_value = $query->num_rows ? $query->row['previous_value'] : $inventory_value;

        // Calculate trend
        $trend = 0;
        if ($previous_value > 0) {
            $trend = (($inventory_value - $previous_value) / $previous_value) * 100;
        }

        return [
            'name' => 'inventory_value',
            'value' => round($inventory_value, 2),
            'previous_value' => round($previous_value, 2),
            'trend' => round($trend, 2),
            'date_range' => 'current'
        ];
    }

    /**
     * Calculate overdue payments KPI
     *
     * @return array
     */
    private function getOverduePayments() {
        // Get current overdue payments count
        // This query may need to be adjusted based on your specific database structure
        $query = $this->db->query("SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "customer_transaction
            WHERE amount < 0 AND due_date < CURDATE() AND status = 'unpaid'");

        $overdue_count = $query->row['total'] ? $query->row['total'] : 0;

        // Get overdue payments amount
        $query = $this->db->query("SELECT SUM(ABS(amount)) as total_amount
            FROM " . DB_PREFIX . "customer_transaction
            WHERE amount < 0 AND due_date < CURDATE() AND status = 'unpaid'");

        $overdue_amount = $query->row['total_amount'] ? $query->row['total_amount'] : 0;

        // Get count from previous month for comparison
        $query = $this->db->query("SELECT value as previous_count
            FROM " . DB_PREFIX . "dashboard_kpi
            WHERE name = 'overdue_payments'
            AND date_range = 'month'
            ORDER BY last_calculated DESC
            LIMIT 1");

        $previous_count = $query->num_rows ? $query->row['previous_count'] : $overdue_count;

        // Calculate trend (negative trend is good for overdue payments)
        $trend = 0;
        if ($previous_count > 0) {
            $trend = (($overdue_count - $previous_count) / $previous_count) * 100;
        }

        return [
            'name' => 'overdue_payments',
            'value' => $overdue_count,
            'previous_value' => $previous_count,
            'trend' => round($trend, 2),
            'date_range' => 'current',
            'total_amount' => round($overdue_amount, 2)
        ];
    }

    /**
     * Save KPI value to database
     *
     * @param array $kpi_data
     * @return bool
     */
    public function saveKpiValue($kpi_data) {
        // Check if this KPI already exists for current date range
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dashboard_kpi
            WHERE name = '" . $this->db->escape($kpi_data['name']) . "'
            AND date_range = '" . $this->db->escape($kpi_data['date_range']) . "'");

        if ($query->num_rows) {
            // Update existing KPI
            $this->db->query("UPDATE " . DB_PREFIX . "dashboard_kpi SET
                value = '" . (float)$kpi_data['value'] . "',
                trend = '" . (float)$kpi_data['trend'] . "',
                previous_value = '" . (float)$kpi_data['previous_value'] . "',
                last_calculated = NOW()
                WHERE kpi_id = '" . (int)$query->row['kpi_id'] . "'");

            return true;
        } else {
            // Insert new KPI
            $this->db->query("INSERT INTO " . DB_PREFIX . "dashboard_kpi SET
                name = '" . $this->db->escape($kpi_data['name']) . "',
                category = '" . $this->db->escape(isset($kpi_data['category']) ? $kpi_data['category'] : 'general') . "',
                value = '" . (float)$kpi_data['value'] . "',
                trend = '" . (float)$kpi_data['trend'] . "',
                previous_value = '" . (float)$kpi_data['previous_value'] . "',
                date_range = '" . $this->db->escape($kpi_data['date_range']) . "',
                last_calculated = NOW()");

            return true;
        }

        return false;
    }

    /**
     * Process all KPIs and update database
     *
     * @return bool
     */
    public function processAllKpis() {
        $kpi_codes = [
            'sales_today',
            'orders_today',
            'customers_online',
            'low_stock_items',
            'total_revenue_month',
            'pending_orders',
            'avg_order_value',
            'inventory_value',
            'overdue_payments'
        ];

        $kpis = $this->getKpiValues($kpi_codes);

        foreach ($kpis as $kpi) {
            $this->saveKpiValue($kpi);
        }

        return true;
    }
}