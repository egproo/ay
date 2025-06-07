<?php
/**
 * AYM ERP - Advanced Multi-Warehouse Management Controller
 *
 * Professional warehouse management system with comprehensive features
 * Features:
 * - Multi-warehouse inventory tracking
 * - Real-time stock levels and movements
 * - Advanced location management (zones, aisles, shelves, bins)
 * - Automated reorder points and procurement
 * - Barcode/QR code integration
 * - Batch and serial number tracking
 * - Expiry date management
 * - Transfer management between warehouses
 * - Advanced reporting and analytics
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerInventoryWarehouse extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('inventory/warehouse');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_inventory'),
            'href' => $this->url->link('inventory/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/warehouse', 'user_token=' . $this->session->data['user_token'], true)
        );

        $this->getList($data);
    }

    public function add() {
        $this->load->language('inventory/warehouse');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('inventory/warehouse');

            $warehouse_id = $this->model_inventory_warehouse->addWarehouse($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

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

            $this->response->redirect($this->url->link('inventory/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('inventory/warehouse');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('inventory/warehouse');

            $this->model_inventory_warehouse->editWarehouse($this->request->get['warehouse_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

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

            $this->response->redirect($this->url->link('inventory/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('inventory/warehouse');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('inventory/warehouse');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $warehouse_id) {
                $this->model_inventory_warehouse->deleteWarehouse($warehouse_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

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

            $this->response->redirect($this->url->link('inventory/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function dashboard() {
        $this->load->language('inventory/warehouse');
        $this->load->model('inventory/warehouse');

        $this->document->setTitle($this->language->get('text_warehouse_dashboard'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_warehouse_dashboard'),
            'href' => $this->url->link('inventory/warehouse/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Get warehouse statistics
        $data['warehouse_stats'] = $this->model_inventory_warehouse->getWarehouseStatistics();

        // Get low stock alerts
        $data['low_stock_alerts'] = $this->model_inventory_warehouse->getLowStockAlerts();

        // Get recent movements
        $data['recent_movements'] = $this->model_inventory_warehouse->getRecentMovements(10);

        // Get expiry alerts
        $data['expiry_alerts'] = $this->model_inventory_warehouse->getExpiryAlerts();

        // Get transfer requests
        $data['transfer_requests'] = $this->model_inventory_warehouse->getPendingTransfers();

        // Get warehouse utilization
        $data['warehouse_utilization'] = $this->model_inventory_warehouse->getWarehouseUtilization();

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/warehouse_dashboard', $data));
    }

    public function stockMovement() {
        $this->load->language('inventory/warehouse');
        $this->load->model('inventory/warehouse');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $movement_data = $this->request->post;

            try {
                // Validate movement data
                if ($this->validateMovement($movement_data)) {
                    $movement_id = $this->model_inventory_warehouse->createStockMovement($movement_data);

                    if ($movement_id) {
                        $json['success'] = true;
                        $json['movement_id'] = $movement_id;
                        $json['message'] = $this->language->get('text_movement_success');

                        // Get updated stock level
                        $json['new_stock_level'] = $this->model_inventory_warehouse->getProductStock(
                            $movement_data['product_id'],
                            $movement_data['warehouse_id']
                        );
                    } else {
                        $json['error'] = $this->language->get('error_movement_failed');
                    }
                } else {
                    $json['error'] = $this->error;
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function transfer() {
        $this->load->language('inventory/warehouse');
        $this->load->model('inventory/warehouse');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $transfer_data = $this->request->post;

            try {
                // Validate transfer data
                if ($this->validateTransfer($transfer_data)) {
                    $transfer_id = $this->model_inventory_warehouse->createTransfer($transfer_data);

                    if ($transfer_id) {
                        $json['success'] = true;
                        $json['transfer_id'] = $transfer_id;
                        $json['message'] = $this->language->get('text_transfer_success');
                    } else {
                        $json['error'] = $this->language->get('error_transfer_failed');
                    }
                } else {
                    $json['error'] = $this->error;
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function barcodeScanner() {
        $this->load->language('inventory/warehouse');
        $this->load->model('inventory/warehouse');

        $json = array('success' => false);

        if (isset($this->request->get['barcode'])) {
            $barcode = $this->request->get['barcode'];

            try {
                $product_info = $this->model_inventory_warehouse->getProductByBarcode($barcode);

                if ($product_info) {
                    $json['success'] = true;
                    $json['product'] = $product_info;

                    // Get stock levels across all warehouses
                    $json['stock_levels'] = $this->model_inventory_warehouse->getProductStockLevels($product_info['product_id']);

                    // Get recent movements
                    $json['recent_movements'] = $this->model_inventory_warehouse->getProductMovements($product_info['product_id'], 5);
                } else {
                    $json['error'] = $this->language->get('error_product_not_found');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_barcode_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function stockAdjustment() {
        $this->load->language('inventory/warehouse');
        $this->load->model('inventory/warehouse');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateAdjustment()) {
            $adjustment_data = $this->request->post;

            try {
                $adjustment_id = $this->model_inventory_warehouse->createStockAdjustment($adjustment_data);

                $this->session->data['success'] = $this->language->get('text_adjustment_success');
            } catch (Exception $e) {
                $this->session->data['error'] = $e->getMessage();
            }

            $this->response->redirect($this->url->link('inventory/warehouse/stockAdjustment', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->document->setTitle($this->language->get('text_stock_adjustment'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_stock_adjustment'),
            'href' => $this->url->link('inventory/warehouse/stockAdjustment', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Get warehouses
        $data['warehouses'] = $this->model_inventory_warehouse->getWarehouses();

        // Get adjustment reasons
        $data['adjustment_reasons'] = $this->model_inventory_warehouse->getAdjustmentReasons();

        // Get recent adjustments
        $data['recent_adjustments'] = $this->model_inventory_warehouse->getRecentAdjustments(20);

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/stock_adjustment', $data));
    }

    protected function getList(&$data) {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'name';
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

        $data['warehouses'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $this->load->model('inventory/warehouse');

        $warehouse_total = $this->model_inventory_warehouse->getTotalWarehouses();

        $results = $this->model_inventory_warehouse->getWarehouses($filter_data);

        foreach ($results as $result) {
            $data['warehouses'][] = array(
                'warehouse_id' => $result['warehouse_id'],
                'name'         => $result['name'],
                'code'         => $result['code'],
                'address'      => $result['address'],
                'status'       => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'total_products' => $this->model_inventory_warehouse->getTotalProductsInWarehouse($result['warehouse_id']),
                'total_value'    => $this->currency->format($this->model_inventory_warehouse->getTotalValueInWarehouse($result['warehouse_id']), $this->config->get('config_currency')),
                'edit'         => $this->url->link('inventory/warehouse/edit', 'user_token=' . $this->session->data['user_token'] . '&warehouse_id=' . $result['warehouse_id'] . $url, true)
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

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
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

        $data['sort_name'] = $this->url->link('inventory/warehouse', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
        $data['sort_code'] = $this->url->link('inventory/warehouse', 'user_token=' . $this->session->data['user_token'] . '&sort=code' . $url, true);
        $data['sort_status'] = $this->url->link('inventory/warehouse', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $warehouse_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/warehouse', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($warehouse_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($warehouse_total - $this->config->get('config_limit_admin'))) ? $warehouse_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $warehouse_total, ceil($warehouse_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/warehouse_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['warehouse_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = '';
        }

        if (isset($this->error['code'])) {
            $data['error_code'] = $this->error['code'];
        } else {
            $data['error_code'] = '';
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
            'href' => $this->url->link('inventory/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['warehouse_id'])) {
            $data['action'] = $this->url->link('inventory/warehouse/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('inventory/warehouse/edit', 'user_token=' . $this->session->data['user_token'] . '&warehouse_id=' . $this->request->get['warehouse_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('inventory/warehouse', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['warehouse_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $this->load->model('inventory/warehouse');

            $warehouse_info = $this->model_inventory_warehouse->getWarehouse($this->request->get['warehouse_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($warehouse_info)) {
            $data['name'] = $warehouse_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['code'])) {
            $data['code'] = $this->request->post['code'];
        } elseif (!empty($warehouse_info)) {
            $data['code'] = $warehouse_info['code'];
        } else {
            $data['code'] = '';
        }

        if (isset($this->request->post['address'])) {
            $data['address'] = $this->request->post['address'];
        } elseif (!empty($warehouse_info)) {
            $data['address'] = $warehouse_info['address'];
        } else {
            $data['address'] = '';
        }

        if (isset($this->request->post['telephone'])) {
            $data['telephone'] = $this->request->post['telephone'];
        } elseif (!empty($warehouse_info)) {
            $data['telephone'] = $warehouse_info['telephone'];
        } else {
            $data['telephone'] = '';
        }

        if (isset($this->request->post['manager'])) {
            $data['manager'] = $this->request->post['manager'];
        } elseif (!empty($warehouse_info)) {
            $data['manager'] = $warehouse_info['manager'];
        } else {
            $data['manager'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($warehouse_info)) {
            $data['status'] = $warehouse_info['status'];
        } else {
            $data['status'] = true;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/warehouse_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/warehouse')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if ((utf8_strlen($this->request->post['code']) < 1) || (utf8_strlen($this->request->post['code']) > 32)) {
            $this->error['code'] = $this->language->get('error_code');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/warehouse')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateMovement($data) {
        if (empty($data['product_id'])) {
            $this->error['product'] = $this->language->get('error_product_required');
        }

        if (empty($data['warehouse_id'])) {
            $this->error['warehouse'] = $this->language->get('error_warehouse_required');
        }

        if (empty($data['movement_type'])) {
            $this->error['movement_type'] = $this->language->get('error_movement_type_required');
        }

        if (!isset($data['quantity']) || $data['quantity'] <= 0) {
            $this->error['quantity'] = $this->language->get('error_quantity_required');
        }

        return !$this->error;
    }

    protected function validateTransfer($data) {
        if (empty($data['from_warehouse_id'])) {
            $this->error['from_warehouse'] = $this->language->get('error_from_warehouse_required');
        }

        if (empty($data['to_warehouse_id'])) {
            $this->error['to_warehouse'] = $this->language->get('error_to_warehouse_required');
        }

        if ($data['from_warehouse_id'] == $data['to_warehouse_id']) {
            $this->error['warehouse'] = $this->language->get('error_same_warehouse');
        }

        if (empty($data['products']) || !is_array($data['products'])) {
            $this->error['products'] = $this->language->get('error_products_required');
        }

        return !$this->error;
    }

    protected function validateAdjustment() {
        if (!$this->user->hasPermission('modify', 'inventory/warehouse')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['adjustments']) || !is_array($this->request->post['adjustments'])) {
            $this->error['adjustments'] = $this->language->get('error_adjustments_required');
        }

        return !$this->error;
    }
}
