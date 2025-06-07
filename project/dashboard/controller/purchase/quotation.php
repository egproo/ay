<?php
// Add use statement for mPDF
use Mpdf\Mpdf;

class ControllerPurchaseQuotation extends Controller {
    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('purchase/quotation');
        $this->load->model('purchase/requisition'); // Needed for supplier list in index filter
        $this->load->language('purchase/quotation');
    }

    public function index() {
        // Verify view permission
        if (!$this->user->hasPermission('access', 'purchase/quotation')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        // Process filter data (used for stats initially)
        $filter_data = [
            'filter_quotation_number' => $this->request->get['filter_quotation_number'] ?? '',
            'filter_requisition_id'   => $this->request->get['filter_requisition_id'] ?? '',
            'filter_supplier_id'      => isset($this->request->get['filter_supplier_id']) ? (int)$this->request->get['filter_supplier_id'] : 0,
            'filter_status'           => $this->request->get['filter_status'] ?? '',
            'filter_validity'         => $this->request->get['filter_validity'] ?? 'all',
            'filter_date_start'       => $this->request->get['filter_date_start'] ?? '',
            'filter_date_end'         => $this->request->get['filter_date_end'] ?? ''
            // No pagination needed for initial stats load
        ];

        $data = [];

        // Get quotation statistics for dashboard widgets
        $data['stats'] = $this->model_purchase_quotation->getQuotationStats($filter_data);

        // Data for filters
        $data['suppliers'] = $this->model_purchase_requisition->getSuppliers(); // Use requisition model helper
        $data['status_options'] = [
            ['value' => 'draft',     'text' => $this->language->get('text_status_draft')],
            ['value' => 'pending',   'text' => $this->language->get('text_status_pending')],
            ['value' => 'approved',  'text' => $this->language->get('text_status_approved')],
            ['value' => 'rejected',  'text' => $this->language->get('text_status_rejected')],
            ['value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled')],
            ['value' => 'converted', 'text' => $this->language->get('text_status_converted')]
        ];

        // User permissions for the view
        $data['can_add'] = $this->user->hasPermission('modify', 'purchase/quotation');
        $data['can_edit'] = $this->user->hasPermission('modify', 'purchase/quotation');
        $data['can_approve'] = $this->user->hasPermission('modify', 'purchase/quotation');
        $data['can_reject'] = $this->user->hasPermission('modify', 'purchase/quotation');
        $data['can_delete'] = $this->user->hasPermission('modify', 'purchase/quotation');
        $data['can_export'] = $this->user->hasPermission('access', 'purchase/quotation');
        $data['can_compare'] = $this->user->hasPermission('access', 'purchase/quotation');

        // Language data
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_filter'] = $this->language->get('text_filter');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm_approve'] = $this->language->get('text_confirm_approve');
        $data['text_confirm_reject'] = $this->language->get('text_confirm_reject');
        $data['text_confirm_delete'] = $this->language->get('text_confirm_delete');
        $data['text_bulk_action'] = $this->language->get('text_bulk_action');
        $data['text_approve_selected'] = $this->language->get('text_approve_selected');
        $data['text_reject_selected'] = $this->language->get('text_reject_selected');
        $data['text_delete_selected'] = $this->language->get('text_delete_selected');
        $data['text_enter_rejection_reason'] = $this->language->get('text_enter_rejection_reason');
        $data['text_export_excel'] = $this->language->get('text_export_excel');
        $data['text_export_pdf'] = $this->language->get('text_export_pdf');
        $data['text_select'] = $this->language->get('text_select');
        $data['text_all_suppliers'] = $this->language->get('text_all_suppliers');
        $data['text_all_statuses'] = $this->language->get('text_all_statuses');
        $data['text_validity'] = $this->language->get('text_validity');
        $data['text_valid'] = $this->language->get('text_valid');
        $data['text_expired'] = $this->language->get('text_expired');
        $data['text_all'] = $this->language->get('text_all');
        $data['text_date_start'] = $this->language->get('text_date_start');
        $data['text_date_end'] = $this->language->get('text_date_end');
        $data['text_requisition'] = $this->language->get('text_requisition');
        $data['text_total_quotations'] = $this->language->get('text_total_quotations');
        $data['text_pending_quotations'] = $this->language->get('text_pending_quotations');
        $data['text_approved_quotations'] = $this->language->get('text_approved_quotations');
        $data['text_rejected_quotations'] = $this->language->get('text_rejected_quotations');
        $data['text_refresh'] = $this->language->get('text_refresh');

        $data['column_quotation_number'] = $this->language->get('column_quotation_number');
        $data['column_requisition_number'] = $this->language->get('column_requisition_number');
        $data['column_supplier'] = $this->language->get('column_supplier');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_filter'] = $this->language->get('button_filter');
        $data['button_clear'] = $this->language->get('button_clear');
        $data['button_add_quotation'] = $this->language->get('button_add_quotation');
        $data['button_apply'] = $this->language->get('button_apply');
        $data['button_export'] = $this->language->get('button_export');

        // Handle notifications
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']);
        } else {
            $data['error_warning'] = '';
        }

        // Breadcrumbs
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true)
        ];

        // Common data
        $data['user_token'] = $this->session->data['user_token'];

        // Load template parts
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/quotation_list', $data));
    }

    /**
     * AJAX method to fetch quotations list
     */
    public function ajaxList() {
        if (!$this->user->hasPermission('access', 'purchase/quotation')) {
            $this->sendJSON(['error' => $this->language->get('error_permission')]);
            return;
        }

        // Initialize filters
        $filter_data = [
            'filter_quotation_number'   => $this->request->get['filter_quotation_number'] ?? '',
            'filter_requisition_id'     => $this->request->get['filter_requisition_id'] ?? '',
            'filter_supplier_id'        => isset($this->request->get['filter_supplier_id']) ? (int)$this->request->get['filter_supplier_id'] : 0,
            'filter_status'             => $this->request->get['filter_status'] ?? '',
            'filter_validity'           => $this->request->get['filter_validity'] ?? 'all',
            'filter_date_start'         => $this->request->get['filter_date_start'] ?? '',
            'filter_date_end'           => $this->request->get['filter_date_end'] ?? '',
            'sort'                      => $this->request->get['sort'] ?? 'q.created_at',
            'order'                     => $this->request->get['order'] ?? 'DESC',
            'page'                      => isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1,
            'limit'                     => isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20
        ];

        $json = [];

        // Get statistics
        $json['stats'] = $this->model_purchase_quotation->getQuotationStats($filter_data);

        // Get quotations list
        $quotations = $this->model_purchase_quotation->getQuotations($filter_data);
        $total = $this->model_purchase_quotation->getTotalQuotations($filter_data);

        $json['quotations'] = [];
        foreach ($quotations as $quotation) {
            $currency_code = $quotation['currency_code'] ?? $this->config->get('config_currency');

            $json['quotations'][] = [
                'quotation_id'          => $quotation['quotation_id'],
                'quotation_number'      => $quotation['quotation_number'],
                'requisition_id'        => $quotation['requisition_id'],
                'requisition_number'    => $quotation['requisition_number'],
                'supplier_name'         => $quotation['supplier_name'],
                'total_formatted'       => $this->currency->format($quotation['total_amount'], $currency_code),
                'status'                => $quotation['status'],
                'status_text'           => $this->model_purchase_quotation->getStatusText($quotation['status']),
                'status_class'          => $this->model_purchase_quotation->getStatusClass($quotation['status']),
                'validity_date'         => date($this->language->get('date_format_short'), strtotime($quotation['validity_date'])),
                'created_at'            => date($this->language->get('datetime_format'), strtotime($quotation['created_at'])),

                // Permissions
                'can_view'              => $this->user->hasKey('purchase_quotation_view'),
                'can_edit'              => $this->user->hasKey('purchase_quotation_edit') && in_array($quotation['status'], ['draft', 'pending', 'rejected']), // Allow edit if rejected
                'can_delete'            => $this->user->hasKey('purchase_quotation_delete') && in_array($quotation['status'], ['draft', 'pending', 'rejected']),
                'can_approve'           => $this->user->hasKey('purchase_quotation_approve') && $quotation['status'] == 'pending',
                'can_reject'            => $this->user->hasKey('purchase_quotation_reject') && $quotation['status'] == 'pending',
                'can_convert'           => $this->user->hasKey('purchase_order_add') && $quotation['status'] == 'approved', // Check PO add permission
                'can_print'             => $this->user->hasKey('purchase_quotation_print'),
                'can_compare'           => $this->user->hasKey('purchase_quotation_compare'),
                'has_documents'         => (bool)($quotation['document_count'] ?? 0)
            ];
        }

        // AJAX pagination data
        $json['page'] = $filter_data['page'];
        $json['limit'] = $filter_data['limit'];
        $json['total'] = $total;

        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = 'javascript:void(0);'; // URL handled by JS

        $json['pagination'] = $pagination->render();
        $json['results'] = sprintf(
            $this->language->get('text_pagination'),
            ($total) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0,
            ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total - $filter_data['limit'])) ? $total : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']),
            $total,
            ceil($total / $filter_data['limit'])
        );

        $this->sendJSON($json);
    }

    /**
     * Display form for adding/editing a quotation
     */
    public function form() {
        if (!$this->user->hasPermission('modify', 'purchase/quotation')) {
            return $this->load->view('error/permission', []);
        }
        $this->load->model('purchase/quotation');
        $this->load->model('purchase/requisition');
        $this->load->model('localisation/currency');

        $data = [];
        $data['error'] = '';

        $quotation_id = isset($this->request->get['quotation_id']) ? (int)$this->request->get['quotation_id'] : 0;
        $requisition_id_from_url = isset($this->request->get['requisition_id']) ? (int)$this->request->get['requisition_id'] : 0;

        // Get mode - add or edit
        $data['mode'] = $quotation_id ? 'edit' : 'add';
        $quotation_info = null;

        if ($data['mode'] == 'edit') {
            $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);

            if (!$quotation_info) {
                $data['error'] = $this->language->get('error_quotation_not_found');
                return $this->load->view('error/not_found', $data);
            }

            // Check edit permission based on status
            if (!in_array($quotation_info['status'], ['draft', 'pending', 'rejected'])) { // Allow editing rejected
                $data['error'] = $this->language->get('error_edit_status');
                return $this->load->view('error/permission', $data);
            }
        }

        // Get form data - either from existing record or defaults
        $form_data = [
            'quotation_id' => $quotation_id,
            'quotation_number' => $quotation_info['quotation_number'] ?? '',
            'requisition_id' => $quotation_info['requisition_id'] ?? $requisition_id_from_url,
            'supplier_id' => $quotation_info['supplier_id'] ?? 0,
            'currency_id' => $quotation_info['currency_id'] ?? $this->config->get('config_currency_id'),
            'exchange_rate' => $quotation_info['exchange_rate'] ?? 1,
            'validity_date' => isset($quotation_info['validity_date']) ? date('Y-m-d', strtotime($quotation_info['validity_date'])) : date('Y-m-d', strtotime('+30 days')),
            'payment_terms' => $quotation_info['payment_terms'] ?? '',
            'delivery_terms' => $quotation_info['delivery_terms'] ?? '',
            'notes' => $quotation_info['notes'] ?? '',
            'tax_included' => $quotation_info['tax_included'] ?? 1,
            'tax_rate' => $quotation_info['tax_rate'] ?? $this->config->get('config_tax') ?? 0,
            'subtotal' => $quotation_info['subtotal'] ?? 0,
            'discount_type' => $quotation_info['discount_type'] ?? 'fixed',
            'has_discount' => $quotation_info['has_discount'] ?? 0,
            'discount_value' => $quotation_info['discount_value'] ?? 0,
            'discount_amount' => $quotation_info['discount_amount'] ?? 0,
            'tax_amount' => $quotation_info['tax_amount'] ?? 0,
            'total_amount' => $quotation_info['total_amount'] ?? 0,
            'status' => $quotation_info['status'] ?? 'draft'
        ];

        $data['form_data'] = $form_data;

        // Get requisition details if available
        $data['requisition_info'] = [];
        if ($form_data['requisition_id']) {
            $requisition_info = $this->model_purchase_requisition->getRequisition($form_data['requisition_id']);
            if ($requisition_info) {
                $data['requisition_info'] = [
                    'requisition_id' => $requisition_info['requisition_id'],
                    'req_number' => $requisition_info['req_number'],
                    'branch_name' => $requisition_info['branch_name'],
                    'user_group_name' => $requisition_info['user_group_name'],
                    'required_date' => date($this->language->get('date_format_short'), strtotime($requisition_info['required_date'])),
                    'priority' => $requisition_info['priority'],
                    'priority_text' => $this->language->get('text_priority_' . $requisition_info['priority']),
                    'status' => $requisition_info['status'],
                    'status_text' => $this->model_purchase_quotation->getStatusText($requisition_info['status']) // Use quotation model's status text
                ];
            }
        }

        // Get quotation items
        $data['items'] = [];
        if ($quotation_id) {
            $items = $this->model_purchase_quotation->getQuotationItems($quotation_id);
            foreach ($items as $item) {
                $data['items'][] = $item;
            }
        } else if ($form_data['requisition_id']) {
            // If adding based on requisition, get requisition items
            $req_items = $this->model_purchase_requisition->getRequisitionItems($form_data['requisition_id']);
            foreach ($req_items as $item) {
                $data['items'][] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'requisition_item_id' => $item['requisition_item_id'],
                    'unit_id' => $item['unit_id'],
                    'unit_name' => $item['unit_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => 0, // Default price to 0
                    'tax_rate' => $form_data['tax_rate'],
                    'discount_rate' => 0,
                    'discount_type' => 'fixed',
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => 0,
                    'description' => $item['description'] ?? ''
                ];
            }
        }

        // Get suppliers list
        $data['suppliers'] = $this->model_purchase_requisition->getSuppliers();

        // Get currencies list
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        // Dropdown options
        $data['discount_types'] = [
            ['value' => 'fixed', 'text' => $this->language->get('text_fixed')],
            ['value' => 'percentage', 'text' => $this->language->get('text_percentage')]
        ];

        $data['tax_options'] = [
            ['value' => '1', 'text' => $this->language->get('text_tax_included')],
            ['value' => '0', 'text' => $this->language->get('text_tax_excluded')]
        ];

        // Permission flags
        $data['can_edit_price'] = $this->user->hasKey('purchase_quotation_edit_price');
        $data['can_apply_discount'] = $this->user->hasKey('purchase_quotation_discount');
        $data['can_change_tax'] = $this->user->hasKey('purchase_quotation_tax');

        // Language strings
        $data['text_edit_quotation'] = $this->language->get('text_edit_quotation');
        $data['text_add_quotation'] = $this->language->get('text_add_quotation');
        $data['text_quotation_details'] = $this->language->get('text_quotation_details');
        $data['text_quotation_items'] = $this->language->get('text_quotation_items');
        $data['text_totals'] = $this->language->get('text_totals');
        $data['text_documents'] = $this->language->get('text_documents');
        $data['text_select_requisition'] = $this->language->get('text_select_requisition');
        $data['text_select_supplier'] = $this->language->get('text_select_supplier');
        $data['text_select_currency'] = $this->language->get('text_select_currency');
        $data['text_upload_documents'] = $this->language->get('text_upload_documents');
        $data['text_add_item'] = $this->language->get('text_add_item');
        $data['text_calculate_totals'] = $this->language->get('text_calculate_totals');
        $data['text_save_as_draft'] = $this->language->get('text_save_as_draft');
        $data['text_submit'] = $this->language->get('text_submit');
        $data['text_cancel'] = $this->language->get('text_cancel');
        $data['text_select_product'] = $this->language->get('text_select_product');
        $data['text_no_items'] = $this->language->get('text_no_items');

        $data['entry_requisition'] = $this->language->get('entry_requisition');
        $data['entry_supplier'] = $this->language->get('entry_supplier');
        $data['entry_currency'] = $this->language->get('entry_currency');
        $data['entry_exchange_rate'] = $this->language->get('entry_exchange_rate');
        $data['entry_validity_date'] = $this->language->get('entry_validity_date');
        $data['entry_payment_terms'] = $this->language->get('entry_payment_terms');
        $data['entry_delivery_terms'] = $this->language->get('entry_delivery_terms');
        $data['entry_notes'] = $this->language->get('entry_notes');
        $data['entry_tax_included'] = $this->language->get('entry_tax_included');
        $data['entry_tax_rate'] = $this->language->get('entry_tax_rate');
        $data['entry_has_discount'] = $this->language->get('entry_has_discount');
        $data['entry_discount_type'] = $this->language->get('entry_discount_type');
        $data['entry_discount_value'] = $this->language->get('entry_discount_value');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_discount'] = $this->language->get('column_discount');
        $data['column_tax'] = $this->language->get('column_tax');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_save_submit'] = $this->language->get('button_save_submit');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['tab_general'] = $this->language->get('tab_general');
        $data['tab_items'] = $this->language->get('tab_items');
        $data['tab_documents'] = $this->language->get('tab_documents');
        $data['tab_totals'] = $this->language->get('tab_totals');

        $data['user_token'] = $this->session->data['user_token'];

        return $this->response->setOutput($this->load->view('purchase/quotation_addedit_form', $data));
    }

    public function ajaxSave() {
        $this->load->language('purchase/quotation');

        $json = [];

        // Check permissions
        if (!$this->user->hasPermission('modify', 'purchase/quotation')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $quotation_id = isset($this->request->post['quotation_id']) ? (int)$this->request->post['quotation_id'] : 0;
        $mode = $quotation_id ? 'edit' : 'add';

        // Validate if editing an existing quotation
        if ($mode == 'edit') {
            $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);

            if (!$quotation_info) {
                $json['error'] = $this->language->get('error_quotation_not_found');
                $this->sendJSON($json);
                return;
            }

            // Check if status allows editing
            if (!in_array($quotation_info['status'], ['draft', 'pending', 'rejected'])) { // Allow editing rejected
                $json['error'] = $this->language->get('error_edit_status');
                $this->sendJSON($json);
                return;
            }
        }

        // Basic validation
        if (empty($this->request->post['requisition_id'])) {
            $json['error'] = $this->language->get('error_requisition_required');
            $this->sendJSON($json);
            return;
        }

        if (empty($this->request->post['supplier_id'])) {
            $json['error'] = $this->language->get('error_supplier_required');
            $this->sendJSON($json);
            return;
        }

        if (empty($this->request->post['validity_date'])) {
            $json['error'] = $this->language->get('error_validity_date_required');
            $this->sendJSON($json);
            return;
        }

        // Validate items - check if the items array exists and is structured correctly
        if (!isset($this->request->post['item']) ||
            !isset($this->request->post['item']['product_id']) ||
            !is_array($this->request->post['item']['product_id'])) {
            $json['error'] = $this->language->get('error_items_required');
            $this->sendJSON($json);
            return;
        }

        // Check for at least one valid item
        $valid_items = false;
        $items_count = count($this->request->post['item']['product_id']);

        for ($i = 0; $i < $items_count; $i++) {
            if (!empty($this->request->post['item']['product_id'][$i]) &&
                isset($this->request->post['item']['quantity'][$i]) &&
                (float)$this->request->post['item']['quantity'][$i] > 0) {
                $valid_items = true;
                break;
            }
        }

        if (!$valid_items) {
            $json['error'] = $this->language->get('error_valid_items_required');
            $this->sendJSON($json);
            return;
        }

        // Prepare data
        $data = [
            'quotation_id' => $quotation_id,
            'requisition_id' => (int)$this->request->post['requisition_id'],
            'supplier_id' => (int)$this->request->post['supplier_id'],
            'currency_id' => (int)$this->request->post['currency_id'],
            'exchange_rate' => (float)$this->request->post['exchange_rate'],
            'validity_date' => $this->request->post['validity_date'],
            'payment_terms' => $this->request->post['payment_terms'] ?? '',
            'delivery_terms' => $this->request->post['delivery_terms'] ?? '',
            'notes' => $this->request->post['notes'] ?? '',
            'tax_included' => isset($this->request->post['tax_included']) ? (int)$this->request->post['tax_included'] : 0,
            'tax_rate' => (float)$this->request->post['tax_rate'],
            'subtotal' => (float)$this->request->post['subtotal'],
            'discount_type' => $this->request->post['discount_type'] ?? 'fixed',
            'has_discount' => isset($this->request->post['has_discount']) ? (int)$this->request->post['has_discount'] : 0,
            'discount_value' => (float)$this->request->post['discount_value'],
            'discount_amount' => (float)$this->request->post['discount_amount'],
            'tax_amount' => (float)$this->request->post['tax_amount'],
            'total_amount' => (float)$this->request->post['total_amount'],
            'status' => isset($this->request->post['submit_type']) && $this->request->post['submit_type'] == 'submit' ? 'pending' : 'draft',
            'items' => [],
            'user_id' => $this->user->getId()
        ];

        // Process items
        for ($i = 0; $i < $items_count; $i++) {
            // Skip empty items
            if (empty($this->request->post['item']['product_id'][$i])) {
                continue;
            }

            $data['items'][] = [
                'quotation_item_id' => isset($this->request->post['item']['quotation_item_id'][$i]) ? (int)$this->request->post['item']['quotation_item_id'][$i] : 0,
                'requisition_item_id' => isset($this->request->post['item']['requisition_item_id'][$i]) ? (int)$this->request->post['item']['requisition_item_id'][$i] : 0,
                'product_id' => (int)$this->request->post['item']['product_id'][$i],
                'unit_id' => (int)$this->request->post['item']['unit_id'][$i],
                'quantity' => (float)$this->request->post['item']['quantity'][$i],
                'unit_price' => (float)$this->request->post['item']['unit_price'][$i],
                'tax_rate' => (float)($this->request->post['item']['tax_rate'][$i] ?? 0),
                'discount_type' => $this->request->post['item']['discount_type'][$i] ?? 'fixed',
                'discount_rate' => (float)($this->request->post['item']['discount_value'][$i] ?? 0), // Corrected key
                'discount_amount' => (float)($this->request->post['item']['discount_amount'][$i] ?? 0),
                'tax_amount' => (float)($this->request->post['item']['tax_amount'][$i] ?? 0),
                'line_total' => (float)($this->request->post['item']['line_total'][$i] ?? 0),
                'description' => $this->request->post['item']['description'][$i] ?? ''
            ];
        }

        // Save quotation
        try {
            if ($mode == 'add') {
                $result = $this->model_purchase_quotation->addQuotation($data);
                $json['success'] = $this->language->get('text_success_add');
            } else {
                $result = $this->model_purchase_quotation->editQuotation($data);
                $json['success'] = $this->language->get('text_success_edit');
            }

            if ($result) {
                $json['quotation_id'] = $result;
                $json['redirect'] = $this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true);
            } else {
                $json['error'] = $this->language->get('error_saving');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * View Quotation Details
     */
    public function view() {
        if (!$this->user->hasKey('purchase_quotation_view')) {
            return $this->load->view('error/permission', []);
        }

        $quotation_id = isset($this->request->get['quotation_id']) ? (int)$this->request->get['quotation_id'] : 0;

        if (!$quotation_id) {
            return $this->load->view('error/not_found', []);
        }

        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);

        if (!$quotation_info) {
            return $this->load->view('error/not_found', []);
        }

        $this->load->language('purchase/quotation');
        $this->load->model('localisation/currency');

        $data = array();
        $data['heading_title'] = $this->language->get('text_quotation_details') . ' #' . $quotation_info['quotation_number'];
        $data['text_quotation_details'] = $this->language->get('text_quotation_details');

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true)
        );
         $data['breadcrumbs'][] = array(
            'text' => $quotation_info['quotation_number'],
            'href' => $this->url->link('purchase/quotation/view', 'user_token=' . $this->session->data['user_token'] . '&quotation_id=' . $quotation_id, true)
        );

        // Format Quotation Data
        $currency_code = $quotation_info['currency_code'] ?? $this->config->get('config_currency');
        $data['quotation'] = [
            'quotation_id'       => $quotation_info['quotation_id'],
            'quotation_number'   => $quotation_info['quotation_number'],
            'requisition_id'     => $quotation_info['requisition_id'],
            'requisition_number' => $quotation_info['requisition_number'] ?? '',
            'supplier_name'      => $quotation_info['supplier_name'],
            'currency_code'      => $currency_code,
            'subtotal'           => $this->currency->format($quotation_info['subtotal'], $currency_code, $quotation_info['exchange_rate']),
            'discount_amount'    => $this->currency->format($quotation_info['discount_amount'], $currency_code, $quotation_info['exchange_rate']),
            'tax_amount'         => $this->currency->format($quotation_info['tax_amount'], $currency_code, $quotation_info['exchange_rate']),
            'total_amount'       => $this->currency->format($quotation_info['total_amount'], $currency_code, $quotation_info['exchange_rate']),
            'status'             => $quotation_info['status'],
            'status_text'        => $this->model_purchase_quotation->getStatusText($quotation_info['status']),
            'status_class'       => $this->model_purchase_quotation->getStatusClass($quotation_info['status']),
            'validity_date'      => date($this->language->get('date_format_short'), strtotime($quotation_info['validity_date'])),
            'payment_terms'      => nl2br($quotation_info['payment_terms']),
            'delivery_terms'     => nl2br($quotation_info['delivery_terms']),
            'notes'              => nl2br($quotation_info['notes']),
            'created_at'         => date($this->language->get('datetime_format'), strtotime($quotation_info['created_at'])),
            'created_by_name'    => $this->model_purchase_quotation->getUserName($quotation_info['created_by']),
            'rejection_reason'   => $quotation_info['rejection_reason'] ?? ''
        ];

        // Get Quotation Items
        $data['items'] = $this->model_purchase_quotation->getQuotationItems($quotation_id);
        foreach ($data['items'] as &$item) {
            $item['unit_price_formatted'] = $this->currency->format($item['unit_price'], $currency_code, $quotation_info['exchange_rate']);
            $item['discount_amount_formatted'] = $this->currency->format($item['discount_amount'], $currency_code, $quotation_info['exchange_rate']);
            $item['tax_amount_formatted'] = $this->currency->format($item['tax_amount'], $currency_code, $quotation_info['exchange_rate']);
            $item['line_total_formatted'] = $this->currency->format($item['line_total'], $currency_code, $quotation_info['exchange_rate']);
        }
        unset($item);

        // Get History
        $data['history'] = $this->model_purchase_quotation->getQuotationHistory($quotation_id);

        // Get Documents
        $doc_data = $this->model_purchase_quotation->getDocumentsWithPermissions($quotation_id);
        $data['documents'] = $doc_data['documents'];
        $data['can_upload_docs'] = $doc_data['can_upload'];
        $data['can_delete_docs'] = $doc_data['can_delete'];
        $data['can_download_docs'] = $this->user->hasKey('purchase_quotation_view'); // Assuming view allows download

        // Permissions for actions
        $data['can_edit'] = $this->user->hasKey('purchase_quotation_edit') && in_array($quotation_info['status'], ['draft', 'pending', 'rejected']);
        $data['can_delete'] = $this->user->hasKey('purchase_quotation_delete') && in_array($quotation_info['status'], ['draft', 'pending', 'rejected']);
        $data['can_approve'] = $this->user->hasKey('purchase_quotation_approve') && $quotation_info['status'] == 'pending';
        $data['can_reject'] = $this->user->hasKey('purchase_quotation_reject') && $quotation_info['status'] == 'pending';
        $data['can_convert'] = $this->user->hasKey('purchase_order_add') && $quotation_info['status'] == 'approved'; // Check PO add permission
        $data['can_print'] = $this->user->hasKey('purchase_quotation_print'); // Add permission key if needed

        // Language Strings
        $data['text_quotation_info'] = $this->language->get('text_quotation_info');
        $data['text_items'] = $this->language->get('text_items');
        $data['text_history'] = $this->language->get('text_history');
        $data['text_documents'] = $this->language->get('text_documents');
        $data['text_requisition'] = $this->language->get('text_requisition');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_currency'] = $this->language->get('text_currency');
        $data['text_validity_date'] = $this->language->get('text_validity_date');
        $data['text_payment_terms'] = $this->language->get('text_payment_terms');
        $data['text_delivery_terms'] = $this->language->get('text_delivery_terms');
        $data['text_notes'] = $this->language->get('text_notes');
        $data['text_status'] = $this->language->get('text_status');
        $data['text_rejection_reason'] = $this->language->get('text_rejection_reason');
        $data['text_created_by'] = $this->language->get('text_created_by');
        $data['text_date_added'] = $this->language->get('text_date_added');
        $data['text_subtotal'] = $this->language->get('text_subtotal');
        $data['text_discount'] = $this->language->get('text_discount');
        $data['text_tax'] = $this->language->get('text_tax');
        $data['text_total'] = $this->language->get('text_total');
        $data['text_no_items'] = $this->language->get('text_no_items');
        $data['text_no_history'] = $this->language->get('text_no_history');
        $data['text_no_documents'] = $this->language->get('text_no_documents');
        $data['text_confirm_approve'] = $this->language->get('text_confirm_approve');
        $data['text_confirm_reject'] = $this->language->get('text_confirm_reject');
        $data['text_confirm_delete'] = $this->language->get('text_confirm_delete');
        $data['text_confirm_convert'] = $this->language->get('text_confirm_convert');
        $data['text_enter_rejection_reason'] = $this->language->get('text_enter_rejection_reason');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_discount'] = $this->language->get('column_discount');
        $data['column_tax'] = $this->language->get('column_tax');
        $data['column_total'] = $this->language->get('column_total');
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
        $data['button_approve'] = $this->language->get('button_approve');
        $data['button_reject'] = $this->language->get('button_reject');
        $data['button_convert_po'] = $this->language->get('button_convert_po');
        $data['button_print'] = $this->language->get('button_print');
        $data['button_upload'] = $this->language->get('button_upload');
        $data['button_download'] = $this->language->get('button_download');
        $data['button_back'] = $this->language->get('button_back');

        $data['user_token'] = $this->session->data['user_token'];

        // Load common parts
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/quotation_view', $data)); // Needs view template
    }

    /**
     * Compare quotations for the same requisition
     */
    public function compare() {
        if (!$this->user->hasKey('purchase_quotation_compare')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $requisition_id = isset($this->request->get['requisition_id']) ? (int)$this->request->get['requisition_id'] : 0;

        if (!$requisition_id) {
            $this->session->data['error_warning'] = $this->language->get('error_requisition_required');
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->model_purchase_quotation->getComparisonData($requisition_id);

        if (!$data || empty($data['quotations'])) {
            $this->session->data['error_warning'] = $this->language->get('error_no_quotations');
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }
        $data['user_token'] = $this->session->data['user_token'];
        $data['can_convert'] = $this->user->hasKey('purchase_quotation_convert');

        // Language strings
        $data['text_comparison_title'] = $this->language->get('text_comparison_title');
        $data['text_requisition_details'] = $this->language->get('text_requisition_details');
        $data['text_requisition_number'] = $this->language->get('text_requisition_number');
        $data['text_branch'] = $this->language->get('text_branch');
        $data['text_department'] = $this->language->get('text_department');
        $data['text_date_required'] = $this->language->get('text_date_required');
        $data['text_priority'] = $this->language->get('text_priority');
        $data['text_quotation_comparison'] = $this->language->get('text_quotation_comparison');
        $data['text_best_price'] = $this->language->get('text_best_price');
        $data['text_lowest_total'] = $this->language->get('text_lowest_total');
        $data['text_convert_explanation'] = $this->language->get('text_convert_explanation');
        $data['text_export_comparison'] = $this->language->get('text_export_comparison');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');

        $data['button_close'] = $this->language->get('button_close');
        $data['button_convert'] = $this->language->get('button_convert');
        $data['button_export_pdf'] = $this->language->get('button_export_pdf');
        $data['button_export_excel'] = $this->language->get('button_export_excel');

        return $this->response->setOutput($this->load->view('purchase/quotation_comparison', $data));

    }

    /**
     * Process form submission for approve/reject/convert
     */
    public function approve() {
        $this->load->language('purchase/quotation');

        $json = [];

        if (!$this->user->hasKey('purchase_quotation_approve')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $quotation_id = isset($this->request->post['quotation_id']) ? (int)$this->request->post['quotation_id'] : 0;

        if (!$quotation_id) {
            $json['error'] = $this->language->get('error_quotation_required');
            $this->sendJSON($json);
            return;
        }

        try {
            $result = $this->model_purchase_quotation->approveQuotation($quotation_id, $this->user->getId());

            if (!$result) {
                $json['error'] = $this->language->get('error_approving');
            } else {
                $json['success'] = $this->language->get('text_approve_success');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    public function reject() {
        $this->load->language('purchase/quotation');

        $json = [];

        if (!$this->user->hasKey('purchase_quotation_reject')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $quotation_id = isset($this->request->post['quotation_id']) ? (int)$this->request->post['quotation_id'] : 0;
        $reason = isset($this->request->post['reason']) ? trim($this->request->post['reason']) : '';

        if (!$quotation_id) {
            $json['error'] = $this->language->get('error_quotation_required');
            $this->sendJSON($json);
            return;
        }

        if (empty($reason)) {
            $json['error'] = $this->language->get('error_rejection_reason_required');
            $this->sendJSON($json);
            return;
        }

        try {
            $result = $this->model_purchase_quotation->rejectQuotation($quotation_id, $reason, $this->user->getId());

            if (!$result) {
                $json['error'] = $this->language->get('error_rejecting');
            } else {
                $json['success'] = $this->language->get('text_reject_success');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    public function delete() {
        $this->load->language('purchase/quotation');

        $json = [];

        if (!$this->user->hasKey('purchase_quotation_delete')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $quotation_id = isset($this->request->post['quotation_id']) ? (int)$this->request->post['quotation_id'] : 0;

        if (!$quotation_id) {
            $json['error'] = $this->language->get('error_quotation_required');
            $this->sendJSON($json);
            return;
        }

        try {
            $result = $this->model_purchase_quotation->deleteQuotation($quotation_id);

            if (!$result) {
                $json['error'] = $this->language->get('error_deleting');
            } else {
                $json['success'] = $this->language->get('text_delete_success');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * Convert quotation to purchase order
     */
    public function convert() {
        $this->load->language('purchase/quotation');

        $json = [];

        if (!$this->user->hasKey('purchase_quotation_convert')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $quotation_id = isset($this->request->post['quotation_id']) ? (int)$this->request->post['quotation_id'] : 0;

        if (!$quotation_id) {
            $json['error'] = $this->language->get('error_quotation_required');
            $this->sendJSON($json);
            return;
        }

        try {
            $this->load->model('purchase/order');
            $po_id = $this->model_purchase_quotation->convertToPurchaseOrder($quotation_id, $this->user->getId());

            if (!$po_id) {
                $json['error'] = $this->language->get('error_converting');
            } else {
                $json['success'] = $this->language->get('text_convert_success');
                $json['po_id'] = $po_id;
                $json['redirect'] = $this->url->link('purchase/order/view', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true);
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * Document management - Upload
     */
    public function ajaxUploadDocument() {
        if (!$this->user->hasKey('purchase_quotation_upload')) {
            $this->sendJSON(['error' => $this->language->get('error_permission')]);
            return;
        }

        $quotation_id = isset($this->request->post['quotation_id']) ? (int)$this->request->post['quotation_id'] : 0;

        if (!$quotation_id) {
            $this->sendJSON(['error' => $this->language->get('error_quotation_required')]);
            return;
        }

        if (!isset($this->request->files['file']) || !$this->request->files['file']['tmp_name']) {
            $this->sendJSON(['error' => $this->language->get('error_file_required')]);
            return;
        }

        try {
            $upload_info = $this->model_purchase_quotation->uploadDocument(
                $quotation_id,
                $this->request->files['file'],
                $this->request->post['document_type'] ?? 'quotation',
                $this->user->getId()
            );

            $this->sendJSON(['success' => $this->language->get('text_upload_success'), 'data' => $upload_info]);
        } catch (Exception $e) {
            $this->sendJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Document management - Download
     */
    public function downloadDocument() {
        if (!$this->user->hasKey('purchase_quotation_view')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $document_id = isset($this->request->get['document_id']) ? (int)$this->request->get['document_id'] : 0;

        if (!$document_id) {
            $this->session->data['error_warning'] = $this->language->get('error_document_required');
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $document_info = $this->model_purchase_quotation->getDocument($document_id);

        if (!$document_info || !file_exists(DIR_UPLOAD . $document_info['file_path'])) {
            $this->session->data['error_warning'] = $this->language->get('error_file_not_found');
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $file = DIR_UPLOAD . $document_info['file_path'];

        $mime_type = mime_content_type($file);
        if (!$mime_type) {
            $mime_type = 'application/octet-stream';
        }

        // Set headers for download
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $document_info['document_name'] . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($file);
        exit();
    }

    /**
     * Document management - Delete
     */
    public function ajaxDeleteDocument() {
        if (!$this->user->hasKey('purchase_quotation_delete')) {
            $this->sendJSON(['error' => $this->language->get('error_permission')]);
            return;
        }

        $document_id = isset($this->request->post['document_id']) ? (int)$this->request->post['document_id'] : 0;

        if (!$document_id) {
            $this->sendJSON(['error' => $this->language->get('error_document_required')]);
            return;
        }

        try {
            $result = $this->model_purchase_quotation->deleteDocument($document_id);

            if (!$result) {
                $this->sendJSON(['error' => $this->language->get('error_deleting_document')]);
            } else {
                $this->sendJSON(['success' => $this->language->get('text_delete_success')]);
            }
        } catch (Exception $e) {
            $this->sendJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Print a specific quotation
     */
    public function print() {
        if (!$this->user->hasKey('purchase_quotation_print')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $quotation_id = isset($this->request->get['quotation_id']) ? (int)$this->request->get['quotation_id'] : 0;

        if (!$quotation_id) {
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }

        // Get quotation data
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);

        if (!$quotation_info) {
$this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = [];

        // Get requisition info if linked
        $requisition_info = [];
        if ($quotation_info['requisition_id']) {
            $this->load->model('purchase/requisition');
            $requisition_info = $this->model_purchase_requisition->getRequisition($quotation_info['requisition_id']);
        }

        // Get supplier info
        $supplier_info = [];
        if ($quotation_info['supplier_id']) {
            $supplier_info = $this->model_purchase_quotation->getSupplier($quotation_info['supplier_id']);
        }

        // Format quotation data
        $currency_code = $quotation_info['currency_code'] ?? $this->config->get('config_currency');

        $data['quotation'] = [
            'quotation_id' => $quotation_info['quotation_id'],
            'quotation_number' => $quotation_info['quotation_number'],
            'requisition_id' => $quotation_info['requisition_id'],
            'requisition_number' => $requisition_info ? $requisition_info['req_number'] : '',
            'supplier_id' => $quotation_info['supplier_id'],
            'supplier_name' => $supplier_info ? $supplier_info['firstname'] . ' ' . $supplier_info['lastname'] : $quotation_info['supplier_name'],
            'supplier_address' => $supplier_info ? nl2br($supplier_info['address_1']) : '',
            'currency_code' => $currency_code,
            'currency_id' => $quotation_info['currency_id'],
            'exchange_rate' => $quotation_info['exchange_rate'],
            'subtotal' => $this->currency->format($quotation_info['subtotal'], $currency_code),
            'tax_amount' => $this->currency->format($quotation_info['tax_amount'], $currency_code),
            'discount_amount' => $this->currency->format($quotation_info['discount_amount'], $currency_code),
            'total_amount' => $this->currency->format($quotation_info['total_amount'], $currency_code),
            'tax_included' => $quotation_info['tax_included'],
            'tax_rate' => $quotation_info['tax_rate'],
            'status' => $quotation_info['status'],
            'status_text' => $this->model_purchase_quotation->getStatusText($quotation_info['status']),
            'validity_date' => date($this->language->get('date_format_short'), strtotime($quotation_info['validity_date'])),
            'payment_terms' => nl2br($quotation_info['payment_terms']),
            'delivery_terms' => nl2br($quotation_info['delivery_terms']),
            'notes' => nl2br($quotation_info['notes']),
            'created_at' => date($this->language->get('datetime_format'), strtotime($quotation_info['created_at'])),
            'created_by_name' => $this->model_purchase_quotation->getUserName($quotation_info['created_by'])
        ];

        // Get quotation items
        $data['items'] = $this->model_purchase_quotation->getQuotationItems($quotation_id);

        // Format item data
        foreach ($data['items'] as &$item) {
            $item['unit_price_formatted'] = $this->currency->format($item['unit_price'], $currency_code);
            $item['line_total_formatted'] = $this->currency->format($item['line_total'], $currency_code);
            $item['discount_amount_formatted'] = $this->currency->format($item['discount_amount'], $currency_code);
            $item['tax_amount_formatted'] = $this->currency->format($item['tax_amount'], $currency_code);
        }

        // Company info
        $data['company'] = [
            'name' => $this->config->get('config_name'),
            'address' => nl2br($this->config->get('config_address')),
            'email' => $this->config->get('config_email'),
            'telephone' => $this->config->get('config_telephone'),
            'logo' => $this->config->get('config_logo') ? HTTP_CATALOG . 'image/' . $this->config->get('config_logo') : '',
        ];

        // Language strings
        $data['text_quotation'] = $this->language->get('text_quotation');
        $data['text_quotation_number'] = $this->language->get('text_quotation_number');
        $data['text_date'] = $this->language->get('text_date');
        $data['text_requisition_reference'] = $this->language->get('text_requisition_reference');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_validity_date'] = $this->language->get('text_validity_date');
        $data['text_payment_terms'] = $this->language->get('text_payment_terms');
        $data['text_delivery_terms'] = $this->language->get('text_delivery_terms');
        $data['text_notes'] = $this->language->get('text_notes');
        $data['text_prepared_by'] = $this->language->get('text_prepared_by');
        $data['text_authorized_by'] = $this->language->get('text_authorized_by');
        $data['text_supplier_signature'] = $this->language->get('text_supplier_signature');
        $data['text_subtotal'] = $this->language->get('text_subtotal');
        $data['text_discount'] = $this->language->get('text_discount');
        $data['text_tax'] = $this->language->get('text_tax');
        $data['text_total'] = $this->language->get('text_total');
        $data['text_print_date'] = $this->language->get('text_print_date');

        $data['column_item'] = $this->language->get('column_item');
        $data['column_description'] = $this->language->get('column_description');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_discount'] = $this->language->get('column_discount');
        $data['column_tax'] = $this->language->get('column_tax');
        $data['column_total'] = $this->language->get('column_total');

        $data['print_date'] = date($this->language->get('datetime_format'));
        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('purchase/quotation_print', $data));
    }

    /**
     * Export quotations data
     */
    public function export() {
        if (!$this->user->hasKey('purchase_quotation_export')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $export_type = isset($this->request->get['type']) ? $this->request->get['type'] : 'excel';

        // Initialize filters
        $filter_data = [
            'filter_quotation_number'   => $this->request->get['filter_quotation_number'] ?? '',
            'filter_requisition_id'     => $this->request->get['filter_requisition_id'] ?? '',
            'filter_supplier_id'        => isset($this->request->get['filter_supplier_id']) ? (int)$this->request->get['filter_supplier_id'] : 0,
            'filter_status'             => $this->request->get['filter_status'] ?? '',
            'filter_validity'           => $this->request->get['filter_validity'] ?? 'all',
            'filter_date_start'         => $this->request->get['filter_date_start'] ?? '',
            'filter_date_end'           => $this->request->get['filter_date_end'] ?? '',
            'sort'                      => 'q.created_at',
            'order'                     => 'DESC',
            'start'                     => 0,
            'limit'                     => 1000 // Higher limit for export
        ];

        $quotations = $this->model_purchase_quotation->getQuotations($filter_data);

        if ($export_type == 'excel') {
            $this->exportExcel($quotations);
        } else if ($export_type == 'pdf') {
            $this->exportPDF($quotations);
        } else {
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

/**
 * تصدير مقارنة عروض الأسعار إلى Excel
 */
protected function exportComparisonExcel($data) {
    // استدعاء مكتبة PhpSpreadsheet
    require_once(DIR_SYSTEM . 'library/phpspreadsheet/vendor/autoload.php');

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

    // ضبط خصائص المستند
    $spreadsheet->getProperties()
        ->setCreator($this->config->get('config_name'))
        ->setLastModifiedBy($this->config->get('config_name'))
        ->setTitle($this->language->get('text_quotation_comparison'))
        ->setSubject($this->language->get('text_quotation_comparison'))
        ->setDescription($this->language->get('text_quotation_comparison'));

    $spreadsheet->setActiveSheetIndex(0);
    $sheet = $spreadsheet->getActiveSheet();

    // تهيئة الترويسات
    $requiredColumns = [
        'A' => $this->language->get('column_product'),
        'B' => $this->language->get('column_quantity'),
        'C' => $this->language->get('column_unit')
    ];

    // إضافة أعمدة الموردين
    $supplier_columns = [];
    $col = 'D';
    foreach ($data['quotations'] as $quotation) {
        $supplier_columns[$col] = $quotation['supplier_name'] . ' (' . $quotation['quotation_number'] . ')';
        $col++;
    }

    // ضبط صف الترويسة
    foreach ($requiredColumns as $column => $title) {
        $sheet->setCellValue($column . '1', $title);
        $sheet->getStyle($column . '1')->getFont()->setBold(true);
        $sheet->getStyle($column . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle($column . '1')->getFill()->getStartColor()->setRGB('EEEEEE');
    }

    foreach ($supplier_columns as $column => $title) {
        $sheet->setCellValue($column . '1', $title);
        $sheet->getStyle($column . '1')->getFont()->setBold(true);
        $sheet->getStyle($column . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle($column . '1')->getFill()->getStartColor()->setRGB('EEEEEE');
    }

    // تعبئة صفوف البيانات
    $row = 2;
    foreach ($data['comparison'] as $item) {
        $sheet->setCellValue('A' . $row, $item['product_name']);
        $sheet->setCellValue('B' . $row, $item['quantity']);
        $sheet->setCellValue('C' . $row, $item['unit_name']);

        // إضافة أسعار الموردين
        $col = 'D';
        foreach ($data['quotations'] as $quotation_id => $quotation) {
            $price = isset($item['supplier_prices'][$quotation_id]) ? $item['supplier_prices'][$quotation_id]['unit_price'] : '';
            $sheet->setCellValue($col . $row, $price);

            // تمييز أفضل سعر
            if (isset($item['supplier_prices'][$quotation_id]) && $item['supplier_prices'][$quotation_id]['is_best_price']) {
                $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle($col . $row)->getFill()->getStartColor()->setRGB('DDFFDD');
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
            }

            $col++;
        }

        $row++;
    }

    // إضافة صف الإجمالي
    $sheet->setCellValue('A' . $row, $this->language->get('text_total'));
    $sheet->mergeCells('A' . $row . ':C' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);

    $col = 'D';
    foreach ($data['quotations'] as $quotation) {
        $sheet->setCellValue($col . $row, $quotation['total_amount']);

        // تمييز أقل إجمالي
        if ($quotation['has_lowest_total']) {
            $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle($col . $row)->getFill()->getStartColor()->setRGB('DDFFDD');
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
        }

        $col++;
    }

    // تنسيق الأعمدة
    foreach (array_merge(array_keys($requiredColumns), array_keys($supplier_columns)) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // تعيين اسم وفهرس ورقة العمل النشطة
    $spreadsheet->getActiveSheet()->setTitle($this->language->get('text_comparison'));
    $spreadsheet->setActiveSheetIndex(0);

    // ضبط ترويسات الملف لتنزيله
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="quotation_comparison_' . date('Y-m-d_His') . '.xlsx"');
    header('Cache-Control: max-age=0');

    // إنشاء الملف وتصديره
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * تصدير إلى Excel
 */
protected function exportExcel($quotations) {
    // استدعاء مكتبة PhpSpreadsheet
    require_once(DIR_SYSTEM . 'library/phpspreadsheet/vendor/autoload.php');

    // إنشاء ملف اكسل جديد
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

    // ضبط خصائص المستند
    $spreadsheet->getProperties()
        ->setCreator($this->config->get('config_name'))
        ->setLastModifiedBy($this->config->get('config_name'))
        ->setTitle($this->language->get('heading_title'))
        ->setSubject($this->language->get('heading_title'))
        ->setDescription($this->language->get('heading_title'));

    $spreadsheet->setActiveSheetIndex(0);
    $sheet = $spreadsheet->getActiveSheet();

    // ضبط ترويسة الأعمدة
    $columns = [
        'A' => 'column_quotation_number',
        'B' => 'column_requisition_number',
        'C' => 'column_supplier',
        'D' => 'column_total',
        'E' => 'column_status',
        'F' => 'text_validity_date',
        'G' => 'column_date_added'
    ];

    foreach ($columns as $column => $langKey) {
        $sheet->setCellValue($column . '1', $this->language->get($langKey));
        $sheet->getStyle($column . '1')->getFont()->setBold(true);
        $sheet->getStyle($column . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle($column . '1')->getFill()->getStartColor()->setRGB('EEEEEE');
    }

    // تعبئة صفوف البيانات
    $row = 2;
    foreach ($quotations as $quotation) {
        $currency_code = $quotation['currency_code'] ?? $this->config->get('config_currency');

        $sheet->setCellValue('A' . $row, $quotation['quotation_number']);
        $sheet->setCellValue('B' . $row, $quotation['requisition_number']);
        $sheet->setCellValue('C' . $row, $quotation['supplier_name']);
        $sheet->setCellValue('D' . $row, $this->currency->format($quotation['total_amount'], $currency_code, false));
        $sheet->setCellValue('E' . $row, $this->model_purchase_quotation->getStatusText($quotation['status']));
        $sheet->setCellValue('F' . $row, date($this->language->get('date_format_short'), strtotime($quotation['validity_date'])));
        $sheet->setCellValue('G' . $row, date($this->language->get('datetime_format'), strtotime($quotation['created_at'])));
        $row++;
    }

    // تنسيق الأعمدة
    foreach (array_keys($columns) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // إضافة تنسيق العملة لعمود المجموع
    $lastRow = count($quotations) + 1;
    $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

    // إضافة تنسيق التاريخ
    $sheet->getStyle('F2:F' . $lastRow)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD);
    $sheet->getStyle('G2:G' . $lastRow)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME);

    // تعيين اسم وفهرس ورقة العمل النشطة
    $spreadsheet->getActiveSheet()->setTitle($this->language->get('text_quotations'));
    $spreadsheet->setActiveSheetIndex(0);

    // ضبط ترويسات الملف لتنزيله
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="quotations_' . date('Y-m-d_His') . '.xlsx"');
    header('Cache-Control: max-age=0');

    // إنشاء الملف وتصديره
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
    /**
     * Export to PDF
     */
    protected function exportPDF($quotations) {
        // Check if we have mPDF or TCPDF installed
        if (class_exists('Mpdf\Mpdf')) {
            $this->exportPDFWithMpdf($quotations);
        } else {
            // Fallback to basic HTML to encourage installing a PDF library
            $this->exportPDFAsHTML($quotations);
        }
    }

    /**
     * Export using mPDF library if available
     */
    protected function exportPDFWithMpdf($quotations) {
        require_once(DIR_SYSTEM . 'library/vendor/autoload.php');

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15
        ]);

        // Set document info
        $mpdf->SetCreator($this->config->get('config_name'));
        $mpdf->SetTitle($this->language->get('heading_title'));

        // Generate HTML content
        $html = $this->getPDFContent($quotations);

        // Write content
        $mpdf->WriteHTML($html);

        // Output PDF
        $mpdf->Output('quotations_' . date('Y-m-d_His') . '.pdf', 'D');
        exit;
    }

    /**
     * Fallback to HTML when no PDF library is available
     */
    protected function exportPDFAsHTML($quotations) {
        $html = $this->getPDFContent($quotations);

        // Set headers
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="quotations_' . date('Y-m-d_His') . '.html"');

        echo $html;
        exit;
    }

    /**
     * Generate HTML content for PDF export
     */
    protected function getPDFContent($quotations) {
        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'text_date' => $this->language->get('text_date') . ': ' . date($this->language->get('date_format_short')),
            'text_quotations' => $this->language->get('text_quotations'),
            'column_quotation_number' => $this->language->get('column_quotation_number'),
            'column_requisition_number' => $this->language->get('column_requisition_number'),
            'column_supplier' => $this->language->get('column_supplier'),
            'column_total' => $this->language->get('column_total'),
            'column_status' => $this->language->get('column_status'),
            'text_validity_date' => $this->language->get('text_validity_date'),
            'column_date_added' => $this->language->get('column_date_added'),
            'config_name' => $this->config->get('config_name'),
            'quotations' => []
        ];

        foreach ($quotations as $quotation) {
            $currency_code = $quotation['currency_code'] ?? $this->config->get('config_currency');

            $data['quotations'][] = [
                'quotation_number' => $quotation['quotation_number'],
                'requisition_number' => $quotation['requisition_number'],
                'supplier_name' => $quotation['supplier_name'],
                'total_formatted' => $this->currency->format($quotation['total_amount'], $currency_code),
                'status_text' => $this->model_purchase_quotation->getStatusText($quotation['status']),
                'validity_date' => date($this->language->get('date_format_short'), strtotime($quotation['validity_date'])),
                'created_at' => date($this->language->get('datetime_format'), strtotime($quotation['created_at']))
            ];
        }

        return $this->load->view('purchase/quotation_export_pdf', $data);
    }

    /**
     * Export comparison of quotations for a requisition to PDF
     */
    public function exportComparison() {
        if (!$this->user->hasKey('purchase_quotation_export')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $requisition_id = isset($this->request->get['requisition_id']) ? (int)$this->request->get['requisition_id'] : 0;
        $export_type = isset($this->request->get['type']) ? $this->request->get['type'] : 'pdf';

        if (!$requisition_id) {
            $this->session->data['error_warning'] = $this->language->get('error_requisition_required');
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->model_purchase_quotation->getComparisonData($requisition_id);

        if (!$data || empty($data['quotations'])) {
            $this->session->data['error_warning'] = $this->language->get('error_no_quotations');
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }

        if ($export_type == 'excel') {
            $this->exportComparisonExcel($data);
        } else {
            $this->exportComparisonPDF($data);
        }
    }

    /**
     * Export comparison to PDF
     */
    protected function exportComparisonPDF($data) {
        // Check if we have mPDF installed
        if (class_exists('Mpdf\Mpdf')) {
            require_once(DIR_SYSTEM . 'library/vendor/autoload.php');

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);

            // Set document info
            $mpdf->SetCreator($this->config->get('config_name'));
            $mpdf->SetTitle($this->language->get('text_quotation_comparison'));

            // Add language data needed for the template
            $data['text_quotation_comparison'] = $this->language->get('text_quotation_comparison');
            $data['text_requisition_details'] = $this->language->get('text_requisition_details');
            $data['text_requisition_number'] = $this->language->get('text_requisition_number');
            $data['text_date'] = $this->language->get('text_date');
            $data['text_comparison_date'] = date($this->language->get('date_format_short'));
            $data['text_best_price'] = $this->language->get('text_best_price');
            $data['text_lowest_total'] = $this->language->get('text_lowest_total');

            $data['column_product'] = $this->language->get('column_product');
            $data['column_quantity'] = $this->language->get('column_quantity');
            $data['column_unit'] = $this->language->get('column_unit');
            $data['text_total'] = $this->language->get('text_total');

            $data['company_name'] = $this->config->get('config_name');
            $data['company_logo'] = $this->config->get('config_logo') ? HTTP_CATALOG . 'image/' . $this->config->get('config_logo') : '';

            // Generate HTML content
            $html = $this->load->view('purchase/quotation_comparison_pdf', $data);

            // Write content
            $mpdf->WriteHTML($html);

            // Output PDF
            $mpdf->Output('quotation_comparison_' . date('Y-m-d_His') . '.pdf', 'D');
            exit;
        } else {
            // Fallback to HTML
            $data['text_quotation_comparison'] = $this->language->get('text_quotation_comparison');
            $data['text_requisition_details'] = $this->language->get('text_requisition_details');
            $data['text_requisition_number'] = $this->language->get('text_requisition_number');
            $data['text_date'] = $this->language->get('text_date');
            $data['text_comparison_date'] = date($this->language->get('date_format_short'));
            $data['text_best_price'] = $this->language->get('text_best_price');
            $data['text_lowest_total'] = $this->language->get('text_lowest_total');

            $data['column_product'] = $this->language->get('column_product');
            $data['column_quantity'] = $this->language->get('column_quantity');
            $data['column_unit'] = $this->language->get('column_unit');
            $data['text_total'] = $this->language->get('text_total');

            $data['company_name'] = $this->config->get('config_name');
            $data['company_logo'] = $this->config->get('config_logo') ? HTTP_CATALOG . 'image/' . $this->config->get('config_logo') : '';

            // Set headers
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="quotation_comparison_' . date('Y-m-d_His') . '.html"');

            echo $this->load->view('purchase/quotation_comparison_pdf', $data);
            exit;
        }
    }

    /**
     * AJAX endpoint for bulk actions on multiple quotations
     */
    public function bulkAction() {
        $this->load->language('purchase/quotation');

        $json = [];

        if (!isset($this->request->post['action']) || !isset($this->request->post['selected']) || !is_array($this->request->post['selected'])) {
            $json['error'] = $this->language->get('error_invalid_request');
            $this->sendJSON($json);
            return;
        }

        $action = $this->request->post['action'];
        $selected = array_map('intval', $this->request->post['selected']);

        // Check permissions based on action
        switch ($action) {
            case 'approve':
                if (!$this->user->hasKey('purchase_quotation_approve')) {
                    $json['error'] = $this->language->get('error_permission');
                    $this->sendJSON($json);
                    return;
                }
                break;

            case 'reject':
                if (!$this->user->hasKey('purchase_quotation_reject')) {
                    $json['error'] = $this->language->get('error_permission');
                    $this->sendJSON($json);
                    return;
                }
                break;

            case 'delete':
                if (!$this->user->hasKey('purchase_quotation_delete')) {
                    $json['error'] = $this->language->get('error_permission');
                    $this->sendJSON($json);
                    return;
                }
                break;

            default:
                $json['error'] = $this->language->get('error_invalid_action');
                $this->sendJSON($json);
                return;
        }

        // Process the action
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($selected as $quotation_id) {
            try {
                switch ($action) {
                    case 'approve':
                        $success = $this->model_purchase_quotation->approveQuotation($quotation_id, $this->user->getId());
                        break;

                    case 'reject':
                        $reason = $this->request->post['reason'] ?? $this->language->get('text_bulk_action');
                        $success = $this->model_purchase_quotation->rejectQuotation($quotation_id, $reason, $this->user->getId());
                        break;

                    case 'delete':
                        $success = $this->model_purchase_quotation->deleteQuotation($quotation_id);
                        break;
                }

                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = $this->language->get('error_processing') . ' #' . $quotation_id . ': ' . $e->getMessage();
            }
        }

        // Build response
        if ($results['success'] > 0) {
            $json['success'] = sprintf($this->language->get('text_bulk_success'), $results['success'], count($selected));
        }

        if ($results['failed'] > 0) {
            $json['error'] = sprintf($this->language->get('text_bulk_failed'), $results['failed'], count($selected));
            if (!empty($results['errors'])) {
                $json['errors'] = $results['errors'];
            }
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX endpoint for searching quotations (for select2)
     */
    public function ajaxQuotations() {
        $json = [];
        $search = isset($this->request->get['q']) ? trim($this->request->get['q']) : '';

        $results = $this->model_purchase_quotation->searchQuotations($search);

        foreach ($results as $result) {
            $json[] = [
                'id' => $result['quotation_id'],
                'text' => sprintf('#%s - %s (%s)',
                    $result['quotation_number'],
                    $result['supplier_name'],
                    $this->currency->format($result['total_amount'], $result['currency_code'] ?? $this->config->get('config_currency'))
                )
            ];
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX: Change quotation status (e.g., draft to pending)
     */
    public function changeStatus() {
        $this->load->language('purchase/quotation');
        $json = [];

        $quotation_id = isset($this->request->post['quotation_id']) ? (int)$this->request->post['quotation_id'] : 0;
        $new_status = isset($this->request->post['status']) ? $this->request->post['status'] : '';
        $reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : ''; // For potential future use (e.g., cancellation)

        // Basic validation
        if (!$quotation_id) {
            $json['error'] = $this->language->get('error_quotation_required');
            $this->sendJSON($json);
            return;
        }

        if (empty($new_status)) {
            $json['error'] = $this->language->get('error_status_required');
            $this->sendJSON($json);
            return;
        }

        // Permission check (assuming edit permission allows status change from draft)
        if (!$this->user->hasKey('purchase_quotation_edit')) {
             $json['error'] = $this->language->get('error_permission');
             $this->sendJSON($json);
             return;
        }

        try {
            // Call a new model function to handle the status change
            $result = $this->model_purchase_quotation->changeQuotationStatus($quotation_id, $new_status, $this->user->getId(), $reason);

            if (!$result) {
                // The model function should throw an exception or return false with an error message
                 $json['error'] = $this->language->get('error_status_change');
            } else {
                $json['success'] = $this->language->get('text_status_change_success');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

/**
 * AJAX method to search products
 */
public function ajaxSearchProducts() {
    if (!$this->user->hasKey('purchase_quotation_view')) {
        $this->sendJSON(['error' => $this->language->get('error_permission')]);
        return;
    }

    $json = [];
    $search = $this->request->get['q'] ?? '';

    $results = $this->model_purchase_quotation->searchProducts($search);

    foreach ($results as $result) {
        $json[] = [
            'id' => $result['product_id'],
            'text' => $result['name'] . ' (' . $result['model'] . ')',
            'model' => $result['model']
        ];
    }

    $this->sendJSON($json);
}
/**
 * AJAX method to fetch requisition items including inventory and cost data
 */
public function ajaxGetRequisitionItems() {
    if (!$this->user->hasKey('purchase_quotation_view')) {
        $this->sendJSON(['error' => $this->language->get('error_permission')]);
        return;
    }

    $json = [];
    $requisition_id = isset($this->request->get['requisition_id']) ? (int)$this->request->get['requisition_id'] : 0;

    if (!$requisition_id) {
        $this->sendJSON(['error' => $this->language->get('error_requisition_required')]);
        return;
    }

    // Load requisition model if not already loaded
    $this->load->model('purchase/requisition');

    // Get requisition info for verification
    $requisition_info = $this->model_purchase_requisition->getRequisition($requisition_id);

    if (!$requisition_info) {
        $this->sendJSON(['error' => $this->language->get('error_requisition_not_found')]);
        return;
    }

    // Get requisition items
    $requisition_items = $this->model_purchase_requisition->getRequisitionItems($requisition_id);

    // Enhanced items array with additional data
    $items = [];

    foreach ($requisition_items as $item) {
        $product_id = $item['product_id'];
        $unit_id = $item['unit_id'];

        // Get product units
        $units = $this->model_purchase_quotation->getProductUnits($product_id);

        // Get inventory data
        $inventory = $this->model_purchase_quotation->getProductInventory($product_id, $unit_id);

        // Get last purchase price for this product/unit
        $last_purchase = $this->model_purchase_quotation->getLastPurchasePrice($product_id, $unit_id);

        // Enhanced item data
        $enhanced_item = [
            'requisition_item_id' => $item['requisition_item_id'],
            'product_id' => $product_id,
            'product_name' => $item['product_name'],
            'product_model' => isset($item['model']) ? $item['model'] : '',
            'quantity' => $item['quantity'],
            'unit_id' => $unit_id,
            'unit_name' => $item['unit_name'],
            'description' => $item['description'] ?? '',
            'units' => $units,
            'inventory' => [
                'quantity_available' => $inventory['quantity_available'] ?? 0,
                'average_cost' => $inventory['average_cost'] ?? 0,
                'average_cost_formatted' => $this->currency->format(
                    $inventory['average_cost'] ?? 0,
                    $this->config->get('config_currency')
                )
            ],
            'last_purchase' => [
                'price' => $last_purchase['unit_price'] ?? 0,
                'price_formatted' => $this->currency->format(
                    $last_purchase['unit_price'] ?? 0,
                    $this->config->get('config_currency')
                ),
                'supplier_name' => $last_purchase['supplier_name'] ?? '',
                'date' => $last_purchase['date'] ?? ''
            ],
            'suggested_price' => $last_purchase['unit_price'] ?? $inventory['average_cost'] ?? 0
        ];

        $items[] = $enhanced_item;
    }

    // Return requisition data and items
    $json['requisition'] = [
        'requisition_id' => $requisition_info['requisition_id'],
        'req_number' => $requisition_info['req_number'],
        'branch_name' => $requisition_info['branch_name'],
        'user_group_name' => $requisition_info['user_group_name'],
        'required_date' => date($this->language->get('date_format_short'), strtotime($requisition_info['required_date'])),
        'priority' => $requisition_info['priority'],
        'priority_text' => $this->language->get('text_priority_' . $requisition_info['priority']),
        'status' => $requisition_info['status'],
        'notes' => $requisition_info['notes'] ?? ''
    ];

    $json['items'] = $items;

    $this->sendJSON($json);
}
/**
 * AJAX method to get product details including units, inventory and costs
 */
public function ajaxGetProductDetails() {
    if (!$this->user->hasKey('purchase_quotation_view')) {
        $this->sendJSON(['error' => $this->language->get('error_permission')]);
        return;
    }

    $product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;

    if (!$product_id) {
        $this->sendJSON(['error' => $this->language->get('error_product_required')]);
        return;
    }

    $product_info = $this->model_purchase_quotation->getProductDetails($product_id);

    $this->sendJSON($product_info);
}

/**
 * AJAX method to get supplier information and rating
 */
public function ajaxGetSupplierInfo() {
    if (!$this->user->hasKey('purchase_quotation_view')) {
        $this->sendJSON(['error' => $this->language->get('error_permission')]);
        return;
    }

    $supplier_id = isset($this->request->get['supplier_id']) ? (int)$this->request->get['supplier_id'] : 0;

    if (!$supplier_id) {
        $this->sendJSON(['error' => $this->language->get('error_supplier_required')]);
        return;
    }

    $supplier_info = $this->model_purchase_quotation->getSupplierInfo($supplier_id);

    $this->sendJSON(['supplier' => $supplier_info]);
}

    /**
     * Helper method to send JSON response
     */
    protected function sendJSON($data) {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function ajaxGetPriceHistory() {
        $json = array();

        $product_id = (int)($this->request->get['product_id'] ?? 0);
        $unit_id = (int)($this->request->get['unit_id'] ?? 0);

        if ($product_id && $unit_id) {
            $this->load->model('purchase/quotation');
            $json['history'] = $this->model_purchase_quotation->getProductPriceHistory($product_id, $unit_id);

            // Calculate price analysis
            if (!empty($json['history'])) {
                $prices = array_column($json['history'], 'unit_price');
                $json['analysis'] = array(
                    'avg_price' => array_sum($prices) / count($prices),
                    'min_price' => min($prices),
                    'max_price' => max($prices)
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function ajaxGetSupplierHistory() {
        $json = array();

        $product_id = (int)($this->request->get['product_id'] ?? 0);
        $supplier_id = (int)($this->request->get['supplier_id'] ?? 0);

        if ($product_id && $supplier_id) {
            $this->load->model('purchase/quotation');
            $json['history'] = $this->model_purchase_quotation->getSupplierProductHistory($product_id, $supplier_id);

            // Calculate supplier performance analysis
            if (!empty($json['history'])) {
                $total_orders = count($json['history']);
                $on_time_deliveries = 0;
                $quality_ratings = array();

                foreach ($json['history'] as $entry) {
                    if ($entry['is_on_time']) {
                        $on_time_deliveries++;
                    }
                    if ($entry['quality_rating']) {
                        $quality_ratings[] = $entry['quality_rating'];
                    }
                }

                $json['analysis'] = array(
                    'on_time_delivery' => $total_orders ? ($on_time_deliveries / $total_orders) : 0,
                    'avg_quality' => $quality_ratings ? (array_sum($quality_ratings) / count($quality_ratings)) : 0
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function ajaxSaveQuotation() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/quotation')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $this->load->model('purchase/quotation');

        try {
            $data = array(
                'requisition_id' => (int)($this->request->post['requisition_id'] ?? 0),
                'supplier_id' => (int)($this->request->post['supplier_id'] ?? 0),
                'currency_id' => (int)($this->request->post['currency_id'] ?? 0),
                'exchange_rate' => (float)($this->request->post['exchange_rate'] ?? 1),
                'validity_date' => $this->request->post['validity_date'] ?? '',
                'payment_terms' => $this->request->post['payment_terms'] ?? '',
                'delivery_terms' => $this->request->post['delivery_terms'] ?? '',
                'notes' => $this->request->post['notes'] ?? '',
                'tax_included' => (bool)($this->request->post['tax_included'] ?? false),
                'tax_rate' => (float)($this->request->post['tax_rate'] ?? 0),
                'discount_type' => $this->request->post['discount_type'] ?? 'fixed',
                'discount_value' => (float)($this->request->post['discount_value'] ?? 0),
                'items' => array()
            );

            // Validate required fields
            if (empty($data['requisition_id'])) {
                throw new Exception($this->language->get('error_requisition'));
            }
            if (empty($data['supplier_id'])) {
                throw new Exception($this->language->get('error_supplier'));
            }
            if (empty($data['currency_id'])) {
                throw new Exception($this->language->get('error_currency'));
            }

            // Process items
            if (!empty($this->request->post['item'])) {
                foreach ($this->request->post['item'] as $item) {
                    $data['items'][] = array(
                        'requisition_item_id' => (int)($item['requisition_item_id'] ?? 0),
                        'product_id' => (int)($item['product_id'] ?? 0),
                        'quantity' => (float)($item['quantity'] ?? 0),
                        'unit_id' => (int)($item['unit_id'] ?? 0),
                        'unit_price' => (float)($item['unit_price'] ?? 0),
                        'tax_rate' => (float)($item['tax_rate'] ?? 0),
                        'discount_type' => $item['discount_type'] ?? 'fixed',
                        'discount_rate' => (float)($item['discount_rate'] ?? 0)
                    );
                }
            }

            if (empty($data['items'])) {
                throw new Exception($this->language->get('error_no_items'));
            }

            // Save quotation
            $quotation_id = $this->model_purchase_quotation->addQuotation($data);

            $json['success'] = $this->language->get('text_success_add');
            $json['quotation_id'] = $quotation_id;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

/**
     * Handle document upload for quotations
     */
    public function ajaxUploadDocument() {
        $json = array();

        // Verify permissions
        if (!$this->user->hasKey('purchase_quotation_document_upload')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        // Check if file was uploaded
        if (!isset($this->request->files['document']) || !is_uploaded_file($this->request->files['document']['tmp_name'])) {
            $json['error'] = $this->language->get('error_upload');
            return $this->sendJSON($json);
        }

        $file = $this->request->files['document'];

        // Validate file size (max 10MB)
        if ($file['size'] > 10485760) {
            $json['error'] = $this->language->get('error_file_size');
            return $this->sendJSON($json);
        }

        // Create upload directory if it doesn't exist
        $upload_dir = DIR_UPLOAD . 'quotation_documents/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $filename = uniqid() . '-' . $this->clean_filename($file['name']);

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
            // Save document info to database
            $document_data = array(
                'quotation_id' => (int)$this->request->post['quotation_id'],
                'filename' => $filename,
                'original_filename' => $file['name'],
                'file_type' => $file['type'],
                'file_size' => $file['size'],
                'document_type' => $this->request->post['document_type'],
                'uploaded_by' => $this->user->getId(),
                'upload_date' => date('Y-m-d H:i:s')
            );

            $document_id = $this->model_purchase_quotation->addDocument($document_data);

            if ($document_id) {
                $json['success'] = $this->language->get('text_upload_success');
                $json['document'] = $this->getDocumentInfo($document_id);
            } else {
                unlink($upload_dir . $filename);
                $json['error'] = $this->language->get('error_saving_document');
            }
        } else {
            $json['error'] = $this->language->get('error_moving_file');
        }

        return $this->sendJSON($json);
    }

    /**
     * Get document info
     */
    public function ajaxGetDocument() {
        $json = array();

        $document_id = isset($this->request->get['document_id']) ? (int)$this->request->get['document_id'] : 0;

        if ($document_id) {
            $document_info = $this->model_purchase_quotation->getDocument($document_id);
            if ($document_info) {
                $json = $this->getDocumentInfo($document_id);
            } else {
                $json['error'] = $this->language->get('error_document_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        return $this->sendJSON($json);
    }

    /**
     * Delete document
     */
    public function ajaxDeleteDocument() {
        $json = array();

        if (!$this->user->hasKey('purchase_quotation_document_delete')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $document_id = isset($this->request->get['document_id']) ? (int)$this->request->get['document_id'] : 0;

        if ($document_id) {
            $document_info = $this->model_purchase_quotation->getDocument($document_id);

            if ($document_info) {
                // Delete file
                $file_path = DIR_UPLOAD . 'quotation_documents/' . $document_info['filename'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                // Delete database record
                $this->model_purchase_quotation->deleteDocument($document_id);

                $json['success'] = $this->language->get('text_delete_success');
            } else {
                $json['error'] = $this->language->get('error_document_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        return $this->sendJSON($json);
    }

    /**
     * Get document preview URL
     */
    public function ajaxGetDocumentPreview() {
        $json = array();

        $document_id = isset($this->request->get['document_id']) ? (int)$this->request->get['document_id'] : 0;

        if ($document_id) {
            $document_info = $this->model_purchase_quotation->getDocument($document_id);

            if ($document_info) {
                $file_path = DIR_UPLOAD . 'quotation_documents/' . $document_info['filename'];
                if (file_exists($file_path)) {
                    // For images, return base64 encoded data
                    if (strpos($document_info['file_type'], 'image/') === 0) {
                        $image_data = base64_encode(file_get_contents($file_path));
                        $json['preview'] = 'data:' . $document_info['file_type'] . ';base64,' . $image_data;
                    } else {
                        // For other files, return download URL
                        $json['preview'] = $this->url->link('purchase/quotation/downloadDocument', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $document_id, true);
                    }

                    $json['filename'] = $document_info['original_filename'];
                    $json['file_type'] = $document_info['file_type'];
                } else {
                    $json['error'] = $this->language->get('error_file_not_found');
                }
            } else {
                $json['error'] = $this->language->get('error_document_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        return $this->sendJSON($json);
    }

    /**
     * Download document
     */
    public function downloadDocument() {
        $document_id = isset($this->request->get['document_id']) ? (int)$this->request->get['document_id'] : 0;

        if ($document_id) {
            $document_info = $this->model_purchase_quotation->getDocument($document_id);

            if ($document_info) {
                $file_path = DIR_UPLOAD . 'quotation_documents/' . $document_info['filename'];

                if (file_exists($file_path)) {
                    header('Content-Type: ' . $document_info['file_type']);
                    header('Content-Disposition: attachment; filename="' . $document_info['original_filename'] . '"');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file_path));

                    readfile($file_path);
                    exit;
                } else {
                    $this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
                }
            } else {
                $this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * Helper: Clean filename
     */
    private function clean_filename($filename) {
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', $filename);
        // Convert spaces to underscores
        $filename = str_replace(' ', '_', $filename);
        // Ensure filename is unique
        return strtolower($filename);
    }

    /**
     * Helper: Get document info array
     */
    private function getDocumentInfo($document_id) {
        $document_info = $this->model_purchase_quotation->getDocument($document_id);

        if ($document_info) {
            return array(
                'document_id' => $document_id,
                'filename' => $document_info['original_filename'],
                'file_type' => $document_info['file_type'],
                'file_size' => $this->formatFileSize($document_info['file_size']),
                'document_type' => $document_info['document_type'],
                'upload_date' => date($this->language->get('date_format_short'), strtotime($document_info['upload_date'])),
                'uploaded_by' => $document_info['uploaded_by'],
                'preview_url' => $this->url->link('purchase/quotation/ajaxGetDocumentPreview', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $document_id, true),
                'download_url' => $this->url->link('purchase/quotation/downloadDocument', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $document_id, true)
            );
        }

        return array();
    }

    /**
     * Helper: Format file size
     */
    private function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    // ...existing code...

/**
 * Get requisition items for select2 AJAX
 */
public function select2Requisitions() {
    $json = array();
    $q = isset($this->request->get['q']) ? $this->request->get['q'] : '';

    $results = $this->model_purchase_quotation->searchRequisitions($q);
    foreach ($results as $result) {
        $json[] = array(
            'id' => $result['requisition_id'],
            'text' => $result['req_number'] . ' - ' . $result['branch_name']
        );
    }

    $this->sendJSON($json);
}

/**
 * Get products for select2 AJAX
 */
public function select2Products() {
    $json = array();
    $q = isset($this->request->get['q']) ? $this->request->get['q'] : '';

    $results = $this->model_purchase_quotation->searchProducts($q);
    foreach ($results as $result) {
        $json[] = array(
            'id' => $result['product_id'],
            'text' => $result['name'],
            'units' => $result['units']
        );
    }

    $this->sendJSON($json);
}

/**
 * Get requisition items details
 */
public function getRequisitionItems() {
    $json = array();
    $requisition_id = isset($this->request->get['requisition_id']) ? (int)$this->request->get['requisition_id'] : 0;

    if ($requisition_id) {
        $json['items'] = $this->model_purchase_quotation->getRequisitionItemsWithDetails($requisition_id);
    }

    $this->sendJSON($json);
}

/**
 * Get price history for a product-unit combination
 */
public function getPriceHistory() {
    $json = array();
    $product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
    $unit_id = isset($this->request->get['unit_id']) ? (int)$this->request->get['unit_id'] : 0;

    if ($product_id && $unit_id) {
        $json['history'] = $this->model_purchase_quotation->getProductPriceHistory($product_id, $unit_id);
    }

    $this->sendJSON($json);
}

/**
 * Get supplier history for a product
 */
public function getSupplierHistory() {
    $json = array();
    $product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
    $supplier_id = isset($this->request->get['supplier_id']) ? (int)$this->request->get['supplier_id'] : 0;

    if ($product_id && $supplier_id) {
        $json['history'] = $this->model_purchase_quotation->getSupplierProductHistory($product_id, $supplier_id);
    }

    $this->sendJSON($json);
}

/**
 * Handle form submission
 */
public function save() {
    $this->load->language('purchase/quotation');
    $json = array();

    // Check permissions
    if (!$this->user->hasKey('purchase_quotation_add') && !$this->user->hasKey('purchase_quotation_edit')) {
        $json['error'] = $this->language->get('error_permission');
        $this->sendJSON($json);
        return;
    }

    // Validate form data
    if (empty($this->request->post['requisition_id'])) {
        $json['error'] = $this->language->get('error_requisition');
        $this->sendJSON($json);
        return;
    }

    if (empty($this->request->post['supplier_id'])) {
        $json['error'] = $this->language->get('error_supplier');
        $this->sendJSON($json);
        return;
    }

    if (empty($this->request->post['validity_date'])) {
        $json['error'] = $this->language->get('error_validity_date');
        $this->sendJSON($json);
        return;
    }

    // Validate items
    if (empty($this->request->post['item']) || !is_array($this->request->post['item'])) {
        $json['error'] = $this->language->get('error_items_required');
        $this->sendJSON($json);
        return;
    }

    foreach ($this->request->post['item'] as $item) {
        if (empty($item['product_id']) || empty($item['quantity']) || empty($item['unit_id'])) {
            $json['error'] = $this->language->get('error_item_data');
            $this->sendJSON($json);
            return;
        }
    }

    try {
        $data = array(
            'requisition_id' => (int)$this->request->post['requisition_id'],
            'supplier_id' => (int)$this->request->post['supplier_id'],
            'currency_id' => (int)$this->request->post['currency_id'],
            'exchange_rate' => (float)$this->request->post['exchange_rate'],
            'validity_date' => $this->request->post['validity_date'],
            'payment_terms' => $this->request->post['payment_terms'],
            'delivery_terms' => $this->request->post['delivery_terms'],
            'notes' => $this->request->post['notes'],
            'tax_included' => (int)$this->request->post['tax_included'],
            'items' => $this->request->post['item'],
            'created_by' => $this->user->getId()
        );

        if (!empty($this->request->post['quotation_id'])) {
            $data['quotation_id'] = (int)$this->request->post['quotation_id'];
        }

        // Determine status based on submit type
        $submit_type = isset($this->request->post['submit_type']) ? $this->request->post['submit_type'] : 'draft';
        $data['status'] = ($submit_type === 'submit') ? 'pending' : 'draft';

        $result = $this->model_purchase_quotation->saveQuotation($data);

        if ($result['quotation_id']) {
            $json['success'] = $this->language->get('text_success_save');
            $json['quotation_id'] = $result['quotation_id'];
        } else {
            $json['error'] = $this->language->get('error_saving');
        }

    } catch (Exception $e) {
        $json['error'] = $e->getMessage();
    }

    $this->sendJSON($json);
}

    /**
     * Helper method to send JSON response
     */
    private function sendJSON($data) {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    /**
     * AJAX method to approve quotation
     */
    public function ajaxApprove() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/quotation')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $quotation_id = (int)($this->request->get['quotation_id'] ?? 0);

        if ($quotation_id) {
            $result = $this->model_purchase_quotation->approveQuotation($quotation_id, $this->user->getId());
            if (!empty($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_approve_success');
            }
        } else {
            $json['error'] = 'Missing quotation_id';
        }

        return $this->sendJSON($json);
    }

    /**
     * AJAX method to reject quotation
     */
    public function ajaxReject() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/quotation')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $quotation_id = (int)($this->request->post['quotation_id'] ?? 0);
        $reason = ($this->request->post['reason'] ?? '');

        if ($quotation_id) {
            $result = $this->model_purchase_quotation->rejectQuotation($quotation_id, $this->user->getId(), $reason);
            if (!empty($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_reject_success');
            }
        } else {
            $json['error'] = 'Missing quotation_id';
        }

        return $this->sendJSON($json);
    }

    /**
     * AJAX method to delete quotation
     */
    public function ajaxDelete() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/quotation')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $quotation_id = (int)($this->request->get['quotation_id'] ?? 0);

        if ($quotation_id) {
            $result = $this->model_purchase_quotation->deleteQuotation($quotation_id);
            if (!empty($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_delete_success');
            }
        } else {
            $json['error'] = 'Missing quotation_id';
        }

        return $this->sendJSON($json);
    }
}

