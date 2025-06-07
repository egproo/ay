<?php
class ControllerSupplierEvaluation extends Controller {
    private $error = array();

    /**
     * عرض صفحة تقييم الموردين
     */
    public function index() {
        $this->load->language('supplier/evaluation');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/evaluation');

        $this->getList();
    }

    /**
     * إضافة تقييم مورد جديد
     */
    public function add() {
        $this->load->language('supplier/evaluation');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/evaluation');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $evaluation_id = $this->model_supplier_evaluation->addEvaluation($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_supplier_id'])) {
                $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
            }

            if (isset($this->request->get['filter_evaluator_id'])) {
                $url .= '&filter_evaluator_id=' . $this->request->get['filter_evaluator_id'];
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

            $this->response->redirect($this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * تعديل تقييم مورد
     */
    public function edit() {
        $this->load->language('supplier/evaluation');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/evaluation');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_supplier_evaluation->editEvaluation($this->request->get['evaluation_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_supplier_id'])) {
                $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
            }

            if (isset($this->request->get['filter_evaluator_id'])) {
                $url .= '&filter_evaluator_id=' . $this->request->get['filter_evaluator_id'];
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

            $this->response->redirect($this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * حذف تقييم مورد
     */
    public function delete() {
        $this->load->language('supplier/evaluation');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/evaluation');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $evaluation_id) {
                $this->model_supplier_evaluation->deleteEvaluation($evaluation_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_supplier_id'])) {
                $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
            }

            if (isset($this->request->get['filter_evaluator_id'])) {
                $url .= '&filter_evaluator_id=' . $this->request->get['filter_evaluator_id'];
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

            $this->response->redirect($this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * عرض قائمة تقييمات الموردين
     */
    protected function getList() {
        if (isset($this->request->get['filter_supplier_id'])) {
            $filter_supplier_id = $this->request->get['filter_supplier_id'];
        } else {
            $filter_supplier_id = '';
        }

        if (isset($this->request->get['filter_evaluator_id'])) {
            $filter_evaluator_id = $this->request->get['filter_evaluator_id'];
        } else {
            $filter_evaluator_id = '';
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
            $sort = 'se.evaluation_date';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_evaluator_id'])) {
            $url .= '&filter_evaluator_id=' . $this->request->get['filter_evaluator_id'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('supplier/evaluation/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('supplier/evaluation/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['evaluations'] = array();

        $filter_data = array(
            'filter_supplier_id'  => $filter_supplier_id,
            'filter_evaluator_id' => $filter_evaluator_id,
            'filter_date_start'   => $filter_date_start,
            'filter_date_end'     => $filter_date_end,
            'sort'                => $sort,
            'order'               => $order,
            'start'               => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'               => $this->config->get('config_limit_admin')
        );

        $evaluation_total = $this->model_supplier_evaluation->getTotalEvaluations($filter_data);

        $results = $this->model_supplier_evaluation->getEvaluations($filter_data);

        foreach ($results as $result) {
            $data['evaluations'][] = array(
                'evaluation_id'    => $result['evaluation_id'],
                'supplier_name'    => $result['supplier_name'],
                'evaluator_name'   => $result['evaluator_name'],
                'evaluation_date'  => date($this->language->get('date_format_short'), strtotime($result['evaluation_date'])),
                'quality_score'    => number_format($result['quality_score'], 2),
                'delivery_score'   => number_format($result['delivery_score'], 2),
                'price_score'      => number_format($result['price_score'], 2),
                'service_score'    => number_format($result['service_score'], 2),
                'overall_score'    => number_format($result['overall_score'], 2),
                'score_class'      => $this->getScoreClass($result['overall_score']),
                'edit'             => $this->url->link('supplier/evaluation/edit', 'user_token=' . $this->session->data['user_token'] . '&evaluation_id=' . $result['evaluation_id'] . $url, true)
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

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_supplier'] = $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . '&sort=supplier_name' . $url, true);
        $data['sort_evaluator'] = $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . '&sort=evaluator_name' . $url, true);
        $data['sort_date'] = $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . '&sort=se.evaluation_date' . $url, true);
        $data['sort_overall_score'] = $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . '&sort=se.overall_score' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_evaluator_id'])) {
            $url .= '&filter_evaluator_id=' . $this->request->get['filter_evaluator_id'];
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
        $pagination->total = $evaluation_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($evaluation_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($evaluation_total - $this->config->get('config_limit_admin'))) ? $evaluation_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $evaluation_total, ceil($evaluation_total / $this->config->get('config_limit_admin')));

        $data['filter_supplier_id'] = $filter_supplier_id;
        $data['filter_evaluator_id'] = $filter_evaluator_id;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        $data['sort'] = $sort;
        $data['order'] = $order;

        // قائمة الموردين للفلترة
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        // قائمة المقيمين للفلترة
        $this->load->model('user/user');
        $data['evaluators'] = $this->model_user_user->getUsers();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/evaluation_list', $data));
    }

    /**
     * عرض نموذج إضافة/تعديل تقييم مورد
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['evaluation_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['supplier_id'])) {
            $data['error_supplier_id'] = $this->error['supplier_id'];
        } else {
            $data['error_supplier_id'] = '';
        }

        if (isset($this->error['evaluation_date'])) {
            $data['error_evaluation_date'] = $this->error['evaluation_date'];
        } else {
            $data['error_evaluation_date'] = '';
        }

        if (isset($this->error['quality_score'])) {
            $data['error_quality_score'] = $this->error['quality_score'];
        } else {
            $data['error_quality_score'] = '';
        }

        if (isset($this->error['delivery_score'])) {
            $data['error_delivery_score'] = $this->error['delivery_score'];
        } else {
            $data['error_delivery_score'] = '';
        }

        if (isset($this->error['price_score'])) {
            $data['error_price_score'] = $this->error['price_score'];
        } else {
            $data['error_price_score'] = '';
        }

        if (isset($this->error['service_score'])) {
            $data['error_service_score'] = $this->error['service_score'];
        } else {
            $data['error_service_score'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_evaluator_id'])) {
            $url .= '&filter_evaluator_id=' . $this->request->get['filter_evaluator_id'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['evaluation_id'])) {
            $data['action'] = $this->url->link('supplier/evaluation/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('supplier/evaluation/edit', 'user_token=' . $this->session->data['user_token'] . '&evaluation_id=' . $this->request->get['evaluation_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['evaluation_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $evaluation_info = $this->model_supplier_evaluation->getEvaluation($this->request->get['evaluation_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['supplier_id'])) {
            $data['supplier_id'] = $this->request->post['supplier_id'];
        } elseif (!empty($evaluation_info)) {
            $data['supplier_id'] = $evaluation_info['supplier_id'];
        } else {
            $data['supplier_id'] = '';
        }

        if (isset($this->request->post['evaluation_date'])) {
            $data['evaluation_date'] = $this->request->post['evaluation_date'];
        } elseif (!empty($evaluation_info)) {
            $data['evaluation_date'] = $evaluation_info['evaluation_date'];
        } else {
            $data['evaluation_date'] = date('Y-m-d');
        }

        if (isset($this->request->post['quality_score'])) {
            $data['quality_score'] = $this->request->post['quality_score'];
        } elseif (!empty($evaluation_info)) {
            $data['quality_score'] = $evaluation_info['quality_score'];
        } else {
            $data['quality_score'] = '';
        }

        if (isset($this->request->post['delivery_score'])) {
            $data['delivery_score'] = $this->request->post['delivery_score'];
        } elseif (!empty($evaluation_info)) {
            $data['delivery_score'] = $evaluation_info['delivery_score'];
        } else {
            $data['delivery_score'] = '';
        }

        if (isset($this->request->post['price_score'])) {
            $data['price_score'] = $this->request->post['price_score'];
        } elseif (!empty($evaluation_info)) {
            $data['price_score'] = $evaluation_info['price_score'];
        } else {
            $data['price_score'] = '';
        }

        if (isset($this->request->post['service_score'])) {
            $data['service_score'] = $this->request->post['service_score'];
        } elseif (!empty($evaluation_info)) {
            $data['service_score'] = $evaluation_info['service_score'];
        } else {
            $data['service_score'] = '';
        }

        if (isset($this->request->post['comments'])) {
            $data['comments'] = $this->request->post['comments'];
        } elseif (!empty($evaluation_info)) {
            $data['comments'] = $evaluation_info['comments'];
        } else {
            $data['comments'] = '';
        }

        // قائمة الموردين
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/evaluation_form', $data));
    }

    /**
     * التحقق من صحة بيانات النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'supplier/evaluation')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['supplier_id'])) {
            $this->error['supplier_id'] = $this->language->get('error_supplier');
        }

        if (empty($this->request->post['evaluation_date'])) {
            $this->error['evaluation_date'] = $this->language->get('error_evaluation_date');
        }

        if (empty($this->request->post['quality_score']) || $this->request->post['quality_score'] < 0 || $this->request->post['quality_score'] > 5) {
            $this->error['quality_score'] = $this->language->get('error_quality_score');
        }

        if (empty($this->request->post['delivery_score']) || $this->request->post['delivery_score'] < 0 || $this->request->post['delivery_score'] > 5) {
            $this->error['delivery_score'] = $this->language->get('error_delivery_score');
        }

        if (empty($this->request->post['price_score']) || $this->request->post['price_score'] < 0 || $this->request->post['price_score'] > 5) {
            $this->error['price_score'] = $this->language->get('error_price_score');
        }

        if (empty($this->request->post['service_score']) || $this->request->post['service_score'] < 0 || $this->request->post['service_score'] > 5) {
            $this->error['service_score'] = $this->language->get('error_service_score');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة عملية الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'supplier/evaluation')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * الحصول على فئة CSS للنتيجة
     */
    private function getScoreClass($score) {
        if ($score >= 4.5) {
            return 'success';
        } elseif ($score >= 3.5) {
            return 'info';
        } elseif ($score >= 2.5) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * عرض تقرير تقييم مورد
     */
    public function report() {
        $this->load->language('supplier/evaluation');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('supplier/evaluation');

        if (isset($this->request->get['supplier_id'])) {
            $supplier_id = $this->request->get['supplier_id'];
        } else {
            $supplier_id = 0;
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_report'),
            'href' => $this->url->link('supplier/evaluation/report', 'user_token=' . $this->session->data['user_token'] . '&supplier_id=' . $supplier_id, true)
        );

        if ($supplier_id) {
            $data['supplier_report'] = $this->model_supplier_evaluation->getSupplierEvaluationReport($supplier_id);
            $data['evaluation_history'] = $this->model_supplier_evaluation->getSupplierEvaluationHistory($supplier_id);
        } else {
            $data['supplier_report'] = array();
            $data['evaluation_history'] = array();
        }

        $data['user_token'] = $this->session->data['user_token'];

        // قائمة الموردين
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();
        $data['supplier_id'] = $supplier_id;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/evaluation_report', $data));
    }
}
