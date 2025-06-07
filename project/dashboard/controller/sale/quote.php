<?php
class ControllerSaleQuote extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('sale/quote');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('sale/quote');
        
        $this->getList();
    }
    
    public function add() {
        $this->load->language('sale/quote');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('sale/quote');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $quote_id = $this->model_sale_quote->addQuote($this->request->post);
            
            // Add activity log
            $this->user->logActivity('create', 'sale', 'تم إنشاء عرض سعر جديد #' . $quote_id, 'quote', $quote_id);
            
            $this->session->data['success'] = $this->language->get('text_success_add');
            
            $this->response->redirect($this->url->link('sale/quote/edit', 'user_token=' . $this->session->data['user_token'] . '&quote_id=' . $quote_id, true));
        }
        
        $this->getForm();
    }
    
    public function edit() {
        $this->load->language('sale/quote');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('sale/quote');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $quote_id = $this->request->get['quote_id'];
            $this->model_sale_quote->editQuote($quote_id, $this->request->post);
            
            // Add activity log
            $this->user->logActivity('update', 'sale', 'تم تحديث عرض سعر #' . $quote_id, 'quote', $quote_id);
            
            $this->session->data['success'] = $this->language->get('text_success_edit');
            
            $this->response->redirect($this->url->link('sale/quote/edit', 'user_token=' . $this->session->data['user_token'] . '&quote_id=' . $quote_id, true));
        }
        
        $this->getForm();
    }
    
    public function delete() {
        $this->load->language('sale/quote');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('sale/quote');
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $quote_id) {
                $this->model_sale_quote->deleteQuote($quote_id);
                
                // Add activity log
                $this->user->logActivity('delete', 'sale', 'تم حذف عرض سعر #' . $quote_id, 'quote', $quote_id);
            }
            
            $this->session->data['success'] = $this->language->get('text_success_delete');
            
            $this->response->redirect($this->url->link('sale/quote', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getList();
    }
    
    public function view() {
        $this->load->language('sale/quote');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('sale/quote');
        
        if (isset($this->request->get['quote_id'])) {
            $quote_id = $this->request->get['quote_id'];
        } else {
            $quote_id = 0;
        }
        
        $quote_info = $this->model_sale_quote->getQuote($quote_id);
        
        if ($quote_info) {
            $this->getQuoteView($quote_info);
        } else {
            $this->session->data['error'] = $this->language->get('error_not_found');
            $this->response->redirect($this->url->link('sale/quote', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    public function print() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        if (isset($this->request->get['quote_id'])) {
            $quote_id = $this->request->get['quote_id'];
        } else {
            $quote_id = 0;
        }
        
        $quote_info = $this->model_sale_quote->getQuote($quote_id);
        
        if ($quote_info) {
            $this->getPrintView($quote_info);
        } else {
            $this->session->data['error'] = $this->language->get('error_not_found');
            $this->response->redirect($this->url->link('sale/quote', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    public function convertToOrder() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        if (isset($this->request->get['quote_id'])) {
            $quote_id = $this->request->get['quote_id'];
        } else {
            $quote_id = 0;
        }
        
        $quote_info = $this->model_sale_quote->getQuote($quote_id);
        
        if ($quote_info) {
            if ($quote_info['status'] != 'approved') {
                $this->session->data['error'] = $this->language->get('error_quote_not_approved');
                $this->response->redirect($this->url->link('sale/quote/view', 'user_token=' . $this->session->data['user_token'] . '&quote_id=' . $quote_id, true));
            }
            
            if ($quote_info['converted_to_order']) {
                $this->session->data['error'] = $this->language->get('error_already_converted');
                $this->response->redirect($this->url->link('sale/quote/view', 'user_token=' . $this->session->data['user_token'] . '&quote_id=' . $quote_id, true));
            }
            
            // Convert quote to order
            $order_id = $this->model_sale_quote->convertToOrder($quote_id);
            
            if ($order_id) {
                // Add activity log
                $this->user->logActivity('convert', 'sale', 'تم تحويل عرض سعر #' . $quote_id . ' إلى طلب #' . $order_id, 'quote', $quote_id);
                
                $this->session->data['success'] = sprintf($this->language->get('text_success_convert'), $order_id);
                $this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true));
            } else {
                $this->session->data['error'] = $this->language->get('error_convert_failed');
                $this->response->redirect($this->url->link('sale/quote/view', 'user_token=' . $this->session->data['user_token'] . '&quote_id=' . $quote_id, true));
            }
        } else {
            $this->session->data['error'] = $this->language->get('error_not_found');
            $this->response->redirect($this->url->link('sale/quote', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    public function sendEmail() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (isset($this->request->get['quote_id'])) {
            $quote_id = $this->request->get['quote_id'];
        } else {
            $quote_id = 0;
        }
        
        $quote_info = $this->model_sale_quote->getQuote($quote_id);
        
        if ($quote_info) {
            if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
                try {
                    // Send email code
                    $this->load->model('customer/customer');
                    $customer_info = $this->model_customer_customer->getCustomer($quote_info['customer_id']);
                    
                    if (!$customer_info['email']) {
                        throw new Exception($this->language->get('error_customer_email'));
                    }
                    
                    // Generate PDF
                    require_once(DIR_SYSTEM . 'library/pdf/tcpdf/tcpdf.php');
                    
                    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
                    $pdf->SetCreator(utf8_decode($this->config->get('config_name')));
                    $pdf->SetAuthor(utf8_decode($this->config->get('config_name')));
                    $pdf->SetTitle(utf8_decode('Quote #' . $quote_info['quotation_number']));
                    $pdf->SetSubject(utf8_decode('Quote #' . $quote_info['quotation_number']));
                    $pdf->SetKeywords(utf8_decode('quote, sale, ' . $this->config->get('config_name')));
                    
                    $pdf->setHeaderFont(Array('dejavusans', '', 10));
                    $pdf->setFooterFont(Array('dejavusans', '', 8));
                    $pdf->SetDefaultMonospacedFont('courier');
                    $pdf->SetMargins(15, 15, 15);
                    $pdf->SetHeaderMargin(5);
                    $pdf->SetFooterMargin(10);
                    $pdf->SetAutoPageBreak(TRUE, 25);
                    
                    $pdf->SetFont('dejavusans', '', 10);
                    $pdf->AddPage();
                    
                    // Get the HTML content
                    $html = $this->getPrintViewHtml($quote_info);
                    
                    // Convert relative image paths to full paths
                    $html = str_replace('src="image/', 'src="' . DIR_IMAGE, $html);
                    
                    // Write the HTML content to the PDF
                    $pdf->writeHTML($html, true, false, true, false, '');
                    
                    // Save the PDF to a temporary file
                    $pdf_file = DIR_DOWNLOAD . 'quote_' . $quote_id . '.pdf';
                    $pdf->Output($pdf_file, 'F');
                    
                    // Send email with PDF attachment
                    $subject = $this->request->post['subject'];
                    $message = $this->request->post['message'];
                    
                    $mail = new Mail($this->config->get('config_mail_engine'));
                    $mail->parameter = $this->config->get('config_mail_parameter');
                    $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                    $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                    $mail->smtp_password = $this->config->get('config_mail_smtp_password');
                    $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                    $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
                    
                    $mail->setTo($customer_info['email']);
                    $mail->setFrom($this->config->get('config_email'));
                    $mail->setSender(utf8_decode($this->config->get('config_name')));
                    $mail->setSubject(utf8_decode($subject));
                    $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                    $mail->addAttachment($pdf_file, 'quote_' . $quote_info['quotation_number'] . '.pdf');
                    $mail->send();
                    
                    // Delete temporary file
                    if (file_exists($pdf_file)) {
                        unlink($pdf_file);
                    }
                    
                    // Update quote with email sent date
                    $this->model_sale_quote->updateQuoteEmailSent($quote_id);
                    
                    // Add activity log
                    $this->user->logActivity('email', 'sale', 'تم إرسال عرض سعر #' . $quote_id . ' بالبريد الإلكتروني', 'quote', $quote_id);
                    
                    $json['success'] = $this->language->get('text_email_success');
                } catch (Exception $e) {
                    $json['error'] = $e->getMessage();
                }
            } else {
                $json['error'] = $this->language->get('error_email_data');
            }
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function approve() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (isset($this->request->get['quote_id'])) {
            $quote_id = $this->request->get['quote_id'];
        } else {
            $quote_id = 0;
        }
        
        $quote_info = $this->model_sale_quote->getQuote($quote_id);
        
        if ($quote_info) {
            if ($quote_info['status'] == 'approved') {
                $json['error'] = $this->language->get('error_already_approved');
            } else {
                // Update quote status to approved
                $this->model_sale_quote->updateQuoteStatus($quote_id, 'approved');
                
                // Add activity log
                $this->user->logActivity('approve', 'sale', 'تم اعتماد عرض سعر #' . $quote_id, 'quote', $quote_id);
                
                $json['success'] = $this->language->get('text_approve_success');
            }
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function reject() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (isset($this->request->get['quote_id'])) {
            $quote_id = $this->request->get['quote_id'];
        } else {
            $quote_id = 0;
        }
        
        $quote_info = $this->model_sale_quote->getQuote($quote_id);
        
        if ($quote_info) {
            if ($quote_info['status'] == 'rejected') {
                $json['error'] = $this->language->get('error_already_rejected');
            } else if ($quote_info['converted_to_order']) {
                $json['error'] = $this->language->get('error_already_converted');
            } else {
                // Update quote status to rejected
                $this->model_sale_quote->updateQuoteStatus($quote_id, 'rejected');
                
                // Add activity log
                $this->user->logActivity('reject', 'sale', 'تم رفض عرض سعر #' . $quote_id, 'quote', $quote_id);
                
                $json['success'] = $this->language->get('text_reject_success');
            }
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function expire() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (isset($this->request->get['quote_id'])) {
            $quote_id = $this->request->get['quote_id'];
        } else {
            $quote_id = 0;
        }
        
        $quote_info = $this->model_sale_quote->getQuote($quote_id);
        
        if ($quote_info) {
            if ($quote_info['status'] == 'expired') {
                $json['error'] = $this->language->get('error_already_expired');
            } else if ($quote_info['converted_to_order']) {
                $json['error'] = $this->language->get('error_already_converted');
            } else {
                // Update quote status to expired
                $this->model_sale_quote->updateQuoteStatus($quote_id, 'expired');
                
                // Add activity log
                $this->user->logActivity('expire', 'sale', 'تم انتهاء صلاحية عرض سعر #' . $quote_id, 'quote', $quote_id);
                
                $json['success'] = $this->language->get('text_expire_success');
            }
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    // AJAX Methods
    public function ajaxList() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');

        $json = array();

        $filter_quote_number = isset($this->request->get['filter_quote_number']) ? $this->request->get['filter_quote_number'] : '';
        $filter_customer = isset($this->request->get['filter_customer']) ? $this->request->get['filter_customer'] : '';
        $filter_status = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';
        $filter_total_min = isset($this->request->get['filter_total_min']) ? $this->request->get['filter_total_min'] : '';
        $filter_total_max = isset($this->request->get['filter_total_max']) ? $this->request->get['filter_total_max'] : '';
        
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'q.quotation_date';
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
        
        $limit = $this->config->get('config_limit_admin');

        $filter_data = array(
            'filter_quote_number'  => $filter_quote_number,
            'filter_customer'      => $filter_customer,
            'filter_status'        => $filter_status,
            'filter_date_start'    => $filter_date_start,
            'filter_date_end'      => $filter_date_end,
            'filter_total_min'     => $filter_total_min,
            'filter_total_max'     => $filter_total_max,
            'sort'                 => $sort,
            'order'                => $order,
            'start'                => ($page - 1) * $limit,
            'limit'                => $limit
        );
        
        // Get statistics
        $json['stats'] = array(
            'total'      => $this->model_sale_quote->getTotalQuotes(array()),
            'pending'    => $this->model_sale_quote->getTotalQuotes(array('filter_status' => 'pending')),
            'approved'   => $this->model_sale_quote->getTotalQuotes(array('filter_status' => 'approved')),
            'rejected'   => $this->model_sale_quote->getTotalQuotes(array('filter_status' => 'rejected')),
            'expired'    => $this->model_sale_quote->getTotalQuotes(array('filter_status' => 'expired')),
            'converted'  => $this->model_sale_quote->getTotalQuotes(array('filter_converted' => true))
        );

        $quote_total = $this->model_sale_quote->getTotalQuotes($filter_data);
        $results = $this->model_sale_quote->getQuotes($filter_data);
        
        $json['quotes'] = array();
        
        foreach ($results as $result) {
            // Determine status class for styling
            $status_class = '';
            switch($result['status']) {
                case 'draft':
                    $status_class = 'default';
                    break;
                case 'pending':
                    $status_class = 'warning';
                    break;
                case 'approved':
                    $status_class = 'success';
                    break;
                case 'rejected':
                    $status_class = 'danger';
                    break;
                case 'expired':
                    $status_class = 'secondary';
                    break;
            }
            
            $json['quotes'][] = array(
                'quotation_id'      => $result['quotation_id'],
                'quotation_number'  => $result['quotation_number'],
                'customer_name'     => $result['customer_name'],
                'status'            => $result['status'],
                'status_text'       => $this->language->get('text_status_' . $result['status']),
                'status_class'      => $status_class,
                'total_amount'      => $result['total_amount'],
                'total_formatted'   => $this->currency->format($result['total_amount'], $this->config->get('config_currency')),
                'quotation_date'    => date($this->language->get('date_format_short'), strtotime($result['quotation_date'])),
                'valid_until'       => date($this->language->get('date_format_short'), strtotime($result['valid_until'])),
                'converted_to_order' => $result['converted_to_order'],
                'order_id'          => $result['order_id'],
                
                // Permissions for actions
                'can_view'          => $this->user->hasPermission('access', 'sale/quote'),
                'can_edit'          => $this->user->hasPermission('modify', 'sale/quote') && $result['status'] == 'draft',
                'can_approve'       => $this->user->hasPermission('modify', 'sale/quote') && $result['status'] == 'pending',
                'can_reject'        => $this->user->hasPermission('modify', 'sale/quote') && $result['status'] == 'pending',
                'can_expire'        => $this->user->hasPermission('modify', 'sale/quote') && in_array($result['status'], ['draft', 'pending', 'approved']) && !$result['converted_to_order'],
                'can_delete'        => $this->user->hasPermission('modify', 'sale/quote') && $result['status'] == 'draft' && !$result['converted_to_order'],
                'can_convert'       => $this->user->hasPermission('modify', 'sale/quote') && $result['status'] == 'approved' && !$result['converted_to_order'],
            );
        }
        
        // Prepare pagination
        $pagination = new Pagination();
        $pagination->total = $quote_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = 'javascript:void(0);';
        
        $json['pagination'] = $pagination->render();
        $json['results'] = sprintf($this->language->get('text_pagination'), ($quote_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($quote_total - $limit)) ? $quote_total : ((($page - 1) * $limit) + $limit), $quote_total, ceil($quote_total / $limit));
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
/**
 * AJAX: الحصول على نموذج عرض السعر
 */
public function ajaxGetQuoteForm() {
    $this->load->language('sale/quote');
    $this->load->model('sale/quote');
    
    $data['heading_title'] = !isset($this->request->get['quote_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
    
    if (isset($this->request->get['quote_id'])) {
        $quote_id = (int)$this->request->get['quote_id'];
        $data['quote_id'] = $quote_id;
        $quote_info = $this->model_sale_quote->getQuote($quote_id);
    } else {
        $quote_id = 0;
        $data['quote_id'] = 0;
        $quote_info = array();
    }
    
    // General info
    if (!empty($quote_info)) {
        $data['quotation_number'] = $quote_info['quotation_number'];
        $data['customer_id'] = $quote_info['customer_id'];
        $data['branch_id'] = $quote_info['branch_id'];
        $data['quotation_date'] = date('Y-m-d', strtotime($quote_info['quotation_date']));
        $data['valid_until'] = date('Y-m-d', strtotime($quote_info['valid_until']));
        $data['status'] = $quote_info['status'];
        $data['notes'] = $quote_info['notes'];
        $data['subtotal'] = $quote_info['subtotal_amount'];
        $data['discount_amount'] = $quote_info['discount_amount'];
        $data['tax_amount'] = $quote_info['tax_amount'];
        $data['total_amount'] = $quote_info['total_amount'];
        
        // Get customer info
        $this->load->model('customer/customer');
        $customer_info = $this->model_customer_customer->getCustomer($quote_info['customer_id']);
        
        if ($customer_info) {
            $data['customer'] = $customer_info['firstname'] . ' ' . $customer_info['lastname'];
        } else {
            $data['customer'] = '';
        }
        
        // Get quote items
        $data['quote_items'] = $this->model_sale_quote->getQuoteItems($quote_id);
    } else {
        // Default values for new quote
        $data['quotation_number'] = $this->model_sale_quote->generateQuoteNumber();
        $data['customer_id'] = 0;
        $data['customer'] = '';
        $data['branch_id'] = $this->user->getBranchId();
        $data['quotation_date'] = date('Y-m-d');
        $data['valid_until'] = date('Y-m-d', strtotime('+30 days'));
        $data['status'] = 'draft';
        $data['notes'] = '';
        $data['subtotal'] = 0;
        $data['discount_amount'] = 0;
        $data['tax_amount'] = 0;
        $data['total_amount'] = 0;
        $data['quote_items'] = array();
    }
    
    // Load branches
    $this->load->model('branch/branch');
    $data['branches'] = $this->model_branch_branch->getBranches();
    
    // Status options
    $data['statuses'] = array(
        'draft'     => $this->language->get('text_status_draft'),
        'pending'   => $this->language->get('text_status_pending'),
        'approved'  => $this->language->get('text_status_approved'),
        'rejected'  => $this->language->get('text_status_rejected'),
        'expired'   => $this->language->get('text_status_expired')
    );
    
    $data['user_token'] = $this->session->data['user_token'];
    
    $this->response->setOutput($this->load->view('sale/quote_form', $data));
}

/**
 * AJAX: البحث عن المنتجات
 */
public function ajaxSearchProducts() {
    $this->load->language('sale/quote');
    $this->load->model('catalog/product');
    $this->load->model('tool/image');
    
    $json = array();
    
    $filter_data = array(
        'filter_name'      => $this->request->post['filter_name'] ?? '',
        'filter_model'     => $this->request->post['filter_name'] ?? '',
        'filter_status'    => 1,
        'start'            => ($this->request->post['page'] ?? 1 - 1) * 10,
        'limit'            => 10
    );
    
    $results = $this->model_catalog_product->getProducts($filter_data);
    $product_total = $this->model_catalog_product->getTotalProducts($filter_data);
    
    $json['products'] = array();
    
    foreach ($results as $result) {
        if (is_file(DIR_IMAGE . $result['image'])) {
            $image = $this->model_tool_image->resize($result['image'], 40, 40);
        } else {
            $image = $this->model_tool_image->resize('no_image.png', 40, 40);
        }
        
        $json['products'][] = array(
            'product_id' => $result['product_id'],
            'name'       => $result['name'],
            'model'      => $result['model'],
            'price'      => $this->currency->format($result['price'], $this->config->get('config_currency')),
            'quantity'   => $result['quantity'],
            'image'      => $image
        );
    }
    
    // Pagination
    $pagination = new Pagination();
    $pagination->total = $product_total;
    $pagination->page = $this->request->post['page'] ?? 1;
    $pagination->limit = 10;
    $pagination->url = 'javascript:void(0);';
    
    $json['pagination'] = $pagination->render();
    
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

/**
 * AJAX: الحصول على تفاصيل المنتج
 */
public function ajaxGetProductDetails() {
    $this->load->language('sale/quote');
    $this->load->model('catalog/product');
    $this->load->model('branch/branch');
    
    $json = array('success' => false);
    
    if (isset($this->request->get['product_id'])) {
        $product_id = (int)$this->request->get['product_id'];
        $customer_id = isset($this->request->get['customer_id']) ? (int)$this->request->get['customer_id'] : 0;
        
        $product_info = $this->model_catalog_product->getProduct($product_id);
        
        if ($product_info) {
            $json['success'] = true;
            
            // Basic product info
            $json['product'] = array(
                'product_id'    => $product_info['product_id'],
                'name'          => $product_info['name'],
                'model'         => $product_info['model'],
                'sku'           => $product_info['sku'],
                'price'         => $product_info['price'],
                'tax_class_id'  => $product_info['tax_class_id'],
                'tax_rate'      => $this->getProductTaxRate($product_info['tax_class_id'])
            );
            
            // Get product units
            $json['units'] = array();
            
            $units = $this->model_catalog_product->getProductUnits($product_id);
            
            foreach ($units as $unit) {
                $unit_info = $this->model_catalog_product->getUnit($unit['unit_id']);
                
                $json['units'][] = array(
                    'unit_id'           => $unit['unit_id'],
                    'code'              => $unit_info['code'],
                    'name'              => $this->language->get('config_language_id') == 1 ? $unit_info['desc_en'] : $unit_info['desc_ar'],
                    'conversion_factor' => $unit['conversion_factor'],
                    'is_base'           => ($unit['unit_type'] == 'base') ? true : false
                );
            }
            
            // Get inventory for each branch
            $json['inventory'] = array();
            
            $branches = $this->model_branch_branch->getBranches();
            
            foreach ($branches as $branch) {
                foreach ($json['units'] as $unit) {
                    $inventory = $this->model_catalog_product->getProductInventory($product_id, $branch['branch_id'], $unit['unit_id']);
                    
                    if ($inventory) {
                        if (!isset($json['inventory'][$branch['branch_id']])) {
                            $json['inventory'][$branch['branch_id']] = array(
                                'branch_name' => $branch['name'],
                                'units' => array()
                            );
                        }
                        
                        $json['inventory'][$branch['branch_id']]['units'][$unit['unit_id']] = array(
                            'unit_name'         => $unit['name'],
                            'quantity'          => $inventory['quantity'],
                            'quantity_available'=> $inventory['quantity_available'],
                            'average_cost'      => $this->currency->format($inventory['average_cost'], $this->config->get('config_currency'))
                        );
                    }
                }
            }
        }
    }
    
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

/**
 * AJAX: الحصول على أسعار الوحدات
 */
public function ajaxGetUnitsPricing() {
    $this->load->model('catalog/product');
    
    $json = array('success' => false);
    
    if (isset($this->request->get['product_id'])) {
        $product_id = (int)$this->request->get['product_id'];
        $unit_id = isset($this->request->get['unit_id']) ? (int)$this->request->get['unit_id'] : 0;
        $customer_id = isset($this->request->get['customer_id']) ? (int)$this->request->get['customer_id'] : 0;
        
        $json['success'] = true;
        
        // Get product pricing for the unit
        $json['pricing'] = array();
        
        if ($customer_id) {
            $this->load->model('customer/customer');
            $customer_info = $this->model_customer_customer->getCustomer($customer_id);
            $customer_group_id = $customer_info ? $customer_info['customer_group_id'] : 0;
        } else {
            $customer_group_id = 0;
        }
        
        $product_pricing = $this->model_catalog_product->getProductPricing($product_id);
        
        if ($product_pricing) {
            foreach ($product_pricing as $pricing) {
                if ($unit_id && $pricing['unit_id'] != $unit_id) {
                    continue;
                }
                
                // Check if there's customer-specific pricing
                $special_price = null;
                
                if ($customer_group_id) {
                    // Get customer group special pricing
                    $special_pricing = $this->model_catalog_product->getProductSpecials($product_id, $customer_group_id);
                    
                    foreach ($special_pricing as $special) {
                        if ($special['unit_id'] == $pricing['unit_id']) {
                            $special_price = $special['price'];
                            break;
                        }
                    }
                }
                
                $json['pricing'][$pricing['unit_id']] = array(
                    'base_price'     => $this->currency->format($pricing['base_price'], $this->config->get('config_currency')),
                    'special_price'  => $special_price !== null ? $this->currency->format($special_price, $this->config->get('config_currency')) : null,
                    'wholesale_price'=> $pricing['wholesale_price'] ? $this->currency->format($pricing['wholesale_price'], $this->config->get('config_currency')) : null,
                    'tax_rate'       => $this->getProductTaxRate($product_id)
                );
            }
        }
    }
    
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

    public function ajaxGetQuote() {
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (isset($this->request->get['quote_id'])) {
            $quote_id = (int)$this->request->get['quote_id'];
            
            $quote_info = $this->model_sale_quote->getQuote($quote_id);
            
            if ($quote_info) {
                $quote_info['items'] = $this->model_sale_quote->getQuoteItems($quote_id);
                
                // Add status text
                $quote_info['status_text'] = $this->language->get('text_status_' . $quote_info['status']);
                
                $json['quote'] = $quote_info;
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        } else {
            $json['error'] = 'Missing quote ID';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function ajaxSave() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'sale/quote')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        // Validate form
        if (!isset($this->request->post['customer_id']) || empty($this->request->post['customer_id'])) {
            $json['error'] = $this->language->get('error_customer');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (!isset($this->request->post['quotation_date']) || empty($this->request->post['quotation_date'])) {
            $json['error'] = $this->language->get('error_quotation_date');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (!isset($this->request->post['valid_until']) || empty($this->request->post['valid_until'])) {
            $json['error'] = $this->language->get('error_valid_until');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        // Check items
        if (!isset($this->request->post['quote_item']) || empty($this->request->post['quote_item'])) {
            $json['error'] = $this->language->get('error_product_required');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        // Process request
        if (isset($this->request->get['quote_id'])) {
            // Edit existing quote
            $quote_id = (int)$this->request->get['quote_id'];
            
            $this->model_sale_quote->editQuote($quote_id, $this->request->post);
            
            // Add activity log
            $this->user->logActivity('update', 'sale', 'تم تحديث عرض سعر #' . $quote_id, 'quote', $quote_id);
            
            $json['success'] = $this->language->get('text_success_edit');
        } else {
            // Add new quote
            $quote_id = $this->model_sale_quote->addQuote($this->request->post);
            
            if ($quote_id) {
                // Add activity log
                $this->user->logActivity('create', 'sale', 'تم إنشاء عرض سعر جديد #' . $quote_id, 'quote', $quote_id);
                
                $json['success'] = $this->language->get('text_success_add');
                $json['redirect'] = $this->url->link('sale/quote/edit', 'user_token=' . $this->session->data['user_token'] . '&quote_id=' . $quote_id, true);
            } else {
                $json['error'] = 'Error saving quote';
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function ajaxDelete() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'sale/quote')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['quote_id'])) {
            $quote_id = (int)$this->request->post['quote_id'];
            $quote_info = $this->model_sale_quote->getQuote($quote_id);
            
            if (!$quote_info) {
                $json['error'] = $this->language->get('error_not_found');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            if ($quote_info['status'] != 'draft') {
                $json['error'] = $this->language->get('error_delete_non_draft');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            if ($quote_info['converted_to_order']) {
                $json['error'] = $this->language->get('error_delete_converted');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            $this->model_sale_quote->deleteQuote($quote_id);
            
            // Add activity log
            $this->user->logActivity('delete', 'sale', 'تم حذف عرض سعر #' . $quote_id, 'quote', $quote_id);
            
            $json['success'] = $this->language->get('text_success_delete');
        } else {
            $json['error'] = 'Missing quote ID';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function ajaxApprove() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'sale/quote')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['quote_id'])) {
            $quote_id = (int)$this->request->post['quote_id'];
            $quote_info = $this->model_sale_quote->getQuote($quote_id);
            
            if (!$quote_info) {
                $json['error'] = $this->language->get('error_not_found');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            if ($quote_info['status'] == 'approved') {
                $json['error'] = $this->language->get('error_already_approved');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            $this->model_sale_quote->updateQuoteStatus($quote_id, 'approved');
            
            // Add activity log
            $this->user->logActivity('approve', 'sale', 'تم اعتماد عرض سعر #' . $quote_id, 'quote', $quote_id);
            
            $json['success'] = $this->language->get('text_approve_success');
        } else {
            $json['error'] = 'Missing quote ID';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function ajaxReject() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'sale/quote')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['quote_id'])) {
            $quote_id = (int)$this->request->post['quote_id'];
            $quote_info = $this->model_sale_quote->getQuote($quote_id);
            
            if (!$quote_info) {
                $json['error'] = $this->language->get('error_not_found');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            if ($quote_info['status'] == 'rejected') {
                $json['error'] = $this->language->get('error_already_rejected');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            if ($quote_info['converted_to_order']) {
                $json['error'] = $this->language->get('error_already_converted');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            $rejection_reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : '';
            
            $this->model_sale_quote->rejectQuote($quote_id, $rejection_reason);
            
            // Add activity log
            $this->user->logActivity('reject', 'sale', 'تم رفض عرض سعر #' . $quote_id, 'quote', $quote_id);
            
            $json['success'] = $this->language->get('text_reject_success');
        } else {
            $json['error'] = 'Missing quote ID';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function ajaxExpire() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'sale/quote')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['quote_id'])) {
            $quote_id = (int)$this->request->post['quote_id'];
            $quote_info = $this->model_sale_quote->getQuote($quote_id);
            
            if (!$quote_info) {
                $json['error'] = $this->language->get('error_not_found');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            if ($quote_info['status'] == 'expired') {
                $json['error'] = $this->language->get('error_already_expired');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            if ($quote_info['converted_to_order']) {
                $json['error'] = $this->language->get('error_already_converted');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            $this->model_sale_quote->updateQuoteStatus($quote_id, 'expired');
            
            // Add activity log
            $this->user->logActivity('expire', 'sale', 'تم انتهاء صلاحية عرض سعر #' . $quote_id, 'quote', $quote_id);
            
            $json['success'] = $this->language->get('text_expire_success');
        } else {
            $json['error'] = 'Missing quote ID';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function ajaxConvertToOrder() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'sale/quote')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['quote_id'])) {
            $quote_id = (int)$this->request->post['quote_id'];
            $quote_info = $this->model_sale_quote->getQuote($quote_id);
            
            if (!$quote_info) {
                $json['error'] = $this->language->get('error_not_found');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            if ($quote_info['status'] != 'approved') {
                $json['error'] = $this->language->get('error_quote_not_approved');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            if ($quote_info['converted_to_order']) {
                $json['error'] = $this->language->get('error_already_converted');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            $order_id = $this->model_sale_quote->convertToOrder($quote_id);
            
            if ($order_id) {
                // Add activity log
                $this->user->logActivity('convert', 'sale', 'تم تحويل عرض سعر #' . $quote_id . ' إلى طلب #' . $order_id, 'quote', $quote_id);
                
                $json['success'] = sprintf($this->language->get('text_success_convert'), $order_id);
                $json['redirect'] = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true);
            } else {
                $json['error'] = $this->language->get('error_convert_failed');
            }
        } else {
            $json['error'] = 'Missing quote ID';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function ajaxBulkAction() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'sale/quote')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (!isset($this->request->post['action']) || !isset($this->request->post['selected']) || !is_array($this->request->post['selected'])) {
            $json['error'] = 'Invalid request';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $action = $this->request->post['action'];
        $selected = $this->request->post['selected'];
        $errors = array();
        $success_count = 0;
        
        foreach ($selected as $quote_id) {
            $quote_info = $this->model_sale_quote->getQuote($quote_id);
            
            if (!$quote_info) {
                $errors[] = 'Quote #' . $quote_id . ' not found';
                continue;
            }
            
            switch ($action) {
                case 'approve':
                    if ($quote_info['status'] != 'pending') {
                        $errors[] = 'Quote #' . $quote_id . ' is not in pending status';
                        continue;
                    }
                    
                    $this->model_sale_quote->updateQuoteStatus($quote_id, 'approved');
                    $this->user->logActivity('approve', 'sale', 'تم اعتماد عرض سعر #' . $quote_id, 'quote', $quote_id);
                    $success_count++;
                    break;
                    
                case 'reject':
                    if ($quote_info['status'] != 'pending') {
                        $errors[] = 'Quote #' . $quote_id . ' is not in pending status';
                        continue;
                    }
                    
                    if ($quote_info['converted_to_order']) {
                        $errors[] = 'Quote #' . $quote_id . ' has already been converted to an order';
                        continue;
                    }
                    
                    $rejection_reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : '';
                    $this->model_sale_quote->rejectQuote($quote_id, $rejection_reason);
                    $this->user->logActivity('reject', 'sale', 'تم رفض عرض سعر #' . $quote_id, 'quote', $quote_id);
                    $success_count++;
                    break;
                    
                case 'expire':
                    if ($quote_info['status'] == 'expired') {
                        $errors[] = 'Quote #' . $quote_id . ' is already expired';
                        continue;
                    }
                    
                    if ($quote_info['converted_to_order']) {
                        $errors[] = 'Quote #' . $quote_id . ' has already been converted to an order';
                        continue;
                    }
                    
                    $this->model_sale_quote->updateQuoteStatus($quote_id, 'expired');
                    $this->user->logActivity('expire', 'sale', 'تم انتهاء صلاحية عرض سعر #' . $quote_id, 'quote', $quote_id);
                    $success_count++;
                    break;
                    
                case 'delete':
                    if ($quote_info['status'] != 'draft') {
                        $errors[] = 'Quote #' . $quote_id . ' is not in draft status';
                        continue;
                    }
                    
                    if ($quote_info['converted_to_order']) {
                        $errors[] = 'Quote #' . $quote_id . ' has already been converted to an order';
                        continue;
                    }
                    
                    $this->model_sale_quote->deleteQuote($quote_id);
                    $this->user->logActivity('delete', 'sale', 'تم حذف عرض سعر #' . $quote_id, 'quote', $quote_id);
                    $success_count++;
                    break;
                    
                default:
                    $errors[] = 'Invalid action';
                    break;
            }
        }
        
        if ($success_count > 0) {
            $action_text = '';
            switch ($action) {
                case 'approve':
                    $action_text = 'approved';
                    break;
                case 'reject':
                    $action_text = 'rejected';
                    break;
                case 'expire':
                    $action_text = 'set as expired';
                    break;
                case 'delete':
                    $action_text = 'deleted';
                    break;
            }
            
            $json['success'] = $success_count . ' quote(s) successfully ' . $action_text;
        }
        
        if (!empty($errors)) {
            $json['errors'] = $errors;
            if (empty($json['success'])) {
                $json['error'] = 'Errors occurred during processing';
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
 
    public function ajaxGetCustomers() {
        $this->load->model('customer/customer');
        
        $json = array();
        
        if (isset($this->request->get['q'])) {
            $filter_data = array(
                'filter_name'  => $this->request->get['q'],
                'filter_email' => $this->request->get['q'],
                'start'        => 0,
                'limit'        => 10
            );
            
            $results = $this->model_customer_customer->getCustomers($filter_data);
            
            foreach ($results as $result) {
                $json[] = array(
                    'id'    => $result['customer_id'],
                    'text'  => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')) . ' (' . $result['email'] . ')'
                );
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function ajaxSendEmail() {
        $this->load->language('sale/quote');
        $this->load->model('sale/quote');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'sale/quote')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        if (isset($this->request->post['quote_id'])) {
            $quote_id = (int)$this->request->post['quote_id'];
            $quote_info = $this->model_sale_quote->getQuote($quote_id);
            
            if (!$quote_info) {
                $json['error'] = $this->language->get('error_not_found');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            if (!isset($this->request->post['email']) || !isset($this->request->post['subject']) || !isset($this->request->post['message'])) {
                $json['error'] = $this->language->get('error_email_data');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            try {
                // Generate PDF
                require_once(DIR_SYSTEM . 'library/pdf/tcpdf/tcpdf.php');
                
                $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
                $pdf->SetCreator(utf8_decode($this->config->get('config_name')));
                $pdf->SetAuthor(utf8_decode($this->config->get('config_name')));
                $pdf->SetTitle(utf8_decode('Quote #' . $quote_info['quotation_number']));
                $pdf->SetSubject(utf8_decode('Quote #' . $quote_info['quotation_number']));
                $pdf->SetKeywords(utf8_decode('quote, sale, ' . $this->config->get('config_name')));
                
                $pdf->setHeaderFont(Array('dejavusans', '', 10));
                $pdf->setFooterFont(Array('dejavusans', '', 8));
                $pdf->SetDefaultMonospacedFont('courier');
                $pdf->SetMargins(15, 15, 15);
                $pdf->SetHeaderMargin(5);
                $pdf->SetFooterMargin(10);
                $pdf->SetAutoPageBreak(TRUE, 25);
                
                $pdf->SetFont('dejavusans', '', 10);
                $pdf->AddPage();
                
                // Get the HTML content
                $html = $this->getPrintViewHtml($quote_info);
                
                // Convert relative image paths to full paths
                $html = str_replace('src="image/', 'src="' . DIR_IMAGE, $html);
                
                // Write the HTML content to the PDF
                $pdf->writeHTML($html, true, false, true, false, '');
                
                // Save the PDF to a temporary file
                $pdf_file = DIR_DOWNLOAD . 'quote_' . $quote_id . '.pdf';
                $pdf->Output($pdf_file, 'F');
                
                // Send email with PDF attachment
                $email = $this->request->post['email'];
                $subject = $this->request->post['subject'];
                $message = $this->request->post['message'];
                
                $mail = new Mail($this->config->get('config_mail_engine'));
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                $mail->smtp_password = $this->config->get('config_mail_smtp_password');
                $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
                
                $mail->setTo($email);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(utf8_decode($this->config->get('config_name')));
                $mail->setSubject(utf8_decode($subject));
                $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->addAttachment($pdf_file, 'quote_' . $quote_info['quotation_number'] . '.pdf');
                $mail->send();
                
                // Delete temporary file
                if (file_exists($pdf_file)) {
                    unlink($pdf_file);
                }
                
                // Update quote with email sent date
                $this->model_sale_quote->updateQuoteEmailSent($quote_id);
                
                // Add activity log
                $this->user->logActivity('email', 'sale', 'تم إرسال عرض سعر #' . $quote_id . ' بالبريد الإلكتروني', 'quote', $quote_id);
                
                $json['success'] = $this->language->get('text_email_success');
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'Missing quote ID';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    // Main view methods
    protected function getList() {
        if (isset($this->request->get['filter_quote_number'])) {
            $filter_quote_number = $this->request->get['filter_quote_number'];
        } else {
            $filter_quote_number = '';
        }
        
        if (isset($this->request->get['filter_customer'])) {
            $filter_customer = $this->request->get['filter_customer'];
        } else {
            $filter_customer = '';
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
        
        if (isset($this->request->get['filter_total_min'])) {
            $filter_total_min = $this->request->get['filter_total_min'];
        } else {
            $filter_total_min = '';
        }
        
        if (isset($this->request->get['filter_total_max'])) {
            $filter_total_max = $this->request->get['filter_total_max'];
        } else {
            $filter_total_max = '';
        }
        
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'q.quotation_date';
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
        
        if (isset($this->request->get['filter_quote_number'])) {
            $url .= '&filter_quote_number=' . urlencode(html_entity_decode($this->request->get['filter_quote_number'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
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
        
        if (isset($this->request->get['filter_total_min'])) {
            $url .= '&filter_total_min=' . $this->request->get['filter_total_min'];
        }
        
        if (isset($this->request->get['filter_total_max'])) {
            $url .= '&filter_total_max=' . $this->request->get['filter_total_max'];
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
            'href' => $this->url->link('sale/quote', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['add'] = $this->url->link('sale/quote/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('sale/quote/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        // Get statistics
        $stats_data = array();
        
        $data['stats'] = array(
            'total'      => $this->model_sale_quote->getTotalQuotes($stats_data),
            'pending'    => $this->model_sale_quote->getTotalQuotes(array('filter_status' => 'pending')),
            'approved'   => $this->model_sale_quote->getTotalQuotes(array('filter_status' => 'approved')),
            'rejected'   => $this->model_sale_quote->getTotalQuotes(array('filter_status' => 'rejected')),
            'expired'    => $this->model_sale_quote->getTotalQuotes(array('filter_status' => 'expired')),
            'converted'  => $this->model_sale_quote->getTotalQuotes(array('filter_converted' => true))
        );
        
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
        
        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }
        
        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }
        
        // Get list of branches for filter
        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();
        
        // Get list of statuses for filter
        $data['statuses'] = array(
            'draft'     => $this->language->get('text_status_draft'),
            'pending'   => $this->language->get('text_status_pending'),
            'approved'  => $this->language->get('text_status_approved'),
            'rejected'  => $this->language->get('text_status_rejected'),
            'expired'   => $this->language->get('text_status_expired')
        );
        
        $data['filter_quote_number'] = $filter_quote_number;
        $data['filter_customer'] = $filter_customer;
        $data['filter_status'] = $filter_status;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['filter_total_min'] = $filter_total_min;
        $data['filter_total_max'] = $filter_total_max;
        
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        // Check permissions
        $data['can_add'] = $this->user->hasPermission('modify', 'sale/quote');
        $data['can_edit'] = $this->user->hasPermission('modify', 'sale/quote');
        $data['can_delete'] = $this->user->hasPermission('modify', 'sale/quote');
        $data['can_view'] = $this->user->hasPermission('access', 'sale/quote');
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('sale/quote_list', $data));
    }
    
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['quote_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['customer'])) {
            $data['error_customer'] = $this->error['customer'];
        } else {
            $data['error_customer'] = '';
        }
        
        if (isset($this->error['quotation_date'])) {
            $data['error_quotation_date'] = $this->error['quotation_date'];
        } else {
            $data['error_quotation_date'] = '';
        }
        
        if (isset($this->error['valid_until'])) {
            $data['error_valid_until'] = $this->error['valid_until'];
        } else {
            $data['error_valid_until'] = '';
        }
        
        $url = '';
        
        if (isset($this->request->get['filter_quote_number'])) {
            $url .= '&filter_quote_number=' . urlencode(html_entity_decode($this->request->get['filter_quote_number'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
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
        
        if (isset($this->request->get['filter_total_min'])) {
            $url .= '&filter_total_min=' . $this->request->get['filter_total_min'];
        }
        
        if (isset($this->request->get['filter_total_max'])) {
            $url .= '&filter_total_max=' . $this->request->get['filter_total_max'];
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
            'href' => $this->url->link('sale/quote', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        if (!isset($this->request->get['quote_id'])) {
            $data['action'] = $this->url->link('sale/quote/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('sale/quote/edit', 'user_token=' . $this->session->data['user_token'] . '&quote_id=' . $this->request->get['quote_id'] . $url, true);
        }
        
        $data['cancel'] = $this->url->link('sale/quote', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        if (isset($this->request->get['quote_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $quote_info = $this->model_sale_quote->getQuote($this->request->get['quote_id']);
        }
        
        $data['user_token'] = $this->session->data['user_token'];
        
        // Quote Details
        if (isset($this->request->post['quotation_number'])) {
            $data['quotation_number'] = $this->request->post['quotation_number'];
        } elseif (!empty($quote_info)) {
            $data['quotation_number'] = $quote_info['quotation_number'];
        } else {
            $data['quotation_number'] = $this->model_sale_quote->generateQuoteNumber();
        }
        
        if (isset($this->request->post['customer_id'])) {
            $data['customer_id'] = $this->request->post['customer_id'];
        } elseif (!empty($quote_info)) {
            $data['customer_id'] = $quote_info['customer_id'];
        } else {
            $data['customer_id'] = 0;
        }
        
        if (isset($this->request->post['customer'])) {
            $data['customer'] = $this->request->post['customer'];
        } elseif (!empty($quote_info)) {
            $this->load->model('customer/customer');
            $customer_info = $this->model_customer_customer->getCustomer($quote_info['customer_id']);
            
            if ($customer_info) {
                $data['customer'] = $customer_info['firstname'] . ' ' . $customer_info['lastname'];
            } else {
                $data['customer'] = '';
            }
        } else {
            $data['customer'] = '';
        }
        
        if (isset($this->request->post['branch_id'])) {
            $data['branch_id'] = $this->request->post['branch_id'];
        } elseif (!empty($quote_info)) {
            $data['branch_id'] = $quote_info['branch_id'];
        } else {
            $data['branch_id'] = $this->user->getBranchId();
        }
        
        // Get branches
        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();
        
        if (isset($this->request->post['quotation_date'])) {
            $data['quotation_date'] = $this->request->post['quotation_date'];
        } elseif (!empty($quote_info)) {
            $data['quotation_date'] = date('Y-m-d', strtotime($quote_info['quotation_date']));
        } else {
            $data['quotation_date'] = date('Y-m-d');
        }
        
        if (isset($this->request->post['valid_until'])) {
            $data['valid_until'] = $this->request->post['valid_until'];
        } elseif (!empty($quote_info)) {
            $data['valid_until'] = date('Y-m-d', strtotime($quote_info['valid_until']));
        } else {
            $data['valid_until'] = date('Y-m-d', strtotime('+30 days'));
        }
        
        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($quote_info)) {
            $data['status'] = $quote_info['status'];
        } else {
            $data['status'] = 'draft';
        }
        
        // Get statuses
        $data['statuses'] = array(
            'draft'     => $this->language->get('text_status_draft'),
            'pending'   => $this->language->get('text_status_pending'),
            'approved'  => $this->language->get('text_status_approved'),
            'rejected'  => $this->language->get('text_status_rejected'),
            'expired'   => $this->language->get('text_status_expired')
        );
        
        if (isset($this->request->post['notes'])) {
            $data['notes'] = $this->request->post['notes'];
        } elseif (!empty($quote_info)) {
            $data['notes'] = $quote_info['notes'];
        } else {
            $data['notes'] = '';
        }
        
        // Items
        $data['quote_items'] = array();
        
        if (isset($this->request->post['quote_item'])) {
            $quote_items = $this->request->post['quote_item'];
        } elseif (isset($this->request->get['quote_id'])) {
            $quote_items = $this->model_sale_quote->getQuoteItems($this->request->get['quote_id']);
        } else {
            $quote_items = array();
        }
        
        if (!empty($quote_items)) {
            $this->load->model('catalog/product');
            $this->load->model('localisation/unit');

            foreach ($quote_items as $quote_item) {
                $product_info = null;
                
                if (isset($quote_item['product_id']) && $quote_item['product_id']) {
                    $product_info = $this->model_catalog_product->getProduct($quote_item['product_id']);
                }
                
                $unit_info = null;
                
                if (isset($quote_item['unit_id']) && $quote_item['unit_id']) {
                    $unit_info = $this->model_localisation_unit->getUnit($quote_item['unit_id']);
                }
                
                $data['quote_items'][] = array(
                    'item_id'       => isset($quote_item['item_id']) ? $quote_item['item_id'] : 0,
                    'product_id'    => isset($quote_item['product_id']) ? $quote_item['product_id'] : 0,
                    'product_name'  => isset($quote_item['product_name']) ? $quote_item['product_name'] : (($product_info) ? $product_info['name'] : ''),
                    'unit_id'       => isset($quote_item['unit_id']) ? $quote_item['unit_id'] : 0,
                    'unit_name'     => isset($quote_item['unit_name']) ? $quote_item['unit_name'] : (($unit_info) ? $unit_info['desc_' . $this->config->get('config_language')] : ''),
                    'quantity'      => isset($quote_item['quantity']) ? $quote_item['quantity'] : 0,
                    'price'         => isset($quote_item['price']) ? $quote_item['price'] : 0,
                    'discount_rate' => isset($quote_item['discount_rate']) ? $quote_item['discount_rate'] : 0,
                    'tax_rate'      => isset($quote_item['tax_rate']) ? $quote_item['tax_rate'] : 0,
                    'total'         => isset($quote_item['total']) ? $quote_item['total'] : 0,
                    'notes'         => isset($quote_item['notes']) ? $quote_item['notes'] : ''
                );
            }
        }
        
        // Totals
        if (isset($this->request->post['total_amount'])) {
            $data['total_amount'] = $this->request->post['total_amount'];
        } elseif (!empty($quote_info)) {
            $data['total_amount'] = $quote_info['total_amount'];
        } else {
            $data['total_amount'] = 0;
        }
        
        if (isset($this->request->post['discount_amount'])) {
            $data['discount_amount'] = $this->request->post['discount_amount'];
        } elseif (!empty($quote_info)) {
            $data['discount_amount'] = $quote_info['discount_amount'];
        } else {
            $data['discount_amount'] = 0;
        }
        
        if (isset($this->request->post['tax_amount'])) {
            $data['tax_amount'] = $this->request->post['tax_amount'];
        } elseif (!empty($quote_info)) {
            $data['tax_amount'] = $quote_info['tax_amount'];
        } else {
            $data['tax_amount'] = 0;
        }
        
        if (isset($this->request->post['net_amount'])) {
            $data['net_amount'] = $this->request->post['net_amount'];
        } elseif (!empty($quote_info)) {
            $data['net_amount'] = $quote_info['net_amount'];
        } else {
            $data['net_amount'] = 0;
        }
        
        // Load company info for header
        $data['company_info'] = array(
            'name'      => $this->config->get('config_name'),
            'address'   => $this->config->get('config_address'),
            'telephone' => $this->config->get('config_telephone'),
            'email'     => $this->config->get('config_email')
        );
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('sale/quote_form', $data));
    }
    
    protected function getQuoteView($quote_info) {
        $this->load->language('sale/quote');
        
        $data['title'] = $this->language->get('text_view') . ' ' . $quote_info['quotation_number'];
        
        $data['quote_id'] = $quote_info['quotation_id'];
        $data['quotation_number'] = $quote_info['quotation_number'];
        
        // Customer information
        $this->load->model('customer/customer');
        $customer_info = $this->model_customer_customer->getCustomer($quote_info['customer_id']);
        
        if ($customer_info) {
            $data['customer'] = array(
                'customer_id' => $customer_info['customer_id'],
                'name' => $customer_info['firstname'] . ' ' . $customer_info['lastname'],
                'email' => $customer_info['email'],
                'telephone' => $customer_info['telephone'],
                'address' => $this->getCustomerAddress($customer_info['customer_id']),
                'href' => $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $customer_info['customer_id'], true)
            );
        } else {
            $data['customer'] = array(
                'customer_id' => 0,
                'name' => $this->language->get('text_missing'),
                'email' => '',
                'telephone' => '',
                'address' => '',
                'href' => ''
            );
        }
        
        // Branch information
        $this->load->model('branch/branch');
        $branch_info = $this->model_branch_branch->getBranch($quote_info['branch_id']);
        
        if ($branch_info) {
            $data['branch'] = array(
                'branch_id' => $branch_info['branch_id'],
                'name' => $branch_info['name'],
                'address' => $branch_info['address_1'] . ' ' . $branch_info['address_2'],
                'telephone' => $branch_info['telephone'],
                'email' => $branch_info['email'],
                'href' => $this->url->link('branch/branch/edit', 'user_token=' . $this->session->data['user_token'] . '&branch_id=' . $branch_info['branch_id'], true)
            );
        } else {
            $data['branch'] = array(
                'branch_id' => 0,
                'name' => $this->language->get('text_missing'),
                'address' => '',
                'telephone' => '',
                'email' => '',
                'href' => ''
            );
        }
        
        // Quote dates and status
        $data['quotation_date'] = date($this->language->get('date_format_short'), strtotime($quote_info['quotation_date']));
        $data['valid_until'] = date($this->language->get('date_format_short'), strtotime($quote_info['valid_until']));
        $data['status'] = $quote_info['status'];
        $data['status_text'] = $this->language->get('text_status_' . $quote_info['status']);
        
        // Status class for styling
        switch ($quote_info['status']) {
            case 'draft':
                $data['status_class'] = 'default';
                break;
            case 'pending':
                $data['status_class'] = 'warning';
                break;
            case 'approved':
                $data['status_class'] = 'success';
                break;
            case 'rejected':
                $data['status_class'] = 'danger';
                break;
            case 'expired':
                $data['status_class'] = 'secondary';
                break;
            default:
                $data['status_class'] = 'default';
        }
        
        // Get items
        $data['items'] = array();
        
        $items = $this->model_sale_quote->getQuoteItems($quote_info['quotation_id']);
        
        foreach ($items as $item) {
            $data['items'][] = array(
                'item_id' => $item['item_id'],
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'unit_id' => $item['unit_id'],
                'unit_name' => $item['unit_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'price_formatted' => $this->currency->format($item['price'], $this->config->get('config_currency')),
                'discount_rate' => $item['discount_rate'],
                'tax_rate' => $item['tax_rate'],
                'total' => $item['total'],
                'total_formatted' => $this->currency->format($item['total'], $this->config->get('config_currency')),
                'notes' => $item['notes']
            );
        }
        
        // Totals
        $data['subtotal'] = $quote_info['subtotal_amount'];
        $data['discount'] = $quote_info['discount_amount'];
        $data['tax'] = $quote_info['tax_amount'];
        $data['total'] = $quote_info['total_amount'];
        
        $data['subtotal_formatted'] = $this->currency->format($quote_info['subtotal_amount'], $this->config->get('config_currency'));
        $data['discount_formatted'] = $this->currency->format($quote_info['discount_amount'], $this->config->get('config_currency'));
        $data['tax_formatted'] = $this->currency->format($quote_info['tax_amount'], $this->config->get('config_currency'));
        $data['total_formatted'] = $this->currency->format($quote_info['total_amount'], $this->config->get('config_currency'));
        
        // Notes
        $data['notes'] = $quote_info['notes'];
        
        // Converted to order information
        $data['converted_to_order'] = $quote_info['converted_to_order'];
        $data['order_id'] = $quote_info['order_id'];
        
        if ($quote_info['order_id']) {
            $data['order_url'] = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $quote_info['order_id'], true);
        } else {
            $data['order_url'] = '';
        }
        
        // Created by user
        $this->load->model('user/user');
        $created_by_info = $this->model_user_user->getUser($quote_info['created_by']);
        
        if ($created_by_info) {
            $data['created_by'] = $created_by_info['firstname'] . ' ' . $created_by_info['lastname'];
        } else {
            $data['created_by'] = $this->language->get('text_missing');
        }
        
        // Email history
        $data['email_sent'] = $quote_info['email_sent'];
        $data['email_sent_date'] = $quote_info['email_sent'] ? date($this->language->get('datetime_format'), strtotime($quote_info['email_sent_date'])) : '';
        
        // Customer email for sending quote
        $data['customer_email'] = $customer_info ? $customer_info['email'] : '';
        
        // Default email subject and message
        $data['default_email_subject'] = sprintf($this->language->get('text_default_email_subject'), $quote_info['quotation_number']);
        
        if ($customer_info) {
            $data['default_email_message'] = sprintf($this->language->get('text_default_email_message'), $customer_info['firstname']);
        } else {
            $data['default_email_message'] = $this->language->get('text_default_email_message_no_name');
        }
        
        // History
        $data['history'] = array();
        
        $results = $this->model_sale_quote->getQuoteHistory($quote_info['quotation_id']);
        
        foreach ($results as $result) {
            $data['history'][] = array(
                'history_id' => $result['history_id'],
                'user' => $result['firstname'] . ' ' . $result['lastname'],
                'action' => $this->getHistoryActionText($result['action']),
                'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
                'description' => $result['description']
            );
        }
        
        // Action buttons and permissions
        $data['can_edit'] = $this->user->hasPermission('modify', 'sale/quote') && $quote_info['status'] == 'draft';
        $data['can_approve'] = $this->user->hasPermission('modify', 'sale/quote') && $quote_info['status'] == 'pending';
        $data['can_reject'] = $this->user->hasPermission('modify', 'sale/quote') && $quote_info['status'] == 'pending';
        $data['can_expire'] = $this->user->hasPermission('modify', 'sale/quote') && in_array($quote_info['status'], array('draft', 'pending', 'approved')) && !$quote_info['converted_to_order'];
        $data['can_delete'] = $this->user->hasPermission('modify', 'sale/quote') && $quote_info['status'] == 'draft' && !$quote_info['converted_to_order'];
        $data['can_convert'] = $this->user->hasPermission('modify', 'sale/quote') && $quote_info['status'] == 'approved' && !$quote_info['converted_to_order'];
        $data['can_email'] = $this->user->hasPermission('modify', 'sale/quote') && !empty($data['customer_email']);
        
        // URLs for actions
        $data['edit_url'] = $this->url->link('sale/quote/edit', 'user_token=' . $this->session->data['user_token'] . '&quote_id=' . $quote_info['quotation_id'], true);
        $data['print_url'] = $this->url->link('sale/quote/print', 'user_token=' . $this->session->data['user_token'] . '&quote_id=' . $quote_info['quotation_id'], true);
        $data['back_url'] = $this->url->link('sale/quote', 'user_token=' . $this->session->data['user_token'], true);
        
        // Company info
        $data['company_name'] = $this->config->get('config_name');
        $data['company_address'] = $this->config->get('config_address');
        $data['company_telephone'] = $this->config->get('config_telephone');
        $data['company_email'] = $this->config->get('config_email');
        $data['company_logo'] = $this->config->get('config_logo') ? $this->model_tool_image->resize($this->config->get('config_logo'), 150, 50) : '';
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('sale/quote_view', $data));
    }
    
    protected function getPrintView($quote_info) {
        $this->load->language('sale/quote');
        
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $quote_info['quotation_number']);
        
        // Set data array
        $data['title'] = $this->language->get('heading_title') . ' - ' . $quote_info['quotation_number'];
        $data['base'] = HTTP_SERVER;
        $data['direction'] = $this->language->get('direction');
        $data['language'] = $this->language->get('code');
        
        // Main data - reuse view data for consistency
        $view_data = array();
        $this->getQuoteView($quote_info);
        $view_data = json_decode($this->response->getOutput(), true);
        
        // Add only the needed data to the print view
        $data['quotation_number'] = $view_data['quotation_number'];
        $data['customer'] = $view_data['customer'];
        $data['branch'] = $view_data['branch'];
        $data['quotation_date'] = $view_data['quotation_date'];
        $data['valid_until'] = $view_data['valid_until'];
        $data['status'] = $view_data['status'];
        $data['status_text'] = $view_data['status_text'];
        $data['status_class'] = $view_data['status_class'];
        $data['items'] = $view_data['items'];
        $data['subtotal'] = $view_data['subtotal'];
        $data['discount'] = $view_data['discount'];
        $data['tax'] = $view_data['tax'];
        $data['total'] = $view_data['total'];
        $data['subtotal_formatted'] = $view_data['subtotal_formatted'];
        $data['discount_formatted'] = $view_data['discount_formatted'];
        $data['tax_formatted'] = $view_data['tax_formatted'];
        $data['total_formatted'] = $view_data['total_formatted'];
        $data['notes'] = $view_data['notes'];
        
        // Company info
        $data['company_name'] = $this->config->get('config_name');
        $data['company_address'] = $this->config->get('config_address');
        $data['company_telephone'] = $this->config->get('config_telephone');
        $data['company_email'] = $this->config->get('config_email');
        $data['company_logo'] = $this->config->get('config_logo') ? HTTP_CATALOG . 'image/' . $this->config->get('config_logo') : '';
        
        // Get pdf specific data if needed
        $data['is_pdf'] = isset($this->request->get['pdf']) && $this->request->get['pdf'] == 1;
        
        // Render the print view
        $this->response->setOutput($this->load->view('sale/quote_print', $data));
    }
    
    protected function getPrintViewHtml($quote_info) {
        // Similar to getPrintView but returns HTML string instead of output
        $this->load->language('sale/quote');
        
        // Set data array
        $data['title'] = $this->language->get('heading_title') . ' - ' . $quote_info['quotation_number'];
        $data['base'] = HTTP_SERVER;
        $data['direction'] = $this->language->get('direction');
        $data['language'] = $this->language->get('code');
        
        // Customer information
        $this->load->model('customer/customer');
        $customer_info = $this->model_customer_customer->getCustomer($quote_info['customer_id']);
        
        if ($customer_info) {
            $data['customer'] = array(
                'customer_id' => $customer_info['customer_id'],
                'name' => $customer_info['firstname'] . ' ' . $customer_info['lastname'],
                'email' => $customer_info['email'],
                'telephone' => $customer_info['telephone'],
                'address' => $this->getCustomerAddress($customer_info['customer_id'])
            );
        } else {
            $data['customer'] = array(
                'customer_id' => 0,
                'name' => $this->language->get('text_missing'),
                'email' => '',
                'telephone' => '',
                'address' => ''
            );
        }
        
        // Branch information
        $this->load->model('branch/branch');
        $branch_info = $this->model_branch_branch->getBranch($quote_info['branch_id']);
        
        if ($branch_info) {
            $data['branch'] = array(
                'branch_id' => $branch_info['branch_id'],
                'name' => $branch_info['name'],
                'address' => $branch_info['address_1'] . ' ' . $branch_info['address_2'],
                'telephone' => $branch_info['telephone'],
                'email' => $branch_info['email']
            );
        } else {
            $data['branch'] = array(
                'branch_id' => 0,
                'name' => $this->language->get('text_missing'),
                'address' => '',
                'telephone' => '',
                'email' => ''
            );
        }
        
        // Quote dates and status
        $data['quotation_number'] = $quote_info['quotation_number'];
        $data['quotation_date'] = date($this->language->get('date_format_short'), strtotime($quote_info['quotation_date']));
        $data['valid_until'] = date($this->language->get('date_format_short'), strtotime($quote_info['valid_until']));
        $data['status'] = $quote_info['status'];
        $data['status_text'] = $this->language->get('text_status_' . $quote_info['status']);
        
        // Status class for styling
        switch ($quote_info['status']) {
            case 'draft':
                $data['status_class'] = 'default';
                break;
            case 'pending':
                $data['status_class'] = 'warning';
                break;
            case 'approved':
                $data['status_class'] = 'success';
                break;
            case 'rejected':
                $data['status_class'] = 'danger';
                break;
            case 'expired':
                $data['status_class'] = 'secondary';
                break;
            default:
                $data['status_class'] = 'default';
        }
        
        // Get items
        $data['items'] = array();
        
        $items = $this->model_sale_quote->getQuoteItems($quote_info['quotation_id']);
        
        foreach ($items as $item) {
            $data['items'][] = array(
                'product_name' => $item['product_name'],
                'unit_name' => $item['unit_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'price_formatted' => $this->currency->format($item['price'], $this->config->get('config_currency')),
                'discount_rate' => $item['discount_rate'],
                'tax_rate' => $item['tax_rate'],
                'total' => $item['total'],
                'total_formatted' => $this->currency->format($item['total'], $this->config->get('config_currency')),
                'notes' => $item['notes']
            );
        }
        
        // Totals
        $data['subtotal'] = $quote_info['subtotal_amount'];
        $data['discount'] = $quote_info['discount_amount'];
        $data['tax'] = $quote_info['tax_amount'];
        $data['total'] = $quote_info['total_amount'];
        
        $data['subtotal_formatted'] = $this->currency->format($quote_info['subtotal_amount'], $this->config->get('config_currency'));
        $data['discount_formatted'] = $this->currency->format($quote_info['discount_amount'], $this->config->get('config_currency'));
        $data['tax_formatted'] = $this->currency->format($quote_info['tax_amount'], $this->config->get('config_currency'));
        $data['total_formatted'] = $this->currency->format($quote_info['total_amount'], $this->config->get('config_currency'));
        
        // Notes
        $data['notes'] = $quote_info['notes'];
        
        // Company info
        $data['company_name'] = $this->config->get('config_name');
        $data['company_address'] = $this->config->get('config_address');
        $data['company_telephone'] = $this->config->get('config_telephone');
        $data['company_email'] = $this->config->get('config_email');
        $data['company_logo'] = $this->config->get('config_logo') ? HTTP_CATALOG . 'image/' . $this->config->get('config_logo') : '';
        
        // PDF setting
        $data['is_pdf'] = true;
        
        return $this->load->view('sale/quote_print', $data);
    }
    
    protected function getCustomerAddress($customer_id) {
        $this->load->model('customer/customer');
        
        $address = '';
        
        // Get default address
        $customer_info = $this->model_customer_customer->getCustomer($customer_id);
        
        if ($customer_info && $customer_info['address_id']) {
            $address_info = $this->model_customer_customer->getAddress($customer_info['address_id']);
            
            if ($address_info) {
                $address = $address_info['address_1'];
                
                if ($address_info['address_2']) {
                    $address .= ', ' . $address_info['address_2'];
                }
                
                if ($address_info['city']) {
                    $address .= ', ' . $address_info['city'];
                }
                
                if ($address_info['postcode']) {
                    $address .= ', ' . $address_info['postcode'];
                }
                
                if ($address_info['zone']) {
                    $address .= ', ' . $address_info['zone'];
                }
                
                if ($address_info['country']) {
                    $address .= ', ' . $address_info['country'];
                }
            }
        }
        
        return $address;
    }
    
    protected function getHistoryActionText($action) {
        $this->load->language('sale/quote');
        
        $action_texts = array(
            'create' => $this->language->get('text_action_create'),
            'update' => $this->language->get('text_action_update'),
            'status_draft' => $this->language->get('text_action_status_draft'),
            'status_pending' => $this->language->get('text_action_status_pending'),
            'status_approved' => $this->language->get('text_action_status_approved'),
            'status_rejected' => $this->language->get('text_action_status_rejected'),
            'status_expired' => $this->language->get('text_action_status_expired'),
            'convert' => $this->language->get('text_action_convert'),
            'email' => $this->language->get('text_action_email')
        );
        
        return isset($action_texts[$action]) ? $action_texts[$action] : $action;
    }
    
    protected function getProductTaxRate($product_id) {
        $this->load->model('catalog/product');
        
        $product_info = $this->model_catalog_product->getProduct($product_id);
        
        if ($product_info && $product_info['tax_class_id']) {
            $this->load->model('localisation/tax_class');
            
            $tax_rules = $this->model_localisation_tax_class->getTaxRules($product_info['tax_class_id']);
            
            if ($tax_rules) {
                return (float)$tax_rules[0]['rate'];
            }
        }
        
        return 0;
    }
    
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'sale/quote')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['customer_id'])) {
            $this->error['customer'] = $this->language->get('error_customer');
        }
        
        if (empty($this->request->post['quotation_date'])) {
            $this->error['quotation_date'] = $this->language->get('error_quotation_date');
        }
        
        if (empty($this->request->post['valid_until'])) {
            $this->error['valid_until'] = $this->language->get('error_valid_until');
        }
        
        return !$this->error;
    }
    
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'sale/quote')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        foreach ($this->request->post['selected'] as $quote_id) {
            $quote_info = $this->model_sale_quote->getQuote($quote_id);
            
            if ($quote_info) {
                if ($quote_info['status'] != 'draft') {
                    $this->error['warning'] = $this->language->get('error_delete_non_draft');
                    break;
                }
                
                if ($quote_info['converted_to_order']) {
                    $this->error['warning'] = $this->language->get('error_delete_converted');
                    break;
                }
            }
        }
        
        return !$this->error;
    }
}