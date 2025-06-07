<?php
/**
 * إدارة استعلام الأرصدة الحالية المتطور (Advanced Current Stock Levels Controller)
 * 
 * الهدف: توفير واجهة متطورة لاستعلام الأرصدة مع فلاتر وتحليلات متقدمة
 * الميزات: فلاتر متعددة، تصدير، تقارير، تنبيهات ذكية
 * التكامل: مع المحاسبة والتقارير والتنبيهات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryStockLevels extends Controller {
    
    private $error = array();
    
    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/stock_levels');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/stock_levels');
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
            'href' => $this->url->link('inventory/stock_levels', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        // روابط الإجراءات
        $data['export_excel'] = $this->url->link('inventory/stock_levels/exportExcel', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['export_pdf'] = $this->url->link('inventory/stock_levels/exportPdf', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['print'] = $this->url->link('inventory/stock_levels/print', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['refresh'] = $this->url->link('inventory/stock_levels', 'user_token=' . $this->session->data['user_token'], true);
        
        // الحصول على البيانات
        $stock_levels = array();
        $filter_data_with_pagination = $filter_data;
        $filter_data_with_pagination['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data_with_pagination['limit'] = $this->config->get('config_limit_admin');
        
        $results = $this->model_inventory_stock_levels->getCurrentStockLevels($filter_data_with_pagination);
        $total = $this->model_inventory_stock_levels->getTotalStockLevels($filter_data);
        
        foreach ($results as $result) {
            $stock_levels[] = array(
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
                'minimum_quantity'        => number_format($result['minimum_quantity'], 2),
                'maximum_quantity'        => number_format($result['maximum_quantity'], 2),
                'stock_status'            => $result['stock_status'],
                'stock_status_text'       => $this->language->get('text_stock_status_' . $result['stock_status']),
                'stock_status_class'      => $this->getStockStatusClass($result['stock_status']),
                'selling_price'           => $this->currency->format($result['selling_price'], $this->config->get('config_currency')),
                'profit_margin'           => $this->currency->format($result['profit_margin'], $this->config->get('config_currency')),
                'profit_percentage'       => number_format($result['profit_percentage'], 2) . '%',
                'profit_percentage_raw'   => $result['profit_percentage'],
                'movements_last_30_days'  => $result['movements_last_30_days'],
                'last_movement_date'      => $result['last_movement_date'] ? date($this->language->get('date_format_short'), strtotime($result['last_movement_date'])) : $this->language->get('text_never'),
                'days_since_last_movement' => $result['days_since_last_movement'] ? $result['days_since_last_movement'] : 0,
                'product_status'          => $result['product_status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'view_movements'          => $this->url->link('inventory/stock_movement', 'user_token=' . $this->session->data['user_token'] . '&filter_product_id=' . $result['product_id'] . '&filter_branch_id=' . $result['branch_id'], true),
                'edit_product'            => $this->url->link('inventory/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true)
            );
        }
        
        $data['stock_levels'] = $stock_levels;
        
        // الحصول على ملخص الأرصدة
        $summary = $this->model_inventory_stock_levels->getStockSummary($filter_data);
        $data['summary'] = array(
            'total_products'     => number_format($summary['total_products']),
            'total_branches'     => number_format($summary['total_branches']),
            'total_quantity'     => number_format($summary['total_quantity'], 2),
            'total_value'        => $this->currency->format($summary['total_value'], $this->config->get('config_currency')),
            'out_of_stock_count' => number_format($summary['out_of_stock_count']),
            'low_stock_count'    => number_format($summary['low_stock_count']),
            'overstock_count'    => number_format($summary['overstock_count']),
            'avg_cost'           => $this->currency->format($summary['avg_cost'], $this->config->get('config_currency')),
            'avg_quantity'       => number_format($summary['avg_quantity'], 2)
        );
        
        // الحصول على أعلى المنتجات قيمة
        $top_products = $this->model_inventory_stock_levels->getTopValueProducts(5, $filter_data);
        $data['top_value_products'] = array();
        foreach ($top_products as $product) {
            $data['top_value_products'][] = array(
                'product_name'  => $product['product_name'],
                'model'         => $product['model'],
                'total_value'   => $this->currency->format($product['total_value'], $this->config->get('config_currency')),
                'total_quantity' => number_format($product['total_quantity'], 2)
            );
        }
        
        // الحصول على المنتجات بطيئة الحركة
        $slow_products = $this->model_inventory_stock_levels->getSlowMovingProducts(90, 5, $filter_data);
        $data['slow_moving_products'] = array();
        foreach ($slow_products as $product) {
            $data['slow_moving_products'][] = array(
                'product_name'             => $product['product_name'],
                'model'                    => $product['model'],
                'quantity'                 => number_format($product['quantity'], 2),
                'total_value'              => $this->currency->format($product['total_value'], $this->config->get('config_currency')),
                'days_since_last_movement' => $product['days_since_last_movement']
            );
        }
        
        // إعداد الفلاتر للعرض
        $this->setupFiltersForDisplay($data, $filter_data);
        
        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/stock_levels', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
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
        
        $this->response->setOutput($this->load->view('inventory/stock_levels_list', $data));
    }
    
    /**
     * معالجة الفلاتر
     */
    private function getFilters() {
        $filters = array(
            'filter_product_name'     => '',
            'filter_category_id'      => '',
            'filter_manufacturer_id'  => '',
            'filter_branch_id'        => '',
            'filter_branch_type'      => '',
            'filter_stock_status'     => '',
            'filter_product_status'   => '',
            'filter_min_quantity'     => '',
            'filter_max_quantity'     => '',
            'filter_min_value'        => '',
            'filter_max_value'        => '',
            'filter_slow_moving_days' => '',
            'sort'                    => 'pd.name',
            'order'                   => 'ASC',
            'page'                    => 1
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
     * تصدير إلى Excel
     */
    public function exportExcel() {
        $this->load->language('inventory/stock_levels');
        $this->load->model('inventory/stock_levels');
        
        $filter_data = $this->getFilters();
        $results = $this->model_inventory_stock_levels->exportToExcel($filter_data);
        
        // إنشاء ملف Excel
        $filename = 'stock_levels_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        
        // كتابة العناوين
        $headers = array(
            $this->language->get('column_product_name'),
            $this->language->get('column_model'),
            $this->language->get('column_sku'),
            $this->language->get('column_category'),
            $this->language->get('column_manufacturer'),
            $this->language->get('column_branch'),
            $this->language->get('column_unit'),
            $this->language->get('column_quantity'),
            $this->language->get('column_average_cost'),
            $this->language->get('column_total_value'),
            $this->language->get('column_stock_status'),
            $this->language->get('column_last_movement')
        );
        
        fputcsv($output, $headers);
        
        // كتابة البيانات
        foreach ($results as $result) {
            $row = array(
                $result['product_name'],
                $result['model'],
                $result['sku'],
                $result['category_name'],
                $result['manufacturer_name'],
                $result['branch_name'],
                $result['unit_name'],
                $result['quantity'],
                $result['average_cost'],
                $result['total_value'],
                $this->language->get('text_stock_status_' . $result['stock_status']),
                $result['last_movement_date']
            );
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * طباعة التقرير
     */
    public function print() {
        $this->load->language('inventory/stock_levels');
        $this->load->model('inventory/stock_levels');
        
        $filter_data = $this->getFilters();
        $results = $this->model_inventory_stock_levels->exportToExcel($filter_data);
        $summary = $this->model_inventory_stock_levels->getStockSummary($filter_data);
        
        $data['results'] = $results;
        $data['summary'] = $summary;
        $data['title'] = $this->language->get('heading_title');
        $data['date'] = date($this->language->get('date_format_long'));
        
        $this->response->setOutput($this->load->view('inventory/stock_levels_print', $data));
    }
}
