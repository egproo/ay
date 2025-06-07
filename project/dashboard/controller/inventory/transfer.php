<?php
class ControllerInventoryTransfer extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('inventory/transfer');
        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->get['filter_reference'])) {
            $filter_reference = $this->request->get['filter_reference'];
        } else {
            $filter_reference = '';
        }

        if (isset($this->request->get['filter_from_branch'])) {
            $filter_from_branch = $this->request->get['filter_from_branch'];
        } else {
            $filter_from_branch = '';
        }

        if (isset($this->request->get['filter_to_branch'])) {
            $filter_to_branch = $this->request->get['filter_to_branch'];
        } else {
            $filter_to_branch = '';
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
        $this->getList();
    }

    public function add() {
        $this->load->language('inventory/transfer');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/transfer');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $transfer_id = $this->model_inventory_transfer->addTransfer($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('inventory/transfer', 'user_token=' . $this->session->data['user_token']));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('inventory/transfer');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/transfer');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_transfer->editTransfer($this->request->get['transfer_id'], $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('inventory/transfer', 'user_token=' . $this->session->data['user_token']));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('inventory/transfer');
        $this->load->model('inventory/transfer');

        if (isset($this->request->post['selected'])) {
            foreach ($this->request->post['selected'] as $transfer_id) {
                $this->model_inventory_transfer->deleteTransfer($transfer_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('inventory/transfer', 'user_token=' . $this->session->data['user_token']));
        }

        $this->getList();
    }

    /**
     * تأكيد تحويل مخزني
     */
    public function approve() {
        $this->load->language('inventory/transfer');
        $this->load->model('inventory/transfer');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/transfer')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (isset($this->request->get['transfer_id'])) {
            $transfer_id = (int)$this->request->get['transfer_id'];

            try {
                $this->model_inventory_transfer->updateTransferStatus($transfer_id, 'confirmed');
                $json['success'] = $this->language->get('text_approve_success');
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_transfer_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * رفض تحويل مخزني
     */
    public function reject() {
        $this->load->language('inventory/transfer');
        $this->load->model('inventory/transfer');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/transfer')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (isset($this->request->get['transfer_id'])) {
            $transfer_id = (int)$this->request->get['transfer_id'];

            try {
                $this->model_inventory_transfer->updateTransferStatus($transfer_id, 'rejected');
                $json['success'] = $this->language->get('text_reject_success');
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_transfer_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحديث حالة التحويل إلى قيد النقل
     */
    public function inTransit() {
        $this->load->language('inventory/transfer');
        $this->load->model('inventory/transfer');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/transfer')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (isset($this->request->get['transfer_id'])) {
            $transfer_id = (int)$this->request->get['transfer_id'];

            try {
                $this->model_inventory_transfer->updateTransferStatus($transfer_id, 'in_transit');
                $json['success'] = $this->language->get('text_in_transit_success');
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_transfer_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحديث حالة التحويل إلى مكتمل
     */
    public function complete() {
        $this->load->language('inventory/transfer');
        $this->load->model('inventory/transfer');

        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/transfer')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (isset($this->request->get['transfer_id'])) {
            $transfer_id = (int)$this->request->get['transfer_id'];

            try {
                $this->model_inventory_transfer->updateTransferStatus($transfer_id, 'completed');
                $json['success'] = $this->language->get('text_complete_success');
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_transfer_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * التحقق من توفر الكمية في المخزون
     */
    public function checkAvailability() {
        $this->load->language('inventory/transfer');
        $this->load->model('inventory/inventory');

        $json = array();

        if (!isset($this->request->post['branch_id']) || !isset($this->request->post['product_id']) ||
            !isset($this->request->post['unit_id']) || !isset($this->request->post['quantity'])) {
            $json['error'] = $this->language->get('error_missing_parameters');
        } else {
            $branch_id = (int)$this->request->post['branch_id'];
            $product_id = (int)$this->request->post['product_id'];
            $unit_id = (int)$this->request->post['unit_id'];
            $quantity = (float)$this->request->post['quantity'];

            // الحصول على الكمية المتاحة
            $available_quantity = $this->model_inventory_inventory->getAvailableQuantity($branch_id, $product_id, $unit_id);

            $json['available'] = $available_quantity;
            $json['is_available'] = ($available_quantity >= $quantity);

            if (!$json['is_available']) {
                $json['error'] = $this->language->get('error_insufficient_stock');
                $json['message'] = sprintf($this->language->get('text_available_quantity'), $available_quantity);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على وحدات المنتج
     */
    public function getProductUnits() {
        $this->load->language('inventory/transfer');
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

    protected function getList() {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/transfer', 'user_token=' . $this->session->data['user_token'])
        );

        $data['add'] = $this->url->link('inventory/transfer/add', 'user_token=' . $this->session->data['user_token']);
        $data['delete'] = $this->url->link('inventory/transfer/delete', 'user_token=' . $this->session->data['user_token']);

        $filter_data = array(
            'filter_reference' => isset($this->request->get['filter_reference']) ? $this->request->get['filter_reference'] : '',
            'filter_from_branch' => isset($this->request->get['filter_from_branch']) ? $this->request->get['filter_from_branch'] : '',
            'filter_to_branch' => isset($this->request->get['filter_to_branch']) ? $this->request->get['filter_to_branch'] : '',
            'filter_status' => isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '',
            'filter_date_start' => isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '',
            'filter_date_end' => isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '',
            'sort' => isset($this->request->get['sort']) ? $this->request->get['sort'] : 'transfer_date',
            'order' => isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC',
            'start' => ($this->request->get['page'] - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $data['transfers'] = array();
        $transfers = $this->model_inventory_transfer->getTransfers($filter_data);

        foreach ($transfers as $transfer) {
            $data['transfers'][] = array(
                'transfer_id' => $transfer['transfer_id'],
                'reference_number' => $transfer['transfer_number'],
                'from_branch_name' => $transfer['from_branch_name'],
                'to_branch_name' => $transfer['to_branch_name'],
                'transfer_date' => date($this->language->get('date_format_short'), strtotime($transfer['transfer_date'])),
                'status' => $transfer['status'],
                'created_by_name' => $transfer['created_by_name'],
                'edit' => $this->url->link('inventory/transfer/edit', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $transfer['transfer_id'])
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

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/transfer_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['transfer_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

        if (isset($this->error['from_branch'])) {
            $data['error_from_branch'] = $this->error['from_branch'];
        } else {
            $data['error_from_branch'] = '';
        }

        if (isset($this->error['to_branch'])) {
            $data['error_to_branch'] = $this->error['to_branch'];
        } else {
            $data['error_to_branch'] = '';
        }

        if (isset($this->error['transfer_date'])) {
            $data['error_transfer_date'] = $this->error['transfer_date'];
        } else {
            $data['error_transfer_date'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/transfer', 'user_token=' . $this->session->data['user_token'])
        );

        if (!isset($this->request->get['transfer_id'])) {
            $data['action'] = $this->url->link('inventory/transfer/add', 'user_token=' . $this->session->data['user_token']);
        } else {
            $data['action'] = $this->url->link('inventory/transfer/edit', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $this->request->get['transfer_id']);
        }

        $data['cancel'] = $this->url->link('inventory/transfer', 'user_token=' . $this->session->data['user_token']);

        // تحميل البيانات الأساسية
        $this->load->model('catalog/product');
        $this->load->model('branch/branch');
        $this->load->model('inventory/branch');

        // تحميل قائمة الفروع
        $data['branches'] = $this->model_branch_branch->getBranches();
        if (empty($data['branches'])) {
            $data['branches'] = $this->model_inventory_branch->getBranches();
        }

        // تحميل قائمة المنتجات
        $data['products_list'] = $this->model_catalog_product->getProducts();

        // تعيين القيم الافتراضية
        $data['reference_number'] = '';
        $data['from_branch_id'] = '';
        $data['to_branch_id'] = '';
        $data['transfer_date'] = date('Y-m-d');
        $data['status'] = 'pending';
        $data['notes'] = '';
        $data['products'] = array();

        if (isset($this->request->get['transfer_id'])) {
            $transfer_info = $this->model_inventory_transfer->getTransfer($this->request->get['transfer_id']);

            if ($transfer_info) {
                $data['reference_number'] = $transfer_info['transfer_number'];
                $data['from_branch_id'] = $transfer_info['from_branch_id'];
                $data['to_branch_id'] = $transfer_info['to_branch_id'];
                $data['transfer_date'] = $transfer_info['transfer_date'];
                $data['status'] = $transfer_info['status'];
                $data['notes'] = $transfer_info['notes'];

                // تحميل منتجات التحويل
                $transfer_products = $this->model_inventory_transfer->getTransferProducts($this->request->get['transfer_id']);

                foreach ($transfer_products as $product) {
                    // تحميل وحدات المنتج
                    $units = $this->model_catalog_product->getProductUnits($product['product_id']);

                    $data['products'][] = array(
                        'product_id' => $product['product_id'],
                        'product_name' => $product['product_name'],
                        'unit_id' => $product['unit_id'],
                        'unit_name' => $product['unit_name'],
                        'quantity' => $product['quantity'],
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

        if (isset($this->request->post['from_branch_id'])) {
            $data['from_branch_id'] = $this->request->post['from_branch_id'];
        }

        if (isset($this->request->post['to_branch_id'])) {
            $data['to_branch_id'] = $this->request->post['to_branch_id'];
        }

        if (isset($this->request->post['transfer_date'])) {
            $data['transfer_date'] = $this->request->post['transfer_date'];
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
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
                    'quantity' => $product['quantity'],
                    'notes' => $product['notes'],
                    'units' => $units
                );
            }
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/transfer_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/transfer')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['reference_number'])) {
            $this->error['reference_number'] = $this->language->get('error_reference_number');
        }

        if (empty($this->request->post['from_branch_id'])) {
            $this->error['from_branch'] = $this->language->get('error_from_branch');
        }

        if (empty($this->request->post['to_branch_id'])) {
            $this->error['to_branch'] = $this->language->get('error_to_branch');
        }

        if ($this->request->post['from_branch_id'] == $this->request->post['to_branch_id']) {
            $this->error['warning'] = $this->language->get('error_same_branch');
        }

        if (empty($this->request->post['transfer_date'])) {
            $this->error['transfer_date'] = $this->language->get('error_transfer_date');
        }

        if (empty($this->request->post['products'])) {
            $this->error['warning'] = $this->language->get('error_products');
        } else {
            // التحقق من توفر الكمية المطلوبة في المخزون
            $this->load->model('inventory/inventory');

            foreach ($this->request->post['products'] as $key => $product) {
                if (empty($product['product_id'])) {
                    $this->error['product_' . $key] = $this->language->get('error_product');
                }

                if (empty($product['unit_id'])) {
                    $this->error['unit_' . $key] = $this->language->get('error_unit');
                }

                if ((float)$product['quantity'] <= 0) {
                    $this->error['quantity_' . $key] = $this->language->get('error_quantity');
                }

                // التحقق من توفر الكمية في المخزون
                if (!empty($product['product_id']) && !empty($product['unit_id']) && (float)$product['quantity'] > 0) {
                    $available = $this->model_inventory_inventory->getAvailableQuantity(
                        $this->request->post['from_branch_id'],
                        $product['product_id'],
                        $product['unit_id']
                    );

                    if ($available < (float)$product['quantity']) {
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

    /**
     * الحصول على الكمية المتاحة للمنتج في فرع معين
     */
    public function getAvailableQuantity() {
        $json = array();

        if (isset($this->request->get['product_id']) && isset($this->request->get['branch_id'])) {
            $product_id = (int)$this->request->get['product_id'];
            $branch_id = (int)$this->request->get['branch_id'];
            $unit_id = isset($this->request->get['unit_id']) ? (int)$this->request->get['unit_id'] : 0;

            $this->load->model('catalog/product');

            // إذا تم تحديد الوحدة، نجلب الكمية المتاحة لهذه الوحدة
            if ($unit_id) {
                $quantity = $this->model_catalog_product->getProductQuantity($product_id, $unit_id, $branch_id);
                $json['quantity'] = $quantity;
            } else {
                // إذا لم يتم تحديد الوحدة، نجلب جميع الوحدات المتاحة للمنتج
                $this->load->model('catalog/product');
                $units = $this->model_catalog_product->getProductUnits($product_id);

                $json['units'] = array();
                foreach ($units as $unit) {
                    $quantity = $this->model_catalog_product->getProductQuantity($product_id, $unit['unit_id'], $branch_id);
                    $json['units'][] = array(
                        'unit_id' => $unit['unit_id'],
                        'name' => $unit['desc_en'],
                        'quantity' => $quantity
                    );
                }
            }
        } else {
            $json['error'] = $this->language->get('error_missing_parameters');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}