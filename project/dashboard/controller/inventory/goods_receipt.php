<?php
class ControllerInventoryGoodsReceipt extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('inventory/goods_receipt');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/goods_receipt');
        $data['user_token'] = $this->session->data['user_token'];
        $this->getForm();
    }

    public function add() {
        $this->load->language('inventory/goods_receipt');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/goods_receipt');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_goods_receipt->addGoodsReceipt($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('inventory/goods_receipt', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    protected function getForm() {
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_form'] = !isset($this->request->get['receipt_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        $data['entry_receipt_number'] = $this->language->get('entry_receipt_number');
        $data['entry_receipt_date'] = $this->language->get('entry_receipt_date');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_notes'] = $this->language->get('entry_notes');
        $data['entry_purchase_order'] = $this->language->get('entry_purchase_order');
        $data['user_token'] = $this->session->data['user_token'];

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['receipt_number'])) {
            $data['error_receipt_number'] = $this->error['receipt_number'];
        } else {
            $data['error_receipt_number'] = '';
        }

        if (isset($this->error['receipt_date'])) {
            $data['error_receipt_date'] = $this->error['receipt_date'];
        } else {
            $data['error_receipt_date'] = '';
        }

        $this->load->model('purchase/order');
        $data['purchase_orders'] = $this->model_purchase_order->getPurchaseOrders(); // الحصول على أوامر الشراء

        $url = '';

        if (!isset($this->request->get['receipt_id'])) {
            $data['action'] = $this->url->link('inventory/goods_receipt/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('inventory/goods_receipt/edit', 'user_token=' . $this->session->data['user_token'] . '&receipt_id=' . $this->request->get['receipt_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('inventory/goods_receipt', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->post['receipt_number'])) {
            $data['receipt_number'] = $this->request->post['receipt_number'];
        } else {
            $data['receipt_number'] = '';
        }

        if (isset($this->request->post['receipt_date'])) {
            $data['receipt_date'] = $this->request->post['receipt_date'];
        } else {
            $data['receipt_date'] = date('Y-m-d');
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } else {
            $data['status'] = 'draft';
        }

        if (isset($this->request->post['notes'])) {
            $data['notes'] = $this->request->post['notes'];
        } else {
            $data['notes'] = '';
        }

        if (isset($this->request->post['purchase_order_id'])) {
            $data['purchase_order_id'] = $this->request->post['purchase_order_id'];
        } else {
            $data['purchase_order_id'] = '';
        }

        if (isset($this->request->post['receipt_items'])) {
            $data['receipt_items'] = $this->request->post['receipt_items'];
        } else {
            $data['receipt_items'] = array();
        }

        $this->load->model('catalog/product');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/goods_receipt_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/goods_receipt')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['receipt_number']) < 3) || (utf8_strlen($this->request->post['receipt_number']) > 64)) {
            $this->error['receipt_number'] = $this->language->get('error_receipt_number');
        }

        if (empty($this->request->post['receipt_date'])) {
            $this->error['receipt_date'] = $this->language->get('error_receipt_date');
        }

        return !$this->error;
    }
}
