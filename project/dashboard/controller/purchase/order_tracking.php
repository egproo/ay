<?php
class ControllerPurchaseOrderTracking extends Controller {
    private $error = array();

    /**
     * عرض صفحة تتبع أوامر الشراء
     */
    public function index() {
        $this->load->language('purchase/order_tracking');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/order_tracking');

        $this->getList();
    }

    /**
     * عرض قائمة تتبع أوامر الشراء
     */
    protected function getList() {
        $this->load->language('purchase/order_tracking');

        // معالجة الفلاتر
        $filter_po_number = isset($this->request->get['filter_po_number']) ? $this->request->get['filter_po_number'] : '';
        $filter_supplier_id = isset($this->request->get['filter_supplier_id']) ? $this->request->get['filter_supplier_id'] : '';
        $filter_status = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';

        $url = '';

        if (isset($this->request->get['filter_po_number'])) {
            $url .= '&filter_po_number=' . urlencode(html_entity_decode($this->request->get['filter_po_number'], ENT_QUOTES, 'UTF-8'));
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
            'href' => $this->url->link('purchase/order_tracking', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['orders'] = array();

        $filter_data = array(
            'filter_po_number'   => $filter_po_number,
            'filter_supplier_id' => $filter_supplier_id,
            'filter_status'      => $filter_status,
            'filter_date_start'  => $filter_date_start,
            'filter_date_end'    => $filter_date_end,
            'sort'               => isset($this->request->get['sort']) ? $this->request->get['sort'] : 'po.order_date',
            'order'              => isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC',
            'start'              => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'              => $this->config->get('config_limit_admin')
        );

        $page = isset($this->request->get['page']) ? $this->request->get['page'] : 1;

        $order_total = $this->model_purchase_order_tracking->getTotalOrders($filter_data);

        $results = $this->model_purchase_order_tracking->getOrders($filter_data);

        foreach ($results as $result) {
            $data['orders'][] = array(
                'po_id'              => $result['po_id'],
                'po_number'          => $result['po_number'],
                'supplier'           => $result['supplier_name'],
                'order_date'         => date($this->language->get('date_format_short'), strtotime($result['order_date'])),
                'expected_delivery'  => $result['expected_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($result['expected_delivery_date'])) : '',
                'actual_delivery'    => $result['actual_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($result['actual_delivery_date'])) : '',
                'status'             => $result['status'],
                'current_status'     => $result['current_status'],
                'total_amount'       => $this->currency->format($result['total_amount'], $result['currency_code']),
                'view'               => $this->url->link('purchase/order_tracking/view', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $result['po_id'] . $url, true),
                'update'             => $this->url->link('purchase/order_tracking/update', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $result['po_id'] . $url, true)
            );
        }

        // نصوص اللغة
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');
        $data['text_loading'] = $this->language->get('text_loading');

        $data['column_po_number'] = $this->language->get('column_po_number');
        $data['column_supplier'] = $this->language->get('column_supplier');
        $data['column_order_date'] = $this->language->get('column_order_date');
        $data['column_expected_delivery'] = $this->language->get('column_expected_delivery');
        $data['column_actual_delivery'] = $this->language->get('column_actual_delivery');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_current_status'] = $this->language->get('column_current_status');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_action'] = $this->language->get('column_action');

        $data['entry_po_number'] = $this->language->get('entry_po_number');
        $data['entry_supplier'] = $this->language->get('entry_supplier');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_date_start'] = $this->language->get('entry_date_start');
        $data['entry_date_end'] = $this->language->get('entry_date_end');

        $data['button_filter'] = $this->language->get('button_filter');
        $data['button_view'] = $this->language->get('button_view');
        $data['button_update'] = $this->language->get('button_update');

        // الفلاتر
        $data['filter_po_number'] = $filter_po_number;
        $data['filter_supplier_id'] = $filter_supplier_id;
        $data['filter_status'] = $filter_status;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        // قائمة الموردين
        $this->load->model('purchase/supplier');
        $data['suppliers'] = $this->model_purchase_supplier->getSuppliers();

        // قائمة الحالات
        $data['statuses'] = array(
            array('value' => 'created', 'text' => $this->language->get('text_status_created')),
            array('value' => 'sent_to_vendor', 'text' => $this->language->get('text_status_sent_to_vendor')),
            array('value' => 'confirmed_by_vendor', 'text' => $this->language->get('text_status_confirmed_by_vendor')),
            array('value' => 'partially_received', 'text' => $this->language->get('text_status_partially_received')),
            array('value' => 'fully_received', 'text' => $this->language->get('text_status_fully_received')),
            array('value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled')),
            array('value' => 'closed', 'text' => $this->language->get('text_status_closed'))
        );

        $url = '';

        if (isset($this->request->get['filter_po_number'])) {
            $url .= '&filter_po_number=' . urlencode(html_entity_decode($this->request->get['filter_po_number'], ENT_QUOTES, 'UTF-8'));
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

        // التصفح
        $pagination = new Pagination();
        $pagination->total = $order_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('purchase/order_tracking', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

        $data['sort'] = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'po.order_date';
        $data['order'] = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/order_tracking_list', $data));
    }

    /**
     * عرض تفاصيل تتبع أمر شراء
     */
    public function view() {
        $this->load->language('purchase/order_tracking');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/order_tracking');

        if (isset($this->request->get['po_id'])) {
            $po_id = $this->request->get['po_id'];
        } else {
            $po_id = 0;
        }

        $order_info = $this->model_purchase_order_tracking->getOrder($po_id);

        if (!$order_info) {
            $this->session->data['error'] = $this->language->get('error_not_found');
            $this->response->redirect($this->url->link('purchase/order_tracking', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getView();
    }

    /**
     * تحديث حالة تتبع أمر شراء
     */
    public function update() {
        $this->load->language('purchase/order_tracking');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/order_tracking');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateUpdate()) {
            $this->model_purchase_order_tracking->updateTracking($this->request->get['po_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_po_number'])) {
                $url .= '&filter_po_number=' . urlencode(html_entity_decode($this->request->get['filter_po_number'], ENT_QUOTES, 'UTF-8'));
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

            $this->response->redirect($this->url->link('purchase/order_tracking', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * عرض نموذج تحديث التتبع
     */
    protected function getForm() {
        $this->load->language('purchase/order_tracking');

        $data['text_form'] = !isset($this->request->get['po_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

        $url = '';

        if (isset($this->request->get['filter_po_number'])) {
            $url .= '&filter_po_number=' . urlencode(html_entity_decode($this->request->get['filter_po_number'], ENT_QUOTES, 'UTF-8'));
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
            'href' => $this->url->link('purchase/order_tracking', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['po_id'])) {
            $data['action'] = $this->url->link('purchase/order_tracking/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('purchase/order_tracking/update', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $this->request->get['po_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('purchase/order_tracking', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['po_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $order_info = $this->model_purchase_order_tracking->getOrder($this->request->get['po_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['status_change'])) {
            $data['status_change'] = $this->request->post['status_change'];
        } elseif (!empty($order_info)) {
            $data['status_change'] = $order_info['status'];
        } else {
            $data['status_change'] = '';
        }

        if (isset($this->request->post['expected_delivery_date'])) {
            $data['expected_delivery_date'] = $this->request->post['expected_delivery_date'];
        } elseif (!empty($order_info)) {
            $data['expected_delivery_date'] = $order_info['expected_delivery_date'];
        } else {
            $data['expected_delivery_date'] = '';
        }

        if (isset($this->request->post['actual_delivery_date'])) {
            $data['actual_delivery_date'] = $this->request->post['actual_delivery_date'];
        } elseif (!empty($order_info)) {
            $data['actual_delivery_date'] = $order_info['actual_delivery_date'];
        } else {
            $data['actual_delivery_date'] = '';
        }

        if (isset($this->request->post['notes'])) {
            $data['notes'] = $this->request->post['notes'];
        } elseif (!empty($order_info)) {
            $data['notes'] = $order_info['notes'];
        } else {
            $data['notes'] = '';
        }

        // قائمة الحالات
        $data['statuses'] = array(
            array('value' => 'created', 'text' => $this->language->get('text_status_created')),
            array('value' => 'sent_to_vendor', 'text' => $this->language->get('text_status_sent_to_vendor')),
            array('value' => 'confirmed_by_vendor', 'text' => $this->language->get('text_status_confirmed_by_vendor')),
            array('value' => 'partially_received', 'text' => $this->language->get('text_status_partially_received')),
            array('value' => 'fully_received', 'text' => $this->language->get('text_status_fully_received')),
            array('value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled')),
            array('value' => 'closed', 'text' => $this->language->get('text_status_closed'))
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/order_tracking_form', $data));
    }

    /**
     * عرض تفاصيل التتبع
     */
    protected function getView() {
        $this->load->language('purchase/order_tracking');

        if (isset($this->request->get['po_id'])) {
            $po_id = $this->request->get['po_id'];
        } else {
            $po_id = 0;
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/order_tracking', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_view'),
            'href' => $this->url->link('purchase/order_tracking/view', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true)
        );

        $data['po_id'] = $po_id;

        $order_info = $this->model_purchase_order_tracking->getOrder($po_id);

        if ($order_info) {
            $data['order'] = $order_info;
            $data['tracking_history'] = $this->model_purchase_order_tracking->getTrackingHistory($po_id);
        } else {
            $data['order'] = array();
            $data['tracking_history'] = array();
        }

        $data['update'] = $this->url->link('purchase/order_tracking/update', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true);
        $data['back'] = $this->url->link('purchase/order_tracking', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/order_tracking_view', $data));
    }

    /**
     * التحقق من صحة بيانات التحديث
     */
    protected function validateUpdate() {
        if (!$this->user->hasPermission('modify', 'purchase/order_tracking')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['status_change'])) {
            $this->error['status'] = $this->language->get('error_status');
        }

        return !$this->error;
    }

    /**
     * تحديث حالة التتبع عبر AJAX
     */
    public function ajaxUpdateStatus() {
        $this->load->language('purchase/order_tracking');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/order_tracking')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/order_tracking');

            if (isset($this->request->post['po_id'])) {
                $po_id = $this->request->post['po_id'];
            } else {
                $po_id = 0;
            }

            if (isset($this->request->post['status_change'])) {
                $status_change = $this->request->post['status_change'];
            } else {
                $status_change = '';
            }

            if (isset($this->request->post['notes'])) {
                $notes = $this->request->post['notes'];
            } else {
                $notes = '';
            }

            if (!$po_id) {
                $json['error'] = $this->language->get('error_po_id');
            } elseif (!$status_change) {
                $json['error'] = $this->language->get('error_status');
            } else {
                $tracking_data = array(
                    'status_change' => $status_change,
                    'notes' => $notes,
                    'expected_delivery_date' => isset($this->request->post['expected_delivery_date']) ? $this->request->post['expected_delivery_date'] : '',
                    'actual_delivery_date' => isset($this->request->post['actual_delivery_date']) ? $this->request->post['actual_delivery_date'] : '',
                    'created_by' => $this->user->getId()
                );

                $this->model_purchase_order_tracking->updateTracking($po_id, $tracking_data);

                $json['success'] = $this->language->get('text_success');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على سجل التتبع عبر AJAX
     */
    public function ajaxGetTrackingHistory() {
        $this->load->language('purchase/order_tracking');
        $json = array();

        if (!$this->user->hasPermission('access', 'purchase/order_tracking')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/order_tracking');

            if (isset($this->request->get['po_id'])) {
                $po_id = $this->request->get['po_id'];
            } else {
                $po_id = 0;
            }

            if (!$po_id) {
                $json['error'] = $this->language->get('error_po_id');
            } else {
                $tracking_history = $this->model_purchase_order_tracking->getTrackingHistory($po_id);
                $json['data'] = $tracking_history;
                $json['success'] = true;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحديث معلومات التسليم عبر AJAX
     */
    public function ajaxUpdateDelivery() {
        $this->load->language('purchase/order_tracking');
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/order_tracking')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/order_tracking');

            if (isset($this->request->post['po_id'])) {
                $po_id = $this->request->post['po_id'];
            } else {
                $po_id = 0;
            }

            if (!$po_id) {
                $json['error'] = $this->language->get('error_po_id');
            } else {
                $tracking_data = array(
                    'expected_delivery_date' => isset($this->request->post['expected_delivery_date']) ? $this->request->post['expected_delivery_date'] : '',
                    'actual_delivery_date' => isset($this->request->post['actual_delivery_date']) ? $this->request->post['actual_delivery_date'] : '',
                    'notes' => isset($this->request->post['notes']) ? $this->request->post['notes'] : '',
                    'created_by' => $this->user->getId()
                );

                // تحديد نوع التحديث بناءً على البيانات المرسلة
                if (!empty($tracking_data['actual_delivery_date'])) {
                    $tracking_data['status_change'] = 'delivery_completed';
                } elseif (!empty($tracking_data['expected_delivery_date'])) {
                    $tracking_data['status_change'] = 'delivery_date_updated';
                } else {
                    $json['error'] = $this->language->get('error_delivery_date');
                }

                if (!isset($json['error'])) {
                    $this->model_purchase_order_tracking->updateTracking($po_id, $tracking_data);
                    $json['success'] = $this->language->get('success_delivery_updated');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * البحث التلقائي لأرقام أوامر الشراء
     */
    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_po_number'])) {
            $this->load->model('purchase/order_tracking');

            $filter_data = array(
                'filter_po_number' => $this->request->get['filter_po_number'],
                'start' => 0,
                'limit' => 5
            );

            $results = $this->model_purchase_order_tracking->getOrders($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'po_id'     => $result['po_id'],
                    'po_number' => strip_tags(html_entity_decode($result['po_number'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تصدير بيانات التتبع
     */
    public function export() {
        $this->load->language('purchase/order_tracking');

        if (!$this->user->hasPermission('access', 'purchase/order_tracking')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->load->model('purchase/order_tracking');

        // معالجة الفلاتر
        $filter_data = array(
            'filter_po_number'   => isset($this->request->get['filter_po_number']) ? $this->request->get['filter_po_number'] : '',
            'filter_supplier_id' => isset($this->request->get['filter_supplier_id']) ? $this->request->get['filter_supplier_id'] : '',
            'filter_status'      => isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '',
            'filter_date_start'  => isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '',
            'filter_date_end'    => isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : ''
        );

        $results = $this->model_purchase_order_tracking->getOrders($filter_data);

        $output = '';
        $output .= 'PO Number,Supplier,Order Date,Expected Delivery,Actual Delivery,Status,Current Status,Total Amount' . "\n";

        foreach ($results as $result) {
            $output .= '"' . $result['po_number'] . '",';
            $output .= '"' . $result['supplier_name'] . '",';
            $output .= '"' . $result['order_date'] . '",';
            $output .= '"' . ($result['expected_delivery_date'] ? $result['expected_delivery_date'] : '') . '",';
            $output .= '"' . ($result['actual_delivery_date'] ? $result['actual_delivery_date'] : '') . '",';
            $output .= '"' . $result['status'] . '",';
            $output .= '"' . $result['current_status'] . '",';
            $output .= '"' . $result['total_amount'] . '"' . "\n";
        }

        $this->response->addheader('Pragma: public');
        $this->response->addheader('Expires: 0');
        $this->response->addheader('Content-Description: File Transfer');
        $this->response->addheader('Content-Type: application/octet-stream');
        $this->response->addheader('Content-Disposition: attachment; filename="purchase_order_tracking_' . date('Y-m-d') . '.csv"');
        $this->response->addheader('Content-Transfer-Encoding: binary');
        $this->response->setOutput($output);
    }

    /**
     * الحصول على إحصائيات التتبع
     */
    public function getStatistics() {
        $this->load->language('purchase/order_tracking');
        $json = array();

        if (!$this->user->hasPermission('access', 'purchase/order_tracking')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('purchase/order_tracking');

            $date_start = isset($this->request->get['date_start']) ? $this->request->get['date_start'] : '';
            $date_end = isset($this->request->get['date_end']) ? $this->request->get['date_end'] : '';

            $statistics = $this->model_purchase_order_tracking->getTrackingStatistics($date_start, $date_end);
            $overdue_orders = $this->model_purchase_order_tracking->getOverdueOrders();
            $upcoming_deliveries = $this->model_purchase_order_tracking->getUpcomingDeliveries();

            $json['statistics'] = $statistics;
            $json['overdue_orders'] = $overdue_orders;
            $json['upcoming_deliveries'] = $upcoming_deliveries;
            $json['success'] = true;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
