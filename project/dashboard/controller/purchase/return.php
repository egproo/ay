<?php
class ControllerPurchaseReturn extends Controller {
    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);
        // Load necessary models and language files
        $this->load->model('purchase/return'); // Uncommented model load
        $this->load->language('purchase/return');
        $this->load->model('purchase/supplier'); // Needed for supplier lookup
        $this->load->model('purchase/order'); // Needed for PO lookup
        $this->load->model('localisation/return_status'); // Needed for status text/class
        // Load models needed for form data later in form() method
    }

    public function index() {
        // Basic structure for the list page
        $this->document->setTitle($this->language->get('heading_title'));

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        // Add other common language strings
        $data['suppliers'] = $this->model_purchase_supplier->getSuppliers(); // Fetch suppliers for filter

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/return', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Add button (placeholder)
        $data['add'] = $this->url->link('purchase/return/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['button_add'] = $this->language->get('button_add');
        $data['button_delete'] = $this->language->get('button_delete'); // Placeholder

        // Export buttons
        $data['export_pdf'] = $this->url->link('purchase/return/exportPdf', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_excel'] = $this->url->link('purchase/return/exportExcel', 'user_token=' . $this->session->data['user_token'], true);
        $data['button_export_pdf'] = $this->language->get('button_export_pdf');
        $data['button_export_excel'] = $this->language->get('button_export_excel');

        // Permissions check (basic)
        $data['can_add'] = $this->user->hasPermission('modify', 'purchase/return');
        $data['can_delete'] = $this->user->hasPermission('modify', 'purchase/return');
        $data['can_export'] = $this->user->hasPermission('access', 'purchase/return');
        $data['can_approve'] = $this->user->hasPermission('modify', 'purchase/return');

        // Session messages
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        // Placeholder for list data (will be loaded via AJAX)
        $data['returns'] = array();
        $data['pagination'] = '';
        $data['results'] = '';

        // Load return statuses for filter
        $this->load->model('localisation/return_status');
        $data['return_statuses'] = $this->model_localisation_return_status->getReturnStatuses();


        // Load common parts
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Load the list view
        $this->response->setOutput($this->load->view('purchase/return_list', $data));
    }

    /**
     * AJAX: جلب قائمة مرتجعات المشتريات
     */
    public function ajaxList() {
        if (!$this->user->hasPermission('access', 'purchase/return')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $this->load->language('purchase/return');
        $this->load->model('purchase/return'); // Ensure model is loaded

        $json = array();

        // Filters
        $filter_return_id = isset($this->request->get['filter_return_id']) ? $this->request->get['filter_return_id'] : '';
        $filter_po_id = isset($this->request->get['filter_po_id']) ? (int)$this->request->get['filter_po_id'] : 0;
        $filter_supplier_id = isset($this->request->get['filter_supplier_id']) ? (int)$this->request->get['filter_supplier_id'] : 0;
        $filter_return_status_id = isset($this->request->get['filter_status']) ? (int)$this->request->get['filter_status'] : 0; // Use status ID for filtering
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';
        $page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : $this->config->get('config_limit_admin');
        $sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'pr.date_added'; // Default sort
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';

        $filter_data = array(
            'filter_return_id'        => $filter_return_id,
            'filter_po_id'            => $filter_po_id,
            'filter_supplier_id'      => $filter_supplier_id,
            'filter_return_status_id' => $filter_return_status_id,
            'filter_date_start'       => $filter_date_start,
            'filter_date_end'         => $filter_date_end,
            'sort'                    => $sort,
            'order'                   => $order,
            'start'                   => ($page - 1) * $limit,
            'limit'                   => $limit
        );

        $returns = $this->model_purchase_return->getReturns($filter_data);
        $total = $this->model_purchase_return->getTotalReturns($filter_data);

        $json['returns'] = array();
        foreach ($returns as $return_info) {
            $json['returns'][] = array(
                'return_id'      => $return_info['return_id'],
                'po_number'      => $return_info['po_number'] ?? '',
                'supplier_name'  => $return_info['supplier_name'],
                'status_id'      => $return_info['return_status_id'],
                'status_text'    => $return_info['status_name'] ?? $this->model_purchase_return->getStatusText($return_info['return_status_id']), // Use joined name or fallback
                'status_class'   => $this->model_purchase_return->getStatusClass($return_info['return_status_id']), // Need implementation in model
                'date_added'     => date($this->language->get('date_format_short'), strtotime($return_info['date_added'])),
                'date_modified'  => date($this->language->get('date_format_short'), strtotime($return_info['date_modified'])),

                // Permissions
                'can_view'       => $this->user->hasPermission('access', 'purchase/return'),
                'can_edit'       => $this->user->hasPermission('modify', 'purchase/return'), // Add status check later
                'can_delete'     => $this->user->hasPermission('modify', 'purchase/return') // Add status check later
            );
        }

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = 'javascript:ReturnManager.loadReturns({page});'; // Use JS function
        $json['pagination'] = $pagination->render();
        $json['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

        $this->sendJSON($json);
    }

    /**
     * عرض نموذج إضافة/تعديل مرتجع مشتريات
     */
    public function form() {
        $this->load->language('purchase/return');
        // Load models needed for form data
        $this->load->model('purchase/return');
        $this->load->model('purchase/supplier');
        $this->load->model('purchase/order');
        $this->load->model('purchase/goods_receipt'); // Needed for GRN lookup
        $this->load->model('localisation/return_reason');
        $this->load->model('localisation/return_action');
        $this->load->model('localisation/return_status');
        $this->load->model('localisation/currency'); // For formatting

        $data = array();
        $data['text_form_title'] = $this->language->get('text_add'); // Default title

        $return_id = isset($this->request->get['return_id']) ? (int)$this->request->get['return_id'] : 0;

        // Determine mode (add/edit)
        $data['mode'] = $return_id ? 'edit' : 'add';

        $return_info = null;
        if ($return_id) { // Editing existing return
            $return_info = $this->model_purchase_return->getReturn($return_id); // Assuming this function exists
            if ($return_info) {
                $data['text_form_title'] = $this->language->get('text_edit');
                // TODO: Add check for editable status if needed
            } else {
                $this->session->data['error_warning'] = $this->language->get('error_return_not_found');
                return $this->response->setOutput($this->load->view('error/not_found', $data)); // Basic error handling
            }
        }

        // --- Populate Form Data ---
        $data['return_id'] = $return_id;
        $data['supplier_id'] = $return_info['supplier_id'] ?? 0;
        $data['po_id'] = $return_info['po_id'] ?? 0;
        $data['po_number'] = $return_info['po_number'] ?? ''; // Need to fetch this if editing
        $data['goods_receipt_id'] = $return_info['goods_receipt_id'] ?? 0; // Assuming this field exists
        $data['receipt_number'] = $return_info['receipt_number'] ?? ''; // Need to fetch this if editing
        $data['return_date'] = isset($return_info['return_date']) ? date('Y-m-d', strtotime($return_info['return_date'])) : date('Y-m-d');
        $data['return_reason_id'] = $return_info['return_reason_id'] ?? 0;
        $data['return_action_id'] = $return_info['return_action_id'] ?? 0;
        $data['return_status_id'] = $return_info['return_status_id'] ?? 0; // Default status might be set in model
        $data['comment'] = $return_info['comment'] ?? '';

        // --- Fetch Supporting Data ---
        $data['suppliers'] = $this->model_purchase_supplier->getSuppliers();
        $data['return_reasons'] = $this->model_localisation_return_reason->getReturnReasons(); // Assuming model exists
        $data['return_actions'] = $this->model_localisation_return_action->getReturnActions(); // Assuming model exists
        // $data['return_statuses'] = $this->model_localisation_return_status->getReturnStatuses(); // For status dropdown if needed

        // Fetch items if editing
        $data['items'] = array();
        if ($return_id && $return_info) {
            $items = $this->model_purchase_return->getReturnItems($return_id); // Assuming this function exists
            foreach ($items as $item) {
                 // Format data for the view
                 $data['items'][] = array(
                     'product_id' => $item['product_id'],
                     'product_name' => $item['product_name'], // Assuming joined in getReturnItems
                     'quantity' => $item['quantity'],
                     'unit_id' => $item['unit_id'],
                     'unit_name' => $item['unit_name'], // Assuming joined
                     'unit_price' => $item['unit_price'], // Price at time of return/receipt
                     'unit_price_formatted' => $this->currency->format($item['unit_price'], $this->config->get('config_currency')), // Adjust currency later
                     'total_formatted' => $this->currency->format($item['quantity'] * $item['unit_price'], $this->config->get('config_currency')), // Adjust currency later
                     'received_quantity' => $item['received_quantity'] ?? $item['quantity'] // Need a way to get original received qty for max validation
                 );
            }
        }

        // --- Language Strings ---
        $data['entry_supplier'] = $this->language->get('entry_supplier');
        $data['entry_purchase_order'] = $this->language->get('entry_purchase_order');
        $data['entry_goods_receipt'] = $this->language->get('entry_goods_receipt');
        $data['entry_return_date'] = $this->language->get('entry_return_date');
        $data['entry_return_reason'] = $this->language->get('entry_return_reason');
        $data['entry_return_action'] = $this->language->get('entry_return_action');
        $data['entry_comment'] = $this->language->get('entry_comment');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_received_qty'] = $this->language->get('column_received_qty');
        $data['column_return_qty'] = $this->language->get('column_return_qty');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_total'] = $this->language->get('column_total');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['text_select_supplier'] = $this->language->get('text_select_supplier');
        $data['text_select_po'] = $this->language->get('text_select_po');
        $data['text_select_grn'] = $this->language->get('text_select_grn');
        $data['text_select'] = $this->language->get('text_select');
        $data['text_items_to_return'] = $this->language->get('text_items_to_return');
        $data['text_select_grn_or_po'] = $this->language->get('text_select_grn_or_po');
        $data['text_no_returnable_items'] = $this->language->get('text_no_returnable_items');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['error_ajax'] = $this->language->get('error_ajax');
        $data['error_payment_exceeds_due'] = $this->language->get('error_payment_exceeds_due'); // Re-use or create specific error

        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('purchase/return_form', $data));
    }

    /**
     * AJAX: حفظ مرتجع مشتريات (إضافة أو تعديل)
     */
    public function ajaxSave() {
        $this->load->language('purchase/return');
        $this->load->model('purchase/return');
        $json = array();

        // Check permissions
        if (!$this->user->hasPermission('modify', 'purchase/return')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        // Check request method
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $json['error'] = $this->language->get('error_invalid_request');
            return $this->sendJSON($json);
        }

        // --- Validation ---
        $this->error = array(); // Reset errors

        if (empty($this->request->post['supplier_id'])) {
            $this->error['supplier'] = $this->language->get('error_supplier_required');
        }
        if (empty($this->request->post['return_date'])) {
             $this->error['return_date'] = $this->language->get('error_return_date_required');
        }
        if (empty($this->request->post['return_reason_id'])) {
             $this->error['return_reason_id'] = $this->language->get('error_return_reason_required');
        }
        if (empty($this->request->post['return_action_id'])) {
             $this->error['return_action_id'] = $this->language->get('error_return_action_required');
        }

        $has_valid_item = false;
        if (empty($this->request->post['item'])) {
             $this->error['items'] = $this->language->get('error_items_required');
        } else {
            // Validate items
            foreach ($this->request->post['item'] as $key => $item) {
                if (empty($item['product_id'])) {
                    // Skip potentially empty template rows if any
                    continue;
                }
                $return_qty = isset($item['quantity']) ? (float)$item['quantity'] : 0;
                if ($return_qty <= 0) {
                    // Don't error, just ignore items with 0 quantity for return
                    continue;
                }
                $has_valid_item = true; // Found at least one item with quantity > 0

                // TODO: Add validation against received quantity if possible
                // This might require fetching received qty based on product_id/grn_item_id/po_item_id
                // $max_returnable = $this->model_purchase_return->getReturnableQuantity($item['product_id'], ...);
                // if ($return_qty > $max_returnable) {
                //     $this->error['item'][$key]['quantity'] = sprintf($this->language->get('error_qty_exceeds_received'), $item['product_name'] ?? $item['product_id']);
                // }
            }
            if (!$has_valid_item) {
                 $this->error['items'] = $this->language->get('error_no_items_to_return');
            }
        }

        if ($this->error) {
             $json['error'] = $this->language->get('error_warning');
             $json['errors'] = $this->error;
        } else {
            $return_id = isset($this->request->post['return_id']) ? (int)$this->request->post['return_id'] : 0;

            // --- Prepare data for model ---
            $return_data = $this->request->post;
            $return_data['user_id'] = $this->user->getId();

            // Filter only items with quantity > 0 before sending to model
            $valid_items_data = [];
            if (isset($return_data['item'])) {
                 foreach ($return_data['item'] as $item) {
                     if (isset($item['quantity']) && (float)$item['quantity'] > 0) {
                         $valid_items_data[] = $item;
                     }
                 }
            }
            $return_data['items'] = $valid_items_data; // Replace with filtered items

            if ($return_id) {
                // --- Edit Return ---
                if ($this->model_purchase_return->editReturn($return_id, $return_data)) {
                    $json['success'] = $this->language->get('text_success_edit');
                    $json['return_id'] = $return_id;
                } else {
                    $json['error'] = $this->language->get('error_saving');
                }
            } else {
                // --- Add Return ---
                $result = $this->model_purchase_return->addReturn($return_data); // Changed to capture array
                if ($result && isset($result['return_id'])) {
                    $json['success'] = $this->language->get('text_success_add');
                    $json['return_id'] = $result['return_id'];
                    $json['return_number'] = $result['return_number']; // Include return number if needed
                } else {
                     $json['error'] = $this->language->get('error_saving');
                }
            }
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX: Delete a purchase return
     */
    public function delete() {
        $this->load->language('purchase/return');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/return')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $return_id = isset($this->request->post['return_id']) ? (int)$this->request->post['return_id'] : 0;

        if (!$return_id) {
            $json['error'] = $this->language->get('error_return_required'); // Add this lang string
            return $this->sendJSON($json);
        }

        try {
            // Note: The model's deleteReturn currently doesn't reverse inventory/accounting.
            // Add warnings or prevent deletion based on status if needed here or in model.
            $result = $this->model_purchase_return->deleteReturn($return_id);
            if ($result) {
                // History is added in the model after successful deletion
                $json['success'] = $this->language->get('text_delete_success');
            } else {
                // Model should throw exception on failure, but handle false return just in case
                $json['error'] = $this->language->get('error_deleting'); // Add this lang string
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    // Helper to send JSON response
    protected function sendJSON($data) {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    /**
     * AJAX: جلب البنود القابلة للإرجاع بناءً على سند استلام أو أمر شراء
     */
    public function ajaxGetReturnableItems() {
        $json = array('items' => []);
        $po_id = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0;
        $goods_receipt_id = isset($this->request->get['goods_receipt_id']) ? (int)$this->request->get['goods_receipt_id'] : 0;

        if (($po_id || $goods_receipt_id) && $this->user->hasPermission('access', 'purchase/return')) {
             $this->load->model('purchase/return');
             $this->load->model('localisation/currency'); // For formatting

             $filter = [];
             if ($po_id) $filter['po_id'] = $po_id;
             if ($goods_receipt_id) $filter['goods_receipt_id'] = $goods_receipt_id;

             $items = $this->model_purchase_return->getReceivableProducts($filter); // Assuming this model function exists

             foreach($items as $item) {
                 // Format data for the view
                 $json['items'][] = array(
                     'product_id' => $item['product_id'],
                     'product_name' => $item['product_name'], // Assuming joined
                     'unit_id' => $item['unit_id'],
                     'unit_name' => $item['unit_name'], // Assuming joined
                     'receivable_quantity' => $item['receivable_quantity'] ?? 0, // Quantity available to return
                     'unit_price' => $item['unit_price'] ?? 0, // Price from GRN or PO
                     'unit_price_formatted' => $this->currency->format($item['unit_price'] ?? 0, $this->config->get('config_currency')), // Adjust currency later
                     'grn_item_id' => $item['grn_item_id'] ?? 0 // If filtering by GRN
                 );
             }

        } else {
            $json['error'] = $this->language->get('error_permission'); // Or missing filter error
        }

        $this->sendJSON($json);
    }


    /**
     * AJAX: Approve a purchase return
     */
    public function approve() {
        $this->load->language('purchase/return');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/return')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $return_id = isset($this->request->post['return_id']) ? (int)$this->request->post['return_id'] : 0;

        if (!$return_id) {
            $json['error'] = $this->language->get('error_return_required');
            return $this->sendJSON($json);
        }

        try {
            $result = $this->model_purchase_return->approveReturn($return_id, $this->user->getId());
            if ($result) {
                $json['success'] = $this->language->get('text_approve_success');
            } else {
                $json['error'] = $this->language->get('error_approving');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX: Reject a purchase return
     */
    public function reject() {
        $this->load->language('purchase/return');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/return')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $return_id = isset($this->request->post['return_id']) ? (int)$this->request->post['return_id'] : 0;
        $reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : '';

        if (!$return_id) {
            $json['error'] = $this->language->get('error_return_required');
            return $this->sendJSON($json);
        }

        try {
            $result = $this->model_purchase_return->rejectReturn($return_id, $reason, $this->user->getId());
            if ($result) {
                $json['success'] = $this->language->get('text_reject_success');
            } else {
                $json['error'] = $this->language->get('error_rejecting');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX: Create credit note for a purchase return
     */
    public function createCreditNote() {
        $this->load->language('purchase/return');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/return')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $return_id = isset($this->request->post['return_id']) ? (int)$this->request->post['return_id'] : 0;

        if (!$return_id) {
            $json['error'] = $this->language->get('error_return_required');
            return $this->sendJSON($json);
        }

        try {
            $result = $this->model_purchase_return->createCreditNote($return_id, $this->user->getId());
            if ($result) {
                $json['success'] = $this->language->get('text_credit_note_success');
                $json['credit_note_id'] = $result['credit_note_id'];
            } else {
                $json['error'] = $this->language->get('error_creating_credit_note');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * View purchase return details
     */
    public function view() {
        $this->load->language('purchase/return');
        $this->load->model('purchase/return');

        $return_id = isset($this->request->get['return_id']) ? (int)$this->request->get['return_id'] : 0;

        if (!$return_id) {
            return $this->response->setOutput($this->load->view('error/not_found'));
        }

        $return_info = $this->model_purchase_return->getReturn($return_id);
        if (!$return_info) {
            return $this->response->setOutput($this->load->view('error/not_found'));
        }

        $data = array();
        $data['return_info'] = $return_info;
        $data['return_items'] = $this->model_purchase_return->getReturnItems($return_id);
        $data['return_history'] = $this->model_purchase_return->getReturnHistory($return_id);

        // Language strings
        $data['text_return_details'] = $this->language->get('text_return_details');
        $data['text_return_items'] = $this->language->get('text_return_items');
        $data['text_history'] = $this->language->get('text_history');
        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_total'] = $this->language->get('column_total');
        $data['button_close'] = $this->language->get('button_close');

        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('purchase/return_view', $data));
    }

    /**
     * Print purchase return
     */
    public function print() {
        $this->load->language('purchase/return');
        $this->load->model('purchase/return');

        $return_id = isset($this->request->get['return_id']) ? (int)$this->request->get['return_id'] : 0;

        if (!$return_id) {
            return $this->response->setOutput($this->load->view('error/not_found'));
        }

        $return_info = $this->model_purchase_return->getReturn($return_id);
        if (!$return_info) {
            return $this->response->setOutput($this->load->view('error/not_found'));
        }

        $data = array();
        $data['return_info'] = $return_info;
        $data['return_items'] = $this->model_purchase_return->getReturnItems($return_id);

        // Language strings for print
        $data['text_return_number'] = $this->language->get('text_return_number');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_date_added'] = $this->language->get('text_date_added');
        $data['text_status'] = $this->language->get('text_status');
        $data['text_reason'] = $this->language->get('text_reason');
        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_total'] = $this->language->get('column_total');

        $this->response->setOutput($this->load->view('purchase/return_print', $data));
    }
}
