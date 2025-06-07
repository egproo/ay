<?php
/**
 * إدارة التصنيفات المتطورة (Advanced Categories Management)
 * 
 * الهدف: توفير نظام تصنيفات هرمي متطور مع ميزات متقدمة
 * الميزات: هيكل شجري، تكويد تلقائي، صور، SEO، ربط بالمحاسبة
 * التكامل: مع المنتجات والمحاسبة والتقارير
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryCategory extends Controller {
    
    private $error = array();
    
    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/category');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/category');
        
        // معالجة الطلبات
        $this->getList();
    }
    
    public function add() {
        // تحميل اللغة
        $this->load->language('inventory/category');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/category');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $category_id = $this->model_inventory_category->addCategory($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('inventory/category', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    public function edit() {
        // تحميل اللغة
        $this->load->language('inventory/category');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/category');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_category->editCategory($this->request->get['category_id'], $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $url = '';
            
            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }
            
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            
            $this->response->redirect($this->url->link('inventory/category', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    public function delete() {
        // تحميل اللغة
        $this->load->language('inventory/category');
        
        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));
        
        // تحميل النماذج المطلوبة
        $this->load->model('inventory/category');
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $category_id) {
                if ($this->model_inventory_category->canDeleteCategory($category_id)) {
                    $this->model_inventory_category->deleteCategory($category_id);
                } else {
                    $this->error['warning'] = $this->language->get('error_category_in_use');
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
            
            $this->response->redirect($this->url->link('inventory/category', 'user_token=' . $this->session->data['user_token'] . $url, true));
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
            $sort = 'name';
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
            'href' => $this->url->link('inventory/category', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['add'] = $this->url->link('inventory/category/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('inventory/category/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        $data['categories'] = array();
        
        $filter_data = array(
            'filter_name'   => $filter_name,
            'filter_status' => $filter_status,
            'sort'          => $sort,
            'order'         => $order,
            'start'         => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'         => $this->config->get('config_limit_admin')
        );
        
        $category_total = $this->model_inventory_category->getTotalCategories($filter_data);
        
        $results = $this->model_inventory_category->getCategories($filter_data);
        
        foreach ($results as $result) {
            $data['categories'][] = array(
                'category_id'  => $result['category_id'],
                'name'         => $result['name'],
                'code_prefix'  => $result['code_prefix'],
                'sort_order'   => $result['sort_order'],
                'status'       => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'edit'         => $this->url->link('inventory/category/edit', 'user_token=' . $this->session->data['user_token'] . '&category_id=' . $result['category_id'] . $url, true)
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
        
        // الحصول على شجرة التصنيفات للعرض
        $data['categories_tree'] = $this->model_inventory_category->getCategoriesTree();
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/category_list', $data));
    }
    
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['category_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        
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
        
        if (isset($this->error['keyword'])) {
            $data['error_keyword'] = $this->error['keyword'];
        } else {
            $data['error_keyword'] = '';
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
            'href' => $this->url->link('inventory/category', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        if (!isset($this->request->get['category_id'])) {
            $data['action'] = $this->url->link('inventory/category/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('inventory/category/edit', 'user_token=' . $this->session->data['user_token'] . '&category_id=' . $this->request->get['category_id'] . $url, true);
        }
        
        $data['cancel'] = $this->url->link('inventory/category', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        if (isset($this->request->get['category_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $category_info = $this->model_inventory_category->getCategory($this->request->get['category_id']);
        }
        
        $data['user_token'] = $this->session->data['user_token'];
        
        // تحميل اللغات
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
        
        // معلومات التصنيف الأساسية
        if (isset($this->request->post['category_description'])) {
            $data['category_description'] = $this->request->post['category_description'];
        } elseif (isset($this->request->get['category_id'])) {
            $data['category_description'] = $this->model_inventory_category->getCategoryDescriptions($this->request->get['category_id']);
        } else {
            $data['category_description'] = array();
        }
        
        // الحصول على التصنيفات للاختيار كأب
        $data['categories'] = $this->model_inventory_category->getCategories();
        
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
        
        if (isset($this->request->post['category_store'])) {
            $data['category_store'] = $this->request->post['category_store'];
        } elseif (isset($this->request->get['category_id'])) {
            $data['category_store'] = $this->model_inventory_category->getCategoryStores($this->request->get['category_id']);
        } else {
            $data['category_store'] = array(0);
        }
        
        // تحميل التخطيطات
        $this->load->model('design/layout');
        $data['layouts'] = $this->model_design_layout->getLayouts();
        
        if (isset($this->request->post['category_layout'])) {
            $data['category_layout'] = $this->request->post['category_layout'];
        } elseif (isset($this->request->get['category_id'])) {
            $data['category_layout'] = $this->model_inventory_category->getCategoryLayouts($this->request->get['category_id']);
        } else {
            $data['category_layout'] = array();
        }
        
        // باقي البيانات
        $fields = array('image', 'parent_id', 'top', 'column', 'sort_order', 'status', 'code_prefix', 'account_id', 'commission_rate', 'keyword');
        
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($category_info)) {
                $data[$field] = $category_info[$field];
            } else {
                $data[$field] = '';
            }
        }
        
        // قيم افتراضية
        if (!$data['status']) {
            $data['status'] = '1';
        }
        
        if (!$data['top']) {
            $data['top'] = '0';
        }
        
        if (!$data['column']) {
            $data['column'] = '1';
        }
        
        // توليد بادئة الكود التلقائي
        if (!$data['code_prefix'] && isset($this->request->post['category_description'][1]['name'])) {
            $data['code_prefix'] = $this->model_inventory_category->generateCategoryPrefix($this->request->post['category_description'][1]['name']);
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('inventory/category_form', $data));
    }
    
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/category')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        foreach ($this->request->post['category_description'] as $language_id => $value) {
            if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
                $this->error['name'][$language_id] = $this->language->get('error_name');
            }
        }
        
        if (utf8_strlen($this->request->post['keyword']) > 0) {
            $this->load->model('catalog/seo_url');
            
            $seo_urls = $this->model_catalog_seo_url->getSeoUrlsByKeyword($this->request->post['keyword']);
            
            foreach ($seo_urls as $seo_url) {
                if (!isset($this->request->get['category_id'])) {
                    $this->error['keyword'] = $this->language->get('error_keyword');
                    break;
                } elseif ($seo_url['query'] != 'category_id=' . $this->request->get['category_id']) {
                    $this->error['keyword'] = $this->language->get('error_keyword');
                    break;
                }
            }
        }
        
        return !$this->error;
    }
    
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/category')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    /**
     * AJAX: البحث في التصنيفات
     */
    public function autocomplete() {
        $json = array();
        
        if (isset($this->request->get['filter_name'])) {
            $this->load->model('inventory/category');
            
            $results = $this->model_inventory_category->getCategoriesAutocomplete($this->request->get['filter_name']);
            
            foreach ($results as $result) {
                $json[] = array(
                    'category_id' => $result['category_id'],
                    'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
