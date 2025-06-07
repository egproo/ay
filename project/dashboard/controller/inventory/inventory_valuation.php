<?php
/**
 * إدارة تقرير تقييم المخزون المتطور (Advanced Inventory Valuation Report Controller)
 * 
 * الهدف: توفير واجهة متطورة لتقييم المخزون بطريقة المتوسط المرجح
 * الميزات: تقييم WAC، مقارنات زمنية، تحليل الربحية، تقارير متعددة المستويات
 * التكامل: مع المحاسبة والتقارير المالية والتحليلات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryInventoryValuation extends Controller {
    
    private $error = array();
    
    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/inventory_valuation');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/inventory_valuation');
        $this->load->model('inventory/category');
        $this->load->model('inventory/manufacturer');
        $this->load->model('inventory/branch');
        
        // معالجة الطلبات
        $this->getList();
    }
    
    protected function getList() {
        // معالجة الفلاتر
        $filter_data = $this->getFilters();
        
        // إعداد الروابط
        $url = $this->buildUrl($filter_data);
        
        // إعداد البيانات الأساسية
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/inventory_valuation', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        // روابط الإجراءات
        $data['export_excel'] = $this->url->link('inventory/inventory_valuation/exportExcel', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['export_pdf'] = $this->url->link('inventory/inventory_valuation/exportPdf', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['print'] = $this->url->link('inventory/inventory_valuation/print', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['refresh'] = $this->url->link('inventory/inventory_valuation', 'user_token=' . $this->session->data['user_token'], true);
        $data['compare_dates'] = $this->url->link('inventory/inventory_valuation/compareDates', 'user_token=' . $this->session->data['user_token'], true);
        
        // الحصول على البيانات
        $inventory_valuation = array();
        $filter_data_with_pagination = $filter_data;
        $filter_data_with_pagination['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data_with_pagination['limit'] = $this->config->get('config_limit_admin');
        
        $results = $this->model_inventory_inventory_valuation->getInventoryValuation($filter_data_with_pagination);
        $total = $this->model_inventory_inventory_valuation->getTotalInventoryValuation($filter_data);
        
        foreach ($results as $result) {
            $inventory_valuation[] = array(
                'product_id'              => $result['product_id'],
                'product_name'            => $result['product_name'],
                'model'                   => $result['model'],
                'sku'                     => $result['sku'],
                'category_name'           => $result['category_name'],
                'manufacturer_name'       => $result['manufacturer_name'],
                'branch_name'             => $result['branch_name'],
                'branch_type'             => $this->language->get('text_branch_type_' . $result['branch_type']),
                'unit_name'               => $result['unit_name'],
                'unit_symbol'             => $result['unit_symbol'],
                'quantity'                => number_format($result['quantity'], 2),
                'quantity_raw'            => $result['quantity'],
                'average_cost'            => $this->currency->format($result['average_cost'], $this->config->get('config_currency')),
                'average_cost_raw'        => $result['average_cost'],
                'total_value'             => $this->currency->format($result['total_value'], $this->config->get('config_currency')),
                'total_value_raw'         => $result['total_value'],
                'selling_price'           => $this->currency->format($result['selling_price'], $this->config->get('config_currency')),
                'selling_price_raw'       => $result['selling_price'],
                'total_selling_value'     => $this->currency->format($result['total_selling_value'], $this->config->get('config_currency')),
                'total_selling_value_raw' => $result['total_selling_value'],
                'unit_profit'             => $this->currency->format($result['unit_profit'], $this->config->get('config_currency')),
                'unit_profit_raw'         => $result['unit_profit'],
                'total_profit'            => $this->currency->format($result['total_profit'], $this->config->get('config_currency')),
                'total_profit_raw'        => $result['total_profit'],
                'profit_percentage'       => number_format($result['profit_percentage'], 2) . '%',
                'profit_percentage_raw'   => $result['profit_percentage'],
                'profit_class'            => $this->getProfitClass($result['profit_percentage']),
                'stock_status'            => $result['stock_status'],
                'stock_status_text'       => $this->language->get('text_stock_status_' . $result['stock_status']),
                'stock_status_class'      => $this->getStockStatusClass($result['stock_status']),
                'historical_avg_cost'     => $this->currency->format($result['historical_avg_cost'], $this->config->get('config_currency')),
                'max_cost'                => $this->currency->format($result['max_cost'], $this->config->get('config_currency')),
                'min_cost'                => $this->currency->format($result['min_cost'], $this->config->get('config_currency')),
                'cost_variance'           => $result['max_cost'] - $result['min_cost'],
                'cost_variance_formatted' => $this->currency->format($result['max_cost'] - $result['min_cost'], $this->config->get('config_currency')),
                'last_movement_date'      => $result['last_movement_date'] ? date($this->language->get('date_format_short'), strtotime($result['last_movement_date'])) : $this->language->get('text_never'),
                'days_since_last_movement' => $result['days_since_last_movement'] ? $result['days_since_last_movement'] : 0,
                'total_movements'         => $result['total_movements'],
                'calculated_quantity'     => number_format($result['calculated_quantity'], 2),
                'quantity_difference'     => $result['quantity'] - $result['calculated_quantity'],
                'view_movements'          => $this->url->link('inventory/stock_movement', 'user_token=' . $this->session->data['user_token'] . '&filter_product_id=' . $result['product_id'] . '&filter_branch_id=' . $result['branch_id'], true),
                'edit_product'            => $this->url->link('inventory/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true)
            );
        }
        
        $data['inventory_valuation'] = $inventory_valuation;
        
        // الحصول على ملخص التقييم
        $summary = $this->model_inventory_inventory_valuation->getValuationSummary($filter_data);
        $data['summary'] = array(
            'total_products'        => number_format($summary['total_products']),
            'total_branches'        => number_format($summary['total_branches']),
            'total_quantity'        => number_format($summary['total_quantity'], 2),
            'total_cost_value'      => $this->currency->format($summary['total_cost_value'], $this->config->get('config_currency')),
            'total_cost_value_raw'  => $summary['total_cost_value'],
            'total_selling_value'   => $this->currency->format($summary['total_selling_value'], $this->config->get('config_currency')),
            'total_selling_value_raw' => $summary['total_selling_value'],
            'total_profit'          => $this->currency->format($summary['total_profit'], $this->config->get('config_currency')),
            'total_profit_raw'      => $summary['total_profit'],
            'avg_cost'              => $this->currency->format($summary['avg_cost'], $this->config->get('config_currency')),
            'avg_selling_price'     => $this->currency->format($summary['avg_selling_price'], $this->config->get('config_currency')),
            'avg_profit_percentage' => number_format($summary['avg_profit_percentage'], 2) . '%',
            'avg_profit_percentage_raw' => $summary['avg_profit_percentage'],
            'out_of_stock_count'    => number_format($summary['out_of_stock_count']),
            'low_stock_count'       => number_format($summary['low_stock_count']),
            'overstock_count'       => number_format($summary['overstock_count']),
            'highest_value_item'    => $this->currency->format($summary['highest_value_item'], $this->config->get('config_currency')),
            'lowest_value_item'     => $this->currency->format($summary['lowest_value_item'], $this->config->get('config_currency'))
        );
        
        // الحصول على التقييم حسب التصنيف
        $valuation_by_category = $this->model_inventory_inventory_valuation->getValuationByCategory($filter_data);
        $data['valuation_by_category'] = array();
        foreach ($valuation_by_category as $category) {
            $data['valuation_by_category'][] = array(
                'category_name'         => $category['category_name'],
                'total_products'        => number_format($category['total_products']),
                'total_quantity'        => number_format($category['total_quantity'], 2),
                'total_cost_value'      => $this->currency->format($category['total_cost_value'], $this->config->get('config_currency')),
                'total_cost_value_raw'  => $category['total_cost_value'],
                'total_selling_value'   => $this->currency->format($category['total_selling_value'], $this->config->get('config_currency')),
                'total_profit'          => $this->currency->format($category['total_profit'], $this->config->get('config_currency')),
                'avg_profit_percentage' => number_format($category['avg_profit_percentage'], 2) . '%',
                'percentage_of_total'   => $summary['total_cost_value'] > 0 ? number_format(($category['total_cost_value'] / $summary['total_cost_value']) * 100, 1) . '%' : '0%'
            );
        }
        
        // الحصول على التقييم حسب الفرع
        $valuation_by_branch = $this->model_inventory_inventory_valuation->getValuationByBranch($filter_data);
        $data['valuation_by_branch'] = array();
        foreach ($valuation_by_branch as $branch) {
            $data['valuation_by_branch'][] = array(
                'branch_name'           => $branch['branch_name'],
                'branch_type'           => $this->language->get('text_branch_type_' . $branch['branch_type']),
                'total_products'        => number_format($branch['total_products']),
                'total_quantity'        => number_format($branch['total_quantity'], 2),
                'total_cost_value'      => $this->currency->format($branch['total_cost_value'], $this->config->get('config_currency')),
                'total_cost_value_raw'  => $branch['total_cost_value'],
                'total_selling_value'   => $this->currency->format($branch['total_selling_value'], $this->config->get('config_currency')),
                'total_profit'          => $this->currency->format($branch['total_profit'], $this->config->get('config_currency')),
                'avg_profit_percentage' => number_format($branch['avg_profit_percentage'], 2) . '%',
                'percentage_of_total'   => $summary['total_cost_value'] > 0 ? number_format(($branch['total_cost_value'] / $summary['total_cost_value']) * 100, 1) . '%' : '0%'
            );
        }
        
        // الحصول على أعلى المنتجات قيمة
        $top_value_products = $this->model_inventory_inventory_valuation->getTopValueProducts(5, $filter_data);
        $data['top_value_products'] = array();
        foreach ($top_value_products as $product) {
            $data['top_value_products'][] = array(
                'product_name'          => $product['product_name'],
                'model'                 => $product['model'],
                'total_cost_value'      => $this->currency->format($product['total_cost_value'], $this->config->get('config_currency')),
                'total_selling_value'   => $this->currency->format($product['total_selling_value'], $this->config->get('config_currency')),
                'total_profit'          => $this->currency->format($product['total_profit'], $this->config->get('config_currency')),
                'total_quantity'        => number_format($product['total_quantity'], 2)
            );
        }
        
        // الحصول على أكثر المنتجات ربحية
        $most_profitable_products = $this->model_inventory_inventory_valuation->getMostProfitableProducts(5, $filter_data);
        $data['most_profitable_products'] = array();
        foreach ($most_profitable_products as $product) {
            $data['most_profitable_products'][] = array(
                'product_name'          => $product['product_name'],
                'model'                 => $product['model'],
                'total_cost_value'      => $this->currency->format($product['total_cost_value'], $this->config->get('config_currency')),
                'total_profit'          => $this->currency->format($product['total_profit'], $this->config->get('config_currency')),
                'avg_profit_percentage' => number_format($product['avg_profit_percentage'], 2) . '%',
                'total_quantity'        => number_format($product['total_quantity'], 2)
            );
        }
        
        // إعداد الفلاتر للعرض
        $this->setupFiltersForDisplay($data, $filter_data);
        
        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/inventory_valuation', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) > ($total - $this->config->get('config_limit_admin'))) ? $total : ((($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $total, ceil($total / $this->config->get('config_limit_admin')));
        
        // إعداد الترتيب
        $data['sort'] = $filter_data['sort'];
        $data['order'] = $filter_data['order'];
        
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
        
        $this->response->setOutput($this->load->view('inventory/inventory_valuation_list', $data));
    }
    
    /**
     * مقارنة التقييم بين تاريخين
     */
    public function compareDates() {
        $this->load->language('inventory/inventory_valuation');
        $this->load->model('inventory/inventory_valuation');
        
        $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : date('Y-m-d', strtotime('-1 month'));
        $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : date('Y-m-d');
        
        $filter_data = $this->getFilters();
        $comparison = $this->model_inventory_inventory_valuation->compareValuation($date_from, $date_to, $filter_data);
        
        $data['comparison'] = $comparison;
        
        $this->response->setOutput($this->load->view('inventory/inventory_valuation_comparison', $data));
    }
    
    /**
     * معالجة الفلاتر
     */
    private function getFilters() {
        $filters = array(
            'filter_product_name'         => '',
            'filter_category_id'          => '',
            'filter_manufacturer_id'      => '',
            'filter_branch_id'            => '',
            'filter_branch_type'          => '',
            'filter_stock_status'         => '',
            'filter_min_value'            => '',
            'filter_max_value'            => '',
            'filter_min_profit_percentage' => '',
            'filter_max_profit_percentage' => '',
            'valuation_date'              => date('Y-m-d'),
            'sort'                        => 'total_value',
            'order'                       => 'DESC',
            'page'                        => 1
        );
        
        foreach ($filters as $key => $default) {
            if (isset($this->request->get[$key])) {
                $filters[$key] = $this->request->get[$key];
            }
        }
        
        return $filters;
    }
    
    /**
     * بناء رابط URL مع الفلاتر
     */
    private function buildUrl($filters) {
        $url = '';
        
        foreach ($filters as $key => $value) {
            if ($value !== '' && $key !== 'page') {
                $url .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
            }
        }
        
        return $url;
    }
    
    /**
     * إعداد الفلاتر للعرض
     */
    private function setupFiltersForDisplay(&$data, $filters) {
        // نسخ الفلاتر للعرض
        foreach ($filters as $key => $value) {
            $data[$key] = $value;
        }
        
        // الحصول على قوائم الفلاتر
        $data['categories'] = $this->model_inventory_category->getCategories();
        $data['manufacturers'] = $this->model_inventory_manufacturer->getManufacturers();
        $data['branches'] = $this->model_inventory_branch->getBranches();
        
        // خيارات حالة المخزون
        $data['stock_status_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'normal', 'text' => $this->language->get('text_stock_status_normal')),
            array('value' => 'low_stock', 'text' => $this->language->get('text_stock_status_low_stock')),
            array('value' => 'out_of_stock', 'text' => $this->language->get('text_stock_status_out_of_stock')),
            array('value' => 'overstock', 'text' => $this->language->get('text_stock_status_overstock'))
        );
        
        // خيارات نوع الفرع
        $data['branch_type_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'store', 'text' => $this->language->get('text_branch_type_store')),
            array('value' => 'warehouse', 'text' => $this->language->get('text_branch_type_warehouse'))
        );
    }
    
    /**
     * الحصول على فئة CSS لحالة المخزون
     */
    private function getStockStatusClass($status) {
        switch ($status) {
            case 'out_of_stock':
                return 'danger';
            case 'low_stock':
                return 'warning';
            case 'overstock':
                return 'info';
            case 'normal':
            default:
                return 'success';
        }
    }
    
    /**
     * الحصول على فئة CSS للربحية
     */
    private function getProfitClass($profit_percentage) {
        if ($profit_percentage >= 50) {
            return 'success';
        } elseif ($profit_percentage >= 20) {
            return 'info';
        } elseif ($profit_percentage >= 0) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
    
    /**
     * تصدير إلى Excel
     */
    public function exportExcel() {
        $this->load->language('inventory/inventory_valuation');
        $this->load->model('inventory/inventory_valuation');
        
        $filter_data = $this->getFilters();
        $results = $this->model_inventory_inventory_valuation->exportToExcel($filter_data);
        
        // إنشاء ملف Excel
        $filename = 'inventory_valuation_' . $filter_data['valuation_date'] . '_' . date('H-i-s') . '.csv';
        
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        
        // كتابة العناوين
        $headers = array(
            $this->language->get('column_product_name'),
            $this->language->get('column_model'),
            $this->language->get('column_category'),
            $this->language->get('column_branch'),
            $this->language->get('column_quantity'),
            $this->language->get('column_average_cost'),
            $this->language->get('column_total_value'),
            $this->language->get('column_selling_price'),
            $this->language->get('column_total_selling_value'),
            $this->language->get('column_total_profit'),
            $this->language->get('column_profit_percentage'),
            $this->language->get('column_stock_status')
        );
        
        fputcsv($output, $headers);
        
        // كتابة البيانات
        foreach ($results as $result) {
            $row = array(
                $result['product_name'],
                $result['model'],
                $result['category_name'],
                $result['branch_name'],
                $result['quantity'],
                $result['average_cost'],
                $result['total_value'],
                $result['selling_price'],
                $result['total_selling_value'],
                $result['total_profit'],
                $result['profit_percentage'],
                $this->language->get('text_stock_status_' . $result['stock_status'])
            );
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
