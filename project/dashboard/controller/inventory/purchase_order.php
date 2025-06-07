<?php
/**
 * إدارة طلبات الشراء
 * يستخدم لإدارة طلبات الشراء وتجديد المخزون
 */
class ControllerInventoryPurchaseOrder extends Controller {
    private $error = array();

    /**
     * عرض صفحة طلبات الشراء
     */
    public function index() {
        $this->load->language('inventory/purchase_order');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/purchase_order');

        $this->getList();
    }

    /**
     * عرض قائمة طلبات الشراء
     */
    protected function getList() {
        if (isset($this->request->get['filter_po_number'])) {
            $filter_po_number = $this->request->get['filter_po_number'];
        } else {
            $filter_po_number = '';
        }

        if (isset($this->request->get['filter_supplier'])) {
            $filter_supplier = $this->request->get['filter_supplier'];
        } else {
            $filter_supplier = '';
        }

        if (isset($this->request->get['filter_branch'])) {
            $filter_branch = $this->request->get['filter_branch'];
        } else {
            $filter_branch = '';
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

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'po.date_added';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_po_number'])) {
            $url .= '&filter_po_number=' . urlencode($this->request->get['filter_po_number']);
        }

        if (isset($this->request->get['filter_supplier'])) {
            $url .= '&filter_supplier=' . $this->request->get['filter_supplier'];
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/purchase_order', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        $data['add'] = $this->url->link('inventory/purchase_order/add', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['delete'] = $this->url->link('inventory/purchase_order/delete', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['export'] = $this->url->link('inventory/purchase_order/export', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['reorder_report'] = $this->url->link('inventory/stock_level/reorderReport', 'user_token=' . $this->session->data['user_token']);

        $filter_data = array(
            'filter_po_number'    => $filter_po_number,
            'filter_supplier'     => $filter_supplier,
            'filter_branch'       => $filter_branch,
            'filter_date_from'    => $filter_date_from,
            'filter_date_to'      => $filter_date_to,
            'filter_status'       => $filter_status,
            'sort'                => $sort,
            'order'               => $order,
            'start'               => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'               => $this->config->get('config_limit_admin')
        );

        $purchase_order_total = $this->model_inventory_purchase_order->getTotalPurchaseOrders($filter_data);
        $purchase_orders = $this->model_inventory_purchase_order->getPurchaseOrders($filter_data);

        $data['purchase_orders'] = array();

        foreach ($purchase_orders as $purchase_order) {
            $data['purchase_orders'][] = array(
                'purchase_order_id' => $purchase_order['purchase_order_id'],
                'po_number'       => $purchase_order['po_number'],
                'supplier_name'   => $purchase_order['supplier_name'],
                'branch_name'     => $purchase_order['branch_name'],
                'order_date'      => date($this->language->get('date_format_short'), strtotime($purchase_order['order_date'])),
                'expected_date'   => date($this->language->get('date_format_short'), strtotime($purchase_order['expected_date'])),
                'total_amount'    => $this->currency->format($purchase_order['total_amount'], $this->config->get('config_currency')),
                'total_items'     => $purchase_order['total_items'],
                'status'          => $purchase_order['status'],
                'status_text'     => $this->language->get('text_status_' . $purchase_order['status']),
                'created_by_name' => $purchase_order['created_by_name'],
                'date_added'      => date($this->language->get('date_format_short'), strtotime($purchase_order['date_added'])),
                'view'            => $this->url->link('inventory/purchase_order/view', 'user_token=' . $this->session->data['user_token'] . '&purchase_order_id=' . $purchase_order['purchase_order_id'] . $url),
                'edit'            => $this->url->link('inventory/purchase_order/edit', 'user_token=' . $this->session->data['user_token'] . '&purchase_order_id=' . $purchase_order['purchase_order_id'] . $url),
                'print'           => $this->url->link('inventory/purchase_order/print', 'user_token=' . $this->session->data['user_token'] . '&purchase_order_id=' . $purchase_order['purchase_order_id'] . $url),
                'approve'         => $this->url->link('inventory/purchase_order/approve', 'user_token=' . $this->session->data['user_token'] . '&purchase_order_id=' . $purchase_order['purchase_order_id'] . $url),
                'receive'         => $this->url->link('inventory/purchase_order/receive', 'user_token=' . $this->session->data['user_token'] . '&purchase_order_id=' . $purchase_order['purchase_order_id'] . $url),
                'cancel'          => $this->url->link('inventory/purchase_order/cancel', 'user_token=' . $this->session->data['user_token'] . '&purchase_order_id=' . $purchase_order['purchase_order_id'] . $url)
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

        if (isset($this->request->get['filter_po_number'])) {
            $url .= '&filter_po_number=' . urlencode($this->request->get['filter_po_number']);
        }

        if (isset($this->request->get['filter_supplier'])) {
            $url .= '&filter_supplier=' . $this->request->get['filter_supplier'];
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_po_number'] = $this->url->link('inventory/purchase_order', 'user_token=' . $this->session->data['user_token'] . '&sort=po.po_number' . $url);
        $data['sort_supplier'] = $this->url->link('inventory/purchase_order', 'user_token=' . $this->session->data['user_token'] . '&sort=s.name' . $url);
        $data['sort_branch'] = $this->url->link('inventory/purchase_order', 'user_token=' . $this->session->data['user_token'] . '&sort=b.name' . $url);
        $data['sort_order_date'] = $this->url->link('inventory/purchase_order', 'user_token=' . $this->session->data['user_token'] . '&sort=po.order_date' . $url);
        $data['sort_expected_date'] = $this->url->link('inventory/purchase_order', 'user_token=' . $this->session->data['user_token'] . '&sort=po.expected_date' . $url);
        $data['sort_status'] = $this->url->link('inventory/purchase_order', 'user_token=' . $this->session->data['user_token'] . '&sort=po.status' . $url);
        $data['sort_date_added'] = $this->url->link('inventory/purchase_order', 'user_token=' . $this->session->data['user_token'] . '&sort=po.date_added' . $url);

        $url = '';

        if (isset($this->request->get['filter_po_number'])) {
            $url .= '&filter_po_number=' . urlencode($this->request->get['filter_po_number']);
        }

        if (isset($this->request->get['filter_supplier'])) {
            $url .= '&filter_supplier=' . $this->request->get['filter_supplier'];
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_from'])) {
            $url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $purchase_order_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/purchase_order', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($purchase_order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($purchase_order_total - $this->config->get('config_limit_admin'))) ? $purchase_order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $purchase_order_total, ceil($purchase_order_total / $this->config->get('config_limit_admin')));

        $data['filter_po_number'] = $filter_po_number;
        $data['filter_supplier'] = $filter_supplier;
        $data['filter_branch'] = $filter_branch;
        $data['filter_date_from'] = $filter_date_from;
        $data['filter_date_to'] = $filter_date_to;
        $data['filter_status'] = $filter_status;

        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        $data['purchase_order_statuses'] = array(
            '' => $this->language->get('text_all_status'),
            'draft' => $this->language->get('text_status_draft'),
            'pending' => $this->language->get('text_status_pending'),
            'approved' => $this->language->get('text_status_approved'),
            'ordered' => $this->language->get('text_status_ordered'),
            'partial' => $this->language->get('text_status_partial'),
            'received' => $this->language->get('text_status_received'),
            'cancelled' => $this->language->get('text_status_cancelled')
        );

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/purchase_order_list', $data));
    }

    /**
     * إضافة طلب شراء جديد
     */
    public function add() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * تعديل طلب شراء
     */
    public function edit() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * عرض تفاصيل طلب الشراء
     */
    public function view() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * طباعة طلب الشراء
     */
    public function print() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * الموافقة على طلب الشراء
     */
    public function approve() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * استلام طلب الشراء
     */
    public function receive() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * إلغاء طلب الشراء
     */
    public function cancel() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * حذف طلب شراء
     */
    public function delete() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * تصدير بيانات طلبات الشراء
     */
    public function export() {
        // سيتم تنفيذه لاحقًا
    }
}
