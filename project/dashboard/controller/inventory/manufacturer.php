<?php
/**
 * إدارة العلامات التجارية المتطورة (Advanced Manufacturers Management)
 * 
 * الهدف: توفير إدارة شاملة للعلامات التجارية مع ميزات متقدمة
 * الميزات: معلومات مفصلة، ربط محاسبي، تقارير، تكويد تلقائي
 * التكامل: مع المنتجات والمحاسبة والتقارير والمشتريات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryManufacturer extends Controller {
    
    private $error = array();
    
    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/manufacturer');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/manufacturer');
        
        // معالجة الطلبات
        $this->getList();
    }
    
    public function add() {
        // تحميل اللغة
        $this->load->language('inventory/manufacturer');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/manufacturer');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $manufacturer_id = $this->model_inventory_manufacturer->addManufacturer($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('inventory/manufacturer', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    public function edit() {
        // تحميل اللغة
        $this->load->language('inventory/manufacturer');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/manufacturer');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_manufacturer->editManufacturer($this->request->get['manufacturer_id'], $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('inventory/manufacturer', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    public function delete() {
        // تحميل اللغة
        $this->load->language('inventory/manufacturer');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/manufacturer');
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $manufacturer_id) {
                if ($this->model_inventory_manufacturer->canDeleteManufacturer($manufacturer_id)) {
                    $this->model_inventory_manufacturer->deleteManufacturer($manufacturer_id);
                } else {
                    $this->error['warning'] = $this->language->get('error_manufacturer_in_use');
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
            
            $this->response->redirect($this->url->link('inventory/manufacturer', 'user_token=' . $this->session->data['user_token'] . $url, true));
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
        
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'md.name';
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
            'href' => $this->url->link('inventory/manufacturer', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['add'] = $this->url->link('inventory/manufacturer/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('inventory/manufacturer/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        $data['manufacturers'] = array();
        
        $filter_data = array(
            'filter_name'   => $filter_name,
            'filter_status' => $filter_status,
            'sort'          => $sort,
            'order'         => $order,
            'start'         => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'         => $this->config->get('config_limit_admin')
        );
        
        $manufacturer_total = $this->model_inventory_manufacturer->getTotalManufacturers($filter_data);
        
        $results = $this->model_inventory_manufacturer->getManufacturers($filter_data);
        
        foreach ($results as $result) {
            $data['manufacturers'][] = array(
                'manufacturer_id' => $result['manufacturer_id'],
                'name'            => $result['name'],
                'code_prefix'     => $result['code_prefix'],
                'contact_person'  => $result['contact_person'],
                'email'           => $result['email'],
                'telephone'       => $result['telephone'],
                'website'         => $result['website'],
                'sort_order'      => $result['sort_order'],
                'status'          => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'edit'            => $this->url->link('inventory/manufacturer/edit', 'user_token=' . $this->session->data['user_token'] . '&manufacturer_id=' . $result['manufacturer_id'] . $url, true)
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
        
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/manufacturer_list', $data));
    }
    
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['manufacturer_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        
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
            'href' => $this->url->link('inventory/manufacturer', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        if (!isset($this->request->get['manufacturer_id'])) {
            $data['action'] = $this->url->link('inventory/manufacturer/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('inventory/manufacturer/edit', 'user_token=' . $this->session->data['user_token'] . '&manufacturer_id=' . $this->request->get['manufacturer_id'] . $url, true);
        }
        
        $data['cancel'] = $this->url->link('inventory/manufacturer', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        if (isset($this->request->get['manufacturer_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $manufacturer_info = $this->model_inventory_manufacturer->getManufacturer($this->request->get['manufacturer_id']);
        }
        
        $data['user_token'] = $this->session->data['user_token'];
        
        // تحميل اللغات
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
        
        // معلومات العلامة التجارية الأساسية
        if (isset($this->request->post['manufacturer_description'])) {
            $data['manufacturer_description'] = $this->request->post['manufacturer_description'];
        } elseif (isset($this->request->get['manufacturer_id'])) {
            $data['manufacturer_description'] = $this->model_inventory_manufacturer->getManufacturerDescriptions($this->request->get['manufacturer_id']);
        } else {
            $data['manufacturer_description'] = array();
        }
        
        // تحميل المتاجر
        $this->load->model('setting/store');
        $data['stores'] = array();
        $data['stores'][] = array(
            'store_id' => 0,
            'name'     => $this->language->get('text_default')
        );
        $stores = $this->model_setting_store->getStores();
        foreach ($stores as $store) {
            $data['stores'][] = array(
                'store_id' => $store['store_id'],
                'name'     => $store['name']
            );
        }
        
        if (isset($this->request->post['manufacturer_store'])) {
            $data['manufacturer_store'] = $this->request->post['manufacturer_store'];
        } elseif (isset($this->request->get['manufacturer_id'])) {
            $data['manufacturer_store'] = $this->model_inventory_manufacturer->getManufacturerStores($this->request->get['manufacturer_id']);
        } else {
            $data['manufacturer_store'] = array(0);
        }
        
        // تحميل البلدان والمناطق
        $this->load->model('localisation/country');
        $data['countries'] = $this->model_localisation_country->getCountries();
        
        // باقي البيانات
        $fields = array(
            'name', 'image', 'sort_order', 'status', 'code_prefix', 'contact_person', 
            'email', 'telephone', 'fax', 'website', 'address', 'city', 'country_id', 
            'zone_id', 'tax_number', 'commercial_register', 'account_id', 'payment_terms', 
            'credit_limit', 'commission_rate', 'notes', 'keyword'
        );
        
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($manufacturer_info)) {
                $data[$field] = $manufacturer_info[$field];
            } else {
                $data[$field] = '';
            }
        }
        
        // قيم افتراضية
        if (!$data['status']) {
            $data['status'] = '1';
        }
        
        if (!$data['payment_terms']) {
            $data['payment_terms'] = '30';
        }
        
        if (!$data['credit_limit']) {
            $data['credit_limit'] = '0';
        }
        
        if (!$data['commission_rate']) {
            $data['commission_rate'] = '0';
        }
        
        // توليد بادئة الكود التلقائي
        if (!$data['code_prefix'] && isset($this->request->post['name'])) {
            $data['code_prefix'] = $this->model_inventory_manufacturer->generateManufacturerPrefix($this->request->post['name']);
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/manufacturer_form', $data));
    }
    
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/manufacturer')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }
        
        return !$this->error;
    }
    
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/manufacturer')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    /**
     * AJAX: البحث في العلامات التجارية
     */
    public function autocomplete() {
        $json = array();
        
        if (isset($this->request->get['filter_name'])) {
            $this->load->model('inventory/manufacturer');
            
            $results = $this->model_inventory_manufacturer->getManufacturersAutocomplete($this->request->get['filter_name']);
            
            foreach ($results as $result) {
                $json[] = array(
                    'manufacturer_id' => $result['manufacturer_id'],
                    'name'            => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
