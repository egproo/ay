<?php
/**
 * AYM ERP - Supplier Performance Controller
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerSupplierPerformance extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('supplier/performance');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'], true)
        );

        $this->getList($data);
    }

    public function dashboard() {
        $this->load->language('supplier/performance');
        $this->load->model('supplier/performance');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Get performance overview
        $data['performance_overview'] = $this->model_supplier_performance->getPerformanceOverview();

        // Get top performing suppliers
        $data['top_suppliers'] = $this->model_supplier_performance->getTopSuppliers(10);

        // Get performance trends
        $data['performance_trends'] = $this->model_supplier_performance->getPerformanceTrends();

        // Get alerts
        $data['performance_alerts'] = $this->model_supplier_performance->getPerformanceAlerts();

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/performance_dashboard', $data));
    }

    public function view() {
        $this->load->language('supplier/performance');
        $this->load->model('supplier/performance');
        $this->load->model('supplier/supplier');

        if (isset($this->request->get['supplier_id'])) {
            $supplier_id = $this->request->get['supplier_id'];
        } else {
            $supplier_id = 0;
        }

        $supplier_info = $this->model_supplier_supplier->getSupplier($supplier_id);

        if ($supplier_info) {
            $this->document->setTitle($this->language->get('heading_title') . ' - ' . $supplier_info['name']);

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'], true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $supplier_info['name'],
                'href' => $this->url->link('supplier/performance/view', 'user_token=' . $this->session->data['user_token'] . '&supplier_id=' . $supplier_id, true)
            );

            // Get supplier performance data
            $data['supplier_info'] = $supplier_info;
            $data['performance_metrics'] = $this->model_supplier_performance->getSupplierMetrics($supplier_id);
            $data['delivery_performance'] = $this->model_supplier_performance->getDeliveryPerformance($supplier_id);
            $data['quality_metrics'] = $this->model_supplier_performance->getQualityMetrics($supplier_id);
            $data['cost_analysis'] = $this->model_supplier_performance->getCostAnalysis($supplier_id);
            $data['performance_history'] = $this->model_supplier_performance->getPerformanceHistory($supplier_id);
            $data['recent_orders'] = $this->model_supplier_performance->getRecentOrders($supplier_id, 10);

            $data['user_token'] = $this->session->data['user_token'];

            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('supplier/performance_view', $data));
        } else {
            $this->response->redirect($this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function evaluate() {
        $this->load->language('supplier/performance');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateEvaluation()) {
            $this->load->model('supplier/performance');

            $this->model_supplier_performance->addEvaluation($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getEvaluationForm();
    }

    public function report() {
        $this->load->language('supplier/performance');
        $this->load->model('supplier/performance');

        $filter_data = array();

        if (isset($this->request->get['filter_supplier_id'])) {
            $filter_data['filter_supplier_id'] = $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_date_start'])) {
            $filter_data['filter_date_start'] = $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_data['filter_date_end'] = $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_metric'])) {
            $filter_data['filter_metric'] = $this->request->get['filter_metric'];
        }

        $data['performance_report'] = $this->model_supplier_performance->getPerformanceReport($filter_data);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    protected function getList(&$data = array()) {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 's.name';
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

        $data['suppliers'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $this->load->model('supplier/performance');

        $supplier_total = $this->model_supplier_performance->getTotalSuppliers();

        $results = $this->model_supplier_performance->getSuppliers($filter_data);

        foreach ($results as $result) {
            $data['suppliers'][] = array(
                'supplier_id'        => $result['supplier_id'],
                'name'               => $result['name'],
                'overall_score'      => number_format($result['overall_score'], 1),
                'delivery_score'     => number_format($result['delivery_score'], 1),
                'quality_score'      => number_format($result['quality_score'], 1),
                'cost_score'         => number_format($result['cost_score'], 1),
                'total_orders'       => $result['total_orders'],
                'last_evaluation'    => $result['last_evaluation'] ? date($this->language->get('date_format_short'), strtotime($result['last_evaluation'])) : $this->language->get('text_never'),
                'view'               => $this->url->link('supplier/performance/view', 'user_token=' . $this->session->data['user_token'] . '&supplier_id=' . $result['supplier_id'] . $url, true),
                'evaluate'           => $this->url->link('supplier/performance/evaluate', 'user_token=' . $this->session->data['user_token'] . '&supplier_id=' . $result['supplier_id'] . $url, true)
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

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'] . '&sort=s.name' . $url, true);
        $data['sort_overall_score'] = $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'] . '&sort=overall_score' . $url, true);
        $data['sort_delivery_score'] = $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'] . '&sort=delivery_score' . $url, true);
        $data['sort_quality_score'] = $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'] . '&sort=quality_score' . $url, true);
        $data['sort_cost_score'] = $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'] . '&sort=cost_score' . $url, true);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $supplier_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($supplier_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($supplier_total - $this->config->get('config_limit_admin'))) ? $supplier_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $supplier_total, ceil($supplier_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['dashboard'] = $this->url->link('supplier/performance/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/performance_list', $data));
    }

    protected function getEvaluationForm() {
        $data['text_form'] = $this->language->get('text_evaluate');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->request->get['supplier_id'])) {
            $supplier_id = $this->request->get['supplier_id'];
        } else {
            $supplier_id = 0;
        }

        $this->load->model('supplier/supplier');
        $supplier_info = $this->model_supplier_supplier->getSupplier($supplier_id);

        if ($supplier_info) {
            $data['supplier_info'] = $supplier_info;

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'], true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_evaluate'),
                'href' => $this->url->link('supplier/performance/evaluate', 'user_token=' . $this->session->data['user_token'] . '&supplier_id=' . $supplier_id, true)
            );

            $data['action'] = $this->url->link('supplier/performance/evaluate', 'user_token=' . $this->session->data['user_token'] . '&supplier_id=' . $supplier_id, true);
            $data['cancel'] = $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'], true);

            $data['user_token'] = $this->session->data['user_token'];

            // Get evaluation criteria
            $this->load->model('supplier/performance');
            $data['evaluation_criteria'] = $this->model_supplier_performance->getEvaluationCriteria();

            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('supplier/performance_evaluate', $data));
        } else {
            $this->response->redirect($this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    protected function validateEvaluation() {
        if (!$this->user->hasPermission('modify', 'supplier/performance')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['supplier_id'])) {
            $this->error['warning'] = $this->language->get('error_supplier');
        }

        if (empty($this->request->post['evaluation_period'])) {
            $this->error['warning'] = $this->language->get('error_period');
        }

        if (empty($this->request->post['criteria_scores'])) {
            $this->error['warning'] = $this->language->get('error_scores');
        }

        return !$this->error;
    }
}
