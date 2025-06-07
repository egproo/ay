<?php
class ControllerPurchaseVendorPayment extends Controller {
    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);
        // Load necessary models and language files
        $this->load->model('purchase/vendor_payment'); // Uncommented model load
        $this->load->language('purchase/vendor_payment'); 
        $this->load->model('purchase/supplier'); // Needed for supplier lookup
        $this->load->model('purchase/supplier_invoice'); // Needed for invoice lookup
        $this->load->model('finance/payment_method'); // Assuming payment methods are here
        $this->load->model('localisation/currency'); 
    }

    public function index() {
        // Basic structure for the list page
        $this->document->setTitle($this->language->get('heading_title'));

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        // Add other common language strings

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/vendor_payment', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Add button (placeholder)
        $data['add'] = $this->url->link('purchase/vendor_payment/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['button_add'] = $this->language->get('button_add');
        $data['button_delete'] = $this->language->get('button_delete'); // Placeholder

        // Permissions check (basic)
        $data['can_add'] = $this->user->hasPermission('modify', 'purchase/vendor_payment');
        $data['can_delete'] = $this->user->hasPermission('modify', 'purchase/vendor_payment');

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
        $data['payments'] = array();
        $data['pagination'] = '';
        $data['results'] = '';

        // Load common parts
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Load the list view (will create this later)
        $this->response->setOutput($this->load->view('purchase/vendor_payment_list', $data));
    }

    /**
     * AJAX: جلب قائمة مدفوعات الموردين
     */
    public function ajaxList() {
        if (!$this->user->hasPermission('access', 'purchase/vendor_payment')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $this->load->language('purchase/vendor_payment');
        $this->load->model('purchase/vendor_payment'); // Ensure model is loaded

        $json = array();

        // Filters
        $filter_payment_id = isset($this->request->get['filter_payment_id']) ? $this->request->get['filter_payment_id'] : '';
        $filter_supplier_id = isset($this->request->get['filter_supplier_id']) ? (int)$this->request->get['filter_supplier_id'] : 0;
        $filter_payment_method_id = isset($this->request->get['filter_payment_method_id']) ? (int)$this->request->get['filter_payment_method_id'] : 0;
        $filter_status = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';
        $page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : $this->config->get('config_limit_admin');
        $sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'vp.payment_date'; // Default sort
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';

        $filter_data = array(
            'filter_payment_id'        => $filter_payment_id,
            'filter_supplier_id'       => $filter_supplier_id,
            'filter_payment_method_id' => $filter_payment_method_id,
            'filter_status'            => $filter_status,
            'filter_date_start'        => $filter_date_start,
            'filter_date_end'          => $filter_date_end,
            'sort'                     => $sort,
            'order'                    => $order,
            'start'                    => ($page - 1) * $limit,
            'limit'                    => $limit
        );

        $payments = $this->model_purchase_vendor_payment->getPayments($filter_data);
        $total = $this->model_purchase_vendor_payment->getTotalPayments($filter_data);

        $json['payments'] = array();
        foreach ($payments as $payment) {
             $currency_code = $payment['currency_code'] ?? $this->config->get('config_currency');
             
            $json['payments'][] = array(
                'payment_id'         => $payment['payment_id'],
                'payment_date'       => date($this->language->get('date_format_short'), strtotime($payment['payment_date'])),
                'supplier_name'      => $payment['supplier_name'],
                'payment_method_name'=> $payment['payment_method_name'] ?? 'N/A', // Handle if method name is missing
                'amount'             => $payment['amount'],
                'amount_formatted'   => $this->currency->format($payment['amount'], $currency_code, $payment['exchange_rate'] ?? 1.0),
                'reference'          => $payment['reference'] ?? '',
                'status'             => $payment['status'] ?? 'draft', // Assuming a status field
                'status_text'        => $this->model_purchase_vendor_payment->getStatusText($payment['status'] ?? 'draft'),
                'status_class'       => $this->model_purchase_vendor_payment->getStatusClass($payment['status'] ?? 'draft'),
                
                // Permissions (adjust based on actual status logic)
                'can_view'           => $this->user->hasPermission('access', 'purchase/vendor_payment'),
                'can_edit'           => $this->user->hasPermission('modify', 'purchase/vendor_payment') && ($payment['status'] ?? 'draft') == 'draft', 
                'can_delete'         => $this->user->hasPermission('modify', 'purchase/vendor_payment') && ($payment['status'] ?? 'draft') == 'draft' 
            );
        }

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = 'javascript:PaymentManager.loadPayments({page});'; // Use JS function
        $json['pagination'] = $pagination->render();
        $json['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

        $this->sendJSON($json);
    }

    /**
     * عرض نموذج إضافة/تعديل دفعة مورد
     */
    public function form() {
        $this->load->language('purchase/vendor_payment');
        // Load models needed for form data
        $this->load->model('purchase/vendor_payment');
        $this->load->model('purchase/supplier'); 
        $this->load->model('localisation/currency');
        // Assuming payment methods are handled elsewhere or need a dedicated model
        // $this->load->model('finance/payment_method'); 

        $data = array();
        $data['text_form_title'] = $this->language->get('text_add'); // Default title

        $payment_id = isset($this->request->get['payment_id']) ? (int)$this->request->get['payment_id'] : 0;
        
        // Determine mode (add/edit)
        $data['mode'] = $payment_id ? 'edit' : 'add';

        $payment_info = null;
        if ($payment_id) { // Editing existing payment
            $payment_info = $this->model_purchase_vendor_payment->getPayment($payment_id); // Assuming this function exists
            if ($payment_info) {
                $data['text_form_title'] = $this->language->get('text_edit');
                // TODO: Add check for editable status if needed
                // if (!in_array($payment_info['status'], ['draft'])) { ... }
            } else {
                $this->session->data['error_warning'] = $this->language->get('error_payment_not_found');
                return $this->response->setOutput($this->load->view('error/not_found', $data)); // Basic error handling
            }
        }

        // --- Populate Form Data ---
        $data['payment_id'] = $payment_id;
        $data['supplier_id'] = $payment_info['supplier_id'] ?? 0;
        $data['payment_date'] = isset($payment_info['payment_date']) ? date('Y-m-d', strtotime($payment_info['payment_date'])) : date('Y-m-d');
        $data['payment_method_id'] = $payment_info['payment_method_id'] ?? 0;
        $data['amount'] = $payment_info['amount'] ?? '';
        $data['currency_id'] = $payment_info['currency_id'] ?? $this->config->get('config_currency_id');
        $data['reference'] = $payment_info['reference'] ?? '';
        $data['notes'] = $payment_info['notes'] ?? '';
        $data['status'] = $payment_info['status'] ?? 'draft'; // Default status for new

        // --- Fetch Supporting Data ---
        $data['suppliers'] = $this->model_purchase_supplier->getSuppliers();
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();
        // Fetch payment methods - Assuming a simple array for now, replace with model call later
        $data['payment_methods'] = $this->model_purchase_vendor_payment->getPaymentMethods(); 
        
        // Get current currency format details for JS formatting
        $currency_info = $this->model_localisation_currency->getCurrency($data['currency_id']);
        $data['currency_format'] = [
            'symbol_left'   => $currency_info['symbol_left'],
            'symbol_right'  => $currency_info['symbol_right'],
            'decimal_place' => $currency_info['decimal_place']
        ];


        // --- Language Strings ---
        $data['entry_supplier'] = $this->language->get('entry_supplier');
        $data['entry_payment_date'] = $this->language->get('entry_payment_date');
        $data['entry_payment_method'] = $this->language->get('entry_payment_method');
        $data['entry_amount'] = $this->language->get('entry_amount');
        $data['entry_currency'] = $this->language->get('entry_currency');
        $data['entry_reference'] = $this->language->get('entry_reference');
        $data['entry_notes'] = $this->language->get('entry_notes');
        
        $data['column_invoice_number'] = $this->language->get('column_invoice_number');
        $data['column_invoice_date'] = $this->language->get('column_invoice_date');
        $data['column_invoice_total'] = $this->language->get('column_invoice_total');
        $data['column_amount_due'] = $this->language->get('column_amount_due');
        $data['column_payment_amount'] = $this->language->get('column_payment_amount');
        
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        
        $data['text_select_supplier'] = $this->language->get('text_select_supplier');
        $data['text_select'] = $this->language->get('text_select');
        $data['text_apply_to_invoices'] = $this->language->get('text_apply_to_invoices');
        $data['text_select_supplier_first'] = $this->language->get('text_select_supplier_first');
        $data['text_total_applied'] = $this->language->get('text_total_applied');
        $data['text_unapplied_amount'] = $this->language->get('text_unapplied_amount');
        $data['text_reference_placeholder'] = $this->language->get('text_reference_placeholder');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['error_ajax'] = $this->language->get('error_ajax'); // Pass AJAX error message
        $data['error_payment_exceeds_due'] = $this->language->get('error_payment_exceeds_due'); // Pass validation message

        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('purchase/vendor_payment_form', $data));
    }

    /**
     * AJAX: Save vendor payment (add/edit)
     */
    public function ajaxSave() {
        $this->load->language('purchase/vendor_payment');
        $this->load->model('purchase/vendor_payment');
        $json = array();

        // Check permissions
        if (!$this->user->hasPermission('modify', 'purchase/vendor_payment')) {
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
        if (empty($this->request->post['payment_date'])) {
             $this->error['payment_date'] = $this->language->get('error_payment_date_required');
        }
        if (empty($this->request->post['payment_method_id'])) {
             $this->error['payment_method'] = $this->language->get('error_payment_method_required');
        }
         if (!isset($this->request->post['amount']) || (float)$this->request->post['amount'] <= 0) {
             $this->error['amount'] = $this->language->get('error_amount_required');
        }
        if (empty($this->request->post['currency_id'])) {
             $this->error['currency'] = $this->language->get('error_currency_required');
        }

        // Validate invoice allocations
        $total_applied = 0;
        if (!empty($this->request->post['invoice_payment'])) {
            foreach ($this->request->post['invoice_payment'] as $key => $payment_allocation) {
                $applied_amount = isset($payment_allocation['amount']) ? (float)$payment_allocation['amount'] : 0;
                if ($applied_amount < 0) {
                     $this->error['invoice_payment'][$key]['amount'] = $this->language->get('error_negative_payment');
                } elseif ($applied_amount > 0 && empty($payment_allocation['invoice_id'])) {
                     $this->error['invoice_payment'][$key]['invoice'] = $this->language->get('error_invoice_id_required');
                }
                $total_applied += $applied_amount;
            }
            
            // Check if total applied amount exceeds total payment amount
            if (round($total_applied, 4) > round((float)$this->request->post['amount'], 4) + 0.0001) { // Allow small rounding diff
                 $this->error['amount_applied'] = $this->language->get('error_applied_exceeds_payment');
            }
        }
        // TODO: Add more validation if needed (e.g., reference format)

        if ($this->error) {
             $json['error'] = $this->language->get('error_warning');
             $json['errors'] = $this->error; // Send specific field errors back to JS
        } else {
            $payment_id = isset($this->request->post['payment_id']) ? (int)$this->request->post['payment_id'] : 0;
            
            // --- Prepare data for model ---
            $payment_data = $this->request->post; 
            $payment_data['user_id'] = $this->user->getId(); 
            
            // Rename invoice_payment to invoice_payments for clarity in model if desired
            if(isset($payment_data['invoice_payment'])) {
                $payment_data['invoice_payments'] = $payment_data['invoice_payment'];
                unset($payment_data['invoice_payment']);
            }

            if ($payment_id) {
                // --- Edit Payment ---
                // Editing posted payments is often restricted or requires reversal.
                // For now, assume editing is only for draft status or limited fields.
                // $result = $this->model_purchase_vendor_payment->editPayment($payment_id, $payment_data);
                // if ($result) { 
                //     $json['success'] = $this->language->get('text_success_edit');
                //     $json['payment_id'] = $payment_id;
                // } else {
                //     $json['error'] = $this->language->get('error_saving'); 
                // }
                 $json['error'] = 'Editing payments is not yet implemented.'; // Placeholder error
            } else {
                // --- Add Payment ---
                try {
                    $new_payment_id = $this->model_purchase_vendor_payment->addPayment($payment_data);
                    if ($new_payment_id) {
                        $json['success'] = $this->language->get('text_success_add');
                        $json['payment_id'] = $new_payment_id;
                    } else {
                         $json['error'] = $this->language->get('error_saving'); 
                    }
                } catch (Exception $e) {
                     $json['error'] = $e->getMessage(); // Display specific error from model
                }
            }
        }

        $this->sendJSON($json);
    }

    /**
     * AJAX: Delete a vendor payment
     */
    public function delete() {
        $this->load->language('purchase/vendor_payment');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/vendor_payment')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $payment_id = isset($this->request->post['payment_id']) ? (int)$this->request->post['payment_id'] : 0;

        if (!$payment_id) {
            $json['error'] = $this->language->get('error_payment_required'); // Add this lang string
            return $this->sendJSON($json);
        }

        try {
            // Note: The model's deletePayment currently doesn't reverse journal/invoice updates.
            // Add warnings or prevent deletion based on status if needed here or in model.
            $result = $this->model_purchase_vendor_payment->deletePayment($payment_id);
            if ($result) {
                $json['success'] = $this->language->get('text_delete_success');
            } else {
                // Model should throw exception on failure
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

    // TODO: Add other necessary functions (view, approve/post, print, etc.)
}
