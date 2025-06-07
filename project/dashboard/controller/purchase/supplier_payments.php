<?php
class ControllerPurchaseSupplierPayments extends Controller {
    private $error = array();

    /**
     * عرض صفحة دفعات الموردين
     */
    public function index() {
        $this->load->language('purchase/supplier_payments');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/supplier_payments');

        $this->getList();
    }

    /**
     * إضافة دفعة مورد جديدة
     */
    public function add() {
        $this->load->language('purchase/supplier_payments');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/supplier_payments');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $payment_id = $this->model_purchase_supplier_payments->addPayment($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_supplier_id'])) {
                $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
            }

            if (isset($this->request->get['filter_payment_method'])) {
                $url .= '&filter_payment_method=' . $this->request->get['filter_payment_method'];
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

            $this->response->redirect($this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * تعديل دفعة مورد
     */
    public function edit() {
        $this->load->language('purchase/supplier_payments');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/supplier_payments');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_purchase_supplier_payments->editPayment($this->request->get['payment_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_supplier_id'])) {
                $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
            }

            if (isset($this->request->get['filter_payment_method'])) {
                $url .= '&filter_payment_method=' . $this->request->get['filter_payment_method'];
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

            $this->response->redirect($this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * حذف دفعة مورد
     */
    public function delete() {
        $this->load->language('purchase/supplier_payments');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/supplier_payments');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $payment_id) {
                $this->model_purchase_supplier_payments->deletePayment($payment_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_supplier_id'])) {
                $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
            }

            if (isset($this->request->get['filter_payment_method'])) {
                $url .= '&filter_payment_method=' . $this->request->get['filter_payment_method'];
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

            $this->response->redirect($this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * عرض قائمة دفعات الموردين
     */
    protected function getList() {
        if (isset($this->request->get['filter_supplier_id'])) {
            $filter_supplier_id = $this->request->get['filter_supplier_id'];
        } else {
            $filter_supplier_id = '';
        }

        if (isset($this->request->get['filter_payment_method'])) {
            $filter_payment_method = $this->request->get['filter_payment_method'];
        } else {
            $filter_payment_method = '';
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
            $sort = 'sp.payment_date';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_payment_method'])) {
            $url .= '&filter_payment_method=' . $this->request->get['filter_payment_method'];
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
            'href' => $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('purchase/supplier_payments/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('purchase/supplier_payments/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['payments'] = array();

        $filter_data = array(
            'filter_supplier_id'    => $filter_supplier_id,
            'filter_payment_method' => $filter_payment_method,
            'filter_status'         => $filter_status,
            'filter_date_start'     => $filter_date_start,
            'filter_date_end'       => $filter_date_end,
            'sort'                  => $sort,
            'order'                 => $order,
            'start'                 => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'                 => $this->config->get('config_limit_admin')
        );

        $payment_total = $this->model_purchase_supplier_payments->getTotalPayments($filter_data);

        $results = $this->model_purchase_supplier_payments->getPayments($filter_data);

        foreach ($results as $result) {
            $data['payments'][] = array(
                'payment_id'       => $result['payment_id'],
                'payment_number'   => $result['payment_number'],
                'supplier_name'    => $result['supplier_name'],
                'payment_amount'   => $this->currency->format($result['payment_amount'], $this->config->get('config_currency')),
                'payment_method'   => $result['payment_method_name'],
                'payment_date'     => date($this->language->get('date_format_short'), strtotime($result['payment_date'])),
                'reference_number' => $result['reference_number'],
                'status'           => $result['status'],
                'status_class'     => $this->getStatusClass($result['status']),
                'edit'             => $this->url->link('purchase/supplier_payments/edit', 'user_token=' . $this->session->data['user_token'] . '&payment_id=' . $result['payment_id'] . $url, true)
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

        $data['sort_payment_number'] = $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . '&sort=sp.payment_number' . $url, true);
        $data['sort_supplier'] = $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . '&sort=supplier_name' . $url, true);
        $data['sort_amount'] = $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . '&sort=sp.payment_amount' . $url, true);
        $data['sort_date'] = $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . '&sort=sp.payment_date' . $url, true);
        $data['sort_status'] = $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . '&sort=sp.status' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_payment_method'])) {
            $url .= '&filter_payment_method=' . $this->request->get['filter_payment_method'];
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
        $pagination->total = $payment_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($payment_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($payment_total - $this->config->get('config_limit_admin'))) ? $payment_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $payment_total, ceil($payment_total / $this->config->get('config_limit_admin')));

        $data['filter_supplier_id'] = $filter_supplier_id;
        $data['filter_payment_method'] = $filter_payment_method;
        $data['filter_status'] = $filter_status;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        $data['sort'] = $sort;
        $data['order'] = $order;

        // قائمة الموردين للفلترة
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        // قائمة طرق الدفع
        $this->load->model('localisation/payment_method');
        $data['payment_methods'] = $this->model_localisation_payment_method->getPaymentMethods();

        // حالات الدفع
        $data['statuses'] = array(
            array('value' => 'pending', 'text' => $this->language->get('text_status_pending')),
            array('value' => 'approved', 'text' => $this->language->get('text_status_approved')),
            array('value' => 'paid', 'text' => $this->language->get('text_status_paid')),
            array('value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled'))
        );

        // إحصائيات الدفعات
        $data['payment_statistics'] = $this->model_purchase_supplier_payments->getPaymentStatistics();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/supplier_payments_list', $data));
    }

    /**
     * عرض نموذج إضافة/تعديل دفعة مورد
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['payment_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['supplier_id'])) {
            $data['error_supplier_id'] = $this->error['supplier_id'];
        } else {
            $data['error_supplier_id'] = '';
        }

        if (isset($this->error['payment_amount'])) {
            $data['error_payment_amount'] = $this->error['payment_amount'];
        } else {
            $data['error_payment_amount'] = '';
        }

        if (isset($this->error['payment_method'])) {
            $data['error_payment_method'] = $this->error['payment_method'];
        } else {
            $data['error_payment_method'] = '';
        }

        if (isset($this->error['payment_date'])) {
            $data['error_payment_date'] = $this->error['payment_date'];
        } else {
            $data['error_payment_date'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_payment_method'])) {
            $url .= '&filter_payment_method=' . $this->request->get['filter_payment_method'];
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
            'href' => $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['payment_id'])) {
            $data['action'] = $this->url->link('purchase/supplier_payments/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('purchase/supplier_payments/edit', 'user_token=' . $this->session->data['user_token'] . '&payment_id=' . $this->request->get['payment_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['payment_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $payment_info = $this->model_purchase_supplier_payments->getPayment($this->request->get['payment_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['supplier_id'])) {
            $data['supplier_id'] = $this->request->post['supplier_id'];
        } elseif (!empty($payment_info)) {
            $data['supplier_id'] = $payment_info['supplier_id'];
        } else {
            $data['supplier_id'] = '';
        }

        if (isset($this->request->post['payment_amount'])) {
            $data['payment_amount'] = $this->request->post['payment_amount'];
        } elseif (!empty($payment_info)) {
            $data['payment_amount'] = $payment_info['payment_amount'];
        } else {
            $data['payment_amount'] = '';
        }

        if (isset($this->request->post['payment_method_id'])) {
            $data['payment_method_id'] = $this->request->post['payment_method_id'];
        } elseif (!empty($payment_info)) {
            $data['payment_method_id'] = $payment_info['payment_method_id'];
        } else {
            $data['payment_method_id'] = '';
        }

        if (isset($this->request->post['payment_date'])) {
            $data['payment_date'] = $this->request->post['payment_date'];
        } elseif (!empty($payment_info)) {
            $data['payment_date'] = $payment_info['payment_date'];
        } else {
            $data['payment_date'] = date('Y-m-d');
        }

        if (isset($this->request->post['reference_number'])) {
            $data['reference_number'] = $this->request->post['reference_number'];
        } elseif (!empty($payment_info)) {
            $data['reference_number'] = $payment_info['reference_number'];
        } else {
            $data['reference_number'] = '';
        }

        if (isset($this->request->post['notes'])) {
            $data['notes'] = $this->request->post['notes'];
        } elseif (!empty($payment_info)) {
            $data['notes'] = $payment_info['notes'];
        } else {
            $data['notes'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($payment_info)) {
            $data['status'] = $payment_info['status'];
        } else {
            $data['status'] = 'pending';
        }

        // قائمة الموردين
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        // قائمة طرق الدفع
        $this->load->model('localisation/payment_method');
        $data['payment_methods'] = $this->model_localisation_payment_method->getPaymentMethods();

        // حالات الدفع
        $data['statuses'] = array(
            array('value' => 'pending', 'text' => $this->language->get('text_status_pending')),
            array('value' => 'approved', 'text' => $this->language->get('text_status_approved')),
            array('value' => 'paid', 'text' => $this->language->get('text_status_paid')),
            array('value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled'))
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/supplier_payments_form', $data));
    }

    /**
     * التحقق من صحة بيانات النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'purchase/supplier_payments')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['supplier_id'])) {
            $this->error['supplier_id'] = $this->language->get('error_supplier');
        }

        if (empty($this->request->post['payment_amount']) || $this->request->post['payment_amount'] <= 0) {
            $this->error['payment_amount'] = $this->language->get('error_payment_amount');
        }

        if (empty($this->request->post['payment_method_id'])) {
            $this->error['payment_method'] = $this->language->get('error_payment_method');
        }

        if (empty($this->request->post['payment_date'])) {
            $this->error['payment_date'] = $this->language->get('error_payment_date');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة عملية الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'purchase/supplier_payments')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * الحصول على فئة CSS للحالة
     */
    private function getStatusClass($status) {
        switch ($status) {
            case 'pending':
                return 'warning';
            case 'approved':
                return 'info';
            case 'paid':
                return 'success';
            case 'cancelled':
                return 'danger';
            default:
                return 'default';
        }
    }

    /**
     * اعتماد دفعة
     */
    public function approve() {
        $this->load->language('purchase/supplier_payments');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/supplier_payments')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/supplier_payments');

            if (isset($this->request->post['payment_id'])) {
                $payment_id = $this->request->post['payment_id'];

                $result = $this->model_purchase_supplier_payments->approvePayment($payment_id);

                if ($result) {
                    $json['success'] = $this->language->get('text_payment_approved');
                } else {
                    $json['error'] = $this->language->get('error_approve_payment');
                }
            } else {
                $json['error'] = $this->language->get('error_payment_id');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إلغاء دفعة
     */
    public function cancel() {
        $this->load->language('purchase/supplier_payments');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/supplier_payments')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/supplier_payments');

            if (isset($this->request->post['payment_id'])) {
                $payment_id = $this->request->post['payment_id'];
                $cancellation_reason = isset($this->request->post['cancellation_reason']) ? $this->request->post['cancellation_reason'] : '';

                $result = $this->model_purchase_supplier_payments->cancelPayment($payment_id, $cancellation_reason);

                if ($result) {
                    $json['success'] = $this->language->get('text_payment_cancelled');
                } else {
                    $json['error'] = $this->language->get('error_cancel_payment');
                }
            } else {
                $json['error'] = $this->language->get('error_payment_id');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تقرير دفعات الموردين
     */
    public function report() {
        $this->load->language('purchase/supplier_payments');

        $this->document->setTitle($this->language->get('text_payment_report'));

        $this->load->model('purchase/supplier_payments');

        if (isset($this->request->get['date_start'])) {
            $date_start = $this->request->get['date_start'];
        } else {
            $date_start = date('Y-m-01'); // بداية الشهر الحالي
        }

        if (isset($this->request->get['date_end'])) {
            $date_end = $this->request->get['date_end'];
        } else {
            $date_end = date('Y-m-d'); // اليوم
        }

        if (isset($this->request->get['supplier_id'])) {
            $supplier_id = $this->request->get['supplier_id'];
        } else {
            $supplier_id = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment_report'),
            'href' => $this->url->link('purchase/supplier_payments/report', 'user_token=' . $this->session->data['user_token'], true)
        );

        $filter_data = array(
            'filter_date_start' => $date_start,
            'filter_date_end' => $date_end,
            'filter_supplier_id' => $supplier_id
        );

        $data['payment_report'] = $this->model_purchase_supplier_payments->getPaymentReport($filter_data);
        $data['payment_summary'] = $this->model_purchase_supplier_payments->getPaymentSummary($filter_data);

        // قائمة الموردين
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['supplier_id'] = $supplier_id;
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/supplier_payments_report', $data));
    }
}
