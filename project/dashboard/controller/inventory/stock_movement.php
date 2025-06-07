<?php
/**
 * إدارة سجل حركة المخزون المتطور (Advanced Stock Movement Ledger Controller)
 * 
 * الهدف: توفير واجهة متطورة لعرض وتتبع جميع حركات المخزون
 * الميزات: كارت صنف تفصيلي، فلاتر متقدمة، تتبع الدفعات، تقارير شاملة
 * التكامل: مع المحاسبة والمشتريات والمبيعات والتحويلات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryStockMovement extends Controller {
    
    private $error = array();
    
    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/stock_movement');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/stock_movement');
        $this->load->model('inventory/category');
        $this->load->model('inventory/manufacturer');
        $this->load->model('inventory/branch');
        $this->load->model('user/user');
        
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
            'href' => $this->url->link('inventory/stock_movement', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        // روابط الإجراءات
        $data['export_excel'] = $this->url->link('inventory/stock_movement/exportExcel', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['export_pdf'] = $this->url->link('inventory/stock_movement/exportPdf', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['print'] = $this->url->link('inventory/stock_movement/print', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['refresh'] = $this->url->link('inventory/stock_movement', 'user_token=' . $this->session->data['user_token'], true);
        $data['lot_report'] = $this->url->link('inventory/stock_movement/lotReport', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['expiring_lots'] = $this->url->link('inventory/stock_movement/expiringLots', 'user_token=' . $this->session->data['user_token'], true);
        
        // الحصول على البيانات
        $stock_movements = array();
        $filter_data_with_pagination = $filter_data;
        $filter_data_with_pagination['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data_with_pagination['limit'] = $this->config->get('config_limit_admin');
        
        $results = $this->model_inventory_stock_movement->getStockMovements($filter_data_with_pagination);
        $total = $this->model_inventory_stock_movement->getTotalStockMovements($filter_data);
        
        foreach ($results as $result) {
            $stock_movements[] = array(
                'movement_id'           => $result['movement_id'],
                'product_id'            => $result['product_id'],
                'product_name'          => $result['product_name'],
                'model'                 => $result['model'],
                'sku'                   => $result['sku'],
                'category_name'         => $result['category_name'],
                'manufacturer_name'     => $result['manufacturer_name'],
                'branch_name'           => $result['branch_name'],
                'branch_type'           => $this->language->get('text_branch_type_' . $result['branch_type']),
                'movement_type'         => $result['movement_type'],
                'movement_type_text'    => $result['movement_type_text'],
                'movement_type_class'   => $this->getMovementTypeClass($result['movement_type']),
                'reference_type'        => $result['reference_type'],
                'reference_type_text'   => $result['reference_type_text'],
                'reference_id'          => $result['reference_id'],
                'reference_number'      => $result['reference_number'],
                'lot_number'            => $result['lot_number'],
                'expiry_date'           => $result['expiry_date'] ? date($this->language->get('date_format_short'), strtotime($result['expiry_date'])) : '',
                'expiry_date_raw'       => $result['expiry_date'],
                'expiry_status'         => $this->getExpiryStatus($result['expiry_date']),
                'unit_name'             => $result['unit_name'],
                'unit_symbol'           => $result['unit_symbol'],
                'quantity_in'           => $result['quantity_in'] > 0 ? number_format($result['quantity_in'], 2) : '',
                'quantity_in_raw'       => $result['quantity_in'],
                'quantity_out'          => $result['quantity_out'] > 0 ? number_format($result['quantity_out'], 2) : '',
                'quantity_out_raw'      => $result['quantity_out'],
                'net_quantity'          => number_format($result['net_quantity'], 2),
                'net_quantity_raw'      => $result['net_quantity'],
                'running_balance'       => number_format($result['running_balance'], 2),
                'running_balance_raw'   => $result['running_balance'],
                'unit_cost'             => $this->currency->format($result['unit_cost'], $this->config->get('config_currency')),
                'unit_cost_raw'         => $result['unit_cost'],
                'total_cost'            => $this->currency->format($result['total_cost'], $this->config->get('config_currency')),
                'total_cost_raw'        => $result['total_cost'],
                'average_cost_before'   => $this->currency->format($result['average_cost_before'], $this->config->get('config_currency')),
                'average_cost_after'    => $this->currency->format($result['average_cost_after'], $this->config->get('config_currency')),
                'cost_change'           => $result['average_cost_after'] - $result['average_cost_before'],
                'cost_change_formatted' => $this->currency->format($result['average_cost_after'] - $result['average_cost_before'], $this->config->get('config_currency')),
                'notes'                 => $result['notes'],
                'user_name'             => $result['user_name'],
                'date_added'            => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
                'date_added_raw'        => $result['date_added'],
                'view_reference'        => $this->getViewReferenceLink($result['reference_type'], $result['reference_id']),
                'product_card'          => $this->url->link('inventory/stock_movement/productCard', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . '&branch_id=' . $result['branch_id'], true)
            );
        }
        
        $data['stock_movements'] = $stock_movements;
        
        // الحصول على ملخص الحركات
        $summary = $this->model_inventory_stock_movement->getMovementSummary($filter_data);
        $data['summary'] = array(
            'total_movements'       => number_format($summary['total_movements']),
            'total_products'        => number_format($summary['total_products']),
            'total_branches'        => number_format($summary['total_branches']),
            'total_quantity_in'     => number_format($summary['total_quantity_in'], 2),
            'total_quantity_out'    => number_format($summary['total_quantity_out'], 2),
            'net_quantity'          => number_format($summary['total_quantity_in'] - $summary['total_quantity_out'], 2),
            'total_value'           => $this->currency->format($summary['total_value'], $this->config->get('config_currency')),
            'avg_unit_cost'         => $this->currency->format($summary['avg_unit_cost'], $this->config->get('config_currency')),
            'total_lots'            => number_format($summary['total_lots']),
            'movements_with_expiry' => number_format($summary['movements_with_expiry'])
        );
        
        // الحصول على تحليل الحركات حسب النوع
        $movements_by_type = $this->model_inventory_stock_movement->getMovementsByType($filter_data);
        $data['movements_by_type'] = array();
        foreach ($movements_by_type as $movement_type) {
            $data['movements_by_type'][] = array(
                'movement_type'      => $movement_type['movement_type'],
                'movement_type_text' => $this->getMovementTypeText($movement_type['movement_type']),
                'movement_count'     => number_format($movement_type['movement_count']),
                'total_quantity_in'  => number_format($movement_type['total_quantity_in'], 2),
                'total_quantity_out' => number_format($movement_type['total_quantity_out'], 2),
                'total_value'        => $this->currency->format($movement_type['total_value'], $this->config->get('config_currency')),
                'avg_unit_cost'      => $this->currency->format($movement_type['avg_unit_cost'], $this->config->get('config_currency'))
            );
        }
        
        // الحصول على الدفعات المنتهية الصلاحية قريباً
        $expiring_lots = $this->model_inventory_stock_movement->getExpiringLots(30, $filter_data);
        $data['expiring_lots'] = array();
        foreach ($expiring_lots as $lot) {
            $data['expiring_lots'][] = array(
                'product_name'       => $lot['product_name'],
                'model'              => $lot['model'],
                'branch_name'        => $lot['branch_name'],
                'lot_number'         => $lot['lot_number'],
                'expiry_date'        => date($this->language->get('date_format_short'), strtotime($lot['expiry_date'])),
                'remaining_quantity' => number_format($lot['remaining_quantity'], 2),
                'days_to_expiry'     => $lot['days_to_expiry'],
                'urgency_class'      => $this->getExpiryUrgencyClass($lot['days_to_expiry'])
            );
        }
        
        // إعداد الفلاتر للعرض
        $this->setupFiltersForDisplay($data, $filter_data);
        
        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/stock_movement', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
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
        
        $this->response->setOutput($this->load->view('inventory/stock_movement_list', $data));
    }
    
    /**
     * عرض كارت الصنف لمنتج محدد
     */
    public function productCard() {
        $this->load->language('inventory/stock_movement');
        $this->load->model('inventory/stock_movement');
        
        $product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
        $branch_id = isset($this->request->get['branch_id']) ? (int)$this->request->get['branch_id'] : 0;
        
        if (!$product_id) {
            $this->session->data['error'] = $this->language->get('error_product_required');
            $this->response->redirect($this->url->link('inventory/stock_movement', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $filter_data = $this->getFilters();
        $movements = $this->model_inventory_stock_movement->getProductCard($product_id, $branch_id, $filter_data);
        
        $data['movements'] = $movements;
        $data['product_id'] = $product_id;
        $data['branch_id'] = $branch_id;
        
        $this->response->setOutput($this->load->view('inventory/stock_movement_card', $data));
    }
    
    /**
     * معالجة الفلاتر
     */
    private function getFilters() {
        $filters = array(
            'filter_product_id'       => '',
            'filter_product_name'     => '',
            'filter_category_id'      => '',
            'filter_manufacturer_id'  => '',
            'filter_branch_id'        => '',
            'filter_branch_type'      => '',
            'filter_movement_type'    => '',
            'filter_reference_type'   => '',
            'filter_reference_number' => '',
            'filter_lot_number'       => '',
            'filter_user_id'          => '',
            'filter_date_from'        => '',
            'filter_date_to'          => '',
            'filter_has_expiry'       => '',
            'filter_expiry_from'      => '',
            'filter_expiry_to'        => '',
            'sort'                    => 'pm.date_added',
            'order'                   => 'DESC',
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
        $data['users'] = $this->model_user_user->getUsers();
        
        // خيارات نوع الحركة
        $movement_types = $this->model_inventory_stock_movement->getMovementTypes();
        $data['movement_type_options'] = array();
        $data['movement_type_options'][] = array('value' => '', 'text' => $this->language->get('text_all'));
        foreach ($movement_types as $key => $value) {
            $data['movement_type_options'][] = array('value' => $key, 'text' => $value);
        }
        
        // خيارات نوع المرجع
        $reference_types = $this->model_inventory_stock_movement->getReferenceTypes();
        $data['reference_type_options'] = array();
        $data['reference_type_options'][] = array('value' => '', 'text' => $this->language->get('text_all'));
        foreach ($reference_types as $key => $value) {
            $data['reference_type_options'][] = array('value' => $key, 'text' => $value);
        }
        
        // خيارات نوع الفرع
        $data['branch_type_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'store', 'text' => $this->language->get('text_branch_type_store')),
            array('value' => 'warehouse', 'text' => $this->language->get('text_branch_type_warehouse'))
        );
        
        // خيارات تتبع الصلاحية
        $data['expiry_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => '1', 'text' => $this->language->get('text_with_expiry')),
            array('value' => '0', 'text' => $this->language->get('text_without_expiry'))
        );
    }
    
    /**
     * الحصول على فئة CSS لنوع الحركة
     */
    private function getMovementTypeClass($type) {
        switch ($type) {
            case 'purchase':
            case 'transfer_in':
            case 'adjustment_in':
            case 'production_in':
            case 'return_in':
            case 'opening_balance':
                return 'success';
            case 'sale':
            case 'transfer_out':
            case 'adjustment_out':
            case 'production_out':
            case 'return_out':
                return 'danger';
            case 'physical_count':
                return 'info';
            default:
                return 'default';
        }
    }
    
    /**
     * الحصول على نص نوع الحركة
     */
    private function getMovementTypeText($type) {
        $types = $this->model_inventory_stock_movement->getMovementTypes();
        return isset($types[$type]) ? $types[$type] : $type;
    }
    
    /**
     * الحصول على حالة انتهاء الصلاحية
     */
    private function getExpiryStatus($expiry_date) {
        if (!$expiry_date) {
            return '';
        }
        
        $days_to_expiry = (strtotime($expiry_date) - time()) / (60 * 60 * 24);
        
        if ($days_to_expiry < 0) {
            return 'expired';
        } elseif ($days_to_expiry <= 7) {
            return 'critical';
        } elseif ($days_to_expiry <= 30) {
            return 'warning';
        } else {
            return 'normal';
        }
    }
    
    /**
     * الحصول على فئة CSS لحالة انتهاء الصلاحية
     */
    private function getExpiryUrgencyClass($days_to_expiry) {
        if ($days_to_expiry < 0) {
            return 'danger';
        } elseif ($days_to_expiry <= 7) {
            return 'danger';
        } elseif ($days_to_expiry <= 30) {
            return 'warning';
        } else {
            return 'success';
        }
    }
    
    /**
     * الحصول على رابط عرض المرجع
     */
    private function getViewReferenceLink($reference_type, $reference_id) {
        switch ($reference_type) {
            case 'purchase_order':
                return $this->url->link('purchase/order/view', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $reference_id, true);
            case 'sale_order':
                return $this->url->link('sale/order/view', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $reference_id, true);
            case 'stock_transfer':
                return $this->url->link('inventory/transfer/view', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $reference_id, true);
            case 'stock_adjustment':
                return $this->url->link('inventory/adjustment/view', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $reference_id, true);
            default:
                return '';
        }
    }
    
    /**
     * تصدير إلى Excel
     */
    public function exportExcel() {
        $this->load->language('inventory/stock_movement');
        $this->load->model('inventory/stock_movement');
        
        $filter_data = $this->getFilters();
        $results = $this->model_inventory_stock_movement->exportToExcel($filter_data);
        
        // إنشاء ملف Excel
        $filename = 'stock_movements_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        
        // كتابة العناوين
        $headers = array(
            $this->language->get('column_date'),
            $this->language->get('column_product_name'),
            $this->language->get('column_branch'),
            $this->language->get('column_movement_type'),
            $this->language->get('column_reference'),
            $this->language->get('column_lot_number'),
            $this->language->get('column_quantity_in'),
            $this->language->get('column_quantity_out'),
            $this->language->get('column_unit_cost'),
            $this->language->get('column_total_cost'),
            $this->language->get('column_running_balance'),
            $this->language->get('column_user'),
            $this->language->get('column_notes')
        );
        
        fputcsv($output, $headers);
        
        // كتابة البيانات
        foreach ($results as $result) {
            $row = array(
                $result['date_added'],
                $result['product_name'],
                $result['branch_name'],
                $result['movement_type_text'],
                $result['reference_number'],
                $result['lot_number'],
                $result['quantity_in'],
                $result['quantity_out'],
                $result['unit_cost'],
                $result['total_cost'],
                $result['running_balance'],
                $result['user_name'],
                $result['notes']
            );
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
