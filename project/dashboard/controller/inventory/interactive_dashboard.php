<?php
/**
 * لوحة تحكم المخزون التفاعلية (Interactive Inventory Dashboard Controller)
 * 
 * الهدف: توفير واجهة تفاعلية شاملة لإدارة ومراقبة المخزون
 * الميزات: إحصائيات في الوقت الفعلي، رسوم بيانية تفاعلية، تنبيهات ذكية
 * التكامل: مع جميع شاشات المخزون والمنتجات والمبيعات والمشتريات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryInteractiveDashboard extends Controller {
    
    private $error = array();
    
    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/interactive_dashboard');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/interactive_dashboard');
        
        // إعداد البيانات الأساسية
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/interactive_dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // الحصول على الإحصائيات العامة
        $filter_data = array();
        if (isset($this->request->get['filter_date_from'])) {
            $filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
        }
        if (isset($this->request->get['filter_date_to'])) {
            $filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
        }
        
        $statistics = $this->model_inventory_interactive_dashboard->getGeneralStatistics($filter_data);
        $data['statistics'] = array(
            'total_products'         => number_format($statistics['total_products']),
            'active_products'        => number_format($statistics['active_products']),
            'inactive_products'      => number_format($statistics['inactive_products']),
            'out_of_stock_products'  => number_format($statistics['out_of_stock_products']),
            'low_stock_products'     => number_format($statistics['low_stock_products']),
            'overstock_products'     => number_format($statistics['overstock_products']),
            'total_quantity'         => number_format($statistics['total_quantity']),
            'total_inventory_value'  => number_format($statistics['total_inventory_value'], 2),
            'avg_cost_price'         => number_format($statistics['avg_cost_price'], 2),
            'avg_selling_price'      => number_format($statistics['avg_selling_price'], 2),
            'total_manufacturers'    => number_format($statistics['total_manufacturers']),
            'total_categories'       => number_format($statistics['total_categories']),
            'total_barcodes'         => number_format($statistics['total_barcodes']),
            'active_barcodes'        => number_format($statistics['active_barcodes']),
            'total_units'            => number_format($statistics['total_units']),
            'movements_30_days'      => number_format($statistics['movements_30_days']),
            'sales_quantity_30_days' => number_format($statistics['sales_quantity_30_days']),
            'sales_value_30_days'    => number_format($statistics['sales_value_30_days'], 2)
        );
        
        // الحصول على إحصائيات الفئات
        $data['category_statistics'] = $this->model_inventory_interactive_dashboard->getInventoryByCategories(8);
        
        // الحصول على تحليل حركة المخزون
        $data['movement_analysis'] = $this->model_inventory_interactive_dashboard->getInventoryMovementAnalysis(30);
        
        // الحصول على أفضل المنتجات مبيعاً
        $data['top_selling_products'] = $this->model_inventory_interactive_dashboard->getTopSellingProducts(10, 30);
        
        // الحصول على المنتجات منخفضة المخزون
        $data['low_stock_products'] = $this->model_inventory_interactive_dashboard->getLowStockProducts(10);
        
        // الحصول على تحليل الربحية
        $data['profitability_analysis'] = $this->model_inventory_interactive_dashboard->getProfitabilityAnalysis(30);
        
        // الحصول على التنبيهات الذكية
        $data['smart_alerts'] = $this->model_inventory_interactive_dashboard->getSmartAlerts();
        
        // الحصول على مؤشرات الأداء الرئيسية
        $data['kpis'] = $this->model_inventory_interactive_dashboard->getKPIs(30);
        
        // إعداد الروابط
        $data['refresh'] = $this->url->link('inventory/interactive_dashboard', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_report'] = $this->url->link('inventory/interactive_dashboard/exportReport', 'user_token=' . $this->session->data['user_token'], true);
        
        // روابط الإجراءات السريعة
        $data['manage_products'] = $this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'], true);
        $data['stock_movements'] = $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'], true);
        $data['stock_adjustments'] = $this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'], true);
        $data['stock_transfers'] = $this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true);
        $data['inventory_count'] = $this->url->link('inventory/stock_count', 'user_token=' . $this->session->data['user_token'], true);
        $data['barcode_management'] = $this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'], true);
        
        // روابط التقارير
        $data['balance_inquiry'] = $this->url->link('inventory/balance_inquiry', 'user_token=' . $this->session->data['user_token'], true);
        $data['inventory_valuation'] = $this->url->link('inventory/inventory_valuation', 'user_token=' . $this->session->data['user_token'], true);
        
        // إعداد الفلاتر
        $data['filter_date_from'] = isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : date('Y-m-01');
        $data['filter_date_to'] = isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : date('Y-m-d');
        
        $data['user_token'] = $this->session->data['user_token'];
        
        // رسائل النجاح والخطأ
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/interactive_dashboard', $data));
    }
    
    /**
     * الحصول على بيانات الرسوم البيانية عبر AJAX
     */
    public function getChartData() {
        $this->load->model('inventory/interactive_dashboard');
        
        $chart_type = isset($this->request->get['type']) ? $this->request->get['type'] : 'movement';
        $days = isset($this->request->get['days']) ? (int)$this->request->get['days'] : 30;
        
        $data = array();
        
        switch ($chart_type) {
            case 'movement':
                $movements = $this->model_inventory_interactive_dashboard->getInventoryMovementAnalysis($days);
                $data = $this->formatMovementChartData($movements);
                break;
                
            case 'categories':
                $categories = $this->model_inventory_interactive_dashboard->getInventoryByCategories(10);
                $data = $this->formatCategoryChartData($categories);
                break;
                
            case 'profitability':
                $profitability = $this->model_inventory_interactive_dashboard->getProfitabilityAnalysis($days);
                $data = $this->formatProfitabilityChartData($profitability);
                break;
                
            default:
                $data = array('error' => 'نوع الرسم البياني غير صحيح');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }
    
    /**
     * تنسيق بيانات رسم حركة المخزون
     */
    private function formatMovementChartData($movements) {
        $dates = array();
        $in_data = array();
        $out_data = array();
        
        foreach ($movements as $movement) {
            $date = $movement['movement_date'];
            if (!in_array($date, $dates)) {
                $dates[] = $date;
                $in_data[$date] = 0;
                $out_data[$date] = 0;
            }
            
            if (in_array($movement['movement_type'], array('in', 'adjustment_in', 'transfer_in'))) {
                $in_data[$date] += $movement['total_in'];
            } else {
                $out_data[$date] += $movement['total_out'];
            }
        }
        
        return array(
            'labels' => array_values($dates),
            'datasets' => array(
                array(
                    'label' => 'الوارد',
                    'data' => array_values($in_data),
                    'backgroundColor' => 'rgba(92, 184, 92, 0.2)',
                    'borderColor' => 'rgba(92, 184, 92, 1)',
                    'borderWidth' => 2
                ),
                array(
                    'label' => 'الصادر',
                    'data' => array_values($out_data),
                    'backgroundColor' => 'rgba(217, 83, 79, 0.2)',
                    'borderColor' => 'rgba(217, 83, 79, 1)',
                    'borderWidth' => 2
                )
            )
        );
    }
    
    /**
     * تنسيق بيانات رسم الفئات
     */
    private function formatCategoryChartData($categories) {
        $labels = array();
        $data = array();
        $colors = array('#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384');
        
        foreach ($categories as $index => $category) {
            $labels[] = $category['category_name'];
            $data[] = (float)$category['total_value'];
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 1
                )
            )
        );
    }
    
    /**
     * تنسيق بيانات رسم الربحية
     */
    private function formatProfitabilityChartData($profitability) {
        $labels = array();
        $revenue_data = array();
        $profit_data = array();
        
        foreach ($profitability as $item) {
            $labels[] = substr($item['name'], 0, 20) . '...';
            $revenue_data[] = (float)$item['total_revenue'];
            $profit_data[] = (float)$item['total_profit'];
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => 'الإيراد',
                    'data' => $revenue_data,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2
                ),
                array(
                    'label' => 'الربح',
                    'data' => $profit_data,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2
                )
            )
        );
    }
    
    /**
     * تحديث البيانات في الوقت الفعلي
     */
    public function refreshData() {
        $this->load->model('inventory/interactive_dashboard');
        
        $statistics = $this->model_inventory_interactive_dashboard->getGeneralStatistics();
        $alerts = $this->model_inventory_interactive_dashboard->getSmartAlerts();
        $kpis = $this->model_inventory_interactive_dashboard->getKPIs();
        
        $data = array(
            'statistics' => $statistics,
            'alerts' => $alerts,
            'kpis' => $kpis,
            'timestamp' => date('Y-m-d H:i:s')
        );
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }
    
    /**
     * تصدير تقرير شامل
     */
    public function exportReport() {
        $this->load->model('inventory/interactive_dashboard');
        
        $statistics = $this->model_inventory_interactive_dashboard->getGeneralStatistics();
        $categories = $this->model_inventory_interactive_dashboard->getInventoryByCategories(20);
        $top_products = $this->model_inventory_interactive_dashboard->getTopSellingProducts(20);
        $low_stock = $this->model_inventory_interactive_dashboard->getLowStockProducts(50);
        
        // إنشاء ملف Excel
        $filename = 'inventory_dashboard_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        
        // كتابة الإحصائيات العامة
        fputcsv($output, array('تقرير لوحة تحكم المخزون - ' . date('Y-m-d H:i:s')));
        fputcsv($output, array(''));
        fputcsv($output, array('الإحصائيات العامة'));
        fputcsv($output, array('إجمالي المنتجات', $statistics['total_products']));
        fputcsv($output, array('المنتجات المفعلة', $statistics['active_products']));
        fputcsv($output, array('المنتجات منخفضة المخزون', $statistics['low_stock_products']));
        fputcsv($output, array('المنتجات غير المتوفرة', $statistics['out_of_stock_products']));
        fputcsv($output, array('إجمالي قيمة المخزون', number_format($statistics['total_inventory_value'], 2)));
        
        // كتابة بيانات الفئات
        fputcsv($output, array(''));
        fputcsv($output, array('إحصائيات الفئات'));
        fputcsv($output, array('الفئة', 'عدد المنتجات', 'إجمالي الكمية', 'إجمالي القيمة'));
        
        foreach ($categories as $category) {
            fputcsv($output, array(
                $category['category_name'],
                $category['product_count'],
                $category['total_quantity'],
                number_format($category['total_value'], 2)
            ));
        }
        
        fclose($output);
        exit;
    }
}
