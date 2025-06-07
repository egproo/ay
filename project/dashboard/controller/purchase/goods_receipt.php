<?php
class ControllerPurchaseGoodsReceipt extends Controller {
    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('purchase/goods_receipt');
        $this->load->language('purchase/goods_receipt');
        // Load other models as needed within methods
    }

    public function index() {
        $this->document->setTitle($this->language->get('heading_title'));

        // الإحصائيات الافتراضية
        $default_filter_data = array();
        $data['stats'] = $this->model_purchase_goods_receipt->getReceiptStats($default_filter_data);

        // البيانات الأساسية للصفحة
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_receipt_total'] = $this->language->get('text_receipt_total');
        $data['text_pending_receipts'] = $this->language->get('text_pending_receipts');
        $data['text_received_receipts'] = $this->language->get('text_received_receipts');
        $data['text_partially_received'] = $this->language->get('text_partially_received');

        // التحقق من الصلاحيات باستخدام hasPermission
        $data['can_view'] = $this->user->hasPermission('access', 'purchase/goods_receipt');
        $data['can_add'] = $this->user->hasPermission('modify', 'purchase/goods_receipt');
        $data['can_edit'] = $this->user->hasPermission('modify', 'purchase/goods_receipt');
        $data['can_delete'] = $this->user->hasPermission('modify', 'purchase/goods_receipt');
        $data['can_complete'] = $this->user->hasPermission('modify', 'purchase/goods_receipt');
        $data['can_quality_check'] = $this->user->hasPermission('modify', 'purchase/goods_receipt');

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

        $data['user_token'] = $this->session->data['user_token'];
        $data['home'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        // جلب قائمة الفروع والعملات للفلاتر
        $this->load->model('localisation/currency'); // Load currency model if not loaded in constructor
        $data['branches'] = $this->model_purchase_goods_receipt->getBranches();
        $data['currencies'] = $this->model_localisation_currency->getCurrencies(); // Use standard currency model

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/goods_receipt_list', $data));
    }

    /**
     * AJAX: جلب قائمة سندات الاستلام
     */
    public function ajaxList() {
        if (!$this->user->hasPermission('access', 'purchase/goods_receipt')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $json = array();

        $filter_receipt_number = isset($this->request->get['filter_receipt_number']) ? $this->request->get['filter_receipt_number'] : '';
        $filter_po_number = isset($this->request->get['filter_po_number']) ? $this->request->get['filter_po_number'] : '';
        $filter_status = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;

        $filter_data = array(
            'filter_receipt_number' => $filter_receipt_number,
            'filter_po_number'     => $filter_po_number,
            'filter_status'        => $filter_status,
            'filter_date_start'    => $filter_date_start,
            'filter_date_end'      => $filter_date_end,
            'start'               => ($page - 1) * $limit,
            'limit'               => $limit
        );

        $receipts = $this->model_purchase_goods_receipt->getReceipts($filter_data);
        $total = $this->model_purchase_goods_receipt->getTotalReceipts($filter_data);

        $json['stats'] = $this->model_purchase_goods_receipt->getReceiptStats($filter_data);

        $json['receipts'] = array();
        foreach ($receipts as $receipt) {
            $json['receipts'][] = array(
                'goods_receipt_id'  => $receipt['goods_receipt_id'],
                'receipt_number'    => $receipt['receipt_number'],
                'po_number'        => $receipt['po_number'],
                'branch_name'      => $receipt['branch_name'],
                'receipt_date'     => date($this->language->get('date_format_short'), strtotime($receipt['receipt_date'])), // Format date
                'status'          => $receipt['status'],
                'quality_status'   => $receipt['quality_status'] ?? 'pending', // Default if null
                'status_text'      => $this->model_purchase_goods_receipt->getStatusText($receipt['status']), // Add getStatusText to model
                'status_class'     => $this->model_purchase_goods_receipt->getStatusClass($receipt['status']), // Add getStatusClass to model

                // الصلاحيات على السند
                'can_view'   => $this->user->hasPermission('access', 'purchase/goods_receipt'),
                'can_edit'   => ($this->user->hasPermission('modify', 'purchase/goods_receipt') && $receipt['status'] == 'pending'),
                'can_delete' => ($this->user->hasPermission('modify', 'purchase/goods_receipt') && $receipt['status'] == 'pending'),
                'can_complete' => ($this->user->hasPermission('modify', 'purchase/goods_receipt') && in_array($receipt['status'], ['pending','partially_received'])),
                'can_quality_check' => ($this->user->hasPermission('modify', 'purchase/goods_receipt') && $receipt['quality_check_required'] && !$receipt['quality_checked_by'])
            );
        }

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = 'javascript:ReceiptManager.loadReceipts({page});'; // Use JS function name
        $json['pagination'] = $pagination->render();
        $json['total'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

        $this->sendJSON($json);
    }

    /**
     * AJAX: جلب منتجات أمر الشراء (Helper for forms, might be redundant if PO controller has similar)
     */
    public function ajaxPurchaseOrderProducts() {
        if (!$this->user->hasKey('purchase_goods_receipt_add') && !$this->user->hasKey('purchase_goods_receipt_view')) { // Allow view permission too
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $json = array();
        $po_id = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0;

        if ($po_id) {
            $products = $this->model_purchase_goods_receipt->getPurchaseOrderProducts($po_id);
            foreach ($products as $product) {
                $json[] = array(
                    'po_item_id'     => $product['po_item_id'],
                    'product_id'     => $product['product_id'],
                    'name'           => $product['name'],
                    'quantity'       => $product['quantity'],
                    'received_qty'   => $product['received_qty'],
                    'remaining_qty'  => max(0, (float)$product['quantity'] - (float)$product['received_qty']), // Ensure non-negative
                    'unit_id'        => $product['unit_id'],
                    'unit_name'      => $product['unit_name']
                );
            }
        }

        $this->sendJSON($json);
    }

    private function sendJSON($data) {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    // Removed ajaxAdd and ajaxEdit as they are likely redundant/incorrectly placed

    /**
     * AJAX: إكمال سند استلام
     */
    public function ajaxComplete() {
        $json = array();
        if (!$this->user->hasKey('purchase_goods_receipt_complete')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $receipt_id = (int)($this->request->post['goods_receipt_id'] ?? 0); // Changed to POST

        if ($receipt_id) {
            // Note: Refactored model function should only update status and add history
            $result = $this->model_purchase_goods_receipt->completeGoodsReceipt($receipt_id, $this->user->getId());
            if (!empty($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success_complete');
                // Journal ID is created during addGoodsReceipt in PO model, not here.
            }
        } else {
            $json['error'] = 'Missing receipt_id';
        }

        return $this->sendJSON($json);
    }

    /**
     * View Goods Receipt Details
     */
    public function view() {
        if (!$this->user->hasKey('purchase_goods_receipt_view')) {
            return $this->load->view('error/permission', []);
        }

        $receipt_id = isset($this->request->get['goods_receipt_id']) ? (int)$this->request->get['goods_receipt_id'] : 0;

        if (!$receipt_id) {
            return $this->load->view('error/not_found', []);
        }

        $receipt_info = $this->model_purchase_goods_receipt->getGoodsReceipt($receipt_id);

        if (!$receipt_info) {
            return $this->load->view('error/not_found', []);
        }

        $this->load->language('purchase/goods_receipt');
        $this->load->model('localisation/currency');

        $data = array();
        $data['heading_title'] = $this->language->get('text_receipt_details') . ' #' . $receipt_info['receipt_number'];
        $data['text_receipt_details'] = $this->language->get('text_receipt_details');

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/goods_receipt', 'user_token=' . $this->session->data['user_token'], true)
        );
         $data['breadcrumbs'][] = array(
            'text' => $receipt_info['receipt_number'],
            'href' => $this->url->link('purchase/goods_receipt/view', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true)
        );


        // Format Receipt Data
        $currency_code = $receipt_info['currency_code'] ?? $this->config->get('config_currency');
        $data['receipt'] = [
            'goods_receipt_id' => $receipt_info['goods_receipt_id'],
            'receipt_number'   => $receipt_info['receipt_number'],
            'po_id'            => $receipt_info['po_id'],
            'po_number'        => $receipt_info['po_number'],
            'branch_name'      => $receipt_info['branch_name'],
            'supplier_name'    => $receipt_info['supplier_name'],
            'receipt_date'     => date($this->language->get('date_format_short'), strtotime($receipt_info['receipt_date'])),
            'invoice_number'   => $receipt_info['invoice_number'] ?? '',
            'invoice_date'     => $receipt_info['invoice_date'] ? date($this->language->get('date_format_short'), strtotime($receipt_info['invoice_date'])) : '',
            'invoice_amount'   => $receipt_info['invoice_amount'] ? $this->currency->format($receipt_info['invoice_amount'], $currency_code, $receipt_info['exchange_rate']) : '',
            'status'           => $receipt_info['status'],
            'status_text'      => $this->model_purchase_goods_receipt->getStatusText($receipt_info['status']),
            'status_class'     => $this->model_purchase_goods_receipt->getStatusClass($receipt_info['status']),
            'quality_check_required' => $receipt_info['quality_check_required'],
            'quality_checked_by_name' => $receipt_info['checked_by_name'] ?? '',
            'quality_check_date' => $receipt_info['quality_check_date'] ? date($this->language->get('datetime_format'), strtotime($receipt_info['quality_check_date'])) : '',
            'notes'            => nl2br($receipt_info['notes']),
            'created_by_name'  => $receipt_info['created_by_name'],
            'created_at'       => date($this->language->get('datetime_format'), strtotime($receipt_info['created_at'])),
            'journal_id'       => $receipt_info['journal_id'] ?? null
        ];

        // Get Receipt Items
        $data['items'] = $this->model_purchase_goods_receipt->getGoodsReceiptItems($receipt_id);
        foreach ($data['items'] as &$item) {
            $item['po_unit_price_formatted'] = $this->currency->format($item['po_unit_price'], $currency_code, $receipt_info['exchange_rate']);
            $item['invoice_unit_price_formatted'] = isset($item['invoice_unit_price']) ? $this->currency->format($item['invoice_unit_price'], $currency_code, $receipt_info['exchange_rate']) : '-';
            $item['quality_result_text'] = $this->model_purchase_goods_receipt->getQualityResultText($item['quality_result']); // Add helper in model
            $item['quality_result_class'] = $this->model_purchase_goods_receipt->getQualityResultClass($item['quality_result']); // Add helper in model
        }
        unset($item);

        // Get History
        $data['history'] = $this->model_purchase_goods_receipt->getReceiptHistory($receipt_id);

        // Get Documents
        $data['documents'] = $this->model_purchase_goods_receipt->getDocuments($receipt_id); // Assuming this exists

        // Permissions
        $data['can_edit'] = $this->user->hasKey('purchase_goods_receipt_edit') && $receipt_info['status'] == 'pending';
        $data['can_delete'] = $this->user->hasKey('purchase_goods_receipt_delete') && $receipt_info['status'] == 'pending';
        $data['can_complete'] = $this->user->hasKey('purchase_goods_receipt_complete') && in_array($receipt_info['status'], ['pending','partially_received']);
        $data['can_quality_check'] = $this->user->hasKey('purchase_goods_receipt_quality') && $receipt_info['quality_check_required'] && !$receipt_info['quality_checked_by'];
        $data['can_print'] = $this->user->hasKey('purchase_goods_receipt_print'); // Add permission key if needed
        $data['can_upload'] = $this->user->hasKey('purchase_goods_receipt_upload'); // Add permission key if needed
        $data['can_download'] = $this->user->hasKey('purchase_goods_receipt_view');

        // Language Strings
        $data['text_receipt_info'] = $this->language->get('text_receipt_info');
        $data['text_items_received'] = $this->language->get('text_items_received');
        $data['text_history'] = $this->language->get('text_history');
        $data['text_documents'] = $this->language->get('text_documents');
        $data['text_po_number'] = $this->language->get('text_po_number');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_branch'] = $this->language->get('text_branch');
        $data['text_receipt_date'] = $this->language->get('text_receipt_date');
        $data['text_invoice_number'] = $this->language->get('text_invoice_number');
        $data['text_invoice_date'] = $this->language->get('text_invoice_date');
        $data['text_invoice_amount'] = $this->language->get('text_invoice_amount');
        $data['text_status'] = $this->language->get('text_status');
        $data['text_quality_check'] = $this->language->get('text_quality_check');
        $data['text_quality_checked_by'] = $this->language->get('text_quality_checked_by');
        $data['text_quality_check_date'] = $this->language->get('text_quality_check_date');
        $data['text_notes'] = $this->language->get('text_notes');
        $data['text_created_by'] = $this->language->get('text_created_by');
        $data['text_date_added'] = $this->language->get('text_date_added');
        $data['text_journal_entry'] = $this->language->get('text_journal_entry');
        $data['text_no_items'] = $this->language->get('text_no_items');
        $data['text_no_history'] = $this->language->get('text_no_history');
        $data['text_no_documents'] = $this->language->get('text_no_documents');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_ordered_quantity'] = $this->language->get('column_ordered_quantity');
        $data['column_received_quantity'] = $this->language->get('column_received_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_po_price'] = $this->language->get('column_po_price');
        $data['column_invoice_price'] = $this->language->get('column_invoice_price');
        $data['column_quality_result'] = $this->language->get('column_quality_result');
        $data['column_remarks'] = $this->language->get('column_remarks');
        $data['column_date'] = $this->language->get('column_date');
        $data['column_user'] = $this->language->get('column_user');
        $data['column_action_type'] = $this->language->get('column_action_type');
        $data['column_description'] = $this->language->get('column_description');
        $data['column_document_name'] = $this->language->get('column_document_name');
        $data['column_document_type'] = $this->language->get('column_document_type');
        $data['column_uploaded_by'] = $this->language->get('column_uploaded_by');
        $data['column_upload_date'] = $this->language->get('column_upload_date');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_complete'] = $this->language->get('button_complete');
        $data['button_quality_check'] = $this->language->get('button_quality_check');
        $data['button_print'] = $this->language->get('button_print');
        $data['button_upload'] = $this->language->get('button_upload');
        $data['button_download'] = $this->language->get('button_download');
        $data['button_back'] = $this->language->get('button_back');

        $data['user_token'] = $this->session->data['user_token'];

        // Load common parts
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/goods_receipt_view', $data)); // Needs view template
    }

    // TODO: Add delete, quality check, print methods

    /**
     * عرض صفحة فحص الجودة
     */
    public function qualityCheck() {
        if (!$this->user->hasKey('purchase_goods_receipt_quality')) {
            return $this->load->view('error/permission', []);
        }

        $receipt_id = isset($this->request->get['goods_receipt_id']) ? (int)$this->request->get['goods_receipt_id'] : 0;

        if (!$receipt_id) {
            return $this->load->view('error/not_found', []);
        }

        $receipt_info = $this->model_purchase_goods_receipt->getGoodsReceipt($receipt_id);

        if (!$receipt_info) {
            return $this->load->view('error/not_found', []);
        }

        // التحقق من أن السند يتطلب فحص جودة وأنه لم يتم فحصه بعد
        if (!$receipt_info['quality_check_required'] || $receipt_info['quality_checked_by']) {
            $this->session->data['error'] = $this->language->get('error_quality_check_not_required');
            $this->response->redirect($this->url->link('purchase/goods_receipt/view', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true));
        }

        $this->load->language('purchase/goods_receipt');
        $this->load->model('localisation/currency');

        $data = array();
        $data['heading_title'] = $this->language->get('text_quality_check') . ' #' . $receipt_info['receipt_number'];
        $data['text_quality_check'] = $this->language->get('text_quality_check');

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/goods_receipt', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $receipt_info['receipt_number'],
            'href' => $this->url->link('purchase/goods_receipt/view', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_quality_check'),
            'href' => $this->url->link('purchase/goods_receipt/qualityCheck', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true)
        );

        // Format Receipt Data
        $currency_code = $receipt_info['currency_code'] ?? $this->config->get('config_currency');
        $data['receipt'] = [
            'goods_receipt_id' => $receipt_info['goods_receipt_id'],
            'receipt_number'   => $receipt_info['receipt_number'],
            'po_id'            => $receipt_info['po_id'],
            'po_number'        => $receipt_info['po_number'],
            'branch_name'      => $receipt_info['branch_name'],
            'supplier_name'    => $receipt_info['supplier_name'],
            'receipt_date'     => date($this->language->get('date_format_short'), strtotime($receipt_info['receipt_date'])),
            'status'           => $receipt_info['status'],
            'status_text'      => $this->model_purchase_goods_receipt->getStatusText($receipt_info['status']),
            'status_class'     => $this->model_purchase_goods_receipt->getStatusClass($receipt_info['status'])
        ];

        // Get Receipt Items
        $data['items'] = $this->model_purchase_goods_receipt->getGoodsReceiptItems($receipt_id);
        foreach ($data['items'] as &$item) {
            $item['po_unit_price_formatted'] = $this->currency->format($item['po_unit_price'], $currency_code, $receipt_info['exchange_rate']);
            $item['invoice_unit_price_formatted'] = isset($item['invoice_unit_price']) ? $this->currency->format($item['invoice_unit_price'], $currency_code, $receipt_info['exchange_rate']) : '-';
            // إضافة حالة الجودة الحالية إذا كانت موجودة
            $item['quality_result_text'] = $item['quality_result'] ? $this->model_purchase_goods_receipt->getQualityResultText($item['quality_result']) : '';
            $item['quality_result_class'] = $item['quality_result'] ? $this->model_purchase_goods_receipt->getQualityResultClass($item['quality_result']) : '';
        }
        unset($item);

        // Language Strings
        $data['text_receipt_info'] = $this->language->get('text_receipt_info');
        $data['text_items_received'] = $this->language->get('text_items_received');
        $data['text_po_number'] = $this->language->get('text_po_number');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_branch'] = $this->language->get('text_branch');
        $data['text_receipt_date'] = $this->language->get('text_receipt_date');
        $data['text_status'] = $this->language->get('text_status');
        $data['text_quality_notes'] = $this->language->get('text_quality_notes');
        $data['text_no_items'] = $this->language->get('text_no_items');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_ordered_quantity'] = $this->language->get('column_ordered_quantity');
        $data['column_received_quantity'] = $this->language->get('column_received_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_po_price'] = $this->language->get('column_po_price');
        $data['column_invoice_price'] = $this->language->get('column_invoice_price');
        $data['column_quality_result'] = $this->language->get('column_quality_result');
        $data['column_remarks'] = $this->language->get('column_remarks');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_complete'] = $this->language->get('button_complete_quality');

        $data['text_approved'] = $this->language->get('text_approved');
        $data['text_rejected'] = $this->language->get('text_rejected');
        $data['text_partial'] = $this->language->get('text_partial');

        $data['user_token'] = $this->session->data['user_token'];
        $data['action_save'] = $this->url->link('purchase/goods_receipt/saveQualityCheck', 'user_token=' . $this->session->data['user_token'], true);
        $data['action_item'] = $this->url->link('purchase/goods_receipt/ajaxQualityCheck', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('purchase/goods_receipt/view', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true);

        // Load common parts
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/goods_receipt_quality', $data)); // Needs quality check template
    }

    /**
     * AJAX: حفظ نتائج فحص الجودة لعنصر واحد
     */
    public function ajaxQualityCheck() {
        $json = array();

        if (!$this->user->hasKey('purchase_goods_receipt_quality')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $receipt_item_id = isset($this->request->post['receipt_item_id']) ? (int)$this->request->post['receipt_item_id'] : 0;
        $quality_result = isset($this->request->post['quality_result']) ? $this->request->post['quality_result'] : '';
        $remarks = isset($this->request->post['remarks']) ? $this->request->post['remarks'] : '';

        if (!$receipt_item_id) {
            $json['error'] = $this->language->get('error_item_id');
            return $this->sendJSON($json);
        }

        if (!in_array($quality_result, array('approved', 'rejected', 'partial'))) {
            $json['error'] = $this->language->get('error_invalid_result');
            return $this->sendJSON($json);
        }

        $result = $this->model_purchase_goods_receipt->updateItemQualityCheck($receipt_item_id, $quality_result, $remarks);

        if (!$result) {
            $json['error'] = $this->language->get('error_update_failed');
            return $this->sendJSON($json);
        }

        $json['success'] = $this->language->get('text_success_item_update');
        $json['quality_result_text'] = $this->model_purchase_goods_receipt->getQualityResultText($quality_result);
        $json['quality_result_class'] = $this->model_purchase_goods_receipt->getQualityResultClass($quality_result);

        return $this->sendJSON($json);
    }


    /**
     * AJAX: حفظ نتائج فحص الجودة للسند كامل
     */
    public function saveQualityCheck() {
        $json = array();

        if (!$this->user->hasKey('purchase_goods_receipt_quality')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $receipt_id = isset($this->request->post['goods_receipt_id']) ? (int)$this->request->post['goods_receipt_id'] : 0;
        $quality_notes = isset($this->request->post['quality_notes']) ? $this->request->post['quality_notes'] : '';

        if (!$receipt_id) {
            $json['error'] = $this->language->get('error_receipt_id');
            return $this->sendJSON($json);
        }

        $result = $this->model_purchase_goods_receipt->completeQualityCheck($receipt_id, $quality_notes, $this->user->getId());

        if (!$result) {
            $json['error'] = $this->language->get('error_update_failed');
            return $this->sendJSON($json);
        }

        $json['success'] = $this->language->get('text_success_quality_complete');
        $json['redirect'] = $this->url->link('purchase/goods_receipt/view', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true);

        return $this->sendJSON($json);
    }

    /**
     * عرض صفحة فحص الجودة
     */
    public function qualityCheck() {
        if (!$this->user->hasKey('purchase_goods_receipt_quality')) {
            return $this->load->view('error/permission', []);
        }

        $receipt_id = isset($this->request->get['goods_receipt_id']) ? (int)$this->request->get['goods_receipt_id'] : 0;

        if (!$receipt_id) {
            return $this->load->view('error/not_found', []);
        }

        $receipt_info = $this->model_purchase_goods_receipt->getGoodsReceipt($receipt_id);

        if (!$receipt_info) {
            return $this->load->view('error/not_found', []);
        }

        // Check if quality check is required and not already done
        if (!$receipt_info['quality_check_required'] || $receipt_info['quality_checked_by']) {
            $this->session->data['error_warning'] = $this->language->get('error_quality_check_not_needed');
            $this->response->redirect($this->url->link('purchase/goods_receipt/view', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true));
        }

        $this->load->language('purchase/goods_receipt');
        $this->document->setTitle($this->language->get('text_quality_check'));

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/goods_receipt', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $receipt_info['receipt_number'],
            'href' => $this->url->link('purchase/goods_receipt/view', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_quality_check'),
            'href' => $this->url->link('purchase/goods_receipt/qualityCheck', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true)
        );

        // Receipt Info
        $data['receipt'] = array(
            'goods_receipt_id' => $receipt_info['goods_receipt_id'],
            'receipt_number'   => $receipt_info['receipt_number'],
            'po_number'        => $receipt_info['po_number'],
            'supplier_name'    => $receipt_info['supplier_name'],
            'branch_name'      => $receipt_info['branch_name'],
            'receipt_date'     => date($this->language->get('date_format_short'), strtotime($receipt_info['receipt_date']))
        );

        // Get Receipt Items
        $data['items'] = $this->model_purchase_goods_receipt->getGoodsReceiptItems($receipt_id);

        // Language Strings
        $data['heading_title'] = $this->language->get('text_quality_check') . ' - ' . $receipt_info['receipt_number'];
        $data['text_quality_check'] = $this->language->get('text_quality_check');
        $data['text_receipt_info'] = $this->language->get('text_receipt_info');
        $data['text_receipt_number'] = $this->language->get('text_receipt_number');
        $data['text_po_number'] = $this->language->get('text_po_number');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_branch'] = $this->language->get('text_branch');
        $data['text_receipt_date'] = $this->language->get('text_receipt_date');
        $data['text_items'] = $this->language->get('text_items');
        $data['text_quality_notes'] = $this->language->get('text_quality_notes');
        $data['text_no_items'] = $this->language->get('text_no_items');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_quality_result'] = $this->language->get('column_quality_result');
        $data['column_remarks'] = $this->language->get('column_remarks');
        $data['column_action'] = $this->language->get('column_action');

        $data['text_quality_pass'] = $this->language->get('text_quality_pass');
        $data['text_quality_fail'] = $this->language->get('text_quality_fail');
        $data['text_quality_partial'] = $this->language->get('text_quality_partial');
        $data['text_check_item'] = $this->language->get('text_check_item');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_check'] = $this->language->get('button_check');
        $data['button_close'] = $this->language->get('button_close');
        $data['button_back'] = $this->language->get('button_back');

        $data['error_quality_status'] = $this->language->get('error_quality_status');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } elseif (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']);
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['back'] = $this->url->link('purchase/goods_receipt/view', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true);
        $data['user_token'] = $this->session->data['user_token'];

        // Load common parts
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/goods_receipt_quality_check', $data));
    }

    /**
     * AJAX: جلب معلومات عنصر لفحص الجودة
     */
    public function getQualityCheck() {
        $json = array();

        if (!$this->user->hasKey('purchase_goods_receipt_quality')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $item_id = isset($this->request->post['item_id']) ? (int)$this->request->post['item_id'] : 0;

        if (!$item_id) {
            $json['error'] = $this->language->get('error_item_id');
            return $this->sendJSON($json);
        }

        $item_info = $this->model_purchase_goods_receipt->getReceiptItem($item_id);

        if (!$item_info) {
            $json['error'] = $this->language->get('error_item_not_found');
            return $this->sendJSON($json);
        }

        $json['item'] = array(
            'receipt_item_id' => $item_info['receipt_item_id'],
            'product_name' => $item_info['product_name'],
            'quality_status' => $item_info['quality_status'] ?? '',
            'quality_notes' => $item_info['quality_notes'] ?? ''
        );

        return $this->sendJSON($json);
    }

    /**
     * AJAX: حفظ حالة الجودة لعنصر واحد
     */
    public function ajaxQualityCheck() {
        $json = array();

        if (!$this->user->hasKey('purchase_goods_receipt_quality')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $item_id = isset($this->request->post['item_id']) ? (int)$this->request->post['item_id'] : 0;
        $status = isset($this->request->post['status']) ? $this->request->post['status'] : '';
        $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';

        if (!$item_id) {
            $json['error'] = $this->language->get('error_item_id');
            return $this->sendJSON($json);
        }

        if (!in_array($status, ['approved', 'rejected', 'partial'])) {
            $json['error'] = $this->language->get('error_quality_status');
            return $this->sendJSON($json);
        }

        $result = $this->model_purchase_goods_receipt->updateItemQualityStatus($item_id, $status, $notes);

        if (!$result) {
            $json['error'] = $this->language->get('error_update_failed');
            return $this->sendJSON($json);
        }

        $json['success'] = $this->language->get('text_success_quality_check');

        return $this->sendJSON($json);
    }

    /**
     * AJAX: حفظ ملاحظات الجودة العامة وإكمال فحص الجودة
     */
    public function saveQualityCheck() {
        $json = array();

        if (!$this->user->hasKey('purchase_goods_receipt_quality')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $receipt_id = isset($this->request->post['goods_receipt_id']) ? (int)$this->request->post['goods_receipt_id'] : 0;
        $quality_notes = isset($this->request->post['quality_notes']) ? $this->request->post['quality_notes'] : '';

        if (!$receipt_id) {
            $json['error'] = $this->language->get('error_receipt_id');
            return $this->sendJSON($json);
        }

        // تحقق من أن جميع العناصر تم فحصها
        $items = $this->model_purchase_goods_receipt->getGoodsReceiptItems($receipt_id);
        $all_checked = true;

        foreach ($items as $item) {
            if (empty($item['quality_status'])) {
                $all_checked = false;
                break;
            }
        }

        if (!$all_checked) {
            $json['error'] = $this->language->get('error_items_not_checked');
            return $this->sendJSON($json);
        }

        $result = $this->model_purchase_goods_receipt->completeQualityCheck($receipt_id, $quality_notes, $this->user->getId());

        if (!$result) {
            $json['error'] = $this->language->get('error_update_failed');
            return $this->sendJSON($json);
        }

        $json['success'] = $this->language->get('text_success_quality_complete');
        $json['redirect'] = $this->url->link('purchase/goods_receipt/view', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt_id, true);

        return $this->sendJSON($json);
    }

    /**
     * AJAX method to delete goods receipt
     */
    public function ajaxDelete() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/goods_receipt')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $receipt_id = (int)($this->request->get['receipt_id'] ?? 0);

        if ($receipt_id) {
            $result = $this->model_purchase_goods_receipt->deleteGoodsReceipt($receipt_id);
            if (!empty($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success_delete');
            }
        } else {
            $json['error'] = 'Missing receipt_id';
        }

        return $this->sendJSON($json);
    }

    /**
     * AJAX method to approve goods receipt
     */
    public function ajaxApprove() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/goods_receipt')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $receipt_id = (int)($this->request->get['receipt_id'] ?? 0);

        if ($receipt_id) {
            $result = $this->model_purchase_goods_receipt->approveGoodsReceipt($receipt_id, $this->user->getId());
            if (!empty($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_success_approve');
            }
        } else {
            $json['error'] = 'Missing receipt_id';
        }

        return $this->sendJSON($json);
    }
}
