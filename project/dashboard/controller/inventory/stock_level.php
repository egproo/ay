<?php
/**
 * إدارة مستويات المخزون (الحد الأدنى والحد الأقصى)
 * يستخدم لإدارة ومراقبة مستويات المخزون للمنتجات في مختلف الفروع
 */
class ControllerInventoryStockLevel extends Controller {
    private $error = array();

    /**
     * عرض صفحة إدارة مستويات المخزون
     */
    public function index() {
        $this->load->language('inventory/stock_level');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/stock_level');

        $this->getList();
    }

    /**
     * عرض قائمة مستويات المخزون
     */
    protected function getList() {
        if (isset($this->request->get['filter_product'])) {
            $filter_product = $this->request->get['filter_product'];
        } else {
            $filter_product = '';
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

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode($this->request->get['filter_product']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
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
            'href' => $this->url->link('inventory/stock_level', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        $data['add'] = $this->url->link('inventory/stock_level/add', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['delete'] = $this->url->link('inventory/stock_level/delete', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['export'] = $this->url->link('inventory/stock_level/export', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['reorder_report'] = $this->url->link('inventory/stock_level/reorderReport', 'user_token=' . $this->session->data['user_token']);
        $data['overstock_report'] = $this->url->link('inventory/stock_level/overstockReport', 'user_token=' . $this->session->data['user_token']);

        $filter_data = array(
            'filter_product'      => $filter_product,
            'filter_branch'       => $filter_branch,
            'filter_status'       => $filter_status,
            'sort'                => $sort,
            'order'               => $order,
            'start'               => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'               => $this->config->get('config_limit_admin')
        );

        $stock_level_total = $this->model_inventory_stock_level->getTotalStockLevels($filter_data);
        $stock_levels = $this->model_inventory_stock_level->getStockLevels($filter_data);

        $data['stock_levels'] = array();

        foreach ($stock_levels as $stock_level) {
            // حساب حالة المخزون
            $stock_status = 'normal';
            $current_stock = $stock_level['current_stock'];
            
            if ($current_stock <= $stock_level['reorder_point']) {
                $stock_status = 'low';
            } elseif ($current_stock >= $stock_level['maximum_stock']) {
                $stock_status = 'high';
            }
            
            $data['stock_levels'][] = array(
                'stock_level_id'  => $stock_level['stock_level_id'],
                'product_name'    => $stock_level['product_name'],
                'product_id'      => $stock_level['product_id'],
                'branch_name'     => $stock_level['branch_name'],
                'branch_id'       => $stock_level['branch_id'],
                'unit_name'       => $stock_level['unit_name'],
                'unit_id'         => $stock_level['unit_id'],
                'minimum_stock'   => $stock_level['minimum_stock'],
                'reorder_point'   => $stock_level['reorder_point'],
                'maximum_stock'   => $stock_level['maximum_stock'],
                'current_stock'   => $current_stock,
                'stock_status'    => $stock_status,
                'status'          => $stock_level['status'],
                'status_text'     => $stock_level['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'edit'            => $this->url->link('inventory/stock_level/edit', 'user_token=' . $this->session->data['user_token'] . '&stock_level_id=' . $stock_level['stock_level_id'] . $url)
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

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
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

        $data['sort_product'] = $this->url->link('inventory/stock_level', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url);
        $data['sort_branch'] = $this->url->link('inventory/stock_level', 'user_token=' . $this->session->data['user_token'] . '&sort=b.name' . $url);
        $data['sort_minimum'] = $this->url->link('inventory/stock_level', 'user_token=' . $this->session->data['user_token'] . '&sort=sl.minimum_stock' . $url);
        $data['sort_reorder'] = $this->url->link('inventory/stock_level', 'user_token=' . $this->session->data['user_token'] . '&sort=sl.reorder_point' . $url);
        $data['sort_maximum'] = $this->url->link('inventory/stock_level', 'user_token=' . $this->session->data['user_token'] . '&sort=sl.maximum_stock' . $url);
        $data['sort_current'] = $this->url->link('inventory/stock_level', 'user_token=' . $this->session->data['user_token'] . '&sort=current_stock' . $url);
        $data['sort_status'] = $this->url->link('inventory/stock_level', 'user_token=' . $this->session->data['user_token'] . '&sort=sl.status' . $url);

        $url = '';

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode($this->request->get['filter_product']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
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
        $pagination->total = $stock_level_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/stock_level', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($stock_level_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($stock_level_total - $this->config->get('config_limit_admin'))) ? $stock_level_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $stock_level_total, ceil($stock_level_total / $this->config->get('config_limit_admin')));

        $data['filter_product'] = $filter_product;
        $data['filter_branch'] = $filter_branch;
        $data['filter_status'] = $filter_status;

        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/stock_level_list', $data));
    }

    /**
     * إضافة مستوى مخزون جديد
     */
    public function add() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * تعديل مستوى مخزون
     */
    public function edit() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * حذف مستوى مخزون
     */
    public function delete() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * تقرير إعادة الطلب
     */
    public function reorderReport() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * تقرير المخزون الزائد
     */
    public function overstockReport() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * تصدير بيانات مستويات المخزون
     */
    public function export() {
        // سيتم تنفيذه لاحقًا
    }
}
