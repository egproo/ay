<?php
class ControllerUserFeatureAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('user/feature_advanced');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('user/feature_advanced');
        $this->load->model('user/user_group');

        $data['permissions'] = $this->model_user_feature_advanced->getPermissions();
        $data['add'] = $this->url->link('user/feature_advanced/add','user_token='.$this->session->data['user_token'],true);
        $data['edit_link'] = function($id){ return $this->url->link('user/feature_advanced/edit','user_token='.$this->session->data['user_token'].'&permission_id='.$id,true); };
        $data['delete_link'] = function($id){ return $this->url->link('user/feature_advanced/delete','user_token='.$this->session->data['user_token'].'&permission_id='.$id,true); };

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
        $this->response->setOutput($this->load->view('user/feature_advanced_list', $data));
    }

    public function add() {
        $this->load->language('user/feature_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('user/feature_advanced');
        $this->load->model('user/user_group');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $permission_id = $this->model_user_feature_advanced->addPermission($this->request->post);
            if (isset($this->request->post['user_group_ids'])) {
                $this->model_user_feature_advanced->setUserGroupPermissions($permission_id, $this->request->post['user_group_ids']);
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('user/feature_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('user/feature_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('user/feature_advanced');
        $this->load->model('user/user_group');

        $permission_id = (int)$this->request->get['permission_id'];

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_user_feature_advanced->editPermission($permission_id, $this->request->post);
            if (isset($this->request->post['user_group_ids'])) {
                $this->model_user_feature_advanced->setUserGroupPermissions($permission_id, $this->request->post['user_group_ids']);
            } else {
                $this->model_user_feature_advanced->setUserGroupPermissions($permission_id, array());
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('user/feature_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('user/feature_advanced');
        $this->load->model('user/feature_advanced');

        if (isset($this->request->get['permission_id']) && $this->validateDelete()) {
            $this->model_user_feature_advanced->deletePermission((int)$this->request->get['permission_id']);
            $this->session->data['success'] = $this->language->get('text_success');
        }

        $this->response->redirect($this->url->link('user/feature_advanced', 'user_token=' . $this->session->data['user_token'], true));
    }

    protected function getForm() {
        $this->load->model('user/user_group');
        $data['user_groups'] = $this->model_user_user_group->getUserGroups();

        if (isset($this->request->get['permission_id'])) {
            $permission_info = $this->model_user_feature_advanced->getPermission((int)$this->request->get['permission_id']);
            $data['selected_user_groups'] = $this->model_user_feature_advanced->getUserGroupPermissions((int)$this->request->get['permission_id']);
        } else {
            $permission_info = array();
            $data['selected_user_groups'] = array();
        }

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif ($permission_info) {
            $data['name'] = $permission_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['key'])) {
            $data['key'] = $this->request->post['key'];
        } elseif ($permission_info) {
            $data['key'] = $permission_info['key'];
        } else {
            $data['key'] = '';
        }

        if (isset($this->request->post['type'])) {
            $data['type'] = $this->request->post['type'];
        } elseif ($permission_info) {
            $data['type'] = $permission_info['type'];
        } else {
            $data['type'] = 'other';
        }

        if (isset($this->request->post['user_group_ids'])) {
            $data['selected_user_groups'] = $this->request->post['user_group_ids'];
        }

        if (isset($this->request->get['permission_id'])) {
            $data['action'] = $this->url->link('user/feature_advanced/edit','user_token='.$this->session->data['user_token'].'&permission_id='.(int)$this->request->get['permission_id'],true);
        } else {
            $data['action'] = $this->url->link('user/feature_advanced/add','user_token='.$this->session->data['user_token'],true);
        }
        $data['cancel'] = $this->url->link('user/feature_advanced','user_token='.$this->session->data['user_token'],true);

        if (isset($this->error['warning'])) { $data['error_warning'] = $this->error['warning']; } else { $data['error_warning'] = ''; }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');      
        $this->response->setOutput($this->load->view('user/feature_advanced_form',$data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify','user/feature_advanced')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (utf8_strlen($this->request->post['name']) < 3) {
            $this->error['warning'] = $this->language->get('error_name');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify','user/feature_advanced')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
