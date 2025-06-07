<?php
class ControllerInventoryStockCount extends Controller {
    private $error = array();

    /**
     * قائمة الجرد
     */
    public function index() {
        $this->load->language('inventory/stock_count');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->getList();
    }

    /**
     * إضافة جلسة جرد جديدة
     */
    public function add() {
        $this->load->language('inventory/stock_count');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('inventory/stock_count');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_stock_count->addStockCount($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('inventory/stock_count', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    /**
     * تعديل جلسة جرد
     */
    public function edit() {
        $this->load->language('inventory/stock_count');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('inventory/stock_count');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_inventory_stock_count->editStockCount($this->request->get['stock_count_id'], $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('inventory/stock_count', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    /**
     * إتمام (إغلاق) الجرد
     */
    public function complete() {
        $this->load->language('inventory/stock_count');
        $json = array();

        if (!$this->user->hasPermission('modify', 'inventory/stock_count')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->get['stock_count_id'])) {
                $stock_count_id = (int)$this->request->get['stock_count_id'];

                $this->load->model('inventory/stock_count');
                $result = $this->model_inventory_stock_count->completeStockCount($stock_count_id);

                if (!empty($result['error'])) {
                    $json['error'] = $result['error'];
                } else {
                    $json['success'] = $result['success'];
                }
            } else {
                $json['error'] = 'Missing stock_count_id!';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * شاشة عرض قائمة الجلسات
     */
    protected function getList() {
        $this->load->model('inventory/stock_count');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/stock_count', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add']    = $this->url->link('inventory/stock_count/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('inventory/stock_count/delete', 'user_token=' . $this->session->data['user_token'], true);

        $data['stock_counts'] = array();

        $results = $this->model_inventory_stock_count->getStockCounts();
        foreach ($results as $res) {
            $data['stock_counts'][] = array(
                'stock_count_id'  => $res['stock_count_id'],
                'reference_code'  => $res['reference_code'],
                'branch_name'     => $res['branch_name'],
                'count_date'      => $res['count_date'],
                'status'          => $res['status'],
                'created_by_name' => $res['created_by_name'],
                'created_at'      => $res['created_at'],
                'edit'            => $this->url->link(
                                        'inventory/stock_count/edit',
                                        'user_token=' . $this->session->data['user_token'] . 
                                        '&stock_count_id=' . $res['stock_count_id'],
                                        true
                                     )
            );
        }

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

        $data['user_token']   = $this->session->data['user_token'];

        // الأحمال الشائعة
        $data['header']       = $this->load->controller('common/header');
        $data['column_left']  = $this->load->controller('common/column_left');
        $data['footer']       = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/stock_count_list', $data));
    }

    /**
     * شاشة النموذج (إضافة / تعديل)
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['stock_count_id']) 
                             ? $this->language->get('text_add') 
                             : $this->language->get('text_edit');

        // الأخطاء
        $data['error_reference_code'] = isset($this->error['reference_code']) ? $this->error['reference_code'] : '';
        $data['error_count_date']     = isset($this->error['count_date'])     ? $this->error['count_date'] : '';
        $data['error_branch']         = isset($this->error['branch_id'])      ? $this->error['branch_id'] : '';

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/stock_count', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (!isset($this->request->get['stock_count_id'])) {
            $data['action']         = $this->url->link('inventory/stock_count/add', 
                                                'user_token=' . $this->session->data['user_token'], true);
            $data['stock_count_id'] = 0;
        } else {
            $data['action']         = $this->url->link('inventory/stock_count/edit', 
                                                'user_token=' . $this->session->data['user_token'] . 
                                                '&stock_count_id=' . $this->request->get['stock_count_id'], true);
            $data['stock_count_id'] = $this->request->get['stock_count_id'];
        }

        $data['cancel'] = $this->url->link('inventory/stock_count', 'user_token=' . $this->session->data['user_token'], true);
        $data['list']   = $this->url->link('inventory/stock_count', 'user_token=' . $this->session->data['user_token'], true);

        // جلب بيانات الجرد إن كنا في وضع التعديل
        $this->load->model('inventory/stock_count');
        if (isset($this->request->get['stock_count_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $stock_count_info = $this->model_inventory_stock_count->getStockCount($this->request->get['stock_count_id']);
        } else {
            $stock_count_info = array();
        }

        // تعبئة الحقول
        $data['reference_code'] = $this->fillField('reference_code', $stock_count_info, '');
        $data['branch_id']      = $this->fillField('branch_id',      $stock_count_info, '');
        $data['count_date']     = $this->fillField('count_date',     $stock_count_info, date('Y-m-d'));
        $data['notes']          = $this->fillField('notes',          $stock_count_info, '');
        $data['status']         = (!empty($stock_count_info)) ? $stock_count_info['status'] : 'draft';

        // احضر قائمة الفروع
        $this->load->model('branch/branch'); 
        $data['branches'] = $this->model_branch_branch->getBranches();

        // items
        if (isset($this->request->post['items'])) {
            $items = $this->request->post['items'];
        } else {
            $items = $this->model_inventory_stock_count->getStockCountItems($data['stock_count_id']);
        }

        // تجهيز البيانات للعرض في الـ form
        $data['items'] = array();
        foreach ($items as $idx => $it) {
            // جلب معلومات المنتج + الوحدات (إن أردت)
            $product_info = $this->model_inventory_stock_count->getProductInfoWithUnits($it['product_id']);
            $data['items'][] = array(
                'count_item_id' => isset($it['count_item_id']) ? $it['count_item_id'] : 0,
                'product_id'    => $it['product_id'],
                'product_name'  => isset($product_info['name']) ? $product_info['name'] : '',
                'unit_id'       => $it['unit_id'],
                'units'         => !empty($product_info['units']) ? $product_info['units'] : array(),
                'system_qty'    => $it['system_qty'],
                'counted_qty'   => $it['counted_qty'],
                'difference'    => $it['difference'],
                'barcode'       => $it['barcode'],
                'notes'         => isset($it['notes']) ? $it['notes'] : '',
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

        // الأحمال الشائعة
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        // تحميل القالب
        $this->response->setOutput($this->load->view('inventory/stock_count_form', $data));
    }

    /**
     * التحقق قبل الحفظ
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'inventory/stock_count')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['reference_code']) < 1) || 
            (utf8_strlen($this->request->post['reference_code']) > 50)) {
            $this->error['reference_code'] = $this->language->get('error_reference_code');
        }

        if (!$this->request->post['count_date']) {
            $this->error['count_date'] = $this->language->get('error_count_date');
        }

        if (!$this->request->post['branch_id']) {
            $this->error['branch_id'] = $this->language->get('error_branch');
        }

        return !$this->error;
    }

    /**
     * دالة مساعدة لتعبئة الحقول
     */
    private function fillField($key, $info, $default = '') {
        if (isset($this->request->post[$key])) {
            return $this->request->post[$key];
        } elseif (!empty($info) && isset($info[$key])) {
            return $info[$key];
        } else {
            return $default;
        }
    }

    /**
     * دالة SELECT2 AJAX للبحث بالمنتج أو الباركود
     */
    public function select2Products() {
        $json = array();

        // قراءة بيانات البحث
        $q = isset($this->request->get['q']) ? trim($this->request->get['q']) : '';
        $branch_id = isset($this->request->get['branch_id']) ? (int)$this->request->get['branch_id'] : 0;

        $this->load->model('inventory/stock_count');

        // limit يمكن تغييره
        $filter_data = array(
            'filter_keyword' => $q,
            'branch_id'      => $branch_id,
            'start'          => 0,
            'limit'          => 20
        );

        $results = $this->model_inventory_stock_count->searchProducts($filter_data);

        foreach ($results as $res) {
            // بناء النتيجة لتوافق Select2
            // base_qty = كمية الوحدة الأساسية
            // units = الوحدات مع عامل التحويل
            $json[] = array(
                'id'       => $res['product_id'],
                'text'     => $res['label'],
                'base_qty' => (float)$res['base_qty'],
                'units'    => $res['units']
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
