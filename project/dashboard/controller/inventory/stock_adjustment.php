<?php
/**
 * إدارة التسويات المخزنية المتطور (Advanced Stock Adjustment Controller) - الجزء الثاني
 *
 * الهدف: توفير واجهة متطورة لإدارة التسويات المخزنية مع موافقات متعددة المستويات
 * الميزات: تسويات يدوية/تلقائية، workflow متقدم، ربط محاسبي، تنبيهات ذكية
 * التكامل: مع المحاسبة والجرد والموافقات والتقارير والتنبيهات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryStockAdjustment extends Controller {

    private $error = array();

    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/stock_adjustment');

        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل النماذج المطلوبة
        $this->load->model('inventory/stock_adjustment');
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
            'href' => $this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        // روابط الإجراءات
        $data['add'] = $this->url->link('inventory/stock_adjustment/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_excel'] = $this->url->link('inventory/stock_adjustment/exportExcel', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['export_pdf'] = $this->url->link('inventory/stock_adjustment/exportPdf', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['print'] = $this->url->link('inventory/stock_adjustment/print', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['refresh'] = $this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'], true);

        // الحصول على البيانات
        $stock_adjustments = array();
        $filter_data_with_pagination = $filter_data;
        $filter_data_with_pagination['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data_with_pagination['limit'] = $this->config->get('config_limit_admin');

        $results = $this->model_inventory_stock_adjustment->getStockAdjustments($filter_data_with_pagination);
        $total = $this->model_inventory_stock_adjustment->getTotalStockAdjustments($filter_data);

        foreach ($results as $result) {
            $stock_adjustments[] = array(
                'adjustment_id'           => $result['adjustment_id'],
                'adjustment_number'       => $result['adjustment_number'],
                'adjustment_name'         => $result['adjustment_name'],
                'adjustment_type'         => $result['adjustment_type'],
                'adjustment_type_text'    => $result['adjustment_type_text'],
                'status'                  => $result['status'],
                'status_text'             => $result['status_text'],
                'status_class'            => $this->getStatusClass($result['status']),
                'branch_name'             => $result['branch_name'],
                'branch_type'             => $this->language->get('text_branch_type_' . $result['branch_type']),
                'reason_name'             => $result['reason_name'] ? $result['reason_name'] : $this->language->get('text_no_reason'),
                'reason_category'         => $result['reason_category'],
                'reason_category_text'    => $result['reason_category_text'],
                'user_name'               => $result['user_name'],
                'approved_by_name'        => $result['approved_by_name'],
                'adjustment_date'         => date($this->language->get('date_format_short'), strtotime($result['adjustment_date'])),
                'approval_date'           => $result['approval_date'] ? date($this->language->get('date_format_short'), strtotime($result['approval_date'])) : '',
                'total_items'             => number_format($result['total_items']),
                'total_quantity'          => number_format($result['total_quantity'], 2),
                'total_value'             => $this->currency->format($result['total_value'], $this->config->get('config_currency')),
                'total_value_raw'         => $result['total_value'],
                'total_increase_quantity' => number_format($result['total_increase_quantity'], 2),
                'total_decrease_quantity' => number_format($result['total_decrease_quantity'], 2),
                'total_increase_value'    => $this->currency->format($result['total_increase_value'], $this->config->get('config_currency')),
                'total_decrease_value'    => $this->currency->format($result['total_decrease_value'], $this->config->get('config_currency')),
                'value_class'             => $this->getValueClass($result['total_value']),
                'notes'                   => $result['notes'],
                'date_added'              => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
                'can_edit'                => $result['status'] == 'draft',
                'can_approve'             => $result['status'] == 'pending_approval' && $this->model_inventory_stock_adjustment->canApprove($result['adjustment_id'], $this->user->getId()),
                'can_post'                => $result['status'] == 'approved',
                'edit'                    => $this->url->link('inventory/stock_adjustment/edit', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $result['adjustment_id'], true),
                'view'                    => $this->url->link('inventory/stock_adjustment/view', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $result['adjustment_id'], true),
                'approve'                 => $this->url->link('inventory/stock_adjustment/approve', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $result['adjustment_id'], true),
                'reject'                  => $this->url->link('inventory/stock_adjustment/reject', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $result['adjustment_id'], true),
                'post'                    => $this->url->link('inventory/stock_adjustment/post', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $result['adjustment_id'], true),
                'delete'                  => $this->url->link('inventory/stock_adjustment/delete', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $result['adjustment_id'], true)
            );
        }

        $data['stock_adjustments'] = $stock_adjustments;

        // الحصول على ملخص التسويات
        $summary = $this->model_inventory_stock_adjustment->getAdjustmentSummary($filter_data);
        $data['summary'] = array(
            'total_adjustments'       => number_format($summary['total_adjustments']),
            'draft_count'             => number_format($summary['draft_count']),
            'pending_approval_count'  => number_format($summary['pending_approval_count']),
            'approved_count'          => number_format($summary['approved_count']),
            'posted_count'            => number_format($summary['posted_count']),
            'rejected_count'          => number_format($summary['rejected_count']),
            'total_increase_value'    => $this->currency->format($summary['total_increase_value'], $this->config->get('config_currency')),
            'total_decrease_value'    => $this->currency->format($summary['total_decrease_value'], $this->config->get('config_currency')),
            'avg_items_per_adjustment' => number_format($summary['avg_items_per_adjustment'], 1)
        );

        // الحصول على التحليلات
        $data['adjustments_by_reason'] = $this->model_inventory_stock_adjustment->getAdjustmentsByReason($filter_data);
        $data['adjustments_by_branch'] = $this->model_inventory_stock_adjustment->getAdjustmentsByBranch($filter_data);
        $data['top_value_adjustments'] = $this->model_inventory_stock_adjustment->getTopValueAdjustments($filter_data);

        // إعداد الفلاتر للعرض
        $this->setupFiltersForDisplay($data, $filter_data);

        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

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

        $this->response->setOutput($this->load->view('inventory/stock_adjustment_list', $data));
    }

    /**
     * إضافة تسوية جديدة
     */
    public function add() {
        $this->load->language('inventory/stock_adjustment');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stock_adjustment');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $adjustment_id = $this->model_inventory_stock_adjustment->addStockAdjustment($this->request->post);

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

            $this->response->redirect($this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * تعديل تسوية
     */
    public function edit() {
        $this->load->language('inventory/stock_adjustment');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stock_adjustment');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_stock_adjustment->editStockAdjustment($this->request->get['adjustment_id'], $this->request->post);

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

            $this->response->redirect($this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * حذف تسوية
     */
    public function delete() {
        $this->load->language('inventory/stock_adjustment');
        $this->load->model('inventory/stock_adjustment');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $adjustment_id) {
                $this->model_inventory_stock_adjustment->deleteStockAdjustment($adjustment_id);
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

            $this->response->redirect($this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * الموافقة على تسوية
     */
    public function approve() {
        $this->load->language('inventory/stock_adjustment');
        $this->load->model('inventory/stock_adjustment');

        $adjustment_id = isset($this->request->get['adjustment_id']) ? (int)$this->request->get['adjustment_id'] : 0;

        if ($adjustment_id && $this->model_inventory_stock_adjustment->canApprove($adjustment_id, $this->user->getId())) {
            $this->model_inventory_stock_adjustment->changeStatus($adjustment_id, 'approved');
            $this->model_inventory_stock_adjustment->sendAdjustmentNotifications($adjustment_id, 'approved');

            $this->session->data['success'] = $this->language->get('text_approved_success');
        } else {
            $this->session->data['error'] = $this->language->get('error_cannot_approve');
        }

        $this->response->redirect($this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'], true));
    }

    /**
     * رفض تسوية
     */
    public function reject() {
        $this->load->language('inventory/stock_adjustment');
        $this->load->model('inventory/stock_adjustment');

        $adjustment_id = isset($this->request->get['adjustment_id']) ? (int)$this->request->get['adjustment_id'] : 0;
        $rejection_reason = isset($this->request->post['rejection_reason']) ? $this->request->post['rejection_reason'] : '';

        if ($adjustment_id) {
            $this->model_inventory_stock_adjustment->changeStatus($adjustment_id, 'rejected', $rejection_reason);
            $this->model_inventory_stock_adjustment->sendAdjustmentNotifications($adjustment_id, 'rejected');

            $this->session->data['success'] = $this->language->get('text_rejected_success');
        }

        $this->response->redirect($this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'], true));
    }

    /**
     * ترحيل تسوية
     */
    public function post() {
        $this->load->language('inventory/stock_adjustment');
        $this->load->model('inventory/stock_adjustment');

        $adjustment_id = isset($this->request->get['adjustment_id']) ? (int)$this->request->get['adjustment_id'] : 0;

        if ($adjustment_id) {
            $this->model_inventory_stock_adjustment->changeStatus($adjustment_id, 'posted');
            $this->model_inventory_stock_adjustment->sendAdjustmentNotifications($adjustment_id, 'posted');

            $this->session->data['success'] = $this->language->get('text_posted_success');
        }

        $this->response->redirect($this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'], true));
    }

    /**
     * معالجة الفلاتر
     */
    private function getFilters() {
        $filters = array(
            'filter_adjustment_number' => '',
            'filter_adjustment_name'   => '',
            'filter_status'            => '',
            'filter_adjustment_type'   => '',
            'filter_branch_id'         => '',
            'filter_reason_id'         => '',
            'filter_reason_category'   => '',
            'filter_user_id'           => '',
            'filter_date_from'         => '',
            'filter_date_to'           => '',
            'filter_min_value'         => '',
            'filter_max_value'         => '',
            'sort'                     => 'sa.date_added',
            'order'                    => 'DESC',
            'page'                     => 1
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
            case 'posted':
                return 'success';
            case 'rejected':
                return 'danger';
            case 'cancelled':
                return 'default';
            default:
                return 'default';
        }
    }

    /**
     * الحصول على فئة CSS للقيمة
     */
    private function getValueClass($value) {
        if ($value >= 10000) {
            return 'danger';  // قيمة عالية
        } elseif ($value >= 5000) {
            return 'warning'; // قيمة متوسطة
        } elseif ($value >= 1000) {
            return 'info';    // قيمة منخفضة
        } else {
            return 'success'; // قيمة قليلة
        }
    }

    /**
     * عرض نموذج الإضافة/التعديل
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['adjustment_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

        $this->response->setOutput($this->load->view('inventory/stock_adjustment_form', $data));
    }

    /**
     * إعداد بيانات النموذج
     */
    private function setupFormData(&$data) {
        // الحصول على البيانات الموجودة أو القيم الافتراضية
        if (isset($this->request->get['adjustment_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $adjustment_info = $this->model_inventory_stock_adjustment->getStockAdjustment($this->request->get['adjustment_id']);
            $adjustment_items = $this->model_inventory_stock_adjustment->getAdjustmentItems($this->request->get['adjustment_id']);
        }

        $fields = array(
            'adjustment_number', 'adjustment_name', 'adjustment_type', 'branch_id',
            'reason_id', 'reference_type', 'reference_id', 'reference_number',
            'adjustment_date', 'notes'
        );

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($adjustment_info)) {
                $data[$field] = $adjustment_info[$field];
            } else {
                $data[$field] = '';
            }
        }

        // توليد رقم تسوية جديد للإضافة
        if (!isset($this->request->get['adjustment_id'])) {
            $data['adjustment_number'] = $this->model_inventory_stock_adjustment->generateAdjustmentNumber();
            $data['adjustment_date'] = date('Y-m-d');
        }

        // عناصر التسوية
        if (isset($this->request->post['adjustment_items'])) {
            $data['adjustment_items'] = $this->request->post['adjustment_items'];
        } elseif (!empty($adjustment_items)) {
            $data['adjustment_items'] = $adjustment_items;
        } else {
            $data['adjustment_items'] = array();
        }

        // الحصول على القوائم
        $this->load->model('inventory/branch');
        $this->load->model('catalog/product');

        $data['branches'] = $this->model_inventory_branch->getBranches();
        $data['adjustment_reasons'] = $this->model_inventory_stock_adjustment->getAdjustmentReasons();

        // خيارات نوع التسوية
        $data['adjustment_types'] = array(
            array('value' => 'manual', 'text' => $this->language->get('text_adjustment_type_manual')),
            array('value' => 'damage', 'text' => $this->language->get('text_adjustment_type_damage')),
            array('value' => 'loss', 'text' => $this->language->get('text_adjustment_type_loss')),
            array('value' => 'found', 'text' => $this->language->get('text_adjustment_type_found')),
            array('value' => 'expiry', 'text' => $this->language->get('text_adjustment_type_expiry')),
            array('value' => 'system', 'text' => $this->language->get('text_adjustment_type_system'))
        );

        // الروابط
        $data['action'] = $this->url->link('inventory/stock_adjustment/' . (!isset($this->request->get['adjustment_id']) ? 'add' : 'edit'), 'user_token=' . $this->session->data['user_token'] . (!isset($this->request->get['adjustment_id']) ? '' : '&adjustment_id=' . $this->request->get['adjustment_id']), true);
        $data['cancel'] = $this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'], true);
        $data['product_autocomplete'] = $this->url->link('catalog/product/autocomplete', 'user_token=' . $this->session->data['user_token'], true);
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
        $data['adjustment_reasons'] = $this->model_inventory_stock_adjustment->getAdjustmentReasons();
        $data['users'] = $this->model_user_user->getUsers();

        // خيارات الحالة
        $data['status_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'draft', 'text' => $this->language->get('text_status_draft')),
            array('value' => 'pending_approval', 'text' => $this->language->get('text_status_pending_approval')),
            array('value' => 'approved', 'text' => $this->language->get('text_status_approved')),
            array('value' => 'posted', 'text' => $this->language->get('text_status_posted')),
            array('value' => 'rejected', 'text' => $this->language->get('text_status_rejected')),
            array('value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled'))
        );

        // خيارات نوع التسوية
        $data['adjustment_type_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'manual', 'text' => $this->language->get('text_adjustment_type_manual')),
            array('value' => 'counting', 'text' => $this->language->get('text_adjustment_type_counting')),
            array('value' => 'damage', 'text' => $this->language->get('text_adjustment_type_damage')),
            array('value' => 'loss', 'text' => $this->language->get('text_adjustment_type_loss')),
            array('value' => 'found', 'text' => $this->language->get('text_adjustment_type_found')),
            array('value' => 'expiry', 'text' => $this->language->get('text_adjustment_type_expiry')),
            array('value' => 'system', 'text' => $this->language->get('text_adjustment_type_system'))
        );

        // خيارات فئة السبب
        $data['reason_category_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'increase', 'text' => $this->language->get('text_reason_category_increase')),
            array('value' => 'decrease', 'text' => $this->language->get('text_reason_category_decrease')),
            array('value' => 'correction', 'text' => $this->language->get('text_reason_category_correction')),
            array('value' => 'transfer', 'text' => $this->language->get('text_reason_category_transfer'))
        );
    }

    /**
     * عرض تفاصيل التسوية
     */
    public function view() {
        $this->load->language('inventory/stock_adjustment');
        $this->load->model('inventory/stock_adjustment');

        $adjustment_id = isset($this->request->get['adjustment_id']) ? (int)$this->request->get['adjustment_id'] : 0;

        if (!$adjustment_id) {
            $this->session->data['error'] = $this->language->get('error_adjustment_not_found');
            $this->response->redirect($this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'], true));
        }

        // الحصول على معلومات التسوية
        $adjustment_info = $this->model_inventory_stock_adjustment->getStockAdjustment($adjustment_id);
        $adjustment_items = $this->model_inventory_stock_adjustment->getAdjustmentItems($adjustment_id);
        $approval_history = $this->model_inventory_stock_adjustment->getApprovalHistory($adjustment_id);

        $data['adjustment_info'] = $adjustment_info;
        $data['adjustment_items'] = $adjustment_items;
        $data['approval_history'] = $approval_history;

        // إعداد الروابط
        $data['edit'] = $this->url->link('inventory/stock_adjustment/edit', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $adjustment_id, true);
        $data['back'] = $this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/stock_adjustment_view', $data));
    }

    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/stock_adjustment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['adjustment_name']) < 3) || (utf8_strlen($this->request->post['adjustment_name']) > 255)) {
            $this->error['adjustment_name'] = $this->language->get('error_adjustment_name');
        }

        if (empty($this->request->post['branch_id'])) {
            $this->error['branch_id'] = $this->language->get('error_branch_required');
        }

        if (empty($this->request->post['adjustment_date'])) {
            $this->error['adjustment_date'] = $this->language->get('error_adjustment_date');
        }

        if (empty($this->request->post['adjustment_items']) || !is_array($this->request->post['adjustment_items'])) {
            $this->error['adjustment_items'] = $this->language->get('error_adjustment_items_required');
        } else {
            foreach ($this->request->post['adjustment_items'] as $key => $item) {
                if (empty($item['product_id'])) {
                    $this->error['adjustment_items'][$key]['product_id'] = $this->language->get('error_product_required');
                }

                if (!isset($item['quantity']) || $item['quantity'] == 0) {
                    $this->error['adjustment_items'][$key]['quantity'] = $this->language->get('error_quantity_required');
                }

                if (empty($item['unit_cost']) || $item['unit_cost'] <= 0) {
                    $this->error['adjustment_items'][$key]['unit_cost'] = $this->language->get('error_unit_cost_required');
                }
            }
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/stock_adjustment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * تصدير إلى Excel
     */
    public function exportExcel() {
        $this->load->language('inventory/stock_adjustment');
        $this->load->model('inventory/stock_adjustment');

        $filter_data = $this->getFilters();
        $results = $this->model_inventory_stock_adjustment->exportToExcel($filter_data);

        // إنشاء ملف Excel
        $filename = 'stock_adjustments_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        $output = fopen('php://output', 'w');

        // كتابة العناوين
        $headers = array(
            $this->language->get('column_adjustment_number'),
            $this->language->get('column_adjustment_name'),
            $this->language->get('column_adjustment_type'),
            $this->language->get('column_status'),
            $this->language->get('column_branch'),
            $this->language->get('column_reason'),
            $this->language->get('column_adjustment_date'),
            $this->language->get('column_total_items'),
            $this->language->get('column_total_value'),
            $this->language->get('column_user'),
            $this->language->get('column_notes')
        );

        fputcsv($output, $headers);

        // كتابة البيانات
        foreach ($results as $result) {
            $row = array(
                $result['adjustment_number'],
                $result['adjustment_name'],
                $result['adjustment_type_text'],
                $result['status_text'],
                $result['branch_name'],
                $result['reason_name'],
                $result['adjustment_date'],
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
     * تقديم للموافقة
     */
    public function submit() {
        $this->load->language('inventory/stock_adjustment');
        $this->load->model('inventory/stock_adjustment');

        $adjustment_id = isset($this->request->get['adjustment_id']) ? (int)$this->request->get['adjustment_id'] : 0;

        if ($adjustment_id) {
            $this->model_inventory_stock_adjustment->changeStatus($adjustment_id, 'pending_approval');
            $this->model_inventory_stock_adjustment->sendAdjustmentNotifications($adjustment_id, 'submitted');

            $this->session->data['success'] = $this->language->get('text_submitted_success');
        }

        $this->response->redirect($this->url->link('inventory/stock_adjustment', 'user_token=' . $this->session->data['user_token'], true));
    }
}
