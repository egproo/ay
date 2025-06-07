<?php
/**
 * إدارة الباركود المتقدم (Advanced Barcode Management)
 * 
 * الهدف: توفير نظام باركود متطور مع دعم أنواع متعددة وربط بالوحدات والخيارات
 * الميزات: 6 أنواع باركود، ربط بالوحدات والخيارات، توليد تلقائي، طباعة
 * التكامل: مع المنتجات والوحدات والخيارات ونقاط البيع
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryBarcode extends Controller {
    
    private $error = array();
    
    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/barcode');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/barcode');
        
        // معالجة الطلبات
        $this->getList();
    }
    
    public function add() {
        // تحميل اللغة
        $this->load->language('inventory/barcode');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/barcode');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $barcode_id = $this->model_inventory_barcode->addBarcode($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['filter_barcode'])) {
                $url .= '&filter_barcode=' . urlencode(html_entity_decode($this->request->get['filter_barcode'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('inventory/barcode', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    public function edit() {
        // تحميل اللغة
        $this->load->language('inventory/barcode');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/barcode');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_barcode->editBarcode($this->request->get['barcode_id'], $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['filter_barcode'])) {
                $url .= '&filter_barcode=' . urlencode(html_entity_decode($this->request->get['filter_barcode'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('inventory/barcode', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    public function delete() {
        // تحميل اللغة
        $this->load->language('inventory/barcode');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/barcode');
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $barcode_id) {
                $this->model_inventory_barcode->deleteBarcode($barcode_id);
            }
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['filter_barcode'])) {
                $url .= '&filter_barcode=' . urlencode(html_entity_decode($this->request->get['filter_barcode'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('inventory/barcode', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getList();
    }
    
    protected function getList() {
        // معالجة الفلاتر
        if (isset($this->request->get['filter_barcode'])) {
            $filter_barcode = $this->request->get['filter_barcode'];
        } else {
            $filter_barcode = '';
        }
        
        if (isset($this->request->get['filter_product'])) {
            $filter_product = $this->request->get['filter_product'];
        } else {
            $filter_product = '';
        }
        
        if (isset($this->request->get['filter_type'])) {
            $filter_type = $this->request->get['filter_type'];
        } else {
            $filter_type = '';
        }
        
        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }
        
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'pb.barcode';
        }
        
        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }
        
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }
        
        $url = '';
        
        if (isset($this->request->get['filter_barcode'])) {
            $url .= '&filter_barcode=' . urlencode(html_entity_decode($this->request->get['filter_barcode'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/barcode', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['add'] = $this->url->link('inventory/barcode/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('inventory/barcode/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['generate'] = $this->url->link('inventory/barcode/generate', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['print'] = $this->url->link('inventory/barcode/print', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        $data['barcodes'] = array();
        
        $filter_data = array(
            'filter_barcode' => $filter_barcode,
            'filter_product' => $filter_product,
            'filter_type'    => $filter_type,
            'filter_status'  => $filter_status,
            'sort'           => $sort,
            'order'          => $order,
            'start'          => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'          => $this->config->get('config_limit_admin')
        );
        
        $barcode_total = $this->model_inventory_barcode->getTotalBarcodes($filter_data);
        
        $results = $this->model_inventory_barcode->getBarcodes($filter_data);
        
        foreach ($results as $result) {
            $data['barcodes'][] = array(
                'barcode_id'      => $result['barcode_id'],
                'barcode'         => $result['barcode'],
                'barcode_type'    => $result['barcode_type'],
                'product_name'    => $result['product_name'],
                'model'           => $result['model'],
                'unit_name'       => $result['unit_name'],
                'option_name'     => $result['option_name'],
                'option_value_name' => $result['option_value_name'],
                'is_primary'      => $result['is_primary'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
                'status'          => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'edit'            => $this->url->link('inventory/barcode/edit', 'user_token=' . $this->session->data['user_token'] . '&barcode_id=' . $result['barcode_id'] . $url, true),
                'print'           => $this->url->link('inventory/barcode/print', 'user_token=' . $this->session->data['user_token'] . '&barcode_id=' . $result['barcode_id'], true)
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
        
        $data['filter_barcode'] = $filter_barcode;
        $data['filter_product'] = $filter_product;
        $data['filter_type'] = $filter_type;
        $data['filter_status'] = $filter_status;
        
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        // الحصول على أنواع الباركود
        $data['barcode_types'] = $this->model_inventory_barcode->getBarcodeTypes();
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/barcode_list', $data));
    }
    
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['barcode_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['barcode'])) {
            $data['error_barcode'] = $this->error['barcode'];
        } else {
            $data['error_barcode'] = '';
        }
        
        if (isset($this->error['product'])) {
            $data['error_product'] = $this->error['product'];
        } else {
            $data['error_product'] = '';
        }
        
        $url = '';
        
        if (isset($this->request->get['filter_barcode'])) {
            $url .= '&filter_barcode=' . urlencode(html_entity_decode($this->request->get['filter_barcode'], ENT_QUOTES, 'UTF-8'));
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
            'href' => $this->url->link('inventory/barcode', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        if (!isset($this->request->get['barcode_id'])) {
            $data['action'] = $this->url->link('inventory/barcode/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('inventory/barcode/edit', 'user_token=' . $this->session->data['user_token'] . '&barcode_id=' . $this->request->get['barcode_id'] . $url, true);
        }
        
        $data['cancel'] = $this->url->link('inventory/barcode', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        if (isset($this->request->get['barcode_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $barcode_info = $this->model_inventory_barcode->getBarcode($this->request->get['barcode_id']);
        }
        
        $data['user_token'] = $this->session->data['user_token'];
        
        // الحصول على أنواع الباركود
        $data['barcode_types'] = $this->model_inventory_barcode->getBarcodeTypes();
        
        // تحميل الوحدات والخيارات
        $this->load->model('inventory/units');
        $data['units'] = $this->model_inventory_units->getUnits();
        
        $this->load->model('catalog/option');
        $data['options'] = $this->model_catalog_option->getOptions();
        
        // باقي البيانات
        $fields = array('product_id', 'barcode', 'barcode_type', 'unit_id', 'option_id', 'option_value_id', 'is_primary', 'status');
        
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($barcode_info)) {
                $data[$field] = $barcode_info[$field];
            } else {
                $data[$field] = '';
            }
        }
        
        // قيم افتراضية
        if (!$data['status']) {
            $data['status'] = '1';
        }
        
        if (!$data['barcode_type']) {
            $data['barcode_type'] = 'EAN13';
        }
        
        // معلومات المنتج
        if (isset($this->request->post['product'])) {
            $data['product'] = $this->request->post['product'];
        } elseif (!empty($barcode_info)) {
            $data['product'] = $barcode_info['product_name'];
        } else {
            $data['product'] = '';
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/barcode_form', $data));
    }
    
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/barcode')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen($this->request->post['barcode']) < 1) || (utf8_strlen($this->request->post['barcode']) > 255)) {
            $this->error['barcode'] = $this->language->get('error_barcode');
        }
        
        if (!$this->request->post['product_id']) {
            $this->error['product'] = $this->language->get('error_product');
        }
        
        // التحقق من عدم تكرار الباركود
        $exclude_id = isset($this->request->get['barcode_id']) ? $this->request->get['barcode_id'] : 0;
        if ($this->model_inventory_barcode->barcodeExists($this->request->post['barcode'], $exclude_id)) {
            $this->error['barcode'] = $this->language->get('error_barcode_exists');
        }
        
        // التحقق من صحة الباركود
        if (!$this->model_inventory_barcode->validateBarcode($this->request->post['barcode'], $this->request->post['barcode_type'])) {
            $this->error['barcode'] = $this->language->get('error_barcode_invalid');
        }
        
        return !$this->error;
    }
    
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/barcode')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    /**
     * AJAX: توليد باركود تلقائي
     */
    public function generateBarcode() {
        $json = array();
        
        if (isset($this->request->post['product_id'])) {
            $this->load->model('inventory/barcode');
            
            $product_id = (int)$this->request->post['product_id'];
            $unit_id = isset($this->request->post['unit_id']) ? (int)$this->request->post['unit_id'] : 0;
            $option_id = isset($this->request->post['option_id']) ? (int)$this->request->post['option_id'] : 0;
            $option_value_id = isset($this->request->post['option_value_id']) ? (int)$this->request->post['option_value_id'] : 0;
            $type = isset($this->request->post['barcode_type']) ? $this->request->post['barcode_type'] : 'EAN13';
            
            $barcode = $this->model_inventory_barcode->generateBarcode($product_id, $unit_id, $option_id, $option_value_id, $type);
            
            if ($barcode) {
                $json['barcode'] = $barcode;
                $json['success'] = $this->language->get('text_barcode_generated');
            } else {
                $json['error'] = $this->language->get('error_barcode_generation');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * AJAX: البحث عن منتج بالباركود
     */
    public function search() {
        $json = array();
        
        if (isset($this->request->get['barcode'])) {
            $this->load->model('inventory/barcode');
            
            $product = $this->model_inventory_barcode->getProductByBarcode($this->request->get['barcode']);
            
            if ($product) {
                $json = array(
                    'product_id'        => $product['product_id'],
                    'product_name'      => $product['product_name'],
                    'model'             => $product['model'],
                    'unit_name'         => $product['unit_name'],
                    'unit_symbol'       => $product['unit_symbol'],
                    'option_name'       => $product['option_name'],
                    'option_value_name' => $product['option_value_name'],
                    'price'             => $product['price'],
                    'quantity'          => $product['quantity']
                );
            } else {
                $json['error'] = $this->language->get('error_barcode_not_found');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * طباعة الباركود
     */
    public function print() {
        $this->load->language('inventory/barcode');
        $this->load->model('inventory/barcode');
        
        if (isset($this->request->get['barcode_id'])) {
            $barcode_info = $this->model_inventory_barcode->getBarcode($this->request->get['barcode_id']);
            
            if ($barcode_info) {
                // إنشاء صفحة طباعة الباركود
                $data['barcode_info'] = $barcode_info;
                $data['barcode_image'] = $this->generateBarcodeImage($barcode_info['barcode'], $barcode_info['barcode_type']);
                
                $this->response->setOutput($this->load->view('inventory/barcode_print', $data));
            }
        } elseif (isset($this->request->get['barcode']) && isset($this->request->get['type'])) {
            // طباعة مباشرة بالباركود والنوع
            $data['barcode'] = $this->request->get['barcode'];
            $data['barcode_type'] = $this->request->get['type'];
            $data['barcode_image'] = $this->generateBarcodeImage($data['barcode'], $data['barcode_type']);
            
            $this->response->setOutput($this->load->view('inventory/barcode_print_simple', $data));
        }
    }
    
    /**
     * توليد صورة الباركود
     */
    private function generateBarcodeImage($barcode, $type) {
        // هنا يمكن استخدام مكتبة لتوليد صور الباركود
        // مثل TCPDF أو مكتبة أخرى
        
        // للتبسيط، سنعيد رابط لصورة افتراضية
        return 'data:image/svg+xml;base64,' . base64_encode($this->generateBarcodeSVG($barcode, $type));
    }
    
    /**
     * توليد SVG للباركود
     */
    private function generateBarcodeSVG($barcode, $type) {
        // توليد SVG بسيط للباركود
        $svg = '<svg width="200" height="80" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="200" height="60" fill="white" stroke="black"/>';
        $svg .= '<text x="100" y="35" text-anchor="middle" font-family="monospace" font-size="12">' . $barcode . '</text>';
        $svg .= '<text x="100" y="75" text-anchor="middle" font-family="Arial" font-size="10">' . $type . '</text>';
        $svg .= '</svg>';
        
        return $svg;
    }
}
