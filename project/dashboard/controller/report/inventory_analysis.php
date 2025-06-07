<?php
/**
 * تقارير وتحليلات المخزون
 * يستخدم لعرض تقارير وتحليلات متقدمة للمخزون
 */
class ControllerReportInventoryAnalysis extends Controller {
    private $error = array();

    /**
     * عرض صفحة تقارير وتحليلات المخزون
     */
    public function index() {
        $this->load->language('report/inventory_analysis');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('report/inventory');

        $this->getList();
    }

    /**
     * تقرير تقييم المخزون
     */
    public function valuation() {
        $this->load->language('report/inventory_analysis');
        $this->document->setTitle($this->language->get('heading_valuation'));
        $this->load->model('report/inventory');

        if (isset($this->request->get['filter_branch'])) {
            $filter_branch = $this->request->get['filter_branch'];
        } else {
            $filter_branch = '';
        }

        if (isset($this->request->get['filter_category'])) {
            $filter_category = $this->request->get['filter_category'];
        } else {
            $filter_category = '';
        }

        if (isset($this->request->get['filter_product'])) {
            $filter_product = $this->request->get['filter_product'];
        } else {
            $filter_product = '';
        }

        if (isset($this->request->get['filter_date'])) {
            $filter_date = $this->request->get['filter_date'];
        } else {
            $filter_date = date('Y-m-d');
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'p.name';
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

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_category'])) {
            $url .= '&filter_category=' . $this->request->get['filter_category'];
        }

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode($this->request->get['filter_product']);
        }

        if (isset($this->request->get['filter_date'])) {
            $url .= '&filter_date=' . $this->request->get['filter_date'];
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
            'href' => $this->url->link('report/inventory_analysis', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_valuation'),
            'href' => $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        $data['export'] = $this->url->link('report/inventory_analysis/export_valuation', 'user_token=' . $this->session->data['user_token'] . $url);

        $filter_data = array(
            'filter_branch'    => $filter_branch,
            'filter_category'  => $filter_category,
            'filter_product'   => $filter_product,
            'filter_date'      => $filter_date,
            'sort'             => $sort,
            'order'            => $order,
            'start'            => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'            => $this->config->get('config_limit_admin')
        );

        $data['inventory_items'] = array();
        $inventory_total = $this->model_report_inventory->getTotalInventoryValuation($filter_data);
        $inventory_items = $this->model_report_inventory->getInventoryValuation($filter_data);

        $data['total_value'] = 0;

        foreach ($inventory_items as $item) {
            $data['inventory_items'][] = array(
                'product_id'    => $item['product_id'],
                'name'          => $item['name'],
                'model'         => $item['model'],
                'sku'           => $item['sku'],
                'category'      => $item['category'],
                'branch'        => $item['branch_name'],
                'quantity'      => $item['quantity'],
                'unit'          => $item['unit_name'],
                'cost'          => $this->currency->format($item['average_cost'], $this->config->get('config_currency')),
                'value'         => $this->currency->format($item['total_value'], $this->config->get('config_currency')),
                'last_movement' => date($this->language->get('date_format_short'), strtotime($item['last_movement_date']))
            );

            $data['total_value'] += $item['total_value'];
        }

        $data['total_value_formatted'] = $this->currency->format($data['total_value'], $this->config->get('config_currency'));

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('branch/branch');
        $data['branches'] = $this->model_branch_branch->getBranches();

        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories(array('sort' => 'name'));

        $url = '';

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_category'])) {
            $url .= '&filter_category=' . $this->request->get['filter_category'];
        }

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode($this->request->get['filter_product']);
        }

        if (isset($this->request->get['filter_date'])) {
            $url .= '&filter_date=' . $this->request->get['filter_date'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'] . '&sort=p.name' . $url);
        $data['sort_model'] = $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url);
        $data['sort_category'] = $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'] . '&sort=category' . $url);
        $data['sort_branch'] = $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'] . '&sort=b.name' . $url);
        $data['sort_quantity'] = $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'] . '&sort=pi.quantity' . $url);
        $data['sort_cost'] = $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'] . '&sort=pi.average_cost' . $url);
        $data['sort_value'] = $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'] . '&sort=total_value' . $url);
        $data['sort_last_movement'] = $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'] . '&sort=last_movement_date' . $url);

        $url = '';

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_category'])) {
            $url .= '&filter_category=' . $this->request->get['filter_category'];
        }

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode($this->request->get['filter_product']);
        }

        if (isset($this->request->get['filter_date'])) {
            $url .= '&filter_date=' . $this->request->get['filter_date'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $inventory_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($inventory_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($inventory_total - $this->config->get('config_limit_admin'))) ? $inventory_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $inventory_total, ceil($inventory_total / $this->config->get('config_limit_admin')));

        $data['filter_branch'] = $filter_branch;
        $data['filter_category'] = $filter_category;
        $data['filter_product'] = $filter_product;
        $data['filter_date'] = $filter_date;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('report/inventory_valuation', $data));
    }

    /**
     * تقرير تحليل دوران المخزون
     */
    public function turnover() {
        // Implementación pendiente
    }

    /**
     * تقرير المخزون الراكد
     */
    public function slow_moving() {
        // Implementación pendiente
    }

    /**
     * تقرير تنبيهات المخزون
     */
    public function alerts() {
        // Implementación pendiente
    }

    /**
     * تصدير تقرير تقييم المخزون
     */
    public function export_valuation() {
        $this->load->language('report/inventory_analysis');
        $this->load->model('report/inventory');

        if (isset($this->request->get['filter_branch'])) {
            $filter_branch = $this->request->get['filter_branch'];
        } else {
            $filter_branch = '';
        }

        if (isset($this->request->get['filter_category'])) {
            $filter_category = $this->request->get['filter_category'];
        } else {
            $filter_category = '';
        }

        if (isset($this->request->get['filter_product'])) {
            $filter_product = $this->request->get['filter_product'];
        } else {
            $filter_product = '';
        }

        if (isset($this->request->get['filter_date'])) {
            $filter_date = $this->request->get['filter_date'];
        } else {
            $filter_date = date('Y-m-d');
        }

        $filter_data = array(
            'filter_branch'    => $filter_branch,
            'filter_category'  => $filter_category,
            'filter_product'   => $filter_product,
            'filter_date'      => $filter_date,
            'sort'             => 'p.name',
            'order'            => 'ASC',
            'start'            => 0,
            'limit'            => 10000 // تصدير كل البيانات
        );

        $inventory_items = $this->model_report_inventory->getInventoryValuation($filter_data);

        // إعداد عناوين الأعمدة
        $headers = array(
            $this->language->get('column_product'),
            $this->language->get('column_model'),
            $this->language->get('column_sku'),
            $this->language->get('column_category'),
            $this->language->get('column_branch'),
            $this->language->get('column_quantity'),
            $this->language->get('column_unit'),
            $this->language->get('column_cost'),
            $this->language->get('column_value'),
            $this->language->get('column_last_movement')
        );

        // إعداد البيانات للتصدير
        $export_data = array();
        $total_value = 0;

        foreach ($inventory_items as $item) {
            $export_data[] = array(
                $item['name'],
                $item['model'],
                $item['sku'],
                $item['category'],
                $item['branch_name'],
                $item['quantity'],
                $item['unit_name'],
                $this->currency->format($item['average_cost'], $this->config->get('config_currency'), '', false),
                $this->currency->format($item['total_value'], $this->config->get('config_currency'), '', false),
                date($this->language->get('date_format_short'), strtotime($item['last_movement_date']))
            );

            $total_value += $item['total_value'];
        }

        // إعداد البيانات الوصفية
        $metadata = array(
            $this->language->get('entry_date') => $filter_date,
            $this->language->get('text_total_value') => $this->currency->format($total_value, $this->config->get('config_currency'))
        );

        // إضافة معلومات الفلتر إذا كانت موجودة
        if ($filter_branch) {
            $this->load->model('branch/branch');
            $branch_info = $this->model_branch_branch->getBranch($filter_branch);
            if ($branch_info) {
                $metadata[$this->language->get('entry_branch')] = $branch_info['name'];
            }
        }

        if ($filter_category) {
            $this->load->model('catalog/category');
            $category_info = $this->model_catalog_category->getCategory($filter_category);
            if ($category_info) {
                $metadata[$this->language->get('entry_category')] = $category_info['name'];
            }
        }

        if ($filter_product) {
            $metadata[$this->language->get('entry_product')] = $filter_product;
        }

        // تعيين عرض الأعمدة
        $column_widths = array(
            0 => 30, // المنتج
            1 => 15, // الموديل
            2 => 15, // SKU
            3 => 20, // التصنيف
            4 => 20, // الفرع
            5 => 10, // الكمية
            6 => 10, // الوحدة
            7 => 15, // التكلفة
            8 => 15, // القيمة
            9 => 15  // آخر حركة
        );

        // تعيين تنسيقات الأعمدة
        $column_formats = array(
            5 => '#,##0.0000', // الكمية
            7 => '#,##0.00', // التكلفة
            8 => '#,##0.00'  // القيمة
        );

        // تصدير البيانات إلى CSV
        $filename = 'inventory_valuation_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // إضافة BOM للتعامل مع الأحرف العربية
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // إضافة عنوان التقرير
        fputcsv($output, array($this->language->get('heading_valuation')));

        // إضافة البيانات الوصفية
        foreach ($metadata as $key => $value) {
            fputcsv($output, array($key . ': ' . $value));
        }

        // إضافة سطر فارغ
        fputcsv($output, array(''));

        // إضافة عناوين الأعمدة
        fputcsv($output, $headers);

        // إضافة البيانات
        foreach ($export_data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * عرض صفحة التقارير الرئيسية
     */
    protected function getList() {
        $data['user_token'] = $this->session->data['user_token'];

        $data['reports'] = array();

        // تقرير تقييم المخزون
        $data['reports'][] = array(
            'name' => $this->language->get('text_inventory_valuation'),
            'description' => $this->language->get('text_inventory_valuation_desc'),
            'href' => $this->url->link('report/inventory_analysis/valuation', 'user_token=' . $this->session->data['user_token'])
        );

        // تقرير تحليل دوران المخزون
        $data['reports'][] = array(
            'name' => $this->language->get('text_inventory_turnover'),
            'description' => $this->language->get('text_inventory_turnover_desc'),
            'href' => $this->url->link('report/inventory_analysis/turnover', 'user_token=' . $this->session->data['user_token'])
        );

        // تقرير المخزون الراكد
        $data['reports'][] = array(
            'name' => $this->language->get('text_slow_moving'),
            'description' => $this->language->get('text_slow_moving_desc'),
            'href' => $this->url->link('report/inventory_analysis/slow_moving', 'user_token=' . $this->session->data['user_token'])
        );

        // تقرير تنبيهات المخزون
        $data['reports'][] = array(
            'name' => $this->language->get('text_inventory_alerts'),
            'description' => $this->language->get('text_inventory_alerts_desc'),
            'href' => $this->url->link('report/inventory_analysis/alerts', 'user_token=' . $this->session->data['user_token'])
        );

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('report/inventory_analysis', 'user_token=' . $this->session->data['user_token'])
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('report/inventory_analysis', $data));
    }
}
