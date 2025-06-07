<?php
/**
 * Advanced Analytics Functions for AYM ERP Dashboard
 * دوال التحليلات المتقدمة للوحة معلومات نظام أيم ERP
 */

/**
 * Get customer analytics
 */
private function getCustomerAnalytics() {
    try {
        $month_start = date('Y-m-01');
        $last_month_start = date('Y-m-01', strtotime('-1 month'));
        $last_month_end = date('Y-m-t', strtotime('-1 month'));
        
        // New customers this month
        $new_customers_query = $this->db->query("SELECT COUNT(*) as count 
                  FROM " . DB_PREFIX . "customer 
                  WHERE DATE(date_added) >= '{$month_start}' AND status = 1");
        $new_customers = (int)$new_customers_query->row['count'];
        
        // Returning customers (customers who made more than 1 order)
        $returning_query = $this->db->query("SELECT COUNT(DISTINCT customer_id) as count 
                  FROM " . DB_PREFIX . "order 
                  WHERE customer_id IN (
                      SELECT customer_id FROM " . DB_PREFIX . "order 
                      WHERE order_status_id > 0 
                      GROUP BY customer_id 
                      HAVING COUNT(*) > 1
                  ) AND DATE(date_added) >= '{$month_start}' AND order_status_id > 0");
        $returning_customers = (int)$returning_query->row['count'];
        
        // Customer retention rate
        $total_customers_query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "customer WHERE status = 1");
        $total_customers = (int)$total_customers_query->row['count'];
        $retention_rate = $total_customers > 0 ? round(($returning_customers / $total_customers) * 100, 2) : 0;
        
        // Top customers by revenue
        $top_customers_query = $this->db->query("SELECT c.customer_id, c.firstname, c.lastname, c.email, 
                  SUM(o.total) as total_spent, COUNT(o.order_id) as order_count
                  FROM " . DB_PREFIX . "customer c
                  LEFT JOIN " . DB_PREFIX . "order o ON c.customer_id = o.customer_id
                  WHERE o.order_status_id > 0 AND DATE(o.date_added) >= '{$month_start}'
                  GROUP BY c.customer_id
                  ORDER BY total_spent DESC
                  LIMIT 10");
        
        $top_customers = [];
        foreach ($top_customers_query->rows as $customer) {
            $top_customers[] = [
                'name' => $customer['firstname'] . ' ' . $customer['lastname'],
                'email' => $customer['email'],
                'total_spent' => (float)$customer['total_spent'],
                'order_count' => (int)$customer['order_count']
            ];
        }
        
        return [
            'new_customers' => $new_customers,
            'returning_customers' => $returning_customers,
            'retention_rate' => $retention_rate,
            'total_customers' => $total_customers,
            'top_customers' => $top_customers
        ];
        
    } catch (Exception $e) {
        return [
            'new_customers' => 0,
            'returning_customers' => 0,
            'retention_rate' => 0,
            'total_customers' => 0,
            'top_customers' => []
        ];
    }
}

/**
 * Get sales performance analytics
 */
private function getSalesPerformance() {
    try {
        $month_start = date('Y-m-01');
        
        // Sales by category
        $category_sales_query = $this->db->query("SELECT cd.name, SUM(op.total) as revenue, SUM(op.quantity) as quantity
                  FROM " . DB_PREFIX . "order_product op
                  LEFT JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                  LEFT JOIN " . DB_PREFIX . "product p ON op.product_id = p.product_id
                  LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON p.product_id = ptc.product_id
                  LEFT JOIN " . DB_PREFIX . "category_description cd ON ptc.category_id = cd.category_id
                  WHERE DATE(o.date_added) >= '{$month_start}' AND o.order_status_id > 0 
                  AND cd.language_id = 1
                  GROUP BY cd.category_id
                  ORDER BY revenue DESC
                  LIMIT 10");
        
        $category_performance = [];
        foreach ($category_sales_query->rows as $category) {
            $category_performance[] = [
                'name' => $category['name'],
                'revenue' => (float)$category['revenue'],
                'quantity' => (int)$category['quantity']
            ];
        }
        
        // Hourly sales pattern
        $hourly_sales_query = $this->db->query("SELECT HOUR(date_added) as hour, COUNT(*) as orders, SUM(total) as revenue
                  FROM " . DB_PREFIX . "order 
                  WHERE DATE(date_added) >= '{$month_start}' AND order_status_id > 0
                  GROUP BY HOUR(date_added)
                  ORDER BY hour");
        
        $hourly_pattern = [];
        for ($i = 0; $i < 24; $i++) {
            $hourly_pattern[$i] = ['hour' => $i, 'orders' => 0, 'revenue' => 0];
        }
        
        foreach ($hourly_sales_query->rows as $hour_data) {
            $hour = (int)$hour_data['hour'];
            $hourly_pattern[$hour] = [
                'hour' => $hour,
                'orders' => (int)$hour_data['orders'],
                'revenue' => (float)$hour_data['revenue']
            ];
        }
        
        // Weekly sales pattern
        $weekly_sales_query = $this->db->query("SELECT DAYOFWEEK(date_added) as day_of_week, COUNT(*) as orders, SUM(total) as revenue
                  FROM " . DB_PREFIX . "order 
                  WHERE DATE(date_added) >= '{$month_start}' AND order_status_id > 0
                  GROUP BY DAYOFWEEK(date_added)
                  ORDER BY day_of_week");
        
        $weekly_pattern = [];
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        for ($i = 1; $i <= 7; $i++) {
            $weekly_pattern[] = ['day' => $days[$i-1], 'orders' => 0, 'revenue' => 0];
        }
        
        foreach ($weekly_sales_query->rows as $day_data) {
            $day_index = (int)$day_data['day_of_week'] - 1;
            $weekly_pattern[$day_index] = [
                'day' => $days[$day_index],
                'orders' => (int)$day_data['orders'],
                'revenue' => (float)$day_data['revenue']
            ];
        }
        
        return [
            'category_performance' => $category_performance,
            'hourly_pattern' => array_values($hourly_pattern),
            'weekly_pattern' => $weekly_pattern
        ];
        
    } catch (Exception $e) {
        return [
            'category_performance' => [],
            'hourly_pattern' => [],
            'weekly_pattern' => []
        ];
    }
}

/**
 * Get revenue vs cost chart data
 */
private function getRevenueVsCostChart() {
    try {
        $labels = [];
        $revenue_data = [];
        $cost_data = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('M j', strtotime($date));
            
            // Revenue
            $revenue_query = $this->db->query("SELECT COALESCE(SUM(total), 0) as amount 
                      FROM " . DB_PREFIX . "order 
                      WHERE DATE(date_added) = '{$date}' AND order_status_id > 0");
            $revenue = (float)$revenue_query->row['amount'];
            $revenue_data[] = $revenue;
            
            // Estimated cost (70% of revenue)
            $cost_data[] = $revenue * 0.7;
        }
        
        return [
            'labels' => $labels,
            'revenue' => $revenue_data,
            'cost' => $cost_data
        ];
        
    } catch (Exception $e) {
        return ['labels' => [], 'revenue' => [], 'cost' => []];
    }
}

/**
 * Get category performance chart
 */
private function getCategoryPerformanceChart() {
    try {
        $month_start = date('Y-m-01');
        
        $query = $this->db->query("SELECT cd.name, SUM(op.total) as revenue
                  FROM " . DB_PREFIX . "order_product op
                  LEFT JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                  LEFT JOIN " . DB_PREFIX . "product p ON op.product_id = p.product_id
                  LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON p.product_id = ptc.product_id
                  LEFT JOIN " . DB_PREFIX . "category_description cd ON ptc.category_id = cd.category_id
                  WHERE DATE(o.date_added) >= '{$month_start}' AND o.order_status_id > 0 
                  AND cd.language_id = 1
                  GROUP BY cd.category_id
                  ORDER BY revenue DESC
                  LIMIT 8");
        
        $labels = [];
        $data = [];
        
        foreach ($query->rows as $category) {
            $labels[] = $category['name'];
            $data[] = (float)$category['revenue'];
        }
        
        return ['labels' => $labels, 'data' => $data];
        
    } catch (Exception $e) {
        return ['labels' => [], 'data' => []];
    }
}

/**
 * Get customer acquisition chart
 */
private function getCustomerAcquisitionChart() {
    try {
        $labels = [];
        $data = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $labels[] = date('M Y', strtotime($month . '-01'));
            
            $query = $this->db->query("SELECT COUNT(*) as count 
                      FROM " . DB_PREFIX . "customer 
                      WHERE DATE_FORMAT(date_added, '%Y-%m') = '{$month}' AND status = 1");
            
            $data[] = (int)$query->row['count'];
        }
        
        return ['labels' => $labels, 'data' => $data];
        
    } catch (Exception $e) {
        return ['labels' => [], 'data' => []];
    }
}

/**
 * Get geographic sales data
 */
private function getGeographicSales() {
    try {
        $month_start = date('Y-m-01');
        
        $query = $this->db->query("SELECT c.name as country, COUNT(o.order_id) as orders, SUM(o.total) as revenue
                  FROM " . DB_PREFIX . "order o
                  LEFT JOIN " . DB_PREFIX . "country c ON o.shipping_country_id = c.country_id
                  WHERE DATE(o.date_added) >= '{$month_start}' AND o.order_status_id > 0
                  GROUP BY o.shipping_country_id
                  ORDER BY revenue DESC
                  LIMIT 10");
        
        $geographic_data = [];
        foreach ($query->rows as $location) {
            $geographic_data[] = [
                'country' => $location['country'],
                'orders' => (int)$location['orders'],
                'revenue' => (float)$location['revenue']
            ];
        }
        
        return $geographic_data;
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get payment methods analysis
 */
private function getPaymentMethodsAnalysis() {
    try {
        $month_start = date('Y-m-01');
        
        $query = $this->db->query("SELECT payment_method, COUNT(*) as count, SUM(total) as revenue
                  FROM " . DB_PREFIX . "order 
                  WHERE DATE(date_added) >= '{$month_start}' AND order_status_id > 0
                  GROUP BY payment_method
                  ORDER BY count DESC");
        
        $payment_data = [];
        foreach ($query->rows as $payment) {
            $payment_data[] = [
                'method' => $payment['payment_method'],
                'count' => (int)$payment['count'],
                'revenue' => (float)$payment['revenue']
            ];
        }
        
        return $payment_data;
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get abandoned carts data
 */
private function getAbandonedCartsData() {
    try {
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        // Total abandoned carts
        $total_query = $this->db->query("SELECT COUNT(*) as count, SUM(total_value) as value
                  FROM " . DB_PREFIX . "abandoned_cart 
                  WHERE DATE(date_created) >= '{$week_ago}' AND status = 'active'");
        
        $total_abandoned = (int)$total_query->row['count'];
        $total_value = (float)$total_query->row['value'];
        
        // Recovery rate
        $recovered_query = $this->db->query("SELECT COUNT(*) as count
                  FROM " . DB_PREFIX . "abandoned_cart 
                  WHERE DATE(date_created) >= '{$week_ago}' AND recovery_date IS NOT NULL");
        
        $recovered = (int)$recovered_query->row['count'];
        $recovery_rate = $total_abandoned > 0 ? round(($recovered / $total_abandoned) * 100, 2) : 0;
        
        return [
            'total_abandoned' => $total_abandoned,
            'total_value' => $total_value,
            'recovered' => $recovered,
            'recovery_rate' => $recovery_rate,
            'potential_revenue' => $total_value - ($recovered * ($total_value / $total_abandoned))
        ];
        
    } catch (Exception $e) {
        return [
            'total_abandoned' => 0,
            'total_value' => 0,
            'recovered' => 0,
            'recovery_rate' => 0,
            'potential_revenue' => 0
        ];
    }
}
?>
