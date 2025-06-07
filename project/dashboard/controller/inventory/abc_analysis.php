<?php
/**
 * تحليل ABC للمخزون
 * يستخدم لتصنيف المنتجات حسب أهميتها (القيمة، المبيعات، الربحية)
 */
class ControllerInventoryAbcAnalysis extends Controller {
    private $error = array();

    /**
     * عرض صفحة تحليل ABC
     */
    public function index() {
        $this->load->language('inventory/abc_analysis');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('inventory/abc_analysis');

        $this->getList();
    }

    /**
     * تحليل المنتجات حسب القيمة
     */
    public function value() {
        $this->load->language('inventory/abc_analysis');
        $this->document->setTitle($this->language->get('heading_value'));
        $this->load->model('inventory/abc_analysis');

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

        if (isset($this->request->get['filter_date'])) {
            $filter_date = $this->request->get['filter_date'];
        } else {
            $filter_date = date('Y-m-d');
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'total_value';
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

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_category'])) {
            $url .= '&filter_category=' . $this->request->get['filter_category'];
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
            'href' => $this->url->link('inventory/abc_analysis', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_value'),
            'href' => $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . $url)
        );

        $data['export'] = $this->url->link('inventory/abc_analysis/export_value', 'user_token=' . $this->session->data['user_token'] . $url);

        $filter_data = array(
            'filter_branch'    => $filter_branch,
            'filter_category'  => $filter_category,
            'filter_date'      => $filter_date,
            'sort'             => $sort,
            'order'            => $order,
            'start'            => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'            => $this->config->get('config_limit_admin')
        );

        $data['products'] = array();
        $product_total = $this->model_inventory_abc_analysis->getTotalProductsByValue($filter_data);
        $products = $this->model_inventory_abc_analysis->getProductsByValue($filter_data);

        $data['total_value'] = 0;
        $data['total_items'] = $product_total;

        // حساب إجمالي القيمة
        $all_products = $this->model_inventory_abc_analysis->getProductsByValue(array(
            'filter_branch'    => $filter_branch,
            'filter_category'  => $filter_category,
            'filter_date'      => $filter_date,
            'sort'             => 'total_value',
            'order'            => 'DESC',
            'start'            => 0,
            'limit'            => 10000
        ));

        $total_inventory_value = 0;
        foreach ($all_products as $product) {
            $total_inventory_value += $product['total_value'];
        }

        // حساب النسب المئوية التراكمية وتحديد فئة ABC
        $cumulative_value = 0;
        $cumulative_items = 0;

        foreach ($all_products as $key => $product) {
            $cumulative_value += $product['total_value'];
            $cumulative_items++;

            $value_percentage = ($product['total_value'] / $total_inventory_value) * 100;
            $cumulative_value_percentage = ($cumulative_value / $total_inventory_value) * 100;
            $cumulative_items_percentage = ($cumulative_items / count($all_products)) * 100;

            // تحديد فئة ABC
            if ($cumulative_value_percentage <= 80) {
                $all_products[$key]['abc_class'] = 'A';
            } elseif ($cumulative_value_percentage <= 95) {
                $all_products[$key]['abc_class'] = 'B';
            } else {
                $all_products[$key]['abc_class'] = 'C';
            }

            $all_products[$key]['value_percentage'] = $value_percentage;
            $all_products[$key]['cumulative_value_percentage'] = $cumulative_value_percentage;
            $all_products[$key]['cumulative_items_percentage'] = $cumulative_items_percentage;
        }

        // تحديث البيانات للعرض
        foreach ($products as $product) {
            foreach ($all_products as $all_product) {
                if ($product['product_id'] == $all_product['product_id']) {
                    $data['products'][] = array(
                        'product_id'                  => $product['product_id'],
                        'name'                        => $product['name'],
                        'model'                       => $product['model'],
                        'sku'                         => $product['sku'],
                        'category'                    => $product['category'],
                        'quantity'                    => $product['quantity'],
                        'unit'                        => $product['unit_name'],
                        'cost'                        => $this->currency->format($product['average_cost'], $this->config->get('config_currency')),
                        'value'                       => $this->currency->format($product['total_value'], $this->config->get('config_currency')),
                        'value_percentage'            => number_format($all_product['value_percentage'], 2) . '%',
                        'cumulative_value_percentage' => number_format($all_product['cumulative_value_percentage'], 2) . '%',
                        'cumulative_items_percentage' => number_format($all_product['cumulative_items_percentage'], 2) . '%',
                        'abc_class'                   => $all_product['abc_class']
                    );

                    $data['total_value'] += $product['total_value'];
                    break;
                }
            }
        }

        $data['total_value_formatted'] = $this->currency->format($data['total_value'], $this->config->get('config_currency'));

        // Calcular estadísticas para cada clase ABC
        $data['class_a_items'] = 0;
        $data['class_b_items'] = 0;
        $data['class_c_items'] = 0;
        $data['class_a_value'] = 0;
        $data['class_b_value'] = 0;
        $data['class_c_value'] = 0;

        foreach ($all_products as $product) {
            if ($product['abc_class'] == 'A') {
                $data['class_a_items']++;
                $data['class_a_value'] += $product['total_value'];
            } elseif ($product['abc_class'] == 'B') {
                $data['class_b_items']++;
                $data['class_b_value'] += $product['total_value'];
            } else {
                $data['class_c_items']++;
                $data['class_c_value'] += $product['total_value'];
            }
        }

        $data['class_a_items_percentage'] = number_format(($data['class_a_items'] / count($all_products)) * 100, 2) . '%';
        $data['class_b_items_percentage'] = number_format(($data['class_b_items'] / count($all_products)) * 100, 2) . '%';
        $data['class_c_items_percentage'] = number_format(($data['class_c_items'] / count($all_products)) * 100, 2) . '%';

        $data['class_a_value_percentage'] = number_format(($data['class_a_value'] / $total_inventory_value) * 100, 2) . '%';
        $data['class_b_value_percentage'] = number_format(($data['class_b_value'] / $total_inventory_value) * 100, 2) . '%';
        $data['class_c_value_percentage'] = number_format(($data['class_c_value'] / $total_inventory_value) * 100, 2) . '%';

        $data['class_a_value_formatted'] = $this->currency->format($data['class_a_value'], $this->config->get('config_currency'));
        $data['class_b_value_formatted'] = $this->currency->format($data['class_b_value'], $this->config->get('config_currency'));
        $data['class_c_value_formatted'] = $this->currency->format($data['class_c_value'], $this->config->get('config_currency'));

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

        $data['sort_name'] = $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . '&sort=p.name' . $url);
        $data['sort_model'] = $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url);
        $data['sort_category'] = $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . '&sort=category' . $url);
        $data['sort_quantity'] = $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . '&sort=pi.quantity' . $url);
        $data['sort_cost'] = $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . '&sort=pi.average_cost' . $url);
        $data['sort_value'] = $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . '&sort=total_value' . $url);
        $data['sort_value_percentage'] = $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . '&sort=value_percentage' . $url);
        $data['sort_cumulative_value'] = $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . '&sort=cumulative_value_percentage' . $url);
        $data['sort_abc_class'] = $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . '&sort=abc_class' . $url);

        $url = '';

        if (isset($this->request->get['filter_branch'])) {
            $url .= '&filter_branch=' . $this->request->get['filter_branch'];
        }

        if (isset($this->request->get['filter_category'])) {
            $url .= '&filter_category=' . $this->request->get['filter_category'];
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
        $pagination->total = $product_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

        $data['filter_branch'] = $filter_branch;
        $data['filter_category'] = $filter_category;
        $data['filter_date'] = $filter_date;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/abc_analysis_value', $data));
    }

    /**
     * تصدير تحليل ABC حسب القيمة
     */
    public function export_value() {
        $this->load->language('inventory/abc_analysis');
        $this->load->model('inventory/abc_analysis');

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

        if (isset($this->request->get['filter_date'])) {
            $filter_date = $this->request->get['filter_date'];
        } else {
            $filter_date = date('Y-m-d');
        }

        $filter_data = array(
            'filter_branch'    => $filter_branch,
            'filter_category'  => $filter_category,
            'filter_date'      => $filter_date,
            'sort'             => 'total_value',
            'order'            => 'DESC',
            'start'            => 0,
            'limit'            => 10000 // تصدير كل البيانات
        );

        $products = $this->model_inventory_abc_analysis->getProductsByValue($filter_data);

        // حساب إجمالي القيمة
        $total_inventory_value = 0;
        foreach ($products as $product) {
            $total_inventory_value += $product['total_value'];
        }

        // حساب النسب المئوية التراكمية وتحديد فئة ABC
        $cumulative_value = 0;
        $cumulative_items = 0;

        foreach ($products as $key => $product) {
            $cumulative_value += $product['total_value'];
            $cumulative_items++;

            $value_percentage = ($product['total_value'] / $total_inventory_value) * 100;
            $cumulative_value_percentage = ($cumulative_value / $total_inventory_value) * 100;
            $cumulative_items_percentage = ($cumulative_items / count($products)) * 100;

            // تحديد فئة ABC
            if ($cumulative_value_percentage <= 80) {
                $products[$key]['abc_class'] = 'A';
            } elseif ($cumulative_value_percentage <= 95) {
                $products[$key]['abc_class'] = 'B';
            } else {
                $products[$key]['abc_class'] = 'C';
            }

            $products[$key]['value_percentage'] = $value_percentage;
            $products[$key]['cumulative_value_percentage'] = $cumulative_value_percentage;
            $products[$key]['cumulative_items_percentage'] = $cumulative_items_percentage;
        }

        // إعداد عناوين الأعمدة
        $headers = array(
            $this->language->get('column_product'),
            $this->language->get('column_model'),
            $this->language->get('column_sku'),
            $this->language->get('column_category'),
            $this->language->get('column_quantity'),
            $this->language->get('column_unit'),
            $this->language->get('column_cost'),
            $this->language->get('column_value'),
            $this->language->get('column_value_percentage'),
            $this->language->get('column_cumulative_value'),
            $this->language->get('column_cumulative_items'),
            $this->language->get('column_abc_class')
        );

        // إعداد البيانات للتصدير
        $export_data = array();

        foreach ($products as $product) {
            $export_data[] = array(
                $product['name'],
                $product['model'],
                $product['sku'],
                $product['category'],
                $product['quantity'],
                $product['unit_name'],
                $this->currency->format($product['average_cost'], $this->config->get('config_currency'), '', false),
                $this->currency->format($product['total_value'], $this->config->get('config_currency'), '', false),
                number_format($product['value_percentage'], 2) . '%',
                number_format($product['cumulative_value_percentage'], 2) . '%',
                number_format($product['cumulative_items_percentage'], 2) . '%',
                $product['abc_class']
            );
        }

        // حساب إحصائيات كل فئة
        $class_a_items = 0;
        $class_b_items = 0;
        $class_c_items = 0;
        $class_a_value = 0;
        $class_b_value = 0;
        $class_c_value = 0;

        foreach ($products as $product) {
            if ($product['abc_class'] == 'A') {
                $class_a_items++;
                $class_a_value += $product['total_value'];
            } elseif ($product['abc_class'] == 'B') {
                $class_b_items++;
                $class_b_value += $product['total_value'];
            } else {
                $class_c_items++;
                $class_c_value += $product['total_value'];
            }
        }

        $class_a_items_percentage = number_format(($class_a_items / count($products)) * 100, 2) . '%';
        $class_b_items_percentage = number_format(($class_b_items / count($products)) * 100, 2) . '%';
        $class_c_items_percentage = number_format(($class_c_items / count($products)) * 100, 2) . '%';

        $class_a_value_percentage = number_format(($class_a_value / $total_inventory_value) * 100, 2) . '%';
        $class_b_value_percentage = number_format(($class_b_value / $total_inventory_value) * 100, 2) . '%';
        $class_c_value_percentage = number_format(($class_c_value / $total_inventory_value) * 100, 2) . '%';

        // إعداد البيانات الوصفية
        $metadata = array(
            $this->language->get('entry_date') => $filter_date,
            $this->language->get('text_total_value') => $this->currency->format($total_inventory_value, $this->config->get('config_currency')),
            $this->language->get('text_class_a_items') => $class_a_items . ' (' . $class_a_items_percentage . ')',
            $this->language->get('text_class_a_value') => $this->currency->format($class_a_value, $this->config->get('config_currency')) . ' (' . $class_a_value_percentage . ')',
            $this->language->get('text_class_b_items') => $class_b_items . ' (' . $class_b_items_percentage . ')',
            $this->language->get('text_class_b_value') => $this->currency->format($class_b_value, $this->config->get('config_currency')) . ' (' . $class_b_value_percentage . ')',
            $this->language->get('text_class_c_items') => $class_c_items . ' (' . $class_c_items_percentage . ')',
            $this->language->get('text_class_c_value') => $this->currency->format($class_c_value, $this->config->get('config_currency')) . ' (' . $class_c_value_percentage . ')'
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

        // تصدير البيانات إلى CSV
        $filename = 'abc_analysis_value_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // إضافة BOM للتعامل مع الأحرف العربية
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // إضافة عنوان التقرير
        fputcsv($output, array($this->language->get('heading_value')));

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
     * عرض صفحة التحليل الرئيسية
     */
    protected function getList() {
        $data['user_token'] = $this->session->data['user_token'];

        $data['analyses'] = array();

        // تحليل ABC حسب القيمة
        $data['analyses'][] = array(
            'name' => $this->language->get('text_abc_by_value'),
            'description' => $this->language->get('text_abc_by_value_desc'),
            'href' => $this->url->link('inventory/abc_analysis/value', 'user_token=' . $this->session->data['user_token'])
        );

        // تحليل ABC حسب المبيعات (يمكن إضافته لاحقًا)
        $data['analyses'][] = array(
            'name' => $this->language->get('text_abc_by_sales'),
            'description' => $this->language->get('text_abc_by_sales_desc'),
            'href' => '#' // $this->url->link('inventory/abc_analysis/sales', 'user_token=' . $this->session->data['user_token'])
        );

        // تحليل ABC حسب الربحية (يمكن إضافته لاحقًا)
        $data['analyses'][] = array(
            'name' => $this->language->get('text_abc_by_profit'),
            'description' => $this->language->get('text_abc_by_profit_desc'),
            'href' => '#' // $this->url->link('inventory/abc_analysis/profit', 'user_token=' . $this->session->data['user_token'])
        );

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/abc_analysis', 'user_token=' . $this->session->data['user_token'])
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/abc_analysis', $data));
    }
}
