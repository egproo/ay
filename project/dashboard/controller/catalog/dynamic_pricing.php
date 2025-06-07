<?php
class ControllerCatalogDynamicPricing extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('catalog/dynamic_pricing');
        $this->load->model('catalog/product');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['heading_title'] = $this->language->get('heading_title');
//need to comlete
    }
    public function add() {
        $this->load->language('catalog/dynamic_pricing');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/product');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_product->addDynamicPricingRule($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['product_id'])) {
                $url .= '&product_id=' . $this->request->get['product_id'];
            }

            $this->response->redirect($this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('catalog/dynamic_pricing');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/product');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_product->editDynamicPricingRule($this->request->get['rule_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['product_id'])) {
                $url .= '&product_id=' . $this->request->get['product_id'];
            }

            $this->response->redirect($this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    protected function getForm() {
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_form'] = !isset($this->request->get['rule_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

$data['entry_name'] = $this->language->get('entry_name');
        $data['entry_type'] = $this->language->get('entry_type');
        $data['entry_value'] = $this->language->get('entry_value');
        $data['entry_formula'] = $this->language->get('entry_formula');
        $data['entry_condition_type'] = $this->language->get('entry_condition_type');
        $data['entry_condition_value'] = $this->language->get('entry_condition_value');
        $data['entry_priority'] = $this->language->get('entry_priority');
        $data['entry_date_start'] = $this->language->get('entry_date_start');
        $data['entry_date_end'] = $this->language->get('entry_date_end');
        $data['entry_status'] = $this->language->get('entry_status');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

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

        $url = '';

        if (isset($this->request->get['product_id'])) {
            $url .= '&product_id=' . $this->request->get['product_id'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/dynamic_pricing', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['rule_id'])) {
            $data['action'] = $this->url->link('catalog/dynamic_pricing/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('catalog/dynamic_pricing/edit', 'user_token=' . $this->session->data['user_token'] . '&rule_id=' . $this->request->get['rule_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['rule_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $rule_info = $this->model_catalog_product->getDynamicPricingRule($this->request->get['rule_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($rule_info)) {
            $data['name'] = $rule_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['type'])) {
            $data['type'] = $this->request->post['type'];
        } elseif (!empty($rule_info)) {
            $data['type'] = $rule_info['type'];
        } else {
            $data['type'] = '';
        }

        if (isset($this->request->post['value'])) {
            $data['value'] = $this->request->post['value'];
        } elseif (!empty($rule_info)) {
            $data['value'] = $rule_info['value'];
        } else {
            $data['value'] = '';
        }

        if (isset($this->request->post['formula'])) {
            $data['formula'] = $this->request->post['formula'];
        } elseif (!empty($rule_info)) {
            $data['formula'] = $rule_info['formula'];
        } else {
            $data['formula'] = '';
        }

        if (isset($this->request->post['condition_type'])) {
            $data['condition_type'] = $this->request->post['condition_type'];
        } elseif (!empty($rule_info)) {
            $data['condition_type'] = $rule_info['condition_type'];
        } else {
            $data['condition_type'] = '';
        }

        if (isset($this->request->post['condition_value'])) {
            $data['condition_value'] = $this->request->post['condition_value'];
        } elseif (!empty($rule_info)) {
            $data['condition_value'] = $rule_info['condition_value'];
        } else {
            $data['condition_value'] = '';
        }

        if (isset($this->request->post['priority'])) {
            $data['priority'] = $this->request->post['priority'];
        } elseif (!empty($rule_info)) {
            $data['priority'] = $rule_info['priority'];
        } else {
            $data['priority'] = 0;
        }

        if (isset($this->request->post['date_start'])) {
            $data['date_start'] = $this->request->post['date_start'];
        } elseif (!empty($rule_info)) {
            $data['date_start'] = ($rule_info['date_start'] != '0000-00-00') ? $rule_info['date_start'] : '';
        } else {
            $data['date_start'] = '';
        }

        if (isset($this->request->post['date_end'])) {
            $data['date_end'] = $this->request->post['date_end'];
        } elseif (!empty($rule_info)) {
            $data['date_end'] = ($rule_info['date_end'] != '0000-00-00') ? $rule_info['date_end'] : '';
        } else {
            $data['date_end'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($rule_info)) {
            $data['status'] = $rule_info['status'];
        } else {
            $data['status'] = true;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/dynamic_pricing_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'catalog/dynamic_pricing')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 255)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        return !$this->error;
    }
}