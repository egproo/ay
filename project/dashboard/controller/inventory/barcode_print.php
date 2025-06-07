<?php
/**
 * AYM ERP - Barcode Print Controller
 * كونترولر طباعة الباركود المتقدم
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerInventoryBarcodePrint extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('inventory/barcode_print');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('inventory/barcode_print');
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('catalog/manufacturer');

        $this->getList();
    }

    public function add() {
        $this->load->language('inventory/barcode_print');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('inventory/barcode_print');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_barcode_print->addTemplate($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_template');

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

            $this->response->redirect($this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('inventory/barcode_print');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('inventory/barcode_print');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_barcode_print->editTemplate($this->request->get['template_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_template');

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

            $this->response->redirect($this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('inventory/barcode_print');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('inventory/barcode_print');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $template_id) {
                $this->model_inventory_barcode_print->deleteTemplate($template_id);
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

            $this->response->redirect($this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function print() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');
        $this->load->model('catalog/product');

        if (!$this->user->hasPermission('access', 'inventory/barcode_print')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $product_ids = $this->request->post['product_ids'] ?? array();
            $quantities = $this->request->post['quantities'] ?? array();
            $settings = $this->request->post['settings'] ?? array();

            if (empty($product_ids)) {
                $json['error'] = $this->language->get('error_no_products');
            } else {
                try {
                    $pdf_data = $this->model_inventory_barcode_print->generateBarcodePDF($product_ids, $quantities, $settings);

                    $filename = 'barcodes_' . date('Y-m-d_H-i-s') . '.pdf';

                    $this->response->addHeader('Content-Type: application/pdf');
                    $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
                    $this->response->setOutput($pdf_data);

                } catch (Exception $e) {
                    $json['error'] = 'خطأ في إنشاء PDF: ' . $e->getMessage();
                }
            }
        }

        if (isset($json)) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }

    public function preview() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');
        $this->load->model('catalog/product');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $product_ids = $this->request->post['product_ids'] ?? array();
            $quantities = $this->request->post['quantities'] ?? array();
            $settings = $this->request->post['settings'] ?? array();

            if (empty($product_ids)) {
                $json['error'] = $this->language->get('error_no_products');
            } else {
                try {
                    $preview_html = $this->model_inventory_barcode_print->generateBarcodePreview($product_ids, $quantities, $settings);
                    $json['success'] = true;
                    $json['html'] = $preview_html;

                } catch (Exception $e) {
                    $json['error'] = 'خطأ في المعاينة: ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveTemplate() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $template_data = $this->request->post;

            if (empty($template_data['name'])) {
                $json['error'] = $this->language->get('error_template_name');
            } else {
                try {
                    $template_id = $this->model_inventory_barcode_print->addTemplate($template_data);
                    $json['success'] = $this->language->get('text_success_template');
                    $json['template_id'] = $template_id;

                } catch (Exception $e) {
                    $json['error'] = 'خطأ في حفظ القالب: ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function loadTemplate() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');

        $json = array();

        if (isset($this->request->get['template_id'])) {
            $template_id = $this->request->get['template_id'];
            $template_info = $this->model_inventory_barcode_print->getTemplate($template_id);

            if ($template_info) {
                $json['success'] = true;
                $json['template'] = $template_info;
            } else {
                $json['error'] = 'القالب غير موجود';
            }
        } else {
            $json['error'] = 'معرف القالب مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getProducts() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('catalog/product');

        $json = array();

        $filter_data = array(
            'filter_name' => $this->request->get['filter_name'] ?? '',
            'filter_model' => $this->request->get['filter_model'] ?? '',
            'filter_category_id' => $this->request->get['filter_category_id'] ?? '',
            'filter_manufacturer_id' => $this->request->get['filter_manufacturer_id'] ?? '',
            'filter_status' => 1,
            'sort' => $this->request->get['sort'] ?? 'pd.name',
            'order' => $this->request->get['order'] ?? 'ASC',
            'start' => $this->request->get['start'] ?? 0,
            'limit' => $this->request->get['limit'] ?? 20
        );

        $products = $this->model_catalog_product->getProducts($filter_data);
        $total = $this->model_catalog_product->getTotalProducts($filter_data);

        $data = array();
        foreach ($products as $product) {
            $data[] = array(
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'model' => $product['model'],
                'sku' => $product['sku'],
                'price' => $this->currency->format($product['price'], $this->config->get('config_currency')),
                'quantity' => $product['quantity'],
                'status' => $product['status'],
                'image' => $this->model_tool_image->resize($product['image'] ?: 'no_image.png', 40, 40)
            );
        }

        $json['success'] = true;
        $json['products'] = $data;
        $json['total'] = $total;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'name';
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
            'href' => $this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('inventory/barcode_print/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('inventory/barcode_print/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['templates'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $template_total = $this->model_inventory_barcode_print->getTotalTemplates();

        $results = $this->model_inventory_barcode_print->getTemplates($filter_data);

        foreach ($results as $result) {
            $data['templates'][] = array(
                'template_id' => $result['template_id'],
                'name' => $result['name'],
                'description' => $result['description'],
                'is_default' => $result['is_default'],
                'created_date' => date($this->language->get('date_format_short'), strtotime($result['created_date'])),
                'edit' => $this->url->link('inventory/barcode_print/edit', 'user_token=' . $this->session->data['user_token'] . '&template_id=' . $result['template_id'] . $url, true)
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

        $data['sort_name'] = $this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
        $data['sort_created_date'] = $this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'] . '&sort=created_date' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $template_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($template_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($template_total - $this->config->get('config_limit_admin'))) ? $template_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $template_total, ceil($template_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/barcode_print', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['template_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = '';
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

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['template_id'])) {
            $data['action'] = $this->url->link('inventory/barcode_print/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('inventory/barcode_print/edit', 'user_token=' . $this->session->data['user_token'] . '&template_id=' . $this->request->get['template_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['template_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $template_info = $this->model_inventory_barcode_print->getTemplate($this->request->get['template_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($template_info)) {
            $data['name'] = $template_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['description'])) {
            $data['description'] = $this->request->post['description'];
        } elseif (!empty($template_info)) {
            $data['description'] = $template_info['description'];
        } else {
            $data['description'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/barcode_print_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/barcode_print')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 255)) {
            $this->error['name'] = $this->language->get('error_template_name');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'inventory/barcode_print')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function generateBarcodes() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');
        $this->load->model('catalog/product');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $filter_data = array(
                'category_id' => $this->request->post['category_id'] ?? '',
                'manufacturer_id' => $this->request->post['manufacturer_id'] ?? '',
                'status' => 1
            );

            try {
                $products = $this->model_catalog_product->getProducts($filter_data);
                $generated_count = 0;

                foreach ($products as $product) {
                    if (empty($product['sku'])) {
                        // إنشاء SKU تلقائي إذا لم يكن موجود
                        $sku = $this->model_inventory_barcode_print->generateSKU($product['product_id']);
                        $this->model_catalog_product->updateSKU($product['product_id'], $sku);
                        $generated_count++;
                    }
                }

                $json['success'] = 'تم إنشاء ' . $generated_count . ' باركود بنجاح';

            } catch (Exception $e) {
                $json['error'] = 'خطأ في إنشاء الباركود: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function bulkPrint() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');
        $this->load->model('catalog/product');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $operation = $this->request->post['operation'] ?? '';
            $settings = $this->request->post['settings'] ?? array();

            try {
                switch ($operation) {
                    case 'category':
                        $category_id = $this->request->post['category_id'] ?? 0;
                        $products = $this->model_catalog_product->getProductsByCategory($category_id);
                        break;
                    case 'manufacturer':
                        $manufacturer_id = $this->request->post['manufacturer_id'] ?? 0;
                        $products = $this->model_catalog_product->getProductsByManufacturer($manufacturer_id);
                        break;
                    case 'all':
                        $products = $this->model_catalog_product->getProducts(array('status' => 1));
                        break;
                    default:
                        throw new Exception('عملية غير صحيحة');
                }

                $product_ids = array_column($products, 'product_id');
                $quantities = array_fill(0, count($product_ids), 1);

                $pdf_data = $this->model_inventory_barcode_print->generateBarcodePDF($product_ids, $quantities, $settings);

                $filename = 'bulk_barcodes_' . date('Y-m-d_H-i-s') . '.pdf';

                $this->response->addHeader('Content-Type: application/pdf');
                $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
                $this->response->setOutput($pdf_data);

            } catch (Exception $e) {
                $json['error'] = 'خطأ في الطباعة المجمعة: ' . $e->getMessage();
            }
        }

        if (isset($json)) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }

    public function exportTemplate() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');

        if (!$this->user->hasPermission('access', 'inventory/barcode_print')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (isset($this->request->get['template_id'])) {
            $template_id = $this->request->get['template_id'];
            $template_info = $this->model_inventory_barcode_print->getTemplate($template_id);

            if ($template_info) {
                $filename = 'barcode_template_' . $template_info['name'] . '_' . date('Y-m-d') . '.json';

                $this->response->addHeader('Content-Type: application/json');
                $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
                $this->response->setOutput(json_encode($template_info, JSON_PRETTY_PRINT));
            } else {
                $this->session->data['error'] = 'القالب غير موجود';
                $this->response->redirect($this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'], true));
            }
        }
    }

    public function importTemplate() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            if (isset($this->request->files['template_file'])) {
                $file = $this->request->files['template_file'];

                if ($file['error'] == UPLOAD_ERR_OK) {
                    $template_data = json_decode(file_get_contents($file['tmp_name']), true);

                    if ($template_data) {
                        try {
                            unset($template_data['template_id']); // إزالة المعرف القديم
                            $template_data['name'] .= ' (مستورد)';

                            $template_id = $this->model_inventory_barcode_print->addTemplate($template_data);
                            $json['success'] = $this->language->get('text_import_success');

                        } catch (Exception $e) {
                            $json['error'] = 'خطأ في استيراد القالب: ' . $e->getMessage();
                        }
                    } else {
                        $json['error'] = 'ملف القالب غير صحيح';
                    }
                } else {
                    $json['error'] = 'خطأ في رفع الملف';
                }
            } else {
                $json['error'] = 'لم يتم اختيار ملف';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getStatistics() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');

        $json = array();

        try {
            $stats = $this->model_inventory_barcode_print->getBarcodeStatistics();
            $json['success'] = true;
            $json['statistics'] = $stats;

        } catch (Exception $e) {
            $json['error'] = 'خطأ في تحميل الإحصائيات: ' . $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function validateBarcode() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $barcode = $this->request->post['barcode'] ?? '';
            $barcode_type = $this->request->post['barcode_type'] ?? 'code128';

            try {
                $is_valid = $this->model_inventory_barcode_print->validateBarcodeFormat($barcode, $barcode_type);

                if ($is_valid) {
                    $json['success'] = true;
                    $json['message'] = 'الباركود صحيح';
                } else {
                    $json['success'] = false;
                    $json['message'] = 'الباركود غير صحيح';
                }

            } catch (Exception $e) {
                $json['error'] = 'خطأ في التحقق من الباركود: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function duplicateTemplate() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');

        $json = array();

        if (isset($this->request->post['template_id'])) {
            $template_id = $this->request->post['template_id'];

            try {
                $new_template_id = $this->model_inventory_barcode_print->duplicateTemplate($template_id);
                $json['success'] = 'تم نسخ القالب بنجاح';
                $json['new_template_id'] = $new_template_id;

            } catch (Exception $e) {
                $json['error'] = 'خطأ في نسخ القالب: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف القالب مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function setDefaultTemplate() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');

        $json = array();

        if (isset($this->request->post['template_id'])) {
            $template_id = $this->request->post['template_id'];

            try {
                $this->model_inventory_barcode_print->setDefaultTemplate($template_id);
                $json['success'] = 'تم تعيين القالب كافتراضي';

            } catch (Exception $e) {
                $json['error'] = 'خطأ في تعيين القالب الافتراضي: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف القالب مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getRecentTemplates() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');

        $json = array();

        try {
            $templates = $this->model_inventory_barcode_print->getRecentTemplates(5);
            $json['success'] = true;
            $json['templates'] = $templates;

        } catch (Exception $e) {
            $json['error'] = 'خطأ في تحميل القوالب الأخيرة: ' . $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function searchProducts() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('catalog/product');

        $json = array();

        $search_term = $this->request->get['search'] ?? '';

        if (strlen($search_term) >= 2) {
            $filter_data = array(
                'filter_name' => $search_term,
                'filter_model' => $search_term,
                'filter_sku' => $search_term,
                'filter_status' => 1,
                'limit' => 10
            );

            $products = $this->model_catalog_product->getProducts($filter_data);

            $data = array();
            foreach ($products as $product) {
                $data[] = array(
                    'product_id' => $product['product_id'],
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'sku' => $product['sku'],
                    'price' => $this->currency->format($product['price'], $this->config->get('config_currency'))
                );
            }

            $json['success'] = true;
            $json['products'] = $data;
        } else {
            $json['products'] = array();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function testPrint() {
        $this->load->language('inventory/barcode_print');
        $this->load->model('inventory/barcode_print');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $settings = $this->request->post['settings'] ?? array();

            try {
                // إنشاء صفحة اختبار مع باركود تجريبي
                $test_data = array(
                    'product_id' => 'TEST',
                    'name' => 'منتج تجريبي',
                    'sku' => '123456789',
                    'price' => '100.00'
                );

                $pdf_data = $this->model_inventory_barcode_print->generateTestPage($test_data, $settings);

                $filename = 'barcode_test_' . date('Y-m-d_H-i-s') . '.pdf';

                $this->response->addHeader('Content-Type: application/pdf');
                $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
                $this->response->setOutput($pdf_data);

            } catch (Exception $e) {
                $json['error'] = 'خطأ في طباعة الاختبار: ' . $e->getMessage();
            }
        }

        if (isset($json)) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }

    public function getCategories() {
        $this->load->model('catalog/category');

        $json = array();

        $categories = $this->model_catalog_category->getCategories();

        $data = array();
        foreach ($categories as $category) {
            $data[] = array(
                'category_id' => $category['category_id'],
                'name' => $category['name']
            );
        }

        $json['success'] = true;
        $json['categories'] = $data;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getManufacturers() {
        $this->load->model('catalog/manufacturer');

        $json = array();

        $manufacturers = $this->model_catalog_manufacturer->getManufacturers();

        $data = array();
        foreach ($manufacturers as $manufacturer) {
            $data[] = array(
                'manufacturer_id' => $manufacturer['manufacturer_id'],
                'name' => $manufacturer['name']
            );
        }

        $json['success'] = true;
        $json['manufacturers'] = $data;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
