<?php
class ControllerCatalogUnit extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('catalog/unit');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->getList();
    }

    public function list() {
        $this->load->language('catalog/unit');

        $json = array();

        // التحقق من الصلاحيات
        if (!$this->user->hasKey('access_catalog_unit')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('catalog/unit');

            $filter_data = array(
                'filter_code'    => isset($this->request->get['filter_code']) ? $this->request->get['filter_code'] : '',
                'filter_name_en' => isset($this->request->get['filter_name_en']) ? $this->request->get['filter_name_en'] : '',
                'filter_name_ar' => isset($this->request->get['filter_name_ar']) ? $this->request->get['filter_name_ar'] : '',
                'sort'           => isset($this->request->get['sort']) ? $this->request->get['sort'] : 'code',
                'order'          => isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC',
                'start'          => isset($this->request->get['page']) ? (((int)$this->request->get['page'] - 1) * $this->config->get('config_limit_admin')) : 0,
                'limit'          => $this->config->get('config_limit_admin')
            );

            $unit_total = $this->model_catalog_unit->getTotalUnits($filter_data);
            $results = $this->model_catalog_unit->getUnits($filter_data);

            $json['units'] = array();

            foreach ($results as $result) {
                $json['units'][] = array(
                    'unit_id'  => $result['unit_id'],
                    'code'     => $result['code'],
                    'desc_en'  => $result['desc_en'],
                    'desc_ar'  => $result['desc_ar']
                );
            }

            $json['total'] = $unit_total;

            // إنشاء الترقيم
            $pagination = new Pagination();
            $pagination->total = $unit_total;
            $pagination->page = $filter_data['start'] / $this->config->get('config_limit_admin') + 1;
            $pagination->limit = $this->config->get('config_limit_admin');
            $pagination->url = $this->url->link('catalog/unit', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

            $json['pagination'] = $pagination->render();
            
$json['results'] = sprintf(
    $this->language->get('text_pagination'),
    ($unit_total) ? ($filter_data['start'] + 1) : 0,  // Start item
    ($unit_total) ? min($filter_data['start'] + $this->config->get('config_limit_admin'), $unit_total) : 0,  // End item
    $unit_total,                                     // Total items
    ceil($unit_total / $this->config->get('config_limit_admin'))  // Total pages
);

        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function form() {
        $this->load->language('catalog/unit');

        $data['user_token'] = $this->session->data['user_token'];

        // التحقق من رقم الوحدة
        if (isset($this->request->get['unit_id'])) {
            $data['unit_id'] = $this->request->get['unit_id'];
        } else {
            $data['unit_id'] = 0;
        }

        // جلب بيانات الوحدة إذا كانت عملية تعديل
        if ($data['unit_id']) {
            $this->load->model('catalog/unit');
            $unit_info = $this->model_catalog_unit->getUnit($data['unit_id']);
            
            if (!$unit_info) {
                $this->response->redirect($this->url->link('catalog/unit', 'user_token=' . $this->session->data['user_token'], true));
            }
        }

        // إعداد رسائل الخطأ
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['code'])) {
            $data['error_code'] = $this->error['code'];
        } else {
            $data['error_code'] = '';
        }

        if (isset($this->error['desc_en'])) {
            $data['error_desc_en'] = $this->error['desc_en'];
        } else {
            $data['error_desc_en'] = '';
        }

        // إعداد حقول النموذج
        if (isset($this->request->post['code'])) {
            $data['code'] = $this->request->post['code'];
        } elseif (!empty($unit_info)) {
            $data['code'] = $unit_info['code'];
        } else {
            $data['code'] = '';
        }

        if (isset($this->request->post['desc_en'])) {
            $data['desc_en'] = $this->request->post['desc_en'];
        } elseif (!empty($unit_info)) {
            $data['desc_en'] = $unit_info['desc_en'];
        } else {
            $data['desc_en'] = '';
        }

        if (isset($this->request->post['desc_ar'])) {
            $data['desc_ar'] = $this->request->post['desc_ar'];
        } elseif (!empty($unit_info)) {
            $data['desc_ar'] = $unit_info['desc_ar'];
        } else {
            $data['desc_ar'] = '';
        }

        $this->response->setOutput($this->load->view('catalog/unit_form', $data));
    }

    public function add() {
        $this->load->language('catalog/unit');

        $json = array();

        // التحقق من الصلاحيات
        if (!$this->user->hasKey('modify_catalog_unit')) {
            $json['error']['warning'] = $this->language->get('error_permission');
        } else {
            $this->load->model('catalog/unit');

            // التحقق من البيانات
            if ((utf8_strlen($this->request->post['code']) < 1) || (utf8_strlen($this->request->post['code']) > 10)) {
                $json['error']['code'] = $this->language->get('error_code');
            }

            // التحقق من وجود رمز متكرر
            $unit_info = $this->model_catalog_unit->getUnitByCode($this->request->post['code']);
            if ($unit_info) {
                $json['error']['code'] = $this->language->get('error_code_exists');
            }

            if ((utf8_strlen($this->request->post['desc_en']) < 1) || (utf8_strlen($this->request->post['desc_en']) > 255)) {
                $json['error']['desc_en'] = $this->language->get('error_desc_en');
            }

            if (!isset($json['error'])) {
                // حفظ البيانات
                $this->model_catalog_unit->addUnit($this->request->post);

                // تسجيل النشاط
$this->user->logActivity('add', 'unit', 'تم إضافة وحدة قياس: ' . $this->request->post['code'], 'unit', null);
                $json['success'] = $this->language->get('text_success_add');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function edit() {
        $this->load->language('catalog/unit');

        $json = array();

        // التحقق من الصلاحيات
        if (!$this->user->hasKey('modify_catalog_unit')) {
            $json['error']['warning'] = $this->language->get('error_permission');
        } else {
            $this->load->model('catalog/unit');

            // التحقق من البيانات
            if ((utf8_strlen($this->request->post['code']) < 1) || (utf8_strlen($this->request->post['code']) > 10)) {
                $json['error']['code'] = $this->language->get('error_code');
            }

            // التحقق من وجود رمز متكرر
            $unit_info = $this->model_catalog_unit->getUnitByCode($this->request->post['code']);
            if ($unit_info && $unit_info['unit_id'] != $this->request->get['unit_id']) {
                $json['error']['code'] = $this->language->get('error_code_exists');
            }

            if ((utf8_strlen($this->request->post['desc_en']) < 1) || (utf8_strlen($this->request->post['desc_en']) > 255)) {
                $json['error']['desc_en'] = $this->language->get('error_desc_en');
            }

            if (!isset($json['error'])) {
                // حفظ البيانات
                $this->model_catalog_unit->editUnit($this->request->get['unit_id'], $this->request->post);

                // تسجيل النشاط
$this->user->logActivity('edit', 'unit', 'تم تعديل وحدة قياس: ' . $this->request->post['code'], 'unit', null);

                $json['success'] = $this->language->get('text_success_edit');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('catalog/unit');

        $json = array();

        // التحقق من الصلاحيات
        if (!$this->user->hasKey('modify_catalog_unit')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('catalog/unit');

            if (isset($this->request->post['selected']) && $this->validateDelete()) {
                foreach ($this->request->post['selected'] as $unit_id) {
                    $unit_info = $this->model_catalog_unit->getUnit($unit_id);
                    $this->model_catalog_unit->deleteUnit($unit_id);
                    
                    // تسجيل النشاط
                    if ($unit_info) {
                        $this->user->logActivity('delete', 'unit', 'تم حذف وحدة قياس: ' . $unit_info['code'], 'unit', $unit_id);
                    }
                }

                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->error['warning'];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validateDelete() {
        if (!$this->user->hasKey('modify_catalog_unit')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        // التحقق من استخدام الوحدات في المنتجات
        $this->load->model('catalog/unit');
        
        foreach ($this->request->post['selected'] as $unit_id) {
            $product_count = $this->model_catalog_unit->getProductCountByUnit($unit_id);
            
            if ($product_count > 0) {
                $this->error['warning'] = sprintf($this->language->get('error_unit_in_use'), $product_count);
                break;
            }
        }

        return !$this->error;
    }

    protected function getList() {
        $data['user_token'] = $this->session->data['user_token'];

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/unit', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('catalog/unit/form', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('catalog/unit/delete', 'user_token=' . $this->session->data['user_token'], true);

        // التحقق من الصلاحيات
        $data['can_add'] = $this->user->hasKey('modify_catalog_unit');
        $data['can_edit'] = $this->user->hasKey('modify_catalog_unit');
        $data['can_delete'] = $this->user->hasKey('modify_catalog_unit');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/unit_list', $data));
    }
}