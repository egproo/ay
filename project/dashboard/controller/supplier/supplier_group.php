<?php
class ControllerSupplierSupplierGroup extends Controller {
    private $error = array();

    /**
     * عرض صفحة مجموعات الموردين
     */
    public function index() {
        $this->load->language('supplier/supplier_group');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/supplier_group');

        $this->getList();
    }

    /**
     * إضافة مجموعة مورد جديدة
     */
    public function add() {
        $this->load->language('supplier/supplier_group');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/supplier_group');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_supplier_supplier_group->addSupplierGroup($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * تعديل مجموعة مورد
     */
    public function edit() {
        $this->load->language('supplier/supplier_group');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/supplier_group');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_supplier_supplier_group->editSupplierGroup($this->request->get['supplier_group_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * حذف مجموعة مورد
     */
    public function delete() {
        $this->load->language('supplier/supplier_group');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/supplier_group');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $supplier_group_id) {
                $this->model_supplier_supplier_group->deleteSupplierGroup($supplier_group_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * عرض قائمة مجموعات الموردين
     */
    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'sgd.name';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

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
            'href' => $this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('supplier/supplier_group/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('supplier/supplier_group/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['supplier_groups'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $supplier_group_total = $this->model_supplier_supplier_group->getTotalSupplierGroups();

        $results = $this->model_supplier_supplier_group->getSupplierGroups($filter_data);

        foreach ($results as $result) {
            $data['supplier_groups'][] = array(
                'supplier_group_id' => $result['supplier_group_id'],
                'name'              => $result['name'],
                'description'       => $result['description'],
                'sort_order'        => $result['sort_order'],
                'edit'              => $this->url->link('supplier/supplier_group/edit', 'user_token=' . $this->session->data['user_token'] . '&supplier_group_id=' . $result['supplier_group_id'] . $url, true)
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

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'] . '&sort=sgd.name' . $url, true);
        $data['sort_sort_order'] = $this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'] . '&sort=sg.sort_order' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $supplier_group_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($supplier_group_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($supplier_group_total - $this->config->get('config_limit_admin'))) ? $supplier_group_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $supplier_group_total, ceil($supplier_group_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/supplier_group_list', $data));
    }

    /**
     * عرض نموذج إضافة/تعديل مجموعة مورد
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['supplier_group_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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
            'href' => $this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['supplier_group_id'])) {
            $data['action'] = $this->url->link('supplier/supplier_group/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('supplier/supplier_group/edit', 'user_token=' . $this->session->data['user_token'] . '&supplier_group_id=' . $this->request->get['supplier_group_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['supplier_group_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $supplier_group_info = $this->model_supplier_supplier_group->getSupplierGroup($this->request->get['supplier_group_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('localisation/language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

        if (isset($this->request->post['supplier_group_description'])) {
            $data['supplier_group_description'] = $this->request->post['supplier_group_description'];
        } elseif (isset($this->request->get['supplier_group_id'])) {
            $data['supplier_group_description'] = $this->model_supplier_supplier_group->getSupplierGroupDescriptions($this->request->get['supplier_group_id']);
        } else {
            $data['supplier_group_description'] = array();
        }

        if (isset($this->request->post['approval'])) {
            $data['approval'] = $this->request->post['approval'];
        } elseif (!empty($supplier_group_info)) {
            $data['approval'] = $supplier_group_info['approval'];
        } else {
            $data['approval'] = 0;
        }

        if (isset($this->request->post['sort_order'])) {
            $data['sort_order'] = $this->request->post['sort_order'];
        } elseif (!empty($supplier_group_info)) {
            $data['sort_order'] = $supplier_group_info['sort_order'];
        } else {
            $data['sort_order'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/supplier_group_form', $data));
    }

    /**
     * التحقق من صحة بيانات النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'supplier/supplier_group')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->request->post['supplier_group_description'] as $language_id => $value) {
            if ((utf8_strlen($value['name']) < 3) || (utf8_strlen($value['name']) > 32)) {
                $this->error['name'][$language_id] = $this->language->get('error_name');
            }
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة عملية الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'supplier/supplier_group')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $this->load->model('supplier/supplier');

        foreach ($this->request->post['selected'] as $supplier_group_id) {
            if ($this->config->get('config_supplier_group_id') == $supplier_group_id) {
                $this->error['warning'] = $this->language->get('error_default');

                break;
            }

            $supplier_total = $this->model_supplier_supplier->getTotalSuppliersBySupplierGroupId($supplier_group_id);

            if ($supplier_total) {
                $this->error['warning'] = sprintf($this->language->get('error_supplier'), $supplier_total);

                break;
            }
        }

        return !$this->error;
    }
}
