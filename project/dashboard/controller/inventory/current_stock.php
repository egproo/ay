<?php
/**
 * AYM ERP - Inventory Current Stock Controller
 * 
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerInventoryCurrentStock extends Controller {
    
    private $error = array();
    
    public function index() {
        $this->load->language('inventory/current_stock');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/current_stock', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['export_excel'] = $this->url->link('inventory/current_stock/export_excel', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_pdf'] = $this->url->link('inventory/current_stock/export_pdf', 'user_token=' . $this->session->data['user_token'], true);
        $data['print'] = $this->url->link('inventory/current_stock/print', 'user_token=' . $this->session->data['user_token'], true);
        
        $this->getList($data);
    }
    
    public function analytics() {
        $this->load->language('inventory/current_stock');
        $this->load->model('inventory/current_stock');
        
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_analytics'));
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/current_stock', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_analytics'),
            'href' => $this->url->link('inventory/current_stock/analytics', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Get analytics data
        $data['stock_summary'] = $this->model_inventory_current_stock->getStockSummary();
        $data['category_analysis'] = $this->model_inventory_current_stock->getCategoryAnalysis();
        $data['warehouse_analysis'] = $this->model_inventory_current_stock->getWarehouseAnalysis();
        $data['valuation_analysis'] = $this->model_inventory_current_stock->getValuationAnalysis();
        $data['movement_trends'] = $this->model_inventory_current_stock->getMovementTrends();
        $data['low_stock_alerts'] = $this->model_inventory_current_stock->getLowStockAlerts();
        $data['overstock_alerts'] = $this->model_inventory_current_stock->getOverstockAlerts();
        $data['aging_analysis'] = $this->model_inventory_current_stock->getAgingAnalysis();
        
        $data['back'] = $this->url->link('inventory/current_stock', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/current_stock_analytics', $data));
    }
    
    public function export_excel() {
        $this->load->language('inventory/current_stock');
        $this->load->model('inventory/current_stock');
        
        // Set filters from request
        $filter_data = $this->getFilterData();
        
        $results = $this->model_inventory_current_stock->getCurrentStock($filter_data);
        
        // Create Excel file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="current_stock_' . date('Y-m-d_H-i-s') . '.xls"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        $headers = array(
            $this->language->get('column_product_name'),
            $this->language->get('column_sku'),
            $this->language->get('column_category'),
            $this->language->get('column_warehouse'),
            $this->language->get('column_current_stock'),
            $this->language->get('column_reserved_stock'),
            $this->language->get('column_available_stock'),
            $this->language->get('column_unit_cost'),
            $this->language->get('column_total_value'),
            $this->language->get('column_reorder_level'),
            $this->language->get('column_max_level'),
            $this->language->get('column_last_movement'),
            $this->language->get('column_status')
        );
        
        fputcsv($output, $headers);
        
        // Data rows
        foreach ($results as $result) {
            $row = array(
                $result['product_name'],
                $result['sku'],
                $result['category_name'],
                $result['warehouse_name'],
                $result['current_stock'],
                $result['reserved_stock'],
                $result['available_stock'],
                $result['unit_cost'],
                $result['total_value'],
                $result['reorder_level'],
                $result['max_level'],
                $result['last_movement_date'],
                $result['status_text']
            );
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    public function export_pdf() {
        $this->load->language('inventory/current_stock');
        $this->load->model('inventory/current_stock');
        
        // Set filters from request
        $filter_data = $this->getFilterData();
        
        $results = $this->model_inventory_current_stock->getCurrentStock($filter_data);
        
        // Generate PDF
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');
        
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('AYM ERP');
        $pdf->SetAuthor('AYM ERP');
        $pdf->SetTitle($this->language->get('heading_title'));
        
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 10);
        
        $html = '<h1>' . $this->language->get('heading_title') . '</h1>';
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<tr style="background-color: #f0f0f0;">';
        $html .= '<th>' . $this->language->get('column_product_name') . '</th>';
        $html .= '<th>' . $this->language->get('column_sku') . '</th>';
        $html .= '<th>' . $this->language->get('column_current_stock') . '</th>';
        $html .= '<th>' . $this->language->get('column_available_stock') . '</th>';
        $html .= '<th>' . $this->language->get('column_total_value') . '</th>';
        $html .= '<th>' . $this->language->get('column_status') . '</th>';
        $html .= '</tr>';
        
        foreach ($results as $result) {
            $html .= '<tr>';
            $html .= '<td>' . $result['product_name'] . '</td>';
            $html .= '<td>' . $result['sku'] . '</td>';
            $html .= '<td>' . $result['current_stock'] . '</td>';
            $html .= '<td>' . $result['available_stock'] . '</td>';
            $html .= '<td>' . $result['total_value'] . '</td>';
            $html .= '<td>' . $result['status_text'] . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('current_stock_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
        exit;
    }
    
    public function print() {
        $this->load->language('inventory/current_stock');
        $this->load->model('inventory/current_stock');
        
        // Set filters from request
        $filter_data = $this->getFilterData();
        
        $data['results'] = $this->model_inventory_current_stock->getCurrentStock($filter_data);
        $data['filter_data'] = $filter_data;
        
        $this->response->setOutput($this->load->view('inventory/current_stock_print', $data));
    }
    
    public function autocomplete() {
        $json = array();
        
        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog/product');
            
            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start'       => 0,
                'limit'       => 5
            );
            
            $products = $this->model_catalog_product->getProducts($filter_data);
            
            foreach ($products as $product) {
                $json[] = array(
                    'product_id'  => $product['product_id'],
                    'name'        => strip_tags(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8')),
                    'model'       => $product['model'],
                    'sku'         => $product['sku'],
                    'quantity'    => $product['quantity']
                );
            }
        }
        
        $sort_order = array();
        
        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }
        
        array_multisort($sort_order, SORT_ASC, $json);
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function update_reorder_levels() {
        $this->load->language('inventory/current_stock');
        $this->load->model('inventory/current_stock');
        
        $json = array();
        
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            if (isset($this->request->post['reorder_levels'])) {
                $this->model_inventory_current_stock->updateReorderLevels($this->request->post['reorder_levels']);
                $json['success'] = $this->language->get('text_success_reorder_update');
            } else {
                $json['error'] = $this->language->get('error_no_data');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    protected function getList(&$data = array()) {
        if (isset($this->request->get['filter_product_name'])) {
            $filter_product_name = $this->request->get['filter_product_name'];
        } else {
            $filter_product_name = '';
        }
        
        if (isset($this->request->get['filter_sku'])) {
            $filter_sku = $this->request->get['filter_sku'];
        } else {
            $filter_sku = '';
        }
        
        if (isset($this->request->get['filter_category_id'])) {
            $filter_category_id = $this->request->get['filter_category_id'];
        } else {
            $filter_category_id = '';
        }
        
        if (isset($this->request->get['filter_warehouse_id'])) {
            $filter_warehouse_id = $this->request->get['filter_warehouse_id'];
        } else {
            $filter_warehouse_id = '';
        }
        
        if (isset($this->request->get['filter_stock_status'])) {
            $filter_stock_status = $this->request->get['filter_stock_status'];
        } else {
            $filter_stock_status = '';
        }
        
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'pd.name';
        }
        
        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }
        
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }
        
        $url = '';
        
        if (isset($this->request->get['filter_product_name'])) {
            $url .= '&filter_product_name=' . urlencode(html_entity_decode($this->request->get['filter_product_name'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_sku'])) {
            $url .= '&filter_sku=' . urlencode(html_entity_decode($this->request->get['filter_sku'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_category_id'])) {
            $url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
        }
        
        if (isset($this->request->get['filter_warehouse_id'])) {
            $url .= '&filter_warehouse_id=' . $this->request->get['filter_warehouse_id'];
        }
        
        if (isset($this->request->get['filter_stock_status'])) {
            $url .= '&filter_stock_status=' . $this->request->get['filter_stock_status'];
        }
        
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        
        $data['stocks'] = array();
        
        $filter_data = array(
            'filter_product_name' => $filter_product_name,
            'filter_sku'         => $filter_sku,
            'filter_category_id' => $filter_category_id,
            'filter_warehouse_id' => $filter_warehouse_id,
            'filter_stock_status' => $filter_stock_status,
            'sort'               => $sort,
            'order'              => $order,
            'start'              => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'              => $this->config->get('config_limit_admin')
        );
        
        $this->load->model('inventory/current_stock');
        
        $stock_total = $this->model_inventory_current_stock->getTotalCurrentStock($filter_data);
        
        $results = $this->model_inventory_current_stock->getCurrentStock($filter_data);
        
        foreach ($results as $result) {
            $data['stocks'][] = array(
                'product_id'       => $result['product_id'],
                'product_name'     => $result['product_name'],
                'sku'              => $result['sku'],
                'model'            => $result['model'],
                'category_name'    => $result['category_name'],
                'warehouse_name'   => $result['warehouse_name'],
                'current_stock'    => $result['current_stock'],
                'reserved_stock'   => $result['reserved_stock'],
                'available_stock'  => $result['available_stock'],
                'unit_cost'        => $this->currency->format($result['unit_cost'], $this->config->get('config_currency')),
                'total_value'      => $this->currency->format($result['total_value'], $this->config->get('config_currency')),
                'reorder_level'    => $result['reorder_level'],
                'max_level'        => $result['max_level'],
                'last_movement_date' => $result['last_movement_date'] ? date($this->language->get('date_format_short'), strtotime($result['last_movement_date'])) : '',
                'status'           => $result['status'],
                'status_text'      => $result['status_text'],
                'status_class'     => $result['status_class']
            );
        }
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        // Load helper data
        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories();
        
        $this->load->model('inventory/warehouse');
        $data['warehouses'] = $this->model_inventory_warehouse->getWarehouses();
        
        $data['filter_product_name'] = $filter_product_name;
        $data['filter_sku'] = $filter_sku;
        $data['filter_category_id'] = $filter_category_id;
        $data['filter_warehouse_id'] = $filter_warehouse_id;
        $data['filter_stock_status'] = $filter_stock_status;
        
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        $pagination = new Pagination();
        $pagination->total = $stock_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/current_stock', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        
        $data['results'] = sprintf($this->language->get('text_pagination'), ($stock_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($stock_total - $this->config->get('config_limit_admin'))) ? $stock_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $stock_total, ceil($stock_total / $this->config->get('config_limit_admin')));
        
        $data['sort_product_name'] = $this->url->link('inventory/current_stock', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, true);
        $data['sort_sku'] = $this->url->link('inventory/current_stock', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sku' . $url, true);
        $data['sort_current_stock'] = $this->url->link('inventory/current_stock', 'user_token=' . $this->session->data['user_token'] . '&sort=current_stock' . $url, true);
        $data['sort_total_value'] = $this->url->link('inventory/current_stock', 'user_token=' . $this->session->data['user_token'] . '&sort=total_value' . $url, true);
        
        $data['analytics'] = $this->url->link('inventory/current_stock/analytics', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/current_stock', $data));
    }
    
    private function getFilterData() {
        return array(
            'filter_product_name' => $this->request->get['filter_product_name'] ?? '',
            'filter_sku'         => $this->request->get['filter_sku'] ?? '',
            'filter_category_id' => $this->request->get['filter_category_id'] ?? '',
            'filter_warehouse_id' => $this->request->get['filter_warehouse_id'] ?? '',
            'filter_stock_status' => $this->request->get['filter_stock_status'] ?? '',
            'sort'               => $this->request->get['sort'] ?? 'pd.name',
            'order'              => $this->request->get['order'] ?? 'ASC',
            'start'              => 0,
            'limit'              => 10000
        );
    }
}
