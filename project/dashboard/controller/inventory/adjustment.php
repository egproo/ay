<?php
/**
 * نظام تعديلات المخزون
 * يستخدم لإجراء تعديلات على المخزون (زيادة أو نقصان)
 */
class ControllerInventoryAdjustment extends Controller {
    private $error = array();

    /**
     * عرض قائمة تعديلات المخزون
     */
    public function index() {
        $this->load->language('inventory/adjustment');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/adjustment');

        $this->getList();
    }

    /**
     * إضافة تعديل مخزون جديد
     */
    public function add() {
        $this->load->language('inventory/adjustment');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/adjustment');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $adjustment_id = $this->model_inventory_adjustment->addAdjustment($this->request->post);
                $this->session->data['success'] = $this->language->get('text_success');
                $this->response->redirect($this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token']));
            } catch (Exception $e) {
                $this->error['warning'] = $e->getMessage();
            }
        }

        $this->getForm();
    }

    /**
     * تعديل تعديل مخزون
     */
    public function edit() {
        $this->load->language('inventory/adjustment');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/adjustment');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $this->model_inventory_adjustment->editAdjustment($this->request->get['adjustment_id'], $this->request->post);
                $this->session->data['success'] = $this->language->get('text_success');
                $this->response->redirect($this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token']));
            } catch (Exception $e) {
                $this->error['warning'] = $e->getMessage();
            }
        }

        $this->getForm();
    }

    /**
     * حذف تعديل مخزون
     */
    public function delete() {
        $this->load->language('inventory/adjustment');
        $this->load->model('inventory/adjustment');

        if (isset($this->request->post['selected'])) {
            foreach ($this->request->post['selected'] as $adjustment_id) {
                $this->model_inventory_adjustment->deleteAdjustment($adjustment_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token']));
        }

        $this->getList();
    }

    /**
     * تأكيد تعديل مخزون
     */
    public function approve() {
        $this->load->language('inventory/adjustment');
        $this->load->model('inventory/adjustment');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/adjustment')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (isset($this->request->get['adjustment_id'])) {
            $adjustment_id = (int)$this->request->get['adjustment_id'];

            try {
                $this->model_inventory_adjustment->updateAdjustmentStatus($adjustment_id, 'approved');
                $json['success'] = $this->language->get('text_approve_success');
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_adjustment_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * رفض تعديل مخزون
     */
    public function reject() {
        $this->load->language('inventory/adjustment');
        $this->load->model('inventory/adjustment');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/adjustment')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (isset($this->request->get['adjustment_id'])) {
            $adjustment_id = (int)$this->request->get['adjustment_id'];

            try {
                $this->model_inventory_adjustment->updateAdjustmentStatus($adjustment_id, 'rejected');
                $json['success'] = $this->language->get('text_reject_success');
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_adjustment_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * التحقق من توفر الكمية في المخزون
     */
    public function checkAvailability() {
        $this->load->language('inventory/adjustment');
        $this->load->model('inventory/inventory');

        $json = array();

        if (!isset($this->request->post['branch_id']) || !isset($this->request->post['product_id']) ||
            !isset($this->request->post['unit_id'])) {
            $json['error'] = $this->language->get('error_missing_parameters');
        } else {
            $branch_id = (int)$this->request->post['branch_id'];
            $product_id = (int)$this->request->post['product_id'];
            $unit_id = (int)$this->request->post['unit_id'];

            // الحصول على الكمية المتاحة
            $available_quantity = $this->model_inventory_inventory->getAvailableQuantity($branch_id, $product_id, $unit_id);

            $json['available'] = $available_quantity;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على وحدات المنتج
     */
    public function getProductUnits() {
        $this->load->language('inventory/adjustment');
        $this->load->model('catalog/product');

        $json = array();

        if (!isset($this->request->post['product_id'])) {
            $json['error'] = $this->language->get('error_product');
        } else {
            $product_id = (int)$this->request->post['product_id'];

            // الحصول على وحدات المنتج
            $units = $this->model_catalog_product->getProductUnits($product_id);

            $json['units'] = $units;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * عرض قائمة تعديلات المخزون
     */
    protected function getList() {
        if (isset($this->request->get['filter_reference'])) {
            $filter_reference = $this->request->get['filter_reference'];
        } else {
            $filter_reference = '';
        }

        if (isset($this->request->get['filter_branch'])) {
            $filter_branch = $this->request->get['filter_branch'];
        } else {
            $filter_branch = '';
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
            $sort = 'a.created_at';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        $data['add'] = $this->url->link('inventory/adjustment/add', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['delete'] = $this->url->link('inventory/adjustment/delete', 'user_token=' . $this->session->data['user_token'] . $url);

        $data['adjustments'] = array();

        $filter_data = array(
            'filter_reference'  => $filter_reference,
            'filter_branch'     => $filter_branch,
            'filter_status'     => $filter_status,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'sort'              => $sort,
            'order'             => $order,
            'start'             => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'             => $this->config->get('config_limit_admin')
        );

        $adjustment_total = $this->model_inventory_adjustment->getTotalAdjustments($filter_data);
        $results = $this->model_inventory_adjustment->getAdjustments($filter_data);

        foreach ($results as $result) {
            $data['adjustments'][] = array(
                'adjustment_id'    => $result['adjustment_id'],
                'reference_number' => $result['reference_number'],
                'branch_name'      => $result['branch_name'],
                'adjustment_date'  => date($this->language->get('date_format_short'), strtotime($result['adjustment_date'])),
                'status'           => $result['status'],
                'created_by'       => $result['created_by_name'],
                'created_at'       => date($this->language->get('datetime_format'), strtotime($result['created_at'])),
                'edit'             => $this->url->link('inventory/adjustment/edit', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $result['adjustment_id'] . $url)
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

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
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

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_reference'] = $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token'] . '&sort=a.reference_number' . $url);
        $data['sort_branch'] = $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token'] . '&sort=b.name' . $url);
        $data['sort_date'] = $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token'] . '&sort=a.adjustment_date' . $url);
        $data['sort_status'] = $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token'] . '&sort=a.status' . $url);
        $data['sort_created_at'] = $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token'] . '&sort=a.created_at' . $url);

        $url = '';

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . urlencode($this->request->get['filter_reference']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
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
        $pagination->total = $adjustment_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($adjustment_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($adjustment_total - $this->config->get('config_limit_admin'))) ? $adjustment_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $adjustment_total, ceil($adjustment_total / $this->config->get('config_limit_admin')));

        $data['filter_reference'] = $filter_reference;
        $data['filter_branch'] = $filter_branch;
        $data['filter_status'] = $filter_status;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/adjustment_list', $data));
    }

    /**
     * عرض نموذج تعديل المخزون
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['adjustment_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['reference_number'])) {
            $data['error_reference_number'] = $this->error['reference_number'];
        } else {
            $data['error_reference_number'] = '';
        }

        if (isset($this->error['branch'])) {
            $data['error_branch'] = $this->error['branch'];
        } else {
            $data['error_branch'] = '';
        }

        if (isset($this->error['adjustment_date'])) {
            $data['error_adjustment_date'] = $this->error['adjustment_date'];
        } else {
            $data['error_adjustment_date'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token'])
        );

        if (!isset($this->request->get['adjustment_id'])) {
            $data['action'] = $this->url->link('inventory/adjustment/add', 'user_token=' . $this->session->data['user_token']);
        } else {
            $data['action'] = $this->url->link('inventory/adjustment/edit', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $this->request->get['adjustment_id']);
        }

        $data['cancel'] = $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token']);

        // تحميل البيانات الأساسية
        $this->load->model('catalog/product');
        $this->load->model('branch/branch');

        // تحميل قائمة الفروع
        $data['branches'] = $this->model_branch_branch->getBranches();

        // تحميل قائمة المنتجات
        $data['products_list'] = $this->model_catalog_product->getProducts();

        // تعيين القيم الافتراضية
        $data['reference_number'] = '';
        $data['branch_id'] = '';
        $data['adjustment_date'] = date('Y-m-d');
        $data['status'] = 'pending';
        $data['reason'] = '';
        $data['notes'] = '';
        $data['products'] = array();

        if (isset($this->request->get['adjustment_id'])) {
            $adjustment_info = $this->model_inventory_adjustment->getAdjustment($this->request->get['adjustment_id']);

            if ($adjustment_info) {
                $data['reference_number'] = $adjustment_info['reference_number'];
                $data['branch_id'] = $adjustment_info['branch_id'];
                $data['adjustment_date'] = $adjustment_info['adjustment_date'];
                $data['status'] = $adjustment_info['status'];
                $data['reason'] = $adjustment_info['reason'];
                $data['notes'] = $adjustment_info['notes'];

                // تحميل منتجات التعديل
                $adjustment_products = $this->model_inventory_adjustment->getAdjustmentProducts($this->request->get['adjustment_id']);

                foreach ($adjustment_products as $product) {
                    // تحميل وحدات المنتج
                    $units = $this->model_catalog_product->getProductUnits($product['product_id']);

                    $data['products'][] = array(
                        'product_id' => $product['product_id'],
                        'product_name' => $product['product_name'],
                        'unit_id' => $product['unit_id'],
                        'unit_name' => $product['unit_name'],
                        'adjustment_type' => $product['adjustment_type'],
                        'quantity' => $product['quantity'],
                        'unit_cost' => $product['unit_cost'],
                        'notes' => $product['notes'],
                        'units' => $units
                    );
                }
            }
        }

        // إذا كان هناك بيانات من الطلب السابق (في حالة الخطأ)
        if (isset($this->request->post['reference_number'])) {
            $data['reference_number'] = $this->request->post['reference_number'];
        }

        if (isset($this->request->post['branch_id'])) {
            $data['branch_id'] = $this->request->post['branch_id'];
        }

        if (isset($this->request->post['adjustment_date'])) {
            $data['adjustment_date'] = $this->request->post['adjustment_date'];
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        }

        if (isset($this->request->post['reason'])) {
            $data['reason'] = $this->request->post['reason'];
        }

        if (isset($this->request->post['notes'])) {
            $data['notes'] = $this->request->post['notes'];
        }

        if (isset($this->request->post['products'])) {
            $data['products'] = array();

            foreach ($this->request->post['products'] as $product) {
                $product_info = $this->model_catalog_product->getProduct($product['product_id']);
                $units = $this->model_catalog_product->getProductUnits($product['product_id']);

                $data['products'][] = array(
                    'product_id' => $product['product_id'],
                    'product_name' => $product_info ? $product_info['name'] : '',
                    'unit_id' => $product['unit_id'],
                    'adjustment_type' => $product['adjustment_type'],
                    'quantity' => $product['quantity'],
                    'unit_cost' => $product['unit_cost'],
                    'notes' => $product['notes'],
                    'units' => $units
                );
            }
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/adjustment_form', $data));
    }

    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/adjustment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['reference_number'])) {
            $this->error['reference_number'] = $this->language->get('error_reference_number');
        }

        if (empty($this->request->post['branch_id'])) {
            $this->error['branch'] = $this->language->get('error_branch');
        }

        if (empty($this->request->post['adjustment_date'])) {
            $this->error['adjustment_date'] = $this->language->get('error_adjustment_date');
        }

        if (empty($this->request->post['products'])) {
            $this->error['warning'] = $this->language->get('error_products');
        } else {
            // التحقق من توفر الكمية المطلوبة في المخزون للتعديلات السالبة
            $this->load->model('inventory/inventory');

            foreach ($this->request->post['products'] as $key => $product) {
                if (empty($product['product_id'])) {
                    $this->error['product_' . $key] = $this->language->get('error_product');
                }

                if (empty($product['unit_id'])) {
                    $this->error['unit_' . $key] = $this->language->get('error_unit');
                }

                if ((float)$product['quantity'] == 0) {
                    $this->error['quantity_' . $key] = $this->language->get('error_quantity');
                }

                // التحقق من توفر الكمية في المخزون للتعديلات السالبة
                if (!empty($product['product_id']) && !empty($product['unit_id']) &&
                    (float)$product['quantity'] < 0 && $product['adjustment_type'] == 'quantity') {
                    $available = $this->model_inventory_inventory->getAvailableQuantity(
                        $this->request->post['branch_id'],
                        $product['product_id'],
                        $product['unit_id']
                    );

                    if ($available < abs((float)$product['quantity'])) {
                        $this->error['quantity_' . $key] = sprintf($this->language->get('error_insufficient_quantity'), $available);

                        if (!isset($this->error['warning'])) {
                            $this->error['warning'] = $this->language->get('error_insufficient_stock');
                        }
                    }
                }
            }
        }

        return !$this->error;
    }
}
