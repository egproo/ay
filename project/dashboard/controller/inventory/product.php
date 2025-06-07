<?php
/**
 * إدارة المنتجات المتقدمة في المخزون (Advanced Product Management)
 *
 * الهدف: فصل إدارة المنتجات عن المتجر وجعل المخزون هو الأساس (مثل Odoo)
 * الميزات: تسعير 5 مستويات، خيارات مرتبطة بالوحدات، باركود متقدم، تكويد ذكي
 * التفوق: أفضل من Odoo وWooCommerce وSAP في سهولة الاستخدام والميزات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerInventoryProduct extends Controller {

    private $error = array();

    public function index() {
        // تحميل اللغة
        $this->load->language('inventory/product');

        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل النماذج المطلوبة
        $this->load->model('inventory/product');
        $this->load->model('inventory/category');
        $this->load->model('inventory/manufacturer');
        $this->load->model('inventory/units');

        // معالجة الطلبات
        $this->getList();
    }

    public function add() {
        // تحميل اللغة
        $this->load->language('inventory/product');

        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل النماذج المطلوبة
        $this->load->model('inventory/product');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $product_id = $this->model_inventory_product->addProduct($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        // تحميل اللغة
        $this->load->language('inventory/product');

        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل النماذج المطلوبة
        $this->load->model('inventory/product');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_product->editProduct($this->request->get['product_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        // تحميل اللغة
        $this->load->language('inventory/product');

        // تحديد عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // تحميل النماذج المطلوبة
        $this->load->model('inventory/product');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $product_id) {
                $this->model_inventory_product->deleteProduct($product_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    protected function getList() {
        // معالجة الفلاتر
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        if (isset($this->request->get['filter_model'])) {
            $filter_model = $this->request->get['filter_model'];
        } else {
            $filter_model = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'pd.name';
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

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
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
            'href' => $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('inventory/product/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('inventory/product/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['products'] = array();

        $filter_data = array(
            'filter_name'   => $filter_name,
            'filter_model'  => $filter_model,
            'filter_status' => $filter_status,
            'sort'          => $sort,
            'order'         => $order,
            'start'         => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'         => $this->config->get('config_limit_admin')
        );

        $product_total = $this->model_inventory_product->getTotalProducts($filter_data);

        $results = $this->model_inventory_product->getProducts($filter_data);

        foreach ($results as $result) {
            $data['products'][] = array(
                'product_id'    => $result['product_id'],
                'image'         => $result['image'],
                'name'          => $result['name'],
                'model'         => $result['model'],
                'price'         => $result['price'],
                'quantity'      => $result['quantity'],
                'status'        => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'edit'          => $this->url->link('inventory/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true)
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

        $data['sort_name'] = $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, true);
        $data['sort_model'] = $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url, true);
        $data['sort_price'] = $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url, true);
        $data['sort_quantity'] = $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity' . $url, true);
        $data['sort_status'] = $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, true);
        $data['sort_order'] = $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
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

        $pagination = new Pagination();
        $pagination->total = $product_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

        $data['filter_name'] = $filter_name;
        $data['filter_model'] = $filter_model;
        $data['filter_status'] = $filter_status;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/product_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['product_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = array();
        }

        if (isset($this->error['model'])) {
            $data['error_model'] = $this->error['model'];
        } else {
            $data['error_model'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
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
            'href' => $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['product_id'])) {
            $data['action'] = $this->url->link('inventory/product/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('inventory/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $this->request->get['product_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['product_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $product_info = $this->model_inventory_product->getProduct($this->request->get['product_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        // تحميل اللغات
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        // معلومات المنتج الأساسية
        if (isset($this->request->post['product_description'])) {
            $data['product_description'] = $this->request->post['product_description'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_description'] = $this->model_inventory_product->getProductDescriptions($this->request->get['product_id']);
        } else {
            $data['product_description'] = array();
        }

        if (isset($this->request->post['model'])) {
            $data['model'] = $this->request->post['model'];
        } elseif (!empty($product_info)) {
            $data['model'] = $product_info['model'];
        } else {
            $data['model'] = '';
        }

        if (isset($this->request->post['sku'])) {
            $data['sku'] = $this->request->post['sku'];
        } elseif (!empty($product_info)) {
            $data['sku'] = $product_info['sku'];
        } else {
            $data['sku'] = '';
        }

        // التصنيفات
        $this->load->model('inventory/category');
        $data['categories'] = $this->model_inventory_category->getCategories();

        if (isset($this->request->post['product_category'])) {
            $data['product_categories'] = $this->request->post['product_category'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_categories'] = $this->model_inventory_product->getProductCategories($this->request->get['product_id']);
        } else {
            $data['product_categories'] = array();
        }

        // العلامات التجارية
        $this->load->model('inventory/manufacturer');
        $data['manufacturers'] = $this->model_inventory_manufacturer->getManufacturers();

        if (isset($this->request->post['manufacturer_id'])) {
            $data['manufacturer_id'] = $this->request->post['manufacturer_id'];
        } elseif (!empty($product_info)) {
            $data['manufacturer_id'] = $product_info['manufacturer_id'];
        } else {
            $data['manufacturer_id'] = 0;
        }

        // الوحدات
        $this->load->model('inventory/units');
        $data['units'] = $this->model_inventory_units->getUnits();

        if (isset($this->request->post['product_units'])) {
            $data['product_units'] = $this->request->post['product_units'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_units'] = $this->model_inventory_product->getProductUnits($this->request->get['product_id']);
        } else {
            $data['product_units'] = array();
        }

        // التسعير المتطور (5 مستويات)
        if (isset($this->request->post['product_pricing'])) {
            $data['product_pricing'] = $this->request->post['product_pricing'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_pricing'] = $this->model_inventory_product->getProductPricing($this->request->get['product_id']);
        } else {
            $data['product_pricing'] = array();
        }

        // الباركود المتقدم
        if (isset($this->request->post['product_barcodes'])) {
            $data['product_barcodes'] = $this->request->post['product_barcodes'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_barcodes'] = $this->model_inventory_product->getProductBarcodes($this->request->get['product_id']);
        } else {
            $data['product_barcodes'] = array();
        }

        // الخيارات المرتبطة بالوحدات (ميزة فريدة)
        $this->load->model('catalog/option');
        $data['options'] = $this->model_catalog_option->getOptions();

        if (isset($this->request->post['product_options'])) {
            $data['product_options'] = $this->request->post['product_options'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_options'] = $this->model_inventory_product->getProductOptions($this->request->get['product_id']);
        } else {
            $data['product_options'] = array();
        }

        // الباقات والخصومات
        if (isset($this->request->post['product_bundles'])) {
            $data['product_bundles'] = $this->request->post['product_bundles'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_bundles'] = $this->model_inventory_product->getProductBundles($this->request->get['product_id']);
        } else {
            $data['product_bundles'] = array();
        }

        if (isset($this->request->post['product_discounts'])) {
            $data['product_discounts'] = $this->request->post['product_discounts'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_discounts'] = $this->model_inventory_product->getProductDiscounts($this->request->get['product_id']);
        } else {
            $data['product_discounts'] = array();
        }

        // الحالة والإعدادات
        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($product_info)) {
            $data['status'] = $product_info['status'];
        } else {
            $data['status'] = 1;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/product_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/product')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->request->post['product_description'] as $language_id => $value) {
            if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
                $this->error['name'][$language_id] = $this->language->get('error_name');
            }
        }

        if ((utf8_strlen($this->request->post['model']) < 1) || (utf8_strlen($this->request->post['model']) > 64)) {
            $this->error['model'] = $this->language->get('error_model');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/product')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * AJAX: البحث في المنتجات
     */
    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('inventory/product');

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start'       => 0,
                'limit'       => 5
            );

            $results = $this->model_inventory_product->getProducts($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'product_id' => $result['product_id'],
                    'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'model'      => $result['model'],
                    'option'     => $result['option'],
                    'price'      => $result['price']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * AJAX: توليد كود المنتج التلقائي
     */
    public function generateCode() {
        $this->load->model('inventory/product');

        $category_id = isset($this->request->get['category_id']) ? $this->request->get['category_id'] : 0;
        $manufacturer_id = isset($this->request->get['manufacturer_id']) ? $this->request->get['manufacturer_id'] : 0;

        $code = $this->model_inventory_product->generateProductCode($category_id, $manufacturer_id);

        $json = array('code' => $code);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
