<?php
/**
 * لوحة معلومات المخزون المتقدمة (Advanced Inventory Dashboard)
 * 
 * الهدف: إنشاء لوحة معلومات شاملة ومتقدمة للمخزون تتفوق على Odoo وSAP
 * الميزات: إحصائيات فورية، رسوم بيانية تفاعلية، تنبيهات ذكية، تحليلات متقدمة
 * التكامل: مع المحاسبة والمشتريات والمبيعات والإشعارات المركزية
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryDashboard extends Controller {
    
    private $error = array();
    
    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/dashboard');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/dashboard');
        $this->load->model('inventory/product');
        $this->load->model('inventory/movement');
        $this->load->model('accounting/journal');
        $this->load->model('purchase/order');
        
        $data = array();
        
        // معلومات الصفحة الأساسية
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_dashboard'] = $this->language->get('text_dashboard');
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // الحصول على الإحصائيات الأساسية
        $data['stats'] = $this->getBasicStats();
        
        // الحصول على التنبيهات الذكية
        $data['alerts'] = $this->getSmartAlerts();
        
        // الحصول على بيانات الرسوم البيانية
        $data['charts'] = $this->getChartsData();
        
        // الحصول على أحدث الحركات
        $data['recent_movements'] = $this->getRecentMovements();
        
        // الحصول على المنتجات الأكثر حركة
        $data['top_products'] = $this->getTopMovingProducts();
        
        // الحصول على تحليل ABC
        $data['abc_analysis'] = $this->getABCAnalysis();
        
        // روابط سريعة للشاشات الأخرى
        $data['quick_links'] = $this->getQuickLinks();
        
        // معلومات المستخدم والصلاحيات
        $data['user_token'] = $this->session->data['user_token'];
        $data['can_modify'] = $this->user->hasPermission('modify', 'inventory/dashboard');
        
        // تحميل الهيدر والفوتر
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/dashboard', $data));
    }
    
    /**
     * الحصول على الإحصائيات الأساسية
     */
    private function getBasicStats() {
        $stats = array();
        
        // إجمالي عدد المنتجات
        $stats['total_products'] = $this->model_inventory_product->getTotalProducts();
        
        // إجمالي قيمة المخزون (بالتكلفة WAC)
        $stats['total_inventory_value'] = $this->model_inventory_dashboard->getTotalInventoryValue();
        
        // عدد المنتجات منخفضة المخزون
        $stats['low_stock_products'] = $this->model_inventory_dashboard->getLowStockProductsCount();
        
        // عدد المنتجات منتهية الصلاحية قريباً
        $stats['expiring_products'] = $this->model_inventory_dashboard->getExpiringProductsCount();
        
        // عدد الحركات اليوم
        $stats['today_movements'] = $this->model_inventory_movement->getTodayMovementsCount();
        
        // متوسط دوران المخزون
        $stats['avg_turnover'] = $this->model_inventory_dashboard->getAverageTurnover();
        
        return $stats;
    }
    
    /**
     * الحصول على التنبيهات الذكية
     */
    private function getSmartAlerts() {
        $alerts = array();
        
        // تنبيهات نقص المخزون
        $low_stock = $this->model_inventory_dashboard->getLowStockProducts(10);
        if (!empty($low_stock)) {
            $alerts[] = array(
                'type' => 'warning',
                'icon' => 'fa-exclamation-triangle',
                'title' => $this->language->get('alert_low_stock_title'),
                'message' => sprintf($this->language->get('alert_low_stock_message'), count($low_stock)),
                'action_text' => $this->language->get('text_view_details'),
                'action_link' => $this->url->link('inventory/current_stock', 'filter_low_stock=1&user_token=' . $this->session->data['user_token'], true)
            );
        }
        
        // تنبيهات انتهاء الصلاحية
        $expiring = $this->model_inventory_dashboard->getExpiringProducts(30);
        if (!empty($expiring)) {
            $alerts[] = array(
                'type' => 'danger',
                'icon' => 'fa-clock-o',
                'title' => $this->language->get('alert_expiring_title'),
                'message' => sprintf($this->language->get('alert_expiring_message'), count($expiring)),
                'action_text' => $this->language->get('text_view_details'),
                'action_link' => $this->url->link('inventory/expiry_tracking', 'user_token=' . $this->session->data['user_token'], true)
            );
        }
        
        // تنبيهات المخزون الراكد
        $slow_moving = $this->model_inventory_dashboard->getSlowMovingProducts(90);
        if (!empty($slow_moving)) {
            $alerts[] = array(
                'type' => 'info',
                'icon' => 'fa-pause',
                'title' => $this->language->get('alert_slow_moving_title'),
                'message' => sprintf($this->language->get('alert_slow_moving_message'), count($slow_moving)),
                'action_text' => $this->language->get('text_view_details'),
                'action_link' => $this->url->link('inventory/slow_moving', 'user_token=' . $this->session->data['user_token'], true)
            );
        }
        
        // تنبيهات الجرد المطلوب
        $pending_stocktake = $this->model_inventory_dashboard->getPendingStocktakeCount();
        if ($pending_stocktake > 0) {
            $alerts[] = array(
                'type' => 'warning',
                'icon' => 'fa-list-alt',
                'title' => $this->language->get('alert_pending_stocktake_title'),
                'message' => sprintf($this->language->get('alert_pending_stocktake_message'), $pending_stocktake),
                'action_text' => $this->language->get('text_view_details'),
                'action_link' => $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'], true)
            );
        }
        
        return $alerts;
    }
    
    /**
     * الحصول على بيانات الرسوم البيانية
     */
    private function getChartsData() {
        $charts = array();
        
        // رسم بياني لحركة المخزون (آخر 30 يوم)
        $charts['inventory_movement'] = $this->model_inventory_dashboard->getInventoryMovementChart(30);
        
        // رسم بياني لقيمة المخزون (آخر 12 شهر)
        $charts['inventory_value'] = $this->model_inventory_dashboard->getInventoryValueChart(12);
        
        // رسم بياني للمنتجات الأكثر حركة
        $charts['top_products'] = $this->model_inventory_dashboard->getTopProductsChart(10);
        
        // رسم بياني لتوزيع المخزون حسب الفروع
        $charts['branch_distribution'] = $this->model_inventory_dashboard->getBranchDistributionChart();
        
        // رسم بياني لتحليل ABC
        $charts['abc_analysis'] = $this->model_inventory_dashboard->getABCAnalysisChart();
        
        return $charts;
    }
    
    /**
     * الحصول على أحدث الحركات
     */
    private function getRecentMovements() {
        return $this->model_inventory_movement->getRecentMovements(10);
    }
    
    /**
     * الحصول على المنتجات الأكثر حركة
     */
    private function getTopMovingProducts() {
        return $this->model_inventory_dashboard->getTopMovingProducts(10);
    }
    
    /**
     * الحصول على تحليل ABC
     */
    private function getABCAnalysis() {
        return $this->model_inventory_dashboard->getABCAnalysisSummary();
    }
    
    /**
     * الحصول على الروابط السريعة
     */
    private function getQuickLinks() {
        $links = array();
        
        if ($this->user->hasPermission('access', 'inventory/product')) {
            $links[] = array(
                'name' => $this->language->get('text_product_management'),
                'href' => $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'], true),
                'icon' => 'fa-cube'
            );
        }
        
        if ($this->user->hasPermission('access', 'inventory/current_stock')) {
            $links[] = array(
                'name' => $this->language->get('text_current_stock'),
                'href' => $this->url->link('inventory/current_stock', 'user_token=' . $this->session->data['user_token'], true),
                'icon' => 'fa-list'
            );
        }
        
        if ($this->user->hasPermission('access', 'inventory/adjustment')) {
            $links[] = array(
                'name' => $this->language->get('text_stock_adjustment'),
                'href' => $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token'], true),
                'icon' => 'fa-edit'
            );
        }
        
        if ($this->user->hasPermission('access', 'inventory/transfer')) {
            $links[] = array(
                'name' => $this->language->get('text_stock_transfer'),
                'href' => $this->url->link('inventory/transfer', 'user_token=' . $this->session->data['user_token'], true),
                'icon' => 'fa-exchange'
            );
        }
        
        if ($this->user->hasPermission('access', 'inventory/stocktake')) {
            $links[] = array(
                'name' => $this->language->get('text_stocktake'),
                'href' => $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'], true),
                'icon' => 'fa-list-alt'
            );
        }
        
        if ($this->user->hasPermission('access', 'report/inventory_valuation')) {
            $links[] = array(
                'name' => $this->language->get('text_inventory_reports'),
                'href' => $this->url->link('report/inventory_valuation', 'user_token=' . $this->session->data['user_token'], true),
                'icon' => 'fa-bar-chart'
            );
        }
        
        return $links;
    }
    
    /**
     * AJAX: تحديث الإحصائيات
     */
    public function refresh_stats() {
        $this->load->model('inventory/dashboard');
        
        $stats = $this->getBasicStats();
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($stats));
    }
    
    /**
     * AJAX: تحديث التنبيهات
     */
    public function refresh_alerts() {
        $alerts = $this->getSmartAlerts();
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($alerts));
    }
}
