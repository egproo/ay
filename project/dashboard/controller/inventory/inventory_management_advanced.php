<?php
/**
 * تحكم إدارة المخزون المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ControllerInventoryInventoryManagementAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('inventory/inventory_management_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/inventory_management_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/inventory/inventory_management.css');
        $this->document->addScript('view/javascript/inventory/inventory_management.js');
        $this->document->addScript('view/javascript/jquery/accounting.min.js');
        $this->document->addScript('view/javascript/jquery/select2.min.js');
        $this->document->addStyle('view/javascript/jquery/select2.min.css');
        $this->document->addScript('view/javascript/jquery/daterangepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/daterangepicker.css');
        $this->document->addScript('view/javascript/jquery/chart.min.js');
        $this->document->addScript('view/javascript/jquery/datatables.min.js');
        $this->document->addStyle('view/javascript/jquery/datatables.min.css');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'inventory_management',
            'record_id' => 0,
            'description' => 'عرض شاشة إدارة المخزون',
            'module' => 'inventory_management'
        ]);

        $this->getList();
    }

    public function stockMovement() {
        $this->load->language('inventory/inventory_management_advanced');
        $this->load->model('inventory/inventory_management_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateStockMovement()) {
            try {
                $movement_data = $this->prepareMovementData();

                $result = $this->model_inventory_inventory_management_advanced->processStockMovement($movement_data);

                // تسجيل حركة المخزون
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'stock_movement',
                    'table_name' => 'stock_movements',
                    'record_id' => $result['movement_id'],
                    'description' => 'حركة مخزون: ' . $movement_data['movement_type'] . ' - ' . $movement_data['product_name'],
                    'module' => 'inventory_management'
                ]);

                $this->session->data['success'] = 'تم تسجيل حركة المخزون بنجاح';

                $this->response->redirect($this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في تسجيل حركة المخزون: ' . $e->getMessage();
            }
        }

        $this->getMovementForm();
    }

    public function stockAdjustment() {
        $this->load->language('inventory/inventory_management_advanced');
        $this->load->model('inventory/inventory_management_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateStockAdjustment()) {
            try {
                $adjustment_data = $this->prepareAdjustmentData();

                $result = $this->model_inventory_inventory_management_advanced->processStockAdjustment($adjustment_data);

                // تسجيل تسوية المخزون
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'stock_adjustment',
                    'table_name' => 'stock_adjustments',
                    'record_id' => $result['adjustment_id'],
                    'description' => 'تسوية مخزون: ' . $adjustment_data['product_name'] . ' - ' . $adjustment_data['reason'],
                    'module' => 'inventory_management'
                ]);

                $this->session->data['success'] = 'تم تسجيل تسوية المخزون بنجاح';

                $this->response->redirect($this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في تسوية المخزون: ' . $e->getMessage();
            }
        }

        $this->getAdjustmentForm();
    }

    public function stockTransfer() {
        $this->load->language('inventory/inventory_management_advanced');
        $this->load->model('inventory/inventory_management_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateStockTransfer()) {
            try {
                $transfer_data = $this->prepareTransferData();

                $result = $this->model_inventory_inventory_management_advanced->processStockTransfer($transfer_data);

                // تسجيل تحويل المخزون
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'stock_transfer',
                    'table_name' => 'stock_transfers',
                    'record_id' => $result['transfer_id'],
                    'description' => 'تحويل مخزون من ' . $transfer_data['from_warehouse_name'] . ' إلى ' . $transfer_data['to_warehouse_name'],
                    'module' => 'inventory_management'
                ]);

                $this->session->data['success'] = 'تم تحويل المخزون بنجاح';

                $this->response->redirect($this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في تحويل المخزون: ' . $e->getMessage();
            }
        }

        $this->getTransferForm();
    }

    public function stockCount() {
        $this->load->language('inventory/inventory_management_advanced');
        $this->load->model('inventory/inventory_management_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateStockCount()) {
            try {
                $count_data = $this->prepareCountData();

                $result = $this->model_inventory_inventory_management_advanced->processStockCount($count_data);

                // تسجيل جرد المخزون
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'stock_count',
                    'table_name' => 'stock_counts',
                    'record_id' => $result['count_id'],
                    'description' => 'جرد مخزون: ' . $count_data['warehouse_name'] . ' - ' . $count_data['count_date'],
                    'module' => 'inventory_management'
                ]);

                $this->session->data['count_result'] = $result;
                $this->session->data['success'] = 'تم إجراء جرد المخزون بنجاح';

                $this->response->redirect($this->url->link('inventory/inventory_management_advanced/viewCountResult', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في جرد المخزون: ' . $e->getMessage();
            }
        }

        $this->getCountForm();
    }

    public function revaluation() {
        $this->load->language('inventory/inventory_management_advanced');
        $this->load->model('inventory/inventory_management_advanced');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateRevaluation()) {
            try {
                $revaluation_data = $this->prepareRevaluationData();

                $result = $this->model_inventory_inventory_management_advanced->processRevaluation($revaluation_data);

                // تسجيل إعادة تقييم المخزون
                $this->model_accounts_audit_trail->logAction([
                    'action_type' => 'inventory_revaluation',
                    'table_name' => 'inventory_revaluations',
                    'record_id' => $result['revaluation_id'],
                    'description' => 'إعادة تقييم المخزون: ' . $revaluation_data['revaluation_date'],
                    'module' => 'inventory_management'
                ]);

                $this->session->data['success'] = 'تم إعادة تقييم المخزون بنجاح';

                $this->response->redirect($this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'], true));

            } catch (Exception $e) {
                $this->error['warning'] = 'خطأ في إعادة تقييم المخزون: ' . $e->getMessage();
            }
        }

        $this->getRevaluationForm();
    }

    public function getInventoryAnalysis() {
        $this->load->model('inventory/inventory_management_advanced');

        $json = array();

        try {
            $filter_data = $this->prepareAnalysisFilter();

            $analysis = $this->model_inventory_inventory_management_advanced->analyzeInventory($filter_data);

            $json['success'] = true;
            $json['analysis'] = $analysis;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getStockLevels() {
        $this->load->model('inventory/inventory_management_advanced');

        $json = array();

        try {
            $warehouse_id = $this->request->get['warehouse_id'] ?? 0;
            $category_id = $this->request->get['category_id'] ?? 0;

            $stock_levels = $this->model_inventory_inventory_management_advanced->getStockLevels($warehouse_id, $category_id);

            $json['success'] = true;
            $json['stock_levels'] = $stock_levels;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getMovementHistory() {
        $this->load->model('inventory/inventory_management_advanced');

        $json = array();

        if (isset($this->request->get['product_id'])) {
            try {
                $product_id = $this->request->get['product_id'];
                $limit = $this->request->get['limit'] ?? 50;
                $offset = $this->request->get['offset'] ?? 0;

                $movements = $this->model_inventory_inventory_management_advanced->getMovementHistory($product_id, $limit, $offset);

                $json['success'] = true;
                $json['movements'] = $movements;

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف المنتج مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getValuationReport() {
        $this->load->model('inventory/inventory_management_advanced');

        $json = array();

        try {
            $valuation_date = $this->request->get['valuation_date'] ?? date('Y-m-d');
            $warehouse_id = $this->request->get['warehouse_id'] ?? 0;

            $valuation = $this->model_inventory_inventory_management_advanced->calculateInventoryValuation($valuation_date, $warehouse_id);

            $json['success'] = true;
            $json['valuation'] = $valuation;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function export() {
        $this->load->language('inventory/inventory_management_advanced');
        $this->load->model('inventory/inventory_management_advanced');

        $format = $this->request->get['format'] ?? 'excel';
        $report_type = $this->request->get['report_type'] ?? 'stock_levels';
        $filter_data = $this->prepareFilterData();

        $inventory_data = $this->model_inventory_inventory_management_advanced->getInventoryForExport($report_type, $filter_data);

        // تسجيل التصدير
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'export_inventory',
            'table_name' => 'inventory',
            'record_id' => 0,
            'description' => "تصدير تقرير المخزون ({$report_type}) بصيغة {$format}",
            'module' => 'inventory_management'
        ]);

        switch ($format) {
            case 'excel':
                $this->exportToExcel($inventory_data, $report_type);
                break;
            case 'pdf':
                $this->exportToPdf($inventory_data, $report_type);
                break;
            case 'csv':
                $this->exportToCsv($inventory_data, $report_type);
                break;
            default:
                $this->exportToExcel($inventory_data, $report_type);
        }
    }

    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'product_name';
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

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['stock_movement'] = $this->url->link('inventory/inventory_management_advanced/stockMovement', 'user_token=' . $this->session->data['user_token'], true);
        $data['stock_adjustment'] = $this->url->link('inventory/inventory_management_advanced/stockAdjustment', 'user_token=' . $this->session->data['user_token'], true);
        $data['stock_transfer'] = $this->url->link('inventory/inventory_management_advanced/stockTransfer', 'user_token=' . $this->session->data['user_token'], true);
        $data['stock_count'] = $this->url->link('inventory/inventory_management_advanced/stockCount', 'user_token=' . $this->session->data['user_token'], true);
        $data['revaluation'] = $this->url->link('inventory/inventory_management_advanced/revaluation', 'user_token=' . $this->session->data['user_token'], true);

        // URLs للـ AJAX
        $data['analysis_url'] = $this->url->link('inventory/inventory_management_advanced/getInventoryAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['stock_levels_url'] = $this->url->link('inventory/inventory_management_advanced/getStockLevels', 'user_token=' . $this->session->data['user_token'], true);
        $data['movement_history_url'] = $this->url->link('inventory/inventory_management_advanced/getMovementHistory', 'user_token=' . $this->session->data['user_token'], true);
        $data['valuation_url'] = $this->url->link('inventory/inventory_management_advanced/getValuationReport', 'user_token=' . $this->session->data['user_token'], true);

        $data['inventory_items'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $inventory_total = $this->model_inventory_inventory_management_advanced->getTotalInventoryItems();

        $results = $this->model_inventory_inventory_management_advanced->getInventoryItems($filter_data);

        foreach ($results as $result) {
            $data['inventory_items'][] = array(
                'product_id'        => $result['product_id'],
                'product_code'      => $result['product_code'],
                'product_name'      => $result['product_name'],
                'category_name'     => $result['category_name'],
                'warehouse_name'    => $result['warehouse_name'],
                'current_stock'     => $result['current_stock'],
                'reserved_stock'    => $result['reserved_stock'],
                'available_stock'   => $result['available_stock'],
                'unit_cost'         => $this->currency->format($result['unit_cost'], $this->config->get('config_currency')),
                'total_value'       => $this->currency->format($result['total_value'], $this->config->get('config_currency')),
                'reorder_level'     => $result['reorder_level'],
                'status'            => $result['status'],
                'last_movement'     => $result['last_movement'] ? date($this->language->get('date_format_short'), strtotime($result['last_movement'])) : 'لا يوجد',
                'movement'          => $this->url->link('inventory/inventory_management_advanced/stockMovement', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true),
                'adjustment'        => $this->url->link('inventory/inventory_management_advanced/stockAdjustment', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true)
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

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

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_product_name'] = $this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=product_name' . $url, true);
        $data['sort_category'] = $this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=category_name' . $url, true);
        $data['sort_current_stock'] = $this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=current_stock' . $url, true);
        $data['sort_unit_cost'] = $this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'] . '&sort=unit_cost' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $inventory_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($inventory_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($inventory_total - $this->config->get('config_limit_admin'))) ? $inventory_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $inventory_total, ceil($inventory_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/inventory_management_advanced_list', $data));
    }
}