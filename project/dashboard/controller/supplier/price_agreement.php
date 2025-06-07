<?php
/**
 * AYM ERP - Supplier Price Agreement Controller
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerSupplierPriceAgreement extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('supplier/price_agreement');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('supplier/price_agreement/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('supplier/price_agreement/delete', 'user_token=' . $this->session->data['user_token'], true);

        $this->getList($data);
    }

    public function add() {
        $this->load->language('supplier/price_agreement');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('supplier/price_agreement');

            $this->model_supplier_price_agreement->addPriceAgreement($this->request->post);

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

            $this->response->redirect($this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('supplier/price_agreement');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('supplier/price_agreement');

            $this->model_supplier_price_agreement->editPriceAgreement($this->request->get['price_agreement_id'], $this->request->post);

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

            $this->response->redirect($this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('supplier/price_agreement');

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            $this->load->model('supplier/price_agreement');

            foreach ($this->request->post['selected'] as $price_agreement_id) {
                $this->model_supplier_price_agreement->deletePriceAgreement($price_agreement_id);
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

            $this->response->redirect($this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    protected function getList(&$data = array()) {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'pa.agreement_name';
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

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['price_agreements'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $this->load->model('supplier/price_agreement');

        $price_agreement_total = $this->model_supplier_price_agreement->getTotalPriceAgreements();

        $results = $this->model_supplier_price_agreement->getPriceAgreements($filter_data);

        foreach ($results as $result) {
            $data['price_agreements'][] = array(
                'price_agreement_id' => $result['price_agreement_id'],
                'agreement_name'     => $result['agreement_name'],
                'supplier_name'      => $result['supplier_name'],
                'start_date'         => date($this->language->get('date_format_short'), strtotime($result['start_date'])),
                'end_date'           => date($this->language->get('date_format_short'), strtotime($result['end_date'])),
                'status'             => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'edit'               => $this->url->link('supplier/price_agreement/edit', 'user_token=' . $this->session->data['user_token'] . '&price_agreement_id=' . $result['price_agreement_id'] . $url, true)
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

        $data['sort_agreement_name'] = $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . '&sort=pa.agreement_name' . $url, true);
        $data['sort_supplier'] = $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . '&sort=s.name' . $url, true);
        $data['sort_start_date'] = $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . '&sort=pa.start_date' . $url, true);
        $data['sort_end_date'] = $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . '&sort=pa.end_date' . $url, true);
        $data['sort_status'] = $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . '&sort=pa.status' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $price_agreement_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($price_agreement_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($price_agreement_total - $this->config->get('config_limit_admin'))) ? $price_agreement_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $price_agreement_total, ceil($price_agreement_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/price_agreement_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['price_agreement_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['agreement_name'])) {
            $data['error_agreement_name'] = $this->error['agreement_name'];
        } else {
            $data['error_agreement_name'] = '';
        }

        if (isset($this->error['supplier'])) {
            $data['error_supplier'] = $this->error['supplier'];
        } else {
            $data['error_supplier'] = '';
        }

        if (isset($this->error['start_date'])) {
            $data['error_start_date'] = $this->error['start_date'];
        } else {
            $data['error_start_date'] = '';
        }

        if (isset($this->error['end_date'])) {
            $data['error_end_date'] = $this->error['end_date'];
        } else {
            $data['error_end_date'] = '';
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
            'href' => $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['price_agreement_id'])) {
            $data['action'] = $this->url->link('supplier/price_agreement/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('supplier/price_agreement/edit', 'user_token=' . $this->session->data['user_token'] . '&price_agreement_id=' . $this->request->get['price_agreement_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['price_agreement_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $price_agreement_info = $this->model_supplier_price_agreement->getPriceAgreement($this->request->get['price_agreement_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['agreement_name'])) {
            $data['agreement_name'] = $this->request->post['agreement_name'];
        } elseif (!empty($price_agreement_info)) {
            $data['agreement_name'] = $price_agreement_info['agreement_name'];
        } else {
            $data['agreement_name'] = '';
        }

        if (isset($this->request->post['supplier_id'])) {
            $data['supplier_id'] = $this->request->post['supplier_id'];
        } elseif (!empty($price_agreement_info)) {
            $data['supplier_id'] = $price_agreement_info['supplier_id'];
        } else {
            $data['supplier_id'] = '';
        }

        if (isset($this->request->post['description'])) {
            $data['description'] = $this->request->post['description'];
        } elseif (!empty($price_agreement_info)) {
            $data['description'] = $price_agreement_info['description'];
        } else {
            $data['description'] = '';
        }

        if (isset($this->request->post['start_date'])) {
            $data['start_date'] = $this->request->post['start_date'];
        } elseif (!empty($price_agreement_info)) {
            $data['start_date'] = $price_agreement_info['start_date'];
        } else {
            $data['start_date'] = '';
        }

        if (isset($this->request->post['end_date'])) {
            $data['end_date'] = $this->request->post['end_date'];
        } elseif (!empty($price_agreement_info)) {
            $data['end_date'] = $price_agreement_info['end_date'];
        } else {
            $data['end_date'] = '';
        }

        if (isset($this->request->post['terms'])) {
            $data['terms'] = $this->request->post['terms'];
        } elseif (!empty($price_agreement_info)) {
            $data['terms'] = $price_agreement_info['terms'];
        } else {
            $data['terms'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($price_agreement_info)) {
            $data['status'] = $price_agreement_info['status'];
        } else {
            $data['status'] = 1;
        }

        // Load suppliers
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        // Load price agreement items if editing
        if (isset($this->request->get['price_agreement_id'])) {
            $data['price_agreement_items'] = $this->model_supplier_price_agreement->getPriceAgreementItems($this->request->get['price_agreement_id']);
        } else {
            $data['price_agreement_items'] = array();
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/price_agreement_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'supplier/price_agreement')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['agreement_name']) < 3) || (utf8_strlen($this->request->post['agreement_name']) > 64)) {
            $this->error['agreement_name'] = $this->language->get('error_agreement_name');
        }

        if (empty($this->request->post['supplier_id'])) {
            $this->error['supplier'] = $this->language->get('error_supplier');
        }

        if (empty($this->request->post['start_date'])) {
            $this->error['start_date'] = $this->language->get('error_start_date');
        }

        if (empty($this->request->post['end_date'])) {
            $this->error['end_date'] = $this->language->get('error_end_date');
        }

        if (!empty($this->request->post['start_date']) && !empty($this->request->post['end_date'])) {
            if (strtotime($this->request->post['start_date']) >= strtotime($this->request->post['end_date'])) {
                $this->error['end_date'] = $this->language->get('error_date_range');
            }
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'supplier/price_agreement')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
