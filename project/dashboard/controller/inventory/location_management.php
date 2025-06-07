<?php
/**
 * إدارة المواقع والمناطق المتطورة (Advanced Location Management Controller)
 *
 * الهدف: توفير واجهة شاملة لإدارة مواقع التخزين مع تنظيم هرمي
 * الميزات: خرائط تفاعلية، تتبع GPS، مناطق متعددة المستويات، تكامل مع المخزون
 * التكامل: مع المنتجات والمخزون والحركات والفروع والمستودعات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryLocationManagement extends Controller {

    private $error = array();

    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/location_management');

        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل النماذج المطلوبة
        $this->load->model('inventory/location_management');

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
            'href' => $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        // روابط الإجراءات
        $data['add'] = $this->url->link('inventory/location_management/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['usage_report'] = $this->url->link('inventory/location_management/usageReport', 'user_token=' . $this->session->data['user_token'], true);
        $data['location_map'] = $this->url->link('inventory/location_management/locationMap', 'user_token=' . $this->session->data['user_token'], true);
        $data['barcode_scanner'] = $this->url->link('inventory/location_management/barcodeScanner', 'user_token=' . $this->session->data['user_token'], true);
        $data['refresh'] = $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'], true);

        // الحصول على البيانات
        $locations = array();
        $filter_data_with_pagination = $filter_data;
        $filter_data_with_pagination['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data_with_pagination['limit'] = $this->config->get('config_limit_admin');

        $results = $this->model_inventory_location_management->getLocations($filter_data_with_pagination);
        $total = $this->model_inventory_location_management->getTotalLocations($filter_data);

        foreach ($results as $result) {
            $locations[] = array(
                'location_id'            => $result['location_id'],
                'name'                   => $result['name'],
                'description'            => $result['description'],
                'location_code'          => $result['location_code'],
                'location_type'          => $result['location_type'],
                'location_type_text'     => $this->getLocationTypeText($result['location_type']),
                'parent_location_name'   => $result['parent_location_name'],
                'branch_name'            => $result['branch_name'],
                'warehouse_name'         => $result['warehouse_name'],
                'zone_name'              => $result['zone_name'],
                'full_address'           => $this->buildFullAddress($result),
                'capacity_units'         => number_format($result['capacity_units']),
                'current_units'          => number_format($result['current_units']),
                'usage_percentage'       => number_format($result['usage_percentage'], 1),
                'occupancy_status'       => $result['occupancy_status'],
                'occupancy_status_text'  => $this->getOccupancyStatusText($result['occupancy_status']),
                'occupancy_status_class' => $this->getOccupancyStatusClass($result['occupancy_status']),
                'products_count'         => number_format($result['products_count']),
                'movements_30_days'      => number_format($result['movements_30_days']),
                'sub_locations_count'    => number_format($result['sub_locations_count']),
                'total_quantity'         => number_format($result['total_quantity'] ?: 0),
                'total_value'            => number_format($result['total_value'] ?: 0, 2),
                'is_active'              => $result['is_active'],
                'is_active_text'         => $result['is_active'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'is_active_class'        => $result['is_active'] ? 'success' : 'danger',
                'is_pickable'            => $result['is_pickable'],
                'is_receivable'          => $result['is_receivable'],
                'is_countable'           => $result['is_countable'],
                'priority_level'         => $result['priority_level'],
                'priority_text'          => $this->getPriorityText($result['priority_level']),
                'has_gps'                => !empty($result['gps_latitude']) && !empty($result['gps_longitude']),
                'gps_coordinates'        => $this->formatGPSCoordinates($result['gps_latitude'], $result['gps_longitude']),
                'date_added'             => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'date_modified'          => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
                'edit'                   => $this->url->link('inventory/location_management/edit', 'user_token=' . $this->session->data['user_token'] . '&location_id=' . $result['location_id'], true),
                'view'                   => $this->url->link('inventory/location_management/view', 'user_token=' . $this->session->data['user_token'] . '&location_id=' . $result['location_id'], true),
                'copy'                   => $this->url->link('inventory/location_management/copy', 'user_token=' . $this->session->data['user_token'] . '&location_id=' . $result['location_id'], true),
                'delete'                 => $this->url->link('inventory/location_management/delete', 'user_token=' . $this->session->data['user_token'] . '&location_id=' . $result['location_id'], true),
                'qr_code'                => $this->url->link('inventory/location_management/generateQR', 'user_token=' . $this->session->data['user_token'] . '&location_id=' . $result['location_id'], true),
                'update_quantities'      => $this->url->link('inventory/location_management/updateQuantities', 'user_token=' . $this->session->data['user_token'] . '&location_id=' . $result['location_id'], true)
            );
        }

        $data['locations'] = $locations;

        // الحصول على إحصائيات المواقع
        $statistics = $this->model_inventory_location_management->getLocationStatistics();
        $data['statistics'] = array(
            'total_locations'           => number_format($statistics['total_locations']),
            'active_locations'          => number_format($statistics['active_locations']),
            'parent_locations'          => number_format($statistics['parent_locations']),
            'location_types'            => number_format($statistics['location_types']),
            'branches_with_locations'   => number_format($statistics['branches_with_locations']),
            'warehouses_with_locations' => number_format($statistics['warehouses_with_locations']),
            'products_with_locations'   => number_format($statistics['products_with_locations']),
            'movements_with_locations'  => number_format($statistics['movements_with_locations']),
            'total_capacity_units'      => number_format($statistics['total_capacity_units']),
            'total_current_units'       => number_format($statistics['total_current_units']),
            'overall_usage_percentage'  => number_format($statistics['overall_usage_percentage'], 1),
            'most_used_location'        => $statistics['most_used_location'] ?: $this->language->get('text_none')
        );

        // إعداد الفلاتر للعرض
        $this->setupFiltersForDisplay($data, $filter_data);

        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

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

        $this->response->setOutput($this->load->view('inventory/location_management_list', $data));
    }

    /**
     * إضافة موقع جديد
     */
    public function add() {
        $this->load->language('inventory/location_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/location_management');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $location_id = $this->model_inventory_location_management->addLocation($this->request->post);

            if ($location_id) {
                $this->session->data['success'] = $this->language->get('text_success');

                $url = $this->buildUrl($this->getFilters());
                $this->response->redirect($this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
            } else {
                $this->error['warning'] = $this->language->get('error_location_code_exists');
            }
        }

        $this->getForm();
    }

    /**
     * تعديل موقع
     */
    public function edit() {
        $this->load->language('inventory/location_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/location_management');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $result = $this->model_inventory_location_management->editLocation($this->request->get['location_id'], $this->request->post);

            if ($result) {
                $this->session->data['success'] = $this->language->get('text_success');

                $url = $this->buildUrl($this->getFilters());
                $this->response->redirect($this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
            } else {
                $this->error['warning'] = $this->language->get('error_location_code_exists');
            }
        }

        $this->getForm();
    }

    /**
     * حذف موقع
     */
    public function delete() {
        $this->load->language('inventory/location_management');
        $this->load->model('inventory/location_management');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $location_id) {
                $result = $this->model_inventory_location_management->deleteLocation($location_id);

                if (isset($result['error'])) {
                    $this->error['warning'] = $result['error'];
                    break;
                }
            }

            if (!$this->error) {
                $this->session->data['success'] = $this->language->get('text_success');
            }

            $url = $this->buildUrl($this->getFilters());
            $this->response->redirect($this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * خريطة المواقع
     */
    public function locationMap() {
        $this->load->language('inventory/location_management');
        $this->load->model('inventory/location_management');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_location_map'),
            'href' => $this->url->link('inventory/location_management/locationMap', 'user_token=' . $this->session->data['user_token'], true)
        );

        // الحصول على المواقع مع إحداثيات GPS
        $locations_with_gps = $this->model_inventory_location_management->getLocations(array(
            'filter_has_gps' => true
        ));

        $data['locations'] = $locations_with_gps;
        $data['back'] = $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/location_map', $data));
    }

    /**
     * ماسح الباركود
     */
    public function barcodeScanner() {
        $this->load->language('inventory/location_management');
        $this->load->model('inventory/location_management');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_barcode_scanner'),
            'href' => $this->url->link('inventory/location_management/barcodeScanner', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['search_url'] = $this->url->link('inventory/location_management/findByCode', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/location_barcode_scanner', $data));
    }

    /**
     * البحث بالباركود عبر AJAX
     */
    public function findByCode() {
        $this->load->model('inventory/location_management');

        $code = $this->request->get['code'] ?? '';

        if ($code) {
            $location = $this->model_inventory_location_management->findLocationByCode($code);

            if ($location) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array(
                    'success' => true,
                    'location' => $location,
                    'view_url' => $this->url->link('inventory/location_management/view', 'user_token=' . $this->session->data['user_token'] . '&location_id=' . $location['location_id'], true)
                )));
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array(
                    'success' => false,
                    'error' => 'الموقع غير موجود'
                )));
            }
        } else {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array(
                'success' => false,
                'error' => 'يرجى إدخال الكود'
            )));
        }
    }

    /**
     * معالجة الفلاتر
     */
    private function getFilters() {
        $filters = array(
            'filter_name'              => '',
            'filter_location_code'     => '',
            'filter_location_type'     => '',
            'filter_branch_id'         => '',
            'filter_warehouse_id'      => '',
            'filter_zone_id'           => '',
            'filter_parent_location_id' => '',
            'filter_is_active'         => '',
            'filter_is_pickable'       => '',
            'filter_occupancy_status'  => '',
            'sort'                     => 'ld.name',
            'order'                    => 'ASC',
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
     * الحصول على نص نوع الموقع
     */
    private function getLocationTypeText($location_type) {
        $location_types = $this->model_inventory_location_management->getLocationTypes();
        return isset($location_types[$location_type]) ? $location_types[$location_type] : $location_type;
    }

    /**
     * بناء العنوان الكامل للموقع
     */
    private function buildFullAddress($location) {
        $address_parts = array();

        if (!empty($location['aisle'])) {
            $address_parts[] = 'ممر: ' . $location['aisle'];
        }

        if (!empty($location['rack'])) {
            $address_parts[] = 'رف: ' . $location['rack'];
        }

        if (!empty($location['shelf'])) {
            $address_parts[] = 'رفة: ' . $location['shelf'];
        }

        if (!empty($location['bin'])) {
            $address_parts[] = 'صندوق: ' . $location['bin'];
        }

        if (!empty($location['zone_name'])) {
            $address_parts[] = 'منطقة: ' . $location['zone_name'];
        }

        if (!empty($location['warehouse_name'])) {
            $address_parts[] = 'مستودع: ' . $location['warehouse_name'];
        }

        if (!empty($location['branch_name'])) {
            $address_parts[] = 'فرع: ' . $location['branch_name'];
        }

        return implode(' | ', $address_parts);
    }

    /**
     * الحصول على نص حالة الإشغال
     */
    private function getOccupancyStatusText($status) {
        $statuses = array(
            'empty'  => 'فارغ',
            'low'    => 'منخفض',
            'medium' => 'متوسط',
            'high'   => 'عالي',
            'full'   => 'ممتلئ'
        );

        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }

    /**
     * الحصول على فئة CSS لحالة الإشغال
     */
    private function getOccupancyStatusClass($status) {
        $classes = array(
            'empty'  => 'default',
            'low'    => 'success',
            'medium' => 'info',
            'high'   => 'warning',
            'full'   => 'danger'
        );

        return isset($classes[$status]) ? $classes[$status] : 'default';
    }

    /**
     * الحصول على نص الأولوية
     */
    private function getPriorityText($priority) {
        $priorities = array(
            1 => 'منخفضة',
            2 => 'عادية',
            3 => 'عالية',
            4 => 'حرجة',
            5 => 'طارئة'
        );

        return isset($priorities[$priority]) ? $priorities[$priority] : 'عادية';
    }

    /**
     * تنسيق إحداثيات GPS
     */
    private function formatGPSCoordinates($latitude, $longitude) {
        if (empty($latitude) || empty($longitude)) {
            return '';
        }

        return number_format($latitude, 6) . ', ' . number_format($longitude, 6);
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
        $data['location_types'] = $this->model_inventory_location_management->getLocationTypes();
        $data['branches'] = $this->model_inventory_location_management->getBranches();
        $data['warehouses'] = $this->model_inventory_location_management->getWarehouses();
        $data['zones'] = $this->model_inventory_location_management->getZones();
        $data['parent_locations'] = $this->model_inventory_location_management->getParentLocations();

        // خيارات الحالة
        $data['status_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => '1', 'text' => $this->language->get('text_enabled')),
            array('value' => '0', 'text' => $this->language->get('text_disabled'))
        );

        // خيارات حالة الإشغال
        $data['occupancy_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'empty', 'text' => 'فارغ'),
            array('value' => 'low', 'text' => 'منخفض'),
            array('value' => 'medium', 'text' => 'متوسط'),
            array('value' => 'high', 'text' => 'عالي'),
            array('value' => 'full', 'text' => 'ممتلئ')
        );
    }

    /**
     * عرض نموذج الإضافة/التعديل
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['location_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

        $this->response->setOutput($this->load->view('inventory/location_management_form', $data));
    }

    /**
     * إعداد بيانات النموذج
     */
    private function setupFormData(&$data) {
        // الحصول على البيانات الموجودة أو القيم الافتراضية
        if (isset($this->request->get['location_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $location_info = $this->model_inventory_location_management->getLocation($this->request->get['location_id']);
        }

        // الحقول الأساسية
        $basic_fields = array(
            'location_code', 'location_type', 'parent_location_id', 'branch_id', 'warehouse_id', 'zone_id',
            'aisle', 'rack', 'shelf', 'bin', 'barcode', 'qr_code',
            'capacity_weight', 'capacity_volume', 'capacity_units',
            'current_weight', 'current_volume', 'current_units',
            'temperature_min', 'temperature_max', 'humidity_min', 'humidity_max',
            'is_active', 'is_pickable', 'is_receivable', 'is_countable', 'priority_level',
            'gps_latitude', 'gps_longitude', 'sort_order'
        );

        foreach ($basic_fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($location_info)) {
                $data[$field] = $location_info[$field];
            } else {
                $data[$field] = '';
            }
        }

        // وصف الموقع
        if (isset($this->request->post['location_description'])) {
            $data['location_description'] = $this->request->post['location_description'];
        } elseif (!empty($location_info)) {
            $data['location_description'][1] = array(
                'name' => $location_info['name'],
                'description' => $location_info['description']
            );
        } else {
            $data['location_description'] = array();
        }

        // الحصول على القوائم
        $data['location_types'] = $this->model_inventory_location_management->getLocationTypes();
        $data['branches'] = $this->model_inventory_location_management->getBranches();
        $data['warehouses'] = $this->model_inventory_location_management->getWarehouses();
        $data['zones'] = $this->model_inventory_location_management->getZones();
        $data['parent_locations'] = $this->model_inventory_location_management->getParentLocations();

        // الروابط
        $data['action'] = $this->url->link('inventory/location_management/' . (!isset($this->request->get['location_id']) ? 'add' : 'edit'), 'user_token=' . $this->session->data['user_token'] . (!isset($this->request->get['location_id']) ? '' : '&location_id=' . $this->request->get['location_id']), true);
        $data['cancel'] = $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'], true);
        $data['generate_qr'] = $this->url->link('inventory/location_management/generateQR', 'user_token=' . $this->session->data['user_token'], true);
    }

    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/location_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['location_description'][1]['name'])) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['location_type'])) {
            $this->error['location_type'] = $this->language->get('error_location_type');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/location_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * تحديث كميات الموقع
     */
    public function updateQuantities() {
        $this->load->model('inventory/location_management');

        $location_id = isset($this->request->get['location_id']) ? (int)$this->request->get['location_id'] : 0;

        if ($location_id) {
            $this->model_inventory_location_management->updateLocationQuantities($location_id);

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array(
                'success' => true,
                'message' => 'تم تحديث الكميات بنجاح'
            )));
        } else {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array(
                'success' => false,
                'error' => 'معرف الموقع غير صحيح'
            )));
        }
    }

    /**
     * إنشاء QR Code
     */
    public function generateQR() {
        $this->load->model('inventory/location_management');

        $location_id = isset($this->request->get['location_id']) ? (int)$this->request->get['location_id'] : 0;

        if ($location_id) {
            $location = $this->model_inventory_location_management->getLocation($location_id);

            if ($location) {
                // إنشاء QR Code (هنا يمكن استخدام مكتبة QR Code)
                $qr_data = json_encode(array(
                    'type' => 'location',
                    'id' => $location_id,
                    'code' => $location['location_code'],
                    'name' => $location['name']
                ));

                // إرجاع بيانات QR Code
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array(
                    'success' => true,
                    'qr_data' => $qr_data,
                    'qr_code' => $location['qr_code']
                )));
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array(
                    'success' => false,
                    'error' => 'الموقع غير موجود'
                )));
            }
        } else {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array(
                'success' => false,
                'error' => 'معرف الموقع غير صحيح'
            )));
        }
    }

    /**
     * تقرير استخدام المواقع
     */
    public function usageReport() {
        $this->load->language('inventory/location_management');
        $this->load->model('inventory/location_management');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_usage_report'),
            'href' => $this->url->link('inventory/location_management/usageReport', 'user_token=' . $this->session->data['user_token'], true)
        );

        // الحصول على تقرير الاستخدام
        $data['usage_report'] = $this->model_inventory_location_management->getLocationUsageReport(50);

        $data['back'] = $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/location_usage_report', $data));
    }
}
