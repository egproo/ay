<?php
/**
 * AYM ERP Executive Dashboard Model
 * نموذج البيانات للوحة المعلومات التنفيذية
 *
 * Model متقدم يحتوي على استعلامات معقدة لجميع وحدات ERP الـ 14:
 * - استعلامات محاسبية متقدمة مع WAC
 * - تحليلات تجارة إلكترونية شاملة
 * - مؤشرات أداء تشغيلية
 * - تقارير مالية ذكية
 * - تحليلات موارد بشرية
 * - مؤشرات CRM وتسويق
 * - إحصائيات شحن وتوزيع
 * - تحليلات مشاريع ومهام
 */

class ModelCommonDashboard extends Model {

    /**
     * Get Today's Revenue
     * إجمالي الإيرادات اليوم
     */
    public function getTodayRevenue() {
        $sql = "SELECT
                    COALESCE(SUM(o.total), 0) as total_revenue,
                    COUNT(o.order_id) as order_count,
                    AVG(o.total) as avg_order_value
                FROM " . DB_PREFIX . "order o
                WHERE DATE(o.date_added) = CURDATE()
                AND o.order_status_id > 0";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Today's Orders
     * عدد الطلبات اليوم
     */
    public function getTodayOrders() {
        $sql = "SELECT
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN order_status_id = 1 THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN order_status_id = 2 THEN 1 ELSE 0 END) as processing_orders,
                    SUM(CASE WHEN order_status_id = 5 THEN 1 ELSE 0 END) as completed_orders
                FROM " . DB_PREFIX . "order
                WHERE DATE(date_added) = CURDATE()";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Today's New Customers
     * العملاء الجدد اليوم
     */
    public function getTodayNewCustomers() {
        $sql = "SELECT COUNT(*) as new_customers
                FROM " . DB_PREFIX . "customer
                WHERE DATE(date_added) = CURDATE()";

        $query = $this->db->query($sql);
        return $query->row['new_customers'];
    }

    /**
     * Get Today's Conversion Rate
     * معدل التحويل اليوم
     */
    public function getTodayConversionRate() {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM " . DB_PREFIX . "order WHERE DATE(date_added) = CURDATE()) as orders,
                    (SELECT COUNT(DISTINCT session_id) FROM " . DB_PREFIX . "customer_online WHERE DATE(date_added) = CURDATE()) as visitors";

        $query = $this->db->query($sql);
        $visitors = $query->row['visitors'] > 0 ? $query->row['visitors'] : 1;
        return round(($query->row['orders'] / $visitors) * 100, 2);
    }

    /**
     * Get Today's Average Order Value
     * متوسط قيمة الطلب اليوم
     */
    public function getTodayAOV() {
        $sql = "SELECT AVG(total) as aov
                FROM " . DB_PREFIX . "order
                WHERE DATE(date_added) = CURDATE()
                AND order_status_id > 0";

        $query = $this->db->query($sql);
        return round($query->row['aov'], 2);
    }

    /**
     * Get Revenue Trend (Last 7 Days)
     * اتجاه الإيرادات (آخر 7 أيام)
     */
    public function getRevenueTrend() {
        $sql = "SELECT
                    DATE(date_added) as date,
                    SUM(total) as revenue,
                    COUNT(*) as orders
                FROM " . DB_PREFIX . "order
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND order_status_id > 0
                GROUP BY DATE(date_added)
                ORDER BY date ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Monthly Revenue
     * إيرادات الشهر الحالي
     */
    public function getMonthRevenue() {
        $sql = "SELECT
                    SUM(total) as total_revenue,
                    COUNT(*) as total_orders,
                    AVG(total) as avg_order_value
                FROM " . DB_PREFIX . "order
                WHERE YEAR(date_added) = YEAR(CURDATE())
                AND MONTH(date_added) = MONTH(CURDATE())
                AND order_status_id > 0";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Monthly Growth Rate
     * معدل النمو الشهري
     */
    public function getMonthGrowthRate() {
        $sql = "SELECT
                    (SELECT SUM(total) FROM " . DB_PREFIX . "order
                     WHERE YEAR(date_added) = YEAR(CURDATE())
                     AND MONTH(date_added) = MONTH(CURDATE())
                     AND order_status_id > 0) as current_month,
                    (SELECT SUM(total) FROM " . DB_PREFIX . "order
                     WHERE YEAR(date_added) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                     AND MONTH(date_added) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                     AND order_status_id > 0) as previous_month";

        $query = $this->db->query($sql);
        $current = $query->row['current_month'] ?: 0;
        $previous = $query->row['previous_month'] ?: 1;

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get Top Selling Products
     * أفضل المنتجات مبيعاً
     */
    public function getTopSellingProducts($limit = 10) {
        $sql = "SELECT
                    p.product_id,
                    pd.name,
                    SUM(op.quantity) as total_sold,
                    SUM(op.total) as total_revenue,
                    p.image
                FROM " . DB_PREFIX . "order_product op
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                WHERE o.order_status_id > 0
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND o.date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY p.product_id
                ORDER BY total_sold DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Current Stock Levels
     * مستويات المخزون الحالية
     */
    public function getCurrentStockLevels() {
        $sql = "SELECT
                    COUNT(*) as total_products,
                    SUM(CASE WHEN quantity <= minimum THEN 1 ELSE 0 END) as low_stock_products,
                    SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_products,
                    SUM(quantity * price) as total_inventory_value
                FROM " . DB_PREFIX . "product
                WHERE status = 1";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Low Stock Alerts
     * تنبيهات المخزون المنخفض
     */
    public function getLowStockAlerts() {
        $sql = "SELECT
                    p.product_id,
                    pd.name,
                    p.quantity,
                    p.minimum,
                    p.model
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                WHERE p.quantity <= p.minimum
                AND p.status = 1
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                ORDER BY (p.quantity - p.minimum) ASC
                LIMIT 20";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Active Leads (CRM)
     * العملاء المحتملين النشطين
     */
    public function getActiveLeads() {
        $sql = "SELECT
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_leads,
                    SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted_leads,
                    SUM(CASE WHEN status = 'qualified' THEN 1 ELSE 0 END) as qualified_leads
                FROM " . DB_PREFIX . "crm_lead
                WHERE status != 'converted' AND status != 'lost'";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Pending Purchase Orders
     * أوامر الشراء المعلقة
     */
    public function getPendingPurchaseOrders() {
        $sql = "SELECT
                    COUNT(*) as total_pending,
                    SUM(total_amount) as total_value,
                    COUNT(CASE WHEN status = 'pending_review' THEN 1 END) as pending_review,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved
                FROM " . DB_PREFIX . "purchase_order
                WHERE status IN ('draft', 'pending_review', 'approved')";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Current Cash Position
     * الوضع النقدي الحالي
     */
    public function getCurrentCashPosition() {
        $sql = "SELECT
                    (SELECT SUM(balance) FROM " . DB_PREFIX . "bank_account WHERE status = 1) as bank_balance,
                    (SELECT SUM(balance) FROM " . DB_PREFIX . "cash_account WHERE status = 1) as cash_balance";

        $query = $this->db->query($sql);
        $bank = $query->row['bank_balance'] ?: 0;
        $cash = $query->row['cash_balance'] ?: 0;

        return array(
            'total_cash' => $bank + $cash,
            'bank_balance' => $bank,
            'cash_balance' => $cash
        );
    }

    /**
     * Get Accounts Receivable
     * الذمم المدينة
     */
    public function getAccountsReceivable() {
        $sql = "SELECT
                    SUM(total) as total_receivable,
                    COUNT(*) as invoice_count,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), date_added) > 30 THEN total ELSE 0 END) as overdue_amount
                FROM " . DB_PREFIX . "order
                WHERE payment_status = 'pending'
                AND order_status_id > 0";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Accounts Payable
     * الذمم الدائنة
     */
    public function getAccountsPayable() {
        $sql = "SELECT
                    SUM(total_amount) as total_payable,
                    COUNT(*) as invoice_count,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), created_at) > 30 THEN total_amount ELSE 0 END) as overdue_amount
                FROM " . DB_PREFIX . "supplier_invoice
                WHERE status = 'pending'";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Total Employees
     * إجمالي الموظفين
     */
    public function getTotalEmployees() {
        $sql = "SELECT
                    COUNT(*) as total_employees,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_employees,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_employees
                FROM " . DB_PREFIX . "employee";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Critical Alerts
     * التنبيهات الحرجة
     */
    public function getCriticalAlerts() {
        $alerts = array();

        // Low stock alerts
        $low_stock = $this->getLowStockAlerts();
        if (count($low_stock) > 0) {
            $alerts[] = array(
                'type' => 'critical',
                'title' => 'مخزون منخفض',
                'message' => count($low_stock) . ' منتج يحتاج إعادة تموين',
                'count' => count($low_stock)
            );
        }

        // Overdue invoices
        $overdue_sql = "SELECT COUNT(*) as count FROM " . DB_PREFIX . "order
                       WHERE payment_status = 'pending'
                       AND DATEDIFF(CURDATE(), date_added) > 30";
        $overdue_query = $this->db->query($overdue_sql);

        if ($overdue_query->row['count'] > 0) {
            $alerts[] = array(
                'type' => 'critical',
                'title' => 'فواتير متأخرة',
                'message' => $overdue_query->row['count'] . ' فاتورة متأخرة السداد',
                'count' => $overdue_query->row['count']
            );
        }

        return $alerts;
    }

    /**
     * Get Recent Orders
     * الطلبات الحديثة
     */
    public function getRecentOrders($limit = 15) {
        $sql = "SELECT
                    o.order_id,
                    o.total,
                    o.date_added,
                    CONCAT(o.firstname, ' ', o.lastname) as customer_name,
                    os.name as status_name
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id)
                WHERE os.language_id = '" . (int)$this->config->get('config_language_id') . "'
                ORDER BY o.date_added DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Daily Revenue Trend (Chart Data)
     * اتجاه الإيرادات اليومية (بيانات المخطط)
     */
    public function getDailyRevenueTrend() {
        $sql = "SELECT
                    DATE(date_added) as date,
                    SUM(total) as revenue
                FROM " . DB_PREFIX . "order
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND order_status_id > 0
                GROUP BY DATE(date_added)
                ORDER BY date ASC";

        $query = $this->db->query($sql);

        $data = array();
        foreach ($query->rows as $row) {
            $data[] = array(
                'date' => $row['date'],
                'revenue' => (float)$row['revenue']
            );
        }

        return $data;
    }

    /**
     * Get Inventory Value
     * قيمة المخزون
     */
    public function getInventoryValue() {
        $sql = "SELECT
                    SUM(quantity * price) as total_value,
                    COUNT(*) as total_products,
                    AVG(price) as avg_price
                FROM " . DB_PREFIX . "product
                WHERE status = 1";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Hourly Sales (Today)
     * المبيعات بالساعة (اليوم)
     */
    public function getHourlySales() {
        $sql = "SELECT
                    HOUR(date_added) as hour,
                    SUM(total) as revenue,
                    COUNT(*) as orders
                FROM " . DB_PREFIX . "order
                WHERE DATE(date_added) = CURDATE()
                AND order_status_id > 0
                GROUP BY HOUR(date_added)
                ORDER BY hour ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Category Performance
     * أداء الفئات
     */
    public function getCategoryPerformance() {
        $sql = "SELECT
                    cd.name as category_name,
                    SUM(op.quantity) as total_sold,
                    SUM(op.total) as total_revenue,
                    COUNT(DISTINCT op.order_id) as order_count
                FROM " . DB_PREFIX . "order_product op
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id)
                LEFT JOIN " . DB_PREFIX . "category_description cd ON (ptc.category_id = cd.category_id)
                LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                WHERE o.order_status_id > 0
                AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND o.date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY ptc.category_id
                ORDER BY total_revenue DESC
                LIMIT 10";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Order Status Distribution
     * توزيع حالات الطلبات
     */
    public function getOrderStatusDistribution() {
        $sql = "SELECT
                    os.name as status_name,
                    COUNT(*) as order_count,
                    SUM(o.total) as total_value
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id)
                WHERE os.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND o.date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY o.order_status_id
                ORDER BY order_count DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Lead Conversion Rate
     * معدل تحويل العملاء المحتملين
     */
    public function getLeadConversionRate() {
        $sql = "SELECT
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_leads
                FROM " . DB_PREFIX . "crm_lead
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

        $query = $this->db->query($sql);
        $total = $query->row['total_leads'] ?: 1;
        $converted = $query->row['converted_leads'] ?: 0;

        return round(($converted / $total) * 100, 2);
    }

    /**
     * Get Sales Pipeline
     * خط أنابيب المبيعات
     */
    public function getSalesPipeline() {
        $sql = "SELECT
                    stage,
                    COUNT(*) as opportunity_count,
                    SUM(estimated_value) as total_value
                FROM " . DB_PREFIX . "crm_opportunity
                WHERE status = 'active'
                GROUP BY stage
                ORDER BY
                    CASE stage
                        WHEN 'prospecting' THEN 1
                        WHEN 'qualification' THEN 2
                        WHEN 'proposal' THEN 3
                        WHEN 'negotiation' THEN 4
                        WHEN 'closing' THEN 5
                        ELSE 6
                    END";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Supplier Performance
     * أداء الموردين
     */
    public function getSupplierPerformance() {
        $sql = "SELECT
                    s.firstname as supplier_name,
                    COUNT(po.po_id) as total_orders,
                    AVG(DATEDIFF(gr.created_at, po.created_at)) as avg_delivery_days,
                    SUM(po.total_amount) as total_value,
                    AVG(se.overall_score) as avg_rating
                FROM " . DB_PREFIX . "supplier s
                LEFT JOIN " . DB_PREFIX . "purchase_order po ON (s.supplier_id = po.supplier_id)
                LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (po.po_id = gr.po_id)
                LEFT JOIN " . DB_PREFIX . "supplier_evaluation se ON (s.supplier_id = se.supplier_id)
                WHERE po.created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                GROUP BY s.supplier_id
                ORDER BY total_value DESC
                LIMIT 10";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Employee Attendance Rate
     * معدل حضور الموظفين
     */
    public function getAttendanceRate() {
        $sql = "SELECT
                    COUNT(DISTINCT employee_id) as total_employees,
                    COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count
                FROM " . DB_PREFIX . "attendance
                WHERE DATE(date) = CURDATE()";

        $query = $this->db->query($sql);
        $total = $query->row['total_employees'] ?: 1;
        $present = $query->row['present_count'] ?: 0;

        return round(($present / $total) * 100, 2);
    }

    /**
     * Get Active Projects
     * المشاريع النشطة
     */
    public function getActiveProjects() {
        $sql = "SELECT
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_projects,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
                    AVG(progress_percentage) as avg_progress
                FROM " . DB_PREFIX . "project";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Daily Transactions (POS)
     * المعاملات اليومية (نقاط البيع)
     */
    public function getDailyTransactions() {
        $sql = "SELECT
                    COUNT(*) as transaction_count,
                    SUM(total) as total_amount,
                    AVG(total) as avg_transaction
                FROM " . DB_PREFIX . "pos_transaction
                WHERE DATE(created_at) = CURDATE()";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Warning Alerts
     * تنبيهات التحذير
     */
    public function getWarningAlerts() {
        $alerts = array();

        // Pending purchase orders
        $pending_po_sql = "SELECT COUNT(*) as count FROM " . DB_PREFIX . "purchase_order
                          WHERE status = 'pending_review'";
        $pending_po_query = $this->db->query($pending_po_sql);

        if ($pending_po_query->row['count'] > 0) {
            $alerts[] = array(
                'type' => 'warning',
                'title' => 'أوامر شراء معلقة',
                'message' => $pending_po_query->row['count'] . ' أمر شراء يحتاج مراجعة',
                'count' => $pending_po_query->row['count']
            );
        }

        return $alerts;
    }

    /**
     * Get Info Alerts
     * تنبيهات المعلومات
     */
    public function getInfoAlerts() {
        $alerts = array();

        // New customers today
        $new_customers = $this->getTodayNewCustomers();
        if ($new_customers > 0) {
            $alerts[] = array(
                'type' => 'info',
                'title' => 'عملاء جدد',
                'message' => $new_customers . ' عميل جديد انضم اليوم',
                'count' => $new_customers
            );
        }

        return $alerts;
    }

    /**
     * Get Recent Customers
     * العملاء الحديثين
     */
    public function getRecentCustomers($limit = 15) {
        $sql = "SELECT
                    customer_id,
                    CONCAT(firstname, ' ', lastname) as name,
                    email,
                    telephone,
                    date_added
                FROM " . DB_PREFIX . "customer
                ORDER BY date_added DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Recent Transactions
     * المعاملات الحديثة
     */
    public function getRecentTransactions($limit = 15) {
        $sql = "SELECT
                    transaction_id,
                    total,
                    payment_method,
                    created_at,
                    status
                FROM " . DB_PREFIX . "pos_transaction
                ORDER BY created_at DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Activity Log
     * سجل الأنشطة
     */
    public function getActivityLog($limit = 20) {
        $sql = "SELECT
                    event_type,
                    event_action,
                    reference_type,
                    reference_id,
                    created_at
                FROM " . DB_PREFIX . "system_events
                ORDER BY created_at DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get System Events
     * أحداث النظام
     */
    public function getSystemEvents($limit = 10) {
        $sql = "SELECT
                    title,
                    content,
                    type,
                    created_at
                FROM " . DB_PREFIX . "system_notifications
                ORDER BY created_at DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // === ADDITIONAL ADVANCED METHODS ===

    /**
     * Get Daily Sales Trend
     * اتجاه المبيعات اليومية
     */
    public function getDailySalesTrend() {
        $sql = "SELECT
                    DATE(date_added) as date,
                    SUM(total) as revenue,
                    COUNT(*) as orders,
                    AVG(total) as avg_order_value
                FROM " . DB_PREFIX . "order
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND order_status_id > 0
                GROUP BY DATE(date_added)
                ORDER BY date ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Weekly Comparison
     * مقارنة أسبوعية
     */
    public function getWeeklyComparison() {
        $sql = "SELECT
                    'current_week' as period,
                    SUM(total) as revenue,
                    COUNT(*) as orders
                FROM " . DB_PREFIX . "order
                WHERE YEARWEEK(date_added) = YEARWEEK(CURDATE())
                AND order_status_id > 0
                UNION ALL
                SELECT
                    'previous_week' as period,
                    SUM(total) as revenue,
                    COUNT(*) as orders
                FROM " . DB_PREFIX . "order
                WHERE YEARWEEK(date_added) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))
                AND order_status_id > 0";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Monthly Comparison
     * مقارنة شهرية
     */
    public function getMonthlyComparison() {
        $sql = "SELECT
                    'current_month' as period,
                    SUM(total) as revenue,
                    COUNT(*) as orders
                FROM " . DB_PREFIX . "order
                WHERE YEAR(date_added) = YEAR(CURDATE())
                AND MONTH(date_added) = MONTH(CURDATE())
                AND order_status_id > 0
                UNION ALL
                SELECT
                    'previous_month' as period,
                    SUM(total) as revenue,
                    COUNT(*) as orders
                FROM " . DB_PREFIX . "order
                WHERE YEAR(date_added) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                AND MONTH(date_added) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                AND order_status_id > 0";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Average Processing Time
     * متوسط وقت المعالجة
     */
    public function getAvgProcessingTime() {
        $sql = "SELECT
                    AVG(TIMESTAMPDIFF(HOUR, date_added, date_modified)) as avg_hours
                FROM " . DB_PREFIX . "order
                WHERE order_status_id = 5
                AND date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

        $query = $this->db->query($sql);
        return round($query->row['avg_hours'], 2);
    }

    /**
     * Get Fulfillment Rate
     * معدل الوفاء بالطلبات
     */
    public function getFulfillmentRate() {
        $sql = "SELECT
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN order_status_id = 5 THEN 1 ELSE 0 END) as fulfilled_orders
                FROM " . DB_PREFIX . "order
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

        $query = $this->db->query($sql);
        $total = $query->row['total_orders'] ?: 1;
        $fulfilled = $query->row['fulfilled_orders'] ?: 0;

        return round(($fulfilled / $total) * 100, 2);
    }

    /**
     * Get Cancellation Rate
     * معدل الإلغاء
     */
    public function getCancellationRate() {
        $sql = "SELECT
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN order_status_id = 7 THEN 1 ELSE 0 END) as cancelled_orders
                FROM " . DB_PREFIX . "order
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

        $query = $this->db->query($sql);
        $total = $query->row['total_orders'] ?: 1;
        $cancelled = $query->row['cancelled_orders'] ?: 0;

        return round(($cancelled / $total) * 100, 2);
    }

    /**
     * Get Return Rate
     * معدل الإرجاع
     */
    public function getReturnRate() {
        $sql = "SELECT
                    COUNT(DISTINCT o.order_id) as total_orders,
                    COUNT(DISTINCT r.order_id) as returned_orders
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "return r ON (o.order_id = r.order_id)
                WHERE o.date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND o.order_status_id > 0";

        $query = $this->db->query($sql);
        $total = $query->row['total_orders'] ?: 1;
        $returned = $query->row['returned_orders'] ?: 0;

        return round(($returned / $total) * 100, 2);
    }

    /**
     * Get Customer Satisfaction
     * رضا العملاء
     */
    public function getCustomerSatisfaction() {
        $sql = "SELECT
                    AVG(rating) as avg_rating,
                    COUNT(*) as total_reviews
                FROM " . DB_PREFIX . "review
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND status = 1";

        $query = $this->db->query($sql);
        return array(
            'avg_rating' => round($query->row['avg_rating'], 2),
            'total_reviews' => $query->row['total_reviews']
        );
    }

    /**
     * Get Purchase Cycle Time
     * وقت دورة الشراء
     */
    public function getPurchaseCycleTime() {
        $sql = "SELECT
                    AVG(DATEDIFF(gr.created_at, po.created_at)) as avg_cycle_days
                FROM " . DB_PREFIX . "purchase_order po
                LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (po.po_id = gr.po_id)
                WHERE po.created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                AND po.status = 'completed'";

        $query = $this->db->query($sql);
        return round($query->row['avg_cycle_days'], 1);
    }

    /**
     * Get Cost Savings
     * توفير التكاليف
     */
    public function getCostSavings() {
        $sql = "SELECT
                    SUM(original_price - final_price) as total_savings,
                    COUNT(*) as negotiated_orders
                FROM " . DB_PREFIX . "purchase_order
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                AND original_price > final_price";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Inventory Turnover
     * معدل دوران المخزون
     */
    public function getInventoryTurnover() {
        $sql = "SELECT
                    (SELECT SUM(op.quantity * op.price)
                     FROM " . DB_PREFIX . "order_product op
                     LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                     WHERE o.date_added >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
                     AND o.order_status_id > 0) as cogs,
                    (SELECT SUM(quantity * price)
                     FROM " . DB_PREFIX . "product
                     WHERE status = 1) as avg_inventory";

        $query = $this->db->query($sql);
        $cogs = $query->row['cogs'] ?: 0;
        $inventory = $query->row['avg_inventory'] ?: 1;

        return round($cogs / $inventory, 2);
    }

    /**
     * Get Dead Stock
     * المخزون الراكد
     */
    public function getDeadStock() {
        $sql = "SELECT
                    p.product_id,
                    pd.name,
                    p.quantity,
                    p.price,
                    (p.quantity * p.price) as value
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                LEFT JOIN " . DB_PREFIX . "order_product op ON (p.product_id = op.product_id)
                LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id AND o.date_added >= DATE_SUB(CURDATE(), INTERVAL 90 DAY))
                WHERE p.status = 1
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND o.order_id IS NULL
                AND p.quantity > 0
                ORDER BY value DESC
                LIMIT 20";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // === FINANCIAL INTELLIGENCE METHODS ===

    /**
     * Get Revenue Forecast
     * توقعات الإيرادات
     */
    public function getRevenueForecast() {
        // Calculate trend based on last 3 months
        $sql = "SELECT
                    MONTH(date_added) as month,
                    SUM(total) as revenue
                FROM " . DB_PREFIX . "order
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                AND order_status_id > 0
                GROUP BY MONTH(date_added)
                ORDER BY month ASC";

        $query = $this->db->query($sql);
        $data = $query->rows;

        if (count($data) >= 2) {
            $growth_rate = 0;
            for ($i = 1; $i < count($data); $i++) {
                $growth_rate += (($data[$i]['revenue'] - $data[$i-1]['revenue']) / $data[$i-1]['revenue']) * 100;
            }
            $avg_growth_rate = $growth_rate / (count($data) - 1);

            $current_month_revenue = end($data)['revenue'];
            $forecast = $current_month_revenue * (1 + ($avg_growth_rate / 100));

            return array(
                'current_month' => $current_month_revenue,
                'forecast_next_month' => round($forecast, 2),
                'growth_rate' => round($avg_growth_rate, 2)
            );
        }

        return array('current_month' => 0, 'forecast_next_month' => 0, 'growth_rate' => 0);
    }

    /**
     * Get Gross Profit
     * إجمالي الربح
     */
    public function getGrossProfit() {
        $sql = "SELECT
                    SUM(o.total) as total_revenue,
                    SUM(op.quantity * p.cost) as total_cost
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                WHERE o.order_status_id > 0
                AND MONTH(o.date_added) = MONTH(CURDATE())
                AND YEAR(o.date_added) = YEAR(CURDATE())";

        $query = $this->db->query($sql);
        $revenue = $query->row['total_revenue'] ?: 0;
        $cost = $query->row['total_cost'] ?: 0;

        return array(
            'revenue' => $revenue,
            'cost' => $cost,
            'gross_profit' => $revenue - $cost,
            'margin_percentage' => $revenue > 0 ? round((($revenue - $cost) / $revenue) * 100, 2) : 0
        );
    }

    /**
     * Get Net Profit
     * صافي الربح
     */
    public function getNetProfit() {
        $gross_profit = $this->getGrossProfit();

        // Get operational expenses
        $sql = "SELECT SUM(amount) as total_expenses
                FROM " . DB_PREFIX . "expense
                WHERE MONTH(date) = MONTH(CURDATE())
                AND YEAR(date) = YEAR(CURDATE())";

        $query = $this->db->query($sql);
        $expenses = $query->row['total_expenses'] ?: 0;

        $net_profit = $gross_profit['gross_profit'] - $expenses;

        return array(
            'gross_profit' => $gross_profit['gross_profit'],
            'expenses' => $expenses,
            'net_profit' => $net_profit,
            'margin_percentage' => $gross_profit['revenue'] > 0 ? round(($net_profit / $gross_profit['revenue']) * 100, 2) : 0
        );
    }

    /**
     * Get Profit Margin Trend
     * اتجاه هامش الربح
     */
    public function getProfitMarginTrend() {
        $sql = "SELECT
                    DATE(o.date_added) as date,
                    SUM(o.total) as revenue,
                    SUM(op.quantity * p.cost) as cost
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                WHERE o.order_status_id > 0
                AND o.date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(o.date_added)
                ORDER BY date ASC";

        $query = $this->db->query($sql);
        $data = array();

        foreach ($query->rows as $row) {
            $revenue = $row['revenue'] ?: 0;
            $cost = $row['cost'] ?: 0;
            $margin = $revenue > 0 ? round((($revenue - $cost) / $revenue) * 100, 2) : 0;

            $data[] = array(
                'date' => $row['date'],
                'margin' => $margin
            );
        }

        return $data;
    }

    /**
     * Get COGS (Cost of Goods Sold)
     * تكلفة البضائع المباعة
     */
    public function getCOGS() {
        $sql = "SELECT
                    SUM(op.quantity * p.cost) as total_cogs
                FROM " . DB_PREFIX . "order_product op
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                WHERE o.order_status_id > 0
                AND MONTH(o.date_added) = MONTH(CURDATE())
                AND YEAR(o.date_added) = YEAR(CURDATE())";

        $query = $this->db->query($sql);
        return $query->row['total_cogs'] ?: 0;
    }

    /**
     * Get Operational Costs
     * التكاليف التشغيلية
     */
    public function getOperationalCosts() {
        $sql = "SELECT
                    SUM(amount) as total_operational_costs
                FROM " . DB_PREFIX . "expense
                WHERE category = 'operational'
                AND MONTH(date) = MONTH(CURDATE())
                AND YEAR(date) = YEAR(CURDATE())";

        $query = $this->db->query($sql);
        return $query->row['total_operational_costs'] ?: 0;
    }

    /**
     * Get Marketing Costs
     * تكاليف التسويق
     */
    public function getMarketingCosts() {
        $sql = "SELECT
                    SUM(amount) as total_marketing_costs
                FROM " . DB_PREFIX . "expense
                WHERE category = 'marketing'
                AND MONTH(date) = MONTH(CURDATE())
                AND YEAR(date) = YEAR(CURDATE())";

        $query = $this->db->query($sql);
        return $query->row['total_marketing_costs'] ?: 0;
    }

    /**
     * Get Cost Breakdown
     * تفصيل التكاليف
     */
    public function getCostBreakdown() {
        $sql = "SELECT
                    category,
                    SUM(amount) as total_amount
                FROM " . DB_PREFIX . "expense
                WHERE MONTH(date) = MONTH(CURDATE())
                AND YEAR(date) = YEAR(CURDATE())
                GROUP BY category
                ORDER BY total_amount DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Cash Flow Trend
     * اتجاه التدفق النقدي
     */
    public function getCashFlowTrend() {
        $sql = "SELECT
                    DATE(date) as date,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as net_flow
                FROM " . DB_PREFIX . "cash_flow
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(date)
                ORDER BY date ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // === CHART DATA METHODS ===

    /**
     * Get Monthly Revenue Comparison Chart
     * مخطط مقارنة الإيرادات الشهرية
     */
    public function getMonthlyRevenueComparison() {
        $sql = "SELECT
                    MONTH(date_added) as month,
                    YEAR(date_added) as year,
                    SUM(total) as revenue
                FROM " . DB_PREFIX . "order
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                AND order_status_id > 0
                GROUP BY YEAR(date_added), MONTH(date_added)
                ORDER BY year ASC, month ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Revenue by Category Chart
     * مخطط الإيرادات حسب الفئة
     */
    public function getRevenueByCategoryChart() {
        $sql = "SELECT
                    cd.name as category,
                    SUM(op.total) as revenue
                FROM " . DB_PREFIX . "order_product op
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id)
                LEFT JOIN " . DB_PREFIX . "category_description cd ON (ptc.category_id = cd.category_id)
                LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                WHERE o.order_status_id > 0
                AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND o.date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY ptc.category_id
                ORDER BY revenue DESC
                LIMIT 10";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Customer Acquisition Chart
     * مخطط اكتساب العملاء
     */
    public function getCustomerAcquisitionChart() {
        $sql = "SELECT
                    DATE(date_added) as date,
                    COUNT(*) as new_customers
                FROM " . DB_PREFIX . "customer
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(date_added)
                ORDER BY date ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Inventory Turnover Chart
     * مخطط دوران المخزون
     */
    public function getInventoryTurnoverChart() {
        $sql = "SELECT
                    cd.name as category,
                    (SUM(op.quantity * op.price) / AVG(p.quantity * p.price)) as turnover_rate
                FROM " . DB_PREFIX . "order_product op
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id)
                LEFT JOIN " . DB_PREFIX . "category_description cd ON (ptc.category_id = cd.category_id)
                LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                WHERE o.order_status_id > 0
                AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND o.date_added >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                GROUP BY ptc.category_id
                HAVING turnover_rate > 0
                ORDER BY turnover_rate DESC
                LIMIT 10";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // === ADDITIONAL MISSING METHODS ===

    /**
     * Get Employee Productivity Chart
     * مخطط إنتاجية الموظفين
     */
    public function getEmployeeProductivityChart() {
        $sql = "SELECT
                    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                    COUNT(t.task_id) as completed_tasks,
                    AVG(t.completion_time) as avg_completion_time
                FROM " . DB_PREFIX . "employee e
                LEFT JOIN " . DB_PREFIX . "task t ON (e.employee_id = t.assigned_to AND t.status = 'completed')
                WHERE t.completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY e.employee_id
                ORDER BY completed_tasks DESC
                LIMIT 10";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Quality Metrics Chart
     * مخطط مؤشرات الجودة
     */
    public function getQualityMetricsChart() {
        $sql = "SELECT
                    DATE(date) as date,
                    AVG(quality_score) as avg_quality,
                    COUNT(defect_id) as defect_count
                FROM " . DB_PREFIX . "quality_control
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(date)
                ORDER BY date ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Employee Productivity
     * إنتاجية الموظفين
     */
    public function getEmployeeProductivity() {
        $sql = "SELECT
                    AVG(productivity_score) as avg_productivity,
                    COUNT(DISTINCT employee_id) as total_employees
                FROM " . DB_PREFIX . "employee_performance
                WHERE MONTH(date) = MONTH(CURDATE())
                AND YEAR(date) = YEAR(CURDATE())";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Employee Satisfaction
     * رضا الموظفين
     */
    public function getEmployeeSatisfaction() {
        $sql = "SELECT
                    AVG(satisfaction_score) as avg_satisfaction,
                    COUNT(*) as survey_responses
                FROM " . DB_PREFIX . "employee_survey
                WHERE MONTH(date) = MONTH(CURDATE())
                AND YEAR(date) = YEAR(CURDATE())";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Project Completion Rate
     * معدل إنجاز المشاريع
     */
    public function getProjectCompletionRate() {
        $sql = "SELECT
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects
                FROM " . DB_PREFIX . "project
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";

        $query = $this->db->query($sql);
        $total = $query->row['total_projects'] ?: 1;
        $completed = $query->row['completed_projects'] ?: 0;

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Get Budget Utilization
     * استخدام الميزانية
     */
    public function getBudgetUtilization() {
        $sql = "SELECT
                    SUM(budget) as total_budget,
                    SUM(spent) as total_spent
                FROM " . DB_PREFIX . "project
                WHERE status = 'active'";

        $query = $this->db->query($sql);
        $budget = $query->row['total_budget'] ?: 1;
        $spent = $query->row['total_spent'] ?: 0;

        return array(
            'total_budget' => $budget,
            'total_spent' => $spent,
            'utilization_rate' => round(($spent / $budget) * 100, 2)
        );
    }

    /**
     * Get Team Productivity
     * إنتاجية الفريق
     */
    public function getTeamProductivity() {
        $sql = "SELECT
                    t.team_name,
                    AVG(tp.productivity_score) as avg_productivity
                FROM " . DB_PREFIX . "team t
                LEFT JOIN " . DB_PREFIX . "team_performance tp ON (t.team_id = tp.team_id)
                WHERE tp.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY t.team_id
                ORDER BY avg_productivity DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Average Transaction Time (POS)
     * متوسط وقت المعاملة
     */
    public function getAvgTransactionTime() {
        $sql = "SELECT
                    AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) as avg_seconds
                FROM " . DB_PREFIX . "pos_transaction
                WHERE DATE(created_at) = CURDATE()
                AND status = 'completed'";

        $query = $this->db->query($sql);
        return round($query->row['avg_seconds'], 2);
    }

    /**
     * Get Cashier Performance
     * أداء أمناء الصندوق
     */
    public function getCashierPerformance() {
        $sql = "SELECT
                    u.username as cashier_name,
                    COUNT(pt.transaction_id) as transaction_count,
                    SUM(pt.total) as total_sales,
                    AVG(TIMESTAMPDIFF(SECOND, pt.start_time, pt.end_time)) as avg_time
                FROM " . DB_PREFIX . "pos_transaction pt
                LEFT JOIN " . DB_PREFIX . "user u ON (pt.user_id = u.user_id)
                WHERE DATE(pt.created_at) = CURDATE()
                GROUP BY pt.user_id
                ORDER BY total_sales DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get POS Uptime
     * وقت تشغيل نقاط البيع
     */
    public function getPOSUptime() {
        $sql = "SELECT
                    AVG(uptime_percentage) as avg_uptime
                FROM " . DB_PREFIX . "pos_system_status
                WHERE DATE(date) = CURDATE()";

        $query = $this->db->query($sql);
        return round($query->row['avg_uptime'], 2);
    }

    /**
     * Get Defect Rate
     * معدل العيوب
     */
    public function getDefectRate() {
        $sql = "SELECT
                    COUNT(*) as total_products,
                    SUM(CASE WHEN defect_found = 1 THEN 1 ELSE 0 END) as defective_products
                FROM " . DB_PREFIX . "quality_control
                WHERE DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

        $query = $this->db->query($sql);
        $total = $query->row['total_products'] ?: 1;
        $defective = $query->row['defective_products'] ?: 0;

        return round(($defective / $total) * 100, 2);
    }

    /**
     * Get Customer Complaints
     * شكاوى العملاء
     */
    public function getCustomerComplaints() {
        $sql = "SELECT
                    COUNT(*) as total_complaints,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_complaints
                FROM " . DB_PREFIX . "customer_complaint
                WHERE MONTH(date_added) = MONTH(CURDATE())
                AND YEAR(date_added) = YEAR(CURDATE())";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Resolution Time
     * وقت الحل
     */
    public function getResolutionTime() {
        $sql = "SELECT
                    AVG(TIMESTAMPDIFF(HOUR, date_added, resolved_at)) as avg_resolution_hours
                FROM " . DB_PREFIX . "customer_complaint
                WHERE status = 'resolved'
                AND resolved_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

        $query = $this->db->query($sql);
        return round($query->row['avg_resolution_hours'], 2);
    }

    /**
     * Get Quality Return Rate
     * معدل الإرجاع للجودة
     */
    public function getQualityReturnRate() {
        $sql = "SELECT
                    COUNT(DISTINCT o.order_id) as total_orders,
                    COUNT(DISTINCT r.order_id) as quality_returns
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "return r ON (o.order_id = r.order_id AND r.reason = 'quality')
                WHERE o.date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND o.order_status_id > 0";

        $query = $this->db->query($sql);
        $total = $query->row['total_orders'] ?: 1;
        $returns = $query->row['quality_returns'] ?: 0;

        return round(($returns / $total) * 100, 2);
    }

    /**
     * Get Training Effectiveness
     * فعالية التدريب
     */
    public function getTrainingEffectiveness() {
        $sql = "SELECT
                    AVG(effectiveness_score) as avg_effectiveness,
                    COUNT(*) as training_sessions
                FROM " . DB_PREFIX . "training_evaluation
                WHERE MONTH(date) = MONTH(CURDATE())
                AND YEAR(date) = YEAR(CURDATE())";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get Year Revenue
     * إيرادات السنة
     */
    public function getYearRevenue() {
        $sql = "SELECT
                    SUM(total) as total_revenue
                FROM " . DB_PREFIX . "order
                WHERE YEAR(date_added) = YEAR(CURDATE())
                AND order_status_id > 0";

        $query = $this->db->query($sql);
        return $query->row['total_revenue'] ?: 0;
    }

    /**
     * Get Year Growth Rate
     * معدل النمو السنوي
     */
    public function getYearGrowthRate() {
        $sql = "SELECT
                    (SELECT SUM(total) FROM " . DB_PREFIX . "order
                     WHERE YEAR(date_added) = YEAR(CURDATE())
                     AND order_status_id > 0) as current_year,
                    (SELECT SUM(total) FROM " . DB_PREFIX . "order
                     WHERE YEAR(date_added) = YEAR(CURDATE()) - 1
                     AND order_status_id > 0) as previous_year";

        $query = $this->db->query($sql);
        $current = $query->row['current_year'] ?: 0;
        $previous = $query->row['previous_year'] ?: 1;

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get Year Customer Growth
     * نمو العملاء السنوي
     */
    public function getYearCustomerGrowth() {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM " . DB_PREFIX . "customer
                     WHERE YEAR(date_added) = YEAR(CURDATE())) as current_year,
                    (SELECT COUNT(*) FROM " . DB_PREFIX . "customer
                     WHERE YEAR(date_added) = YEAR(CURDATE()) - 1) as previous_year";

        $query = $this->db->query($sql);
        $current = $query->row['current_year'] ?: 0;
        $previous = $query->row['previous_year'] ?: 1;

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get Order Trend
     * اتجاه الطلبات
     */
    public function getOrderTrend() {
        $sql = "SELECT
                    DATE(date_added) as date,
                    COUNT(*) as order_count
                FROM " . DB_PREFIX . "order
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND order_status_id > 0
                GROUP BY DATE(date_added)
                ORDER BY date ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get Month Orders
     * طلبات الشهر
     */
    public function getMonthOrders() {
        $sql = "SELECT
                    COUNT(*) as total_orders
                FROM " . DB_PREFIX . "order
                WHERE YEAR(date_added) = YEAR(CURDATE())
                AND MONTH(date_added) = MONTH(CURDATE())
                AND order_status_id > 0";

        $query = $this->db->query($sql);
        return $query->row['total_orders'];
    }

    /**
     * Get Month Profit Margin
     * هامش ربح الشهر
     */
    public function getMonthProfitMargin() {
        $gross_profit = $this->getGrossProfit();
        return $gross_profit['margin_percentage'];
    }
}
