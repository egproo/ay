<?php
class ControllerPurchaseSupplierInvoice extends Controller {
    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);
        // Load necessary models and language files
        $this->load->model('purchase/supplier_invoice'); 
        $this->load->language('purchase/supplier_invoice'); 
        $this->load->model('purchase/order'); // Needed for PO lookup
        $this->load->model('localisation/currency'); // Needed for currencies
        $this->load->model('purchase/supplier'); // Needed for supplier lookup in filters
    }

    public function index() {
        // Basic structure for the list page
        $this->document->setTitle($this->language->get('heading_title'));

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        // Add other common language strings
        $this->load->model('purchase/supplier'); // Load supplier model for filter dropdown
        $data['suppliers'] = $this->model_purchase_supplier->getSuppliers(); // Fetch suppliers for filter

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/supplier_invoice', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Add button (placeholder)
        $data['add'] = $this->url->link('purchase/supplier_invoice/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['button_add'] = $this->language->get('button_add');
        $data['button_delete'] = $this->language->get('button_delete'); // Placeholder

        // Permissions check (basic)
        $data['can_add'] = $this->user->hasPermission('modify', 'purchase/supplier_invoice');
        $data['can_delete'] = $this->user->hasPermission('modify', 'purchase/supplier_invoice');

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
        $data['invoices'] = array();
        $data['pagination'] = '';
        $data['results'] = '';
        
        // Status options for filter
        $data['status_options'] = [
            ['value' => 'pending_approval', 'text' => $this->language->get('text_status_pending_approval')],
            ['value' => 'approved',         'text' => $this->language->get('text_status_approved')],
            ['value' => 'rejected',         'text' => $this->language->get('text_status_rejected')],
            ['value' => 'partially_paid',   'text' => $this->language->get('text_status_partially_paid')],
            ['value' => 'paid',             'text' => $this->language->get('text_status_paid')],
            ['value' => 'cancelled',        'text' => $this->language->get('text_status_cancelled')]
        ];

        // Load common parts
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Load the list view (will create this later)
        $this->response->setOutput($this->load->view('purchase/supplier_invoice_list', $data));
    }

    /**
     * AJAX: جلب قائمة فواتير الموردين
     */
    public function ajaxList() {
        if (!$this->user->hasPermission('access', 'purchase/supplier_invoice')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $this->load->language('purchase/supplier_invoice');

        $json = array();

        $filter_invoice_number = isset($this->request->get['filter_invoice_number']) ? $this->request->get['filter_invoice_number'] : '';
        $filter_po_id = isset($this->request->get['filter_po_id']) ? (int)$this->request->get['filter_po_id'] : 0; // Filter by PO ID
        $filter_po_number = isset($this->request->get['filter_po_number']) ? $this->request->get['filter_po_number'] : ''; // Added PO Number filter
        $filter_supplier_id = isset($this->request->get['filter_supplier_id']) ? (int)$this->request->get['filter_supplier_id'] : 0;
        $filter_status = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';
        $page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : $this->config->get('config_limit_admin');
        $sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'si.invoice_date';
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';

        $filter_data = array(
            'filter_invoice_number' => $filter_invoice_number,
            'filter_po_id'          => $filter_po_id,
            'filter_po_number'      => $filter_po_number, // Pass PO number filter
            'filter_supplier_id'    => $filter_supplier_id,
            'filter_status'         => $filter_status,
            'filter_date_start'     => $filter_date_start,
            'filter_date_end'       => $filter_date_end,
            'sort'                  => $sort,
            'order'                 => $order,
            'start'                 => ($page - 1) * $limit,
            'limit'                 => $limit
        );

        $invoices = $this->model_purchase_supplier_invoice->getInvoices($filter_data);
        $total = $this->model_purchase_supplier_invoice->getTotalInvoices($filter_data);
        
        // $json['stats'] = $this->model_purchase_supplier_invoice->getInvoiceStats($filter_data); // Add stats if needed

        $json['invoices'] = array();
        foreach ($invoices as $invoice) {
            $currency_code = $invoice['currency_code'] ?? $this->config->get('config_currency');
            
            $json['invoices'][] = array(
                'invoice_id'        => $invoice['invoice_id'],
                'invoice_number'    => $invoice['invoice_number'],
                'po_number'         => $invoice['po_number'] ?? '',
                'supplier_name'     => $invoice['supplier_name'],
                'total_formatted'   => $this->currency->format($invoice['total_amount'], $currency_code, $invoice['exchange_rate']),
                'status'            => $invoice['status'],
                'status_text'       => $this->model_purchase_supplier_invoice->getStatusText($invoice['status']),
                'status_class'      => $this->model_purchase_supplier_invoice->getStatusClass($invoice['status']),
                'invoice_date'      => date($this->language->get('date_format_short'), strtotime($invoice['invoice_date'])),
                'due_date'          => $invoice['due_date'] ? date($this->language->get('date_format_short'), strtotime($invoice['due_date'])) : '',
                
                // Permissions
                'can_view'          => $this->user->hasPermission('access', 'purchase/supplier_invoice'),
                'can_edit'          => $this->user->hasPermission('modify', 'purchase/supplier_invoice') && in_array($invoice['status'], ['pending_approval']), // Example: Only edit pending
                'can_delete'        => $this->user->hasPermission('modify', 'purchase/supplier_invoice') && in_array($invoice['status'], ['pending_approval', 'rejected', 'cancelled']), // Corrected allowed delete statuses
                'can_approve'       => $this->user->hasPermission('modify', 'purchase/supplier_invoice') && $invoice['status'] == 'pending_approval',
                'can_reject'        => $this->user->hasPermission('modify', 'purchase/supplier_invoice') && $invoice['status'] == 'pending_approval',
                'can_pay'           => $this->user->hasPermission('modify', 'finance/payment_voucher') && in_array($invoice['status'], ['approved', 'partially_paid']) // Link to payment voucher
            );
        }

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = 'javascript:InvoiceManager.loadInvoices({page});'; // Use JS function for AJAX pagination
        $json['pagination'] = $pagination->render();
        $json['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

        $this->sendJSON($json);
    }

    /**
     * عرض نموذج إضافة/تعديل فاتورة مورد
     */
    public function form() {
        $this->load->language('purchase/supplier_invoice');
        // Load models needed for form data
        $this->load->model('purchase/supplier_invoice');
        $this->load->model('purchase/supplier'); // For supplier list
        $this->load->model('purchase/order'); // For PO list/details
        $this->load->model('localisation/currency');

        $data = array();
        $data['text_form_title'] = $this->language->get('text_add'); // Default title

        $invoice_id = isset($this->request->get['invoice_id']) ? (int)$this->request->get['invoice_id'] : 0;
        $po_id_from_request = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0; // For creating invoice from PO

        $data['mode'] = $invoice_id ? 'edit' : 'add';

        $invoice_info = null;
        $po_info_for_add = null; // To store PO info when adding from PO

        if ($invoice_id) { // Editing existing invoice
            $invoice_info = $this->model_purchase_supplier_invoice->getInvoice($invoice_id);
            if ($invoice_info) {
                $data['text_form_title'] = $this->language->get('text_edit');
                // Check edit permissions based on status if needed
                // if (!in_array($invoice_info['status'], ['pending_approval'])) { ... }
            } else {
                // Handle invoice not found error - maybe redirect or show error in modal
                $this->session->data['error_warning'] = $this->language->get('error_invoice_not_found');
                // Ideally, return an error view or JSON for AJAX loading
                return $this->response->setOutput($this->load->view('error/not_found', $data));
            }
        } elseif ($po_id_from_request) { // Adding new invoice based on PO
             $po_info_for_add = $this->model_purchase_order->getOrder($po_id_from_request);
             if (!$po_info_for_add) {
                 // Handle PO not found if trying to add from non-existent PO
                 $this->session->data['error_warning'] = $this->language->get('error_po_not_found'); // Add this error string
                 return $this->response->setOutput($this->load->view('error/not_found', $data));
             }
        }

        // --- Populate Form Data ---
        $data['invoice_id'] = $invoice_id;
        $data['supplier_id'] = $invoice_info['supplier_id'] ?? ($po_info_for_add ? $po_info_for_add['supplier_id'] : 0);
        $data['invoice_number'] = $invoice_info['invoice_number'] ?? '';
        $data['invoice_date'] = isset($invoice_info['invoice_date']) ? date('Y-m-d', strtotime($invoice_info['invoice_date'])) : date('Y-m-d');
        $data['due_date'] = isset($invoice_info['due_date']) ? date('Y-m-d', strtotime($invoice_info['due_date'])) : date('Y-m-d', strtotime('+30 days')); // Default due date
        $data['po_id'] = $invoice_info['po_id'] ?? $po_id_from_request;
        $data['po_number'] = $invoice_info['po_number'] ?? ($po_info_for_add ? $po_info_for_add['po_number'] : '');
        $data['currency_id'] = $invoice_info['currency_id'] ?? ($po_info_for_add ? $po_info_for_add['currency_id'] : $this->config->get('config_currency_id'));
        $data['exchange_rate'] = $invoice_info['exchange_rate'] ?? ($po_info_for_add ? $po_info_for_add['exchange_rate'] : 1.0);
        $data['notes'] = $invoice_info['notes'] ?? '';
        $data['subtotal'] = $invoice_info['subtotal'] ?? 0;
        $data['tax_amount'] = $invoice_info['tax_amount'] ?? 0;
        $data['total_amount'] = $invoice_info['total_amount'] ?? 0;
        $data['status'] = $invoice_info['status'] ?? 'pending_approval'; // Default status for new

        // --- Fetch Supporting Data ---
        $data['suppliers'] = $this->model_purchase_supplier->getSuppliers(); // Assuming model exists
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        // Fetch items (if editing or creating from PO)
        $data['items'] = array();
        if ($invoice_id) {
            $data['items'] = $this->model_purchase_supplier_invoice->getInvoiceItems($invoice_id);
        } elseif ($po_id_from_request && $po_info_for_add) {
            // Fetch PO items to pre-populate invoice form
            $po_items = $this->model_purchase_supplier_invoice->getPurchaseOrderItemsForInvoice($po_id_from_request);
            foreach ($po_items as $item) {
                 // Only add items with remaining quantity to invoice by default
                 // Also consider items already received but not yet invoiced
                 // This logic might need refinement based on exact requirements for pre-populating
                 if ( (float)$item['remaining_quantity'] > 0) { // Simple check for now
                     $data['items'][] = array(
                         'po_item_id'    => $item['po_item_id'],
                         'product_id'    => $item['product_id'],
                         'product_name'  => $item['product_name'],
                         'quantity'      => $item['remaining_quantity'], // Default to remaining qty
                         'unit_id'       => $item['unit_id'],
                         'unit_name'     => $item['unit_name'],
                         'unit_price'    => $item['unit_price'], // Default to PO price
                         'line_total'    => $item['remaining_quantity'] * $item['unit_price'] // Calculate initial total
                         // Add other fields like tax if needed
                     );
                 }
            }
        }

        // --- Language Strings ---
        $data['entry_supplier'] = $this->language->get('entry_supplier');
        $data['entry_invoice_number'] = $this->language->get('entry_invoice_number');
        $data['entry_invoice_date'] = $this->language->get('entry_invoice_date');
        $data['entry_due_date'] = $this->language->get('entry_due_date');
        $data['entry_purchase_order'] = $this->language->get('entry_purchase_order');
        $data['entry_currency'] = $this->language->get('entry_currency');
        $data['entry_exchange_rate'] = $this->language->get('entry_exchange_rate');
        $data['entry_notes'] = $this->language->get('entry_notes');
        // Add other entry_* strings

        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_action'] = $this->language->get('column_action');
        // Add other column_* strings

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_add_item'] = $this->language->get('button_add_item');
        // Add other button_* strings

        $data['tab_general'] = $this->language->get('tab_general');
        $data['tab_items'] = $this->language->get('tab_items');
        $data['tab_documents'] = $this->language->get('tab_documents');
        $data['tab_totals'] = $this->language->get('tab_totals');
        // Add other tab_* strings

        $data['text_select_supplier'] = $this->language->get('text_select_supplier');
        $data['text_select_po'] = $this->language->get('text_select_po');
        $data['text_no_items'] = $this->language->get('text_no_items');
        // Add other text_* strings

        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('purchase/supplier_invoice_form', $data));
    }

    /**
     * AJAX: حفظ فاتورة مورد (إضافة أو تعديل)
     */
    public function ajaxSave() {
        $this->load->language('purchase/supplier_invoice');
        $this->load->model('purchase/supplier_invoice');
        $json = array();

        // Check permissions
        if (!$this->user->hasPermission('modify', 'purchase/supplier_invoice')) {
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
        if (empty($this->request->post['invoice_number'])) {
             $this->error['invoice_number'] = $this->language->get('error_invoice_number_required');
        }
         if (empty($this->request->post['invoice_date'])) {
             $this->error['invoice_date'] = $this->language->get('error_invoice_date_required');
        }
        if (empty($this->request->post['item'])) {
             $this->error['items'] = $this->language->get('error_items_required');
        } else {
            // Validate items
            foreach ($this->request->post['item'] as $key => $item) {
                if (empty($item['product_id'])) {
                    $this->error['item'][$key]['product'] = 'Product is required.'; // Add specific error lang string if needed
                }
                if (!isset($item['quantity']) || (float)$item['quantity'] <= 0) {
                     $this->error['item'][$key]['quantity'] = 'Quantity must be greater than 0.'; // Add specific error lang string if needed
                }
                 if (!isset($item['unit_price']) || (float)$item['unit_price'] < 0) {
                     $this->error['item'][$key]['unit_price'] = 'Unit price cannot be negative.'; // Add specific error lang string if needed
                }
            }
        }
        // TODO: Add more validation (currency, exchange rate, due date format etc.)

        if ($this->error) {
             $json['error'] = $this->language->get('error_warning');
             $json['errors'] = $this->error; // Send specific field errors back to JS if needed
        } else {
            $invoice_id = isset($this->request->post['invoice_id']) ? (int)$this->request->post['invoice_id'] : 0;
            
            // --- Prepare data for model ---
            $invoice_data = $this->request->post; 
            $invoice_data['user_id'] = $this->user->getId(); 

            // --- Server-side Calculation of Totals ---
            $invoice_data['subtotal'] = 0; 
            $invoice_data['tax_amount'] = 0; // Placeholder for tax calculation
            $invoice_data['total_amount'] = 0;
            
            if (isset($invoice_data['item'])) {
                foreach ($invoice_data['item'] as $item) { 
                    $line_total = (float)$item['quantity'] * (float)$item['unit_price'];
                    $invoice_data['subtotal'] += $line_total;
                    // TODO: Implement tax calculation based on system settings/item tax class
                }
                // Example total calculation (without tax for now)
                $invoice_data['total_amount'] = $invoice_data['subtotal'] + $invoice_data['tax_amount']; 
            }
            // --- End Calculation ---

            if ($invoice_id) {
                // --- Edit Invoice ---
                if ($this->model_purchase_supplier_invoice->editInvoice($invoice_id, $invoice_data)) { 
                    $json['success'] = $this->language->get('text_success_edit');
                    $json['invoice_id'] = $invoice_id;
                    
                    // إرسال إشعار بتحديث الفاتورة
                    $notification_data = array(
                        'title' => $this->language->get('text_invoice_updated_notification_title'),
                        'message' => sprintf($this->language->get('text_invoice_updated_notification_message'), $invoice_data['invoice_number']),
                        'icon' => 'fa-pencil',
                        'color' => 'info',
                        'reference_type' => 'supplier_invoice',
                        'reference_id' => $invoice_id
                    );
                    
                    // إرسال الإشعار للمستخدمين المعنيين (مثل المشرفين)
                    $this->model_common_notification->addNotificationForUserGroup(1, $notification_data); // 1 = Admin group
                } else {
                    $json['error'] = $this->language->get('error_saving'); 
                }
            } else {
                // --- Add Invoice ---
                $new_invoice_id = $this->model_purchase_supplier_invoice->addInvoice($invoice_data);
                if ($new_invoice_id) {
                    $json['success'] = $this->language->get('text_success_add');
                    $json['invoice_id'] = $new_invoice_id;
                    
                    // إرسال إشعار بإضافة فاتورة جديدة
                    $notification_data = array(
                        'title' => $this->language->get('text_invoice_added_notification_title'),
                        'message' => sprintf($this->language->get('text_invoice_added_notification_message'), $invoice_data['invoice_number']),
                        'icon' => 'fa-plus',
                        'color' => 'success',
                        'reference_type' => 'supplier_invoice',
                        'reference_id' => $new_invoice_id
                    );
                    
                    // إرسال الإشعار للمستخدمين المعنيين (مثل المشرفين)
                    $this->model_common_notification->addNotificationForUserGroup(1, $notification_data); // 1 = Admin group
                } else {
                     $json['error'] = $this->language->get('error_saving'); 
                }
            }
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX: Approve a supplier invoice
     */
    public function approve() {
        $this->load->language('purchase/supplier_invoice');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/supplier_invoice')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $invoice_id = isset($this->request->post['invoice_id']) ? (int)$this->request->post['invoice_id'] : 0;

        if (!$invoice_id) {
            $json['error'] = $this->language->get('error_invoice_required');
            return $this->sendJSON($json);
        }

        try {
            $result = $this->model_purchase_supplier_invoice->approveInvoice($invoice_id, $this->user->getId());
            if ($result) {
                $json['success'] = $this->language->get('text_approve_success');
            } else {
                // Model should throw exception on failure, but handle false return just in case
                $json['error'] = $this->language->get('error_approving');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX: Reject a supplier invoice
     */
    public function reject() {
        $this->load->language('purchase/supplier_invoice');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/supplier_invoice')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $invoice_id = isset($this->request->post['invoice_id']) ? (int)$this->request->post['invoice_id'] : 0;
        $reason = isset($this->request->post['reason']) ? trim($this->request->post['reason']) : '';

        if (!$invoice_id) {
            $json['error'] = $this->language->get('error_invoice_required');
            return $this->sendJSON($json);
        }
        
        // Optional: Enforce reason requirement here if needed
        // if (empty($reason)) {
        //     $json['error'] = $this->language->get('error_rejection_reason_required');
        //     return $this->sendJSON($json);
        // }

        try {
            $result = $this->model_purchase_supplier_invoice->rejectInvoice($invoice_id, $reason, $this->user->getId());
            if ($result) {
                $json['success'] = $this->language->get('text_reject_success');
            } else {
                // Model should throw exception on failure
                $json['error'] = $this->language->get('error_rejecting');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX: Delete a supplier invoice
     */
    public function delete() {
        $this->load->language('purchase/supplier_invoice');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/supplier_invoice')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $invoice_id = isset($this->request->post['invoice_id']) ? (int)$this->request->post['invoice_id'] : 0;

        if (!$invoice_id) {
            $json['error'] = $this->language->get('error_invoice_required');
            return $this->sendJSON($json);
        }

        try {
            $result = $this->model_purchase_supplier_invoice->deleteInvoice($invoice_id);
            if ($result) {
                // History is added in the model after successful deletion
                $json['success'] = $this->language->get('text_delete_success');
            } else {
                // Model should throw exception on failure
                $json['error'] = $this->language->get('error_deleting');
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
     * تحديث حالة الفاتورة (الموافقة أو الرفض)
     */
    public function updateStatus() {
        $this->load->language('purchase/supplier_invoice');
        $this->load->model('purchase/supplier_invoice');
        $this->load->model('common/notification');
        
        $json = array();
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'purchase/supplier_invoice')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }
        
        if (isset($this->request->post['invoice_id']) && isset($this->request->post['status'])) {
            $invoice_id = (int)$this->request->post['invoice_id'];
            $status = $this->request->post['status'];
            $comment = isset($this->request->post['comment']) ? $this->request->post['comment'] : '';
            
            // التحقق من صحة الحالة
            $allowed_statuses = array('approved', 'rejected', 'cancelled');
            if (!in_array($status, $allowed_statuses)) {
                $json['error'] = $this->language->get('error_invalid_status');
                return $this->sendJSON($json);
            }
            
            $invoice_info = $this->model_purchase_supplier_invoice->getInvoice($invoice_id);
            
            if (!$invoice_info) {
                $json['error'] = $this->language->get('error_invoice_not_found');
                return $this->sendJSON($json);
            }
            
            // التحقق من أن الحالة الحالية تسمح بالتغيير
            if ($status == 'approved' || $status == 'rejected') {
                if ($invoice_info['status'] != 'pending_approval') {
                    $json['error'] = $this->language->get('error_invalid_status_change');
                    return $this->sendJSON($json);
                }
            } elseif ($status == 'cancelled') {
                if (!in_array($invoice_info['status'], array('pending_approval', 'rejected'))) {
                    $json['error'] = $this->language->get('error_invalid_status_change');
                    return $this->sendJSON($json);
                }
            }
            
            // تحديث الحالة
            $result = $this->model_purchase_supplier_invoice->updateInvoiceStatus($invoice_id, $status, $this->user->getId(), $comment);
            
            if ($result) {
                // إرسال إشعار
                $notification_title = '';
                $notification_message = '';
                $notification_icon = '';
                $notification_color = '';
                
                if ($status == 'approved') {
                    $notification_title = $this->language->get('text_invoice_approved_notification_title');
                    $notification_message = sprintf($this->language->get('text_invoice_approved_notification_message'), $invoice_info['invoice_number'], $invoice_info['supplier_name']);
                    $notification_icon = 'fa-check-circle';
                    $notification_color = 'success';
                } elseif ($status == 'rejected') {
                    $notification_title = $this->language->get('text_invoice_rejected_notification_title');
                    $notification_message = sprintf($this->language->get('text_invoice_rejected_notification_message'), $invoice_info['invoice_number'], $invoice_info['supplier_name']);
                    $notification_icon = 'fa-times-circle';
                    $notification_color = 'danger';
                } elseif ($status == 'cancelled') {
                    $notification_title = $this->language->get('text_invoice_cancelled_notification_title');
                    $notification_message = sprintf($this->language->get('text_invoice_cancelled_notification_message'), $invoice_info['invoice_number'], $invoice_info['supplier_name']);
                    $notification_icon = 'fa-ban';
                    $notification_color = 'warning';
                }
                
                $notification_data = array(
                    'title' => $notification_title,
                    'message' => $notification_message,
                    'icon' => $notification_icon,
                    'color' => $notification_color,
                    'reference_type' => 'supplier_invoice',
                    'reference_id' => $invoice_id
                );
                
                // إرسال الإشعار للمستخدم الذي أنشأ الفاتورة
                if (isset($invoice_info['created_by'])) {
                    $this->model_common_notification->addNotificationForUser($invoice_info['created_by'], $notification_data);
                }
                
                $json['success'] = $this->language->get('text_status_updated');
                $json['status_text'] = $this->model_purchase_supplier_invoice->getStatusText($status);
                $json['status_class'] = $this->model_purchase_supplier_invoice->getStatusClass($status);
            } else {
                $json['error'] = $this->language->get('error_update_failed');
            }
        } else {
            $json['error'] = $this->language->get('error_missing_data');
        }
        
        $this->sendJSON($json);
    }
    
    /**
     * AJAX: جلب بنود أمر الشراء لإنشاء فاتورة
     */
    public function ajaxGetPoItems() {
        $json = array('items' => []);
        $po_id = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0;

        if ($po_id && $this->user->hasPermission('access', 'purchase/supplier_invoice')) {
             $this->load->model('purchase/supplier_invoice');
             $json['items'] = $this->model_purchase_supplier_invoice->getPurchaseOrderItemsForInvoice($po_id);
        } else {
            $json['error'] = $this->language->get('error_permission'); // Or PO not found error
        }
        
        $this->sendJSON($json);
    }

    /**
     * View details of a supplier invoice
     */
    public function view() {
        $this->load->language('purchase/supplier_invoice');

        if (!$this->user->hasPermission('access', 'purchase/supplier_invoice')) {
            return $this->load->view('error/permission', []);
        }

        $invoice_id = isset($this->request->get['invoice_id']) ? (int)$this->request->get['invoice_id'] : 0;

        if (!$invoice_id) {
            return $this->load->view('error/not_found', []);
        }

        // Get invoice data
        $invoice_info = $this->model_purchase_supplier_invoice->getInvoice($invoice_id);

        if (!$invoice_info) {
            return $this->load->view('error/not_found', []);
        }

        $data = array();
        $data['text_invoice_details'] = $this->language->get('text_invoice_details');

        // Format invoice data
        $currency_code = $invoice_info['currency_code'] ?? $this->config->get('config_currency');
        $data['invoice'] = [
            'invoice_id'        => $invoice_info['invoice_id'],
            'invoice_number'    => $invoice_info['invoice_number'],
            'po_id'             => $invoice_info['po_id'],
            'po_number'         => $invoice_info['po_number'] ?? '',
            'supplier_name'     => $invoice_info['supplier_name'],
            'currency_code'     => $currency_code,
            'subtotal'          => $this->currency->format($invoice_info['subtotal'], $currency_code, $invoice_info['exchange_rate']),
            'tax_amount'        => $this->currency->format($invoice_info['tax_amount'], $currency_code, $invoice_info['exchange_rate']),
            'total_amount'      => $this->currency->format($invoice_info['total_amount'], $currency_code, $invoice_info['exchange_rate']),
            'status'            => $invoice_info['status'],
            'status_text'       => $this->model_purchase_supplier_invoice->getStatusText($invoice_info['status']),
            'status_class'      => $this->model_purchase_supplier_invoice->getStatusClass($invoice_info['status']),
            'invoice_date'      => date($this->language->get('date_format_short'), strtotime($invoice_info['invoice_date'])),
            'due_date'          => $invoice_info['due_date'] ? date($this->language->get('date_format_short'), strtotime($invoice_info['due_date'])) : '',
            'notes'             => nl2br($invoice_info['notes']),
            'created_at'        => date($this->language->get('datetime_format'), strtotime($invoice_info['created_at'])),
            'created_by_name'   => $this->model_purchase_supplier_invoice->getUserName($invoice_info['created_by']), // Assuming getUserName exists
            'journal_id'        => $invoice_info['journal_id'] ?? null
        ];

        // Get invoice items
        $data['items'] = $this->model_purchase_supplier_invoice->getInvoiceItems($invoice_id);
        foreach ($data['items'] as &$item) {
            $item['unit_price_formatted'] = $this->currency->format($item['unit_price'], $currency_code, $invoice_info['exchange_rate']);
            $item['line_total_formatted'] = $this->currency->format($item['line_total'], $currency_code, $invoice_info['exchange_rate']);
        }
        unset($item); // Unset reference

        // Get history
        $data['history'] = $this->model_purchase_supplier_invoice->getInvoiceHistory($invoice_id); // Assuming this function exists now

        // Get documents
        $data['documents'] = $this->model_purchase_supplier_invoice->getDocuments($invoice_id); // Assuming this function exists

        // Permissions for view actions
        $data['can_edit'] = $this->user->hasPermission('modify', 'purchase/supplier_invoice') && in_array($invoice_info['status'], ['pending_approval']);
        $data['can_delete'] = $this->user->hasPermission('modify', 'purchase/supplier_invoice') && in_array($invoice_info['status'], ['pending_approval', 'rejected']);
        $data['can_approve'] = $this->user->hasPermission('modify', 'purchase/supplier_invoice') && $invoice_info['status'] == 'pending_approval';
        $data['can_reject'] = $this->user->hasPermission('modify', 'purchase/supplier_invoice') && $invoice_info['status'] == 'pending_approval';
        $data['can_pay'] = $this->user->hasPermission('modify', 'finance/payment_voucher') && in_array($invoice_info['status'], ['approved', 'partially_paid']);
        $data['can_print'] = $this->user->hasPermission('access', 'purchase/supplier_invoice'); // Assuming view implies print
        $data['can_upload'] = $this->user->hasPermission('modify', 'purchase/supplier_invoice');
        $data['can_download'] = $this->user->hasPermission('access', 'purchase/supplier_invoice');

        // Language strings for the view template
        $data['text_invoice_view'] = $this->language->get('text_invoice_view');
        $data['text_invoice_details'] = $this->language->get('text_invoice_details');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_invoice_number'] = $this->language->get('text_invoice_number');
        $data['text_invoice_date'] = $this->language->get('text_invoice_date');
        $data['text_due_date'] = $this->language->get('text_due_date');
        $data['text_purchase_order'] = $this->language->get('text_purchase_order');
        $data['text_currency'] = $this->language->get('text_currency');
        $data['text_status'] = $this->language->get('text_status');
        $data['text_notes'] = $this->language->get('text_notes');
        $data['text_items'] = $this->language->get('text_items');
        $data['text_history'] = $this->language->get('text_history');
        $data['text_documents'] = $this->language->get('text_documents');
        $data['text_subtotal'] = $this->language->get('text_subtotal');
        $data['text_tax'] = $this->language->get('text_tax');
        $data['text_total'] = $this->language->get('text_total');
        $data['text_created_by'] = $this->language->get('text_created_by');
        $data['text_date_added'] = $this->language->get('text_date_added');
        $data['text_journal_entry'] = $this->language->get('text_journal_entry');
        $data['text_no_items'] = $this->language->get('text_no_items');
        $data['text_no_history'] = $this->language->get('text_no_history');
        $data['text_no_documents'] = $this->language->get('text_no_documents');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
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
        $data['button_pay'] = $this->language->get('button_pay');
        $data['button_print'] = $this->language->get('button_print');
        $data['button_upload'] = $this->language->get('button_upload');
        $data['button_download'] = $this->language->get('button_download');
        $data['button_close'] = $this->language->get('button_close'); // For modal

        $data['user_token'] = $this->session->data['user_token'];

        // Load the view template (needs creation)
        $this->response->setOutput($this->load->view('purchase/supplier_invoice_view', $data));
    }

    /**
     * AJAX: Upload a document
     */
    public function ajaxUploadDocument() {
        $this->load->language('purchase/supplier_invoice');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/supplier_invoice')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $invoice_id = isset($this->request->post['invoice_id']) ? (int)$this->request->post['invoice_id'] : 0;
        if (!$invoice_id) {
            $json['error'] = $this->language->get('error_invoice_required');
            return $this->sendJSON($json);
        }

        if (!isset($this->request->files['file']) || !$this->request->files['file']['tmp_name']) {
            $json['error'] = $this->language->get('error_file_required');
            return $this->sendJSON($json);
        }

        try {
            $upload_info = $this->model_purchase_supplier_invoice->uploadDocument(
                $invoice_id, 
                $this->request->files['file'], 
                $this->request->post['document_type'] ?? 'invoice_attachment', // Default type
                $this->user->getId()
            );
            
            // Prepare data for the view (similar to getDocumentsWithPermissions)
            $fileExt = strtolower(pathinfo($upload_info['path'], PATHINFO_EXTENSION));
            $upload_info['icon_class'] = $this->model_purchase_supplier_invoice->getFileIconClass($fileExt);
            $upload_info['preview_possible'] = in_array($fileExt, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp']);
            $upload_info['uploaded_by_name'] = $this->model_purchase_supplier_invoice->getUserName($this->user->getId()); // Get current user name
            $upload_info['upload_date'] = date($this->language->get('date_format_short'), strtotime($upload_info['upload_date'])); // Format date

            $json['success'] = $this->language->get('text_upload_success');
            $json['document'] = $upload_info; // Send back the formatted document info

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX: Delete a document
     */
    public function ajaxDeleteDocument() {
        $this->load->language('purchase/supplier_invoice');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/supplier_invoice')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $document_id = isset($this->request->post['document_id']) ? (int)$this->request->post['document_id'] : 0;

        if (!$document_id) {
            $json['error'] = $this->language->get('error_document_required');
            return $this->sendJSON($json);
        }

        try {
            $result = $this->model_purchase_supplier_invoice->deleteDocument($document_id);
            if ($result) {
                $json['success'] = $this->language->get('text_delete_success');
            } else {
                $json['error'] = $this->language->get('error_deleting_document');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX: Get documents for an invoice
     */
    public function getDocuments() {
        $this->load->language('purchase/supplier_invoice');
        $json = array();

        if (!$this->user->hasPermission('access', 'purchase/supplier_invoice')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $invoice_id = isset($this->request->get['invoice_id']) ? (int)$this->request->get['invoice_id'] : 0;

        if (!$invoice_id) {
            $json['error'] = $this->language->get('error_invoice_required');
            return $this->sendJSON($json);
        }

        $result = $this->model_purchase_supplier_invoice->getDocumentsWithPermissions($invoice_id);
        
        // Format dates for display
        foreach ($result['documents'] as &$doc) {
             $doc['upload_date'] = date($this->language->get('date_format_short'), strtotime($doc['upload_date']));
        }
        unset($doc);

        $json = $result;
        $this->sendJSON($json);
    }

    /**
     * Download a document
     */
    public function downloadDocument() {
        if (!$this->user->hasPermission('access', 'purchase/supplier_invoice')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $document_id = isset($this->request->get['document_id']) ? (int)$this->request->get['document_id'] : 0;

        if (!$document_id) {
            $this->session->data['error_warning'] = $this->language->get('error_document_required');
            $this->response->redirect($this->url->link('purchase/supplier_invoice', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $document_info = $this->model_purchase_supplier_invoice->getDocument($document_id);

        if (!$document_info || !file_exists(DIR_UPLOAD . $document_info['file_path']) || !is_file(DIR_UPLOAD . $document_info['file_path'])) {
            $this->session->data['error_warning'] = $this->language->get('error_file_not_found');
            // Redirect back to the invoice view or list page
            $redirect_url = $this->url->link('purchase/supplier_invoice', 'user_token=' . $this->session->data['user_token'], true);
            if(isset($document_info['reference_id'])) { // Try redirecting to the specific invoice if possible
                 $redirect_url = $this->url->link('purchase/supplier_invoice/view', 'user_token=' . $this->session->data['user_token'] . '&invoice_id=' . $document_info['reference_id'], true);
            }
            $this->response->redirect($redirect_url);
            return;
        }

        $file = DIR_UPLOAD . $document_info['file_path'];
        $mask = basename($document_info['document_name']); // Use original filename for download

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $mask . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        readfile($file);
        exit();
    }

    /**
     * Preview a document (image/pdf)
     */
    public function previewDocument() {
         if (!$this->user->hasPermission('access', 'purchase/supplier_invoice')) {
            // Output an error message or image placeholder if needed for AJAX context
            header("HTTP/1.1 403 Forbidden");
            echo $this->language->get('error_permission');
            exit;
        }

        $document_id = isset($this->request->get['document_id']) ? (int)$this->request->get['document_id'] : 0;
        $thumbnail = isset($this->request->get['thumb']) && $this->request->get['thumb'] == '1';

        if (!$document_id) {
             header("HTTP/1.1 404 Not Found");
             echo $this->language->get('error_document_required');
             exit;
        }

        try {
            $result = $this->model_purchase_supplier_invoice->previewDocument($document_id, $thumbnail);
            if (!$result) {
                 header("HTTP/1.1 404 Not Found");
                 echo $this->language->get('error_preview_unavailable'); // Add this lang string
                 exit;
            }
            // If previewDocument outputs directly, we just exit here
            exit;
        } catch (Exception $e) {
             header("HTTP/1.1 500 Internal Server Error");
             echo "Error generating preview: " . $e->getMessage(); // Show error for debugging
             exit;
        }
    }

    // TODO: Add other necessary functions (print, export, etc.)
}
