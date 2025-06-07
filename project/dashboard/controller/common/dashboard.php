<?php
/**
 * AYM ERP Executive Dashboard Controller
 * لوحة المعلومات التنفيذية الشاملة لنظام أيم ERP
 * 
 * Dashboard شامل يعكس قوة النظام الحقيقية:
 * - 14 وحدة ERP متكاملة
 * - نظام محاسبي متقدم مع WAC
 * - تجارة إلكترونية متطورة
 * - إدارة مشاريع ومهام
 * - موارد بشرية ورواتب
 * - CRM وتسويق متقدم
 * - شحن وتوزيع ذكي
 * - تقارير وتحليلات AI
 */

class ControllerCommonDashboard extends Controller {
    
    /**
     * Main Dashboard Index
     * الصفحة الرئيسية للوحة المعلومات
     */
    public function index() {
        // Load language files
        $this->load->language('common/dashboard');
        
        // Load models
        $this->load->model('common/dashboard');
        $this->load->model('setting/setting');
        
        // Set page title
        $this->document->setTitle($this->language->get('heading_title'));
        
        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['heading_title'] = $this->language->get('heading_title');
        $data['user_token'] = $this->session->data['user_token'];
        
        // === EXECUTIVE SUMMARY ===
        $data['executive_summary'] = $this->getExecutiveSummary();
        
        // === E-COMMERCE ENGINE ===
        $data['ecommerce_metrics'] = $this->getEcommerceMetrics();
        
        // === ERP MODULES OVERVIEW ===
        $data['erp_modules'] = $this->getERPModulesOverview();
        
        // === FINANCIAL INTELLIGENCE ===
        $data['financial_intelligence'] = $this->getFinancialIntelligence();
        
        // === OPERATIONAL EXCELLENCE ===
        $data['operational_metrics'] = $this->getOperationalMetrics();
        
        // === REAL-TIME ALERTS ===
        $data['real_time_alerts'] = $this->getRealTimeAlerts();
        
        // === RECENT ACTIVITIES ===
        $data['recent_activities'] = $this->getRecentActivities();
        
        // === CHART DATA ===
        $data['chart_data'] = $this->getChartData();
        
        // Load header and footer
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('common/dashboard', $data));
    }
    
    /**
     * Get Executive Summary
     * الملخص التنفيذي للإدارة العليا
     */
    private function getExecutiveSummary() {
        $summary = array();
        
        // Today's Performance
        $summary['today'] = array(
            'total_revenue' => $this->model_common_dashboard->getTodayRevenue(),
            'total_orders' => $this->model_common_dashboard->getTodayOrders(),
            'new_customers' => $this->model_common_dashboard->getTodayNewCustomers(),
            'conversion_rate' => $this->model_common_dashboard->getTodayConversionRate(),
            'avg_order_value' => $this->model_common_dashboard->getTodayAOV(),
            'revenue_trend' => $this->model_common_dashboard->getRevenueTrend(),
            'order_trend' => $this->model_common_dashboard->getOrderTrend()
        );
        
        // Monthly Performance
        $summary['month'] = array(
            'total_revenue' => $this->model_common_dashboard->getMonthRevenue(),
            'total_orders' => $this->model_common_dashboard->getMonthOrders(),
            'growth_rate' => $this->model_common_dashboard->getMonthGrowthRate(),
            'profit_margin' => $this->model_common_dashboard->getMonthProfitMargin()
        );
        
        // Yearly Performance
        $summary['year'] = array(
            'total_revenue' => $this->model_common_dashboard->getYearRevenue(),
            'growth_rate' => $this->model_common_dashboard->getYearGrowthRate(),
            'customer_growth' => $this->model_common_dashboard->getYearCustomerGrowth()
        );
        
        return $summary;
    }
    
    /**
     * Get E-commerce Metrics
     * مؤشرات محرك التجارة الإلكترونية
     */
    private function getEcommerceMetrics() {
        return array(
            'hourly_sales' => $this->model_common_dashboard->getHourlySales(),
            'daily_trend' => $this->model_common_dashboard->getDailySalesTrend(),
            'weekly_comparison' => $this->model_common_dashboard->getWeeklyComparison(),
            'monthly_comparison' => $this->model_common_dashboard->getMonthlyComparison(),
            'top_products' => $this->model_common_dashboard->getTopSellingProducts(10),
            'category_performance' => $this->model_common_dashboard->getCategoryPerformance(),
            'order_status_distribution' => $this->model_common_dashboard->getOrderStatusDistribution(),
            'avg_processing_time' => $this->model_common_dashboard->getAvgProcessingTime(),
            'fulfillment_rate' => $this->model_common_dashboard->getFulfillmentRate(),
            'cancellation_rate' => $this->model_common_dashboard->getCancellationRate(),
            'return_rate' => $this->model_common_dashboard->getReturnRate()
        );
    }
    
    /**
     * Get ERP Modules Overview
     * نظرة عامة على وحدات ERP الـ 14
     */
    private function getERPModulesOverview() {
        return array(
            // 1. Sales & CRM
            'sales_crm' => array(
                'active_leads' => $this->model_common_dashboard->getActiveLeads(),
                'conversion_rate' => $this->model_common_dashboard->getLeadConversionRate(),
                'sales_pipeline' => $this->model_common_dashboard->getSalesPipeline(),
                'customer_satisfaction' => $this->model_common_dashboard->getCustomerSatisfaction()
            ),
            
            // 2. Purchasing & Procurement
            'purchasing' => array(
                'pending_pos' => $this->model_common_dashboard->getPendingPurchaseOrders(),
                'supplier_performance' => $this->model_common_dashboard->getSupplierPerformance(),
                'purchase_cycle_time' => $this->model_common_dashboard->getPurchaseCycleTime(),
                'cost_savings' => $this->model_common_dashboard->getCostSavings()
            ),
            
            // 3. Inventory & Warehouse
            'inventory' => array(
                'stock_levels' => $this->model_common_dashboard->getCurrentStockLevels(),
                'turnover_rate' => $this->model_common_dashboard->getInventoryTurnover(),
                'low_stock_alerts' => $this->model_common_dashboard->getLowStockAlerts(),
                'dead_stock' => $this->model_common_dashboard->getDeadStock(),
                'inventory_value' => $this->model_common_dashboard->getInventoryValue()
            ),
            
            // 4. Accounting & Finance
            'finance' => array(
                'cash_position' => $this->model_common_dashboard->getCurrentCashPosition(),
                'accounts_receivable' => $this->model_common_dashboard->getAccountsReceivable(),
                'accounts_payable' => $this->model_common_dashboard->getAccountsPayable(),
                'profit_margin' => $this->model_common_dashboard->getMonthProfitMargin()
            ),
            
            // 5. Human Resources
            'hr' => array(
                'total_employees' => $this->model_common_dashboard->getTotalEmployees(),
                'attendance_rate' => $this->model_common_dashboard->getAttendanceRate(),
                'productivity' => $this->model_common_dashboard->getEmployeeProductivity(),
                'satisfaction' => $this->model_common_dashboard->getEmployeeSatisfaction()
            ),
            
            // 6. Project Management
            'projects' => array(
                'active_projects' => $this->model_common_dashboard->getActiveProjects(),
                'completion_rate' => $this->model_common_dashboard->getProjectCompletionRate(),
                'budget_utilization' => $this->model_common_dashboard->getBudgetUtilization(),
                'team_productivity' => $this->model_common_dashboard->getTeamProductivity()
            )
        );
    }
    
    /**
     * Get Financial Intelligence
     * الذكاء المالي المتقدم
     */
    private function getFinancialIntelligence() {
        return array(
            'revenue_forecast' => $this->model_common_dashboard->getRevenueForecast(),
            'profit_analysis' => array(
                'gross_profit' => $this->model_common_dashboard->getGrossProfit(),
                'net_profit' => $this->model_common_dashboard->getNetProfit(),
                'profit_trend' => $this->model_common_dashboard->getProfitMarginTrend()
            ),
            'cost_analysis' => array(
                'cogs' => $this->model_common_dashboard->getCOGS(),
                'operational_costs' => $this->model_common_dashboard->getOperationalCosts(),
                'marketing_costs' => $this->model_common_dashboard->getMarketingCosts(),
                'cost_breakdown' => $this->model_common_dashboard->getCostBreakdown()
            ),
            'cash_flow' => array(
                'current_position' => $this->model_common_dashboard->getCurrentCashPosition(),
                'flow_trend' => $this->model_common_dashboard->getCashFlowTrend(),
                'receivables' => $this->model_common_dashboard->getAccountsReceivable(),
                'payables' => $this->model_common_dashboard->getAccountsPayable()
            )
        );
    }
    
    /**
     * Get Operational Metrics
     * مؤشرات التميز التشغيلي
     */
    private function getOperationalMetrics() {
        return array(
            'pos_operations' => array(
                'daily_transactions' => $this->model_common_dashboard->getDailyTransactions(),
                'avg_transaction_time' => $this->model_common_dashboard->getAvgTransactionTime(),
                'cashier_performance' => $this->model_common_dashboard->getCashierPerformance(),
                'uptime' => $this->model_common_dashboard->getPOSUptime()
            ),
            'quality_metrics' => array(
                'defect_rate' => $this->model_common_dashboard->getDefectRate(),
                'customer_complaints' => $this->model_common_dashboard->getCustomerComplaints(),
                'resolution_time' => $this->model_common_dashboard->getResolutionTime(),
                'return_rate' => $this->model_common_dashboard->getQualityReturnRate()
            ),
            'training_effectiveness' => $this->model_common_dashboard->getTrainingEffectiveness()
        );
    }
    
    /**
     * Get Real-time Alerts
     * التنبيهات الفورية
     */
    private function getRealTimeAlerts() {
        return array(
            'critical' => $this->model_common_dashboard->getCriticalAlerts(),
            'warnings' => $this->model_common_dashboard->getWarningAlerts(),
            'info' => $this->model_common_dashboard->getInfoAlerts()
        );
    }
    
    /**
     * Get Recent Activities
     * الأنشطة الحديثة
     */
    private function getRecentActivities() {
        return array(
            'recent_orders' => $this->model_common_dashboard->getRecentOrders(15),
            'recent_customers' => $this->model_common_dashboard->getRecentCustomers(15),
            'recent_transactions' => $this->model_common_dashboard->getRecentTransactions(15),
            'activity_log' => $this->model_common_dashboard->getActivityLog(20),
            'system_events' => $this->model_common_dashboard->getSystemEvents(10)
        );
    }
    
    /**
     * Get Chart Data
     * بيانات المخططات
     */
    private function getChartData() {
        return array(
            'sales_trend' => $this->model_common_dashboard->getDailyRevenueTrend(),
            'revenue_comparison' => $this->model_common_dashboard->getMonthlyRevenueComparison(),
            'category_revenue' => $this->model_common_dashboard->getRevenueByCategoryChart(),
            'customer_acquisition' => $this->model_common_dashboard->getCustomerAcquisitionChart(),
            'inventory_turnover' => $this->model_common_dashboard->getInventoryTurnoverChart(),
            'employee_productivity' => $this->model_common_dashboard->getEmployeeProductivityChart(),
            'quality_metrics' => $this->model_common_dashboard->getQualityMetricsChart()
        );
    }
    
    /**
     * AJAX Refresh Dashboard Data
     * تحديث بيانات لوحة المعلومات عبر AJAX
     */
    public function refresh() {
        $json = array();
        
        try {
            $range = isset($this->request->get['range']) ? $this->request->get['range'] : 'today';
            
            // Refresh data based on range
            $json['success'] = true;
            $json['data'] = array(
                'executive_summary' => $this->getExecutiveSummary(),
                'ecommerce_metrics' => $this->getEcommerceMetrics(),
                'chart_data' => $this->getChartData()
            );
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
