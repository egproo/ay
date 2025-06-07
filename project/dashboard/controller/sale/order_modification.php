<?php
/**
 * AYM ERP - Order Modification Controller
 *
 * Professional order modification system with ETA integration
 * Handles complex scenarios including:
 * - Multiple product units and variants
 * - Product options modifications
 * - Quantity changes (increase/decrease)
 * - Price adjustments
 * - Tax recalculations
 * - Automatic ETA credit/debit note generation
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerSaleOrderModification extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('sale/order_modification');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_sales'),
            'href' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('sale/order_modification', 'user_token=' . $this->session->data['user_token'], true)
        );

        $this->getList($data);
    }

    public function modify() {
        $this->load->language('sale/order_modification');
        $this->load->model('sale/order');
        $this->load->model('sale/order_modification');

        $order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;

        if (!$order_id) {
            $this->session->data['error'] = $this->language->get('error_order_not_found');
            $this->response->redirect($this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true));
        }

        $order_info = $this->model_sale_order->getOrder($order_id);

        if (!$order_info) {
            $this->session->data['error'] = $this->language->get('error_order_not_found');
            $this->response->redirect($this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true));
        }

        // Check if order can be modified
        if (!$this->canModifyOrder($order_info)) {
            $this->session->data['error'] = $this->language->get('error_order_cannot_modify');
            $this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateModification()) {
            $modification_data = $this->request->post;

            try {
                // Process the modification
                $result = $this->model_sale_order_modification->processModification($order_id, $modification_data);

                if ($result['success']) {
                    // Send ETA notification if enabled
                    if ($this->config->get('config_eta_order_modification_enabled')) {
                        $this->sendETAModificationNote($order_id, $result['modification_type'], $result['changes']);
                    }

                    $this->session->data['success'] = $this->language->get('text_modification_success');
                    $this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true));
                } else {
                    $this->error['warning'] = $result['error'];
                }
            } catch (Exception $e) {
                $this->error['warning'] = $e->getMessage();
            }
        }

        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_modify_order'));

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_modify_order'),
            'href' => $this->url->link('sale/order_modification/modify', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true)
        );

        // Load order data
        $data['order_info'] = $order_info;
        $data['order_products'] = $this->model_sale_order->getOrderProducts($order_id);
        $data['order_options'] = array();
        $data['order_totals'] = $this->model_sale_order->getOrderTotals($order_id);

        // Load product options for each product
        foreach ($data['order_products'] as $key => $product) {
            $data['order_options'][$product['order_product_id']] = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

            // Load available units for this product
            $data['order_products'][$key]['available_units'] = $this->getProductUnits($product['product_id']);

            // Load product variants/options
            $data['order_products'][$key]['available_options'] = $this->getProductOptions($product['product_id']);
        }

        // Load modification history
        $data['modification_history'] = $this->model_sale_order_modification->getModificationHistory($order_id);

        // ETA status
        $data['eta_enabled'] = $this->config->get('config_eta_order_modification_enabled');
        $data['eta_invoice_sent'] = $this->hasETAInvoice($order_id);

        $data['order_id'] = $order_id;
        $data['action'] = $this->url->link('sale/order_modification/modify', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true);
        $data['cancel'] = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true);

        $data['user_token'] = $this->session->data['user_token'];

        // Load error messages
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

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('sale/order_modification_form', $data));
    }

    public function ajax_calculate_totals() {
        $this->load->language('sale/order_modification');
        $this->load->model('sale/order_modification');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $order_id = (int)$this->request->post['order_id'];
            $modifications = $this->request->post['modifications'] ?? array();

            try {
                $totals = $this->model_sale_order_modification->calculateModificationTotals($order_id, $modifications);

                $json['success'] = true;
                $json['totals'] = $totals;
                $json['eta_required'] = $this->isETAModificationRequired($totals);
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function ajax_get_product_info() {
        $this->load->model('catalog/product');

        $json = array('success' => false);

        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];

            $product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info) {
                $json['success'] = true;
                $json['product'] = array(
                    'product_id' => $product_info['product_id'],
                    'name' => $product_info['name'],
                    'model' => $product_info['model'],
                    'sku' => $product_info['sku'],
                    'price' => $product_info['price'],
                    'tax_class_id' => $product_info['tax_class_id'],
                    'units' => $this->getProductUnits($product_id),
                    'options' => $this->getProductOptions($product_id)
                );
            } else {
                $json['error'] = $this->language->get('error_product_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_product_id_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getList(&$data) {
        $this->load->model('sale/order_modification');

        if (isset($this->request->get['filter_order_id'])) {
            $filter_order_id = $this->request->get['filter_order_id'];
        } else {
            $filter_order_id = '';
        }

        if (isset($this->request->get['filter_customer'])) {
            $filter_customer = $this->request->get['filter_customer'];
        } else {
            $filter_customer = '';
        }

        if (isset($this->request->get['filter_date_from'])) {
            $filter_date_from = $this->request->get['filter_date_from'];
        } else {
            $filter_date_from = '';
        }

        if (isset($this->request->get['filter_date_to'])) {
            $filter_date_to = $this->request->get['filter_date_to'];
        } else {
            $filter_date_to = '';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['modifications'] = array();

        $filter_data = array(
            'filter_order_id' => $filter_order_id,
            'filter_customer' => $filter_customer,
            'filter_date_from' => $filter_date_from,
            'filter_date_to' => $filter_date_to,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $modification_total = $this->model_sale_order_modification->getTotalModifications($filter_data);
        $results = $this->model_sale_order_modification->getModifications($filter_data);

        foreach ($results as $result) {
            $data['modifications'][] = array(
                'modification_id' => $result['modification_id'],
                'order_id' => $result['order_id'],
                'customer_name' => $result['customer_name'],
                'modification_type' => $result['modification_type'],
                'amount_change' => $this->currency->format($result['amount_change'], $result['currency_code']),
                'eta_status' => $result['eta_status'],
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'view' => $this->url->link('sale/order_modification/view', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $result['modification_id'], true),
                'order_link' => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'], true)
            );
        }

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $modification_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('sale/order_modification', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($modification_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($modification_total - $this->config->get('config_limit_admin'))) ? $modification_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $modification_total, ceil($modification_total / $this->config->get('config_limit_admin')));

        $data['filter_order_id'] = $filter_order_id;
        $data['filter_customer'] = $filter_customer;
        $data['filter_date_from'] = $filter_date_from;
        $data['filter_date_to'] = $filter_date_to;

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('sale/order_modification_list', $data));
    }

    private function canModifyOrder($order_info) {
        // Check if order status allows modification
        $modifiable_statuses = array(1, 2, 3, 5, 15); // Pending, Processing, Shipped, Complete, etc.

        if (!in_array($order_info['order_status_id'], $modifiable_statuses)) {
            return false;
        }

        // Check if order is not too old (configurable)
        $max_days = $this->config->get('config_order_modification_max_days') ?: 30;
        $order_date = strtotime($order_info['date_added']);
        $max_date = strtotime('-' . $max_days . ' days');

        if ($order_date < $max_date) {
            return false;
        }

        return true;
    }

    private function validateModification() {
        if (empty($this->request->post['modifications'])) {
            $this->error['warning'] = $this->language->get('error_no_modifications');
            return false;
        }

        foreach ($this->request->post['modifications'] as $modification) {
            if (empty($modification['type'])) {
                $this->error['warning'] = $this->language->get('error_modification_type_required');
                return false;
            }

            if ($modification['type'] == 'quantity' && (!isset($modification['new_quantity']) || $modification['new_quantity'] < 0)) {
                $this->error['warning'] = $this->language->get('error_invalid_quantity');
                return false;
            }

            if ($modification['type'] == 'price' && (!isset($modification['new_price']) || $modification['new_price'] < 0)) {
                $this->error['warning'] = $this->language->get('error_invalid_price');
                return false;
            }
        }

        return !$this->error;
    }

    private function getProductUnits($product_id) {
        $this->load->model('catalog/product');

        // Get available units for this product
        $query = $this->db->query("SELECT pu.*, ud.name
            FROM " . DB_PREFIX . "product_unit pu
            LEFT JOIN " . DB_PREFIX . "unit_description ud ON (pu.unit_id = ud.unit_id AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE pu.product_id = '" . (int)$product_id . "'");

        return $query->rows;
    }

    private function getProductOptions($product_id) {
        $this->load->model('catalog/product');

        return $this->model_catalog_product->getProductOptions($product_id);
    }

    private function hasETAInvoice($order_id) {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "eta_invoices WHERE order_id = '" . (int)$order_id . "' AND status = 'sent'");

        return $query->row['total'] > 0;
    }

    private function sendETAModificationNote($order_id, $modification_type, $changes) {
        $this->load->controller('extension/eta/invoice');

        $eta_controller = new ControllerExtensionEtaInvoice($this->registry);

        // Prepare modification data for ETA
        $modification_data = array();

        foreach ($changes as $change) {
            $modification_data[] = array(
                'description' => $change['product_name'],
                'item_code' => $change['product_model'],
                'unit_type' => $change['unit_type'] ?? 'EA',
                'quantity' => $change['quantity_change'],
                'unit_price' => $change['unit_price'],
                'taxes' => $change['taxes'] ?? array()
            );
        }

        // Send to ETA
        return $eta_controller->sendOrderModificationNote($order_id, $modification_type, $modification_data);
    }

    private function isETAModificationRequired($totals) {
        // Check if the modification amount exceeds the threshold for ETA notification
        $threshold = $this->config->get('config_eta_modification_threshold') ?: 0;

        return abs($totals['total_change']) > $threshold;
    }

    public function view() {
        $this->load->language('sale/order_modification');
        $this->load->model('sale/order_modification');

        $modification_id = isset($this->request->get['modification_id']) ? (int)$this->request->get['modification_id'] : 0;

        if (!$modification_id) {
            $this->session->data['error'] = $this->language->get('error_modification_not_found');
            $this->response->redirect($this->url->link('sale/order_modification', 'user_token=' . $this->session->data['user_token'], true));
        }

        $modification_info = $this->model_sale_order_modification->getModification($modification_id);

        if (!$modification_info) {
            $this->session->data['error'] = $this->language->get('error_modification_not_found');
            $this->response->redirect($this->url->link('sale/order_modification', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_view_modification'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('sale/order_modification', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_view_modification'),
            'href' => $this->url->link('sale/order_modification/view', 'user_token=' . $this->session->data['user_token'] . '&modification_id=' . $modification_id, true)
        );

        $data['modification_info'] = $modification_info;
        $data['modification_items'] = $this->model_sale_order_modification->getModificationItems($modification_id);
        $data['eta_notes'] = $this->model_sale_order_modification->getETANotes($modification_id);

        $data['back'] = $this->url->link('sale/order_modification', 'user_token=' . $this->session->data['user_token'], true);
        $data['order_link'] = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $modification_info['order_id'], true);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('sale/order_modification_view', $data));
    }
}
