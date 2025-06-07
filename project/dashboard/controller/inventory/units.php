<?php
/**
 * إدارة الوحدات المتطورة (Advanced Units Management)
 * 
 * الهدف: توفير نظام وحدات متطور مع تحويل تلقائي بين الوحدات
 * الميزات: وحدات أساسية وفرعية وعليا، معاملات تحويل، تحويل تلقائي
 * التكامل: مع المنتجات والتسعير والباركود والخيارات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryUnits extends Controller {
    
    private $error = array();
    
    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/units');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/units');
        
        // معالجة الطلبات
        $this->getList();
    }
    
    public function add() {
        // تحميل اللغة
        $this->load->language('inventory/units');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/units');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $unit_id = $this->model_inventory_units->addUnit($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('inventory/units', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    public function edit() {
        // تحميل اللغة
        $this->load->language('inventory/units');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/units');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_units->editUnit($this->request->get['unit_id'], $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('inventory/units', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    public function delete() {
        // تحميل اللغة
        $this->load->language('inventory/units');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/units');
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $unit_id) {
                if ($this->model_inventory_units->canDeleteUnit($unit_id)) {
                    $this->model_inventory_units->deleteUnit($unit_id);
                } else {
                    $this->error['warning'] = $this->language->get('error_unit_in_use');
                }
            }
            
            if (!$this->error) {
                $this->session->data['success'] = $this->language->get('text_success');
            }
            
            $url = '';
            
            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('inventory/units', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getList();
    }
    
    protected function getList() {
        // معالجة الفلاتر
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }
        
        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }
        
        if (isset($this->request->get['filter_type'])) {
            $filter_type = $this->request->get['filter_type'];
        } else {
            $filter_type = '';
        }
        
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'ud.name';
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
        
        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }
        
        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }
        
        if (isset($this->request->get['filter_type'])) {
            $url .= '&filter_type=' . $this->request->get['filter_type'];
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
            'href' => $this->url->link('inventory/units', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['add'] = $this->url->link('inventory/units/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('inventory/units/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        $data['units'] = array();
        
        $filter_data = array(
            'filter_name'   => $filter_name,
            'filter_status' => $filter_status,
            'filter_type'   => $filter_type,
            'sort'          => $sort,
            'order'         => $order,
            'start'         => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'         => $this->config->get('config_limit_admin')
        );
        
        $unit_total = $this->model_inventory_units->getTotalUnits($filter_data);
        
        $results = $this->model_inventory_units->getUnits($filter_data);
        
        foreach ($results as $result) {
            $data['units'][] = array(
                'unit_id'         => $result['unit_id'],
                'name'            => $result['name'],
                'symbol'          => $result['symbol'],
                'type'            => $this->language->get('text_unit_type_' . $result['type']),
                'base_unit_name'  => $result['base_unit_name'],
                'conversion_factor' => $result['conversion_factor'],
                'status'          => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'edit'            => $this->url->link('inventory/units/edit', 'user_token=' . $this->session->data['user_token'] . '&unit_id=' . $result['unit_id'] . $url, true)
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
        
        $data['filter_name'] = $filter_name;
        $data['filter_status'] = $filter_status;
        $data['filter_type'] = $filter_type;
        
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        // الحصول على شجرة الوحدات للعرض
        $data['units_tree'] = $this->model_inventory_units->getUnitsTree();
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/units_list', $data));
    }
    
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['unit_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = array();
        }
        
        $url = '';
        
        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
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
            'href' => $this->url->link('inventory/units', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        if (!isset($this->request->get['unit_id'])) {
            $data['action'] = $this->url->link('inventory/units/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('inventory/units/edit', 'user_token=' . $this->session->data['user_token'] . '&unit_id=' . $this->request->get['unit_id'] . $url, true);
        }
        
        $data['cancel'] = $this->url->link('inventory/units', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        if (isset($this->request->get['unit_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $unit_info = $this->model_inventory_units->getUnit($this->request->get['unit_id']);
        }
        
        $data['user_token'] = $this->session->data['user_token'];
        
        // تحميل اللغات
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
        
        // معلومات الوحدة الأساسية
        if (isset($this->request->post['unit_description'])) {
            $data['unit_description'] = $this->request->post['unit_description'];
        } elseif (isset($this->request->get['unit_id'])) {
            $data['unit_description'] = $this->model_inventory_units->getUnitDescriptions($this->request->get['unit_id']);
        } else {
            $data['unit_description'] = array();
        }
        
        // الحصول على الوحدات الأساسية للاختيار منها
        $data['base_units'] = $this->model_inventory_units->getBaseUnits();
        
        // باقي البيانات
        $fields = array('type', 'base_unit_id', 'conversion_factor', 'decimal_places', 'status', 'sort_order');
        
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($unit_info)) {
                $data[$field] = $unit_info[$field];
            } else {
                $data[$field] = '';
            }
        }
        
        // قيم افتراضية
        if (!$data['conversion_factor']) {
            $data['conversion_factor'] = '1';
        }
        
        if (!$data['decimal_places']) {
            $data['decimal_places'] = '0';
        }
        
        if (!$data['status']) {
            $data['status'] = '1';
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/units_form', $data));
    }
    
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/units')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        foreach ($this->request->post['unit_description'] as $language_id => $value) {
            if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 64)) {
                $this->error['name'][$language_id] = $this->language->get('error_name');
            }
        }
        
        return !$this->error;
    }
    
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/units')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    /**
     * AJAX: البحث في الوحدات
     */
    public function autocomplete() {
        $json = array();
        
        if (isset($this->request->get['filter_name'])) {
            $this->load->model('inventory/units');
            
            $results = $this->model_inventory_units->getUnitsAutocomplete($this->request->get['filter_name']);
            
            foreach ($results as $result) {
                $json[] = array(
                    'unit_id' => $result['unit_id'],
                    'name'    => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'symbol'  => $result['symbol']
                );
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * AJAX: تحويل الكمية بين الوحدات
     */
    public function convert() {
        $json = array();
        
        if (isset($this->request->get['quantity']) && isset($this->request->get['from_unit_id']) && isset($this->request->get['to_unit_id'])) {
            $this->load->model('inventory/units');
            
            $converted_quantity = $this->model_inventory_units->convertQuantity(
                (float)$this->request->get['quantity'],
                (int)$this->request->get['from_unit_id'],
                (int)$this->request->get['to_unit_id']
            );
            
            $json = array('converted_quantity' => $converted_quantity);
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * إنشاء الوحدات الافتراضية
     */
    public function createDefaults() {
        $this->load->language('inventory/units');
        $this->load->model('inventory/units');
        
        $this->model_inventory_units->createDefaultUnits();
        
        $this->session->data['success'] = $this->language->get('text_defaults_created');
        
        $this->response->redirect($this->url->link('inventory/units', 'user_token=' . $this->session->data['user_token'], true));
    }
}
