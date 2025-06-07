<?php
/**
 * إدارة المنتجات المتطورة (Advanced Product Management Controller) - الجزء الثاني
 *
 * الهدف: توفير واجهة متطورة لإدارة المنتجات مع التكامل الشامل
 * الميزات: ترميز ذكي، 5 مستويات تسعير، خيارات مرتبطة بالوحدات، باركود متعدد
 * التكامل: مع المخزون والوحدات والباركود والتسعير والخيارات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryProductManagement extends Controller {

    private $error = array();

    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/product_management');

        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل النماذج المطلوبة
        $this->load->model('inventory/product_management');
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/category');

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
            'href' => $this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        // روابط الإجراءات
        $data['add'] = $this->url->link('inventory/product_management/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['import'] = $this->url->link('inventory/product_management/import', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_excel'] = $this->url->link('inventory/product_management/exportExcel', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['bulk_update'] = $this->url->link('inventory/product_management/bulkUpdate', 'user_token=' . $this->session->data['user_token'], true);
        $data['refresh'] = $this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'], true);

        // الحصول على البيانات
        $products = array();
        $filter_data_with_pagination = $filter_data;
        $filter_data_with_pagination['start'] = ($filter_data['page'] - 1) * $this->config->get('config_limit_admin');
        $filter_data_with_pagination['limit'] = $this->config->get('config_limit_admin');

        $results = $this->model_inventory_product_management->getProducts($filter_data_with_pagination);
        $total = $this->model_inventory_product_management->getTotalProducts($filter_data);

        foreach ($results as $result) {
            $products[] = array(
                'product_id'              => $result['product_id'],
                'name'                    => $result['name'],
                'model'                   => $result['model'],
                'sku'                     => $result['sku'],
                'image'                   => $result['image'],
                'manufacturer'            => $result['manufacturer'],
                'price'                   => number_format($result['price'], 2),
                'basic_price'             => number_format($result['basic_price'] ?: $result['price'], 2),
                'offer_price'             => number_format($result['offer_price'], 2),
                'wholesale_price'         => number_format($result['wholesale_price'], 2),
                'available_quantity'      => number_format($result['available_quantity']),
                'reorder_level'           => number_format($result['reorder_level']),
                'max_stock_level'         => number_format($result['max_stock_level']),
                'inventory_value'         => number_format($result['inventory_value'], 2),
                'barcode_count'           => $result['barcode_count'],
                'sales_30_days'           => number_format($result['sales_30_days']),
                'last_movement_date'      => $result['last_movement_date'] ? date($this->language->get('date_format_short'), strtotime($result['last_movement_date'])) : $this->language->get('text_never'),
                'computed_stock_status'   => $result['computed_stock_status'],
                'stock_status_text'       => $this->getStockStatusText($result['computed_stock_status']),
                'stock_status_class'      => $this->getStockStatusClass($result['computed_stock_status']),
                'status'                  => $result['status'],
                'status_text'             => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'status_class'            => $result['status'] ? 'success' : 'danger',
                'date_added'              => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'date_modified'           => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
                'edit'                    => $this->url->link('inventory/product_management/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true),
                'view'                    => $this->url->link('inventory/product_management/view', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true),
                'copy'                    => $this->url->link('inventory/product_management/copy', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true),
                'delete'                  => $this->url->link('inventory/product_management/delete', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true),
                'manage_barcodes'         => $this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'] . '&filter_product_id=' . $result['product_id'], true),
                'stock_movements'         => $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'] . '&filter_product_id=' . $result['product_id'], true)
            );
        }

        $data['products'] = $products;

        // الحصول على إحصائيات المنتجات
        $statistics = $this->model_inventory_product_management->getProductStatistics($filter_data);
        $data['statistics'] = array(
            'total_products'         => number_format($statistics['total_products']),
            'active_products'        => number_format($statistics['active_products']),
            'inactive_products'      => number_format($statistics['inactive_products']),
            'out_of_stock_products'  => number_format($statistics['out_of_stock_products']),
            'low_stock_products'     => number_format($statistics['low_stock_products']),
            'overstock_products'     => number_format($statistics['overstock_products']),
            'total_inventory_value'  => number_format($statistics['total_inventory_value'], 2),
            'avg_selling_price'      => number_format($statistics['avg_selling_price'], 2),
            'avg_cost_price'         => number_format($statistics['avg_cost_price'], 2),
            'total_quantity'         => number_format($statistics['total_quantity']),
            'total_manufacturers'    => number_format($statistics['total_manufacturers']),
            'total_categories'       => number_format($statistics['total_categories'])
        );

        // الحصول على أفضل المنتجات مبيعاً
        $data['top_selling_products'] = $this->model_inventory_product_management->getTopSellingProducts(5);

        // الحصول على المنتجات منخفضة المخزون
        $data['low_stock_products'] = $this->model_inventory_product_management->getLowStockProducts(5);

        // الحصول على المنتجات عالية المخزون
        $data['overstock_products'] = $this->model_inventory_product_management->getOverstockProducts(5);

        // إعداد الفلاتر للعرض
        $this->setupFiltersForDisplay($data, $filter_data);

        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

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

        $this->response->setOutput($this->load->view('inventory/product_management_list', $data));
    }

    /**
     * إضافة منتج جديد
     */
    public function add() {
        $this->load->language('inventory/product_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/product_management');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $product_id = $this->model_inventory_product_management->addProduct($this->request->post);

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

            $this->response->redirect($this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * تعديل منتج
     */
    public function edit() {
        $this->load->language('inventory/product_management');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/product_management');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_product_management->editProduct($this->request->get['product_id'], $this->request->post);

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

            $this->response->redirect($this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * حذف منتج
     */
    public function delete() {
        $this->load->language('inventory/product_management');
        $this->load->model('inventory/product_management');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $product_id) {
                $this->model_inventory_product_management->deleteProduct($product_id);
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

            $this->response->redirect($this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * نسخ منتج
     */
    public function copy() {
        $this->load->language('inventory/product_management');
        $this->load->model('inventory/product_management');

        if (isset($this->request->get['product_id']) && $this->validateCopy()) {
            $product_info = $this->model_inventory_product_management->getProduct($this->request->get['product_id']);

            if ($product_info) {
                // تعديل البيانات للنسخة الجديدة
                $product_info['sku'] = $this->model_inventory_product_management->generateProductCode($product_info);
                $product_info['model'] = $product_info['model'] . ' - نسخة';
                $product_info['product_description'][1]['name'] = $product_info['name'] . ' - نسخة';

                $new_product_id = $this->model_inventory_product_management->addProduct($product_info);

                $this->session->data['success'] = $this->language->get('text_success');
            }

            $this->response->redirect($this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getList();
    }

    /**
     * معالجة الفلاتر
     */
    private function getFilters() {
        $filters = array(
            'filter_name'            => '',
            'filter_model'           => '',
            'filter_sku'             => '',
            'filter_manufacturer_id' => '',
            'filter_category_id'     => '',
            'filter_status'          => '',
            'filter_stock_status'    => '',
            'filter_price_from'      => '',
            'filter_price_to'        => '',
            'filter_quantity_from'   => '',
            'filter_quantity_to'     => '',
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
     * الحصول على نص حالة المخزون
     */
    private function getStockStatusText($status) {
        switch ($status) {
            case 'out_of_stock':
                return $this->language->get('text_out_of_stock');
            case 'low_stock':
                return $this->language->get('text_low_stock');
            case 'overstock':
                return $this->language->get('text_overstock');
            case 'in_stock':
            default:
                return $this->language->get('text_in_stock');
        }
    }

    /**
     * الحصول على فئة CSS لحالة المخزون
     */
    private function getStockStatusClass($status) {
        switch ($status) {
            case 'out_of_stock':
                return 'danger';
            case 'low_stock':
                return 'warning';
            case 'overstock':
                return 'info';
            case 'in_stock':
            default:
                return 'success';
        }
    }

    /**
     * عرض نموذج الإضافة/التعديل
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['product_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

        $this->response->setOutput($this->load->view('inventory/product_management_form', $data));
    }

    /**
     * إعداد بيانات النموذج
     */
    private function setupFormData(&$data) {
        // الحصول على البيانات الموجودة أو القيم الافتراضية
        if (isset($this->request->get['product_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $product_info = $this->model_inventory_product_management->getProduct($this->request->get['product_id']);
        }

        // الحقول الأساسية
        $basic_fields = array(
            'model', 'sku', 'upc', 'ean', 'jan', 'isbn', 'mpn', 'location',
            'quantity', 'minimum', 'subtract', 'stock_status_id', 'image',
            'manufacturer_id', 'shipping', 'price', 'points', 'tax_class_id',
            'date_available', 'weight', 'weight_class_id', 'length', 'width',
            'height', 'length_class_id', 'status', 'sort_order'
        );

        foreach ($basic_fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($product_info)) {
                $data[$field] = $product_info[$field];
            } else {
                $data[$field] = '';
            }
        }

        // حقول المخزون المتقدمة
        $inventory_fields = array(
            'available_quantity', 'reserved_quantity', 'on_order_quantity',
            'reorder_level', 'max_stock_level', 'avg_cost', 'last_cost', 'standard_cost'
        );

        foreach ($inventory_fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($product_info)) {
                $data[$field] = $product_info[$field];
            } else {
                $data[$field] = '';
            }
        }

        // حقول التسعير المتقدمة
        $pricing_fields = array(
            'basic_price', 'offer_price', 'wholesale_price', 'semi_wholesale_price',
            'special_price', 'pos_price', 'online_price', 'cost_price',
            'margin_percentage', 'markup_percentage'
        );

        foreach ($pricing_fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($product_info)) {
                $data[$field] = $product_info[$field];
            } else {
                $data[$field] = '';
            }
        }

        // وصف المنتج
        if (isset($this->request->post['product_description'])) {
            $data['product_description'] = $this->request->post['product_description'];
        } elseif (!empty($product_info)) {
            $data['product_description'][1] = array(
                'name' => $product_info['name'],
                'description' => $product_info['description'],
                'tag' => $product_info['tag'],
                'meta_title' => $product_info['meta_title'],
                'meta_description' => $product_info['meta_description'],
                'meta_keyword' => $product_info['meta_keyword']
            );
        } else {
            $data['product_description'] = array();
        }

        // الحصول على القوائم
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/category');
        $this->load->model('localisation/stock_status');
        $this->load->model('localisation/tax_class');
        $this->load->model('localisation/weight_class');
        $this->load->model('localisation/length_class');
        $this->load->model('inventory/unit');

        $data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers();
        $data['categories'] = $this->model_catalog_category->getCategories();
        $data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
        $data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();
        $data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();
        $data['units'] = $this->model_inventory_unit->getUnits();

        // الروابط
        $data['action'] = $this->url->link('inventory/product_management/' . (!isset($this->request->get['product_id']) ? 'add' : 'edit'), 'user_token=' . $this->session->data['user_token'] . (!isset($this->request->get['product_id']) ? '' : '&product_id=' . $this->request->get['product_id']), true);
        $data['cancel'] = $this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'], true);
        $data['generate_sku'] = $this->url->link('inventory/product_management/generateSku', 'user_token=' . $this->session->data['user_token'], true);
        $data['calculate_pricing'] = $this->url->link('inventory/product_management/calculatePricing', 'user_token=' . $this->session->data['user_token'], true);
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
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/category');

        $data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers();
        $data['categories'] = $this->model_catalog_category->getCategories();

        // خيارات الحالة
        $data['status_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => '1', 'text' => $this->language->get('text_enabled')),
            array('value' => '0', 'text' => $this->language->get('text_disabled'))
        );

        // خيارات حالة المخزون
        $data['stock_status_options'] = array(
            array('value' => '', 'text' => $this->language->get('text_all')),
            array('value' => 'in_stock', 'text' => $this->language->get('text_in_stock')),
            array('value' => 'low_stock', 'text' => $this->language->get('text_low_stock')),
            array('value' => 'out_of_stock', 'text' => $this->language->get('text_out_of_stock')),
            array('value' => 'overstock', 'text' => $this->language->get('text_overstock'))
        );
    }

    /**
     * توليد SKU تلقائي
     */
    public function generateSku() {
        $this->load->model('inventory/product_management');

        $sku = $this->model_inventory_product_management->generateProductCode($this->request->get);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array(
            'sku' => $sku
        )));
    }

    /**
     * حساب التسعير التلقائي
     */
    public function calculatePricing() {
        $cost_price = (float)$this->request->get['cost_price'];
        $margin_percentage = (float)$this->request->get['margin_percentage'];
        $markup_percentage = (float)$this->request->get['markup_percentage'];

        $pricing = array();

        if ($cost_price > 0) {
            if ($margin_percentage > 0) {
                $pricing['basic_price'] = $cost_price / (1 - ($margin_percentage / 100));
            } elseif ($markup_percentage > 0) {
                $pricing['basic_price'] = $cost_price * (1 + ($markup_percentage / 100));
            }

            // حساب الأسعار الأخرى بناءً على السعر الأساسي
            if (!empty($pricing['basic_price'])) {
                $pricing['wholesale_price'] = $pricing['basic_price'] * 0.85; // خصم 15%
                $pricing['semi_wholesale_price'] = $pricing['basic_price'] * 0.90; // خصم 10%
                $pricing['pos_price'] = $pricing['basic_price'];
                $pricing['online_price'] = $pricing['basic_price'] * 1.05; // زيادة 5%
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($pricing));
    }

    /**
     * عرض تفاصيل المنتج
     */
    public function view() {
        $this->load->language('inventory/product_management');
        $this->load->model('inventory/product_management');

        $product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;

        if (!$product_id) {
            $this->session->data['error'] = $this->language->get('error_product_not_found');
            $this->response->redirect($this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'], true));
        }

        // الحصول على معلومات المنتج
        $product_info = $this->model_inventory_product_management->getProduct($product_id);

        $data['product_info'] = $product_info;

        // إعداد الروابط
        $data['edit'] = $this->url->link('inventory/product_management/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product_id, true);
        $data['copy'] = $this->url->link('inventory/product_management/copy', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product_id, true);
        $data['back'] = $this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/product_management_view', $data));
    }

    /**
     * تحديث مجمع
     */
    public function bulkUpdate() {
        $this->load->language('inventory/product_management');
        $this->load->model('inventory/product_management');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $product_ids = $this->request->post['selected'];
            $update_data = $this->request->post['bulk_data'];

            $updated_count = 0;

            foreach ($product_ids as $product_id) {
                $product_info = $this->model_inventory_product_management->getProduct($product_id);

                if ($product_info) {
                    // دمج البيانات الجديدة مع البيانات الموجودة
                    $merged_data = array_merge($product_info, $update_data);
                    $this->model_inventory_product_management->editProduct($product_id, $merged_data);
                    $updated_count++;
                }
            }

            $this->session->data['success'] = sprintf($this->language->get('text_bulk_updated'), $updated_count);

            $this->response->redirect($this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'], true));
        } else {
            // عرض نموذج التحديث المجمع
            $this->getBulkUpdateForm();
        }
    }

    /**
     * عرض نموذج التحديث المجمع
     */
    private function getBulkUpdateForm() {
        $data['action'] = $this->url->link('inventory/product_management/bulkUpdate', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'], true);

        // الحصول على المنتجات المحددة
        if (isset($this->request->get['selected'])) {
            $selected_ids = explode(',', $this->request->get['selected']);
            $data['selected_products'] = array();

            foreach ($selected_ids as $product_id) {
                $product_info = $this->model_inventory_product_management->getProduct($product_id);
                if ($product_info) {
                    $data['selected_products'][] = $product_info;
                }
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/product_bulk_update', $data));
    }

    /**
     * تصدير إلى Excel
     */
    public function exportExcel() {
        $this->load->language('inventory/product_management');
        $this->load->model('inventory/product_management');

        $filter_data = $this->getFilters();
        $results = $this->model_inventory_product_management->exportToExcel($filter_data);

        // إنشاء ملف Excel
        $filename = 'products_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        $output = fopen('php://output', 'w');

        // كتابة العناوين
        $headers = array(
            $this->language->get('column_name'),
            $this->language->get('column_model'),
            $this->language->get('column_sku'),
            $this->language->get('column_manufacturer'),
            $this->language->get('column_price'),
            $this->language->get('column_quantity'),
            $this->language->get('column_status'),
            $this->language->get('column_date_added')
        );

        fputcsv($output, $headers);

        // كتابة البيانات
        foreach ($results as $result) {
            $row = array(
                $result['name'],
                $result['model'],
                $result['sku'],
                $result['manufacturer'],
                $result['price'],
                $result['available_quantity'],
                $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
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
        if (!$this->user->hasPermission('modify', 'inventory/product_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['product_description'][1]['name'])) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['model'])) {
            $this->error['model'] = $this->language->get('error_model');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/product_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة النسخ
     */
    protected function validateCopy() {
        if (!$this->user->hasPermission('modify', 'inventory/product_management')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
