<?php
class ControllerPurchasePurchaseOrder extends Controller {
    private $error = array();

    /**
     * عرض قائمة أوامر الشراء
     */
    public function index() {
        $this->load->language('purchase/purchase');
        $this->load->language('purchase/purchase_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/order');

        $this->getList();
    }

    /**
     * عرض صفحة إضافة أمر شراء جديد
     */
    public function add() {
        $this->load->language('purchase/purchase');
        $this->load->language('purchase/purchase_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/order');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $data = $this->prepareOrderData();
            $po_id = $this->model_purchase_order->addOrder($data);

            $this->session->data['success'] = $this->language->get('text_save_po_success');

            $this->response->redirect($this->url->link('purchase/purchase_order/edit', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true));
        }

        $this->getForm();
    }

    /**
     * عرض صفحة تعديل أمر شراء
     */
    public function edit() {
        $this->load->language('purchase/purchase');
        $this->load->language('purchase/purchase_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/order');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $data = $this->prepareOrderData();
            $data['po_id'] = $this->request->get['po_id'];

            $this->model_purchase_order->editOrder($data);

            $this->session->data['success'] = $this->language->get('text_save_po_success');

            $this->response->redirect($this->url->link('purchase/purchase_order/edit', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $this->request->get['po_id'], true));
        }

        $this->getForm();
    }

    /**
     * عرض تفاصيل أمر شراء
     */
    public function view() {
        $this->load->language('purchase/purchase');
        $this->load->language('purchase/purchase_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/order');

        $this->getView();
    }

    /**
     * حذف أمر شراء
     */
    public function delete() {
        $this->load->language('purchase/purchase');
        $this->load->language('purchase/purchase_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/order');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $po_id) {
                $this->model_purchase_order->deleteOrder($po_id);
            }

            $this->session->data['success'] = $this->language->get('text_delete_success');

            $url = '';

            if (isset($this->request->get['filter_po_number'])) {
                $url .= '&filter_po_number=' . $this->request->get['filter_po_number'];
            }

            if (isset($this->request->get['filter_supplier_id'])) {
                $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_start'])) {
                $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
            }

            if (isset($this->request->get['filter_date_end'])) {
                $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
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

            $this->response->redirect($this->url->link('purchase/purchase_order', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * الموافقة على أمر شراء
     */
    public function approve() {
        $this->load->language('purchase/purchase');
        $this->load->language('purchase/purchase_order');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/purchase_order')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/order');

            if (isset($this->request->get['po_id'])) {
                $po_id = $this->request->get['po_id'];
            } else {
                $po_id = 0;
            }

            $order_info = $this->model_purchase_order->getOrder($po_id);

            if (!$order_info) {
                $json['error'] = $this->language->get('error_po_not_found');
            } elseif ($order_info['status'] != 'pending') {
                $json['error'] = $this->language->get('error_po_status');
            } else {
                $this->model_purchase_order->approveOrder($po_id, $this->user->getId());
                $json['success'] = $this->language->get('text_approve_requisition_success');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * رفض أمر شراء
     */
    public function reject() {
        $this->load->language('purchase/purchase');
        $this->load->language('purchase/purchase_order');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/purchase_order')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/order');

            if (isset($this->request->post['po_id'])) {
                $po_id = $this->request->post['po_id'];
            } else {
                $po_id = 0;
            }

            if (isset($this->request->post['reject_reason'])) {
                $reject_reason = $this->request->post['reject_reason'];
            } else {
                $reject_reason = '';
            }

            $order_info = $this->model_purchase_order->getOrder($po_id);

            if (!$order_info) {
                $json['error'] = $this->language->get('error_po_not_found');
            } elseif ($order_info['status'] != 'pending') {
                $json['error'] = $this->language->get('error_po_status');
            } else {
                $this->model_purchase_order->rejectOrder($po_id, $reject_reason, $this->user->getId());
                $json['success'] = $this->language->get('text_reject_requisition_success');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * طباعة أمر شراء
     */
    public function print() {
        $this->load->language('purchase/purchase');
        $this->load->language('purchase/purchase_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/order');

        if (isset($this->request->get['po_id'])) {
            $po_id = $this->request->get['po_id'];
        } else {
            $po_id = 0;
        }

        $order_info = $this->model_purchase_order->getOrder($po_id);

        if (!$order_info) {
            $this->response->redirect($this->url->link('purchase/purchase_order', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->getPrintData($order_info);

        $this->response->setOutput($this->load->view('purchase/purchase_order_print', $data));
    }

    /**
     * تحضير بيانات الطباعة
     */
    protected function getPrintData($order_info) {
        $data = array();

        $data['title'] = $this->language->get('text_purchase_order');
        $data['base'] = HTTP_SERVER;
        $data['direction'] = $this->language->get('direction');
        $data['lang'] = $this->language->get('code');

        $data['text_purchase_order'] = $this->language->get('text_purchase_order');
        $data['text_po_number'] = $this->language->get('text_po_number');
        $data['text_date'] = $this->language->get('text_date');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_delivery_date'] = $this->language->get('text_delivery_date');
        $data['text_payment_terms'] = $this->language->get('text_payment_terms');
        $data['text_delivery_terms'] = $this->language->get('text_delivery_terms');
        $data['text_notes'] = $this->language->get('text_notes');
        $data['text_subtotal'] = $this->language->get('text_subtotal');
        $data['text_discount'] = $this->language->get('text_discount');
        $data['text_tax'] = $this->language->get('text_tax');
        $data['text_total'] = $this->language->get('text_total');
        $data['text_print_date'] = $this->language->get('text_print_date');
        $data['text_prepared_by'] = $this->language->get('text_prepared_by');
        $data['text_approved_by'] = $this->language->get('text_approved_by');
        $data['text_supplier_signature'] = $this->language->get('text_supplier_signature');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_description'] = $this->language->get('column_description');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_tax_rate'] = $this->language->get('column_tax_rate');
        $data['column_total'] = $this->language->get('column_total');

        $data['order'] = $order_info;
        $data['items'] = $this->model_purchase_order->getOrderItems($order_info['po_id']);
        $data['print_date'] = date('Y-m-d H:i:s');

        // Get company info
        $this->load->model('setting/setting');
        $setting_info = $this->model_setting_setting->getSetting('config');

        $data['company_name'] = $setting_info['config_name'];
        $data['company_address'] = $setting_info['config_address'];
        $data['company_email'] = $setting_info['config_email'];
        $data['company_telephone'] = $setting_info['config_telephone'];

        return $data;
    }

    /**
     * Handle AJAX request to add a new goods receipt
     */
public function addGoodsReceipt() {
    $json = array();

    // Check permission
    if (!$this->user->hasPermission('modify', 'purchase/purchase_order')) {
        $json['error'] = $this->language->get('error_permission');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    // Validate required data
    if (empty($this->request->post['po_id'])) {
        $json['error'] = $this->language->get('error_po_not_found');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    $po_id = (int)$this->request->post['po_id'];

    $this->load->model('purchase/order');

    // Check if PO exists and is in valid status
    $po_info = $this->model_purchase_order->getOrder($po_id);
    if (!$po_info) {
        $json['error'] = $this->language->get('error_po_not_found');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    // Only approved orders can have goods receipts
    if ($po_info['status'] != 'approved' && $po_info['status'] != 'partially_received') {
        $json['error'] = $this->language->get('error_po_not_approved');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    // Validate receipt date (can't be before PO date)
    if (!empty($this->request->post['receipt_date'])) {
        $receipt_date = $this->request->post['receipt_date'];
        $po_date = $po_info['order_date'];

        if (strtotime($receipt_date) < strtotime($po_date)) {
            $json['error'] = $this->language->get('error_receipt_date');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
    } else {
        // If no date provided, use current date
        $this->request->post['receipt_date'] = date('Y-m-d');
    }

    // Validate items
    if (empty($this->request->post['items'])) {
        $json['error'] = $this->language->get('error_no_items');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    // Get remaining quantities for validation
    $po_items = $this->model_purchase_order->getOrderItemsWithReceiptInfo($po_id);
    $remaining_quantities = array();

    foreach ($po_items as $item) {
        $po_item_id = $item['po_item_id'];
        $remaining = $item['quantity'] - $item['received_quantity'];
        $remaining_quantities[$po_item_id] = $remaining;
    }

    // Check for valid quantities
    $valid_items = false;
    foreach ($this->request->post['items'] as $po_item_id => $item) {
        if (!isset($remaining_quantities[$po_item_id])) {
            continue;
        }

        $quantity = (float)$item['quantity'];

        if ($quantity <= 0) {
            continue;
        }

        if ($quantity > $remaining_quantities[$po_item_id]) {
            $json['error'] = $this->language->get('error_quantity_exceeded');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $valid_items = true;
    }

    if (!$valid_items) {
        $json['error'] = $this->language->get('error_receipt_quantities');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    // Prepare data for goods receipt
    $receipt_data = array(
        'po_id'        => $po_id,
        'receipt_date' => $this->request->post['receipt_date'],
        'reference'    => isset($this->request->post['reference']) ? $this->request->post['reference'] : '',
        'notes'        => isset($this->request->post['notes']) ? $this->request->post['notes'] : '',
        'branch_id'    => $po_info['branch_id'],
        'supplier_id'  => $po_info['supplier_id'],
        'user_id'      => $this->user->getId(),
        'items'        => array()
    );

    // Format items data
    foreach ($this->request->post['items'] as $po_item_id => $item) {
        $quantity = (float)$item['quantity'];

        // Skip items with zero quantity
        if ($quantity <= 0) {
            continue;
        }

        $receipt_data['items'][] = array(
            'po_item_id' => $po_item_id,
            'product_id' => $item['product_id'],
            'unit_id'    => $item['unit_id'],
            'quantity'   => $quantity,
            'unit_cost'  => (float)$item['unit_cost']
        );
    }

    try {
        // Create the goods receipt
        $receipt_id = $this->model_purchase_order->addGoodsReceipt($receipt_data);

        if ($receipt_id) {
            $json['success'] = $this->language->get('success_receipt_add');
            $json['receipt_id'] = $receipt_id;

            // Generate view URL
            $json['view_url'] = $this->url->link('purchase/goods_receipt/view', 'user_token=' . $this->session->data['user_token'] . '&receipt_id=' . $receipt_id, true);
        } else {
            $json['error'] = $this->language->get('error_server');
        }
    } catch (Exception $e) {
        $json['error'] = $e->getMessage();
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

/**
 * Get goods receipt details for display
 */
public function getGoodsReceiptDetails() {
    // Check permission
    if (!$this->user->hasPermission('access', 'purchase/purchase_order')) {
        $this->response->setOutput('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' . $this->language->get('error_permission') . '</div>');
        return;
    }

    $receipt_id = isset($this->request->get['receipt_id']) ? (int)$this->request->get['receipt_id'] : 0;

    if (!$receipt_id) {
        $this->response->setOutput('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Receipt ID is required</div>');
        return;
    }

    $this->load->model('purchase/order');

    // Get receipt details
    $receipt_info = $this->model_purchase_order->getGoodsReceipt($receipt_id);

    if (!$receipt_info) {
        $this->response->setOutput('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Receipt not found</div>');
        return;
    }

    // Get receipt items
    $receipt_items = $this->model_purchase_order->getGoodsReceiptItems($receipt_id);

    // Get PO details
    $po_info = $this->model_purchase_order->getOrder($receipt_info['po_id']);

    // Prepare data for view
    $data = array(
        'receipt' => $receipt_info,
        'items' => $receipt_items,
        'order' => $po_info,
        'user_token' => $this->session->data['user_token']
    );

    // Load language variables
    $data['heading_title'] = $this->language->get('text_receipt_details');
    $data['text_receipt_number'] = $this->language->get('text_receipt_number');
    $data['text_po_number'] = $this->language->get('text_po_number');
    $data['text_supplier'] = $this->language->get('text_supplier');
    $data['text_receipt_date'] = $this->language->get('text_receipt_date');
    $data['text_reference'] = $this->language->get('text_reference');
    $data['text_received_by'] = $this->language->get('text_received_by');
    $data['text_notes'] = $this->language->get('text_notes');

    $data['column_product'] = $this->language->get('column_product');
    $data['column_quantity'] = $this->language->get('column_quantity');
    $data['column_unit'] = $this->language->get('column_unit');
    $data['column_unit_cost'] = $this->language->get('column_unit_cost');
    $data['column_total_cost'] = $this->language->get('column_total_cost');

    $data['button_close'] = $this->language->get('button_close');
    $data['button_print'] = $this->language->get('button_print');

    // Load the view
    $this->response->setOutput($this->load->view('purchase/goods_receipt_view', $data));
}

/**
 * Print goods receipt
 */
public function printGoodsReceipt() {
    // Check permission
    if (!$this->user->hasPermission('access', 'purchase/purchase_order')) {
        $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        return;
    }

    $receipt_id = isset($this->request->get['receipt_id']) ? (int)$this->request->get['receipt_id'] : 0;

    if (!$receipt_id) {
        $this->response->redirect($this->url->link('purchase/purchase_order', 'user_token=' . $this->session->data['user_token'], true));
        return;
    }

    $this->load->model('purchase/order');

    // Get receipt details
    $receipt_info = $this->model_purchase_order->getGoodsReceipt($receipt_id);

    if (!$receipt_info) {
        $this->response->redirect($this->url->link('purchase/purchase_order', 'user_token=' . $this->session->data['user_token'], true));
        return;
    }

    // Get receipt items
    $receipt_items = $this->model_purchase_order->getGoodsReceiptItems($receipt_id);

    // Get PO details
    $po_info = $this->model_purchase_order->getOrder($receipt_info['po_id']);

    // Prepare data for view
    $data = array(
        'receipt' => $receipt_info,
        'items' => $receipt_items,
        'order' => $po_info,
        'print_date' => date('Y-m-d H:i:s')
    );

    // Load language variables
    $data['title'] = $this->language->get('text_receipt_details');
    $data['text_receipt_number'] = $this->language->get('text_receipt_number');
    $data['text_po_number'] = $this->language->get('text_po_number');
    $data['text_supplier'] = $this->language->get('text_supplier');
    $data['text_receipt_date'] = $this->language->get('text_receipt_date');
    $data['text_reference'] = $this->language->get('text_reference');
    $data['text_received_by'] = $this->language->get('text_received_by');
    $data['text_notes'] = $this->language->get('text_notes');
    $data['text_print_date'] = $this->language->get('text_print_date');

    $data['column_product'] = $this->language->get('column_product');
    $data['column_quantity'] = $this->language->get('column_quantity');
    $data['column_unit'] = $this->language->get('column_unit');
    $data['column_unit_cost'] = $this->language->get('column_unit_cost');
    $data['column_total_cost'] = $this->language->get('column_total_cost');

    // Load the print view
    $this->response->setOutput($this->load->view('purchase/goods_receipt_print', $data));
}

/**
 * Get order items with receipt information for goods receipt form
 */
public function getOrderItemsWithReceiptInfo() {
    // Check permission
    if (!$this->user->hasPermission('access', 'purchase/purchase_order')) {
        $json['error'] = $this->language->get('error_permission');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    $json = array();

    if (!isset($this->request->get['po_id'])) {
        $json['error'] = $this->language->get('error_po_id_required');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    $po_id = (int)$this->request->get['po_id'];

    $this->load->model('purchase/order');

    // Get order info
    $order_info = $this->model_purchase_order->getOrder($po_id);
    if (!$order_info) {
        $json['error'] = $this->language->get('error_po_not_found');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    // Get items with receipt info
    $items = $this->model_purchase_order->getOrderItemsWithReceiptInfo($po_id);

    $json['po_number'] = $order_info['po_number'];
    $json['supplier_name'] = $order_info['supplier_name'];
    $json['items'] = array();

    if (!empty($items)) {
        foreach ($items as $item) {
            // Only add items that have remaining quantity to receive
            if ($item['remaining_quantity'] > 0) {
                $json['items'][] = array(
                    'po_item_id' => $item['po_item_id'],
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => $item['received_quantity'],
                    'remaining_quantity' => $item['remaining_quantity'],
                    'unit_id' => $item['unit_id'],
                    'unit_name' => $item['unit_name'],
                    'unit_price' => $item['unit_price']
                );
            }
        }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}