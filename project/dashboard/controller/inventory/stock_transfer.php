<?php
/**
 * إدارة نقل المخزون بين الفروع المتطور (Advanced Stock Transfer Controller) - الجزء الثاني
 *
 * الهدف: توفير واجهة متطورة لإدارة نقل المخزون بين المستودعات والمتاجر
 * الميزات: طلبات نقل، موافقات، تتبع الشحنات، تسويات تلقائية، تقارير متقدمة
 * التكامل: مع المخزون والجرد والتسويات والشحن والإشعارات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryStockTransfer extends Controller {

    private $error = array();

    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/stock_transfer');

        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل النماذج المطلوبة
        $this->load->model('inventory/stock_transfer');
        $this->load->model('inventory/branch');
        $this->load->model('user/user');

        // معالجة الطلبات
        $this->getList();
    }

    protected function getList() {
        // معالجة الفلاتر
        $filter_data = $this->getFilters();

        // إعداد الروابط
        $url = $this->buildUrl($filter_data);

        // إعداد البيانات الأساسية
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        // روابط الإجراءات
        $data['add'] = $this->url->link('inventory/stock_transfer/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_excel'] = $this->url->link('inventory/stock_transfer/exportExcel', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['export_pdf'] = $this->url->link('inventory/stock_transfer/exportPdf', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['print'] = $this->url->link('inventory/stock_transfer/print', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['refresh'] = $this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true);

        // الحصول على البيانات
        $stock_transfers = array();
        $filter_data_with_pagination = $filter_data;
        $filter_data_with_pagination['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data_with_pagination['limit'] = $this->config->get('config_limit_admin');

        $results = $this->model_inventory_stock_transfer->getStockTransfers($filter_data_with_pagination);
        $total = $this->model_inventory_stock_transfer->getTotalStockTransfers($filter_data);

        foreach ($results as $result) {
            $progress_percentage = $result['total_quantity'] > 0 ? round(($result['total_received_quantity'] / $result['total_quantity']) * 100, 1) : 0;

            $stock_transfers[] = array(
                'transfer_id'             => $result['transfer_id'],
                'transfer_number'         => $result['transfer_number'],
                'transfer_name'           => $result['transfer_name'],
                'transfer_type'           => $result['transfer_type'],
                'transfer_type_text'      => $result['transfer_type_text'],
                'status'                  => $result['status'],
                'status_text'             => $result['status_text'],
                'status_class'            => $this->getStatusClass($result['status']),
                'priority'                => $result['priority'],
                'priority_text'           => $result['priority_text'],
                'priority_class'          => $this->getPriorityClass($result['priority']),
                'from_branch_name'        => $result['from_branch_name'],
                'from_branch_type'        => $this->language->get('text_branch_type_' . $result['from_branch_type']),
                'to_branch_name'          => $result['to_branch_name'],
                'to_branch_type'          => $this->language->get('text_branch_type_' . $result['to_branch_type']),
                'reason_name'             => $result['reason_name'] ? $result['reason_name'] : $this->language->get('text_no_reason'),
                'user_name'               => $result['user_name'],
                'approved_by_name'        => $result['approved_by_name'],
                'shipped_by_name'         => $result['shipped_by_name'],
                'received_by_name'        => $result['received_by_name'],
                'request_date'            => date($this->language->get('date_format_short'), strtotime($result['request_date'])),
                'approval_date'           => $result['approval_date'] ? date($this->language->get('date_format_short'), strtotime($result['approval_date'])) : '',
                'ship_date'               => $result['ship_date'] ? date($this->language->get('date_format_short'), strtotime($result['ship_date'])) : '',
                'expected_delivery_date'  => $result['expected_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($result['expected_delivery_date'])) : '',
                'actual_delivery_date'    => $result['actual_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($result['actual_delivery_date'])) : '',
                'total_items'             => number_format($result['total_items']),
                'total_quantity'          => number_format($result['total_quantity'], 2),
                'total_received_quantity' => number_format($result['total_received_quantity'], 2),
                'total_value'             => $this->currency->format($result['total_value'], $this->config->get('config_currency')),
                'total_value_raw'         => $result['total_value'],
                'progress_percentage'     => $progress_percentage,
                'progress_class'          => $this->getProgressClass($progress_percentage),
                'value_class'             => $this->getValueClass($result['total_value']),
                'notes'                   => $result['notes'],
                'date_added'              => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
                'can_edit'                => $result['status'] == 'draft',
                'can_approve'             => $result['status'] == 'pending_approval' && $this->user->hasPermission('modify', 'inventory/stock_transfer'),
                'can_ship'                => $result['status'] == 'approved',
                'can_receive'             => in_array($result['status'], array('shipped', 'in_transit', 'delivered')),
                'can_complete'            => $result['status'] == 'received',
                'edit'                    => $this->url->link('inventory/stock_transfer/edit', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $result['transfer_id'], true),
                'view'                    => $this->url->link('inventory/stock_transfer/view', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $result['transfer_id'], true),
                'approve'                 => $this->url->link('inventory/stock_transfer/approve', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $result['transfer_id'], true),
                'ship'                    => $this->url->link('inventory/stock_transfer/ship', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $result['transfer_id'], true),
                'receive'                 => $this->url->link('inventory/stock_transfer/receive', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $result['transfer_id'], true),
                'complete'                => $this->url->link('inventory/stock_transfer/complete', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $result['transfer_id'], true),
                'delete'                  => $this->url->link('inventory/stock_transfer/delete', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $result['transfer_id'], true)
            );
        }

        $data['stock_transfers'] = $stock_transfers;

        // الحصول على ملخص النقل
        $summary = $this->model_inventory_stock_transfer->getTransferSummary($filter_data);
        $data['summary'] = array(
            'total_transfers'         => number_format($summary['total_transfers']),
            'draft_count'             => number_format($summary['draft_count']),
            'pending_approval_count'  => number_format($summary['pending_approval_count']),
            'approved_count'          => number_format($summary['approved_count']),
            'shipped_count'           => number_format($summary['shipped_count']),
            'in_transit_count'        => number_format($summary['in_transit_count']),
            'delivered_count'         => number_format($summary['delivered_count']),
            'received_count'          => number_format($summary['received_count']),
            'completed_count'         => number_format($summary['completed_count']),
            'cancelled_count'         => number_format($summary['cancelled_count']),
            'total_completed_value'   => $this->currency->format($summary['total_completed_value'], $this->config->get('config_currency')),
            'avg_items_per_transfer'  => number_format($summary['avg_items_per_transfer'], 1)
        );

        // الحصول على التحليلات
        $data['transfers_by_branch'] = $this->model_inventory_stock_transfer->getTransfersByBranch($filter_data);

        // إعداد الفلاتر للعرض
        $this->setupFiltersForDisplay($data, $filter_data);

        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) > ($total - $this->config->get('config_limit_admin'))) ? $total : ((($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $total, ceil($total / $this->config->get('config_limit_admin')));

        // إعداد الترتيب
        $data['sort'] = $filter_data['sort'];
        $data['order'] = $filter_data['order'];

        $data['user_token'] = $this->session->data['user_token'];

        // رسائل النجاح والخطأ
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

        $this->response->setOutput($this->load->view('inventory/stock_transfer_list', $data));
    }

    /**
     * إضافة طلب نقل جديد
     */
    public function add() {
        $this->load->language('inventory/stock_transfer');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stock_transfer');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $transfer_id = $this->model_inventory_stock_transfer->addStockTransfer($this->request->post);

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

            $this->response->redirect($this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * تعديل طلب نقل
     */
    public function edit() {
        $this->load->language('inventory/stock_transfer');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stock_transfer');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_stock_transfer->editStockTransfer($this->request->get['transfer_id'], $this->request->post);

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

            $this->response->redirect($this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * حذف طلب نقل
     */
    public function delete() {
        $this->load->language('inventory/stock_transfer');
        $this->load->model('inventory/stock_transfer');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $transfer_id) {
                $this->model_inventory_stock_transfer->deleteStockTransfer($transfer_id);
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

            $this->response->redirect($this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * الموافقة على طلب نقل
     */
    public function approve() {
        $this->load->language('inventory/stock_transfer');
        $this->load->model('inventory/stock_transfer');

        $transfer_id = isset($this->request->get['transfer_id']) ? (int)$this->request->get['transfer_id'] : 0;

        if ($transfer_id) {
            // التحقق من توفر المخزون
            $availability = $this->model_inventory_stock_transfer->checkStockAvailability($transfer_id);
            $all_available = true;

            foreach ($availability as $item) {
                if (!$item['is_available']) {
                    $all_available = false;
                    break;
                }
            }

            if ($all_available) {
                $this->model_inventory_stock_transfer->changeStatus($transfer_id, 'approved');
                $this->session->data['success'] = $this->language->get('text_approved_success');
            } else {
                $this->session->data['error'] = $this->language->get('error_insufficient_stock');
            }
        }

        $this->response->redirect($this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true));
    }

    /**
     * شحن طلب نقل
     */
    public function ship() {
        $this->load->language('inventory/stock_transfer');
        $this->load->model('inventory/stock_transfer');

        $transfer_id = isset($this->request->get['transfer_id']) ? (int)$this->request->get['transfer_id'] : 0;

        if ($transfer_id) {
            $this->model_inventory_stock_transfer->changeStatus($transfer_id, 'shipped');
            $this->session->data['success'] = $this->language->get('text_shipped_success');
        }

        $this->response->redirect($this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true));
    }

    /**
     * استلام طلب نقل
     */
    public function receive() {
        $this->load->language('inventory/stock_transfer');
        $this->load->model('inventory/stock_transfer');

        $transfer_id = isset($this->request->get['transfer_id']) ? (int)$this->request->get['transfer_id'] : 0;

        if ($transfer_id) {
            // معالجة تحديث الكميات المستلمة
            if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['items'])) {
                foreach ($this->request->post['items'] as $item_id => $item_data) {
                    if (isset($item_data['received_quantity']) && $item_data['received_quantity'] !== '') {
                        $this->model_inventory_stock_transfer->updateReceivedQuantity(
                            $item_id,
                            $item_data['received_quantity'],
                            isset($item_data['notes']) ? $item_data['notes'] : ''
                        );
                    }
                }

                $this->model_inventory_stock_transfer->changeStatus($transfer_id, 'received');
                $this->session->data['success'] = $this->language->get('text_received_success');

                $this->response->redirect($this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true));
            }

            // عرض نموذج الاستلام
            $this->getReceiveForm($transfer_id);
        } else {
            $this->response->redirect($this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * إكمال طلب نقل
     */
    public function complete() {
        $this->load->language('inventory/stock_transfer');
        $this->load->model('inventory/stock_transfer');

        $transfer_id = isset($this->request->get['transfer_id']) ? (int)$this->request->get['transfer_id'] : 0;

        if ($transfer_id) {
            $this->model_inventory_stock_transfer->changeStatus($transfer_id, 'completed');
            $this->session->data['success'] = $this->language->get('text_completed_success');
        }

        $this->response->redirect($this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true));
    }

    /**
     * معالجة الفلاتر
     */
    private function getFilters() {
        $filters = array(
            'filter_transfer_number'  => '',
            'filter_transfer_name'    => '',
            'filter_status'           => '',
            'filter_transfer_type'    => '',
            'filter_priority'         => '',
            'filter_from_branch_id'   => '',
            'filter_to_branch_id'     => '',
            'filter_reason_id'        => '',
            'filter_user_id'          => '',
            'filter_date_from'        => '',
            'filter_date_to'          => '',
            'filter_min_value'        => '',
            'filter_max_value'        => '',
            'sort'                    => 'st.date_added',
            'order'                   => 'DESC',
            'page'                    => 1
        );

        foreach ($filters as $key => $default) {
            if (isset($this->request->get[$key])) {
                $filters[$key] = $this->request->get[$key];
            }
        }

        return $filters;
    }

    /**
     * بناء رابط URL مع الفلاتر
     */
    private function buildUrl($filters) {
        $url = '';

        foreach ($filters as $key => $value) {
            if ($value !== '' && $key !== 'page') {
                $url .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
            }
        }

        return $url;
    }

    /**
     * الحصول على فئة CSS للحالة
     */
    private function getStatusClass($status) {
        switch ($status) {
            case 'draft':
                return 'default';
            case 'pending_approval':
                return 'warning';
            case 'approved':
                return 'info';
            case 'shipped':
                return 'primary';
            case 'in_transit':
                return 'primary';
            case 'delivered':
                return 'info';
            case 'received':
                return 'info';
            case 'completed':
                return 'success';
            case 'cancelled':
                return 'default';
            case 'rejected':
                return 'danger';
            default:
                return 'default';
        }
    }

    /**
     * الحصول على فئة CSS للأولوية
     */
    private function getPriorityClass($priority) {
        switch ($priority) {
            case 'low':
                return 'success';
            case 'normal':
                return 'info';
            case 'high':
                return 'warning';
            case 'urgent':
                return 'danger';
            default:
                return 'info';
        }
    }

    /**
     * الحصول على فئة CSS للتقدم
     */
    private function getProgressClass($percentage) {
        if ($percentage >= 100) {
            return 'success';
        } elseif ($percentage >= 75) {
            return 'info';
        } elseif ($percentage >= 50) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * الحصول على فئة CSS للقيمة
     */
    private function getValueClass($value) {
        if ($value >= 50000) {
            return 'danger';  // قيمة عالية جداً
        } elseif ($value >= 20000) {
            return 'warning'; // قيمة عالية
        } elseif ($value >= 5000) {
            return 'info';    // قيمة متوسطة
        } else {
            return 'success'; // قيمة منخفضة
        }
    }

    /**
     * عرض نموذج الإضافة/التعديل
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['transfer_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        // إعداد البيانات للنموذج
        $this->setupFormData($data);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/stock_transfer_form', $data));
    }

    /**
     * إعداد بيانات النموذج
     */
    private function setupFormData(&$data) {
        // الحصول على البيانات الموجودة أو القيم الافتراضية
        if (isset($this->request->get['transfer_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $transfer_info = $this->model_inventory_stock_transfer->getStockTransfer($this->request->get['transfer_id']);
            $transfer_items = $this->model_inventory_stock_transfer->getTransferItems($this->request->get['transfer_id']);
        }

        $fields = array(
            'transfer_number', 'transfer_name', 'transfer_type', 'priority',
            'from_branch_id', 'to_branch_id', 'reason_id', 'request_date',
            'expected_delivery_date', 'notes'
        );

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($transfer_info)) {
                $data[$field] = $transfer_info[$field];
            } else {
                $data[$field] = '';
            }
        }

        // توليد رقم نقل جديد للإضافة
        if (!isset($this->request->get['transfer_id'])) {
            $data['transfer_number'] = $this->model_inventory_stock_transfer->generateTransferNumber();
            $data['request_date'] = date('Y-m-d');
        }

        // عناصر النقل
        if (isset($this->request->post['transfer_items'])) {
            $data['transfer_items'] = $this->request->post['transfer_items'];
        } elseif (!empty($transfer_items)) {
            $data['transfer_items'] = $transfer_items;
        } else {
            $data['transfer_items'] = array();
        }

        // الحصول على القوائم
        $this->load->model('inventory/branch');
        $this->load->model('catalog/product');

        $data['branches'] = $this->model_inventory_branch->getBranches();
        $data['transfer_reasons'] = $this->model_inventory_stock_transfer->getTransferReasons();

        // خيارات نوع النقل
        $data['transfer_types'] = array(
            array('value' => 'regular', 'text' => $this->language->get('text_transfer_type_regular')),
            array('value' => 'emergency', 'text' => $this->language->get('text_transfer_type_emergency')),
            array('value' => 'restock', 'text' => $this->language->get('text_transfer_type_restock')),
            array('value' => 'redistribution', 'text' => $this->language->get('text_transfer_type_redistribution')),
            array('value' => 'return', 'text' => $this->language->get('text_transfer_type_return'))
        );

        // خيارات الأولوية
        $data['priorities'] = array(
            array('value' => 'low', 'text' => $this->language->get('text_priority_low')),
            array('value' => 'normal', 'text' => $this->language->get('text_priority_normal')),
            array('value' => 'high', 'text' => $this->language->get('text_priority_high')),
            array('value' => 'urgent', 'text' => $this->language->get('text_priority_urgent'))
        );

        // الروابط
        $data['action'] = $this->url->link('inventory/stock_transfer/' . (!isset($this->request->get['transfer_id']) ? 'add' : 'edit'), 'user_token=' . $this->session->data['user_token'] . (!isset($this->request->get['transfer_id']) ? '' : '&transfer_id=' . $this->request->get['transfer_id']), true);
        $data['cancel'] = $this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true);
        $data['product_autocomplete'] = $this->url->link('catalog/product/autocomplete', 'user_token=' . $this->session->data['user_token'], true);
        $data['check_availability'] = $this->url->link('inventory/stock_transfer/checkAvailability', 'user_token=' . $this->session->data['user_token'], true);
    }

    /**
     * إعداد الفلاتر للعرض
     */
    private function setupFiltersForDisplay(&$data, $filters) {
        // نسخ الفلاتر للعرض
        foreach ($filters as $key => $value) {
            $data[$key] = $value;
        }

        // الحصول على قوائم الفلاتر
        $data['branches'] = $this->model_inventory_branch->getBranches();
        $data['transfer_reasons'] = $this->model_inventory_stock_transfer->getTransferReasons();
        $data['users'] = $this->model_user_user->getUsers();

        // خيارات الحالة
        $data['status_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'draft', 'text' => $this->language->get('text_status_draft')),
            array('value' => 'pending_approval', 'text' => $this->language->get('text_status_pending_approval')),
            array('value' => 'approved', 'text' => $this->language->get('text_status_approved')),
            array('value' => 'shipped', 'text' => $this->language->get('text_status_shipped')),
            array('value' => 'in_transit', 'text' => $this->language->get('text_status_in_transit')),
            array('value' => 'delivered', 'text' => $this->language->get('text_status_delivered')),
            array('value' => 'received', 'text' => $this->language->get('text_status_received')),
            array('value' => 'completed', 'text' => $this->language->get('text_status_completed')),
            array('value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled')),
            array('value' => 'rejected', 'text' => $this->language->get('text_status_rejected'))
        );

        // خيارات نوع النقل
        $data['transfer_type_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'regular', 'text' => $this->language->get('text_transfer_type_regular')),
            array('value' => 'emergency', 'text' => $this->language->get('text_transfer_type_emergency')),
            array('value' => 'restock', 'text' => $this->language->get('text_transfer_type_restock')),
            array('value' => 'redistribution', 'text' => $this->language->get('text_transfer_type_redistribution')),
            array('value' => 'return', 'text' => $this->language->get('text_transfer_type_return'))
        );

        // خيارات الأولوية
        $data['priority_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'low', 'text' => $this->language->get('text_priority_low')),
            array('value' => 'normal', 'text' => $this->language->get('text_priority_normal')),
            array('value' => 'high', 'text' => $this->language->get('text_priority_high')),
            array('value' => 'urgent', 'text' => $this->language->get('text_priority_urgent'))
        );
    }

    /**
     * عرض نموذج الاستلام
     */
    private function getReceiveForm($transfer_id) {
        $data['transfer_info'] = $this->model_inventory_stock_transfer->getStockTransfer($transfer_id);
        $data['transfer_items'] = $this->model_inventory_stock_transfer->getTransferItems($transfer_id);

        $data['action'] = $this->url->link('inventory/stock_transfer/receive', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $transfer_id, true);
        $data['cancel'] = $this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/stock_transfer_receive', $data));
    }

    /**
     * التحقق من توفر المخزون
     */
    public function checkAvailability() {
        $this->load->model('inventory/stock_transfer');

        $transfer_id = isset($this->request->get['transfer_id']) ? (int)$this->request->get['transfer_id'] : 0;

        if ($transfer_id) {
            $availability = $this->model_inventory_stock_transfer->checkStockAvailability($transfer_id);

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($availability));
        }
    }

    /**
     * عرض تفاصيل النقل
     */
    public function view() {
        $this->load->language('inventory/stock_transfer');
        $this->load->model('inventory/stock_transfer');

        $transfer_id = isset($this->request->get['transfer_id']) ? (int)$this->request->get['transfer_id'] : 0;

        if (!$transfer_id) {
            $this->session->data['error'] = $this->language->get('error_transfer_not_found');
            $this->response->redirect($this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true));
        }

        // الحصول على معلومات النقل
        $transfer_info = $this->model_inventory_stock_transfer->getStockTransfer($transfer_id);
        $transfer_items = $this->model_inventory_stock_transfer->getTransferItems($transfer_id);
        $transfer_history = $this->model_inventory_stock_transfer->getTransferHistory($transfer_id);

        $data['transfer_info'] = $transfer_info;
        $data['transfer_items'] = $transfer_items;
        $data['transfer_history'] = $transfer_history;

        // إعداد الروابط
        $data['edit'] = $this->url->link('inventory/stock_transfer/edit', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $transfer_id, true);
        $data['back'] = $this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/stock_transfer_view', $data));
    }

    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/stock_transfer')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['transfer_name']) < 3) || (utf8_strlen($this->request->post['transfer_name']) > 255)) {
            $this->error['transfer_name'] = $this->language->get('error_transfer_name');
        }

        if (empty($this->request->post['from_branch_id'])) {
            $this->error['from_branch_id'] = $this->language->get('error_from_branch_required');
        }

        if (empty($this->request->post['to_branch_id'])) {
            $this->error['to_branch_id'] = $this->language->get('error_to_branch_required');
        }

        if ($this->request->post['from_branch_id'] == $this->request->post['to_branch_id']) {
            $this->error['to_branch_id'] = $this->language->get('error_same_branch');
        }

        if (empty($this->request->post['request_date'])) {
            $this->error['request_date'] = $this->language->get('error_request_date');
        }

        if (empty($this->request->post['transfer_items']) || !is_array($this->request->post['transfer_items'])) {
            $this->error['transfer_items'] = $this->language->get('error_transfer_items_required');
        } else {
            foreach ($this->request->post['transfer_items'] as $key => $item) {
                if (empty($item['product_id'])) {
                    $this->error['transfer_items'][$key]['product_id'] = $this->language->get('error_product_required');
                }

                if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                    $this->error['transfer_items'][$key]['quantity'] = $this->language->get('error_quantity_required');
                }

                if (empty($item['unit_cost']) || $item['unit_cost'] <= 0) {
                    $this->error['transfer_items'][$key]['unit_cost'] = $this->language->get('error_unit_cost_required');
                }
            }
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/stock_transfer')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * تصدير إلى Excel
     */
    public function exportExcel() {
        $this->load->language('inventory/stock_transfer');
        $this->load->model('inventory/stock_transfer');

        $filter_data = $this->getFilters();
        $results = $this->model_inventory_stock_transfer->exportToExcel($filter_data);

        // إنشاء ملف Excel
        $filename = 'stock_transfers_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        $output = fopen('php://output', 'w');

        // كتابة العناوين
        $headers = array(
            $this->language->get('column_transfer_number'),
            $this->language->get('column_transfer_name'),
            $this->language->get('column_transfer_type'),
            $this->language->get('column_status'),
            $this->language->get('column_priority'),
            $this->language->get('column_from_branch'),
            $this->language->get('column_to_branch'),
            $this->language->get('column_request_date'),
            $this->language->get('column_total_items'),
            $this->language->get('column_total_value'),
            $this->language->get('column_user'),
            $this->language->get('column_notes')
        );

        fputcsv($output, $headers);

        // كتابة البيانات
        foreach ($results as $result) {
            $row = array(
                $result['transfer_number'],
                $result['transfer_name'],
                $result['transfer_type_text'],
                $result['status_text'],
                $result['priority_text'],
                $result['from_branch_name'],
                $result['to_branch_name'],
                $result['request_date'],
                $result['total_items'],
                $result['total_value'],
                $result['user_name'],
                $result['notes']
            );

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * العمليات المجمعة - الاعتماد
     */
    public function bulkApprove() {
        $this->load->language('inventory/stock_transfer');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/stock_transfer')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('inventory/stock_transfer');

            if (isset($this->request->post['transfer_ids']) && is_array($this->request->post['transfer_ids'])) {
                $approved_count = 0;
                $errors = array();

                foreach ($this->request->post['transfer_ids'] as $transfer_id) {
                    try {
                        $transfer = $this->model_inventory_stock_transfer->getStockTransfer($transfer_id);

                        if ($transfer && $transfer['status'] == 'pending_approval') {
                            $this->model_inventory_stock_transfer->approveTransfer($transfer_id);
                            $approved_count++;
                        } else {
                            $errors[] = sprintf('طلب النقل #%s غير قابل للاعتماد', $transfer['transfer_number'] ?? $transfer_id);
                        }
                    } catch (Exception $e) {
                        $errors[] = sprintf('خطأ في طلب النقل #%s: %s', $transfer_id, $e->getMessage());
                    }
                }

                if ($approved_count > 0) {
                    $json['success'] = sprintf('تم اعتماد %d طلب نقل بنجاح', $approved_count);
                }

                if (!empty($errors)) {
                    $json['error'] = implode('<br>', $errors);
                }
            } else {
                $json['error'] = 'لم يتم تحديد أي طلبات نقل';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * العمليات المجمعة - الشحن
     */
    public function bulkShip() {
        $this->load->language('inventory/stock_transfer');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/stock_transfer')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('inventory/stock_transfer');

            if (isset($this->request->post['transfer_ids']) && is_array($this->request->post['transfer_ids'])) {
                $shipped_count = 0;
                $errors = array();

                foreach ($this->request->post['transfer_ids'] as $transfer_id) {
                    try {
                        $transfer = $this->model_inventory_stock_transfer->getStockTransfer($transfer_id);

                        if ($transfer && $transfer['status'] == 'approved') {
                            $this->model_inventory_stock_transfer->shipTransfer($transfer_id);
                            $shipped_count++;
                        } else {
                            $errors[] = sprintf('طلب النقل #%s غير قابل للشحن', $transfer['transfer_number'] ?? $transfer_id);
                        }
                    } catch (Exception $e) {
                        $errors[] = sprintf('خطأ في طلب النقل #%s: %s', $transfer_id, $e->getMessage());
                    }
                }

                if ($shipped_count > 0) {
                    $json['success'] = sprintf('تم شحن %d طلب نقل بنجاح', $shipped_count);
                }

                if (!empty($errors)) {
                    $json['error'] = implode('<br>', $errors);
                }
            } else {
                $json['error'] = 'لم يتم تحديد أي طلبات نقل';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * العمليات المجمعة - الإلغاء
     */
    public function bulkCancel() {
        $this->load->language('inventory/stock_transfer');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/stock_transfer')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('inventory/stock_transfer');

            if (isset($this->request->post['transfer_ids']) && is_array($this->request->post['transfer_ids'])) {
                $cancelled_count = 0;
                $errors = array();

                foreach ($this->request->post['transfer_ids'] as $transfer_id) {
                    try {
                        $transfer = $this->model_inventory_stock_transfer->getStockTransfer($transfer_id);

                        if ($transfer && in_array($transfer['status'], ['draft', 'pending_approval', 'approved'])) {
                            $this->model_inventory_stock_transfer->cancelTransfer($transfer_id);
                            $cancelled_count++;
                        } else {
                            $errors[] = sprintf('طلب النقل #%s غير قابل للإلغاء', $transfer['transfer_number'] ?? $transfer_id);
                        }
                    } catch (Exception $e) {
                        $errors[] = sprintf('خطأ في طلب النقل #%s: %s', $transfer_id, $e->getMessage());
                    }
                }

                if ($cancelled_count > 0) {
                    $json['success'] = sprintf('تم إلغاء %d طلب نقل بنجاح', $cancelled_count);
                }

                if (!empty($errors)) {
                    $json['error'] = implode('<br>', $errors);
                }
            } else {
                $json['error'] = 'لم يتم تحديد أي طلبات نقل';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الطباعة المجمعة
     */
    public function bulkPrint() {
        $this->load->language('inventory/stock_transfer');

        if (!$this->user->hasPermission('access', 'inventory/stock_transfer')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('inventory/stock_transfer', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->load->model('inventory/stock_transfer');

        if (isset($this->request->get['transfer_ids'])) {
            $transfer_ids = explode(',', $this->request->get['transfer_ids']);

            $transfers = array();
            foreach ($transfer_ids as $transfer_id) {
                $transfer = $this->model_inventory_stock_transfer->getStockTransfer($transfer_id);
                if ($transfer) {
                    $transfers[] = $transfer;
                }
            }

            if (!empty($transfers)) {
                // إنشاء PDF للطباعة المجمعة
                require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

                $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
                $pdf->SetCreator('ERP System');
                $pdf->SetAuthor('ERP System');
                $pdf->SetTitle('طلبات النقل المحددة');

                foreach ($transfers as $transfer) {
                    $pdf->AddPage();

                    $html = '<h2 style="text-align: center;">طلب نقل مخزون</h2>';
                    $html .= '<table border="1" cellpadding="5">';
                    $html .= '<tr><td><strong>رقم النقل:</strong></td><td>' . $transfer['transfer_number'] . '</td></tr>';
                    $html .= '<tr><td><strong>اسم النقل:</strong></td><td>' . $transfer['transfer_name'] . '</td></tr>';
                    $html .= '<tr><td><strong>من الفرع:</strong></td><td>' . $transfer['from_branch_name'] . '</td></tr>';
                    $html .= '<tr><td><strong>إلى الفرع:</strong></td><td>' . $transfer['to_branch_name'] . '</td></tr>';
                    $html .= '<tr><td><strong>الحالة:</strong></td><td>' . $transfer['status_text'] . '</td></tr>';
                    $html .= '<tr><td><strong>تاريخ الطلب:</strong></td><td>' . $transfer['request_date'] . '</td></tr>';
                    $html .= '</table>';

                    $pdf->writeHTML($html, true, false, true, false, '');
                }

                $pdf->Output('bulk_transfers_' . date('Y-m-d') . '.pdf', 'I');
            }
        }
    }

    /**
     * تحديث الحالة
     */
    public function updateStatus() {
        $this->load->language('inventory/stock_transfer');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/stock_transfer')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('inventory/stock_transfer');

            if (isset($this->request->post['transfer_id']) && isset($this->request->post['status'])) {
                $transfer_id = $this->request->post['transfer_id'];
                $status = $this->request->post['status'];

                try {
                    $transfer = $this->model_inventory_stock_transfer->getStockTransfer($transfer_id);

                    if ($transfer) {
                        $result = $this->model_inventory_stock_transfer->updateStatus($transfer_id, $status);

                        if ($result) {
                            $json['success'] = 'تم تحديث الحالة بنجاح';
                        } else {
                            $json['error'] = 'فشل في تحديث الحالة';
                        }
                    } else {
                        $json['error'] = 'طلب النقل غير موجود';
                    }
                } catch (Exception $e) {
                    $json['error'] = 'خطأ: ' . $e->getMessage();
                }
            } else {
                $json['error'] = 'بيانات غير مكتملة';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على الفلاتر
     */
    private function getFilters() {
        return array(
            'filter_transfer_number' => $this->request->get['filter_transfer_number'] ?? '',
            'filter_transfer_name' => $this->request->get['filter_transfer_name'] ?? '',
            'filter_status' => $this->request->get['filter_status'] ?? '',
            'filter_transfer_type' => $this->request->get['filter_transfer_type'] ?? '',
            'filter_priority' => $this->request->get['filter_priority'] ?? '',
            'filter_from_branch_id' => $this->request->get['filter_from_branch_id'] ?? '',
            'filter_to_branch_id' => $this->request->get['filter_to_branch_id'] ?? '',
            'filter_date_from' => $this->request->get['filter_date_from'] ?? '',
            'filter_date_to' => $this->request->get['filter_date_to'] ?? '',
            'filter_min_value' => $this->request->get['filter_min_value'] ?? '',
            'filter_max_value' => $this->request->get['filter_max_value'] ?? '',
            'sort' => $this->request->get['sort'] ?? 'st.transfer_id',
            'order' => $this->request->get['order'] ?? 'DESC',
            'start' => ($this->request->get['page'] ?? 1 - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
    }
}
