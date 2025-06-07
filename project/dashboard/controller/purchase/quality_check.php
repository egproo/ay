<?php
class ControllerPurchaseQualityCheck extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('purchase/quality_check');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('purchase/quality_check');
        $this->load->model('purchase/goods_receipt');
        
        // User permissions check
        if (!$this->user->hasPermission('access', 'purchase/quality_check')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Set up breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/quality_check', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Handle session messages
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
        
        // Filter setup
        $data['token'] = $this->session->data['user_token'];
        
        if (isset($this->request->get['filter_receipt_number'])) {
            $filter_receipt_number = $this->request->get['filter_receipt_number'];
            $data['filter_receipt_number'] = $this->request->get['filter_receipt_number'];
        } else {
            $filter_receipt_number = '';
            $data['filter_receipt_number'] = '';
        }
        
        if (isset($this->request->get['filter_po_number'])) {
            $filter_po_number = $this->request->get['filter_po_number'];
            $data['filter_po_number'] = $this->request->get['filter_po_number'];
        } else {
            $filter_po_number = '';
            $data['filter_po_number'] = '';
        }
        
        if (isset($this->request->get['filter_supplier'])) {
            $filter_supplier = $this->request->get['filter_supplier'];
            $data['filter_supplier'] = $this->request->get['filter_supplier'];
        } else {
            $filter_supplier = '';
            $data['filter_supplier'] = '';
        }
        
        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
            $data['filter_status'] = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
            $data['filter_status'] = '';
        }
        
        if (isset($this->request->get['filter_date_from'])) {
            $filter_date_from = $this->request->get['filter_date_from'];
            $data['filter_date_from'] = $this->request->get['filter_date_from'];
        } else {
            $filter_date_from = '';
            $data['filter_date_from'] = '';
        }
        
        if (isset($this->request->get['filter_date_to'])) {
            $filter_date_to = $this->request->get['filter_date_to'];
            $data['filter_date_to'] = $this->request->get['filter_date_to'];
        } else {
            $filter_date_to = '';
            $data['filter_date_to'] = '';
        }
        
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }
        
        // Data preparation
        $filter_data = array(
            'filter_receipt_number' => $filter_receipt_number,
            'filter_po_number'      => $filter_po_number,
            'filter_supplier'       => $filter_supplier,
            'filter_status'         => $filter_status,
            'filter_date_from'      => $filter_date_from,
            'filter_date_to'        => $filter_date_to,
            'sort'                  => 'gr.created_at',
            'order'                 => 'DESC',
            'start'                 => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'                 => $this->config->get('config_limit_admin')
        );
        
        // Get quality check statistics
        $data['stats'] = $this->model_purchase_quality_check->getQualityCheckStats();
        
        // Get quality check list
        $receipts = $this->model_purchase_quality_check->getQualityCheckList($filter_data);
        $total_receipts = $this->model_purchase_quality_check->getTotalQualityCheckList($filter_data);
        
        $data['receipts'] = array();
        
        foreach ($receipts as $receipt) {
            $data['receipts'][] = array(
                'goods_receipt_id' => $receipt['goods_receipt_id'],
                'receipt_number'   => $receipt['receipt_number'],
                'po_number'        => $receipt['po_number'],
                'supplier_name'    => $receipt['supplier_name'],
                'receipt_date'     => date($this->language->get('date_format_short'), strtotime($receipt['receipt_date'])),
                'quality_status'   => $receipt['quality_status'],
                'check_url'        => $this->url->link('purchase/quality_check/form', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $receipt['goods_receipt_id'], true)
            );
        }
        
        // Pagination setup
        $pagination = new Pagination();
        $pagination->total = $total_receipts;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('purchase/quality_check', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total_receipts) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($total_receipts - $this->config->get('config_limit_admin'))) ? $total_receipts : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $total_receipts, ceil($total_receipts / $this->config->get('config_limit_admin')));
        
        // Language variables
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_filter'] = $this->language->get('text_filter');
        $data['text_all_status'] = $this->language->get('text_all_status');
        $data['text_status_pending'] = $this->language->get('text_status_pending');
        $data['text_quality_status_pass'] = $this->language->get('text_quality_status_pass');
        $data['text_quality_status_fail'] = $this->language->get('text_quality_status_fail');
        $data['text_quality_status_partial'] = $this->language->get('text_quality_status_partial');
        $data['text_date_from'] = $this->language->get('text_date_from');
        $data['text_date_to'] = $this->language->get('text_date_to');
        
        $data['column_receipt_number'] = $this->language->get('column_receipt_number');
        $data['column_po_number'] = $this->language->get('column_po_number');
        $data['column_supplier'] = $this->language->get('column_supplier');
        $data['column_receipt_date'] = $this->language->get('column_receipt_date');
        $data['column_quality_status'] = $this->language->get('column_quality_status');
        $data['column_action'] = $this->language->get('column_action');
        
        $data['button_filter'] = $this->language->get('button_filter');
        $data['button_reset'] = $this->language->get('button_reset');
        $data['button_check'] = $this->language->get('button_check');
        
        // Load template
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('purchase/quality_check_list', $data));
    }
    
    public function form() {
        $this->load->language('purchase/quality_check');
        
        $this->document->setTitle($this->language->get('heading_title_form'));
        
        $this->load->model('purchase/quality_check');
        $this->load->model('purchase/goods_receipt');
        
        // Check permissions
        if (!$this->user->hasPermission('modify', 'purchase/quality_check')) {
            $this->response->redirect($this->url->link('purchase/quality_check', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        if (isset($this->request->get['goods_receipt_id'])) {
            $goods_receipt_id = $this->request->get['goods_receipt_id'];
        } else {
            $this->response->redirect($this->url->link('purchase/quality_check', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $receipt_info = $this->model_purchase_goods_receipt->getGoodsReceipt($goods_receipt_id);
        
        if (!$receipt_info) {
            $this->response->redirect($this->url->link('purchase/quality_check', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // Breadcrumbs
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/quality_check', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $receipt_info['receipt_number'],
            'href' => $this->url->link('purchase/quality_check/form', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $goods_receipt_id, true)
        );
        
        // Receipt data
        $data['goods_receipt_id'] = $goods_receipt_id;
        $data['receipt_number'] = $receipt_info['receipt_number'];
        $data['po_number'] = $receipt_info['po_number'];
        $data['supplier_name'] = $receipt_info['supplier_name'];
        $data['receipt_date'] = date($this->language->get('date_format_short'), strtotime($receipt_info['receipt_date']));
        $data['quality_notes'] = $receipt_info['quality_notes'];
        $data['quality_status'] = $receipt_info['quality_status'];
        
        // Get receipt items
        $data['items'] = $this->model_purchase_quality_check->getReceiptItems($goods_receipt_id);
        
        // Action URLs
        $data['save_url'] = $this->url->link('purchase/quality_check/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['back_url'] = $this->url->link('purchase/quality_check', 'user_token=' . $this->session->data['user_token'], true);
        $data['item_check_url'] = $this->url->link('purchase/quality_check/ajaxItemCheck', 'user_token=' . $this->session->data['user_token'], true);
        
        // Language variables
        $data['heading_title'] = $this->language->get('heading_title_form');
        $data['text_form'] = $this->language->get('text_form');
        $data['text_quality_check'] = $this->language->get('text_quality_check');
        $data['text_receipt_details'] = $this->language->get('text_receipt_details');
        $data['text_item_list'] = $this->language->get('text_item_list');
        $data['text_select'] = $this->language->get('text_select');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['text_status_pending'] = $this->language->get('text_status_pending');
        $data['text_quality_status_pass'] = $this->language->get('text_quality_status_pass');
        $data['text_quality_status_fail'] = $this->language->get('text_quality_status_fail');
        $data['text_quality_status_partial'] = $this->language->get('text_quality_status_partial');
        
        $data['entry_receipt_number'] = $this->language->get('entry_receipt_number');
        $data['entry_po_number'] = $this->language->get('entry_po_number');
        $data['entry_supplier'] = $this->language->get('entry_supplier');
        $data['entry_date'] = $this->language->get('entry_date');
        $data['entry_quality_status'] = $this->language->get('entry_quality_status');
        $data['entry_notes'] = $this->language->get('entry_notes');
        $data['entry_quality_notes'] = $this->language->get('entry_quality_notes');
        
        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_quality_status'] = $this->language->get('column_quality_status');
        $data['column_accepted'] = $this->language->get('column_accepted');
        $data['column_notes'] = $this->language->get('column_notes');
        $data['column_action'] = $this->language->get('column_action');
        
        $data['button_save'] = $this->language->get('button_save');
        $data['button_back'] = $this->language->get('button_back');
        $data['button_check'] = $this->language->get('button_check');
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('purchase/quality_check_form', $data));
    }
    
    public function save() {
        $this->load->language('purchase/quality_check');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'purchase/quality_check')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (isset($this->request->post['goods_receipt_id']) && $this->request->post['goods_receipt_id']) {
            $this->load->model('purchase/quality_check');
            
            $goods_receipt_id = $this->request->post['goods_receipt_id'];
            
            // Update quality notes
            if (isset($this->request->post['quality_notes'])) {
                $this->model_purchase_quality_check->updateQualityNotes($goods_receipt_id, $this->request->post['quality_notes']);
            }
            
            // Process item quality statuses if provided
            if (isset($this->request->post['items']) && is_array($this->request->post['items'])) {
                foreach ($this->request->post['items'] as $item_id => $item) {
                    if (isset($item['quality_status']) && $item['quality_status']) {
                        $accepted_percentage = isset($item['accepted_percentage']) ? (float)$item['accepted_percentage'] : 100;
                        $notes = isset($item['notes']) ? $item['notes'] : '';
                        
                        $this->model_purchase_quality_check->updateItemQualityStatus(
                            $item_id, 
                            $item['quality_status'],
                            $accepted_percentage,
                            $notes
                        );
                    }
                }
            }
            
            // Update receipt quality status
            $new_status = $this->model_purchase_quality_check->updateReceiptQualityStatus($goods_receipt_id);
            
            // Add history record
            $comment = $this->language->get('text_quality_check_completed');
            $this->model_purchase_quality_check->addQualityCheckHistory($goods_receipt_id, $new_status, $comment);
            
            // Update inventory if needed
            if ($new_status == 'passed' || $new_status == 'partial') {
                $this->model_purchase_quality_check->updateInventoryAfterQualityCheck($goods_receipt_id);
            }
            
            $json['success'] = $this->language->get('text_success');
            $json['redirect'] = $this->url->link('purchase/quality_check', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $json['error'] = $this->language->get('error_receipt');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function ajaxItemCheck() {
        $this->load->language('purchase/quality_check');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'purchase/quality_check')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (empty($this->request->post['item_id'])) {
            $json['error'] = $this->language->get('error_item');
        } else if (empty($this->request->post['quality_status']) || !in_array($this->request->post['quality_status'], array('pass', 'fail', 'partial'))) {
            $json['error'] = $this->language->get('error_status');
        } else {
            $this->load->model('purchase/quality_check');
            
            $item_id = (int)$this->request->post['item_id'];
            $quality_status = $this->request->post['quality_status'];
            $accepted_percentage = isset($this->request->post['accepted_percentage']) ? (float)$this->request->post['accepted_percentage'] : 100;
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';
            
            // Validate accepted percentage for partial status
            if ($quality_status == 'partial' && ($accepted_percentage <= 0 || $accepted_percentage >= 100)) {
                $json['error'] = $this->language->get('error_percentage');
            } else {
                // Update item quality status
                $this->model_purchase_quality_check->updateItemQualityStatus($item_id, $quality_status, $accepted_percentage, $notes);
                
                // Get updated item data
                $item_info = $this->model_purchase_quality_check->getReceiptItem($item_id);
                
                if ($item_info) {
                    $json['item'] = array(
                        'goods_receipt_item_id' => $item_info['goods_receipt_item_id'],
                        'quality_status' => $item_info['quality_status'],
                        'accepted_percentage' => $item_info['accepted_percentage'],
                        'notes' => $item_info['quality_notes']
                    );
                    
                    $json['success'] = $this->language->get('text_item_updated');
                } else {
                    $json['error'] = $this->language->get('error_item_not_found');
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function ajaxGetQualityList() {
        $this->load->language('purchase/quality_check');
        
        $json = array();
        
        if (!$this->user->hasPermission('access', 'purchase/quality_check')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/quality_check');
            
            // Prepare filter data
            $filter_data = array();
            
            if (isset($this->request->get['filter_receipt_number'])) {
                $filter_data['filter_receipt_number'] = $this->request->get['filter_receipt_number'];
            }
            
            if (isset($this->request->get['filter_po_number'])) {
                $filter_data['filter_po_number'] = $this->request->get['filter_po_number'];
            }
            
            if (isset($this->request->get['filter_supplier'])) {
                $filter_data['filter_supplier'] = $this->request->get['filter_supplier'];
            }
            
            if (isset($this->request->get['filter_status'])) {
                $filter_data['filter_status'] = $this->request->get['filter_status'];
            }
            
            if (isset($this->request->get['filter_date_from'])) {
                $filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
            }
            
            if (isset($this->request->get['filter_date_to'])) {
                $filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
            }
            
            // Sorting
            $filter_data['sort'] = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'gr.created_at';
            $filter_data['order'] = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';
            
            // Pagination
            $filter_data['start'] = isset($this->request->get['start']) ? (int)$this->request->get['start'] : 0;
            $filter_data['limit'] = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : $this->config->get('config_limit_admin');
            
            // Get statistics
            $json['stats'] = $this->model_purchase_quality_check->getQualityCheckStats();
            
            // Get receipts
            $results = $this->model_purchase_quality_check->getQualityCheckList($filter_data);
            $total = $this->model_purchase_quality_check->getTotalQualityCheckList($filter_data);
            
            $json['receipts'] = array();
            
            foreach ($results as $result) {
                $json['receipts'][] = array(
                    'goods_receipt_id' => $result['goods_receipt_id'],
                    'receipt_number' => $result['receipt_number'],
                    'po_number' => $result['po_number'],
                    'supplier_name' => $result['supplier_name'],
                    'receipt_date' => date($this->language->get('date_format_short'), strtotime($result['receipt_date'])),
                    'quality_status' => $result['quality_status'],
                    'check_url' => $this->url->link('purchase/quality_check/form', 'user_token=' . $this->session->data['user_token'] . '&goods_receipt_id=' . $result['goods_receipt_id'], true)
                );
            }
            
            $json['total'] = $total;
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
} 