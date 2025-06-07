<?php
/**
 * تتبع التشغيلات/الدفعات وتواريخ الصلاحية
 * يستخدم لإدارة ومتابعة الأصناف حسب رقم التشغيلة وتاريخ الصلاحية
 */
class ControllerInventoryBatchTracking extends Controller {
    private $error = array();

    /**
     * عرض صفحة تتبع التشغيلات/الدفعات
     */
    public function index() {
        $this->load->language('inventory/batch_tracking');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/batch_tracking');

        $this->getList();
    }

    /**
     * عرض قائمة الدفعات/التشغيلات
     */
    protected function getList() {
        if (isset($this->request->get['filter_product'])) {
            $filter_product = $this->request->get['filter_product'];
        } else {
            $filter_product = '';
        }

        if (isset($this->request->get['filter_batch_number'])) {
            $filter_batch_number = $this->request->get['filter_batch_number'];
        } else {
            $filter_batch_number = '';
        }

        if (isset($this->request->get['filter_branch'])) {
            $filter_branch = $this->request->get['filter_branch'];
        } else {
            $filter_branch = '';
        }

        if (isset($this->request->get['filter_expiry_from'])) {
            $filter_expiry_from = $this->request->get['filter_expiry_from'];
        } else {
            $filter_expiry_from = '';
        }

        if (isset($this->request->get['filter_expiry_to'])) {
            $filter_expiry_to = $this->request->get['filter_expiry_to'];
        } else {
            $filter_expiry_to = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'b.expiry_date';
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

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode($this->request->get['filter_product']);
        }

        if (isset($this->request->get['filter_batch_number'])) {
            $url .= '&filter_batch_number=' . urlencode($this->request->get['filter_batch_number']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_expiry_from'])) {
            $url .= '&filter_expiry_from=' . $this->request->get['filter_expiry_from'];
        }

        if (isset($this->request->get['filter_expiry_to'])) {
            $url .= '&filter_expiry_to=' . $this->request->get['filter_expiry_to'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/batch_tracking', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        $data['add'] = $this->url->link('inventory/batch_tracking/add', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['delete'] = $this->url->link('inventory/batch_tracking/delete', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['export'] = $this->url->link('inventory/batch_tracking/export', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['expiry_report'] = $this->url->link('inventory/batch_tracking/expiryReport', 'user_token=' . $this->session->data['user_token']);

        $filter_data = array(
            'filter_product'      => $filter_product,
            'filter_batch_number' => $filter_batch_number,
            'filter_branch'       => $filter_branch,
            'filter_expiry_from'  => $filter_expiry_from,
            'filter_expiry_to'    => $filter_expiry_to,
            'filter_status'       => $filter_status,
            'sort'                => $sort,
            'order'               => $order,
            'start'               => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'               => $this->config->get('config_limit_admin')
        );

        $batch_total = $this->model_inventory_batch_tracking->getTotalBatches($filter_data);
        $batches = $this->model_inventory_batch_tracking->getBatches($filter_data);

        $data['batches'] = array();

        foreach ($batches as $batch) {
            // حساب الأيام المتبقية للصلاحية
            $days_remaining = 0;
            $expiry_status = 'expired';
            
            if ($batch['expiry_date']) {
                $current_date = new DateTime();
                $expiry_date = new DateTime($batch['expiry_date']);
                $interval = $current_date->diff($expiry_date);
                $days_remaining = $interval->invert ? -$interval->days : $interval->days;
                
                // تحديد حالة الصلاحية
                if ($interval->invert) {
                    $expiry_status = 'expired';
                } elseif ($days_remaining <= $batch['expiry_warning_days']) {
                    $expiry_status = 'warning';
                } else {
                    $expiry_status = 'valid';
                }
            }
            
            $data['batches'][] = array(
                'batch_id'        => $batch['batch_id'],
                'product_name'    => $batch['product_name'],
                'product_id'      => $batch['product_id'],
                'batch_number'    => $batch['batch_number'],
                'branch_name'     => $batch['branch_name'],
                'branch_id'       => $batch['branch_id'],
                'quantity'        => $batch['quantity'],
                'unit_name'       => $batch['unit_name'],
                'manufacturing_date' => $batch['manufacturing_date'] ? date($this->language->get('date_format_short'), strtotime($batch['manufacturing_date'])) : '',
                'expiry_date'     => $batch['expiry_date'] ? date($this->language->get('date_format_short'), strtotime($batch['expiry_date'])) : '',
                'days_remaining'  => $days_remaining,
                'expiry_status'   => $expiry_status,
                'status'          => $batch['status'],
                'status_text'     => $this->language->get('text_status_' . $batch['status']),
                'edit'            => $this->url->link('inventory/batch_tracking/edit', 'user_token=' . $this->session->data['user_token'] . '&batch_id=' . $batch['batch_id'] . $url),
                'history'         => $this->url->link('inventory/batch_tracking/history', 'user_token=' . $this->session->data['user_token'] . '&batch_id=' . $batch['batch_id'] . $url)
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

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode($this->request->get['filter_product']);
        }

        if (isset($this->request->get['filter_batch_number'])) {
            $url .= '&filter_batch_number=' . urlencode($this->request->get['filter_batch_number']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_expiry_from'])) {
            $url .= '&filter_expiry_from=' . $this->request->get['filter_expiry_from'];
        }

        if (isset($this->request->get['filter_expiry_to'])) {
            $url .= '&filter_expiry_to=' . $this->request->get['filter_expiry_to'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_product'] = $this->url->link('inventory/batch_tracking', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url);
        $data['sort_batch_number'] = $this->url->link('inventory/batch_tracking', 'user_token=' . $this->session->data['user_token'] . '&sort=b.batch_number' . $url);
        $data['sort_branch'] = $this->url->link('inventory/batch_tracking', 'user_token=' . $this->session->data['user_token'] . '&sort=br.name' . $url);
        $data['sort_quantity'] = $this->url->link('inventory/batch_tracking', 'user_token=' . $this->session->data['user_token'] . '&sort=b.quantity' . $url);
        $data['sort_manufacturing_date'] = $this->url->link('inventory/batch_tracking', 'user_token=' . $this->session->data['user_token'] . '&sort=b.manufacturing_date' . $url);
        $data['sort_expiry_date'] = $this->url->link('inventory/batch_tracking', 'user_token=' . $this->session->data['user_token'] . '&sort=b.expiry_date' . $url);
        $data['sort_status'] = $this->url->link('inventory/batch_tracking', 'user_token=' . $this->session->data['user_token'] . '&sort=b.status' . $url);

        $url = '';

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode($this->request->get['filter_product']);
        }

        if (isset($this->request->get['filter_batch_number'])) {
            $url .= '&filter_batch_number=' . urlencode($this->request->get['filter_batch_number']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_expiry_from'])) {
            $url .= '&filter_expiry_from=' . $this->request->get['filter_expiry_from'];
        }

        if (isset($this->request->get['filter_expiry_to'])) {
            $url .= '&filter_expiry_to=' . $this->request->get['filter_expiry_to'];
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
        $pagination->total = $batch_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/batch_tracking', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($batch_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($batch_total - $this->config->get('config_limit_admin'))) ? $batch_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $batch_total, ceil($batch_total / $this->config->get('config_limit_admin')));

        $data['filter_product'] = $filter_product;
        $data['filter_batch_number'] = $filter_batch_number;
        $data['filter_branch'] = $filter_branch;
        $data['filter_expiry_from'] = $filter_expiry_from;
        $data['filter_expiry_to'] = $filter_expiry_to;
        $data['filter_status'] = $filter_status;

        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/batch_tracking_list', $data));
    }

    /**
     * إضافة دفعة/تشغيلة جديدة
     */
    public function add() {
        // Implementación pendiente
    }

    /**
     * تعديل دفعة/تشغيلة
     */
    public function edit() {
        // Implementación pendiente
    }

    /**
     * حذف دفعة/تشغيلة
     */
    public function delete() {
        // Implementación pendiente
    }

    /**
     * عرض تاريخ حركة الدفعة/التشغيلة
     */
    public function history() {
        // Implementación pendiente
    }

    /**
     * تقرير المنتجات قريبة انتهاء الصلاحية
     */
    public function expiryReport() {
        // Implementación pendiente
    }

    /**
     * تصدير بيانات الدفعات/التشغيلات
     */
    public function export() {
        // Implementación pendiente
    }
}
