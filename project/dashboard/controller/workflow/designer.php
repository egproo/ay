<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * وحدة محرر سير العمل المرئي المشابه لـ n8n
 */
class ControllerWorkflowDesigner extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('workflow/designer');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('workflow/workflow');

        // Save workflow if form submitted
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $workflow_id = $this->model_workflow_workflow->saveWorkflow($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('workflow/workflow', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_workflow'),
            'href' => $this->url->link('workflow/workflow', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_designer'),
            'href' => $this->url->link('workflow/designer', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (isset($this->request->get['workflow_id'])) {
            $data['action'] = $this->url->link('workflow/designer', 'user_token=' . $this->session->data['user_token'] . '&workflow_id=' . $this->request->get['workflow_id'], true);
            $workflow_info = $this->model_workflow_workflow->getWorkflow($this->request->get['workflow_id']);
        } else {
            $data['action'] = $this->url->link('workflow/designer', 'user_token=' . $this->session->data['user_token'], true);
            $workflow_info = array();
        }

        $data['cancel'] = $this->url->link('workflow/workflow', 'user_token=' . $this->session->data['user_token'], true);

        // Load workflow data
        if (!empty($workflow_info)) {
            $data['name'] = $workflow_info['name'];
            $data['description'] = $workflow_info['description'];
            $data['status'] = $workflow_info['status'];
            $data['workflow_data'] = $workflow_info['workflow_data'];
        } else {
            $data['name'] = '';
            $data['description'] = '';
            $data['status'] = 1;
            $data['workflow_data'] = '';
        }

        // Load language strings for template
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_form'] = !isset($this->request->get['workflow_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_workflow_designer'] = $this->language->get('text_workflow_designer');
        $data['text_start'] = $this->language->get('text_start');
        $data['text_task'] = $this->language->get('text_task');
        $data['text_decision'] = $this->language->get('text_decision');
        $data['text_email'] = $this->language->get('text_email');
        $data['text_delay'] = $this->language->get('text_delay');
        $data['text_end'] = $this->language->get('text_end');
        $data['text_zoom_in'] = $this->language->get('text_zoom_in');
        $data['text_zoom_out'] = $this->language->get('text_zoom_out');
        $data['text_clear'] = $this->language->get('text_clear');

        $data['entry_name'] = $this->language->get('entry_name');
        $data['entry_description'] = $this->language->get('entry_description');
        $data['entry_status'] = $this->language->get('entry_status');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        // Load required scripts and styles
        $this->document->addScript('view/javascript/common/workflow_designer.js');
        $this->document->addStyle('view/stylesheet/workflow/workflow_designer.css');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('workflow/designer', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'workflow/designer')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        return !$this->error;
    }
} 