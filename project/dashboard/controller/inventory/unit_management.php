<?php
/**
 * إدارة الوحدات والتحويلات المتطورة (Advanced Unit Management Controller)
 *
 * الهدف: توفير واجهة شاملة لإدارة وحدات القياس مع تحويلات تلقائية
 * الميزات: تحويلات متعددة المستويات، تسعير مختلف للوحدات، ربط بالمنتجات والباركود
 * التكامل: مع المنتجات والباركود والمخزون والمبيعات والمشتريات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryUnitManagement extends Controller {

    private $error = array();

    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/unit_management');

        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل النماذج المطلوبة
        $this->load->model('inventory/unit_management');

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
            'href' => $this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        // روابط الإجراءات
        $data['add'] = $this->url->link('inventory/unit_management/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['create_defaults'] = $this->url->link('inventory/unit_management/createDefaults', 'user_token=' . $this->session->data['user_token'], true);
        $data['usage_report'] = $this->url->link('inventory/unit_management/usageReport', 'user_token=' . $this->session->data['user_token'], true);
        $data['conversion_calculator'] = $this->url->link('inventory/unit_management/conversionCalculator', 'user_token=' . $this->session->data['user_token'], true);
        $data['refresh'] = $this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true);

        // الحصول على البيانات
        $units = array();
        $filter_data_with_pagination = $filter_data;
        $filter_data_with_pagination['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data_with_pagination['limit'] = $this->config->get('config_limit_admin');

        $results = $this->model_inventory_unit_management->getUnits($filter_data_with_pagination);
        $total = $this->model_inventory_unit_management->getTotalUnits($filter_data);

        foreach ($results as $result) {
            $units[] = array(
                'unit_id'                => $result['unit_id'],
                'name'                   => $result['name'],
                'symbol'                 => $result['symbol'],
                'description'            => $result['description'],
                'unit_type'              => $result['unit_type'],
                'unit_type_text'         => $this->getUnitTypeText($result['unit_type']),
                'base_unit_name'         => $result['base_unit_name'],
                'base_unit_symbol'       => $result['base_unit_symbol'],
                'conversion_factor'      => number_format($result['conversion_factor'], 4),
                'total_conversion_factor' => number_format($result['total_conversion_factor'], 6),
                'is_base_unit'           => $result['is_base_unit'],
                'is_base_unit_text'      => $result['is_base_unit'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
                'is_active'              => $result['is_active'],
                'is_active_text'         => $result['is_active'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'is_active_class'        => $result['is_active'] ? 'success' : 'danger',
                'products_count'         => number_format($result['products_count']),
                'barcodes_count'         => number_format($result['barcodes_count']),
                'movements_30_days'      => number_format($result['movements_30_days']),
                'pricing_levels'         => number_format($result['pricing_levels']),
                'sub_units_count'        => number_format($result['sub_units_count']),
                'sort_order'             => $result['sort_order'],
                'date_added'             => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'date_modified'          => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
                'edit'                   => $this->url->link('inventory/unit_management/edit', 'user_token=' . $this->session->data['user_token'] . '&unit_id=' . $result['unit_id'], true),
                'view'                   => $this->url->link('inventory/unit_management/view', 'user_token=' . $this->session->data['user_token'] . '&unit_id=' . $result['unit_id'], true),
                'copy'                   => $this->url->link('inventory/unit_management/copy', 'user_token=' . $this->session->data['user_token'] . '&unit_id=' . $result['unit_id'], true),
                'delete'                 => $this->url->link('inventory/unit_management/delete', 'user_token=' . $this->session->data['user_token'] . '&unit_id=' . $result['unit_id'], true),
                'conversion_table'       => $this->url->link('inventory/unit_management/conversionTable', 'user_token=' . $this->session->data['user_token'] . '&unit_id=' . $result['unit_id'], true)
            );
        }

        $data['units'] = $units;

        // الحصول على إحصائيات الوحدات
        $statistics = $this->model_inventory_unit_management->getUnitStatistics();
        $data['statistics'] = array(
            'total_units'         => number_format($statistics['total_units']),
            'active_units'        => number_format($statistics['active_units']),
            'base_units'          => number_format($statistics['base_units']),
            'unit_types'          => number_format($statistics['unit_types']),
            'products_with_units' => number_format($statistics['products_with_units']),
            'barcodes_with_units' => number_format($statistics['barcodes_with_units']),
            'movements_with_units' => number_format($statistics['movements_with_units']),
            'most_used_unit'      => $statistics['most_used_unit'] ?: $this->language->get('text_none')
        );

        // إعداد الفلاتر للعرض
        $this->setupFiltersForDisplay($data, $filter_data);

        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

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

        $this->response->setOutput($this->load->view('inventory/unit_management_list', $data));
    }

    /**
     * إضافة وحدة جديدة
     */
    public function add() {
        $this->load->language('inventory/unit_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/unit_management');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $unit_id = $this->model_inventory_unit_management->addUnit($this->request->post);

            if ($unit_id) {
                $this->session->data['success'] = $this->language->get('text_success');

                $url = $this->buildUrl($this->getFilters());
                $this->response->redirect($this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
            } else {
                $this->error['warning'] = $this->language->get('error_symbol_exists');
            }
        }

        $this->getForm();
    }

    /**
     * تعديل وحدة
     */
    public function edit() {
        $this->load->language('inventory/unit_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/unit_management');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $result = $this->model_inventory_unit_management->editUnit($this->request->get['unit_id'], $this->request->post);

            if ($result) {
                $this->session->data['success'] = $this->language->get('text_success');

                $url = $this->buildUrl($this->getFilters());
                $this->response->redirect($this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
            } else {
                $this->error['warning'] = $this->language->get('error_symbol_exists');
            }
        }

        $this->getForm();
    }

    /**
     * حذف وحدة
     */
    public function delete() {
        $this->load->language('inventory/unit_management');
        $this->load->model('inventory/unit_management');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $unit_id) {
                $result = $this->model_inventory_unit_management->deleteUnit($unit_id);

                if (isset($result['error'])) {
                    $this->error['warning'] = $result['error'];
                    break;
                }
            }

            if (!$this->error) {
                $this->session->data['success'] = $this->language->get('text_success');
            }

            $url = $this->buildUrl($this->getFilters());
            $this->response->redirect($this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * إنشاء الوحدات الافتراضية
     */
    public function createDefaults() {
        $this->load->language('inventory/unit_management');
        $this->load->model('inventory/unit_management');

        if ($this->validatePermission()) {
            $this->model_inventory_unit_management->createDefaultUnits();
            $this->session->data['success'] = $this->language->get('text_defaults_created');
        }

        $this->response->redirect($this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true));
    }

    /**
     * حاسبة التحويل
     */
    public function conversionCalculator() {
        $this->load->language('inventory/unit_management');
        $this->load->model('inventory/unit_management');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_conversion_calculator'),
            'href' => $this->url->link('inventory/unit_management/conversionCalculator', 'user_token=' . $this->session->data['user_token'], true)
        );

        // الحصول على جميع الوحدات النشطة
        $data['units'] = $this->model_inventory_unit_management->getUnits(array('filter_is_active' => 1));
        $data['unit_types'] = $this->model_inventory_unit_management->getUnitTypes();

        $data['calculate_url'] = $this->url->link('inventory/unit_management/calculateConversion', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/unit_conversion_calculator', $data));
    }

    /**
     * حساب التحويل عبر AJAX
     */
    public function calculateConversion() {
        $this->load->model('inventory/unit_management');

        $quantity = (float)$this->request->get['quantity'];
        $from_unit_id = (int)$this->request->get['from_unit_id'];
        $to_unit_id = (int)$this->request->get['to_unit_id'];

        $result = $this->model_inventory_unit_management->convertQuantity($quantity, $from_unit_id, $to_unit_id);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array(
            'result' => $result,
            'formatted_result' => number_format($result, 6)
        )));
    }

    /**
     * معالجة الفلاتر
     */
    private function getFilters() {
        $filters = array(
            'filter_name'         => '',
            'filter_symbol'       => '',
            'filter_unit_type'    => '',
            'filter_base_unit_id' => '',
            'filter_is_base_unit' => '',
            'filter_is_active'    => '',
            'sort'                => 'ud.name',
            'order'               => 'ASC',
            'page'                => 1
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
     * الحصول على نص نوع الوحدة
     */
    private function getUnitTypeText($unit_type) {
        $unit_types = $this->model_inventory_unit_management->getUnitTypes();
        return isset($unit_types[$unit_type]) ? $unit_types[$unit_type] : $unit_type;
    }

    /**
     * عرض نموذج الإضافة/التعديل
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['unit_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

        $this->response->setOutput($this->load->view('inventory/unit_management_form', $data));
    }

    /**
     * إعداد بيانات النموذج
     */
    private function setupFormData(&$data) {
        // الحصول على البيانات الموجودة أو القيم الافتراضية
        if (isset($this->request->get['unit_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $unit_info = $this->model_inventory_unit_management->getUnit($this->request->get['unit_id']);
        }

        // الحقول الأساسية
        $basic_fields = array(
            'unit_type', 'base_unit_id', 'conversion_factor', 'is_base_unit',
            'is_active', 'sort_order'
        );

        foreach ($basic_fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($unit_info)) {
                $data[$field] = $unit_info[$field];
            } else {
                $data[$field] = '';
            }
        }

        // وصف الوحدة
        if (isset($this->request->post['unit_description'])) {
            $data['unit_description'] = $this->request->post['unit_description'];
        } elseif (!empty($unit_info)) {
            $data['unit_description'][1] = array(
                'name' => $unit_info['name'],
                'symbol' => $unit_info['symbol'],
                'description' => $unit_info['description']
            );
        } else {
            $data['unit_description'] = array();
        }

        // الحصول على القوائم
        $data['unit_types'] = $this->model_inventory_unit_management->getUnitTypes();
        $data['base_units'] = $this->model_inventory_unit_management->getBaseUnits();

        // الروابط
        $data['action'] = $this->url->link('inventory/unit_management/' . (!isset($this->request->get['unit_id']) ? 'add' : 'edit'), 'user_token=' . $this->session->data['user_token'] . (!isset($this->request->get['unit_id']) ? '' : '&unit_id=' . $this->request->get['unit_id']), true);
        $data['cancel'] = $this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true);
        $data['calculate_conversion'] = $this->url->link('inventory/unit_management/calculateConversion', 'user_token=' . $this->session->data['user_token'], true);
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
        $data['unit_types'] = $this->model_inventory_unit_management->getUnitTypes();
        $data['base_units'] = $this->model_inventory_unit_management->getBaseUnits();

        // خيارات الحالة
        $data['status_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => '1', 'text' => $this->language->get('text_enabled')),
            array('value' => '0', 'text' => $this->language->get('text_disabled'))
        );

        // خيارات الوحدة الأساسية
        $data['base_unit_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => '1', 'text' => $this->language->get('text_yes')),
            array('value' => '0', 'text' => $this->language->get('text_no'))
        );
    }

    /**
     * عرض تفاصيل الوحدة
     */
    public function view() {
        $this->load->language('inventory/unit_management');
        $this->load->model('inventory/unit_management');

        $unit_id = isset($this->request->get['unit_id']) ? (int)$this->request->get['unit_id'] : 0;

        if (!$unit_id) {
            $this->session->data['error'] = $this->language->get('error_unit_not_found');
            $this->response->redirect($this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true));
        }

        // الحصول على معلومات الوحدة
        $unit_info = $this->model_inventory_unit_management->getUnit($unit_id);
        $data['unit_info'] = $unit_info;

        // الحصول على الوحدات الفرعية
        $data['sub_units'] = $this->model_inventory_unit_management->getSubUnits($unit_id);

        // الحصول على جدول التحويلات
        $data['conversion_table'] = $this->model_inventory_unit_management->getConversionTable($unit_id);

        // إعداد الروابط
        $data['edit'] = $this->url->link('inventory/unit_management/edit', 'user_token=' . $this->session->data['user_token'] . '&unit_id=' . $unit_id, true);
        $data['copy'] = $this->url->link('inventory/unit_management/copy', 'user_token=' . $this->session->data['user_token'] . '&unit_id=' . $unit_id, true);
        $data['back'] = $this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/unit_management_view', $data));
    }

    /**
     * تقرير استخدام الوحدات
     */
    public function usageReport() {
        $this->load->language('inventory/unit_management');
        $this->load->model('inventory/unit_management');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_usage_report'),
            'href' => $this->url->link('inventory/unit_management/usageReport', 'user_token=' . $this->session->data['user_token'], true)
        );

        // الحصول على تقرير الاستخدام
        $data['usage_report'] = $this->model_inventory_unit_management->getUnitUsageReport(50);

        $data['back'] = $this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/unit_usage_report', $data));
    }

    /**
     * جدول التحويلات لوحدة معينة
     */
    public function conversionTable() {
        $this->load->language('inventory/unit_management');
        $this->load->model('inventory/unit_management');

        $unit_id = isset($this->request->get['unit_id']) ? (int)$this->request->get['unit_id'] : 0;

        if (!$unit_id) {
            $this->response->redirect($this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true));
        }

        $unit_info = $this->model_inventory_unit_management->getUnit($unit_id);
        $conversion_table = $this->model_inventory_unit_management->getConversionTable($unit_id);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array(
            'unit_info' => $unit_info,
            'conversion_table' => $conversion_table
        )));
    }

    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/unit_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['unit_description'][1]['name'])) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['unit_description'][1]['symbol'])) {
            $this->error['symbol'] = $this->language->get('error_symbol');
        }

        if (empty($this->request->post['unit_type'])) {
            $this->error['unit_type'] = $this->language->get('error_unit_type');
        }

        if (!isset($this->request->post['conversion_factor']) || $this->request->post['conversion_factor'] <= 0) {
            $this->error['conversion_factor'] = $this->language->get('error_conversion_factor');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/unit_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * التحقق من الصلاحيات
     */
    protected function validatePermission() {
        if (!$this->user->hasPermission('modify', 'inventory/unit_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
            return false;
        }

        return true;
    }
}
