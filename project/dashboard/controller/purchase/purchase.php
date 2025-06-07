<?php
class ControllerPurchasePurchase extends Controller {
    private $error = array();

    /* =====================================================
     *   1) SELECT2 AJAX METHODS
     * ===================================================== */
    public function select2Products() {
        $json = array();
        $this->load->model('catalog/product');

        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];
            $product = $this->model_catalog_product->getProduct($product_id);
            if ($product) {
                $json[] = array(
                    'id'    => $product['product_id'],
                    'text'  => $product['name'],
                    'units' => $product['units']
                );
            }
        } else {
            $q = isset($this->request->get['q']) ? $this->request->get['q'] : '';
            $filter_data = array(
                'filter_name' => $q,
                'start'       => 0,
                'limit'       => 100
            );
            $results = $this->model_catalog_product->getProducts($filter_data);
            foreach ($results as $res) {
                $json[] = array(
                    'id'    => $res['product_id'],
                    'text'  => $res['name'],
                    'units' => $res['units']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function select2Vendors() {
        $json = array();
        $this->load->model('purchase/purchase');

        if (isset($this->request->get['vendor_id'])) {
            $vendor_id = (int)$this->request->get['vendor_id'];
            $vendor = $this->model_purchase_purchase->getSingleVendor($vendor_id);
            if ($vendor) {
                $json[] = array(
                    'id'   => $vendor['supplier_id'],
                    'text' => $vendor['name']
                );
            }
        } else {
            $q = isset($this->request->get['q']) ? $this->request->get['q'] : '';
            $filter_data = array(
                'filter_name' => $q,
                'start'       => 0,
                'limit'       => 20
            );
            $results = $this->model_purchase_purchase->searchVendors($filter_data);
            foreach ($results as $res) {
                $json[] = array(
                    'id'   => $res['supplier_id'],
                    'text' => $res['name']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function select2PO() {
        $json = array();
        $this->load->model('purchase/purchase');

        if (isset($this->request->get['po_id'])) {
            $po_id = (int)$this->request->get['po_id'];
            $po = $this->model_purchase_purchase->getSinglePO($po_id);
            if ($po) {
                $json[] = array(
                    'id'   => $po['po_id'],
                    'text' => $po['po_number']
                );
            }
        } else {
            $q = isset($this->request->get['q']) ? $this->request->get['q'] : '';
            $filter_data = array(
                'filter_po_number' => $q,
                'start'            => 0,
                'limit'            => 20
            );
            $results = $this->model_purchase_purchase->searchPOs($filter_data);
            foreach ($results as $r) {
                $json[] = array(
                    'id'   => $r['po_id'],
                    'text' => $r['po_number']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function select2VendorInvoices() {
        $json = array();
        $this->load->model('purchase/purchase');

        $vendor_id  = isset($this->request->get['vendor_id']) ? (int)$this->request->get['vendor_id'] : 0;
        $invoice_id = isset($this->request->get['invoice_id']) ? (int)$this->request->get['invoice_id'] : 0;

        if ($invoice_id) {
            $inv = $this->model_purchase_purchase->getSingleInvoice($invoice_id);
            if ($inv) {
                $json[] = array(
                    'id'         => $inv['invoice_id'],
                    'text'       => $inv['invoice_number'],
                    'amount_due' => $inv['amount_due'],
                    'amount_pay' => $inv['amount_due']
                );
            }
        } else {
            $q = isset($this->request->get['q']) ? $this->request->get['q'] : '';
            $filter_data = array(
                'vendor_id'   => $vendor_id,
                'filter_name' => $q,
                'start'       => 0,
                'limit'       => 20
            );
            $results = $this->model_purchase_purchase->searchVendorInvoices($filter_data);
            foreach ($results as $res) {
                $json[] = array(
                    'id'         => $res['invoice_id'],
                    'text'       => $res['invoice_number'],
                    'amount_due' => $res['amount_due'],
                    'amount_pay' => $res['amount_due']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* =====================================================
     *   2) INDEX (الصفحة الرئيسية)
     * ===================================================== */
    public function index() {
        $this->load->language('purchase/purchase');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/purchase','user_token='.$this->session->data['user_token'],true)
        );

        $this->load->model('purchase/purchase');

        $data['user_purchase_requisition_view']    = $this->user->hasKey('purchase_requisition_view');
        $data['user_purchase_requisition_add']     = $this->user->hasKey('purchase_requisition_add');
        $data['user_purchase_requisition_edit']    = $this->user->hasKey('purchase_requisition_edit');
        $data['user_purchase_requisition_delete']  = $this->user->hasKey('purchase_requisition_delete');
        $data['user_purchase_requisition_approve'] = $this->user->hasKey('purchase_requisition_approve');
        $data['user_purchase_requisition_reject']  = $this->user->hasKey('purchase_requisition_reject');

        $data['user_quotation_view']    = $this->user->hasKey('quotation_view');
        $data['user_quotation_add']     = $this->user->hasKey('quotation_add');
        $data['user_quotation_edit']    = $this->user->hasKey('quotation_edit');
        $data['user_quotation_delete']  = $this->user->hasKey('quotation_delete');
        $data['user_quotation_approve'] = $this->user->hasKey('quotation_approve');
        $data['user_quotation_reject']  = $this->user->hasKey('quotation_reject');

        $data['user_purchase_order_view']    = $this->user->hasKey('purchase_order_view');
        $data['user_purchase_order_add']     = $this->user->hasKey('purchase_order_add');
        $data['user_purchase_order_edit']    = $this->user->hasKey('purchase_order_edit');
        $data['user_purchase_order_delete']  = $this->user->hasKey('purchase_order_delete');
        $data['user_purchase_order_approve'] = $this->user->hasKey('purchase_order_approve');
        $data['user_purchase_order_reject']  = $this->user->hasKey('purchase_order_reject');

        $data['user_goods_receipt_view']    = $this->user->hasKey('goods_receipt_view');
        $data['user_goods_receipt_add']     = $this->user->hasKey('goods_receipt_add');
        $data['user_goods_receipt_edit']    = $this->user->hasKey('goods_receipt_edit');
        $data['user_goods_receipt_delete']  = $this->user->hasKey('goods_receipt_delete');
        $data['user_goods_receipt_approve'] = $this->user->hasKey('goods_receipt_approve');
        $data['user_goods_receipt_reject']  = $this->user->hasKey('goods_receipt_reject');

        $data['user_supplier_invoice_view']    = $this->user->hasKey('supplier_invoice_view');
        $data['user_supplier_invoice_add']     = $this->user->hasKey('supplier_invoice_add');
        $data['user_supplier_invoice_edit']    = $this->user->hasKey('supplier_invoice_edit');
        $data['user_supplier_invoice_delete']  = $this->user->hasKey('supplier_invoice_delete');
        $data['user_supplier_invoice_approve'] = $this->user->hasKey('supplier_invoice_approve');
        $data['user_supplier_invoice_reject']  = $this->user->hasKey('supplier_invoice_reject');

        $data['user_vendor_payment_view']    = $this->user->hasKey('vendor_payment_view');
        $data['user_vendor_payment_add']     = $this->user->hasKey('vendor_payment_add');
        $data['user_vendor_payment_edit']    = $this->user->hasKey('vendor_payment_edit');
        $data['user_vendor_payment_delete']  = $this->user->hasKey('vendor_payment_delete');
        $data['user_vendor_payment_approve'] = $this->user->hasKey('vendor_payment_approve');
        $data['user_vendor_payment_reject']  = $this->user->hasKey('vendor_payment_reject');

        $data['user_inventory_view'] = $this->user->hasKey('inventory_view');

        $data['user_purchase_return_view']    = $this->user->hasKey('purchase_return_view');
        $data['user_purchase_return_add']     = $this->user->hasKey('purchase_return_add');
        $data['user_purchase_return_edit']    = $this->user->hasKey('purchase_return_edit');
        $data['user_purchase_return_delete']  = $this->user->hasKey('purchase_return_delete');
        $data['user_purchase_return_approve'] = $this->user->hasKey('purchase_return_approve');
        $data['user_purchase_return_reject']  = $this->user->hasKey('purchase_return_reject');

        $data['user_stock_adjustment_view']   = $this->user->hasKey('stock_adjustment_view');
        $data['user_stock_adjustment_add']    = $this->user->hasKey('stock_adjustment_add');
        $data['user_stock_adjustment_edit']   = $this->user->hasKey('stock_adjustment_edit');
        $data['user_stock_adjustment_delete'] = $this->user->hasKey('stock_adjustment_delete');
        $data['user_stock_adjustment_approve'] = $this->user->hasKey('stock_adjustment_approve');
        $data['user_stock_adjustment_cancel']  = $this->user->hasKey('stock_adjustment_cancel');

        $data['user_stock_transfer_view']    = $this->user->hasKey('stock_transfer_view');
        $data['user_stock_transfer_add']     = $this->user->hasKey('stock_transfer_add');
        $data['user_stock_transfer_edit']    = $this->user->hasKey('stock_transfer_edit');
        $data['user_stock_transfer_delete']  = $this->user->hasKey('stock_transfer_delete');
        $data['user_stock_transfer_approve'] = $this->user->hasKey('stock_transfer_approve');
        $data['user_stock_transfer_reject']  = $this->user->hasKey('stock_transfer_reject');

        $data['user_quality_inspection_view']    = $this->user->hasKey('quality_inspection_view');
        $data['user_quality_inspection_add']     = $this->user->hasKey('quality_inspection_add');
        $data['user_quality_inspection_edit']    = $this->user->hasKey('quality_inspection_edit');
        $data['user_quality_inspection_delete']  = $this->user->hasKey('quality_inspection_delete');
        $data['user_quality_inspection_approve'] = $this->user->hasKey('quality_inspection_approve');
        $data['user_quality_inspection_reject']  = $this->user->hasKey('quality_inspection_reject');

        $data['user_accounting_integration'] = $this->user->hasKey('accounting_integration');
        $data['user_print_report']           = $this->user->hasKey('print_report');

        $data['heading_title']           = $this->language->get('heading_title');
        $data['text_purchase_dashboard'] = $this->language->get('text_purchase_dashboard');

        $data['text_add_requisition']        = $this->language->get('text_add_requisition');
        $data['text_add_quotation']          = $this->language->get('text_add_quotation');
        $data['text_add_po']                 = $this->language->get('text_add_po');
        $data['text_add_goods_receipt']      = $this->language->get('text_add_goods_receipt');
        $data['text_add_invoice']            = $this->language->get('text_add_invoice');
        $data['text_add_payment']            = $this->language->get('text_add_payment');
        $data['text_add_purchase_return']    = $this->language->get('text_add_purchase_return');
        $data['text_add_stock_adjustment']   = $this->language->get('text_add_stock_adjustment');
        $data['text_add_stock_transfer']     = $this->language->get('text_add_stock_transfer');
        $data['text_add_quality_inspection'] = $this->language->get('text_add_quality_inspection');
        $data['text_open_ledger']            = $this->language->get('text_open_ledger');
        $data['text_print_report']           = $this->language->get('text_print_report');

        $data['tab_dashboard']            = $this->language->get('tab_dashboard');
        $data['tab_purchase_requisition'] = $this->language->get('tab_purchase_requisition');
        $data['tab_quotation']            = $this->language->get('tab_quotation');
        $data['tab_purchase_order']       = $this->language->get('tab_purchase_order');
        $data['tab_goods_receipt']        = $this->language->get('tab_goods_receipt');
        $data['tab_supplier_invoice']     = $this->language->get('tab_supplier_invoice');
        $data['tab_vendor_payment']       = $this->language->get('tab_vendor_payment');
        $data['tab_inventory']            = $this->language->get('tab_inventory');
        $data['tab_purchase_return']      = $this->language->get('tab_purchase_return');
        $data['tab_stock_adjustment']     = $this->language->get('tab_stock_adjustment');
        $data['tab_stock_transfer']       = $this->language->get('tab_stock_transfer');
        $data['tab_quality_inspection']   = $this->language->get('tab_quality_inspection');

        $data['text_filter_branch']  = $this->language->get('text_filter_branch');
        $data['text_filter_period']  = $this->language->get('text_filter_period');
        $data['text_loading']        = $this->language->get('text_loading');
        $data['button_filter']       = $this->language->get('button_filter');

        $data['branches']    = $this->model_purchase_purchase->getBranches();
        $data['vendors']     = $this->model_purchase_purchase->getVendors();
        $data['users']       = $this->model_purchase_purchase->getUsers();
        $data['user_groups'] = $this->model_purchase_purchase->getUserGroups();
        $data['user_token']  = $this->session->data['user_token'];

        $data['requisition_statuses'] = array(
            array('value'=>'draft','text'=>$this->language->get('text_draft')),
            array('value'=>'pending','text'=>$this->language->get('text_pending')),
            array('value'=>'approved','text'=>$this->language->get('text_approved')),
            array('value'=>'rejected','text'=>$this->language->get('text_rejected')),
            array('value'=>'cancelled','text'=>$this->language->get('text_cancelled')),
            array('value'=>'processing','text'=>$this->language->get('text_processing')),
            array('value'=>'completed','text'=>$this->language->get('text_completed'))
        );
        $data['quotation_statuses'] = array(
            array('value'=>'draft','text'=>$this->language->get('text_draft')),
            array('value'=>'pending','text'=>$this->language->get('text_pending')),
            array('value'=>'approved','text'=>$this->language->get('text_approved')),
            array('value'=>'rejected','text'=>$this->language->get('text_rejected')),
            array('value'=>'converted','text'=>$this->language->get('text_converted'))
        );
        $data['po_statuses'] = array(
            array('value'=>'draft','text'=>$this->language->get('text_draft')),
            array('value'=>'pending_review','text'=>$this->language->get('text_pending_review')),
            array('value'=>'approved','text'=>$this->language->get('text_approved')),
            array('value'=>'rejected','text'=>$this->language->get('text_rejected')),
            array('value'=>'cancelled','text'=>$this->language->get('text_cancelled')),
            array('value'=>'completed','text'=>$this->language->get('text_completed'))
        );
        $data['gr_statuses'] = array(
            array('value'=>'pending','text'=>$this->language->get('text_pending')),
            array('value'=>'received','text'=>$this->language->get('text_received')),
            array('value'=>'partially_received','text'=>$this->language->get('text_partially_received')),
            array('value'=>'cancelled','text'=>$this->language->get('text_cancelled'))
        );
        $data['invoice_statuses'] = array(
            array('value'=>'pending','text'=>$this->language->get('text_pending')),
            array('value'=>'approved','text'=>$this->language->get('text_approved')),
            array('value'=>'paid','text'=>$this->language->get('text_paid')),
            array('value'=>'cancelled','text'=>$this->language->get('text_cancelled'))
        );
        $data['payment_statuses'] = array(
            array('value'=>'pending','text'=>$this->language->get('text_pending')),
            array('value'=>'completed','text'=>$this->language->get('text_completed')),
            array('value'=>'cancelled','text'=>$this->language->get('text_cancelled'))
        );
        $data['return_statuses'] = array(
            array('value'=>'draft','text'=>$this->language->get('text_draft')),
            array('value'=>'pending','text'=>$this->language->get('text_pending')),
            array('value'=>'approved','text'=>$this->language->get('text_approved')),
            array('value'=>'rejected','text'=>$this->language->get('text_rejected')),
            array('value'=>'cancelled','text'=>$this->language->get('text_cancelled'))
        );
        $data['inspection_statuses'] = array(
            array('value'=>'pending','text'=>$this->language->get('text_pending')),
            array('value'=>'passed','text'=>$this->language->get('text_passed')),
            array('value'=>'failed','text'=>$this->language->get('text_failed'))
        );
        $data['adjustment_statuses'] = array(
            array('value'=>'draft','text'=>$this->language->get('text_draft')),
            array('value'=>'approved','text'=>$this->language->get('text_approved')),
            array('value'=>'cancelled','text'=>$this->language->get('text_cancelled'))
        );

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['success']       = isset($this->session->data['success']) ? $this->session->data['success'] : '';
        if (isset($this->session->data['success'])) {
            unset($this->session->data['success']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/purchase', $data));
    }

    /* =====================================================
     *   3) DASHBOARD
     * ===================================================== */
    public function getDashboardData() {
        $branch = isset($this->request->get['filter_branch']) ? $this->request->get['filter_branch'] : '';
        $period = isset($this->request->get['filter_period']) ? $this->request->get['filter_period'] : '';
        $this->load->model('purchase/purchase');
        $json = $this->model_purchase_purchase->getDashboardStats($branch, $period);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* =====================================================
     *   4) PURCHASE REQUISITION
     * ===================================================== */
    public function getRequisitionList() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $filter_req_number = isset($this->request->get['filter-requisition-id']) ? $this->request->get['filter-requisition-id'] : '';
        $filter_branch     = isset($this->request->get['filter-requisition-branch']) ? $this->request->get['filter-requisition-branch'] : '';
        $filter_dept       = isset($this->request->get['filter-requisition-department']) ? $this->request->get['filter-requisition-department'] : '';
        $filter_status     = isset($this->request->get['filter-requisition-status']) ? $this->request->get['filter-requisition-status'] : '';
        $filter_date_start = isset($this->request->get['filter-requisition-date-start']) ? $this->request->get['filter-requisition-date-start'] : '';
        $filter_date_end   = isset($this->request->get['filter-requisition-date-end']) ? $this->request->get['filter-requisition-date-end'] : '';
        $filter_user       = isset($this->request->get['filter-requisition-user']) ? $this->request->get['filter-requisition-user'] : '';

        $limit = 10;
        $start = ($page - 1) * $limit;

        $filter_data = array(
            'filter_req_number' => $filter_req_number,
            'filter_branch'     => $filter_branch,
            'filter_dept'       => $filter_dept,
            'filter_status'     => $filter_status,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'filter_user'       => $filter_user,
            'start'             => $start,
            'limit'             => $limit
        );

        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getRequisitions($filter_data);
        $total   = $this->model_purchase_purchase->getTotalRequisitions($filter_data);

        $data['requisitions'] = array();
        if ($results) {
            foreach ($results as $res) {
                $data['requisitions'][] = array(
                    'requisition_id'  => $res['requisition_id'],
                    'department_name' => $res['department_name'],
                    'status'          => $res['status'],
                    'date_added'      => $res['date_added']
                );
            }
        }

        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'purchase/purchase/getRequisitionList',
            'user_token='.$this->session->data['user_token'].'&page={page}',
            true
        );
        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($total) ? $start+1 : 0,
            ((($start)+$limit)>$total) ? $total : (($start)+$limit),
            $total,
            ceil($total/$limit)
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function getRequisitionForm() {
        $json = array();
        $this->load->model('purchase/purchase');
        $requisition_id = isset($this->request->get['requisition_id']) ? (int)$this->request->get['requisition_id'] : 0;
        $requisition = $this->model_purchase_purchase->getRequisition($requisition_id);

        if ($requisition) {
            $json['requisition_id'] = $requisition['requisition_id'];
            $json['branch_id']      = $requisition['branch_id'];
            $json['department_id']  = $requisition['department_id'];
            $json['required_date']  = $requisition['required_date'];
            $json['priority']       = $requisition['priority'];
            $json['notes']          = $requisition['notes'];

            $json['items'] = array();
            $items = $this->model_purchase_purchase->getRequisitionItems($requisition_id);
            if ($items) {
                foreach ($items as $itm) {
                    $json['items'][] = array(
                        'product_id'   => $itm['product_id'],
                        'product_name' => $itm['product_name'],
                        'quantity'     => $itm['quantity'],
                        'description'  => $itm['description']
                    );
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveRequisition() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase');
            $data = array(
                'requisition_id' => isset($this->request->post['requisition_id']) ? (int)$this->request->post['requisition_id'] : 0,
                'branch_id'      => $this->request->post['branch_id'],
                'department_id'  => $this->request->post['department_id'],
                'required_date'  => $this->request->post['required_date'],
                'priority'       => $this->request->post['priority'],
                'notes'          => $this->request->post['notes'],
                'items'          => array()
            );

            if (!empty($this->request->post['item_product_id'])) {
                foreach ($this->request->post['item_product_id'] as $i => $pid) {
                    $data['items'][] = array(
                        'product_id'  => $pid,
                        'quantity'    => $this->request->post['item_quantity'][$i],
                        'unit_id'     => $this->request->post['item_unit_id'][$i],
                        'description' => $this->request->post['item_description'][$i]
                    );
                }
            }

            $result = $this->model_purchase_purchase->saveRequisitionData($data);
            if (isset($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewRequisition() {
        $html = '';
        if (!empty($this->request->get['requisition_id'])) {
            $reqId = (int)$this->request->get['requisition_id'];
            $this->load->model('purchase/purchase');
            $reqInfo = $this->model_purchase_purchase->getRequisition($reqId);
            $items   = $this->model_purchase_purchase->getRequisitionItems($reqId);

            if ($reqInfo) {
                ob_start();
                echo '<div><strong>Requisition ID:</strong> '.$reqInfo['requisition_id'].'</div>';
                echo '<div><strong>Branch:</strong> '.$reqInfo['branch_name'].'</div>';
                echo '<div><strong>Department:</strong> '.$reqInfo['department_name'].'</div>';
                echo '<div><strong>Priority:</strong> '.$reqInfo['priority'].'</div>';
                echo '<div><strong>Required Date:</strong> '.$reqInfo['required_date'].'</div>';
                echo '<div><strong>Status:</strong> '.$reqInfo['status'].'</div>';
                echo '<hr>';
                echo '<table class="table table-bordered">';
                echo '<thead><tr><th>Product</th><th>Quantity</th><th>Unit</th><th>Description</th></tr></thead>';
                echo '<tbody>';
                if ($items) {
                    foreach ($items as $i) {
                        echo '<tr>';
                        echo '<td>'.$i['product_name'].'</td>';
                        echo '<td>'.$i['quantity'].'</td>';
                        echo '<td>'.$i['unit_name'].'</td>';
                        echo '<td>'.$i['description'].'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No Items Found</td></tr>';
                }
                echo '</tbody></table>';
                $html = ob_get_clean();
            }
        }
        $this->response->setOutput($html);
    }

    public function deleteRequisition() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['requisition_id'])) {
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->deleteRequisition((int)$this->request->get['requisition_id']);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approveRequisition() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['requisition_id'])) {
                $reqId = (int)$this->request->get['requisition_id'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->approveRequisition($reqId);
                $json['success'] = $this->language->get('text_success_approve');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function rejectRequisition() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['requisition_id'])) {
                $reqId = (int)$this->request->post['requisition_id'];
                $reason = $this->request->post['reason'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->rejectRequisition($reqId, $reason);
                $json['success'] = $this->language->get('text_success_reject');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getRequisitionQuotations() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $requisition_id = isset($this->request->post['requisition_id']) ? (int)$this->request->post['requisition_id'] : 0;
            if ($requisition_id) {
                $this->load->model('purchase/purchase');
                $quotations = $this->model_purchase_purchase->getQuotationsByRequisition($requisition_id);
                $json['quotations'] = $quotations;
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approveQuotationForRequisition() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $requisition_id = isset($this->request->post['requisition_id']) ? (int)$this->request->post['requisition_id'] : 0;
            $quotation_id   = isset($this->request->post['quotation_id'])   ? (int)$this->request->post['quotation_id']   : 0;
            if ($requisition_id && $quotation_id) {
                $this->load->model('purchase/purchase');
                $res = $this->model_purchase_purchase->approveQuotationForRequisition($requisition_id,$quotation_id);
                if (!empty($res['error'])) {
                    $json['error'] = $res['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_approve');
                }
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* =====================================================
     *   5) QUOTATION
     * ===================================================== */
    public function getQuotationList() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $filter_quotation_number = isset($this->request->get['filter-quotation-number']) ? $this->request->get['filter-quotation-number'] : '';
        $filter_vendor           = isset($this->request->get['filter-quotation-vendor']) ? $this->request->get['filter-quotation-vendor'] : '';
        $filter_status           = isset($this->request->get['filter-quotation-status']) ? $this->request->get['filter-quotation-status'] : '';
        $filter_date_start       = isset($this->request->get['filter-quotation-date-start']) ? $this->request->get['filter-quotation-date-start'] : '';
        $filter_date_end         = isset($this->request->get['filter-quotation-date-end']) ? $this->request->get['filter-quotation-date-end'] : '';

        $limit = 10;
        $start = ($page - 1) * $limit;

        $filter_data = array(
            'filter_quotation_number' => $filter_quotation_number,
            'filter_vendor'           => $filter_vendor,
            'filter_status'           => $filter_status,
            'filter_date_start'       => $filter_date_start,
            'filter_date_end'         => $filter_date_end,
            'start'                   => $start,
            'limit'                   => $limit
        );

        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getQuotations($filter_data);
        $total   = $this->model_purchase_purchase->getTotalQuotations($filter_data);

        $data['quotations'] = array();
        if ($results) {
            foreach ($results as $res) {
                $data['quotations'][] = array(
                    'quotation_id'     => $res['quotation_id'],
                    'quotation_number' => $res['quotation_number'],
                    'vendor_name'      => $res['vendor_name'],
                    'total_amount'     => $res['total_amount'],
                    'status'           => $res['status'],
                    'date_added'       => $res['date_added']
                );
            }
        }

        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'purchase/purchase/getQuotationList',
            'user_token='.$this->session->data['user_token'].'&page={page}',
            true
        );
        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($total) ? $start+1 : 0,
            ((($start)+$limit)>$total) ? $total : (($start)+$limit),
            $total,
            ceil($total/$limit)
        );
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function getQuotationForm() {
        $json = array();
        $this->load->model('purchase/purchase');
        $quotation_id = isset($this->request->get['quotation_id']) ? (int)$this->request->get['quotation_id'] : 0;
        $quotation = $this->model_purchase_purchase->getQuotation($quotation_id);

        if ($quotation) {
            $json['quotation_id']     = $quotation['quotation_id'];
            $json['quotation_number'] = $quotation['quotation_number'];
            $json['vendor_id']        = $quotation['supplier_id'];
            $json['validity_date']    = $quotation['validity_date'];
            $json['status']           = $quotation['status'];
            $json['notes']            = $quotation['notes'];

            $json['items'] = array();
            $items = $this->model_purchase_purchase->getQuotationItems($quotation_id);
            if ($items) {
                foreach ($items as $itm) {
                    $json['items'][] = array(
                        'product_id'  => $itm['product_id'],
                        'quantity'    => $itm['quantity'],
                        'unit_price'  => $itm['unit_price'],
                        'total'       => $itm['total'],
                        'description' => $itm['description']
                    );
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveQuotation() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase');
            $data = array(
                'quotation_id'     => isset($this->request->post['quotation_id']) ? (int)$this->request->post['quotation_id'] : 0,
                'quotation_number' => $this->request->post['quotation_number'],
                'vendor_id'        => $this->request->post['vendor_id'],
                'validity_date'    => $this->request->post['validity_date'],
                'status'           => isset($this->request->post['status']) ? $this->request->post['status'] : 'draft',
                'notes'            => $this->request->post['notes'],
                'items'            => array()
            );
            if (!empty($this->request->post['item_product_id'])) {
                foreach ($this->request->post['item_product_id'] as $i => $pid) {
                    $data['items'][] = array(
                        'product_id'  => $pid,
                        'quantity'    => $this->request->post['item_quantity'][$i],
                        'unit_price'  => $this->request->post['item_unit_price'][$i],
                        'description' => $this->request->post['item_description'][$i]
                    );
                }
            }
            $result = $this->model_purchase_purchase->saveQuotationData($data);
            if (isset($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewQuotation() {
        $html = '';
        if (!empty($this->request->get['quotation_id'])) {
            $quotation_id = (int)$this->request->get['quotation_id'];
            $this->load->model('purchase/purchase');
            $info = $this->model_purchase_purchase->getQuotation($quotation_id);
            $items= $this->model_purchase_purchase->getQuotationItems($quotation_id);
            if ($info) {
                ob_start();
                echo '<div><strong>Quotation #:</strong> '.$info['quotation_number'].'</div>';
                echo '<div><strong>Vendor:</strong> '.$info['vendor_name'].'</div>';
                echo '<div><strong>Status:</strong> '.$info['status'].'</div>';
                echo '<div><strong>Validity:</strong> '.$info['validity_date'].'</div>';
                echo '<hr>';
                echo '<table class="table table-bordered">';
                echo '<thead><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr></thead>';
                echo '<tbody>';
                if ($items) {
                    foreach ($items as $it) {
                        echo '<tr>';
                        echo '<td>'.$it['product_name'].'</td>';
                        echo '<td>'.$it['quantity'].'</td>';
                        echo '<td>'.$it['unit_price'].'</td>';
                        echo '<td>'.$it['total'].'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No Results</td></tr>';
                }
                echo '</tbody></table>';
                $html = ob_get_clean();
            }
        }
        $this->response->setOutput($html);
    }

    public function deleteQuotation() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['quotation_id'])) {
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->deleteQuotation((int)$this->request->get['quotation_id']);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approveQuotation() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['quotation_id'])) {
                $quotation_id = (int)$this->request->post['quotation_id'];
                $comment      = $this->request->post['comment'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->approveQuotation($quotation_id, $comment);
                $json['success'] = $this->language->get('text_success_approve');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function rejectQuotation() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['quotation_id'])) {
                $quotation_id = (int)$this->request->post['quotation_id'];
                $reason       = $this->request->post['reject_reason'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->rejectQuotation($quotation_id, $reason);
                $json['success'] = $this->language->get('text_success_reject');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function convertQuotationToPO() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $quotation_id = isset($this->request->get['quotation_id']) ? (int)$this->request->get['quotation_id'] : 0;
            if ($quotation_id) {
                $this->load->model('purchase/purchase');
                $res = $this->model_purchase_purchase->convertQuotationToPO($quotation_id);
                if (!empty($res['error'])) {
                    $json['error'] = $res['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_convert_to_po');
                }
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* =====================================================
     *   6) PURCHASE ORDER
     * ===================================================== */
    public function getPOList() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $filter_po_number  = isset($this->request->get['filter-po-number']) ? $this->request->get['filter-po-number'] : '';
        $filter_status     = isset($this->request->get['filter-po-status']) ? $this->request->get['filter-po-status'] : '';
        $filter_vendor     = isset($this->request->get['filter-po-vendor']) ? $this->request->get['filter-po-vendor'] : '';
        $filter_date_start = isset($this->request->get['filter-po-date-start']) ? $this->request->get['filter-po-date-start'] : '';
        $filter_date_end   = isset($this->request->get['filter-po-date-end']) ? $this->request->get['filter-po-date-end'] : '';

        $limit = 10;
        $start = ($page - 1) * $limit;

        $filter_data = array(
            'filter_po_number'  => $filter_po_number,
            'filter_status'     => $filter_status,
            'filter_vendor'     => $filter_vendor,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'start'             => $start,
            'limit'             => $limit
        );

        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getPurchaseOrders($filter_data);
        $total   = $this->model_purchase_purchase->getTotalPurchaseOrders($filter_data);

        $data['pos'] = array();
        if ($results) {
            foreach ($results as $res) {
                $data['pos'][] = array(
                    'po_id'        => $res['po_id'],
                    'po_number'    => $res['po_number'],
                    'status'       => $res['status'],
                    'vendor_name'  => $res['vendor_name'],
                    'total_amount' => $res['total_amount'],
                    'date_added'   => $res['date_added']
                );
            }
        }

        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'purchase/purchase/getPOList',
            'user_token='.$this->session->data['user_token'].'&page={page}',
            true
        );
        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($total) ? $start+1 : 0,
            ((($start)+$limit)>$total) ? $total : (($start)+$limit),
            $total,
            ceil($total/$limit)
        );
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function getPurchaseOrderForm() {
        $json = array();
        $this->load->model('purchase/purchase');
        $po_id = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0;
        $po = $this->model_purchase_purchase->getPurchaseOrder($po_id);

        if ($po) {
            $json['po_id']                  = $po['po_id'];
            $json['po_number']              = $po['po_number'];
            $json['vendor_id']              = $po['vendor_id'];
            $json['requisition_id']         = $po['requisition_id'];
            $json['order_date']             = $po['order_date'];
            $json['expected_delivery_date'] = $po['expected_delivery_date'];
            $json['status']                 = $po['status'];
            $json['terms_conditions']       = $po['terms_conditions'];
            $json['notes']                  = $po['notes'];

            $json['items'] = array();
            $items = $this->model_purchase_purchase->getPurchaseOrderItems($po_id);
            if ($items) {
                foreach ($items as $it) {
                    $json['items'][] = array(
                        'product_id'  => $it['product_id'],
                        'quantity'    => $it['quantity'],
                        'unit_price'  => $it['unit_price'],
                        'total'       => $it['total_price'],
                        'description' => $it['description']
                    );
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function savePurchaseOrder() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase');
            $data = array(
                'po_id'                => isset($this->request->post['po_id']) ? (int)$this->request->post['po_id'] : 0,
                'po_number'            => $this->request->post['po_number'],
                'requisition_id'       => isset($this->request->post['requisition_id']) ? $this->request->post['requisition_id'] : 0,
                'vendor_id'            => $this->request->post['vendor_id'],
                'order_date'           => $this->request->post['order_date'],
                'expected_delivery_date'=> $this->request->post['expected_delivery_date'],
                'status'               => $this->request->post['status'],
                'terms_conditions'     => $this->request->post['terms_conditions'],
                'notes'                => $this->request->post['notes'],
                'items'                => array()
            );
            if (!empty($this->request->post['item_product_id'])) {
                foreach ($this->request->post['item_product_id'] as $i => $pid) {
                    $data['items'][] = array(
                        'product_id'  => $pid,
                        'quantity'    => $this->request->post['item_quantity'][$i],
                        'unit_price'  => $this->request->post['item_price'][$i],
                        'description' => (isset($this->request->post['item_description'][$i]) ? $this->request->post['item_description'][$i] : '')
                    );
                }
            }

            $result = $this->model_purchase_purchase->savePurchaseOrderData($data);
            if (isset($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewPurchaseOrder() {
        $html = '';
        if (!empty($this->request->get['po_id'])) {
            $po_id = (int)$this->request->get['po_id'];
            $this->load->model('purchase/purchase');
            $info  = $this->model_purchase_purchase->getPurchaseOrder($po_id);
            $items = $this->model_purchase_purchase->getPurchaseOrderItems($po_id);
            if ($info) {
                ob_start();
                echo '<div><strong>PO #:</strong> '.$info['po_number'].'</div>';
                echo '<div><strong>Vendor:</strong> '.$info['vendor_name'].'</div>';
                echo '<div><strong>Status:</strong> '.$info['status'].'</div>';
                echo '<div><strong>Order Date:</strong> '.$info['order_date'].'</div>';
                echo '<div><strong>Expected Delivery:</strong> '.$info['expected_delivery_date'].'</div>';
                echo '<hr>';
                echo '<table class="table table-bordered">';
                echo '<thead><tr><th>Product</th><th>Quantity</th><th>Unit</th><th>Price</th><th>Total</th></tr></thead>';
                echo '<tbody>';
                if ($items) {
                    foreach ($items as $it) {
                        echo '<tr>';
                        echo '<td>'.$it['product_name'].'</td>';
                        echo '<td>'.$it['quantity'].'</td>';
                        echo '<td>'.$it['unit_name'].'</td>';
                        echo '<td>'.$it['unit_price'].'</td>';
                        echo '<td>'.$it['total_price'].'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">No Results</td></tr>';
                }
                echo '</tbody></table>';
                $html = ob_get_clean();
            }
        }
        $this->response->setOutput($html);
    }

    public function deletePurchaseOrder() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['po_id'])) {
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->deletePurchaseOrder((int)$this->request->get['po_id']);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approvePurchaseOrder() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['po_id'])) {
                $po_id  = (int)$this->request->post['po_id'];
                $comment= $this->request->post['comment'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->approvePurchaseOrder($po_id, $comment);
                $json['success'] = $this->language->get('text_success_approve');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function rejectPurchaseOrder() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['po_id'])) {
                $po_id  = (int)$this->request->post['po_id'];
                $reason = $this->request->post['reject_reason'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->rejectPurchaseOrder($po_id, $reason);
                $json['success'] = $this->language->get('text_success_reject');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* =====================================================
     *   7) GOODS RECEIPTS
     * ===================================================== */
    public function getGoodsReceiptList() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $filter_gr_number  = isset($this->request->get['filter-gr-number']) ? $this->request->get['filter-gr-number'] : '';
        $filter_status     = isset($this->request->get['filter-gr-status']) ? $this->request->get['filter-gr-status'] : '';
        $filter_po_number  = isset($this->request->get['filter-gr-po-number']) ? $this->request->get['filter-gr-po-number'] : '';
        $filter_date_start = isset($this->request->get['filter-gr-date-start']) ? $this->request->get['filter-gr-date-start'] : '';
        $filter_date_end   = isset($this->request->get['filter-gr-date-end']) ? $this->request->get['filter-gr-date-end'] : '';

        $limit = 10;
        $start = ($page - 1)*$limit;

        $filter_data = array(
            'filter_gr_number'  => $filter_gr_number,
            'filter_status'     => $filter_status,
            'filter_po_number'  => $filter_po_number,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'start'             => $start,
            'limit'             => $limit
        );
        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getGoodsReceipts($filter_data);
        $total   = $this->model_purchase_purchase->getTotalGoodsReceipts($filter_data);

        $data['receipts'] = array();
        if ($results) {
            foreach ($results as $r) {
                $data['receipts'][] = array(
                    'goods_receipt_id' => $r['goods_receipt_id'],
                    'gr_number'        => $r['gr_number'],
                    'po_number'        => $r['po_number'],
                    'status'           => $r['status'],
                    'date_added'       => $r['date_added']
                );
            }
        }

        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'purchase/purchase/getGoodsReceiptList',
            'user_token='.$this->session->data['user_token'].'&page={page}',
            true
        );
        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($total)? $start+1 : 0,
            ((($start)+$limit)>$total) ? $total : ($start+$limit),
            $total,
            ceil($total/$limit)
        );
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function getGoodsReceiptForm() {
        $json = array();
        $this->load->model('purchase/purchase');
        $goods_receipt_id = isset($this->request->get['goods_receipt_id'])? (int)$this->request->get['goods_receipt_id']:0;
        $info = $this->model_purchase_purchase->getGoodsReceipt($goods_receipt_id);
        if ($info) {
            $json['goods_receipt_id'] = $info['goods_receipt_id'];
            $json['gr_number']        = $info['gr_number'];
            $json['po_id']            = $info['po_id'];
            $json['receipt_date']     = $info['receipt_date'];
            $json['status']           = $info['status'];
            $json['notes']            = $info['notes'];

            $json['items'] = array();
            $items = $this->model_purchase_purchase->getGoodsReceiptItems($goods_receipt_id);
            if ($items) {
                foreach ($items as $it) {
                    $json['items'][] = array(
                        'product_id'        => $it['product_id'],
                        'quantity_received' => $it['quantity_received'],
                        'unit_id'           => $it['unit_id'],
                        'quality_result'    => $it['quality_result'],
                        'remarks'           => $it['remarks']
                    );
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveGoodsReceipt() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase');
            $data = array(
                'goods_receipt_id' => isset($this->request->post['goods_receipt_id']) ? (int)$this->request->post['goods_receipt_id'] : 0,
                'gr_number'        => $this->request->post['gr_number'],
                'po_id'            => $this->request->post['po_id_select'],
                'receipt_date'     => $this->request->post['receipt_date'],
                'status'           => $this->request->post['status'],
                'notes'            => $this->request->post['notes'],
                'items'            => array()
            );

            if (!empty($this->request->post['item_product_id'])) {
                foreach ($this->request->post['item_product_id'] as $i => $pid) {
                    $data['items'][] = array(
                        'product_id'        => $pid,
                        'quantity_received' => $this->request->post['item_quantity_received'][$i],
                        'unit_id'           => $this->request->post['item_unit_id'][$i],
                        'quality_result'    => $this->request->post['item_quality_result'][$i],
                        'remarks'           => $this->request->post['item_remarks'][$i]
                    );
                }
            }

            $result = $this->model_purchase_purchase->saveGoodsReceiptData($data);
            if (isset($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewGoodsReceipt() {
        $html = '';
        if (!empty($this->request->get['goods_receipt_id'])) {
            $gr_id = (int)$this->request->get['goods_receipt_id'];
            $this->load->model('purchase/purchase');
            $info  = $this->model_purchase_purchase->getGoodsReceipt($gr_id);
            $items = $this->model_purchase_purchase->getGoodsReceiptItems($gr_id);
            if ($info) {
                ob_start();
                echo '<div><strong>GR #:</strong> '.$info['gr_number'].'</div>';
                echo '<div><strong>PO #:</strong> '.$info['po_number'].'</div>';
                echo '<div><strong>Status:</strong> '.$info['status'].'</div>';
                echo '<div><strong>Date:</strong> '.$info['receipt_date'].'</div>';
                echo '<hr>';
                echo '<table class="table table-bordered">';
                echo '<thead><tr><th>Product</th><th>Qty Received</th><th>Unit</th><th>Quality</th><th>Remarks</th></tr></thead>';
                echo '<tbody>';
                if ($items) {
                    foreach ($items as $it) {
                        echo '<tr>';
                        echo '<td>'.$it['product_name'].'</td>';
                        echo '<td>'.$it['quantity_received'].'</td>';
                        echo '<td>'.$it['unit_name'].'</td>';
                        echo '<td>'.$it['quality_result'].'</td>';
                        echo '<td>'.$it['remarks'].'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">No Results</td></tr>';
                }
                echo '</tbody></table>';
                $html = ob_get_clean();
            }
        }
        $this->response->setOutput($html);
    }

    public function deleteGoodsReceipt() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['goods_receipt_id'])) {
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->deleteGoodsReceipt((int)$this->request->get['goods_receipt_id']);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approveGoodsReceipt() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['goods_receipt_id'])) {
                $grId   = (int)$this->request->post['goods_receipt_id'];
                $comment= $this->request->post['comment'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->approveGoodsReceipt($grId,$comment);
                $json['success'] = $this->language->get('text_success_approve');
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function rejectGoodsReceipt() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['goods_receipt_id'])) {
                $grId   = (int)$this->request->post['goods_receipt_id'];
                $reason = $this->request->post['reject_reason'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->rejectGoodsReceipt($grId,$reason);
                $json['success'] = $this->language->get('text_success_reject');
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* =====================================================
     *   8) SUPPLIER INVOICE
     * ===================================================== */
    public function getSupplierInvoiceList() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $filter_invoice_number = isset($this->request->get['filter-invoice-number']) ? $this->request->get['filter-invoice-number'] : '';
        $filter_status         = isset($this->request->get['filter-invoice-status']) ? $this->request->get['filter-invoice-status'] : '';
        $filter_vendor         = isset($this->request->get['filter-invoice-vendor']) ? $this->request->get['filter-invoice-vendor'] : '';
        $filter_date_start     = isset($this->request->get['filter-invoice-date-start']) ? $this->request->get['filter-invoice-date-start'] : '';
        $filter_date_end       = isset($this->request->get['filter-invoice-date-end']) ? $this->request->get['filter-invoice-date-end'] : '';

        $limit = 10;
        $start = ($page - 1)*$limit;

        $filter_data = array(
            'filter_invoice_number' => $filter_invoice_number,
            'filter_status'         => $filter_status,
            'filter_vendor'         => $filter_vendor,
            'filter_date_start'     => $filter_date_start,
            'filter_date_end'       => $filter_date_end,
            'start'                 => $start,
            'limit'                 => $limit
        );

        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getSupplierInvoices($filter_data);
        $total   = $this->model_purchase_purchase->getTotalSupplierInvoices($filter_data);

        $data['invoices'] = array();
        if ($results) {
            foreach ($results as $inv) {
                $data['invoices'][] = array(
                    'invoice_id'     => $inv['invoice_id'],
                    'invoice_number' => $inv['invoice_number'],
                    'vendor_name'    => $inv['vendor_name'],
                    'total_amount'   => $inv['total_amount'],
                    'status'         => $inv['status'],
                    'date_added'     => $inv['date_added']
                );
            }
        }

        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'purchase/purchase/getSupplierInvoiceList',
            'user_token='.$this->session->data['user_token'].'&page={page}',
            true
        );
        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($total)? $start+1 : 0,
            ((($start)+$limit)>$total) ? $total : ($start+$limit),
            $total,
            ceil($total/$limit)
        );
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function getSupplierInvoiceForm() {
        $json = array();
        $this->load->model('purchase/purchase');
        $invoice_id = isset($this->request->get['invoice_id'])? (int)$this->request->get['invoice_id']:0;
        $info = $this->model_purchase_purchase->getSupplierInvoice($invoice_id);
        if ($info) {
            $json['invoice_id']     = $info['invoice_id'];
            $json['invoice_number'] = $info['invoice_number'];
            $json['vendor_id']      = $info['vendor_id'];
            $json['po_id']          = $info['po_id'];
            $json['invoice_date']   = $info['invoice_date'];
            $json['due_date']       = $info['due_date'];
            $json['subtotal']       = $info['subtotal'];
            $json['tax_amount']     = $info['tax_amount'];
            $json['discount_amount']= $info['discount_amount'];
            $json['total_amount']   = $info['total_amount'];
            $json['status']         = $info['status'];
            $json['notes']          = $info['notes'];

            $json['items'] = array();
            $items = $this->model_purchase_purchase->getSupplierInvoiceItems($invoice_id);
            if ($items) {
                foreach ($items as $it) {
                    $json['items'][] = array(
                        'product_id'   => $it['product_id'],
                        'quantity'     => $it['quantity'],
                        'unit_price'   => $it['unit_price'],
                        'total_price'  => $it['total_price'],
                        'unit_id'      => $it['unit_id']
                    );
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveSupplierInvoice() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase');
            $data = array(
                'invoice_id'     => isset($this->request->post['invoice_id']) ? (int)$this->request->post['invoice_id'] : 0,
                'invoice_number' => $this->request->post['invoice_number'],
                'po_id'          => $this->request->post['po_id'],
                'vendor_id'      => $this->request->post['vendor_id'],
                'invoice_date'   => $this->request->post['invoice_date'],
                'due_date'       => $this->request->post['due_date'],
                'subtotal'       => $this->request->post['subtotal'],
                'tax_amount'     => $this->request->post['tax_amount'],
                'discount_amount'=> $this->request->post['discount_amount'],
                'total_amount'   => $this->request->post['total_amount'],
                'status'         => $this->request->post['status'],
                'notes'          => $this->request->post['notes'],
                'items'          => array()
            );
            if (!empty($this->request->post['item_product_id'])) {
                foreach ($this->request->post['item_product_id'] as $i => $pid) {
                    $data['items'][] = array(
                        'product_id'  => $pid,
                        'quantity'    => $this->request->post['item_quantity'][$i],
                        'unit_price'  => $this->request->post['item_unit_price'][$i],
                        'description' => (isset($this->request->post['item_description'][$i]) ? $this->request->post['item_description'][$i] : '')
                    );
                }
            }
            $result = $this->model_purchase_purchase->saveSupplierInvoiceData($data);
            if (isset($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewSupplierInvoice() {
        $html = '';
        if (!empty($this->request->get['invoice_id'])) {
            $invoice_id = (int)$this->request->get['invoice_id'];
            $this->load->model('purchase/purchase');
            $info  = $this->model_purchase_purchase->getSupplierInvoice($invoice_id);
            $items = $this->model_purchase_purchase->getSupplierInvoiceItems($invoice_id);
            if ($info) {
                ob_start();
                echo '<div><strong>Invoice #:</strong> '.$info['invoice_number'].'</div>';
                echo '<div><strong>Vendor:</strong> '.$info['vendor_name'].'</div>';
                echo '<div><strong>Status:</strong> '.$info['status'].'</div>';
                echo '<div><strong>Invoice Date:</strong> '.$info['invoice_date'].'</div>';
                echo '<div><strong>Due Date:</strong> '.$info['due_date'].'</div>';
                echo '<hr>';
                echo '<table class="table table-bordered">';
                echo '<thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Price</th><th>Total</th></tr></thead>';
                echo '<tbody>';
                if ($items) {
                    foreach ($items as $it) {
                        echo '<tr>';
                        echo '<td>'.$it['product_name'].'</td>';
                        echo '<td>'.$it['quantity'].'</td>';
                        echo '<td>'.$it['unit_name'].'</td>';
                        echo '<td>'.$it['unit_price'].'</td>';
                        echo '<td>'.$it['total_price'].'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">No Results</td></tr>';
                }
                echo '</tbody></table>';
                $html = ob_get_clean();
            }
        }
        $this->response->setOutput($html);
    }

    public function deleteSupplierInvoice() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['invoice_id'])) {
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->deleteSupplierInvoice((int)$this->request->get['invoice_id']);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approveSupplierInvoice() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['invoice_id'])) {
                $invoice_id = (int)$this->request->post['invoice_id'];
                $comment    = $this->request->post['comment'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->approveSupplierInvoice($invoice_id, $comment);
                $json['success'] = $this->language->get('text_success_approve');
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function rejectSupplierInvoice() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['invoice_id'])) {
                $invoice_id = (int)$this->request->post['invoice_id'];
                $reason     = $this->request->post['reject_reason'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->rejectSupplierInvoice($invoice_id, $reason);
                $json['success'] = $this->language->get('text_success_reject');
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* =====================================================
     *   9) VENDOR PAYMENT
     * ===================================================== */
    public function getVendorPaymentList() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $filter_payment_number = isset($this->request->get['filter-payment-number']) ? $this->request->get['filter-payment-number'] : '';
        $filter_vendor         = isset($this->request->get['filter-payment-vendor']) ? $this->request->get['filter-payment-vendor'] : '';
        $filter_status         = isset($this->request->get['filter-payment-status']) ? $this->request->get['filter-payment-status'] : '';
        $date_start            = isset($this->request->get['filter-payment-date-start']) ? $this->request->get['filter-payment-date-start'] : '';
        $date_end              = isset($this->request->get['filter-payment-date-end']) ? $this->request->get['filter-payment-date-end'] : '';

        $limit = 10;
        $start = ($page -1)*$limit;

        $filter_data = array(
            'filter_payment_number' => $filter_payment_number,
            'filter_vendor'         => $filter_vendor,
            'filter_status'         => $filter_status,
            'filter_date_start'     => $date_start,
            'filter_date_end'       => $date_end,
            'start'                 => $start,
            'limit'                 => $limit
        );

        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getVendorPayments($filter_data);
        $total   = $this->model_purchase_purchase->getTotalVendorPayments($filter_data);

        $data['payments'] = array();
        if ($results) {
            foreach ($results as $p) {
                $data['payments'][] = array(
                    'payment_id'     => $p['payment_id'],
                    'payment_number' => $p['payment_number'],
                    'vendor_name'    => $p['vendor_name'],
                    'amount'         => $p['amount'],
                    'status'         => $p['status'],
                    'date_added'     => $p['date_added']
                );
            }
        }

        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'purchase/purchase/getVendorPaymentList',
            'user_token='.$this->session->data['user_token'].'&page={page}',
            true
        );
        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($total)? $start+1 : 0,
            ((($start)+$limit)>$total)? $total : (($start)+$limit),
            $total,
            ceil($total/$limit)
        );
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function getVendorPaymentForm() {
        $json = array();
        $this->load->model('purchase/purchase');
        $payment_id = isset($this->request->get['payment_id']) ? (int)$this->request->get['payment_id'] : 0;
        $info = $this->model_purchase_purchase->getVendorPayment($payment_id);
        if ($info) {
            $json['payment_id']      = $info['payment_id'];
            $json['payment_number']  = $info['payment_number'];
            $json['vendor_id']       = $info['vendor_id'];
            $json['payment_date']    = $info['payment_date'];
            $json['amount']          = $info['amount'];
            $json['payment_method']  = $info['payment_method'];
            $json['reference_number']= $info['reference_number'];
            $json['notes']           = $info['notes'];

            $json['invoices'] = array();
            $invs = $this->model_purchase_purchase->getVendorPaymentInvoices($payment_id);
            if ($invs) {
                foreach ($invs as $iv) {
                    $json['invoices'][] = array(
                        'invoice_id'    => $iv['invoice_id'],
                        'invoice_number'=> $iv['invoice_number'],
                        'amount_due'    => $iv['amount_due'],
                        'amount_pay'    => $iv['amount_pay']
                    );
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveVendorPayment() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase');
            $data = array(
                'payment_id'     => isset($this->request->post['payment_id']) ? (int)$this->request->post['payment_id'] : 0,
                'payment_number' => $this->request->post['payment_number'],
                'vendor_id'      => $this->request->post['vendor_id'],
                'payment_date'   => $this->request->post['payment_date'],
                'amount'         => $this->request->post['amount'],
                'payment_method' => $this->request->post['payment_method'],
                'reference_no'   => $this->request->post['reference_number'],
                'notes'          => $this->request->post['notes']
            );
            $result = $this->model_purchase_purchase->saveVendorPaymentData($data);
            if (isset($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewVendorPayment() {
        $html = '';
        if (!empty($this->request->get['payment_id'])) {
            $payment_id = (int)$this->request->get['payment_id'];
            $this->load->model('purchase/purchase');
            $info = $this->model_purchase_purchase->getVendorPayment($payment_id);
            if ($info) {
                ob_start();
                echo '<div><strong>Payment #:</strong> '.$info['payment_number'].'</div>';
                echo '<div><strong>Vendor:</strong> '.$info['vendor_name'].'</div>';
                echo '<div><strong>Payment Date:</strong> '.$info['payment_date'].'</div>';
                echo '<div><strong>Amount:</strong> '.$info['amount'].'</div>';
                echo '<div><strong>Method:</strong> '.$info['payment_method'].'</div>';
                echo '<div><strong>Reference:</strong> '.$info['reference_number'].'</div>';
                echo '<div><strong>Status:</strong> '.$info['status'].'</div>';
                if (!empty($info['notes'])) {
                    echo '<hr><strong>Notes:</strong><br>'.nl2br($info['notes']);
                }
                $invoices = $this->model_purchase_purchase->getVendorPaymentInvoices($payment_id);
                if ($invoices) {
                    echo '<hr><h4>Allocated Invoices</h4>';
                    echo '<table class="table table-bordered">';
                    echo '<thead><tr><th>Invoice #</th><th>Amount Paid</th></tr></thead><tbody>';
                    foreach ($invoices as $inv) {
                        echo '<tr>';
                        echo '<td>'.$inv['invoice_number'].'</td>';
                        echo '<td>'.$inv['amount_paid'].'</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }
                $html = ob_get_clean();
            }
        }
        $this->response->setOutput($html);
    }

    public function deleteVendorPayment() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['payment_id'])) {
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->deleteVendorPayment((int)$this->request->get['payment_id']);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approveVendorPayment() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['payment_id'])) {
                $payment_id = (int)$this->request->post['payment_id'];
                $comment    = isset($this->request->post['comment'])?$this->request->post['comment']:'';
                $this->load->model('purchase/purchase');
                $res = $this->model_purchase_purchase->approveVendorPayment($payment_id, $comment);
                if (!empty($res['error'])) {
                    $json['error'] = $res['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_approve');
                }
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function rejectVendorPayment() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['payment_id'])) {
                $payment_id = (int)$this->request->post['payment_id'];
                $reason     = isset($this->request->post['reject_reason'])?$this->request->post['reject_reason']:'';
                $this->load->model('purchase/purchase');
                $res = $this->model_purchase_purchase->rejectVendorPayment($payment_id, $reason);
                if (!empty($res['error'])) {
                    $json['error'] = $res['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_reject');
                }
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* =====================================================
     *   10) INVENTORY
     * ===================================================== */
    public function getInventoryList() {
        $page            = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $filter_branch   = isset($this->request->get['filter-inv-branch']) ? $this->request->get['filter-inv-branch'] : '';
        $filter_product  = isset($this->request->get['filter-inv-product']) ? $this->request->get['filter-inv-product'] : '';
        $filter_movement = isset($this->request->get['filter-inv-movement']) ? $this->request->get['filter-inv-movement'] : '';

        $limit = 10;
        $start = ($page - 1)*$limit;

        $filter_data = array(
            'filter_branch'   => $filter_branch,
            'filter_product'  => $filter_product,
            'filter_movement' => $filter_movement,
            'start'           => $start,
            'limit'           => $limit
        );
        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getInventory($filter_data);
        $total   = $this->model_purchase_purchase->getTotalInventory($filter_data);

        $data['inventory'] = array();
        if ($results) {
            foreach ($results as $inv) {
                $data['inventory'][] = array(
                    'branch_name'          => $inv['branch_name'],
                    'product_name'         => $inv['product_name'],
                    'quantity'             => $inv['quantity'],
                    'unit_name'            => $inv['unit_name'],
                    'is_consignment'       => $inv['is_consignment'],
                    'consignment_supplier' => $inv['consignment_supplier']
                );
            }
        }
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'purchase/purchase/getInventoryList',
            'user_token='.$this->session->data['user_token'].'&page={page}',true
        );
        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($total)? $start+1 : 0,
            ((($start)+$limit)>$total)? $total : (($start)+$limit),
            $total,
            ceil($total/$limit)
        );
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function viewInventoryDetails() {
        $html = 'No implementation yet';
        $this->response->setOutput($html);
    }

    /* =====================================================
     *   11) PURCHASE RETURN
     * ===================================================== */
    public function getPurchaseReturnList() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $filter_return_number = isset($this->request->get['filter-return-number']) ? $this->request->get['filter-return-number'] : '';
        $filter_vendor        = isset($this->request->get['filter-return-vendor']) ? $this->request->get['filter-return-vendor'] : '';
        $filter_status        = isset($this->request->get['filter-return-status']) ? $this->request->get['filter-return-status'] : '';

        $limit = 10;
        $start = ($page -1)*$limit;

        $filter_data = array(
            'filter_return_number' => $filter_return_number,
            'filter_vendor'        => $filter_vendor,
            'filter_status'        => $filter_status,
            'start'                => $start,
            'limit'                => $limit
        );
        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getPurchaseReturns($filter_data);
        $total   = $this->model_purchase_purchase->getTotalPurchaseReturns($filter_data);

        $data['returns'] = array();
        if ($results) {
            foreach ($results as $ret) {
                $data['returns'][] = array(
                    'return_id'     => $ret['return_id'],
                    'return_number' => $ret['return_number'],
                    'vendor_name'   => $ret['vendor_name'],
                    'status'        => $ret['status'],
                    'date_added'    => $ret['date_added']
                );
            }
        }
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'purchase/purchase/getPurchaseReturnList',
            'user_token='.$this->session->data['user_token'].'&page={page}',
            true
        );
        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($total)? $start+1 : 0,
            ((($start)+$limit)>$total)? $total : (($start)+$limit),
            $total,
            ceil($total/$limit)
        );
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function getPurchaseReturnForm() {
        $json = array();
        $this->load->model('purchase/purchase');
        $return_id = isset($this->request->get['return_id']) ? (int)$this->request->get['return_id'] : 0;
        $info = $this->model_purchase_purchase->getPurchaseReturn($return_id);
        if ($info) {
            $json['return_id']         = $info['return_id'];
            $json['return_number']     = $info['return_number'];
            $json['supplier_id']       = $info['supplier_id'];
            $json['purchase_order_id'] = $info['purchase_order_id'];
            $json['goods_receipt_id']  = $info['goods_receipt_id'];
            $json['return_date']       = $info['return_date'];
            $json['notes']             = $info['notes'];

            $json['items'] = array();
            $items = $this->model_purchase_purchase->getPurchaseReturnItems($return_id);
            if ($items) {
                foreach ($items as $it) {
                    $json['items'][] = array(
                        'product_id' => $it['product_id'],
                        'quantity'   => $it['quantity'],
                        'unit_id'    => $it['unit_id'],
                        'price'      => $it['price'],
                        'total'      => $it['total'],
                        'reason'     => $it['reason']
                    );
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function savePurchaseReturn() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase');
            $data = array(
                'return_id'         => isset($this->request->post['return_id']) ? (int)$this->request->post['return_id'] : 0,
                'return_number'     => $this->request->post['return_number'],
                'supplier_id'       => $this->request->post['supplier_id'],
                'purchase_order_id' => $this->request->post['purchase_order_id'],
                'goods_receipt_id'  => $this->request->post['goods_receipt_id'],
                'return_date'       => $this->request->post['return_date'],
                'notes'             => $this->request->post['notes'],
                'items'             => array()
            );
            if (!empty($this->request->post['item_product_id'])) {
                foreach ($this->request->post['item_product_id'] as $i => $pid) {
                    $data['items'][] = array(
                        'product_id' => $pid,
                        'quantity'   => $this->request->post['item_quantity'][$i],
                        'unit_id'    => $this->request->post['item_unit_id'][$i],
                        'price'      => $this->request->post['item_price'][$i],
                        'total'      => $this->request->post['item_total'][$i],
                        'reason'     => $this->request->post['item_reason'][$i]
                    );
                }
            }
            $result = $this->model_purchase_purchase->savePurchaseReturnData($data);
            if (isset($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewPurchaseReturn() {
        $html = '';
        if (!empty($this->request->get['return_id'])) {
            $return_id = (int)$this->request->get['return_id'];
            $this->load->model('purchase/purchase');
            $info  = $this->model_purchase_purchase->getPurchaseReturn($return_id);
            $items = $this->model_purchase_purchase->getPurchaseReturnItems($return_id);
            if ($info) {
                ob_start();
                echo '<div><strong>Return #:</strong> '.$info['return_number'].'</div>';
                echo '<div><strong>Vendor:</strong> '.$info['supplier_name'].'</div>';
                echo '<div><strong>Status:</strong> '.$info['status'].'</div>';
                echo '<div><strong>Date:</strong> '.$info['return_date'].'</div>';
                echo '<hr>';
                echo '<table class="table table-bordered">';
                echo '<thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Price</th><th>Total</th><th>Reason</th></tr></thead>';
                echo '<tbody>';
                if ($items) {
                    foreach ($items as $it) {
                        echo '<tr>';
                        echo '<td>'.$it['product_name'].'</td>';
                        echo '<td>'.$it['quantity'].'</td>';
                        echo '<td>'.$it['unit_name'].'</td>';
                        echo '<td>'.$it['price'].'</td>';
                        echo '<td>'.$it['total'].'</td>';
                        echo '<td>'.$it['reason'].'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6">No Results</td></tr>';
                }
                echo '</tbody></table>';
                $html = ob_get_clean();
            }
        }
        $this->response->setOutput($html);
    }

    public function deletePurchaseReturn() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['return_id'])) {
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->deletePurchaseReturn((int)$this->request->get['return_id']);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approvePurchaseReturn() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['return_id'])) {
                $return_id = (int)$this->request->post['return_id'];
                $comment   = $this->request->post['approval_comment'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->approvePurchaseReturn($return_id,$comment);
                $json['success'] = $this->language->get('text_success_approve');
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function rejectPurchaseReturn() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify','purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->post['return_id'])) {
                $return_id = (int)$this->request->post['return_id'];
                $reason    = $this->request->post['reject_reason'];
                $this->load->model('purchase/purchase');
                $this->model_purchase_purchase->rejectPurchaseReturn($return_id,$reason);
                $json['success'] = $this->language->get('text_success_reject');
            } else {
                $json['error'] = 'Not found';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /* =====================================================
     *   12) STOCK ADJUSTMENT
     * ===================================================== */
     public function getStockAdjustmentsList() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

        $filter_adjustment_number = isset($this->request->get['filter_adjustment_number']) ? $this->request->get['filter_adjustment_number'] : '';
        $filter_status            = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';

        $limit = 10;
        $start = ($page - 1) * $limit;

        $filter_data = array(
            'filter_adjustment_number' => $filter_adjustment_number,
            'filter_status'            => $filter_status,
            'start'                    => $start,
            'limit'                    => $limit
        );

        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getAdjustments($filter_data);
        $total   = $this->model_purchase_purchase->getTotalAdjustments($filter_data);

        $data['adjustments'] = array();
        if ($results) {
            foreach ($results as $adj) {
                $data['adjustments'][] = array(
                    'adjustment_id'     => $adj['adjustment_id'],
                    'adjustment_number' => $adj['adjustment_number'],
                    'branch_name'       => $adj['branch_name'],
                    'type'              => $adj['type'],
                    'status'            => $adj['status'],
                    'date_added'        => $adj['date_added']
                );
            }
        }

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link('purchase/purchase/getStockAdjustmentsList','user_token='.$this->session->data['user_token'].'&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($total) ? $start + 1 : 0,
            ((($start) + $limit) > $total) ? $total : (($start) + $limit),
            $total,
            ceil($total / $limit)
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function saveAdjustment() {
        $json = array();
        $this->load->language('purchase/purchase');

        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase');
            $data = array(
                'adjustment_id'     => isset($this->request->post['adjustment_id']) ? (int)$this->request->post['adjustment_id'] : 0,
                'adjustment_number' => $this->request->post['adjustment_number'],
                'branch_id'         => $this->request->post['branch_id'],
                'type'              => $this->request->post['type'],
                'notes'             => $this->request->post['notes'],
                'items'             => array()
            );

            if (!empty($this->request->post['item_product_id'])) {
                foreach ($this->request->post['item_product_id'] as $i => $pid) {
                    $data['items'][] = array(
                        'product_id' => $pid,
                        'quantity'   => $this->request->post['item_quantity'][$i],
                        'unit_id'    => $this->request->post['item_unit_id'][$i],
                        'reason'     => $this->request->post['item_reason'][$i]
                    );
                }
            }

            $result = $this->model_purchase_purchase->saveAdjustment($data);

            if (isset($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success_save');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteAdjustment() {
        $json = array();
        $this->load->language('purchase/purchase');

        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['adjustment_id'])) {
                $this->load->model('purchase/purchase');
                $result = $this->model_purchase_purchase->deleteAdjustment((int)$this->request->get['adjustment_id']);
                if (!empty($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_delete');
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approveAdjustment() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $adjustment_id = isset($this->request->post['adjustment_id']) ? (int)$this->request->post['adjustment_id'] : 0;
            $this->load->model('purchase/purchase');
            if ($adjustment_id) {
                $result = $this->model_purchase_purchase->approveAdjustment($adjustment_id);
                if (!empty($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_approve');
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function cancelAdjustment() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $adjustment_id = isset($this->request->post['adjustment_id']) ? (int)$this->request->post['adjustment_id'] : 0;
            $this->load->model('purchase/purchase');
            if ($adjustment_id) {
                $result = $this->model_purchase_purchase->cancelAdjustment($adjustment_id);
                if (!empty($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_cancel');
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    //-------------------------------------------------------------------------
    //                     Stock Transfers
    //-------------------------------------------------------------------------
    public function getStockTransferList() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

        $filter_transfer_number = isset($this->request->get['filter_transfer_number']) ? $this->request->get['filter_transfer_number'] : '';
        $filter_from_branch     = isset($this->request->get['filter_from_branch']) ? $this->request->get['filter_from_branch'] : '';
        $filter_to_branch       = isset($this->request->get['filter_to_branch']) ? $this->request->get['filter_to_branch'] : '';

        $limit = 10;
        $start = ($page - 1) * $limit;

        $filter_data = array(
            'filter_transfer_number' => $filter_transfer_number,
            'filter_from_branch'     => $filter_from_branch,
            'filter_to_branch'       => $filter_to_branch,
            'start'                  => $start,
            'limit'                  => $limit
        );

        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getStockTransfers($filter_data);
        $total   = $this->model_purchase_purchase->getTotalStockTransfers($filter_data);

        $data['transfers'] = array();
        foreach ($results as $res) {
            $data['transfers'][] = array(
                'transfer_id'     => $res['transfer_id'],
                'transfer_number' => $res['transfer_number'],
                'from_branch_name'=> $res['from_branch_name'],
                'to_branch_name'  => $res['to_branch_name'],
                'status'          => $res['status'],
                'date_added'      => $res['date_added']
            );
        }

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link('purchase/purchase/getStockTransferList','user_token='.$this->session->data['user_token'].'&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf(
            $this->language->get('text_pagination'),
            ($total) ? $start + 1 : 0,
            ((($start) + $limit) > $total) ? $total : (($start) + $limit),
            $total,
            ceil($total / $limit)
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function saveStockTransfer() {
        $json = array();
        $this->load->language('purchase/purchase');

        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase');
            $data = array(
                'transfer_id'      => isset($this->request->post['transfer_id']) ? (int)$this->request->post['transfer_id'] : 0,
                'transfer_number'  => $this->request->post['transfer_number'],
                'from_branch_id'   => $this->request->post['from_branch_id'],
                'to_branch_id'     => $this->request->post['to_branch_id'],
                'notes'            => $this->request->post['notes'],
                'items'            => array()
            );

            if (!empty($this->request->post['item_product_id'])) {
                foreach ($this->request->post['item_product_id'] as $i => $pid) {
                    $data['items'][] = array(
                        'product_id' => $pid,
                        'quantity'   => $this->request->post['item_quantity'][$i],
                        'unit_id'    => $this->request->post['item_unit_id'][$i],
                        'notes'      => $this->request->post['item_notes'][$i]
                    );
                }
            }

            $result = $this->model_purchase_purchase->saveStockTransferData($data);

            if (isset($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewStockTransfer() {
        $html = '';
        if (!empty($this->request->get['transfer_id'])) {
            $transfer_id = (int)$this->request->get['transfer_id'];
            $this->load->model('purchase/purchase');
            $info  = $this->model_purchase_purchase->getStockTransfer($transfer_id);
            $items = $this->model_purchase_purchase->getStockTransferItems($transfer_id);

            if ($info) {
                ob_start();
                echo '<div><strong>'.$this->language->get('text_transfer_number').':</strong> '.$info['transfer_number'].'</div>';
                echo '<div><strong>'.$this->language->get('text_from_branch').':</strong> '.$info['from_branch_name'].'</div>';
                echo '<div><strong>'.$this->language->get('text_to_branch').':</strong> '.$info['to_branch_name'].'</div>';
                echo '<div><strong>'.$this->language->get('text_status').':</strong> '.$info['status'].'</div>';
                echo '<hr>';
                echo '<table class="table table-bordered">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>'.$this->language->get('column_product').'</th>';
                echo '<th>'.$this->language->get('column_quantity').'</th>';
                echo '<th>'.$this->language->get('column_unit').'</th>';
                echo '<th>'.$this->language->get('column_notes').'</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                if ($items) {
                    foreach ($items as $i) {
                        echo '<tr>';
                        echo '<td>'.$i['product_name'].'</td>';
                        echo '<td>'.$i['quantity'].'</td>';
                        echo '<td>'.$i['unit_name'].'</td>';
                        echo '<td>'.$i['notes'].'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">'.$this->language->get('text_no_results').'</td></tr>';
                }
                echo '</tbody>';
                echo '</table>';
                $html = ob_get_clean();
            }
        }

        $this->response->setOutput($html);
    }

    public function deleteStockTransfer() {
        $json = array();
        $this->load->language('purchase/purchase');

        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['transfer_id'])) {
                $this->load->model('purchase/purchase');
                $result = $this->model_purchase_purchase->deleteStockTransfer((int)$this->request->get['transfer_id']);
                if (!empty($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_delete');
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approveStockTransfer() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $transfer_id = isset($this->request->post['transfer_id']) ? (int)$this->request->post['transfer_id'] : 0;
            $this->load->model('purchase/purchase');
            if ($transfer_id) {
                $result = $this->model_purchase_purchase->approveStockTransfer($transfer_id);
                if (!empty($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_approve');
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function rejectStockTransfer() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $transfer_id   = isset($this->request->post['transfer_id']) ? (int)$this->request->post['transfer_id'] : 0;
            $reject_reason = isset($this->request->post['reject_reason']) ? $this->request->post['reject_reason'] : '';
            $this->load->model('purchase/purchase');
            if ($transfer_id) {
                $result = $this->model_purchase_purchase->rejectStockTransfer($transfer_id, $reject_reason);
                if (!empty($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_reject');
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }


    //-------------------------------------------------------------------------
    //                  Quality Inspections
    //-------------------------------------------------------------------------
    public function getQualityInspectionList() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

        $filter_inspection_number = isset($this->request->get['filter_inspection_number']) ? $this->request->get['filter_inspection_number'] : '';
        $filter_status            = isset($this->request->get['filter_inspection_status']) ? $this->request->get['filter_inspection_status'] : '';

        $limit = 10;
        $start = ($page - 1) * $limit;

        $filter_data = array(
            'filter_inspection_number' => $filter_inspection_number,
            'filter_status'            => $filter_status,
            'start'                    => $start,
            'limit'                    => $limit
        );

        $this->load->model('purchase/purchase');
        $results = $this->model_purchase_purchase->getQualityInspections($filter_data);
        $total   = $this->model_purchase_purchase->getTotalQualityInspections($filter_data);

        $data['inspections'] = array();
        foreach ($results as $res) {
            $data['inspections'][] = array(
                'inspection_id'     => $res['inspection_id'],
                'inspection_number' => $res['inspection_number'],
                'goods_receipt_id'  => $res['goods_receipt_id'],
                'inspector_name'    => $res['inspector_name'],
                'status'            => $res['status'],
                'date_added'        => $res['date_added']
            );
        }

        // Pagination
        $pagination        = new Pagination();
        $pagination->total = $total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link('purchase/purchase/getQualityInspectionList','user_token='.$this->session->data['user_token'].'&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($total) ? $start + 1 : 0,
            ((($start) + $limit) > $total) ? $total : (($start) + $limit),
            $total,
            ceil($total / $limit)
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function saveQualityInspection() {
        $json = array();
        $this->load->language('purchase/purchase');

        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/purchase');
            $data = array(
                'inspection_id'     => isset($this->request->post['inspection_id']) ? (int)$this->request->post['inspection_id'] : 0,
                'inspection_number' => $this->request->post['inspection_number'],
                'goods_receipt_id'  => $this->request->post['goods_receipt_id'],
                'inspection_date'   => $this->request->post['inspection_date'],
                'notes'             => $this->request->post['notes'],
                'items'             => array()
            );

            if (!empty($this->request->post['inspection_item_id'])) {
                foreach ($this->request->post['inspection_item_id'] as $i => $rcptItemId) {
                    $data['items'][] = array(
                        'receipt_item_id' => $rcptItemId,
                        'quality_result'  => $this->request->post['quality_result'][$i],
                        'remarks'         => $this->request->post['remarks'][$i]
                    );
                }
            }

            $result = $this->model_purchase_purchase->saveQualityInspectionData($data);

            if (isset($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewQualityInspection() {
        $html = '';
        if (!empty($this->request->get['inspection_id'])) {
            $inspection_id = (int)$this->request->get['inspection_id'];
            $this->load->model('purchase/purchase');
            $info  = $this->model_purchase_purchase->getQualityInspection($inspection_id);
            $items = $this->model_purchase_purchase->getQualityInspectionItems($inspection_id);

            if ($info) {
                ob_start();
                echo '<div><strong>'.$this->language->get('text_inspection_number').':</strong> '.$info['inspection_number'].'</div>';
                echo '<div><strong>'.$this->language->get('text_goods_receipt').':</strong> '.$info['gr_number'].'</div>';
                echo '<div><strong>'.$this->language->get('text_inspection_date').':</strong> '.$info['inspection_date'].'</div>';
                echo '<div><strong>'.$this->language->get('text_status').':</strong> '.$info['status'].'</div>';
                echo '<hr>';
                echo '<table class="table table-bordered">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>'.$this->language->get('column_product').'</th>';
                echo '<th>'.$this->language->get('column_quantity').'</th>';
                echo '<th>'.$this->language->get('column_unit').'</th>';
                echo '<th>'.$this->language->get('column_quality_result').'</th>';
                echo '<th>'.$this->language->get('column_remarks').'</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                if ($items) {
                    foreach ($items as $i) {
                        echo '<tr>';
                        echo '<td>'.$i['product_name'].'</td>';
                        echo '<td>'.$i['quantity'].'</td>';
                        echo '<td>'.$i['unit_name'].'</td>';
                        echo '<td>'.$i['quality_result'].'</td>';
                        echo '<td>'.nl2br($i['remarks']).'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">'.$this->language->get('text_no_results').'</td></tr>';
                }
                echo '</tbody>';
                echo '</table>';
                $html = ob_get_clean();
            }
        }

        $this->response->setOutput($html);
    }

    public function deleteQualityInspection() {
        $json = array();
        $this->load->language('purchase/purchase');

        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!empty($this->request->get['inspection_id'])) {
                $this->load->model('purchase/purchase');
                $result = $this->model_purchase_purchase->deleteQualityInspection((int)$this->request->get['inspection_id']);
                if (!empty($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_delete');
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approveInspection() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $inspection_id = isset($this->request->post['inspection_id']) ? (int)$this->request->post['inspection_id'] : 0;
            $this->load->model('purchase/purchase');
            if ($inspection_id) {
                $result = $this->model_purchase_purchase->approveInspection($inspection_id);
                if (!empty($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_approve');
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function rejectInspection() {
        $json = array();
        $this->load->language('purchase/purchase');
        if (!$this->user->hasPermission('modify', 'purchase/purchase')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $inspection_id = isset($this->request->post['inspection_id']) ? (int)$this->request->post['inspection_id'] : 0;
            $reject_reason = isset($this->request->post['reject_reason']) ? $this->request->post['reject_reason'] : '';
            $this->load->model('purchase/purchase');
            if ($inspection_id) {
                $result = $this->model_purchase_purchase->rejectInspection($inspection_id, $reject_reason);
                if (!empty($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $json['success'] = $this->language->get('text_success_reject');
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}