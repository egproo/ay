<?php
class ControllerUserPermission extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('user/permission');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('user/permission');
        $this->load->model('user/user_group');
        $this->load->model('user/user');

        // فلتر حسب الاسم
        $filter_name = isset($this->request->get['filter_name']) ? $this->request->get['filter_name'] : '';

        $data['filter_name'] = $filter_name;

        $data['permissions'] = $this->model_user_permission->getPermissions($filter_name);

        $data['user_groups'] = $this->model_user_user_group->getUserGroups();
        $data['users'] = $this->model_user_user->getUsers(); // لجلب أسماء المستخدمين

        $data['user_token'] = $this->session->data['user_token'];

        $data['text_no_results'] = $this->language->get('text_no_results');

        // زر إضافة
        // سيكون الإضافة من المودال وليس صفحة جديدة، سنستخدم Ajax
        // لكن يمكن وضع رابط وهمي هنا
        $data['add'] = '#';

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

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // سيعرض القالب الذي يحتوي على الجدول والمودال
        $this->response->setOutput($this->load->view('user/permission_list', $data));
    }

    // AJAX: إضافة صلاحية
    public function addPermissionAjax() {
        $this->load->language('user/permission');
        $this->load->model('user/permission');

        $json = array();
        if (!$this->user->hasPermission('modify','user/permission')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (utf8_strlen($this->request->post['name']) < 3) {
                $json['error'] = $this->language->get('error_name');
            }
        }

        if (!isset($json['error'])) {
            $permission_id = $this->model_user_permission->addPermission($this->request->post);

            if (isset($this->request->post['user_group_ids'])) {
                $this->model_user_permission->setUserGroupPermissions($permission_id, $this->request->post['user_group_ids']);
            }
            if (isset($this->request->post['user_ids'])) {
                $this->model_user_permission->setUserPermissions($permission_id, $this->request->post['user_ids']);
            }

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // AJAX: تحميل بيانات صلاحية للتعديل
    public function loadPermissionAjax() {
        $this->load->model('user/permission');
        $permission_id = (int)$this->request->get['permission_id'];
        $info = $this->model_user_permission->getPermission($permission_id);
        $ug = $this->model_user_permission->getUserGroupPermissions($permission_id);
        $us = $this->model_user_permission->getUserPermissions($permission_id);

        $json = array(
            'permission_id' => $info['permission_id'],
            'name' => $info['name'],
            'key' => $info['key'],
            'type' => $info['type'],
            'user_group_ids' => $ug,
            'user_ids' => $us
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // AJAX: حفظ تعديل
    public function editPermissionAjax() {
        $this->load->language('user/permission');
        $this->load->model('user/permission');

        $json = array();
        if (!$this->user->hasPermission('modify','user/permission')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (utf8_strlen($this->request->post['name']) < 3) {
                $json['error'] = $this->language->get('error_name');
            }
        }

        $permission_id = (int)$this->request->post['permission_id'];

        if (!isset($json['error'])) {
            $this->model_user_permission->editPermission($permission_id, $this->request->post);

            $ug = isset($this->request->post['user_group_ids'])?$this->request->post['user_group_ids']:array();
            $this->model_user_permission->setUserGroupPermissions($permission_id, $ug);

            $us = isset($this->request->post['user_ids'])?$this->request->post['user_ids']:array();
            $this->model_user_permission->setUserPermissions($permission_id, $us);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // AJAX: حذف
    public function deletePermissionAjax() {
        $this->load->language('user/permission');
        $this->load->model('user/permission');

        $json = array();
        if (!$this->user->hasPermission('modify','user/permission')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $permission_id = (int)$this->request->post['permission_id'];

        if (!isset($json['error'])) {
            $this->model_user_permission->deletePermission($permission_id);
            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
