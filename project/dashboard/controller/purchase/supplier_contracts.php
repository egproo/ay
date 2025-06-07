<?php
class ControllerPurchaseSupplierContracts extends Controller {
    private $error = array();

    /**
     * عرض صفحة عقود الموردين
     */
    public function index() {
        $this->load->language('purchase/supplier_contracts');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/supplier_contracts');

        $this->getList();
    }

    /**
     * إضافة عقد مورد جديد
     */
    public function add() {
        $this->load->language('purchase/supplier_contracts');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/supplier_contracts');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $contract_id = $this->model_purchase_supplier_contracts->addContract($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_contract_number'])) {
                $url .= '&filter_contract_number=' . urlencode(html_entity_decode($this->request->get['filter_contract_number'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_supplier_id'])) {
                $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
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

            $this->response->redirect($this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * تعديل عقد مورد
     */
    public function edit() {
        $this->load->language('purchase/supplier_contracts');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/supplier_contracts');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_purchase_supplier_contracts->editContract($this->request->get['contract_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_contract_number'])) {
                $url .= '&filter_contract_number=' . urlencode(html_entity_decode($this->request->get['filter_contract_number'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_supplier_id'])) {
                $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
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

            $this->response->redirect($this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * حذف عقد مورد
     */
    public function delete() {
        $this->load->language('purchase/supplier_contracts');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/supplier_contracts');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $contract_id) {
                $this->model_purchase_supplier_contracts->deleteContract($contract_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_contract_number'])) {
                $url .= '&filter_contract_number=' . urlencode(html_entity_decode($this->request->get['filter_contract_number'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_supplier_id'])) {
                $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
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

            $this->response->redirect($this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * عرض قائمة عقود الموردين
     */
    protected function getList() {
        $this->load->language('purchase/supplier_contracts');

        if (isset($this->request->get['filter_contract_number'])) {
            $filter_contract_number = $this->request->get['filter_contract_number'];
        } else {
            $filter_contract_number = '';
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $filter_supplier_id = $this->request->get['filter_supplier_id'];
        } else {
            $filter_supplier_id = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = '';
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'sc.contract_date';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_contract_number'])) {
            $url .= '&filter_contract_number=' . urlencode(html_entity_decode($this->request->get['filter_contract_number'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
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
            'href' => $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('purchase/supplier_contracts/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('purchase/supplier_contracts/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['contracts'] = array();

        $filter_data = array(
            'filter_contract_number' => $filter_contract_number,
            'filter_supplier_id'     => $filter_supplier_id,
            'filter_status'          => $filter_status,
            'filter_date_start'      => $filter_date_start,
            'filter_date_end'        => $filter_date_end,
            'sort'                   => $sort,
            'order'                  => $order,
            'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'                  => $this->config->get('config_limit_admin')
        );

        $contract_total = $this->model_purchase_supplier_contracts->getTotalContracts($filter_data);

        $results = $this->model_purchase_supplier_contracts->getContracts($filter_data);

        foreach ($results as $result) {
            $data['contracts'][] = array(
                'contract_id'      => $result['contract_id'],
                'contract_number'  => $result['contract_number'],
                'supplier_name'    => $result['supplier_name'],
                'contract_date'    => date($this->language->get('date_format_short'), strtotime($result['contract_date'])),
                'start_date'       => date($this->language->get('date_format_short'), strtotime($result['start_date'])),
                'end_date'         => date($this->language->get('date_format_short'), strtotime($result['end_date'])),
                'contract_value'   => $this->currency->format($result['contract_value'], $result['currency_code']),
                'status'           => $result['status'],
                'edit'             => $this->url->link('purchase/supplier_contracts/edit', 'user_token=' . $this->session->data['user_token'] . '&contract_id=' . $result['contract_id'] . $url, true)
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

        $data['sort_contract_number'] = $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.contract_number' . $url, true);
        $data['sort_supplier'] = $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . '&sort=supplier_name' . $url, true);
        $data['sort_contract_date'] = $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.contract_date' . $url, true);
        $data['sort_start_date'] = $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.start_date' . $url, true);
        $data['sort_end_date'] = $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.end_date' . $url, true);
        $data['sort_status'] = $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . '&sort=sc.status' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_contract_number'])) {
            $url .= '&filter_contract_number=' . urlencode(html_entity_decode($this->request->get['filter_contract_number'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $contract_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($contract_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($contract_total - $this->config->get('config_limit_admin'))) ? $contract_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $contract_total, ceil($contract_total / $this->config->get('config_limit_admin')));

        $data['filter_contract_number'] = $filter_contract_number;
        $data['filter_supplier_id'] = $filter_supplier_id;
        $data['filter_status'] = $filter_status;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/supplier_contracts_list', $data));
    }

    /**
     * عرض نموذج إضافة/تعديل عقد مورد
     */
    protected function getForm() {
        $this->load->language('purchase/supplier_contracts');

        $data['text_form'] = !isset($this->request->get['contract_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['contract_number'])) {
            $data['error_contract_number'] = $this->error['contract_number'];
        } else {
            $data['error_contract_number'] = '';
        }

        if (isset($this->error['supplier_id'])) {
            $data['error_supplier_id'] = $this->error['supplier_id'];
        } else {
            $data['error_supplier_id'] = '';
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

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_contract_number'])) {
            $url .= '&filter_contract_number=' . urlencode(html_entity_decode($this->request->get['filter_contract_number'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
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
            'href' => $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['contract_id'])) {
            $data['action'] = $this->url->link('purchase/supplier_contracts/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('purchase/supplier_contracts/edit', 'user_token=' . $this->session->data['user_token'] . '&contract_id=' . $this->request->get['contract_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['contract_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $contract_info = $this->model_purchase_supplier_contracts->getContract($this->request->get['contract_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['contract_number'])) {
            $data['contract_number'] = $this->request->post['contract_number'];
        } elseif (!empty($contract_info)) {
            $data['contract_number'] = $contract_info['contract_number'];
        } else {
            $data['contract_number'] = '';
        }

        if (isset($this->request->post['supplier_id'])) {
            $data['supplier_id'] = $this->request->post['supplier_id'];
        } elseif (!empty($contract_info)) {
            $data['supplier_id'] = $contract_info['supplier_id'];
        } else {
            $data['supplier_id'] = '';
        }

        if (isset($this->request->post['contract_type'])) {
            $data['contract_type'] = $this->request->post['contract_type'];
        } elseif (!empty($contract_info)) {
            $data['contract_type'] = $contract_info['contract_type'];
        } else {
            $data['contract_type'] = 'general';
        }

        if (isset($this->request->post['contract_date'])) {
            $data['contract_date'] = $this->request->post['contract_date'];
        } elseif (!empty($contract_info)) {
            $data['contract_date'] = $contract_info['contract_date'];
        } else {
            $data['contract_date'] = date('Y-m-d');
        }

        if (isset($this->request->post['start_date'])) {
            $data['start_date'] = $this->request->post['start_date'];
        } elseif (!empty($contract_info)) {
            $data['start_date'] = $contract_info['start_date'];
        } else {
            $data['start_date'] = '';
        }

        if (isset($this->request->post['end_date'])) {
            $data['end_date'] = $this->request->post['end_date'];
        } elseif (!empty($contract_info)) {
            $data['end_date'] = $contract_info['end_date'];
        } else {
            $data['end_date'] = '';
        }

        if (isset($this->request->post['contract_value'])) {
            $data['contract_value'] = $this->request->post['contract_value'];
        } elseif (!empty($contract_info)) {
            $data['contract_value'] = $contract_info['contract_value'];
        } else {
            $data['contract_value'] = '';
        }

        if (isset($this->request->post['currency_id'])) {
            $data['currency_id'] = $this->request->post['currency_id'];
        } elseif (!empty($contract_info)) {
            $data['currency_id'] = $contract_info['currency_id'];
        } else {
            $data['currency_id'] = $this->config->get('config_currency_id');
        }

        if (isset($this->request->post['payment_terms'])) {
            $data['payment_terms'] = $this->request->post['payment_terms'];
        } elseif (!empty($contract_info)) {
            $data['payment_terms'] = $contract_info['payment_terms'];
        } else {
            $data['payment_terms'] = '';
        }

        if (isset($this->request->post['delivery_terms'])) {
            $data['delivery_terms'] = $this->request->post['delivery_terms'];
        } elseif (!empty($contract_info)) {
            $data['delivery_terms'] = $contract_info['delivery_terms'];
        } else {
            $data['delivery_terms'] = '';
        }

        if (isset($this->request->post['terms_conditions'])) {
            $data['terms_conditions'] = $this->request->post['terms_conditions'];
        } elseif (!empty($contract_info)) {
            $data['terms_conditions'] = $contract_info['terms_conditions'];
        } else {
            $data['terms_conditions'] = '';
        }

        if (isset($this->request->post['notes'])) {
            $data['notes'] = $this->request->post['notes'];
        } elseif (!empty($contract_info)) {
            $data['notes'] = $contract_info['notes'];
        } else {
            $data['notes'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($contract_info)) {
            $data['status'] = $contract_info['status'];
        } else {
            $data['status'] = 'draft';
        }

        // قائمة الموردين
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        // قائمة العملات
        $this->load->model('localisation/currency');
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        // أنواع العقود
        $data['contract_types'] = array(
            array('value' => 'general', 'text' => $this->language->get('text_contract_type_general')),
            array('value' => 'framework', 'text' => $this->language->get('text_contract_type_framework')),
            array('value' => 'exclusive', 'text' => $this->language->get('text_contract_type_exclusive')),
            array('value' => 'service', 'text' => $this->language->get('text_contract_type_service')),
            array('value' => 'maintenance', 'text' => $this->language->get('text_contract_type_maintenance'))
        );

        // حالات العقد
        $data['statuses'] = array(
            array('value' => 'draft', 'text' => $this->language->get('text_status_draft')),
            array('value' => 'pending_approval', 'text' => $this->language->get('text_status_pending_approval')),
            array('value' => 'active', 'text' => $this->language->get('text_status_active')),
            array('value' => 'suspended', 'text' => $this->language->get('text_status_suspended')),
            array('value' => 'expired', 'text' => $this->language->get('text_status_expired')),
            array('value' => 'terminated', 'text' => $this->language->get('text_status_terminated'))
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/supplier_contracts_form', $data));
    }

    /**
     * التحقق من صحة بيانات النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'purchase/supplier_contracts')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['contract_number']) < 1) || (utf8_strlen($this->request->post['contract_number']) > 64)) {
            $this->error['contract_number'] = $this->language->get('error_contract_number');
        }

        if (empty($this->request->post['supplier_id'])) {
            $this->error['supplier_id'] = $this->language->get('error_supplier');
        }

        if (empty($this->request->post['start_date'])) {
            $this->error['start_date'] = $this->language->get('error_start_date');
        }

        if (empty($this->request->post['end_date'])) {
            $this->error['end_date'] = $this->language->get('error_end_date');
        }

        if (!empty($this->request->post['start_date']) && !empty($this->request->post['end_date'])) {
            if (strtotime($this->request->post['end_date']) <= strtotime($this->request->post['start_date'])) {
                $this->error['end_date'] = $this->language->get('error_end_date_before_start');
            }
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة عملية الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'purchase/supplier_contracts')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * تجديد عقد مورد
     */
    public function renew() {
        $this->load->language('purchase/supplier_contracts');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/supplier_contracts')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/supplier_contracts');

            if (isset($this->request->post['contract_id'])) {
                $contract_id = $this->request->post['contract_id'];
            } else {
                $contract_id = 0;
            }

            if (isset($this->request->post['new_end_date'])) {
                $new_end_date = $this->request->post['new_end_date'];
            } else {
                $new_end_date = '';
            }

            if (!$contract_id) {
                $json['error'] = $this->language->get('error_contract_id');
            } elseif (!$new_end_date) {
                $json['error'] = $this->language->get('error_new_end_date');
            } else {
                $contract_info = $this->model_purchase_supplier_contracts->getContract($contract_id);

                if ($contract_info) {
                    if (strtotime($new_end_date) <= strtotime($contract_info['end_date'])) {
                        $json['error'] = $this->language->get('error_new_end_date_invalid');
                    } else {
                        $renewal_data = array(
                            'end_date' => $new_end_date,
                            'status' => 'active',
                            'renewed_by' => $this->user->getId(),
                            'renewed_at' => date('Y-m-d H:i:s'),
                            'renewal_notes' => isset($this->request->post['renewal_notes']) ? $this->request->post['renewal_notes'] : ''
                        );

                        $this->model_purchase_supplier_contracts->renewContract($contract_id, $renewal_data);

                        $json['success'] = $this->language->get('text_contract_renewed');
                    }
                } else {
                    $json['error'] = $this->language->get('error_contract_not_found');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إنهاء عقد مورد
     */
    public function terminate() {
        $this->load->language('purchase/supplier_contracts');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/supplier_contracts')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/supplier_contracts');

            if (isset($this->request->post['contract_id'])) {
                $contract_id = $this->request->post['contract_id'];
            } else {
                $contract_id = 0;
            }

            if (!$contract_id) {
                $json['error'] = $this->language->get('error_contract_id');
            } else {
                $contract_info = $this->model_purchase_supplier_contracts->getContract($contract_id);

                if ($contract_info) {
                    if (in_array($contract_info['status'], array('terminated', 'expired'))) {
                        $json['error'] = $this->language->get('error_contract_already_terminated');
                    } else {
                        $termination_data = array(
                            'status' => 'terminated',
                            'terminated_by' => $this->user->getId(),
                            'terminated_at' => date('Y-m-d H:i:s'),
                            'termination_reason' => isset($this->request->post['termination_reason']) ? $this->request->post['termination_reason'] : '',
                            'termination_notes' => isset($this->request->post['termination_notes']) ? $this->request->post['termination_notes'] : ''
                        );

                        $this->model_purchase_supplier_contracts->terminateContract($contract_id, $termination_data);

                        $json['success'] = $this->language->get('text_contract_terminated');
                    }
                } else {
                    $json['error'] = $this->language->get('error_contract_not_found');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
