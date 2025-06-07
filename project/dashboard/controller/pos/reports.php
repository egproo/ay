<?php
class ControllerPosReports extends Controller {
    public function index() {
        $this->load->language('pos/reports');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['user_token'] = $this->session->data['user_token'];

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/reports', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['reports'] = array();
        
        $data['reports'][] = array(
            'name' => $this->language->get('text_sales_report'),
            'href' => $this->url->link('pos/reports/sales', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['reports'][] = array(
            'name' => $this->language->get('text_cashier_report'),
            'href' => $this->url->link('pos/reports/cashier', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['reports'][] = array(
            'name' => $this->language->get('text_product_report'),
            'href' => $this->url->link('pos/reports/product', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['reports'][] = array(
            'name' => $this->language->get('text_tax_report'),
            'href' => $this->url->link('pos/reports/tax', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['reports'][] = array(
            'name' => $this->language->get('text_payment_report'),
            'href' => $this->url->link('pos/reports/payment', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/reports', $data));
    }

    public function sales() {
        $this->load->language('pos/reports');
        $this->document->setTitle($this->language->get('heading_title_sales'));
        $this->load->model('pos/reports');

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = date('Y-m-d', strtotime('-7 days'));
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = date('Y-m-d');
        }

        if (isset($this->request->get['filter_branch_id'])) {
            $filter_branch_id = $this->request->get['filter_branch_id'];
        } else {
            $filter_branch_id = 0;
        }

        if (isset($this->request->get['filter_terminal_id'])) {
            $filter_terminal_id = $this->request->get['filter_terminal_id'];
        } else {
            $filter_terminal_id = 0;
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/reports', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_sales'),
            'href' => $this->url->link('pos/reports/sales', 'user_token=' . $this->session->data['user_token'], true)
        );

        $filter_data = array(
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'filter_branch_id'  => $filter_branch_id,
            'filter_terminal_id' => $filter_terminal_id,
            'start'             => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'             => $this->config->get('config_limit_admin')
        );

        $results = $this->model_pos_reports->getSalesReport($filter_data);
        $sales_total = $this->model_pos_reports->getTotalSales($filter_data);

        $data['sales'] = array();
        
        foreach ($results as $result) {
            $data['sales'][] = array(
                'order_id'      => $result['order_id'],
                'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'customer_name' => $result['customer_name'],
                'products'      => $result['products'],
                'total'         => $this->currency->format($result['total']??0, $this->config->get('config_currency')),
                'user_name'     => $result['user_name'],
                'branch_name'   => $result['branch_name'],
                'terminal_name' => $result['terminal_name'],
                'payment_method' => $result['payment_method'],
                'view'          => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'], true)
            );
        }

        // Get summary data
        $summary = $this->model_pos_reports->getSalesSummary($filter_data);
        
        $data['summary'] = array(
            'total_sales'  => $this->currency->format($summary['total_sales']??0, $this->config->get('config_currency')),
            'total_orders' => $summary['total_orders'],
            'avg_order'    => $summary['total_orders'] ? $this->currency->format($summary['total_sales']??0 / $summary['total_orders']??0, $this->config->get('config_currency')) : $this->currency->format(0, $this->config->get('config_currency'))
        );

        // Get payment method breakdown
        $payment_methods = $this->model_pos_reports->getPaymentMethodSummary($filter_data);
        
        $data['payment_methods'] = array();
        
        foreach ($payment_methods as $method) {
            $data['payment_methods'][] = array(
                'payment_method' => $method['payment_method'],
                'total'          => $this->currency->format($method['total']??0, $this->config->get('config_currency')),
                'count'          => $method['count'],
                'percentage'     => $summary['total_sales'] ? round(($method['total']??0 / $summary['total_sales']??0) * 100, 2) . '%' : '0%'
            );
        }

        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        $this->load->model('pos/terminal');
        $data['terminals'] = $this->model_pos_terminal->getTerminals();

        $url = '';

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_branch_id'])) {
            $url .= '&filter_branch_id=' . $this->request->get['filter_branch_id'];
        }

        if (isset($this->request->get['filter_terminal_id'])) {
            $url .= '&filter_terminal_id=' . $this->request->get['filter_terminal_id'];
        }

        $pagination = new Pagination();
        $pagination->total = $sales_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('pos/reports/sales', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($sales_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($sales_total - $this->config->get('config_limit_admin'))) ? $sales_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $sales_total, ceil($sales_total / $this->config->get('config_limit_admin')));

        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['filter_branch_id'] = $filter_branch_id;
        $data['filter_terminal_id'] = $filter_terminal_id;

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/report_sales', $data));
    }

    public function cashier() {
        $this->load->language('pos/reports');
        $this->document->setTitle($this->language->get('heading_title_cashier'));
        $this->load->model('pos/reports');

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = date('Y-m-d', strtotime('-7 days'));
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = date('Y-m-d');
        }

        if (isset($this->request->get['filter_user_id'])) {
            $filter_user_id = $this->request->get['filter_user_id'];
        } else {
            $filter_user_id = 0;
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/reports', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_cashier'),
            'href' => $this->url->link('pos/reports/cashier', 'user_token=' . $this->session->data['user_token'], true)
        );

        $filter_data = array(
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'filter_user_id'    => $filter_user_id,
            'start'             => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'             => $this->config->get('config_limit_admin')
        );

        $results = $this->model_pos_reports->getCashierReport($filter_data);
        $shifts_total = $this->model_pos_reports->getTotalCashierShifts($filter_data);

        $data['shifts'] = array();
        
        foreach ($results as $result) {
            $data['shifts'][] = array(
                'shift_id'       => $result['shift_id'],
                'user_name'      => $result['user_name'],
                'branch_name'    => $result['branch_name'],
                'terminal_name'  => $result['terminal_name'],
                'start_time'     => date($this->language->get('datetime_format'), strtotime($result['start_time'])),
                'end_time'       => $result['end_time'] ? date($this->language->get('datetime_format'), strtotime($result['end_time'])) : $this->language->get('text_ongoing'),
                'duration'       => $result['duration'],
                'sales_count'    => $result['sales_count'],
                'sales_total'    => $this->currency->format($result['sales_total']??0, $this->config->get('config_currency')),
                'starting_cash'  => $this->currency->format($result['starting_cash']??0, $this->config->get('config_currency')),
                'ending_cash'    => $result['ending_cash'] ? $this->currency->format($result['ending_cash']??0, $this->config->get('config_currency')) : '-',
                'cash_difference' => $result['cash_difference'] ? $this->currency->format($result['cash_difference']??0, $this->config->get('config_currency')) : '-',
                'status'         => $this->language->get('text_status_' . $result['status']),
                'view'           => $this->url->link('pos/shift/view', 'user_token=' . $this->session->data['user_token'] . '&shift_id=' . $result['shift_id'], true)
            );
        }

        // Get summary data by user
        $user_summary = $this->model_pos_reports->getCashierSummary($filter_data);
        
        $data['user_summary'] = array();
        
        foreach ($user_summary as $summary) {
            $data['user_summary'][] = array(
                'user_id'       => $summary['user_id'],
                'user_name'     => $summary['user_name'],
                'shifts_count'  => $summary['shifts_count'],
                'total_hours'   => round($summary['total_minutes'] / 60, 2),
                'sales_count'   => $summary['sales_count'],
                'sales_total'   => $this->currency->format($summary['sales_total']??0, $this->config->get('config_currency')),
                'avg_sale'      => $summary['sales_count'] ? $this->currency->format($summary['sales_total'] ?? 0 / $summary['sales_count'] ?? 0, $this->config->get('config_currency')) : $this->currency->format(0, $this->config->get('config_currency')),
                'sales_per_hour' => $summary['total_minutes'] > 0 ? round(($summary['sales_count'] / ($summary['total_minutes'] / 60)), 2) : 0
            );
        }

        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();

        $url = '';

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_user_id'])) {
            $url .= '&filter_user_id=' . $this->request->get['filter_user_id'];
        }

        $pagination = new Pagination();
        $pagination->total = $shifts_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('pos/reports/cashier', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($shifts_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($shifts_total - $this->config->get('config_limit_admin'))) ? $shifts_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $shifts_total, ceil($shifts_total / $this->config->get('config_limit_admin')));

        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['filter_user_id'] = $filter_user_id;

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/report_cashier', $data));
    }

    public function product() {
        $this->load->language('pos/reports');
        $this->document->setTitle($this->language->get('heading_title_product'));
        $this->load->model('pos/reports');

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

        if (isset($this->request->get['filter_category_id'])) {
            $filter_category_id = $this->request->get['filter_category_id'];
        } else {
            $filter_category_id = 0;
        }

        if (isset($this->request->get['filter_branch_id'])) {
            $filter_branch_id = $this->request->get['filter_branch_id'];
        } else {
            $filter_branch_id = 0;
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'quantity';
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

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('pos/reports', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_product'),
            'href' => $this->url->link('pos/reports/product', 'user_token=' . $this->session->data['user_token'], true)
        );

        $filter_data = array(
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'filter_category_id' => $filter_category_id,
            'filter_branch_id'  => $filter_branch_id,
            'sort'              => $sort,
            'order'             => $order,
            'start'             => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'             => $this->config->get('config_limit_admin')
        );

        $results = $this->model_pos_reports->getProductReport($filter_data);
        $products_total = $this->model_pos_reports->getTotalProductReport($filter_data);

        $data['products'] = array();
        
        foreach ($results as $result) {
            $data['products'][] = array(
                'product_id'  => $result['product_id'],
                'name'        => $result['name'],
                'model'       => $result['model'],
                'category'    => $result['category'],
                'quantity'    => $result['quantity'],
                'total'       => $this->currency->format($result['total']??0, $this->config->get('config_currency')),
                'avg_price'   => $this->currency->format($result['avg_price']??0, $this->config->get('config_currency'))
            );
        }

        // Get top categories
        $top_categories = $this->model_pos_reports->getTopCategories($filter_data);
        
        $data['categories_chart'] = array(
            'labels' => array_column($top_categories, 'name'),
            'data'   => array_column($top_categories, 'quantity')
        );

        // Get daily sales
        $daily_sales = $this->model_pos_reports->getDailySales($filter_data);
        
        $data['daily_chart'] = array(
            'labels' => array_column($daily_sales, 'date'),
            'data'   => array_column($daily_sales, 'total')
        );

        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories();

        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        $url = '';

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_category_id'])) {
            $url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
        }

        if (isset($this->request->get['filter_branch_id'])) {
            $url .= '&filter_branch_id=' . $this->request->get['filter_branch_id'];
        }

        $pagination = new Pagination();
        $pagination->total = $products_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('pos/reports/product', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($products_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($products_total - $this->config->get('config_limit_admin'))) ? $products_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $products_total, ceil($products_total / $this->config->get('config_limit_admin')));

        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['filter_category_id'] = $filter_category_id;
        $data['filter_branch_id'] = $filter_branch_id;
        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['user_token'] = $this->session->data['user_token'];

        // Add Chart.js for nice charts
        $this->document->addStyle('view/javascript/chart.js/Chart.min.css');
        $this->document->addScript('view/javascript/chart.js/Chart.min.js');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/report_product', $data));
    }
}