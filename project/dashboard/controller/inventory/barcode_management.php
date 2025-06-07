<?php
/**
 * إدارة الباركود المتعدد المتطور (Advanced Multi-Barcode Management Controller) - الجزء الثاني
 *
 * الهدف: توفير واجهة متطورة لإدارة الباركود المتعدد مع التكامل الشامل
 * الميزات: إنشاء، تعديل، حذف، طباعة، قراءة، تحليلات، تقارير متقدمة
 * التكامل: مع المنتجات والوحدات والخيارات والمخزون والمبيعات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryBarcodeManagement extends Controller {

    private $error = array();

    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/barcode_management');

        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل النماذج المطلوبة
        $this->load->model('inventory/barcode_management');
        $this->load->model('catalog/product');
        $this->load->model('inventory/unit');

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
            'href' => $this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        // روابط الإجراءات
        $data['add'] = $this->url->link('inventory/barcode_management/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['generate_bulk'] = $this->url->link('inventory/barcode_management/generateBulk', 'user_token=' . $this->session->data['user_token'], true);
        $data['scan_barcode'] = $this->url->link('inventory/barcode_management/scan', 'user_token=' . $this->session->data['user_token'], true);
        $data['print_labels'] = $this->url->link('inventory/barcode_management/printLabels', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_excel'] = $this->url->link('inventory/barcode_management/exportExcel', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['refresh'] = $this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'], true);

        // الحصول على البيانات
        $barcodes = array();
        $filter_data_with_pagination = $filter_data;
        $filter_data_with_pagination['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data_with_pagination['limit'] = $this->config->get('config_limit_admin');

        $results = $this->model_inventory_barcode_management->getProductBarcodes($filter_data_with_pagination);
        $total = $this->model_inventory_barcode_management->getTotalProductBarcodes($filter_data);

        foreach ($results as $result) {
            $barcodes[] = array(
                'barcode_id'          => $result['barcode_id'],
                'product_id'          => $result['product_id'],
                'product_name'        => $result['product_name'],
                'model'               => $result['model'],
                'sku'                 => $result['sku'],
                'barcode_value'       => $result['barcode_value'],
                'barcode_type'        => $result['barcode_type'],
                'barcode_type_text'   => $result['barcode_type_text'],
                'barcode_category'    => $result['barcode_category'],
                'unit_name'           => $result['unit_name'] ? $result['unit_name'] : $this->language->get('text_base_unit'),
                'unit_symbol'         => $result['unit_symbol'],
                'option_name'         => $result['option_name'],
                'option_value_name'   => $result['option_value_name'],
                'is_primary'          => $result['is_primary'],
                'is_primary_text'     => $result['is_primary'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
                'is_active'           => $result['is_active'],
                'is_active_text'      => $result['is_active'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'auto_generated'      => $result['auto_generated'],
                'auto_generated_text' => $result['auto_generated'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
                'scan_count'          => number_format($result['scan_count']),
                'print_count'         => number_format($result['print_count']),
                'today_scans'         => number_format($result['today_scans']),
                'today_prints'        => number_format($result['today_prints']),
                'last_scanned'        => $result['last_scanned'] ? date($this->language->get('datetime_format'), strtotime($result['last_scanned'])) : $this->language->get('text_never'),
                'notes'               => $result['notes'],
                'date_added'          => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
                'status_class'        => $this->getStatusClass($result),
                'category_class'      => $this->getCategoryClass($result['barcode_category']),
                'usage_class'         => $this->getUsageClass($result['scan_count'] + $result['print_count']),
                'edit'                => $this->url->link('inventory/barcode_management/edit', 'user_token=' . $this->session->data['user_token'] . '&barcode_id=' . $result['barcode_id'], true),
                'view'                => $this->url->link('inventory/barcode_management/view', 'user_token=' . $this->session->data['user_token'] . '&barcode_id=' . $result['barcode_id'], true),
                'print'               => $this->url->link('inventory/barcode_management/print', 'user_token=' . $this->session->data['user_token'] . '&barcode_id=' . $result['barcode_id'], true),
                'duplicate'           => $this->url->link('inventory/barcode_management/duplicate', 'user_token=' . $this->session->data['user_token'] . '&barcode_id=' . $result['barcode_id'], true),
                'delete'              => $this->url->link('inventory/barcode_management/delete', 'user_token=' . $this->session->data['user_token'] . '&barcode_id=' . $result['barcode_id'], true)
            );
        }

        $data['barcodes'] = $barcodes;

        // الحصول على إحصائيات الباركود
        $statistics = $this->model_inventory_barcode_management->getBarcodeStatistics($filter_data);
        $data['statistics'] = array(
            'total_barcodes'         => number_format($statistics['total_barcodes']),
            'active_barcodes'        => number_format($statistics['active_barcodes']),
            'primary_barcodes'       => number_format($statistics['primary_barcodes']),
            'auto_generated_barcodes'=> number_format($statistics['auto_generated_barcodes']),
            'total_scans'            => number_format($statistics['total_scans']),
            'total_prints'           => number_format($statistics['total_prints']),
            'avg_scans_per_barcode'  => number_format($statistics['avg_scans_per_barcode'], 1),
            'avg_prints_per_barcode' => number_format($statistics['avg_prints_per_barcode'], 1),
            'products_with_barcodes' => number_format($statistics['products_with_barcodes']),
            'barcode_types_used'     => number_format($statistics['barcode_types_used'])
        );

        // الحصول على إحصائيات حسب النوع
        $data['type_statistics'] = $this->model_inventory_barcode_management->getBarcodeTypeStatistics($filter_data);

        // الحصول على أكثر الباركودات استخداماً
        $data['most_used_barcodes'] = $this->model_inventory_barcode_management->getMostUsedBarcodes(5);

        // إعداد الفلاتر للعرض
        $this->setupFiltersForDisplay($data, $filter_data);

        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

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

        $this->response->setOutput($this->load->view('inventory/barcode_management_list', $data));
    }

    /**
     * إضافة باركود جديد
     */
    public function add() {
        $this->load->language('inventory/barcode_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/barcode_management');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $barcode_id = $this->model_inventory_barcode_management->addProductBarcode($this->request->post);

            if ($barcode_id) {
                $this->session->data['success'] = $this->language->get('text_success');
            } else {
                $this->error['warning'] = $this->language->get('error_barcode_exists');
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

            $this->response->redirect($this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * تعديل باركود
     */
    public function edit() {
        $this->load->language('inventory/barcode_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/barcode_management');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $result = $this->model_inventory_barcode_management->editProductBarcode($this->request->get['barcode_id'], $this->request->post);

            if ($result) {
                $this->session->data['success'] = $this->language->get('text_success');
            } else {
                $this->error['warning'] = $this->language->get('error_barcode_exists');
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

            $this->response->redirect($this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * حذف باركود
     */
    public function delete() {
        $this->load->language('inventory/barcode_management');
        $this->load->model('inventory/barcode_management');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $barcode_id) {
                $this->model_inventory_barcode_management->deleteProductBarcode($barcode_id);
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

            $this->response->redirect($this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * مسح الباركود
     */
    public function scan() {
        $this->load->language('inventory/barcode_management');
        $this->load->model('inventory/barcode_management');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $barcode_value = $this->request->post['barcode_value'];
            $result = $this->model_inventory_barcode_management->findBarcodeByValue($barcode_value);

            if ($result) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array(
                    'success' => true,
                    'data' => $result
                )));
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(array(
                    'success' => false,
                    'error' => $this->language->get('error_barcode_not_found')
                )));
            }
        } else {
            // عرض نموذج المسح
            $data['action'] = $this->url->link('inventory/barcode_management/scan', 'user_token=' . $this->session->data['user_token'], true);
            $data['cancel'] = $this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'], true);

            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('inventory/barcode_scan', $data));
        }
    }

    /**
     * إنشاء باركودات مجمعة
     */
    public function generateBulk() {
        $this->load->language('inventory/barcode_management');
        $this->load->model('inventory/barcode_management');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $product_ids = $this->request->post['product_ids'];
            $barcode_types = $this->request->post['barcode_types'];
            $include_units = !empty($this->request->post['include_units']);
            $include_options = !empty($this->request->post['include_options']);

            $generated_count = 0;

            foreach ($product_ids as $product_id) {
                $generated = $this->model_inventory_barcode_management->generateProductBarcodes(
                    $product_id,
                    $barcode_types,
                    $include_units,
                    $include_options
                );
                $generated_count += count($generated);
            }

            $this->session->data['success'] = sprintf($this->language->get('text_bulk_generated'), $generated_count);

            $this->response->redirect($this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'], true));
        } else {
            // عرض نموذج الإنشاء المجمع
            $this->getBulkGenerateForm();
        }
    }

    /**
     * معالجة الفلاتر
     */
    private function getFilters() {
        $filters = array(
            'filter_product_id'      => '',
            'filter_product_name'    => '',
            'filter_barcode_value'   => '',
            'filter_barcode_type'    => '',
            'filter_unit_id'         => '',
            'filter_option_id'       => '',
            'filter_is_primary'      => '',
            'filter_is_active'       => '',
            'filter_auto_generated'  => '',
            'filter_date_from'       => '',
            'filter_date_to'         => '',
            'sort'                   => 'pd.name',
            'order'                  => 'ASC',
            'page'                   => 1
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
    private function getStatusClass($result) {
        if (!$result['is_active']) {
            return 'danger';
        } elseif ($result['is_primary']) {
            return 'success';
        } else {
            return 'info';
        }
    }

    /**
     * الحصول على فئة CSS للفئة
     */
    private function getCategoryClass($category) {
        switch ($category) {
            case 'أساسي':
                return 'success';
            case 'وحدة':
                return 'info';
            case 'خيار':
                return 'warning';
            default:
                return 'default';
        }
    }

    /**
     * الحصول على فئة CSS للاستخدام
     */
    private function getUsageClass($usage) {
        if ($usage >= 100) {
            return 'success';
        } elseif ($usage >= 50) {
            return 'warning';
        } elseif ($usage >= 10) {
            return 'info';
        } else {
            return 'default';
        }
    }

    /**
     * عرض نموذج الإضافة/التعديل
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['barcode_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

        $this->response->setOutput($this->load->view('inventory/barcode_management_form', $data));
    }

    /**
     * إعداد بيانات النموذج
     */
    private function setupFormData(&$data) {
        // الحصول على البيانات الموجودة أو القيم الافتراضية
        if (isset($this->request->get['barcode_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $barcode_info = $this->model_inventory_barcode_management->getProductBarcode($this->request->get['barcode_id']);
        }

        $fields = array(
            'product_id', 'barcode_value', 'barcode_type', 'unit_id', 'option_id',
            'option_value_id', 'is_primary', 'is_active', 'notes'
        );

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($barcode_info)) {
                $data[$field] = $barcode_info[$field];
            } else {
                $data[$field] = '';
            }
        }

        // الحصول على القوائم
        $this->load->model('catalog/product');
        $this->load->model('inventory/unit');

        $data['products'] = $this->model_catalog_product->getProducts();
        $data['barcode_types'] = $this->model_inventory_barcode_management->getBarcodeTypes();
        $data['units'] = $this->model_inventory_unit->getUnits();

        // الروابط
        $data['action'] = $this->url->link('inventory/barcode_management/' . (!isset($this->request->get['barcode_id']) ? 'add' : 'edit'), 'user_token=' . $this->session->data['user_token'] . (!isset($this->request->get['barcode_id']) ? '' : '&barcode_id=' . $this->request->get['barcode_id']), true);
        $data['cancel'] = $this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'], true);
        $data['generate_barcode'] = $this->url->link('inventory/barcode_management/generateBarcode', 'user_token=' . $this->session->data['user_token'], true);
        $data['validate_barcode'] = $this->url->link('inventory/barcode_management/validateBarcode', 'user_token=' . $this->session->data['user_token'], true);
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
        $this->load->model('catalog/product');
        $this->load->model('inventory/unit');

        $data['products'] = $this->model_catalog_product->getProducts();
        $data['barcode_types'] = $this->model_inventory_barcode_management->getBarcodeTypes();
        $data['units'] = $this->model_inventory_unit->getUnits();

        // خيارات الحالة
        $data['status_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => '1', 'text' => $this->language->get('text_enabled')),
            array('value' => '0', 'text' => $this->language->get('text_disabled'))
        );

        // خيارات الباركود الأساسي
        $data['primary_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => '1', 'text' => $this->language->get('text_yes')),
            array('value' => '0', 'text' => $this->language->get('text_no'))
        );

        // خيارات الإنشاء التلقائي
        $data['auto_generated_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => '1', 'text' => $this->language->get('text_yes')),
            array('value' => '0', 'text' => $this->language->get('text_no'))
        );
    }

    /**
     * عرض نموذج الإنشاء المجمع
     */
    private function getBulkGenerateForm() {
        $data['action'] = $this->url->link('inventory/barcode_management/generateBulk', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'], true);

        // الحصول على المنتجات
        $this->load->model('catalog/product');
        $data['products'] = $this->model_catalog_product->getProducts();

        // أنواع الباركود
        $data['barcode_types'] = $this->model_inventory_barcode_management->getBarcodeTypes();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/barcode_bulk_generate', $data));
    }

    /**
     * توليد باركود تلقائي
     */
    public function generateBarcode() {
        $this->load->model('inventory/barcode_management');

        $product_id = $this->request->get['product_id'];
        $barcode_type = $this->request->get['barcode_type'];
        $unit_id = isset($this->request->get['unit_id']) ? $this->request->get['unit_id'] : null;
        $option_id = isset($this->request->get['option_id']) ? $this->request->get['option_id'] : null;
        $option_value_id = isset($this->request->get['option_value_id']) ? $this->request->get['option_value_id'] : null;

        $barcode_value = $this->model_inventory_barcode_management->generateBarcode(
            $product_id,
            $barcode_type,
            $unit_id,
            $option_id,
            $option_value_id
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array(
            'barcode_value' => $barcode_value
        )));
    }

    /**
     * التحقق من صحة الباركود
     */
    public function validateBarcode() {
        $this->load->model('inventory/barcode_management');

        $barcode_value = $this->request->get['barcode_value'];
        $barcode_type = $this->request->get['barcode_type'];

        $is_valid = $this->model_inventory_barcode_management->validateBarcode($barcode_value, $barcode_type);
        $exists = $this->model_inventory_barcode_management->barcodeExists($barcode_value);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array(
            'is_valid' => $is_valid,
            'exists' => $exists,
            'message' => $is_valid ? ($exists ? $this->language->get('error_barcode_exists') : $this->language->get('text_barcode_valid')) : $this->language->get('error_barcode_invalid')
        )));
    }

    /**
     * عرض تفاصيل الباركود
     */
    public function view() {
        $this->load->language('inventory/barcode_management');
        $this->load->model('inventory/barcode_management');

        $barcode_id = isset($this->request->get['barcode_id']) ? (int)$this->request->get['barcode_id'] : 0;

        if (!$barcode_id) {
            $this->session->data['error'] = $this->language->get('error_barcode_not_found');
            $this->response->redirect($this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'], true));
        }

        // الحصول على معلومات الباركود
        $barcode_info = $this->model_inventory_barcode_management->getProductBarcode($barcode_id);
        $scan_log = $this->model_inventory_barcode_management->getScanLog($barcode_id);
        $print_log = $this->model_inventory_barcode_management->getPrintLog($barcode_id);

        $data['barcode_info'] = $barcode_info;
        $data['scan_log'] = $scan_log;
        $data['print_log'] = $print_log;

        // إعداد الروابط
        $data['edit'] = $this->url->link('inventory/barcode_management/edit', 'user_token=' . $this->session->data['user_token'] . '&barcode_id=' . $barcode_id, true);
        $data['print'] = $this->url->link('inventory/barcode_management/print', 'user_token=' . $this->session->data['user_token'] . '&barcode_id=' . $barcode_id, true);
        $data['back'] = $this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/barcode_management_view', $data));
    }

    /**
     * طباعة الباركود
     */
    public function print() {
        $this->load->model('inventory/barcode_management');

        $barcode_id = isset($this->request->get['barcode_id']) ? (int)$this->request->get['barcode_id'] : 0;
        $quantity = isset($this->request->get['quantity']) ? (int)$this->request->get['quantity'] : 1;

        if ($barcode_id) {
            // تسجيل عملية الطباعة
            $this->model_inventory_barcode_management->logBarcodePrint($barcode_id, 'single', $quantity);

            // الحصول على معلومات الباركود
            $barcode_info = $this->model_inventory_barcode_management->getProductBarcode($barcode_id);

            $data['barcode_info'] = $barcode_info;
            $data['quantity'] = $quantity;

            $this->response->setOutput($this->load->view('inventory/barcode_print', $data));
        }
    }

    /**
     * تصدير إلى Excel
     */
    public function exportExcel() {
        $this->load->language('inventory/barcode_management');
        $this->load->model('inventory/barcode_management');

        $filter_data = $this->getFilters();
        $results = $this->model_inventory_barcode_management->exportToExcel($filter_data);

        // إنشاء ملف Excel
        $filename = 'barcodes_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        $output = fopen('php://output', 'w');

        // كتابة العناوين
        $headers = array(
            $this->language->get('column_product_name'),
            $this->language->get('column_barcode_value'),
            $this->language->get('column_barcode_type'),
            $this->language->get('column_unit'),
            $this->language->get('column_option'),
            $this->language->get('column_is_primary'),
            $this->language->get('column_is_active'),
            $this->language->get('column_scan_count'),
            $this->language->get('column_print_count'),
            $this->language->get('column_date_added')
        );

        fputcsv($output, $headers);

        // كتابة البيانات
        foreach ($results as $result) {
            $row = array(
                $result['product_name'],
                $result['barcode_value'],
                $result['barcode_type_text'],
                $result['unit_name'],
                $result['option_name'] . ($result['option_value_name'] ? ' - ' . $result['option_value_name'] : ''),
                $result['is_primary'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
                $result['is_active'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                $result['scan_count'],
                $result['print_count'],
                $result['date_added']
            );

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/barcode_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['product_id'])) {
            $this->error['product_id'] = $this->language->get('error_product_required');
        }

        if (empty($this->request->post['barcode_value'])) {
            $this->error['barcode_value'] = $this->language->get('error_barcode_value_required');
        } else {
            // التحقق من صحة الباركود
            if (!$this->model_inventory_barcode_management->validateBarcode($this->request->post['barcode_value'], $this->request->post['barcode_type'])) {
                $this->error['barcode_value'] = $this->language->get('error_barcode_invalid');
            }
        }

        if (empty($this->request->post['barcode_type'])) {
            $this->error['barcode_type'] = $this->language->get('error_barcode_type_required');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/barcode_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
