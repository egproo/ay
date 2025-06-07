<?php
/**
 * تحليل اتجاهات المخزون
 * يستخدم لتحليل حركة المخزون وتوقع الاتجاهات المستقبلية
 */
class ControllerReportInventoryTrends extends Controller {
    private $error = array();

    /**
     * عرض صفحة تحليل اتجاهات المخزون
     */
    public function index() {
        $this->load->language('report/inventory_trends');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('report/inventory_trends');

        $this->getList();
    }

    /**
     * عرض قائمة التحليلات المتاحة
     */
    protected function getList() {
        $data['user_token'] = $this->session->data['user_token'];

        $data['analyses'] = array();
        
        // تحليل حركة المخزون
        $data['analyses'][] = array(
            'name' => $this->language->get('text_movement_analysis'),
            'description' => $this->language->get('text_movement_analysis_desc'),
            'href' => $this->url->link('report/inventory_trends/movementAnalysis', 'user_token=' . $this->session->data['user_token'])
        );
        
        // تحليل معدل دوران المخزون
        $data['analyses'][] = array(
            'name' => $this->language->get('text_turnover_analysis'),
            'description' => $this->language->get('text_turnover_analysis_desc'),
            'href' => $this->url->link('report/inventory_trends/turnoverAnalysis', 'user_token=' . $this->session->data['user_token'])
        );
        
        // تحليل موسمية المخزون
        $data['analyses'][] = array(
            'name' => $this->language->get('text_seasonality_analysis'),
            'description' => $this->language->get('text_seasonality_analysis_desc'),
            'href' => $this->url->link('report/inventory_trends/seasonalityAnalysis', 'user_token=' . $this->session->data['user_token'])
        );
        
        // توقعات المخزون
        $data['analyses'][] = array(
            'name' => $this->language->get('text_forecast_analysis'),
            'description' => $this->language->get('text_forecast_analysis_desc'),
            'href' => $this->url->link('report/inventory_trends/forecastAnalysis', 'user_token=' . $this->session->data['user_token'])
        );

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('report/inventory_trends', 'user_token=' . $this->session->data['user_token'])
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('report/inventory_trends', $data));
    }

    /**
     * تحليل حركة المخزون
     */
    public function movementAnalysis() {
        $this->load->language('report/inventory_trends');
        $this->document->setTitle($this->language->get('heading_movement'));
        $this->load->model('report/inventory_trends');

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

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = date('Y-m-d', strtotime('-30 days'));
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = date('Y-m-d');
        }

        if (isset($this->request->get['filter_movement_type'])) {
            $filter_movement_type = $this->request->get['filter_movement_type'];
        } else {
            $filter_movement_type = '';
        }

        if (isset($this->request->get['filter_group'])) {
            $filter_group = $this->request->get['filter_group'];
        } else {
            $filter_group = 'day';
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

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_movement_type'])) {
            $url .= '&filter_movement_type=' . $this->request->get['filter_movement_type'];
        }

        if (isset($this->request->get['filter_group'])) {
            $url .= '&filter_group=' . $this->request->get['filter_group'];
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
            'href' => $this->url->link('report/inventory_trends', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_movement'),
            'href' => $this->url->link('report/inventory_trends/movementAnalysis', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        $data['export'] = $this->url->link('report/inventory_trends/exportMovement', 'user_token=' . $this->session->data['user_token'] . $url);

        $filter_data = array(
            'filter_product'      => $filter_product,
            'filter_branch'       => $filter_branch,
            'filter_date_start'   => $filter_date_start,
            'filter_date_end'     => $filter_date_end,
            'filter_movement_type' => $filter_movement_type,
            'filter_group'        => $filter_group,
            'start'               => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'               => $this->config->get('config_limit_admin')
        );

        $movement_total = $this->model_report_inventory_trends->getTotalMovements($filter_data);
        $movements = $this->model_report_inventory_trends->getMovements($filter_data);

        $data['movements'] = array();
        $data['chart_data'] = array();
        $data['chart_labels'] = array();
        $data['chart_in_data'] = array();
        $data['chart_out_data'] = array();
        $data['chart_balance_data'] = array();

        $total_in = 0;
        $total_out = 0;
        $total_balance = 0;

        foreach ($movements as $movement) {
            $data['movements'][] = array(
                'date'          => $movement['date'],
                'product_name'  => $movement['product_name'],
                'branch_name'   => $movement['branch_name'],
                'quantity_in'   => $movement['quantity_in'],
                'quantity_out'  => $movement['quantity_out'],
                'balance'       => $movement['balance'],
                'unit_name'     => $movement['unit_name']
            );

            $data['chart_labels'][] = $movement['date'];
            $data['chart_in_data'][] = $movement['quantity_in'];
            $data['chart_out_data'][] = $movement['quantity_out'];
            $data['chart_balance_data'][] = $movement['balance'];

            $total_in += $movement['quantity_in'];
            $total_out += $movement['quantity_out'];
            $total_balance = $movement['balance']; // آخر رصيد
        }

        $data['total_in'] = $total_in;
        $data['total_out'] = $total_out;
        $data['total_balance'] = $total_balance;

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        $data['movement_types'] = array(
            '' => $this->language->get('text_all_movements'),
            'in' => $this->language->get('text_in'),
            'out' => $this->language->get('text_out'),
            'adjustment' => $this->language->get('text_adjustment'),
            'transfer' => $this->language->get('text_transfer')
        );

        $data['groups'] = array(
            'day' => $this->language->get('text_day'),
            'week' => $this->language->get('text_week'),
            'month' => $this->language->get('text_month'),
            'quarter' => $this->language->get('text_quarter'),
            'year' => $this->language->get('text_year')
        );

        $url = '';

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode($this->request->get['filter_product']);
        }

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_movement_type'])) {
            $url .= '&filter_movement_type=' . $this->request->get['filter_movement_type'];
        }

        if (isset($this->request->get['filter_group'])) {
            $url .= '&filter_group=' . $this->request->get['filter_group'];
        }

        $pagination = new Pagination();
        $pagination->total = $movement_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('report/inventory_trends/movementAnalysis', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($movement_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($movement_total - $this->config->get('config_limit_admin'))) ? $movement_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $movement_total, ceil($movement_total / $this->config->get('config_limit_admin')));

        $data['filter_product'] = $filter_product;
        $data['filter_branch'] = $filter_branch;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['filter_movement_type'] = $filter_movement_type;
        $data['filter_group'] = $filter_group;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('report/inventory_movement', $data));
    }

    /**
     * تحليل معدل دوران المخزون
     */
    public function turnoverAnalysis() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * تحليل موسمية المخزون
     */
    public function seasonalityAnalysis() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * توقعات المخزون
     */
    public function forecastAnalysis() {
        // سيتم تنفيذه لاحقًا
    }

    /**
     * تصدير بيانات حركة المخزون
     */
    public function exportMovement() {
        // سيتم تنفيذه لاحقًا
    }
}
