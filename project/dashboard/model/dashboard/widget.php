<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 * 
 * Model: Dashboard Widgets
 */
class ModelDashboardWidget extends Model {
    
    /**
     * Get all available widgets for the dashboard
     * 
     * @return array
     */
    public function getAvailableWidgets() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dashboard_widget WHERE enabled = 1 ORDER BY name ASC");
        
        return $query->rows;
    }
    
    /**
     * Get widget data by ID
     * 
     * @param string $widget_id
     * @return array
     */
    public function getWidgetById($widget_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dashboard_widget WHERE widget_id = '" . $this->db->escape($widget_id) . "'");
        
        return $query->row;
    }
    
    /**
     * Get widget data (actual content)
     * 
     * @param string $widget_id
     * @return array
     */
    public function getWidgetData($widget_id) {
        $widget = $this->getWidgetById($widget_id);
        
        if (!$widget) {
            return ['error' => 'Widget not found'];
        }
        
        // Based on widget type and data_method, fetch the appropriate data
        switch ($widget_id) {
            case 'sales_overview':
                return $this->getSalesOverviewData();
                
            case 'inventory_status':
                return $this->getInventoryStatusData();
                
            case 'latest_orders':
                return $this->getLatestOrdersData();
                
            case 'top_products':
                return $this->getTopProductsData();
                
            case 'cash_flow':
                return $this->getCashFlowData();
                
            case 'recent_activity':
                return $this->getRecentActivityData();
                
            case 'customer_stats':
                return $this->getCustomerStatsData();
                
            case 'purchase_orders':
                return $this->getPurchaseOrdersData();
                
            case 'supplier_payments':
                return $this->getSupplierPaymentsData();
                
            default:
                return ['error' => 'Widget data method not implemented'];
        }
    }
    
    /**
     * Get user's default dashboard
     * 
     * @param int $user_id
     * @return array
     */
    public function getUserDefaultDashboard($user_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user_dashboard 
            WHERE user_id = '" . (int)$user_id . "' AND is_default = 1");
        
        return $query->row;
    }
    
    /**
     * Create a default dashboard for user
     * 
     * @param int $user_id
     * @return int Dashboard ID
     */
    public function createDefaultDashboard($user_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "user_dashboard SET 
            user_id = '" . (int)$user_id . "', 
            name = 'Default Dashboard', 
            is_default = 1, 
            layout_config = '" . $this->db->escape(json_encode($this->getDefaultLayoutConfig())) . "', 
            settings_config = '" . $this->db->escape(json_encode(['auto_refresh' => true, 'refresh_interval' => 120])) . "', 
            created_at = NOW(), 
            updated_at = NOW()");
        
        return $this->db->getLastId();
    }
    
    /**
     * Update dashboard layout
     * 
     * @param int $dashboard_id
     * @param array $layout_config
     * @return bool
     */
    public function updateDashboardLayout($dashboard_id, $layout_config) {
        $this->db->query("UPDATE " . DB_PREFIX . "user_dashboard SET 
            layout_config = '" . $this->db->escape(json_encode($layout_config)) . "', 
            updated_at = NOW() 
            WHERE dashboard_id = '" . (int)$dashboard_id . "'");
        
        return $this->db->countAffected() > 0;
    }
    
    /**
     * Update dashboard settings
     * 
     * @param int $dashboard_id
     * @param array $settings_config
     * @return bool
     */
    public function updateDashboardSettings($dashboard_id, $settings_config) {
        $this->db->query("UPDATE " . DB_PREFIX . "user_dashboard SET 
            settings_config = '" . $this->db->escape(json_encode($settings_config)) . "', 
            updated_at = NOW() 
            WHERE dashboard_id = '" . (int)$dashboard_id . "'");
        
        return $this->db->countAffected() > 0;
    }
    
    /**
     * Get default layout configuration
     * 
     * @return array
     */
    private function getDefaultLayoutConfig() {
        return [
            'widgets' => [
                [
                    'id' => 'sales_overview',
                    'type' => 'chart',
                    'x' => 0,
                    'y' => 0,
                    'width' => 6,
                    'height' => 4
                ],
                [
                    'id' => 'inventory_status',
                    'type' => 'table',
                    'x' => 6,
                    'y' => 0,
                    'width' => 6,
                    'height' => 4
                ],
                [
                    'id' => 'latest_orders',
                    'type' => 'list',
                    'x' => 0,
                    'y' => 4,
                    'width' => 6,
                    'height' => 4
                ],
                [
                    'id' => 'top_products',
                    'type' => 'table',
                    'x' => 6,
                    'y' => 4,
                    'width' => 6,
                    'height' => 4
                ],
                [
                    'id' => 'cash_flow',
                    'type' => 'chart',
                    'x' => 0,
                    'y' => 8,
                    'width' => 12,
                    'height' => 4
                ]
            ]
        ];
    }
    
    /**
     * Get sales overview data for widget
     * 
     * @return array
     */
    private function getSalesOverviewData() {
        // Get sales data for last 7 days
        $data = [];
        $labels = [];
        $values = [];
        
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-6 days'));
        
        // Generate date range
        $current_date = $start_date;
        while (strtotime($current_date) <= strtotime($end_date)) {
            $labels[] = date('d M', strtotime($current_date));
            $values[] = 0; // Initialize with zero
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
        
        // Query to get actual sales for each day
        $query = $this->db->query("SELECT 
            DATE(date_added) as order_date, 
            SUM(total) as daily_total 
            FROM " . DB_PREFIX . "order 
            WHERE order_status_id > 0 
            AND DATE(date_added) BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "' 
            GROUP BY DATE(date_added) 
            ORDER BY DATE(date_added)");
        
        // Map the results to the day indexes
        foreach ($query->rows as $row) {
            $day_index = (int)((strtotime($row['order_date']) - strtotime($start_date)) / 86400);
            if (isset($values[$day_index])) {
                $values[$day_index] = round($row['daily_total'], 2);
            }
        }
        
        return [
            'title' => 'Sales Overview - Last 7 Days',
            'type' => 'line',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $values,
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'fill' => true
                ]
            ]
        ];
    }
    
    /**
     * Get inventory status data for widget
     * 
     * @return array
     */
    private function getInventoryStatusData() {
        // Query to get low stock items
        $query = $this->db->query("SELECT p.product_id, pd.name, p.model, p.quantity, p.price 
            FROM " . DB_PREFIX . "product p 
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            AND p.quantity <= p.minimum 
            ORDER BY p.quantity ASC 
            LIMIT 10");
        
        $items = $query->rows;
        
        // Get out of stock count
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE quantity <= 0");
        $out_of_stock_count = $query->row['total'];
        
        // Get low stock count (below minimum but > 0)
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE quantity > 0 AND quantity <= minimum");
        $low_stock_count = $query->row['total'];
        
        // Get total inventory value
        $query = $this->db->query("SELECT SUM(quantity * price) as total_value FROM " . DB_PREFIX . "product");
        $total_value = $query->row['total_value'];
        
        return [
            'title' => 'Inventory Status',
            'items' => $items,
            'summary' => [
                'out_of_stock' => $out_of_stock_count,
                'low_stock' => $low_stock_count,
                'total_value' => round($total_value, 2)
            ]
        ];
    }
    
    /**
     * Get latest orders data for widget
     * 
     * @return array
     */
    private function getLatestOrdersData() {
        $query = $this->db->query("SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, 
            os.name AS status, o.date_added, o.total, o.currency_code, o.currency_value 
            FROM `" . DB_PREFIX . "order` o 
            LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) 
            WHERE os.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            ORDER BY o.date_added DESC 
            LIMIT 10");
        
        $orders = [];
        
        foreach ($query->rows as $row) {
            $orders[] = [
                'order_id' => $row['order_id'],
                'customer' => $row['customer'],
                'status' => $row['status'],
                'date_added' => date('d/m/Y H:i', strtotime($row['date_added'])),
                'total' => $this->currency->format($row['total'], $row['currency_code'], $row['currency_value']),
                'view' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $row['order_id'], true)
            ];
        }
        
        return [
            'title' => 'Latest Orders',
            'orders' => $orders
        ];
    }
    
    /**
     * Get top products data for widget
     * 
     * @return array
     */
    private function getTopProductsData() {
        $query = $this->db->query("SELECT op.product_id, pd.name, SUM(op.quantity) AS quantity, 
            SUM(op.price * op.quantity) AS total 
            FROM " . DB_PREFIX . "order_product op 
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (op.product_id = pd.product_id) 
            LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) 
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            AND o.order_status_id > 0 
            AND o.date_added > DATE_SUB(NOW(), INTERVAL 30 DAY) 
            GROUP BY op.product_id 
            ORDER BY total DESC 
            LIMIT 10");
        
        $products = [];
        
        foreach ($query->rows as $row) {
            $products[] = [
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'quantity' => $row['quantity'],
                'total' => round($row['total'], 2)
            ];
        }
        
        return [
            'title' => 'Top Selling Products (Last 30 days)',
            'products' => $products
        ];
    }
    
    /**
     * Get cash flow data for widget
     * 
     * @return array
     */
    private function getCashFlowData() {
        // Get data for last 30 days
        $data = [];
        $labels = [];
        $income = [];
        $expenses = [];
        
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-29 days'));
        
        // Generate weekly date ranges (last 4 weeks)
        for ($i = 0; $i < 4; $i++) {
            $week_start = date('Y-m-d', strtotime('-' . (21 - ($i * 7)) . ' days'));
            $week_end = date('Y-m-d', strtotime('-' . (15 - ($i * 7)) . ' days'));
            
            if (strtotime($week_end) > strtotime('now')) {
                $week_end = date('Y-m-d');
            }
            
            $labels[] = date('d M', strtotime($week_start)) . ' - ' . date('d M', strtotime($week_end));
            $income[] = 0;
            $expenses[] = 0;
        }
        
        // Query for income (orders)
        $query = $this->db->query("SELECT 
            FLOOR((DATEDIFF(date_added, '" . $this->db->escape($start_date) . "')) / 7) as week_index,
            SUM(total) as total_income 
            FROM " . DB_PREFIX . "order 
            WHERE order_status_id > 0 
            AND date_added BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "' 
            GROUP BY week_index");
        
        foreach ($query->rows as $row) {
            if (isset($income[$row['week_index']])) {
                $income[$row['week_index']] = round($row['total_income'], 2);
            }
        }
        
        // Query for expenses (purchases)
        // This is a simplified example, actual implementation may need to adapt to your system
        $query = $this->db->query("SELECT 
            FLOOR((DATEDIFF(date_added, '" . $this->db->escape($start_date) . "')) / 7) as week_index,
            SUM(total) as total_expenses 
            FROM " . DB_PREFIX . "purchase_order 
            WHERE status_id > 0 
            AND date_added BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "' 
            GROUP BY week_index");
        
        foreach ($query->rows as $row) {
            if (isset($expenses[$row['week_index']])) {
                $expenses[$row['week_index']] = round($row['total_expenses'], 2);
            }
        }
        
        return [
            'title' => 'Cash Flow - Last 4 Weeks',
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => $income,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenses,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1
                ]
            ]
        ];
    }
    
    /**
     * Get recent activity data for widget
     * 
     * @return array
     */
    private function getRecentActivityData() {
        $query = $this->db->query("SELECT log_id, user_id, action_type, module, 
            description, created_at 
            FROM " . DB_PREFIX . "activity_log 
            ORDER BY created_at DESC 
            LIMIT 15");
        
        // Load user model for user names
        $this->load->model('user/user');
        
        $activities = [];
        
        foreach ($query->rows as $row) {
            $user_info = $this->model_user_user->getUser($row['user_id']);
            $user_name = $user_info ? $user_info['username'] : 'Unknown User';
            
            $activities[] = [
                'log_id' => $row['log_id'],
                'user' => $user_name,
                'action_type' => $row['action_type'],
                'module' => $row['module'],
                'description' => $row['description'],
                'time' => date('d/m/Y H:i', strtotime($row['created_at'])),
                'time_ago' => $this->timeAgo($row['created_at'])
            ];
        }
        
        return [
            'title' => 'Recent Activity',
            'activities' => $activities
        ];
    }
    
    /**
     * Get customer stats data for widget
     * 
     * @return array
     */
    private function getCustomerStatsData() {
        // Get total customers
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "customer WHERE status = 1");
        $total_customers = $query->row['total'];
        
        // Get new customers (last 30 days)
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "customer 
            WHERE status = 1 AND date_added > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $new_customers = $query->row['total'];
        
        // Get customer growth percentage
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "customer 
            WHERE status = 1 AND date_added > DATE_SUB(NOW(), INTERVAL 60 DAY) 
            AND date_added <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $previous_period = $query->row['total'];
        
        $growth_percentage = 0;
        if ($previous_period > 0) {
            $growth_percentage = round((($new_customers - $previous_period) / $previous_period) * 100, 2);
        }
        
        // Get top customers by revenue
        $query = $this->db->query("SELECT c.customer_id, c.firstname, c.lastname, c.email, 
            SUM(o.total) as total_spent 
            FROM " . DB_PREFIX . "customer c 
            LEFT JOIN `" . DB_PREFIX . "order` o ON (c.customer_id = o.customer_id) 
            WHERE o.order_status_id > 0 
            GROUP BY c.customer_id 
            ORDER BY total_spent DESC 
            LIMIT 5");
        
        $top_customers = [];
        
        foreach ($query->rows as $row) {
            $top_customers[] = [
                'customer_id' => $row['customer_id'],
                'name' => $row['firstname'] . ' ' . $row['lastname'],
                'email' => $row['email'],
                'total_spent' => round($row['total_spent'], 2)
            ];
        }
        
        return [
            'title' => 'Customer Statistics',
            'total_customers' => $total_customers,
            'new_customers' => $new_customers,
            'growth_percentage' => $growth_percentage,
            'top_customers' => $top_customers
        ];
    }
    
    /**
     * Get purchase orders data for widget
     * 
     * @return array
     */
    private function getPurchaseOrdersData() {
        // This is a simplified example, actual implementation may need to adapt to your system
        $query = $this->db->query("SELECT po.purchase_order_id, po.reference_no, s.name as supplier, 
            po.total, po.date_added, po.status 
            FROM " . DB_PREFIX . "purchase_order po 
            LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id) 
            ORDER BY po.date_added DESC 
            LIMIT 10");
        
        $purchase_orders = [];
        
        foreach ($query->rows as $row) {
            $purchase_orders[] = [
                'purchase_order_id' => $row['purchase_order_id'],
                'reference_no' => $row['reference_no'],
                'supplier' => $row['supplier'],
                'total' => round($row['total'], 2),
                'date_added' => date('d/m/Y', strtotime($row['date_added'])),
                'status' => $row['status'],
                'view' => $this->url->link('purchase/order/info', 'user_token=' . $this->session->data['user_token'] . '&purchase_order_id=' . $row['purchase_order_id'], true)
            ];
        }
        
        return [
            'title' => 'Recent Purchase Orders',
            'purchase_orders' => $purchase_orders
        ];
    }
    
    /**
     * Get supplier payments data for widget
     * 
     * @return array
     */
    private function getSupplierPaymentsData() {
        // This is a simplified example, actual implementation may need to adapt to your system
        $query = $this->db->query("SELECT sp.payment_id, s.name as supplier, sp.amount, 
            sp.payment_method, sp.date_added 
            FROM " . DB_PREFIX . "supplier_payment sp 
            LEFT JOIN " . DB_PREFIX . "supplier s ON (sp.supplier_id = s.supplier_id) 
            ORDER BY sp.date_added DESC 
            LIMIT 10");
        
        $payments = [];
        
        foreach ($query->rows as $row) {
            $payments[] = [
                'payment_id' => $row['payment_id'],
                'supplier' => $row['supplier'],
                'amount' => round($row['amount'], 2),
                'payment_method' => $row['payment_method'],
                'date_added' => date('d/m/Y', strtotime($row['date_added']))
            ];
        }
        
        return [
            'title' => 'Recent Supplier Payments',
            'payments' => $payments
        ];
    }
    
    /**
     * Helper function to get time ago string
     * 
     * @param string $datetime
     * @return string
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        $units = [
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];
        
        foreach ($units as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text . ($numberOfUnits > 1 ? 's' : '') . ' ago';
        }
        
        return 'just now';
    }
} 