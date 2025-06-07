<?php
class ControllerPurchasePurchaseReturn extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('purchase/purchase_return');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->getList();
    }

    public function list() {
        $this->load->language('purchase/purchase_return');

        $this->response->setOutput($this->getList());
    }

    protected function getList() {
        if (isset($this->request->get['filter_return_id'])) {
            $filter_return_id = $this->request->get['filter_return_id'];
        } else {
            $filter_return_id = '';
        }

        if (isset($this->request->get['filter_po_id'])) {
            $filter_po_id = $this->request->get['filter_po_id'];
        } else {
            $filter_po_id = '';
        }

        if (isset($this->request->get['filter_supplier'])) {
            $filter_supplier = $this->request->get['filter_supplier'];
        } else {
            $filter_supplier = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = '';
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'r.return_id';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_return_id'])) {
            $url .= '&filter_return_id=' . urlencode($this->request->get['filter_return_id']);
        }

        if (isset($this->request->get['filter_po_id'])) {
            $url .= '&filter_po_id=' . urlencode($this->request->get['filter_po_id']);
        }

        if (isset($this->request->get['filter_supplier'])) {
            $url .= '&filter_supplier=' . urlencode($this->request->get['filter_supplier']);
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

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('purchase/purchase_return/form', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('purchase/purchase_return/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['returns'] = array();

        $filter_data = array(
            'filter_return_id'    => $filter_return_id,
            'filter_po_id'        => $filter_po_id,
            'filter_supplier'     => $filter_supplier,
            'filter_status'       => $filter_status,
            'filter_date_start'   => $filter_date_start,
            'filter_date_end'     => $filter_date_end,
            'sort'                => $sort,
            'order'               => $order,
            'start'               => ($page - 1) * $this->config->get('config_pagination_admin'),
            'limit'               => $this->config->get('config_pagination_admin')
        );

        $this->load->model('purchase/purchase_return');

        $return_total = $this->model_purchase_purchase_return->getTotalReturns($filter_data);
        $results = $this->model_purchase_purchase_return->getReturns($filter_data);

        foreach ($results as $result) {
            $data['returns'][] = array(
                'return_id'    => $result['return_id'],
                'po_number'    => $result['po_number'],
                'supplier'     => $result['supplier'],
                'created_by'   => $result['created_by'],
                'return_date'  => date($this->language->get('date_format_short'), strtotime($result['return_date'])),
                'status'       => $this->getStatusText($result['status']),
                'status_id'    => $result['status'],
                'view'         => $this->url->link('purchase/purchase_return|view', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $result['return_id'] . $url, true),
                'edit'         => $this->url->link('purchase/purchase_return|form', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $result['return_id'] . $url, true),
                'print'        => $this->url->link('purchase/purchase_return|print', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $result['return_id'] . $url, true),
                'download'     => $this->url->link('purchase/purchase_return|download', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $result['return_id'] . $url, true),
                'approve'      => $this->url->link('purchase/purchase_return|approve', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $result['return_id'] . $url, true),
                'credit_note'  => $this->url->link('purchase/purchase_return|generateCreditNote', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $result['return_id'] . $url, true),
                'replacement'  => $this->url->link('purchase/purchase_return|requestReplacement', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $result['return_id'] . $url, true)
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

        if (isset($this->request->get['filter_return_id'])) {
            $url .= '&filter_return_id=' . urlencode($this->request->get['filter_return_id']);
        }

        if (isset($this->request->get['filter_po_id'])) {
            $url .= '&filter_po_id=' . urlencode($this->request->get['filter_po_id']);
        }

        if (isset($this->request->get['filter_supplier'])) {
            $url .= '&filter_supplier=' . urlencode($this->request->get['filter_supplier']);
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

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_return_id'] = $this->url->link('purchase/purchase_return/list', 'user_token=' . $this->session->data['user_token'] . '&sort=r.return_id' . $url, true);
        $data['sort_po_number'] = $this->url->link('purchase/purchase_return/list', 'user_token=' . $this->session->data['user_token'] . '&sort=po.po_number' . $url, true);
        $data['sort_supplier'] = $this->url->link('purchase/purchase_return/list', 'user_token=' . $this->session->data['user_token'] . '&sort=s.name' . $url, true);
        $data['sort_created_by'] = $this->url->link('purchase/purchase_return/list', 'user_token=' . $this->session->data['user_token'] . '&sort=u.username' . $url, true);
        $data['sort_return_date'] = $this->url->link('purchase/purchase_return/list', 'user_token=' . $this->session->data['user_token'] . '&sort=r.return_date' . $url, true);
        $data['sort_status'] = $this->url->link('purchase/purchase_return/list', 'user_token=' . $this->session->data['user_token'] . '&sort=r.status' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_return_id'])) {
            $url .= '&filter_return_id=' . urlencode($this->request->get['filter_return_id']);
        }

        if (isset($this->request->get['filter_po_id'])) {
            $url .= '&filter_po_id=' . urlencode($this->request->get['filter_po_id']);
        }

        if (isset($this->request->get['filter_supplier'])) {
            $url .= '&filter_supplier=' . urlencode($this->request->get['filter_supplier']);
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

        $data['pagination'] = $this->load->controller('common/pagination', array(
            'total' => $return_total,
            'page'  => $page,
            'limit' => $this->config->get('config_pagination_admin'),
            'url'   => $this->url->link('purchase/purchase_return/list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true)
        ));

        $data['results'] = sprintf($this->language->get('text_pagination'), ($return_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($return_total - $this->config->get('config_pagination_admin'))) ? $return_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $return_total, ceil($return_total / $this->config->get('config_pagination_admin')));

        $data['filter_return_id'] = $filter_return_id;
        $data['filter_po_id'] = $filter_po_id;
        $data['filter_supplier'] = $filter_supplier;
        $data['filter_status'] = $filter_status;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        $data['sort'] = $sort;
        $data['order'] = $order;

        return $this->load->view('purchase/purchase_return_list', $data);
    }

    public function form() {
        $this->load->language('purchase/purchase_return');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['text_form'] = !isset($this->request->get['return_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        $url = '';

        if (isset($this->request->get['filter_return_id'])) {
            $url .= '&filter_return_id=' . urlencode($this->request->get['filter_return_id']);
        }

        if (isset($this->request->get['filter_po_id'])) {
            $url .= '&filter_po_id=' . urlencode($this->request->get['filter_po_id']);
        }

        if (isset($this->request->get['filter_supplier'])) {
            $url .= '&filter_supplier=' . urlencode($this->request->get['filter_supplier']);
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

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['return_id'])) {
            $data['action'] = $this->url->link('purchase/purchase_return/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('purchase/purchase_return/edit', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $this->request->get['return_id'] . $url, true);
        }

        $data['save'] = $this->url->link('purchase/purchase_return/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['return_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $this->load->model('purchase/purchase_return');
            $return_info = $this->model_purchase_purchase_return->getReturn($this->request->get['return_id']);
            $return_items = $this->model_purchase_purchase_return->getReturnItems($this->request->get['return_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->get['return_id'])) {
            $data['return_id'] = $this->request->get['return_id'];
        } else {
            $data['return_id'] = 0;
        }

        if (isset($this->request->post['purchase_order_id'])) {
            $data['purchase_order_id'] = $this->request->post['purchase_order_id'];
        } elseif (!empty($return_info)) {
            $data['purchase_order_id'] = $return_info['purchase_order_id'];
        } else {
            $data['purchase_order_id'] = 0;
        }

        if (isset($this->request->post['po_number'])) {
            $data['po_number'] = $this->request->post['po_number'];
        } elseif (!empty($return_info)) {
            $data['po_number'] = $return_info['po_number'];
        } else {
            $data['po_number'] = '';
        }

        if (isset($this->request->post['goods_receipt_id'])) {
            $data['goods_receipt_id'] = $this->request->post['goods_receipt_id'];
        } elseif (!empty($return_info)) {
            $data['goods_receipt_id'] = $return_info['goods_receipt_id'];
        } else {
            $data['goods_receipt_id'] = 0;
        }

        if (isset($this->request->post['receipt_number'])) {
            $data['receipt_number'] = $this->request->post['receipt_number'];
        } elseif (!empty($return_info)) {
            $data['receipt_number'] = $return_info['receipt_number'];
        } else {
            $data['receipt_number'] = '';
        }

        if (isset($this->request->post['supplier_id'])) {
            $data['supplier_id'] = $this->request->post['supplier_id'];
        } elseif (!empty($return_info)) {
            $data['supplier_id'] = $return_info['supplier_id'];
        } else {
            $data['supplier_id'] = 0;
        }

        if (isset($this->request->post['supplier'])) {
            $data['supplier'] = $this->request->post['supplier'];
        } elseif (!empty($return_info)) {
            $data['supplier'] = $return_info['supplier'];
        } else {
            $data['supplier'] = '';
        }

        if (isset($this->request->post['return_date'])) {
            $data['return_date'] = $this->request->post['return_date'];
        } elseif (!empty($return_info)) {
            $data['return_date'] = date($this->language->get('date_format_short'), strtotime($return_info['return_date']));
        } else {
            $data['return_date'] = date($this->language->get('date_format_short'), time());
        }

        if (isset($this->request->post['reference'])) {
            $data['reference'] = $this->request->post['reference'];
        } elseif (!empty($return_info)) {
            $data['reference'] = $return_info['reference'];
        } else {
            $data['reference'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($return_info)) {
            $data['status'] = $return_info['status'];
        } else {
            $data['status'] = 1; // Pending
        }

        if (isset($this->request->post['return_type'])) {
            $data['return_type'] = $this->request->post['return_type'];
        } elseif (!empty($return_info)) {
            $data['return_type'] = $return_info['return_type'];
        } else {
            $data['return_type'] = 'credit'; // Credit Note
        }

        if (isset($this->request->post['notes'])) {
            $data['notes'] = $this->request->post['notes'];
        } elseif (!empty($return_info)) {
            $data['notes'] = $return_info['notes'];
        } else {
            $data['notes'] = '';
        }

        if (isset($this->request->post['return_item'])) {
            $data['return_items'] = $this->request->post['return_item'];
        } elseif (!empty($return_items)) {
            $data['return_items'] = $return_items;
        } else {
            $data['return_items'] = array();
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/purchase_return_form', $data));
    }

    public function purchaseOrders() {
        $this->load->language('purchase/purchase_return');

        $this->load->model('purchase/purchase_order');

        if (isset($this->request->get['filter_po_number'])) {
            $filter_po_number = $this->request->get['filter_po_number'];
        } else {
            $filter_po_number = '';
        }

        $filter_data = array(
            'filter_po_number' => $filter_po_number,
            'filter_status'    => array(2, 3, 4), // Approved, Received, Partially Received
            'start'            => 0,
            'limit'            => 10
        );

        $results = $this->model_purchase_purchase_order->getPurchaseOrders($filter_data);

        $data['purchase_orders'] = array();

        foreach ($results as $result) {
            $data['purchase_orders'][] = array(
                'purchase_order_id' => $result['purchase_order_id'],
                'po_number'         => $result['po_number'],
                'supplier'          => $result['supplier'],
                'supplier_id'       => $result['supplier_id'],
                'total'             => $this->currency->format($result['total'], $result['currency_code']),
                'status'            => $result['status'],
                'date_added'        => date($this->language->get('date_format_short'), strtotime($result['date_added']))
            );
        }

        $this->response->setOutput($this->load->view('purchase/purchase_order_list_mini', $data));
    }

    public function goodsReceipts() {
        $this->load->language('purchase/purchase_return');

        $this->load->model('purchase/goods_receipt');

        if (!isset($this->request->get['purchase_order_id'])) {
            $this->response->setOutput($this->language->get('error_purchase_order'));
            return;
        }

        $purchase_order_id = $this->request->get['purchase_order_id'];

        $filter_data = array(
            'filter_purchase_order_id' => $purchase_order_id,
            'filter_status'            => array(2), // Completed only
            'start'                    => 0,
            'limit'                    => 10
        );

        $results = $this->model_purchase_goods_receipt->getGoodsReceipts($filter_data);

        $data['receipts'] = array();

        foreach ($results as $result) {
            $data['receipts'][] = array(
                'receipt_id'     => $result['receipt_id'],
                'receipt_number' => $result['receipt_number'],
                'reference'      => $result['reference'],
                'receipt_date'   => date($this->language->get('date_format_short'), strtotime($result['receipt_date'])),
                'status'         => $result['status']
            );
        }

        $this->response->setOutput($this->load->view('purchase/goods_receipt_list_mini', $data));
    }

    public function receiptItems() {
        $this->load->language('purchase/purchase_return');

        $json = array();

        if (!isset($this->request->get['goods_receipt_id'])) {
            $json['error'] = $this->language->get('error_goods_receipt');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $this->load->model('purchase/goods_receipt');

        $goods_receipt_id = $this->request->get['goods_receipt_id'];
        $items = $this->model_purchase_goods_receipt->getGoodsReceiptItems($goods_receipt_id);

        $json = array();

        foreach ($items as $item) {
            $json[] = array(
                'receipt_item_id'    => $item['receipt_item_id'],
                'product_id'         => $item['product_id'],
                'product_name'       => $item['name'],
                'model'              => $item['model'],
                'received_quantity'  => $item['received_quantity'],
                'unit_cost'          => $item['unit_cost']
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function availableItems() {
        $this->load->language('purchase/purchase_return');

        if (!isset($this->request->get['goods_receipt_id'])) {
            $this->response->setOutput($this->language->get('error_goods_receipt'));
            return;
        }

        $goods_receipt_id = $this->request->get['goods_receipt_id'];

        $this->load->model('purchase/goods_receipt');
        $items = $this->model_purchase_goods_receipt->getGoodsReceiptItems($goods_receipt_id);

        $data['items'] = array();

        foreach ($items as $item) {
            $data['items'][] = array(
                'receipt_item_id'    => $item['receipt_item_id'],
                'product_id'         => $item['product_id'],
                'product_name'       => $item['name'],
                'model'              => $item['model'],
                'received_quantity'  => $item['received_quantity'],
                'unit_cost'          => $item['unit_cost']
            );
        }

        $this->response->setOutput($this->load->view('purchase/receipt_items_mini', $data));
    }

    public function save() {
        $this->load->language('purchase/purchase_return');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/purchase_return')) {
            $json['error']['warning'] = $this->language->get('error_permission');
        }

        if (!isset($this->request->post['purchase_order_id']) || !$this->request->post['purchase_order_id']) {
            $json['error']['purchase_order'] = $this->language->get('error_purchase_order');
        }

        if (!isset($this->request->post['goods_receipt_id']) || !$this->request->post['goods_receipt_id']) {
            $json['error']['goods_receipt'] = $this->language->get('error_goods_receipt');
        }

        if (!isset($this->request->post['return_date']) || $this->request->post['return_date'] == '') {
            $json['error']['return_date'] = $this->language->get('error_return_date');
        }

        if (!isset($this->request->post['reference']) || $this->request->post['reference'] == '') {
            $json['error']['reference'] = $this->language->get('error_reference');
        }

        // Check return items
        if (!isset($this->request->post['return_item']) || !$this->request->post['return_item']) {
            $json['error']['items'] = $this->language->get('error_no_items');
        } else {
            foreach ($this->request->post['return_item'] as $key => $item) {
                if ((int)$item['return_quantity'] <= 0) {
                    $json['error']['item-' . $key . '-return_quantity'] = $this->language->get('error_quantity_positive');
                }

                if (!isset($item['reason']) || $item['reason'] == '') {
                    $json['error']['item-' . $key . '-reason'] = $this->language->get('error_reason_required');
                }
            }
        }

        if (!$json) {
            $this->load->model('purchase/purchase_return');

            if (!isset($this->request->post['return_id']) || $this->request->post['return_id'] == '') {
                $json['return_id'] = $this->model_purchase_purchase_return->addReturn($this->request->post);
                $json['success'] = $this->language->get('text_success');
            } else {
                $this->model_purchase_purchase_return->editReturn($this->request->post['return_id'], $this->request->post);
                $json['success'] = $this->language->get('text_success');
            }

            $json['redirect'] = $this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('purchase/purchase_return');

        $json = array();

        if (isset($this->request->post['selected'])) {
            $selected = $this->request->post['selected'];
        } else {
            $selected = array();
        }

        if (!$this->user->hasPermission('modify', 'purchase/purchase_return')) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase_return');

            foreach ($selected as $return_id) {
                $this->model_purchase_purchase_return->deleteReturn($return_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');
        }

        $this->response->redirect($this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function approve() {
        $this->load->language('purchase/purchase_return');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/purchase_return')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!isset($this->request->get['return_id'])) {
            $json['error'] = $this->language->get('error_return_id');
        }

        if (!$json) {
            $this->load->model('purchase/purchase_return');

            $return_info = $this->model_purchase_purchase_return->getReturn($this->request->get['return_id']);

            if (!$return_info) {
                $json['error'] = $this->language->get('error_return_not_found');
            } elseif ($return_info['status'] != 1) { // Not Pending
                $json['error'] = $this->language->get('error_return_complete');
            }
        }

        if (!$json) {
            $this->model_purchase_purchase_return->approveReturn($this->request->get['return_id']);

            $json['success'] = $this->language->get('text_success');
            $json['redirect'] = $this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function view() {
        $this->load->language('purchase/purchase_return');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_view'),
            'href' => $this->url->link('purchase/purchase_return|view', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $this->request->get['return_id'], true)
        );
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (!isset($this->request->get['return_id'])) {
            $this->response->redirect($this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->load->model('purchase/purchase_return');
        
        $return_info = $this->model_purchase_purchase_return->getReturn($this->request->get['return_id']);
        
        if (!$return_info) {
            $this->response->redirect($this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['heading_title'] = $this->language->get('heading_title');
        
        $data['text_view'] = $this->language->get('text_view');
        $data['text_return_details'] = $this->language->get('text_return_details');
        $data['text_return_number'] = $this->language->get('text_return_number');
        $data['text_order_number'] = $this->language->get('text_order_number');
        $data['text_receipt_number'] = $this->language->get('text_receipt_number');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_status'] = $this->language->get('text_status');
        $data['text_date_added'] = $this->language->get('text_date_added');
        $data['text_reason'] = $this->language->get('text_reason');
        $data['text_note'] = $this->language->get('text_note');
        $data['text_return_items'] = $this->language->get('text_return_items');
        $data['text_total_amount'] = $this->language->get('text_total_amount');
        $data['text_history'] = $this->language->get('text_history');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_pending'] = $this->language->get('text_pending');
        $data['text_approved'] = $this->language->get('text_approved');
        $data['text_rejected'] = $this->language->get('text_rejected');
        $data['text_completed'] = $this->language->get('text_completed');
        $data['text_canceled'] = $this->language->get('text_canceled');
        $data['text_confirm'] = $this->language->get('text_confirm');
        
        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_user'] = $this->language->get('column_user');
        $data['column_comment'] = $this->language->get('column_comment');
        
        $data['button_approve'] = $this->language->get('button_approve');
        $data['button_reject'] = $this->language->get('button_reject');
        $data['button_print'] = $this->language->get('button_print');
        $data['button_back'] = $this->language->get('button_back');
        $data['button_create_credit_note'] = $this->language->get('button_create_credit_note');
        
        $data['return'] = $return_info;
        
        // Format date
        $data['return']['date_added'] = date($this->language->get('date_format_short'), strtotime($return_info['date_added']));
        
        // Get return items
        $return_items = $this->model_purchase_purchase_return->getReturnItems($this->request->get['return_id']);
        $data['return_items'] = $return_items;
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($return_items as $item) {
            $total_amount += $item['total'];
        }
        
        $data['total_amount'] = $this->currency->format($total_amount, $this->config->get('config_currency'));
        
        // Get return histories
        $data['histories'] = $this->model_purchase_purchase_return->getReturnHistories($this->request->get['return_id']);
        
        // Format history dates
        foreach ($data['histories'] as &$history) {
            $history['date_added'] = date($this->language->get('datetime_format'), strtotime($history['date_added']));
        }
        
        // Actions
        $data['back'] = $this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true);
        $data['print'] = $this->url->link('purchase/purchase_return|print', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $this->request->get['return_id'], true);
        
        if ($return_info['status'] == 'pending') {
            $data['approve'] = $this->url->link('purchase/purchase_return|approve', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $this->request->get['return_id'], true);
            $data['reject'] = $this->url->link('purchase/purchase_return|reject', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $this->request->get['return_id'], true);
        }
        
        if ($return_info['status'] == 'approved') {
            $data['credit_note'] = $this->url->link('purchase/purchase_return|generateCreditNote', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $this->request->get['return_id'], true);
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('purchase/purchase_return_view', $data));
    }

    public function reject() {
        $this->load->language('purchase/purchase_return');

        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/purchase_return')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!isset($this->request->get['return_id'])) {
            $json['error'] = $this->language->get('error_return_id');
        }

        if (!$json) {
            $this->load->model('purchase/purchase_return');

            $return_info = $this->model_purchase_purchase_return->getReturn($this->request->get['return_id']);

            if (!$return_info) {
                $json['error'] = $this->language->get('error_return_not_found');
            } elseif ($return_info['status'] != 'pending') { // Not Pending
                $json['error'] = $this->language->get('error_already_processed');
            }
        }

        if (!$json) {
            // Perform rejection
            $this->model_purchase_purchase_return->rejectReturn($this->request->get['return_id']);

            $this->session->data['success'] = $this->language->get('text_reject_success');
            
            $json['success'] = $this->language->get('text_reject_success');
            $json['redirect'] = $this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function print() {
        $this->load->language('purchase/purchase_return');
        
        if (isset($this->request->get['return_id'])) {
            $return_id = $this->request->get['return_id'];
        } else {
            $return_id = 0;
        }
        
        $this->load->model('purchase/purchase_return');
        
        $return_info = $this->model_purchase_purchase_return->getReturn($return_id);
        
        if ($return_info) {
            $this->document->setTitle($this->language->get('text_return') . ' - ' . $return_info['return_number']);
            
            $data['title'] = $this->language->get('text_return') . ' - ' . $return_info['return_number'];
            
            $data['base'] = HTTP_SERVER;
            $data['direction'] = $this->language->get('direction');
            $data['lang'] = $this->language->get('code');
            
            $data['text_return'] = $this->language->get('text_return');
            $data['text_return_details'] = $this->language->get('text_return_details');
            $data['text_return_number'] = $this->language->get('text_return_number');
            $data['text_order_number'] = $this->language->get('text_order_number');
            $data['text_receipt_number'] = $this->language->get('text_receipt_number');
            $data['text_supplier'] = $this->language->get('text_supplier');
            $data['text_status'] = $this->language->get('text_status');
            $data['text_date_added'] = $this->language->get('text_date_added');
            $data['text_reason'] = $this->language->get('text_reason');
            $data['text_note'] = $this->language->get('text_note');
            $data['text_return_items'] = $this->language->get('text_return_items');
            $data['text_total_amount'] = $this->language->get('text_total_amount');
            
            $data['column_product'] = $this->language->get('column_product');
            $data['column_quantity'] = $this->language->get('column_quantity');
            $data['column_unit'] = $this->language->get('column_unit');
            $data['column_unit_price'] = $this->language->get('column_unit_price');
            $data['column_total'] = $this->language->get('column_total');
            
            $data['return'] = $return_info;
            
            // Format date
            $data['return']['date_added'] = date($this->language->get('date_format_short'), strtotime($return_info['date_added']));
            
            // Get return items
            $return_items = $this->model_purchase_purchase_return->getReturnItems($return_id);
            $data['return_items'] = $return_items;
            
            // Calculate total amount
            $total_amount = 0;
            foreach ($return_items as $item) {
                $total_amount += $item['total'];
            }
            
            $data['total_amount'] = $this->currency->format($total_amount, $this->config->get('config_currency'));
            
            // Get store info
            $this->load->model('setting/setting');
            
            $store_info = $this->model_setting_setting->getSetting('config', 0);
            
            if ($store_info) {
                $data['store_name'] = $store_info['config_name'];
                $data['store_address'] = nl2br($store_info['config_address']);
                $data['store_email'] = $store_info['config_email'];
                $data['store_telephone'] = $store_info['config_telephone'];
            } else {
                $data['store_name'] = '';
                $data['store_address'] = '';
                $data['store_email'] = '';
                $data['store_telephone'] = '';
            }
            
            $this->response->setOutput($this->load->view('purchase/purchase_return_print', $data));
        } else {
            return new Action('error/not_found');
        }
    }
    
    public function generateCreditNote() {
        $this->load->language('purchase/purchase_return');
        
        if (!$this->user->hasPermission('modify', 'purchase/purchase_return')) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        if (!isset($this->request->get['return_id'])) {
            $this->response->redirect($this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->load->model('purchase/purchase_return');
        
        $return_info = $this->model_purchase_purchase_return->getReturn($this->request->get['return_id']);
        
        if (!$return_info || $return_info['status'] != 'approved') {
            $this->session->data['error_warning'] = $this->language->get('error_not_approved');
            $this->response->redirect($this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Generate credit note logic would go here
        $result = $this->model_purchase_purchase_return->generateCreditNote($this->request->get['return_id']);
        
        if ($result) {
            $this->session->data['success'] = $this->language->get('text_credit_note_success');
        } else {
            $this->session->data['error_warning'] = $this->language->get('error_credit_note');
        }
        
        $this->response->redirect($this->url->link('purchase/purchase_return|view', 'user_token=' . $this->session->data['user_token'] . '&return_id=' . $this->request->get['return_id'], true));
    }

    private function getStatusText($status_id) {
        switch ($status_id) {
            case 1:
                return $this->language->get('text_pending');
            case 2:
                return $this->language->get('text_completed');
            case 3:
                return $this->language->get('text_cancelled');
            default:
                return '';
        }
    }
} 